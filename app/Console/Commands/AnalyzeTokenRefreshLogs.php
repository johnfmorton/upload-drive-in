<?php

namespace App\Console\Commands;

use App\Services\TokenRefreshMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;

/**
 * Console command for analyzing token refresh logs and generating reports
 */
class AnalyzeTokenRefreshLogs extends Command
{
    protected $signature = 'token-refresh:analyze-logs 
                           {--provider=google-drive : The cloud storage provider to analyze}
                           {--hours=24 : Number of hours to analyze}
                           {--format=table : Output format (table, json, csv)}
                           {--export= : Export results to file}';

    protected $description = 'Analyze token refresh logs for troubleshooting and performance insights';

    public function __construct(
        private TokenRefreshMonitoringService $monitoringService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $provider = $this->option('provider');
        $hours = (int) $this->option('hours');
        $format = $this->option('format');
        $exportFile = $this->option('export');

        $this->info("Analyzing token refresh logs for {$provider} (last {$hours} hours)...");

        try {
            // Get log analysis queries
            $queries = $this->monitoringService->getLogAnalysisQueries();
            
            // Analyze logs
            $analysis = $this->performLogAnalysis($provider, $hours);
            
            // Display results
            $this->displayAnalysis($analysis, $format);
            
            // Export if requested
            if ($exportFile) {
                $this->exportAnalysis($analysis, $exportFile, $format);
            }
            
            // Show recommendations
            $this->showRecommendations($analysis);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to analyze logs: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Perform comprehensive log analysis
     */
    private function performLogAnalysis(string $provider, int $hours): array
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (!File::exists($logFile)) {
            throw new \Exception('Log file not found');
        }

        $this->info('Reading log file...');
        
        // Read and parse log entries
        $logEntries = $this->parseLogEntries($logFile, $hours);
        
        $this->info("Found {$logEntries->count()} relevant log entries");
        
        // Analyze different aspects
        return [
            'summary' => $this->analyzeSummary($logEntries, $provider),
            'error_patterns' => $this->analyzeErrorPatterns($logEntries, $provider),
            'performance' => $this->analyzePerformance($logEntries, $provider),
            'user_impact' => $this->analyzeUserImpact($logEntries, $provider),
            'time_patterns' => $this->analyzeTimePatterns($logEntries, $provider),
            'recommendations' => []
        ];
    }

    /**
     * Parse log entries from the log file
     */
    private function parseLogEntries(string $logFile, int $hours): \Illuminate\Support\Collection
    {
        $cutoffTime = now()->subHours($hours);
        $entries = collect();
        
        $handle = fopen($logFile, 'r');
        if (!$handle) {
            throw new \Exception('Cannot open log file');
        }

        while (($line = fgets($handle)) !== false) {
            if ($this->isTokenRefreshLogEntry($line)) {
                $entry = $this->parseLogEntry($line);
                if ($entry && $entry['timestamp']->gte($cutoffTime)) {
                    $entries->push($entry);
                }
            }
        }

        fclose($handle);
        
        return $entries->sortBy('timestamp');
    }

    /**
     * Check if a log line is related to token refresh
     */
    private function isTokenRefreshLogEntry(string $line): bool
    {
        return str_contains($line, 'token_refresh') || 
               str_contains($line, 'Token refresh') ||
               str_contains($line, 'health_validation') ||
               str_contains($line, 'api_connectivity_test');
    }

    /**
     * Parse a single log entry
     */
    private function parseLogEntry(string $line): ?array
    {
        // Extract timestamp
        if (!preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            return null;
        }

        try {
            $timestamp = Carbon::createFromFormat('Y-m-d H:i:s', $matches[1]);
        } catch (\Exception $e) {
            return null;
        }

        // Extract JSON context if present
        $context = [];
        if (preg_match('/\{.*\}$/', $line, $jsonMatches)) {
            $jsonData = json_decode($jsonMatches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $context = $jsonData;
            }
        }

        // Determine log level
        $level = 'info';
        if (str_contains($line, '.ERROR:')) {
            $level = 'error';
        } elseif (str_contains($line, '.WARNING:')) {
            $level = 'warning';
        }

        return [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $line,
            'context' => $context,
            'raw' => $line
        ];
    }

    /**
     * Analyze summary statistics
     */
    private function analyzeSummary(\Illuminate\Support\Collection $entries, string $provider): array
    {
        $tokenRefreshEntries = $entries->filter(function ($entry) {
            return isset($entry['context']['event']) && 
                   str_contains($entry['context']['event'], 'token_refresh');
        });

        $successEntries = $tokenRefreshEntries->filter(function ($entry) {
            return $entry['context']['event'] === 'token_refresh_success';
        });

        $failureEntries = $tokenRefreshEntries->filter(function ($entry) {
            return $entry['context']['event'] === 'token_refresh_failure';
        });

        $totalOperations = $successEntries->count() + $failureEntries->count();
        $successRate = $totalOperations > 0 ? $successEntries->count() / $totalOperations : 0;

        return [
            'total_entries' => $entries->count(),
            'token_refresh_operations' => $totalOperations,
            'successful_operations' => $successEntries->count(),
            'failed_operations' => $failureEntries->count(),
            'success_rate' => round($successRate, 4),
            'unique_users' => $tokenRefreshEntries->pluck('context.user_id')->unique()->count(),
            'time_range' => [
                'start' => $entries->first()['timestamp'] ?? null,
                'end' => $entries->last()['timestamp'] ?? null
            ]
        ];
    }

    /**
     * Analyze error patterns
     */
    private function analyzeErrorPatterns(\Illuminate\Support\Collection $entries, string $provider): array
    {
        $errorEntries = $entries->filter(function ($entry) {
            return $entry['level'] === 'error' || 
                   (isset($entry['context']['event']) && $entry['context']['event'] === 'token_refresh_failure');
        });

        $errorsByType = $errorEntries->groupBy('context.error_type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'percentage' => 0, // Will be calculated below
                'sample_messages' => $group->take(3)->pluck('context.error_message')->toArray(),
                'affected_users' => $group->pluck('context.user_id')->unique()->count()
            ];
        });

        $totalErrors = $errorEntries->count();
        if ($totalErrors > 0) {
            $errorsByType = $errorsByType->map(function ($data) use ($totalErrors) {
                $data['percentage'] = round(($data['count'] / $totalErrors) * 100, 2);
                return $data;
            });
        }

        return [
            'total_errors' => $totalErrors,
            'error_types' => $errorsByType->toArray(),
            'most_common_error' => $errorsByType->sortByDesc('count')->keys()->first(),
            'error_timeline' => $this->getErrorTimeline($errorEntries)
        ];
    }

    /**
     * Analyze performance metrics
     */
    private function analyzePerformance(\Illuminate\Support\Collection $entries, string $provider): array
    {
        $performanceEntries = $entries->filter(function ($entry) {
            return isset($entry['context']['duration_ms']);
        });

        $durations = $performanceEntries->pluck('context.duration_ms')->filter();

        if ($durations->isEmpty()) {
            return [
                'average_duration_ms' => 0,
                'median_duration_ms' => 0,
                'min_duration_ms' => 0,
                'max_duration_ms' => 0,
                'slow_operations_count' => 0,
                'performance_trend' => 'insufficient_data'
            ];
        }

        $sorted = $durations->sort()->values();
        $count = $sorted->count();
        $median = $count % 2 === 0 
            ? ($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2
            : $sorted[intval($count / 2)];

        return [
            'average_duration_ms' => round($durations->average(), 2),
            'median_duration_ms' => round($median, 2),
            'min_duration_ms' => $durations->min(),
            'max_duration_ms' => $durations->max(),
            'slow_operations_count' => $durations->filter(fn($d) => $d > 5000)->count(),
            'performance_trend' => $this->calculatePerformanceTrend($performanceEntries)
        ];
    }

    /**
     * Analyze user impact
     */
    private function analyzeUserImpact(\Illuminate\Support\Collection $entries, string $provider): array
    {
        $userEntries = $entries->filter(function ($entry) {
            return isset($entry['context']['user_id']);
        })->groupBy('context.user_id');

        $userStats = $userEntries->map(function ($userLogs, $userId) {
            $failures = $userLogs->filter(function ($entry) {
                return $entry['level'] === 'error' || 
                       (isset($entry['context']['event']) && $entry['context']['event'] === 'token_refresh_failure');
            });

            return [
                'user_id' => $userId,
                'total_operations' => $userLogs->count(),
                'failures' => $failures->count(),
                'failure_rate' => $userLogs->count() > 0 ? round($failures->count() / $userLogs->count(), 4) : 0,
                'last_activity' => $userLogs->max('timestamp')
            ];
        })->sortByDesc('failure_rate');

        return [
            'total_affected_users' => $userStats->count(),
            'users_with_failures' => $userStats->filter(fn($u) => $u['failures'] > 0)->count(),
            'most_affected_users' => $userStats->take(5)->toArray(),
            'average_failure_rate' => round($userStats->avg('failure_rate'), 4)
        ];
    }

    /**
     * Analyze time patterns
     */
    private function analyzeTimePatterns(\Illuminate\Support\Collection $entries, string $provider): array
    {
        $hourlyStats = $entries->groupBy(function ($entry) {
            return $entry['timestamp']->format('H');
        })->map(function ($hourEntries, $hour) {
            $failures = $hourEntries->filter(fn($e) => $e['level'] === 'error');
            return [
                'hour' => (int) $hour,
                'total_operations' => $hourEntries->count(),
                'failures' => $failures->count(),
                'failure_rate' => $hourEntries->count() > 0 ? round($failures->count() / $hourEntries->count(), 4) : 0
            ];
        })->sortBy('hour');

        $peakHour = $hourlyStats->sortByDesc('total_operations')->first();
        $worstHour = $hourlyStats->sortByDesc('failure_rate')->first();

        return [
            'hourly_distribution' => $hourlyStats->toArray(),
            'peak_activity_hour' => $peakHour['hour'] ?? null,
            'worst_performance_hour' => $worstHour['hour'] ?? null,
            'business_hours_impact' => $this->calculateBusinessHoursImpact($hourlyStats)
        ];
    }

    /**
     * Display analysis results
     */
    private function displayAnalysis(array $analysis, string $format): void
    {
        switch ($format) {
            case 'json':
                $this->line(json_encode($analysis, JSON_PRETTY_PRINT));
                break;
            case 'csv':
                $this->displayCsvFormat($analysis);
                break;
            default:
                $this->displayTableFormat($analysis);
        }
    }

    /**
     * Display results in table format
     */
    private function displayTableFormat(array $analysis): void
    {
        $this->info("\n=== TOKEN REFRESH LOG ANALYSIS ===\n");

        // Summary
        $this->info("📊 SUMMARY");
        $summary = $analysis['summary'];
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Log Entries', number_format($summary['total_entries'])],
                ['Token Refresh Operations', number_format($summary['token_refresh_operations'])],
                ['Success Rate', ($summary['success_rate'] * 100) . '%'],
                ['Unique Users Affected', number_format($summary['unique_users'])],
            ]
        );

        // Error Patterns
        if (!empty($analysis['error_patterns']['error_types'])) {
            $this->info("\n🚨 ERROR PATTERNS");
            $errorData = [];
            foreach ($analysis['error_patterns']['error_types'] as $type => $data) {
                $errorData[] = [
                    $type,
                    $data['count'],
                    $data['percentage'] . '%',
                    $data['affected_users']
                ];
            }
            $this->table(
                ['Error Type', 'Count', 'Percentage', 'Affected Users'],
                $errorData
            );
        }

        // Performance
        $this->info("\n⚡ PERFORMANCE METRICS");
        $perf = $analysis['performance'];
        $this->table(
            ['Metric', 'Value'],
            [
                ['Average Duration', $perf['average_duration_ms'] . 'ms'],
                ['Median Duration', $perf['median_duration_ms'] . 'ms'],
                ['Slow Operations (>5s)', number_format($perf['slow_operations_count'])],
                ['Performance Trend', $perf['performance_trend']],
            ]
        );

        // User Impact
        if (!empty($analysis['user_impact']['most_affected_users'])) {
            $this->info("\n👥 MOST AFFECTED USERS");
            $userData = [];
            foreach ($analysis['user_impact']['most_affected_users'] as $user) {
                $userData[] = [
                    $user['user_id'],
                    $user['total_operations'],
                    $user['failures'],
                    ($user['failure_rate'] * 100) . '%'
                ];
            }
            $this->table(
                ['User ID', 'Operations', 'Failures', 'Failure Rate'],
                $userData
            );
        }
    }

    /**
     * Export analysis to file
     */
    private function exportAnalysis(array $analysis, string $filename, string $format): void
    {
        $content = match ($format) {
            'json' => json_encode($analysis, JSON_PRETTY_PRINT),
            'csv' => $this->convertToCsv($analysis),
            default => json_encode($analysis, JSON_PRETTY_PRINT)
        };

        File::put($filename, $content);
        $this->info("Analysis exported to: {$filename}");
    }

    /**
     * Show recommendations based on analysis
     */
    private function showRecommendations(array $analysis): void
    {
        $this->info("\n💡 RECOMMENDATIONS");

        $recommendations = [];

        // High failure rate
        if ($analysis['summary']['success_rate'] < 0.9) {
            $recommendations[] = "🔴 High failure rate detected. Investigate API connectivity and token validity.";
        }

        // Slow operations
        if ($analysis['performance']['slow_operations_count'] > 0) {
            $recommendations[] = "🟡 Slow operations detected. Review network latency and API response times.";
        }

        // Error patterns
        if (!empty($analysis['error_patterns']['most_common_error'])) {
            $commonError = $analysis['error_patterns']['most_common_error'];
            $recommendations[] = "🔍 Most common error: {$commonError}. Focus troubleshooting efforts here.";
        }

        // User impact
        if ($analysis['user_impact']['users_with_failures'] > 5) {
            $recommendations[] = "👥 Multiple users affected. Consider system-wide issue rather than individual problems.";
        }

        if (empty($recommendations)) {
            $recommendations[] = "✅ System appears to be operating normally based on log analysis.";
        }

        foreach ($recommendations as $recommendation) {
            $this->line($recommendation);
        }
    }

    /**
     * Helper methods for analysis
     */
    private function getErrorTimeline(\Illuminate\Support\Collection $errorEntries): array
    {
        return $errorEntries->groupBy(function ($entry) {
            return $entry['timestamp']->format('Y-m-d H:00');
        })->map(function ($group) {
            return $group->count();
        })->toArray();
    }

    private function calculatePerformanceTrend(\Illuminate\Support\Collection $entries): string
    {
        if ($entries->count() < 10) {
            return 'insufficient_data';
        }

        $recent = $entries->sortBy('timestamp')->take(-5)->avg('context.duration_ms');
        $older = $entries->sortBy('timestamp')->take(5)->avg('context.duration_ms');

        if ($recent > $older * 1.2) {
            return 'degrading';
        } elseif ($recent < $older * 0.8) {
            return 'improving';
        } else {
            return 'stable';
        }
    }

    private function calculateBusinessHoursImpact(\Illuminate\Support\Collection $hourlyStats): array
    {
        $businessHours = $hourlyStats->filter(fn($h) => $h['hour'] >= 9 && $h['hour'] <= 17);
        $offHours = $hourlyStats->filter(fn($h) => $h['hour'] < 9 || $h['hour'] > 17);

        return [
            'business_hours_failure_rate' => $businessHours->avg('failure_rate'),
            'off_hours_failure_rate' => $offHours->avg('failure_rate'),
            'business_hours_operations' => $businessHours->sum('total_operations'),
            'off_hours_operations' => $offHours->sum('total_operations')
        ];
    }

    private function convertToCsv(array $analysis): string
    {
        // Simple CSV conversion for summary data
        $csv = "Metric,Value\n";
        foreach ($analysis['summary'] as $key => $value) {
            $csv .= "{$key}," . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
        return $csv;
    }

    private function displayCsvFormat(array $analysis): void
    {
        $this->line($this->convertToCsv($analysis));
    }
}