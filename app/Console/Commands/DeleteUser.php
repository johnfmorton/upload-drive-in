<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * php artisan user:delete {email} {--force}
 *
 * Examples:
 * php artisan user:delete user@example.com
 * php artisan user:delete user@example.com --force
 */
class DeleteUser extends Command
{
    protected $signature = 'user:delete {email} {--force}';
    protected $description = 'Delete a user by email address';

    public function handle()
    {
        $email = $this->argument('email');
        $force = $this->option('force');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        // Check if user has employees (for admin users)
        $employeeCount = $user->employees()->count();
        if ($employeeCount > 0 && !$force) {
            $this->error("User has {$employeeCount} employee(s). Use --force to delete anyway or reassign employees first.");
            return 1;
        }

        // Show user details
        $this->info("User to delete:");
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $user->name],
                ['Email', $user->email],
                ['Role', $user->role->label()],
                ['Created', $user->created_at->format('Y-m-d H:i:s')],
                ['Employees', $employeeCount],
            ]
        );

        // Confirm deletion unless forced
        if (!$force && !$this->confirm('Are you sure you want to delete this user?')) {
            $this->info('User deletion cancelled.');
            return 0;
        }

        // Handle employee reassignment or deletion
        if ($employeeCount > 0) {
            if ($force) {
                // Set employees' owner_id to null or delete them
                $user->employees()->update(['owner_id' => null]);
                $this->warn("Removed owner relationship for {$employeeCount} employee(s).");
            }
        }

        $userName = $user->name;
        $user->delete();

        $this->info("User '{$userName}' ({$email}) has been deleted successfully.");
        return 0;
    }
}