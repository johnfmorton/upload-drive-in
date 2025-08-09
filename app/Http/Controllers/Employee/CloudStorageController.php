<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CloudStorageController extends Controller
{
    protected GoogleDriveService $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    /**
     * Display the employee's cloud storage settings.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get current folder name if set
        $currentFolderName = null;
        $currentFolderId = $user->google_drive_root_folder_id;
        
        if ($currentFolderId && $user->hasGoogleDriveConnected()) {
            try {
                if ($currentFolderId === 'root') {
                    $currentFolderName = 'Root Folder';
                } else {
                    $service = $this->driveService->getDriveService($user);
                    $folder = $service->files->get($currentFolderId, ['fields' => 'name']);
                    $currentFolderName = $folder->getName();
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get folder name for employee', [
                    'user_id' => $user->id,
                    'folder_id' => $currentFolderId,
                    'error' => $e->getMessage()
                ]);
                $currentFolderName = 'Unknown Folder';
            }
        }

        return view('employee.cloud-storage.index', compact('user', 'currentFolderName', 'currentFolderId'));
    }

    /**
     * Connect to Google Drive (redirect to OAuth).
     */
    public function connect()
    {
        $user = Auth::user();
        $authUrl = $this->driveService->getAuthUrl($user);
        
        return redirect($authUrl);
    }

    /**
     * Disconnect from Google Drive.
     */
    public function disconnect()
    {
        $user = Auth::user();
        
        try {
            $this->driveService->disconnect($user);
            
            return redirect()->route('employee.cloud-storage.index', ['username' => $user->username])
                ->with('success', 'Successfully disconnected from Google Drive');
        } catch (\Exception $e) {
            Log::error('Failed to disconnect Google Drive for employee', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('employee.cloud-storage.index', ['username' => $user->username])
                ->with('error', 'Failed to disconnect from Google Drive');
        }
    }

    /**
     * Update the Google Drive root folder.
     */
    public function updateFolder(Request $request)
    {
        $validated = $request->validate([
            'google_drive_root_folder_id' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $user->google_drive_root_folder_id = $validated['google_drive_root_folder_id'];
        $user->save();

        return redirect()->route('employee.cloud-storage.index', ['username' => $user->username])
            ->with('success', 'Root folder updated successfully');
    }
}