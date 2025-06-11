<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Illuminate\Http\UploadedFile;
use App\Jobs\UploadToGoogleDrive;
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
     * @return \Illuminate\Http\JsonResponse
     * @throws UploadMissingFileException
     */
    public function store(Request $request)
    {
        Log::debug('Chunk upload request received.', [
            'headers' => $request->headers->all(),
            'method' => $request->method(),
            'resumable' => $request->all()
        ]);

        // Create the file receiver
        $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

        // Check if the upload is successful
        if (!$receiver->isUploaded()) {
            Log::error('FileReceiver initialization failed or file not uploaded.');
            return response()->json(['error' => 'No file uploaded or receiver init failed.'], 400);
        }

        // Receive the file
        try {
            $save = $receiver->receive();
        } catch (\Exception $e) {
            Log::error('Exception during upload handling.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to process upload request.'], 500);
        }

        // Check if the upload has finished
        if ($save->isFinished()) {
            Log::info('Upload finished, saving the complete file.');
            return $this->saveFile($save->getFile());
        }

        // We are in chunk mode, lets send the current progress
        Log::debug('Chunk received successfully.');
        return response()->json(['status' => true, 'message' => 'Chunk received successfully.']);
    }

    /**
     * Saves the file when all chunks have been uploaded.
     *
     * @param UploadedFile $file
     * @return \Illuminate\Http\JsonResponse
     */
    protected function saveFile(UploadedFile $file)
    {
        $fileName = $this->createFilename($file);
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Build the file path
        $filePath = "public/uploads/";
        $finalPath = storage_path("app/" . $filePath);

        // Ensure the upload directory exists
        if (!Storage::disk('local')->exists($filePath)) {
            Storage::disk('local')->makeDirectory($filePath);
        }

        // Move the file
        try {
            $file->move($finalPath, $fileName);
        } catch (\Exception $e) {
            Log::error('Failed to move uploaded file.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to save uploaded file.'], 500);
        }

        // Create FileUpload record
        try {
            $fileUpload = FileUpload::create([
                'email' => Auth::user()->email,
                'filename' => $fileName,
                'original_filename' => $originalFilename,
                'google_drive_file_id' => '',
                'validation_method' => 'auth',
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
            ]);

            // Dispatch upload job
            UploadToGoogleDrive::dispatch($fileUpload);

            return response()->json([
                'file_upload_id' => $fileUpload->id,
                'path' => $filePath . $fileName,
                'name' => $fileName,
                'original_filename' => $originalFilename,
                'mime_type' => $mimeType,
                'size' => $fileSize,
                'status' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create FileUpload record.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to record file upload.'], 500);
        }
    }

    /**
     * Create unique filename for uploaded file.
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function createFilename(UploadedFile $file)
    {
        $extension = $file->getClientOriginalExtension();
        return Str::random(40) . '.' . $extension;
    }
}
