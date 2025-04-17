<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cloud Storage Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default cloud storage provider that will be used
    | for file uploads when no specific provider is requested. You may change
    | this value as needed, but google-drive is the default.
    |
    */
    'default' => env('CLOUD_STORAGE_PROVIDER', 'google-drive'),

    /*
    |--------------------------------------------------------------------------
    | Cloud Storage Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the cloud storage providers used by your
    | application. Each provider has its own configuration options which are
    | defined in the services.php configuration file.
    |
    */
    'providers' => [
        'google-drive' => [
            'driver' => 'google-drive',
            // Configuration is in config/services.php
        ],

        'microsoft-teams' => [
            'driver' => 'microsoft-teams',
            'client_id' => env('MS_TEAMS_CLIENT_ID'),
            'client_secret' => env('MS_TEAMS_CLIENT_SECRET'),
            'redirect_uri' => env('MS_TEAMS_REDIRECT_URI'),
            'root_folder_id' => env('MS_TEAMS_ROOT_FOLDER_ID'),
        ],

        'dropbox' => [
            'driver' => 'dropbox',
            'app_key' => env('DROPBOX_APP_KEY'),
            'app_secret' => env('DROPBOX_APP_SECRET'),
            'redirect_uri' => env('DROPBOX_REDIRECT_URI'),
            'root_folder' => env('DROPBOX_ROOT_FOLDER', '/UploadDriveIn'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Features
    |--------------------------------------------------------------------------
    |
    | This section defines which features are supported by each provider.
    | This allows the application to gracefully handle provider-specific
    | limitations.
    |
    */
    'features' => [
        'google-drive' => [
            'folder_creation' => true,
            'file_upload' => true,
            'file_delete' => true,
            'folder_delete' => true,
            'max_file_size' => 5368709120, // 5GB in bytes
        ],
        'microsoft-teams' => [
            'folder_creation' => true,
            'file_upload' => true,
            'file_delete' => true,
            'folder_delete' => true,
            'max_file_size' => 15728640, // 15MB in bytes (Teams has lower limits)
        ],
        'dropbox' => [
            'folder_creation' => true,
            'file_upload' => true,
            'file_delete' => true,
            'folder_delete' => true,
            'max_file_size' => 2147483648, // 2GB in bytes (Dropbox Basic limit)
        ],
    ],
];
