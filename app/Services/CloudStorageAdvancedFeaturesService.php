<?php

namespace App\Services;

use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use App\Exceptions\CloudStorageException;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing advanced cloud storage provider features
 * 
 * Provides a unified interface for advanced features like presigned URLs,
 * storage classes, metadata management, and optimization recommendations
 * across all cloud storage providers.
 */
class CloudStorageAdvancedFeaturesService
{
    public function __construct(
        private readonly CloudStorageManager $storageManager,
        private readonly CloudStorageLogService $logService
    ) {}

    /**
     * Generate a presigned URL for file access
     *
     * @param User $user The user whose cloud storage to use
     * @param string $fileId The cloud storage file ID
     * @param int $expirationMinutes URL expiration time in minutes
     * @param string $operation Operation type ('download', 'upload', 'delete')
     * @param string|null $providerName Specific provider to use (optional)
     * @return array Result with URL and provider information
     * @throws CloudStorageException
     */
    public function generatePresignedUrl(
        User $user, 
        string $fileId, 
        int $expirationMinutes = 60, 
        string $operation = 'download',
        ?string $providerName = null
    ): array {
        $provider = $this->storageManager->getProvider($providerName, $user);
        
        Log::info('Generating presigned URL', [
            'user_id' => $user->id,
            'file_id' => $fileId,
            'provider' => $provider->getProviderName(),
            'operation' => $operation,
            'expiration_minutes' => $expirationMinutes,
        ]);

        $url = $provider->generatePresignedUrl($user, $fileId, $expirationMinutes, $operation);
        
        return [
            'url' => $url,
            'provider' => $provider->getProviderName(),
            'supported' => $url !== null,
            'expires_at' => $url ? now()->addMinutes($expirationMinutes)->toISOString() : null,
            'operation' => $operation,
        ];
    }

    /**
     * Set storage class for a file
     *
     * @param User $user The user whose cloud storage to use
     * @param string $fileId The cloud storage file ID
     * @param string $storageClass Storage class name
     * @param string|null $providerName Specific provider to use (optional)
     * @return array Result with success status and provider information
     * @throws CloudStorageException
     */
    public function setStorageClass(
        User $user, 
        string $fileId, 
        string $storageClass,
        ?string $providerName = null
    ): array {
        $provider = $this->storageManager->getProvider($providerName, $user);
        
        Log::info('Setting storage class', [
            'user_id' => $user->id,
            'file_id' => $fileId,
            'provider' => $provider->getProviderName(),
            'storage_class' => $storageClass,
        ]);

        try {
            $success = $provider->setStorageClass($user, $fileId, $storageClass);
            
            return [
                'success' => $success,
                'provider' => $provider->getProviderName(),
                'storage_class' => $storageClass,
                'supported' => true,
            ];
        } catch (CloudStorageException $e) {
            if ($e->getErrorType() === \App\Enums\CloudStorageErrorType::FEATURE_NOT_SUPPORTED) {
                return [
                    'success' => false,
                    'provider' => $provider->getProviderName(),
                    'storage_class' => $storageClass,
                    'supported' => false,
                    'message' => 'Storage classes not supported by this provider',
                ];
            }
            throw $e;
        }
    }

    /**
     * Get available storage classes for a provider
     *
     * @param User $user The user whose cloud storage to use
     * @param string|null $providerName Specific provider to use (optional)
     * @return array Available storage classes with provider information
     */
    public function getAvailableStorageClasses(User $user, ?string $providerName = null): array
    {
        $provider = $this->storageManager->getProvider($providerName, $user);
        
        $storageClasses = $provider->getAvailableStorageClasses();
        
        return [
            'provider' => $provider->getProviderName(),
            'storage_classes' => $storageClasses,
            'supported' => !empty($storageClasses),
        ];
    }

    /**
     * Apply provider-specific optimizations for file upload
     *
     * @param User $user The user whose cloud storage to use
     * @param string $localPath Path to the local file to upload
     * @param array $options Optimization options
     * @param string|null $providerName Specific provider to use (optional)
     * @return array Optimized upload parameters with provider information
     */
    public function optimizeUpload(
        User $user, 
        string $localPath, 
        array $options = [],
        ?string $providerName = null
    ): array {
        $provider = $this->storageManager->getProvider($providerName, $user);
        
        Log::debug('Optimizing upload', [
            'user_id' => $user->id,
            'local_path' => $localPath,
            'provider' => $provider->getProviderName(),
            'options' => $options,
        ]);

        $optimizations = $provider->optimizeUpload($user, $localPath, $options);
        
        return [
            'provider' => $provider->getProviderName(),
            'optimizations' => $optimizations,
            'file_size' => file_exists($localPath) ? filesize($localPath) : 0,
        ];
    }

    /**
     * Set custom metadata for a file
     *
     * @param User $user The user whose cloud storage to use
     * @param string $fileId The cloud storage file ID
     * @param array $metadata Custom metadata key-value pairs
     * @param string|null $providerName Specific provider to use (optional)
     * @return array Result with success status and provider information
     * @throws CloudStorageException
     */
    public function setFileMetadata(
        User $user, 
        string $fileId, 
        array $metadata,
        ?string $providerName = null
    ): array {
        $provider = $this->storageManager->getProvider($providerName, $user);
        
        Log::info('Setting file metadata', [
            'user_id' => $user->id,
            'file_id' => $fileId,
            'provider' => $provider->getProviderName(),
            'metadata_keys' => array_keys($metadata),
        ]);

        $success = $provider->setFileMetadata($user, $fileId, $metadata);
        
        return [
            'success' => $success,
            'provider' => $provider->getProviderName(),
            'metadata_keys' => array_keys($metadata),
        ];
    }

    /**
     * Get custom metadata for a file
     *
     * @param User $user The user whose cloud storage to use
     * @param string $fileId The cloud storage file ID
     * @param string|null $providerName Specific provider to use (optional)
     * @return array File metadata with provider information
     * @throws CloudStorageException
     */
    public function getFileMetadata(
        User $user, 
        string $fileId,
        ?string $providerName = null
    ): array {
        $provider = $this->storageManager->getProvider($providerName, $user);
        
        $metadata = $provider->getFileMetadata($user, $fileId);
        
        return [
            'provider' => $provider->getProviderName(),
            'metadata' => $metadata,
        ];
    }

    /**
     * Add tags to a file
     *
     * @param User $user The user whose cloud storage to use
     * @param string $fileId The cloud storage file ID
     * @param array $tags Array of tag strings
     * @param string|null $providerName Specific provider to use (optional)
     * @return array Result with success status and provider information
     * @throws CloudStorageException
     */
    public function addFileTags(
        User $user, 
        string $fileId, 
        array $tags,
        ?string $providerName = null
    ): array {
        $provider = $this->storageManager->getProvider($providerName, $user);
        
        Log::info('Adding file tags', [
            'user_id' => $user->id,
            'file_id' => $fileId,
            'provider' => $provider->getProviderName(),
            'tags' => $tags,
        ]);

        $success = $provider->addFileTags($user, $fileId, $tags);
        
        return [
            'success' => $success,
            'provider' => $provider->getProviderName(),
            'tags_added' => $tags,
        ];
    }

    /**
     * Get tags for a file
     *
     * @param User $user The user whose cloud storage to use
     * @param string $fileId The cloud storage file ID
     * @param string|null $providerName Specific provider to use (optional)
     * @return array File tags with provider information
     * @throws CloudStorageException
     */
    public function getFileTags(
        User $user, 
        string $fileId,
        ?string $providerName = null
    ): array {
        $provider = $this->storageManager->getProvider($providerName, $user);
        
        $tags = $provider->getFileTags($user, $fileId);
        
        return [
            'provider' => $provider->getProviderName(),
            'tags' => $tags,
        ];
    }

    /**
     * Get optimization recommendations for a user and context
     *
     * @param User $user The user whose cloud storage to use
     * @param array $context Context information (file size, type, etc.)
     * @param string|null $providerName Specific provider to use (optional)
     * @return array Optimization recommendations with provider information
     */
    public function getOptimizationRecommendations(
        User $user, 
        array $context = [],
        ?string $providerName = null
    ): array {
        $provider = $this->storageManager->getProvider($providerName, $user);
        
        $recommendations = $provider->getOptimizationRecommendations($user, $context);
        
        return [
            'provider' => $provider->getProviderName(),
            'recommendations' => $recommendations,
            'context' => $context,
        ];
    }

    /**
     * Get advanced feature capabilities for all providers
     *
     * @param User $user The user whose cloud storage to check
     * @return array Feature capabilities by provider
     */
    public function getAdvancedFeatureCapabilities(User $user): array
    {
        $providers = $this->storageManager->getAllProviders();
        $capabilities = [];

        foreach ($providers as $providerName => $provider) {
            try {
                $providerCapabilities = $provider->getCapabilities();
                
                $capabilities[$providerName] = [
                    'provider_name' => $provider->getProviderName(),
                    'presigned_urls' => $providerCapabilities['presigned_urls'] ?? false,
                    'storage_classes' => !empty($provider->getAvailableStorageClasses()),
                    'metadata_support' => $providerCapabilities['metadata_support'] ?? false,
                    'tagging_support' => true, // All providers support tagging through our implementation
                    'optimization_support' => true, // All providers provide optimization recommendations
                    'available_storage_classes' => $provider->getAvailableStorageClasses(),
                ];
            } catch (\Exception $e) {
                Log::warning('Failed to get capabilities for provider', [
                    'provider' => $providerName,
                    'error' => $e->getMessage(),
                ]);
                
                $capabilities[$providerName] = [
                    'provider_name' => $providerName,
                    'error' => 'Failed to retrieve capabilities',
                ];
            }
        }

        return $capabilities;
    }

    /**
     * Bulk apply optimizations to multiple files
     *
     * @param User $user The user whose cloud storage to use
     * @param array $fileIds Array of file IDs to optimize
     * @param array $optimizations Optimizations to apply
     * @param string|null $providerName Specific provider to use (optional)
     * @return array Results for each file
     */
    public function bulkOptimizeFiles(
        User $user, 
        array $fileIds, 
        array $optimizations,
        ?string $providerName = null
    ): array {
        $provider = $this->storageManager->getProvider($providerName, $user);
        $results = [];

        Log::info('Starting bulk file optimization', [
            'user_id' => $user->id,
            'provider' => $provider->getProviderName(),
            'file_count' => count($fileIds),
            'optimizations' => array_keys($optimizations),
        ]);

        foreach ($fileIds as $fileId) {
            try {
                $fileResult = [
                    'file_id' => $fileId,
                    'success' => true,
                    'applied_optimizations' => [],
                    'errors' => [],
                ];

                // Apply storage class if specified
                if (isset($optimizations['storage_class'])) {
                    try {
                        $result = $this->setStorageClass($user, $fileId, $optimizations['storage_class'], $providerName);
                        if ($result['success']) {
                            $fileResult['applied_optimizations'][] = 'storage_class';
                        } else {
                            $fileResult['errors'][] = 'Storage class not supported';
                        }
                    } catch (\Exception $e) {
                        $fileResult['errors'][] = 'Storage class: ' . $e->getMessage();
                    }
                }

                // Apply metadata if specified
                if (isset($optimizations['metadata'])) {
                    try {
                        $this->setFileMetadata($user, $fileId, $optimizations['metadata'], $providerName);
                        $fileResult['applied_optimizations'][] = 'metadata';
                    } catch (\Exception $e) {
                        $fileResult['errors'][] = 'Metadata: ' . $e->getMessage();
                    }
                }

                // Apply tags if specified
                if (isset($optimizations['tags'])) {
                    try {
                        $this->addFileTags($user, $fileId, $optimizations['tags'], $providerName);
                        $fileResult['applied_optimizations'][] = 'tags';
                    } catch (\Exception $e) {
                        $fileResult['errors'][] = 'Tags: ' . $e->getMessage();
                    }
                }

                $fileResult['success'] = empty($fileResult['errors']);
                $results[] = $fileResult;

            } catch (\Exception $e) {
                $results[] = [
                    'file_id' => $fileId,
                    'success' => false,
                    'applied_optimizations' => [],
                    'errors' => [$e->getMessage()],
                ];
            }
        }

        $successCount = count(array_filter($results, fn($r) => $r['success']));
        
        Log::info('Bulk file optimization completed', [
            'user_id' => $user->id,
            'provider' => $provider->getProviderName(),
            'total_files' => count($fileIds),
            'successful' => $successCount,
            'failed' => count($fileIds) - $successCount,
        ]);

        return [
            'provider' => $provider->getProviderName(),
            'total_files' => count($fileIds),
            'successful' => $successCount,
            'failed' => count($fileIds) - $successCount,
            'results' => $results,
        ];
    }
}