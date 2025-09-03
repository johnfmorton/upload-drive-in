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

        // Ensure client has a relationship with a company user
        if ($user->isClient() && $user->companyUsers()->count() === 0) {
            $adminUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)
                ->whereHas('googleDriveToken') // Ensure admin has Google Drive connected
                ->first();

            if ($adminUser) {
                $this->clientUserService->associateWithCompanyUser($user, $adminUser);

                Log::info('Created fallback relationship with admin user for client visiting upload page', [
                    'client_user_id' => $user->id,
                    'client_email' => $user->email,
                    'admin_user_id' => $adminUser->id,
                    'admin_email' => $adminUser->email
                ]);
            }
        }

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

        // If still no company user, create a relationship with an admin user as fallback
        if (!$companyUser) {
            $adminUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)
                ->whereHas('googleDriveToken') // Ensure admin has Google Drive connected
                ->first();

            if ($adminUser) {
                // Create the relationship using the service
                $this->clientUserService->associateWithCompanyUser($user, $adminUser);
                $companyUser = $adminUser;

                Log::info('Created fallback relationship with admin user for client upload', [
                    'client_user_id' => $user->id,
                    'client_email' => $user->email,
                    'admin_user_id' => $adminUser->id,
                    'admin_email' => $adminUser->email
                ]);
            }
        }

        if (!$companyUser) {
            Log::error('No company user found for upload', [
                'client_user_id' => $user->id,
                'selected_company_user_id' => $request->input('company_user_id'),
                'has_relationships' => $user->companyUsers()->count() > 0
            ]);
            return response()->json([
                'error' => __('messages.no_valid_upload_destination')
            ], 400);
        }

        // Log a warning if the company user doesn't have Google Drive connected
        // but allow the upload to proceed - the job will handle fallbacks
        if (!$companyUser->hasGoogleDriveConnected()) {
            Log::warning('Company user does not have valid Google Drive connection, upload will rely on job fallbacks', [
                'client_user_id' => $user->id,
                'company_user_id' => $companyUser->id,
                'company_user_email' => $companyUser->email
            ]);
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
                'email' => Auth::user()->email,
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
            'file_upload_ids' => 'required|array',
            'file_upload_ids.*' => 'required|exists:file_uploads,id',
            'message' => 'nullable|string|max:1000',
        ]);

        FileUpload::whereIn('id', $validated['file_upload_ids'])
            ->where('client_user_id', Auth::id())
            ->update(['message' => $validated['message']]);

        // Dispatch batch completion event to trigger emails when message was associated
        try {
            \Log::info('Dispatching BatchUploadComplete event after message association (client).', [
                'user_id' => Auth::id(),
                'file_upload_ids' => $validated['file_upload_ids'],
            ]);
            \App\Events\BatchUploadComplete::dispatch($validated['file_upload_ids'], Auth::id());
        } catch (\Exception $e) {
            \Log::error('Failed to dispatch BatchUploadComplete event after message association (client).', [
                'user_id' => Auth::id(),
                'file_upload_ids' => $validated['file_upload_ids'],
                'error' => $e->getMessage(),
            ]);
        }

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
            'file_upload_ids' => 'required|array',
            'file_upload_ids.*' => 'required|exists:file_uploads,id',
        ]);

        // Optionally verify ownership; uploads are already queued on creation
        FileUpload::whereIn('id', $validated['file_upload_ids'])
            ->where('client_user_id', Auth::id())
            ->count();

        // Dispatch batch completion event to trigger emails (client confirmation + recipient notification)
        try {
            \App\Events\BatchUploadComplete::dispatch($validated['file_upload_ids'], Auth::id());
        } catch (\Exception $e) {
            Log::error('Failed to dispatch BatchUploadComplete event from client batchComplete.', [
                'user_id' => Auth::id(),
                'file_upload_ids' => $validated['file_upload_ids'],
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
