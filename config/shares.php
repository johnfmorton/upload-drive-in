<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Outgoing File Shares
    |--------------------------------------------------------------------------
    |
    | Admins and employees can upload files into a "share" and send someone a
    | link to download them. Uploaded files are staged on this server until
    | they have been copied to cloud storage, so the size limits below should
    | fit the server's disk. Sizes are in bytes.
    |
    */

    // Largest single file allowed in a share (5 GB, Google Drive's limit in cloud-storage.php)
    'max_file_size' => (int) env('SHARES_MAX_FILE_SIZE', 5 * 1024 * 1024 * 1024),

    // Largest total size of one share (10 GB)
    'max_share_size' => (int) env('SHARES_MAX_SHARE_SIZE', 10 * 1024 * 1024 * 1024),

    // Refuse an upload if accepting it would leave less free disk than this (5 GB)
    'min_free_disk' => (int) env('SHARES_MIN_FREE_DISK', 5 * 1024 * 1024 * 1024),

    // Expiry choices offered to the sender, in days, and the default choice
    'expiry_days' => [7, 14, 30],
    'default_expiry_days' => (int) env('SHARES_DEFAULT_EXPIRY_DAYS', 14),

    // Shares still being uploaded after this many hours are abandoned and purged
    'stale_upload_hours' => 24,

    // Where files are staged before the cloud upload, on the private "local" disk
    'staging_directory' => 'shares',

];
