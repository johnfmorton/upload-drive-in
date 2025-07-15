<?php

namespace App\Services;

use App\Models\User;
use App\Models\GoogleDriveToken;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
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

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('cloud-storage.providers.google-drive.client_id'));
        $this->client->setClientSecret(config('cloud-storage.providers.google-drive.client_secret'));
        $this->client->addScope(Drive::DRIVE_FILE);
        $this->client->addScope(Drive::DRIVE);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }



    /**
     * Returns the configured Google Drive Root Folder ID.
     *
     * @return string The Root Folder ID.
     * @throws Exception If the root folder ID is not configured.
     */
    public function getRootFolderId(): string
    {
        $rootFolderId = config('cloud-storage.providers.google-drive.root_folder_id');
        if (empty($rootFolderId)) {
            Log::error('Google Drive root folder ID is not configured.', ['config_key' => 'cloud-storage.providers.google-drive.root_folder_id']);
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
     * @param User $user The user whose Google Drive to search in.
     * @param string $email The user's email address.
     * @return string|null The Google Drive folder ID if found, otherwise null.
     * @throws Exception If Google Drive API interaction fails.
     */
    public function findUserFolderId(User $user, string $email): ?string
    {
        try {
            $service = $this->getDriveService($user);
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
     * @param User $user The user whose Google Drive to use.
     * @param string $email The user's email address.
     * @return string The Google Drive folder ID (existing or newly created).
     * @throws Exception If folder cannot be found or created.
     */
    public function getOrCreateUserFolderId(User $user, string $email): string
    {
        $folderId = $this->findUserFolderId($user, $email);

        if ($folderId) {
            return $folderId;
        }

        // If not found, create it
        try {
            $service = $this->getDriveService($user);
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
     * @param User $user The user whose Google Drive to use.
     * @param string $folderId The ID of the folder to delete.
     * @return bool True on success, false otherwise.
     * @throws Exception If the API call fails.
     */
    public function deleteFolder(User $user, string $folderId): bool
    {
        try {
            $service = $this->getDriveService($user);
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
     * @param User $user The user whose Google Drive to use.
     * @param string $localRelativePath The path of the file in Laravel's public storage (e.g., 'uploads/filename.ext').
     * @param string $driveFolderId The ID of the Google Drive folder to upload into.
     * @param string $originalFilename The desired name of the file in Google Drive.
     * @param string $mimeType The MIME type of the file.
     * @param string|null $description Optional description for the file in Google Drive.
     * @return string The Google Drive File ID of the newly uploaded file.
     * @throws Exception If the local file doesn't exist or the upload fails.
     */
    public function uploadFile(
        User $user,
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
            $service = $this->getDriveService($user);
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
     * @param User $user The user whose Google Drive to use.
     * @param string $fileId The ID of the file to delete.
     * @return bool True on success, false otherwise.
     * @throws Exception If the API call fails.
     */
    public function deleteFile(User $user, string $fileId): bool
    {
        try {
            $service = $this->getDriveService($user);
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

    /**
     * Get the authorization URL for a specific user.
     */
    public function getAuthUrl(User $user): string
    {
        // Set the appropriate redirect URI based on user role
        if ($user->isEmployee()) {
            $this->client->setRedirectUri(route('employee.google-drive.callback', ['username' => $user->username]));
        } else {
            $this->client->setRedirectUri(route('admin.cloud-storage.google-drive.callback'));
        }

        return $this->client->createAuthUrl();
    }

    /**
     * Handle the OAuth callback and store the token for the user.
     */
    public function handleCallback(User $user, string $code): void
    {
        // Set the appropriate redirect URI based on user role
        if ($user->isEmployee()) {
            $this->client->setRedirectUri(route('employee.google-drive.callback', ['username' => $user->username]));
        } else {
            $this->client->setRedirectUri(route('admin.cloud-storage.google-drive.callback'));
        }

        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (!isset($token['access_token'])) {
            throw new Exception('Failed to get access token from Google');
        }

        // Store or update the token
        GoogleDriveToken::updateOrCreate(
            ['user_id' => $user->id],
            [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'token_type' => $token['token_type'] ?? 'Bearer',
                'expires_at' => isset($token['expires_in'])
                    ? Carbon::now()->addSeconds($token['expires_in'])
                    : null,
                'scopes' => $this->client->getScopes(),
            ]
        );
    }

    /**
     * Get an authenticated Google Drive service for a specific user.
     */
    public function getDriveService(User $user): Drive
    {
        $token = $this->getValidToken($user);
        $this->client->setAccessToken([
            'access_token' => $token->access_token,
            'refresh_token' => $token->refresh_token,
            'token_type' => $token->token_type,
            'expires_at' => $token->expires_at?->timestamp,
        ]);

        return new Drive($this->client);
    }

    /**
     * Get a valid token for the user, refreshing if necessary.
     */
    protected function getValidToken(User $user): GoogleDriveToken
    {
        $token = GoogleDriveToken::where('user_id', $user->id)->first();
        if (!$token) {
            throw new Exception('User has not connected their Google Drive account.');
        }

        // Check if token needs refresh
        if ($token->expires_at && $token->expires_at->isPast()) {
            if (!$token->refresh_token) {
                throw new Exception('No refresh token available for user.');
            }

            $this->client->setAccessToken([
                'refresh_token' => $token->refresh_token,
                'access_token' => $token->access_token,
                'token_type' => $token->token_type,
            ]);

            $newToken = $this->client->fetchAccessTokenWithRefreshToken();

            $token->update([
                'access_token' => $newToken['access_token'],
                'expires_at' => isset($newToken['expires_in'])
                    ? Carbon::now()->addSeconds($newToken['expires_in'])
                    : null,
            ]);
        }

        return $token;
    }

    /**
     * Disconnect Google Drive for a user.
     */
    public function disconnect(User $user): void
    {
        $token = GoogleDriveToken::where('user_id', $user->id)->first();
        if ($token) {
            // Try to revoke the token with Google
            try {
                $this->client->setAccessToken([
                    'access_token' => $token->access_token,
                    'token_type' => $token->token_type,
                ]);
                $this->client->revokeToken();
            } catch (Exception $e) {
                Log::warning('Failed to revoke Google token', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Delete the token from our database regardless
            $token->delete();
        }
    }

    /**
     * Upload a file to Google Drive using a specific user's credentials.
     * This method handles both employee and admin uploads.
     */
    public function uploadFileForUser(
        User $targetUser,
        string $localRelativePath,
        string $clientEmail,
        string $originalFilename,
        string $mimeType,
        ?string $description = null
    ): string {
        $driveService = null;
        $rootFolderId = null;

        // Try to get the employee's Drive service if they have it connected
        if ($targetUser->isEmployee() && $targetUser->hasGoogleDriveConnected()) {
            try {
                $driveService = $this->getDriveService($targetUser);
                $rootFolderId = $targetUser->google_drive_root_folder_id ?? config('cloud-storage.providers.google-drive.root_folder_id');
                Log::info('Using employee Google Drive for upload', [
                    'employee_id' => $targetUser->id,
                    'employee_email' => $targetUser->email
                ]);
            } catch (Exception $e) {
                Log::warning('Failed to get employee Google Drive service, falling back to admin', [
                    'employee_id' => $targetUser->id,
                    'error' => $e->getMessage()
                ]);
                $driveService = null;
            }
        }

        // Fallback to admin's Drive if employee doesn't have Drive connected or there was an error
        if (!$driveService) {
            // Find an admin user with Google Drive connected
            $adminUser = User::where('role', \App\Enums\UserRole::ADMIN)
                ->whereHas('googleDriveToken')
                ->first();

            if ($adminUser && $adminUser->hasGoogleDriveConnected()) {
                try {
                    $driveService = $this->getDriveService($adminUser);
                    $rootFolderId = config('cloud-storage.providers.google-drive.root_folder_id');
                    Log::info('Using admin Google Drive for upload as fallback', [
                        'admin_id' => $adminUser->id,
                        'target_employee_id' => $targetUser->id
                    ]);
                } catch (Exception $e) {
                    Log::error('Failed to get admin Google Drive service', [
                        'admin_id' => $adminUser->id,
                        'error' => $e->getMessage()
                    ]);
                    throw new Exception('No valid Google Drive connection available for upload. Please ensure either the employee or an admin has connected their Google Drive account.');
                }
            } else {
                Log::error('No Google Drive connection available - no admin user with Google Drive token found', [
                    'target_user_id' => $targetUser->id
                ]);
                throw new Exception('No Google Drive connection available. Please ensure either the employee has connected their Google Drive account, or an admin user has connected their Google Drive account.');
            }
        }

        if (!$driveService) {
            throw new Exception('Unable to establish Google Drive connection for upload.');
        }

        // Create or find the client folder
        $sanitizedEmail = $this->sanitizeEmailForFolderName($clientEmail);
        $folderName = "User: {$sanitizedEmail}";

        // Search for existing folder
        $query = sprintf(
            "name = '%s' and mimeType = 'application/vnd.google-apps.folder' and '%s' in parents and trashed = false",
            addslashes($folderName),
            addslashes($rootFolderId)
        );

        $results = $driveService->files->listFiles([
            'q' => $query,
            'fields' => 'files(id, name)',
            'pageSize' => 1
        ]);

        if (count($results->getFiles()) > 0) {
            $userFolderId = $results->getFiles()[0]->getId();
            Log::info('Found existing user folder.', ['folder_id' => $userFolderId]);
        } else {
            // Create new folder
            $folderMetadata = new \Google\Service\Drive\DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$rootFolderId]
            ]);

            $createdFolder = $driveService->files->create($folderMetadata, ['fields' => 'id']);
            $userFolderId = $createdFolder->getId();
            Log::info('Created new user folder.', ['folder_id' => $userFolderId]);
        }

        // Verify local file exists
        if (!Storage::disk('public')->exists($localRelativePath)) {
            throw new Exception("Local file not found: {$localRelativePath}");
        }

        // Prepare file metadata
        $fileMetadata = new \Google\Service\Drive\DriveFile([
            'name' => $originalFilename,
            'parents' => [$userFolderId]
        ]);

        if ($description) {
            $fileMetadata->setDescription($description);
        }

        // Get file content
        $content = Storage::disk('public')->get($localRelativePath);
        if ($content === false) {
            throw new Exception("Could not read local file: {$localRelativePath}");
        }

        // Perform the upload
        $createdFile = $driveService->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id'
        ]);

        return $createdFile->getId();
    }
}
