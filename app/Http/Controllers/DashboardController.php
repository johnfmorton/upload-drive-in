<?php

namespace App\Http\Controllers;

use App\Models\FileUpload;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class DashboardController extends Controller
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
                $credentialsFilename = 'google-credentials.json';
                $credentialsPath = Storage::path($credentialsFilename);

                if (file_exists($credentialsPath)) {
                    try {
                        $client = new Client();
                        $client->setClientId(Config::get('services.google_drive.client_id'));
                        $client->setClientSecret(Config::get('services.google_drive.client_secret'));
                        $client->addScope(Drive::DRIVE_FILE);
                        $client->setAccessType('offline');

                        $token = json_decode(file_get_contents($credentialsPath), true);
                        if (!$token) {
                            throw new \Exception('Invalid Google Drive token format in ' . $credentialsFilename);
                        }
                        $client->setAccessToken($token);

                        if ($client->isAccessTokenExpired()) {
                            Log::info('Google Drive token expired, attempting refresh.');
                            if ($client->getRefreshToken()) {
                                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                                file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
                                Log::info('Google Drive token refreshed and saved.');
                            } else {
                                throw new \Exception('Refresh token not available in ' . $credentialsFilename . '. Please reconnect Google Drive.');
                            }
                        }

                        $service = new Drive($client);
                        Log::info('Attempting Google Drive deletion for file ID: ' . $file->google_drive_file_id);
                        $service->files->delete($file->google_drive_file_id);
                        Log::info('Successfully deleted file from Google Drive: ' . $file->google_drive_file_id);

                    } catch (\Exception $e) {
                        Log::error('Google Drive API call failed during deletion for file ID: ' . $file->google_drive_file_id . ' Error: ' . $e->getMessage());
                        throw new \Exception('Failed to delete file from Google Drive. Aborting deletion. Error: ' . $e->getMessage(), 0, $e);
                    }
                } else {
                    Log::warning('Google Drive credentials file (' . $credentialsFilename . ') not found at ' . $credentialsPath . '. Skipping Drive deletion for file ID: ' . $file->google_drive_file_id);
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
