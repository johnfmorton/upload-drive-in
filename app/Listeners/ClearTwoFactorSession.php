<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Session;

class ClearTwoFactorSession
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        // Clear 2FA verification status
        Session::forget('two_factor_verified');

        // Clear any stored intended URL
        Session::forget('url.intended');
    }
}
