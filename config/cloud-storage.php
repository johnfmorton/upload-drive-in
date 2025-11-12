<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cloud Storage Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default cloud storage provider that will be used
    | when no specific provider is requested. This should match one of the
    | provider keys defined in the providers array below.
    |
    */
    'default' => env('CLOUD_STORAGE_DEFAULT', 'google-drive'),

    /*
    |--------------------------------------------------------------------------
    | Cloud Storage Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the cloud storage providers for your application.
    | Each provider has its own configuration including authentication type,
    | storage model, and provider-specific settings.
    |
    */
    'providers' => [
        'google-drive' => [
            'driver' => 'google-drive',
            'class' => \App\Services\GoogleDriveProvider::class,
            'error_handler' => \App\Services\GoogleDriveErrorHandler::class,
            'auth_type' => 'oauth',
            'storage_model' => 'hierarchical',
            'enabled' => env('GOOGLE_DRIVE_ENABLED', true),
            'config' => [
                'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
                'redirect_uri' => config('app.url') . '/admin/cloud-storage/google-drive/callback',
                'scopes' => ['https://www.googleapis.com/auth/drive.file', 'https://www.googleapis.com/auth/drive'],
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
                'supported_file_types' => ['*'], // All types
                'metadata_support' => true,
                'version_control' => true,
            ],
            'chunked_upload' => [
                'enabled' => env('GOOGLE_DRIVE_CHUNKED_UPLOAD_ENABLED', true),
                'threshold_bytes' => env('GOOGLE_DRIVE_CHUNKED_UPLOAD_THRESHOLD', 52428800), // 50MB
                'memory_threshold_percent' => env('GOOGLE_DRIVE_MEMORY_THRESHOLD_PERCENT', 25), // 25% of available memory
                'default_chunk_size' => env('GOOGLE_DRIVE_DEFAULT_CHUNK_SIZE', 8388608), // 8MB
                'min_chunk_size' => 262144, // 256KB
                'max_chunk_size' => 104857600, // 100MB
                'chunk_delay_ms' => env('GOOGLE_DRIVE_CHUNK_DELAY_MS', 100), // Delay between chunks
            ],
            'limits' => [
                'requests_per_second' => 10,
                'requests_per_day' => 1000000000,
                'upload_bandwidth' => '750MB',
            ],
        ],

        'amazon-s3' => [
            'driver' => 'amazon-s3',
            'class' => \App\Services\S3Provider::class,
            'error_handler' => \App\Services\S3ErrorHandler::class,
            'auth_type' => 'api_key',
            'storage_model' => 'flat',
            'enabled' => env('AWS_S3_ENABLED', false),
            'config' => [
                'access_key_id' => env('AWS_ACCESS_KEY_ID'),
                'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
                'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'bucket' => env('AWS_BUCKET'),
                'endpoint' => env('AWS_ENDPOINT'), // For S3-compatible services
                'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
                'version' => 'latest',
            ],
            'features' => [
                'folder_creation' => false, // S3 uses key prefixes
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
            'limits' => [
                'requests_per_second' => 3500,
                'requests_per_day' => null, // No daily limit
                'upload_bandwidth' => null, // No bandwidth limit
            ],
        ],



        'microsoft-teams' => [
            'driver' => 'microsoft-teams',
            'class' => \App\Services\MicrosoftTeamsProvider::class,
            'error_handler' => \App\Services\MicrosoftTeamsErrorHandler::class,
            'auth_type' => 'oauth',
            'storage_model' => 'hierarchical',
            'enabled' => env('MICROSOFT_TEAMS_ENABLED', false),
            'config' => [
                'client_id' => env('MICROSOFT_TEAMS_CLIENT_ID'),
                'client_secret' => env('MICROSOFT_TEAMS_CLIENT_SECRET'),
                'redirect_uri' => config('app.url') . '/admin/cloud-storage/microsoft-teams/callback',
                'tenant_id' => env('MICROSOFT_TEAMS_TENANT_ID'),
                'scopes' => ['https://graph.microsoft.com/Files.ReadWrite.All'],
            ],
            'features' => [
                'folder_creation' => true,
                'file_upload' => true,
                'file_delete' => true,
                'folder_delete' => true,
                'file_sharing' => true,
                'batch_operations' => false,
                'resumable_uploads' => true,
                'max_file_size' => 15728640, // 15MB via Graph API
                'supported_file_types' => ['*'],
                'metadata_support' => true,
                'version_control' => true,
            ],
            'limits' => [
                'requests_per_second' => 10,
                'requests_per_day' => 10000,
                'upload_bandwidth' => '32MB',
            ],
        ],


    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Availability Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines the availability status of each provider.
    | Providers can be marked as fully_available, coming_soon, deprecated,
    | or under maintenance to control UI display and selection logic.
    |
    */
    'provider_availability' => [
        'google-drive' => 'fully_available',
        'amazon-s3' => 'fully_available',
        'microsoft-teams' => 'coming_soon',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Detection Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines which features are required and optional
    | for cloud storage providers in this application.
    |
    */
    'feature_detection' => [
        'required_features' => [
            'file_upload',
            'file_delete',
        ],
        'optional_features' => [
            'folder_creation',
            'folder_delete',
            'file_sharing',
            'batch_operations',
            'resumable_uploads',
            'presigned_urls',
            'metadata_support',
            'version_control',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | When a provider fails or is unavailable, the system can automatically
    | fallback to alternative providers in the specified order.
    |
    */
    'fallback' => [
        'enabled' => env('CLOUD_STORAGE_FALLBACK_ENABLED', true),
        'order' => [
            'google-drive',
            'amazon-s3',
            'microsoft-teams',
        ],
        'max_retries' => 3,
        'retry_delay' => 5, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for provider health checks and monitoring.
    |
    */
    'health_check' => [
        'enabled' => env('CLOUD_STORAGE_HEALTH_CHECK_ENABLED', true),
        'interval' => env('CLOUD_STORAGE_HEALTH_CHECK_INTERVAL', 300), // 5 minutes
        'timeout' => env('CLOUD_STORAGE_HEALTH_CHECK_TIMEOUT', 30), // 30 seconds
        'failure_threshold' => 3, // Number of consecutive failures before marking as unhealthy
        'recovery_threshold' => 2, // Number of consecutive successes before marking as healthy
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for migrating from legacy Google Drive settings to the
    | new provider system.
    |
    */
    'migration' => [
        'legacy_google_drive_settings' => [
            'GOOGLE_DRIVE_ROOT_FOLDER_ID' => 'config.root_folder_id',
            'GOOGLE_DRIVE_CLIENT_ID' => 'config.client_id',
            'GOOGLE_DRIVE_CLIENT_SECRET' => 'config.client_secret',
        ],
        'auto_migrate' => env('CLOUD_STORAGE_AUTO_MIGRATE', true),
        'backup_legacy_settings' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for cloud storage operation logging and monitoring.
    |
    */
    'logging' => [
        'enabled' => env('CLOUD_STORAGE_LOGGING_ENABLED', true),
        'level' => env('CLOUD_STORAGE_LOG_LEVEL', 'info'),
        'channels' => [
            'operations' => 'cloud-storage',
            'errors' => 'cloud-storage-errors',
            'performance' => 'cloud-storage-performance',
        ],
        'log_uploads' => true,
        'log_deletions' => true,
        'log_authentication' => true,
        'log_configuration_changes' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for caching provider instances and configuration data.
    |
    */
    'cache' => [
        'enabled' => env('CLOUD_STORAGE_CACHE_ENABLED', true),
        'ttl' => env('CLOUD_STORAGE_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'cloud_storage',
        'tags' => ['cloud-storage', 'providers'],
    ],
];
