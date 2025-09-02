<?php

namespace Tests\Unit\Services;

use App\Enums\CloudStorageErrorType;
use App\Models\CloudStorageHealthStatus;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageLogService;
use App\Services\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class CloudStorageHealthServiceCachingTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageHealthService $service;
    private User $user;
    private $mockLogService;
    private $mockGoogleDriveService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        
        $this->mockLogService = Mockery::mock(CloudStorageLogService::class);
        $this->mockGoogleDriveService = Mockery::mock(GoogleDriveService::class);
        
        $this->service = new CloudStorageHealthService($this->mockLogService);
        
        // Mock the GoogleDriveService in the container
        $this->app->instance(GoogleDriveService::class, $this->mockGoogleDriveService);
        
        // Clear cache before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_ensure_valid_token_caches_successful_result()
    {
        // Mock successful token validation
        $this->mockGoogleDriveService
            ->shouldReceive('validateAndRefreshToken')
            ->once()
            ->with($this->user)
            ->andReturn(true);

        // First call should hit the service
        $result1 = $this->service->ensureValidToken($this->user, 'google-drive');
        $this->assertTrue($result1);

        // Second call should use cache (no service call)
        $result2 = $this->service->ensureValidToken($this->user, 'google-drive');
        $this->assertTrue($result2);

        // Verify cache key exists
        $cacheKey = "token_valid_{$this->user->id}_google-drive";
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertTrue(Cache::get($cacheKey));
    }

    public function test_ensure_valid_token_caches_failed_result()
    {
        // Mock failed token validation
        $this->mockGoogleDriveService
            ->shouldReceive('validateAndRefreshToken')
            ->once()
            ->with($this->user)
            ->andReturn(false);

        // First call should hit the service
        $result1 = $this->service->ensureValidToken($this->user, 'google-drive');
        $this->assertFalse($result1);

        // Second call should use cache (no service call)
        $result2 = $this->service->ensureValidToken($this->user, 'google-drive');
        $this->assertFalse($result2);

        // Verify cache key exists with failed result
        $cacheKey = "token_valid_{$this->user->id}_google-drive";
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertFalse(Cache::get($cacheKey));
    }

    public function test_test_api_connectivity_caches_successful_result()
    {
        // Mock successful API connectivity test
        $this->mockGoogleDriveService
            ->shouldReceive('testApiConnectivity')
            ->once()
            ->with($this->user)
            ->andReturn(true);

        // First call should hit the service
        $result1 = $this->service->testApiConnectivity($this->user, 'google-drive');
        $this->assertTrue($result1);

        // Second call should use cache (no service call)
        $result2 = $this->service->testApiConnectivity($this->user, 'google-drive');
        $this->assertTrue($result2);

        // Verify cache key exists
        $cacheKey = "api_connectivity_{$this->user->id}_google-drive";
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertTrue(Cache::get($cacheKey));
    }

    public function test_test_api_connectivity_caches_failed_result()
    {
        // Mock failed API connectivity test
        $this->mockGoogleDriveService
            ->shouldReceive('testApiConnectivity')
            ->once()
            ->with($this->user)
            ->andReturn(false);

        // First call should hit the service
        $result1 = $this->service->testApiConnectivity($this->user, 'google-drive');
        $this->assertFalse($result1);

        // Second call should use cache (no service call)
        $result2 = $this->service->testApiConnectivity($this->user, 'google-drive');
        $this->assertFalse($result2);

        // Verify cache key exists with failed result
        $cacheKey = "api_connectivity_{$this->user->id}_google-drive";
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertFalse(Cache::get($cacheKey));
    }

    public function test_token_refresh_rate_limiting()
    {
        // Mock successful token validation for first few attempts
        $this->mockGoogleDriveService
            ->shouldReceive('validateAndRefreshToken')
            ->times(10) // Should only be called 10 times due to rate limiting
            ->with($this->user)
            ->andReturn(true);

        // Make 15 attempts - only first 10 should succeed due to rate limiting
        $results = [];
        for ($i = 0; $i < 15; $i++) {
            // Clear cache to force service calls
            Cache::forget("token_valid_{$this->user->id}_google-drive");
            $results[] = $this->service->ensureValidToken($this->user, 'google-drive');
        }

        // First 10 should succeed, last 5 should fail due to rate limiting
        for ($i = 0; $i < 10; $i++) {
            $this->assertTrue($results[$i], "Attempt $i should succeed");
        }
        for ($i = 10; $i < 15; $i++) {
            $this->assertFalse($results[$i], "Attempt $i should fail due to rate limiting");
        }
    }

    public function test_connectivity_test_rate_limiting()
    {
        // Mock successful API connectivity test for first few attempts
        $this->mockGoogleDriveService
            ->shouldReceive('testApiConnectivity')
            ->times(20) // Should only be called 20 times due to rate limiting
            ->with($this->user)
            ->andReturn(true);

        // Make 25 attempts - only first 20 should succeed due to rate limiting
        $results = [];
        for ($i = 0; $i < 25; $i++) {
            // Clear cache to force service calls
            Cache::forget("api_connectivity_{$this->user->id}_google-drive");
            $results[] = $this->service->testApiConnectivity($this->user, 'google-drive');
        }

        // First 20 should succeed, last 5 should return last known result (true)
        for ($i = 0; $i < 25; $i++) {
            $this->assertTrue($results[$i], "Attempt $i should succeed or return cached result");
        }
    }

    public function test_clear_caches_removes_all_cached_data()
    {
        // Set up cached data
        $tokenCacheKey = "token_valid_{$this->user->id}_google-drive";
        $apiCacheKey = "api_connectivity_{$this->user->id}_google-drive";
        $tokenRateLimitKey = "token_refresh_rate_limit_{$this->user->id}_google-drive";
        $connectivityRateLimitKey = "connectivity_test_rate_limit_{$this->user->id}_google-drive";

        Cache::put($tokenCacheKey, true, now()->addMinutes(5));
        Cache::put($apiCacheKey, true, now()->addMinutes(2));
        Cache::put($tokenRateLimitKey, 5, now()->addHour());
        Cache::put($connectivityRateLimitKey, 10, now()->addHour());

        // Verify cache exists
        $this->assertTrue(Cache::has($tokenCacheKey));
        $this->assertTrue(Cache::has($apiCacheKey));
        $this->assertTrue(Cache::has($tokenRateLimitKey));
        $this->assertTrue(Cache::has($connectivityRateLimitKey));

        // Clear caches
        $this->service->clearCaches($this->user, 'google-drive');

        // Verify all caches are cleared
        $this->assertFalse(Cache::has($tokenCacheKey));
        $this->assertFalse(Cache::has($apiCacheKey));
        $this->assertFalse(Cache::has($tokenRateLimitKey));
        $this->assertFalse(Cache::has($connectivityRateLimitKey));
    }

    public function test_get_rate_limit_status_returns_correct_information()
    {
        // Set up some rate limit data
        $tokenRateLimitKey = "token_refresh_rate_limit_{$this->user->id}_google-drive";
        $connectivityRateLimitKey = "connectivity_test_rate_limit_{$this->user->id}_google-drive";

        Cache::put($tokenRateLimitKey, 3, now()->addHour());
        Cache::put($connectivityRateLimitKey, 15, now()->addHour());

        $status = $this->service->getRateLimitStatus($this->user, 'google-drive');

        $this->assertEquals([
            'token_refresh' => [
                'attempts' => 3,
                'max_attempts' => 10,
                'window_minutes' => 60,
                'can_attempt' => true,
            ],
            'connectivity_test' => [
                'attempts' => 15,
                'max_attempts' => 20,
                'window_minutes' => 60,
                'can_attempt' => true,
            ],
        ], $status);
    }

    public function test_rate_limit_status_shows_blocked_when_limit_exceeded()
    {
        // Set up rate limit data at maximum
        $tokenRateLimitKey = "token_refresh_rate_limit_{$this->user->id}_google-drive";
        $connectivityRateLimitKey = "connectivity_test_rate_limit_{$this->user->id}_google-drive";

        Cache::put($tokenRateLimitKey, 10, now()->addHour());
        Cache::put($connectivityRateLimitKey, 20, now()->addHour());

        $status = $this->service->getRateLimitStatus($this->user, 'google-drive');

        $this->assertFalse($status['token_refresh']['can_attempt']);
        $this->assertFalse($status['connectivity_test']['can_attempt']);
    }

    public function test_cache_expiration_times_are_correct()
    {
        // Skip this test if not using Redis cache driver
        if (!Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $this->markTestSkipped('This test requires Redis cache driver');
        }

        // Mock successful operations
        $this->mockGoogleDriveService
            ->shouldReceive('validateAndRefreshToken')
            ->once()
            ->andReturn(true);

        $this->mockGoogleDriveService
            ->shouldReceive('testApiConnectivity')
            ->once()
            ->andReturn(true);

        // Perform operations
        $this->service->ensureValidToken($this->user, 'google-drive');
        $this->service->testApiConnectivity($this->user, 'google-drive');

        // Check cache TTL (approximately)
        $tokenCacheKey = "token_valid_{$this->user->id}_google-drive";
        $apiCacheKey = "api_connectivity_{$this->user->id}_google-drive";

        // Token cache should expire in approximately 5 minutes (300 seconds)
        $tokenTtl = Cache::getStore()->getRedis()->ttl($tokenCacheKey);
        $this->assertGreaterThan(290, $tokenTtl); // Allow some variance
        $this->assertLessThan(301, $tokenTtl);

        // API cache should expire in approximately 2 minutes (120 seconds)
        $apiTtl = Cache::getStore()->getRedis()->ttl($apiCacheKey);
        $this->assertGreaterThan(110, $apiTtl); // Allow some variance
        $this->assertLessThan(121, $apiTtl);
    }

    public function test_failed_operations_have_shorter_cache_times()
    {
        // Skip this test if not using Redis cache driver
        if (!Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $this->markTestSkipped('This test requires Redis cache driver');
        }

        // Mock failed operations
        $this->mockGoogleDriveService
            ->shouldReceive('validateAndRefreshToken')
            ->once()
            ->andReturn(false);

        $this->mockGoogleDriveService
            ->shouldReceive('testApiConnectivity')
            ->once()
            ->andReturn(false);

        // Perform operations
        $this->service->ensureValidToken($this->user, 'google-drive');
        $this->service->testApiConnectivity($this->user, 'google-drive');

        // Check cache TTL for failed operations
        $tokenCacheKey = "token_valid_{$this->user->id}_google-drive";
        $apiCacheKey = "api_connectivity_{$this->user->id}_google-drive";

        // Failed token validation should be cached for 1 minute (60 seconds)
        $tokenTtl = Cache::getStore()->getRedis()->ttl($tokenCacheKey);
        $this->assertGreaterThan(50, $tokenTtl); // Allow some variance
        $this->assertLessThan(61, $tokenTtl);

        // Failed API connectivity should be cached for 30 seconds
        $apiTtl = Cache::getStore()->getRedis()->ttl($apiCacheKey);
        $this->assertGreaterThan(25, $apiTtl); // Allow some variance
        $this->assertLessThan(31, $apiTtl);
    }
}