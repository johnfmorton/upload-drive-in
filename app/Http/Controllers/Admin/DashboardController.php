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

        return view('admin.dashboard', compact('files'));
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
}
