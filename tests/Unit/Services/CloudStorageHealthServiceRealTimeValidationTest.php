<?php

namespace Tests\Unit\Services;

use App\Models\CloudStorageHealthStatus;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageLogService;
use App\Services\CloudStorageManager;
use App\Services\HealthStatus;
use App\Services\RealTimeHealthValidator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CloudStorageHealthServiceRealTimeValidationTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageHealthService $healthService;
    private CloudStorageLogService $mockLogService;
    private CloudStorageManager $mockStorageManager;
    private RealTimeHealthValidator $mockValidator;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        
        $this->mockLogService = Mockery::mock(CloudStorageLogService::class);
        $this->mockStorageManager = Mockery::mock(CloudStorageManager::class);
        $this->mockValidator = Mockery::mock(RealTimeHealthValidator::class);

        $this->healthService = new CloudStorageHealthService(
            $this->mockLogService,
            $this->mockStorageManager,
            $this->mockValidator
        );

        // Mock log service methods to prevent errors
        $this->mockLogService->shouldReceive('logStatusDetermination')->andReturn(null);
        $this->mockLogService->shouldReceive('logCacheOperation')->andReturn(null);
        $this->mockLogService->shouldReceive('logHealthStatusChange')->andReturn(null);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_health_check_rate_limiting_prevents_api_abuse(): void
    {
        // Arrange
        $provider = 'google-drive';
        
        // Create health status with recent live validation
        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => $provider,
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'last_live_validation_at' => now()->subSeconds(30), // Recent validation
        ]);

        // Set up health check rate limiting to be at the limit
        Cache::put("health_check_rate_limit_{$this->user->id}_{$provider}", 6, now()->addMinute());

        // Validator should not be called due to rate limiting
        $this->mockValidator->shouldNotReceive('validateConnectionHealth');

        // Act
        $result = $this->healthService->determineConsolidatedStatus($this->user, $provider);

        // Assert
        $this->assertEquals('healthy', $result);
    }

    public function test_health_check_rate_limiting_allows_calls_within_limit(): void
    {
        // Arrange
        $provider = 'google-drive';
        
        // Set up health check rate limiting below the limit
        Cache::put("health_check_rate_limit_{$this->user->id}_{$provider}", 3, now()->addMinute());

        $healthStatus = HealthStatus::healthy(['within_rate_limit' => true]);
        
        // Validator should be called since we're within rate limit
        $this->mockValidator
            ->shouldReceive('validateConnectionHealth')
            ->once()
            ->with($this->user, $provider)
            ->andReturn($healthStatus);

        // Act
        $result = $this->healthService->determineConsolidatedStatus($this->user, $provider);

        // Assert
        $this->assertEquals('healthy', $result);
        
        // Verify rate limit counter was incremented
        $newCount = Cache::get("health_check_rate_limit_{$this->user->id}_{$provider}");
        $this->assertEquals(4, $newCount);
    }

    public function test_get_rate_limit_status_includes_health_check_limits(): void
    {
        // Arrange
        $provider = 'google-drive';
        
        // Set up various rate limit counters
        Cache::put("token_refresh_rate_limit_{$this->user->id}_{$provider}", 5, now()->addHour());
        Cache::put("connectivity_test_rate_limit_{$this->user->id}_{$provider}", 10, now()->addHour());
        Cache::put("health_check_rate_limit_{$this->user->id}_{$provider}", 3, now()->addMinute());

        // Act
        $rateLimitStatus = $this->healthService->getRateLimitStatus($this->user, $provider);

        // Assert
        $this->assertArrayHasKey('health_check', $rateLimitStatus);
        $this->assertEquals(3, $rateLimitStatus['health_check']['attempts']);
        $this->assertEquals(6, $rateLimitStatus['health_check']['max_attempts']);
        $this->assertEquals(1, $rateLimitStatus['health_check']['window_minutes']);
        $this->assertTrue($rateLimitStatus['health_check']['can_attempt']);
        
        // Verify other rate limits are still included
        $this->assertArrayHasKey('token_refresh', $rateLimitStatus);
        $this->assertArrayHasKey('connectivity_test', $rateLimitStatus);
    }

    public function test_get_health_summary_includes_rate_limit_information(): void
    {
        // Arrange
        $provider = 'google-drive';
        
        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => $provider,
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
        ]);

        // Set up rate limiting
        Cache::put("health_check_rate_limit_{$this->user->id}_{$provider}", 2, now()->addMinute());

        // Act
        $summary = $this->healthService->getHealthSummary($this->user, $provider);

        // Assert
        $this->assertArrayHasKey('rate_limits', $summary);
        $this->assertArrayHasKey('health_check', $summary['rate_limits']);
        $this->assertEquals(2, $summary['rate_limits']['health_check']['attempts']);
        $this->assertTrue($summary['rate_limits']['health_check']['can_attempt']);
    }

    public function test_get_health_summary_includes_live_validation_timestamps(): void
    {
        // Arrange
        $provider = 'google-drive';
        $validatedAt = Carbon::parse('2025-01-01 12:00:00');
        $validationDetails = ['test' => 'validation_data'];
        
        $healthStatus = HealthStatus::healthy($validationDetails);
        
        // Create health status record with live validation data
        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => $provider,
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'last_live_validation_at' => $validatedAt,
            'live_validation_result' => $healthStatus->toArray(),
            'api_connectivity_last_tested_at' => $validatedAt,
            'api_connectivity_result' => ['success' => true, 'tested_at' => $validatedAt->toISOString()],
        ]);

        // Act
        $summary = $this->healthService->getHealthSummary($this->user, $provider);

        // Assert
        $this->assertArrayHasKey('live_validation', $summary);
        $this->assertEquals($validatedAt->toISOString(), $summary['live_validation']['last_validation_at']);
        $this->assertEquals($healthStatus->toArray(), $summary['live_validation']['last_validation_result']);
        $this->assertEquals($validatedAt->toISOString(), $summary['live_validation']['api_connectivity_last_tested_at']);
        $this->assertArrayHasKey('rate_limits', $summary);
    }

    public function test_live_validation_updates_database_with_results(): void
    {
        // Arrange
        $provider = 'google-drive';
        $testedAt = now();
        
        $validationDetails = [
            'api_test' => [
                'success' => true,
                'tested_at' => $testedAt->toISOString(),
                'duration_ms' => 150,
            ]
        ];
        
        $healthStatus = HealthStatus::healthy($validationDetails);
        
        $dbHealthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => $provider,
            'status' => 'degraded',
            'consolidated_status' => 'connection_issues',
            'consecutive_failures' => 1,
            'requires_reconnection' => false,
        ]);

        $this->mockValidator
            ->shouldReceive('validateConnectionHealth')
            ->once()
            ->with($this->user, $provider)
            ->andReturn($healthStatus);

        // Act
        $result = $this->healthService->determineConsolidatedStatus($this->user, $provider);

        // Assert
        $this->assertEquals('healthy', $result);
        
        // Verify database was updated with live validation results
        $dbHealthStatus->refresh();
        $this->assertEquals('healthy', $dbHealthStatus->consolidated_status);
        $this->assertNotNull($dbHealthStatus->last_live_validation_at);
        $this->assertEquals($healthStatus->toArray(), $dbHealthStatus->live_validation_result);
        $this->assertNotNull($dbHealthStatus->api_connectivity_last_tested_at);
        $this->assertEquals($validationDetails['api_test'], $dbHealthStatus->api_connectivity_result);
    }

    public function test_auto_correction_detects_healthy_status_with_expired_tokens(): void
    {
        // Arrange
        $provider = 'google-drive';
        $expiredTime = now()->subHours(2);
        
        // Create health status with healthy consolidated status but expired token
        $dbHealthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => $provider,
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'token_expires_at' => $expiredTime,
        ]);

        // Mock validator to return authentication required (correct status)
        $correctedHealthStatus = HealthStatus::authenticationRequired('Token expired', ['auto_corrected' => true]);
        
        $this->mockValidator
            ->shouldReceive('validateConnectionHealth')
            ->once()
            ->with($this->user, $provider)
            ->andReturn($correctedHealthStatus);

        // Act
        $result = $this->healthService->determineConsolidatedStatus($this->user, $provider);

        // Assert
        $this->assertEquals('authentication_required', $result);
        
        // Verify the inconsistency was detected and corrected
        $dbHealthStatus->refresh();
        $this->assertEquals('authentication_required', $dbHealthStatus->consolidated_status);
    }

    public function test_auto_correction_detects_not_connected_with_recent_success(): void
    {
        // Arrange
        $provider = 'google-drive';
        $recentSuccess = now()->subMinutes(30);
        
        // Create health status showing not connected but with recent successful operation
        $dbHealthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => $provider,
            'status' => 'healthy',
            'consolidated_status' => 'not_connected',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'last_successful_operation_at' => $recentSuccess,
        ]);

        // Mock validator to return healthy (correct status)
        $correctedHealthStatus = HealthStatus::healthy(['auto_corrected' => true]);
        
        $this->mockValidator
            ->shouldReceive('validateConnectionHealth')
            ->once()
            ->with($this->user, $provider)
            ->andReturn($correctedHealthStatus);

        // Act
        $result = $this->healthService->determineConsolidatedStatus($this->user, $provider);

        // Assert
        $this->assertEquals('healthy', $result);
        
        // Verify the inconsistency was detected and corrected
        $dbHealthStatus->refresh();
        $this->assertEquals('healthy', $dbHealthStatus->consolidated_status);
    }

    public function test_rate_limiting_respects_cached_status_when_limited(): void
    {
        // Arrange
        $provider = 'google-drive';
        
        // Create health status with recent live validation
        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => $provider,
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'last_live_validation_at' => now()->subMinutes(2), // Within 5-minute cache window
        ]);

        // Set up health check rate limiting to be at the limit
        Cache::put("health_check_rate_limit_{$this->user->id}_{$provider}", 6, now()->addMinute());

        // Validator should not be called due to rate limiting and recent cache
        $this->mockValidator->shouldNotReceive('validateConnectionHealth');

        // Act
        $result = $this->healthService->determineConsolidatedStatus($this->user, $provider);

        // Assert - should use cached status
        $this->assertEquals('healthy', $result);
    }

    public function test_rate_limiting_ignores_stale_cache_when_limited(): void
    {
        // Arrange
        $provider = 'google-drive';
        
        // Create health status with stale live validation (older than 5 minutes)
        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => $provider,
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'last_live_validation_at' => now()->subMinutes(10), // Stale cache
        ]);

        // Set up health check rate limiting to be at the limit
        Cache::put("health_check_rate_limit_{$this->user->id}_{$provider}", 6, now()->addMinute());

        // Mock validator should be called since cache is stale
        $healthStatus = HealthStatus::healthy(['fresh_validation' => true]);
        $this->mockValidator
            ->shouldReceive('validateConnectionHealth')
            ->once()
            ->with($this->user, $provider)
            ->andReturn($healthStatus);

        // Act
        $result = $this->healthService->determineConsolidatedStatus($this->user, $provider);

        // Assert
        $this->assertEquals('healthy', $result);
    }
}