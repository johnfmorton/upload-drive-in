<?php

namespace App\Services;

use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageLogService;
use App\Services\TokenRefreshCoordinator;
use App\Services\GoogleDriveChunkedUploadService;
use App\Enums\CloudStorageErrorType;
use App\Enums\TokenRefreshErrorType;
use App\Exceptions\CloudStorageException;
use App\Traits\LogsDeprecationWarnings;
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
 * 
 * @deprecated Use CloudStorageManager with google-drive provider instead
 */
class GoogleDriveService
{
    use LogsDeprecationWarnings;


    /**
     * @var Client|null The Google API client instance.
     */
    private ?Client $client = null;

    public function __construct(
        private ?CloudStorageHealthService $healthService = null,
        private ?CloudStorageLogService $logService = null,
        private ?TokenRefreshMonitoringService $monitoringService = null,
        private ?TokenSecurityService $securityService = null,
        private ?GoogleApiConnectionPool $connectionPool = null
    ) {
        $this->client = new Client();
        $this->client->setClientId(config('cloud-storage.providers.google-drive.config.client_id'));
        $this->client->setClientSecret(config('cloud-storage.providers.google-drive.config.client_secret'));
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
        if (!$this->monitoringService) {
            $this->monitoringService = app(TokenRefreshMonitoringService::class);
        }
        if (!$this->securityService) {
            $this->securityService = app(TokenSecurityService::class);
        }
        if (!$this->connectionPool) {
            $this->connectionPool = app(GoogleApiConnectionPool::class);
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
     * @deprecated Use CloudStorageManager::getProvider('google-drive')->uploadFile() instead
     */
    public function uploadFile(
        User $user,
        string $localRelativePath,
        string $driveFolderId,
        string $originalFilename,
        string $mimeType,
        ?string $description = null
    ): string {
        $this->logDeprecationWarning(__METHOD__, 'CloudStorageManager::getProvider(\'google-drive\')->uploadFile()');
        
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
     * @deprecated Use CloudStorageManager::getProvider('google-drive')->getAuthUrl() instead
     */
    public function getAuthUrl(User $user, bool $isReconnection = false): string
    {
        $this->logDeprecationWarning(__METHOD__, 'CloudStorageManager::getProvider(\'google-drive\')->getAuthUrl()');
        
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
     * @deprecated Use CloudStorageManager::getProvider('google-drive')->handleAuthCallback() instead
     */
    public function handleCallback(User $user, string $code): void
    {
        $this->logDeprecationWarning(__METHOD__, 'CloudStorageManager::getProvider(\'google-drive\')->handleAuthCallback()');
        
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
        
        // Use connection pool if available for better performance
        if ($this->connectionPool) {
            return $this->connectionPool->getDriveService(
                config('cloud-storage.providers.google-drive.config.client_id'),
                config('cloud-storage.providers.google-drive.config.client_secret'),
                [
                    'access_token' => $token->access_token,
                    'refresh_token' => $token->refresh_token,
                    'token_type' => $token->token_type,
                    'expires_at' => $token->expires_at?->timestamp,
                ]
            );
        }
        
        // Fallback to original implementation
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

            $refreshResult = $this->refreshTokenInternal($token);
            if (!$refreshResult['success']) {
                throw new Exception($refreshResult['error'] ?? 'Token refresh failed');
            }
            
            $token = $token->fresh(); // Reload the updated token
        }

        return $token;
    }

    /**
     * Manually refresh a user's Google Drive token.
     * This method is used for manual token refresh requests from the dashboard.
     * 
     * @param User $user
     * @return RefreshResult
     */
    public function refreshToken(User $user): RefreshResult
    {
        $token = GoogleDriveToken::where('user_id', $user->id)->first();
        
        if (!$token) {
            return RefreshResult::failed('No token found for user', TokenRefreshErrorType::INVALID_REFRESH_TOKEN);
        }
        
        if (!$token->refresh_token) {
            return RefreshResult::failed('No refresh token available', TokenRefreshErrorType::INVALID_REFRESH_TOKEN);
        }
        
        if ($token->requires_user_intervention) {
            return RefreshResult::failed('Token requires user intervention', TokenRefreshErrorType::INVALID_REFRESH_TOKEN);
        }
        
        $refreshResult = $this->refreshTokenInternal($token);
        
        if ($refreshResult['success']) {
            return RefreshResult::successful($token->expires_at);
        } else {
            $errorType = $refreshResult['error_type'] ?? TokenRefreshErrorType::UNKNOWN_ERROR;
            return RefreshResult::failed($refreshResult['error'] ?? 'Token refresh failed', $errorType);
        }
    }

    /**
     * Validates and refreshes a token without performing file operations.
     * This method proactively attempts token refresh during status checks.
     * Uses TokenRefreshCoordinator for thread-safe operations and implements
     * proactive refresh logic (refresh 15 minutes before expiration).
     *
     * @param User $user The user whose token to validate and refresh
     * @return bool True if token is valid or successfully refreshed, false otherwise
     */
    public function validateAndRefreshToken(User $user): bool
    {
        $operationId = uniqid('validate_', true);
        
        try {
            $token = GoogleDriveToken::where('user_id', $user->id)->first();
            if (!$token) {
                Log::debug('No Google Drive token found for user', [
                    'user_id' => $user->id,
                    'operation_id' => $operationId
                ]);
                return false;
            }

            // Check if token is expiring soon (proactive refresh - 15 minutes before expiration)
            $isExpiringSoon = $token->isExpiringSoon(15);
            $hasExpired = $token->hasExpired();
            
            Log::info('Token validation initiated', [
                'user_id' => $user->id,
                'operation_id' => $operationId,
                'expires_at' => $token->expires_at?->toISOString(),
                'is_expiring_soon' => $isExpiringSoon,
                'has_expired' => $hasExpired,
                'proactive_refresh_threshold' => 15
            ]);

            // If token is not expiring soon and not expired, it's valid
            if (!$isExpiringSoon && !$hasExpired) {
                $this->logService->logProactiveTokenValidation($user, 'google-drive', false, false);
                Log::debug('Google Drive token is still valid and not expiring soon', [
                    'user_id' => $user->id,
                    'operation_id' => $operationId,
                    'expires_at' => $token->expires_at?->toISOString(),
                    'minutes_until_expiry' => $token->expires_at?->diffInMinutes(now())
                ]);
                return true;
            }

            // Token is expiring soon or expired, attempt coordinated refresh
            if (!$token->canBeRefreshed()) {
                $this->logService->logProactiveTokenValidation($user, 'google-drive', true, false);
                Log::warning('Google Drive token cannot be refreshed', [
                    'user_id' => $user->id,
                    'operation_id' => $operationId,
                    'has_refresh_token' => !empty($token->refresh_token),
                    'requires_user_intervention' => $token->requires_user_intervention,
                    'failure_count' => $token->refresh_failure_count
                ]);
                return false;
            }

            $this->logService->logTokenRefreshAttempt($user, 'google-drive', [
                'trigger' => 'proactive_validation',
                'operation_id' => $operationId,
                'token_expired_at' => $token->expires_at?->toISOString(),
                'is_proactive' => $isExpiringSoon && !$hasExpired
            ]);

            Log::info('Attempting coordinated Google Drive token refresh', [
                'user_id' => $user->id,
                'operation_id' => $operationId,
                'token_expired_at' => $token->expires_at?->toISOString(),
                'is_proactive_refresh' => $isExpiringSoon && !$hasExpired
            ]);

            // Use TokenRefreshCoordinator for thread-safe refresh
            $coordinator = app(TokenRefreshCoordinator::class);
            $refreshResult = $coordinator->coordinateRefresh($user, 'google-drive');
            
            if ($refreshResult->isSuccessful()) {
                $this->logService->logTokenRefreshSuccess($user, 'google-drive', [
                    'trigger' => 'proactive_validation',
                    'operation_id' => $operationId,
                    'was_already_valid' => $refreshResult->wasAlreadyValid,
                    'was_refreshed_by_another_process' => $refreshResult->wasRefreshedByAnotherProcess,
                    'was_token_refreshed' => $refreshResult->wasTokenRefreshed()
                ]);
                $this->logService->logProactiveTokenValidation($user, 'google-drive', true, true, true);
                
                Log::info('Google Drive token validation successful', [
                    'user_id' => $user->id,
                    'operation_id' => $operationId,
                    'result_type' => $refreshResult->wasAlreadyValid ? 'already_valid' : 
                                   ($refreshResult->wasRefreshedByAnotherProcess ? 'refreshed_by_another' : 'refreshed'),
                    'message' => $refreshResult->message
                ]);
                return true;
            } else {
                $this->logService->logTokenRefreshFailure($user, 'google-drive', $refreshResult->message, [
                    'trigger' => 'proactive_validation',
                    'operation_id' => $operationId,
                    'error_type' => $refreshResult->errorType?->value
                ]);
                $this->logService->logProactiveTokenValidation($user, 'google-drive', true, true, false);
                
                Log::error('Coordinated Google Drive token refresh failed', [
                    'user_id' => $user->id,
                    'operation_id' => $operationId,
                    'error' => $refreshResult->message,
                    'error_type' => $refreshResult->errorType?->value
                ]);
                return false;
            }
        } catch (Exception $e) {
            $this->logService->logTokenRefreshFailure($user, 'google-drive', $e->getMessage(), [
                'trigger' => 'proactive_validation',
                'operation_id' => $operationId,
                'exception' => true
            ]);
            
            Log::error('Exception during Google Drive token validation and refresh', [
                'user_id' => $user->id,
                'operation_id' => $operationId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e)
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
     * Implements exponential backoff retry logic (1s, 2s, 4s, 8s, 16s) with TokenRefreshErrorType classification.
     *
     * @param GoogleDriveToken $token The token to refresh
     * @param int $attempt Current attempt number for exponential backoff
     * @return array Array with 'success' boolean and additional result data
     */
    private function refreshTokenInternal(GoogleDriveToken $token, int $attempt = 1): array
    {
        $maxAttempts = 5; // Increased to match exponential backoff sequence (1s, 2s, 4s, 8s, 16s)
        $user = $token->user;
        $operationId = uniqid('refresh_', true);
        
        try {
            // Check rate limits before attempting refresh
            if ($attempt === 1) { // Only check on first attempt to avoid double-checking in retries
                if (!$this->securityService->checkUserRateLimit($user)) {
                    $error = 'Rate limit exceeded for token refresh attempts';
                    $this->securityService->auditRefreshFailure($user, new Exception($error), [
                        'reason' => 'rate_limit_exceeded',
                        'operation_id' => $operationId
                    ]);
                    
                    return [
                        'success' => false,
                        'error' => $error,
                        'error_type' => TokenRefreshErrorType::UNKNOWN_ERROR,
                        'requires_user_intervention' => true,
                        'is_recoverable' => false,
                        'operation_id' => $operationId
                    ];
                }
                
                if (!$this->securityService->checkIpRateLimit()) {
                    $error = 'IP rate limit exceeded for token refresh attempts';
                    $this->securityService->auditRefreshFailure($user, new Exception($error), [
                        'reason' => 'ip_rate_limit_exceeded',
                        'operation_id' => $operationId
                    ]);
                    
                    return [
                        'success' => false,
                        'error' => $error,
                        'error_type' => TokenRefreshErrorType::UNKNOWN_ERROR,
                        'requires_user_intervention' => true,
                        'is_recoverable' => false,
                        'operation_id' => $operationId
                    ];
                }
            }
            
            if (!$token->refresh_token) {
                $error = 'No refresh token available';
                $errorType = TokenRefreshErrorType::INVALID_REFRESH_TOKEN;
                $exception = new Exception($error);
                
                $this->logService->logTokenRefreshFailure($user, 'google-drive', $error, [
                    'attempt' => $attempt,
                    'operation_id' => $operationId,
                    'error_type' => $errorType->value,
                    'requires_user_intervention' => true
                ]);
                
                // Audit the failure
                $this->securityService->auditRefreshFailure($user, $exception, [
                    'operation_id' => $operationId,
                    'error_type' => $errorType->value
                ]);
                
                // Mark the failure on the token
                $token->markRefreshFailure($exception);
                
                return [
                    'success' => false,
                    'error' => $error,
                    'error_type' => $errorType,
                    'requires_user_intervention' => true,
                    'is_recoverable' => false,
                    'operation_id' => $operationId
                ];
            }

            Log::info('Attempting Google Drive token refresh with exponential backoff', [
                'user_id' => $token->user_id,
                'token_expires_at' => $token->expires_at?->toISOString(),
                'attempt' => $attempt,
                'max_attempts' => $maxAttempts,
                'operation_id' => $operationId
            ]);

            $this->client->setAccessToken([
                'refresh_token' => $token->refresh_token,
                'access_token' => $token->access_token,
                'token_type' => $token->token_type,
            ]);

            $newToken = $this->client->fetchAccessTokenWithRefreshToken();

            // Check for specific error responses from Google
            if (isset($newToken['error'])) {
                return $this->handleTokenRefreshError($token, $newToken, $attempt, $maxAttempts, $operationId);
            }

            // Check if refresh was successful
            if (!isset($newToken['access_token'])) {
                $error = 'No access token in refresh response';
                $errorType = TokenRefreshErrorType::UNKNOWN_ERROR;
                
                $this->logService->logTokenRefreshFailure($user, 'google-drive', $error, [
                    'attempt' => $attempt,
                    'operation_id' => $operationId,
                    'error_type' => $errorType->value,
                    'response' => $newToken
                ]);
                
                Log::error('Google Drive token refresh failed - no access token in response', [
                    'user_id' => $token->user_id,
                    'operation_id' => $operationId,
                    'response' => $newToken
                ]);
                
                // Mark the failure on the token
                $token->markRefreshFailure(new Exception($error));
                
                return [
                    'success' => false,
                    'error' => $error,
                    'error_type' => $errorType,
                    'requires_user_intervention' => true,
                    'is_recoverable' => false,
                    'operation_id' => $operationId
                ];
            }

            $expiresAt = isset($newToken['expires_in'])
                ? Carbon::now()->addSeconds($newToken['expires_in'])
                : null;
                
            // Use security service for token rotation
            $token = $this->securityService->rotateTokenOnRefresh($token, [
                'access_token' => $newToken['access_token'],
                'refresh_token' => $newToken['refresh_token'] ?? $token->refresh_token,
                'expires_in' => $newToken['expires_in'] ?? null,
            ]);
            
            // Reset rate limit on successful refresh
            $this->securityService->resetUserRateLimit($user);
            
            // Update health service with new token expiration
            $this->healthService?->updateTokenExpiration($token->user, 'google-drive', $expiresAt);

            $this->logService->logTokenRefreshSuccess($user, 'google-drive', [
                'attempt' => $attempt,
                'operation_id' => $operationId,
                'new_expires_at' => $expiresAt?->toISOString(),
                'expires_in_seconds' => $newToken['expires_in'] ?? null
            ]);

            Log::info('Google Drive token refresh successful', [
                'user_id' => $token->user_id,
                'new_expires_at' => $expiresAt?->toISOString(),
                'attempt' => $attempt,
                'operation_id' => $operationId
            ]);

            return [
                'success' => true,
                'expires_at' => $expiresAt,
                'access_token' => $newToken['access_token'],
                'attempt' => $attempt,
                'operation_id' => $operationId
            ];
            
        } catch (GoogleServiceException $e) {
            return $this->handleGoogleServiceException($token, $e, $attempt, $maxAttempts, $operationId);
        } catch (Exception $e) {
            return $this->handleGenericTokenRefreshException($token, $e, $attempt, $maxAttempts, $operationId);
        }
    }

    /**
     * Handle specific token refresh errors from Google's response.
     * Implements exponential backoff retry logic with TokenRefreshErrorType classification.
     */
    private function handleTokenRefreshError(GoogleDriveToken $token, array $response, int $attempt, int $maxAttempts, string $operationId): array
    {
        $error = $response['error'];
        $errorDescription = $response['error_description'] ?? '';
        
        Log::error('Google Drive token refresh failed with error response', [
            'user_id' => $token->user_id,
            'operation_id' => $operationId,
            'error' => $error,
            'error_description' => $errorDescription,
            'attempt' => $attempt
        ]);

        // Handle specific error types with TokenRefreshErrorType classification
        switch ($error) {
            case 'invalid_grant':
                // Refresh token is expired or revoked
                $errorType = TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN;
                $errorMessage = 'Refresh token is expired or revoked. Please reconnect your Google Drive account.';
                
                // Mark the failure on the token
                $exception = new Exception($errorMessage);
                $token->markRefreshFailure($exception);
                
                // Audit the failure
                $this->securityService->auditRefreshFailure($token->user, $exception, [
                    'operation_id' => $operationId,
                    'error_type' => $errorType->value,
                    'original_error' => $error,
                    'error_description' => $errorDescription
                ]);
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_type' => $errorType,
                    'requires_user_intervention' => $errorType->requiresUserIntervention(),
                    'is_recoverable' => $errorType->isRecoverable(),
                    'original_error' => $error,
                    'error_description' => $errorDescription,
                    'operation_id' => $operationId
                ];
                
            case 'invalid_client':
                // Client credentials are invalid
                $errorType = TokenRefreshErrorType::INVALID_REFRESH_TOKEN;
                $errorMessage = 'Invalid client credentials. Please check Google Drive configuration.';
                
                // Mark the failure on the token
                $exception = new Exception($errorMessage);
                $token->markRefreshFailure($exception);
                
                // Audit the failure
                $this->securityService->auditRefreshFailure($token->user, $exception, [
                    'operation_id' => $operationId,
                    'error_type' => $errorType->value,
                    'original_error' => $error,
                    'error_description' => $errorDescription
                ]);
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_type' => $errorType,
                    'requires_user_intervention' => $errorType->requiresUserIntervention(),
                    'is_recoverable' => $errorType->isRecoverable(),
                    'original_error' => $error,
                    'error_description' => $errorDescription,
                    'operation_id' => $operationId
                ];
                
            case 'temporarily_unavailable':
            case 'server_error':
                // Temporary server issues - retry with exponential backoff (1s, 2s, 4s, 8s, 16s)
                $errorType = TokenRefreshErrorType::SERVICE_UNAVAILABLE;
                
                if ($attempt < $maxAttempts) {
                    $delay = $errorType->getRetryDelay($attempt); // Uses enum's retry delay logic
                    
                    Log::info('Google Drive service temporarily unavailable, retrying with exponential backoff', [
                        'user_id' => $token->user_id,
                        'operation_id' => $operationId,
                        'attempt' => $attempt,
                        'next_attempt' => $attempt + 1,
                        'delay_seconds' => $delay,
                        'error' => $error,
                        'max_attempts' => $maxAttempts
                    ]);
                    
                    sleep($delay);
                    return $this->refreshTokenInternal($token, $attempt + 1);
                }
                
                $errorMessage = 'Google Drive service temporarily unavailable after multiple attempts.';
                
                // Mark the failure on the token
                $token->markRefreshFailure(new Exception($errorMessage));
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_type' => $errorType,
                    'requires_user_intervention' => $errorType->requiresUserIntervention(),
                    'is_recoverable' => $errorType->isRecoverable(),
                    'original_error' => $error,
                    'error_description' => $errorDescription,
                    'attempts_made' => $attempt,
                    'operation_id' => $operationId
                ];
                
            default:
                $errorType = TokenRefreshErrorType::UNKNOWN_ERROR;
                $errorMessage = "Token refresh failed: {$errorDescription}";
                
                // Mark the failure on the token
                $token->markRefreshFailure(new Exception($errorMessage));
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_type' => $errorType,
                    'requires_user_intervention' => $errorType->requiresUserIntervention(),
                    'is_recoverable' => $errorType->isRecoverable(),
                    'original_error' => $error,
                    'error_description' => $errorDescription,
                    'operation_id' => $operationId
                ];
        }
    }

    /**
     * Handle Google Service exceptions during token refresh.
     * Implements exponential backoff retry logic with TokenRefreshErrorType classification.
     */
    private function handleGoogleServiceException(GoogleDriveToken $token, GoogleServiceException $e, int $attempt, int $maxAttempts, string $operationId): array
    {
        $httpCode = $e->getCode();
        $errorMessage = $e->getMessage();
        
        Log::error('Google Service exception during token refresh', [
            'user_id' => $token->user_id,
            'operation_id' => $operationId,
            'http_code' => $httpCode,
            'error' => $errorMessage,
            'attempt' => $attempt
        ]);

        switch ($httpCode) {
            case 400:
                // Bad request - likely invalid refresh token
                $errorType = TokenRefreshErrorType::INVALID_REFRESH_TOKEN;
                $message = 'Invalid refresh token. Please reconnect your Google Drive account.';
                
                // Mark the failure on the token
                $token->markRefreshFailure($e);
                
                return [
                    'success' => false,
                    'error' => $message,
                    'error_type' => $errorType,
                    'requires_user_intervention' => $errorType->requiresUserIntervention(),
                    'is_recoverable' => $errorType->isRecoverable(),
                    'http_code' => $httpCode,
                    'operation_id' => $operationId
                ];
                
            case 401:
                // Unauthorized - refresh token expired
                $errorType = TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN;
                $message = 'Google Drive authorization expired. Please reconnect your account.';
                
                // Mark the failure on the token
                $token->markRefreshFailure($e);
                
                return [
                    'success' => false,
                    'error' => $message,
                    'error_type' => $errorType,
                    'requires_user_intervention' => $errorType->requiresUserIntervention(),
                    'is_recoverable' => $errorType->isRecoverable(),
                    'http_code' => $httpCode,
                    'operation_id' => $operationId
                ];
                
            case 403:
                // Forbidden - could be quota exceeded or permissions issue
                if (str_contains(strtolower($errorMessage), 'quota')) {
                    $errorType = TokenRefreshErrorType::API_QUOTA_EXCEEDED;
                    $message = 'Google Drive API quota exceeded. Please try again later.';
                    
                    // For quota errors, retry if we haven't exceeded max attempts
                    if ($attempt < $maxAttempts) {
                        $delay = $errorType->getRetryDelay($attempt);
                        
                        Log::info('Google Drive API quota exceeded, retrying with exponential backoff', [
                            'user_id' => $token->user_id,
                            'operation_id' => $operationId,
                            'attempt' => $attempt,
                            'next_attempt' => $attempt + 1,
                            'delay_seconds' => $delay,
                            'max_attempts' => $maxAttempts
                        ]);
                        
                        sleep($delay);
                        return $this->refreshTokenInternal($token, $attempt + 1);
                    }
                    
                    // Mark the failure on the token
                    $token->markRefreshFailure($e);
                    
                    return [
                        'success' => false,
                        'error' => $message,
                        'error_type' => $errorType,
                        'requires_user_intervention' => $errorType->requiresUserIntervention(),
                        'is_recoverable' => $errorType->isRecoverable(),
                        'http_code' => $httpCode,
                        'attempts_made' => $attempt,
                        'operation_id' => $operationId
                    ];
                }
                
                $errorType = TokenRefreshErrorType::INVALID_REFRESH_TOKEN;
                $message = 'Insufficient permissions for Google Drive access.';
                
                // Mark the failure on the token
                $token->markRefreshFailure($e);
                
                return [
                    'success' => false,
                    'error' => $message,
                    'error_type' => $errorType,
                    'requires_user_intervention' => $errorType->requiresUserIntervention(),
                    'is_recoverable' => $errorType->isRecoverable(),
                    'http_code' => $httpCode,
                    'operation_id' => $operationId
                ];
                
            case 429:
                // Rate limit exceeded - retry with exponential backoff
                $errorType = TokenRefreshErrorType::API_QUOTA_EXCEEDED;
                
                if ($attempt < $maxAttempts) {
                    $delay = $errorType->getRetryDelay($attempt);
                    
                    Log::info('Google Drive rate limit exceeded, retrying with exponential backoff', [
                        'user_id' => $token->user_id,
                        'operation_id' => $operationId,
                        'attempt' => $attempt,
                        'next_attempt' => $attempt + 1,
                        'delay_seconds' => $delay,
                        'max_attempts' => $maxAttempts
                    ]);
                    
                    sleep($delay);
                    return $this->refreshTokenInternal($token, $attempt + 1);
                }
                
                $message = 'Google Drive rate limit exceeded. Please try again later.';
                
                // Mark the failure on the token
                $token->markRefreshFailure($e);
                
                return [
                    'success' => false,
                    'error' => $message,
                    'error_type' => $errorType,
                    'requires_user_intervention' => $errorType->requiresUserIntervention(),
                    'is_recoverable' => $errorType->isRecoverable(),
                    'http_code' => $httpCode,
                    'attempts_made' => $attempt,
                    'operation_id' => $operationId
                ];
                
            case 500:
            case 502:
            case 503:
            case 504:
                // Server errors - retry with exponential backoff
                $errorType = TokenRefreshErrorType::SERVICE_UNAVAILABLE;
                
                if ($attempt < $maxAttempts) {
                    $delay = $errorType->getRetryDelay($attempt);
                    
                    Log::info('Google Drive server error, retrying with exponential backoff', [
                        'user_id' => $token->user_id,
                        'operation_id' => $operationId,
                        'http_code' => $httpCode,
                        'attempt' => $attempt,
                        'next_attempt' => $attempt + 1,
                        'delay_seconds' => $delay,
                        'max_attempts' => $maxAttempts
                    ]);
                    
                    sleep($delay);
                    return $this->refreshTokenInternal($token, $attempt + 1);
                }
                
                $message = 'Google Drive service temporarily unavailable.';
                
                // Mark the failure on the token
                $token->markRefreshFailure($e);
                
                return [
                    'success' => false,
                    'error' => $message,
                    'error_type' => $errorType,
                    'requires_user_intervention' => $errorType->requiresUserIntervention(),
                    'is_recoverable' => $errorType->isRecoverable(),
                    'http_code' => $httpCode,
                    'attempts_made' => $attempt,
                    'operation_id' => $operationId
                ];
                
            default:
                $errorType = TokenRefreshErrorType::UNKNOWN_ERROR;
                $message = "Google Drive API error: {$errorMessage}";
                
                // Mark the failure on the token
                $token->markRefreshFailure($e);
                
                return [
                    'success' => false,
                    'error' => $message,
                    'error_type' => $errorType,
                    'requires_user_intervention' => $errorType->requiresUserIntervention(),
                    'is_recoverable' => $errorType->isRecoverable(),
                    'http_code' => $httpCode,
                    'operation_id' => $operationId
                ];
        }
    }

    /**
     * Handle generic exceptions during token refresh.
     * Implements exponential backoff retry logic with TokenRefreshErrorType classification.
     */
    private function handleGenericTokenRefreshException(GoogleDriveToken $token, Exception $e, int $attempt, int $maxAttempts, string $operationId): array
    {
        $errorMessage = $e->getMessage();
        
        Log::error('Generic exception during Google Drive token refresh', [
            'user_id' => $token->user_id,
            'operation_id' => $operationId,
            'error' => $errorMessage,
            'error_code' => $e->getCode(),
            'attempt' => $attempt,
            'exception_class' => get_class($e)
        ]);

        // Check for network-related errors
        if ($this->isNetworkError($e)) {
            $errorType = TokenRefreshErrorType::NETWORK_TIMEOUT;
            
            if ($attempt < $maxAttempts) {
                $delay = $errorType->getRetryDelay($attempt); // Uses enum's exponential backoff: 1s, 2s, 4s, 8s, 16s
                
                Log::info('Network error during token refresh, retrying with exponential backoff', [
                    'user_id' => $token->user_id,
                    'operation_id' => $operationId,
                    'attempt' => $attempt,
                    'next_attempt' => $attempt + 1,
                    'delay_seconds' => $delay,
                    'error' => $errorMessage,
                    'max_attempts' => $maxAttempts
                ]);
                
                sleep($delay);
                return $this->refreshTokenInternal($token, $attempt + 1);
            }
            
            $message = 'Network error during token refresh. Please check your internet connection.';
            
            // Mark the failure on the token
            $token->markRefreshFailure($e);
            
            return [
                'success' => false,
                'error' => $message,
                'error_type' => $errorType,
                'requires_user_intervention' => $errorType->requiresUserIntervention(),
                'is_recoverable' => $errorType->isRecoverable(),
                'original_error' => $errorMessage,
                'attempts_made' => $attempt,
                'operation_id' => $operationId
            ];
        }

        // Check for timeout errors
        if ($this->isTimeoutError($e)) {
            $errorType = TokenRefreshErrorType::NETWORK_TIMEOUT;
            
            if ($attempt < $maxAttempts) {
                $delay = $errorType->getRetryDelay($attempt); // Uses enum's exponential backoff
                
                Log::info('Timeout error during token refresh, retrying with exponential backoff', [
                    'user_id' => $token->user_id,
                    'operation_id' => $operationId,
                    'attempt' => $attempt,
                    'next_attempt' => $attempt + 1,
                    'delay_seconds' => $delay,
                    'max_attempts' => $maxAttempts
                ]);
                
                sleep($delay);
                return $this->refreshTokenInternal($token, $attempt + 1);
            }
            
            $message = 'Token refresh timed out. Please try again.';
            
            // Mark the failure on the token
            $token->markRefreshFailure($e);
            
            return [
                'success' => false,
                'error' => $message,
                'error_type' => $errorType,
                'requires_user_intervention' => $errorType->requiresUserIntervention(),
                'is_recoverable' => $errorType->isRecoverable(),
                'original_error' => $errorMessage,
                'attempts_made' => $attempt,
                'operation_id' => $operationId
            ];
        }

        // Generic error handling
        $errorType = TokenRefreshErrorType::UNKNOWN_ERROR;
        $message = "Token refresh failed: {$errorMessage}";
        
        // Mark the failure on the token
        $token->markRefreshFailure($e);
        
        return [
            'success' => false,
            'error' => $message,
            'error_type' => $errorType,
            'requires_user_intervention' => $errorType->requiresUserIntervention(),
            'is_recoverable' => $errorType->isRecoverable(),
            'original_error' => $errorMessage,
            'error_code' => $e->getCode(),
            'operation_id' => $operationId
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

        // Check if we should use chunked upload for large files
        $fullPath = Storage::disk('public')->path($localRelativePath);
        $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;
        
        // Use chunked upload for files larger than 50MB or when memory is constrained
        $chunkedUploadService = app(GoogleDriveChunkedUploadService::class);
        if ($chunkedUploadService->shouldUseChunkedUpload($fileSize)) {
            Log::info('Using chunked upload for large file', [
                'file_size' => $fileSize,
                'file_path' => $localRelativePath,
                'target_folder' => $userFolderId
            ]);
            
            return $chunkedUploadService->uploadFileChunked(
                $targetUser->isEmployee() && $targetUser->hasGoogleDriveConnected() ? $targetUser : $adminUser,
                $localRelativePath,
                $userFolderId,
                $originalFilename,
                $mimeType,
                $description
            );
        }

        // Use traditional upload for smaller files
        Log::info('Using traditional upload for small file', [
            'file_size' => $fileSize,
            'file_path' => $localRelativePath
        ]);

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
