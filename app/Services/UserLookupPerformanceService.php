<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UserLookupPerformanceService
{
    private const PERFORMANCE_CACHE_KEY = 'user_lookup_performance_stats';
    private const PERFORMANCE_CACHE_TTL = 300; // 5 minutes
    private const SLOW_QUERY_THRESHOLD_MS = 100;

    /**
     * Perform optimized user lookup with performance monitoring
     *
     * @param string $email
     * @return User|null
     */
    public function findUserByEmail(string $email): ?User
    {
        $startTime = microtime(true);
        $queryCount = 0;
        
        // Enable query logging for this operation
        DB::enableQueryLog();
        
        try {
            // Optimized query with covering index
            $user = User::select(['id', 'email', 'role', 'created_at', 'name', 'username'])
                ->where('email', $email)
                ->first();
            
            $queries = DB::getQueryLog();
            $queryCount = count($queries);
            
            $totalTime = (microtime(true) - $startTime) * 1000;
            
            // Log performance metrics
            $this->logPerformanceMetrics($email, $totalTime, $queryCount, $queries, (bool)$user);
            
            // Update performance statistics
            $this->updatePerformanceStats($totalTime, $queryCount, (bool)$user);
            
            return $user;
            
        } catch (\Exception $e) {
            $totalTime = (microtime(true) - $startTime) * 1000;
            
            Log::error('User lookup failed with exception', [
                'email' => $email,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'execution_time_ms' => round($totalTime, 2),
                'query_count' => $queryCount,
                'context' => 'user_lookup_performance'
            ]);
            
            throw $e;
        } finally {
            DB::disableQueryLog();
        }
    }

    /**
     * Log detailed performance metrics for user lookup
     *
     * @param string $email
     * @param float $totalTime
     * @param int $queryCount
     * @param array $queries
     * @param bool $userFound
     * @return void
     */
    private function logPerformanceMetrics(string $email, float $totalTime, int $queryCount, array $queries, bool $userFound): void
    {
        $isSlowQuery = $totalTime > self::SLOW_QUERY_THRESHOLD_MS;
        
        $logData = [
            'email' => $email,
            'execution_time_ms' => round($totalTime, 2),
            'query_count' => $queryCount,
            'user_found' => $userFound,
            'is_slow_query' => $isSlowQuery,
            'slow_query_threshold_ms' => self::SLOW_QUERY_THRESHOLD_MS,
            'context' => 'user_lookup_performance',
            'timestamp' => now()->toISOString()
        ];
        
        // Add query details for slow queries
        if ($isSlowQuery && !empty($queries)) {
            $logData['query_details'] = array_map(function ($query) {
                return [
                    'sql' => $query['query'],
                    'bindings' => $query['bindings'],
                    'time_ms' => $query['time'] ?? null
                ];
            }, $queries);
        }
        
        if ($isSlowQuery) {
            Log::warning('Slow user lookup query detected', $logData);
        } else {
            Log::debug('User lookup performance', $logData);
        }
    }

    /**
     * Update performance statistics in cache
     *
     * @param float $executionTime
     * @param int $queryCount
     * @param bool $userFound
     * @return void
     */
    private function updatePerformanceStats(float $executionTime, int $queryCount, bool $userFound): void
    {
        try {
            $stats = Cache::get(self::PERFORMANCE_CACHE_KEY, [
                'total_lookups' => 0,
                'successful_lookups' => 0,
                'failed_lookups' => 0,
                'total_execution_time_ms' => 0,
                'total_queries' => 0,
                'slow_queries' => 0,
                'min_time_ms' => null,
                'max_time_ms' => null,
                'last_updated' => null
            ]);
            
            $stats['total_lookups']++;
            $stats['total_execution_time_ms'] += $executionTime;
            $stats['total_queries'] += $queryCount;
            $stats['last_updated'] = now()->toISOString();
            
            if ($userFound) {
                $stats['successful_lookups']++;
            } else {
                $stats['failed_lookups']++;
            }
            
            if ($executionTime > self::SLOW_QUERY_THRESHOLD_MS) {
                $stats['slow_queries']++;
            }
            
            // Update min/max times
            if ($stats['min_time_ms'] === null || $executionTime < $stats['min_time_ms']) {
                $stats['min_time_ms'] = $executionTime;
            }
            
            if ($stats['max_time_ms'] === null || $executionTime > $stats['max_time_ms']) {
                $stats['max_time_ms'] = $executionTime;
            }
            
            Cache::put(self::PERFORMANCE_CACHE_KEY, $stats, self::PERFORMANCE_CACHE_TTL);
            
        } catch (\Exception $e) {
            Log::error('Failed to update user lookup performance stats', [
                'error' => $e->getMessage(),
                'context' => 'user_lookup_performance'
            ]);
        }
    }

    /**
     * Get current performance statistics
     *
     * @return array
     */
    public function getPerformanceStats(): array
    {
        $stats = Cache::get(self::PERFORMANCE_CACHE_KEY, []);
        
        if (!empty($stats) && $stats['total_lookups'] > 0) {
            $stats['average_time_ms'] = round($stats['total_execution_time_ms'] / $stats['total_lookups'], 2);
            $stats['average_queries_per_lookup'] = round($stats['total_queries'] / $stats['total_lookups'], 2);
            $stats['success_rate'] = round(($stats['successful_lookups'] / $stats['total_lookups']) * 100, 2);
            $stats['slow_query_rate'] = round(($stats['slow_queries'] / $stats['total_lookups']) * 100, 2);
        }
        
        return $stats;
    }

    /**
     * Clear performance statistics
     *
     * @return void
     */
    public function clearPerformanceStats(): void
    {
        Cache::forget(self::PERFORMANCE_CACHE_KEY);
        
        Log::info('User lookup performance stats cleared', [
            'context' => 'user_lookup_performance'
        ]);
    }

    /**
     * Check if user lookup performance is healthy
     *
     * @return array
     */
    public function checkPerformanceHealth(): array
    {
        $stats = $this->getPerformanceStats();
        
        if (empty($stats) || $stats['total_lookups'] === 0) {
            return [
                'status' => 'no_data',
                'message' => 'No performance data available',
                'recommendations' => []
            ];
        }
        
        $issues = [];
        $recommendations = [];
        
        // Check average execution time
        if (isset($stats['average_time_ms']) && $stats['average_time_ms'] > self::SLOW_QUERY_THRESHOLD_MS) {
            $issues[] = 'High average execution time';
            $recommendations[] = 'Consider optimizing database indexes';
        }
        
        // Check slow query rate
        if (isset($stats['slow_query_rate']) && $stats['slow_query_rate'] > 10) {
            $issues[] = 'High slow query rate';
            $recommendations[] = 'Review query optimization and database performance';
        }
        
        // Check success rate
        if (isset($stats['success_rate']) && $stats['success_rate'] < 95) {
            $issues[] = 'Low success rate';
            $recommendations[] = 'Investigate database connection issues';
        }
        
        $status = empty($issues) ? 'healthy' : 'needs_attention';
        
        return [
            'status' => $status,
            'issues' => $issues,
            'recommendations' => $recommendations,
            'stats' => $stats
        ];
    }
}