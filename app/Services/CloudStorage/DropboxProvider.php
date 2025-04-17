<?php

namespace App\Services\CloudStorage;

use Spatie\Dropbox\Client as DropboxClient;
use Illuminate\Support\Facades\Storage;

class DropboxProvider extends AbstractCloudStorageProvider
{
    private ?DropboxClient $service = null;

    /**
     * Get the authenticated Dropbox client instance.
     *
     * @return DropboxClient The authenticated Dropbox client
     * @throws \Exception If initialization fails
     */
    public function getService(): DropboxClient
    {
        if (!$this->service) {
            $this->initializeClient();
        }
        return $this->service;
    }

    /**
     * Initializes the Dropbox client and authenticates.
     *
     * @throws \Exception If authentication fails or configuration is missing
     */
    private function initializeClient(): void
    {
        // Check for credentials file
        $credentialsPath = Storage::path('dropbox-credentials.json');
        if (!file_exists($credentialsPath)) {
            $this->log('Dropbox credentials file not found.', ['path' => $credentialsPath], 'error');
            throw new \Exception('Dropbox token not found. Please connect Dropbox in admin settings.');
        }

        // Check for required configuration values
        $appKey = config('cloud-storage.providers.dropbox.app_key');
        $appSecret = config('cloud-storage.providers.dropbox.app_secret');
        if (!$appKey || !$appSecret) {
            $this->log('Dropbox app key or secret is not configured.', [], 'error');
            throw new \Exception('Dropbox API app key or secret is missing in configuration.');
        }

        try {
            $token = json_decode(file_get_contents($credentialsPath), true);
            if (!$token || !isset($token['access_token'])) {
                $this->log('Invalid Dropbox token format found.', ['path' => $credentialsPath], 'error');
                throw new \Exception('Invalid Dropbox token format.');
            }

            // Initialize Dropbox client with access token
            $this->service = new DropboxClient($token['access_token']);

            // Test the connection
            $this->service->getAccountInfo();

        } catch (\Exception $e) {
            $this->log('Failed to initialize client.', ['error' => $e->getMessage()], 'error');
            throw $e;
        }
    }

    public function getRootFolderId(): string
    {
        return config('cloud-storage.providers.dropbox.root_folder', '/UploadDriveIn');
    }

    public function findUserFolderId(string $email): ?string
    {
        try {
            $client = $this->getService();
            $folderName = $this->getUserFolderName($email);
            $rootFolder = $this->getRootFolderId();
            $folderPath = "{$rootFolder}/{$folderName}";

            $this->log('Searching for user folder.', [
                'email' => $email,
                'folder_path' => $folderPath
            ], 'debug');

            try {
                $metadata = $client->getMetadata($folderPath);
                if ($metadata && $metadata['folder']) {
                    $this->log('Found existing user folder.', [
                        'email' => $email,
                        'folder_path' => $folderPath
                    ]);
                    return $folderPath; // Dropbox uses paths as IDs
                }
            } catch (\Exception $e) {
                // Folder not found
                return null;
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
        $folderPath = $this->findUserFolderId($email);

        if ($folderPath) {
            return $folderPath;
        }

        try {
            $client = $this->getService();
            $folderName = $this->getUserFolderName($email);
            $rootFolder = $this->getRootFolderId();
            $newFolderPath = "{$rootFolder}/{$folderName}";

            $this->log('Creating new user folder.', [
                'email' => $email,
                'folder_path' => $newFolderPath
            ]);

            $client->createFolder($newFolderPath);

            $this->log('Successfully created new user folder.', [
                'email' => $email,
                'folder_path' => $newFolderPath
            ]);

            return $newFolderPath;

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
            $client = $this->getService();
            $dropboxPath = "{$folderId}/{$filename}";

            $this->log('Starting file upload.', [
                'local_path' => $localPath,
                'dropbox_path' => $dropboxPath
            ]);

            $content = Storage::disk('public')->get($localPath);
            if ($content === false) {
                throw new \Exception("Could not read local file: {$localPath}");
            }

            // Upload the file
            $result = $client->upload($dropboxPath, $content, ['mode' => 'add']);

            if ($description) {
                // Dropbox doesn't have native file descriptions, but we could
                // create a companion .txt file with the description if needed
                $descriptionPath = "{$folderId}/{$filename}.description.txt";
                $client->upload($descriptionPath, $description, ['mode' => 'overwrite']);
            }

            $this->log('File successfully uploaded.', [
                'dropbox_path' => $dropboxPath
            ]);

            return $dropboxPath; // Dropbox uses paths as IDs

        } catch (\Exception $e) {
            $this->log('Failed to upload file.', [
                'local_path' => $localPath,
                'dropbox_path' => $dropboxPath ?? null,
                'error' => $e->getMessage()
            ], 'error');
            throw $e;
        }
    }

    public function deleteFile(string $fileId): bool
    {
        try {
            $client = $this->getService();

            $this->log('Attempting to delete file.', ['file_path' => $fileId]);
            $client->delete($fileId);

            // Also delete description file if it exists
            try {
                $client->delete($fileId . '.description.txt');
            } catch (\Exception $e) {
                // Ignore errors deleting description file
            }

            $this->log('Successfully deleted file.', ['file_path' => $fileId]);
            return true;
        } catch (\Exception $e) {
            $this->log('Failed to delete file.', [
                'file_path' => $fileId,
                'error' => $e->getMessage()
            ], 'error');
            return false;
        }
    }

    public function deleteFolder(string $folderId): bool
    {
        try {
            $client = $this->getService();

            $this->log('Attempting to delete folder.', ['folder_path' => $folderId]);
            $client->delete($folderId);

            $this->log('Successfully deleted folder.', ['folder_path' => $folderId]);
            return true;
        } catch (\Exception $e) {
            $this->log('Failed to delete folder.', [
                'folder_path' => $folderId,
                'error' => $e->getMessage()
            ], 'error');
            return false;
        }
    }

    public function getProviderName(): string
    {
        return 'dropbox';
    }
}
