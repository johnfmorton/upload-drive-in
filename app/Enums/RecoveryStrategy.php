<?php

namespace App\Enums;

/**
 * Enum defining different recovery strategies for cloud storage connection issues.
 */
enum RecoveryStrategy: string
{
    /**
     * Attempt to refresh expired or invalid tokens
     */
    case TOKEN_REFRESH = 'token_refresh';

    /**
     * Retry network operations after temporary connectivity issues
     */
    case NETWORK_RETRY = 'network_retry';

    /**
     * Wait for API quota to be restored
     */
    case QUOTA_WAIT = 'quota_wait';

    /**
     * Retry after service becomes available
     */
    case SERVICE_RETRY = 'service_retry';

    /**
     * Perform general health check and retry
     */
    case HEALTH_CHECK_RETRY = 'health_check_retry';

    /**
     * User intervention is required (manual reconnection, permission changes, etc.)
     */
    case USER_INTERVENTION_REQUIRED = 'user_intervention_required';

    /**
     * No action needed, connection is already healthy
     */
    case NO_ACTION_NEEDED = 'no_action_needed';

    /**
     * Unknown strategy (fallback)
     */
    case UNKNOWN = 'unknown';

    /**
     * Get a human-readable description of the recovery strategy.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::TOKEN_REFRESH => __('messages.recovery_strategy_token_refresh'),
            self::NETWORK_RETRY => __('messages.recovery_strategy_network_retry'),
            self::QUOTA_WAIT => __('messages.recovery_strategy_quota_wait'),
            self::SERVICE_RETRY => __('messages.recovery_strategy_service_retry'),
            self::HEALTH_CHECK_RETRY => __('messages.recovery_strategy_health_check_retry'),
            self::USER_INTERVENTION_REQUIRED => __('messages.recovery_strategy_user_intervention_required'),
            self::NO_ACTION_NEEDED => __('messages.recovery_strategy_no_action_needed'),
            self::UNKNOWN => __('messages.recovery_strategy_unknown'),
        };
    }

    /**
     * Check if this strategy requires user intervention.
     */
    public function requiresUserIntervention(): bool
    {
        return $this === self::USER_INTERVENTION_REQUIRED;
    }

    /**
     * Check if this strategy can be automated.
     */
    public function isAutomated(): bool
    {
        return match ($this) {
            self::TOKEN_REFRESH,
            self::NETWORK_RETRY,
            self::QUOTA_WAIT,
            self::SERVICE_RETRY,
            self::HEALTH_CHECK_RETRY,
            self::NO_ACTION_NEEDED => true,
            self::USER_INTERVENTION_REQUIRED,
            self::UNKNOWN => false,
        };
    }

    /**
     * Get the expected recovery time in seconds (approximate).
     */
    public function getExpectedRecoveryTime(): int
    {
        return match ($this) {
            self::TOKEN_REFRESH => 30, // Token refresh usually takes seconds
            self::NETWORK_RETRY => 60, // Network issues may resolve quickly
            self::QUOTA_WAIT => 3600, // API quotas often reset hourly
            self::SERVICE_RETRY => 300, // Service outages vary, assume 5 minutes
            self::HEALTH_CHECK_RETRY => 30, // Quick health check
            self::NO_ACTION_NEEDED => 0, // Immediate
            self::USER_INTERVENTION_REQUIRED => -1, // Indefinite
            self::UNKNOWN => -1, // Unknown
        };
    }

    /**
     * Get the priority level for this recovery strategy (lower number = higher priority).
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::NO_ACTION_NEEDED => 1, // Highest priority - no work needed
            self::TOKEN_REFRESH => 2, // High priority - quick automated fix
            self::HEALTH_CHECK_RETRY => 3, // Medium-high priority - quick check
            self::NETWORK_RETRY => 4, // Medium priority - may resolve quickly
            self::SERVICE_RETRY => 5, // Medium-low priority - depends on external service
            self::QUOTA_WAIT => 6, // Low priority - may take time
            self::USER_INTERVENTION_REQUIRED => 7, // Lowest automated priority
            self::UNKNOWN => 8, // Lowest priority - unknown outcome
        };
    }
}