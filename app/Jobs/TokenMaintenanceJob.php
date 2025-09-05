<?php

namespace App\Jobs;

use App\Models\GoogleDriveToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TokenMaintenanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        $operationId = uniqid('token_maintenance_');
        
        Log::info('Token maintenance job started', [
            'operation_id' => $operationId,
            'timestamp' => now()->toISOString()
        ]);

        try {
            $this->refreshExpiringTokens($operationId);
            $this->cleanupFailedRefreshAttempts($operationId);
            $this->scheduleProactiveRefreshes($operationId);
            
            Log::info('Token maintenance job completed successfully', [
                'operation_id' => $operationId,
                'duration_ms' => $this->getExecutionTime()
            ]);
        } catch (\Exception $e) {
            Log::error('Token maintenance job failed', [
                'operation_id' => $operationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    private function refreshExpiringTokens(string $operationId): void
    {
        // Find tokens expiring within 30 minutes
        $expiringTokens = GoogleDriveToken::where('expires_at', '<=', now()->addMinutes(30))
            ->where('expires_at', '>', now())
            ->where('requires_user_intervention', false)
            ->get();

        Log::info('Found expiring tokens for refresh', [
            'operation_id' => $operationId,
            'count' => $expiringTokens->count()
        ]);

        foreach ($expiringTokens as $token) {
            try {
                // Schedule immediate refresh for expiring tokens
                dispatch(new RefreshTokenJob($token->user, 'google-drive'))
                    ->onQueue('high');
                    
                Log::info('Scheduled refresh for expiring token', [
                    'operation_id' => $operationId,
                    'user_id' => $token->user_id,
                    'expires_at' => $token->expires_at->toISOString()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to schedule token refresh', [
                    'operation_id' => $operationId,
                    'user_id' => $token->user_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function cleanupFailedRefreshAttempts(string $operationId): void
    {
        // Clean up tokens with old failed refresh attempts (older than 7 days)
        $cutoffDate = now()->subDays(7);
        
        $updated = GoogleDriveToken::where('last_refresh_attempt_at', '<', $cutoffDate)
            ->where('refresh_failure_count', '>', 0)
            ->update([
                'refresh_failure_count' => 0,
                'last_refresh_attempt_at' => null
            ]);

        Log::info('Cleaned up old failed refresh attempts', [
            'operation_id' => $operationId,
            'updated_count' => $updated,
            'cutoff_date' => $cutoffDate->toISOString()
        ]);
    }

    private function scheduleProactiveRefreshes(string $operationId): void
    {
        // Find tokens that need proactive refresh scheduling (expire in 1-24 hours)
        $tokensNeedingScheduling = GoogleDriveToken::where('expires_at', '>', now()->addMinutes(30))
            ->where('expires_at', '<=', now()->addHours(24))
            ->whereNull('proactive_refresh_scheduled_at')
            ->where('requires_user_intervention', false)
            ->get();

        Log::info('Found tokens needing proactive refresh scheduling', [
            'operation_id' => $operationId,
            'count' => $tokensNeedingScheduling->count()
        ]);

        foreach ($tokensNeedingScheduling as $token) {
            try {
                $refreshTime = $token->expires_at->subMinutes(15);
                
                if ($refreshTime->isFuture()) {
                    // Schedule for future refresh
                    dispatch(new RefreshTokenJob($token->user, 'google-drive'))
                        ->delay($refreshTime)
                        ->onQueue('maintenance');
                        
                    // Mark as scheduled
                    $token->update(['proactive_refresh_scheduled_at' => now()]);
                    
                    Log::info('Scheduled proactive token refresh', [
                        'operation_id' => $operationId,
                        'user_id' => $token->user_id,
                        'scheduled_for' => $refreshTime->toISOString()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to schedule proactive refresh', [
                    'operation_id' => $operationId,
                    'user_id' => $token->user_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    private function getExecutionTime(): int
    {
        return (int) ((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
    }
}