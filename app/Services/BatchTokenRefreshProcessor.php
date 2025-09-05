<?php

namespace App\Services;

use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\TokenRefreshCoordinator;
use App\Services\GoogleApiConnectionPool;
use App\Services\TokenExpirationQueryOptimizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Batch processing service for multiple token refresh operations.
 * Optimizes performance by processing tokens in batches with connection pooling.
 */
class BatchTokenRefreshProcessor
{
    // Batch processing configuration
    private const DEFAULT_BATCH_SIZE = 20;
    private const MAX_CONCURRENT_BATCHES = 5;
    private const BATCH_TIMEOUT = 300; // 5 minutes
    private const RETRY_DELAY = 30; // 30 seconds
    
    // Cache configuration
    private const BATCH_CACHE_PREFIX = 'batch_refresh_v2';
    private const BATCH_LOCK_PREFIX = 'batch_lock_v2';
    private const BATCH_RESULTS_TTL = 1800; // 30 minutes
    
    // Performance thresholds
    private const SUCCESS_RATE_THRESHOLD = 0.8; // 80%
    private const MAX_FAILURE_RATE = 0.3; // 30%

    public function __construct(
        private readonly TokenRefreshCoordinator $refreshCoordinator,
        private readonly GoogleApiConnectionPool $connectionPool,
        private readonly TokenExpirationQueryOptimizer $queryOptimizer,
        private readonly TokenRefreshMonitoringService $monitoringService
    ) {}

    /**
     * Process batch token refresh for expiring tokens.
     * 
     * @param int $expirationMinutes Minutes from now to consider tokens expiring
     * @param int $batchSize Number of tokens to process per batch
     * @param bool $dryRun Whether to perform a dry run without actual refresh
     * @return array Batch processing results
     */
    public function processBatchRefresh(
        int $expirationMinutes = 30,
        int $batchSize = self::DEFAULT_BATCH_SIZE,
        bool $dryRun = false
    ): array {
        $batchId = uniqid('batch_', true);
        $startTime = microtime(true);
        
        Log::info('Starting batch token refresh processing', [
            'batch_id' => $batchId,
            'expiration_minutes' => $expirationMinutes,
            'batch_size' => $batchSize,
            'dry_run' => $dryRun,
        ]);
        
        // Check if another batch is already running
        if (!$this->acquireBatchLock($batchId)) {
            return [
                'success' => false,
                'error' => 'Another batch refresh is already running',
                'batch_id' => $batchId,
            ];
        }
        
        try {
            // Get expiring tokens using optimized query
            $expiringTokens = $this->queryOptimizer->getExpiringTokens($expirationMinutes);
            
            if ($expiringTokens->isEmpty()) {
                Log::info('No expiring tokens found for batch processing', [
                    'batch_id' => $batchId,
                    'expiration_minutes' => $expirationMinutes,
                ]);
                
                return [
                    'success' => true,
                    'batch_id' => $batchId,
                    'total_tokens' => 0,
                    'processed' => 0,
                    'successful' => 0,
                    'failed' => 0,
                    'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ];
            }
            
            // Process tokens in batches
            $results = $this->processBatches($expiringTokens, $batchSize, $batchId, $dryRun);
            
            // Cache results for monitoring
            $this->cacheBatchResults($batchId, $results);
            
            // Record metrics
            $this->recordBatchMetrics($batchId, $results);
            
            $totalTime = microtime(true) - $startTime;
            
            Log::info('Batch token refresh processing completed', [
                'batch_id' => $batchId,
                'total_tokens' => $results['total_tokens'],
                'successful' => $results['successful'],
                'failed' => $results['failed'],
                'success_rate' => $results['success_rate'],
                'processing_time_ms' => round($totalTime * 1000, 2),
            ]);
            
            return array_merge($results, [
                'processing_time_ms' => round($totalTime * 1000, 2),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Batch token refresh processing failed', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'batch_id' => $batchId,
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        } finally {
            $this->releaseBatchLock($batchId);
        }
    }

    /**
     * Process batch health validation for multiple users.
     * 
     * @param array $userIds Array of user IDs to validate
     * @param array $providers Array of providers to validate
     * @param int $batchSize Number of users to process per batch
     * @return array Batch validation results
     */
    public function processBatchHealthValidation(
        array $userIds,
        array $providers = ['google-drive'],
        int $batchSize = self::DEFAULT_BATCH_SIZE
    ): array {
        $batchId = uniqid('health_batch_', true);
        $startTime = microtime(true);
        
        Log::info('Starting batch health validation', [
            'batch_id' => $batchId,
            'user_count' => count($userIds),
            'providers' => $providers,
            'batch_size' => $batchSize,
        ]);
        
        $results = [
            'batch_id' => $batchId,
            'total_users' => count($userIds),
            'processed' => 0,
            'healthy' => 0,
            'unhealthy' => 0,
            'errors' => 0,
            'batches_processed' => 0,
            'validation_results' => [],
        ];
        
        try {
            // Process users in batches
            $userBatches = array_chunk($userIds, $batchSize);
            
            foreach ($userBatches as $batchIndex => $userBatch) {
                $batchResults = $this->processHealthValidationBatch(
                    $userBatch,
                    $providers,
                    $batchId,
                    $batchIndex
                );
                
                // Merge results
                $results['processed'] += $batchResults['processed'];
                $results['healthy'] += $batchResults['healthy'];
                $results['unhealthy'] += $batchResults['unhealthy'];
                $results['errors'] += $batchResults['errors'];
                $results['batches_processed']++;
                $results['validation_results'] = array_merge(
                    $results['validation_results'],
                    $batchResults['validation_results']
                );
            }
            
            // Calculate success rate
            $results['success_rate'] = $results['processed'] > 0 
                ? round($results['healthy'] / $results['processed'], 3)
                : 0;
            
            $totalTime = microtime(true) - $startTime;
            $results['processing_time_ms'] = round($totalTime * 1000, 2);
            
            Log::info('Batch health validation completed', [
                'batch_id' => $batchId,
                'processed' => $results['processed'],
                'healthy' => $results['healthy'],
                'success_rate' => $results['success_rate'],
                'processing_time_ms' => $results['processing_time_ms'],
            ]);
            
            return $results;
            
        } catch (\Exception $e) {
            Log::error('Batch health validation failed', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
            
            $results['error'] = $e->getMessage();
            $results['processing_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
            
            return $results;
        }
    }

    /**
     * Get batch processing statistics and performance metrics.
     * 
     * @param int $hours Hours of history to include
     * @return array
     */
    public function getBatchProcessingStats(int $hours = 24): array
    {
        try {
            $stats = [
                'recent_batches' => $this->getRecentBatchResults($hours),
                'performance_metrics' => $this->getPerformanceMetrics($hours),
                'connection_pool_stats' => $this->connectionPool->getPoolStats(),
                'query_optimization_stats' => $this->getQueryOptimizationStats(),
            ];
            
            return $stats;
        } catch (\Exception $e) {
            Log::error('Failed to get batch processing stats', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Optimize batch processing performance.
     * 
     * @return array Optimization results
     */
    public function optimizeBatchProcessing(): array
    {
        $results = [];
        
        try {
            // Optimize connection pool
            $poolOptimized = $this->connectionPool->optimizePool();
            $results['connection_pool_optimized'] = $poolOptimized;
            
            // Clear query caches
            $queryCachesCleared = $this->queryOptimizer->clearQueryCaches();
            $results['query_caches_cleared'] = $queryCachesCleared;
            
            // Analyze database indexes
            $indexAnalysis = $this->queryOptimizer->optimizeIndexes();
            $results['index_analysis'] = $indexAnalysis;
            
            Log::info('Batch processing optimization completed', $results);
            
        } catch (\Exception $e) {
            Log::error('Batch processing optimization failed', [
                'error' => $e->getMessage(),
            ]);
            
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Process tokens in batches with connection pooling.
     * 
     * @param Collection $tokens
     * @param int $batchSize
     * @param string $batchId
     * @param bool $dryRun
     * @return array
     */
    private function processBatches(Collection $tokens, int $batchSize, string $batchId, bool $dryRun): array
    {
        $results = [
            'batch_id' => $batchId,
            'total_tokens' => $tokens->count(),
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'batches_processed' => 0,
            'errors' => [],
        ];
        
        $batches = $tokens->chunk($batchSize);
        
        foreach ($batches as $batchIndex => $batch) {
            Log::debug('Processing token batch', [
                'batch_id' => $batchId,
                'batch_index' => $batchIndex,
                'batch_size' => $batch->count(),
            ]);
            
            $batchResults = $this->processSingleBatch($batch, $batchId, $batchIndex, $dryRun);
            
            // Merge results
            $results['processed'] += $batchResults['processed'];
            $results['successful'] += $batchResults['successful'];
            $results['failed'] += $batchResults['failed'];
            $results['batches_processed']++;
            
            if (!empty($batchResults['errors'])) {
                $results['errors'] = array_merge($results['errors'], $batchResults['errors']);
            }
            
            // Check if we should continue based on failure rate
            if ($this->shouldStopProcessing($results)) {
                Log::warning('Stopping batch processing due to high failure rate', [
                    'batch_id' => $batchId,
                    'failure_rate' => $results['failed'] / max($results['processed'], 1),
                    'processed' => $results['processed'],
                    'failed' => $results['failed'],
                ]);
                break;
            }
        }
        
        // Calculate success rate
        $results['success_rate'] = $results['processed'] > 0 
            ? round($results['successful'] / $results['processed'], 3)
            : 0;
        
        // Add overall success flag
        $results['success'] = true;
        
        return $results;
    }

    /**
     * Process a single batch of tokens.
     * 
     * @param Collection $batch
     * @param string $batchId
     * @param int $batchIndex
     * @param bool $dryRun
     * @return array
     */
    private function processSingleBatch(Collection $batch, string $batchId, int $batchIndex, bool $dryRun): array
    {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
        ];
        
        foreach ($batch as $token) {
            try {
                $results['processed']++;
                
                if ($dryRun) {
                    // Simulate processing for dry run
                    Log::debug('Dry run: would refresh token', [
                        'batch_id' => $batchId,
                        'user_id' => $token->user_id,
                        'expires_at' => $token->expires_at,
                    ]);
                    $results['successful']++;
                    continue;
                }
                
                // Perform actual token refresh
                $refreshResult = $this->refreshCoordinator->coordinateRefresh(
                    $token->user,
                    'google-drive'
                );
                
                if ($refreshResult->isSuccessful()) {
                    $results['successful']++;
                    Log::debug('Batch token refresh successful', [
                        'batch_id' => $batchId,
                        'user_id' => $token->user_id,
                        'was_already_valid' => $refreshResult->wasAlreadyValid,
                    ]);
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'user_id' => $token->user_id,
                        'error' => $refreshResult->message,
                        'error_type' => $refreshResult->errorType?->value,
                    ];
                    
                    Log::warning('Batch token refresh failed', [
                        'batch_id' => $batchId,
                        'user_id' => $token->user_id,
                        'error' => $refreshResult->message,
                    ]);
                }
                
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'user_id' => $token->user_id ?? 'unknown',
                    'error' => $e->getMessage(),
                    'error_type' => 'exception',
                ];
                
                Log::error('Exception during batch token refresh', [
                    'batch_id' => $batchId,
                    'user_id' => $token->user_id ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $results;
    }

    /**
     * Process health validation batch.
     * 
     * @param array $userIds
     * @param array $providers
     * @param string $batchId
     * @param int $batchIndex
     * @return array
     */
    private function processHealthValidationBatch(
        array $userIds,
        array $providers,
        string $batchId,
        int $batchIndex
    ): array {
        $results = [
            'processed' => 0,
            'healthy' => 0,
            'unhealthy' => 0,
            'errors' => 0,
            'validation_results' => [],
        ];
        
        foreach ($userIds as $userId) {
            try {
                $user = User::find($userId);
                if (!$user) {
                    $results['errors']++;
                    continue;
                }
                
                foreach ($providers as $provider) {
                    $results['processed']++;
                    
                    // Use the performance optimized validator
                    $validator = app(PerformanceOptimizedHealthValidator::class);
                    $healthStatus = $validator->validateConnectionHealth($user, $provider);
                    
                    if ($healthStatus->isHealthy()) {
                        $results['healthy']++;
                    } else {
                        $results['unhealthy']++;
                    }
                    
                    $results['validation_results'][] = [
                        'user_id' => $userId,
                        'provider' => $provider,
                        'status' => $healthStatus->getStatus(),
                        'is_healthy' => $healthStatus->isHealthy(),
                    ];
                }
                
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Exception during batch health validation', [
                    'batch_id' => $batchId,
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $results;
    }

    /**
     * Check if processing should stop due to high failure rate.
     * 
     * @param array $results
     * @return bool
     */
    private function shouldStopProcessing(array $results): bool
    {
        if ($results['processed'] < 10) {
            return false; // Need minimum sample size
        }
        
        $failureRate = $results['failed'] / $results['processed'];
        return $failureRate > self::MAX_FAILURE_RATE;
    }

    /**
     * Acquire batch processing lock.
     * 
     * @param string $batchId
     * @return bool
     */
    private function acquireBatchLock(string $batchId): bool
    {
        $lockKey = self::BATCH_LOCK_PREFIX . ':processing';
        
        try {
            return Cache::lock($lockKey, self::BATCH_TIMEOUT)->get(function () use ($batchId) {
                Cache::put(self::BATCH_LOCK_PREFIX . ':current', $batchId, now()->addSeconds(self::BATCH_TIMEOUT));
                return true;
            });
        } catch (\Exception $e) {
            Log::warning('Failed to acquire batch processing lock', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Release batch processing lock.
     * 
     * @param string $batchId
     * @return void
     */
    private function releaseBatchLock(string $batchId): void
    {
        try {
            Cache::forget(self::BATCH_LOCK_PREFIX . ':processing');
            Cache::forget(self::BATCH_LOCK_PREFIX . ':current');
        } catch (\Exception $e) {
            Log::warning('Failed to release batch processing lock', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cache batch results for monitoring.
     * 
     * @param string $batchId
     * @param array $results
     * @return void
     */
    private function cacheBatchResults(string $batchId, array $results): void
    {
        try {
            $cacheKey = self::BATCH_CACHE_PREFIX . ":{$batchId}";
            $cacheData = array_merge($results, [
                'completed_at' => now()->toISOString(),
            ]);
            
            Cache::put($cacheKey, $cacheData, now()->addSeconds(self::BATCH_RESULTS_TTL));
        } catch (\Exception $e) {
            Log::warning('Failed to cache batch results', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record batch processing metrics.
     * 
     * @param string $batchId
     * @param array $results
     * @return void
     */
    private function recordBatchMetrics(string $batchId, array $results): void
    {
        try {
            $this->monitoringService->recordBatchProcessingMetrics([
                'batch_id' => $batchId,
                'total_tokens' => $results['total_tokens'],
                'successful_refreshes' => $results['successful'],
                'failed_refreshes' => $results['failed'],
                'success_rate' => $results['success_rate'],
                'processing_time_ms' => $results['processing_time_ms'] ?? 0,
                'batches_processed' => $results['batches_processed'],
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to record batch metrics', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get recent batch results from cache.
     * 
     * @param int $hours
     * @return array
     */
    private function getRecentBatchResults(int $hours): array
    {
        try {
            // Check if Redis is available
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $cacheKeys = Cache::getRedis()->keys(self::BATCH_CACHE_PREFIX . ':*');
                $recentBatches = [];
                
                foreach ($cacheKeys as $key) {
                    $batchData = Cache::get($key);
                    if ($batchData && isset($batchData['completed_at'])) {
                        $completedAt = now()->parse($batchData['completed_at']);
                        if ($completedAt->isAfter(now()->subHours($hours))) {
                            $recentBatches[] = $batchData;
                        }
                    }
                }
                
                // Sort by completion time
                usort($recentBatches, function ($a, $b) {
                    return strcmp($b['completed_at'], $a['completed_at']);
                });
                
                return $recentBatches;
            } else {
                // Fallback for non-Redis cache stores
                Log::debug('Redis not available for batch results retrieval');
                return [];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get recent batch results', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get performance metrics for batch processing.
     * 
     * @param int $hours
     * @return array
     */
    private function getPerformanceMetrics(int $hours): array
    {
        try {
            return $this->monitoringService->getBatchProcessingMetrics($hours);
        } catch (\Exception $e) {
            Log::warning('Failed to get performance metrics', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get query optimization statistics.
     * 
     * @return array
     */
    private function getQueryOptimizationStats(): array
    {
        try {
            return $this->queryOptimizer->optimizeIndexes();
        } catch (\Exception $e) {
            Log::warning('Failed to get query optimization stats', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}