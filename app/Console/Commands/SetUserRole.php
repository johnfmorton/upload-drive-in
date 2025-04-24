<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * php artisan user:set-role {email} {--role=admin}
 *
 * Example:
 * php artisan user:set-role user@example.com --role=admin
 * php artisan user:set-role user@example.com --role=employee
 * php artisan user:set-role user@example.com --role=client
 */
class SetUserRole extends Command
{
    protected $signature = 'user:set-role {email} {--role=admin}';
    protected $description = 'Set the role for a specific user by email';

    public function handle()
    {
        $email = $this->argument('email');
        $role = $this->option('role');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        $user->update(['role' => $role]);
        $this->info("User {$user->name} (Email: {$user->email}) has been set to role '{$role}'.");
        return 0;
    }
}
