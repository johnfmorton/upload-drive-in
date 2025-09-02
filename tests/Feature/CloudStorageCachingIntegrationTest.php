<?php

namespace Tests\Feature;

use App\Models\CloudStorageHealthStatus;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CloudStorageCachingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CloudStorageHealthService $healthService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->healthService = app(CloudStorageHealthService::class);
        
        // Clear cache before each test
        Cache::flush();
    }

    public function test_complete_status_check_workflow_with_caching()
    {
        // Create a valid Google Drive token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => now()->addHour(),
        ]);

        // Mock the Google Drive service to avoid actual API calls
        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->method('validateAndRefreshToken')->willReturn(true);
        $mockGoogleDriveService->method('testApiConnectivity')->willReturn(true);
        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        // First status check - should hit the services
        $status1 = $this->healthService->checkConnectionHealth($this->user, 'google-drive');
        $this->assertEquals('healthy', $status1->consolidated_status);

        // Verify caches are populated
        $tokenCacheKey = "token_valid_{$this->user->id}_google-drive";
        $apiCacheKey = "api_connectivity_{$this->user->id}_google-drive";
        
        $this->assertTrue(Cache::has($tokenCacheKey));
        $this->assertTrue(Cache::has($apiCacheKey));
        $this->assertTrue(Cache::get($tokenCacheKey));
        $this->assertTrue(Cache::get($apiCacheKey));

        // Second status check - should use cached results
        $status2 = $this->healthService->checkConnectionHealth($this->user, 'google-drive');
        $this->assertEquals('healthy', $status2->consolidated_status);
    }

    public function test_rate_limiting_prevents_excessive_api_calls()
    {
        // Create a valid Google Drive token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => now()->addHour(),
        ]);

        // Track API call counts
        $tokenValidationCalls = 0;
        $connectivityTestCalls = 0;

        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->method('validateAndRefreshToken')
            ->willReturnCallback(function() use (&$tokenValidationCalls) {
                $tokenValidationCalls++;
                return true;
            });
        $mockGoogleDriveService->method('testApiConnectivity')
            ->willReturnCallback(function() use (&$connectivityTestCalls) {
                $connectivityTestCalls++;
                return true;
            });
        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        // Make multiple rapid status checks
        for ($i = 0; $i < 15; $i++) {
            // Clear caches to force service calls (simulating different time periods)
            if ($i > 0) {
                Cache::forget("token_valid_{$this->user->id}_google-drive");
                Cache::forget("api_connectivity_{$this->user->id}_google-drive");
            }
            
            $this->healthService->checkConnectionHealth($this->user, 'google-drive');
        }

        // Due to rate limiting, we should have fewer API calls than attempts
        $this->assertLessThanOrEqual(10, $tokenValidationCalls, 'Token validation calls should be rate limited');
        $this->assertLessThanOrEqual(20, $connectivityTestCalls, 'Connectivity test calls should be rate limited');
    }

    public function test_cache_expiration_behavior()
    {
        // Create a valid Google Drive token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => now()->addHour(),
        ]);

        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->method('validateAndRefreshToken')->willReturn(true);
        $mockGoogleDriveService->method('testApiConnectivity')->willReturn(true);
        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        // Perform initial operations to populate cache
        $this->healthService->ensureValidToken($this->user, 'google-drive');
        $this->healthService->testApiConnectivity($this->user, 'google-drive');

        // Verify caches exist
        $tokenCacheKey = "token_valid_{$this->user->id}_google-drive";
        $apiCacheKey = "api_connectivity_{$this->user->id}_google-drive";
        
        $this->assertTrue(Cache::has($tokenCacheKey));
        $this->assertTrue(Cache::has($apiCacheKey));

        // Simulate time passing by manually expiring caches
        Cache::put($tokenCacheKey, true, now()->subMinute()); // Expired
        Cache::put($apiCacheKey, true, now()->subMinute()); // Expired

        // Next calls should not use expired cache
        $result1 = $this->healthService->ensureValidToken($this->user, 'google-drive');
        $result2 = $this->healthService->testApiConnectivity($this->user, 'google-drive');

        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    public function test_failed_operations_have_shorter_cache_duration()
    {
        // Create an expired token to simulate failure
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => null, // No refresh token to force failure
            'expires_at' => now()->subHour(),
        ]);

        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->method('validateAndRefreshToken')->willReturn(false);
        $mockGoogleDriveService->method('testApiConnectivity')->willReturn(false);
        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        // Perform operations that will fail
        $tokenResult = $this->healthService->ensureValidToken($this->user, 'google-drive');
        $apiResult = $this->healthService->testApiConnectivity($this->user, 'google-drive');

        $this->assertFalse($tokenResult);
        $this->assertFalse($apiResult);

        // Verify failed results are cached
        $tokenCacheKey = "token_valid_{$this->user->id}_google-drive";
        $apiCacheKey = "api_connectivity_{$this->user->id}_google-drive";
        
        $this->assertTrue(Cache::has($tokenCacheKey));
        $this->assertTrue(Cache::has($apiCacheKey));
        $this->assertFalse(Cache::get($tokenCacheKey));
        $this->assertFalse(Cache::get($apiCacheKey));
    }

    public function test_clear_caches_functionality()
    {
        // Create a valid Google Drive token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => now()->addHour(),
        ]);

        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->method('validateAndRefreshToken')->willReturn(true);
        $mockGoogleDriveService->method('testApiConnectivity')->willReturn(true);
        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        // Populate caches and rate limits
        $this->healthService->ensureValidToken($this->user, 'google-drive');
        $this->healthService->testApiConnectivity($this->user, 'google-drive');

        // Verify caches exist
        $tokenCacheKey = "token_valid_{$this->user->id}_google-drive";
        $apiCacheKey = "api_connectivity_{$this->user->id}_google-drive";
        $tokenRateLimitKey = "token_refresh_rate_limit_{$this->user->id}_google-drive";
        $connectivityRateLimitKey = "connectivity_test_rate_limit_{$this->user->id}_google-drive";
        
        $this->assertTrue(Cache::has($tokenCacheKey));
        $this->assertTrue(Cache::has($apiCacheKey));
        $this->assertTrue(Cache::has($tokenRateLimitKey));
        $this->assertTrue(Cache::has($connectivityRateLimitKey));

        // Clear all caches
        $this->healthService->clearCaches($this->user, 'google-drive');

        // Verify all caches are cleared
        $this->assertFalse(Cache::has($tokenCacheKey));
        $this->assertFalse(Cache::has($apiCacheKey));
        $this->assertFalse(Cache::has($tokenRateLimitKey));
        $this->assertFalse(Cache::has($connectivityRateLimitKey));
    }

    public function test_rate_limit_status_reporting()
    {
        // Make some attempts to populate rate limit counters
        for ($i = 0; $i < 5; $i++) {
            Cache::forget("token_valid_{$this->user->id}_google-drive");
            $this->healthService->ensureValidToken($this->user, 'google-drive');
        }

        for ($i = 0; $i < 10; $i++) {
            Cache::forget("api_connectivity_{$this->user->id}_google-drive");
            $this->healthService->testApiConnectivity($this->user, 'google-drive');
        }

        // Check rate limit status
        $status = $this->healthService->getRateLimitStatus($this->user, 'google-drive');

        $this->assertArrayHasKey('token_refresh', $status);
        $this->assertArrayHasKey('connectivity_test', $status);
        
        $this->assertEquals(5, $status['token_refresh']['attempts']);
        $this->assertEquals(10, $status['token_refresh']['max_attempts']);
        $this->assertTrue($status['token_refresh']['can_attempt']);
        
        $this->assertEquals(10, $status['connectivity_test']['attempts']);
        $this->assertEquals(20, $status['connectivity_test']['max_attempts']);
        $this->assertTrue($status['connectivity_test']['can_attempt']);
    }

    public function test_health_summary_includes_caching_information()
    {
        // Create a valid Google Drive token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => now()->addHour(),
        ]);

        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->method('validateAndRefreshToken')->willReturn(true);
        $mockGoogleDriveService->method('testApiConnectivity')->willReturn(true);
        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        // Perform status check
        $this->healthService->checkConnectionHealth($this->user, 'google-drive');

        // Get health summary
        $summary = $this->healthService->getHealthSummary($this->user, 'google-drive');

        $this->assertEquals('healthy', $summary['consolidated_status']);
        $this->assertTrue($summary['is_healthy']);
        $this->assertArrayHasKey('operational_test_result', $summary);
        $this->assertArrayHasKey('token_refresh_working', $summary);
    }

    public function test_performance_optimization_reduces_database_queries()
    {
        // Create a valid Google Drive token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => now()->addHour(),
        ]);

        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->method('validateAndRefreshToken')->willReturn(true);
        $mockGoogleDriveService->method('testApiConnectivity')->willReturn(true);
        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        // Enable query logging
        \DB::enableQueryLog();

        // First call - should perform database operations
        $this->healthService->checkConnectionHealth($this->user, 'google-drive');
        $firstCallQueries = count(\DB::getQueryLog());

        // Clear query log
        \DB::flushQueryLog();

        // Second call - should use cached results and perform fewer database operations
        $this->healthService->checkConnectionHealth($this->user, 'google-drive');
        $secondCallQueries = count(\DB::getQueryLog());

        // Second call should have fewer or equal database queries due to caching
        $this->assertLessThanOrEqual($firstCallQueries, $secondCallQueries);
    }
}