<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    protected $client;
    protected $service;
    protected $rootFolderId;

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(storage_path('app/google-credentials.json'));
        $this->client->addScope(Drive::DRIVE_FILE);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');

        $this->service = new Drive($this->client);
        $this->rootFolderId = config('services.google_drive.root_folder_id');
    }

    public function setAccessToken($token)
    {
        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            }
        }
    }

    public function uploadFile($file, $email)
    {
        // Create or get folder for the email
        $folderId = $this->getOrCreateFolder($email);

        // Create file metadata
        $fileMetadata = new DriveFile([
            'name' => $file->getClientOriginalName(),
            'parents' => [$folderId]
        ]);

        // Upload file content
        $content = file_get_contents($file->getRealPath());

        // Create the file
        $uploadedFile = $this->service->files->create(
            $fileMetadata,
            [
                'data' => $content,
                'mimeType' => $file->getMimeType(),
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]
        );

        return $uploadedFile->id;
    }

    protected function getOrCreateFolder($email)
    {
        // Check if folder exists
        $query = "name = '{$email}' and mimeType = 'application/vnd.google-apps.folder' and '{$this->rootFolderId}' in parents and trashed = false";
        $results = $this->service->files->listFiles([
            'q' => $query,
            'fields' => 'files(id, name)'
        ]);

        if (count($results->getFiles()) > 0) {
            return $results->getFiles()[0]->getId();
        }

        // Create new folder
        $folderMetadata = new DriveFile([
            'name' => $email,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$this->rootFolderId]
        ]);

        $folder = $this->service->files->create($folderMetadata, [
            'fields' => 'id'
        ]);

        return $folder->id;
    }

    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function fetchAccessToken($code)
    {
        return $this->client->fetchAccessTokenWithAuthCode($code);
    }
}
