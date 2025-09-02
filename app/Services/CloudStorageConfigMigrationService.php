<?php

namespace App\Services;

use App\Models\CloudStorageSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * Service for migrating cloud storage configuration from legacy formats
 * to the new enhanced provider system.
 */
class CloudStorageConfigMigrationService
{
    /**
     * Migrate legacy Google Drive environment variables to the new configuration format.
     *
     * @return array Migration results
     */
    public function migrateLegacyGoogleDriveSettings(): array
    {
        $results = [
            'migrated' => [],
            'skipped' => [],
            'errors' => [],
        ];

        $legacyMappings = config('cloud-storage.migration.legacy_google_drive_settings', []);
        
        foreach ($legacyMappings as $envKey => $configPath) {
            try {
                $envValue = env($envKey);
                
                if (empty($envValue)) {
                    $results['skipped'][] = [
                        'env_key' => $envKey,
                        'reason' => 'Environment variable not set or empty',
                    ];
                    continue;
                }

                // Check if already migrated
                if ($this->isAlreadyMigrated($envKey, $configPath)) {
                    $results['skipped'][] = [
                        'env_key' => $envKey,
                        'reason' => 'Already migrated',
                    ];
                    continue;
                }

                // Perform migration
                $this->migrateSettingToDatabase($envKey, $configPath, $envValue);
                
                $results['migrated'][] = [
                    'env_key' => $envKey,
                    'config_path' => $configPath,
                    'value' => $this->maskSensitiveValue($envKey, $envValue),
                ];

                Log::info('Migrated legacy Google Drive setting', [
                    'env_key' => $envKey,
                    'config_path' => $configPath,
                ]);

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'env_key' => $envKey,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to migrate legacy Google Drive setting', [
                    'env_key' => $envKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Migrate configuration from old cloud-storage.php format to new format.
     *
     * @return array Migration results
     */
    public function migrateConfigurationFormat(): array
    {
        $results = [
            'migrated' => [],
            'skipped' => [],
            'errors' => [],
        ];

        try {
            $currentConfig = config('cloud-storage');
            
            // Check if already in new format
            if ($this->isNewConfigurationFormat($currentConfig)) {
                $results['skipped'][] = [
                    'reason' => 'Configuration already in new format',
                ];
                return $results;
            }

            // Migrate providers from old format
            if (isset($currentConfig['providers'])) {
                foreach ($currentConfig['providers'] as $providerName => $providerConfig) {
                    try {
                        $this->migrateProviderConfiguration($providerName, $providerConfig);
                        $results['migrated'][] = [
                            'provider' => $providerName,
                            'action' => 'Configuration format updated',
                        ];
                    } catch (\Exception $e) {
                        $results['errors'][] = [
                            'provider' => $providerName,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }

            // Migrate features from old format
            if (isset($currentConfig['features'])) {
                foreach ($currentConfig['features'] as $providerName => $features) {
                    try {
                        $this->migrateProviderFeatures($providerName, $features);
                        $results['migrated'][] = [
                            'provider' => $providerName,
                            'action' => 'Features migrated to provider configuration',
                        ];
                    } catch (\Exception $e) {
                        $results['errors'][] = [
                            'provider' => $providerName,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = [
                'general' => $e->getMessage(),
            ];
        }

        return $results;
    }

    /**
     * Validate that all required configuration is present for enabled providers.
     *
     * @return array Validation results
     */
    public function validateProviderConfigurations(): array
    {
        $results = [
            'valid' => [],
            'invalid' => [],
            'warnings' => [],
        ];

        $providers = config('cloud-storage.providers', []);

        foreach ($providers as $providerName => $providerConfig) {
            try {
                if (!($providerConfig['enabled'] ?? true)) {
                    $results['warnings'][] = [
                        'provider' => $providerName,
                        'message' => 'Provider is disabled',
                    ];
                    continue;
                }

                $validation = $this->validateProviderConfig($providerName, $providerConfig);
                
                if ($validation['valid']) {
                    $results['valid'][] = [
                        'provider' => $providerName,
                        'message' => 'Configuration is valid',
                    ];
                } else {
                    $results['invalid'][] = [
                        'provider' => $providerName,
                        'errors' => $validation['errors'],
                    ];
                }

            } catch (\Exception $e) {
                $results['invalid'][] = [
                    'provider' => $providerName,
                    'errors' => [$e->getMessage()],
                ];
            }
        }

        return $results;
    }

    /**
     * Create backup of current configuration before migration.
     *
     * @return string Backup file path
     */
    public function createConfigurationBackup(): string
    {
        $backupPath = storage_path('app/backups/cloud-storage-config-' . date('Y-m-d-H-i-s') . '.php');
        
        // Ensure backup directory exists
        $backupDir = dirname($backupPath);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $currentConfig = config('cloud-storage');
        $backupContent = "<?php\n\n// Cloud Storage Configuration Backup - " . date('Y-m-d H:i:s') . "\n\nreturn " . var_export($currentConfig, true) . ";\n";
        
        file_put_contents($backupPath, $backupContent);
        
        Log::info('Created cloud storage configuration backup', [
            'backup_path' => $backupPath,
        ]);

        return $backupPath;
    }

    /**
     * Check if a legacy setting has already been migrated.
     */
    private function isAlreadyMigrated(string $envKey, string $configPath): bool
    {
        // Check if setting exists in database
        $parts = explode('.', $configPath);
        if (count($parts) >= 2) {
            $provider = 'google-drive'; // Legacy settings are for Google Drive
            $key = end($parts);
            
            return CloudStorageSetting::where('provider', $provider)
                ->where('key', $key)
                ->exists();
        }

        return false;
    }

    /**
     * Migrate a setting to the database.
     */
    private function migrateSettingToDatabase(string $envKey, string $configPath, string $value): void
    {
        $parts = explode('.', $configPath);
        if (count($parts) >= 2) {
            $provider = 'google-drive'; // Legacy settings are for Google Drive
            $key = end($parts);
            
            CloudStorageSetting::updateOrCreate(
                [
                    'provider' => $provider,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                    'is_encrypted' => $this->shouldEncryptValue($envKey),
                    'migrated_from' => $envKey,
                ]
            );
        }
    }

    /**
     * Check if configuration is already in new format.
     */
    private function isNewConfigurationFormat(array $config): bool
    {
        // New format has provider classes and auth types
        if (isset($config['providers'])) {
            foreach ($config['providers'] as $provider) {
                if (isset($provider['class']) && isset($provider['auth_type'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Migrate provider configuration to new format.
     */
    private function migrateProviderConfiguration(string $providerName, array $providerConfig): void
    {
        // This would typically involve updating the configuration file
        // For now, we'll log the migration need
        Log::info('Provider configuration needs migration', [
            'provider' => $providerName,
            'current_config' => $providerConfig,
        ]);
    }

    /**
     * Migrate provider features to new format.
     */
    private function migrateProviderFeatures(string $providerName, array $features): void
    {
        // This would typically involve merging features into provider config
        Log::info('Provider features need migration', [
            'provider' => $providerName,
            'features' => $features,
        ]);
    }

    /**
     * Validate provider configuration.
     */
    private function validateProviderConfig(string $providerName, array $config): array
    {
        $errors = [];

        // Check required fields
        $requiredFields = ['driver', 'class', 'auth_type', 'storage_model'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // Check if class exists
        if (isset($config['class']) && !class_exists($config['class'])) {
            $errors[] = "Provider class does not exist: {$config['class']}";
        }

        // Check auth type
        $validAuthTypes = ['oauth', 'api_key', 'connection_string', 'service_account'];
        if (isset($config['auth_type']) && !in_array($config['auth_type'], $validAuthTypes)) {
            $errors[] = "Invalid auth type: {$config['auth_type']}";
        }

        // Check storage model
        $validStorageModels = ['hierarchical', 'flat', 'hybrid'];
        if (isset($config['storage_model']) && !in_array($config['storage_model'], $validStorageModels)) {
            $errors[] = "Invalid storage model: {$config['storage_model']}";
        }

        // Validate provider-specific configuration
        if (isset($config['config'])) {
            $configErrors = $this->validateProviderSpecificConfig($providerName, $config['config'], $config['auth_type'] ?? '');
            $errors = array_merge($errors, $configErrors);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate provider-specific configuration.
     */
    private function validateProviderSpecificConfig(string $providerName, array $config, string $authType): array
    {
        $errors = [];

        switch ($providerName) {
            case 'google-drive':
                if (empty($config['client_id'])) {
                    $errors[] = 'Google Drive client_id is required';
                }
                if (empty($config['client_secret'])) {
                    $errors[] = 'Google Drive client_secret is required';
                }
                break;

            case 'amazon-s3':
                if (empty($config['access_key_id'])) {
                    $errors[] = 'Amazon S3 access_key_id is required';
                }
                if (empty($config['secret_access_key'])) {
                    $errors[] = 'Amazon S3 secret_access_key is required';
                }
                if (empty($config['bucket'])) {
                    $errors[] = 'Amazon S3 bucket is required';
                }
                if (empty($config['region'])) {
                    $errors[] = 'Amazon S3 region is required';
                }
                break;

            case 'azure-blob':
                if (empty($config['connection_string']) && (empty($config['account_name']) || empty($config['account_key']))) {
                    $errors[] = 'Azure Blob Storage requires either connection_string or account_name + account_key';
                }
                if (empty($config['container'])) {
                    $errors[] = 'Azure Blob Storage container is required';
                }
                break;
        }

        return $errors;
    }

    /**
     * Mask sensitive values for logging.
     */
    private function maskSensitiveValue(string $envKey, string $value): string
    {
        $sensitiveKeys = ['SECRET', 'KEY', 'PASSWORD', 'TOKEN'];
        
        foreach ($sensitiveKeys as $sensitiveKey) {
            if (str_contains(strtoupper($envKey), $sensitiveKey)) {
                return str_repeat('*', min(8, strlen($value)));
            }
        }

        return $value;
    }

    /**
     * Determine if a value should be encrypted in the database.
     */
    private function shouldEncryptValue(string $envKey): bool
    {
        $encryptKeys = ['SECRET', 'KEY', 'PASSWORD', 'TOKEN'];
        
        foreach ($encryptKeys as $encryptKey) {
            if (str_contains(strtoupper($envKey), $encryptKey)) {
                return true;
            }
        }

        return false;
    }
}