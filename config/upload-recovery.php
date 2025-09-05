<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Upload Recovery Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the upload recovery system that handles
    | automatic retry of failed uploads and connection recovery.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Retry Limits
    |--------------------------------------------------------------------------
    |
    | Maximum number of retry attempts for different types of operations.
    |
    */
    'max_retry_attempts' => env('UPLOAD_RECOVERY_MAX_RETRY_ATTEMPTS', 3),
    'max_recovery_attempts' => env('UPLOAD_RECOVERY_MAX_RECOVERY_ATTEMPTS', 5),

    /*
    |--------------------------------------------------------------------------
    | Timing Configuration
    |--------------------------------------------------------------------------
    |
    | Time thresholds and delays for recovery operations.
    |
    */
    'stuck_threshold_minutes' => env('UPLOAD_RECOVERY_STUCK_THRESHOLD_MINUTES', 30),
    'retry_batch_size' => env('UPLOAD_RECOVERY_RETRY_BATCH_SIZE', 10),
    'retry_batch_delay' => env('UPLOAD_RECOVERY_RETRY_BATCH_DELAY', 30), // seconds

    /*
    |--------------------------------------------------------------------------
    | Recovery Strategy Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for different recovery strategies.
    |
    */
    'recovery_strategies' => [
        'token_refresh' => [
            'enabled' => env('UPLOAD_RECOVERY_TOKEN_REFRESH_ENABLED', true),
            'max_attempts' => env('UPLOAD_RECOVERY_TOKEN_REFRESH_MAX_ATTEMPTS', 3),
            'delay_seconds' => env('UPLOAD_RECOVERY_TOKEN_REFRESH_DELAY', 30),
        ],
        'network_retry' => [
            'enabled' => env('UPLOAD_RECOVERY_NETWORK_RETRY_ENABLED', true),
            'max_attempts' => env('UPLOAD_RECOVERY_NETWORK_RETRY_MAX_ATTEMPTS', 5),
            'delay_seconds' => env('UPLOAD_RECOVERY_NETWORK_RETRY_DELAY', 60),
        ],
        'quota_wait' => [
            'enabled' => env('UPLOAD_RECOVERY_QUOTA_WAIT_ENABLED', true),
            'max_attempts' => env('UPLOAD_RECOVERY_QUOTA_WAIT_MAX_ATTEMPTS', 3),
            'delay_seconds' => env('UPLOAD_RECOVERY_QUOTA_WAIT_DELAY', 3600), // 1 hour
        ],
        'service_retry' => [
            'enabled' => env('UPLOAD_RECOVERY_SERVICE_RETRY_ENABLED', true),
            'max_attempts' => env('UPLOAD_RECOVERY_SERVICE_RETRY_MAX_ATTEMPTS', 3),
            'delay_seconds' => env('UPLOAD_RECOVERY_SERVICE_RETRY_DELAY', 300), // 5 minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for recovery-related notifications.
    |
    */
    'notifications' => [
        'recovery_success' => env('UPLOAD_RECOVERY_NOTIFY_SUCCESS', true),
        'recovery_failure' => env('UPLOAD_RECOVERY_NOTIFY_FAILURE', true),
        'retry_exhausted' => env('UPLOAD_RECOVERY_NOTIFY_RETRY_EXHAUSTED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue settings for recovery jobs.
    |
    */
    'queue' => [
        'recovery_queue' => env('UPLOAD_RECOVERY_QUEUE', 'recovery'),
        'retry_queue' => env('UPLOAD_RECOVERY_RETRY_QUEUE', 'recovery'),
        'high_priority_queue' => env('UPLOAD_RECOVERY_HIGH_PRIORITY_QUEUE', 'high'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for monitoring recovery operations.
    |
    */
    'monitoring' => [
        'log_recovery_attempts' => env('UPLOAD_RECOVERY_LOG_ATTEMPTS', true),
        'log_retry_decisions' => env('UPLOAD_RECOVERY_LOG_RETRY_DECISIONS', true),
        'track_success_rates' => env('UPLOAD_RECOVERY_TRACK_SUCCESS_RATES', true),
    ],
];