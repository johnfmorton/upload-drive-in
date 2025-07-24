<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * php artisan user:reset-password {email} {--password=}
 *
 * Examples:
 * php artisan user:reset-password user@example.com
 * php artisan user:reset-password user@example.com --password=newpassword123
 */
class ResetUserPassword extends Command
{
    protected $signature = 'user:reset-password {email} {--password=}';
    protected $description = 'Reset a user\'s password';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->option('password');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        // Check if user can login with password
        if (!$user->canLoginWithPassword()) {
            $this->error("User with role '{$user->role->label()}' cannot login with password. Use token-based login instead.");
            return 1;
        }

        // Get password if not provided
        if (!$password) {
            $password = $this->secret('Enter new password (leave empty to generate random):');
            if (!$password) {
                $password = Str::random(16);
                $this->info("Generated password: {$password}");
            }
        }

        // Validate password length
        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters long.');
            return 1;
        }

        // Update password
        $user->update([
            'password' => Hash::make($password),
        ]);

        $this->info("Password reset successfully for user '{$user->name}' ({$user->email}).");
        
        if ($this->option('password')) {
            $this->warn('Password was provided via command line option. Consider using interactive mode for better security.');
        }

        return 0;
    }
}