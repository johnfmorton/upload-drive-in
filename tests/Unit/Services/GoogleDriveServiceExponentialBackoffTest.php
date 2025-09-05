<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\GoogleDriveService;
use App\Services\TokenRefreshCoordinator;
use App\Services\RefreshResult;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageLogService;
use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Enums\TokenRefreshErrorType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mockery;
use Exception;

class GoogleDriveServiceExponentialBackoffTest extends TestCase
{
    use RefreshDatabase;

    private GoogleDriveService $service;
    private User $user;
    private GoogleDriveToken $token;
    private $mockHealthService;
    private $mockLogService;
    private $mockCoordinator;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN
        ]);
        
        // Create mock services
        $this->mockHealthService = Mockery::mock(CloudStorageHealthService::class);
        $this->mockLogService = Mockery::mock(CloudStorageLogService::class);
        $this->mockCoordinator = Mockery::mock(TokenRefreshCoordinator::class);
        
        // Create service with mocked dependencies
        $this->service = new GoogleDriveService($this->mockHealthService, $this->mockLogService);
        
        // Bind the mock coordinator to the container
        $this->app->instance(TokenRefreshCoordinator::class, $this->mockCoordinator);
        
        // Create test token
        $this->token = GoogleDriveToken::create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->subMinutes(10),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false
        ]);
    }

    public function test_validate_and_refresh_token_uses_coordinator_for_retry_logic()
    {
        // Mock successful refresh result after retries (simulating exponential backoff)
        $refreshResult = RefreshResult::success(['access_token' => 'new_token'], 'Token refreshed after retries');

        // Mock coordinator expectations - the coordinator handles the retry logic internally
        $this->mockCoordinator->shouldReceive('coordinateRefresh')
            ->once()
            ->with($this->user, 'google-drive')
            ->andReturn($refreshResult);

        // Mock log service expectations
        $this->mockLogService->shouldReceive('logTokenRefreshAttempt')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('array'));

        $this->mockLogService->shouldReceive('logTokenRefreshSuccess')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('array'));

        $this->mockLogService->shouldReceive('logProactiveTokenValidation')
            ->once()
            ->with($this->user, 'google-drive', true, true, true);

        $result = $this->service->validateAndRefreshToken($this->user);

        $this->assertTrue($result);
    }

    public function test_validate_and_refresh_token_handles_network_timeout_error_type()
    {
        // Mock network timeout failure result
        $refreshResult = RefreshResult::failure(
            TokenRefreshErrorType::NETWORK_TIMEOUT,
            new Exception('Connection timed out'),
            'Network timeout during refresh'
        );

        // Mock coordinator expectations
        $this->mockCoordinator->shouldReceive('coordinateRefresh')
            ->once()
            ->with($this->user, 'google-drive')
            ->andReturn($refreshResult);

        // Mock log service expectations
        $this->mockLogService->shouldReceive('logTokenRefreshAttempt')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('array'));

        $this->mockLogService->shouldReceive('logTokenRefreshFailure')
            ->once()
            ->with($this->user, 'google-drive', 'Network timeout during refresh', Mockery::on(function ($data) {
                return isset($data['error_type']) && $data['error_type'] === 'network_timeout';
            }));

        $this->mockLogService->shouldReceive('logProactiveTokenValidation')
            ->once()
            ->with($this->user, 'google-drive', true, true, false);

        $result = $this->service->validateAndRefreshToken($this->user);

        $this->assertFalse($result);
    }

    public function test_validate_and_refresh_token_uses_15_minute_proactive_threshold()
    {
        // Create a token that expires in exactly 14 minutes (within 15 minute threshold)
        $this->token->update([
            'expires_at' => Carbon::now()->addMinutes(14)
        ]);

        // Mock successful refresh result
        $refreshResult = RefreshResult::success(['access_token' => 'new_token'], 'Token refreshed proactively');

        // Mock coordinator expectations
        $this->mockCoordinator->shouldReceive('coordinateRefresh')
            ->once()
            ->with($this->user, 'google-drive')
            ->andReturn($refreshResult);

        // Mock log service expectations
        $this->mockLogService->shouldReceive('logTokenRefreshAttempt')
            ->once()
            ->with($this->user, 'google-drive', Mockery::on(function ($data) {
                return isset($data['is_proactive']) && $data['is_proactive'] === true;
            }));

        $this->mockLogService->shouldReceive('logTokenRefreshSuccess')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('array'));

        $this->mockLogService->shouldReceive('logProactiveTokenValidation')
            ->once()
            ->with($this->user, 'google-drive', true, true, true);

        $result = $this->service->validateAndRefreshToken($this->user);

        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}