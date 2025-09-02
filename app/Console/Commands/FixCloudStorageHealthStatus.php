<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\CloudStorageHealthStatus;
use App\Services\CloudStorageHealthService;
use App\Enums\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FixCloudStorageHealthStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloud-storage:fix-health-status 
                            {--provider=* : Specific provider(s) to fix (default: all)}
                            {--user= : Specific user ID to fix (default: all users)}
                            {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix inconsistent cloud storage health status records';

    /**
     * Execute the console command.
     */
    public function handle(CloudStorageHealthService $healthService): int
    {
        $this->info('🔧 Fixing Cloud Storage Health Status Records');
        $this->info('================================================');

        $providers = $this->option('provider') ?: ['google-drive'];
        $userId = $this->option('user');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        // Get users to process
        $usersQuery = User::whereIn('role', [UserRole::ADMIN, UserRole::EMPLOYEE]);
        if ($userId) {
            $usersQuery->where('id', $userId);
        }
        $users = $usersQuery->get();

        if ($users->isEmpty()) {
            $this->error('No users found to process');
            return 1;
        }

        $this->info("Processing {$users->count()} users for providers: " . implode(', ', $providers));
        $this->newLine();

        $totalFixed = 0;
        $totalChecked = 0;

        foreach ($users as $user) {
            $this->line("👤 Processing user: {$user->email} (ID: {$user->id})");

            foreach ($providers as $provider) {
                $totalChecked++;
                
                $healthStatus = CloudStorageHealthStatus::where('user_id', $user->id)
                    ->where('provider', $provider)
                    ->first();

                if (!$healthStatus) {
                    $this->line("   ℹ️  No health record for {$provider}");
                    continue;
                }

                // Check for inconsistencies
                $needsFix = $this->detectInconsistency($healthStatus);
                
                if ($needsFix) {
                    $oldStatus = $healthStatus->consolidated_status;
                    
                    if (!$isDryRun) {
                        // Recalculate the correct status
                        $newStatus = $healthService->determineConsolidatedStatus($user, $provider);
                        $healthStatus->update(['consolidated_status' => $newStatus]);
                        
                        $this->line("   ✅ Fixed {$provider}: {$oldStatus} → {$newStatus}");
                        
                        Log::info('Fixed cloud storage health status via command', [
                            'user_id' => $user->id,
                            'provider' => $provider,
                            'old_status' => $oldStatus,
                            'new_status' => $newStatus,
                        ]);
                    } else {
                        $newStatus = $healthService->determineConsolidatedStatus($user, $provider);
                        $this->line("   🔍 Would fix {$provider}: {$oldStatus} → {$newStatus}");
                    }
                    
                    $totalFixed++;
                } else {
                    $this->line("   ✅ {$provider}: {$healthStatus->consolidated_status} (OK)");
                }
            }
        }

        $this->newLine();
        $this->info("📊 Summary:");
        $this->info("   Records checked: {$totalChecked}");
        $this->info("   Records " . ($isDryRun ? 'needing fix' : 'fixed') . ": {$totalFixed}");

        if ($isDryRun && $totalFixed > 0) {
            $this->newLine();
            $this->warn('To apply these fixes, run the command without --dry-run');
        }

        return 0;
    }

    /**
     * Detect if a health status record has inconsistencies.
     */
    private function detectInconsistency(CloudStorageHealthStatus $healthStatus): bool
    {
        // No consolidated status set
        if (!$healthStatus->consolidated_status) {
            return true;
        }

        // Status is healthy but consolidated shows not connected
        if ($healthStatus->status === 'healthy' && $healthStatus->consolidated_status === 'not_connected') {
            return true;
        }

        // Had successful operations recently but shows not connected
        if ($healthStatus->last_successful_operation_at && 
            $healthStatus->consolidated_status === 'not_connected' && 
            $healthStatus->last_successful_operation_at->isAfter(now()->subHours(24))) {
            return true;
        }

        // Token exists and is valid but shows authentication required
        if ($healthStatus->token_expires_at && 
            $healthStatus->token_expires_at->isFuture() &&
            $healthStatus->consolidated_status === 'authentication_required') {
            return true;
        }

        return false;
    }
}