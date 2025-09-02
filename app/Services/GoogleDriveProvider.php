<?php

namespace App\Services;

use App\Contracts\CloudStorageProviderInterface;
use App\Exceptions\CloudStorageException;
use App\Models\User;
use App\Models\GoogleDriveToken;
use Carbon\Carbon;
use Exception;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Google Drive provider implementation
 * 
 * Implements CloudStorageProviderInterface to provide Google Drive
 * operations with enhanced error handling and health monitoring
 */
class GoogleDriveProvider implements CloudStorageProviderInterface
{
    private const PROVIDER_NAME = 'google-drive';

    public function __construct(
        private readonly GoogleDriveService $driveService,
        private readonly GoogleDriveErrorHandler $errorHandler,
        private readonly CloudStorageLogService $logService
    ) {}

    /**
     * Upload a file to Google Drive
     *
     * @param User $user The user whose Google Drive to use
     * @param string $localPath Path to the local file to upload
     * @param string $targetPath Target path/folder in Google Drive (client email)
     * @param array $metadata Additional metadata for the file
     * @return string The Google Drive file ID
     * @throws CloudStorageException
     */
    public function uploadFile(User $user, string $localPath, string $targetPath, array $metadata = []): string
    {
        $startTime = microtime(true);
        $operationId = $this->logService->logOperationStart('upload', self::PROVIDER_NAME, $user, [
            'local_path' => $localPath,
            'target_path' => $targetPath,
            'file_size' => file_exists($localPath) ? filesize($localPath) : null,
            'metadata' => $metadata,
        ]);

        try {
            // Extract required metadata
            $originalFilename = $metadata['original_filename'] ?? basename($localPath);
            $mimeType = $metadata['mime_type'] ?? 'application/octet-stream';
            $description = $metadata['description'] ?? null;

            // Use the existing service method that handles both employee and admin uploads
            $fileId = $this->driveService->uploadFileForUser(
                $user,
                $localPath,
                $targetPath, // This is the client email
                $originalFilename,
                $mimeType,
                $description
            );

            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logService->logOperationSuccess($operationId, 'upload', self::PROVIDER_NAME, $user, [
                'file_id' => $fileId,
                'filename' => $originalFilename,
                'file_size' => file_exists($localPath) ? filesize($localPath) : null,
                'mime_type' => $mimeType,
            ], $durationMs);

            return $fileId;

        } catch (Exception $e) {
            $errorType = $this->errorHandler->classifyError($e);
            $durationMs = (microtime(true) - $startTime) * 1000;
            
            $this->logService->logOperationFailure(
                $operationId,
                'upload',
                self::PROVIDER_NAME,
                $user,
                $errorType,
                $e->getMessage(),
                [
                    'local_path' => $localPath,
                    'target_path' => $targetPath,
                    'filename' => $metadata['original_filename'] ?? basename($localPath),
                    'file_size' => file_exists($localPath) ? filesize($localPath) : null,
                ],
                $durationMs,
                $e
            );

            throw CloudStorageException::create(
                $errorType,
                self::PROVIDER_NAME,
                [
                    'operation' => 'upload',
                    'file_name' => $metadata['original_filename'] ?? basename($localPath),
                    'target_path' => $targetPath,
                    'user_id' => $user->id,
                    'original_message' => $e->getMessage()
                ],
                $e
            );
        }
    }

    /**
     * Delete a file from Google Drive
     *
     * @param User $user The user whose Google Drive to use
     * @param string $fileId The Google Drive file ID
     * @return bool True if deletion was successful
     * @throws CloudStorageException
     */
    public function deleteFile(User $user, string $fileId): bool
    {
        $startTime = microtime(true);
        $operationId = $this->logService->logOperationStart('delete', self::PROVIDER_NAME, $user, [
            'file_id' => $fileId,
        ]);

        try {
            $result = $this->driveService->deleteFile($user, $fileId);
            $durationMs = (microtime(true) - $startTime) * 1000;

            if ($result) {
                $this->logService->logOperationSuccess($operationId, 'delete', self::PROVIDER_NAME, $user, [
                    'file_id' => $fileId,
                ], $durationMs);
            } else {
                $this->logService->logOperationFailure(
                    $operationId,
                    'delete',
                    self::PROVIDER_NAME,
                    $user,
                    \App\Enums\CloudStorageErrorType::UNKNOWN_ERROR,
                    'Delete operation returned false',
                    ['file_id' => $fileId],
                    $durationMs
                );
            }

            return $result;

        } catch (Exception $e) {
            $errorType = $this->errorHandler->classifyError($e);
            $durationMs = (microtime(true) - $startTime) * 1000;
            
            $this->logService->logOperationFailure(
                $operationId,
                'delete',
                self::PROVIDER_NAME,
                $user,
                $errorType,
                $e->getMessage(),
                ['file_id' => $fileId],
                $durationMs,
                $e
            );

            throw CloudStorageException::create(
                $errorType,
                self::PROVIDER_NAME,
                [
                    'operation' => 'delete',
                    'file_id' => $fileId,
                    'user_id' => $user->id,
                    'original_message' => $e->getMessage()
                ],
                $e
            );
        }
    }

    /**
     * Check the health status of the Google Drive connection
     *
     * @param User $user The user whose connection to check
     * @return CloudStorageHealthStatus The current health status
     */
    public function getConnectionHealth(User $user): CloudStorageHealthStatus
    {
        try {
            Log::debug('Checking Google Drive connection health', [
                'user_id' => $user->id
            ]);

            // Check if user has a token
            $token = GoogleDriveToken::where('user_id', $user->id)->first();
            if (!$token) {
                return CloudStorageHealthStatus::disconnected(
                    self::PROVIDER_NAME,
                    ['reason' => 'No authentication token found']
                );
            }

            // Check token expiration
            $tokenExpiresAt = $token->expires_at;
            $isTokenExpired = $tokenExpiresAt && $tokenExpiresAt->isPast();
            $isTokenExpiringSoon = $tokenExpiresAt && now()->diffInHours($tokenExpiresAt) <= 24;

            if ($isTokenExpired && !$token->refresh_token) {
                return CloudStorageHealthStatus::unhealthy(
                    self::PROVIDER_NAME,
                    consecutiveFailures: 1,
                    lastErrorType: \App\Enums\CloudStorageErrorType::TOKEN_EXPIRED,
                    lastErrorMessage: 'Token expired and no refresh token available',
                    requiresReconnection: true,
                    providerSpecificData: [
                        'token_expired_at' => $tokenExpiresAt?->toISOString(),
                        'has_refresh_token' => false
                    ]
                );
            }

            // Try to get a valid token (this will refresh if needed)
            try {
                $validToken = $this->driveService->getValidToken($user);
                
                // Perform a lightweight health check by getting user info
                $service = $this->driveService->getDriveService($user);
                $about = $service->about->get(['fields' => 'user,storageQuota']);
                
                $storageQuota = $about->getStorageQuota();
                $quotaUsed = $storageQuota ? $storageQuota->getUsage() : null;
                $quotaLimit = $storageQuota ? $storageQuota->getLimit() : null;
                $quotaUsagePercent = ($quotaUsed && $quotaLimit) ? ($quotaUsed / $quotaLimit) * 100 : null;

                Log::debug('Google Drive connection health check successful', [
                    'user_id' => $user->id,
                    'quota_usage_percent' => $quotaUsagePercent
                ]);

                // Determine status based on quota usage
                $status = CloudStorageHealthStatus::STATUS_HEALTHY;
                if ($quotaUsagePercent && $quotaUsagePercent > 95) {
                    $status = CloudStorageHealthStatus::STATUS_DEGRADED;
                }

                return new CloudStorageHealthStatus(
                    provider: self::PROVIDER_NAME,
                    status: $status,
                    lastSuccessfulOperation: now(),
                    tokenExpiresAt: $validToken->expires_at,
                    providerSpecificData: [
                        'user_email' => $about->getUser()?->getEmailAddress(),
                        'quota_used' => $quotaUsed,
                        'quota_limit' => $quotaLimit,
                        'quota_usage_percent' => $quotaUsagePercent,
                        'is_token_expiring_soon' => $isTokenExpiringSoon,
                        'has_refresh_token' => !empty($validToken->refresh_token)
                    ]
                );

            } catch (Exception $e) {
                $errorType = $this->errorHandler->classifyError($e);
                
                Log::warning('Google Drive health check failed', [
                    'user_id' => $user->id,
                    'error_type' => $errorType->value,
                    'error_message' => $e->getMessage()
                ]);

                $requiresReconnection = in_array($errorType, [
                    \App\Enums\CloudStorageErrorType::TOKEN_EXPIRED,
                    \App\Enums\CloudStorageErrorType::INVALID_CREDENTIALS,
                    \App\Enums\CloudStorageErrorType::INSUFFICIENT_PERMISSIONS
                ]);

                return CloudStorageHealthStatus::unhealthy(
                    self::PROVIDER_NAME,
                    consecutiveFailures: 1,
                    lastErrorType: $errorType,
                    lastErrorMessage: $e->getMessage(),
                    requiresReconnection: $requiresReconnection,
                    providerSpecificData: [
                        'health_check_failed_at' => now()->toISOString(),
                        'error_details' => $e->getMessage()
                    ]
                );
            }

        } catch (Exception $e) {
            Log::error('Google Drive health check exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return CloudStorageHealthStatus::unhealthy(
                self::PROVIDER_NAME,
                consecutiveFailures: 1,
                lastErrorType: \App\Enums\CloudStorageErrorType::UNKNOWN_ERROR,
                lastErrorMessage: $e->getMessage(),
                requiresReconnection: true,
                providerSpecificData: [
                    'health_check_exception' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Handle OAuth callback after user authorization
     *
     * @param User $user The user to associate the token with
     * @param string $code The authorization code from OAuth callback
     * @return void
     * @throws CloudStorageException
     */
    public function handleAuthCallback(User $user, string $code): void
    {
        try {
            $this->logService->logOAuthEvent(self::PROVIDER_NAME, $user, 'callback_start', true);
            
            $this->driveService->handleCallback($user, $code);

            $this->logService->logOAuthEvent(self::PROVIDER_NAME, $user, 'callback_complete', true);

        } catch (Exception $e) {
            $errorType = $this->errorHandler->classifyError($e);
            
            $this->logService->logOAuthEvent(self::PROVIDER_NAME, $user, 'callback_failed', false, $e->getMessage());

            throw CloudStorageException::create(
                $errorType,
                self::PROVIDER_NAME,
                [
                    'operation' => 'oauth_callback',
                    'user_id' => $user->id,
                    'original_message' => $e->getMessage()
                ],
                $e
            );
        }
    }

    /**
     * Get the OAuth authorization URL for user authentication
     *
     * @param User $user The user to generate auth URL for
     * @param bool $isReconnection Whether this is a reconnection attempt
     * @return string The OAuth authorization URL
     */
    public function getAuthUrl(User $user, bool $isReconnection = false): string
    {
        try {
            $event = $isReconnection ? 'auth_url_reconnection' : 'auth_url_initial';
            $this->logService->logOAuthEvent(self::PROVIDER_NAME, $user, $event, true, null, [
                'is_reconnection' => $isReconnection,
            ]);

            $authUrl = $this->driveService->getAuthUrl($user, $isReconnection);

            return $authUrl;

        } catch (Exception $e) {
            $event = $isReconnection ? 'auth_url_reconnection_failed' : 'auth_url_initial_failed';
            $this->logService->logOAuthEvent(self::PROVIDER_NAME, $user, $event, false, $e->getMessage(), [
                'is_reconnection' => $isReconnection,
            ]);

            // For auth URL generation, we'll return a fallback or throw
            throw new CloudStorageException(
                message: 'Failed to generate Google Drive authorization URL',
                errorType: \App\Enums\CloudStorageErrorType::INVALID_CREDENTIALS,
                context: [
                    'operation' => 'get_auth_url',
                    'user_id' => $user->id,
                    'is_reconnection' => $isReconnection,
                    'original_message' => $e->getMessage()
                ],
                provider: self::PROVIDER_NAME,
                previous: $e
            );
        }
    }

    /**
     * Disconnect the user's Google Drive account
     *
     * @param User $user The user to disconnect
     * @return void
     */
    public function disconnect(User $user): void
    {
        try {
            $this->logService->logOAuthEvent(self::PROVIDER_NAME, $user, 'disconnect_start', true);
            
            $this->driveService->disconnect($user);

            $this->logService->logOAuthEvent(self::PROVIDER_NAME, $user, 'disconnect_complete', true);

        } catch (Exception $e) {
            $this->logService->logOAuthEvent(self::PROVIDER_NAME, $user, 'disconnect_failed', false, $e->getMessage());
            
            // Don't throw exception for disconnect failures - we want to clean up locally regardless
        }
    }

    /**
     * Get the provider name identifier
     *
     * @return string The provider name
     */
    public function getProviderName(): string
    {
        return self::PROVIDER_NAME;
    }

    /**
     * Check if the user has a valid connection to Google Drive
     *
     * @param User $user The user to check
     * @return bool True if connection is valid
     */
    public function hasValidConnection(User $user): bool
    {
        try {
            $healthStatus = $this->getConnectionHealth($user);
            return $healthStatus->isHealthy() || $healthStatus->isDegraded();
        } catch (Exception $e) {
            Log::debug('Connection validity check failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get file metadata from Google Drive (enhanced with advanced features)
     *
     * @param User $user The user whose Google Drive to use
     * @param string $fileId The Google Drive file ID
     * @return array File metadata including custom properties
     * @throws CloudStorageException
     */
    public function getFileMetadata(User $user, string $fileId): array
    {
        $startTime = microtime(true);
        $operationId = $this->logService->logOperationStart('get_metadata', self::PROVIDER_NAME, $user, [
            'file_id' => $fileId,
        ]);

        try {
            Log::debug('Getting Google Drive file metadata', [
                'user_id' => $user->id,
                'file_id' => $fileId
            ]);

            // Use the Drive service directly to get more detailed metadata
            $service = $this->driveService->getDriveService($user);
            
            $file = $service->files->get($fileId, [
                'fields' => 'id,name,size,mimeType,createdTime,modifiedTime,properties,parents,webViewLink,webContentLink'
            ]);

            $metadata = [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'created_time' => $file->getCreatedTime(),
                'modified_time' => $file->getModifiedTime(),
                'parents' => $file->getParents(),
                'web_view_link' => $file->getWebViewLink(),
                'web_content_link' => $file->getWebContentLink(),
                'properties' => $file->getProperties() ?? [],
            ];

            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logService->logOperationSuccess($operationId, 'get_metadata', self::PROVIDER_NAME, $user, [
                'file_id' => $fileId,
                'metadata_keys' => array_keys($metadata),
            ], $durationMs);

            Log::debug('Google Drive file metadata retrieved', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'metadata' => $metadata
            ]);

            return $metadata;

        } catch (Exception $e) {
            $errorType = $this->errorHandler->classifyError($e);
            $durationMs = (microtime(true) - $startTime) * 1000;
            
            $this->logService->logOperationFailure(
                $operationId,
                'get_metadata',
                self::PROVIDER_NAME,
                $user,
                $errorType,
                $e->getMessage(),
                ['file_id' => $fileId],
                $durationMs,
                $e
            );
            
            Log::error('Google Drive file metadata retrieval failed', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'error_type' => $errorType->value,
                'error_message' => $e->getMessage()
            ]);

            throw CloudStorageException::create(
                $errorType,
                self::PROVIDER_NAME,
                [
                    'operation' => 'get_metadata',
                    'file_id' => $fileId,
                    'user_id' => $user->id,
                    'original_message' => $e->getMessage()
                ],
                $e
            );
        }
    }

    /**
     * Download file content from Google Drive
     *
     * @param User $user The user whose Google Drive to use
     * @param string $fileId The Google Drive file ID
     * @return string File content
     * @throws CloudStorageException
     */
    public function downloadFile(User $user, string $fileId): string
    {
        try {
            Log::info('Starting Google Drive file download', [
                'user_id' => $user->id,
                'file_id' => $fileId
            ]);

            $content = $this->driveService->downloadFile($user, $fileId);

            Log::info('Google Drive file download successful', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'content_size' => strlen($content)
            ]);

            return $content;

        } catch (Exception $e) {
            $errorType = $this->errorHandler->classifyError($e);
            
            Log::error('Google Drive file download failed', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'error_type' => $errorType->value,
                'error_message' => $e->getMessage()
            ]);

            throw CloudStorageException::create(
                $errorType,
                self::PROVIDER_NAME,
                [
                    'operation' => 'download',
                    'file_id' => $fileId,
                    'user_id' => $user->id,
                    'original_message' => $e->getMessage()
                ],
                $e
            );
        }
    }

    // ========================================
    // NEW ENHANCED INTERFACE METHODS
    // ========================================

    /**
     * Get the capabilities supported by Google Drive
     *
     * @return array Array of capability names and their support status
     */
    public function getCapabilities(): array
    {
        return [
            'folder_creation' => true,
            'file_upload' => true,
            'file_delete' => true,
            'folder_delete' => true,
            'file_download' => true,
            'file_metadata' => true,
            'oauth_authentication' => true,
            'hierarchical_storage' => true,
            'file_sharing' => true,
            'version_history' => true,
            'search' => true,
            'batch_operations' => false,
            'presigned_urls' => false,
            'storage_classes' => false,
            'encryption_at_rest' => true,
            'access_control' => true,
        ];
    }

    /**
     * Validate Google Drive provider configuration
     *
     * @param array $config Configuration array to validate
     * @return array Array of validation errors (empty if valid)
     */
    public function validateConfiguration(array $config): array
    {
        $errors = [];

        // Check required configuration keys
        $requiredKeys = ['client_id', 'client_secret', 'redirect_uri'];
        
        foreach ($requiredKeys as $key) {
            if (empty($config[$key])) {
                $errors[] = "Missing required configuration key: {$key}";
            }
        }

        // Validate client_id format (should be a Google OAuth client ID)
        if (!empty($config['client_id'])) {
            if (!str_ends_with($config['client_id'], '.apps.googleusercontent.com')) {
                $errors[] = "Invalid Google OAuth client_id format";
            }
        }

        // Validate redirect_uri format
        if (!empty($config['redirect_uri'])) {
            if (!filter_var($config['redirect_uri'], FILTER_VALIDATE_URL)) {
                $errors[] = "Invalid redirect_uri format - must be a valid URL";
            }
        }

        // Validate client_secret (should be non-empty string)
        if (!empty($config['client_secret'])) {
            if (strlen($config['client_secret']) < 10) {
                $errors[] = "Google OAuth client_secret appears to be too short";
            }
        }

        return $errors;
    }

    /**
     * Initialize Google Drive provider with configuration
     *
     * @param array $config Provider-specific configuration
     * @return void
     * @throws \App\Exceptions\CloudStorageSetupException
     */
    public function initialize(array $config): void
    {
        // Validate configuration
        $errors = $this->validateConfiguration($config);
        if (!empty($errors)) {
            throw new \App\Exceptions\CloudStorageSetupException(
                'Google Drive provider configuration is invalid: ' . implode(', ', $errors)
            );
        }

        // Configuration is handled by the underlying GoogleDriveService
        // which reads from the config/cloud-storage.php file
        Log::debug('GoogleDriveProvider: Initialized successfully', [
            'provider' => self::PROVIDER_NAME,
            'has_client_id' => !empty($config['client_id']),
            'has_client_secret' => !empty($config['client_secret']),
            'redirect_uri' => $config['redirect_uri'] ?? null,
        ]);
    }

    /**
     * Get the authentication type used by Google Drive
     *
     * @return string Authentication type
     */
    public function getAuthenticationType(): string
    {
        return 'oauth';
    }

    /**
     * Get the storage model used by Google Drive
     *
     * @return string Storage model
     */
    public function getStorageModel(): string
    {
        return 'hierarchical';
    }

    /**
     * Get the maximum file size supported by Google Drive
     *
     * @return int Maximum file size in bytes (5GB)
     */
    public function getMaxFileSize(): int
    {
        return 5368709120; // 5GB in bytes
    }

    /**
     * Get the supported file types for Google Drive
     *
     * @return array Array of supported MIME types
     */
    public function getSupportedFileTypes(): array
    {
        return ['*']; // Google Drive supports all file types
    }

    /**
     * Check if Google Drive supports a specific feature
     *
     * @param string $feature Feature name to check
     * @return bool True if feature is supported
     */
    public function supportsFeature(string $feature): bool
    {
        $capabilities = $this->getCapabilities();
        return $capabilities[$feature] ?? false;
    }

    /**
     * Clean up Google Drive provider resources and connections
     *
     * @return void
     */
    public function cleanup(): void
    {
        // Google Drive provider doesn't maintain persistent connections
        // that need cleanup, but we can clear any cached data
        Log::debug('GoogleDriveProvider: Cleanup completed', [
            'provider' => self::PROVIDER_NAME
        ]);
    }

    // ========================================
    // ADVANCED PROVIDER FEATURES
    // ========================================

    /**
     * Generate a presigned URL for Google Drive file access
     * Note: Google Drive doesn't support presigned URLs like S3
     *
     * @param User $user The user whose Google Drive to use
     * @param string $fileId The Google Drive file ID
     * @param int $expirationMinutes URL expiration time in minutes
     * @param string $operation Operation type ('download', 'upload', 'delete')
     * @return string|null Presigned URL or null if not supported
     * @throws CloudStorageException
     */
    public function generatePresignedUrl(User $user, string $fileId, int $expirationMinutes = 60, string $operation = 'download'): ?string
    {
        // Google Drive doesn't support presigned URLs in the same way as S3
        // We can only provide direct download links for publicly shared files
        if ($operation !== 'download') {
            return null;
        }

        try {
            Log::debug('Google Drive presigned URL requested (not supported)', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'operation' => $operation,
            ]);

            // Google Drive uses direct file access URLs, but they require authentication
            // We can't create truly "presigned" URLs without making the file public
            return null;

        } catch (Exception $e) {
            Log::warning('Google Drive presigned URL generation failed', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Set storage class for a Google Drive file
     * Note: Google Drive doesn't have storage classes like S3
     *
     * @param User $user The user whose Google Drive to use
     * @param string $fileId The Google Drive file ID
     * @param string $storageClass Storage class name
     * @return bool True if storage class was set successfully
     * @throws CloudStorageException
     */
    public function setStorageClass(User $user, string $fileId, string $storageClass): bool
    {
        // Google Drive doesn't support storage classes
        throw new CloudStorageException(
            message: 'Google Drive does not support storage classes',
            errorType: \App\Enums\CloudStorageErrorType::FEATURE_NOT_SUPPORTED,
            context: [
                'operation' => 'set_storage_class',
                'file_id' => $fileId,
                'storage_class' => $storageClass,
                'user_id' => $user->id,
            ],
            provider: self::PROVIDER_NAME
        );
    }

    /**
     * Get available storage classes for Google Drive
     *
     * @return array Array of available storage class names with descriptions
     */
    public function getAvailableStorageClasses(): array
    {
        // Google Drive doesn't support storage classes
        return [];
    }

    /**
     * Apply Google Drive-specific optimizations for file upload
     *
     * @param User $user The user whose Google Drive to use
     * @param string $localPath Path to the local file to upload
     * @param array $options Optimization options
     * @return array Optimized upload parameters
     */
    public function optimizeUpload(User $user, string $localPath, array $options = []): array
    {
        $fileSize = file_exists($localPath) ? filesize($localPath) : 0;
        $mimeType = $options['mime_type'] ?? 'application/octet-stream';
        
        $optimizations = [
            'use_resumable_upload' => false,
            'chunk_size' => null,
            'convert_to_google_format' => false,
            'compress_images' => false,
            'metadata' => [],
        ];

        // Use resumable upload for files larger than 5MB
        if ($fileSize > 5242880) { // 5MB
            $optimizations['use_resumable_upload'] = true;
            $optimizations['chunk_size'] = min(8388608, $fileSize); // 8MB chunks or file size
        }

        // Convert office documents to Google formats for better collaboration
        if (in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
            'application/vnd.openxmlformats-officedocument.presentationml.presentation', // .pptx
            'application/msword', // .doc
            'application/vnd.ms-excel', // .xls
            'application/vnd.ms-powerpoint', // .ppt
        ])) {
            if ($options['convert_to_google_format'] ?? false) {
                $optimizations['convert_to_google_format'] = true;
            }
        }

        // Compress large images if requested
        if (str_starts_with($mimeType, 'image/') && $fileSize > 10485760) { // 10MB
            if ($options['compress_images'] ?? false) {
                $optimizations['compress_images'] = true;
            }
        }

        // Add optimization metadata
        $optimizations['metadata']['optimization_applied'] = 'true';
        $optimizations['metadata']['optimization_timestamp'] = now()->toISOString();
        $optimizations['metadata']['file_size_category'] = $this->getFileSizeCategory($fileSize);

        Log::debug('Google Drive upload optimizations applied', [
            'user_id' => $user->id,
            'file_size' => $fileSize,
            'optimizations' => $optimizations,
        ]);

        return $optimizations;
    }

    /**
     * Set custom metadata for a Google Drive file
     *
     * @param User $user The user whose Google Drive to use
     * @param string $fileId The Google Drive file ID
     * @param array $metadata Custom metadata key-value pairs
     * @return bool True if metadata was set successfully
     * @throws CloudStorageException
     */
    public function setFileMetadata(User $user, string $fileId, array $metadata): bool
    {
        $startTime = microtime(true);
        $operationId = $this->logService->logOperationStart('set_metadata', self::PROVIDER_NAME, $user, [
            'file_id' => $fileId,
            'metadata_keys' => array_keys($metadata),
        ]);

        try {
            $service = $this->driveService->getDriveService($user);
            
            // Google Drive uses properties for custom metadata
            $properties = [];
            foreach ($metadata as $key => $value) {
                $properties[$key] = (string) $value;
            }

            $fileMetadata = new DriveFile();
            $fileMetadata->setProperties($properties);

            $service->files->update($fileId, $fileMetadata);

            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logService->logOperationSuccess($operationId, 'set_metadata', self::PROVIDER_NAME, $user, [
                'file_id' => $fileId,
                'metadata_keys' => array_keys($metadata),
            ], $durationMs);

            return true;

        } catch (Exception $e) {
            $errorType = $this->errorHandler->classifyError($e);
            $durationMs = (microtime(true) - $startTime) * 1000;
            
            $this->logService->logOperationFailure(
                $operationId,
                'set_metadata',
                self::PROVIDER_NAME,
                $user,
                $errorType,
                $e->getMessage(),
                [
                    'file_id' => $fileId,
                    'metadata_keys' => array_keys($metadata),
                ],
                $durationMs,
                $e
            );

            throw CloudStorageException::create(
                $errorType,
                self::PROVIDER_NAME,
                [
                    'operation' => 'set_metadata',
                    'file_id' => $fileId,
                    'user_id' => $user->id,
                    'original_message' => $e->getMessage()
                ],
                $e
            );
        }
    }



    /**
     * Add tags to a Google Drive file
     * Note: Google Drive doesn't have native tagging, but we can use properties
     *
     * @param User $user The user whose Google Drive to use
     * @param string $fileId The Google Drive file ID
     * @param array $tags Array of tag strings
     * @return bool True if tags were added successfully
     * @throws CloudStorageException
     */
    public function addFileTags(User $user, string $fileId, array $tags): bool
    {
        $startTime = microtime(true);
        $operationId = $this->logService->logOperationStart('add_tags', self::PROVIDER_NAME, $user, [
            'file_id' => $fileId,
            'tags' => $tags,
        ]);

        try {
            $service = $this->driveService->getDriveService($user);
            
            // Get current properties to preserve existing tags
            $file = $service->files->get($fileId, ['fields' => 'properties']);
            $currentProperties = $file->getProperties() ?? [];
            
            // Get existing tags
            $existingTags = [];
            if (isset($currentProperties['tags'])) {
                $existingTags = explode(',', $currentProperties['tags']);
            }

            // Merge with new tags
            $allTags = array_unique(array_merge($existingTags, $tags));
            
            // Update properties with new tags
            $fileMetadata = new DriveFile();
            $newProperties = $currentProperties;
            $newProperties['tags'] = implode(',', $allTags);
            $newProperties['tags_updated'] = now()->toISOString();
            $fileMetadata->setProperties($newProperties);

            $service->files->update($fileId, $fileMetadata);

            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logService->logOperationSuccess($operationId, 'add_tags', self::PROVIDER_NAME, $user, [
                'file_id' => $fileId,
                'tags_added' => $tags,
                'total_tags' => count($allTags),
            ], $durationMs);

            return true;

        } catch (Exception $e) {
            $errorType = $this->errorHandler->classifyError($e);
            $durationMs = (microtime(true) - $startTime) * 1000;
            
            $this->logService->logOperationFailure(
                $operationId,
                'add_tags',
                self::PROVIDER_NAME,
                $user,
                $errorType,
                $e->getMessage(),
                [
                    'file_id' => $fileId,
                    'tags' => $tags,
                ],
                $durationMs,
                $e
            );

            throw CloudStorageException::create(
                $errorType,
                self::PROVIDER_NAME,
                [
                    'operation' => 'add_tags',
                    'file_id' => $fileId,
                    'tags' => $tags,
                    'user_id' => $user->id,
                    'original_message' => $e->getMessage()
                ],
                $e
            );
        }
    }

    /**
     * Get tags for a Google Drive file
     *
     * @param User $user The user whose Google Drive to use
     * @param string $fileId The Google Drive file ID
     * @return array Array of tag strings
     * @throws CloudStorageException
     */
    public function getFileTags(User $user, string $fileId): array
    {
        $startTime = microtime(true);
        $operationId = $this->logService->logOperationStart('get_tags', self::PROVIDER_NAME, $user, [
            'file_id' => $fileId,
        ]);

        try {
            $service = $this->driveService->getDriveService($user);
            
            $file = $service->files->get($fileId, ['fields' => 'properties']);
            $properties = $file->getProperties() ?? [];
            
            $tags = [];
            if (isset($properties['tags']) && !empty($properties['tags'])) {
                $tags = explode(',', $properties['tags']);
                $tags = array_filter(array_map('trim', $tags)); // Remove empty tags
            }

            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logService->logOperationSuccess($operationId, 'get_tags', self::PROVIDER_NAME, $user, [
                'file_id' => $fileId,
                'tag_count' => count($tags),
            ], $durationMs);

            return $tags;

        } catch (Exception $e) {
            $errorType = $this->errorHandler->classifyError($e);
            $durationMs = (microtime(true) - $startTime) * 1000;
            
            $this->logService->logOperationFailure(
                $operationId,
                'get_tags',
                self::PROVIDER_NAME,
                $user,
                $errorType,
                $e->getMessage(),
                ['file_id' => $fileId],
                $durationMs,
                $e
            );

            throw CloudStorageException::create(
                $errorType,
                self::PROVIDER_NAME,
                [
                    'operation' => 'get_tags',
                    'file_id' => $fileId,
                    'user_id' => $user->id,
                    'original_message' => $e->getMessage()
                ],
                $e
            );
        }
    }

    /**
     * Get Google Drive-specific optimization recommendations
     *
     * @param User $user The user whose Google Drive to use
     * @param array $context Context information (file size, type, etc.)
     * @return array Array of optimization recommendations
     */
    public function getOptimizationRecommendations(User $user, array $context = []): array
    {
        $recommendations = [];
        $fileSize = $context['file_size'] ?? 0;
        $mimeType = $context['mime_type'] ?? '';
        $accessPattern = $context['access_pattern'] ?? 'unknown';

        // File size recommendations
        if ($fileSize > 5242880) { // 5MB
            $recommendations[] = [
                'type' => 'upload_optimization',
                'title' => 'Use Resumable Upload',
                'description' => 'Files larger than 5MB should use resumable upload for better reliability',
                'priority' => 'high',
                'implementation' => 'Enable resumable upload with appropriate chunk size',
            ];
        }

        // Google Workspace format conversion recommendations
        if (in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ])) {
            $recommendations[] = [
                'type' => 'collaboration_optimization',
                'title' => 'Convert to Google Workspace Format',
                'description' => 'Converting Office documents to Google formats enables real-time collaboration',
                'priority' => 'medium',
                'implementation' => 'Enable format conversion during upload',
            ];
        }

        // Image compression recommendations
        if (str_starts_with($mimeType, 'image/') && $fileSize > 10485760) { // 10MB
            $recommendations[] = [
                'type' => 'storage_optimization',
                'title' => 'Compress Large Images',
                'description' => 'Large images can be compressed to save storage space and improve loading times',
                'priority' => 'medium',
                'implementation' => 'Enable image compression during upload',
            ];
        }

        // Sharing and permissions recommendations
        if ($accessPattern === 'collaborative') {
            $recommendations[] = [
                'type' => 'collaboration_optimization',
                'title' => 'Set Appropriate Sharing Permissions',
                'description' => 'Configure sharing permissions to enable collaboration while maintaining security',
                'priority' => 'high',
                'implementation' => 'Set file permissions based on collaboration needs',
            ];
        }

        // Organization recommendations
        $recommendations[] = [
            'type' => 'organization_optimization',
            'title' => 'Use Descriptive File Names',
            'description' => 'Google Drive\'s search is powerful - use descriptive names for better findability',
            'priority' => 'low',
            'implementation' => 'Include relevant keywords in file names',
        ];

        // Backup recommendations
        if ($context['important'] ?? false) {
            $recommendations[] = [
                'type' => 'backup_optimization',
                'title' => 'Enable Version History',
                'description' => 'Important files should have version history enabled for recovery purposes',
                'priority' => 'medium',
                'implementation' => 'Google Drive automatically maintains version history',
            ];
        }

        // Quota management recommendations
        try {
            $healthStatus = $this->getConnectionHealth($user);
            $quotaUsagePercent = $healthStatus->providerSpecificData['quota_usage_percent'] ?? 0;
            
            if ($quotaUsagePercent > 80) {
                $recommendations[] = [
                    'type' => 'quota_optimization',
                    'title' => 'Manage Storage Quota',
                    'description' => 'Your Google Drive is nearly full. Consider cleaning up old files or upgrading storage',
                    'priority' => 'high',
                    'implementation' => 'Review and delete unnecessary files, or upgrade to Google One',
                ];
            }
        } catch (Exception $e) {
            // Ignore quota check errors
        }

        Log::debug('Google Drive optimization recommendations generated', [
            'user_id' => $user->id,
            'context' => $context,
            'recommendation_count' => count($recommendations),
        ]);

        return $recommendations;
    }

    // ========================================
    // PRIVATE HELPER METHODS FOR ADVANCED FEATURES
    // ========================================

    /**
     * Get file size category for optimization purposes
     *
     * @param int $fileSize File size in bytes
     * @return string Size category
     */
    private function getFileSizeCategory(int $fileSize): string
    {
        return match (true) {
            $fileSize < 1048576 => 'small', // < 1MB
            $fileSize < 52428800 => 'medium', // < 50MB
            $fileSize < 1073741824 => 'large', // < 1GB
            default => 'very_large', // >= 1GB
        };
    }
}