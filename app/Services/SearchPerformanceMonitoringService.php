<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SearchPerformanceMonitoringService
{
    private const CACHE_PREFIX = 'search_performance:';
    private const SLOW_QUERY_THRESHOLD_MS = 100;
    private const MONITORING_WINDOW_MINUTES = 60;

    /**
     * Record search performance metrics.
     *
     * @param string $searchTerm
     * @param float $executionTimeMs
     * @param int $resultCount
     * @param int $userId
     * @return void
     */
    public function recordSearchMetrics(
        string $searchTerm,
        float $executionTimeMs,
        int $resultCount,
        int $userId
    ): void {
        $metrics = [
            'search_term' => $searchTerm,
            'execution_time_ms' => $executionTimeMs,
            'result_count' => $resultCount,
            'user_id' => $userId,
            'timestamp' => now()->toISOString(),
            'is_slow' => $executionTimeMs > self::SLOW_QUERY_THRESHOLD_MS
        ];

        // Store in cache for real-time monitoring
        $this->storeMetricsInCache($metrics);

        // Log performance data
        Log::info('Admin search performance', $metrics);

        // Alert on slow queries
        if ($metrics['is_slow']) {
            $this->handleSlowQuery($metrics);
        }
    }

    /**
     * Store metrics in cache for real-time monitoring.
     *
     * @param array $metrics
     * @return void
     */
    private function storeMetricsInCache(array $metrics): void
    {
        $cacheKey = self::CACHE_PREFIX . 'recent_searches';
        
        // Get existing metrics
        $recentSearches = Cache::get($cacheKey, []);
        
        // Add new metrics
        $recentSearches[] = $metrics;
        
        // Keep only last 100 searches
        if (count($recentSearches) > 100) {
            $recentSearches = array_slice($recentSearches, -100);
        }
        
        // Store for monitoring window
        Cache::put($cacheKey, $recentSearches, self::MONITORING_WINDOW_MINUTES * 60);
    }

    /**
     * Handle slow query detection and alerting.
     *
     * @param array $metrics
     * @return void
     */
    private function handleSlowQuery(array $metrics): void
    {
        Log::warning('Slow admin user search query detected', $metrics);

        // Track slow query frequency
        $slowQueryKey = self::CACHE_PREFIX . 'slow_queries_count';
        $slowQueryCount = Cache::increment($slowQueryKey, 1);
        
        if ($slowQueryCount === 1) {
            // Set expiration for the counter
            Cache::put($slowQueryKey, 1, self::MONITORING_WINDOW_MINUTES * 60);
        }

        // Alert if too many slow queries in the monitoring window
        if ($slowQueryCount >= 10) {
            $this->alertHighSlowQueryRate($slowQueryCount);
        }
    }

    /**
     * Alert when slow query rate is high.
     *
     * @param int $slowQueryCount
     * @return void
     */
    private function alertHighSlowQueryRate(int $slowQueryCount): void
    {
        Log::critical('High rate of slow admin user search queries detected', [
            'slow_query_count' => $slowQueryCount,
            'monitoring_window_minutes' => self::MONITORING_WINDOW_MINUTES,
            'threshold_ms' => self::SLOW_QUERY_THRESHOLD_MS,
            'recommendation' => 'Consider database optimization or query tuning'
        ]);
    }

    /**
     * Get recent search performance metrics.
     *
     * @return array
     */
    public function getRecentMetrics(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'recent_searches';
        return Cache::get($cacheKey, []);
    }

    /**
     * Get performance statistics for the monitoring window.
     *
     * @return array
     */
    public function getPerformanceStats(): array
    {
        $recentMetrics = $this->getRecentMetrics();
        
        if (empty($recentMetrics)) {
            return [
                'total_searches' => 0,
                'average_execution_time_ms' => 0,
                'slow_query_count' => 0,
                'slow_query_percentage' => 0,
                'most_common_terms' => [],
                'monitoring_window_minutes' => self::MONITORING_WINDOW_MINUTES
            ];
        }

        $totalSearches = count($recentMetrics);
        $totalExecutionTime = array_sum(array_column($recentMetrics, 'execution_time_ms'));
        $slowQueries = array_filter($recentMetrics, fn($m) => $m['is_slow']);
        $slowQueryCount = count($slowQueries);

        // Get most common search terms
        $searchTerms = array_column($recentMetrics, 'search_term');
        $termCounts = array_count_values($searchTerms);
        arsort($termCounts);
        $mostCommonTerms = array_slice($termCounts, 0, 5, true);

        return [
            'total_searches' => $totalSearches,
            'average_execution_time_ms' => round($totalExecutionTime / $totalSearches, 2),
            'slow_query_count' => $slowQueryCount,
            'slow_query_percentage' => round(($slowQueryCount / $totalSearches) * 100, 2),
            'most_common_terms' => $mostCommonTerms,
            'monitoring_window_minutes' => self::MONITORING_WINDOW_MINUTES
        ];
    }

    /**
     * Get database performance insights.
     *
     * @return array
     */
    public function getDatabaseInsights(): array
    {
        $insights = [];

        try {
            // Get table statistics
            if (DB::connection()->getDriverName() === 'mysql') {
                $tableStats = DB::select("
                    SELECT 
                        TABLE_ROWS as row_count,
                        AVG_ROW_LENGTH as avg_row_length,
                        DATA_LENGTH as data_length,
                        INDEX_LENGTH as index_length
                    FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'users'
                ");

                if (!empty($tableStats)) {
                    $stats = $tableStats[0];
                    $insights['table_stats'] = [
                        'row_count' => $stats->row_count,
                        'avg_row_length' => $stats->avg_row_length,
                        'data_size_mb' => round($stats->data_length / 1024 / 1024, 2),
                        'index_size_mb' => round($stats->index_length / 1024 / 1024, 2)
                    ];
                }

                // Get index cardinality for search indexes
                $indexStats = DB::select("
                    SELECT 
                        INDEX_NAME,
                        CARDINALITY,
                        COLUMN_NAME
                    FROM information_schema.STATISTICS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'users'
                    AND INDEX_NAME LIKE 'idx_users_%search%'
                    ORDER BY INDEX_NAME, SEQ_IN_INDEX
                ");

                $insights['search_indexes'] = array_map(function($stat) {
                    return [
                        'name' => $stat->INDEX_NAME,
                        'column' => $stat->COLUMN_NAME,
                        'cardinality' => $stat->CARDINALITY
                    ];
                }, $indexStats);
            }

        } catch (\Exception $e) {
            $insights['error'] = 'Could not retrieve database insights: ' . $e->getMessage();
        }

        return $insights;
    }

    /**
     * Generate optimization recommendations based on performance data.
     *
     * @return array
     */
    public function getOptimizationRecommendations(): array
    {
        $stats = $this->getPerformanceStats();
        $insights = $this->getDatabaseInsights();
        $recommendations = [];

        // Check average execution time
        if ($stats['average_execution_time_ms'] > 50) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'message' => 'Average search time is high (' . $stats['average_execution_time_ms'] . 'ms). Consider query optimization.',
                'action' => 'Review database indexes and query patterns'
            ];
        }

        // Check slow query percentage
        if ($stats['slow_query_percentage'] > 10) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'medium',
                'message' => $stats['slow_query_percentage'] . '% of searches are slow. Consider optimization.',
                'action' => 'Analyze slow query patterns and optimize indexes'
            ];
        }

        // Check for common search patterns
        if (!empty($stats['most_common_terms'])) {
            $topTerm = array_key_first($stats['most_common_terms']);
            $topTermCount = $stats['most_common_terms'][$topTerm];
            
            if ($topTermCount > $stats['total_searches'] * 0.2) {
                $recommendations[] = [
                    'type' => 'caching',
                    'priority' => 'low',
                    'message' => "Search term '{$topTerm}' is very common ({$topTermCount} searches). Consider caching.",
                    'action' => 'Implement result caching for frequent search terms'
                ];
            }
        }

        // Check table size
        if (isset($insights['table_stats']['row_count']) && $insights['table_stats']['row_count'] > 10000) {
            $recommendations[] = [
                'type' => 'scaling',
                'priority' => 'medium',
                'message' => 'Large user table (' . number_format($insights['table_stats']['row_count']) . ' rows). Monitor performance closely.',
                'action' => 'Consider implementing search result pagination limits or full-text search'
            ];
        }

        return $recommendations;
    }

    /**
     * Clear performance monitoring data.
     *
     * @return void
     */
    public function clearMetrics(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'recent_searches');
        Cache::forget(self::CACHE_PREFIX . 'slow_queries_count');
        
        Log::info('Search performance monitoring data cleared');
    }
}