<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Upload Recovery Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the upload recovery system that handles
    | stuck and failed file uploads, monitoring, and alerting.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Detection Thresholds
    |--------------------------------------------------------------------------
    |
    | Settings for detecting stuck uploads and determining when intervention
    | is needed.
    |
    */

    // Time threshold in minutes after which an upload is considered stuck
    'stuck_threshold_minutes' => env('UPLOAD_STUCK_THRESHOLD', 30),

    // Time threshold in minutes for uploads that are actively processing
    'processing_timeout_minutes' => env('UPLOAD_PROCESSING_TIMEOUT', 60),

    // Time threshold in hours for critical stuck upload alerts
    'critical_stuck_threshold_hours' => env('UPLOAD_CRITICAL_STUCK_THRESHOLD', 2),

    /*
    |--------------------------------------------------------------------------
    | Retry and Recovery Limits
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic retry attempts and recovery processes.
    |
    */

    // Maximum number of retry attempts before marking as failed
    'max_retry_attempts' => env('UPLOAD_MAX_RETRIES', 3),

    // Maximum number of recovery attempts before giving up
    'max_recovery_attempts' => env('UPLOAD_MAX_RECOVERY', 5),

    // Exponential backoff multiplier for retry delays (in minutes)
    'retry_backoff_multiplier' => env('UPLOAD_RETRY_BACKOFF_MULTIPLIER', 2),

    // Base delay in minutes for first retry attempt
    'retry_base_delay_minutes' => env('UPLOAD_RETRY_BASE_DELAY', 5),

    // Maximum delay in minutes between retry attempts
    'retry_max_delay_minutes' => env('UPLOAD_RETRY_MAX_DELAY', 60),

    /*
    |--------------------------------------------------------------------------
    | Batch Processing Limits
    |--------------------------------------------------------------------------
    |
    | Settings for controlling batch processing performance and resource usage.
    |
    */

    // Batch size for processing multiple uploads
    'batch_size' => env('UPLOAD_RECOVERY_BATCH_SIZE', 10),

    // Maximum number of uploads to process in a single recovery run
    'max_processing_limit' => env('UPLOAD_MAX_PROCESSING_LIMIT', 100),

    // Memory limit in MB for batch processing operations
    'memory_limit_mb' => env('UPLOAD_RECOVERY_MEMORY_LIMIT', 512),

    // Maximum execution time in seconds for recovery operations
    'max_execution_time_seconds' => env('UPLOAD_RECOVERY_MAX_EXECUTION_TIME', 300),

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Health Checks
    |--------------------------------------------------------------------------
    |
    | Configuration for system health monitoring and diagnostic checks.
    |
    */

    // Interval in minutes for automated health checks
    'health_check_interval_minutes' => env('UPLOAD_HEALTH_CHECK_INTERVAL', 15),

    // Minimum disk space in MB required for upload processing
    'min_disk_space_mb' => env('UPLOAD_MIN_DISK_SPACE', 1024),

    // Queue worker response timeout in seconds
    'queue_worker_timeout_seconds' => env('UPLOAD_QUEUE_WORKER_TIMEOUT', 30),

    // Google Drive API connectivity timeout in seconds
    'api_connectivity_timeout_seconds' => env('UPLOAD_API_CONNECTIVITY_TIMEOUT', 10),

    // Token expiration warning threshold in days
    'token_expiration_warning_days' => env('UPLOAD_TOKEN_EXPIRATION_WARNING', 7),

    /*
    |--------------------------------------------------------------------------
    | Alerting Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for automated alerts and notifications about upload issues.
    |
    */

    // Alert threshold in hours for stuck uploads
    'alert_threshold_hours' => env('UPLOAD_ALERT_THRESHOLD', 1),

    // Failure rate threshold (0.1 = 10%) for triggering alerts
    'failure_rate_threshold' => env('UPLOAD_FAILURE_RATE_THRESHOLD', 0.1),

    // Time window in hours for calculating failure rates
    'failure_rate_window_hours' => env('UPLOAD_FAILURE_RATE_WINDOW', 24),

    // Minimum number of uploads required before calculating failure rates
    'failure_rate_min_uploads' => env('UPLOAD_FAILURE_RATE_MIN_UPLOADS', 10),

    // Alert cooldown period in minutes to prevent spam
    'alert_cooldown_minutes' => env('UPLOAD_ALERT_COOLDOWN', 60),

    // Enable/disable automated alerting
    'alerts_enabled' => env('UPLOAD_ALERTS_ENABLED', true),

    // Email addresses for administrative alerts (comma-separated)
    'alert_recipients' => env('UPLOAD_ALERT_RECIPIENTS', ''),

    /*
    |--------------------------------------------------------------------------
    | Cleanup and Maintenance
    |--------------------------------------------------------------------------
    |
    | Settings for cleanup operations and maintenance tasks.
    |
    */

    // Age in days after which failed uploads can be cleaned up
    'cleanup_failed_uploads_days' => env('UPLOAD_CLEANUP_FAILED_DAYS', 30),

    // Age in days after which successful uploads metadata can be archived
    'archive_successful_uploads_days' => env('UPLOAD_ARCHIVE_SUCCESSFUL_DAYS', 90),

    // Enable/disable automatic cleanup of orphaned files
    'auto_cleanup_orphaned_files' => env('UPLOAD_AUTO_CLEANUP_ORPHANED', true),

    // Maximum age in hours for temporary files before cleanup
    'temp_file_max_age_hours' => env('UPLOAD_TEMP_FILE_MAX_AGE', 24),

    /*
    |--------------------------------------------------------------------------
    | Logging and Debugging
    |--------------------------------------------------------------------------
    |
    | Configuration for logging upload recovery operations and debugging.
    |
    */

    // Enable detailed logging for recovery operations
    'detailed_logging' => env('UPLOAD_RECOVERY_DETAILED_LOGGING', true),

    // Log level for recovery operations (debug, info, warning, error)
    'log_level' => env('UPLOAD_RECOVERY_LOG_LEVEL', 'info'),

    // Enable performance metrics logging
    'log_performance_metrics' => env('UPLOAD_LOG_PERFORMANCE_METRICS', false),

    // Maximum number of error details to store per upload
    'max_error_details_count' => env('UPLOAD_MAX_ERROR_DETAILS', 10),
];