<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Token Refresh Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the Google Drive token
    | auto-renewal system, including timing, feature flags, and environment-
    | specific settings for gradual rollout.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | These flags control the availability of token refresh features and
    | allow for gradual rollout across different environments.
    |
    */
    'features' => [
        // Enable proactive token refresh (refresh before expiration)
        'proactive_refresh' => env('TOKEN_REFRESH_PROACTIVE_ENABLED', true),
        
        // Enable live validation of connection health
        'live_validation' => env('TOKEN_REFRESH_LIVE_VALIDATION_ENABLED', true),
        
        // Enable automatic recovery from connection issues
        'automatic_recovery' => env('TOKEN_REFRESH_AUTO_RECOVERY_ENABLED', true),
        
        // Enable background maintenance jobs
        'background_maintenance' => env('TOKEN_REFRESH_BACKGROUND_MAINTENANCE_ENABLED', true),
        
        // Enable real-time health monitoring
        'health_monitoring' => env('TOKEN_REFRESH_HEALTH_MONITORING_ENABLED', true),
        
        // Enable enhanced dashboard features
        'enhanced_dashboard' => env('TOKEN_REFRESH_ENHANCED_DASHBOARD_ENABLED', true),
        
        // Enable comprehensive logging
        'enhanced_logging' => env('TOKEN_REFRESH_ENHANCED_LOGGING_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure timing for various token refresh operations.
    |
    */
    'timing' => [
        // Minutes before expiration to trigger proactive refresh
        'proactive_refresh_minutes' => env('TOKEN_REFRESH_PROACTIVE_MINUTES', 15),
        
        // Minutes before expiration to schedule background refresh
        'background_refresh_minutes' => env('TOKEN_REFRESH_BACKGROUND_MINUTES', 30),
        
        // Seconds to wait between retry attempts (exponential backoff base)
        'retry_base_delay_seconds' => env('TOKEN_REFRESH_RETRY_BASE_DELAY', 1),
        
        // Maximum retry attempts for recoverable errors
        'max_retry_attempts' => env('TOKEN_REFRESH_MAX_RETRIES', 5),
        
        // Seconds for token refresh coordination lock TTL
        'coordination_lock_ttl' => env('TOKEN_REFRESH_LOCK_TTL', 30),
        
        // Seconds to cache healthy status
        'health_cache_ttl_healthy' => env('TOKEN_REFRESH_HEALTH_CACHE_HEALTHY', 30),
        
        // Seconds to cache error status
        'health_cache_ttl_error' => env('TOKEN_REFRESH_HEALTH_CACHE_ERROR', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configure notification behavior and throttling.
    |
    */
    'notifications' => [
        // Enable email notifications for token issues
        'enabled' => env('TOKEN_REFRESH_NOTIFICATIONS_ENABLED', true),
        
        // Hours to throttle notifications (max 1 per error type per period)
        'throttle_hours' => env('TOKEN_REFRESH_NOTIFICATION_THROTTLE_HOURS', 24),
        
        // Enable escalation to admin when employee notifications fail
        'escalate_to_admin' => env('TOKEN_REFRESH_ESCALATE_TO_ADMIN', true),
        
        // Maximum notification failures before escalation
        'max_notification_failures' => env('TOKEN_REFRESH_MAX_NOTIFICATION_FAILURES', 3),
        
        // Email templates configuration
        'templates' => [
            'token_expired' => [
                'subject' => 'Google Drive Connection Expired - Action Required',
                'view' => 'emails.token-expired',
            ],
            'refresh_failed' => [
                'subject' => 'Google Drive Connection Issue - Automatic Renewal Failed',
                'view' => 'emails.token-refresh-failed',
            ],
            'connection_restored' => [
                'subject' => 'Google Drive Connection Restored',
                'view' => 'emails.connection-restored',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for token refresh operations.
    |
    */
    'rate_limiting' => [
        // Maximum refresh attempts per hour per user
        'max_attempts_per_hour' => env('TOKEN_REFRESH_MAX_ATTEMPTS_PER_HOUR', 5),
        
        // Maximum health validation requests per minute per user
        'max_health_checks_per_minute' => env('TOKEN_REFRESH_MAX_HEALTH_CHECKS_PER_MINUTE', 10),
        
        // Enable IP-based rate limiting
        'ip_based_limiting' => env('TOKEN_REFRESH_IP_RATE_LIMITING', true),
        
        // Maximum requests per IP per hour
        'max_requests_per_ip_per_hour' => env('TOKEN_REFRESH_MAX_IP_REQUESTS_PER_HOUR', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for token refresh operations.
    |
    */
    'security' => [
        // Enable token rotation on successful refresh
        'token_rotation' => env('TOKEN_REFRESH_TOKEN_ROTATION', true),
        
        // Enable audit logging for token operations
        'audit_logging' => env('TOKEN_REFRESH_AUDIT_LOGGING', true),
        
        // Log level for security events (debug, info, warning, error)
        'security_log_level' => env('TOKEN_REFRESH_SECURITY_LOG_LEVEL', 'info'),
        
        // Enable detailed error logging
        'detailed_error_logging' => env('TOKEN_REFRESH_DETAILED_ERROR_LOGGING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment-Specific Settings
    |--------------------------------------------------------------------------
    |
    | Different configurations for different environments.
    |
    */
    'environments' => [
        'local' => [
            'features' => [
                'proactive_refresh' => true,
                'live_validation' => true,
                'automatic_recovery' => true,
                'background_maintenance' => false, // Disable in local to avoid interference
                'health_monitoring' => true,
                'enhanced_dashboard' => true,
                'enhanced_logging' => true,
            ],
            'timing' => [
                'proactive_refresh_minutes' => 5, // Shorter for testing
                'max_retry_attempts' => 3, // Fewer retries for faster feedback
                'health_cache_ttl_healthy' => 10, // Shorter cache for testing
                'health_cache_ttl_error' => 5,
            ],
            'notifications' => [
                'throttle_hours' => 1, // Shorter throttle for testing
            ],
        ],
        
        'testing' => [
            'features' => [
                'proactive_refresh' => false, // Disable for predictable tests
                'live_validation' => false,
                'automatic_recovery' => false,
                'background_maintenance' => false,
                'health_monitoring' => false,
                'enhanced_dashboard' => false,
                'enhanced_logging' => false,
            ],
            'notifications' => [
                'enabled' => false, // Disable notifications in tests
            ],
        ],
        
        'staging' => [
            'features' => [
                'proactive_refresh' => true,
                'live_validation' => true,
                'automatic_recovery' => false, // Gradual rollout
                'background_maintenance' => true,
                'health_monitoring' => true,
                'enhanced_dashboard' => true,
                'enhanced_logging' => true,
            ],
            'timing' => [
                'proactive_refresh_minutes' => 10, // Slightly more aggressive
            ],
        ],
        
        'production' => [
            'features' => [
                'proactive_refresh' => true,
                'live_validation' => true,
                'automatic_recovery' => true,
                'background_maintenance' => true,
                'health_monitoring' => true,
                'enhanced_dashboard' => true,
                'enhanced_logging' => true,
            ],
            // Use default timing and notification settings
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Alerting
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring token refresh performance.
    |
    */
    'monitoring' => [
        // Enable performance metrics collection
        'collect_metrics' => env('TOKEN_REFRESH_COLLECT_METRICS', true),
        
        // Alerting thresholds
        'alerts' => [
            // Alert when refresh failure rate exceeds this percentage
            'failure_rate_threshold' => env('TOKEN_REFRESH_FAILURE_RATE_THRESHOLD', 10),
            
            // Alert when health cache miss rate exceeds this percentage
            'cache_miss_rate_threshold' => env('TOKEN_REFRESH_CACHE_MISS_THRESHOLD', 50),
            
            // Alert when average refresh time exceeds this many seconds
            'avg_refresh_time_threshold' => env('TOKEN_REFRESH_AVG_TIME_THRESHOLD', 5),
            
            // Alert when pending uploads exceed this count
            'pending_uploads_threshold' => env('TOKEN_REFRESH_PENDING_UPLOADS_THRESHOLD', 100),
        ],
        
        // Metrics retention (days)
        'metrics_retention_days' => env('TOKEN_REFRESH_METRICS_RETENTION_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Interface Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the admin interface to manage token refresh settings.
    |
    */
    'admin_interface' => [
        // Enable admin interface for token refresh management
        'enabled' => env('TOKEN_REFRESH_ADMIN_INTERFACE_ENABLED', true),
        
        // Allow runtime configuration changes (requires cache clearing)
        'allow_runtime_changes' => env('TOKEN_REFRESH_ALLOW_RUNTIME_CHANGES', false),
        
        // Settings that can be modified through admin interface
        'modifiable_settings' => [
            'timing.proactive_refresh_minutes',
            'timing.max_retry_attempts',
            'notifications.throttle_hours',
            'rate_limiting.max_attempts_per_hour',
            'features.proactive_refresh',
            'features.live_validation',
            'features.automatic_recovery',
        ],
        
        // Require confirmation for critical setting changes
        'require_confirmation' => [
            'features.proactive_refresh',
            'features.automatic_recovery',
            'timing.proactive_refresh_minutes',
        ],
    ],
];