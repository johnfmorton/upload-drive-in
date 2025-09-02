<?php

namespace App\Console\Commands;

use App\Models\CloudStorageHealthStatus;
use App\Models\GoogleDriveToken;
use App\Models\User;
use Illuminate\Console\Command;

class DiagnoseCloudStorageStatus extends Command
{
    protected $signature = 'cloud-storage:diagnose {--user-id= : Specific user ID to diagnose}';
    protected $description = 'Diagnose cloud storage status inconsistencies';

    public function handle()
    {
        $userId = $this->option('user-id');

        if ($userId) {
            $users = User::where('id', $userId)->get();
        } else {
            $users = User::whereHas('googleDriveToken')->get();
        }

        if ($users->isEmpty()) {
            $this->error('No users found with Google Drive tokens');
            return 1;
        }

        foreach ($users as $user) {
            $this->info("=== User: {$user->name} (ID: {$user->id}) ===");

            // Check Google Drive Token
            $token = $user->googleDriveToken;
            if ($token) {
                $this->info("Google Drive Token:");
                $this->line("  - Expires at: " . ($token->expires_at ? $token->expires_at->toDateTimeString() : 'Never'));
                $this->line("  - Is expired: " . ($token->expires_at && $token->expires_at->isPast() ? 'YES' : 'NO'));
                $this->line("  - Has refresh token: " . ($token->refresh_token ? 'YES' : 'NO'));
                $this->line("  - Scopes: " . implode(', ', $token->scopes ?? []));
            } else {
                $this->warn("  - No Google Drive token found");
            }

            // Check Health Status
            $healthStatus = CloudStorageHealthStatus::where('user_id', $user->id)
                ->where('provider', 'google-drive')
                ->first();

            if ($healthStatus) {
                $this->info("Health Status:");
                $this->line("  - Status: {$healthStatus->status}");
                $this->line("  - Requires reconnection: " . ($healthStatus->requires_reconnection ? 'YES' : 'NO'));
                $this->line("  - Last error type: " . ($healthStatus->last_error_type ?? 'None'));
                $this->line("  - Last error message: " . ($healthStatus->last_error_message ?? 'None'));
                $this->line("  - Consecutive failures: {$healthStatus->consecutive_failures}");
                $this->line("  - Last successful operation: " . ($healthStatus->last_successful_operation_at ? $healthStatus->last_successful_operation_at->toDateTimeString() : 'Never'));
                $this->line("  - Token expires at: " . ($healthStatus->token_expires_at ? $healthStatus->token_expires_at->toDateTimeString() : 'Not set'));
            } else {
                $this->warn("  - No health status record found");
            }

            // Check for inconsistencies
            $this->info("Inconsistency Check:");
            if ($healthStatus && $token) {
                $issues = [];

                // Check if status is healthy but requires reconnection
                if ($healthStatus->status === 'healthy' && $healthStatus->requires_reconnection) {
                    $issues[] = "Status is 'healthy' but requires_reconnection is true";
                }

                // Check if token is expired but status is healthy
                if ($token->expires_at && $token->expires_at->isPast() && $healthStatus->status === 'healthy') {
                    $issues[] = "Token is expired but status is 'healthy'";
                }

                // Check if there's an error type but status is healthy
                if ($healthStatus->last_error_type && $healthStatus->status === 'healthy') {
                    $issues[] = "Has error type '{$healthStatus->last_error_type}' but status is 'healthy'";
                }

                if (empty($issues)) {
                    $this->info("  - No inconsistencies found");
                } else {
                    foreach ($issues as $issue) {
                        $this->error("  - ISSUE: {$issue}");
                    }
                }
            }

            $this->line('');
        }

        return 0;
    }
}
