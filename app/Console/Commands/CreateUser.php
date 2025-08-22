<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Create a new user with specified role and details
 * 
 * Supports both positional arguments and explicit options:
 * php artisan user:create {name} {email} {--role=client} {--password=} {--owner=}
 * php artisan user:create --name="Name" --email="email@example.com" {--role=client} {--password=} {--owner=}
 *
 * Examples:
 * php artisan user:create "Client User" client@example.com (default: client role)
 * php artisan user:create "John Doe" john@example.com --role=admin
 * php artisan user:create "Jane Smith" jane@example.com --role=employee --owner=admin@example.com
 * php artisan user:create --name="Admin User" --email="admin@example.com" --role=admin --password="secure-password"
 */
class CreateUser extends Command
{
    protected $signature = 'user:create {name?} {email?} {--name=} {--email=} {--role=client} {--password=} {--owner=}';
    protected $description = 'Create a new user with specified role and details';

    public function handle()
    {
        // Support both positional arguments and explicit options
        // Explicit options take precedence over positional arguments
        $name = $this->option('name') ?: $this->argument('name');
        $email = $this->option('email') ?: $this->argument('email');
        
        // Validate that we have both name and email
        if (!$name || !$email) {
            $this->error('Both name and email are required. Use either:');
            $this->line('  php artisan user:create "Name" "email@example.com" [options]');
            $this->line('  php artisan user:create --name="Name" --email="email@example.com" [options]');
            return 1;
        }
        
        $role = $this->option('role');
        $password = $this->option('password');
        $ownerEmail = $this->option('owner');

        // Validate role
        if (!in_array($role, ['admin', 'employee', 'client'])) {
            $this->error("Invalid role '{$role}'. Valid roles are: admin, employee, client");
            return 1;
        }

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists.");
            return 1;
        }

        // Handle owner for employee users
        $ownerId = null;
        if ($role === 'employee') {
            if (!$ownerEmail) {
                $this->error("Employee users require an owner. Use --owner=admin@example.com");
                return 1;
            }
            $owner = User::where('email', $ownerEmail)->first();
            if (!$owner) {
                $this->error("Owner user with email {$ownerEmail} not found.");
                return 1;
            }
            if (!$owner->isAdmin()) {
                $this->error("Owner user must be an admin.");
                return 1;
            }
            $ownerId = $owner->id;
        }

        // Generate password if not provided
        if (!$password) {
            if ($role === 'client') {
                // Clients don't need passwords as they use token-based login
                $password = Str::random(32);
            } else {
                $password = $this->secret('Enter password (leave empty to generate random):');
                if (!$password) {
                    $password = Str::random(16);
                    $this->info("Generated password: {$password}");
                }
            }
        }

        // Create user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'username' => $email, // Use email as username
            'password' => Hash::make($password),
            'role' => UserRole::from($role),
            'owner_id' => $ownerId,
            'receive_upload_notifications' => $role === 'admin' || $role === 'employee',
        ]);

        $this->info("User created successfully!");
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $user->name],
                ['Email', $user->email],
                ['Role', $user->role->label()],
                ['Owner', $ownerId ? $owner->name : 'None'],
                ['Login URL', $role === 'client' ? $user->login_url : 'N/A (password login)'],
            ]
        );

        if ($role === 'client') {
            $this->warn("Client users use token-based login. Share the Login URL above with the client.");
        }

        return 0;
    }
}