<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 2FA Enforcement
    |--------------------------------------------------------------------------
    |
    | This option controls whether 2FA is mandatory for admin users.
    |
    */
    'enforce_admin_2fa' => env('ENFORCE_ADMIN_2FA', true),

    /*
    |--------------------------------------------------------------------------
    | Recovery Codes Count
    |--------------------------------------------------------------------------
    |
    | The number of recovery codes to generate for each user.
    |
    */
    'recovery_codes_count' => 8,

    /*
    |--------------------------------------------------------------------------
    | Code Timeout
    |--------------------------------------------------------------------------
    |
    | The time in seconds that a 2FA session remains valid.
    |
    */
    'code_timeout' => 300, // 5 minutes
];
