<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service for collecting and analyzing cloud storage provider performance metrics.
 * Provides detailed performance tracking, alerting, and reporting capabilities.
 */
class CloudStoragePerformanceMetricsService
{
    private const METRICS_CACHE_PREFIX = 'cloud_storage_perf:';
    private const METRICS_TTL = 86400; // 24 hours
    private const ALERT_THRESHOLDS = [
        'response_time_ms' => 5000, // 5 seconds
        'error_rate_percent' => 10, // 10%
        'token_refresh_failures' => 5, // 5 failures per hour
    ];

    /**
     * Record operation performance metrics.
     */
    public function recordOperationMetrics(
        string $provider,
        string $operation,
        User $user,
        float $durationMs,
        bool $success,
        ?string $errorType = null,
        array $metadata = []
    ): void {
        $timestamp = now();
        $hour = $timestamp->format('Y-m-d-H');
        
        // Record basic metrics
        $this->incrementHourlyMetric($provider, $operation, 'total_operations', $hour);
        
        if ($success) {
            $this->incrementHourlyMetric($provider, $operation, 'successful_operations', $hour);
        } else {
            $this->incrementHourlyMetric($provider, $operation, 'failed_operations', $hour);
            if ($errorType) {
                $this->incrementHourlyMetric($provider, $operation, "errors.{$errorType}", $hour);
            }
        }

        // Record duration metrics
        $this->recordDurationMetric($provider, $operation, $durationMs, $hour);

        // Log detailed performance data
        $this->logPerformanceData($provider, $operation, $user, $durationMs, $success, $errorType, $metadata);

        // Check for performance alerts
        $this->checkPerformanceAlerts($provider, $operation, $durationMs, $success);
    }

    /**
     * Record file operation metrics (upload, download, delete).
     */
    public function recordFileOperationMetrics(
        string $provider,
        string $operation,
        User $user,
        int $fileSizeBytes,
        float $durationMs,
        bool $success,
        ?string $errorType = null
    ): void {
        $timestamp = now();
        $hour = $timestamp->format('Y-m-d-H');
        
        // Calculate throughput (bytes per second)
        $throughputBps = $durationMs > 0 ? ($fileSizeBytes / ($durationMs / 1000)) : 0;
        
        // Record file-specific metrics
        $this->recordDurationMetric($provider, $operation, $durationMs, $hour);
        $this->recordThroughputMetric($provider, $operation, $throughputBps, $hour);
        $this->recordFileSizeMetric($provider, $operation, $fileSizeBytes, $hour);

        // Log file operation performance
        Log::channel('performance')->info('File operation performance recorded', [
            'provider' => $provider,
            'operation' => $operation,
            'user_id' => $user->id,
            'file_size_bytes' => $fileSizeBytes,
            'duration_ms' => $durationMs,
            'throughput_bps' => $throughputBps,
            'success' => $success,
            'error_type' => $errorType,
            'timestamp' => $timestamp->toISOString(),
        ]);

        // Record general operation metrics
        $this->recordOperationMetrics($provider, $operation, $user, $durationMs, $success, $errorType, [
            'file_size_bytes' => $fileSizeBytes,
            'throughput_bps' => $throughputBps,
        ]);
    }

    /**
     * Get performance summary for a provider.
     */
    public function getPerformanceSummary(string $provider, int $hours = 24): array
    {
        $endTime = now();
        $startTime = $endTime->copy()->subHours($hours);
        
        $operations = ['upload', 'download', 'delete', 'list', 'auth'];
        $summary = [
            'provider' => $provider,
            'time_range' => [
                'start' => $startTime->toISOString(),
                'end' => $endTime->toISOString(),
                'hours' => $hours,
            ],
            'operations' => [],
            'overall' => [
                'total_operations' => 0,
                'successful_operations' => 0,
                'failed_operations' => 0,
                'success_rate' => 0,
                'avg_response_time_ms' => 0,
                'error_distribution' => [],
            ],
        ];

        foreach ($operations as $operation) {
            $operationMetrics = $this->getOperationMetrics($provider, $operation, $hours);
            $summary['operations'][$operation] = $operationMetrics;
            
            // Aggregate overall metrics
            $summary['overall']['total_operations'] += $operationMetrics['total_operations'];
            $summary['overall']['successful_operations'] += $operationMetrics['successful_operations'];
            $summary['overall']['failed_operations'] += $operationMetrics['failed_operations'];
        }

        // Calculate overall success rate
        if ($summary['overall']['total_operations'] > 0) {
            $summary['overall']['success_rate'] = 
                ($summary['overall']['successful_operations'] / $summary['overall']['total_operations']) * 100;
        }

        // Get overall average response time
        $summary['overall']['avg_response_time_ms'] = $this->getAverageResponseTime($provider, $hours);
        
        // Get error distribution
        $summary['overall']['error_distribution'] = $this->getErrorDistribution($provider, $hours);

        return $summary;
    }

    /**
     * Get operation-specific metrics.
     */
    public function getOperationMetrics(string $provider, string $operation, int $hours = 24): array
    {
        $metrics = [
            'operation' => $operation,
            'total_operations' => 0,
            'successful_operations' => 0,
            'failed_operations' => 0,
            'success_rate' => 0,
            'avg_response_time_ms' => 0,
            'min_response_time_ms' => null,
            'max_response_time_ms' => null,
            'p95_response_time_ms' => null,
            'avg_throughput_bps' => 0,
            'error_types' => [],
        ];

        $endTime = now();
        $startTime = $endTime->copy()->subHours($hours);

        // Collect metrics from each hour
        for ($time = $startTime->copy(); $time <= $endTime; $time->addHour()) {
            $hour = $time->format('Y-m-d-H');
            
            $metrics['total_operations'] += $this->getHourlyMetric($provider, $operation, 'total_operations', $hour);
            $metrics['successful_operations'] += $this->getHourlyMetric($provider, $operation, 'successful_operations', $hour);
            $metrics['failed_operations'] += $this->getHourlyMetric($provider, $operation, 'failed_operations', $hour);
        }

        // Calculate success rate
        if ($metrics['total_operations'] > 0) {
            $metrics['success_rate'] = ($metrics['successful_operations'] / $metrics['total_operations']) * 100;
        }

        // Get response time statistics
        $responseTimeStats = $this->getResponseTimeStatistics($provider, $operation, $hours);
        $metrics = array_merge($metrics, $responseTimeStats);

        // Get throughput statistics
        $metrics['avg_throughput_bps'] = $this->getAverageThroughput($provider, $operation, $hours);

        // Get error type distribution
        $metrics['error_types'] = $this->getOperationErrorTypes($provider, $operation, $hours);

        return $metrics;
    }

    /**
     * Get provider health score based on performance metrics.
     */
    public function getProviderHealthScore(string $provider, int $hours = 24): array
    {
        $summary = $this->getPerformanceSummary($provider, $hours);
        
        $score = 100; // Start with perfect score
        $factors = [];

        // Deduct points for low success rate
        $successRate = $summary['overall']['success_rate'];
        if ($successRate < 95) {
            $deduction = (95 - $successRate) * 2; // 2 points per percent below 95%
            $score -= $deduction;
            $factors[] = "Success rate: {$successRate}% (-{$deduction} points)";
        }

        // Deduct points for high response times
        $avgResponseTime = $summary['overall']['avg_response_time_ms'];
        if ($avgResponseTime > 2000) {
            $deduction = min(20, ($avgResponseTime - 2000) / 100); // Up to 20 points for slow responses
            $score -= $deduction;
            $factors[] = "Avg response time: {$avgResponseTime}ms (-{$deduction} points)";
        }

        // Deduct points for high error rates
        $errorRate = 100 - $successRate;
        if ($errorRate > 5) {
            $deduction = ($errorRate - 5) * 3; // 3 points per percent above 5%
            $score -= $deduction;
            $factors[] = "Error rate: {$errorRate}% (-{$deduction} points)";
        }

        $score = max(0, $score); // Don't go below 0

        return [
            'provider' => $provider,
            'health_score' => round($score, 1),
            'grade' => $this->getHealthGrade($score),
            'factors' => $factors,
            'metrics_summary' => $summary,
            'calculated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get performance alerts for a provider.
     */
    public function getPerformanceAlerts(string $provider, int $hours = 24): array
    {
        $alerts = [];
        $summary = $this->getPerformanceSummary($provider, $hours);

        // Check success rate alert
        if ($summary['overall']['success_rate'] < 90) {
            $alerts[] = [
                'type' => 'low_success_rate',
                'severity' => 'high',
                'message' => "Success rate is {$summary['overall']['success_rate']}% (below 90% threshold)",
                'value' => $summary['overall']['success_rate'],
                'threshold' => 90,
            ];
        }

        // Check response time alert
        if ($summary['overall']['avg_response_time_ms'] > self::ALERT_THRESHOLDS['response_time_ms']) {
            $alerts[] = [
                'type' => 'high_response_time',
                'severity' => 'medium',
                'message' => "Average response time is {$summary['overall']['avg_response_time_ms']}ms (above " . self::ALERT_THRESHOLDS['response_time_ms'] . "ms threshold)",
                'value' => $summary['overall']['avg_response_time_ms'],
                'threshold' => self::ALERT_THRESHOLDS['response_time_ms'],
            ];
        }

        // Check error rate alert
        $errorRate = 100 - $summary['overall']['success_rate'];
        if ($errorRate > self::ALERT_THRESHOLDS['error_rate_percent']) {
            $alerts[] = [
                'type' => 'high_error_rate',
                'severity' => 'high',
                'message' => "Error rate is {$errorRate}% (above " . self::ALERT_THRESHOLDS['error_rate_percent'] . "% threshold)",
                'value' => $errorRate,
                'threshold' => self::ALERT_THRESHOLDS['error_rate_percent'],
            ];
        }

        return $alerts;
    }

    /**
     * Record duration metric for an operation.
     */
    private function recordDurationMetric(string $provider, string $operation, float $durationMs, string $hour): void
    {
        $key = self::METRICS_CACHE_PREFIX . "durations:{$provider}:{$operation}:{$hour}";
        $durations = Cache::get($key, []);
        
        $durations[] = $durationMs;
        
        // Keep only last 1000 durations per hour to prevent memory issues
        if (count($durations) > 1000) {
            array_shift($durations);
        }
        
        Cache::put($key, $durations, self::METRICS_TTL);
    }

    /**
     * Record throughput metric for file operations.
     */
    private function recordThroughputMetric(string $provider, string $operation, float $throughputBps, string $hour): void
    {
        $key = self::METRICS_CACHE_PREFIX . "throughput:{$provider}:{$operation}:{$hour}";
        $throughputs = Cache::get($key, []);
        
        $throughputs[] = $throughputBps;
        
        // Keep only last 1000 throughput measurements per hour
        if (count($throughputs) > 1000) {
            array_shift($throughputs);
        }
        
        Cache::put($key, $throughputs, self::METRICS_TTL);
    }

    /**
     * Record file size metric for file operations.
     */
    private function recordFileSizeMetric(string $provider, string $operation, int $fileSizeBytes, string $hour): void
    {
        $key = self::METRICS_CACHE_PREFIX . "file_sizes:{$provider}:{$operation}:{$hour}";
        $sizes = Cache::get($key, []);
        
        $sizes[] = $fileSizeBytes;
        
        // Keep only last 1000 file sizes per hour
        if (count($sizes) > 1000) {
            array_shift($sizes);
        }
        
        Cache::put($key, $sizes, self::METRICS_TTL);
    }

    /**
     * Increment hourly metric counter.
     */
    private function incrementHourlyMetric(string $provider, string $operation, string $metric, string $hour): void
    {
        $key = self::METRICS_CACHE_PREFIX . "hourly:{$provider}:{$operation}:{$metric}:{$hour}";
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, self::METRICS_TTL);
    }

    /**
     * Get hourly metric value.
     */
    private function getHourlyMetric(string $provider, string $operation, string $metric, string $hour): int
    {
        $key = self::METRICS_CACHE_PREFIX . "hourly:{$provider}:{$operation}:{$metric}:{$hour}";
        return Cache::get($key, 0);
    }

    /**
     * Get response time statistics for an operation.
     */
    private function getResponseTimeStatistics(string $provider, string $operation, int $hours): array
    {
        $allDurations = [];
        $endTime = now();
        $startTime = $endTime->copy()->subHours($hours);

        // Collect all durations from the time range
        for ($time = $startTime->copy(); $time <= $endTime; $time->addHour()) {
            $hour = $time->format('Y-m-d-H');
            $key = self::METRICS_CACHE_PREFIX . "durations:{$provider}:{$operation}:{$hour}";
            $hourlyDurations = Cache::get($key, []);
            $allDurations = array_merge($allDurations, $hourlyDurations);
        }

        if (empty($allDurations)) {
            return [
                'avg_response_time_ms' => 0,
                'min_response_time_ms' => null,
                'max_response_time_ms' => null,
                'p95_response_time_ms' => null,
            ];
        }

        sort($allDurations);
        $count = count($allDurations);
        $p95Index = (int) ceil($count * 0.95) - 1;

        return [
            'avg_response_time_ms' => round(array_sum($allDurations) / $count, 2),
            'min_response_time_ms' => min($allDurations),
            'max_response_time_ms' => max($allDurations),
            'p95_response_time_ms' => $allDurations[$p95Index] ?? null,
        ];
    }

    /**
     * Get average response time across all operations for a provider.
     */
    private function getAverageResponseTime(string $provider, int $hours): float
    {
        $operations = ['upload', 'download', 'delete', 'list', 'auth'];
        $totalDuration = 0;
        $totalOperations = 0;

        foreach ($operations as $operation) {
            $stats = $this->getResponseTimeStatistics($provider, $operation, $hours);
            if ($stats['avg_response_time_ms'] > 0) {
                $operationCount = $this->getOperationCount($provider, $operation, $hours);
                $totalDuration += $stats['avg_response_time_ms'] * $operationCount;
                $totalOperations += $operationCount;
            }
        }

        return $totalOperations > 0 ? round($totalDuration / $totalOperations, 2) : 0;
    }

    /**
     * Get operation count for a provider and operation.
     */
    private function getOperationCount(string $provider, string $operation, int $hours): int
    {
        $count = 0;
        $endTime = now();
        $startTime = $endTime->copy()->subHours($hours);

        for ($time = $startTime->copy(); $time <= $endTime; $time->addHour()) {
            $hour = $time->format('Y-m-d-H');
            $count += $this->getHourlyMetric($provider, $operation, 'total_operations', $hour);
        }

        return $count;
    }

    /**
     * Get average throughput for file operations.
     */
    private function getAverageThroughput(string $provider, string $operation, int $hours): float
    {
        $allThroughputs = [];
        $endTime = now();
        $startTime = $endTime->copy()->subHours($hours);

        for ($time = $startTime->copy(); $time <= $endTime; $time->addHour()) {
            $hour = $time->format('Y-m-d-H');
            $key = self::METRICS_CACHE_PREFIX . "throughput:{$provider}:{$operation}:{$hour}";
            $hourlyThroughputs = Cache::get($key, []);
            $allThroughputs = array_merge($allThroughputs, $hourlyThroughputs);
        }

        if (empty($allThroughputs)) {
            return 0;
        }

        return round(array_sum($allThroughputs) / count($allThroughputs), 2);
    }

    /**
     * Get error distribution across all operations.
     */
    private function getErrorDistribution(string $provider, int $hours): array
    {
        // This would need to be implemented based on how errors are tracked
        // For now, return empty array
        return [];
    }

    /**
     * Get error types for a specific operation.
     */
    private function getOperationErrorTypes(string $provider, string $operation, int $hours): array
    {
        // This would need to be implemented based on how error types are tracked
        // For now, return empty array
        return [];
    }

    /**
     * Get health grade based on score.
     */
    private function getHealthGrade(float $score): string
    {
        if ($score >= 95) return 'A';
        if ($score >= 85) return 'B';
        if ($score >= 75) return 'C';
        if ($score >= 65) return 'D';
        return 'F';
    }

    /**
     * Log detailed performance data.
     */
    private function logPerformanceData(
        string $provider,
        string $operation,
        User $user,
        float $durationMs,
        bool $success,
        ?string $errorType,
        array $metadata
    ): void {
        Log::channel('performance')->info('Operation performance recorded', [
            'provider' => $provider,
            'operation' => $operation,
            'user_id' => $user->id,
            'duration_ms' => $durationMs,
            'success' => $success,
            'error_type' => $errorType,
            'metadata' => $metadata,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Check for performance alerts and log them.
     */
    private function checkPerformanceAlerts(string $provider, string $operation, float $durationMs, bool $success): void
    {
        // Check response time alert
        if ($durationMs > self::ALERT_THRESHOLDS['response_time_ms']) {
            Log::channel('cloud-storage')->warning('High response time detected', [
                'provider' => $provider,
                'operation' => $operation,
                'duration_ms' => $durationMs,
                'threshold_ms' => self::ALERT_THRESHOLDS['response_time_ms'],
                'timestamp' => now()->toISOString(),
            ]);
        }

        // Additional alert checks could be added here
    }
}