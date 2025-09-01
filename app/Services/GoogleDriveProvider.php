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
     * Get file metadata from Google Drive
     *
     * @param User $user The user whose Google Drive to use
     * @param string $fileId The Google Drive file ID
     * @return array File metadata
     * @throws CloudStorageException
     */
    public function getFileMetadata(User $user, string $fileId): array
    {
        try {
            Log::debug('Getting Google Drive file metadata', [
                'user_id' => $user->id,
                'file_id' => $fileId
            ]);

            $file = $this->driveService->getFileMetadata($user, $fileId);

            $metadata = [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'created_time' => $file->getCreatedTime(),
                'modified_time' => $file->getModifiedTime(),
                'parents' => $file->getParents()
            ];

            Log::debug('Google Drive file metadata retrieved', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'metadata' => $metadata
            ]);

            return $metadata;

        } catch (Exception $e) {
            $errorType = $this->errorHandler->classifyError($e);
            
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
}