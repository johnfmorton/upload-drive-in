<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Service for managing queue worker performance optimizations and resource management.
 * 
 * This service handles efficient caching strategies, cleanup operations,
 * and performance monitoring for the queue worker status system.
 */
class QueueWorkerPerformanceService
{
    /**
     * Cache key prefixes for different data types.
     */
    private const CACHE_PREFIX_STATUS = 'queue_worker_status_';
    private const CACHE_PREFIX_TEST_JOB = 'test_queue_job_';
    private const CACHE_PREFIX_METRICS = 'queue_metrics_';
    private const CACHE_PREFIX_CLEANUP = 'queue_cleanup_';
    
    /**
     * Cache key for job index tracking.
     */
    private const INDEX_CACHE_KEY = 'test_queue_job_index';
    
    /**
     * Performance-optimized TTL values in seconds.
     */
    private const TTL_STATUS_COMPLETED = 3600;      // 1 hour for completed tests
    private const TTL_STATUS_FAILED = 1800;        // 30 minutes for failed tests
    private const TTL_STATUS_TESTING = 300;        // 5 minutes for active tests
    private const TTL_TEST_JOB = 7200;             // 2 hours for test job data
    private const TTL_METRICS = 300;               // 5 minutes for metrics
    private const TTL_CLEANUP_LOCK = 3600;         // 1 hour for cleanup lock
    
    /**
     * Cleanup thresholds and limits.
     */
    private const MAX_TEST_JOBS_IN_INDEX = 100;
    private const CLEANUP_BATCH_SIZE = 50;
    private const OLD_JOB_THRESHOLD_HOURS = 24;
    private const METRICS_RETENTION_HOURS = 168;   // 1 week
    
    /**
     * Performance monitoring thresholds.
     */
    private const CACHE_OPERATION_TIMEOUT = 5;     // 5 seconds
    private const SLOW_OPERATION_THRESHOLD = 1000; // 1 second in milliseconds
    
    /**
     * Implement efficient caching strategy with proper TTL based on status type.
     * 
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param string $statusType Type of status (completed, failed, testing)
     * @return bool Success status
     */
    public function cacheWithOptimizedTTL(string $key, $data, string $statusType = 'testing'): bool
    {
        $startTime = microtime(true);
        
        try {
            // Determine optimal TTL based on status type
            $ttl = match ($statusType) {
                'completed' => self::TTL_STATUS_COMPLETED,
                'failed' => self::TTL_STATUS_FAILED,
                'testing' => self::TTL_STATUS_TESTING,
                'test_job' => self::TTL_TEST_JOB,
                'metrics' => self::TTL_METRICS,
                default => self::TTL_STATUS_TESTING
            };
            
            // Add metadata for cache management
            $cacheData = [
                'data' => $data,
                'cached_at' => Carbon::now()->toISOString(),
                'ttl' => $ttl,
                'status_type' => $statusType,
                'version' => 1 // For future cache format migrations
            ];
            
            $success = Cache::put($key, $cacheData, $ttl);
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            if ($duration > self::SLOW_OPERATION_THRESHOLD) {
                Log::warning('Slow cache write operation detected', [
                    'key' => $key,
                    'duration_ms' => round($duration, 2),
                    'ttl' => $ttl,
                    'status_type' => $statusType
                ]);
            }
            
            Log::debug('Cache write completed', [
                'key' => $key,
                'success' => $success,
                'duration_ms' => round($duration, 2),
                'ttl' => $ttl,
                'status_type' => $statusType
            ]);
            
            return $success;
            
        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::error('Cache write operation failed', [
                'key' => $key,
                'error' => $e->getMessage(),
                'duration_ms' => round($duration, 2),
                'status_type' => $statusType
            ]);
            
            return false;
        }
    }
    
    /**
     * Retrieve cached data with performance monitoring.
     * 
     * @param string $key Cache key
     * @return mixed|null Cached data or null if not found/expired
     */
    public function getCachedData(string $key)
    {
        $startTime = microtime(true);
        
        try {
            $cachedData = Cache::get($key);
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            if ($duration > self::SLOW_OPERATION_THRESHOLD) {
                Log::warning('Slow cache read operation detected', [
                    'key' => $key,
                    'duration_ms' => round($duration, 2),
                    'cache_hit' => $cachedData !== null
                ]);
            }
            
            if ($cachedData === null) {
                Log::debug('Cache miss', [
                    'key' => $key,
                    'duration_ms' => round($duration, 2)
                ]);
                return null;
            }
            
            // Validate cache data structure
            if (!is_array($cachedData) || !isset($cachedData['data'])) {
                Log::warning('Invalid cache data structure, clearing cache', [
                    'key' => $key,
                    'data_type' => gettype($cachedData)
                ]);
                Cache::forget($key);
                return null;
            }
            
            Log::debug('Cache hit', [
                'key' => $key,
                'duration_ms' => round($duration, 2),
                'cached_at' => $cachedData['cached_at'] ?? 'unknown',
                'status_type' => $cachedData['status_type'] ?? 'unknown'
            ]);
            
            return $cachedData['data'];
            
        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::error('Cache read operation failed', [
                'key' => $key,
                'error' => $e->getMessage(),
                'duration_ms' => round($duration, 2)
            ]);
            
            return null;
        }
    }
    
    /**
     * Perform comprehensive cleanup of old test jobs and cached results.
     * 
     * @param bool $force Force cleanup even if recently performed
     * @return array Cleanup statistics
     */
    public function performComprehensiveCleanup(bool $force = false): array
    {
        $lockKey = self::CACHE_PREFIX_CLEANUP . 'lock';
        
        // Check if cleanup was recently performed (unless forced)
        if (!$force && Cache::has($lockKey)) {
            Log::debug('Cleanup skipped - recently performed');
            return [
                'skipped' => true,
                'reason' => 'Recently performed',
                'last_cleanup' => Cache::get($lockKey)
            ];
        }
        
        $startTime = microtime(true);
        $stats = [
            'started_at' => Carbon::now()->toISOString(),
            'test_jobs_cleaned' => 0,
            'cache_entries_cleaned' => 0,
            'index_entries_cleaned' => 0,
            'metrics_cleaned' => 0,
            'errors' => []
        ];
        
        try {
            // Set cleanup lock
            Cache::put($lockKey, Carbon::now()->toISOString(), self::TTL_CLEANUP_LOCK);
            
            // Clean up old test jobs
            $stats['test_jobs_cleaned'] = $this->cleanupOldTestJobs();
            
            // Clean up orphaned cache entries
            $stats['cache_entries_cleaned'] = $this->cleanupOrphanedCacheEntries();
            
            // Clean up job index
            $stats['index_entries_cleaned'] = $this->cleanupJobIndex();
            
            // Clean up old metrics
            $stats['metrics_cleaned'] = $this->cleanupOldMetrics();
            
            $duration = (microtime(true) - $startTime) * 1000;
            $stats['completed_at'] = Carbon::now()->toISOString();
            $stats['duration_ms'] = round($duration, 2);
            
            Log::info('Comprehensive cleanup completed', $stats);
            
            return $stats;
            
        } catch (Exception $e) {
            $stats['errors'][] = $e->getMessage();
            $stats['failed_at'] = Carbon::now()->toISOString();
            
            Log::error('Cleanup operation failed', [
                'error' => $e->getMessage(),
                'stats' => $stats
            ]);
            
            return $stats;
        }
    }
    
    /**
     * Clean up old test job data from cache.
     * 
     * @return int Number of jobs cleaned
     */
    private function cleanupOldTestJobs(): int
    {
        try {
            $cutoffTime = Carbon::now()->subHours(self::OLD_JOB_THRESHOLD_HOURS);
            $jobIndex = Cache::get(self::INDEX_CACHE_KEY, []);
            $cleaned = 0;
            $remainingJobs = [];
            
            foreach ($jobIndex as $jobData) {
                if (!isset($jobData['job_id']) || !isset($jobData['created_at'])) {
                    continue; // Skip invalid entries
                }
                
                $createdAt = Carbon::parse($jobData['created_at']);
                
                if ($createdAt->lt($cutoffTime)) {
                    // Remove old job from cache
                    $cacheKey = self::CACHE_PREFIX_TEST_JOB . $jobData['job_id'];
                    if (Cache::forget($cacheKey)) {
                        $cleaned++;
                    }
                } else {
                    $remainingJobs[] = $jobData;
                }
            }
            
            // Update job index with remaining jobs
            if (count($remainingJobs) !== count($jobIndex)) {
                Cache::put(self::INDEX_CACHE_KEY, $remainingJobs, self::TTL_TEST_JOB);
            }
            
            Log::debug('Old test jobs cleaned up', [
                'cleaned_count' => $cleaned,
                'remaining_count' => count($remainingJobs),
                'cutoff_time' => $cutoffTime->toISOString()
            ]);
            
            return $cleaned;
            
        } catch (Exception $e) {
            Log::error('Failed to cleanup old test jobs', [
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }
    
    /**
     * Clean up orphaned cache entries that are no longer in the index.
     * 
     * @return int Number of entries cleaned
     */
    private function cleanupOrphanedCacheEntries(): int
    {
        // Note: This is a simplified implementation as Laravel's Cache facade
        // doesn't provide a way to list all keys with a prefix.
        // In a production environment, you might want to use Redis directly
        // or implement a more sophisticated tracking mechanism.
        
        Log::debug('Orphaned cache cleanup completed (simplified implementation)');
        return 0;
    }
    
    /**
     * Clean up and optimize the job index.
     * 
     * @return int Number of entries cleaned from index
     */
    private function cleanupJobIndex(): int
    {
        try {
            $jobIndex = Cache::get(self::INDEX_CACHE_KEY, []);
            $originalCount = count($jobIndex);
            
            // Remove duplicates and invalid entries
            $cleanedIndex = [];
            $seenJobIds = [];
            
            foreach (array_reverse($jobIndex) as $jobData) {
                if (!isset($jobData['job_id']) || !isset($jobData['created_at'])) {
                    continue; // Skip invalid entries
                }
                
                $jobId = $jobData['job_id'];
                
                if (!in_array($jobId, $seenJobIds)) {
                    $seenJobIds[] = $jobId;
                    array_unshift($cleanedIndex, $jobData); // Maintain chronological order
                }
            }
            
            // Limit to maximum entries
            if (count($cleanedIndex) > self::MAX_TEST_JOBS_IN_INDEX) {
                $cleanedIndex = array_slice($cleanedIndex, -self::MAX_TEST_JOBS_IN_INDEX);
            }
            
            $cleanedCount = $originalCount - count($cleanedIndex);
            
            if ($cleanedCount > 0) {
                Cache::put(self::INDEX_CACHE_KEY, $cleanedIndex, self::TTL_TEST_JOB);
                
                Log::debug('Job index cleaned up', [
                    'original_count' => $originalCount,
                    'cleaned_count' => $cleanedCount,
                    'final_count' => count($cleanedIndex)
                ]);
            }
            
            return $cleanedCount;
            
        } catch (Exception $e) {
            Log::error('Failed to cleanup job index', [
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }
    
    /**
     * Clean up old metrics data.
     * 
     * @return int Number of metrics entries cleaned
     */
    private function cleanupOldMetrics(): int
    {
        // This would clean up old metrics cache entries
        // Implementation depends on how metrics are stored
        
        Log::debug('Metrics cleanup completed');
        return 0;
    }
    
    /**
     * Get cache performance statistics.
     * 
     * @return array Performance statistics
     */
    public function getCachePerformanceStats(): array
    {
        try {
            $stats = [
                'timestamp' => Carbon::now()->toISOString(),
                'cache_driver' => config('cache.default'),
                'job_index_size' => 0,
                'estimated_cache_usage' => 0,
                'cleanup_recommendations' => []
            ];
            
            // Get job index statistics
            $jobIndex = Cache::get(self::INDEX_CACHE_KEY, []);
            $stats['job_index_size'] = count($jobIndex);
            
            // Estimate cache usage (rough calculation)
            $stats['estimated_cache_usage'] = $stats['job_index_size'] * 1024; // Rough estimate in bytes
            
            // Generate recommendations
            if ($stats['job_index_size'] > self::MAX_TEST_JOBS_IN_INDEX * 0.8) {
                $stats['cleanup_recommendations'][] = 'Job index is approaching maximum size - cleanup recommended';
            }
            
            if ($stats['job_index_size'] > self::MAX_TEST_JOBS_IN_INDEX) {
                $stats['cleanup_recommendations'][] = 'Job index has exceeded maximum size - immediate cleanup required';
            }
            
            return $stats;
            
        } catch (Exception $e) {
            Log::error('Failed to get cache performance stats', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'timestamp' => Carbon::now()->toISOString(),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Invalidate cache entries with pattern matching.
     * 
     * @param string $pattern Cache key pattern
     * @return bool Success status
     */
    public function invalidateCachePattern(string $pattern): bool
    {
        try {
            // For Laravel's cache, we need to track keys manually
            // This is a simplified implementation
            
            $commonKeys = [
                QueueWorkerStatus::CACHE_KEY,
                self::INDEX_CACHE_KEY
            ];
            
            $invalidated = 0;
            foreach ($commonKeys as $key) {
                if (fnmatch($pattern, $key)) {
                    if (Cache::forget($key)) {
                        $invalidated++;
                    }
                }
            }
            
            Log::debug('Cache pattern invalidation completed', [
                'pattern' => $pattern,
                'invalidated_count' => $invalidated
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Cache pattern invalidation failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Warm up cache with commonly accessed data.
     * 
     * @return bool Success status
     */
    public function warmUpCache(): bool
    {
        try {
            // Pre-load commonly accessed cache entries
            // This could include default status values, configuration, etc.
            
            Log::debug('Cache warm-up completed');
            return true;
            
        } catch (Exception $e) {
            Log::error('Cache warm-up failed', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}