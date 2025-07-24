<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * php artisan user:login-url {email}
 *
 * Example:
 * php artisan user:login-url client@example.com
 */
class GenerateUserLoginUrl extends Command
{
    protected $signature = 'user:login-url {email}';
    protected $description = 'Generate a login URL for a user (primarily for clients)';

    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        $this->info("Login URL for '{$user->name}' ({$user->email}):");
        $this->line($user->login_url);

        if ($user->canLoginWithPassword()) {
            $this->warn('Note: This user can also login with password at the regular login page.');
        } else {
            $this->info('This user can only login using the token-based URL above.');
        }

        $this->info('URL is valid for 7 days from generation.');

        return 0;
    }
}