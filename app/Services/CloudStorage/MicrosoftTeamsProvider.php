<?php

namespace App\Services\CloudStorage;

use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

class MicrosoftTeamsProvider extends AbstractCloudStorageProvider
{
    private ?Graph $service = null;

    /**
     * Get the authenticated Microsoft Graph service instance.
     *
     * @return Graph The authenticated Graph service
     * @throws \Exception If initialization fails
     */
    public function getService(): Graph
    {
        if (!$this->service) {
            $this->initializeClient();
        }
        return $this->service;
    }

    /**
     * Initializes the Microsoft Graph client and authenticates.
     *
     * @throws \Exception If authentication fails or configuration is missing
     */
    private function initializeClient(): void
    {
        // Check for credentials file
        $credentialsPath = Storage::path('microsoft-teams-credentials.json');
        if (!file_exists($credentialsPath)) {
            $this->log('Microsoft Teams credentials file not found.', ['path' => $credentialsPath], 'error');
            throw new \Exception('Microsoft Teams token not found. Please connect Microsoft Teams in admin settings.');
        }

        // Check for required configuration values
        $clientId = config('cloud-storage.providers.microsoft-teams.client_id');
        $clientSecret = config('cloud-storage.providers.microsoft-teams.client_secret');
        if (!$clientId || !$clientSecret) {
            $this->log('Microsoft Teams client ID or secret is not configured.', [], 'error');
            throw new \Exception('Microsoft Teams API client ID or secret is missing in configuration.');
        }

        try {
            $token = json_decode(file_get_contents($credentialsPath), true);
            if (!$token || !isset($token['access_token'])) {
                $this->log('Invalid Microsoft Teams token format found.', ['path' => $credentialsPath], 'error');
                throw new \Exception('Invalid Microsoft Teams token format.');
            }

            // Initialize Graph with access token
            $graph = new Graph();
            $graph->setAccessToken($token['access_token']);

            // If token is expired, refresh it
            if (time() >= $token['expires_at']) {
                $this->log('Access token expired, attempting refresh.');

                $client = new Client();
                $response = $client->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
                    'form_params' => [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'refresh_token' => $token['refresh_token'],
                        'grant_type' => 'refresh_token',
                    ],
                ]);

                $newToken = json_decode($response->getBody()->getContents(), true);
                $newToken['expires_at'] = time() + $newToken['expires_in'];

                file_put_contents($credentialsPath, json_encode($newToken));
                $this->log('Token refreshed and saved successfully.');

                $graph->setAccessToken($newToken['access_token']);
            }

            $this->service = $graph;

        } catch (\Exception $e) {
            $this->log('Failed to initialize client.', ['error' => $e->getMessage()], 'error');
            throw $e;
        }
    }

    public function getRootFolderId(): string
    {
        $rootFolderId = config('cloud-storage.providers.microsoft-teams.root_folder_id');
        if (empty($rootFolderId)) {
            $this->log('Root folder ID is not configured.', [], 'error');
            throw new \Exception('Microsoft Teams root folder ID is not configured.');
        }
        return $rootFolderId;
    }

    public function findUserFolderId(string $email): ?string
    {
        try {
            $graph = $this->getService();
            $folderName = $this->getUserFolderName($email);
            $rootFolderId = $this->getRootFolderId();

            $this->log('Searching for user folder.', [
                'email' => $email,
                'folder_name' => $folderName
            ], 'debug');

            // Search for folder in the root directory
            $result = $graph->createRequest('GET', "/drives/{$rootFolderId}/items/root:/children")
                ->setReturnType(Model\DriveItem::class)
                ->execute();

            foreach ($result as $item) {
                if ($item->getName() === $folderName && $item->getFolder()) {
                    $this->log('Found existing user folder.', [
                        'email' => $email,
                        'folder_id' => $item->getId()
                    ]);
                    return $item->getId();
                }
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
            $graph = $this->getService();
            $folderName = $this->getUserFolderName($email);
            $rootFolderId = $this->getRootFolderId();

            $this->log('Creating new user folder.', [
                'email' => $email,
                'folder_name' => $folderName
            ]);

            $folderMetadata = [
                'name' => $folderName,
                'folder' => new \stdClass(), // This indicates it's a folder
                '@microsoft.graph.conflictBehavior' => 'fail'
            ];

            $result = $graph->createRequest('POST', "/drives/{$rootFolderId}/items/root:/children")
                ->attachBody($folderMetadata)
                ->setReturnType(Model\DriveItem::class)
                ->execute();

            $newFolderId = $result->getId();

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
            $graph = $this->getService();
            $rootFolderId = $this->getRootFolderId();

            $this->log('Starting file upload.', [
                'local_path' => $localPath,
                'folder_id' => $folderId,
                'filename' => $filename
            ]);

            $content = Storage::disk('public')->get($localPath);
            if ($content === false) {
                throw new \Exception("Could not read local file: {$localPath}");
            }

            // For files under 4MB, use single request upload
            if (strlen($content) < 4 * 1024 * 1024) {
                $result = $graph->createRequest('PUT', "/drives/{$rootFolderId}/items/{$folderId}:/{$filename}:/content")
                    ->upload($content);

                $newFileId = $result->getId();

                // Update description if provided
                if ($description) {
                    $graph->createRequest('PATCH', "/drives/{$rootFolderId}/items/{$newFileId}")
                        ->attachBody(['description' => $description])
                        ->execute();
                }
            } else {
                // For larger files, use upload session
                $uploadSession = $graph->createRequest('POST', "/drives/{$rootFolderId}/items/{$folderId}:/{$filename}:/createUploadSession")
                    ->attachBody([
                        '@microsoft.graph.conflictBehavior' => 'rename',
                        'description' => $description
                    ])
                    ->execute();

                $uploadUrl = $uploadSession['uploadUrl'];

                // Upload the file in chunks
                $chunkSize = 327680; // 320 KB chunks
                $totalLength = strlen($content);
                $remainingLength = $totalLength;
                $offset = 0;

                while ($remainingLength > 0) {
                    $chunkContent = substr($content, $offset, $chunkSize);
                    $contentRange = "bytes {$offset}-" . ($offset + strlen($chunkContent) - 1) . "/{$totalLength}";

                    $client = new Client();
                    $response = $client->put($uploadUrl, [
                        'headers' => [
                            'Content-Length' => strlen($chunkContent),
                            'Content-Range' => $contentRange
                        ],
                        'body' => $chunkContent
                    ]);

                    $remainingLength -= strlen($chunkContent);
                    $offset += strlen($chunkContent);
                }

                $result = json_decode($response->getBody()->getContents(), true);
                $newFileId = $result['id'];
            }

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
            $graph = $this->getService();
            $rootFolderId = $this->getRootFolderId();

            $this->log('Attempting to delete file.', ['file_id' => $fileId]);
            $graph->createRequest('DELETE', "/drives/{$rootFolderId}/items/{$fileId}")
                ->execute();

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
            $graph = $this->getService();
            $rootFolderId = $this->getRootFolderId();

            $this->log('Attempting to delete folder.', ['folder_id' => $folderId]);
            $graph->createRequest('DELETE', "/drives/{$rootFolderId}/items/{$folderId}")
                ->execute();

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
        return 'microsoft-teams';
    }
}
