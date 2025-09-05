<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing token refresh configuration and feature flags.
 * 
 * This service provides a centralized way to access configuration values
 * with environment-specific overrides and runtime caching.
 */
class TokenRefreshConfigService
{
    private const CACHE_PREFIX = 'token_refresh_config:';
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Check if a feature is enabled.
     */
    public function isFeatureEnabled(string $feature): bool
    {
        $cacheKey = self::CACHE_PREFIX . "feature:{$feature}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($feature) {
            // Check environment-specific override first
            $envConfig = $this->getEnvironmentConfig();
            if (isset($envConfig['features'][$feature])) {
                return (bool) $envConfig['features'][$feature];
            }
            
            // Fall back to default configuration
            return (bool) config("token-refresh.features.{$feature}", false);
        });
    }

    /**
     * Get timing configuration value.
     */
    public function getTimingConfig(string $key): int
    {
        $cacheKey = self::CACHE_PREFIX . "timing:{$key}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key) {
            // Check environment-specific override first
            $envConfig = $this->getEnvironmentConfig();
            if (isset($envConfig['timing'][$key])) {
                return (int) $envConfig['timing'][$key];
            }
            
            // Fall back to default configuration
            return (int) config("token-refresh.timing.{$key}", 0);
        });
    }

    /**
     * Get notification configuration.
     */
    public function getNotificationConfig(string $key = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX . "notifications:" . ($key ?? 'all');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key) {
            // Check environment-specific override first
            $envConfig = $this->getEnvironmentConfig();
            if (isset($envConfig['notifications'])) {
                $notifications = array_merge(
                    config('token-refresh.notifications', []),
                    $envConfig['notifications']
                );
            } else {
                $notifications = config('token-refresh.notifications', []);
            }
            
            return $key ? ($notifications[$key] ?? null) : $notifications;
        });
    }

    /**
     * Get rate limiting configuration.
     */
    public function getRateLimitConfig(string $key): int
    {
        $cacheKey = self::CACHE_PREFIX . "rate_limit:{$key}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key) {
            return (int) config("token-refresh.rate_limiting.{$key}", 0);
        });
    }

    /**
     * Get security configuration.
     */
    public function getSecurityConfig(string $key): mixed
    {
        $cacheKey = self::CACHE_PREFIX . "security:{$key}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key) {
            return config("token-refresh.security.{$key}");
        });
    }

    /**
     * Get monitoring configuration.
     */
    public function getMonitoringConfig(string $key = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX . "monitoring:" . ($key ?? 'all');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key) {
            $monitoring = config('token-refresh.monitoring', []);
            return $key ? ($monitoring[$key] ?? null) : $monitoring;
        });
    }

    /**
     * Get admin interface configuration.
     */
    public function getAdminConfig(string $key = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX . "admin:" . ($key ?? 'all');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key) {
            $admin = config('token-refresh.admin_interface', []);
            return $key ? ($admin[$key] ?? null) : $admin;
        });
    }

    /**
     * Check if proactive refresh is enabled.
     */
    public function isProactiveRefreshEnabled(): bool
    {
        return $this->isFeatureEnabled('proactive_refresh');
    }

    /**
     * Check if live validation is enabled.
     */
    public function isLiveValidationEnabled(): bool
    {
        return $this->isFeatureEnabled('live_validation');
    }

    /**
     * Check if automatic recovery is enabled.
     */
    public function isAutomaticRecoveryEnabled(): bool
    {
        return $this->isFeatureEnabled('automatic_recovery');
    }

    /**
     * Check if background maintenance is enabled.
     */
    public function isBackgroundMaintenanceEnabled(): bool
    {
        return $this->isFeatureEnabled('background_maintenance');
    }

    /**
     * Check if health monitoring is enabled.
     */
    public function isHealthMonitoringEnabled(): bool
    {
        return $this->isFeatureEnabled('health_monitoring');
    }

    /**
     * Check if enhanced dashboard is enabled.
     */
    public function isEnhancedDashboardEnabled(): bool
    {
        return $this->isFeatureEnabled('enhanced_dashboard');
    }

    /**
     * Check if enhanced logging is enabled.
     */
    public function isEnhancedLoggingEnabled(): bool
    {
        return $this->isFeatureEnabled('enhanced_logging');
    }

    /**
     * Get proactive refresh timing in minutes.
     */
    public function getProactiveRefreshMinutes(): int
    {
        return $this->getTimingConfig('proactive_refresh_minutes');
    }

    /**
     * Get background refresh timing in minutes.
     */
    public function getBackgroundRefreshMinutes(): int
    {
        return $this->getTimingConfig('background_refresh_minutes');
    }

    /**
     * Get maximum retry attempts.
     */
    public function getMaxRetryAttempts(): int
    {
        return $this->getTimingConfig('max_retry_attempts');
    }

    /**
     * Get retry base delay in seconds.
     */
    public function getRetryBaseDelay(): int
    {
        return $this->getTimingConfig('retry_base_delay_seconds');
    }

    /**
     * Get coordination lock TTL in seconds.
     */
    public function getCoordinationLockTtl(): int
    {
        return $this->getTimingConfig('coordination_lock_ttl');
    }

    /**
     * Get health cache TTL for healthy status.
     */
    public function getHealthCacheTtlHealthy(): int
    {
        return $this->getTimingConfig('health_cache_ttl_healthy');
    }

    /**
     * Get health cache TTL for error status.
     */
    public function getHealthCacheTtlError(): int
    {
        return $this->getTimingConfig('health_cache_ttl_error');
    }

    /**
     * Check if notifications are enabled.
     */
    public function areNotificationsEnabled(): bool
    {
        return (bool) $this->getNotificationConfig('enabled');
    }

    /**
     * Get notification throttle hours.
     */
    public function getNotificationThrottleHours(): int
    {
        return (int) $this->getNotificationConfig('throttle_hours');
    }

    /**
     * Check if admin escalation is enabled.
     */
    public function isAdminEscalationEnabled(): bool
    {
        return (bool) $this->getNotificationConfig('escalate_to_admin');
    }

    /**
     * Get maximum notification failures before escalation.
     */
    public function getMaxNotificationFailures(): int
    {
        return (int) $this->getNotificationConfig('max_notification_failures');
    }

    /**
     * Get notification template configuration.
     */
    public function getNotificationTemplate(string $type): array
    {
        $templates = $this->getNotificationConfig('templates');
        return $templates[$type] ?? [];
    }

    /**
     * Update a configuration value at runtime (if allowed).
     */
    public function updateConfig(string $key, mixed $value): bool
    {
        if (!$this->getAdminConfig('allow_runtime_changes')) {
            Log::warning('Runtime configuration changes are disabled', [
                'key' => $key,
                'value' => $value,
            ]);
            return false;
        }

        $modifiableSettings = $this->getAdminConfig('modifiable_settings') ?? [];
        if (!in_array($key, $modifiableSettings)) {
            Log::warning('Configuration key is not modifiable', [
                'key' => $key,
                'modifiable_settings' => $modifiableSettings,
            ]);
            return false;
        }

        try {
            // Update the configuration
            Config::set("token-refresh.{$key}", $value);
            
            // Clear related cache
            $this->clearConfigCache($key);
            
            Log::info('Configuration updated at runtime', [
                'key' => $key,
                'value' => $value,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update configuration', [
                'key' => $key,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Clear configuration cache.
     */
    public function clearConfigCache(string $key = null): void
    {
        if ($key) {
            // Clear specific cache entries related to the key
            $parts = explode('.', $key);
            $section = $parts[0] ?? '';
            $subKey = isset($parts[1]) ? $parts[1] : '';
            
            // Clear the specific cache entry
            Cache::forget(self::CACHE_PREFIX . "{$section}:{$subKey}");
        } else {
            // Clear all token refresh configuration cache by flushing all cache
            // In a real implementation, you might want to use cache tags or a more specific approach
            Cache::flush();
        }
    }

    /**
     * Get environment-specific configuration.
     */
    private function getEnvironmentConfig(): array
    {
        $environment = app()->environment();
        return config("token-refresh.environments.{$environment}", []);
    }

    /**
     * Get all feature flags status.
     */
    public function getAllFeatureFlags(): array
    {
        $features = config('token-refresh.features', []);
        $envConfig = $this->getEnvironmentConfig();
        
        if (isset($envConfig['features'])) {
            $features = array_merge($features, $envConfig['features']);
        }
        
        return $features;
    }

    /**
     * Get configuration summary for admin interface.
     */
    public function getConfigurationSummary(): array
    {
        return [
            'features' => $this->getAllFeatureFlags(),
            'timing' => [
                'proactive_refresh_minutes' => $this->getProactiveRefreshMinutes(),
                'background_refresh_minutes' => $this->getBackgroundRefreshMinutes(),
                'max_retry_attempts' => $this->getMaxRetryAttempts(),
                'retry_base_delay_seconds' => $this->getRetryBaseDelay(),
                'coordination_lock_ttl' => $this->getCoordinationLockTtl(),
                'health_cache_ttl_healthy' => $this->getHealthCacheTtlHealthy(),
                'health_cache_ttl_error' => $this->getHealthCacheTtlError(),
            ],
            'notifications' => [
                'enabled' => $this->areNotificationsEnabled(),
                'throttle_hours' => $this->getNotificationThrottleHours(),
                'escalate_to_admin' => $this->isAdminEscalationEnabled(),
                'max_notification_failures' => $this->getMaxNotificationFailures(),
            ],
            'rate_limiting' => [
                'max_attempts_per_hour' => $this->getRateLimitConfig('max_attempts_per_hour'),
                'max_health_checks_per_minute' => $this->getRateLimitConfig('max_health_checks_per_minute'),
                'ip_based_limiting' => $this->getRateLimitConfig('ip_based_limiting'),
                'max_requests_per_ip_per_hour' => $this->getRateLimitConfig('max_requests_per_ip_per_hour'),
            ],
            'security' => [
                'token_rotation' => $this->getSecurityConfig('token_rotation'),
                'audit_logging' => $this->getSecurityConfig('audit_logging'),
                'security_log_level' => $this->getSecurityConfig('security_log_level'),
                'detailed_error_logging' => $this->getSecurityConfig('detailed_error_logging'),
            ],
            'environment' => app()->environment(),
        ];
    }

    /**
     * Validate configuration values.
     */
    public function validateConfiguration(): array
    {
        $errors = [];
        
        // Validate timing values
        if ($this->getProactiveRefreshMinutes() < 1) {
            $errors[] = __('messages.token_config_proactive_refresh_minutes_min');
        }
        
        if ($this->getMaxRetryAttempts() < 1 || $this->getMaxRetryAttempts() > 10) {
            $errors[] = __('messages.token_config_max_retry_attempts_range');
        }
        
        if ($this->getRetryBaseDelay() < 1) {
            $errors[] = __('messages.token_config_retry_base_delay_min');
        }
        
        if ($this->getNotificationThrottleHours() < 1) {
            $errors[] = __('messages.token_config_notification_throttle_hours_min');
        }
        
        // Validate rate limiting
        if ($this->getRateLimitConfig('max_attempts_per_hour') < 1) {
            $errors[] = __('messages.token_config_max_attempts_per_hour_min');
        }
        
        if ($this->getRateLimitConfig('max_health_checks_per_minute') < 1) {
            $errors[] = __('messages.token_config_max_health_checks_per_minute_min');
        }
        
        return $errors;
    }
}