<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\FileUpload;
use App\Jobs\UploadToGoogleDrive;

class GoogleDriveController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('cloud-storage.providers.google-drive.client_id'));
        $this->client->setClientSecret(config('cloud-storage.providers.google-drive.client_secret'));
        $this->client->setRedirectUri(config('cloud-storage.providers.google-drive.redirect_uri'));
        $this->client->addScope(Drive::DRIVE_FILE);
        $this->client->addScope(Drive::DRIVE);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    public function connect()
    {
        $authUrl = $this->client->createAuthUrl();
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        if ($request->has('code')) {
            try {
                Log::info('Google Drive callback received', ['code' => $request->code]);

                // Exchange the authorization code for an access token
                $token = $this->client->fetchAccessTokenWithAuthCode($request->code);

                if (!isset($token['error'])) {
                    Log::info('Token received successfully', ['token_type' => $token['token_type']]);

                    // Store the token
                    Storage::put('google-credentials.json', json_encode($token));

                    // Test the connection by getting the root folder
                    $service = new Drive($this->client);
                    $rootFolderId = config('cloud-storage.providers.google-drive.root_folder_id');

                    try {
                        $service->files->get($rootFolderId);
                        Log::info('Successfully verified root folder access', ['folder_id' => $rootFolderId]);

                        // Process pending uploads after successful connection
                        $pendingUploads = FileUpload::whereNull('google_drive_file_id')
                            ->orWhere('google_drive_file_id', '')
                            ->get();

                        Log::info('Found pending uploads after Google Drive connection', ['count' => $pendingUploads->count()]);

                        foreach ($pendingUploads as $upload) {
                            try {
                                Log::info('Processing upload after Google Drive connection', [
                                    'file_id' => $upload->id,
                                    'filename' => $upload->filename,
                                    'original_filename' => $upload->original_filename,
                                    'email' => $upload->email
                                ]);

                                UploadToGoogleDrive::dispatch($upload);
                                Log::info('Dispatched Google Drive upload job', [
                                    'file_id' => $upload->id,
                                    'file' => $upload->original_filename,
                                    'email' => $upload->email
                                ]);
                            } catch (\Exception $e) {
                                Log::error('Failed to dispatch Google Drive upload job', [
                                    'file' => $upload->original_filename,
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                            }
                        }

                    } catch (\Exception $e) {
                        Log::error('Failed to access root folder', [
                            'error' => $e->getMessage(),
                            'folder_id' => $rootFolderId
                        ]);
                        throw $e;
                    }

                    return redirect()->route('admin.dashboard')
                        ->with('success', 'Google Drive connected successfully.');
                } else {
                    Log::error('Token error received', ['error' => $token['error']]);
                    throw new \Exception('Failed to get access token: ' . $token['error']);
                }
            } catch (\Exception $e) {
                Log::error('Google Drive connection failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return redirect()->route('admin.dashboard')
                    ->with('error', 'Failed to connect Google Drive: ' . $e->getMessage());
            }
        }

        Log::warning('No authorization code received in callback');
        return redirect()->route('admin.dashboard')
            ->with('error', 'Failed to connect Google Drive: No authorization code received.');
    }

    public function disconnect()
    {
        if (Storage::exists('google-credentials.json')) {
            Storage::delete('google-credentials.json');
        }
        return redirect()->route('admin.dashboard')
            ->with('success', 'Google Drive disconnected successfully.');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'email' => 'required|email',
        ]);

        try {
            $file = $request->file('file');
            $email = $request->email;

            // Get the stored token
            $token = json_decode(Storage::get('google-credentials.json'), true);
            $this->client->setAccessToken($token);

            // Upload the file
            $fileId = $this->client->uploadFile($file, $email);

            // Store the upload record
            $upload = \App\Models\FileUpload::create([
                'email' => $email,
                'filename' => $file->hashName(),
                'original_filename' => $file->getClientOriginalName(),
                'google_drive_file_id' => $fileId,
                'message' => $request->message,
                'validation_method' => 'email', // or 'token' if using token-based validation
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'file_id' => $fileId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage()
            ], 500);
        }
    }
}
