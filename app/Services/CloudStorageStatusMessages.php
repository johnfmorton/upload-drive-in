<?php

namespace App\Services;

/**
 * Centralized cloud storage status message constants and configuration
 * 
 * This class provides consistent message definitions for cloud storage status displays,
 * dynamic retry time message generation, and message validation to ensure all status
 * messages are managed in a single location with consistency checking.
 */
class CloudStorageStatusMessages
{
    /**
     * Standard status messages
     */
    public const RATE_LIMITED_MESSAGE = 'messages.cloud_storage_rate_limited';
    public const AUTH_REQUIRED_MESSAGE = 'messages.cloud_storage_auth_required';
    public const CONNECTION_HEALTHY_MESSAGE = 'messages.cloud_storage_connection_healthy';
    public const NOT_CONNECTED_MESSAGE = 'messages.cloud_storage_not_connected';
    public const CONNECTION_ISSUES_MESSAGE = 'messages.cloud_storage_connection_issues';
    public const MULTIPLE_FAILURES_MESSAGE = 'messages.cloud_storage_multiple_failures';
    public const STATUS_UNKNOWN_MESSAGE = 'messages.cloud_storage_status_unknown';

    /**
     * Additional status message constants for specific scenarios
     */
    public const PERSISTENT_FAILURES_MESSAGE = 'messages.cloud_storage_persistent_failures';
    public const TOKEN_REFRESH_ATTEMPTS_MESSAGE = 'messages.cloud_storage_multiple_token_refresh_attempts';
    public const RETRY_WITH_TIME_MESSAGE = 'messages.cloud_storage_retry_with_time';

    /**
     * Message priority levels for consistent display
     */
    public const PRIORITY_CRITICAL = 1;    // Rate limiting, blocking issues
    public const PRIORITY_HIGH = 2;        // Authentication required
    public const PRIORITY_MEDIUM = 3;      // Storage/quota issues
    public const PRIORITY_LOW = 4;         // Network/service issues
    public const PRIORITY_INFO = 5;        // General connection issues

    /**
     * Generate dynamic retry time message for rate limiting scenarios
     *
     * @param int $retryAfterSeconds Number of seconds until retry is allowed
     * @return string Formatted retry time message with specific timing
     */
    public static function getRetryTimeMessage(int $retryAfterSeconds): string
    {
        if ($retryAfterSeconds <= 0) {
            return __(self::RATE_LIMITED_MESSAGE);
        }

        $minutes = ceil($retryAfterSeconds / 60);
        
        // For very short waits (less than 2 minutes), show seconds
        if ($retryAfterSeconds < 120) {
            $seconds = $retryAfterSeconds;
            return trans_choice('messages.cloud_storage_retry_seconds_message', $seconds, ['seconds' => $seconds]);
        }
        
        // For longer waits, show minutes
        return trans_choice('messages.cloud_storage_retry_time_message', $minutes, ['minutes' => $minutes]);
    }

    /**
     * Generate dynamic retry time message with context awareness
     *
     * @param int $retryAfterSeconds Number of seconds until retry is allowed
     * @param array $context Additional context for message customization
     * @return string Contextual retry time message
     */
    public static function getContextualRetryTimeMessage(int $retryAfterSeconds, array $context = []): string
    {
        $consecutiveFailures = $context['consecutive_failures'] ?? 0;
        $provider = $context['provider'] ?? null;
        
        if ($retryAfterSeconds <= 0) {
            return __(self::RATE_LIMITED_MESSAGE);
        }

        $minutes = ceil($retryAfterSeconds / 60);
        
        // Provide more specific messaging based on failure count
        if ($consecutiveFailures > 5) {
            return trans_choice('messages.cloud_storage_retry_persistent_message', $minutes, [
                'minutes' => $minutes,
                'provider' => $provider ? self::getProviderDisplayName($provider) : 'cloud storage'
            ]);
        }
        
        if ($consecutiveFailures > 3) {
            return trans_choice('messages.cloud_storage_retry_multiple_message', $minutes, [
                'minutes' => $minutes
            ]);
        }
        
        return self::getRetryTimeMessage($retryAfterSeconds);
    }

    /**
     * Get message for consecutive failures
     *
     * @param int $failureCount Number of consecutive failures
     * @return string Appropriate message based on failure count
     */
    public static function getConsecutiveFailureMessage(int $failureCount): string
    {
        if ($failureCount > 3) {
            return __(self::MULTIPLE_FAILURES_MESSAGE);
        }
        
        return __(self::CONNECTION_ISSUES_MESSAGE);
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
            '/^Connection issues detected - please check your network and try again$/i',
            '/^Generic connection error$/i',
            '/^Please try again$/i', // When used alone without context
            '/^Error occurred$/i',
            '/^Something went wrong$/i',
            '/^Try again later$/i', // Without specific timing
            '/^Connection failed$/i', // Too generic
            '/^Network error$/i', // Too generic without context
        ];
        
        foreach ($deprecatedPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return false;
            }
        }
        
        // Check for required elements in specific message types
        if (self::isRateLimitMessage($message)) {
            return self::validateRateLimitMessage($message);
        }
        
        if (self::isAuthenticationMessage($message)) {
            return self::validateAuthenticationMessage($message);
        }
        
        return true;
    }

    /**
     * Enhanced message validation with detailed feedback
     *
     * @param string $message The message to validate
     * @return array Validation result with details
     */
    public static function validateMessageWithDetails(string $message): array
    {
        $isValid = self::validateMessageConsistency($message);
        $issues = [];
        $suggestions = [];
        
        if (!$isValid) {
            $issues = self::identifyMessageIssues($message);
            $suggestions = self::getMessageSuggestions($message);
        }
        
        return [
            'is_valid' => $isValid,
            'issues' => $issues,
            'suggestions' => $suggestions,
            'message_type' => self::identifyMessageType($message),
            'priority_level' => self::getMessagePriority($message),
        ];
    }

    /**
     * Check if message contains redundant information
     *
     * @param string $message The message to check
     * @param array $context Context to check for redundancy
     * @return bool Whether the message contains redundant information
     */
    public static function hasRedundantInformation(string $message, array $context = []): bool
    {
        $connectionStatus = $context['connection_status'] ?? null;
        $consolidatedStatus = $context['consolidated_status'] ?? null;
        
        // Check for contradictory messaging
        if ($connectionStatus === 'connected' && str_contains(strtolower($message), 'connection issue')) {
            return true;
        }
        
        if ($consolidatedStatus === 'healthy' && str_contains(strtolower($message), 'error')) {
            return true;
        }
        
        // Check for duplicate information in context
        if (isset($context['status_badge']) && isset($context['health_message'])) {
            $badgeText = strtolower($context['status_badge']);
            $messageText = strtolower($message);
            
            // If badge says "connected" and message says "connection issues"
            if (str_contains($badgeText, 'connected') && str_contains($messageText, 'connection issue')) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get all standard messages for reference
     *
     * @return array Array of all standard messages with their priorities
     */
    public static function getAllMessages(): array
    {
        return [
            'rate_limited' => [
                'message' => __(self::RATE_LIMITED_MESSAGE),
                'priority' => self::PRIORITY_CRITICAL,
                'requires_action' => true,
            ],
            'auth_required' => [
                'message' => __(self::AUTH_REQUIRED_MESSAGE),
                'priority' => self::PRIORITY_HIGH,
                'requires_action' => true,
            ],
            'connection_healthy' => [
                'message' => __(self::CONNECTION_HEALTHY_MESSAGE),
                'priority' => self::PRIORITY_INFO,
                'requires_action' => false,
            ],
            'not_connected' => [
                'message' => __(self::NOT_CONNECTED_MESSAGE),
                'priority' => self::PRIORITY_HIGH,
                'requires_action' => true,
            ],
            'connection_issues' => [
                'message' => __(self::CONNECTION_ISSUES_MESSAGE),
                'priority' => self::PRIORITY_LOW,
                'requires_action' => false,
            ],
            'multiple_failures' => [
                'message' => __(self::MULTIPLE_FAILURES_MESSAGE),
                'priority' => self::PRIORITY_MEDIUM,
                'requires_action' => true,
            ],
            'status_unknown' => [
                'message' => __(self::STATUS_UNKNOWN_MESSAGE),
                'priority' => self::PRIORITY_LOW,
                'requires_action' => false,
            ],
        ];
    }

    /**
     * Get message configuration for a specific message type
     *
     * @param string $messageType The type of message
     * @return array|null Message configuration or null if not found
     */
    public static function getMessageConfig(string $messageType): ?array
    {
        $messages = self::getAllMessages();
        return $messages[$messageType] ?? null;
    }

    /**
     * Get provider display name for consistent messaging
     *
     * @param string $provider The provider identifier
     * @return string User-friendly provider name
     */
    private static function getProviderDisplayName(string $provider): string
    {
        return match ($provider) {
            'google-drive' => 'Google Drive',
            'amazon-s3' => 'Amazon S3',
            'azure-blob' => 'Azure Blob Storage',
            'microsoft-teams' => 'Microsoft Teams',
            'dropbox' => 'Dropbox',
            'onedrive' => 'OneDrive',
            default => ucfirst(str_replace('-', ' ', $provider))
        };
    }

    /**
     * Check if a message is a rate limit message
     *
     * @param string $message The message to check
     * @return bool Whether the message is rate limit related
     */
    private static function isRateLimitMessage(string $message): bool
    {
        $rateLimitPatterns = [
            '/too many.*attempts/i',
            '/rate limit/i',
            '/try again.*later/i',
            '/wait.*minutes/i',
        ];
        
        foreach ($rateLimitPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if a message is an authentication message
     *
     * @param string $message The message to check
     * @return bool Whether the message is authentication related
     */
    private static function isAuthenticationMessage(string $message): bool
    {
        $authPatterns = [
            '/authentication.*required/i',
            '/reconnect.*account/i',
            '/invalid.*credentials/i',
            '/token.*expired/i',
        ];
        
        foreach ($authPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Validate rate limit message format
     *
     * @param string $message The rate limit message to validate
     * @return bool Whether the message is properly formatted
     */
    private static function validateRateLimitMessage(string $message): bool
    {
        // Rate limit messages should include timing information or clear guidance
        $validPatterns = [
            '/try again in \d+ minute/i',
            '/wait \d+ minute/i',
            '/try again later/i',
        ];
        
        foreach ($validPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Validate authentication message format
     *
     * @param string $message The authentication message to validate
     * @return bool Whether the message is properly formatted
     */
    private static function validateAuthenticationMessage(string $message): bool
    {
        // Authentication messages should provide clear action
        $validPatterns = [
            '/reconnect.*account/i',
            '/authentication.*required/i',
        ];
        
        foreach ($validPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Identify specific issues with a message
     *
     * @param string $message The message to analyze
     * @return array List of identified issues
     */
    private static function identifyMessageIssues(string $message): array
    {
        $issues = [];
        
        if (preg_match('/^Connection issues detected - please check your network and try again$/i', $message)) {
            $issues[] = 'Generic connection message without specific guidance';
        }
        
        if (preg_match('/^Please try again$/i', $message)) {
            $issues[] = 'Vague instruction without context or timing';
        }
        
        if (preg_match('/^Error occurred$/i', $message)) {
            $issues[] = 'Non-descriptive error message';
        }
        
        return $issues;
    }

    /**
     * Get suggestions for improving a message
     *
     * @param string $message The message to improve
     * @return array List of suggestions
     */
    private static function getMessageSuggestions(string $message): array
    {
        $suggestions = [];
        
        if (preg_match('/connection issues/i', $message)) {
            $suggestions[] = 'Specify the type of connection issue (authentication, network, etc.)';
            $suggestions[] = 'Provide specific recovery actions';
        }
        
        if (preg_match('/try again/i', $message) && !preg_match('/in \d+ minute/i', $message)) {
            $suggestions[] = 'Include specific timing for retry attempts';
        }
        
        return $suggestions;
    }

    /**
     * Identify the type of message
     *
     * @param string $message The message to classify
     * @return string The message type
     */
    private static function identifyMessageType(string $message): string
    {
        if (self::isRateLimitMessage($message)) {
            return 'rate_limit';
        }
        
        if (self::isAuthenticationMessage($message)) {
            return 'authentication';
        }
        
        if (preg_match('/connected.*working/i', $message)) {
            return 'healthy';
        }
        
        if (preg_match('/not connected/i', $message)) {
            return 'not_connected';
        }
        
        return 'generic';
    }

    /**
     * Get message priority level
     *
     * @param string $message The message to analyze
     * @return int Priority level
     */
    private static function getMessagePriority(string $message): int
    {
        if (self::isRateLimitMessage($message)) {
            return self::PRIORITY_CRITICAL;
        }
        
        if (self::isAuthenticationMessage($message)) {
            return self::PRIORITY_HIGH;
        }
        
        if (preg_match('/quota.*exceeded/i', $message)) {
            return self::PRIORITY_MEDIUM;
        }
        
        if (preg_match('/network.*error|service.*unavailable/i', $message)) {
            return self::PRIORITY_LOW;
        }
        
        return self::PRIORITY_INFO;
    }
}