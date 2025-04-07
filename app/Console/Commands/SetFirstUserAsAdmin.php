<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetFirstUserAsAdmin extends Command
{
    protected $signature = 'user:set-first-admin';
    protected $description = 'Set the first user as admin';

    public function handle()
    {
        $user = User::first();
        if (!$user) {
            $this->error('No users found in the database.');
            return 1;
        }

        $user->update(['role' => 'admin']);
        $this->info("User {$user->name} (ID: {$user->id}) has been set as admin.");
        return 0;
    }
}
