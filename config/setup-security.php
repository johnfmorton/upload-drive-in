<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Setup Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security settings for setup-related operations
    | including rate limiting, input validation, and access controls.
    |
    */

    'rate_limits' => [
        /*
        |--------------------------------------------------------------------------
        | Status Check Rate Limits
        |--------------------------------------------------------------------------
        |
        | Rate limits for setup status checking endpoints to prevent abuse.
        | Format: [max_attempts, decay_minutes]
        |
        */
        'status_refresh' => [
            'max_attempts' => 30,
            'decay_minutes' => 1,
        ],
        
        'queue_test' => [
            'max_attempts' => 10,
            'decay_minutes' => 1,
        ],
        
        'admin_queue_test' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Validation Rules
    |--------------------------------------------------------------------------
    |
    | Validation rules for setup-related inputs to ensure data integrity
    | and prevent malicious input.
    |
    */
    'validation' => [
        'allowed_steps' => [
            'database',
            'mail',
            'google_drive',
            'migrations',
            'admin_user',
            'queue_worker',
        ],
        
        'delay_limits' => [
            'min' => 0,
            'max' => 60,
        ],
        
        'job_id_pattern' => '/^test_[a-f0-9\-]{36}$/',
        
        'max_env_value_length' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Monitoring
    |--------------------------------------------------------------------------
    |
    | Settings for security event monitoring and logging.
    |
    */
    'monitoring' => [
        'log_channel' => 'security',
        'log_failed_attempts' => true,
        'log_successful_operations' => true,
        'alert_on_suspicious_activity' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Security Checks
    |--------------------------------------------------------------------------
    |
    | Security checks performed on incoming requests.
    |
    */
    'request_security' => [
        'check_user_agent' => true,
        'check_request_frequency' => true,
        'require_ajax_headers' => true,
        'block_suspicious_patterns' => true,
        
        'suspicious_user_agents' => [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i',
            '/python/i',
        ],
        
        'max_requests_per_minute' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | CSRF Protection
    |--------------------------------------------------------------------------
    |
    | CSRF protection settings for setup endpoints.
    |
    */
    'csrf' => [
        'verify_token' => true,
        'require_same_origin' => true,
        'token_lifetime' => 7200, // 2 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Control
    |--------------------------------------------------------------------------
    |
    | Access control settings for different types of operations.
    |
    */
    'access_control' => [
        'setup_status_checks' => [
            'require_auth' => false, // Public during setup
            'allowed_roles' => ['admin', 'employee'], // When authenticated
        ],
        
        'admin_queue_tests' => [
            'require_auth' => true,
            'allowed_roles' => ['admin'],
        ],
        
        'public_queue_tests' => [
            'require_auth' => false, // Public during setup
            'rate_limit_strict' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Settings for security-related error handling and responses.
    |
    */
    'error_handling' => [
        'expose_debug_info' => false, // Set to true only in development
        'generic_error_messages' => true,
        'log_all_errors' => true,
        'include_request_id' => true,
    ],
];