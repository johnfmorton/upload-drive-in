<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GoogleDriveController extends Controller
{
    protected $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    public function connect()
    {
        $authUrl = $this->driveService->getAuthUrl();
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        if ($request->has('code')) {
            $token = $this->driveService->fetchAccessToken($request->code);

            // Store the token securely
            Storage::put('google-drive-token.json', json_encode($token));

            return redirect()->route('dashboard')
                ->with('success', 'Successfully connected to Google Drive!');
        }

        return redirect()->route('dashboard')
            ->with('error', 'Failed to connect to Google Drive.');
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
            $token = json_decode(Storage::get('google-drive-token.json'), true);
            $this->driveService->setAccessToken($token);

            // Upload the file
            $fileId = $this->driveService->uploadFile($file, $email);

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
