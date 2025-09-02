<?php

namespace App\Console\Commands;

use App\Models\CloudStorageHealthStatus;
use App\Models\User;
use Illuminate\Console\Command;

class SyncCloudStorageTokens extends Command
{
    protected $signature = 'cloud-storage:sync-tokens {--user-id= : Specific user ID to sync} {--dry-run : Show what would be synced without making changes}';
    protected $description = 'Sync cloud storage token expiration data between tokens and health status';

    public function handle()
    {
        $userId = $this->option('user-id');
        $dryRun = $this->option('dry-run');
        
        if ($userId) {
            $users = User::where('id', $userId)->get();
        } else {
            $users = User::whereHas('googleDriveToken')->get();
        }

        if ($users->isEmpty()) {
            $this->error('No users found with Google Drive tokens');
            return 1;
        }

        $syncCount = 0;

        foreach ($users as $user) {
            $this->info("=== Syncing User: {$user->name} (ID: {$user->id}) ===");
            
            $token = $user->googleDriveToken;
            if (!$token) {
                $this->warn("No Google Drive token found");
                continue;
            }
            
            $healthStatus = CloudStorageHealthStatus::where('user_id', $user->id)
                ->where('provider', 'google-drive')
                ->first();
                
            if (!$healthStatus) {
                if ($dryRun) {
                    $this->warn("WOULD CREATE: New health status record");
                } else {
                    $healthStatus = CloudStorageHealthStatus::create([
                        'user_id' => $user->id,
                        'provider' => 'google-drive',
                        'status' => 'healthy',
                        'consecutive_failures' => 0,
                        'requires_reconnection' => false,
                        'token_expires_at' => $token->expires_at,
                    ]);
                    $this->info("CREATED: New health status record");
                    $syncCount++;
                }
                continue;
            }
            
            // Check if token expiration needs syncing
            $needsSync = false;
            $updates = [];
            
            if (!$healthStatus->token_expires_at || 
                ($token->expires_at && $healthStatus->token_expires_at->ne($token->expires_at))) {
                $needsSync = true;
                $updates['token_expires_at'] = $token->expires_at;
                $this->line("  - Token expiration: " . 
                    ($healthStatus->token_expires_at ? $healthStatus->token_expires_at->toDateTimeString() : 'null') . 
                    " → " . 
                    ($token->expires_at ? $token->expires_at->toDateTimeString() : 'null')
                );
            }
            
            // If token is valid but health status shows it's expired, fix the status
            if ($token->expires_at && !$token->expires_at->isPast() && 
                $healthStatus->isTokenExpired()) {
                $needsSync = true;
                $updates['status'] = 'healthy';
                $updates['requires_reconnection'] = false;
                $updates['last_error_type'] = null;
                $updates['last_error_message'] = null;
                $this->line("  - Status: Fixing incorrect expired token status");
            }
            
            // If token is actually expired but status is healthy, fix it
            if ($token->expires_at && $token->expires_at->isPast() && 
                $healthStatus->status === 'healthy') {
                $needsSync = true;
                $updates['status'] = 'unhealthy';
                $updates['requires_reconnection'] = true;
                $updates['last_error_type'] = 'token_expired';
                $updates['last_error_message'] = 'Google Drive token has expired';
                $this->line("  - Status: Marking as unhealthy due to expired token");
            }
            
            if ($needsSync) {
                if ($dryRun) {
                    $this->warn("  - WOULD SYNC: " . count($updates) . " fields");
                } else {
                    $healthStatus->update($updates);
                    $this->info("  - SYNCED: " . count($updates) . " fields");
                    $syncCount++;
                }
            } else {
                $this->info("  - Already in sync");
            }
            
            $this->line('');
        }
        
        if ($dryRun) {
            $this->info("Dry run completed. Found records that could be synced.");
        } else {
            $this->info("Synced {$syncCount} records.");
        }
        
        return 0;
    }
}