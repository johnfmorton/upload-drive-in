<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Setup Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the application setup wizard.
    | These settings control how the setup process behaves and what checks
    | are performed during application bootstrap.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Setup State File
    |--------------------------------------------------------------------------
    |
    | The path where setup state is stored. This file tracks the progress
    | of the setup wizard and determines if setup is complete.
    |
    */
    'state_file' => 'setup/setup-state.json',

    /*
    |--------------------------------------------------------------------------
    | Setup State Backup Directory
    |--------------------------------------------------------------------------
    |
    | The directory where setup state backups are stored. These backups
    | are used for recovery in case of state corruption or interruption.
    |
    */
    'backup_directory' => 'setup/backups',

    /*
    |--------------------------------------------------------------------------
    | Setup Steps
    |--------------------------------------------------------------------------
    |
    | The ordered list of setup steps that must be completed.
    | These correspond to the routes and methods in the SetupController.
    |
    */
    'steps' => [
        'assets',
        'welcome',
        'database',
        'admin',
        'storage',
        'complete',
    ],

    /*
    |--------------------------------------------------------------------------
    | Required Checks
    |--------------------------------------------------------------------------
    |
    | Configuration for the various checks performed to determine if
    | setup is required. These can be enabled/disabled as needed.
    |
    */
    'checks' => [
        'asset_validation' => true,
        'database_connectivity' => true,
        'admin_user_exists' => true,
        'cloud_storage_configured' => true,
        'migrations_run' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Exempt Routes
    |--------------------------------------------------------------------------
    |
    | Routes that should be exempt from setup requirements.
    | These routes will be accessible even when setup is required.
    |
    */
    'exempt_routes' => [
        'health',
        'ping',
        'status',
        'up',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exempt Paths
    |--------------------------------------------------------------------------
    |
    | Path patterns that should be exempt from setup requirements.
    | These support wildcards and regex patterns.
    |
    */
    'exempt_paths' => [
        'build/*',
        'storage/*',
        'images/*',
        'css/*',
        'js/*',
        'assets/*',
        'fonts/*',
        'favicon.ico',
        'robots.txt',
        'sitemap.xml',
        'manifest.json',
        'site.webmanifest',
        '*.css',
        '*.js',
        '*.png',
        '*.jpg',
        '*.jpeg',
        '*.gif',
        '*.svg',
        '*.ico',
        '*.woff',
        '*.woff2',
        '*.ttf',
        '*.eot',
        '*.map',
        '*.json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Setup Route Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix used for all setup routes. This should match the
    | prefix defined in the setup routes file.
    |
    */
    'route_prefix' => 'setup',

    /*
    |--------------------------------------------------------------------------
    | Redirect Route
    |--------------------------------------------------------------------------
    |
    | The route to redirect to when setup is required.
    | This should be the entry point of the setup wizard.
    |
    */
    'redirect_route' => 'setup.welcome',

    /*
    |--------------------------------------------------------------------------
    | Setup Enabled
    |--------------------------------------------------------------------------
    |
    | Whether the setup process is enabled. When disabled, the application
    | will skip setup checks and consider setup complete. This should be
    | set to false after initial setup is complete.
    |
    */
    'enabled' => env('APP_SETUP_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Cache Setup State
    |--------------------------------------------------------------------------
    |
    | Whether to cache the setup state to improve performance.
    | The cache will be cleared when setup state changes.
    |
    */
    'cache_state' => true,

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | How long to cache the setup state in seconds.
    | Set to 0 to cache indefinitely until manually cleared.
    |
    */
    'cache_ttl' => 300, // 5 minutes

    /*
    |--------------------------------------------------------------------------
    | Asset Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for asset validation during setup process.
    | These settings control how the setup wizard validates frontend assets.
    |
    */
    'asset_paths' => [
        'vite_manifest' => 'public/build/manifest.json',
        'build_directory' => 'public/build',
        'package_json' => 'package.json',
    ],

    'asset_checks' => [
        'vite_manifest_required' => true,
        'node_environment_check' => false,
        'build_instructions_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Test Security
    |--------------------------------------------------------------------------
    |
    | Security configuration for queue worker testing functionality.
    | These settings control rate limiting and cooldown periods.
    |
    */
    'queue_worker_test' => [
        'rate_limit' => [
            'max_attempts' => env('SETUP_QUEUE_TEST_MAX_ATTEMPTS', 5),
            'decay_minutes' => env('SETUP_QUEUE_TEST_DECAY_MINUTES', 15),
        ],
        'cooldown' => [
            'seconds' => env('SETUP_QUEUE_TEST_COOLDOWN_SECONDS', 30),
        ],
        'cache_keys' => [
            'rate_limit' => 'setup:queue_test:rate_limit:',
            'cooldown' => 'setup:queue_test:cooldown:',
        ],
    ],
];