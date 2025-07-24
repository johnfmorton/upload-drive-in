<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * php artisan user:list {--role=} {--owner=}
 *
 * Examples:
 * php artisan user:list
 * php artisan user:list --role=admin
 * php artisan user:list --role=employee --owner=admin@example.com
 */
class ListUsers extends Command
{
    protected $signature = 'user:list {--role=} {--owner=}';
    protected $description = 'List all users or filter by role and owner';

    public function handle()
    {
        $roleFilter = $this->option('role');
        $ownerEmail = $this->option('owner');

        $query = User::query()->with(['owner']);

        // Filter by role if specified
        if ($roleFilter) {
            if (!in_array($roleFilter, ['admin', 'employee', 'client'])) {
                $this->error("Invalid role '{$roleFilter}'. Valid roles are: admin, employee, client");
                return 1;
            }
            $query->where('role', $roleFilter);
        }

        // Filter by owner if specified
        if ($ownerEmail) {
            $owner = User::where('email', $ownerEmail)->first();
            if (!$owner) {
                $this->error("Owner user with email {$ownerEmail} not found.");
                return 1;
            }
            $query->where('owner_id', $owner->id);
        }

        $users = $query->orderBy('role')->orderBy('name')->get();

        if ($users->isEmpty()) {
            $this->info('No users found matching the criteria.');
            return 0;
        }

        $headers = ['ID', 'Name', 'Email', 'Role', 'Owner', 'Created'];
        $rows = $users->map(function ($user) {
            return [
                $user->id,
                $user->name,
                $user->email,
                $user->role->label(),
                $user->owner ? $user->owner->name : '-',
                $user->created_at->format('Y-m-d H:i'),
            ];
        });

        $this->table($headers, $rows);
        $this->info("Total users: {$users->count()}");

        return 0;
    }
}