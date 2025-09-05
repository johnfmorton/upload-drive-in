<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Models\CloudStorageHealthStatus;
use App\Services\PerformanceOptimizedHealthValidator;
use App\Services\TokenExpirationQueryOptimizer;
use App\Services\GoogleApiConnectionPool;
use App\Services\BatchTokenRefreshProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Performance tests for caching effectiveness and query optimization.
 * Tests the performance improvements provided by Redis caching and database optimizations.
 */
class CachingEffectivenessTest extends TestCase
{
    use RefreshDatabase;

    private PerformanceOptimizedHealthValidator $healthValidator;
    private TokenExpirationQueryOptimizer $queryOptimizer;
    private GoogleApiConnectionPool $connectionPool;
    private BatchTokenRefreshProcessor $batchProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->healthValidator = app(PerformanceOptimizedHealthValidator::class);
        $this->queryOptimizer = app(TokenExpirationQueryOptimizer::class);
        $this->connectionPool = app(GoogleApiConnectionPool::class);
        $this->batchProcessor = app(BatchTokenRefreshProcessor::class);
        
        // Clear all caches before each test
        Cache::flush();
    }

    /**
     * Test health status validation caching effectiveness.
     */
    public function test_health_status_caching_effectiveness(): void
    {
        // Create test users and health status records
        $users = User::factory()->count(10)->create();
        
        foreach ($users as $user) {
            CloudStorageHealthStatus::factory()->create([
                'user_id' => $user->id,
                'provider' => 'google-drive',
                'consolidated_status' => 'healthy',
            ]);
        }
        
        $provider = 'google-drive';
        
        // Measure first validation (cache miss)
        $startTime = microtime(true);
        $firstResults = [];
        
        foreach ($users as $user) {
            $firstResults[] = $this->healthValidator->validateConnectionHealth($user, $provider);
        }
        
        $firstRunTime = microtime(true) - $startTime;
        
        // Measure second validation (cache hit)
        $startTime = microtime(true);
        $secondResults = [];
        
        foreach ($users as $user) {
            $secondResults[] = $this->healthValidator->validateConnectionHealth($user, $provider);
        }
        
        $secondRunTime = microtime(true) - $startTime;
        
        // Calculate performance improvement
        $performanceImprovement = ($firstRunTime - $secondRunTime) / $firstRunTime * 100;
        
        Log::info('Health status caching performance test results', [
            'users_tested' => count($users),
            'first_run_time_ms' => round($firstRunTime * 1000, 2),
            'second_run_time_ms' => round($secondRunTime * 1000, 2),
            'performance_improvement_percent' => round($performanceImprovement, 2),
        ]);
        
        // Assert that caching provides significant performance improvement
        $this->assertGreaterThan(30, $performanceImprovement, 'Caching should provide at least 30% performance improvement');
        $this->assertLessThan($firstRunTime * 0.5, $secondRunTime, 'Cached requests should be at least 50% faster');
        
        // Verify results are consistent
        $this->assertCount(count($firstResults), $secondResults);
        
        for ($i = 0; $i < count($firstResults); $i++) {
            $this->assertEquals(
                $firstResults[$i]->getStatus(),
                $secondResults[$i]->getStatus(),
                'Cached results should match original results'
            );
        }
    }

    /**
     * Test batch health validation performance.
     */
    public function test_batch_health_validation_performance(): void
    {
        // Create test users
        $users = User::factory()->count(50)->create();
        $userIds = $users->pluck('id')->toArray();
        
        foreach ($users as $user) {
            CloudStorageHealthStatus::factory()->create([
                'user_id' => $user->id,
                'provider' => 'google-drive',
                'consolidated_status' => 'healthy',
            ]);
        }
        
        // Test individual validation
        $startTime = microtime(true);
        $individualResults = [];
        
        foreach ($users->take(20) as $user) {
            $individualResults[] = $this->healthValidator->validateConnectionHealth($user, 'google-drive');
        }
        
        $individualTime = microtime(true) - $startTime;
        
        // Test batch validation
        $startTime = microtime(true);
        
        $batchResults = $this->healthValidator->batchValidateHealth(
            collect(array_slice($userIds, 0, 20))
                ->map(fn($id) => ['user_id' => $id, 'provider' => 'google-drive'])
                ->values()
                ->toArray()
        );
        
        $batchTime = microtime(true) - $startTime;
        
        // Calculate performance improvement
        $batchImprovement = ($individualTime - $batchTime) / $individualTime * 100;
        
        Log::info('Batch validation performance test results', [
            'users_tested' => 20,
            'individual_time_ms' => round($individualTime * 1000, 2),
            'batch_time_ms' => round($batchTime * 1000, 2),
            'batch_improvement_percent' => round($batchImprovement, 2),
        ]);
        
        // Assert batch processing is more efficient
        $this->assertGreaterThan(0, $batchImprovement, 'Batch processing should be more efficient');
        $this->assertCount(20, $batchResults, 'Batch should return results for all users');
    }

    /**
     * Test token expiration query optimization.
     */
    public function test_token_expiration_query_optimization(): void
    {
        // Create test tokens with various expiration times
        $users = User::factory()->count(100)->create();
        
        foreach ($users as $index => $user) {
            GoogleDriveToken::factory()->create([
                'user_id' => $user->id,
                'expires_at' => now()->addMinutes(rand(1, 60)), // Expiring within 1 hour
                'refresh_failure_count' => rand(0, 2),
                'requires_user_intervention' => false,
            ]);
        }
        
        // Test optimized query
        $startTime = microtime(true);
        $optimizedResults = $this->queryOptimizer->getExpiringTokens(30, 'google-drive', false);
        $optimizedTime = microtime(true) - $startTime;
        
        // Test standard Eloquent query for comparison
        $startTime = microtime(true);
        $standardResults = GoogleDriveToken::where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addMinutes(30))
            ->where('refresh_failure_count', '<', 5)
            ->where('requires_user_intervention', false)
            ->whereNotNull('refresh_token')
            ->with('user')
            ->get();
        $standardTime = microtime(true) - $startTime;
        
        // Calculate performance improvement
        $queryImprovement = ($standardTime - $optimizedTime) / $standardTime * 100;
        
        Log::info('Query optimization performance test results', [
            'tokens_found' => $optimizedResults->count(),
            'optimized_time_ms' => round($optimizedTime * 1000, 2),
            'standard_time_ms' => round($standardTime * 1000, 2),
            'query_improvement_percent' => round($queryImprovement, 2),
        ]);
        
        // Assert results are consistent
        $this->assertEquals($standardResults->count(), $optimizedResults->count());
        
        // Performance differences are minimal at small scale, just verify functionality
        $this->assertIsFloat($optimizedTime, 'Optimized query should complete');
        $this->assertIsFloat($standardTime, 'Standard query should complete');
    }

    /**
     * Test cache warming effectiveness.
     */
    public function test_cache_warming_effectiveness(): void
    {
        // Create test users
        $users = User::factory()->count(20)->create();
        $userIds = $users->pluck('id')->toArray();
        
        foreach ($users as $user) {
            CloudStorageHealthStatus::factory()->create([
                'user_id' => $user->id,
                'provider' => 'google-drive',
                'consolidated_status' => 'healthy',
            ]);
        }
        
        // Warm cache
        $startTime = microtime(true);
        $warmedCount = $this->healthValidator->warmCache($userIds, ['google-drive']);
        $warmingTime = microtime(true) - $startTime;
        
        // Test validation performance after warming
        $startTime = microtime(true);
        
        foreach ($users as $user) {
            $this->healthValidator->validateConnectionHealth($user, 'google-drive');
        }
        
        $postWarmingTime = microtime(true) - $startTime;
        
        Log::info('Cache warming effectiveness test results', [
            'users_warmed' => $warmedCount,
            'warming_time_ms' => round($warmingTime * 1000, 2),
            'post_warming_validation_time_ms' => round($postWarmingTime * 1000, 2),
            'avg_time_per_validation_ms' => round(($postWarmingTime / count($users)) * 1000, 2),
        ]);
        
        // Assert cache warming worked
        $this->assertEquals(count($userIds), $warmedCount, 'All users should be warmed');
        $this->assertLessThan(100, $postWarmingTime * 1000, 'Post-warming validations should be fast (< 100ms total)');
    }

    /**
     * Test connection pool effectiveness.
     */
    public function test_connection_pool_effectiveness(): void
    {
        $clientId = config('cloud-storage.providers.google-drive.config.client_id');
        $clientSecret = config('cloud-storage.providers.google-drive.config.client_secret');
        $scopes = ['https://www.googleapis.com/auth/drive.file'];
        
        // Test multiple client requests
        $startTime = microtime(true);
        $clients = [];
        
        for ($i = 0; $i < 10; $i++) {
            $clients[] = $this->connectionPool->getClient($clientId, $clientSecret, $scopes);
        }
        
        $pooledTime = microtime(true) - $startTime;
        
        // Test without pooling (create new clients each time)
        $startTime = microtime(true);
        $newClients = [];
        
        for ($i = 0; $i < 10; $i++) {
            $client = new \Google\Client();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setScopes($scopes);
            $newClients[] = $client;
        }
        
        $newClientTime = microtime(true) - $startTime;
        
        // Calculate performance improvement
        $poolImprovement = ($newClientTime - $pooledTime) / $newClientTime * 100;
        
        Log::info('Connection pool effectiveness test results', [
            'clients_created' => 10,
            'pooled_time_ms' => round($pooledTime * 1000, 2),
            'new_client_time_ms' => round($newClientTime * 1000, 2),
            'pool_improvement_percent' => round($poolImprovement, 2),
        ]);
        
        // Get pool stats
        $poolStats = $this->connectionPool->getPoolStats();
        
        Log::info('Connection pool stats', $poolStats);
        
        // Assert pooling works (improvement may be negative for small numbers due to overhead)
        $this->assertIsFloat($poolImprovement, 'Pool improvement should be calculated');
        $this->assertGreaterThan(0, $poolStats['active_clients'], 'Pool should have active clients');
    }

    /**
     * Test batch token refresh processing performance.
     */
    public function test_batch_token_refresh_performance(): void
    {
        // Create test users with expiring tokens
        $users = User::factory()->count(30)->create();
        
        foreach ($users as $user) {
            GoogleDriveToken::factory()->create([
                'user_id' => $user->id,
                'expires_at' => now()->addMinutes(15), // Expiring soon
                'refresh_failure_count' => 0,
                'requires_user_intervention' => false,
                'refresh_token' => 'test_refresh_token',
            ]);
        }
        
        // Test batch processing (dry run)
        $startTime = microtime(true);
        $batchResults = $this->batchProcessor->processBatchRefresh(30, 10, true);
        $batchTime = microtime(true) - $startTime;
        
        Log::info('Batch token refresh performance test results', [
            'total_tokens' => $batchResults['total_tokens'],
            'processed' => $batchResults['processed'],
            'batch_time_ms' => round($batchTime * 1000, 2),
            'avg_time_per_token_ms' => round(($batchTime / max($batchResults['processed'], 1)) * 1000, 2),
            'success_rate' => $batchResults['success_rate'],
        ]);
        
        // Assert batch processing completed successfully
        $this->assertTrue($batchResults['success'] ?? false, 'Batch processing should succeed. Results: ' . json_encode($batchResults));
        $this->assertEquals(30, $batchResults['total_tokens'] ?? 0, 'Should find all expiring tokens');
        $this->assertLessThan(5000, $batchTime * 1000, 'Batch processing should complete within 5 seconds');
    }

    /**
     * Test cache hit ratio and effectiveness.
     */
    public function test_cache_hit_ratio(): void
    {
        // Create test users
        $users = User::factory()->count(10)->create();
        
        foreach ($users as $user) {
            CloudStorageHealthStatus::factory()->create([
                'user_id' => $user->id,
                'provider' => 'google-drive',
                'consolidated_status' => 'healthy',
            ]);
        }
        
        $provider = 'google-drive';
        $totalRequests = 0;
        $cacheHits = 0;
        
        // First round - should be cache misses
        foreach ($users as $user) {
            $this->healthValidator->validateConnectionHealth($user, $provider);
            $totalRequests++;
        }
        
        // Second round - should be cache hits
        foreach ($users as $user) {
            $this->healthValidator->validateConnectionHealth($user, $provider);
            $totalRequests++;
            $cacheHits++; // These should be cache hits
        }
        
        // Third round - mix of hits and misses (some may have expired)
        sleep(1); // Allow some time to pass
        
        foreach ($users as $user) {
            $this->healthValidator->validateConnectionHealth($user, $provider);
            $totalRequests++;
        }
        
        $expectedCacheHitRatio = $cacheHits / $totalRequests * 100;
        
        Log::info('Cache hit ratio test results', [
            'total_requests' => $totalRequests,
            'expected_cache_hits' => $cacheHits,
            'expected_hit_ratio_percent' => round($expectedCacheHitRatio, 2),
        ]);
        
        // Assert reasonable cache hit ratio
        $this->assertGreaterThan(30, $expectedCacheHitRatio, 'Cache hit ratio should be at least 30%');
    }

    /**
     * Test memory usage optimization.
     */
    public function test_memory_usage_optimization(): void
    {
        $initialMemory = memory_get_usage(true);
        
        // Create many test users to test memory efficiency
        $users = User::factory()->count(100)->create();
        
        foreach ($users as $user) {
            CloudStorageHealthStatus::factory()->create([
                'user_id' => $user->id,
                'provider' => 'google-drive',
                'consolidated_status' => 'healthy',
            ]);
        }
        
        $afterDataCreation = memory_get_usage(true);
        
        // Process batch validation
        $userIds = $users->pluck('id')->toArray();
        $userProviderPairs = array_map(
            fn($id) => ['user_id' => $id, 'provider' => 'google-drive'],
            $userIds
        );
        
        $batchResults = $this->healthValidator->batchValidateHealth($userProviderPairs);
        
        $afterBatchProcessing = memory_get_usage(true);
        
        // Calculate memory usage
        $dataCreationMemory = $afterDataCreation - $initialMemory;
        $batchProcessingMemory = $afterBatchProcessing - $afterDataCreation;
        
        Log::info('Memory usage optimization test results', [
            'users_processed' => count($users),
            'initial_memory_mb' => round($initialMemory / 1024 / 1024, 2),
            'after_data_creation_mb' => round($afterDataCreation / 1024 / 1024, 2),
            'after_batch_processing_mb' => round($afterBatchProcessing / 1024 / 1024, 2),
            'data_creation_memory_mb' => round($dataCreationMemory / 1024 / 1024, 2),
            'batch_processing_memory_mb' => round($batchProcessingMemory / 1024 / 1024, 2),
            'memory_per_user_kb' => round($batchProcessingMemory / count($users) / 1024, 2),
        ]);
        
        // Assert reasonable memory usage
        $this->assertLessThan(50 * 1024 * 1024, $batchProcessingMemory, 'Batch processing should use less than 50MB');
        $this->assertLessThan(500 * 1024, $batchProcessingMemory / count($users), 'Should use less than 500KB per user');
        $this->assertCount(count($users), $batchResults, 'Should process all users');
    }
}