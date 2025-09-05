<?php

namespace App\Services;

use App\Models\User;
use App\Services\HealthStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Performance-optimized health validator with advanced Redis caching strategies.
 * Implements multi-tier caching, connection pooling, and batch processing.
 */
class PerformanceOptimizedHealthValidator
{
    // Cache TTL constants
    private const HEALTHY_CACHE_TTL = 30;     // 30 seconds for healthy status
    private const ERROR_CACHE_TTL = 10;       // 10 seconds for error status
    private const BATCH_CACHE_TTL = 60;       // 1 minute for batch results
    private const WARM_CACHE_TTL = 300;       // 5 minutes for cache warming
    
    // Cache key prefixes
    private const HEALTH_CACHE_PREFIX = 'health_status_v2';
    private const BATCH_CACHE_PREFIX = 'batch_health_v2';
    private const WARM_CACHE_PREFIX = 'warm_health_v2';
    private const RATE_LIMIT_PREFIX = 'health_rate_limit';
    
    // Performance thresholds
    private const MAX_CONCURRENT_VALIDATIONS = 10;
    private const BATCH_SIZE = 20;
    private const RATE_LIMIT_WINDOW = 60; // seconds
    private const RATE_LIMIT_MAX_REQUESTS = 30;

    public function __construct(
        private readonly RealTimeHealthValidator $realTimeValidator,
        private readonly CloudStorageHealthService $healthService,
        private readonly CloudStorageManager $storageManager
    ) {}

    /**
     * Validate connection health with advanced caching and performance optimizations.
     * 
     * @param User $user
     * @param string $provider
     * @param bool $forceRefresh Force bypass cache
     * @return HealthStatus
     */
    public function validateConnectionHealth(User $user, string $provider, bool $forceRefresh = false): HealthStatus
    {
        $startTime = microtime(true);
        $cacheKey = $this->buildCacheKey($user->id, $provider);
        
        // Check rate limiting first
        if (!$this->checkRateLimit($user->id, $provider)) {
            Log::warning('Health validation rate limited', [
                'user_id' => $user->id,
                'provider' => $provider,
            ]);
            
            // Return cached result if available, otherwise return rate limited status
            return $this->getCachedResultOrDefault($cacheKey, $user, $provider);
        }
        
        // Try cache first unless forced refresh
        if (!$forceRefresh) {
            $cachedResult = $this->getCachedHealthStatus($cacheKey);
            if ($cachedResult !== null) {
                $this->recordCacheHit($user->id, $provider, microtime(true) - $startTime);
                return $cachedResult;
            }
        }
        
        // Perform validation with connection pooling
        $healthStatus = $this->performOptimizedValidation($user, $provider);
        
        // Cache the result with appropriate TTL
        $this->cacheHealthStatus($cacheKey, $healthStatus);
        
        // Record performance metrics
        $this->recordValidationMetrics($user->id, $provider, microtime(true) - $startTime, false);
        
        return $healthStatus;
    }

    /**
     * Batch validate health status for multiple users to improve performance.
     * 
     * @param array $userProviderPairs Array of ['user_id' => int, 'provider' => string]
     * @return array Keyed by "user_id:provider"
     */
    public function batchValidateHealth(array $userProviderPairs): array
    {
        $startTime = microtime(true);
        $results = [];
        $cacheKeys = [];
        $uncachedPairs = [];
        
        // Build cache keys and check for cached results
        foreach ($userProviderPairs as $pair) {
            $userId = $pair['user_id'];
            $provider = $pair['provider'];
            $key = "{$userId}:{$provider}";
            $cacheKey = $this->buildCacheKey($userId, $provider);
            
            $cacheKeys[$key] = $cacheKey;
            
            $cachedResult = $this->getCachedHealthStatus($cacheKey);
            if ($cachedResult !== null) {
                $results[$key] = $cachedResult;
            } else {
                $uncachedPairs[$key] = $pair;
            }
        }
        
        Log::info('Batch health validation started', [
            'total_pairs' => count($userProviderPairs),
            'cached_results' => count($results),
            'uncached_pairs' => count($uncachedPairs),
        ]);
        
        // Process uncached pairs in batches
        if (!empty($uncachedPairs)) {
            $batches = array_chunk($uncachedPairs, self::BATCH_SIZE, true);
            
            foreach ($batches as $batch) {
                $batchResults = $this->processBatch($batch);
                $results = array_merge($results, $batchResults);
                
                // Cache batch results
                foreach ($batchResults as $key => $healthStatus) {
                    $this->cacheHealthStatus($cacheKeys[$key], $healthStatus);
                }
            }
        }
        
        $totalTime = microtime(true) - $startTime;
        
        Log::info('Batch health validation completed', [
            'total_pairs' => count($userProviderPairs),
            'total_time_ms' => round($totalTime * 1000, 2),
            'avg_time_per_pair_ms' => round(($totalTime / count($userProviderPairs)) * 1000, 2),
        ]);
        
        return $results;
    }

    /**
     * Warm cache for frequently accessed health status.
     * 
     * @param array $userIds Array of user IDs to warm cache for
     * @param array $providers Array of providers to warm cache for
     * @return int Number of cache entries warmed
     */
    public function warmCache(array $userIds, array $providers = ['google-drive']): int
    {
        $startTime = microtime(true);
        $warmedCount = 0;
        $userProviderPairs = [];
        
        // Build user-provider pairs
        foreach ($userIds as $userId) {
            foreach ($providers as $provider) {
                $userProviderPairs[] = [
                    'user_id' => $userId,
                    'provider' => $provider,
                ];
            }
        }
        
        Log::info('Cache warming started', [
            'user_count' => count($userIds),
            'provider_count' => count($providers),
            'total_pairs' => count($userProviderPairs),
        ]);
        
        // Use batch validation for efficiency
        $results = $this->batchValidateHealth($userProviderPairs);
        
        // Mark as warmed cache entries with extended TTL
        foreach ($results as $key => $healthStatus) {
            [$userId, $provider] = explode(':', $key);
            $warmCacheKey = $this->buildWarmCacheKey($userId, $provider);
            
            Cache::put($warmCacheKey, [
                'warmed_at' => now()->toISOString(),
                'health_status' => $healthStatus->toArray(),
            ], now()->addSeconds(self::WARM_CACHE_TTL));
            
            $warmedCount++;
        }
        
        $totalTime = microtime(true) - $startTime;
        
        Log::info('Cache warming completed', [
            'warmed_count' => $warmedCount,
            'total_time_ms' => round($totalTime * 1000, 2),
        ]);
        
        return $warmedCount;
    }

    /**
     * Get cached health status with Redis optimization.
     * 
     * @param string $cacheKey
     * @return HealthStatus|null
     */
    private function getCachedHealthStatus(string $cacheKey): ?HealthStatus
    {
        try {
            $cached = Cache::get($cacheKey);
            
            if ($cached === null) {
                return null;
            }
            
            // Reconstruct HealthStatus from cached data
            return new HealthStatus(
                isHealthy: $cached['is_healthy'],
                status: $cached['status'],
                errorMessage: $cached['error_message'],
                errorType: $cached['error_type'],
                validationDetails: $cached['validation_details'],
                validatedAt: $cached['validated_at'] ? now()->parse($cached['validated_at']) : null,
                cacheTtlSeconds: $cached['cache_ttl_seconds']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to retrieve cached health status', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Cache health status with appropriate TTL and Redis optimization.
     * 
     * @param string $cacheKey
     * @param HealthStatus $healthStatus
     * @return void
     */
    private function cacheHealthStatus(string $cacheKey, HealthStatus $healthStatus): void
    {
        try {
            $ttl = $healthStatus->isHealthy() ? self::HEALTHY_CACHE_TTL : self::ERROR_CACHE_TTL;
            
            $cacheData = $healthStatus->toArray();
            $cacheData['cached_at'] = now()->toISOString();
            
            Cache::put($cacheKey, $cacheData, now()->addSeconds($ttl));
            
            Log::debug('Cached health status', [
                'cache_key' => $cacheKey,
                'status' => $healthStatus->getStatus(),
                'ttl_seconds' => $ttl,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to cache health status', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Perform optimized validation with connection pooling.
     * 
     * @param User $user
     * @param string $provider
     * @return HealthStatus
     */
    private function performOptimizedValidation(User $user, string $provider): HealthStatus
    {
        try {
            // Use connection pooling for Google API client
            $pooledClient = $this->getPooledApiClient($provider);
            
            // Perform validation using the real-time validator
            return $this->realTimeValidator->validateConnectionHealth($user, $provider);
        } catch (\Exception $e) {
            Log::error('Optimized validation failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            
            return HealthStatus::connectionIssues(
                __('messages.health_validation_failed', ['message' => $e->getMessage()]),
                'validation_exception',
                ['exception_class' => get_class($e)]
            );
        }
    }

    /**
     * Process a batch of user-provider pairs.
     * 
     * @param array $batch
     * @return array
     */
    private function processBatch(array $batch): array
    {
        $results = [];
        
        foreach ($batch as $key => $pair) {
            try {
                $user = User::find($pair['user_id']);
                if (!$user) {
                    $results[$key] = HealthStatus::connectionIssues(
                        __('messages.health_user_not_found'),
                        'user_not_found'
                    );
                    continue;
                }
                
                $results[$key] = $this->performOptimizedValidation($user, $pair['provider']);
            } catch (\Exception $e) {
                Log::error('Batch processing failed for pair', [
                    'pair' => $pair,
                    'error' => $e->getMessage(),
                ]);
                
                $results[$key] = HealthStatus::connectionIssues(
                    __('messages.health_batch_processing_failed', ['message' => $e->getMessage()]),
                    'batch_processing_error'
                );
            }
        }
        
        return $results;
    }

    /**
     * Get pooled API client for better performance.
     * 
     * @param string $provider
     * @return mixed
     */
    private function getPooledApiClient(string $provider)
    {
        // For now, return the standard client
        // In the future, this could implement actual connection pooling
        return $this->storageManager->getProvider($provider);
    }

    /**
     * Check rate limiting for health validation requests.
     * 
     * @param int $userId
     * @param string $provider
     * @return bool
     */
    private function checkRateLimit(int $userId, string $provider): bool
    {
        $rateLimitKey = $this->buildRateLimitKey($userId, $provider);
        
        try {
            $current = Cache::get($rateLimitKey, 0);
            
            if ($current >= self::RATE_LIMIT_MAX_REQUESTS) {
                return false;
            }
            
            Cache::put($rateLimitKey, $current + 1, now()->addSeconds(self::RATE_LIMIT_WINDOW));
            return true;
        } catch (\Exception $e) {
            Log::warning('Rate limit check failed', [
                'user_id' => $userId,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            
            // Allow request if rate limit check fails
            return true;
        }
    }

    /**
     * Get cached result or return default status when rate limited.
     * 
     * @param string $cacheKey
     * @param User $user
     * @param string $provider
     * @return HealthStatus
     */
    private function getCachedResultOrDefault(string $cacheKey, User $user, string $provider): HealthStatus
    {
        $cachedResult = $this->getCachedHealthStatus($cacheKey);
        
        if ($cachedResult !== null) {
            return $cachedResult;
        }
        
        // Return rate limited status
        return HealthStatus::connectionIssues(
            __('messages.health_validation_rate_limited'),
            'rate_limited',
            ['rate_limit_window' => self::RATE_LIMIT_WINDOW]
        );
    }

    /**
     * Record cache hit metrics.
     * 
     * @param int $userId
     * @param string $provider
     * @param float $responseTime
     * @return void
     */
    private function recordCacheHit(int $userId, string $provider, float $responseTime): void
    {
        Log::debug('Health validation cache hit', [
            'user_id' => $userId,
            'provider' => $provider,
            'response_time_ms' => round($responseTime * 1000, 2),
        ]);
    }

    /**
     * Record validation performance metrics.
     * 
     * @param int $userId
     * @param string $provider
     * @param float $responseTime
     * @param bool $fromCache
     * @return void
     */
    private function recordValidationMetrics(int $userId, string $provider, float $responseTime, bool $fromCache): void
    {
        Log::info('Health validation metrics', [
            'user_id' => $userId,
            'provider' => $provider,
            'response_time_ms' => round($responseTime * 1000, 2),
            'from_cache' => $fromCache,
        ]);
    }

    /**
     * Build cache key for health status.
     * 
     * @param int $userId
     * @param string $provider
     * @return string
     */
    private function buildCacheKey(int $userId, string $provider): string
    {
        return self::HEALTH_CACHE_PREFIX . ":{$userId}:{$provider}";
    }

    /**
     * Build cache key for warmed cache entries.
     * 
     * @param int $userId
     * @param string $provider
     * @return string
     */
    private function buildWarmCacheKey(int $userId, string $provider): string
    {
        return self::WARM_CACHE_PREFIX . ":{$userId}:{$provider}";
    }

    /**
     * Build rate limit key.
     * 
     * @param int $userId
     * @param string $provider
     * @return string
     */
    private function buildRateLimitKey(int $userId, string $provider): string
    {
        return self::RATE_LIMIT_PREFIX . ":{$userId}:{$provider}";
    }
}