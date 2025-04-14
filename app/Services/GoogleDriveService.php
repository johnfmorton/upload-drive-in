<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * Service class for interacting with the Google Drive API.
 * Handles authentication, folder operations, and file uploads.
 */
class GoogleDriveService
{
    /**
     * @var Drive|null The authenticated Google Drive service instance.
     */
    private ?Drive $service = null;

    /**
     * @var Client|null The Google API client instance.
     */
    private ?Client $client = null;

    /**
     * Initializes the Google API client and authenticates.
     * Stores the client and service instance for reuse within the request lifecycle.
     *
     * @throws Exception If authentication fails or configuration is missing.
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
            Log::error('Google Drive credentials file not found.', ['path' => $credentialsPath]);
            throw new Exception('Google Drive token (google-credentials.json) not found. Please connect Google Drive in admin settings.');
        }

        // Check for required configuration values
        $clientId = config('services.google_drive.client_id');
        $clientSecret = config('services.google_drive.client_secret');
        if (!$clientId || !$clientSecret) {
            Log::error('Google Drive client ID or secret is not configured.');
            throw new Exception('Google Drive API client ID or secret is missing in configuration.');
        }

        try {
            $client = new Client();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            // Requesting full DRIVE scope allows creating folders, uploading files, deleting files/folders
            $client->addScope(Drive::DRIVE);
            $client->setAccessType('offline'); // Required to get refresh tokens
            $client->setPrompt('select_account consent'); // Useful during the initial auth flow

            // Load token
            $token = json_decode(file_get_contents($credentialsPath), true);
            if (!$token || !isset($token['access_token'])) {
                 Log::error('Invalid Google Drive token format found.', ['path' => $credentialsPath]);
                throw new Exception('Invalid Google Drive token format.');
            }
            $client->setAccessToken($token);

            // Refresh token if expired
            if ($client->isAccessTokenExpired()) {
                Log::info('Google Drive access token expired, attempting refresh.');
                $refreshToken = $client->getRefreshToken();
                if ($refreshToken) {
                    $client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $newAccessToken = $client->getAccessToken();
                    // Persist the new token (includes refresh token if granted)
                    file_put_contents($credentialsPath, json_encode($newAccessToken));
                    Log::info('Google Drive token refreshed and saved successfully.');
                    // Update the client's token in memory
                    $client->setAccessToken($newAccessToken);
                } else {
                    // No refresh token available - requires re-authentication by the user
                    Log::error('Google Drive token expired, but no refresh token available.');
                    // Optionally: trigger an event or notification to prompt re-authentication
                    throw new Exception('Google Drive token expired, and no refresh token available. Please reconnect Google Drive.');
                }
            }

            $this->client = $client;
            $this->service = new Drive($client);

        } catch (Exception $e) {
            Log::error('Failed to initialize Google Drive client.', [
                'error' => $e->getMessage(),
                // Avoid logging full trace in production unless necessary for debugging
                // 'trace' => $e->getTraceAsString()
            ]);
            // Re-throw the exception to be handled by the caller
            throw $e;
        }
    }

    /**
     * Returns an authenticated Google Drive service instance.
     * Initializes the client if it hasn't been already.
     *
     * @return Drive The authenticated Google Drive service.
     * @throws Exception If initialization fails.
     */
    public function getService(): Drive
    {
        if (!$this->service) {
            $this->initializeClient();
        }
        return $this->service;
    }

    /**
     * Returns the configured Google Drive Root Folder ID.
     *
     * @return string The Root Folder ID.
     * @throws Exception If the root folder ID is not configured.
     */
    public function getRootFolderId(): string
    {
        $rootFolderId = config('services.google_drive.root_folder_id');
        if (empty($rootFolderId)) {
            Log::error('Google Drive root folder ID is not configured.', ['config_key' => 'services.google_drive.root_folder_id']);
            throw new Exception('Google Drive root folder ID is not configured.');
        }
        return $rootFolderId;
    }

    /**
     * Sanitizes an email address to create a valid and unique folder name.
     * Replaces special characters to avoid issues with folder naming conventions.
     * Example: 'user.name@example.com' becomes 'user-dot-name-at-example-dot-com'.
     *
     * @param string $email The email address to sanitize.
     * @return string The sanitized string suitable for a folder name component.
     */
    public function sanitizeEmailForFolderName(string $email): string
    {
        $sanitized = str_replace(['@', '.'], ['-at-', '-dot-'], $email);
        // Replace any remaining non-alphanumeric characters (except hyphen) with a hyphen
        $sanitized = preg_replace('/[^a-zA-Z0-9\-]/', '-', $sanitized);
        // Replace multiple consecutive hyphens with a single hyphen
        $sanitized = preg_replace('/-+/', '-', $sanitized);
        // Remove leading and trailing hyphens
        return trim($sanitized, '-');
    }

    /**
     * Finds the Google Drive folder ID for a given user email within the root application folder.
     * Constructs the expected folder name using sanitizeEmailForFolderName.
     *
     * @param string $email The user's email address.
     * @return string|null The Google Drive folder ID if found, otherwise null.
     * @throws Exception If Google Drive API interaction fails.
     */
    public function findUserFolderId(string $email): ?string
    {
        try {
            $service = $this->getService();
            $sanitizedEmail = $this->sanitizeEmailForFolderName($email);
            $folderName = "User: {$sanitizedEmail}"; // Consistent naming convention
            $rootFolderId = $this->getRootFolderId();

            Log::debug('Searching for user folder in Google Drive.', [
                'email' => $email,
                'search_name' => $folderName,
                'parent_id' => $rootFolderId
            ]);

            // Construct the query to find the specific folder by name within the root folder
            $query = sprintf(
                "name = '%s' and mimeType = 'application/vnd.google-apps.folder' and '%s' in parents and trashed = false",
                addslashes($folderName), // Properly escape folder name for the query
                addslashes($rootFolderId)
            );

            $results = $service->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name)', // Only fetch necessary fields
                'pageSize' => 1 // We only need to know if it exists
            ]);

            if (count($results->getFiles()) > 0) {
                $folderId = $results->getFiles()[0]->getId();
                Log::info('Found existing user folder in Google Drive.', [
                    'email' => $email,
                    'folder_name' => $folderName,
                    'folder_id' => $folderId
                ]);
                return $folderId;
            } else {
                Log::info('User folder not found in Google Drive.', [
                     'email' => $email,
                    'searched_name' => $folderName,
                    'parent_id' => $rootFolderId
                ]);
                return null;
            }
        } catch (Exception $e) {
            Log::error('Failed to search for user folder in Google Drive.', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            // Re-throw exception for the caller to handle
            throw $e;
        }
    }

     /**
     * Gets the folder ID for a user, creating the folder if it doesn't exist.
     * Uses findUserFolderId first, then creates if necessary.
     *
     * @param string $email The user's email address.
     * @return string The Google Drive folder ID (existing or newly created).
     * @throws Exception If folder cannot be found or created.
     */
    public function getOrCreateUserFolderId(string $email): string
    {
        $folderId = $this->findUserFolderId($email);

        if ($folderId) {
            return $folderId;
        }

        // If not found, create it
        try {
            $service = $this->getService();
            $sanitizedEmail = $this->sanitizeEmailForFolderName($email);
            $folderName = "User: {$sanitizedEmail}";
            $rootFolderId = $this->getRootFolderId();

            Log::info('User folder not found, creating new folder in Google Drive.', [
                 'email' => $email,
                 'folder_name' => $folderName,
                 'parent_id' => $rootFolderId
            ]);

            $folderMetadata = new DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$rootFolderId]
            ]);

            $createdFolder = $service->files->create($folderMetadata, ['fields' => 'id']);
            $newFolderId = $createdFolder->getId();

            Log::info('Successfully created new user folder in Google Drive.', [
                 'email' => $email,
                 'folder_name' => $folderName,
                 'folder_id' => $newFolderId
            ]);

            return $newFolderId;

        } catch (Exception $e) {
            Log::error('Failed to create user folder in Google Drive.', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw after logging
        }
    }


    /**
     * Deletes a Google Drive folder (moves it to trash).
     *
     * @param string $folderId The ID of the folder to delete.
     * @return bool True on success, false otherwise.
     * @throws Exception If the API call fails.
     */
    public function deleteFolder(string $folderId): bool
    {
        try {
            $service = $this->getService();
            Log::info('Attempting to delete Google Drive folder.', ['folder_id' => $folderId]);
            $service->files->delete($folderId);
             Log::info('Successfully deleted Google Drive folder (moved to trash).', ['folder_id' => $folderId]);
            return true;
        } catch (Exception $e) {
             // Check for specific "not found" errors if needed (e.g., $e->getCode() == 404)
            Log::error('Failed to delete Google Drive folder.', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
             // Depending on requirements, you might want to return false or re-throw
             // Returning false allows the caller to potentially continue other operations
             // throw $e; // Uncomment if failure should halt the process
             return false;
        }
    }

    /**
     * Uploads a file from local storage to a specified Google Drive folder.
     *
     * @param string $localRelativePath The path of the file in Laravel's public storage (e.g., 'uploads/filename.ext').
     * @param string $driveFolderId The ID of the Google Drive folder to upload into.
     * @param string $originalFilename The desired name of the file in Google Drive.
     * @param string $mimeType The MIME type of the file.
     * @param string|null $description Optional description for the file in Google Drive.
     * @return string The Google Drive File ID of the newly uploaded file.
     * @throws Exception If the local file doesn't exist or the upload fails.
     */
    public function uploadFile(
        string $localRelativePath,
        string $driveFolderId,
        string $originalFilename,
        string $mimeType,
        ?string $description = null
    ): string {
        // Verify local file exists
        if (!Storage::disk('public')->exists($localRelativePath)) {
            Log::error('Local file not found for Google Drive upload.', ['path' => $localRelativePath]);
            throw new Exception("Local file not found: {$localRelativePath}");
        }

        try {
            $service = $this->getService();
            Log::info('Starting file upload to Google Drive.', [
                'local_path' => $localRelativePath,
                'drive_folder_id' => $driveFolderId,
                'target_name' => $originalFilename
            ]);

            // Prepare file metadata
            $fileMetadata = new DriveFile([
                'name' => $originalFilename,
                'parents' => [$driveFolderId]
            ]);
            if ($description) {
                $fileMetadata->setDescription($description);
            }

            // Get file content (consider streaming for very large files)
            $content = Storage::disk('public')->get($localRelativePath);
            if ($content === false) {
                 Log::error('Failed to read local file content.', ['path' => $localRelativePath]);
                 throw new Exception("Could not read local file: {$localRelativePath}");
            }

            // Perform the upload
            $createdFile = $service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart', // Suitable for most file sizes, 'resumable' is better for large files
                'fields' => 'id' // Request only the ID back
            ]);

            $newFileId = $createdFile->getId();
            Log::info('File successfully uploaded to Google Drive.', [
                 'local_path' => $localRelativePath,
                 'drive_file_id' => $newFileId,
                 'drive_folder_id' => $driveFolderId
            ]);

            return $newFileId;

        } catch (Exception $e) {
            Log::error('Failed to upload file to Google Drive.', [
                'local_path' => $localRelativePath,
                'drive_folder_id' => $driveFolderId,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw
        }
    }

    /**
     * Deletes a file from Google Drive.
     *
     * @param string $fileId The ID of the file to delete.
     * @return bool True on success, false otherwise.
     * @throws Exception If the API call fails.
     */
    public function deleteFile(string $fileId): bool
    {
        try {
            $service = $this->getService();
            Log::info('Attempting to delete Google Drive file.', ['file_id' => $fileId]);
            $service->files->delete($fileId);
            Log::info('Successfully deleted Google Drive file.', ['file_id' => $fileId]);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to delete Google Drive file.', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
