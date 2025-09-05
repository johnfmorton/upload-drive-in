<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Models\CloudStorageHealthStatus;
use App\Services\TokenExpirationQueryOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Performance tests for database query optimization.
 * Tests the effectiveness of database indexes and query optimization strategies.
 */
class QueryOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private TokenExpirationQueryOptimizer $queryOptimizer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->queryOptimizer = app(TokenExpirationQueryOptimizer::class);
        
        // Create recommended indexes for testing
        $this->createTestIndexes();
    }

    /**
     * Test expiring tokens query performance with different dataset sizes.
     */
    public function test_expiring_tokens_query_performance(): void
    {
        $testSizes = [100, 500, 1000];
        $results = [];
        
        foreach ($testSizes as $size) {
            // Create test dataset
            $this->createTokenDataset($size);
            
            // Test optimized query
            $startTime = microtime(true);
            $optimizedResults = $this->queryOptimizer->getExpiringTokens(30, 'google-drive', false);
            $optimizedTime = microtime(true) - $startTime;
            
            // Test standard query
            $startTime = microtime(true);
            $standardResults = $this->runStandardExpiringTokensQuery();
            $standardTime = microtime(true) - $startTime;
            
            $results[$size] = [
                'dataset_size' => $size,
                'optimized_time_ms' => round($optimizedTime * 1000, 2),
                'standard_time_ms' => round($standardTime * 1000, 2),
                'optimized_count' => $optimizedResults->count(),
                'standard_count' => $standardResults->count(),
                'performance_ratio' => $standardTime > 0 ? round($optimizedTime / $standardTime, 3) : 1,
            ];
            
            // Clean up for next iteration
            GoogleDriveToken::query()->delete();
            User::query()->delete();
        }
        
        Log::info('Expiring tokens query performance test results', $results);
        
        // Assert performance characteristics
        foreach ($results as $size => $result) {
            $this->assertEquals(
                $result['standard_count'],
                $result['optimized_count'],
                "Results should be consistent for dataset size {$size}"
            );
            
            // Performance should not degrade significantly with larger datasets
            $this->assertLessThan(
                1000, // 1 second
                $result['optimized_time_ms'],
                "Optimized query should complete within 1 second for dataset size {$size}"
            );
        }
        
        // Check that performance scales reasonably
        $smallDatasetTime = $results[100]['optimized_time_ms'];
        $largeDatasetTime = $results[1000]['optimized_time_ms'];
        $scalingFactor = $largeDatasetTime / $smallDatasetTime;
        
        $this->assertLessThan(
            5, // Should not be more than 5x slower for 10x data
            $scalingFactor,
            'Query performance should scale reasonably with dataset size'
        );
    }

    /**
     * Test health status validation query performance.
     */
    public function test_health_status_validation_query_performance(): void
    {
        $testSizes = [50, 200, 500];
        $results = [];
        
        foreach ($testSizes as $size) {
            // Create test dataset
            $this->createHealthStatusDataset($size);
            
            // Test optimized query
            $startTime = microtime(true);
            $optimizedResults = $this->queryOptimizer->getHealthStatusNeedingValidation(
                30, ['google-drive'], false
            );
            $optimizedTime = microtime(true) - $startTime;
            
            // Test standard query
            $startTime = microtime(true);
            $standardResults = $this->runStandardHealthStatusQuery();
            $standardTime = microtime(true) - $startTime;
            
            $results[$size] = [
                'dataset_size' => $size,
                'optimized_time_ms' => round($optimizedTime * 1000, 2),
                'standard_time_ms' => round($standardTime * 1000, 2),
                'optimized_count' => $optimizedResults->count(),
                'standard_count' => $standardResults->count(),
                'performance_ratio' => $standardTime > 0 ? round($optimizedTime / $standardTime, 3) : 1,
            ];
            
            // Clean up for next iteration
            CloudStorageHealthStatus::query()->delete();
            User::query()->delete();
        }
        
        Log::info('Health status validation query performance test results', $results);
        
        // Assert performance characteristics
        foreach ($results as $size => $result) {
            $this->assertEquals(
                $result['standard_count'],
                $result['optimized_count'],
                "Results should be consistent for dataset size {$size}"
            );
            
            $this->assertLessThan(
                500, // 500ms
                $result['optimized_time_ms'],
                "Optimized query should complete within 500ms for dataset size {$size}"
            );
        }
    }

    /**
     * Test batch update performance.
     */
    public function test_batch_update_performance(): void
    {
        // Create test tokens
        $users = User::factory()->count(100)->create();
        $tokens = [];
        
        foreach ($users as $user) {
            $token = GoogleDriveToken::factory()->create([
                'user_id' => $user->id,
                'expires_at' => now()->addHours(1),
            ]);
            $tokens[] = $token;
        }
        
        // Prepare batch updates
        $tokenUpdates = [];
        foreach ($tokens as $token) {
            $tokenUpdates[$token->id] = now()->addHours(2);
        }
        
        // Test batch update
        $startTime = microtime(true);
        $updatedCount = $this->queryOptimizer->batchUpdateTokenExpirations($tokenUpdates);
        $batchTime = microtime(true) - $startTime;
        
        // Test individual updates for comparison
        $individualTokens = GoogleDriveToken::factory()->count(100)->create([
            'expires_at' => now()->addHours(1),
        ]);
        
        $startTime = microtime(true);
        $individualUpdatedCount = 0;
        
        foreach ($individualTokens as $token) {
            $token->update(['expires_at' => now()->addHours(2)]);
            $individualUpdatedCount++;
        }
        
        $individualTime = microtime(true) - $startTime;
        
        // Calculate performance improvement
        $performanceImprovement = ($individualTime - $batchTime) / $individualTime * 100;
        
        Log::info('Batch update performance test results', [
            'tokens_updated' => count($tokenUpdates),
            'batch_time_ms' => round($batchTime * 1000, 2),
            'individual_time_ms' => round($individualTime * 1000, 2),
            'performance_improvement_percent' => round($performanceImprovement, 2),
            'batch_updated_count' => $updatedCount,
            'individual_updated_count' => $individualUpdatedCount,
        ]);
        
        // Assert batch update is more efficient
        $this->assertEquals(count($tokenUpdates), $updatedCount, 'All tokens should be updated');
        $this->assertGreaterThan(50, $performanceImprovement, 'Batch update should be at least 50% faster');
        $this->assertLessThan($individualTime * 0.5, $batchTime, 'Batch update should be at least 2x faster');
    }

    /**
     * Test index effectiveness.
     */
    public function test_index_effectiveness(): void
    {
        // Create large dataset to test index effectiveness
        $this->createLargeTokenDataset(2000);
        
        // Test queries with EXPLAIN to check index usage
        $expiringTokensExplain = DB::select("
            EXPLAIN SELECT * FROM google_drive_tokens 
            WHERE expires_at > NOW() 
            AND expires_at <= DATE_ADD(NOW(), INTERVAL 30 MINUTE)
            AND refresh_failure_count < 5
            AND requires_user_intervention = 0
            AND refresh_token IS NOT NULL
        ");
        
        $userTokensExplain = DB::select("
            EXPLAIN SELECT * FROM google_drive_tokens 
            WHERE user_id = 1 
            AND expires_at > NOW()
        ");
        
        Log::info('Index effectiveness test results', [
            'expiring_tokens_explain' => $expiringTokensExplain,
            'user_tokens_explain' => $userTokensExplain,
        ]);
        
        // Check that queries are using indexes efficiently
        foreach ($expiringTokensExplain as $row) {
            // Should not be doing full table scan for large datasets
            $this->assertNotEquals('ALL', $row->type, 'Query should use index, not full table scan');
        }
        
        // Test actual query performance
        $startTime = microtime(true);
        $results = $this->queryOptimizer->getExpiringTokens(30, 'google-drive', false);
        $queryTime = microtime(true) - $startTime;
        
        Log::info('Large dataset query performance', [
            'dataset_size' => 2000,
            'results_count' => $results->count(),
            'query_time_ms' => round($queryTime * 1000, 2),
        ]);
        
        // Should complete quickly even with large dataset
        $this->assertLessThan(1000, $queryTime * 1000, 'Query should complete within 1 second for large dataset');
    }

    /**
     * Test query cache effectiveness.
     */
    public function test_query_cache_effectiveness(): void
    {
        // Create test dataset
        $this->createTokenDataset(200);
        
        // First query (cache miss)
        $startTime = microtime(true);
        $firstResults = $this->queryOptimizer->getExpiringTokens(30, 'google-drive', false);
        $firstTime = microtime(true) - $startTime;
        
        // Second query (cache hit)
        $startTime = microtime(true);
        $secondResults = $this->queryOptimizer->getExpiringTokens(30, 'google-drive', true);
        $secondTime = microtime(true) - $startTime;
        
        // Third query without cache
        $startTime = microtime(true);
        $thirdResults = $this->queryOptimizer->getExpiringTokens(30, 'google-drive', false);
        $thirdTime = microtime(true) - $startTime;
        
        $cacheImprovement = ($firstTime - $secondTime) / $firstTime * 100;
        
        Log::info('Query cache effectiveness test results', [
            'first_query_time_ms' => round($firstTime * 1000, 2),
            'cached_query_time_ms' => round($secondTime * 1000, 2),
            'third_query_time_ms' => round($thirdTime * 1000, 2),
            'cache_improvement_percent' => round($cacheImprovement, 2),
            'results_count' => $firstResults->count(),
        ]);
        
        // Assert cache provides improvement
        $this->assertEquals($firstResults->count(), $secondResults->count(), 'Cached results should match');
        $this->assertEquals($firstResults->count(), $thirdResults->count(), 'All results should match');
        $this->assertGreaterThan(20, $cacheImprovement, 'Cache should provide at least 20% improvement');
    }

    /**
     * Test multi-provider query performance.
     */
    public function test_multi_provider_query_performance(): void
    {
        // Create users with multiple providers
        $users = User::factory()->count(100)->create();
        
        foreach ($users as $user) {
            // Create health status for multiple providers
            CloudStorageHealthStatus::factory()->create([
                'user_id' => $user->id,
                'provider' => 'google-drive',
                'consolidated_status' => 'healthy',
                'last_live_validation_at' => now()->subHours(2),
            ]);
            
            if (rand(0, 1)) {
                CloudStorageHealthStatus::factory()->create([
                    'user_id' => $user->id,
                    'provider' => 'dropbox',
                    'consolidated_status' => 'healthy',
                    'last_live_validation_at' => now()->subHours(1),
                ]);
            }
        }
        
        // Test single provider query
        $startTime = microtime(true);
        $singleProviderResults = $this->queryOptimizer->getHealthStatusNeedingValidation(
            30, ['google-drive'], false
        );
        $singleProviderTime = microtime(true) - $startTime;
        
        // Test multi-provider query
        $startTime = microtime(true);
        $multiProviderResults = $this->queryOptimizer->getHealthStatusNeedingValidation(
            30, ['google-drive', 'dropbox'], false
        );
        $multiProviderTime = microtime(true) - $startTime;
        
        // Test users with multiple providers query
        $startTime = microtime(true);
        $multiProviderUsers = $this->queryOptimizer->getUsersWithMultipleProviders(
            ['google-drive', 'dropbox'], false
        );
        $multiProviderUsersTime = microtime(true) - $startTime;
        
        Log::info('Multi-provider query performance test results', [
            'single_provider_time_ms' => round($singleProviderTime * 1000, 2),
            'multi_provider_time_ms' => round($multiProviderTime * 1000, 2),
            'multi_provider_users_time_ms' => round($multiProviderUsersTime * 1000, 2),
            'single_provider_count' => $singleProviderResults->count(),
            'multi_provider_count' => $multiProviderResults->count(),
            'multi_provider_users_count' => $multiProviderUsers->count(),
        ]);
        
        // Assert reasonable performance
        $this->assertLessThan(500, $multiProviderTime * 1000, 'Multi-provider query should complete within 500ms');
        $this->assertLessThan(300, $multiProviderUsersTime * 1000, 'Multi-provider users query should complete within 300ms');
        $this->assertGreaterThanOrEqual($singleProviderResults->count(), $multiProviderResults->count());
    }

    /**
     * Test database index recommendations.
     */
    public function test_database_index_recommendations(): void
    {
        $indexAnalysis = $this->queryOptimizer->optimizeIndexes();
        
        Log::info('Database index analysis results', $indexAnalysis);
        
        // Should provide analysis without errors
        $this->assertArrayHasKey('existing_indexes', $indexAnalysis);
        $this->assertArrayHasKey('performance_analysis', $indexAnalysis);
        $this->assertArrayHasKey('recommendations', $indexAnalysis);
        
        // Check that recommended indexes are identified
        if (isset($indexAnalysis['recommendations']['missing_token_indexes'])) {
            $this->assertIsArray($indexAnalysis['recommendations']['missing_token_indexes']);
        }
        
        if (isset($indexAnalysis['recommendations']['missing_health_indexes'])) {
            $this->assertIsArray($indexAnalysis['recommendations']['missing_health_indexes']);
        }
    }

    /**
     * Create test indexes for performance testing.
     */
    private function createTestIndexes(): void
    {
        try {
            // Create indexes if they don't exist
            if (!$this->indexExists('google_drive_tokens', 'idx_expires_at_refresh_failures')) {
                DB::statement('CREATE INDEX idx_expires_at_refresh_failures ON google_drive_tokens (expires_at, refresh_failure_count, requires_user_intervention)');
            }
            
            if (!$this->indexExists('google_drive_tokens', 'idx_user_id_expires_at')) {
                DB::statement('CREATE INDEX idx_user_id_expires_at ON google_drive_tokens (user_id, expires_at)');
            }
            
            if (!$this->indexExists('cloud_storage_health_statuses', 'idx_provider_validation_failures')) {
                DB::statement('CREATE INDEX idx_provider_validation_failures ON cloud_storage_health_statuses (provider, last_live_validation_at, consecutive_failures)');
            }
            
        } catch (\Exception $e) {
            // Indexes may already exist, which is fine for testing
            Log::debug('Index creation skipped (may already exist)', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create token dataset for testing.
     */
    private function createTokenDataset(int $size): void
    {
        $users = User::factory()->count($size)->create();
        
        foreach ($users as $index => $user) {
            GoogleDriveToken::factory()->create([
                'user_id' => $user->id,
                'expires_at' => now()->addMinutes(rand(1, 120)), // Mix of expiring and non-expiring
                'refresh_failure_count' => rand(0, 3),
                'requires_user_intervention' => rand(0, 10) > 8, // 20% require intervention
            ]);
        }
    }

    /**
     * Create large token dataset for index testing.
     */
    private function createLargeTokenDataset(int $size): void
    {
        // Use raw SQL for faster insertion
        $values = [];
        $users = User::factory()->count($size)->create();
        
        foreach ($users as $user) {
            $values[] = sprintf(
                "(%d, '%s', '%s', 'Bearer', '%s', '[]', 0, 0, NOW(), NOW())",
                $user->id,
                'test_access_token_' . $user->id,
                'test_refresh_token_' . $user->id,
                now()->addMinutes(rand(1, 120))->toDateTimeString()
            );
        }
        
        // Insert in batches
        $batches = array_chunk($values, 100);
        
        foreach ($batches as $batch) {
            $valuesStr = implode(',', $batch);
            DB::statement("
                INSERT INTO google_drive_tokens 
                (user_id, access_token, refresh_token, token_type, expires_at, scopes, refresh_failure_count, requires_user_intervention, created_at, updated_at) 
                VALUES {$valuesStr}
            ");
        }
    }

    /**
     * Create health status dataset for testing.
     */
    private function createHealthStatusDataset(int $size): void
    {
        $users = User::factory()->count($size)->create();
        
        foreach ($users as $user) {
            CloudStorageHealthStatus::factory()->create([
                'user_id' => $user->id,
                'provider' => 'google-drive',
                'consolidated_status' => rand(0, 1) ? 'healthy' : 'connection_issues',
                'last_live_validation_at' => rand(0, 1) ? now()->subHours(rand(1, 48)) : null,
                'consecutive_failures' => rand(0, 5),
            ]);
        }
    }

    /**
     * Run standard expiring tokens query for comparison.
     */
    private function runStandardExpiringTokensQuery()
    {
        return GoogleDriveToken::where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addMinutes(30))
            ->where('refresh_failure_count', '<', 5)
            ->where('requires_user_intervention', false)
            ->whereNotNull('refresh_token')
            ->with('user')
            ->orderBy('expires_at', 'asc')
            ->get();
    }

    /**
     * Run standard health status query for comparison.
     */
    private function runStandardHealthStatusQuery()
    {
        $cutoffTime = now()->subMinutes(30);
        
        return CloudStorageHealthStatus::where('provider', 'google-drive')
            ->where(function ($query) use ($cutoffTime) {
                $query->whereNull('last_live_validation_at')
                      ->orWhere('last_live_validation_at', '<', $cutoffTime);
            })
            ->where('consecutive_failures', '<', 10)
            ->with('user')
            ->orderByRaw('last_live_validation_at ASC NULLS FIRST')
            ->get();
    }
}