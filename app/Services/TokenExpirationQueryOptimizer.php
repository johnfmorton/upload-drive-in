<?php

namespace App\Services;

use App\Models\GoogleDriveToken;
use App\Models\CloudStorageHealthStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Optimized database queries for token expiration lookups and health status operations.
 * Implements efficient indexing strategies and query optimization techniques.
 */
class TokenExpirationQueryOptimizer
{
    // Cache TTL for query results
    private const EXPIRING_TOKENS_CACHE_TTL = 300; // 5 minutes
    private const HEALTH_STATUS_CACHE_TTL = 180;   // 3 minutes
    private const BATCH_QUERY_CACHE_TTL = 240;     // 4 minutes
    
    // Cache key prefixes
    private const EXPIRING_TOKENS_PREFIX = 'expiring_tokens_v2';
    private const HEALTH_STATUS_PREFIX = 'health_status_query_v2';
    private const BATCH_QUERY_PREFIX = 'batch_query_v2';
    
    // Query optimization constants
    private const MAX_BATCH_SIZE = 100;
    private const INDEX_HINT_THRESHOLD = 1000;

    /**
     * Get tokens expiring within specified minutes with optimized query.
     * 
     * @param int $minutes Minutes from now to check for expiration
     * @param string $provider Provider to filter by (default: all)
     * @param bool $useCache Whether to use cached results
     * @return Collection
     */
    public function getExpiringTokens(int $minutes = 30, string $provider = 'google-drive', bool $useCache = true): Collection
    {
        $cacheKey = $this->buildExpiringTokensCacheKey($minutes, $provider);
        
        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                Log::debug('Using cached expiring tokens result', [
                    'minutes' => $minutes,
                    'provider' => $provider,
                    'count' => $cached->count(),
                ]);
                return $cached;
            }
        }
        
        $startTime = microtime(true);
        
        // Optimized query with proper indexing
        $query = GoogleDriveToken::query()
            ->select([
                'id',
                'user_id', 
                'expires_at',
                'refresh_token',
                'refresh_failure_count',
                'requires_user_intervention',
                'last_refresh_attempt_at'
            ])
            ->with(['user:id,email,role']) // Only load necessary user fields
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addMinutes($minutes))
            ->where('refresh_failure_count', '<', 5) // Only tokens that can be refreshed
            ->where('requires_user_intervention', false)
            ->whereNotNull('refresh_token')
            ->orderBy('expires_at', 'asc'); // Process most urgent first
        
        // Add index hint for large datasets
        if ($this->shouldUseIndexHint()) {
            $query->from(DB::raw('google_drive_tokens USE INDEX (idx_expires_at_refresh_failures)'));
        }
        
        $tokens = $query->get();
        
        $queryTime = microtime(true) - $startTime;
        
        Log::info('Expiring tokens query completed', [
            'minutes' => $minutes,
            'provider' => $provider,
            'count' => $tokens->count(),
            'query_time_ms' => round($queryTime * 1000, 2),
        ]);
        
        // Cache the results
        if ($useCache) {
            Cache::put($cacheKey, $tokens, now()->addSeconds(self::EXPIRING_TOKENS_CACHE_TTL));
        }
        
        return $tokens;
    }

    /**
     * Get health status records that need validation with optimized query.
     * 
     * @param int $minutesSinceLastValidation Minutes since last validation
     * @param array $providers Providers to include
     * @param bool $useCache Whether to use cached results
     * @return Collection
     */
    public function getHealthStatusNeedingValidation(
        int $minutesSinceLastValidation = 30,
        array $providers = ['google-drive'],
        bool $useCache = true
    ): Collection {
        $cacheKey = $this->buildHealthStatusCacheKey($minutesSinceLastValidation, $providers);
        
        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                Log::debug('Using cached health status validation result', [
                    'minutes_since_validation' => $minutesSinceLastValidation,
                    'providers' => $providers,
                    'count' => $cached->count(),
                ]);
                return $cached;
            }
        }
        
        $startTime = microtime(true);
        $cutoffTime = now()->subMinutes($minutesSinceLastValidation);
        
        // Optimized query for health status records needing validation
        $query = CloudStorageHealthStatus::query()
            ->select([
                'id',
                'user_id',
                'provider',
                'consolidated_status',
                'last_live_validation_at',
                'last_successful_operation_at',
                'consecutive_failures',
                'token_refresh_failures'
            ])
            ->with(['user:id,email,role']) // Only load necessary user fields
            ->whereIn('provider', $providers)
            ->where(function (Builder $query) use ($cutoffTime) {
                $query->whereNull('last_live_validation_at')
                      ->orWhere('last_live_validation_at', '<', $cutoffTime);
            })
            ->where('consecutive_failures', '<', 10) // Skip permanently failed connections
            ->orderBy('last_live_validation_at', 'asc', 'nulls first'); // Prioritize never validated
        
        // Add index hint for large datasets
        if ($this->shouldUseIndexHint()) {
            $query->from(DB::raw('cloud_storage_health_statuses USE INDEX (idx_provider_validation_failures)'));
        }
        
        $healthStatuses = $query->get();
        
        $queryTime = microtime(true) - $startTime;
        
        Log::info('Health status validation query completed', [
            'minutes_since_validation' => $minutesSinceLastValidation,
            'providers' => $providers,
            'count' => $healthStatuses->count(),
            'query_time_ms' => round($queryTime * 1000, 2),
        ]);
        
        // Cache the results
        if ($useCache) {
            Cache::put($cacheKey, $healthStatuses, now()->addSeconds(self::HEALTH_STATUS_CACHE_TTL));
        }
        
        return $healthStatuses;
    }

    /**
     * Batch update token expiration times with optimized query.
     * 
     * @param array $tokenUpdates Array of ['token_id' => int, 'expires_at' => Carbon]
     * @return int Number of updated records
     */
    public function batchUpdateTokenExpirations(array $tokenUpdates): int
    {
        if (empty($tokenUpdates)) {
            return 0;
        }
        
        $startTime = microtime(true);
        $updatedCount = 0;
        
        // Process in batches to avoid memory issues
        $batches = array_chunk($tokenUpdates, self::MAX_BATCH_SIZE, true);
        
        DB::transaction(function () use ($batches, &$updatedCount) {
            foreach ($batches as $batch) {
                $cases = [];
                $tokenIds = [];
                
                foreach ($batch as $tokenId => $expiresAt) {
                    $tokenIds[] = $tokenId;
                    $cases[] = "WHEN {$tokenId} THEN '{$expiresAt->toDateTimeString()}'";
                }
                
                $tokenIdsStr = implode(',', $tokenIds);
                $casesStr = implode(' ', $cases);
                
                // Use raw SQL for efficient batch update
                $updated = DB::update("
                    UPDATE google_drive_tokens 
                    SET expires_at = CASE id {$casesStr} END,
                        updated_at = NOW()
                    WHERE id IN ({$tokenIdsStr})
                ");
                
                $updatedCount += $updated;
            }
        });
        
        $queryTime = microtime(true) - $startTime;
        
        Log::info('Batch token expiration update completed', [
            'total_updates' => count($tokenUpdates),
            'batches_processed' => count($batches),
            'updated_count' => $updatedCount,
            'query_time_ms' => round($queryTime * 1000, 2),
        ]);
        
        return $updatedCount;
    }

    /**
     * Get users with multiple provider connections for batch processing.
     * 
     * @param array $providers Providers to check
     * @param bool $useCache Whether to use cached results
     * @return Collection
     */
    public function getUsersWithMultipleProviders(array $providers = ['google-drive'], bool $useCache = true): Collection
    {
        $cacheKey = $this->buildBatchQueryCacheKey('multi_provider_users', $providers);
        
        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        $startTime = microtime(true);
        
        // Optimized query to find users with multiple provider connections
        $users = User::query()
            ->select(['id', 'email', 'role'])
            ->whereHas('cloudStorageHealthStatuses', function (Builder $query) use ($providers) {
                $query->whereIn('provider', $providers)
                      ->where('consolidated_status', '!=', 'not_connected');
            })
            ->with(['cloudStorageHealthStatuses' => function ($query) use ($providers) {
                $query->select([
                    'id', 'user_id', 'provider', 'consolidated_status', 
                    'last_successful_operation_at', 'consecutive_failures'
                ])
                ->whereIn('provider', $providers);
            }])
            ->get();
        
        $queryTime = microtime(true) - $startTime;
        
        Log::info('Multi-provider users query completed', [
            'providers' => $providers,
            'count' => $users->count(),
            'query_time_ms' => round($queryTime * 1000, 2),
        ]);
        
        // Cache the results
        if ($useCache) {
            Cache::put($cacheKey, $users, now()->addSeconds(self::BATCH_QUERY_CACHE_TTL));
        }
        
        return $users;
    }

    /**
     * Optimize database indexes for token and health status queries.
     * 
     * @return array Results of index optimization
     */
    public function optimizeIndexes(): array
    {
        $results = [];
        
        try {
            // Check if recommended indexes exist
            $indexes = $this->checkRecommendedIndexes();
            $results['existing_indexes'] = $indexes;
            
            // Analyze query performance
            $performance = $this->analyzeQueryPerformance();
            $results['performance_analysis'] = $performance;
            
            // Provide recommendations
            $recommendations = $this->generateIndexRecommendations($indexes, $performance);
            $results['recommendations'] = $recommendations;
            
            Log::info('Database index optimization analysis completed', $results);
            
        } catch (\Exception $e) {
            Log::error('Database index optimization failed', [
                'error' => $e->getMessage(),
            ]);
            
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Clear all query optimization caches.
     * 
     * @return int Number of cache keys cleared
     */
    public function clearQueryCaches(): int
    {
        $patterns = [
            self::EXPIRING_TOKENS_PREFIX . ':*',
            self::HEALTH_STATUS_PREFIX . ':*',
            self::BATCH_QUERY_PREFIX . ':*',
        ];
        
        $clearedCount = 0;
        
        foreach ($patterns as $pattern) {
            try {
                // Check if Redis is available
                if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                    $keys = Cache::getRedis()->keys($pattern);
                    if (!empty($keys)) {
                        Cache::getRedis()->del($keys);
                        $clearedCount += count($keys);
                    }
                } else {
                    // For non-Redis stores, we can't use pattern matching
                    // Just log that Redis is not available
                    Log::debug('Redis not available for pattern cache clearing', [
                        'pattern' => $pattern,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to clear cache pattern', [
                    'pattern' => $pattern,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        Log::info('Query optimization caches cleared', [
            'cleared_count' => $clearedCount,
        ]);
        
        return $clearedCount;
    }

    /**
     * Check if index hints should be used based on table size.
     * 
     * @return bool
     */
    private function shouldUseIndexHint(): bool
    {
        try {
            $tokenCount = DB::table('google_drive_tokens')->count();
            $healthCount = DB::table('cloud_storage_health_statuses')->count();
            
            return $tokenCount > self::INDEX_HINT_THRESHOLD || $healthCount > self::INDEX_HINT_THRESHOLD;
        } catch (\Exception $e) {
            Log::warning('Failed to check table sizes for index hints', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check which recommended indexes exist.
     * 
     * @return array
     */
    private function checkRecommendedIndexes(): array
    {
        $indexes = [];
        
        try {
            // Check Google Drive tokens indexes
            $tokenIndexes = DB::select("SHOW INDEX FROM google_drive_tokens");
            $indexes['google_drive_tokens'] = collect($tokenIndexes)->pluck('Key_name')->unique()->toArray();
            
            // Check health status indexes
            $healthIndexes = DB::select("SHOW INDEX FROM cloud_storage_health_statuses");
            $indexes['cloud_storage_health_statuses'] = collect($healthIndexes)->pluck('Key_name')->unique()->toArray();
            
        } catch (\Exception $e) {
            Log::warning('Failed to check existing indexes', [
                'error' => $e->getMessage(),
            ]);
        }
        
        return $indexes;
    }

    /**
     * Analyze query performance for optimization.
     * 
     * @return array
     */
    private function analyzeQueryPerformance(): array
    {
        $performance = [];
        
        try {
            // Analyze expiring tokens query
            $expiringTokensExplain = DB::select("
                EXPLAIN SELECT * FROM google_drive_tokens 
                WHERE expires_at > NOW() 
                AND expires_at <= DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                AND refresh_failure_count < 5
            ");
            $performance['expiring_tokens_query'] = $expiringTokensExplain;
            
            // Analyze health status query
            $healthStatusExplain = DB::select("
                EXPLAIN SELECT * FROM cloud_storage_health_statuses 
                WHERE provider = 'google-drive'
                AND (last_live_validation_at IS NULL OR last_live_validation_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE))
            ");
            $performance['health_status_query'] = $healthStatusExplain;
            
        } catch (\Exception $e) {
            Log::warning('Failed to analyze query performance', [
                'error' => $e->getMessage(),
            ]);
        }
        
        return $performance;
    }

    /**
     * Generate index recommendations based on analysis.
     * 
     * @param array $indexes
     * @param array $performance
     * @return array
     */
    private function generateIndexRecommendations(array $indexes, array $performance): array
    {
        $recommendations = [];
        
        // Recommended indexes for google_drive_tokens
        $tokenRecommendations = [
            'idx_expires_at_refresh_failures' => 'CREATE INDEX idx_expires_at_refresh_failures ON google_drive_tokens (expires_at, refresh_failure_count, requires_user_intervention)',
            'idx_user_id_expires_at' => 'CREATE INDEX idx_user_id_expires_at ON google_drive_tokens (user_id, expires_at)',
            'idx_proactive_refresh_scheduled' => 'CREATE INDEX idx_proactive_refresh_scheduled ON google_drive_tokens (proactive_refresh_scheduled_at)',
        ];
        
        // Recommended indexes for cloud_storage_health_statuses
        $healthRecommendations = [
            'idx_provider_validation_failures' => 'CREATE INDEX idx_provider_validation_failures ON cloud_storage_health_statuses (provider, last_live_validation_at, consecutive_failures)',
            'idx_user_provider_status' => 'CREATE INDEX idx_user_provider_status ON cloud_storage_health_statuses (user_id, provider, consolidated_status)',
            'idx_last_successful_operation' => 'CREATE INDEX idx_last_successful_operation ON cloud_storage_health_statuses (last_successful_operation_at)',
        ];
        
        // Check which indexes are missing
        $tokenIndexes = $indexes['google_drive_tokens'] ?? [];
        $healthIndexes = $indexes['cloud_storage_health_statuses'] ?? [];
        
        foreach ($tokenRecommendations as $indexName => $sql) {
            if (!in_array($indexName, $tokenIndexes)) {
                $recommendations['missing_token_indexes'][$indexName] = $sql;
            }
        }
        
        foreach ($healthRecommendations as $indexName => $sql) {
            if (!in_array($indexName, $healthIndexes)) {
                $recommendations['missing_health_indexes'][$indexName] = $sql;
            }
        }
        
        return $recommendations;
    }

    /**
     * Build cache key for expiring tokens query.
     * 
     * @param int $minutes
     * @param string $provider
     * @return string
     */
    private function buildExpiringTokensCacheKey(int $minutes, string $provider): string
    {
        return self::EXPIRING_TOKENS_PREFIX . ":{$minutes}:{$provider}";
    }

    /**
     * Build cache key for health status query.
     * 
     * @param int $minutes
     * @param array $providers
     * @return string
     */
    private function buildHealthStatusCacheKey(int $minutes, array $providers): string
    {
        $providersStr = implode(',', $providers);
        return self::HEALTH_STATUS_PREFIX . ":{$minutes}:{$providersStr}";
    }

    /**
     * Build cache key for batch queries.
     * 
     * @param string $queryType
     * @param array $params
     * @return string
     */
    private function buildBatchQueryCacheKey(string $queryType, array $params): string
    {
        $paramsStr = md5(serialize($params));
        return self::BATCH_QUERY_PREFIX . ":{$queryType}:{$paramsStr}";
    }
}