<?php

namespace App\Services;

use App\Enums\TokenRefreshErrorType;
use App\Mail\TokenExpiredMail;
use App\Mail\TokenRefreshFailedMail;
use App\Mail\ConnectionRestoredMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class TokenRenewalNotificationService
{
    private const NOTIFICATION_THROTTLE_HOURS = 24;
    private const MAX_NOTIFICATION_ATTEMPTS = 3;

    /**
     * Send notification for expired token
     */
    public function sendTokenExpiredNotification(User $user, string $provider): bool
    {
        $notificationType = 'token_expired';
        
        if (!$this->shouldSendNotification($user, $provider, $notificationType)) {
            Log::info('Token expired notification throttled', [
                'user_id' => $user->id,
                'provider' => $provider,
                'type' => $notificationType
            ]);
            return false;
        }

        try {
            Mail::to($user->email)->send(new TokenExpiredMail($user, $provider));
            
            $this->recordNotificationSent($user, $provider, $notificationType);
            
            Log::info('Token expired notification sent successfully', [
                'user_id' => $user->id,
                'provider' => $provider,
                'email' => $user->email
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send token expired notification', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            
            $this->handleNotificationFailure($user, $provider, $notificationType, $e);
            return false;
        }
    }

    /**
     * Send notification for token refresh failure
     */
    public function sendRefreshFailureNotification(
        User $user, 
        string $provider, 
        TokenRefreshErrorType $errorType, 
        int $attemptCount,
        string $errorMessage = null
    ): bool {
        $notificationType = 'refresh_failure';
        
        if (!$this->shouldSendNotification($user, $provider, $notificationType)) {
            Log::info('Refresh failure notification throttled', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error_type' => $errorType->value,
                'attempt_count' => $attemptCount
            ]);
            return false;
        }

        try {
            Mail::to($user->email)->send(
                new TokenRefreshFailedMail($user, $provider, $errorType, $attemptCount, $errorMessage)
            );
            
            $this->recordNotificationSent($user, $provider, $notificationType);
            
            Log::info('Refresh failure notification sent successfully', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error_type' => $errorType->value,
                'attempt_count' => $attemptCount,
                'email' => $user->email
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send refresh failure notification', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error_type' => $errorType->value,
                'error' => $e->getMessage()
            ]);
            
            $this->handleNotificationFailure($user, $provider, $notificationType, $e);
            return false;
        }
    }

    /**
     * Send notification for connection restored
     */
    public function sendConnectionRestoredNotification(User $user, string $provider): bool
    {
        $notificationType = 'connection_restored';
        
        if (!$this->shouldSendNotification($user, $provider, $notificationType)) {
            Log::info('Connection restored notification throttled', [
                'user_id' => $user->id,
                'provider' => $provider,
                'type' => $notificationType
            ]);
            return false;
        }

        try {
            Mail::to($user->email)->send(new ConnectionRestoredMail($user, $provider));
            
            $this->recordNotificationSent($user, $provider, $notificationType);
            
            Log::info('Connection restored notification sent successfully', [
                'user_id' => $user->id,
                'provider' => $provider,
                'email' => $user->email
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send connection restored notification', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            
            $this->handleNotificationFailure($user, $provider, $notificationType, $e);
            return false;
        }
    }

    /**
     * Check if notification should be sent based on throttling rules
     */
    public function shouldSendNotification(User $user, string $provider, string $notificationType): bool
    {
        $cacheKey = $this->getThrottleCacheKey($user, $provider, $notificationType);
        $lastSent = Cache::get($cacheKey);
        
        if (!$lastSent) {
            return true;
        }
        
        $lastSentTime = Carbon::parse($lastSent);
        $throttleUntil = $lastSentTime->addHours(self::NOTIFICATION_THROTTLE_HOURS);
        
        return now()->isAfter($throttleUntil);
    }

    /**
     * Record that a notification was sent for throttling purposes
     */
    private function recordNotificationSent(User $user, string $provider, string $notificationType): void
    {
        $cacheKey = $this->getThrottleCacheKey($user, $provider, $notificationType);
        $ttlSeconds = self::NOTIFICATION_THROTTLE_HOURS * 3600;
        
        Cache::put($cacheKey, now()->toISOString(), $ttlSeconds);
    }

    /**
     * Handle notification failure and escalate to admin if needed
     */
    private function handleNotificationFailure(User $user, string $provider, string $notificationType, \Exception $exception): void
    {
        $failureKey = $this->getFailureCacheKey($user, $provider, $notificationType);
        $failureCount = Cache::get($failureKey, 0) + 1;
        
        Cache::put($failureKey, $failureCount, 3600); // Store for 1 hour
        
        Log::warning('Notification failure recorded', [
            'user_id' => $user->id,
            'provider' => $provider,
            'notification_type' => $notificationType,
            'failure_count' => $failureCount,
            'error' => $exception->getMessage()
        ]);
        
        if ($failureCount >= self::MAX_NOTIFICATION_ATTEMPTS) {
            $this->escalateToAdmin($user, $provider, $notificationType, $failureCount, $exception);
        }
    }

    /**
     * Escalate notification failure to admin users
     */
    private function escalateToAdmin(User $user, string $provider, string $notificationType, int $failureCount, \Exception $exception): void
    {
        $adminUsers = User::where('role', 'admin')->get();
        
        if ($adminUsers->isEmpty()) {
            Log::critical('No admin users found for notification escalation', [
                'user_id' => $user->id,
                'provider' => $provider,
                'notification_type' => $notificationType,
                'failure_count' => $failureCount
            ]);
            return;
        }
        
        foreach ($adminUsers as $admin) {
            try {
                // Send escalation email to admin
                $subject = __('messages.notification_failure_alert_subject', ['email' => $user->email]);
                $message = __('messages.notification_failure_alert_body', [
                    'type' => $notificationType,
                    'email' => $user->email,
                    'provider' => $provider,
                    'attempts' => $failureCount,
                    'error' => $exception->getMessage()
                ]);
                
                Mail::raw($message, function ($mail) use ($admin, $subject) {
                    $mail->to($admin->email)->subject($subject);
                });
                
                Log::info('Notification failure escalated to admin', [
                    'admin_id' => $admin->id,
                    'admin_email' => $admin->email,
                    'original_user_id' => $user->id,
                    'provider' => $provider,
                    'notification_type' => $notificationType
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to escalate notification failure to admin', [
                    'admin_id' => $admin->id,
                    'admin_email' => $admin->email,
                    'original_user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Get cache key for notification throttling
     */
    private function getThrottleCacheKey(User $user, string $provider, string $notificationType): string
    {
        return "notification_throttle_{$user->id}_{$provider}_{$notificationType}";
    }

    /**
     * Get cache key for failure tracking
     */
    private function getFailureCacheKey(User $user, string $provider, string $notificationType): string
    {
        return "notification_failure_{$user->id}_{$provider}_{$notificationType}";
    }

    /**
     * Clear notification throttle for a user and provider (useful for testing)
     */
    public function clearNotificationThrottle(User $user, string $provider, string $notificationType = null): void
    {
        if ($notificationType) {
            $cacheKey = $this->getThrottleCacheKey($user, $provider, $notificationType);
            Cache::forget($cacheKey);
        } else {
            // Clear all notification types for this user/provider
            $types = ['token_expired', 'refresh_failure', 'connection_restored'];
            foreach ($types as $type) {
                $cacheKey = $this->getThrottleCacheKey($user, $provider, $type);
                Cache::forget($cacheKey);
            }
        }
    }

    /**
     * Handle token refresh failure by determining appropriate notification strategy
     */
    public function handleTokenRefreshFailure(
        User $user,
        string $provider,
        TokenRefreshErrorType $errorType,
        ?Exception $exception,
        int $attemptCount
    ): void {
        Log::info('Handling token refresh failure notification', [
            'user_id' => $user->id,
            'provider' => $provider,
            'error_type' => $errorType->value,
            'attempt_count' => $attemptCount,
            'requires_immediate_notification' => $errorType->shouldNotifyImmediately()
        ]);

        // For errors requiring immediate notification, send immediately
        if ($errorType->shouldNotifyImmediately()) {
            if ($errorType === TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN) {
                $this->sendTokenExpiredNotification($user, $provider);
            } else {
                $this->sendRefreshFailureNotification(
                    $user,
                    $provider,
                    $errorType,
                    $attemptCount,
                    $exception?->getMessage()
                );
            }
            return;
        }

        // For recoverable errors, only notify after max attempts reached
        if ($errorType->isRecoverable()) {
            $maxAttempts = $errorType->getMaxRetryAttempts();
            if ($attemptCount >= $maxAttempts) {
                Log::info('Max retry attempts reached, sending failure notification', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'error_type' => $errorType->value,
                    'attempt_count' => $attemptCount,
                    'max_attempts' => $maxAttempts
                ]);

                $this->sendRefreshFailureNotification(
                    $user,
                    $provider,
                    $errorType,
                    $attemptCount,
                    $exception?->getMessage()
                );
            } else {
                Log::info('Recoverable error, not sending notification yet', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'error_type' => $errorType->value,
                    'attempt_count' => $attemptCount,
                    'max_attempts' => $maxAttempts
                ]);
            }
            return;
        }

        // For non-recoverable errors, send notification immediately
        $this->sendRefreshFailureNotification(
            $user,
            $provider,
            $errorType,
            $attemptCount,
            $exception?->getMessage()
        );
    }

    /**
     * Get notification status for a user and provider
     */
    public function getNotificationStatus(User $user, string $provider): array
    {
        $types = ['token_expired', 'refresh_failure', 'connection_restored'];
        $status = [];
        
        foreach ($types as $type) {
            $cacheKey = $this->getThrottleCacheKey($user, $provider, $type);
            $lastSent = Cache::get($cacheKey);
            
            $status[$type] = [
                'last_sent' => $lastSent ? Carbon::parse($lastSent) : null,
                'can_send' => $this->shouldSendNotification($user, $provider, $type),
                'throttled_until' => $lastSent ? 
                    Carbon::parse($lastSent)->addHours(self::NOTIFICATION_THROTTLE_HOURS) : null
            ];
        }
        
        return $status;
    }
}