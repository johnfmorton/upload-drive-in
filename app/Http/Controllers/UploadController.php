<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Illuminate\Http\UploadedFile;
use App\Jobs\UploadToGoogleDrive; // Assuming this job exists
use App\Models\FileUpload;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    /**
     * Handles the file upload using chunks.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws UploadMissingFileException
     */
    public function store(Request $request)
    {
        // Create the file receiver
        $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

        // Check if the upload is success, throw exception or return response
        if ($receiver->isUploaded() === false) {
            throw new UploadMissingFileException();
        }

        // Receive the file
        $save = $receiver->receive();

        // Check if the upload has finished (all chunks received)
        if ($save->isFinished()) {
            return $this->saveFile($save->getFile());
        }

        // We are in chunk mode, send the current progress
        $handler = $save->handler();
        return response()->json([
            "done" => $handler->getPercentageDone(),
            'status' => true
        ]);
    }

    /**
     * Saves the final file.
     *
     * @param UploadedFile $file
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function saveFile(UploadedFile $file)
    {
        $fileName = $this->createFilename($file);
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Build the file path
        $filePath = "public/uploads/"; // Your target directory relative to storage/app
        $finalPath = storage_path("app/" . $filePath);

        // Move the file
        $file->move($finalPath, $fileName);

        // Get authenticated user's email
        $userEmail = Auth::user() ? Auth::user()->email : null;
        if (!$userEmail) {
             // Handle the case where the user is not authenticated if necessary
             // For now, let's return an error or log it
             Log::error('User not authenticated during file save.');
             return response()->json(['error' => 'User not authenticated', 'status' => false], 401);
        }

        // Create FileUpload model instance
        $fileUpload = FileUpload::create([
            'email' => $userEmail,
            'filename' => $fileName,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'validation_method' => 'auth', // Indicate authentication method
            // 'message' => null, // We'll handle message association later
        ]);

        // Dispatch the job with the FileUpload model instance
        UploadToGoogleDrive::dispatch($fileUpload);

        return response()->json([
            'file_upload_id' => $fileUpload->id,
            'path' => $filePath . $fileName,
            'name' => $fileName,
            'original_name' => $originalFilename,
            'mime_type' => $mimeType,
            'size' => $fileSize,
            'status' => true
        ]);
    }

    /**
     * Create unique filename for uploaded file
     * @param UploadedFile $file
     * @return string
     */
    protected function createFilename(UploadedFile $file)
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::random(40) . '.' . $extension;
        return $filename;
    }
}
