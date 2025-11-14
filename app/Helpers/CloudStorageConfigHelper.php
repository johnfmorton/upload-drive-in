<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Helper class for cloud storage configuration management and validation.
 */
class CloudStorageConfigHelper
{
    /**
     * Get the configuration schema for a specific provider.
     *
     * @param string $providerName
     * @return array
     */
    public static function getProviderSchema(string $providerName): array
    {
        $schemas = [
            'google-drive' => [
                'required' => [
                    'config.client_id' => 'string',
                    'config.client_secret' => 'string',
                ],
                'optional' => [
                    'config.redirect_uri' => 'string',
                    'config.scopes' => 'array',
                    'config.access_type' => 'string',
                    'config.approval_prompt' => 'string',
                ],
                'auth_type' => 'oauth',
                'storage_model' => 'hierarchical',
            ],
            'amazon-s3' => [
                'required' => [
                    'config.access_key_id' => 'string',
                    'config.secret_access_key' => 'string',
                    'config.region' => 'string',
                    'config.bucket' => 'string',
                ],
                'optional' => [
                    'config.endpoint' => 'string',
                    'config.use_path_style_endpoint' => 'boolean',
                    'config.version' => 'string',
                ],
                'auth_type' => 'api_key',
                'storage_model' => 'flat',
            ],
            'azure-blob' => [
                'required' => [
                    'config.container' => 'string',
                ],
                'required_one_of' => [
                    ['config.connection_string'],
                    ['config.account_name', 'config.account_key'],
                ],
                'optional' => [
                    'config.endpoint_suffix' => 'string',
                ],
                'auth_type' => 'connection_string',
                'storage_model' => 'flat',
            ],
            'microsoft-teams' => [
                'required' => [
                    'config.client_id' => 'string',
                    'config.client_secret' => 'string',
                    'config.tenant_id' => 'string',
                ],
                'optional' => [
                    'config.redirect_uri' => 'string',
                    'config.scopes' => 'array',
                ],
                'auth_type' => 'oauth',
                'storage_model' => 'hierarchical',
            ],
            'dropbox' => [
                'required' => [
                    'config.app_key' => 'string',
                    'config.app_secret' => 'string',
                ],
                'optional' => [
                    'config.redirect_uri' => 'string',
                    'config.access_type' => 'string',
                ],
                'auth_type' => 'oauth',
                'storage_model' => 'hierarchical',
            ],
        ];

        return $schemas[$providerName] ?? [];
    }

    /**
     * Validate provider configuration against its schema.
     *
     * @param string $providerName
     * @param array $config
     * @return array List of validation errors
     */
    public static function validateProviderConfig(string $providerName, array $config): array
    {
        $schema = self::getProviderSchema($providerName);
        $errors = [];

        if (empty($schema)) {
            return ["Unknown provider: {$providerName}"];
        }

        // Validate required fields
        foreach ($schema['required'] ?? [] as $field => $type) {
            $value = data_get($config, $field);
            
            if (empty($value)) {
                $errors[] = "Required field '{$field}' is missing or empty";
                continue;
            }

            if (!self::validateFieldType($value, $type)) {
                $errors[] = "Field '{$field}' must be of type '{$type}'";
            }
        }

        // Validate required_one_of fields
        if (isset($schema['required_one_of'])) {
            $hasValidGroup = false;
            
            foreach ($schema['required_one_of'] as $group) {
                $groupValid = true;
                foreach ($group as $field) {
                    if (empty(data_get($config, $field))) {
                        $groupValid = false;
                        break;
                    }
                }
                
                if ($groupValid) {
                    $hasValidGroup = true;
                    break;
                }
            }
            
            if (!$hasValidGroup) {
                $groupStrings = array_map(function ($group) {
                    return '[' . implode(', ', $group) . ']';
                }, $schema['required_one_of']);
                $errors[] = "At least one of these field groups is required: " . implode(' OR ', $groupStrings);
            }
        }

        // Validate optional fields (if present)
        foreach ($schema['optional'] ?? [] as $field => $type) {
            $value = data_get($config, $field);
            
            if (!empty($value) && !self::validateFieldType($value, $type)) {
                $errors[] = "Optional field '{$field}' must be of type '{$type}' if provided";
            }
        }

        return $errors;
    }

    /**
     * Validate field type.
     *
     * @param mixed $value
     * @param string $expectedType
     * @return bool
     */
    private static function validateFieldType($value, string $expectedType): bool
    {
        return match ($expectedType) {
            'string' => is_string($value),
            'array' => is_array($value),
            'boolean' => is_bool($value),
            'integer' => is_int($value),
            'float' => is_float($value),
            'numeric' => is_numeric($value),
            default => true, // Unknown type, assume valid
        };
    }

    /**
     * Get default configuration for a provider.
     *
     * @param string $providerName
     * @return array
     */
    public static function getDefaultProviderConfig(string $providerName): array
    {
        $defaults = [
            'google-drive' => [
                'driver' => 'google-drive',
                'class' => \App\Services\GoogleDriveProvider::class,
                'error_handler' => \App\Services\GoogleDriveErrorHandler::class,
                'auth_type' => 'oauth',
                'storage_model' => 'hierarchical',
                'enabled' => true,
                'config' => [
                    'scopes' => [
                        'https://www.googleapis.com/auth/drive.file',
                        'https://www.googleapis.com/auth/drive'
                    ],
                    'access_type' => 'offline',
                    'approval_prompt' => 'force',
                ],
                'features' => [
                    'folder_creation' => true,
                    'file_upload' => true,
                    'file_delete' => true,
                    'folder_delete' => true,
                    'file_sharing' => true,
                    'batch_operations' => true,
                    'resumable_uploads' => true,
                    'max_file_size' => 5368709120, // 5GB
                    'supported_file_types' => ['*'],
                    'metadata_support' => true,
                    'version_control' => true,
                ],
            ],
            'amazon-s3' => [
                'driver' => 'amazon-s3',
                'class' => \App\Services\S3Provider::class,
                'error_handler' => \App\Services\S3ErrorHandler::class,
                'auth_type' => 'api_key',
                'storage_model' => 'flat',
                'enabled' => false,
                'config' => [
                    'version' => 'latest',
                    'use_path_style_endpoint' => false,
                ],
                'features' => [
                    'folder_creation' => false,
                    'file_upload' => true,
                    'file_delete' => true,
                    'folder_delete' => false,
                    'file_sharing' => true,
                    'batch_operations' => true,
                    'resumable_uploads' => true,
                    'max_file_size' => 5497558138880, // 5TB
                    'supported_file_types' => ['*'],
                    'metadata_support' => true,
                    'version_control' => true,
                    'presigned_urls' => true,
                    'storage_classes' => ['STANDARD', 'REDUCED_REDUNDANCY', 'STANDARD_IA', 'ONEZONE_IA', 'INTELLIGENT_TIERING', 'GLACIER', 'DEEP_ARCHIVE'],
                    'server_side_encryption' => true,
                    'cross_region_replication' => true,
                ],
            ],
            'azure-blob' => [
                'driver' => 'azure-blob',
                'class' => \App\Services\AzureBlobProvider::class,
                'error_handler' => \App\Services\AzureBlobErrorHandler::class,
                'auth_type' => 'connection_string',
                'storage_model' => 'flat',
                'enabled' => false,
                'config' => [
                    'container' => 'uploads',
                    'endpoint_suffix' => 'core.windows.net',
                ],
                'features' => [
                    'folder_creation' => false,
                    'file_upload' => true,
                    'file_delete' => true,
                    'folder_delete' => false,
                    'file_sharing' => true,
                    'batch_operations' => true,
                    'resumable_uploads' => true,
                    'max_file_size' => 4398046511104, // 4TB
                    'supported_file_types' => ['*'],
                    'metadata_support' => true,
                    'version_control' => true,
                    'access_tiers' => ['Hot', 'Cool', 'Archive'],
                    'server_side_encryption' => true,
                    'immutable_storage' => true,
                ],
            ],
        ];

        return $defaults[$providerName] ?? [];
    }

    /**
     * Generate environment variable template for a provider.
     *
     * @param string $providerName
     * @return array
     */
    public static function generateEnvTemplate(string $providerName): array
    {
        $templates = [
            'google-drive' => [
                'GOOGLE_DRIVE_ENABLED=true',
                'GOOGLE_DRIVE_CLIENT_ID=your_client_id_here',
                'GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret_here',
                '',
                '# Optional Google Drive settings',
                '# GOOGLE_DRIVE_ROOT_FOLDER_ID=root',
            ],
            'amazon-s3' => [
                'AWS_ACCESS_KEY_ID=your_access_key_here',
                'AWS_SECRET_ACCESS_KEY=your_secret_key_here',
                'AWS_DEFAULT_REGION=us-east-1',
                'AWS_BUCKET=your_bucket_name',
                '',
                '# Optional S3 settings',
                '# AWS_ENDPOINT=https://s3.amazonaws.com',
                '# AWS_USE_PATH_STYLE_ENDPOINT=false',
            ],
            'azure-blob' => [
                'AZURE_STORAGE_ENABLED=false',
                'AZURE_STORAGE_CONNECTION_STRING=your_connection_string_here',
                'AZURE_STORAGE_CONTAINER=uploads',
                '',
                '# Alternative Azure authentication',
                '# AZURE_STORAGE_ACCOUNT_NAME=your_account_name',
                '# AZURE_STORAGE_ACCOUNT_KEY=your_account_key',
                '# AZURE_STORAGE_ENDPOINT_SUFFIX=core.windows.net',
            ],
            'microsoft-teams' => [
                'MICROSOFT_TEAMS_ENABLED=false',
                'MICROSOFT_TEAMS_CLIENT_ID=your_client_id_here',
                'MICROSOFT_TEAMS_CLIENT_SECRET=your_client_secret_here',
                'MICROSOFT_TEAMS_TENANT_ID=your_tenant_id_here',
            ],
            'dropbox' => [
                'DROPBOX_ENABLED=false',
                'DROPBOX_CLIENT_ID=your_app_key_here',
                'DROPBOX_CLIENT_SECRET=your_app_secret_here',
            ],
        ];

        return $templates[$providerName] ?? [];
    }

    /**
     * Check if a provider is properly configured.
     *
     * @param string $providerName
     * @return bool
     */
    public static function isProviderConfigured(string $providerName): bool
    {
        $config = Config::get("cloud-storage.providers.{$providerName}", []);
        
        if (empty($config)) {
            return false;
        }

        $errors = self::validateProviderConfig($providerName, $config);
        return empty($errors);
    }

    /**
     * Get all configured providers.
     *
     * @param bool $enabledOnly
     * @return array
     */
    public static function getConfiguredProviders(bool $enabledOnly = false): array
    {
        $providers = Config::get('cloud-storage.providers', []);
        $configured = [];

        foreach ($providers as $name => $config) {
            if ($enabledOnly && !($config['enabled'] ?? true)) {
                continue;
            }

            if (self::isProviderConfigured($name)) {
                $configured[] = $name;
            }
        }

        return $configured;
    }

    /**
     * Get provider configuration summary for debugging.
     *
     * @param string $providerName
     * @return array
     */
    public static function getProviderConfigSummary(string $providerName): array
    {
        $config = Config::get("cloud-storage.providers.{$providerName}", []);
        
        if (empty($config)) {
            return [
                'exists' => false,
                'enabled' => false,
                'configured' => false,
                'errors' => ["Provider '{$providerName}' not found in configuration"],
            ];
        }

        $errors = self::validateProviderConfig($providerName, $config);
        
        return [
            'exists' => true,
            'enabled' => $config['enabled'] ?? true,
            'configured' => empty($errors),
            'auth_type' => $config['auth_type'] ?? 'unknown',
            'storage_model' => $config['storage_model'] ?? 'unknown',
            'class' => $config['class'] ?? 'unknown',
            'features_count' => count($config['features'] ?? []),
            'errors' => $errors,
        ];
    }

    /**
     * Log configuration validation results.
     *
     * @param string $providerName
     * @param array $errors
     * @return void
     */
    public static function logConfigurationValidation(string $providerName, array $errors): void
    {
        if (empty($errors)) {
            Log::info("Cloud storage provider configuration valid", [
                'provider' => $providerName,
            ]);
        } else {
            Log::warning("Cloud storage provider configuration invalid", [
                'provider' => $providerName,
                'errors' => $errors,
            ]);
        }
    }

    /**
     * Get the S3 folder path from environment or database.
     * Environment variable takes precedence over database value.
     *
     * @return string The folder path (empty string if not configured)
     */
    public static function getS3FolderPath(): string
    {
        // Check environment variable first
        $envFolderPath = env('AWS_FOLDER_PATH');
        if (!empty($envFolderPath)) {
            return trim($envFolderPath, "/ \t\n\r\0\x0B");
        }

        // Fall back to database
        $setting = \App\Models\CloudStorageSetting::where('provider', 'amazon-s3')
            ->where('key', 'folder_path')
            ->whereNull('user_id') // System-level setting
            ->first();

        return $setting ? trim($setting->value, "/ \t\n\r\0\x0B") : '';
    }

    /**
     * Generate an example S3 key for display purposes.
     *
     * @param string $folderPath The folder path to use
     * @return string Example S3 key
     */
    public static function generateExampleS3Key(string $folderPath): string
    {
        $folderPath = trim($folderPath, "/ \t\n\r\0\x0B");
        $exampleEmail = 'client@example.com';
        $exampleFilename = 'document_2024-01-15_abc123.pdf';
        
        if (!empty($folderPath)) {
            return $folderPath . '/' . $exampleEmail . '/' . $exampleFilename;
        }
        
        return $exampleEmail . '/' . $exampleFilename;
    }
}