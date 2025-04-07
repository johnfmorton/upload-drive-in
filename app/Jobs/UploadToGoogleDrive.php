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

    protected function sanitizeEmailForFolder($email)
    {
        // Replace @ with -at- and . with -dot-
        $sanitized = str_replace(['@', '.'], ['-at-', '-dot-'], $email);
        // Remove any other special characters that might cause issues
        $sanitized = preg_replace('/[^a-zA-Z0-9\-]/', '-', $sanitized);
        // Remove multiple consecutive hyphens
        $sanitized = preg_replace('/-+/', '-', $sanitized);
        // Remove leading and trailing hyphens
        return trim($sanitized, '-');
    }

    protected function getOrCreateUserFolder($service, $email)
    {
        try {
            $sanitizedEmail = $this->sanitizeEmailForFolder($email);
            $folderName = "User: {$sanitizedEmail}";

            // Search for existing folder
            $query = "name = '{$folderName}' and mimeType = 'application/vnd.google-apps.folder' and '{$this->getRootFolderId()}' in parents and trashed = false";
            $results = $service->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name)'
            ]);

            if (count($results->getFiles()) > 0) {
                Log::info('Found existing user folder', [
                    'folder_id' => $results->getFiles()[0]->getId(),
                    'folder_name' => $folderName
                ]);
                return $results->getFiles()[0]->getId();
            }

            // Create new folder if it doesn't exist
            $folder = new DriveFile();
            $folder->setName($folderName);
            $folder->setMimeType('application/vnd.google-apps.folder');
            $folder->setParents([$this->getRootFolderId()]);

            $createdFolder = $service->files->create($folder, [
                'fields' => 'id'
            ]);

            Log::info('Created new user folder', [
                'folder_id' => $createdFolder->getId(),
                'folder_name' => $folderName
            ]);

            return $createdFolder->getId();
        } catch (\Exception $e) {
            Log::error('Failed to get or create user folder', [
                'error' => $e->getMessage(),
                'email' => $email,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function getRootFolderId()
    {
        return config('services.google_drive.root_folder_id');
    }

    public function handle()
    {
        try {
            $credentialsPath = Storage::path('google-credentials.json');
            if (!file_exists($credentialsPath)) {
                throw new \Exception('Google Drive token not found. Please connect your Google Drive account.');
            }

            $client = new Client();
            $client->setClientId(config('services.google_drive.client_id'));
            $client->setClientSecret(config('services.google_drive.client_secret'));
            $client->addScope(Drive::DRIVE_FILE);
            $client->addScope(Drive::DRIVE);
            $client->setAccessType('offline');

            // Get the access token from storage
            $token = json_decode(file_get_contents($credentialsPath), true);
            if (!$token) {
                throw new \Exception('Invalid Google Drive token format.');
            }

            $client->setAccessToken($token);

            // Refresh the token if it's expired
            if ($client->isAccessTokenExpired()) {
                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
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

            Log::info('Starting Google Drive upload process', [
                'file' => $this->fileUpload->filename,
                'email' => $this->fileUpload->email
            ]);

            // Get or create user's folder
            $userFolderId = $this->getOrCreateUserFolder($service, $this->fileUpload->email);

            // Create a new file in Google Drive
            $file = new DriveFile();
            $file->setName($this->fileUpload->original_filename);
            $file->setDescription("Uploaded by: " . $this->fileUpload->email . "\nMessage: " . ($this->fileUpload->message ?? 'No message'));
            $file->setParents([$userFolderId]);

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
                'original_name' => $this->fileUpload->original_filename,
                'user_folder' => $userFolderId
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
