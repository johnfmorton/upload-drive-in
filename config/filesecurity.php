<?php

return [
    'clamav' => [
        'enabled' => env('CLAMAV_ENABLED', false),
        'socket' => env('CLAMAV_SOCKET', '/var/run/clamav/clamd.ctl'),
        'host' => env('CLAMAV_HOST', '127.0.0.1'),
        'port' => (int) env('CLAMAV_PORT', 3310),
        'connection_type' => env('CLAMAV_CONNECTION', 'socket'), // 'socket' or 'tcp'
        'timeout' => (int) env('CLAMAV_TIMEOUT', 30),
        'max_file_size' => (int) env('CLAMAV_MAX_FILE_SIZE', 25 * 1024 * 1024), // 25MB
        'fail_closed' => env('CLAMAV_FAIL_CLOSED', false),
    ],
];
