<?php

namespace Tests\Feature;

use App\Models\CloudStorageHealthStatus;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\GoogleDriveService;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleDriveTokenRefreshComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CloudStorageHealthService $healthService;
    private GoogleDriveService $driveService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->healthService = app(CloudStorageHealthService::class);
        $this->driveService = app(GoogleDriveService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function health_check_shows_healthy_status_after_successful_token_refresh()
    {
        // Arrange - Create expired access token with valid refresh token
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $pastExpiry,
        ]);

        // Mock successful token refresh and API connectivity
        $this->mockSuccessfulTokenRefreshAndApiTest();

        // Act
        $healthStatus = $this->healthService->checkConnectionHealth($this->user, 'google-drive');

        // Assert
        $this->assertEquals('healthy', $healthStatus->consolidated_status);
        $this->assertEquals('Connection is working properly', $healthStatus->getConsolidatedStatusMessage());
        $this->assertTrue($healthStatus->isTokenRefreshWorking());
        $this->assertNotNull($healthStatus->last_success_at);
        $this->assertNotNull($healthStatus->last_token_refresh_attempt_at);
        $this->assertEquals(0, $healthStatus->token_refresh_failures);
        $this->assertEquals('success', $healthStatus->operational_test_result);
    }

    #[Test]
    public function health_check_shows_authentication_required_when_refresh_token_expired()
    {
        // Arrange - Create expired tokens (both access and refresh)
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'expired_refresh_token',
            'expires_at' => $pastExpiry,
        ]);

        // Mock failed token refresh
        $this->mockFailedTokenRefresh();

        // Act
        $healthStatus = $this->healthService->checkConnectionHealth($this->user, 'google-drive');

        // Assert
        $this->assertEquals('authentication_required', $healthStatus->consolidated_status);
        $this->assertEquals('Please reconnect your account', $healthStatus->getConsolidatedStatusMessage());
        $this->assertFalse($healthStatus->isTokenRefreshWorking());
        $this->assertNotNull($healthStatus->last_token_refresh_attempt_at);
        $this->assertGreaterThan(0, $healthStatus->token_refresh_failures);
        $this->assertEquals('failed', $healthStatus->operational_test_result);
    }

    #[Test]
    public function health_check_shows_connection_issues_when_api_fails_after_successful_refresh()
    {
        // Arrange - Create expired access token with valid refresh token
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $pastExpiry,
        ]);

        // Mock successful token refresh but failed API connectivity
        $this->mockSuccessfulTokenRefreshButFailedApi();

        // Act
        $healthStatus = $this->healthService->checkConnectionHealth($this->user, 'google-drive');

        // Assert
        $this->assertEquals('connection_issues', $healthStatus->consolidated_status);
        $this->assertEquals('Experiencing connectivity problems', $healthStatus->getConsolidatedStatusMessage());
        $this->assertTrue($healthStatus->isTokenRefreshWorking());
        $this->assertNotNull($healthStatus->last_token_refresh_attempt_at);
        $this->assertEquals(0, $healthStatus->token_refresh_failures);
        $this->assertEquals('failed', $healthStatus->operational_test_result);
    }

    #[Test]
    public function multiple_health_checks_cache_successful_token_refresh()
    {
        // Arrange - Create expired access token with valid refresh token
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $pastExpiry,
        ]);

        // Mock token refresh that should only happen once due to caching
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')
                   ->with('expired_access_token')
                   ->once();
        $mockClient->shouldReceive('isAccessTokenExpired')
                   ->andReturn(true)
                   ->once();
        $mockClient->shouldReceive('getRefreshToken')
                   ->andReturn('valid_refresh_token')
                   ->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
                   ->with('valid_refresh_token')
                   ->andReturn([
                       'access_token' => 'refreshed_access_token',
                       'expires_in' => 3600,
                       'refresh_token' => 'valid_refresh_token'
                   ])
                   ->once();
        $mockClient->shouldReceive('getAccessToken')
                   ->andReturn('refreshed_access_token')
                   ->atLeast()->once();

        // For subsequent calls, token should be valid
        $mockClient->shouldReceive('setAccessToken')
                   ->with('refreshed_access_token')
                   ->atLeast()->once();
        $mockClient->shouldReceive('isAccessTokenExpired')
                   ->andReturn(false)
                   ->atLeast()->once();

        // Mock API connectivity tests
        $mockDrive = Mockery::mock(Drive::class);
        $mockAbout = Mockery::mock();
        $mockAbout->shouldReceive('get')
                  ->with(['fields' => 'user'])
                  ->andReturn((object)['user' => (object)['displayName' => 'Test User']])
                  ->atLeast()->once();
        $mockDrive->about = $mockAbout;

        $this->replaceDriveServiceMocks($mockClient, $mockDrive);

        // Act - Perform multiple health checks
        $healthStatus1 = $this->healthService->checkConnectionHealth($this->user, 'google-drive');
        $healthStatus2 = $this->healthService->checkConnectionHealth($this->user, 'google-drive');
        $healthStatus3 = $this->healthService->checkConnectionHealth($this->user, 'google-drive');

        // Assert - All should be healthy
        $this->assertEquals('healthy', $healthStatus1->consolidated_status);
        $this->assertEquals('healthy', $healthStatus2->consolidated_status);
        $this->assertEquals('healthy', $healthStatus3->consolidated_status);

        // Verify token was only refreshed once
        $updatedToken = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('refreshed_access_token', $updatedToken->access_token);
    }

    #[Test]
    public function health_check_handles_network_errors_during_token_refresh()
    {
        // Arrange - Create expired access token with valid refresh token
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $pastExpiry,
        ]);

        // Mock network error during token refresh
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')
                   ->with('expired_access_token')
                   ->once();
        $mockClient->shouldReceive('isAccessTokenExpired')
                   ->andReturn(true)
                   ->once();
        $mockClient->shouldReceive('getRefreshToken')
                   ->andReturn('valid_refresh_token')
                   ->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
                   ->with('valid_refresh_token')
                   ->andThrow(new \Exception('Network error: Connection timeout'))
                   ->once();

        $this->replaceDriveServiceMocks($mockClient);

        // Act
        $healthStatus = $this->healthService->checkConnectionHealth($this->user, 'google-drive');

        // Assert
        $this->assertEquals('connection_issues', $healthStatus->consolidated_status);
        $this->assertEquals('Experiencing connectivity problems', $healthStatus->getConsolidatedStatusMessage());
        $this->assertNotNull($healthStatus->last_token_refresh_attempt_at);
        $this->assertGreaterThan(0, $healthStatus->token_refresh_failures);
        $this->assertEquals('failed', $healthStatus->operational_test_result);
    }

    #[Test]
    public function health_check_tracks_consecutive_refresh_failures()
    {
        // Arrange - Create expired access token with refresh token
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'problematic_refresh_token',
            'expires_at' => $pastExpiry,
        ]);

        // Mock consecutive failed token refreshes
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')
                   ->with('expired_access_token')
                   ->times(3);
        $mockClient->shouldReceive('isAccessTokenExpired')
                   ->andReturn(true)
                   ->times(3);
        $mockClient->shouldReceive('getRefreshToken')
                   ->andReturn('problematic_refresh_token')
                   ->times(3);
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
                   ->with('problematic_refresh_token')
                   ->andThrow(new \Exception('invalid_grant: Token has been expired or revoked'))
                   ->times(3);

        $this->replaceDriveServiceMocks($mockClient);

        // Act - Perform multiple health checks to accumulate failures
        $healthStatus1 = $this->healthService->checkConnectionHealth($this->user, 'google-drive');
        $healthStatus2 = $this->healthService->checkConnectionHealth($this->user, 'google-drive');
        $healthStatus3 = $this->healthService->checkConnectionHealth($this->user, 'google-drive');

        // Assert - Failure count should increase
        $this->assertEquals('authentication_required', $healthStatus1->consolidated_status);
        $this->assertEquals('authentication_required', $healthStatus2->consolidated_status);
        $this->assertEquals('authentication_required', $healthStatus3->consolidated_status);

        $this->assertEquals(1, $healthStatus1->token_refresh_failures);
        $this->assertEquals(2, $healthStatus2->token_refresh_failures);
        $this->assertEquals(3, $healthStatus3->token_refresh_failures);

        // After 3 failures, token refresh should be considered not working
        $this->assertFalse($healthStatus3->isTokenRefreshWorking());
    }

    #[Test]
    public function health_check_resets_failure_count_after_successful_refresh()
    {
        // Arrange - Create health status with previous failures
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $pastExpiry,
        ]);

        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'authentication_required',
            'consolidated_status' => 'authentication_required',
            'token_refresh_failures' => 2,
            'last_token_refresh_attempt_at' => Carbon::now()->subMinutes(5),
            'operational_test_result' => 'failed',
        ]);

        // Mock successful token refresh and API connectivity
        $this->mockSuccessfulTokenRefreshAndApiTest();

        // Act
        $healthStatus = $this->healthService->checkConnectionHealth($this->user, 'google-drive');

        // Assert - Failure count should be reset
        $this->assertEquals('healthy', $healthStatus->consolidated_status);
        $this->assertEquals(0, $healthStatus->token_refresh_failures);
        $this->assertTrue($healthStatus->isTokenRefreshWorking());
        $this->assertEquals('success', $healthStatus->operational_test_result);
    }

    private function mockSuccessfulTokenRefreshAndApiTest(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')
                   ->with('expired_access_token')
                   ->once();
        $mockClient->shouldReceive('isAccessTokenExpired')
                   ->andReturn(true)
                   ->once();
        $mockClient->shouldReceive('getRefreshToken')
                   ->andReturn('valid_refresh_token')
                   ->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
                   ->with('valid_refresh_token')
                   ->andReturn([
                       'access_token' => 'refreshed_access_token',
                       'expires_in' => 3600,
                       'refresh_token' => 'valid_refresh_token'
                   ])
                   ->once();
        $mockClient->shouldReceive('getAccessToken')
                   ->andReturn('refreshed_access_token')
                   ->atLeast()->once();

        $mockDrive = Mockery::mock(Drive::class);
        $mockAbout = Mockery::mock();
        $mockAbout->shouldReceive('get')
                  ->with(['fields' => 'user'])
                  ->andReturn((object)['user' => (object)['displayName' => 'Test User']])
                  ->once();
        $mockDrive->about = $mockAbout;

        $this->replaceDriveServiceMocks($mockClient, $mockDrive);
    }

    private function mockFailedTokenRefresh(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')
                   ->with('expired_access_token')
                   ->once();
        $mockClient->shouldReceive('isAccessTokenExpired')
                   ->andReturn(true)
                   ->once();
        $mockClient->shouldReceive('getRefreshToken')
                   ->andReturn('expired_refresh_token')
                   ->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
                   ->with('expired_refresh_token')
                   ->andThrow(new \Exception('invalid_grant: Token has been expired or revoked'))
                   ->once();

        $this->replaceDriveServiceMocks($mockClient);
    }

    private function mockSuccessfulTokenRefreshButFailedApi(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')
                   ->with('expired_access_token')
                   ->once();
        $mockClient->shouldReceive('isAccessTokenExpired')
                   ->andReturn(true)
                   ->once();
        $mockClient->shouldReceive('getRefreshToken')
                   ->andReturn('valid_refresh_token')
                   ->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
                   ->with('valid_refresh_token')
                   ->andReturn([
                       'access_token' => 'refreshed_access_token',
                       'expires_in' => 3600,
                       'refresh_token' => 'valid_refresh_token'
                   ])
                   ->once();
        $mockClient->shouldReceive('getAccessToken')
                   ->andReturn('refreshed_access_token')
                   ->once();

        $mockDrive = Mockery::mock(Drive::class);
        $mockAbout = Mockery::mock();
        $mockAbout->shouldReceive('get')
                  ->with(['fields' => 'user'])
                  ->andThrow(new \Exception('API Error: Service unavailable'))
                  ->once();
        $mockDrive->about = $mockAbout;

        $this->replaceDriveServiceMocks($mockClient, $mockDrive);
    }

    private function replaceDriveServiceMocks(?GoogleClient $mockClient = null, ?Drive $mockDrive = null): void
    {
        if ($mockClient) {
            $reflection = new \ReflectionClass($this->driveService);
            $clientProperty = $reflection->getProperty('client');
            $clientProperty->setAccessible(true);
            $clientProperty->setValue($this->driveService, $mockClient);
        }

        if ($mockDrive) {
            $reflection = new \ReflectionClass($this->driveService);
            $driveProperty = $reflection->getProperty('drive');
            $driveProperty->setAccessible(true);
            $driveProperty->setValue($this->driveService, $mockDrive);
        }

        // Replace the health service's drive service instance
        $healthReflection = new \ReflectionClass($this->healthService);
        $driveServiceProperty = $healthReflection->getProperty('googleDriveService');
        $driveServiceProperty->setAccessible(true);
        $driveServiceProperty->setValue($this->healthService, $this->driveService);
    }
}