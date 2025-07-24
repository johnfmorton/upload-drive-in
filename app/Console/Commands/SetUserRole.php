<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Console\Command;

/**
 * php artisan user:set-role {email} {--role=admin} {--owner=}
 *
 * Examples:
 * php artisan user:set-role user@example.com --role=admin
 * php artisan user:set-role user@example.com --role=employee --owner=admin@example.com
 * php artisan user:set-role user@example.com --role=employee (auto-assigns to first admin)
 * php artisan user:set-role user@example.com --role=client
 */
class SetUserRole extends Command
{
    protected $signature = 'user:set-role {email} {--role=admin} {--owner=}';
    protected $description = 'Set the role for a specific user by email';

    public function handle()
    {
        $email = $this->argument('email');
        $role = $this->option('role');
        $ownerEmail = $this->option('owner');

        // Validate role
        if (!in_array($role, ['admin', 'employee', 'client'])) {
            $this->error("Invalid role '{$role}'. Valid roles are: admin, employee, client");
            return 1;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        $ownerId = null;
        
        // Handle owner relationship for employee role
        if ($role === 'employee') {
            if (!$ownerEmail) {
                // Try to find an admin to assign as owner
                $adminUser = User::where('role', 'admin')->first();
                if ($adminUser) {
                    $ownerId = $adminUser->id;
                    $this->info("No owner specified. Assigning to admin: {$adminUser->name} ({$adminUser->email})");
                } else {
                    $this->error("Employee users require an owner. Use --owner=admin@example.com or create an admin user first.");
                    return 1;
                }
            } else {
                $owner = User::where('email', $ownerEmail)->first();
                if (!$owner) {
                    $this->error("Owner user with email {$ownerEmail} not found.");
                    return 1;
                }
                if ($owner->role->value !== 'admin') {
                    $this->error("Owner user must be an admin. User {$owner->name} has role '{$owner->role->label()}'.");
                    return 1;
                }
                $ownerId = $owner->id;
            }
        } else {
            // Clear owner_id for non-employee roles
            $ownerId = null;
        }

        // Update user role and owner
        $user->update([
            'role' => $role,
            'owner_id' => $ownerId,
            'receive_upload_notifications' => $role === 'admin' || $role === 'employee',
        ]);

        $ownerInfo = $ownerId ? User::find($ownerId)->name : 'None';
        
        $this->info("User {$user->name} (Email: {$user->email}) has been set to role '{$role}'.");
        if ($ownerId) {
            $this->info("Owner: {$ownerInfo}");
        }
        
        return 0;
    }
}
