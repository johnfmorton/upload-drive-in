<?php

namespace App\Console\Commands;

use App\Models\CloudStorageHealthStatus;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use Illuminate\Console\Command;

class FixCloudStorageStatus extends Command
{
    protected $signature = 'cloud-storage:fix {--user-id= : Specific user ID to fix} {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix cloud storage status inconsistencies';

    public function __construct(
        private CloudStorageHealthService $healthService
    ) {
        parent::__construct();
    }

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

        $fixCount = 0;

        foreach ($users as $user) {
            $this->info("=== Checking User: {$user->name} (ID: {$user->id}) ===");
            
            $token = $user->googleDriveToken;
            $healthStatus = CloudStorageHealthStatus::where('user_id', $user->id)
                ->where('provider', 'google-drive')
                ->first();
                
            if (!$token || !$healthStatus) {
                $this->warn("Skipping - missing token or health status");
                continue;
            }
            
            $fixes = [];
            
            // Fix 1: Token expired but status is healthy
            if ($token->expires_at && $token->expires_at->isPast() && $healthStatus->status === 'healthy') {
                $fixes[] = [
                    'issue' => 'Token expired but status is healthy',
                    'action' => function() use ($healthStatus) {
                        $healthStatus->update([
                            'status' => 'unhealthy',
                            'requires_reconnection' => true,
                            'last_error_type' => 'token_expired',
                            'last_error_message' => 'Google Drive token has expired',
                            'consecutive_failures' => 1
                        ]);
                    }
                ];
            }
            
            // Fix 2: Status is healthy but requires reconnection
            if ($healthStatus->status === 'healthy' && $healthStatus->requires_reconnection) {
                $fixes[] = [
                    'issue' => 'Status is healthy but requires reconnection',
                    'action' => function() use ($healthStatus, $token) {
                        // If token is valid, clear reconnection requirement
                        if ($token->expires_at && !$token->expires_at->isPast()) {
                            $healthStatus->update([
                                'requires_reconnection' => false,
                                'last_error_type' => null,
                                'last_error_message' => null
                            ]);
                        } else {
                            // If token is invalid, mark as unhealthy
                            $healthStatus->update([
                                'status' => 'unhealthy',
                                'last_error_type' => 'token_expired',
                                'last_error_message' => 'Google Drive token requires refresh'
                            ]);
                        }
                    }
                ];
            }
            
            // Fix 3: Has error type but status is healthy
            if ($healthStatus->last_error_type && $healthStatus->status === 'healthy') {
                $fixes[] = [
                    'issue' => "Has error type '{$healthStatus->last_error_type}' but status is healthy",
                    'action' => function() use ($healthStatus) {
                        // Clear error information if status is truly healthy
                        $healthStatus->update([
                            'last_error_type' => null,
                            'last_error_message' => null,
                            'requires_reconnection' => false,
                            'consecutive_failures' => 0
                        ]);
                    }
                ];
            }
            
            // Fix 4: Update token expiration in health status
            if ($token->expires_at && (!$healthStatus->token_expires_at || $healthStatus->token_expires_at->ne($token->expires_at))) {
                $fixes[] = [
                    'issue' => 'Health status token expiration is out of sync',
                    'action' => function() use ($healthStatus, $token) {
                        $healthStatus->update([
                            'token_expires_at' => $token->expires_at
                        ]);
                    }
                ];
            }
            
            if (empty($fixes)) {
                $this->info("  - No issues found");
            } else {
                foreach ($fixes as $fix) {
                    if ($dryRun) {
                        $this->warn("  - WOULD FIX: {$fix['issue']}");
                    } else {
                        $this->info("  - FIXING: {$fix['issue']}");
                        $fix['action']();
                        $fixCount++;
                    }
                }
            }
            
            $this->line('');
        }
        
        if ($dryRun) {
            $this->info("Dry run completed. Found issues that could be fixed.");
        } else {
            $this->info("Fixed {$fixCount} issues.");
        }
        
        return 0;
    }
}