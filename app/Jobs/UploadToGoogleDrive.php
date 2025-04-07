<?php

namespace App\Jobs;

use App\Models\FileUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class UploadToGoogleDrive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fileUpload;

    public function __construct(FileUpload $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    public function handle()
    {
        $client = new Client();
        $client->setClientId(config('services.google_drive.client_id'));
        $client->setClientSecret(config('services.google_drive.client_secret'));
        $client->addScope(Drive::DRIVE_FILE);
        $client->setAccessType('offline');

        // Get the access token from storage
        $tokenPath = storage_path('app/google-drive-token.json');
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // Refresh the token if it's expired
        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents($tokenPath, json_encode($client->getAccessToken()));
            }
        }

        $service = new Drive($client);

        // Create a new file in Google Drive
        $file = new DriveFile();
        $file->setName($this->fileUpload->original_filename);
        $file->setDescription("Uploaded by: " . $this->fileUpload->email . "\nMessage: " . ($this->fileUpload->message ?? 'No message'));
        $file->setParents([config('services.google_drive.root_folder_id')]);

        // Get the file content
        $content = Storage::disk('public')->get($this->fileUpload->filename);

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
    }
}
