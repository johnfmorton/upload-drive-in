<?php

namespace App\Jobs;

use App\Models\FileUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class UploadToGoogleDrive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fileUpload;
    public $tries = 3;

    public function __construct(FileUpload $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    public function handle()
    {
        try {
            if (!Storage::exists('google-drive-token.json')) {
                throw new \Exception('Google Drive token not found. Please connect your Google Drive account.');
            }

            $client = new Client();
            $client->setClientId(config('services.google_drive.client_id'));
            $client->setClientSecret(config('services.google_drive.client_secret'));
            $client->addScope(Drive::DRIVE_FILE);
            $client->setAccessType('offline');

            // Get the access token from storage
            $token = json_decode(Storage::get('google-drive-token.json'), true);
            if (!$token) {
                throw new \Exception('Invalid Google Drive token format.');
            }

            $client->setAccessToken($token);

            // Refresh the token if it's expired
            if ($client->isAccessTokenExpired()) {
                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    Storage::put('google-drive-token.json', json_encode($client->getAccessToken()));
                } else {
                    throw new \Exception('Refresh token not available. Please reconnect your Google Drive account.');
                }
            }

            $service = new Drive($client);

            // Check if the file exists in storage
            $filePath = 'uploads/' . $this->fileUpload->filename;
            if (!Storage::disk('public')->exists($filePath)) {
                throw new \Exception("File not found in storage: {$filePath}");
            }

            // Create a new file in Google Drive
            $file = new DriveFile();
            $file->setName($this->fileUpload->original_filename);
            $file->setDescription("Uploaded by: " . $this->fileUpload->email . "\nMessage: " . ($this->fileUpload->message ?? 'No message'));
            $file->setParents([config('services.google_drive.root_folder_id')]);

            // Get the file content
            $content = Storage::disk('public')->get($filePath);

            // Upload the file
            $result = $service->files->create($file, [
                'data' => $content,
                'mimeType' => $this->fileUpload->mime_type,
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);

            // Update the file upload record with the Google Drive file ID
            $this->fileUpload->update([
                'google_drive_file_id' => $result->id
            ]);

            Log::info('File uploaded to Google Drive successfully', [
                'file_id' => $result->id,
                'original_name' => $this->fileUpload->original_filename
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to upload file to Google Drive', [
                'error' => $e->getMessage(),
                'file' => $this->fileUpload->original_filename,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
