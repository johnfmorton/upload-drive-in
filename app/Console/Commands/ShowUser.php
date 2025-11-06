<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * php artisan user:show {email}
 *
 * Example:
 * php artisan user:show user@example.com
 */
class ShowUser extends Command
{
    protected $signature = 'user:show {email}';
    protected $description = 'Show detailed information about a user';

    public function handle()
    {
        $email = $this->argument('email');

        $user = User::with(['owner', 'employees', 'googleDriveToken'])
            ->where('email', $email)
            ->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        // Basic user information
        $this->info("User Details:");
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $user->id],
                ['Name', $user->name],
                ['Email', $user->email],
                ['Username', $user->username],
                ['Role', $user->role->label()],
                ['Owner', $user->owner ? $user->owner->name . ' (' . $user->owner->email . ')' : 'None'],
                ['Created', $user->created_at->format('Y-m-d H:i:s')],
                ['Updated', $user->updated_at->format('Y-m-d H:i:s')],
                ['Email Verified', $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : 'No'],
            ]
        );

        // Settings
        $this->info("\nUser Settings:");
        $this->table(
            ['Setting', 'Value'],
            [
                ['Upload Notifications', $user->receive_upload_notifications ? 'Enabled' : 'Disabled'],
                ['2FA Enabled', $user->two_factor_enabled ? 'Yes' : 'No'],
                ['2FA Confirmed', $user->two_factor_confirmed_at ? $user->two_factor_confirmed_at->format('Y-m-d H:i:s') : 'No'],
                ['Google Drive Connected', $user->hasGoogleDriveConnected() ? 'Yes' : 'No'],
                ['Google Drive Root Folder', $user->google_drive_root_folder_id ?: 'Not set'],
            ]
        );

        // Login information
        if ($user->isClient()) {
            $this->info("\nClient Login Information:");
            $this->table(
                ['Type', 'URL'],
                [
                    ['Login URL', $user->login_url],
                    ['Password Reset URL', $user->generateResetUrl()],
                ]
            );
        }

        // Employees (if admin)
        if ($user->isAdmin()) {
            $employees = $user->employees;
            if ($employees->isNotEmpty()) {
                $this->info("\nEmployees ({$employees->count()}):");
                $this->table(
                    ['Name', 'Email', 'Role', 'Created'],
                    $employees->map(function ($employee) {
                        return [
                            $employee->name,
                            $employee->email,
                            $employee->role->label(),
                            $employee->created_at->format('Y-m-d H:i:s'),
                        ];
                    })
                );
            } else {
                $this->info("\nNo employees found.");
            }
        }

        return 0;
    }
}