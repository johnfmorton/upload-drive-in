<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\FileUpload;
use App\Services\GoogleDriveService;
use App\Jobs\UploadToGoogleDrive;

class UploadController extends Controller
{
    protected GoogleDriveService $driveService;

    /**
     * Construct the controller.
     *
     * @param  GoogleDriveService  $driveService
     * @return void
     */
    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    /**
     * Show the employee-specific upload page.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        $user = Auth::user();
        
        // Get current folder name if set
        $currentFolderName = null;
        $currentFolderId = $user->google_drive_root_folder_id;
        
        if ($user->hasGoogleDriveConnected()) {
            try {
                if (empty($currentFolderId) || $currentFolderId === 'root') {
                    $currentFolderName = 'Google Drive Root';
                } else {
                    $service = $this->driveService->getDriveService($user);
                    $folder = $service->files->get($currentFolderId, ['fields' => 'name']);
                    $currentFolderName = $folder->getName();
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get folder name for employee upload page', [
                    'user_id' => $user->id,
                    'folder_id' => $currentFolderId,
                    'error' => $e->getMessage()
                ]);
                $currentFolderName = 'Unknown Folder';
            }
        } else {
            // Default messaging when not connected
            $currentFolderName = empty($currentFolderId) ? 'Google Drive Root (default)' : 'Selected Folder';
        }
        
        return view('employee.upload-page', compact('user', 'currentFolderName', 'currentFolderId'));
    }

    /**
     * Redirect the employee to Google's OAuth consent screen.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function connect()
    {
        $user = Auth::user();
        $authUrl = $this->driveService->getAuthUrl($user);
        
        return redirect($authUrl);
    }

    /**
     * Handle the OAuth callback from Google Drive.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        $user = Auth::user();
        $code = $request->input('code');
        
        if (!$code) {
            return redirect()->route('employee.cloud-storage.index', ['username' => $user->username])
                ->with('error', __('messages.google_drive_connection_failed'));
        }

        try {
            $this->driveService->handleCallback($user, $code);
            
            return redirect()->route('employee.cloud-storage.index', ['username' => $user->username])
                ->with('success', __('messages.google_drive_connected'));
        } catch (\Exception $e) {
            return redirect()->route('employee.cloud-storage.index', ['username' => $user->username])
                ->with('error', __('messages.google_drive_connection_failed'));
        }
    }

    /**
     * Disconnect Google Drive for the employee.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disconnect(Request $request)
    {
        $user = Auth::user();
        
        try {
            $this->driveService->disconnect($user);
            
            return redirect()->route('employee.cloud-storage.index', ['username' => $user->username])
                ->with('success', __('messages.google_drive_disconnected'));
        } catch (\Exception $e) {
            return redirect()->route('employee.cloud-storage.index', ['username' => $user->username])
                ->with('error', __('messages.google_drive_disconnect_failed'));
        }
    }

    /**
     * Update the selected Google Drive root folder for the employee.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateFolder(Request $request)
    {
        $validated = $request->validate([
            'google_drive_root_folder_id' => ['nullable', 'string'],
        ]);

        try {
            $user = Auth::user();
            
            // Save to user's database record
            $user->google_drive_root_folder_id = $validated['google_drive_root_folder_id'] ?? null;
            $user->save();
            
            Log::info('Google Drive root folder updated successfully for employee', [
                'user_id' => $user->id,
                'folder_id' => $validated['google_drive_root_folder_id']
            ]);

            return redirect()->route(
                'employee.upload.show',
                ['username' => $user->username]
            )
            ->with('success', __('messages.save_root_folder'));
        } catch (\Exception $e) {
            Log::error('Failed to update Google Drive root folder for employee', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route(
                'employee.upload.show',
                ['username' => $user->username]
            )
            ->with('error', __('messages.settings_update_failed'));
        }
    }

    /**
     * Handle file uploads for the employee and dispatch Google Drive jobs.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'message' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $original_name = $file->getClientOriginalName();
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('uploads', $file, $filename);

                $upload = FileUpload::create([
                    'email' => $user->email,
                    'filename' => $filename,
                    'original_filename' => $original_name,
                    'google_drive_file_id' => '',
                    'message' => $request->input('message'),
                    'validation_method' => 'employee',
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by_user_id' => $user->id, // Track which employee this upload belongs to
                ]);

                UploadToGoogleDrive::dispatch($upload);
            }
        }

        return redirect()->route(
            'employee.upload.show',
            ['username' => $user->username]
        )
        ->with('success', __('messages.success_generic'));
    }
}
