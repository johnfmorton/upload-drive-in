<?php

namespace App\Services;

use App\Contracts\CloudStorageProviderInterface;
use App\Exceptions\CloudStorageException;
use App\Exceptions\CloudStorageSetupException;
use App\Models\User;
use App\Models\CloudStorageSetting;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Amazon S3 provider implementation
 * 
 * Implements CloudStorageProviderInterface to provide Amazon S3
 * operations with flat storage model and key-based file organization
 */
class S3Provider implements CloudStorageProviderInterface
{
    private const PROVIDER_NAME = 'amazon-s3';
    
    private ?S3Client $s3Client = null;
    private array $config = [];

    public function __construct(
        private readonly S3ErrorHandler $errorHandler,
        private readonly CloudStorageLogService $logService
    ) {}

    /**
     * Upload a file to Amazon S3
     *
     * @param User $user The user whose S3 to use
     * @param string $localPath Path to the local file to upload
     * @param string $targetPath Target path/folder in S3 (client email)
     * @param array $metadata Additional metadata for the file
     * @return string The S3 object key
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
            $this->ensureInitialized($user);

            // Extract required metadata
            $originalFilename = $metadata['original_filename'] ?? basename($localPath);
            $mimeType = $metadata['mime_type'] ?? 'application/octet-stream';
            $description = $metadata['description'] ?? null;

            // Generate S3 key (flat storage model with key-based organization)
            $key = $this->generateS3Key($targetPath, $originalFilename);

            // Prepare upload parameters
            $uploadParams = [
                'Bucket' => $this->getBucket($user),
                'Key' => $key,
                'SourceFile' => $localPath,
                'ContentType' => $mimeType,
                'Metadata' => [
                    'original_filename' => $originalFilename,
                    'client_email' => $targetPath,
                    'uploaded_by' => (string) $user->id,
                    'upload_timestamp' => now()->toISOString(),
                ],
            ];

            // Add description if provided
            if ($description) {
                $uploadParams['Metadata']['description'] = $description;
            }

            // Upload file to S3
            $result = $this->s3Client->putObject($uploadParams);

            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logService->logOperationSuccess($operationId, 'upload', self::PROVIDER_NAME, $user, [
                'key' => $key,
                'filename' => $originalFilename,
                'file_size' => file_exists($localPath) ? filesize($localPath) : null,
                'mime_type' => $mimeType,
                'etag' => $result['ETag'] ?? null,
            ], $durationMs);

            return $key;

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
     * Delete a file from Amazon S3
     *
     * @param User $user The user whose S3 to use
     * @param string $fileId The S3 object key
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
            $this->ensureInitialized($user);

            $this->s3Client->deleteObject([
                'Bucket' => $this->getBucket($user),
                'Key' => $fileId,
            ]);

            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logService->logOperationSuccess($operationId, 'delete', self::PROVIDER_NAME, $user, [
                'key' => $fileId,
            ], $durationMs);

            return true;

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
    }    /**
 
    * Check the health status of the S3 connection
     *
     * @param User $user The user whose connection to check
     * @return CloudStorageHealthStatus The current health status
     */
    public function getConnectionHealth(User $user): CloudStorageHealthStatus
    {
        $startTime = microtime(true);
        $operationId = $this->logService->logOperationStart('health_check', self::PROVIDER_NAME, $user, [
            'bucket' => $this->config['bucket'] ?? 'not_configured',
            'region' => $this->config['region'] ?? 'not_configured',
        ]);

        try {
            $this->ensureInitialized($user);

            // Perform a lightweight health check by listing bucket contents (limit 1)
            $result = $this->s3Client->listObjectsV2([
                'Bucket' => $this->getBucket($user),
                'MaxKeys' => 1,
            ]);

            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logService->logOperationSuccess($operationId, 'health_check', self::PROVIDER_NAME, $user, [
                'bucket' => $this->getBucket($user),
                'region' => $this->getRegion($user),
                'objects_found' => count($result['Contents'] ?? []),
            ], $durationMs);

            return new CloudStorageHealthStatus(
                provider: self::PROVIDER_NAME,
                status: CloudStorageHealthStatus::STATUS_HEALTHY,
                lastSuccessfulOperation: now(),
                providerSpecificData: [
                    'bucket' => $this->getBucket($user),
                    'region' => $this->getRegion($user),
                    'health_check_successful' => true,
                ]
            );

        } catch (Exception $e) {
            $errorType = $this->errorHandler->classifyError($e);
            $durationMs = (microtime(true) - $startTime) * 1000;
            
            $this->logService->logOperationFailure(
                $operationId,
                'health_check',
                self::PROVIDER_NAME,
                $user,
                $errorType,
                $e->getMessage(),
                [
                    'bucket' => $this->config['bucket'] ?? 'not_configured',
                    'region' => $this->config['region'] ?? 'not_configured',
                ],
                $durationMs,
                $e
            );

            $requiresReconnection = in_array($errorType, [
                \App\Enums\CloudStorageErrorType::INVALID_CREDENTIALS,
                \App\Enums\CloudStorageErrorType::BUCKET_NOT_FOUND,
                \App\Enums\CloudStorageErrorType::BUCKET_ACCESS_DENIED,
                \App\Enums\CloudStorageErrorType::INVALID_REGION,
                \App\Enums\CloudStorageErrorType::PROVIDER_NOT_CONFIGURED,
            ]);

            return CloudStorageHealthStatus::unhealthy(
                self::PROVIDER_NAME,
                consecutiveFailures: 1,
                lastErrorType: $errorType,
                lastErrorMessage: $e->getMessage(),
                requiresReconnection: $requiresReconnection,
                providerSpecificData: [
                    'health_check_failed_at' => now()->toISOString(),
                    'error_details' => $e->getMessage(),
                    'bucket' => $this->config['bucket'] ?? 'not_configured',
                    'region' => $this->config['region'] ?? 'not_configured',
                ]
            );
        }
    }

    /**
     * Handle OAuth callback after user authorization
     * Note: S3 uses API key authentication, not OAuth
     *
     * @param User $user The user to associate the token with
     * @param string $code The authorization code from OAuth callback
     * @return void
     * @throws CloudStorageException
     */
    public function handleAuthCallback(User $user, string $code): void
    {
        throw new CloudStorageException(
            message: 'S3 provider does not support OAuth authentication',
            errorType: \App\Enums\CloudStorageErrorType::FEATURE_NOT_SUPPORTED,
            context: [
                'operation' => 'oauth_callback',
                'user_id' => $user->id,
                'provider' => self::PROVIDER_NAME,
            ],
            provider: self::PROVIDER_NAME
        );
    }

    /**
     * Get the OAuth authorization URL for user authentication
     * Note: S3 uses API key authentication, not OAuth
     *
     * @param User $user The user to generate auth URL for
     * @param bool $isReconnection Whether this is a reconnection attempt
     * @return string The OAuth authorization URL
     */
    public function getAuthUrl(User $user, bool $isReconnection = false): string
    {
        throw new CloudStorageException(
            message: 'S3 provider does not support OAuth authentication',
            errorType: \App\Enums\CloudStorageErrorType::FEATURE_NOT_SUPPORTED,
            context: [
                'operation' => 'get_auth_url',
                'user_id' => $user->id,
                'provider' => self::PROVIDER_NAME,
                'is_reconnection' => $isReconnection,
            ],
            provider: self::PROVIDER_NAME
        );
    }

    /**
     * Disconnect the user's S3 account
     * Note: For S3, this means clearing system-level stored credentials
     *
     * @param User $user The user requesting disconnect (must be admin)
     * @return void
     */
    public function disconnect(User $user): void
    {
        try {
            $this->logService->logOAuthEvent(self::PROVIDER_NAME, $user, 'disconnect_start', true);
            
            // Clear system-level S3 credentials from database
            // Note: cloud_storage_settings table is system-level only (no user_id column)
            CloudStorageSetting::where('provider', self::PROVIDER_NAME)
                ->delete();

            // Clear the initialized client
            $this->s3Client = null;
            $this->config = [];

            $this->logService->logOAuthEvent(self::PROVIDER_NAME, $user, 'disconnect_complete', true);

        } catch (Exception $e) {
            $this->logService->logOAuthEvent(self::PROVIDER_NAME, $user, 'disconnect_failed', false, $e->getMessage());
            
            // Don't throw exception for disconnect failures - we want to clean up locally regardless
            Log::warning('S3 disconnect failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
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
     * Check if the user has a valid connection to S3
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
            Log::debug('S3 connection validity check failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    // ========================================
    // NEW ENHANCED INTERFACE METHODS
    // ========================================

    /**
     * Get the capabilities supported by Amazon S3
     *
     * @return array Array of capability names and their support status
     */
    public function getCapabilities(): array
    {
        return [
            'folder_creation' => false, // S3 uses key prefixes, not real folders
            'file_upload' => true,
            'file_delete' => true,
            'folder_delete' => false, // No real folders in S3
            'file_download' => true,
            'file_metadata' => true,
            'oauth_authentication' => false, // S3 uses API keys
            'api_key_authentication' => true,
            'hierarchical_storage' => false, // S3 is flat storage
            'flat_storage' => true,
            'file_sharing' => true,
            'version_history' => true, // S3 versioning
            'search' => false, // Limited search capabilities
            'batch_operations' => true,
            'presigned_urls' => true,
            'storage_classes' => true, // Standard, IA, Glacier, etc.
            'encryption_at_rest' => true,
            'access_control' => true,
            'multipart_upload' => true,
            'cross_region_replication' => true,
        ];
    }

    /**
     * Validate S3 provider configuration
     *
     * @param array $config Configuration array to validate
     * @return array Array of validation errors (empty if valid)
     */
    public function validateConfiguration(array $config): array
    {
        $errors = [];

        // Check required configuration keys
        $requiredKeys = ['access_key_id', 'secret_access_key', 'region', 'bucket'];
        
        foreach ($requiredKeys as $key) {
            if (empty($config[$key])) {
                $errors[] = "Missing required configuration key: {$key}";
            }
        }

        // Validate access_key_id format (should be AWS access key format)
        if (!empty($config['access_key_id'])) {
            if (!preg_match('/^[A-Z0-9]{20}$/', $config['access_key_id'])) {
                $errors[] = "Invalid AWS access_key_id format";
            }
        }

        // Validate secret_access_key format (should be AWS secret key format)
        if (!empty($config['secret_access_key'])) {
            if (strlen($config['secret_access_key']) !== 40) {
                $errors[] = "Invalid AWS secret_access_key format";
            }
        }

        // Validate region format
        if (!empty($config['region'])) {
            if (!preg_match('/^[a-z0-9-]+$/', $config['region'])) {
                $errors[] = "Invalid AWS region format";
            }
        }

        // Validate bucket name format
        if (!empty($config['bucket'])) {
            if (!$this->isValidBucketName($config['bucket'])) {
                $errors[] = "Invalid S3 bucket name format";
            }
        }

        // Validate endpoint if provided (for S3-compatible services)
        if (!empty($config['endpoint'])) {
            if (!filter_var($config['endpoint'], FILTER_VALIDATE_URL)) {
                $errors[] = "Invalid endpoint URL format";
            }
        }

        return $errors;
    }

    /**
     * Initialize S3 provider with configuration
     *
     * @param array $config Provider-specific configuration
     * @return void
     * @throws CloudStorageSetupException
     */
    public function initialize(array $config): void
    {
        $startTime = microtime(true);
        
        // Log configuration change start
        Log::info('S3Provider: Configuration initialization started', [
            'provider' => self::PROVIDER_NAME,
            'region' => $config['region'] ?? 'not_set',
            'bucket' => $config['bucket'] ?? 'not_set',
            'has_custom_endpoint' => !empty($config['endpoint']),
            'endpoint' => $config['endpoint'] ?? null,
        ]);

        // Validate configuration
        $errors = $this->validateConfiguration($config);
        if (!empty($errors)) {
            Log::error('S3Provider: Configuration validation failed', [
                'provider' => self::PROVIDER_NAME,
                'errors' => $errors,
                'config_keys' => array_keys($config),
            ]);
            
            throw new CloudStorageSetupException(
                'S3 provider configuration is invalid: ' . implode(', ', $errors)
            );
        }

        $this->config = $config;

        // Initialize S3 client
        $clientConfig = [
            'version' => 'latest',
            'region' => $config['region'],
            'credentials' => [
                'key' => $config['access_key_id'],
                'secret' => $config['secret_access_key'],
            ],
        ];

        // Add custom endpoint if provided (for S3-compatible services)
        if (!empty($config['endpoint'])) {
            $clientConfig['endpoint'] = $config['endpoint'];
            $clientConfig['use_path_style_endpoint'] = true;
        }

        try {
            $this->s3Client = new S3Client($clientConfig);
            
            $durationMs = (microtime(true) - $startTime) * 1000;
            
            Log::info('S3Provider: Configuration initialized successfully', [
                'provider' => self::PROVIDER_NAME,
                'region' => $config['region'],
                'bucket' => $config['bucket'],
                'has_custom_endpoint' => !empty($config['endpoint']),
                'duration_ms' => $durationMs,
            ]);
        } catch (Exception $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;
            
            Log::error('S3Provider: Failed to initialize S3 client', [
                'provider' => self::PROVIDER_NAME,
                'error' => $e->getMessage(),
                'region' => $config['region'] ?? 'not_set',
                'bucket' => $config['bucket'] ?? 'not_set',
                'duration_ms' => $durationMs,
            ]);
            
            throw new CloudStorageSetupException(
                'Failed to initialize S3 client: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Get the authentication type used by S3
     *
     * @return string Authentication type
     */
    public function getAuthenticationType(): string
    {
        return 'api_key';
    }

    /**
     * Get the storage model used by S3
     *
     * @return string Storage model
     */
    public function getStorageModel(): string
    {
        return 'flat';
    }

    /**
     * Get the maximum file size supported by S3
     *
     * @return int Maximum file size in bytes (5TB)
     */
    public function getMaxFileSize(): int
    {
        return 5497558138880; // 5TB in bytes
    }

    /**
     * Get the supported file types for S3
     *
     * @return array Array of supported MIME types
     */
    public function getSupportedFileTypes(): array
    {
        return ['*']; // S3 supports all file types
    }

    /**
     * Check if S3 supports a specific feature
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
     * Clean up S3 provider resources and connections
     *
     * @return void
     */
    public function cleanup(): void
    {
        // Clear the S3 client instance
        $this->s3Client = null;
        $this->config = [];
        
        Log::debug('S3Provider: Cleanup completed', [
            'provider' => self::PROVIDER_NAME
        ]);
    }

    // ========================================
    // ADVANCED PROVIDER FEATURES
    // ========================================

    /**
     * Generate a presigned URL for S3 file access
     *
     * @param User $user The user whose S3 to use
     * @param string $fileId The S3 object key
     * @param int $expirationMinutes URL expiration time in minutes
     * @param string $operation Operation type ('download', 'upload', 'delete')
     * @return string|null Presigned URL
     * @throws CloudStorageException
     */
    public function generatePresignedUrl(User $user, string $fileId, int $expirationMinutes = 60, string $operation = 'download'): ?string
    {
        $startTime = microtime(true);
        $operationId = $this->logService->logOperationStart('generate_presigned_url', self::PROVIDER_NAME, $user, [
            'file_id' => $fileId,
            'operation' => $operation,
            'expiration_minutes' => $expirationMinutes,
        ]);

        try {
            $this->ensureInitialized($user);

            $command = match ($operation) {
                'download' => $this->s3Client->getCommand('GetObject', [
                    'Bucket' => $this->getBucket($user),
                    'Key' => $fileId,
                ]),
                'upload' => $this->s3Client->getCommand('PutObject', [
                    'Bucket' => $this->getBucket($user),
                    'Key' => $fileId,
                ]),
                'delete' => $this->s3Client->getCommand('DeleteObject', [
                    'Bucket' => $this->getBucket($user),
                    'Key' => $fileId,
                ]),
                default => throw new CloudStorageException(
                    message: "Unsupported presigned URL operation: {$operation}",
                    errorType: \App\Enums\CloudStorageErrorType::FEATURE_NOT_SUPPORTED,
                    context: ['operation' => $operation],
                    provider: self::PROVIDER_NAME
                ),
            };

            $request = $this->s3Client->createPresignedRequest(
                $command,
                "+{$expirationMinutes} minutes"
            );

            $presignedUrl = (string) $request->getUri();

            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logService->logOperationSuccess($operationId, 'generate_presigned_url', self::PROVIDER_NAME, $user, [
                'file_id' => $fileId,
                'operation' => $operation,
                'expiration_minutes' => $expirationMinutes,
                'url_length' => strlen($presignedUrl),
            ], $durationMs);

            return $presignedUrl;

        } catch (Exception $e) {
            $errorType = $this->errorHandler->classifyError($e);
            $durationMs = (microtime(true) - $startTime) * 1000;
            
            $this->logService->logOperationFailure(
                $operationId,
                'generate_presigned_url',
                self::PROVIDER_NAME,
                $user,
                $errorType,
                $e->getMessage(),
                [
                    'file_id' => $fileId,
                    'operation' => $operation,
                    'expiration_minutes' => $expirationMinutes,
                ],
                $durationMs,
                $e
            );

            throw CloudStorageException::create(
                $errorType,
                self::PROVIDER_NAME,
                [
                    'operation' => 'generate_presigned_url',
                    'file_id' => $fileId,
                    'presigned_operation' => $operation,
                    'user_id' => $user->id,
                    'original_message' => $e->getMessage()
                ],
                $e
            );
        }
    }

    /**
     * Set storage class for an S3 object
     *
     * @param User $user The user whose S3 to use
     * @param string $fileId The S3 object key
     * @param string $storageClass Storage class name
     * @return bool True if storage class was set successfully
     * @throws CloudStorageException
     */
    public function setStorageClass(User $user, string $fileId, string $storageClass): bool
    {
        $startTime = microtime(true);
        $operationId = $this->logService->logOperationStart('set_storage_class', self::PROVIDER_NAME, $user, [
            'file_id' => $fileId,
            'storage_class' => $storageClass,
        ]);

        try {
            $this->ensureInitialized($user);

            // Validate storage class
            $availableClasses = array_keys($this->getAvailableStorageClasses());
            if (!in_array($storageClass, $availableClasses)) {
                throw new CloudStorageException(
                    message: "Invalid storage class: {$storageClass}",
                    errorType: \App\Enums\CloudStorageErrorType::INVALID_PARAMETER,
                    context: [
                        'storage_class' => $storageClass,
                        'available_classes' => $availableClasses,
                    ],
                    provider: self::PROVIDER_NAME
                );
            }

            // Copy object to itself with new storage class
            $this->s3Client->copyObject([
                'Bucket' => $this->getBucket($user),
                'Key' => $fileId,
                'CopySource' => $this->getBucket($user) . '/' . $fileId,
                'StorageClass' => $storageClass,
                'MetadataDirective' => 'COPY',
            ]);

            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logService->logOperationSuccess($operationId, 'set_storage_class', self::PROVIDER_NAME, $user, [
                'file_id' => $fileId,
                'storage_class' => $storageClass,
            ], $durationMs);

            return true;

        } catch (Exception $e) {
            $errorType = $this->errorHandler->classifyError($e);
            $durationMs = (microtime(true) - $startTime) * 1000;
            
            $this->logService->logOperationFailure(
                $operationId,
                'set_storage_class',
                self::PROVIDER_NAME,
                $user,
                $errorType,
                $e->getMessage(),
                [
                    'file_id' => $fileId,
                    'storage_class' => $storageClass,
                ],
                $durationMs,
                $e
            );

            throw CloudStorageException::create(
                $errorType,
                self::PROVIDER_NAME,
                [
                    'operation' => 'set_storage_class',
                    'file_id' => $fileId,
                    'storage_class' => $storageClass,
                    'user_id' => $user->id,
                    'original_message' => $e->getMessage()
                ],
                $e
            );
        }
    }

    /**
     * Get available S3 storage classes
     *
     * @return array Array of available storage class names with descriptions
     */
    public function getAvailableStorageClasses(): array
    {
        return [
            'STANDARD' => [
                'name' => 'Standard',
                'description' => 'General purpose storage for frequently accessed data',
                'cost_tier' => 'high',
                'retrieval_time' => 'immediate',
                'durability' => '99.999999999%',
                'availability' => '99.99%',
            ],
            'REDUCED_REDUNDANCY' => [
                'name' => 'Reduced Redundancy',
                'description' => 'Lower cost option for non-critical, reproducible data',
                'cost_tier' => 'medium',
                'retrieval_time' => 'immediate',
                'durability' => '99.99%',
                'availability' => '99.99%',
                'deprecated' => true,
            ],
            'STANDARD_IA' => [
                'name' => 'Standard-Infrequent Access',
                'description' => 'For data accessed less frequently but requires rapid access',
                'cost_tier' => 'medium',
                'retrieval_time' => 'immediate',
                'durability' => '99.999999999%',
                'availability' => '99.9%',
                'minimum_storage_duration' => '30 days',
            ],
            'ONEZONE_IA' => [
                'name' => 'One Zone-Infrequent Access',
                'description' => 'Lower cost for infrequently accessed data in a single AZ',
                'cost_tier' => 'low',
                'retrieval_time' => 'immediate',
                'durability' => '99.999999999%',
                'availability' => '99.5%',
                'minimum_storage_duration' => '30 days',
            ],
            'INTELLIGENT_TIERING' => [
                'name' => 'Intelligent Tiering',
                'description' => 'Automatically moves data between access tiers',
                'cost_tier' => 'variable',
                'retrieval_time' => 'immediate',
                'durability' => '99.999999999%',
                'availability' => '99.9%',
                'automatic_tiering' => true,
            ],
            'GLACIER' => [
                'name' => 'Glacier',
                'description' => 'Low-cost archival storage with retrieval times from minutes to hours',
                'cost_tier' => 'very_low',
                'retrieval_time' => '1-5 minutes to 3-5 hours',
                'durability' => '99.999999999%',
                'availability' => '99.99%',
                'minimum_storage_duration' => '90 days',
            ],
            'DEEP_ARCHIVE' => [
                'name' => 'Glacier Deep Archive',
                'description' => 'Lowest cost archival storage with 12-hour retrieval time',
                'cost_tier' => 'lowest',
                'retrieval_time' => '12 hours',
                'durability' => '99.999999999%',
                'availability' => '99.99%',
                'minimum_storage_duration' => '180 days',
            ],
        ];
    }

    /**
     * Apply S3-specific optimizations for file upload
     * Implements multipart upload for large files with progress tracking
     *
     * @param User $user The user whose S3 to use
     * @param string $localPath Path to the local file to upload
     * @param array $options Optimization options
     * @return array Optimized upload parameters and execution result
     * @throws CloudStorageException
     */
    public function optimizeUpload(User $user, string $localPath, array $options = []): array
    {
        $startTime = microtime(true);
        $fileSize = file_exists($localPath) ? filesize($localPath) : 0;
        $mimeType = $options['mime_type'] ?? 'application/octet-stream';
        
        // Configurable multipart threshold (default 50MB as per requirements)
        $multipartThreshold = $options['multipart_threshold'] ?? 52428800; // 50MB
        
        // Configurable chunk size (default 10MB, minimum 5MB per S3 requirements)
        $defaultChunkSize = $options['chunk_size'] ?? 10485760; // 10MB
        $chunkSize = max(5242880, $defaultChunkSize); // Ensure minimum 5MB
        
        $optimizations = [
            'use_multipart' => false,
            'part_size' => null,
            'total_parts' => 0,
            'storage_class' => 'STANDARD',
            'server_side_encryption' => null,
            'cache_control' => null,
            'content_encoding' => null,
            'metadata' => [],
            'progress_tracking' => [
                'enabled' => $options['track_progress'] ?? true,
                'callback' => $options['progress_callback'] ?? null,
                'uploaded_bytes' => 0,
                'total_bytes' => $fileSize,
                'percentage' => 0,
            ],
        ];

        // Use multipart upload for files larger than threshold
        if ($fileSize > $multipartThreshold) {
            $optimizations['use_multipart'] = true;
            
            // Calculate optimal part size
            // S3 allows max 10,000 parts, so ensure we don't exceed that
            $calculatedPartSize = ceil($fileSize / 10000);
            $optimizations['part_size'] = max($chunkSize, $calculatedPartSize);
            $optimizations['total_parts'] = ceil($fileSize / $optimizations['part_size']);
            
            Log::info('S3 multipart upload configured', [
                'user_id' => $user->id,
                'file_size' => $fileSize,
                'part_size' => $optimizations['part_size'],
                'total_parts' => $optimizations['total_parts'],
                'threshold' => $multipartThreshold,
            ]);
        }

        // Optimize storage class based on file type and size
        if (isset($options['access_pattern'])) {
            $optimizations['storage_class'] = match ($options['access_pattern']) {
                'frequent' => 'STANDARD',
                'infrequent' => 'STANDARD_IA',
                'archive' => 'GLACIER',
                'deep_archive' => 'DEEP_ARCHIVE',
                'intelligent' => 'INTELLIGENT_TIERING',
                default => 'STANDARD',
            };
        }

        // Enable server-side encryption for sensitive files
        if ($options['encrypt'] ?? false) {
            $optimizations['server_side_encryption'] = 'AES256';
        }

        // Set cache control for web-accessible files
        if (str_starts_with($mimeType, 'image/') || str_starts_with($mimeType, 'text/')) {
            $optimizations['cache_control'] = 'public, max-age=31536000'; // 1 year
        }

        // Compress text files
        if (str_starts_with($mimeType, 'text/') || $mimeType === 'application/json') {
            $optimizations['content_encoding'] = 'gzip';
        }

        // Add optimization metadata
        $optimizations['metadata']['optimization_applied'] = 'true';
        $optimizations['metadata']['optimization_timestamp'] = now()->toISOString();
        $optimizations['metadata']['file_size_category'] = $this->getFileSizeCategory($fileSize);
        $optimizations['metadata']['multipart_enabled'] = $optimizations['use_multipart'] ? 'true' : 'false';

        // If multipart upload is needed, perform it now
        if ($optimizations['use_multipart'] && isset($options['target_path'])) {
            try {
                $this->ensureInitialized($user);
                
                $uploadResult = $this->performMultipartUpload(
                    $user,
                    $localPath,
                    $options['target_path'],
                    $options['original_filename'] ?? basename($localPath),
                    $optimizations
                );
                
                $optimizations['upload_result'] = $uploadResult;
                $optimizations['upload_completed'] = true;
                
            } catch (Exception $e) {
                $errorType = $this->errorHandler->classifyError($e);
                
                Log::error('S3 multipart upload failed', [
                    'user_id' => $user->id,
                    'file_size' => $fileSize,
                    'error' => $e->getMessage(),
                ]);
                
                throw CloudStorageException::create(
                    $errorType,
                    self::PROVIDER_NAME,
                    [
                        'operation' => 'multipart_upload',
                        'file_path' => $localPath,
                        'file_size' => $fileSize,
                        'user_id' => $user->id,
                        'original_message' => $e->getMessage()
                    ],
                    $e
                );
            }
        }

        $durationMs = (microtime(true) - $startTime) * 1000;
        
        Log::debug('S3 upload optimizations applied', [
            'user_id' => $user->id,
            'file_size' => $fileSize,
            'use_multipart' => $optimizations['use_multipart'],
            'part_size' => $optimizations['part_size'],
            'total_parts' => $optimizations['total_parts'],
            'duration_ms' => $durationMs,
        ]);

        return $optimizations;
    }

    /**
     * Perform multipart upload for large files with progress tracking
     *
     * @param User $user The user whose S3 to use
     * @param string $localPath Path to the local file
     * @param string $targetPath Target path (client email)
     * @param string $originalFilename Original filename
     * @param array $optimizations Optimization parameters
     * @return array Upload result with key and metadata
     * @throws Exception
     */
    private function performMultipartUpload(
        User $user,
        string $localPath,
        string $targetPath,
        string $originalFilename,
        array $optimizations
    ): array {
        $startTime = microtime(true);
        $bucket = $this->getBucket($user);
        $key = $this->generateS3Key($targetPath, $originalFilename);
        $partSize = $optimizations['part_size'];
        $fileSize = filesize($localPath);
        
        Log::info('Starting S3 multipart upload', [
            'user_id' => $user->id,
            'key' => $key,
            'file_size' => $fileSize,
            'part_size' => $partSize,
            'total_parts' => $optimizations['total_parts'],
        ]);

        // Initialize multipart upload
        $uploadParams = [
            'Bucket' => $bucket,
            'Key' => $key,
            'ContentType' => $optimizations['metadata']['mime_type'] ?? 'application/octet-stream',
            'Metadata' => [
                'original_filename' => $originalFilename,
                'client_email' => $targetPath,
                'uploaded_by' => (string) $user->id,
                'upload_timestamp' => now()->toISOString(),
                'multipart_upload' => 'true',
            ],
        ];

        // Add storage class if specified
        if (!empty($optimizations['storage_class'])) {
            $uploadParams['StorageClass'] = $optimizations['storage_class'];
        }

        // Add server-side encryption if specified
        if (!empty($optimizations['server_side_encryption'])) {
            $uploadParams['ServerSideEncryption'] = $optimizations['server_side_encryption'];
        }

        $multipartUpload = $this->s3Client->createMultipartUpload($uploadParams);
        $uploadId = $multipartUpload['UploadId'];

        try {
            $file = fopen($localPath, 'rb');
            if (!$file) {
                throw new Exception("Failed to open file: {$localPath}");
            }

            $parts = [];
            $partNumber = 1;
            $uploadedBytes = 0;
            $progressCallback = $optimizations['progress_tracking']['callback'] ?? null;

            // Upload parts
            while (!feof($file)) {
                $partData = fread($file, $partSize);
                $partLength = strlen($partData);

                if ($partLength === 0) {
                    break;
                }

                Log::debug('Uploading part', [
                    'part_number' => $partNumber,
                    'part_size' => $partLength,
                    'uploaded_bytes' => $uploadedBytes,
                    'total_bytes' => $fileSize,
                ]);

                $uploadPartResult = $this->s3Client->uploadPart([
                    'Bucket' => $bucket,
                    'Key' => $key,
                    'UploadId' => $uploadId,
                    'PartNumber' => $partNumber,
                    'Body' => $partData,
                ]);

                $parts[] = [
                    'PartNumber' => $partNumber,
                    'ETag' => $uploadPartResult['ETag'],
                ];

                $uploadedBytes += $partLength;
                $percentage = ($uploadedBytes / $fileSize) * 100;

                // Call progress callback if provided
                if (is_callable($progressCallback)) {
                    $progressCallback([
                        'uploaded_bytes' => $uploadedBytes,
                        'total_bytes' => $fileSize,
                        'percentage' => $percentage,
                        'part_number' => $partNumber,
                        'total_parts' => $optimizations['total_parts'],
                    ]);
                }

                Log::debug('Part uploaded successfully', [
                    'part_number' => $partNumber,
                    'percentage' => round($percentage, 2),
                ]);

                $partNumber++;
            }

            fclose($file);

            // Complete multipart upload
            $result = $this->s3Client->completeMultipartUpload([
                'Bucket' => $bucket,
                'Key' => $key,
                'UploadId' => $uploadId,
                'MultipartUpload' => [
                    'Parts' => $parts,
                ],
            ]);

            $durationMs = (microtime(true) - $startTime) * 1000;

            Log::info('S3 multipart upload completed successfully', [
                'user_id' => $user->id,
                'key' => $key,
                'file_size' => $fileSize,
                'parts_uploaded' => count($parts),
                'duration_ms' => $durationMs,
                'etag' => $result['ETag'] ?? null,
            ]);

            return [
                'key' => $key,
                'etag' => $result['ETag'] ?? null,
                'location' => $result['Location'] ?? null,
                'parts_uploaded' => count($parts),
                'total_bytes' => $fileSize,
                'duration_ms' => $durationMs,
            ];

        } catch (Exception $e) {
            // Abort multipart upload on failure
            try {
                $this->s3Client->abortMultipartUpload([
                    'Bucket' => $bucket,
                    'Key' => $key,
                    'UploadId' => $uploadId,
                ]);
                
                Log::warning('Multipart upload aborted due to error', [
                    'upload_id' => $uploadId,
                    'key' => $key,
                ]);
            } catch (Exception $abortException) {
                Log::error('Failed to abort multipart upload', [
                    'upload_id' => $uploadId,
                    'error' => $abortException->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Set custom metadata for an S3 object
     *
     * @param User $user The user whose S3 to use
     * @param string $fileId The S3 object key
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
            $this->ensureInitialized($user);

            // Get current object metadata
            $headResult = $this->s3Client->headObject([
                'Bucket' => $this->getBucket($user),
                'Key' => $fileId,
            ]);

            $currentMetadata = $headResult['Metadata'] ?? [];
            $mergedMetadata = array_merge($currentMetadata, $metadata);

            // Copy object to itself with updated metadata
            $this->s3Client->copyObject([
                'Bucket' => $this->getBucket($user),
                'Key' => $fileId,
                'CopySource' => $this->getBucket($user) . '/' . $fileId,
                'Metadata' => $mergedMetadata,
                'MetadataDirective' => 'REPLACE',
            ]);

            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logService->logOperationSuccess($operationId, 'set_metadata', self::PROVIDER_NAME, $user, [
                'file_id' => $fileId,
                'metadata_keys' => array_keys($metadata),
                'total_metadata_keys' => count($mergedMetadata),
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
     * Get custom metadata for an S3 object
     *
     * @param User $user The user whose S3 to use
     * @param string $fileId The S3 object key
     * @return array Custom metadata key-value pairs
     * @throws CloudStorageException
     */
    public function getFileMetadata(User $user, string $fileId): array
    {
        $startTime = microtime(true);
        $operationId = $this->logService->logOperationStart('get_metadata', self::PROVIDER_NAME, $user, [
            'file_id' => $fileId,
        ]);

        try {
            $this->ensureInitialized($user);

            $result = $this->s3Client->headObject([
                'Bucket' => $this->getBucket($user),
                'Key' => $fileId,
            ]);

            $metadata = $result['Metadata'] ?? [];

            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logService->logOperationSuccess($operationId, 'get_metadata', self::PROVIDER_NAME, $user, [
                'file_id' => $fileId,
                'metadata_keys' => array_keys($metadata),
            ], $durationMs);

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
     * Add tags to an S3 object
     *
     * @param User $user The user whose S3 to use
     * @param string $fileId The S3 object key
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
            $this->ensureInitialized($user);

            // Get current tags
            try {
                $currentTagsResult = $this->s3Client->getObjectTagging([
                    'Bucket' => $this->getBucket($user),
                    'Key' => $fileId,
                ]);
                $currentTags = $currentTagsResult['TagSet'] ?? [];
            } catch (Exception $e) {
                // Object might not have tags yet
                $currentTags = [];
            }

            // Convert current tags to key-value format
            $currentTagsMap = [];
            foreach ($currentTags as $tag) {
                $currentTagsMap[$tag['Key']] = $tag['Value'];
            }

            // Add new tags (using tag as both key and value for simplicity)
            foreach ($tags as $tag) {
                $currentTagsMap[$tag] = $tag;
            }

            // Convert back to S3 tag format
            $tagSet = [];
            foreach ($currentTagsMap as $key => $value) {
                $tagSet[] = ['Key' => $key, 'Value' => $value];
            }

            // Set the updated tags
            $this->s3Client->putObjectTagging([
                'Bucket' => $this->getBucket($user),
                'Key' => $fileId,
                'Tagging' => ['TagSet' => $tagSet],
            ]);

            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logService->logOperationSuccess($operationId, 'add_tags', self::PROVIDER_NAME, $user, [
                'file_id' => $fileId,
                'tags_added' => $tags,
                'total_tags' => count($tagSet),
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
     * Get tags for an S3 object
     *
     * @param User $user The user whose S3 to use
     * @param string $fileId The S3 object key
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
            $this->ensureInitialized($user);

            $result = $this->s3Client->getObjectTagging([
                'Bucket' => $this->getBucket($user),
                'Key' => $fileId,
            ]);

            $tags = [];
            foreach ($result['TagSet'] ?? [] as $tag) {
                $tags[] = $tag['Key'];
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
     * Get S3-specific optimization recommendations
     *
     * @param User $user The user whose S3 to use
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
        if ($fileSize > 104857600) { // 100MB
            $recommendations[] = [
                'type' => 'upload_optimization',
                'title' => 'Use Multipart Upload',
                'description' => 'Files larger than 100MB should use multipart upload for better performance and reliability',
                'priority' => 'high',
                'implementation' => 'Enable multipart upload with appropriate part size',
            ];
        }

        // Storage class recommendations
        if ($accessPattern === 'infrequent') {
            $recommendations[] = [
                'type' => 'cost_optimization',
                'title' => 'Use Standard-IA Storage Class',
                'description' => 'For infrequently accessed files, Standard-IA can reduce storage costs by up to 40%',
                'priority' => 'medium',
                'implementation' => 'Set storage class to STANDARD_IA during upload',
            ];
        } elseif ($accessPattern === 'archive') {
            $recommendations[] = [
                'type' => 'cost_optimization',
                'title' => 'Use Glacier Storage Class',
                'description' => 'For archival data, Glacier can reduce storage costs by up to 80%',
                'priority' => 'medium',
                'implementation' => 'Set storage class to GLACIER for long-term storage',
            ];
        }

        // Compression recommendations
        if (str_starts_with($mimeType, 'text/') || $mimeType === 'application/json') {
            $recommendations[] = [
                'type' => 'performance_optimization',
                'title' => 'Enable Compression',
                'description' => 'Text files can be compressed to reduce storage costs and transfer time',
                'priority' => 'low',
                'implementation' => 'Set Content-Encoding to gzip',
            ];
        }

        // Security recommendations
        if ($context['sensitive'] ?? false) {
            $recommendations[] = [
                'type' => 'security_optimization',
                'title' => 'Enable Server-Side Encryption',
                'description' => 'Sensitive files should be encrypted at rest using S3 server-side encryption',
                'priority' => 'high',
                'implementation' => 'Enable AES256 or KMS encryption',
            ];
        }

        // Lifecycle recommendations
        if ($fileSize > 1073741824) { // 1GB
            $recommendations[] = [
                'type' => 'lifecycle_optimization',
                'title' => 'Configure Lifecycle Policy',
                'description' => 'Large files should have lifecycle policies to automatically transition to cheaper storage classes',
                'priority' => 'medium',
                'implementation' => 'Create lifecycle rule to transition to IA after 30 days, Glacier after 90 days',
            ];
        }

        // Intelligent Tiering recommendation
        if ($accessPattern === 'unknown' && $fileSize > 134217728) { // 128KB minimum for Intelligent Tiering
            $recommendations[] = [
                'type' => 'cost_optimization',
                'title' => 'Consider Intelligent Tiering',
                'description' => 'For files with unknown access patterns, Intelligent Tiering automatically optimizes costs',
                'priority' => 'low',
                'implementation' => 'Set storage class to INTELLIGENT_TIERING',
            ];
        }

        Log::debug('S3 optimization recommendations generated', [
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
            $fileSize < 104857600 => 'medium', // < 100MB
            $fileSize < 1073741824 => 'large', // < 1GB
            default => 'very_large', // >= 1GB
        };
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

    /**
     * Ensure the S3 client is initialized for the user
     *
     * @param User $user
     * @return void
     * @throws CloudStorageException
     */
    private function ensureInitialized(User $user): void
    {
        if ($this->s3Client !== null) {
            return;
        }

        // Get user-specific S3 configuration
        $config = $this->getUserConfig($user);
        
        if (empty($config)) {
            throw new CloudStorageException(
                message: 'S3 provider not configured for user',
                errorType: \App\Enums\CloudStorageErrorType::PROVIDER_NOT_CONFIGURED,
                context: [
                    'user_id' => $user->id,
                    'provider' => self::PROVIDER_NAME,
                ],
                provider: self::PROVIDER_NAME
            );
        }

        $this->initialize($config);
    }

    /**
     * Get system-level S3 configuration
     * Note: S3 uses system-level credentials, not per-user credentials
     *
     * @return array
     */
    private function getSystemConfig(): array
    {
        // Get system-level configuration from database
        // Note: cloud_storage_settings table is system-level only (no user_id column)
        $settings = CloudStorageSetting::where('provider', self::PROVIDER_NAME)
            ->get();

        if ($settings->isEmpty()) {
            // Fallback to configuration file
            $globalConfig = config('cloud-storage.providers.amazon-s3.config', []);
            return $globalConfig;
        }

        // Build configuration array with decrypted values
        $config = [];
        foreach ($settings as $setting) {
            $key = $setting->key;
            $config[$key] = $setting->decrypted_value;
        }

        return $config;
    }

    /**
     * Get user-specific S3 configuration
     * Note: For S3, this delegates to system-level config
     *
     * @param User $user
     * @return array
     */
    private function getUserConfig(User $user): array
    {
        // S3 uses system-level credentials for all users
        return $this->getSystemConfig();
    }

    /**
     * Get the S3 bucket for the user
     *
     * @param User $user
     * @return string
     */
    private function getBucket(User $user): string
    {
        return $this->config['bucket'] ?? '';
    }

    /**
     * Get the AWS region for the user
     *
     * @param User $user
     * @return string
     */
    private function getRegion(User $user): string
    {
        return $this->config['region'] ?? 'us-east-1';
    }

    /**
     * Generate S3 key for flat storage model
     *
     * @param string $targetPath Client email or folder path
     * @param string $filename Original filename
     * @return string S3 object key
     */
    private function generateS3Key(string $targetPath, string $filename): string
    {
        // Clean the target path (client email) to be S3-safe
        $cleanPath = $this->sanitizeS3Key($targetPath);
        
        // Generate unique filename to avoid conflicts
        $timestamp = now()->format('Y-m-d_H-i-s');
        $randomSuffix = Str::random(8);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        
        $uniqueFilename = $this->sanitizeS3Key($baseName) . '_' . $timestamp . '_' . $randomSuffix;
        if ($extension) {
            $uniqueFilename .= '.' . $extension;
        }

        return $cleanPath . '/' . $uniqueFilename;
    }

    /**
     * Sanitize string for use in S3 key
     *
     * @param string $input
     * @return string
     */
    private function sanitizeS3Key(string $input): string
    {
        // Replace invalid characters with underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $input);
        
        // Remove multiple consecutive underscores
        $sanitized = preg_replace('/_+/', '_', $sanitized);
        
        // Trim underscores from start and end
        return trim($sanitized, '_');
    }

    /**
     * Validate S3 bucket name format
     *
     * @param string $bucketName
     * @return bool
     */
    private function isValidBucketName(string $bucketName): bool
    {
        // S3 bucket naming rules
        if (strlen($bucketName) < 3 || strlen($bucketName) > 63) {
            return false;
        }

        if (!preg_match('/^[a-z0-9][a-z0-9\-]*[a-z0-9]$/', $bucketName)) {
            return false;
        }

        if (strpos($bucketName, '--') !== false) {
            return false;
        }

        if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $bucketName)) {
            return false;
        }

        return true;
    }
}