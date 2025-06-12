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
use Illuminate\Support\Facades\Validator;
use App\Events\BatchUploadComplete;
use App\Models\User;
use App\Services\GoogleDriveService;
use App\Services\ClientUserService;

class UploadController extends Controller
{
    protected GoogleDriveService $driveService;
    protected ClientUserService $clientUserService;

    public function __construct(GoogleDriveService $driveService, ClientUserService $clientUserService)
    {
        $this->driveService = $driveService;
        $this->clientUserService = $clientUserService;
    }

    /**
     * Show the upload form.
     */
    public function show()
    {
        $user = Auth::user();
        return view('client.upload-page', compact('user'));
    }

    /**
     * Handles the file upload using chunks.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws UploadMissingFileException
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Get the company user who should receive this upload
        $companyUser = null;
        if ($request->has('company_user_id')) {
            // If a specific company user was selected
            $companyUser = $user->companyUsers()
                ->where('users.id', $request->input('company_user_id'))
                ->first();
        }

        if (!$companyUser) {
            // Fall back to primary company user if none selected or selection invalid
            $companyUser = $user->primaryCompanyUser();
        }

        if (!$companyUser || !$companyUser->hasGoogleDriveConnected()) {
            Log::error('No valid company user with Google Drive connection found for upload', [
                'client_user_id' => $user->id,
                'selected_company_user_id' => $request->input('company_user_id')
            ]);
            return response()->json([
                'error' => __('messages.no_valid_upload_destination')
            ], 400);
        }

        // Create the file receiver
        $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

        // Check if the upload is successful
        if (!$receiver->isUploaded()) {
            Log::error('FileReceiver initialization failed or file not uploaded.');
            return response()->json([
                'error' => __('messages.no_file_uploaded')
            ], 400);
        }

        // Receive the file
        try {
            $save = $receiver->receive();
        } catch (\Exception $e) {
            Log::error('Exception during upload handling.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => __('messages.failed_to_process_upload')
            ], 500);
        }

        // Check if the upload has finished
        if ($save->isFinished()) {
            Log::info('Upload finished, saving the complete file.');
            return $this->saveFile($save->getFile(), $companyUser, $request);
        }

        // We are in chunk mode, lets send the current progress
        Log::debug('Chunk received successfully.');
        return response()->json([
            'status' => true,
            'message' => __('messages.chunk_received_successfully')
        ]);
    }

    /**
     * Saves the file when all chunks have been uploaded.
     *
     * @param UploadedFile $file
     * @param User $companyUser
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function saveFile(UploadedFile $file, User $companyUser, Request $request)
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
            return response()->json([
                'error' => __('messages.failed_to_save_uploaded_file')
            ], 500);
        }

        // Create FileUpload record
        try {
            $fileUpload = FileUpload::create([
                'client_user_id' => Auth::user()->id,
                'company_user_id' => $companyUser->id,
                'filename' => $fileName,
                'original_filename' => $originalFilename,
                'google_drive_file_id' => '',
                'validation_method' => 'auth',
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'chunk_size' => $request->input('chunk_size'),
                'total_chunks' => $request->input('total_chunks'),
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
            return response()->json([
                'error' => __('messages.failed_to_record_file_upload')
            ], 500);
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

    /**
     * Associates a message with one or more FileUpload records.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function associateMessage(Request $request)
    {
        $validated = $request->validate([
            'upload_ids' => 'required|array',
            'upload_ids.*' => 'required|exists:file_uploads,id',
            'message' => 'nullable|string|max:1000',
        ]);

        FileUpload::whereIn('id', $validated['upload_ids'])
            ->where('client_user_id', Auth::id())
            ->update(['message' => $validated['message']]);

        return response()->json(['success' => true]);
    }

    /**
     * Handles the completion of a batch upload (called from frontend).
     * Dispatches an event to trigger batch notifications.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchComplete(Request $request)
    {
        $validated = $request->validate([
            'upload_ids' => 'required|array',
            'upload_ids.*' => 'required|exists:file_uploads,id',
        ]);

        $uploads = FileUpload::whereIn('id', $validated['upload_ids'])
            ->where('client_user_id', Auth::id())
            ->get();

        foreach ($uploads as $upload) {
            // Get the company user associated with this upload
            $companyUser = User::find($upload->company_user_id);

            if ($companyUser && $companyUser->hasGoogleDriveConnected()) {
                // Use the company user's Google Drive token
                $this->driveService->uploadFile($upload, $companyUser);
            } else {
                Log::error('Failed to find valid company user for upload', [
                    'upload_id' => $upload->id,
                    'company_user_id' => $upload->company_user_id
                ]);
            }
        }

        return response()->json(['success' => true]);
    }
}
