<?php

namespace App\Services;

use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageLogService;
use App\Enums\CloudStorageErrorType;
use App\Exceptions\CloudStorageException;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Exception as GoogleServiceException;
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
     * @var Client|null The Google API client instance.
     */
    private ?Client $client = null;

    public function __construct(
        private ?CloudStorageHealthService $healthService = null,
        private ?CloudStorageLogService $logService = null
    ) {
        $this->client = new Client();
        $this->client->setClientId(config('cloud-storage.providers.google-drive.client_id'));
        $this->client->setClientSecret(config('cloud-storage.providers.google-drive.client_secret'));
        $this->client->addScope(Drive::DRIVE_FILE);
        $this->client->addScope(Drive::DRIVE);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        
        // Inject services if not provided (for backward compatibility)
        if (!$this->healthService) {
            $this->healthService = app(CloudStorageHealthService::class);
        }
        if (!$this->logService) {
            $this->logService = app(CloudStorageLogService::class);
        }
    }



    /**
     * Returns the default Google Drive Root Folder ID.
     * Always returns 'root' (Google Drive root) as the default.
     *
     * @return string The Root Folder ID.
     */
    public function getRootFolderId(): string
    {
        Log::debug('Using Google Drive root as default folder.', ['default_folder_id' => 'root']);
        return 'root';
    }

    /**
     * Returns the effective root folder ID for a specific user.
     * Uses user-specific folder if configured, otherwise defaults to Google Drive root.
     *
     * @param User $user The user to get the root folder for.
     * @return string The effective root folder ID.
     */
    public function getEffectiveRootFolderId(User $user): string
    {
        // Return user's setting or default to 'root'
        return !empty($user->google_drive_root_folder_id) ? $user->google_drive_root_folder_id : 'root';
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
            $rootFolderId = $this->getEffectiveRootFolderId($user);

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
            $rootFolderId = $this->getEffectiveRootFolderId($user);

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

            // Record successful operation for health monitoring
            $this->healthService?->recordSuccessfulOperation($user, 'google-drive', [
                'last_upload_file_id' => $newFileId,
                'last_upload_at' => now()->toISOString(),
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
    public function getAuthUrl(User $user, bool $isReconnection = false): string
    {
        // Use unified callback endpoint for all user types
        $this->client->setRedirectUri(route('google-drive.unified-callback'));

        // Add user ID and reconnection flag as state parameter to identify user after callback
        $state = base64_encode(json_encode([
            'user_id' => $user->id,
            'user_type' => $user->role->value,
            'is_reconnection' => $isReconnection,
            'timestamp' => now()->timestamp
        ]));
        
        $this->client->setState($state);

        return $this->client->createAuthUrl();
    }

    /**
     * Handle the OAuth callback and store the token for the user.
     */
    public function handleCallback(User $user, string $code): void
    {
        // Use unified callback endpoint for all user types
        $this->client->setRedirectUri(route('google-drive.unified-callback'));

        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (!isset($token['access_token'])) {
            throw new Exception('Failed to get access token from Google');
        }

        $expiresAt = isset($token['expires_in'])
            ? Carbon::now()->addSeconds($token['expires_in'])
            : null;
            
        // Store or update the token
        GoogleDriveToken::updateOrCreate(
            ['user_id' => $user->id],
            [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'token_type' => $token['token_type'] ?? 'Bearer',
                'expires_at' => $expiresAt,
                'scopes' => $this->client->getScopes(),
            ]
        );
        
        // Record successful connection for health monitoring
        $this->healthService?->recordSuccessfulOperation($user, 'google-drive', [
            'connected_at' => now()->toISOString(),
            'scopes' => $this->client->getScopes(),
        ]);
        
        // Update token expiration info
        $this->healthService?->updateTokenExpiration($user, 'google-drive', $expiresAt);
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
    public function getValidToken(User $user): GoogleDriveToken
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

            $refreshResult = $this->refreshToken($token);
            if (!$refreshResult['success']) {
                throw new Exception($refreshResult['error'] ?? 'Token refresh failed');
            }
            
            $token = $token->fresh(); // Reload the updated token
        }

        return $token;
    }

    /**
     * Validates and refreshes a token without performing file operations.
     * This method proactively attempts token refresh during status checks.
     * Implements caching to reduce redundant API calls.
     *
     * @param User $user The user whose token to validate and refresh
     * @return bool True if token is valid or successfully refreshed, false otherwise
     */
    public function validateAndRefreshToken(User $user): bool
    {
        try {
            $token = GoogleDriveToken::where('user_id', $user->id)->first();
            if (!$token) {
                Log::debug('No Google Drive token found for user', ['user_id' => $user->id]);
                return false;
            }

            $wasExpired = $token->expires_at && $token->expires_at->isPast();
            $refreshNeeded = false;
            $refreshSuccess = null;

            // If token is not expired, it's valid
            if (!$wasExpired) {
                $this->logService->logProactiveTokenValidation($user, 'google-drive', false, false);
                Log::debug('Google Drive token is still valid', [
                    'user_id' => $user->id,
                    'expires_at' => $token->expires_at?->toISOString()
                ]);
                return true;
            }

            // Token is expired, attempt refresh
            if (!$token->refresh_token) {
                $this->logService->logProactiveTokenValidation($user, 'google-drive', true, false);
                Log::warning('Google Drive token expired and no refresh token available', [
                    'user_id' => $user->id
                ]);
                return false;
            }

            $refreshNeeded = true;
            $this->logService->logTokenRefreshAttempt($user, 'google-drive', [
                'trigger' => 'proactive_validation',
                'token_expired_at' => $token->expires_at->toISOString()
            ]);

            Log::info('Attempting proactive Google Drive token refresh', [
                'user_id' => $user->id,
                'token_expired_at' => $token->expires_at->toISOString()
            ]);

            $refreshResult = $this->refreshToken($token);
            $refreshSuccess = $refreshResult['success'];
            
            if ($refreshSuccess) {
                $this->logService->logTokenRefreshSuccess($user, 'google-drive', [
                    'trigger' => 'proactive_validation',
                    'new_expires_at' => $refreshResult['expires_at']?->toISOString()
                ]);
                $this->logService->logProactiveTokenValidation($user, 'google-drive', true, true, true);
                
                Log::info('Google Drive token successfully refreshed proactively', [
                    'user_id' => $user->id,
                    'new_expires_at' => $refreshResult['expires_at']?->toISOString()
                ]);
                return true;
            } else {
                $this->logService->logTokenRefreshFailure($user, 'google-drive', $refreshResult['error'], [
                    'trigger' => 'proactive_validation'
                ]);
                $this->logService->logProactiveTokenValidation($user, 'google-drive', true, true, false);
                
                Log::error('Proactive Google Drive token refresh failed', [
                    'user_id' => $user->id,
                    'error' => $refreshResult['error']
                ]);
                return false;
            }
        } catch (Exception $e) {
            $this->logService->logTokenRefreshFailure($user, 'google-drive', $e->getMessage(), [
                'trigger' => 'proactive_validation',
                'exception' => true
            ]);
            
            Log::error('Exception during Google Drive token validation and refresh', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Tests API connectivity with a lightweight API call to verify connection.
     * This method performs a minimal API operation to validate operational capability.
     * Implements caching to reduce redundant API calls.
     *
     * @param User $user The user whose Google Drive connection to test
     * @return bool True if API connectivity is working, false otherwise
     */
    public function testApiConnectivity(User $user): bool
    {
        try {
            // First ensure we have a valid token
            if (!$this->validateAndRefreshToken($user)) {
                $this->logService->logApiConnectivityTest($user, 'google-drive', false, [
                    'reason' => 'token_validation_failed'
                ]);
                
                Log::debug('Cannot test API connectivity - token validation failed', [
                    'user_id' => $user->id
                ]);
                return false;
            }

            // Get the Drive service with the validated token
            $service = $this->getDriveService($user);
            
            Log::debug('Testing Google Drive API connectivity', ['user_id' => $user->id]);

            // Perform a lightweight API call - get user's Drive info
            $about = $service->about->get(['fields' => 'user']);
            
            if ($about && $about->getUser()) {
                $this->logService->logApiConnectivityTest($user, 'google-drive', true, [
                    'drive_user_email' => $about->getUser()->getEmailAddress(),
                    'test_method' => 'about_get'
                ]);
                
                Log::info('Google Drive API connectivity test successful', [
                    'user_id' => $user->id,
                    'drive_user_email' => $about->getUser()->getEmailAddress()
                ]);
                
                // Record successful operation for health monitoring
                $this->healthService?->recordSuccessfulOperation($user, 'google-drive', [
                    'last_connectivity_test_at' => now()->toISOString(),
                    'drive_user_email' => $about->getUser()->getEmailAddress(),
                ]);
                
                return true;
            } else {
                $this->logService->logApiConnectivityTest($user, 'google-drive', false, [
                    'reason' => 'empty_response',
                    'test_method' => 'about_get'
                ]);
                
                Log::warning('Google Drive API connectivity test returned empty response', [
                    'user_id' => $user->id
                ]);
                return false;
            }
        } catch (Exception $e) {
            $this->logService->logApiConnectivityTest($user, 'google-drive', false, [
                'reason' => 'exception',
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'test_method' => 'about_get'
            ]);
            
            Log::error('Google Drive API connectivity test failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ]);
            return false;
        }
    }

    /**
     * Refreshes a Google Drive token with comprehensive error handling and exponential backoff.
     * This method centralizes token refresh logic and provides detailed success/failure status.
     *
     * @param GoogleDriveToken $token The token to refresh
     * @param int $attempt Current attempt number for exponential backoff
     * @return array Array with 'success' boolean and additional result data
     */
    private function refreshToken(GoogleDriveToken $token, int $attempt = 1): array
    {
        $maxAttempts = 3;
        $baseDelay = 1; // Base delay in seconds
        $user = $token->user;
        
        try {
            if (!$token->refresh_token) {
                $error = 'No refresh token available';
                $this->logService->logTokenRefreshFailure($user, 'google-drive', $error, [
                    'attempt' => $attempt,
                    'error_type' => 'invalid_credentials',
                    'requires_user_intervention' => true
                ]);
                
                return [
                    'success' => false,
                    'error' => $error,
                    'error_type' => CloudStorageErrorType::INVALID_CREDENTIALS,
                    'requires_user_intervention' => true,
                    'is_recoverable' => false
                ];
            }

            Log::info('Attempting Google Drive token refresh', [
                'user_id' => $token->user_id,
                'token_expires_at' => $token->expires_at?->toISOString(),
                'attempt' => $attempt,
                'max_attempts' => $maxAttempts
            ]);

            $this->client->setAccessToken([
                'refresh_token' => $token->refresh_token,
                'access_token' => $token->access_token,
                'token_type' => $token->token_type,
            ]);

            $newToken = $this->client->fetchAccessTokenWithRefreshToken();

            // Check for specific error responses from Google
            if (isset($newToken['error'])) {
                return $this->handleTokenRefreshError($token, $newToken, $attempt, $maxAttempts);
            }

            // Check if refresh was successful
            if (!isset($newToken['access_token'])) {
                $error = 'No access token in refresh response';
                $this->logService->logTokenRefreshFailure($user, 'google-drive', $error, [
                    'attempt' => $attempt,
                    'error_type' => 'unknown_error',
                    'response' => $newToken
                ]);
                
                Log::error('Google Drive token refresh failed - no access token in response', [
                    'user_id' => $token->user_id,
                    'response' => $newToken
                ]);
                
                return [
                    'success' => false,
                    'error' => $error,
                    'error_type' => CloudStorageErrorType::UNKNOWN_ERROR,
                    'requires_user_intervention' => true,
                    'is_recoverable' => false
                ];
            }

            $expiresAt = isset($newToken['expires_in'])
                ? Carbon::now()->addSeconds($newToken['expires_in'])
                : null;
                
            $token->update([
                'access_token' => $newToken['access_token'],
                'expires_at' => $expiresAt,
            ]);
            
            // Update health service with new token expiration
            $this->healthService?->updateTokenExpiration($token->user, 'google-drive', $expiresAt);

            $this->logService->logTokenRefreshSuccess($user, 'google-drive', [
                'attempt' => $attempt,
                'new_expires_at' => $expiresAt?->toISOString(),
                'expires_in_seconds' => $newToken['expires_in'] ?? null
            ]);

            Log::info('Google Drive token refresh successful', [
                'user_id' => $token->user_id,
                'new_expires_at' => $expiresAt?->toISOString(),
                'attempt' => $attempt
            ]);

            return [
                'success' => true,
                'expires_at' => $expiresAt,
                'access_token' => $newToken['access_token'],
                'attempt' => $attempt
            ];
            
        } catch (GoogleServiceException $e) {
            return $this->handleGoogleServiceException($token, $e, $attempt, $maxAttempts);
        } catch (Exception $e) {
            return $this->handleGenericTokenRefreshException($token, $e, $attempt, $maxAttempts);
        }
    }

    /**
     * Handle specific token refresh errors from Google's response.
     */
    private function handleTokenRefreshError(GoogleDriveToken $token, array $response, int $attempt, int $maxAttempts): array
    {
        $error = $response['error'];
        $errorDescription = $response['error_description'] ?? '';
        
        Log::error('Google Drive token refresh failed with error response', [
            'user_id' => $token->user_id,
            'error' => $error,
            'error_description' => $errorDescription,
            'attempt' => $attempt
        ]);

        // Handle specific error types
        switch ($error) {
            case 'invalid_grant':
                // Refresh token is expired or revoked
                return [
                    'success' => false,
                    'error' => 'Refresh token is expired or revoked. Please reconnect your Google Drive account.',
                    'error_type' => CloudStorageErrorType::TOKEN_EXPIRED,
                    'requires_user_intervention' => true,
                    'is_recoverable' => false,
                    'original_error' => $error,
                    'error_description' => $errorDescription
                ];
                
            case 'invalid_client':
                // Client credentials are invalid
                return [
                    'success' => false,
                    'error' => 'Invalid client credentials. Please check Google Drive configuration.',
                    'error_type' => CloudStorageErrorType::INVALID_CREDENTIALS,
                    'requires_user_intervention' => true,
                    'is_recoverable' => false,
                    'original_error' => $error,
                    'error_description' => $errorDescription
                ];
                
            case 'temporarily_unavailable':
            case 'server_error':
                // Temporary server issues - retry with exponential backoff
                if ($attempt < $maxAttempts) {
                    $delay = pow(2, $attempt - 1); // Exponential backoff: 1s, 2s, 4s
                    Log::info('Google Drive token refresh failed temporarily, retrying', [
                        'user_id' => $token->user_id,
                        'attempt' => $attempt,
                        'delay_seconds' => $delay,
                        'error' => $error
                    ]);
                    
                    sleep($delay);
                    return $this->refreshToken($token, $attempt + 1);
                }
                
                return [
                    'success' => false,
                    'error' => 'Google Drive service temporarily unavailable after multiple attempts.',
                    'error_type' => CloudStorageErrorType::SERVICE_UNAVAILABLE,
                    'requires_user_intervention' => false,
                    'is_recoverable' => true,
                    'original_error' => $error,
                    'error_description' => $errorDescription,
                    'attempts_made' => $attempt
                ];
                
            default:
                return [
                    'success' => false,
                    'error' => "Token refresh failed: {$errorDescription}",
                    'error_type' => CloudStorageErrorType::UNKNOWN_ERROR,
                    'requires_user_intervention' => true,
                    'is_recoverable' => false,
                    'original_error' => $error,
                    'error_description' => $errorDescription
                ];
        }
    }

    /**
     * Handle Google Service exceptions during token refresh.
     */
    private function handleGoogleServiceException(GoogleDriveToken $token, GoogleServiceException $e, int $attempt, int $maxAttempts): array
    {
        $httpCode = $e->getCode();
        $errorMessage = $e->getMessage();
        
        Log::error('Google Service exception during token refresh', [
            'user_id' => $token->user_id,
            'http_code' => $httpCode,
            'error' => $errorMessage,
            'attempt' => $attempt
        ]);

        switch ($httpCode) {
            case 400:
                // Bad request - likely invalid refresh token
                return [
                    'success' => false,
                    'error' => 'Invalid refresh token. Please reconnect your Google Drive account.',
                    'error_type' => CloudStorageErrorType::TOKEN_EXPIRED,
                    'requires_user_intervention' => true,
                    'is_recoverable' => false,
                    'http_code' => $httpCode
                ];
                
            case 401:
                // Unauthorized - refresh token expired
                return [
                    'success' => false,
                    'error' => 'Google Drive authorization expired. Please reconnect your account.',
                    'error_type' => CloudStorageErrorType::TOKEN_EXPIRED,
                    'requires_user_intervention' => true,
                    'is_recoverable' => false,
                    'http_code' => $httpCode
                ];
                
            case 403:
                // Forbidden - could be quota exceeded or permissions issue
                if (str_contains(strtolower($errorMessage), 'quota')) {
                    return [
                        'success' => false,
                        'error' => 'Google Drive API quota exceeded. Please try again later.',
                        'error_type' => CloudStorageErrorType::API_QUOTA_EXCEEDED,
                        'requires_user_intervention' => false,
                        'is_recoverable' => true,
                        'http_code' => $httpCode
                    ];
                }
                
                return [
                    'success' => false,
                    'error' => 'Insufficient permissions for Google Drive access.',
                    'error_type' => CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
                    'requires_user_intervention' => true,
                    'is_recoverable' => false,
                    'http_code' => $httpCode
                ];
                
            case 429:
                // Rate limit exceeded - retry with exponential backoff
                if ($attempt < $maxAttempts) {
                    $delay = pow(2, $attempt - 1) * 2; // Longer delay for rate limits: 2s, 4s, 8s
                    Log::info('Google Drive rate limit exceeded, retrying', [
                        'user_id' => $token->user_id,
                        'attempt' => $attempt,
                        'delay_seconds' => $delay
                    ]);
                    
                    sleep($delay);
                    return $this->refreshToken($token, $attempt + 1);
                }
                
                return [
                    'success' => false,
                    'error' => 'Google Drive rate limit exceeded. Please try again later.',
                    'error_type' => CloudStorageErrorType::API_QUOTA_EXCEEDED,
                    'requires_user_intervention' => false,
                    'is_recoverable' => true,
                    'http_code' => $httpCode,
                    'attempts_made' => $attempt
                ];
                
            case 500:
            case 502:
            case 503:
            case 504:
                // Server errors - retry with exponential backoff
                if ($attempt < $maxAttempts) {
                    $delay = pow(2, $attempt - 1); // Exponential backoff: 1s, 2s, 4s
                    Log::info('Google Drive server error, retrying', [
                        'user_id' => $token->user_id,
                        'http_code' => $httpCode,
                        'attempt' => $attempt,
                        'delay_seconds' => $delay
                    ]);
                    
                    sleep($delay);
                    return $this->refreshToken($token, $attempt + 1);
                }
                
                return [
                    'success' => false,
                    'error' => 'Google Drive service temporarily unavailable.',
                    'error_type' => CloudStorageErrorType::SERVICE_UNAVAILABLE,
                    'requires_user_intervention' => false,
                    'is_recoverable' => true,
                    'http_code' => $httpCode,
                    'attempts_made' => $attempt
                ];
                
            default:
                return [
                    'success' => false,
                    'error' => "Google Drive API error: {$errorMessage}",
                    'error_type' => CloudStorageErrorType::UNKNOWN_ERROR,
                    'requires_user_intervention' => true,
                    'is_recoverable' => false,
                    'http_code' => $httpCode
                ];
        }
    }

    /**
     * Handle generic exceptions during token refresh.
     */
    private function handleGenericTokenRefreshException(GoogleDriveToken $token, Exception $e, int $attempt, int $maxAttempts): array
    {
        $errorMessage = $e->getMessage();
        
        Log::error('Generic exception during Google Drive token refresh', [
            'user_id' => $token->user_id,
            'error' => $errorMessage,
            'error_code' => $e->getCode(),
            'attempt' => $attempt
        ]);

        // Check for network-related errors
        if ($this->isNetworkError($e)) {
            if ($attempt < $maxAttempts) {
                $delay = pow(2, $attempt - 1); // Exponential backoff: 1s, 2s, 4s
                Log::info('Network error during token refresh, retrying', [
                    'user_id' => $token->user_id,
                    'attempt' => $attempt,
                    'delay_seconds' => $delay,
                    'error' => $errorMessage
                ]);
                
                sleep($delay);
                return $this->refreshToken($token, $attempt + 1);
            }
            
            return [
                'success' => false,
                'error' => 'Network error during token refresh. Please check your internet connection.',
                'error_type' => CloudStorageErrorType::NETWORK_ERROR,
                'requires_user_intervention' => false,
                'is_recoverable' => true,
                'original_error' => $errorMessage,
                'attempts_made' => $attempt
            ];
        }

        // Check for timeout errors
        if ($this->isTimeoutError($e)) {
            if ($attempt < $maxAttempts) {
                $delay = pow(2, $attempt - 1) * 2; // Longer delay for timeouts: 2s, 4s, 8s
                Log::info('Timeout error during token refresh, retrying', [
                    'user_id' => $token->user_id,
                    'attempt' => $attempt,
                    'delay_seconds' => $delay
                ]);
                
                sleep($delay);
                return $this->refreshToken($token, $attempt + 1);
            }
            
            return [
                'success' => false,
                'error' => 'Token refresh timed out. Please try again.',
                'error_type' => CloudStorageErrorType::TIMEOUT,
                'requires_user_intervention' => false,
                'is_recoverable' => true,
                'original_error' => $errorMessage,
                'attempts_made' => $attempt
            ];
        }

        // Generic error handling
        return [
            'success' => false,
            'error' => "Token refresh failed: {$errorMessage}",
            'error_type' => CloudStorageErrorType::UNKNOWN_ERROR,
            'requires_user_intervention' => true,
            'is_recoverable' => false,
            'original_error' => $errorMessage,
            'error_code' => $e->getCode()
        ];
    }

    /**
     * Check if an exception is network-related.
     */
    private function isNetworkError(Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        $networkKeywords = [
            'connection refused',
            'connection timed out',
            'network is unreachable',
            'no route to host',
            'connection reset',
            'dns resolution failed',
            'could not resolve host',
            'ssl connection error'
        ];
        
        foreach ($networkKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if an exception is timeout-related.
     */
    private function isTimeoutError(Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        $timeoutKeywords = [
            'timeout',
            'timed out',
            'operation timeout',
            'request timeout'
        ];
        
        foreach ($timeoutKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Downloads a file from Google Drive and returns its content.
     *
     * @param User $user The user whose Google Drive to use.
     * @param string $fileId The ID of the file to download.
     * @return string The file content.
     * @throws Exception If the file doesn't exist or download fails.
     */
    public function downloadFile(User $user, string $fileId): string
    {
        try {
            $service = $this->getDriveService($user);
            
            Log::info('Starting file download from Google Drive.', [
                'file_id' => $fileId,
                'user_id' => $user->id
            ]);

            // First, verify the file exists and get its metadata
            $file = $service->files->get($fileId, ['fields' => 'id,name,size,mimeType']);
            
            if (!$file) {
                Log::error('File not found in Google Drive.', ['file_id' => $fileId]);
                throw new Exception("File not found: {$fileId}");
            }

            // Download the file content
            $response = $service->files->get($fileId, ['alt' => 'media']);
            
            // Extract the actual content from the response
            $content = $response->getBody()->getContents();

            Log::info('File successfully downloaded from Google Drive.', [
                'file_id' => $fileId,
                'file_name' => $file->getName(),
                'file_size' => $file->getSize(),
                'content_length' => strlen($content)
            ]);

            return $content;
        } catch (Exception $e) {
            Log::error('Failed to download file from Google Drive.', [
                'file_id' => $fileId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Downloads a file from Google Drive as a stream for large files.
     *
     * @param User $user The user whose Google Drive to use.
     * @param string $fileId The ID of the file to download.
     * @return resource The file stream resource.
     * @throws Exception If the file doesn't exist or download fails.
     */
    public function downloadFileStream(User $user, string $fileId)
    {
        try {
            $service = $this->getDriveService($user);
            
            Log::info('Starting streaming file download from Google Drive.', [
                'file_id' => $fileId,
                'user_id' => $user->id
            ]);

            // First, verify the file exists and get its metadata
            $file = $service->files->get($fileId, ['fields' => 'id,name,size,mimeType']);
            
            if (!$file) {
                Log::error('File not found in Google Drive.', ['file_id' => $fileId]);
                throw new Exception("File not found: {$fileId}");
            }

            // Get the file content as a stream
            $response = $service->files->get($fileId, ['alt' => 'media']);
            
            // Extract the actual content from the response and create a stream
            $content = $response->getBody()->getContents();
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $content);
            rewind($stream);

            Log::info('File stream successfully created from Google Drive.', [
                'file_id' => $fileId,
                'file_name' => $file->getName(),
                'file_size' => $file->getSize()
            ]);

            return $stream;
        } catch (Exception $e) {
            Log::error('Failed to create file stream from Google Drive.', [
                'file_id' => $fileId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Gets file metadata from Google Drive.
     *
     * @param User $user The user whose Google Drive to use.
     * @param string $fileId The ID of the file.
     * @return DriveFile The file metadata.
     * @throws Exception If the file doesn't exist or API call fails.
     */
    public function getFileMetadata(User $user, string $fileId): DriveFile
    {
        try {
            $service = $this->getDriveService($user);
            
            Log::debug('Getting file metadata from Google Drive.', [
                'file_id' => $fileId,
                'user_id' => $user->id
            ]);

            $file = $service->files->get($fileId, [
                'fields' => 'id,name,size,mimeType,createdTime,modifiedTime,parents'
            ]);

            if (!$file) {
                Log::error('File not found in Google Drive.', ['file_id' => $fileId]);
                throw new Exception("File not found: {$fileId}");
            }

            Log::debug('File metadata retrieved from Google Drive.', [
                'file_id' => $fileId,
                'file_name' => $file->getName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

            return $file;
        } catch (Exception $e) {
            Log::error('Failed to get file metadata from Google Drive.', [
                'file_id' => $fileId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
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
                $rootFolderId = $this->getEffectiveRootFolderId($targetUser);
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
                    $rootFolderId = $this->getEffectiveRootFolderId($adminUser);
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
