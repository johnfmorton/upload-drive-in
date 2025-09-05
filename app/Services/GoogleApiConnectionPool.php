<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Connection pooling service for Google API client instances.
 * Manages reusable client connections to improve performance and reduce overhead.
 */
class GoogleApiConnectionPool
{
    // Pool configuration
    private const MAX_POOL_SIZE = 10;
    private const CLIENT_TTL = 3600; // 1 hour
    private const POOL_CACHE_PREFIX = 'google_api_pool';
    private const POOL_STATS_PREFIX = 'google_api_stats';
    
    // Client configuration cache
    private const CONFIG_CACHE_TTL = 7200; // 2 hours
    
    private array $activeClients = [];
    private array $clientStats = [];

    /**
     * Get a pooled Google API client instance.
     * 
     * @param string $clientId Google OAuth client ID
     * @param string $clientSecret Google OAuth client secret
     * @param array $scopes Required OAuth scopes
     * @return Client
     */
    public function getClient(string $clientId, string $clientSecret, array $scopes = []): Client
    {
        $poolKey = $this->generatePoolKey($clientId, $scopes);
        
        // Try to get from active pool first
        if (isset($this->activeClients[$poolKey])) {
            $this->recordClientUsage($poolKey, 'pool_hit');
            Log::debug('Using pooled Google API client', [
                'pool_key' => $poolKey,
                'source' => 'active_pool',
            ]);
            return $this->activeClients[$poolKey];
        }
        
        // Try to get from cache
        $cachedClient = $this->getCachedClient($poolKey);
        if ($cachedClient !== null) {
            $this->activeClients[$poolKey] = $cachedClient;
            $this->recordClientUsage($poolKey, 'cache_hit');
            Log::debug('Using cached Google API client', [
                'pool_key' => $poolKey,
                'source' => 'cache',
            ]);
            return $cachedClient;
        }
        
        // Create new client
        $client = $this->createNewClient($clientId, $clientSecret, $scopes);
        
        // Add to pool if there's space
        if (count($this->activeClients) < self::MAX_POOL_SIZE) {
            $this->activeClients[$poolKey] = $client;
            $this->cacheClient($poolKey, $client);
        }
        
        $this->recordClientUsage($poolKey, 'new_client');
        Log::info('Created new Google API client', [
            'pool_key' => $poolKey,
            'pool_size' => count($this->activeClients),
        ]);
        
        return $client;
    }

    /**
     * Get a pooled Google Drive service instance.
     * 
     * @param string $clientId Google OAuth client ID
     * @param string $clientSecret Google OAuth client secret
     * @param array $accessToken User's access token
     * @return Drive
     */
    public function getDriveService(string $clientId, string $clientSecret, array $accessToken): Drive
    {
        $scopes = [Drive::DRIVE_FILE, Drive::DRIVE];
        $client = $this->getClient($clientId, $clientSecret, $scopes);
        
        // Set the access token for this specific request
        $client->setAccessToken($accessToken);
        
        return new Drive($client);
    }

    /**
     * Release a client back to the pool (for explicit resource management).
     * 
     * @param string $clientId
     * @param array $scopes
     * @return void
     */
    public function releaseClient(string $clientId, array $scopes = []): void
    {
        $poolKey = $this->generatePoolKey($clientId, $scopes);
        
        if (isset($this->activeClients[$poolKey])) {
            $this->recordClientUsage($poolKey, 'released');
            Log::debug('Released Google API client to pool', [
                'pool_key' => $poolKey,
            ]);
        }
    }

    /**
     * Clear the entire connection pool.
     * 
     * @return int Number of clients cleared
     */
    public function clearPool(): int
    {
        $clearedCount = count($this->activeClients);
        
        // Clear active clients
        $this->activeClients = [];
        
        // Clear cached clients
        try {
            // Check if Redis is available
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $cacheKeys = Cache::getRedis()->keys(self::POOL_CACHE_PREFIX . ':*');
                if (!empty($cacheKeys)) {
                    Cache::getRedis()->del($cacheKeys);
                }
            } else {
                // For non-Redis stores, we can't efficiently clear by pattern
                Log::debug('Redis not available for connection pool cache clearing');
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear cached Google API clients', [
                'error' => $e->getMessage(),
            ]);
        }
        
        Log::info('Cleared Google API connection pool', [
            'cleared_count' => $clearedCount,
        ]);
        
        return $clearedCount;
    }

    /**
     * Get connection pool statistics.
     * 
     * @return array
     */
    public function getPoolStats(): array
    {
        $stats = [
            'active_clients' => count($this->activeClients),
            'max_pool_size' => self::MAX_POOL_SIZE,
            'pool_utilization' => round((count($this->activeClients) / self::MAX_POOL_SIZE) * 100, 2),
            'client_usage' => $this->getClientUsageStats(),
        ];
        
        // Get cached stats
        try {
            $cachedStats = Cache::get(self::POOL_STATS_PREFIX . ':summary', []);
            $stats['cached_stats'] = $cachedStats;
        } catch (\Exception $e) {
            Log::warning('Failed to retrieve cached pool stats', [
                'error' => $e->getMessage(),
            ]);
        }
        
        return $stats;
    }

    /**
     * Optimize the connection pool by removing unused clients.
     * 
     * @return int Number of clients removed
     */
    public function optimizePool(): int
    {
        $removedCount = 0;
        $currentTime = time();
        
        foreach ($this->activeClients as $poolKey => $client) {
            $lastUsed = $this->clientStats[$poolKey]['last_used'] ?? $currentTime;
            
            // Remove clients not used in the last hour
            if (($currentTime - $lastUsed) > 3600) {
                unset($this->activeClients[$poolKey]);
                unset($this->clientStats[$poolKey]);
                $removedCount++;
                
                Log::debug('Removed unused Google API client from pool', [
                    'pool_key' => $poolKey,
                    'last_used' => date('Y-m-d H:i:s', $lastUsed),
                ]);
            }
        }
        
        if ($removedCount > 0) {
            Log::info('Optimized Google API connection pool', [
                'removed_count' => $removedCount,
                'remaining_clients' => count($this->activeClients),
            ]);
        }
        
        return $removedCount;
    }

    /**
     * Warm up the connection pool with commonly used client configurations.
     * 
     * @param array $configurations Array of client configurations
     * @return int Number of clients warmed up
     */
    public function warmUpPool(array $configurations): int
    {
        $warmedCount = 0;
        
        foreach ($configurations as $config) {
            try {
                $client = $this->getClient(
                    $config['client_id'],
                    $config['client_secret'],
                    $config['scopes'] ?? []
                );
                
                if ($client) {
                    $warmedCount++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to warm up Google API client', [
                    'config' => $config,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        Log::info('Warmed up Google API connection pool', [
            'warmed_count' => $warmedCount,
            'total_configurations' => count($configurations),
        ]);
        
        return $warmedCount;
    }

    /**
     * Create a new Google API client with optimized configuration.
     * 
     * @param string $clientId
     * @param string $clientSecret
     * @param array $scopes
     * @return Client
     */
    private function createNewClient(string $clientId, string $clientSecret, array $scopes): Client
    {
        $client = new Client();
        
        // Basic configuration
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        
        // Set scopes
        if (!empty($scopes)) {
            $client->setScopes($scopes);
        } else {
            $client->addScope(Drive::DRIVE_FILE);
            $client->addScope(Drive::DRIVE);
        }
        
        // Performance optimizations
        $client->setConfig('retry', [
            'retries' => 3,
            'delay' => function ($attempt) {
                return 1000 * pow(2, $attempt); // Exponential backoff
            }
        ]);
        
        // Set timeout configurations
        $client->setConfig('timeout', 30);
        $client->setConfig('connect_timeout', 10);
        
        return $client;
    }

    /**
     * Generate a unique pool key for client configuration.
     * 
     * @param string $clientId
     * @param array $scopes
     * @return string
     */
    private function generatePoolKey(string $clientId, array $scopes): string
    {
        sort($scopes); // Ensure consistent ordering
        $scopesHash = md5(implode(',', $scopes));
        return "client_{$clientId}_{$scopesHash}";
    }

    /**
     * Cache a client instance.
     * 
     * @param string $poolKey
     * @param Client $client
     * @return void
     */
    private function cacheClient(string $poolKey, Client $client): void
    {
        try {
            $cacheKey = self::POOL_CACHE_PREFIX . ":{$poolKey}";
            
            // Store client configuration instead of the full object
            $clientConfig = [
                'client_id' => $client->getClientId(),
                'client_secret' => $client->getClientSecret(),
                'scopes' => $client->getScopes(),
                'access_type' => 'offline', // Default access type
                'cached_at' => time(),
            ];
            
            Cache::put($cacheKey, $clientConfig, now()->addSeconds(self::CLIENT_TTL));
        } catch (\Exception $e) {
            Log::warning('Failed to cache Google API client', [
                'pool_key' => $poolKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get a cached client instance.
     * 
     * @param string $poolKey
     * @return Client|null
     */
    private function getCachedClient(string $poolKey): ?Client
    {
        try {
            $cacheKey = self::POOL_CACHE_PREFIX . ":{$poolKey}";
            $clientConfig = Cache::get($cacheKey);
            
            if ($clientConfig === null) {
                return null;
            }
            
            // Recreate client from cached configuration
            return $this->createNewClient(
                $clientConfig['client_id'],
                $clientConfig['client_secret'],
                $clientConfig['scopes'] ?? []
            );
        } catch (\Exception $e) {
            Log::warning('Failed to retrieve cached Google API client', [
                'pool_key' => $poolKey,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Record client usage statistics.
     * 
     * @param string $poolKey
     * @param string $action
     * @return void
     */
    private function recordClientUsage(string $poolKey, string $action): void
    {
        if (!isset($this->clientStats[$poolKey])) {
            $this->clientStats[$poolKey] = [
                'created_at' => time(),
                'usage_count' => 0,
                'last_used' => time(),
                'actions' => [],
            ];
        }
        
        $this->clientStats[$poolKey]['usage_count']++;
        $this->clientStats[$poolKey]['last_used'] = time();
        $this->clientStats[$poolKey]['actions'][] = [
            'action' => $action,
            'timestamp' => time(),
        ];
        
        // Keep only last 10 actions to prevent memory bloat
        if (count($this->clientStats[$poolKey]['actions']) > 10) {
            $this->clientStats[$poolKey]['actions'] = array_slice(
                $this->clientStats[$poolKey]['actions'], -10
            );
        }
        
        // Cache stats summary periodically
        if ($this->clientStats[$poolKey]['usage_count'] % 10 === 0) {
            $this->cacheStatsummary();
        }
    }

    /**
     * Get client usage statistics.
     * 
     * @return array
     */
    private function getClientUsageStats(): array
    {
        $stats = [];
        
        foreach ($this->clientStats as $poolKey => $clientStat) {
            $stats[$poolKey] = [
                'usage_count' => $clientStat['usage_count'],
                'last_used' => date('Y-m-d H:i:s', $clientStat['last_used']),
                'age_seconds' => time() - $clientStat['created_at'],
                'recent_actions' => array_slice($clientStat['actions'], -5), // Last 5 actions
            ];
        }
        
        return $stats;
    }

    /**
     * Cache statistics summary.
     * 
     * @return void
     */
    private function cacheStatsummary(): void
    {
        try {
            $summary = [
                'active_clients' => count($this->activeClients),
                'total_usage' => array_sum(array_column($this->clientStats, 'usage_count')),
                'last_updated' => time(),
            ];
            
            Cache::put(self::POOL_STATS_PREFIX . ':summary', $summary, now()->addMinutes(30));
        } catch (\Exception $e) {
            Log::warning('Failed to cache pool stats summary', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}