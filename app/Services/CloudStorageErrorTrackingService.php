<?php

namespace App\Services;

use App\Models\User;
use App\Enums\CloudStorageErrorType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CloudStorageConnectionAlert;
use Carbon\Carbon;

/**
 * Service for tracking cloud storage errors and managing alerting.
 * Provides error pattern detection, escalation, and notification management.
 */
class CloudStorageErrorTrackingService
{
    private const ERROR_CACHE_PREFIX = 'cloud_storage_errors:';
    private const ERROR_TTL = 86400; // 24 hours
    private const ALERT_THRESHOLDS = [
        'error_rate_threshold' => 10, // 10 errors per hour
        'consecutive_failures_threshold' => 5,
        'critical_error_threshold' => 1, // Immediate alert for critical errors
        'escalation_threshold' => 20, // 20 errors per hour for escalation
    ];

    /**
     * Track an error occurrence.
     */
    public function trackError(
        string $provider,
        User $user,
        CloudStorageErrorType $errorType,
        string $operation,
        string $errorMessage,
        ?\Throwable $exception = null,
        array $context = []
    ): void {
        $timestamp = now();
        $errorId = uniqid('error_', true);

        // Create comprehensive error record
        $errorRecord = [
            'error_id' => $errorId,
            'provider' => $provider,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'error_type' => $errorType->value,
            'operation' => $operation,
            'error_message' => $errorMessage,
            'timestamp' => $timestamp->toISOString(),
            'context' => $context,
        ];

        if ($exception) {
            $errorRecord['exception'] = [
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'previous' => $exception->getPrevious() ? get_class($exception->getPrevious()) : null,
            ];
        }

        // Log the error
        Log::channel('cloud-storage')->error("Cloud storage error tracked: {$errorType->value}", $errorRecord);

        // Store error for pattern analysis
        $this->storeErrorForAnalysis($provider, $user, $errorType, $operation, $errorRecord);

        // Update error counters
        $this->updateErrorCounters($provider, $user, $errorType, $operation, $timestamp);

        // Check for alert conditions
        $this->checkAlertConditions($provider, $user, $errorType, $operation, $errorRecord);

        // Update consecutive failure tracking
        $this->updateConsecutiveFailureTracking($provider, $user, $operation, true);
    }

    /**
     * Track successful operation (resets consecutive failure counters).
     */
    public function trackSuccess(string $provider, User $user, string $operation): void
    {
        $this->updateConsecutiveFailureTracking($provider, $user, $operation, false);
        
        // Log success for pattern analysis
        Log::channel('cloud-storage')->debug("Operation success tracked", [
            'provider' => $provider,
            'user_id' => $user->id,
            'operation' => $operation,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get error statistics for a provider and user.
     */
    public function getErrorStatistics(string $provider, User $user, int $hours = 24): array
    {
        $endTime = now();
        $startTime = $endTime->copy()->subHours($hours);
        
        $statistics = [
            'provider' => $provider,
            'user_id' => $user->id,
            'time_range' => [
                'start' => $startTime->toISOString(),
                'end' => $endTime->toISOString(),
                'hours' => $hours,
            ],
            'total_errors' => 0,
            'error_rate_per_hour' => 0,
            'error_types' => [],
            'operations' => [],
            'consecutive_failures' => [],
            'recent_errors' => [],
        ];

        // Get error counts by type
        foreach (CloudStorageErrorType::cases() as $errorType) {
            $count = $this->getErrorCount($provider, $user, $errorType, $hours);
            if ($count > 0) {
                $statistics['error_types'][$errorType->value] = $count;
                $statistics['total_errors'] += $count;
            }
        }

        // Calculate error rate
        $statistics['error_rate_per_hour'] = $hours > 0 ? round($statistics['total_errors'] / $hours, 2) : 0;

        // Get error counts by operation
        $operations = ['upload', 'download', 'delete', 'list', 'auth'];
        foreach ($operations as $operation) {
            $count = $this->getOperationErrorCount($provider, $user, $operation, $hours);
            if ($count > 0) {
                $statistics['operations'][$operation] = $count;
            }
        }

        // Get consecutive failure counts
        foreach ($operations as $operation) {
            $consecutiveCount = $this->getConsecutiveFailureCount($provider, $user, $operation);
            if ($consecutiveCount > 0) {
                $statistics['consecutive_failures'][$operation] = $consecutiveCount;
            }
        }

        // Get recent errors (last 10)
        $statistics['recent_errors'] = $this->getRecentErrors($provider, $user, 10);

        return $statistics;
    }

    /**
     * Get error patterns and trends.
     */
    public function getErrorPatterns(string $provider, User $user, int $days = 7): array
    {
        return [
            'provider' => $provider,
            'user_id' => $user->id,
            'analysis_period_days' => $days,
            'patterns' => [
                'most_common_errors' => $this->getMostCommonErrors($provider, $user, $days),
                'error_trends' => $this->getErrorTrends($provider, $user, $days),
                'operation_failure_rates' => $this->getOperationFailureRates($provider, $user, $days),
                'time_based_patterns' => $this->getTimeBasedErrorPatterns($provider, $user, $days),
            ],
            'recommendations' => $this->generateErrorRecommendations($provider, $user, $days),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get active alerts for a provider and user.
     */
    public function getActiveAlerts(string $provider, User $user): array
    {
        $alerts = [];
        
        // Check error rate alerts
        $hourlyErrorRate = $this->getErrorCount($provider, $user, null, 1);
        if ($hourlyErrorRate >= self::ALERT_THRESHOLDS['error_rate_threshold']) {
            $alerts[] = [
                'type' => 'high_error_rate',
                'severity' => 'medium',
                'message' => "High error rate: {$hourlyErrorRate} errors in the last hour",
                'threshold' => self::ALERT_THRESHOLDS['error_rate_threshold'],
                'current_value' => $hourlyErrorRate,
                'created_at' => now()->toISOString(),
            ];
        }

        // Check consecutive failure alerts
        $operations = ['upload', 'download', 'delete', 'list', 'auth'];
        foreach ($operations as $operation) {
            $consecutiveFailures = $this->getConsecutiveFailureCount($provider, $user, $operation);
            if ($consecutiveFailures >= self::ALERT_THRESHOLDS['consecutive_failures_threshold']) {
                $alerts[] = [
                    'type' => 'consecutive_failures',
                    'severity' => 'high',
                    'message' => "Consecutive failures in {$operation}: {$consecutiveFailures} failures",
                    'operation' => $operation,
                    'threshold' => self::ALERT_THRESHOLDS['consecutive_failures_threshold'],
                    'current_value' => $consecutiveFailures,
                    'created_at' => now()->toISOString(),
                ];
            }
        }

        // Check for critical errors in the last hour
        $criticalErrors = $this->getCriticalErrorCount($provider, $user, 1);
        if ($criticalErrors > 0) {
            $alerts[] = [
                'type' => 'critical_errors',
                'severity' => 'critical',
                'message' => "Critical errors detected: {$criticalErrors} critical errors in the last hour",
                'current_value' => $criticalErrors,
                'created_at' => now()->toISOString(),
            ];
        }

        return $alerts;
    }

    /**
     * Send error alert notification.
     */
    public function sendErrorAlert(
        string $provider,
        User $user,
        string $alertType,
        string $message,
        array $details = []
    ): void {
        // Check if we should throttle alerts to prevent spam
        if ($this->shouldThrottleAlert($provider, $user, $alertType)) {
            Log::channel('cloud-storage')->info('Alert throttled to prevent spam', [
                'provider' => $provider,
                'user_id' => $user->id,
                'alert_type' => $alertType,
                'message' => $message,
            ]);
            return;
        }

        // Send notification
        try {
            $user->notify(new CloudStorageConnectionAlert($provider, $alertType, null, array_merge($details, ['message' => $message])));
            
            Log::channel('cloud-storage')->info('Error alert sent', [
                'provider' => $provider,
                'user_id' => $user->id,
                'alert_type' => $alertType,
                'message' => $message,
                'timestamp' => now()->toISOString(),
            ]);

            // Track alert sent
            $this->trackAlertSent($provider, $user, $alertType);

        } catch (\Exception $e) {
            Log::channel('cloud-storage')->error('Failed to send error alert', [
                'provider' => $provider,
                'user_id' => $user->id,
                'alert_type' => $alertType,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Clear alerts for a provider and user (when issues are resolved).
     */
    public function clearAlerts(string $provider, User $user, ?string $alertType = null): void
    {
        $cacheKey = $alertType 
            ? "alerts:{$provider}:{$user->id}:{$alertType}"
            : "alerts:{$provider}:{$user->id}:*";

        if ($alertType) {
            Cache::forget(self::ERROR_CACHE_PREFIX . $cacheKey);
        } else {
            // Clear all alerts for this provider/user combination
            $pattern = self::ERROR_CACHE_PREFIX . "alerts:{$provider}:{$user->id}:*";
            // Note: This would need a more sophisticated cache clearing mechanism in production
        }

        Log::channel('cloud-storage')->info('Alerts cleared', [
            'provider' => $provider,
            'user_id' => $user->id,
            'alert_type' => $alertType ?? 'all',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Store error for pattern analysis.
     */
    private function storeErrorForAnalysis(
        string $provider,
        User $user,
        CloudStorageErrorType $errorType,
        string $operation,
        array $errorRecord
    ): void {
        $hour = now()->format('Y-m-d-H');
        $key = self::ERROR_CACHE_PREFIX . "errors:{$provider}:{$user->id}:{$hour}";
        
        $errors = Cache::get($key, []);
        $errors[] = $errorRecord;
        
        // Keep only last 100 errors per hour to prevent memory issues
        if (count($errors) > 100) {
            array_shift($errors);
        }
        
        Cache::put($key, $errors, self::ERROR_TTL);
    }

    /**
     * Update error counters for metrics.
     */
    private function updateErrorCounters(
        string $provider,
        User $user,
        CloudStorageErrorType $errorType,
        string $operation,
        Carbon $timestamp
    ): void {
        $hour = $timestamp->format('Y-m-d-H');
        
        // Increment total error counter
        $totalKey = self::ERROR_CACHE_PREFIX . "count:{$provider}:{$user->id}:total:{$hour}";
        $this->incrementCounter($totalKey);
        
        // Increment error type counter
        $typeKey = self::ERROR_CACHE_PREFIX . "count:{$provider}:{$user->id}:type:{$errorType->value}:{$hour}";
        $this->incrementCounter($typeKey);
        
        // Increment operation error counter
        $opKey = self::ERROR_CACHE_PREFIX . "count:{$provider}:{$user->id}:operation:{$operation}:{$hour}";
        $this->incrementCounter($opKey);
    }

    /**
     * Update consecutive failure tracking.
     */
    private function updateConsecutiveFailureTracking(
        string $provider,
        User $user,
        string $operation,
        bool $isFailure
    ): void {
        $key = self::ERROR_CACHE_PREFIX . "consecutive:{$provider}:{$user->id}:{$operation}";
        
        if ($isFailure) {
            $current = Cache::get($key, 0);
            Cache::put($key, $current + 1, self::ERROR_TTL);
        } else {
            Cache::forget($key);
        }
    }

    /**
     * Check alert conditions and trigger alerts if necessary.
     */
    private function checkAlertConditions(
        string $provider,
        User $user,
        CloudStorageErrorType $errorType,
        string $operation,
        array $errorRecord
    ): void {
        // Check for critical errors (immediate alert)
        if ($this->isCriticalError($errorType)) {
            $this->sendErrorAlert(
                $provider,
                $user,
                'critical_error',
                "Critical error in {$operation}: {$errorType->value}",
                $errorRecord
            );
        }

        // Check error rate threshold
        $hourlyErrorRate = $this->getErrorCount($provider, $user, null, 1);
        if ($hourlyErrorRate >= self::ALERT_THRESHOLDS['error_rate_threshold']) {
            $this->sendErrorAlert(
                $provider,
                $user,
                'high_error_rate',
                "High error rate detected: {$hourlyErrorRate} errors in the last hour"
            );
        }

        // Check consecutive failures
        $consecutiveFailures = $this->getConsecutiveFailureCount($provider, $user, $operation);
        if ($consecutiveFailures >= self::ALERT_THRESHOLDS['consecutive_failures_threshold']) {
            $this->sendErrorAlert(
                $provider,
                $user,
                'consecutive_failures',
                "Consecutive failures in {$operation}: {$consecutiveFailures} failures",
                ['operation' => $operation, 'consecutive_count' => $consecutiveFailures]
            );
        }

        // Check escalation threshold
        if ($hourlyErrorRate >= self::ALERT_THRESHOLDS['escalation_threshold']) {
            $this->sendErrorAlert(
                $provider,
                $user,
                'escalation',
                "Error rate requires escalation: {$hourlyErrorRate} errors in the last hour"
            );
        }
    }

    /**
     * Get error count for specific criteria.
     */
    private function getErrorCount(
        string $provider,
        User $user,
        ?CloudStorageErrorType $errorType = null,
        int $hours = 24
    ): int {
        $count = 0;
        $endTime = now();
        $startTime = $endTime->copy()->subHours($hours);

        for ($time = $startTime->copy(); $time <= $endTime; $time->addHour()) {
            $hour = $time->format('Y-m-d-H');
            
            if ($errorType) {
                $key = self::ERROR_CACHE_PREFIX . "count:{$provider}:{$user->id}:type:{$errorType->value}:{$hour}";
            } else {
                $key = self::ERROR_CACHE_PREFIX . "count:{$provider}:{$user->id}:total:{$hour}";
            }
            
            $count += Cache::get($key, 0);
        }

        return $count;
    }

    /**
     * Get error count for specific operation.
     */
    private function getOperationErrorCount(string $provider, User $user, string $operation, int $hours): int
    {
        $count = 0;
        $endTime = now();
        $startTime = $endTime->copy()->subHours($hours);

        for ($time = $startTime->copy(); $time <= $endTime; $time->addHour()) {
            $hour = $time->format('Y-m-d-H');
            $key = self::ERROR_CACHE_PREFIX . "count:{$provider}:{$user->id}:operation:{$operation}:{$hour}";
            $count += Cache::get($key, 0);
        }

        return $count;
    }

    /**
     * Get consecutive failure count for an operation.
     */
    private function getConsecutiveFailureCount(string $provider, User $user, string $operation): int
    {
        $key = self::ERROR_CACHE_PREFIX . "consecutive:{$provider}:{$user->id}:{$operation}";
        return Cache::get($key, 0);
    }

    /**
     * Get critical error count.
     */
    private function getCriticalErrorCount(string $provider, User $user, int $hours): int
    {
        $criticalErrorTypes = [
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
        ];

        $count = 0;
        foreach ($criticalErrorTypes as $errorType) {
            $count += $this->getErrorCount($provider, $user, $errorType, $hours);
        }

        return $count;
    }

    /**
     * Check if error type is critical.
     */
    private function isCriticalError(CloudStorageErrorType $errorType): bool
    {
        return in_array($errorType, [
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
        ]);
    }

    /**
     * Check if alert should be throttled.
     */
    private function shouldThrottleAlert(string $provider, User $user, string $alertType): bool
    {
        $key = self::ERROR_CACHE_PREFIX . "alert_throttle:{$provider}:{$user->id}:{$alertType}";
        $lastSent = Cache::get($key);
        
        if ($lastSent) {
            $timeSinceLastAlert = now()->diffInMinutes($lastSent);
            return $timeSinceLastAlert < 60; // Throttle for 1 hour
        }
        
        return false;
    }

    /**
     * Track that an alert was sent.
     */
    private function trackAlertSent(string $provider, User $user, string $alertType): void
    {
        $key = self::ERROR_CACHE_PREFIX . "alert_throttle:{$provider}:{$user->id}:{$alertType}";
        Cache::put($key, now(), 3600); // Store for 1 hour
    }

    /**
     * Increment a counter in cache.
     */
    private function incrementCounter(string $key): void
    {
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, self::ERROR_TTL);
    }

    /**
     * Get recent errors for display.
     */
    private function getRecentErrors(string $provider, User $user, int $limit): array
    {
        $recentErrors = [];
        $endTime = now();
        $startTime = $endTime->copy()->subHours(24);

        // Collect errors from recent hours
        for ($time = $endTime->copy(); $time >= $startTime && count($recentErrors) < $limit; $time->subHour()) {
            $hour = $time->format('Y-m-d-H');
            $key = self::ERROR_CACHE_PREFIX . "errors:{$provider}:{$user->id}:{$hour}";
            $hourlyErrors = Cache::get($key, []);
            
            foreach (array_reverse($hourlyErrors) as $error) {
                if (count($recentErrors) >= $limit) break;
                $recentErrors[] = $error;
            }
        }

        return array_slice($recentErrors, 0, $limit);
    }

    // Placeholder methods for pattern analysis (would be implemented based on requirements)
    private function getMostCommonErrors(string $provider, User $user, int $days): array { return []; }
    private function getErrorTrends(string $provider, User $user, int $days): array { return []; }
    private function getOperationFailureRates(string $provider, User $user, int $days): array { return []; }
    private function getTimeBasedErrorPatterns(string $provider, User $user, int $days): array { return []; }
    private function generateErrorRecommendations(string $provider, User $user, int $days): array { return []; }
}