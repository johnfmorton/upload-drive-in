<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GoogleDriveController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('services.google_drive.client_id'));
        $this->client->setClientSecret(config('services.google_drive.client_secret'));
        $this->client->setRedirectUri(config('services.google_drive.redirect_uri'));
        $this->client->addScope(Drive::DRIVE_FILE);
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
            $token = $this->client->fetchAccessTokenWithAuthCode($request->code);

            if (!isset($token['error'])) {
                Storage::put('google-credentials.json', json_encode($token));
                return redirect()->route('admin.dashboard')->with('success', 'Google Drive connected successfully.');
            }
        }

        return redirect()->route('admin.dashboard')->with('error', 'Failed to connect Google Drive.');
    }

    public function disconnect()
    {
        if (Storage::exists('google-credentials.json')) {
            Storage::delete('google-credentials.json');
        }
        return redirect()->route('admin.dashboard')->with('success', 'Google Drive disconnected successfully.');
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
