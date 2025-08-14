<?php

namespace App\Http\Controllers\Admin;

use App\Models\FileUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DashboardController extends AdminController
{
    public function index()
    {
        // Get all files, ordered by most recent
        $files = FileUpload::orderBy('created_at', 'desc')->paginate(10);

        // Check if this is a first-time login after setup completion
        $isFirstTimeLogin = $this->checkFirstTimeLogin();

        return view('admin.dashboard', compact('files', 'isFirstTimeLogin'));
    }

    /**
     * Check if this is the admin's first login after setup completion
     */
    private function checkFirstTimeLogin(): bool
    {
        $user = auth()->user();
        
        // Only check for admin users
        if (!$user || !$user->isAdmin()) {
            return false;
        }

        // Check if setup was recently completed (within last 5 minutes)
        $setupService = app(\App\Services\SetupService::class);
        $setupState = $setupService->getSetupState();
        
        if (!isset($setupState['completed_at'])) {
            return false;
        }

        $completedAt = \Carbon\Carbon::parse($setupState['completed_at']);
        $isRecentlyCompleted = $completedAt->diffInMinutes(now()) <= 5;

        // Check if user has logged in before (excluding the current session)
        $hasLoggedInBefore = $user->last_login_at && 
                           $user->last_login_at->lt($completedAt);

        return $isRecentlyCompleted && !$hasLoggedInBefore;
    }

    public function destroy(FileUpload $file)
    {
        try {
            // Delete from Google Drive if file exists there
            if ($file->google_drive_file_id) {
                try {
                    // Use the new method from FileUpload model
                    $deleted = $file->deleteFromGoogleDrive();
                    if ($deleted) {
                        Log::info('Successfully deleted file from Google Drive: ' . $file->google_drive_file_id);
                    } else {
                        Log::warning('Failed to delete file from Google Drive: ' . $file->google_drive_file_id);
                    }
                } catch (\Exception $e) {
                    Log::error('Google Drive API call failed during deletion for file ID: ' . $file->google_drive_file_id . ' Error: ' . $e->getMessage());
                    throw new \Exception('Failed to delete file from Google Drive. Aborting deletion. Error: ' . $e->getMessage(), 0, $e);
                }
            }

            // Delete the local file if it exists
            if (Storage::disk('public')->exists('uploads/' . $file->filename)) {
                Storage::disk('public')->delete('uploads/' . $file->filename);
            }

            // Delete the database record
            $file->delete();

            return redirect()->route('admin.dashboard')
                ->with('success', 'File has been deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Error deleting file: ' . $e->getMessage());
        }
    }

    /**
     * Process pending uploads that failed to upload to Google Drive.
     */
    public function processPendingUploads()
    {
        try {
            // Get pending uploads count
            $pendingCount = FileUpload::whereNull('google_drive_file_id')
                ->orWhere('google_drive_file_id', '')
                ->count();

            if ($pendingCount === 0) {
                return redirect()->route('admin.dashboard')
                    ->with('info', 'No pending uploads found.');
            }

            // Call the artisan command to process pending uploads
            \Illuminate\Support\Facades\Artisan::call('uploads:process-pending', [
                '--limit' => 50
            ]);

            $output = \Illuminate\Support\Facades\Artisan::output();
            Log::info('Processed pending uploads via admin interface', ['output' => $output]);

            return redirect()->route('admin.dashboard')
                ->with('success', "Processing {$pendingCount} pending uploads. Check the queue status for progress.");

        } catch (\Exception $e) {
            Log::error('Failed to process pending uploads', ['error' => $e->getMessage()]);
            return redirect()->route('admin.dashboard')
                ->with('error', 'Failed to process pending uploads: ' . $e->getMessage());
        }
    }
}
