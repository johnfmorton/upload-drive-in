<?php

namespace App\Http\Controllers;

use App\Models\FileUpload;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        // Get all files, ordered by most recent
        $files = FileUpload::orderBy('created_at', 'desc')->paginate(10);

        return view('dashboard', compact('files'));
    }

    public function destroy(FileUpload $file)
    {
        try {
            // Delete from Google Drive if file exists there
            if ($file->google_drive_file_id) {
                $tokenPath = Storage::path('google-drive-token.json');
                if (Storage::exists('google-drive-token.json')) {
                    $client = new Client();
                    $client->setAuthConfig(config_path('google-drive.json'));
                    $client->addScope(Drive::DRIVE_FILE);
                    $client->setAccessType('offline');
                    $accessToken = json_decode(file_get_contents($tokenPath), true);
                    $client->setAccessToken($accessToken);

                    $service = new Drive($client);
                    try {
                        $service->files->delete($file->google_drive_file_id);
                    } catch (\Exception $e) {
                        // File might already be deleted from Drive, continue with local deletion
                    }
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
