<?php

namespace App\Services\CloudStorage;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Storage;

class GoogleDriveProvider extends AbstractCloudStorageProvider
{
    private ?Drive $service = null;
    private ?Client $client = null;

    /**
     * Get the authenticated Google Drive service instance.
     *
     * @return Drive The authenticated Google Drive service
     * @throws \Exception If initialization fails
     */
    public function getService(): Drive
    {
        if (!$this->service) {
            $this->initializeClient();
        }
        return $this->service;
    }

    /**
     * Initializes the Google API client and authenticates.
     *
     * @throws \Exception If authentication fails or configuration is missing
     */
    private function initializeClient(): void
    {
        // Only initialize once per service instance
        if ($this->client) {
            return;
        }

        // Check for credentials file
        $credentialsPath = Storage::path('google-credentials.json');
        if (!file_exists($credentialsPath)) {
            $this->log('Google Drive credentials file not found.', ['path' => $credentialsPath], 'error');
            throw new \Exception('Google Drive token (google-credentials.json) not found. Please connect Google Drive in admin settings.');
        }

        // Check for required configuration values
        $clientId = config('cloud-storage.providers.google-drive.client_id');
        $clientSecret = config('cloud-storage.providers.google-drive.client_secret');
        if (!$clientId || !$clientSecret) {
            $this->log('Google Drive client ID or secret is not configured.', [], 'error');
            throw new \Exception('Google Drive API client ID or secret is missing in configuration.');
        }

        try {
            $client = new Client();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->addScope(Drive::DRIVE);
            $client->setAccessType('offline');
            $client->setPrompt('select_account consent');

            // Load token
            $token = json_decode(file_get_contents($credentialsPath), true);
            if (!$token || !isset($token['access_token'])) {
                $this->log('Invalid Google Drive token format found.', ['path' => $credentialsPath], 'error');
                throw new \Exception('Invalid Google Drive token format.');
            }
            $client->setAccessToken($token);

            // Refresh token if expired
            if ($client->isAccessTokenExpired()) {
                $this->log('Access token expired, attempting refresh.');
                $refreshToken = $client->getRefreshToken();
                if ($refreshToken) {
                    $client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $newAccessToken = $client->getAccessToken();
                    file_put_contents($credentialsPath, json_encode($newAccessToken));
                    $this->log('Token refreshed and saved successfully.');
                    $client->setAccessToken($newAccessToken);
                } else {
                    $this->log('Token expired, but no refresh token available.', [], 'error');
                    throw new \Exception('Google Drive token expired, and no refresh token available. Please reconnect Google Drive.');
                }
            }

            $this->client = $client;
            $this->service = new Drive($client);

        } catch (\Exception $e) {
            $this->log('Failed to initialize client.', ['error' => $e->getMessage()], 'error');
            throw $e;
        }
    }

    public function getRootFolderId(): string
    {
        $rootFolderId = config('cloud-storage.providers.google-drive.root_folder_id');
        if (empty($rootFolderId)) {
            $this->log('Root folder ID is not configured.', [], 'error');
            throw new \Exception('Google Drive root folder ID is not configured.');
        }
        return $rootFolderId;
    }

    public function findUserFolderId(string $email): ?string
    {
        try {
            $service = $this->getService();
            $folderName = $this->getUserFolderName($email);
            $rootFolderId = $this->getRootFolderId();

            $this->log('Searching for user folder.', [
                'email' => $email,
                'folder_name' => $folderName
            ], 'debug');

            $query = sprintf(
                "name = '%s' and mimeType = 'application/vnd.google-apps.folder' and '%s' in parents and trashed = false",
                addslashes($folderName),
                addslashes($rootFolderId)
            );

            $results = $service->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name)',
                'pageSize' => 1
            ]);

            if (count($results->getFiles()) > 0) {
                $folderId = $results->getFiles()[0]->getId();
                $this->log('Found existing user folder.', [
                    'email' => $email,
                    'folder_id' => $folderId
                ]);
                return $folderId;
            }

            return null;

        } catch (\Exception $e) {
            $this->log('Failed to search for user folder.', [
                'email' => $email,
                'error' => $e->getMessage()
            ], 'error');
            throw $e;
        }
    }

    public function getOrCreateUserFolderId(string $email): string
    {
        $folderId = $this->findUserFolderId($email);

        if ($folderId) {
            return $folderId;
        }

        try {
            $service = $this->getService();
            $folderName = $this->getUserFolderName($email);
            $rootFolderId = $this->getRootFolderId();

            $this->log('Creating new user folder.', [
                'email' => $email,
                'folder_name' => $folderName
            ]);

            $folderMetadata = new DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$rootFolderId]
            ]);

            $createdFolder = $service->files->create($folderMetadata, ['fields' => 'id']);
            $newFolderId = $createdFolder->getId();

            $this->log('Successfully created new user folder.', [
                'email' => $email,
                'folder_id' => $newFolderId
            ]);

            return $newFolderId;

        } catch (\Exception $e) {
            $this->log('Failed to create user folder.', [
                'email' => $email,
                'error' => $e->getMessage()
            ], 'error');
            throw $e;
        }
    }

    public function uploadFile(
        string $localPath,
        string $folderId,
        string $filename,
        string $mimeType,
        ?string $description = null
    ): string {
        $this->verifyLocalFile($localPath);

        try {
            $service = $this->getService();
            $this->log('Starting file upload.', [
                'local_path' => $localPath,
                'folder_id' => $folderId,
                'filename' => $filename
            ]);

            $fileMetadata = new DriveFile([
                'name' => $filename,
                'parents' => [$folderId]
            ]);
            if ($description) {
                $fileMetadata->setDescription($description);
            }

            $content = Storage::disk('public')->get($localPath);
            if ($content === false) {
                throw new \Exception("Could not read local file: {$localPath}");
            }

            $createdFile = $service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);

            $newFileId = $createdFile->getId();
            $this->log('File successfully uploaded.', [
                'file_id' => $newFileId,
                'folder_id' => $folderId
            ]);

            return $newFileId;

        } catch (\Exception $e) {
            $this->log('Failed to upload file.', [
                'local_path' => $localPath,
                'folder_id' => $folderId,
                'error' => $e->getMessage()
            ], 'error');
            throw $e;
        }
    }

    public function deleteFile(string $fileId): bool
    {
        try {
            $service = $this->getService();
            $this->log('Attempting to delete file.', ['file_id' => $fileId]);
            $service->files->delete($fileId);
            $this->log('Successfully deleted file.', ['file_id' => $fileId]);
            return true;
        } catch (\Exception $e) {
            $this->log('Failed to delete file.', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ], 'error');
            return false;
        }
    }

    public function deleteFolder(string $folderId): bool
    {
        try {
            $service = $this->getService();
            $this->log('Attempting to delete folder.', ['folder_id' => $folderId]);
            $service->files->delete($folderId);
            $this->log('Successfully deleted folder.', ['folder_id' => $folderId]);
            return true;
        } catch (\Exception $e) {
            $this->log('Failed to delete folder.', [
                'folder_id' => $folderId,
                'error' => $e->getMessage()
            ], 'error');
            return false;
        }
    }

    public function getProviderName(): string
    {
        return 'google-drive';
    }
}
