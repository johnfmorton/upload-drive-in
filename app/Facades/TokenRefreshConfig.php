<?php

namespace App\Facades;

use App\Services\TokenRefreshConfigService;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for TokenRefreshConfigService.
 * 
 * @method static bool isFeatureEnabled(string $feature)
 * @method static int getTimingConfig(string $key)
 * @method static mixed getNotificationConfig(string $key = null)
 * @method static int getRateLimitConfig(string $key)
 * @method static mixed getSecurityConfig(string $key)
 * @method static mixed getMonitoringConfig(string $key = null)
 * @method static mixed getAdminConfig(string $key = null)
 * @method static bool isProactiveRefreshEnabled()
 * @method static bool isLiveValidationEnabled()
 * @method static bool isAutomaticRecoveryEnabled()
 * @method static bool isBackgroundMaintenanceEnabled()
 * @method static bool isHealthMonitoringEnabled()
 * @method static bool isEnhancedDashboardEnabled()
 * @method static bool isEnhancedLoggingEnabled()
 * @method static int getProactiveRefreshMinutes()
 * @method static int getBackgroundRefreshMinutes()
 * @method static int getMaxRetryAttempts()
 * @method static int getRetryBaseDelay()
 * @method static int getCoordinationLockTtl()
 * @method static int getHealthCacheTtlHealthy()
 * @method static int getHealthCacheTtlError()
 * @method static bool areNotificationsEnabled()
 * @method static int getNotificationThrottleHours()
 * @method static bool isAdminEscalationEnabled()
 * @method static int getMaxNotificationFailures()
 * @method static array getNotificationTemplate(string $type)
 * @method static bool updateConfig(string $key, mixed $value)
 * @method static void clearConfigCache(string $key = null)
 * @method static array getAllFeatureFlags()
 * @method static array getConfigurationSummary()
 * @method static array validateConfiguration()
 * 
 * @see \App\Services\TokenRefreshConfigService
 */
class TokenRefreshConfig extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return TokenRefreshConfigService::class;
    }
}