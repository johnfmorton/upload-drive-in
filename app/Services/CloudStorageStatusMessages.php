<?php

namespace App\Services;

/**
 * Centralized cloud storage status message constants
 * 
 * This class provides consistent message definitions for cloud storage status displays
 * and ensures all status messages are managed in a single location.
 */
class CloudStorageStatusMessages
{
    /**
     * Standard status messages - using translation keys
     */
    public const RATE_LIMITED_MESSAGE = 'cloud_storage_status_rate_limited';
    public const AUTH_REQUIRED_MESSAGE = 'cloud_storage_status_auth_required';
    public const CONNECTION_HEALTHY_MESSAGE = 'cloud_storage_status_connection_healthy';
    public const NOT_CONNECTED_MESSAGE = 'cloud_storage_status_not_connected';
    public const CONNECTION_ISSUES_MESSAGE = 'cloud_storage_status_connection_issues';
    public const MULTIPLE_FAILURES_MESSAGE = 'cloud_storage_status_multiple_failures';
    public const STATUS_UNKNOWN_MESSAGE = 'cloud_storage_status_unknown';

    /**
     * Generate retry time message for rate limiting scenarios
     *
     * @param int $retryAfterSeconds Number of seconds until retry is allowed
     * @return string Formatted retry time message
     */
    public static function getRetryTimeMessage(int $retryAfterSeconds): string
    {
        $minutes = ceil($retryAfterSeconds / 60);
        return __('messages.cloud_storage_retry_time_message', ['minutes' => $minutes]);
    }

    /**
     * Get message key for consecutive failures
     *
     * @param int $failureCount Number of consecutive failures
     * @return string Appropriate message key based on failure count
     */
    public static function getConsecutiveFailureMessage(int $failureCount): string
    {
        if ($failureCount > 3) {
            return self::MULTIPLE_FAILURES_MESSAGE;
        }
        
        return self::CONNECTION_ISSUES_MESSAGE;
    }

    /**
     * Validate that a message is consistent with established patterns
     *
     * @param string $message The message to validate
     * @return bool Whether the message follows established patterns
     */
    public static function validateMessageConsistency(string $message): bool
    {
        // Check for deprecated generic messages that should not be used
        $deprecatedPatterns = [
            '/Connection issues detected - please check your network and try again/i',
            '/Generic connection error/i',
            '/Please try again/i' // When used alone without context
        ];
        
        foreach ($deprecatedPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get all standard messages for reference
     *
     * @return array Array of all standard translated messages
     */
    public static function getAllMessages(): array
    {
        return [
            'rate_limited' => __('messages.' . self::RATE_LIMITED_MESSAGE),
            'auth_required' => __('messages.' . self::AUTH_REQUIRED_MESSAGE),
            'connection_healthy' => __('messages.' . self::CONNECTION_HEALTHY_MESSAGE),
            'not_connected' => __('messages.' . self::NOT_CONNECTED_MESSAGE),
            'connection_issues' => __('messages.' . self::CONNECTION_ISSUES_MESSAGE),
            'multiple_failures' => __('messages.' . self::MULTIPLE_FAILURES_MESSAGE),
            'status_unknown' => __('messages.' . self::STATUS_UNKNOWN_MESSAGE),
        ];
    }
}