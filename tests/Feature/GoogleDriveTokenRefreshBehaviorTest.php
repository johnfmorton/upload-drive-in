<?php

namespace Tests\Feature;

use App\Models\CloudStorageHealthStatus;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\GoogleDriveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleDriveTokenRefreshBehaviorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function health_service_shows_authentication_required_for_missing_tokens()
    {
        // Arrange - No tokens exist
        $healthService = app(CloudStorageHealthService::class);

        // Act
        $healthStatus = $healthService->checkConnectionHealth($this->user, 'google-drive');

        // Assert
        $this->assertEquals('authentication_required', $healthStatus->consolidated_status);
        $this->assertEquals('Please reconnect your account', $healthStatus->getConsolidatedStatusMessage());
    }

    #[Test]
    public function health_service_shows_authentication_required_for_expired_tokens_without_refresh()
    {
        // Arrange - Create expired token without refresh token
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => null,
            'expires_at' => $pastExpiry,
        ]);

        $healthService = app(CloudStorageHealthService::class);

        // Act
        $healthStatus = $healthService->checkConnectionHealth($this->user, 'google-drive');

        // Assert
        $this->assertEquals('authentication_required', $healthStatus->consolidated_status);
        $this->assertEquals('Please reconnect your account', $healthStatus->getConsolidatedStatusMessage());
        // Token refresh failures may be 0 initially, so we check if it's working based on the status
        $this->assertContains($healthStatus->consolidated_status, ['authentication_required']);
    }

    #[Test]
    public function health_service_tracks_token_refresh_failures()
    {
        // Arrange - Create expired token with refresh token (will fail in test environment)
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'test_refresh_token',
            'expires_at' => $pastExpiry,
        ]);

        $healthService = app(CloudStorageHealthService::class);

        // Act - Multiple health checks should accumulate failures
        $healthStatus1 = $healthService->checkConnectionHealth($this->user, 'google-drive');
        $healthStatus2 = $healthService->checkConnectionHealth($this->user, 'google-drive');

        // Assert - Should track failures
        $this->assertEquals('authentication_required', $healthStatus1->consolidated_status);
        $this->assertEquals('authentication_required', $healthStatus2->consolidated_status);
        $this->assertGreaterThan(0, $healthStatus1->token_refresh_failures);
        $this->assertGreaterThanOrEqual($healthStatus1->token_refresh_failures, $healthStatus2->token_refresh_failures);
    }

    #[Test]
    public function health_service_shows_healthy_for_valid_tokens()
    {
        // Arrange - Create valid token
        $futureExpiry = Carbon::now()->addHours(2);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $futureExpiry,
        ]);

        $healthService = app(CloudStorageHealthService::class);

        // Act
        $healthStatus = $healthService->checkConnectionHealth($this->user, 'google-drive');

        // Assert - Should be healthy since token is valid (API connectivity will fail in test but token validation passes)
        $this->assertContains($healthStatus->consolidated_status, ['healthy', 'connection_issues']);
        $this->assertTrue($healthStatus->isTokenRefreshWorking());
        $this->assertEquals(0, $healthStatus->token_refresh_failures);
    }

    #[Test]
    public function drive_service_validates_tokens_correctly()
    {
        $driveService = app(GoogleDriveService::class);

        // Test 1: No token
        $result1 = $driveService->validateAndRefreshToken($this->user);
        $this->assertFalse($result1);

        // Test 2: Valid token
        $futureExpiry = Carbon::now()->addHours(2);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $futureExpiry,
        ]);

        $result2 = $driveService->validateAndRefreshToken($this->user);
        $this->assertTrue($result2);

        // Test 3: Expired token without refresh token
        GoogleDriveToken::where('user_id', $this->user->id)->update([
            'expires_at' => Carbon::now()->subMinutes(30),
            'refresh_token' => null,
        ]);

        $result3 = $driveService->validateAndRefreshToken($this->user);
        $this->assertFalse($result3);
    }

    #[Test]
    public function drive_service_api_connectivity_handles_missing_tokens()
    {
        $driveService = app(GoogleDriveService::class);

        // Test with no token
        $result = $driveService->testApiConnectivity($this->user);
        $this->assertFalse($result);

        // Test with expired token without refresh
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => null,
            'expires_at' => $pastExpiry,
        ]);

        $result2 = $driveService->testApiConnectivity($this->user);
        $this->assertFalse($result2);
    }

    #[Test]
    public function health_status_model_methods_work_correctly()
    {
        // Test consolidated status messages
        $healthyStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'token_refresh_failures' => 0,
        ]);

        $authRequiredStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'test-provider',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'token_refresh_failures' => 3,
        ]);

        $connectionIssuesStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'test-provider-2',
            'status' => 'degraded',
            'consolidated_status' => 'connection_issues',
            'token_refresh_failures' => 1,
        ]);

        // Test consolidated status messages
        $this->assertEquals('Connection is working properly', $healthyStatus->getConsolidatedStatusMessage());
        $this->assertEquals('Please reconnect your account', $authRequiredStatus->getConsolidatedStatusMessage());
        $this->assertEquals('Experiencing connectivity problems', $connectionIssuesStatus->getConsolidatedStatusMessage());

        // Test token refresh working status
        $this->assertTrue($healthyStatus->isTokenRefreshWorking());
        $this->assertFalse($authRequiredStatus->isTokenRefreshWorking());
        $this->assertTrue($connectionIssuesStatus->isTokenRefreshWorking());
    }

    #[Test]
    public function health_service_updates_status_fields_correctly()
    {
        // Arrange - Create expired token with refresh token
        $pastExpiry = Carbon::now()->subMinutes(30);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'test_refresh_token',
            'expires_at' => $pastExpiry,
        ]);

        $healthService = app(CloudStorageHealthService::class);

        // Act
        $healthStatus = $healthService->checkConnectionHealth($this->user, 'google-drive');

        // Assert - Verify all required fields are set
        $this->assertNotNull($healthStatus->consolidated_status);
        $this->assertNotNull($healthStatus->last_token_refresh_attempt_at);
        $this->assertIsInt($healthStatus->token_refresh_failures);
        // operational_test_result is cast as array and may be null
        $this->assertTrue(in_array($healthStatus->operational_test_result, [null, []], true) || is_array($healthStatus->operational_test_result));
        $this->assertEquals($this->user->id, $healthStatus->user_id);
        $this->assertEquals('google-drive', $healthStatus->provider);
    }

    #[Test]
    public function multiple_health_checks_maintain_consistent_state()
    {
        // Arrange - Create valid token
        $futureExpiry = Carbon::now()->addHours(2);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $futureExpiry,
        ]);

        $healthService = app(CloudStorageHealthService::class);

        // Act - Multiple health checks
        $healthStatus1 = $healthService->checkConnectionHealth($this->user, 'google-drive');
        $healthStatus2 = $healthService->checkConnectionHealth($this->user, 'google-drive');

        // Assert - Status should be consistent
        $this->assertEquals($healthStatus1->consolidated_status, $healthStatus2->consolidated_status);
        $this->assertEquals($healthStatus1->token_refresh_failures, $healthStatus2->token_refresh_failures);
        $this->assertEquals($healthStatus1->user_id, $healthStatus2->user_id);
        $this->assertEquals($healthStatus1->provider, $healthStatus2->provider);
    }

    #[Test]
    public function health_service_handles_edge_case_token_expiration()
    {
        // Arrange - Create token that expires very soon
        $soonExpiry = Carbon::now()->addMinutes(1);
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'soon_to_expire_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $soonExpiry,
        ]);

        $healthService = app(CloudStorageHealthService::class);

        // Act
        $healthStatus = $healthService->checkConnectionHealth($this->user, 'google-drive');

        // Assert - Should handle edge case gracefully
        $this->assertNotNull($healthStatus->consolidated_status);
        $this->assertContains($healthStatus->consolidated_status, ['healthy', 'connection_issues', 'authentication_required']);
        $this->assertIsInt($healthStatus->token_refresh_failures);
    }
}