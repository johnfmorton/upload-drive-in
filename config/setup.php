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
    | Bootstrap Checks
    |--------------------------------------------------------------------------
    |
    | Whether to perform setup checks during application bootstrap.
    | Disabling this can improve performance but may miss setup requirements.
    |
    */
    'bootstrap_checks' => env('SETUP_BOOTSTRAP_CHECKS', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Setup State
    |--------------------------------------------------------------------------
    |
    | Whether to cache the setup state to improve performance.
    | The cache will be cleared when setup state changes.
    |
    */
    'cache_state' => env('SETUP_CACHE_STATE', true),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | How long to cache the setup state in seconds.
    | Set to 0 to cache indefinitely until manually cleared.
    |
    */
    'cache_ttl' => env('SETUP_CACHE_TTL', 300), // 5 minutes

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
        'vite_manifest_required' => env('SETUP_ASSET_MANIFEST_REQUIRED', true),
        'node_environment_check' => env('SETUP_NODE_ENVIRONMENT_CHECK', true),
        'build_instructions_enabled' => env('SETUP_BUILD_INSTRUCTIONS_ENABLED', true),
    ],
];