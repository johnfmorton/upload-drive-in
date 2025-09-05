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

class CloudStorageHealthServiceEnhancedTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_determine_consolidated_status_uses_real_time_validator(): void
    {
        // Arrange
        $provider = 'google-drive';
        
        // Mock the storage manager to return a provider that has valid connection
        $mockProvider = Mockery::mock();
        $mockProvider->shouldReceive('hasValidConnection')->andReturn(true);
        $mockProvider->shouldReceive('validateAndRefreshToken')->andReturn(true);
        $mockProvider->shouldReceive('testApiConnectivity')->andReturn(true);
        
        $this->mockStorageManager
            ->shouldReceive('getProvider')
            ->with($provider)
            ->andReturn($mockProvider);

        // Act
        $result = $this->healthService->determineConsolidatedStatus($this->user, $provider);

        // Assert
        $this->assertEquals('healthy', $result);
    }

    public function test_determine_consolidated_status_handles_authentication_required(): void
    {
        // Arrange
        $provider = 'google-drive';
        
        // Mock the storage manager to return a provider that has invalid token
        $mockProvider = Mockery::mock();
        $mockProvider->shouldReceive('hasValidConnection')->andReturn(false);
        $mockProvider->shouldReceive('validateAndRefreshToken')->andReturn(false);
        
        $this->mockStorageManager
            ->shouldReceive('getProvider')
            ->with($provider)
            ->andReturn($mockProvider);

        // Act
        $result = $this->healthService->determineConsolidatedStatus($this->user, $provider);

        // Assert
        $this->assertEquals('authentication_required', $result);
    }

    public function test_determine_consolidated_status_handles_connection_issues(): void
    {
        // Arrange
        $provider = 'google-drive';
        
        // Mock the storage manager to return a provider that has valid token but API connectivity fails
        $mockProvider = Mockery::mock();
        $mockProvider->shouldReceive('hasValidConnection')->andReturn(true);
        $mockProvider->shouldReceive('validateAndRefreshToken')->andReturn(true);
        $mockProvider->shouldReceive('testApiConnectivity')->andReturn(false);
        
        $this->mockStorageManager
            ->shouldReceive('getProvider')
            ->with($provider)
            ->andReturn($mockProvider);

        // Act
        $result = $this->healthService->determineConsolidatedStatus($this->user, $provider);

        // Assert
        $this->assertEquals('connection_issues', $result);
    }

    public function test_determine_consolidated_status_respects_rate_limiting(): void
    {
        // Arrange
        $provider = 'google-drive';
        
        // Create existing health status with known consolidated status
        $existingHealthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => $provider,
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
        ]);

        // Set up rate limiting - exceed both token refresh and connectivity test limits
        Cache::put("token_refresh_rate_limit_{$this->user->id}_{$provider}", 15, now()->addHour());
        Cache::put("connectivity_test_rate_limit_{$this->user->id}_{$provider}", 25, now()->addHour());

        // Act
        $result = $this->healthService->determineConsolidatedStatus($this->user, $provider);

        // Assert
        $this->assertEquals('healthy', $result);
    }

    public function test_determine_consolidated_status_handles_exceptions(): void
    {
        // Arrange
        $provider = 'google-drive';
        
        // Mock the storage manager to throw an exception
        $this->mockStorageManager
            ->shouldReceive('getProvider')
            ->with($provider)
            ->andThrow(new \Exception('Provider not found'));

        // Act
        $result = $this->healthService->determineConsolidatedStatus($this->user, $provider);

        // Assert
        $this->assertEquals('connection_issues', $result);
    }

    public function test_get_health_summary_includes_live_validation_data(): void
    {
        // Arrange
        $provider = 'google-drive';
        $validatedAt = now();
        $validationDetails = ['test' => 'validation_data'];
        
        $healthStatus = HealthStatus::healthy($validationDetails);
        
        // Create health status record with live validation data
        $dbHealthStatus = CloudStorageHealthStatus::create([
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

    public function test_get_health_summary_auto_corrects_inconsistent_status(): void
    {
        // Arrange
        $provider = 'google-drive';
        
        // Create health status with inconsistent data (healthy status but shows not_connected)
        $dbHealthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => $provider,
            'status' => 'healthy',
            'consolidated_status' => 'not_connected', // Inconsistent!
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'last_successful_operation_at' => now()->subHour(), // Recent success
        ]);

        $correctedHealthStatus = HealthStatus::healthy(['auto_corrected' => true]);
        
        $this->mockValidator
            ->shouldReceive('validateConnectionHealth')
            ->once()
            ->with($this->user, $provider)
            ->andReturn($correctedHealthStatus);

        // Act
        $summary = $this->healthService->getHealthSummary($this->user, $provider);

        // Assert
        $this->assertEquals('healthy', $summary['consolidated_status']);
        
        // Verify the database was updated
        $dbHealthStatus->refresh();
        $this->assertEquals('healthy', $dbHealthStatus->consolidated_status);
    }

    public function test_detect_and_correct_inconsistent_status_with_expired_tokens(): void
    {
        // Arrange
        $provider = 'google-drive';
        $expiredTokenTime = now()->subHours(2);
        $freshTokenTime = now()->addHours(1);
        
        // Create health status with healthy status but expired token
        $dbHealthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => $provider,
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'token_expires_at' => $expiredTokenTime,
        ]);

        $healthStatus = HealthStatus::healthy(['corrected_token' => true]);
        
        // Mock getting fresh token expiration
        $mockProvider = Mockery::mock();
        $mockProvider->shouldReceive('getTokenExpiration')
            ->with($this->user)
            ->andReturn($freshTokenTime);
        
        $this->mockStorageManager
            ->shouldReceive('getProvider')
            ->with($provider)
            ->andReturn($mockProvider);

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

    public function test_update_health_status_from_live_validation_healthy(): void
    {
        // Arrange
        $provider = 'google-drive';
        $validationDetails = ['token_validation' => ['valid' => true], 'api_test' => ['success' => true]];
        $healthStatus = HealthStatus::healthy($validationDetails);
        
        $dbHealthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => $provider,
            'status' => 'degraded',
            'consolidated_status' => 'connection_issues',
            'consecutive_failures' => 2,
            'requires_reconnection' => true,
            'last_error_message' => 'Previous error',
            'last_error_type' => 'network_error',
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
        
        // Verify database was updated correctly
        $dbHealthStatus->refresh();
        $this->assertEquals('healthy', $dbHealthStatus->consolidated_status);
        $this->assertNotNull($dbHealthStatus->last_live_validation_at);
        $this->assertEquals($healthStatus->toArray(), $dbHealthStatus->live_validation_result);
        $this->assertNotNull($dbHealthStatus->api_connectivity_last_tested_at);
        $this->assertNull($dbHealthStatus->last_error_message);
        $this->assertNull($dbHealthStatus->last_error_type);
        $this->assertFalse($dbHealthStatus->requires_reconnection);
        $this->assertEquals(0, $dbHealthStatus->consecutive_failures);
    }

    public function test_update_health_status_from_live_validation_unhealthy(): void
    {
        // Arrange
        $provider = 'google-drive';
        $errorMessage = 'Authentication required';
        $errorType = 'authentication_error';
        $healthStatus = HealthStatus::authenticationRequired($errorMessage, ['error_details' => 'token_expired']);
        
        $dbHealthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => $provider,
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
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
        $this->assertEquals('authentication_required', $result);
        
        // Verify database was updated correctly
        $dbHealthStatus->refresh();
        $this->assertEquals('authentication_required', $dbHealthStatus->consolidated_status);
        $this->assertEquals($errorMessage, $dbHealthStatus->last_error_message);
        $this->assertEquals($errorType, $dbHealthStatus->last_error_type);
        $this->assertTrue($dbHealthStatus->requires_reconnection);
    }

    public function test_rate_limiting_prevents_excessive_api_calls(): void
    {
        // Arrange
        $provider = 'google-drive';
        
        // Set up rate limiting to be at the limit
        Cache::put("token_refresh_rate_limit_{$this->user->id}_{$provider}", 10, now()->addHour());
        Cache::put("connectivity_test_rate_limit_{$this->user->id}_{$provider}", 20, now()->addHour());

        // Create health status with existing consolidated status
        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => $provider,
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
        ]);

        // Validator should not be called due to rate limiting
        $this->mockValidator->shouldNotReceive('validateConnectionHealth');

        // Act
        $result = $this->healthService->determineConsolidatedStatus($this->user, $provider);

        // Assert
        $this->assertEquals('healthy', $result);
    }

    public function test_get_live_validation_timestamps(): void
    {
        // Arrange
        $provider = 'google-drive';
        $validatedAt = now();
        $liveValidationResult = ['status' => 'healthy', 'validated_at' => $validatedAt->toISOString()];
        $apiResult = ['success' => true, 'tested_at' => $validatedAt->toISOString()];
        
        $dbHealthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => $provider,
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'last_live_validation_at' => $validatedAt,
            'live_validation_result' => $liveValidationResult,
            'api_connectivity_last_tested_at' => $validatedAt,
            'api_connectivity_result' => $apiResult,
        ]);

        // Act
        $summary = $this->healthService->getHealthSummary($this->user, $provider);

        // Assert
        $liveValidation = $summary['live_validation'];
        $this->assertEquals($validatedAt->toISOString(), $liveValidation['last_validation_at']);
        $this->assertEquals($liveValidationResult, $liveValidation['last_validation_result']);
        $this->assertEquals($validatedAt->toISOString(), $liveValidation['api_connectivity_last_tested_at']);
        $this->assertEquals($apiResult, $liveValidation['api_connectivity_result']);
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

    public function test_live_validation_updates_api_connectivity_timestamps(): void
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
        
        // Verify API connectivity timestamps were updated
        $dbHealthStatus->refresh();
        $this->assertNotNull($dbHealthStatus->api_connectivity_last_tested_at);
        $this->assertEquals($validationDetails['api_test'], $dbHealthStatus->api_connectivity_result);
    }
}