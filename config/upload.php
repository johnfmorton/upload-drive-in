<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Disk Space Management
    |--------------------------------------------------------------------------
    |
    | Configure disk space monitoring and management for file uploads.
    | These settings help prevent disk space issues during file uploads.
    |
    */
    'disk_space' => [
        // Minimum free space required (in bytes) - default 2GB
        'minimum_free' => env('UPLOAD_MIN_FREE_SPACE', 2 * 1024 * 1024 * 1024),
        
        // Warning threshold (in bytes) - default 5GB
        'warning_threshold' => env('UPLOAD_WARNING_THRESHOLD', 5 * 1024 * 1024 * 1024),
        
        // Enable automatic emergency cleanup when disk space is critically low
        'emergency_cleanup' => env('UPLOAD_EMERGENCY_CLEANUP', true),
        
        // Maximum age for emergency cleanup (in hours)
        'emergency_cleanup_max_age' => env('UPLOAD_EMERGENCY_CLEANUP_MAX_AGE', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Limits
    |--------------------------------------------------------------------------
    |
    | Configure file upload size limits and chunking behavior.
    |
    */
    'limits' => [
        // Maximum file size in bytes (default 5GB)
        'max_file_size' => env('UPLOAD_MAX_FILE_SIZE', 5 * 1024 * 1024 * 1024),
        
        // Chunk size in bytes (default 5MB)
        'chunk_size' => env('UPLOAD_CHUNK_SIZE', 5 * 1024 * 1024),
        
        // Maximum concurrent uploads
        'max_concurrent_uploads' => env('UPLOAD_MAX_CONCURRENT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic cleanup of temporary files.
    |
    */
    'cleanup' => [
        // Default age for cleanup command (in hours)
        'default_age_hours' => env('UPLOAD_CLEANUP_DEFAULT_HOURS', 24),
        
        // Enable automatic cleanup after successful uploads
        'auto_cleanup_after_upload' => env('UPLOAD_AUTO_CLEANUP', true),
        
        // Cleanup failed uploads after this many hours
        'failed_upload_cleanup_hours' => env('UPLOAD_FAILED_CLEANUP_HOURS', 72),
    ],
];