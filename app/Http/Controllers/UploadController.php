<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Illuminate\Http\UploadedFile;
use App\Jobs\UploadToGoogleDrive; // Assuming this job exists
use Illuminate\Support\Str;

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
        // Build the file path
        $filePath = "public/uploads/"; // Your target directory relative to storage/app
        $finalPath = storage_path("app/" . $filePath);

        // Move the file
        $file->move($finalPath, $fileName);

        // Optionally, dispatch your job to upload to Google Drive
        // Assuming your job takes the file path relative to the storage directory
        // You might need to adjust this based on your job's implementation
        $storagePath = $filePath . $fileName;
        UploadToGoogleDrive::dispatch($storagePath, $fileName); // Adjust arguments if needed

        return response()->json([
            'path' => $filePath,
            'name' => $fileName,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => true // Indicate success
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
