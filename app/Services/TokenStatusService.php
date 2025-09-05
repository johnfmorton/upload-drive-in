<?php

namespace App\Services;

use App\Models\User;
use App\Models\GoogleDriveToken;
use Carbon\Carbon;

/**
 * Service for retrieving comprehensive token status information for dashboard display.
 * Provides detailed token lifecycle information including expiration, renewal schedules, and health indicators.
 */
class TokenStatusService
{
    public function __construct(
        private readonly ProactiveRefreshScheduler $refreshScheduler
    ) {}

    /**
     * Get comprehensive token status for a user and provider.
     * 
     * @param User $user
     * @param string $provider
     * @return array|null
     */
    public function getTokenStatus(User $user, string $provider): ?array
    {
        if ($provider !== 'google-drive') {
            // For now, only Google Drive tokens are supported
            return null;
        }

        $token = GoogleDriveToken::where('user_id', $user->id)->first();
        
        if (!$token) {
            return [
                'exists' => false,
                'status' => 'not_connected',
                'message' => __('messages.token_status_not_connected'),
            ];
        }

        return $this->buildTokenStatusArray($token);
    }

    /**
     * Build comprehensive token status array.
     * 
     * @param GoogleDriveToken $token
     * @return array
     */
    private function buildTokenStatusArray(GoogleDriveToken $token): array
    {
        $now = now();
        $expiresAt = $token->expires_at;
        $isExpired = $expiresAt && $expiresAt->isPast();
        $isExpiringSoon = $token->isExpiringSoon(15); // 15 minutes
        $canBeRefreshed = $token->canBeRefreshed();

        // Calculate time until expiration
        $timeUntilExpiration = null;
        $timeUntilExpirationHuman = null;
        if ($expiresAt && !$isExpired) {
            $timeUntilExpiration = $expiresAt->diffInSeconds($now);
            $timeUntilExpirationHuman = $this->formatTimeUntilExpiration($expiresAt, $now);
        }

        // Calculate next renewal time
        $nextRenewalTime = null;
        $nextRenewalTimeHuman = null;
        if ($expiresAt && !$isExpired && $canBeRefreshed) {
            $nextRenewalTime = $expiresAt->copy()->subMinutes(15);
            if ($nextRenewalTime->isFuture()) {
                $nextRenewalTimeHuman = $nextRenewalTime->format('M j, Y \a\t g:i A');
            } else {
                $nextRenewalTimeHuman = __('messages.token_status_scheduled_now');
            }
        }

        // Determine overall token health
        $tokenHealth = $this->determineTokenHealth($token, $isExpired, $isExpiringSoon, $canBeRefreshed);

        // Get last error information
        $lastError = $this->getLastRefreshError($token);

        return [
            'exists' => true,
            'status' => $tokenHealth['status'],
            'health_indicator' => $tokenHealth['indicator'],
            'message' => $tokenHealth['message'],
            
            // Token lifecycle information
            'issued_at' => $token->created_at,
            'issued_at_human' => $token->created_at->format('M j, Y \a\t g:i A'),
            'issued_ago_human' => $token->created_at->diffForHumans(),
            
            'expires_at' => $expiresAt,
            'expires_at_human' => $expiresAt ? $expiresAt->format('M j, Y \a\t g:i A') : null,
            'expires_in_human' => $timeUntilExpirationHuman,
            'time_until_expiration_seconds' => $timeUntilExpiration,
            
            'is_expired' => $isExpired,
            'is_expiring_soon' => $isExpiringSoon,
            'can_be_refreshed' => $canBeRefreshed,
            'can_manually_refresh' => !$token->requires_user_intervention && $token->refresh_token,
            
            // Renewal information
            'next_renewal_at' => $nextRenewalTime,
            'next_renewal_at_human' => $nextRenewalTimeHuman,
            'proactive_refresh_scheduled_at' => $token->proactive_refresh_scheduled_at,
            
            // Refresh history
            'last_refresh_attempt_at' => $token->last_refresh_attempt_at,
            'last_refresh_attempt_human' => $token->last_refresh_attempt_at?->diffForHumans(),
            'last_successful_refresh_at' => $token->last_successful_refresh_at,
            'last_successful_refresh_human' => $token->last_successful_refresh_at?->diffForHumans(),
            'refresh_failure_count' => $token->refresh_failure_count,
            'last_error' => $lastError,
            
            // User intervention status
            'requires_user_intervention' => $token->requires_user_intervention,
            'health_check_failures' => $token->health_check_failures,
            
            // Notification status
            'last_notification_sent_at' => $token->last_notification_sent_at,
            'notification_failure_count' => $token->notification_failure_count,
            
            // Scopes and permissions
            'scopes' => $token->scopes,
            'token_type' => $token->token_type,
            
            // Validation timestamp for real-time updates
            'validated_at' => $now->toISOString(),
        ];
    }

    /**
     * Determine token health status and indicators.
     * 
     * @param GoogleDriveToken $token
     * @param bool $isExpired
     * @param bool $isExpiringSoon
     * @param bool $canBeRefreshed
     * @return array
     */
    private function determineTokenHealth(GoogleDriveToken $token, bool $isExpired, bool $isExpiringSoon, bool $canBeRefreshed): array
    {
        if ($token->requires_user_intervention) {
            return [
                'status' => 'requires_intervention',
                'indicator' => 'red',
                'message' => __('messages.token_status_requires_intervention'),
            ];
        }

        if ($isExpired) {
            if ($canBeRefreshed) {
                return [
                    'status' => 'expired_refreshable',
                    'indicator' => 'yellow',
                    'message' => __('messages.token_status_expired_refreshable'),
                ];
            } else {
                return [
                    'status' => 'expired_manual',
                    'indicator' => 'red',
                    'message' => __('messages.token_status_expired_manual'),
                ];
            }
        }

        if ($isExpiringSoon) {
            return [
                'status' => 'expiring_soon',
                'indicator' => 'yellow',
                'message' => __('messages.token_status_expiring_soon'),
            ];
        }

        if ($token->refresh_failure_count > 0) {
            return [
                'status' => 'healthy_with_warnings',
                'indicator' => 'yellow',
                'message' => __('messages.token_status_healthy_with_warnings', ['count' => $token->refresh_failure_count]),
            ];
        }

        return [
            'status' => 'healthy',
            'indicator' => 'green',
            'message' => __('messages.token_status_healthy'),
        ];
    }

    /**
     * Format time until expiration in a human-readable way.
     * 
     * @param Carbon $expiresAt
     * @param Carbon $now
     * @return string
     */
    private function formatTimeUntilExpiration(Carbon $expiresAt, Carbon $now): string
    {
        $diffInSeconds = $expiresAt->diffInSeconds($now);
        
        if ($diffInSeconds < 60) {
            return __('messages.token_status_less_than_minute');
        }
        
        if ($diffInSeconds < 3600) {
            $minutes = floor($diffInSeconds / 60);
            $minuteWord = $minutes === 1 ? __('messages.token_status_minute') : __('messages.token_status_minutes');
            return $minutes . ' ' . $minuteWord;
        }
        
        if ($diffInSeconds < 86400) {
            $hours = floor($diffInSeconds / 3600);
            $minutes = floor(($diffInSeconds % 3600) / 60);
            
            $hourWord = $hours === 1 ? __('messages.token_status_hour') : __('messages.token_status_hours');
            $result = $hours . ' ' . $hourWord;
            
            if ($minutes > 0) {
                $minuteWord = $minutes === 1 ? __('messages.token_status_minute') : __('messages.token_status_minutes');
                $result .= ' ' . $minutes . ' ' . $minuteWord;
            }
            return $result;
        }
        
        $days = floor($diffInSeconds / 86400);
        $hours = floor(($diffInSeconds % 86400) / 3600);
        
        $dayWord = $days === 1 ? __('messages.token_status_day') : __('messages.token_status_days');
        $result = $days . ' ' . $dayWord;
        
        if ($hours > 0) {
            $hourWord = $hours === 1 ? __('messages.token_status_hour') : __('messages.token_status_hours');
            $result .= ' ' . $hours . ' ' . $hourWord;
        }
        return $result;
    }

    /**
     * Get token status for multiple providers.
     * 
     * @param User $user
     * @param array $providers
     * @return array
     */
    public function getMultipleTokenStatuses(User $user, array $providers): array
    {
        $statuses = [];
        
        foreach ($providers as $provider) {
            $statuses[$provider] = $this->getTokenStatus($user, $provider);
        }
        
        return $statuses;
    }

    /**
     * Check if any tokens need immediate attention.
     * 
     * @param User $user
     * @param array $providers
     * @return array
     */
    public function getTokensNeedingAttention(User $user, array $providers): array
    {
        $needingAttention = [];
        
        foreach ($providers as $provider) {
            $status = $this->getTokenStatus($user, $provider);
            
            if ($status && in_array($status['status'], ['requires_intervention', 'expired_manual'])) {
                $needingAttention[$provider] = $status;
            }
        }
        
        return $needingAttention;
    }

    /**
     * Check if manual refresh is available for a token.
     * 
     * @param User $user
     * @param string $provider
     * @return bool
     */
    public function canManuallyRefresh(User $user, string $provider): bool
    {
        if ($provider !== 'google-drive') {
            return false;
        }

        $token = GoogleDriveToken::where('user_id', $user->id)->first();
        
        if (!$token) {
            return false;
        }

        // Can manually refresh if token exists and doesn't require user intervention
        return !$token->requires_user_intervention && $token->refresh_token;
    }

    /**
     * Get detailed error information for the last refresh failure.
     * 
     * @param GoogleDriveToken $token
     * @return array|null
     */
    private function getLastRefreshError(GoogleDriveToken $token): ?array
    {
        if ($token->refresh_failure_count === 0) {
            return null;
        }

        // This would ideally come from a separate error log table
        // For now, we'll provide generic error information based on the token state
        if ($token->requires_user_intervention) {
            return [
                'type' => 'requires_intervention',
                'message' => __('messages.token_status_last_error_intervention'),
                'occurred_at' => $token->last_refresh_attempt_at,
            ];
        }

        return [
            'type' => 'refresh_failure',
            'message' => __('messages.token_status_last_error_generic'),
            'occurred_at' => $token->last_refresh_attempt_at,
        ];
    }
}