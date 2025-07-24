<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * php artisan user:toggle-notifications {email} {--enable} {--disable}
 *
 * Examples:
 * php artisan user:toggle-notifications user@example.com --enable
 * php artisan user:toggle-notifications user@example.com --disable
 * php artisan user:toggle-notifications user@example.com (toggles current state)
 */
class ToggleUserNotifications extends Command
{
    protected $signature = 'user:toggle-notifications {email} {--enable} {--disable}';
    protected $description = 'Toggle upload notifications for a user';

    public function handle()
    {
        $email = $this->argument('email');
        $enable = $this->option('enable');
        $disable = $this->option('disable');

        if ($enable && $disable) {
            $this->error('Cannot use both --enable and --disable options.');
            return 1;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        $currentState = $user->receive_upload_notifications;
        
        if ($enable) {
            $newState = true;
        } elseif ($disable) {
            $newState = false;
        } else {
            // Toggle current state
            $newState = !$currentState;
        }

        $user->update(['receive_upload_notifications' => $newState]);

        $status = $newState ? 'enabled' : 'disabled';
        $this->info("Upload notifications {$status} for user '{$user->name}' ({$user->email}).");

        return 0;
    }
}