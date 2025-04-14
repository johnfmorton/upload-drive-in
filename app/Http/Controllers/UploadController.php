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
use Illuminate\Support\Facades\Validator;
use Pion\Laravel\ChunkUpload\Save\ChunkSave;
use App\Events\FileUploaded; // <-- Add Event import

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
        Log::debug('Chunk upload request received.', [
            'headers' => $request->headers->all(),
            'method' => $request->method(),
            'resumable' => $request->all()
        ]);

        // Determine handler using the factory
        $handlerClass = HandlerFactory::classFromRequest($request);
        Log::debug('Using factory-determined handler.', ['handler' => $handlerClass]);

        // --- Explicitly use ContentRangeUploadHandler ---
        /*
        $handlerClass = ContentRangeUploadHandler::class;
        Log::debug('Forcing ContentRangeUploadHandler.');
        */

        // Instantiate the FileReceiver, passing the determined handler class name
        $receiver = new FileReceiver('file', $request, $handlerClass);

        // Check if receiver initialized successfully
        if (!$receiver->isUploaded()) {
            Log::error('FileReceiver initialization failed or file not uploaded.');
            return response()->json(['error' => 'No file uploaded or receiver init failed.'], 400);
        }

        Log::debug('Receiver created, attempting to receive/handle request.', ['handler' => $handlerClass]);

        // Receive the file or chunk
        try {
            $save = $receiver->receive(); // $save is ChunkSave or SingleSave
        } catch (\Exception $e) {
            Log::error('Exception during upload handling.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'handler_class' => $handlerClass, // Use the determined class
            ]);
            return response()->json(['error' => 'Failed to process upload request.'], 500);
        }

        // Check if the upload is finished (SingleSave will also pass isFinished == true)
        if ($save->isFinished()) {
            Log::info('Upload finished, attempting to save the complete file.');
            return $this->saveFile($save->getFile());
        }

        // If upload is not finished (it's a ChunkSave instance),
        // let the handler handle the response for the received chunk.
        // The handler instance is not directly accessible from the receiver in v1.5.6,
        // but the AbstractSave object ($save) has a reference to its handler.
        // $handler = $save->handler(); // Get handler from the Save object - Not needed for simple response
        // Log::debug('Chunk received successfully, returning handler chunk response.');
        // return $handler->handleChunkResponse(); // Method doesn't exist on DropZone handler

        // Instead, just return a simple success response for the chunk
        Log::debug('Chunk received successfully, returning generic success response.');
        return response()->json(['status' => true, 'message' => 'Chunk received successfully.'], 200);
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
        Log::info('saveFile method entered.', [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
            'temp_path' => $file->getRealPath() // Path where pion assembled the file
        ]);

        $fileName = $this->createFilename($file);
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Build the file path
        $filePath = "public/uploads/"; // Your target directory relative to storage/app
        $finalPath = storage_path("app/" . $filePath);

        Log::debug('Generated unique filename and final path.', [
            'new_filename' => $fileName,
            'final_storage_path' => $finalPath
        ]);

        // Check if final directory exists
        if (!Storage::disk('local')->exists($filePath)) { // Check relative to storage/app
             Log::info('Final storage directory does not exist, attempting to create.', ['directory' => $filePath]);
             try {
                Storage::disk('local')->makeDirectory($filePath);
             } catch (\Exception $e) {
                 Log::error('Failed to create final storage directory.', [
                     'directory' => $filePath,
                     'error' => $e->getMessage()
                 ]);
                 return response()->json(['error' => 'Could not create storage directory.'], 500);
             }
        }

        Log::debug('Attempting to move file to final destination.', [
            'source' => $file->getRealPath(),
            'destination_dir' => $finalPath,
            'destination_filename' => $fileName
        ]);

        // Move the file
        try {
            $moveResult = $file->move($finalPath, $fileName);
            Log::info('File moved successfully.', ['move_result_path' => $moveResult->getPathname()]);
        } catch (\Exception $e) {
             Log::error('Failed to move uploaded file to final destination.', [
                 'source' => $file->getRealPath(),
                 'destination' => $finalPath . $fileName,
                 'error' => $e->getMessage(),
                 'trace' => $e->getTraceAsString()
             ]);
             // Note: pion/laravel-chunk-upload might leave the temp file here.
             // Consider adding cleanup logic if needed.
             return response()->json(['error' => 'Failed to save uploaded file.'], 500);
        }


        // Get authenticated user's email
        $user = Auth::user();
        $userEmail = $user ? $user->email : null;
        if (!$userEmail) {
             Log::error('User not authenticated during file save.', [
                 'file_name' => $fileName
             ]);
             // Clean up the moved file if auth fails? Depends on requirements.
             // Storage::disk('local')->delete($filePath . $fileName);
             return response()->json(['error' => 'User not authenticated'], 401);
        }

        Log::debug('User authenticated, creating FileUpload record.', [
            'email' => $userEmail,
            'filename' => $fileName
        ]);

        // Create FileUpload model instance
        try {
            $fileUpload = FileUpload::create([
                'email' => $userEmail,
                'filename' => $fileName,
                'original_filename' => $originalFilename,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'validation_method' => 'auth',
            ]);
            Log::info('FileUpload record created successfully.', ['file_upload_id' => $fileUpload->id]);
        } catch (\Exception $e) {
            Log::error('Failed to create FileUpload database record.', [
                'filename' => $fileName,
                'email' => $userEmail,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Clean up the moved file if DB save fails
             Storage::disk('local')->delete($filePath . $fileName);
             return response()->json(['error' => 'Failed to record file upload.'], 500);
        }


        // Dispatch the job with the FileUpload model instance
        Log::debug('Dispatching UploadToGoogleDrive job.', ['file_upload_id' => $fileUpload->id]);
        try {
            UploadToGoogleDrive::dispatch($fileUpload);
            Log::info('UploadToGoogleDrive job dispatched successfully.', ['file_upload_id' => $fileUpload->id]);

            // --> Dispatch the FileUploaded event HERE <--
            try {
                // <-- Log before dispatch -->
                Log::debug('Preparing to dispatch FileUploaded event with IDs.', [
                    'fileUploadId' => $fileUpload->id,
                    'userId' => $user->id
                ]);
                // Pass IDs instead of models
                FileUploaded::dispatch($fileUpload->id, $user->id);
                Log::info('FileUploaded event dispatched successfully.', ['file_upload_id' => $fileUpload->id, 'user_id' => $user->id]);
            } catch (\Exception $e) {
                Log::error('Failed to dispatch FileUploaded event.', [
                    'file_upload_id' => $fileUpload->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Decide if this failure should affect the user response
            }

        } catch (\Exception $e) {
             Log::error('Failed to dispatch UploadToGoogleDrive job.', [
                 'file_upload_id' => $fileUpload->id,
                 'error' => $e->getMessage(),
                 'trace' => $e->getTraceAsString()
             ]);
             // Note: The file is saved locally, and DB record exists.
             // The ProcessPendingUploads command should pick it up later.
             // So, we might not want to return a hard error to the user here.
             // However, it's good to log that immediate dispatch failed.
        }


        Log::info('saveFile method completed successfully.', ['file_upload_id' => $fileUpload->id]);
        return response()->json([
            'file_upload_id' => $fileUpload->id,
            'path' => $filePath . $fileName,
            'name' => $fileName,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'size' => $fileSize,
            'status' => true,
            // Add any other data needed by the frontend modal
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
        // Use a more robust method for filename generation if needed
        $filename = Str::random(40) . '.' . $extension;
        Log::debug('Created filename.', ['original' => $file->getClientOriginalName(), 'new' => $filename]);
        return $filename;
    }

    /**
     * Associates a message with one or more FileUpload records.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function associateMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:5000',
            'file_upload_ids' => 'required|array',
            'file_upload_ids.*' => 'required|integer|exists:file_uploads,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $message = $request->input('message');
        $fileUploadIds = $request->input('file_upload_ids');

        try {
            // Ensure the files belong to the authenticated user for security
            $updatedCount = FileUpload::whereIn('id', $fileUploadIds)
                ->where('email', Auth::user()->email) // Check ownership
                ->update(['message' => $message]);

            if ($updatedCount == 0) {
                // This could happen if the IDs were valid but didn't belong to the user
                Log::warning('Associate message: No files updated, possibly due to ownership mismatch.', [
                    'user_id' => Auth::id(),
                    'requested_ids' => $fileUploadIds
                ]);
                // Return a success response anyway, as the request was valid,
                // but maybe log or handle this case specifically if needed.
            }

            return response()->json(['success' => true, 'message' => 'Message associated with ' . $updatedCount . ' file(s).']);

        } catch (\Exception $e) {
            Log::error('Error associating message with uploads:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to associate message.'], 500);
        }
    }
}
