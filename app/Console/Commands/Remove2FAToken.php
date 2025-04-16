<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class Remove2FAToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:2fa-remove {email : The email of the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove 2FA token for a specific user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return Command::FAILURE;
        }

        if (!$user->two_factor_enabled) {
            $this->info("2FA is already disabled for user {$email}.");
            return Command::SUCCESS;
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_enabled = false;
        $user->save();

        $this->info("Successfully removed 2FA for user {$email}.");
        return Command::SUCCESS;
    }
}
