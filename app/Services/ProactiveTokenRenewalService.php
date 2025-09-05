<?php

namespace App\Services;

use App\Enums\TokenRefreshErrorType;
use App\Jobs\RefreshTokenJob;
use App\Models\GoogleDriveToken;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Service for proactive token renewal and scheduling
 * 
 * Handles automatic token refresh scheduling and failure management
 * to ensure tokens are renewed before they expire
 */
class ProactiveTokenRenewalService
{
    private const PROACTIVE_REFRESH_MINUTES = 15; // Refresh 15 minutes before expiration
    private const PREEMPTIVE_REFRESH_MINUTES = 30; // Schedule refresh 30 minutes before expiration

    public function __construct(
        private TokenRefreshCoordinator $tokenRefreshCoordinator,
        private TokenRefreshMonitoringService $monitoringService
    ) {}

    /**
     * Refresh a token if it needs refreshing
     * 
     * @param User $user The user whose token needs refreshing
     * @param string $provider The cloud storage provider (e.g., 'google-drive')
     * @return RefreshResult The result of the refresh operation
     */
    public function refreshTokenIfNeeded(User $user, string $provider): RefreshResult
    {
        $operationId = uniqid('proactive_refresh_', true);
        
        Log::info('Proactive token refresh check initiated', [
            'user_id' => $user->id,
            'provider' => $provider,
            'operation_id' => $operationId
        ]);

        // Only handle Google Drive for now
        if ($provider !== 'google-drive') {
            Log::warning('Proactive refresh not supported for provider', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId
            ]);
            
            return RefreshResult::failure(
                TokenRefreshErrorType::UNKNOWN_ERROR,
                new Exception("Proactive refresh not supported for provider: {$provider}"),
                __('messages.proactive_refresh_provider_not_supported')
            );
        }

        // Get the user's Google Drive token
        $token = GoogleDriveToken::where('user_id', $user->id)->first();
        
        if (!$token) {
            Log::info('No token found for user, skipping proactive refresh', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId
            ]);
            
            return RefreshResult::failure(
                TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
                new Exception('No token found for user'),
                __('messages.proactive_refresh_no_token_found')
            );
        }

        // Check if token needs refreshing
        if (!$token->isExpiringSoon(self::PROACTIVE_REFRESH_MINUTES)) {
            Log::info('Token is not expiring soon, no refresh needed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId,
                'expires_at' => $token->expires_at?->toISOString(),
                'minutes_until_expiry' => $token->expires_at?->diffInMinutes(now())
            ]);
            
            return RefreshResult::alreadyValid(
                __('messages.proactive_refresh_token_not_expiring')
            );
        }

        // Check if token can be refreshed
        if (!$token->canBeRefreshed()) {
            Log::warning('Token cannot be refreshed, requires user intervention', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId,
                'requires_user_intervention' => $token->requires_user_intervention,
                'refresh_failure_count' => $token->refresh_failure_count,
                'has_refresh_token' => !empty($token->refresh_token)
            ]);
            
            return RefreshResult::failure(
                TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
                new Exception('Token cannot be refreshed, user intervention required'),
                __('messages.proactive_refresh_requires_reauth')
            );
        }

        Log::info('Token needs proactive refresh, coordinating refresh', [
            'user_id' => $user->id,
            'provider' => $provider,
            'operation_id' => $operationId,
            'expires_at' => $token->expires_at?->toISOString(),
            'minutes_until_expiry' => $token->expires_at?->diffInMinutes(now())
        ]);

        // Use the TokenRefreshCoordinator to perform the refresh
        $result = $this->tokenRefreshCoordinator->coordinateRefresh($user, $provider);
        
        // Handle the result
        if ($result->isSuccessful()) {
            Log::info('Proactive token refresh completed successfully', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId,
                'was_already_valid' => $result->wasAlreadyValid,
                'was_refreshed_by_another_process' => $result->wasRefreshedByAnotherProcess
            ]);
        } else {
            Log::error('Proactive token refresh failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId,
                'error_type' => $result->errorType?->value,
                'error_message' => $result->message
            ]);
            
            // Handle the refresh failure
            $this->handleRefreshFailure($user, $provider, $result->errorType, $result->exception);
        }

        return $result;
    }

    /**
     * Schedule a preemptive token refresh before expiration
     * 
     * @param User $user The user whose token should be refreshed
     * @param string $provider The cloud storage provider
     * @param \Carbon\Carbon $expiresAt When the token expires
     * @return void
     */
    public function schedulePreemptiveRefresh(User $user, string $provider, \Carbon\Carbon $expiresAt): void
    {
        $operationId = uniqid('schedule_refresh_', true);
        
        Log::info('Scheduling preemptive token refresh', [
            'user_id' => $user->id,
            'provider' => $provider,
            'expires_at' => $expiresAt->toISOString(),
            'operation_id' => $operationId
        ]);

        // Log proactive refresh scheduling
        $this->monitoringService->logProactiveRefreshScheduled(
            $user, 
            $provider, 
            $refreshTime, 
            'token_expiring_soon',
            ['expires_at' => $expiresAt->toISOString(), 'operation_id' => $operationId]
        );

        // Only handle Google Drive for now
        if ($provider !== 'google-drive') {
            Log::warning('Preemptive refresh scheduling not supported for provider', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId
            ]);
            return;
        }

        // Calculate when to schedule the refresh (15 minutes before expiration)
        $refreshTime = $expiresAt->copy()->subMinutes(self::PROACTIVE_REFRESH_MINUTES);
        
        // Don't schedule if the refresh time is in the past
        if ($refreshTime->isPast()) {
            Log::info('Refresh time is in the past, dispatching job immediately', [
                'user_id' => $user->id,
                'provider' => $provider,
                'refresh_time' => $refreshTime->toISOString(),
                'operation_id' => $operationId
            ]);
            
            // Dispatch immediately on high priority queue
            RefreshTokenJob::dispatch($user, $provider)
                ->onQueue('high')
                ->onConnection(config('queue.default'));
        } else {
            Log::info('Scheduling refresh job for future execution', [
                'user_id' => $user->id,
                'provider' => $provider,
                'refresh_time' => $refreshTime->toISOString(),
                'delay_minutes' => $refreshTime->diffInMinutes(now()),
                'operation_id' => $operationId
            ]);
            
            // Schedule for future execution
            RefreshTokenJob::dispatch($user, $provider)
                ->delay($refreshTime)
                ->onQueue('maintenance')
                ->onConnection(config('queue.default'));
        }

        // Update the token record to indicate a refresh has been scheduled
        $token = GoogleDriveToken::where('user_id', $user->id)->first();
        if ($token) {
            $token->update([
                'proactive_refresh_scheduled_at' => $refreshTime
            ]);
            
            Log::info('Updated token with scheduled refresh time', [
                'user_id' => $user->id,
                'provider' => $provider,
                'scheduled_at' => $refreshTime->toISOString(),
                'operation_id' => $operationId
            ]);
        }
    }

    /**
     * Handle a token refresh failure with appropriate error classification and notifications
     * 
     * @param User $user The user whose token refresh failed
     * @param string $provider The cloud storage provider
     * @param TokenRefreshErrorType|null $errorType The classified error type
     * @param Exception|null $exception The original exception
     * @return void
     */
    public function handleRefreshFailure(
        User $user, 
        string $provider, 
        ?TokenRefreshErrorType $errorType, 
        ?Exception $exception
    ): void {
        $operationId = uniqid('handle_failure_', true);
        
        Log::error('Handling token refresh failure', [
            'user_id' => $user->id,
            'provider' => $provider,
            'error_type' => $errorType?->value,
            'error_message' => $exception?->getMessage(),
            'operation_id' => $operationId
        ]);

        // Get the token to update failure tracking
        $token = GoogleDriveToken::where('user_id', $user->id)->first();
        if ($token && $exception) {
            $token->markRefreshFailure($exception);
            
            Log::info('Updated token failure tracking', [
                'user_id' => $user->id,
                'provider' => $provider,
                'failure_count' => $token->refresh_failure_count,
                'requires_intervention' => $token->requires_user_intervention,
                'operation_id' => $operationId
            ]);
        }

        // Determine if we should send notifications
        if ($errorType && $this->shouldSendNotification($user, $provider, $errorType)) {
            try {
                $this->sendRefreshFailureNotification($user, $provider, $errorType, $exception);
                
                Log::info('Sent refresh failure notification', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'error_type' => $errorType->value,
                    'operation_id' => $operationId
                ]);
            } catch (Exception $notificationError) {
                Log::error('Failed to send refresh failure notification', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'error_type' => $errorType->value,
                    'notification_error' => $notificationError->getMessage(),
                    'operation_id' => $operationId
                ]);
                
                // Update notification failure count
                if ($token) {
                    $token->increment('notification_failure_count');
                }
            }
        }

        // Schedule retry if the error is recoverable
        if ($errorType && $errorType->isRecoverable() && $token && $token->canBeRefreshed()) {
            $retryDelay = $errorType->getRetryDelay($token->refresh_failure_count);
            
            if ($retryDelay > 0) {
                Log::info('Scheduling retry for recoverable error', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'error_type' => $errorType->value,
                    'retry_delay_seconds' => $retryDelay,
                    'failure_count' => $token->refresh_failure_count,
                    'operation_id' => $operationId
                ]);
                
                RefreshTokenJob::dispatch($user, $provider)
                    ->delay(now()->addSeconds($retryDelay))
                    ->onQueue('maintenance')
                    ->onConnection(config('queue.default'));
            }
        }
    }

    /**
     * Determine if a notification should be sent for this error type
     * 
     * @param User $user The user
     * @param string $provider The provider
     * @param TokenRefreshErrorType $errorType The error type
     * @return bool True if notification should be sent
     */
    private function shouldSendNotification(User $user, string $provider, TokenRefreshErrorType $errorType): bool
    {
        // Always send notifications for errors that require user intervention
        if ($errorType->requiresUserIntervention()) {
            return true;
        }

        // For recoverable errors, only send notification after multiple failures
        $token = GoogleDriveToken::where('user_id', $user->id)->first();
        if (!$token) {
            return true; // Send notification if we can't check failure count
        }

        // Send notification if we've had 3 or more failures
        return $token->refresh_failure_count >= 3;
    }

    /**
     * Send a notification about token refresh failure
     * 
     * @param User $user The user to notify
     * @param string $provider The provider
     * @param TokenRefreshErrorType $errorType The error type
     * @param Exception|null $exception The original exception
     * @return void
     */
    private function sendRefreshFailureNotification(
        User $user, 
        string $provider, 
        TokenRefreshErrorType $errorType, 
        ?Exception $exception
    ): void {
        // TODO: Implement actual notification sending
        // This will be implemented in a later task (task 8)
        
        Log::info('Token refresh failure notification would be sent', [
            'user_id' => $user->id,
            'provider' => $provider,
            'error_type' => $errorType->value,
            'error_message' => $exception?->getMessage(),
            'notification_message' => $errorType->getNotificationMessage()
        ]);

        // Update the last notification sent timestamp
        $token = GoogleDriveToken::where('user_id', $user->id)->first();
        if ($token) {
            $token->update([
                'last_notification_sent_at' => now()
            ]);
        }
    }

    /**
     * Check if any tokens need proactive refresh and schedule them
     * 
     * This method is intended to be called by scheduled tasks
     * 
     * @return array Summary of tokens processed
     */
    public function scheduleProactiveRefreshForExpiringTokens(): array
    {
        $operationId = uniqid('batch_schedule_', true);
        
        Log::info('Starting batch proactive refresh scheduling', [
            'operation_id' => $operationId
        ]);

        // Find tokens that are expiring within the preemptive window and don't have a refresh scheduled
        $expiringTokens = GoogleDriveToken::where('expires_at', '<=', now()->addMinutes(self::PREEMPTIVE_REFRESH_MINUTES))
            ->where('expires_at', '>', now())
            ->whereNull('proactive_refresh_scheduled_at')
            ->where('requires_user_intervention', false)
            ->whereNotNull('refresh_token')
            ->with('user')
            ->get();

        $scheduled = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($expiringTokens as $token) {
            try {
                if ($token->shouldScheduleProactiveRefresh()) {
                    $this->schedulePreemptiveRefresh($token->user, 'google-drive', $token->expires_at);
                    $scheduled++;
                } else {
                    $skipped++;
                    Log::debug('Skipped token that should not be scheduled', [
                        'user_id' => $token->user_id,
                        'expires_at' => $token->expires_at?->toISOString(),
                        'can_be_refreshed' => $token->canBeRefreshed(),
                        'operation_id' => $operationId
                    ]);
                }
            } catch (Exception $e) {
                $errors++;
                Log::error('Failed to schedule proactive refresh for token', [
                    'user_id' => $token->user_id,
                    'error' => $e->getMessage(),
                    'operation_id' => $operationId
                ]);
            }
        }

        $summary = [
            'total_expiring_tokens' => $expiringTokens->count(),
            'scheduled' => $scheduled,
            'skipped' => $skipped,
            'errors' => $errors,
            'operation_id' => $operationId
        ];

        Log::info('Completed batch proactive refresh scheduling', $summary);

        return $summary;
    }
}