<?php

namespace App\Enums;

/**
 * Token refresh specific error types
 * 
 * Categorizes errors that can occur during token refresh operations
 * to enable specific retry logic, user notifications, and recovery strategies
 */
enum TokenRefreshErrorType: string
{
    /**
     * Network timeout during token refresh request
     */
    case NETWORK_TIMEOUT = 'network_timeout';

    /**
     * The refresh token is invalid or malformed
     */
    case INVALID_REFRESH_TOKEN = 'invalid_refresh_token';

    /**
     * The refresh token has expired and cannot be used
     */
    case EXPIRED_REFRESH_TOKEN = 'expired_refresh_token';

    /**
     * API quota or rate limit exceeded during refresh
     */
    case API_QUOTA_EXCEEDED = 'api_quota_exceeded';

    /**
     * OAuth service is temporarily unavailable
     */
    case SERVICE_UNAVAILABLE = 'service_unavailable';

    /**
     * Unknown or unclassified token refresh error
     */
    case UNKNOWN_ERROR = 'unknown_error';

    /**
     * Check if this error type is recoverable through retry
     *
     * @return bool True if the error is potentially recoverable
     */
    public function isRecoverable(): bool
    {
        return match ($this) {
            self::NETWORK_TIMEOUT,
            self::API_QUOTA_EXCEEDED,
            self::SERVICE_UNAVAILABLE => true,
            self::INVALID_REFRESH_TOKEN,
            self::EXPIRED_REFRESH_TOKEN,
            self::UNKNOWN_ERROR => false,
        };
    }

    /**
     * Check if this error type requires user intervention
     *
     * @return bool True if user intervention is required
     */
    public function requiresUserIntervention(): bool
    {
        return match ($this) {
            self::INVALID_REFRESH_TOKEN,
            self::EXPIRED_REFRESH_TOKEN => true,
            self::NETWORK_TIMEOUT,
            self::API_QUOTA_EXCEEDED,
            self::SERVICE_UNAVAILABLE,
            self::UNKNOWN_ERROR => false,
        };
    }

    /**
     * Get the retry delay in seconds for this error type
     *
     * @param int $attempt The current attempt number (1-based)
     * @return int Delay in seconds before next retry
     */
    public function getRetryDelay(int $attempt): int
    {
        return match ($this) {
            self::NETWORK_TIMEOUT => min(pow(2, $attempt - 1), 16), // Exponential backoff: 1s, 2s, 4s, 8s, 16s
            self::API_QUOTA_EXCEEDED => 3600, // 1 hour for quota issues
            self::SERVICE_UNAVAILABLE => min(60 * $attempt, 300), // Linear backoff: 1min, 2min, 3min, max 5min
            self::INVALID_REFRESH_TOKEN,
            self::EXPIRED_REFRESH_TOKEN,
            self::UNKNOWN_ERROR => 0, // No retry for these errors
        };
    }

    /**
     * Get the maximum number of retry attempts for this error type
     *
     * @return int Maximum retry attempts
     */
    public function getMaxRetryAttempts(): int
    {
        return match ($this) {
            self::NETWORK_TIMEOUT => 5,
            self::API_QUOTA_EXCEEDED => 3,
            self::SERVICE_UNAVAILABLE => 3,
            self::INVALID_REFRESH_TOKEN,
            self::EXPIRED_REFRESH_TOKEN,
            self::UNKNOWN_ERROR => 0, // No retries for these errors
        };
    }

    /**
     * Get a human-readable description of the error type
     *
     * @return string Description of the error type
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::NETWORK_TIMEOUT => __('messages.token_refresh_error_network_timeout'),
            self::INVALID_REFRESH_TOKEN => __('messages.token_refresh_error_invalid_refresh_token'),
            self::EXPIRED_REFRESH_TOKEN => __('messages.token_refresh_error_expired_refresh_token'),
            self::API_QUOTA_EXCEEDED => __('messages.token_refresh_error_api_quota_exceeded'),
            self::SERVICE_UNAVAILABLE => __('messages.token_refresh_error_service_unavailable'),
            self::UNKNOWN_ERROR => __('messages.token_refresh_error_unknown_error'),
        };
    }

    /**
     * Get the severity level of this error type
     *
     * @return string Severity level (low, medium, high, critical)
     */
    public function getSeverity(): string
    {
        return match ($this) {
            self::INVALID_REFRESH_TOKEN,
            self::EXPIRED_REFRESH_TOKEN => 'critical', // Requires immediate user action
            self::API_QUOTA_EXCEEDED => 'medium', // Temporary but affects functionality
            self::NETWORK_TIMEOUT,
            self::SERVICE_UNAVAILABLE => 'low', // Temporary and recoverable
            self::UNKNOWN_ERROR => 'high', // Unknown issues need investigation
        };
    }

    /**
     * Check if this error should trigger immediate user notification
     *
     * @return bool True if immediate notification is required
     */
    public function shouldNotifyImmediately(): bool
    {
        return match ($this) {
            self::INVALID_REFRESH_TOKEN,
            self::EXPIRED_REFRESH_TOKEN => true, // User needs to reconnect
            self::NETWORK_TIMEOUT,
            self::API_QUOTA_EXCEEDED,
            self::SERVICE_UNAVAILABLE,
            self::UNKNOWN_ERROR => false, // Wait for retry attempts first
        };
    }

    /**
     * Get the notification message for this error type
     *
     * @return string User-friendly notification message
     */
    public function getNotificationMessage(): string
    {
        return match ($this) {
            self::NETWORK_TIMEOUT => __('messages.token_refresh_notification_network_timeout'),
            self::INVALID_REFRESH_TOKEN => __('messages.token_refresh_notification_invalid_refresh_token'),
            self::EXPIRED_REFRESH_TOKEN => __('messages.token_refresh_notification_expired_refresh_token'),
            self::API_QUOTA_EXCEEDED => __('messages.token_refresh_notification_api_quota_exceeded'),
            self::SERVICE_UNAVAILABLE => __('messages.token_refresh_notification_service_unavailable'),
            self::UNKNOWN_ERROR => __('messages.token_refresh_notification_unknown_error'),
        };
    }
}