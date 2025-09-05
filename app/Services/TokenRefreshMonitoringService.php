<?php

namespace App\Services;

use App\Enums\TokenRefreshErrorType;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * Comprehensive monitoring and logging service for token refresh operations
 * 
 * Provides structured logging, performance metrics tracking, and monitoring
 * capabilities for the Google Drive token auto-renewal system
 */
class TokenRefreshMonitoringService
{
    // Cache keys for metrics
    private const METRICS_PREFIX = 'token_refresh_metrics';
    private const SUCCESS_RATE_KEY = self::METRICS_PREFIX . ':success_rate';
    private const AVERAGE_TIME_KEY = self::METRICS_PREFIX . ':average_time';
    private const HEALTH_CACHE_MISS_KEY = self::METRICS_PREFIX . ':health_cache_miss';
    private const OPERATION_COUNT_KEY = self::METRICS_PREFIX . ':operation_count';
    private const FAILURE_COUNT_KEY = self::METRICS_PREFIX . ':failure_count';
    
    // Alerting thresholds
    private const FAILURE_RATE_THRESHOLD = 0.10; // 10%
    private const HEALTH_CACHE_MISS_THRESHOLD = 0.50; // 50%
    private const AVERAGE_TIME_THRESHOLD = 5000; // 5 seconds in milliseconds
    
    // Metrics retention
    private const METRICS_TTL = 86400; // 24 hours
    private const DETAILED_METRICS_TTL = 3600; // 1 hour for detailed metrics

    /**
     * Log a token refresh operation start with structured data
     */
    public function logRefreshOperationStart(
        User $user,
        string $provider,
        string $operationId,
        array $context = []
    ): void {
        $logData = [
            'event' => 'token_refresh_start',
            'user_id' => $user->id,
            'provider' => $provider,
            'operation_id' => $operationId,
            'timestamp' => now()->toISOString(),
            'context' => $context
        ];

        Log::info('Token refresh operation started', $logData);
        
        // Store operation start time for performance tracking
        $this->storeOperationStartTime($operationId);
    }

    /**
     * Log a successful token refresh operation with performance metrics
     */
    public function logRefreshOperationSuccess(
        User $user,
        string $provider,
        string $operationId,
        array $context = []
    ): void {
        $duration = $this->calculateOperationDuration($operationId);
        
        $logData = [
            'event' => 'token_refresh_success',
            'user_id' => $user->id,
            'provider' => $provider,
            'operation_id' => $operationId,
            'timestamp' => now()->toISOString(),
            'duration_ms' => $duration,
            'context' => $context
        ];

        Log::info('Token refresh operation completed successfully', $logData);
        
        // Update performance metrics
        $this->updateSuccessMetrics($provider, $duration);
        
        // Check for alerting thresholds
        $this->checkAlertingThresholds($provider);
    }

    /**
     * Log a failed token refresh operation with error classification
     */
    public function logRefreshOperationFailure(
        User $user,
        string $provider,
        string $operationId,
        TokenRefreshErrorType $errorType,
        Exception $exception,
        array $context = []
    ): void {
        $duration = $this->calculateOperationDuration($operationId);
        
        $logData = [
            'event' => 'token_refresh_failure',
            'user_id' => $user->id,
            'provider' => $provider,
            'operation_id' => $operationId,
            'timestamp' => now()->toISOString(),
            'duration_ms' => $duration,
            'error_type' => $errorType->value,
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'error_class' => get_class($exception),
            'is_recoverable' => $errorType->isRecoverable(),
            'requires_user_intervention' => $errorType->requiresUserIntervention(),
            'max_retry_attempts' => $errorType->getMaxRetryAttempts(),
            'context' => $context
        ];

        Log::error('Token refresh operation failed', $logData);
        
        // Update failure metrics
        $this->updateFailureMetrics($provider, $errorType, $duration);
        
        // Check for alerting thresholds
        $this->checkAlertingThresholds($provider);
    }

    /**
     * Log health validation operation with cache hit/miss tracking
     */
    public function logHealthValidation(
        User $user,
        string $provider,
        string $operationId,
        bool $cacheHit,
        bool $validationResult,
        array $context = []
    ): void {
        $logData = [
            'event' => 'health_validation',
            'user_id' => $user->id,
            'provider' => $provider,
            'operation_id' => $operationId,
            'timestamp' => now()->toISOString(),
            'cache_hit' => $cacheHit,
            'validation_result' => $validationResult,
            'context' => $context
        ];

        Log::info('Health validation performed', $logData);
        
        // Update cache hit/miss metrics
        $this->updateHealthCacheMetrics($provider, $cacheHit);
    }

    /**
     * Log API connectivity test with detailed results
     */
    public function logApiConnectivityTest(
        User $user,
        string $provider,
        string $operationId,
        bool $success,
        int $responseTime,
        array $context = []
    ): void {
        $logData = [
            'event' => 'api_connectivity_test',
            'user_id' => $user->id,
            'provider' => $provider,
            'operation_id' => $operationId,
            'timestamp' => now()->toISOString(),
            'success' => $success,
            'response_time_ms' => $responseTime,
            'context' => $context
        ];

        Log::info('API connectivity test performed', $logData);
        
        // Update API performance metrics
        $this->updateApiPerformanceMetrics($provider, $success, $responseTime);
    }

    /**
     * Log proactive refresh scheduling
     */
    public function logProactiveRefreshScheduled(
        User $user,
        string $provider,
        Carbon $scheduledAt,
        string $reason,
        array $context = []
    ): void {
        $logData = [
            'event' => 'proactive_refresh_scheduled',
            'user_id' => $user->id,
            'provider' => $provider,
            'scheduled_at' => $scheduledAt->toISOString(),
            'reason' => $reason,
            'delay_minutes' => $scheduledAt->diffInMinutes(now()),
            'timestamp' => now()->toISOString(),
            'context' => $context
        ];

        Log::info('Proactive refresh scheduled', $logData);
    }

    /**
     * Get comprehensive performance metrics for monitoring dashboard
     */
    public function getPerformanceMetrics(string $provider, int $hours = 24): array
    {
        $cacheKey = self::METRICS_PREFIX . ":{$provider}:summary:{$hours}h";
        
        return Cache::remember($cacheKey, 300, function () use ($provider, $hours) {
            $metrics = [
                'provider' => $provider,
                'time_period_hours' => $hours,
                'generated_at' => now()->toISOString(),
                'refresh_operations' => $this->getRefreshOperationMetrics($provider, $hours),
                'health_validation' => $this->getHealthValidationMetrics($provider, $hours),
                'api_connectivity' => $this->getApiConnectivityMetrics($provider, $hours),
                'alerting_status' => $this->getAlertingStatus($provider),
                'system_health' => $this->getSystemHealthIndicators($provider)
            ];

            return $metrics;
        });
    }

    /**
     * Get refresh operation metrics
     */
    private function getRefreshOperationMetrics(string $provider, int $hours): array
    {
        $totalOperations = $this->getMetricValue(self::OPERATION_COUNT_KEY . ":{$provider}", 0);
        $totalFailures = $this->getMetricValue(self::FAILURE_COUNT_KEY . ":{$provider}", 0);
        $averageTime = $this->getMetricValue(self::AVERAGE_TIME_KEY . ":{$provider}", 0);
        
        $successRate = $totalOperations > 0 ? (($totalOperations - $totalFailures) / $totalOperations) : 1.0;
        
        return [
            'total_operations' => $totalOperations,
            'successful_operations' => $totalOperations - $totalFailures,
            'failed_operations' => $totalFailures,
            'success_rate' => round($successRate, 4),
            'failure_rate' => round(1 - $successRate, 4),
            'average_duration_ms' => round($averageTime, 2),
            'error_breakdown' => $this->getErrorBreakdown($provider, $hours)
        ];
    }

    /**
     * Get health validation metrics
     */
    private function getHealthValidationMetrics(string $provider, int $hours): array
    {
        $cacheHits = $this->getMetricValue(self::HEALTH_CACHE_MISS_KEY . ":{$provider}:hits", 0);
        $cacheMisses = $this->getMetricValue(self::HEALTH_CACHE_MISS_KEY . ":{$provider}:misses", 0);
        $totalValidations = $cacheHits + $cacheMisses;
        
        $cacheHitRate = $totalValidations > 0 ? ($cacheHits / $totalValidations) : 1.0;
        $cacheMissRate = 1 - $cacheHitRate;
        
        return [
            'total_validations' => $totalValidations,
            'cache_hits' => $cacheHits,
            'cache_misses' => $cacheMisses,
            'cache_hit_rate' => round($cacheHitRate, 4),
            'cache_miss_rate' => round($cacheMissRate, 4)
        ];
    }

    /**
     * Get API connectivity metrics
     */
    private function getApiConnectivityMetrics(string $provider, int $hours): array
    {
        $apiTests = $this->getMetricValue("api_tests:{$provider}", 0);
        $apiFailures = $this->getMetricValue("api_failures:{$provider}", 0);
        $averageResponseTime = $this->getMetricValue("api_response_time:{$provider}", 0);
        
        $apiSuccessRate = $apiTests > 0 ? (($apiTests - $apiFailures) / $apiTests) : 1.0;
        
        return [
            'total_tests' => $apiTests,
            'successful_tests' => $apiTests - $apiFailures,
            'failed_tests' => $apiFailures,
            'success_rate' => round($apiSuccessRate, 4),
            'average_response_time_ms' => round($averageResponseTime, 2)
        ];
    }

    /**
     * Get current alerting status
     */
    private function getAlertingStatus(string $provider): array
    {
        $metrics = $this->getRefreshOperationMetrics($provider, 1); // Last hour
        $healthMetrics = $this->getHealthValidationMetrics($provider, 1);
        
        $alerts = [];
        
        // Check failure rate threshold
        if ($metrics['failure_rate'] > self::FAILURE_RATE_THRESHOLD) {
            $alerts[] = [
                'type' => 'high_failure_rate',
                'severity' => 'critical',
                'message' => "Token refresh failure rate ({$metrics['failure_rate']}) exceeds threshold (" . self::FAILURE_RATE_THRESHOLD . ")",
                'current_value' => $metrics['failure_rate'],
                'threshold' => self::FAILURE_RATE_THRESHOLD
            ];
        }
        
        // Check cache miss rate threshold
        if ($healthMetrics['cache_miss_rate'] > self::HEALTH_CACHE_MISS_THRESHOLD) {
            $alerts[] = [
                'type' => 'high_cache_miss_rate',
                'severity' => 'warning',
                'message' => "Health cache miss rate ({$healthMetrics['cache_miss_rate']}) exceeds threshold (" . self::HEALTH_CACHE_MISS_THRESHOLD . ")",
                'current_value' => $healthMetrics['cache_miss_rate'],
                'threshold' => self::HEALTH_CACHE_MISS_THRESHOLD
            ];
        }
        
        // Check average response time threshold
        if ($metrics['average_duration_ms'] > self::AVERAGE_TIME_THRESHOLD) {
            $alerts[] = [
                'type' => 'slow_refresh_operations',
                'severity' => 'warning',
                'message' => "Average refresh time ({$metrics['average_duration_ms']}ms) exceeds threshold (" . self::AVERAGE_TIME_THRESHOLD . "ms)",
                'current_value' => $metrics['average_duration_ms'],
                'threshold' => self::AVERAGE_TIME_THRESHOLD
            ];
        }
        
        return [
            'active_alerts' => $alerts,
            'alert_count' => count($alerts),
            'last_checked' => now()->toISOString()
        ];
    }

    /**
     * Get system health indicators
     */
    private function getSystemHealthIndicators(string $provider): array
    {
        return [
            'overall_status' => $this->calculateOverallHealthStatus($provider),
            'token_refresh_health' => $this->getTokenRefreshHealth($provider),
            'api_connectivity_health' => $this->getApiConnectivityHealth($provider),
            'cache_performance_health' => $this->getCachePerformanceHealth($provider),
            'last_updated' => now()->toISOString()
        ];
    }

    /**
     * Store operation start time for duration calculation
     */
    private function storeOperationStartTime(string $operationId): void
    {
        $key = "operation_start:{$operationId}";
        Cache::put($key, microtime(true), 300); // 5 minutes TTL
    }

    /**
     * Calculate operation duration in milliseconds
     */
    private function calculateOperationDuration(string $operationId): float
    {
        $key = "operation_start:{$operationId}";
        $startTime = Cache::get($key);
        
        if ($startTime) {
            Cache::forget($key); // Clean up
            return round((microtime(true) - $startTime) * 1000, 2);
        }
        
        return 0.0;
    }

    /**
     * Update success metrics
     */
    private function updateSuccessMetrics(string $provider, float $duration): void
    {
        // Increment operation count
        $this->incrementMetric(self::OPERATION_COUNT_KEY . ":{$provider}");
        
        // Update average duration using exponential moving average
        $this->updateAverageMetric(self::AVERAGE_TIME_KEY . ":{$provider}", $duration);
    }

    /**
     * Update failure metrics
     */
    private function updateFailureMetrics(string $provider, TokenRefreshErrorType $errorType, float $duration): void
    {
        // Increment operation and failure counts
        $this->incrementMetric(self::OPERATION_COUNT_KEY . ":{$provider}");
        $this->incrementMetric(self::FAILURE_COUNT_KEY . ":{$provider}");
        
        // Track error type breakdown
        $this->incrementMetric("error_breakdown:{$provider}:{$errorType->value}");
        
        // Update average duration
        $this->updateAverageMetric(self::AVERAGE_TIME_KEY . ":{$provider}", $duration);
    }

    /**
     * Update health cache metrics
     */
    private function updateHealthCacheMetrics(string $provider, bool $cacheHit): void
    {
        if ($cacheHit) {
            $this->incrementMetric(self::HEALTH_CACHE_MISS_KEY . ":{$provider}:hits");
        } else {
            $this->incrementMetric(self::HEALTH_CACHE_MISS_KEY . ":{$provider}:misses");
        }
    }

    /**
     * Update API performance metrics
     */
    private function updateApiPerformanceMetrics(string $provider, bool $success, int $responseTime): void
    {
        $this->incrementMetric("api_tests:{$provider}");
        
        if (!$success) {
            $this->incrementMetric("api_failures:{$provider}");
        }
        
        $this->updateAverageMetric("api_response_time:{$provider}", $responseTime);
    }

    /**
     * Increment a metric counter
     */
    private function incrementMetric(string $key): void
    {
        Cache::increment($key, 1);
        
        // Set TTL if the cache store supports it
        if (method_exists(Cache::getStore(), 'expire')) {
            Cache::expire($key, self::METRICS_TTL);
        } else {
            // ArrayStore doesn't support expire, use put instead
            $currentValue = Cache::get($key, 0);
            Cache::put($key, $currentValue, self::METRICS_TTL);
        }
    }

    /**
     * Update an average metric using exponential moving average
     */
    private function updateAverageMetric(string $key, float $newValue): void
    {
        $currentAverage = Cache::get($key, 0);
        $alpha = 0.1; // Smoothing factor for exponential moving average
        
        $newAverage = ($alpha * $newValue) + ((1 - $alpha) * $currentAverage);
        
        Cache::put($key, $newAverage, self::METRICS_TTL);
    }

    /**
     * Get a metric value with default
     */
    private function getMetricValue(string $key, $default = 0)
    {
        return Cache::get($key, $default);
    }

    /**
     * Get error breakdown for the specified time period
     */
    private function getErrorBreakdown(string $provider, int $hours): array
    {
        $breakdown = [];
        
        foreach (TokenRefreshErrorType::cases() as $errorType) {
            $count = $this->getMetricValue("error_breakdown:{$provider}:{$errorType->value}", 0);
            if ($count > 0) {
                $breakdown[$errorType->value] = $count;
            }
        }
        
        return $breakdown;
    }

    /**
     * Check alerting thresholds and log alerts
     */
    private function checkAlertingThresholds(string $provider): void
    {
        $alerts = $this->getAlertingStatus($provider);
        
        foreach ($alerts['active_alerts'] as $alert) {
            Log::warning('Alerting threshold exceeded', [
                'event' => 'threshold_alert',
                'provider' => $provider,
                'alert_type' => $alert['type'],
                'severity' => $alert['severity'],
                'message' => $alert['message'],
                'current_value' => $alert['current_value'],
                'threshold' => $alert['threshold'],
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    /**
     * Calculate overall health status
     */
    private function calculateOverallHealthStatus(string $provider): string
    {
        $metrics = $this->getRefreshOperationMetrics($provider, 1);
        $healthMetrics = $this->getHealthValidationMetrics($provider, 1);
        
        // Critical if failure rate is too high
        if ($metrics['failure_rate'] > self::FAILURE_RATE_THRESHOLD) {
            return 'critical';
        }
        
        // Warning if cache miss rate is high or average time is slow
        if ($healthMetrics['cache_miss_rate'] > self::HEALTH_CACHE_MISS_THRESHOLD ||
            $metrics['average_duration_ms'] > self::AVERAGE_TIME_THRESHOLD) {
            return 'warning';
        }
        
        return 'healthy';
    }

    /**
     * Get token refresh health status
     */
    private function getTokenRefreshHealth(string $provider): string
    {
        $metrics = $this->getRefreshOperationMetrics($provider, 1);
        
        if ($metrics['failure_rate'] > self::FAILURE_RATE_THRESHOLD) {
            return 'unhealthy';
        }
        
        if ($metrics['average_duration_ms'] > self::AVERAGE_TIME_THRESHOLD) {
            return 'degraded';
        }
        
        return 'healthy';
    }

    /**
     * Get API connectivity health status
     */
    private function getApiConnectivityHealth(string $provider): string
    {
        $metrics = $this->getApiConnectivityMetrics($provider, 1);
        
        if ($metrics['success_rate'] < 0.9) { // Less than 90% success rate
            return 'unhealthy';
        }
        
        if ($metrics['average_response_time_ms'] > 3000) { // Slower than 3 seconds
            return 'degraded';
        }
        
        return 'healthy';
    }

    /**
     * Get cache performance health status
     */
    private function getCachePerformanceHealth(string $provider): string
    {
        $metrics = $this->getHealthValidationMetrics($provider, 1);
        
        if ($metrics['cache_miss_rate'] > self::HEALTH_CACHE_MISS_THRESHOLD) {
            return 'degraded';
        }
        
        return 'healthy';
    }

    /**
     * Generate log analysis queries for troubleshooting
     */
    public function getLogAnalysisQueries(): array
    {
        return [
            'recent_failures' => [
                'description' => 'Recent token refresh failures',
                'query' => 'grep "token_refresh_failure" storage/logs/laravel.log | tail -50',
                'log_filter' => ['event' => 'token_refresh_failure'],
                'time_range' => '1 hour'
            ],
            'error_patterns' => [
                'description' => 'Token refresh error patterns by type',
                'query' => 'grep "token_refresh_failure" storage/logs/laravel.log | jq -r ".error_type" | sort | uniq -c',
                'log_filter' => ['event' => 'token_refresh_failure'],
                'group_by' => 'error_type'
            ],
            'slow_operations' => [
                'description' => 'Slow token refresh operations (>5s)',
                'query' => 'grep "token_refresh" storage/logs/laravel.log | jq "select(.duration_ms > 5000)"',
                'log_filter' => ['duration_ms' => ['>' => 5000]],
                'time_range' => '24 hours'
            ],
            'user_specific_issues' => [
                'description' => 'Token refresh issues by user',
                'query' => 'grep "token_refresh_failure" storage/logs/laravel.log | jq -r ".user_id" | sort | uniq -c | sort -nr',
                'log_filter' => ['event' => 'token_refresh_failure'],
                'group_by' => 'user_id'
            ],
            'cache_performance' => [
                'description' => 'Health validation cache performance',
                'query' => 'grep "health_validation" storage/logs/laravel.log | jq -r ".cache_hit" | sort | uniq -c',
                'log_filter' => ['event' => 'health_validation'],
                'group_by' => 'cache_hit'
            ]
        ];
    }

    /**
     * Reset metrics for testing or maintenance
     */
    public function resetMetrics(string $provider): void
    {
        $keys = [
            self::OPERATION_COUNT_KEY . ":{$provider}",
            self::FAILURE_COUNT_KEY . ":{$provider}",
            self::AVERAGE_TIME_KEY . ":{$provider}",
            self::HEALTH_CACHE_MISS_KEY . ":{$provider}:hits",
            self::HEALTH_CACHE_MISS_KEY . ":{$provider}:misses",
            "api_tests:{$provider}",
            "api_failures:{$provider}",
            "api_response_time:{$provider}"
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        // Clear error breakdown metrics
        foreach (TokenRefreshErrorType::cases() as $errorType) {
            Cache::forget("error_breakdown:{$provider}:{$errorType->value}");
        }
        
        Log::info('Token refresh metrics reset', [
            'event' => 'metrics_reset',
            'provider' => $provider,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Record batch processing metrics.
     * 
     * @param array $metrics
     * @return void
     */
    public function recordBatchProcessingMetrics(array $metrics): void
    {
        try {
            $cacheKey = 'batch_processing_metrics:' . now()->format('Y-m-d-H');
            
            $existingMetrics = Cache::get($cacheKey, []);
            $existingMetrics[] = array_merge($metrics, [
                'recorded_at' => now()->toISOString(),
            ]);
            
            Cache::put($cacheKey, $existingMetrics, now()->addHours(25));
            
            Log::debug('Recorded batch processing metrics', $metrics);
        } catch (\Exception $e) {
            Log::warning('Failed to record batch processing metrics', [
                'error' => $e->getMessage(),
                'metrics' => $metrics,
            ]);
        }
    }

    /**
     * Get batch processing metrics for specified hours.
     * 
     * @param int $hours
     * @return array
     */
    public function getBatchProcessingMetrics(int $hours = 24): array
    {
        try {
            $metrics = [];
            $startHour = now()->subHours($hours);
            
            for ($hour = $startHour; $hour <= now(); $hour->addHour()) {
                $cacheKey = 'batch_processing_metrics:' . $hour->format('Y-m-d-H');
                $hourlyMetrics = Cache::get($cacheKey, []);
                
                if (!empty($hourlyMetrics)) {
                    $metrics = array_merge($metrics, $hourlyMetrics);
                }
            }
            
            return [
                'total_batches' => count($metrics),
                'metrics' => $metrics,
                'summary' => $this->calculateBatchMetricsSummary($metrics),
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get batch processing metrics', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Calculate summary statistics for batch metrics.
     * 
     * @param array $metrics
     * @return array
     */
    private function calculateBatchMetricsSummary(array $metrics): array
    {
        if (empty($metrics)) {
            return [];
        }
        
        $totalTokens = array_sum(array_column($metrics, 'total_tokens'));
        $totalSuccessful = array_sum(array_column($metrics, 'successful_refreshes'));
        $totalFailed = array_sum(array_column($metrics, 'failed_refreshes'));
        $processingTimes = array_column($metrics, 'processing_time_ms');
        
        return [
            'total_tokens_processed' => $totalTokens,
            'total_successful_refreshes' => $totalSuccessful,
            'total_failed_refreshes' => $totalFailed,
            'overall_success_rate' => $totalTokens > 0 ? round($totalSuccessful / $totalTokens, 3) : 0,
            'average_processing_time_ms' => !empty($processingTimes) ? round(array_sum($processingTimes) / count($processingTimes), 2) : 0,
            'min_processing_time_ms' => !empty($processingTimes) ? min($processingTimes) : 0,
            'max_processing_time_ms' => !empty($processingTimes) ? max($processingTimes) : 0,
        ];
    }}
