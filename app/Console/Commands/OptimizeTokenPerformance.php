<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PerformanceOptimizedHealthValidator;
use App\Services\TokenExpirationQueryOptimizer;
use App\Services\GoogleApiConnectionPool;
use App\Services\BatchTokenRefreshProcessor;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Console command for optimizing token performance and monitoring system health.
 * Provides tools for cache warming, performance analysis, and optimization.
 */
class OptimizeTokenPerformance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'token:optimize-performance 
                            {action=analyze : Action to perform (analyze|warm-cache|clear-cache|optimize|batch-refresh|stats)}
                            {--users= : Comma-separated list of user IDs for cache warming}
                            {--providers=google-drive : Comma-separated list of providers}
                            {--batch-size=20 : Batch size for processing}
                            {--dry-run : Perform dry run without making changes}
                            {--force : Force optimization even if not recommended}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize token performance through caching, query optimization, and batch processing';

    private PerformanceOptimizedHealthValidator $healthValidator;
    private TokenExpirationQueryOptimizer $queryOptimizer;
    private GoogleApiConnectionPool $connectionPool;
    private BatchTokenRefreshProcessor $batchProcessor;

    /**
     * Create a new command instance.
     */
    public function __construct(
        PerformanceOptimizedHealthValidator $healthValidator,
        TokenExpirationQueryOptimizer $queryOptimizer,
        GoogleApiConnectionPool $connectionPool,
        BatchTokenRefreshProcessor $batchProcessor
    ) {
        parent::__construct();
        
        $this->healthValidator = $healthValidator;
        $this->queryOptimizer = $queryOptimizer;
        $this->connectionPool = $connectionPool;
        $this->batchProcessor = $batchProcessor;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        
        $this->info("Starting token performance optimization: {$action}");
        
        try {
            switch ($action) {
                case 'analyze':
                    return $this->analyzePerformance();
                    
                case 'warm-cache':
                    return $this->warmCache();
                    
                case 'clear-cache':
                    return $this->clearCache();
                    
                case 'optimize':
                    return $this->optimizeSystem();
                    
                case 'batch-refresh':
                    return $this->batchRefresh();
                    
                case 'stats':
                    return $this->showStats();
                    
                default:
                    $this->error("Unknown action: {$action}");
                    $this->info('Available actions: analyze, warm-cache, clear-cache, optimize, batch-refresh, stats');
                    return 1;
            }
        } catch (\Exception $e) {
            $this->error("Command failed: {$e->getMessage()}");
            Log::error('Token performance optimization command failed', [
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * Analyze current performance metrics and provide recommendations.
     */
    private function analyzePerformance(): int
    {
        $this->info('Analyzing token performance metrics...');
        
        // Get database index analysis
        $this->line('Checking database indexes...');
        $indexAnalysis = $this->queryOptimizer->optimizeIndexes();
        
        if (isset($indexAnalysis['error'])) {
            $this->error("Index analysis failed: {$indexAnalysis['error']}");
        } else {
            $this->displayIndexAnalysis($indexAnalysis);
        }
        
        // Get connection pool stats
        $this->line('Checking connection pool status...');
        $poolStats = $this->connectionPool->getPoolStats();
        $this->displayPoolStats($poolStats);
        
        // Get batch processing stats
        $this->line('Checking batch processing performance...');
        $batchStats = $this->batchProcessor->getBatchProcessingStats(24);
        $this->displayBatchStats($batchStats);
        
        // Provide recommendations
        $this->line('Performance Recommendations:');
        $this->provideRecommendations($indexAnalysis, $poolStats, $batchStats);
        
        return 0;
    }

    /**
     * Warm cache for frequently accessed data.
     */
    private function warmCache(): int
    {
        $providers = explode(',', $this->option('providers'));
        
        // Get user IDs to warm
        if ($this->option('users')) {
            $userIds = array_map('intval', explode(',', $this->option('users')));
        } else {
            // Get active users (users with recent activity)
            $userIds = User::whereHas('cloudStorageHealthStatuses', function ($query) {
                $query->where('last_successful_operation_at', '>', now()->subDays(7));
            })->limit(100)->pluck('id')->toArray();
        }
        
        if (empty($userIds)) {
            $this->warn('No users found for cache warming');
            return 0;
        }
        
        $this->info("Warming cache for " . count($userIds) . " users and " . count($providers) . " providers...");
        
        $progressBar = $this->output->createProgressBar(count($userIds));
        $progressBar->start();
        
        $warmedCount = 0;
        
        try {
            // Warm cache in batches
            $userBatches = array_chunk($userIds, 20);
            
            foreach ($userBatches as $batch) {
                $batchWarmed = $this->healthValidator->warmCache($batch, $providers);
                $warmedCount += $batchWarmed;
                $progressBar->advance(count($batch));
            }
            
            $progressBar->finish();
            $this->newLine();
            
            $this->info("Cache warming completed. Warmed {$warmedCount} entries.");
            
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine();
            $this->error("Cache warming failed: {$e->getMessage()}");
            return 1;
        }
        
        return 0;
    }

    /**
     * Clear performance-related caches.
     */
    private function clearCache(): int
    {
        $this->info('Clearing performance caches...');
        
        // Clear query optimization caches
        $queryKeysCleared = $this->queryOptimizer->clearQueryCaches();
        $this->line("Cleared {$queryKeysCleared} query cache keys");
        
        // Optimize connection pool
        $poolOptimized = $this->connectionPool->optimizePool();
        $this->line("Optimized connection pool, removed {$poolOptimized} unused clients");
        
        // Clear general cache patterns
        $patterns = [
            'health_status_v2:*',
            'batch_health_v2:*',
            'warm_health_v2:*',
            'expiring_tokens_v2:*',
            'batch_query_v2:*',
        ];
        
        $totalCleared = 0;
        
        foreach ($patterns as $pattern) {
            try {
                $store = Cache::getStore();
                if (method_exists($store, 'getRedis')) {
                    $keys = $store->getRedis()->keys($pattern);
                    if (!empty($keys)) {
                        $store->getRedis()->del($keys);
                        $totalCleared += count($keys);
                    }
                } else {
                    // For non-Redis stores, we can't use pattern matching
                    // Just flush all cache as a fallback
                    Cache::flush();
                    $this->info("Non-Redis cache detected. Performed full cache flush instead of pattern-based clearing.");
                    break; // No need to continue with other patterns
                }
            } catch (\Exception $e) {
                $this->warn("Failed to clear pattern {$pattern}: {$e->getMessage()}");
            }
        }
        
        $this->info("Cache clearing completed. Cleared {$totalCleared} total cache keys.");
        
        return 0;
    }

    /**
     * Optimize the entire system performance.
     */
    private function optimizeSystem(): int
    {
        $this->info('Starting comprehensive system optimization...');
        
        if (!$this->option('force')) {
            if (!$this->confirm('This will clear caches and optimize the system. Continue?')) {
                $this->info('Optimization cancelled.');
                return 0;
            }
        }
        
        // Step 1: Clear caches
        $this->line('Step 1: Clearing caches...');
        $this->clearCache();
        
        // Step 2: Optimize batch processing
        $this->line('Step 2: Optimizing batch processing...');
        $batchOptimization = $this->batchProcessor->optimizeBatchProcessing();
        $this->displayOptimizationResults($batchOptimization);
        
        // Step 3: Warm cache for active users
        $this->line('Step 3: Warming cache for active users...');
        $activeUserIds = User::whereHas('cloudStorageHealthStatuses', function ($query) {
            $query->where('last_successful_operation_at', '>', now()->subDays(3));
        })->limit(50)->pluck('id')->toArray();
        
        if (!empty($activeUserIds)) {
            $warmedCount = $this->healthValidator->warmCache($activeUserIds, ['google-drive']);
            $this->line("Warmed cache for {$warmedCount} entries");
        }
        
        // Step 4: Connection pool warmup
        $this->line('Step 4: Warming up connection pool...');
        $configurations = [
            [
                'client_id' => config('cloud-storage.providers.google-drive.config.client_id'),
                'client_secret' => config('cloud-storage.providers.google-drive.config.client_secret'),
                'scopes' => ['https://www.googleapis.com/auth/drive.file', 'https://www.googleapis.com/auth/drive'],
            ]
        ];
        
        $poolWarmed = $this->connectionPool->warmUpPool($configurations);
        $this->line("Warmed up {$poolWarmed} connection pool entries");
        
        $this->info('System optimization completed successfully!');
        
        return 0;
    }

    /**
     * Perform batch token refresh.
     */
    private function batchRefresh(): int
    {
        $batchSize = (int) $this->option('batch-size');
        $dryRun = $this->option('dry-run');
        
        $this->info("Starting batch token refresh (batch size: {$batchSize}, dry run: " . ($dryRun ? 'yes' : 'no') . ")...");
        
        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm('This will perform actual token refresh operations. Continue?')) {
                $this->info('Batch refresh cancelled.');
                return 0;
            }
        }
        
        $results = $this->batchProcessor->processBatchRefresh(30, $batchSize, $dryRun);
        
        $this->displayBatchRefreshResults($results);
        
        return $results['success'] ?? false ? 0 : 1;
    }

    /**
     * Show performance statistics.
     */
    private function showStats(): int
    {
        $this->info('Token Performance Statistics');
        $this->line(str_repeat('=', 50));
        
        // Connection pool stats
        $poolStats = $this->connectionPool->getPoolStats();
        $this->line('Connection Pool:');
        $this->line("  Active Clients: {$poolStats['active_clients']}/{$poolStats['max_pool_size']}");
        $this->line("  Utilization: {$poolStats['pool_utilization']}%");
        
        // Batch processing stats
        $batchStats = $this->batchProcessor->getBatchProcessingStats(24);
        if (!empty($batchStats['recent_batches'])) {
            $this->line('Recent Batch Processing (24h):');
            $this->line("  Total Batches: " . count($batchStats['recent_batches']));
            
            $totalTokens = array_sum(array_column($batchStats['recent_batches'], 'total_tokens'));
            $totalSuccessful = array_sum(array_column($batchStats['recent_batches'], 'successful'));
            $successRate = $totalTokens > 0 ? round(($totalSuccessful / $totalTokens) * 100, 2) : 0;
            
            $this->line("  Total Tokens Processed: {$totalTokens}");
            $this->line("  Success Rate: {$successRate}%");
        }
        
        // Cache statistics (if available)
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $cacheInfo = Cache::getRedis()->info('memory');
                if (isset($cacheInfo['used_memory_human'])) {
                    $this->line('Redis Cache:');
                    $this->line("  Memory Used: {$cacheInfo['used_memory_human']}");
                }
            } else {
                $this->line('Cache: ' . get_class(Cache::getStore()));
            }
        } catch (\Exception $e) {
            // Cache info not available
        }
        
        return 0;
    }

    /**
     * Display index analysis results.
     */
    private function displayIndexAnalysis(array $analysis): void
    {
        $this->line('Database Index Analysis:');
        
        if (isset($analysis['existing_indexes'])) {
            foreach ($analysis['existing_indexes'] as $table => $indexes) {
                $this->line("  {$table}: " . count($indexes) . " indexes");
            }
        }
        
        if (isset($analysis['recommendations']['missing_token_indexes'])) {
            $missing = count($analysis['recommendations']['missing_token_indexes']);
            if ($missing > 0) {
                $this->warn("  Missing {$missing} recommended token indexes");
            }
        }
        
        if (isset($analysis['recommendations']['missing_health_indexes'])) {
            $missing = count($analysis['recommendations']['missing_health_indexes']);
            if ($missing > 0) {
                $this->warn("  Missing {$missing} recommended health status indexes");
            }
        }
    }

    /**
     * Display connection pool statistics.
     */
    private function displayPoolStats(array $stats): void
    {
        $this->line('Connection Pool Status:');
        $this->line("  Active Clients: {$stats['active_clients']}/{$stats['max_pool_size']}");
        $this->line("  Utilization: {$stats['pool_utilization']}%");
        
        if (isset($stats['client_usage']) && !empty($stats['client_usage'])) {
            $totalUsage = array_sum(array_column($stats['client_usage'], 'usage_count'));
            $this->line("  Total Usage: {$totalUsage} requests");
        }
    }

    /**
     * Display batch processing statistics.
     */
    private function displayBatchStats(array $stats): void
    {
        $this->line('Batch Processing Status:');
        
        if (!empty($stats['recent_batches'])) {
            $recentCount = count($stats['recent_batches']);
            $this->line("  Recent Batches (24h): {$recentCount}");
            
            if ($recentCount > 0) {
                $latestBatch = $stats['recent_batches'][0];
                $this->line("  Latest Batch Success Rate: " . ($latestBatch['success_rate'] ?? 'N/A'));
            }
        } else {
            $this->line("  No recent batch processing activity");
        }
    }

    /**
     * Provide performance recommendations.
     */
    private function provideRecommendations(array $indexAnalysis, array $poolStats, array $batchStats): void
    {
        $recommendations = [];
        
        // Index recommendations
        if (isset($indexAnalysis['recommendations']['missing_token_indexes']) && 
            !empty($indexAnalysis['recommendations']['missing_token_indexes'])) {
            $recommendations[] = "Run database migration to add missing token indexes";
        }
        
        if (isset($indexAnalysis['recommendations']['missing_health_indexes']) && 
            !empty($indexAnalysis['recommendations']['missing_health_indexes'])) {
            $recommendations[] = "Run database migration to add missing health status indexes";
        }
        
        // Pool recommendations
        if ($poolStats['pool_utilization'] > 80) {
            $recommendations[] = "Consider increasing connection pool size (current utilization: {$poolStats['pool_utilization']}%)";
        }
        
        if ($poolStats['pool_utilization'] < 20 && $poolStats['active_clients'] > 0) {
            $recommendations[] = "Connection pool may be oversized (current utilization: {$poolStats['pool_utilization']}%)";
        }
        
        // Cache recommendations
        $recommendations[] = "Run 'token:optimize-performance warm-cache' to improve response times";
        $recommendations[] = "Schedule regular cache warming for active users";
        
        if (empty($recommendations)) {
            $this->info('  System performance appears optimal');
        } else {
            foreach ($recommendations as $recommendation) {
                $this->line("  • {$recommendation}");
            }
        }
    }

    /**
     * Display optimization results.
     */
    private function displayOptimizationResults(array $results): void
    {
        if (isset($results['connection_pool_optimized'])) {
            $this->line("  Connection pool: removed {$results['connection_pool_optimized']} unused clients");
        }
        
        if (isset($results['query_caches_cleared'])) {
            $this->line("  Query caches: cleared {$results['query_caches_cleared']} entries");
        }
        
        if (isset($results['error'])) {
            $this->warn("  Optimization error: {$results['error']}");
        }
    }

    /**
     * Display batch refresh results.
     */
    private function displayBatchRefreshResults(array $results): void
    {
        $this->line('Batch Refresh Results:');
        $this->line("  Total Tokens: {$results['total_tokens']}");
        $this->line("  Processed: {$results['processed']}");
        $this->line("  Successful: {$results['successful']}");
        $this->line("  Failed: {$results['failed']}");
        $this->line("  Success Rate: " . ($results['success_rate'] * 100) . "%");
        
        if (isset($results['processing_time_ms'])) {
            $this->line("  Processing Time: {$results['processing_time_ms']}ms");
        }
        
        if (!empty($results['errors'])) {
            $this->line('Errors:');
            foreach (array_slice($results['errors'], 0, 5) as $error) {
                $this->line("  • User {$error['user_id']}: {$error['error']}");
            }
            
            if (count($results['errors']) > 5) {
                $remaining = count($results['errors']) - 5;
                $this->line("  ... and {$remaining} more errors");
            }
        }
    }
}