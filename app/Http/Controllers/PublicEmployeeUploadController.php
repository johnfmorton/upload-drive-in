<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\GoogleDriveManager;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Jobs\UploadToGoogleDrive;
use App\Models\FileUpload;

class PublicEmployeeUploadController extends Controller
{
    protected GoogleDriveManager $drive_manager;

    /**
     * Inject the GoogleDriveManager.
     */
    public function __construct(GoogleDriveManager $drive_manager)
    {
        $this->drive_manager = $drive_manager;
    }

    /**
     * Show the public upload page for an employee.
     *
     * @param  Request  $request
     * @param  string   $username
     * @return \Illuminate\View\View
     */
    public function show(Request $request, string $username)
    {
        $user = User::where('username', $username)->firstOrFail();

        $current_folder_id = $user->google_drive_root_folder_id
            ?? config('cloud-storage.providers.google-drive.root_folder_id');

        $current_folder_name = $this->drive_manager->getFolderName(
            $user,
            $current_folder_id
        );

        return view('public-employee.upload-page', compact(
            'username',
            'current_folder_id',
            'current_folder_name'
        ));
    }

    /**
     * Handle public file uploads for an employee.
     *
     * @param  Request  $request
     * @param  string   $username
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upload(Request $request, string $username)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'email' => 'required|email',
            'message' => 'nullable|string|max:1000',
        ]);

        $employee = User::where('username', $username)->firstOrFail();
        
        if (!$employee->isEmployee()) {
            abort(404, 'Employee not found');
        }

        $clientEmail = $request->input('email');
        $message = $request->input('message');

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
                \Illuminate\Support\Facades\Storage::disk('public')->putFileAs('uploads', $file, $filename);

                $upload = \App\Models\FileUpload::create([
                    'email' => $clientEmail,
                    'filename' => $filename,
                    'original_filename' => $originalName,
                    'google_drive_file_id' => '',
                    'message' => $message,
                    'validation_method' => 'employee_public',
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by_user_id' => $employee->id, // Track which employee this upload belongs to
                ]);

                \App\Jobs\UploadToGoogleDrive::dispatch($upload);
            }
        }

        return redirect()->route(
            'public.employee.upload.show',
            ['username' => $username]
        )
        ->with('success', __('messages.files_uploaded_successfully'));
    }

    /**
     * Show the public upload page for an employee or admin by name (extracted from email).
     *
     * @param  string   $name
     * @return \Illuminate\View\View
     */
    public function showByName(string $name)
    {
        // Find employee or admin by extracting name from email
        // We need to escape special characters for the LIKE query
        $escapedName = str_replace(['%', '_'], ['\%', '\_'], $name);
        $employee = User::where('email', 'LIKE', $escapedName . '@%')
            ->whereIn('role', [\App\Enums\UserRole::EMPLOYEE, \App\Enums\UserRole::ADMIN])
            ->first();

        if (!$employee) {
            abort(404, 'User not found');
        }

        // Check if user is authenticated
        if (!\Illuminate\Support\Facades\Auth::check()) {
            // Store the intended URL in session for redirect after authentication
            session(['intended_url' => request()->url()]);
            
            // Show email validation form for guests
            return view('public-employee.email-validation', compact('name', 'employee'));
        }

        // Check if employee has Google Drive connected
        $hasGoogleDriveConnected = $employee->hasGoogleDriveConnected();

        return view('public-employee.upload-by-name', compact('name', 'employee', 'hasGoogleDriveConnected'));
    }

    /**
     * Handle public file uploads for an employee or admin by name.
     *
     * @param  Request  $request
     * @param  string   $name
     * @return \Illuminate\Http\RedirectResponse
     */
    public function uploadByName(Request $request, string $name)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'email' => 'required|email',
            'message' => 'nullable|string|max:1000',
        ]);

        // Find employee or admin by extracting name from email
        // We need to escape special characters for the LIKE query
        $escapedName = str_replace(['%', '_'], ['\%', '\_'], $name);
        $employee = User::where('email', 'LIKE', $escapedName . '@%')
            ->whereIn('role', [\App\Enums\UserRole::EMPLOYEE, \App\Enums\UserRole::ADMIN])
            ->first();

        if (!$employee) {
            abort(404, 'User not found');
        }

        $clientEmail = $request->input('email');
        $message = $request->input('message');

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
                \Illuminate\Support\Facades\Storage::disk('public')->putFileAs('uploads', $file, $filename);

                $upload = \App\Models\FileUpload::create([
                    'email' => $clientEmail,
                    'filename' => $filename,
                    'original_filename' => $originalName,
                    'google_drive_file_id' => '',
                    'message' => $message,
                    'validation_method' => 'employee_public',
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by_user_id' => $employee->id, // Track which employee this upload belongs to
                ]);

                \App\Jobs\UploadToGoogleDrive::dispatch($upload);
            }
        }

        return redirect()->route('upload.employee', ['name' => $name])
            ->with('success', __('messages.files_uploaded_successfully'));
    }

    /**
     * Handles the chunked file upload for an employee or admin by name.
     *
     * @param  Request  $request
     * @param  string   $name
     * @return \Illuminate\Http\JsonResponse
     */
    public function chunkUpload(Request $request, string $name)
    {
        // Find employee or admin by extracting name from email
        $escapedName = str_replace(['%', '_'], ['\%', '\_'], $name);
        $employee = User::where('email', 'LIKE', $escapedName . '@%')
            ->whereIn('role', [\App\Enums\UserRole::EMPLOYEE, \App\Enums\UserRole::ADMIN])
            ->first();

        if (!$employee) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Create the file receiver
        $receiver = new \Pion\Laravel\ChunkUpload\Receiver\FileReceiver(
            'file', 
            $request, 
            \Pion\Laravel\ChunkUpload\Handler\HandlerFactory::classFromRequest($request)
        );

        // Check if the upload is successful
        if (!$receiver->isUploaded()) {
            \Illuminate\Support\Facades\Log::error('FileReceiver initialization failed or file not uploaded.');
            return response()->json([
                'error' => __('messages.no_file_uploaded')
            ], 400);
        }

        // Receive the file
        try {
            $save = $receiver->receive();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Exception during upload handling.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => __('messages.failed_to_process_upload')
            ], 500);
        }

        // Check if the upload has finished
        if ($save->isFinished()) {
            \Illuminate\Support\Facades\Log::info('Upload finished, saving the complete file.');
            return $this->saveChunkedFile($save->getFile(), $employee, $request);
        }

        // We are in chunk mode, lets send the current progress
        \Illuminate\Support\Facades\Log::debug('Chunk received successfully.');
        return response()->json([
            'status' => true,
            'message' => __('messages.chunk_received_successfully')
        ]);
    }

    /**
     * Saves the file when all chunks have been uploaded.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  User  $employee
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function saveChunkedFile(\Illuminate\Http\UploadedFile $file, User $employee, Request $request)
    {
        $fileName = $this->createFilename($file);
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Build the file path
        $filePath = "public/uploads/";
        $finalPath = storage_path("app/" . $filePath);

        // Ensure the upload directory exists
        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($filePath)) {
            \Illuminate\Support\Facades\Storage::disk('local')->makeDirectory($filePath);
        }

        // Move the file
        try {
            $file->move($finalPath, $fileName);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to move uploaded file.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => __('messages.failed_to_save_uploaded_file')
            ], 500);
        }

        // Create FileUpload record
        try {
            $fileUpload = \App\Models\FileUpload::create([
                'email' => \Illuminate\Support\Facades\Auth::user()->email, // Client's email
                'filename' => $fileName,
                'original_filename' => $originalFilename,
                'google_drive_file_id' => '',
                'validation_method' => 'employee_public',
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'chunk_size' => $request->input('chunk_size'),
                'total_chunks' => $request->input('total_chunks'),
                'uploaded_by_user_id' => $employee->id, // Track which employee this upload belongs to
            ]);

            // Dispatch upload job
            \App\Jobs\UploadToGoogleDrive::dispatch($fileUpload);

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
            \Illuminate\Support\Facades\Log::error('Failed to create FileUpload record.', [
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
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string
     */
    protected function createFilename(\Illuminate\Http\UploadedFile $file)
    {
        $extension = $file->getClientOriginalExtension();
        return \Illuminate\Support\Str::random(40) . '.' . $extension;
    }

    /**
     * Associates a message with one or more FileUpload records.
     *
     * @param  Request  $request
     * @param  string   $name
     * @return \Illuminate\Http\JsonResponse
     */
    public function associateMessage(Request $request, string $name)
    {
        $validated = $request->validate([
            'file_upload_ids' => 'required|array',
            'file_upload_ids.*' => 'required|exists:file_uploads,id',
            'message' => 'nullable|string|max:1000',
        ]);

        // Find employee or admin by extracting name from email
        $escapedName = str_replace(['%', '_'], ['\%', '\_'], $name);
        $employee = User::where('email', 'LIKE', $escapedName . '@%')
            ->whereIn('role', [\App\Enums\UserRole::EMPLOYEE, \App\Enums\UserRole::ADMIN])
            ->first();

        if (!$employee) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Update the message for uploads belonging to this employee and client
        \App\Models\FileUpload::whereIn('id', $validated['file_upload_ids'])
            ->where('uploaded_by_user_id', $employee->id)
            ->where('email', \Illuminate\Support\Facades\Auth::user()->email)
            ->update(['message' => $validated['message']]);

        return response()->json(['success' => true]);
    }

    /**
     * Handles the completion of a batch upload for an employee or admin.
     *
     * @param  Request  $request
     * @param  string   $name
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchComplete(Request $request, string $name)
    {
        $validated = $request->validate([
            'file_upload_ids' => 'required|array',
            'file_upload_ids.*' => 'required|exists:file_uploads,id',
        ]);

        // Find employee or admin by extracting name from email
        $escapedName = str_replace(['%', '_'], ['\%', '\_'], $name);
        $employee = User::where('email', 'LIKE', $escapedName . '@%')
            ->whereIn('role', [\App\Enums\UserRole::EMPLOYEE, \App\Enums\UserRole::ADMIN])
            ->first();

        if (!$employee) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Get the uploads for this employee and client
        $uploads = \App\Models\FileUpload::whereIn('id', $validated['file_upload_ids'])
            ->where('uploaded_by_user_id', $employee->id)
            ->where('email', \Illuminate\Support\Facades\Auth::user()->email)
            ->get();

        if ($uploads->count() > 0) {
            // Trigger batch upload complete event if needed
            // For now, just return success as the upload jobs are already dispatched
            \Illuminate\Support\Facades\Log::info('User batch upload completed', [
                'user_id' => $employee->id,
                'user_role' => $employee->role->value,
                'client_email' => \Illuminate\Support\Facades\Auth::user()->email,
                'upload_count' => $uploads->count()
            ]);
        }

        return response()->json(['success' => true]);
    }
}
