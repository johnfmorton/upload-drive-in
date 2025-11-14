<?php

namespace App\Services;

use App\Models\CloudStorageSetting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Service for managing cloud storage settings
 * 
 * Handles CRUD operations for cloud storage provider configurations,
 * including encryption of sensitive credentials and validation.
 */
class CloudStorageSettingsService
{
    public function __construct(
        private readonly S3Provider $s3Provider
    ) {}

    /**
     * Store S3 configuration with encrypted credentials
     *
     * @param array $config Configuration array with S3 credentials
     * @param User|null $user User to associate settings with (null for system-level)
     * @return array Array with success status and any messages
     * @throws ValidationException
     */
    public function storeS3Configuration(array $config, ?User $user = null): array
    {
        Log::info('CloudStorageSettingsService: Storing S3 configuration', [
            'user_id' => $user?->id,
            'has_access_key' => !empty($config['access_key_id']),
            'has_secret_key' => !empty($config['secret_access_key']),
            'region' => $config['region'] ?? null,
            'bucket' => $config['bucket'] ?? null,
            'has_endpoint' => !empty($config['endpoint']),
        ]);

        // Validate configuration using S3Provider
        $validationErrors = $this->validateS3Configuration($config);
        
        if (!empty($validationErrors)) {
            Log::warning('CloudStorageSettingsService: S3 configuration validation failed', [
                'errors' => $validationErrors,
            ]);
            
            throw ValidationException::withMessages([
                'configuration' => $validationErrors,
            ]);
        }

        try {
            $userId = $user?->id;
            $provider = 'amazon-s3';

            // Define which keys should be encrypted
            $encryptedKeys = ['secret_access_key'];

            // Store each configuration value
            $settings = [
                'access_key_id' => $config['access_key_id'],
                'secret_access_key' => $config['secret_access_key'],
                'region' => $config['region'],
                'bucket' => $config['bucket'],
            ];

            // Add optional endpoint for S3-compatible services
            if (!empty($config['endpoint'])) {
                $settings['endpoint'] = $config['endpoint'];
                $settings['use_path_style_endpoint'] = 'true';
            }

            // Add optional storage class
            if (!empty($config['storage_class'])) {
                $settings['storage_class'] = $config['storage_class'];
            }

            // Add optional folder path
            if (isset($config['folder_path'])) {
                $settings['folder_path'] = $config['folder_path'];
            }

            // Store each setting
            foreach ($settings as $key => $value) {
                $shouldEncrypt = in_array($key, $encryptedKeys);
                
                CloudStorageSetting::setValue(
                    provider: $provider,
                    key: $key,
                    value: $value,
                    encrypt: $shouldEncrypt,
                    userId: $userId
                );
            }

            Log::info('CloudStorageSettingsService: S3 configuration stored successfully', [
                'user_id' => $userId,
                'provider' => $provider,
                'keys_stored' => array_keys($settings),
            ]);

            return [
                'success' => true,
                'message' => __('messages.s3_configuration_saved'),
            ];

        } catch (\Exception $e) {
            Log::error('CloudStorageSettingsService: Failed to store S3 configuration', [
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Retrieve and decrypt S3 configuration
     *
     * @param User|null $user User to retrieve settings for (null for system-level)
     * @return array Configuration array with decrypted values
     */
    public function getS3Configuration(?User $user = null): array
    {
        $userId = $user?->id;
        $provider = 'amazon-s3';

        Log::debug('CloudStorageSettingsService: Retrieving S3 configuration', [
            'user_id' => $userId,
            'provider' => $provider,
        ]);

        try {
            // Get all settings for the provider from database
            $settings = CloudStorageSetting::getProviderSettings($provider, $userId);

            // Fallback to environment variables if database settings are empty
            if (empty($settings['access_key_id']) && !empty(env('AWS_ACCESS_KEY_ID'))) {
                $settings['access_key_id'] = env('AWS_ACCESS_KEY_ID');
                Log::debug('CloudStorageSettingsService: Using AWS_ACCESS_KEY_ID from environment');
            }
            
            if (empty($settings['secret_access_key']) && !empty(env('AWS_SECRET_ACCESS_KEY'))) {
                $settings['secret_access_key'] = env('AWS_SECRET_ACCESS_KEY');
                Log::debug('CloudStorageSettingsService: Using AWS_SECRET_ACCESS_KEY from environment');
            }
            
            if (empty($settings['region']) && !empty(env('AWS_DEFAULT_REGION'))) {
                $settings['region'] = env('AWS_DEFAULT_REGION');
                Log::debug('CloudStorageSettingsService: Using AWS_DEFAULT_REGION from environment');
            }
            
            if (empty($settings['bucket']) && !empty(env('AWS_BUCKET'))) {
                $settings['bucket'] = env('AWS_BUCKET');
                Log::debug('CloudStorageSettingsService: Using AWS_BUCKET from environment');
            }
            
            if (empty($settings['folder_path']) && !empty(env('AWS_FOLDER_PATH'))) {
                $settings['folder_path'] = env('AWS_FOLDER_PATH');
                Log::debug('CloudStorageSettingsService: Using AWS_FOLDER_PATH from environment');
            }

            // Convert use_path_style_endpoint to boolean if present
            if (isset($settings['use_path_style_endpoint'])) {
                $settings['use_path_style_endpoint'] = $settings['use_path_style_endpoint'] === 'true';
            }

            Log::debug('CloudStorageSettingsService: S3 configuration retrieved', [
                'user_id' => $userId,
                'has_access_key' => !empty($settings['access_key_id']),
                'has_secret_key' => !empty($settings['secret_access_key']),
                'region' => $settings['region'] ?? null,
                'bucket' => $settings['bucket'] ?? null,
                'has_endpoint' => !empty($settings['endpoint']),
            ]);

            return $settings;

        } catch (\Exception $e) {
            Log::error('CloudStorageSettingsService: Failed to retrieve S3 configuration', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Delete S3 configuration and remove credentials
     *
     * @param User|null $user User to delete settings for (null for system-level)
     * @return array Array with success status and message
     */
    public function deleteS3Configuration(?User $user = null): array
    {
        $userId = $user?->id;
        $provider = 'amazon-s3';

        Log::info('CloudStorageSettingsService: Deleting S3 configuration', [
            'user_id' => $userId,
            'provider' => $provider,
        ]);

        try {
            // Delete all settings for this provider and user
            $deleted = CloudStorageSetting::where('provider', $provider)
                ->where('user_id', $userId)
                ->delete();

            Log::info('CloudStorageSettingsService: S3 configuration deleted', [
                'user_id' => $userId,
                'provider' => $provider,
                'settings_deleted' => $deleted,
            ]);

            return [
                'success' => true,
                'message' => __('messages.s3_configuration_deleted'),
                'settings_deleted' => $deleted,
            ];

        } catch (\Exception $e) {
            Log::error('CloudStorageSettingsService: Failed to delete S3 configuration', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('messages.s3_configuration_delete_failed', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Validate S3 configuration using S3Provider validation
     *
     * @param array $config Configuration array to validate
     * @return array Array of validation errors (empty if valid)
     */
    public function validateS3Configuration(array $config): array
    {
        Log::debug('CloudStorageSettingsService: Validating S3 configuration', [
            'has_access_key' => !empty($config['access_key_id']),
            'has_secret_key' => !empty($config['secret_access_key']),
            'region' => $config['region'] ?? null,
            'bucket' => $config['bucket'] ?? null,
            'has_endpoint' => !empty($config['endpoint']),
        ]);

        try {
            // Use S3Provider's validation method
            $errors = $this->s3Provider->validateConfiguration($config);

            if (!empty($errors)) {
                Log::warning('CloudStorageSettingsService: S3 configuration validation failed', [
                    'errors' => $errors,
                ]);
            } else {
                Log::debug('CloudStorageSettingsService: S3 configuration validation passed');
            }

            return $errors;

        } catch (\Exception $e) {
            Log::error('CloudStorageSettingsService: S3 configuration validation error', [
                'error' => $e->getMessage(),
            ]);

            return [__('messages.s3_configuration_validation_error', ['error' => $e->getMessage()])];
        }
    }

    /**
     * Check if S3 is configured for a user
     *
     * @param User|null $user User to check (null for system-level)
     * @return bool True if S3 is configured
     */
    public function isS3Configured(?User $user = null): bool
    {
        $config = $this->getS3Configuration($user);
        
        // Check if all required keys are present
        $requiredKeys = ['access_key_id', 'secret_access_key', 'region', 'bucket'];
        
        foreach ($requiredKeys as $key) {
            if (empty($config[$key])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Update a specific S3 configuration value
     *
     * @param string $key Configuration key to update
     * @param string $value New value
     * @param User|null $user User to update settings for (null for system-level)
     * @return array Array with success status and message
     */
    public function updateS3ConfigurationValue(string $key, string $value, ?User $user = null): array
    {
        $userId = $user?->id;
        $provider = 'amazon-s3';

        Log::info('CloudStorageSettingsService: Updating S3 configuration value', [
            'user_id' => $userId,
            'key' => $key,
        ]);

        try {
            // Define which keys should be encrypted
            $encryptedKeys = ['secret_access_key'];
            $shouldEncrypt = in_array($key, $encryptedKeys);

            CloudStorageSetting::setValue(
                provider: $provider,
                key: $key,
                value: $value,
                encrypt: $shouldEncrypt,
                userId: $userId
            );

            Log::info('CloudStorageSettingsService: S3 configuration value updated', [
                'user_id' => $userId,
                'key' => $key,
            ]);

            return [
                'success' => true,
                'message' => __('messages.s3_configuration_value_updated', ['key' => $key]),
            ];

        } catch (\Exception $e) {
            Log::error('CloudStorageSettingsService: Failed to update S3 configuration value', [
                'user_id' => $userId,
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('messages.s3_configuration_update_failed', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Get S3 configuration status
     *
     * @param User|null $user User to check (null for system-level)
     * @return array Status information
     */
    public function getS3ConfigurationStatus(?User $user = null): array
    {
        $config = $this->getS3Configuration($user);
        $isConfigured = $this->isS3Configured($user);

        return [
            'is_configured' => $isConfigured,
            'has_access_key' => !empty($config['access_key_id']),
            'has_secret_key' => !empty($config['secret_access_key']),
            'has_region' => !empty($config['region']),
            'has_bucket' => !empty($config['bucket']),
            'has_custom_endpoint' => !empty($config['endpoint']),
            'region' => $config['region'] ?? null,
            'bucket' => $config['bucket'] ?? null,
            'endpoint' => $config['endpoint'] ?? null,
            'storage_class' => $config['storage_class'] ?? null,
        ];
    }
}
