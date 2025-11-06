<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupPasswordResetTokens extends Command
{
    protected $signature = 'auth:cleanup-reset-tokens {--force : Force cleanup without confirmation}';
    protected $description = 'Clean up duplicate and expired password reset tokens';

    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('This will remove all existing password reset tokens. Continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Cleaning up password reset tokens...');

        // Count existing tokens
        $existingCount = DB::table('password_reset_tokens')->count();
        $this->info("Found {$existingCount} existing tokens.");

        // Remove all existing tokens to clear any duplicates
        $deletedCount = DB::table('password_reset_tokens')->delete();
        
        $this->info("Deleted {$deletedCount} password reset tokens.");
        $this->info('Password reset token cleanup completed successfully.');

        return 0;
    }
}