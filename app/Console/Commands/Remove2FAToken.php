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
    protected $signature = 'admin:2fa-remove {email : The email of the user} {--show-only : Only show the 2FA details without removing them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove 2FA token for a specific user and optionally show the details';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $showOnly = $this->option('show-only');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return Command::FAILURE;
        }

        if (!$user->two_factor_enabled) {
            $this->error("2FA is not enabled for user {$email}.");
            return Command::FAILURE;
        }

        // Show 2FA details
        $this->info("\n2FA Details for user: {$email}");
        $this->info("----------------------------------------");
        $this->info("2FA Secret: " . $user->two_factor_secret);

        if ($user->two_factor_recovery_codes) {
            $this->info("\nRecovery Codes:");
            $this->info("----------------------------------------");
            $recoveryCodes = json_decode($user->two_factor_recovery_codes, true);
            foreach ($recoveryCodes as $code) {
                $this->line($code);
            }
        }

        if ($showOnly) {
            return Command::SUCCESS;
        }

        $this->info("\n----------------------------------------");
        if ($this->confirm('Do you want to proceed with removing 2FA?', true)) {
            $user->two_factor_secret = null;
            $user->two_factor_recovery_codes = null;
            $user->two_factor_enabled = false;
            $user->save();

            $this->info("\nSuccessfully removed 2FA for user {$email}.");
            return Command::SUCCESS;
        }

        $this->info("\n2FA removal cancelled.");
        return Command::SUCCESS;
    }
}
