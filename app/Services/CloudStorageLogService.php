<?php

namespace App\Services;

use App\Models\User;
use App\Models\CloudStorageHealthStatus;
use App\Enums\CloudStorageErrorType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Service for comprehensive logging and monitoring of cloud storage operations.
 * Provides detailed logging for token refresh operations, status determinations,
 * and metrics tracking for monitoring purposes.
 */
class CloudStorageLogService
{
    private const METRICS_CACHE_PREFIX = 'cloud_storage_metrics:';
    private const METRICS_TTL = 3600; // 1 hour

    /**
     * Log token refresh attempt with detailed context.
     */
    public function logTokenRefreshAttempt(User $user, string $provider, array $context = []): void
    {
        $logData = [
            'event' => 'token_refresh_attempt',
            'user_id' => $user->id,
            'provider' => $provider,
            'timestamp' => now()->toISOString(),
            'context' => $context,
        ];

        Log::channel('cloud-storage')->info('Token refresh attempt initiated', $logData);

        // Track metrics
        $this->incrementMetric("token_refresh_attempts.{$provider}");
        $this->incrementMetric("token_refresh_attempts.{$provider}.user.{$user->id}");
    }

    /**
     * Log successful token refresh with outcome details.
     */
    public function logTokenRefreshSuccess(User $user, string $provider, array $context = []): void
    {
        $logData = [
            'event' => 'token_refresh_success',
            'user_id' => $user->id,
            'provider' => $provider,
            'timestamp' => now()->toISOString(),
            'context' => $context,
        ];

        Log::channel('cloud-storage')->info('Token refresh completed successfully', $logData);

        // Track metrics
        $this->incrementMetric("token_refresh_success.{$provider}");
        $this->incrementMetric("token_refresh_success.{$provider}.user.{$user->id}");

        // Reset failure count on success
        $this->resetMetric("token_refresh_failures.{$provider}.user.{$user->id}");
    }

    /**
     * Log failed token refresh with error details.
     */
    public function logTokenRefreshFailure(User $user, string $provider, string $error, array $context = []): void
    {
        $logData = [
            'event' => 'token_refresh_failure',
            'user_id' => $user->id,
            'provider' => $provider,
            'error' => $error,
            'timestamp' => now()->toISOString(),
            'context' => $context,
        ];

        Log::channel('cloud-storage')->error('Token refresh failed', $logData);

        // Track metrics
        $this->incrementMetric("token_refresh_failures.{$provider}");
        $this->incrementMetric("token_refresh_failures.{$provider}.user.{$user->id}");
    }

    /**
     * Log status determination decision with reasoning.
     */
    public function logStatusDetermination(User $user, string $provider, string $status, string $reason, array $context = []): void
    {
        $logData = [
            'event' => 'status_determination',
            'user_id' => $user->id,
            'provider' => $provider,
            'determined_status' => $status,
            'reason' => $reason,
            'timestamp' => now()->toISOString(),
            'context' => $context,
        ];

        Log::channel('cloud-storage')->info('Status determined', $logData);

        // Track status frequency metrics
        $this->incrementMetric("status_frequency.{$provider}.{$status}");
        $this->incrementMetric("status_frequency.{$provider}.{$status}.user.{$user->id}");

        // Track status changes
        $this->trackStatusChange($user, $provider, $status);
    }

    /**
     * Log API connectivity test results.
     */
    public function logApiConnectivityTest(User $user, string $provider, bool $success, array $context = []): void
    {
        $logData = [
            'event' => 'api_connectivity_test',
            'user_id' => $user->id,
            'provider' => $provider,
            'success' => $success,
            'timestamp' => now()->toISOString(),
            'context' => $context,
        ];

        $level = $success ? 'info' : 'warning';
        $message = $success ? 'API connectivity test passed' : 'API connectivity test failed';

        Log::channel('cloud-storage')->{$level}($message, $logData);

        // Track metrics
        $metric = $success ? 'api_connectivity_success' : 'api_connectivity_failures';
        $this->incrementMetric("{$metric}.{$provider}");
        $this->incrementMetric("{$metric}.{$provider}.user.{$user->id}");
    }

    /**
     * Log proactive token validation results.
     */
    public function logProactiveTokenValidation(User $user, string $provider, bool $wasExpired, bool $refreshNeeded, bool $refreshSuccess = null): void
    {
        $logData = [
            'event' => 'proactive_token_validation',
            'user_id' => $user->id,
            'provider' => $provider,
            'was_expired' => $wasExpired,
            'refresh_needed' => $refreshNeeded,
            'refresh_success' => $refreshSuccess,
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('cloud-storage')->info('Proactive token validation completed', $logData);

        // Track proactive validation metrics
        if ($wasExpired) {
            $this->incrementMetric("proactive_validation.expired_tokens.{$provider}");
        }
        if ($refreshNeeded) {
            $this->incrementMetric("proactive_validation.refresh_needed.{$provider}");
        }
    }

    /**
     * Log cache operations for performance monitoring.
     */
    public function logCacheOperation(string $operation, string $key, bool $hit = null, array $context = []): void
    {
        $logData = [
            'event' => 'cache_operation',
            'operation' => $operation,
            'cache_key' => $key,
            'cache_hit' => $hit,
            'timestamp' => now()->toISOString(),
            'context' => $context,
        ];

        Log::channel('cloud-storage')->debug('Cache operation', $logData);

        // Track cache metrics
        if ($hit !== null) {
            $metric = $hit ? 'cache_hits' : 'cache_misses';
            $this->incrementMetric($metric);
        }
    }

    /**
     * Get token refresh success rate for a provider.
     */
    public function getTokenRefreshSuccessRate(string $provider, int $hours = 24): float
    {
        $attempts = $this->getMetric("token_refresh_attempts.{$provider}", $hours) ?? 0;
        $successes = $this->getMetric("token_refresh_success.{$provider}", $hours) ?? 0;

        if ($attempts === 0) {
            return 1.0; // No attempts means 100% success rate
        }

        return $successes / $attempts;
    }

    /**
     * Get status distribution for monitoring.
     */
    public function getStatusDistribution(string $provider, int $hours = 24): array
    {
        $statuses = ['healthy', 'authentication_required', 'connection_issues', 'not_connected'];
        $distribution = [];

        foreach ($statuses as $status) {
            $distribution[$status] = $this->getMetric("status_frequency.{$provider}.{$status}", $hours) ?? 0;
        }

        return $distribution;
    }

    /**
     * Get comprehensive metrics summary.
     */
    public function getMetricsSummary(string $provider, int $hours = 24): array
    {
        return [
            'token_refresh' => [
                'attempts' => $this->getMetric("token_refresh_attempts.{$provider}", $hours) ?? 0,
                'successes' => $this->getMetric("token_refresh_success.{$provider}", $hours) ?? 0,
                'failures' => $this->getMetric("token_refresh_failures.{$provider}", $hours) ?? 0,
                'success_rate' => $this->getTokenRefreshSuccessRate($provider, $hours),
            ],
            'api_connectivity' => [
                'successes' => $this->getMetric("api_connectivity_success.{$provider}", $hours) ?? 0,
                'failures' => $this->getMetric("api_connectivity_failures.{$provider}", $hours) ?? 0,
            ],
            'status_distribution' => $this->getStatusDistribution($provider, $hours),
            'cache_performance' => [
                'hits' => $this->getMetric('cache_hits', $hours) ?? 0,
                'misses' => $this->getMetric('cache_misses', $hours) ?? 0,
            ],
        ];
    }

    /**
     * Increment a metric counter.
     */
    private function incrementMetric(string $metric): void
    {
        $key = self::METRICS_CACHE_PREFIX . $metric;
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, self::METRICS_TTL);
    }

    /**
     * Reset a metric counter.
     */
    private function resetMetric(string $metric): void
    {
        $key = self::METRICS_CACHE_PREFIX . $metric;
        Cache::forget($key);
    }

    /**
     * Get a metric value.
     */
    private function getMetric(string $metric, int $hours = 24): ?int
    {
        $key = self::METRICS_CACHE_PREFIX . $metric;
        return Cache::get($key);
    }

    /**
     * Log operation start and return operation ID for tracking.
     */
    public function logOperationStart(string $operation, string $provider, User $user, array $context = []): string
    {
        $operationId = uniqid("{$operation}_{$provider}_", true);

        $logData = [
            'event' => 'operation_start',
            'operation_id' => $operationId,
            'operation' => $operation,
            'provider' => $provider,
            'user_id' => $user->id,
            'timestamp' => now()->toISOString(),
            'context' => $context,
        ];

        Log::channel('cloud-storage')->info("Operation started: {$operation}", $logData);

        // Track operation metrics
        $this->incrementMetric("operations.{$provider}.{$operation}.started");

        return $operationId;
    }

    /**
     * Log successful operation completion.
     */
    public function logOperationSuccess(string $operationId, string $operation, string $provider, User $user, array $result = [], float $durationMs = null): void
    {
        $logData = [
            'event' => 'operation_success',
            'operation_id' => $operationId,
            'operation' => $operation,
            'provider' => $provider,
            'user_id' => $user->id,
            'duration_ms' => $durationMs,
            'timestamp' => now()->toISOString(),
            'result' => $result,
        ];

        Log::channel('cloud-storage')->info("Operation completed successfully: {$operation}", $logData);

        // Track success metrics
        $this->incrementMetric("operations.{$provider}.{$operation}.success");

        if ($durationMs !== null) {
            $this->trackOperationDuration($provider, $operation, $durationMs);
            
            // Integrate with performance metrics service
            $performanceService = app(CloudStoragePerformanceMetricsService::class);
            $performanceService->recordOperationMetrics($provider, $operation, $user, $durationMs, true, null, $result);
        }

        // Track success with error tracking service to reset consecutive failures
        $errorTrackingService = app(CloudStorageErrorTrackingService::class);
        $errorTrackingService->trackSuccess($provider, $user, $operation);
    }

    /**
     * Log failed operation.
     */
    public function logOperationFailure(string $operationId, string $operation, string $provider, User $user, $errorType, string $errorMessage, array $context = [], float $durationMs = null, ?\Throwable $exception = null): void
    {
        $logData = [
            'event' => 'operation_failure',
            'operation_id' => $operationId,
            'operation' => $operation,
            'provider' => $provider,
            'user_id' => $user->id,
            'error_type' => is_object($errorType) ? $errorType->value : $errorType,
            'error_message' => $errorMessage,
            'duration_ms' => $durationMs,
            'timestamp' => now()->toISOString(),
            'context' => $context,
        ];

        if ($exception) {
            $logData['exception'] = [
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        Log::channel('cloud-storage')->error("Operation failed: {$operation}", $logData);

        // Track failure metrics
        $this->incrementMetric("operations.{$provider}.{$operation}.failures");

        if ($durationMs !== null) {
            $this->trackOperationDuration($provider, $operation, $durationMs);
        }

        // Integrate with error tracking service
        if ($errorType instanceof CloudStorageErrorType) {
            $errorTrackingService = app(CloudStorageErrorTrackingService::class);
            $errorTrackingService->trackError($provider, $user, $errorType, $operation, $errorMessage, $exception, $context);
        }
    }

    /**
     * Log OAuth events.
     */
    public function logOAuthEvent(string $provider, User $user, string $event, bool $success, ?string $error = null, array $context = []): void
    {
        $logData = [
            'event' => 'oauth_event',
            'oauth_event' => $event,
            'provider' => $provider,
            'user_id' => $user->id,
            'success' => $success,
            'timestamp' => now()->toISOString(),
            'context' => $context,
        ];

        if ($error) {
            $logData['error'] = $error;
        }

        $level = $success ? 'info' : 'warning';
        $message = $success ? "OAuth event successful: {$event}" : "OAuth event failed: {$event}";

        Log::channel('cloud-storage')->{$level}($message, $logData);

        // Track OAuth metrics
        $metric = $success ? 'oauth_success' : 'oauth_failures';
        $this->incrementMetric("oauth.{$provider}.{$event}.{$metric}");
    }

    /**
     * Log retry decision for operations.
     */
    public function logRetryDecision(string $operationId, string $operation, string $provider, User $user, $errorType, int $attempt, bool $shouldRetry, ?int $retryDelaySeconds = null, array $context = []): void
    {
        $logData = [
            'event' => 'retry_decision',
            'operation_id' => $operationId,
            'operation' => $operation,
            'provider' => $provider,
            'user_id' => $user->id,
            'error_type' => is_object($errorType) ? $errorType->value : $errorType,
            'attempt' => $attempt,
            'should_retry' => $shouldRetry,
            'retry_delay_seconds' => $retryDelaySeconds,
            'timestamp' => now()->toISOString(),
            'context' => $context,
        ];

        $message = $shouldRetry
            ? "Operation will be retried: {$operation} (attempt {$attempt})"
            : "Operation will not be retried: {$operation} (attempt {$attempt})";

        Log::channel('cloud-storage')->info($message, $logData);

        // Track retry metrics
        $metric = $shouldRetry ? 'retries_scheduled' : 'retries_abandoned';
        $this->incrementMetric("operations.{$provider}.{$operation}.{$metric}");
    }

    /**
     * Track operation duration for performance monitoring.
     */
    private function trackOperationDuration(string $provider, string $operation, float $durationMs): void
    {
        // Store duration in cache for performance analysis
        $key = "operation_durations.{$provider}.{$operation}";
        $durations = Cache::get($key, []);

        // Keep only last 100 durations to prevent memory issues
        if (count($durations) >= 100) {
            array_shift($durations);
        }

        $durations[] = $durationMs;
        Cache::put($key, $durations, self::METRICS_TTL);
    }

    /**
     * Track status changes for monitoring.
     */
    private function trackStatusChange(User $user, string $provider, string $newStatus): void
    {
        $cacheKey = "last_status.{$provider}.user.{$user->id}";
        $lastStatus = Cache::get($cacheKey);

        if ($lastStatus && $lastStatus !== $newStatus) {
            $this->incrementMetric("status_changes.{$provider}.{$lastStatus}_to_{$newStatus}");

            Log::channel('cloud-storage')->info('Status change detected', [
                'event' => 'status_change',
                'user_id' => $user->id,
                'provider' => $provider,
                'from_status' => $lastStatus,
                'to_status' => $newStatus,
                'timestamp' => now()->toISOString(),
            ]);
        }

        Cache::put($cacheKey, $newStatus, self::METRICS_TTL);
    }

    /**
     * Log health status changes for monitoring.
     */
    public function logHealthStatusChange(string $provider, User $user, string $previousStatus, string $newStatus, $errorType = null, ?string $reason = null, array $context = []): void
    {
        $logData = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'provider' => $provider,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'timestamp' => now()->toISOString(),
        ];

        if ($errorType) {
            $logData['error_type'] = is_object($errorType) ? $errorType->value : $errorType;
        }

        if (!empty($context)) {
            $logData['context'] = $context;
        }

        Log::channel('cloud-storage')->info("Health status changed: {$previousStatus} -> {$newStatus}", $logData);

        // Track status change metrics
        $this->incrementMetric("health_status_changes.{$provider}");
        $this->incrementMetric("health_status_changes.{$provider}.to.{$newStatus}");
        $this->incrementMetric("health_status_changes.{$provider}.from.{$previousStatus}");
    }
}
