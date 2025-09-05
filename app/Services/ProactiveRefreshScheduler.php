<?php

namespace App\Services;

use App\Jobs\RefreshTokenJob;
use App\Models\GoogleDriveToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProactiveRefreshScheduler
{
    private const DEFAULT_REFRESH_BUFFER_MINUTES = 15;
    private const IMMEDIATE_REFRESH_THRESHOLD_MINUTES = 5;
    private const MAX_SCHEDULE_AHEAD_HOURS = 24;

    public function scheduleRefreshForToken(GoogleDriveToken $token): bool
    {
        $operationId = uniqid('schedule_refresh_');
        
        Log::info('Scheduling refresh for token', [
            'operation_id' => $operationId,
            'user_id' => $token->user_id,
            'expires_at' => $token->expires_at?->toISOString(),
            'current_time' => now()->toISOString()
        ]);

        try {
            if (!$token->expires_at) {
                Log::warning('Cannot schedule refresh for token without expiration', [
                    'operation_id' => $operationId,
                    'user_id' => $token->user_id
                ]);
                return false;
            }

            if ($token->requires_user_intervention) {
                Log::info('Skipping refresh scheduling for token requiring user intervention', [
                    'operation_id' => $operationId,
                    'user_id' => $token->user_id
                ]);
                return false;
            }

            $refreshTime = $this->calculateRefreshTime($token);
            
            if ($refreshTime->isPast()) {
                // Token expires very soon, refresh immediately
                $this->scheduleImmediateRefresh($token, $operationId);
                return true;
            }

            $hoursAhead = now()->diffInHours($refreshTime, false);
            if ($hoursAhead > self::MAX_SCHEDULE_AHEAD_HOURS) {
                Log::info('Token refresh too far in future, will be scheduled later', [
                    'operation_id' => $operationId,
                    'user_id' => $token->user_id,
                    'refresh_time' => $refreshTime->toISOString(),
                    'hours_ahead' => $hoursAhead,
                    'max_hours' => self::MAX_SCHEDULE_AHEAD_HOURS
                ]);
                return false;
            }

            $this->scheduleDelayedRefresh($token, $refreshTime, $operationId);
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to schedule token refresh', [
                'operation_id' => $operationId,
                'user_id' => $token->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function scheduleRefreshForUser(User $user, string $provider = 'google-drive'): bool
    {
        $token = $user->googleDriveToken;
        
        if (!$token) {
            Log::info('No token found for user, skipping refresh scheduling', [
                'user_id' => $user->id,
                'provider' => $provider
            ]);
            return false;
        }

        return $this->scheduleRefreshForToken($token);
    }

    public function scheduleAllExpiringTokens(int $withinMinutes = 30): array
    {
        $operationId = uniqid('schedule_all_expiring_');
        
        Log::info('Scheduling refresh for all expiring tokens', [
            'operation_id' => $operationId,
            'within_minutes' => $withinMinutes
        ]);

        $expiringTokens = GoogleDriveToken::where('expires_at', '<=', now()->addMinutes($withinMinutes))
            ->where('expires_at', '>', now())
            ->where('requires_user_intervention', false)
            ->whereNull('proactive_refresh_scheduled_at')
            ->get();

        $results = [
            'total' => $expiringTokens->count(),
            'scheduled' => 0,
            'failed' => 0,
            'skipped' => 0
        ];

        foreach ($expiringTokens as $token) {
            try {
                if ($this->scheduleRefreshForToken($token)) {
                    $results['scheduled']++;
                } else {
                    $results['skipped']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('Failed to schedule refresh for expiring token', [
                    'operation_id' => $operationId,
                    'user_id' => $token->user_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Completed scheduling for all expiring tokens', [
            'operation_id' => $operationId,
            'results' => $results
        ]);

        return $results;
    }

    public function cancelScheduledRefresh(GoogleDriveToken $token): bool
    {
        try {
            $token->update(['proactive_refresh_scheduled_at' => null]);
            
            Log::info('Cancelled scheduled refresh for token', [
                'user_id' => $token->user_id
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cancel scheduled refresh', [
                'user_id' => $token->user_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function calculateRefreshTime(GoogleDriveToken $token): Carbon
    {
        $bufferMinutes = config('cloud-storage.token_refresh_buffer_minutes', self::DEFAULT_REFRESH_BUFFER_MINUTES);
        return $token->expires_at->copy()->subMinutes($bufferMinutes);
    }

    private function scheduleImmediateRefresh(GoogleDriveToken $token, string $operationId): void
    {
        dispatch(new RefreshTokenJob($token->user, 'google-drive'))
            ->onQueue('high');
            
        $token->update(['proactive_refresh_scheduled_at' => now()]);
        
        Log::info('Scheduled immediate token refresh', [
            'operation_id' => $operationId,
            'user_id' => $token->user_id,
            'reason' => 'expires_very_soon'
        ]);
    }

    private function scheduleDelayedRefresh(GoogleDriveToken $token, Carbon $refreshTime, string $operationId): void
    {
        dispatch(new RefreshTokenJob($token->user, 'google-drive'))
            ->delay($refreshTime)
            ->onQueue('maintenance');
            
        $token->update(['proactive_refresh_scheduled_at' => now()]);
        
        Log::info('Scheduled delayed token refresh', [
            'operation_id' => $operationId,
            'user_id' => $token->user_id,
            'scheduled_for' => $refreshTime->toISOString(),
            'delay_minutes' => $refreshTime->diffInMinutes(now())
        ]);
    }
}