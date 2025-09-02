<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CloudStorageHealthStatus;
use App\Services\CloudStorageHealthService;
use App\Services\GoogleDriveService;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CloudStorageStatusIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CloudStorageHealthService $healthService;
    private GoogleDriveService $googleDriveService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->healthService = app(CloudStorageHealthService::class);
        $this->googleDriveService = app(GoogleDriveService::class);
    }

    /** @test */
    public function it_shows_healthy_status_after_successful_token_refresh()
    {
        // Create a health status with expired token
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'last_error_message' => 'Token expired',
            'token_expires_at' => now()->subHour(),
        ]);

        // Mock the GoogleDriveService to return successful results
        $mockGoogleDriveService = Mockery::mock(GoogleDriveService::class);
        $mockGoogleDriveService->shouldReceive('validateAndRefreshToken')
            ->with($this->user)
            ->once()
            ->andReturn(true);
        $mockGoogleDriveService->shouldReceive('testApiConnectivity')
            ->with($this->user)
            ->once()
            ->andReturn(true);

        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        // Check connection health
        $result = $this->healthService->checkConnectionHealth($this->user, 'google-drive');

        // Verify healthy status
        $this->assertTrue($result);
        
        $summary = $this->healthService->getHealthSummary($this->user, 'google-drive');
        $this->assertEquals('healthy', $summary['status']);
        $this->assertEquals('Connected and working properly', $summary['status_message']);
        $this->assertTrue($summary['is_healthy']);
        $this->assertFalse($summary['requires_user_intervention']);
    }

    /** @test */
    public function it_shows_authentication_required_when_refresh_token_is_invalid()
    {
        // Create a health status
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'token_expires_at' => now()->subHour(),
        ]);

        // Mock the GoogleDriveService to return failed token validation
        $mockGoogleDriveService = Mockery::mock(GoogleDriveService::class);
        $mockGoogleDriveService->shouldReceive('validateAndRefreshToken')
            ->with($this->user)
            ->once()
            ->andReturn(false);

        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        // Check connection health
        $result = $this->healthService->checkConnectionHealth($this->user, 'google-drive');

        // Verify authentication required status
        $this->assertFalse($result);
        
        $summary = $this->healthService->getHealthSummary($this->user, 'google-drive');
        $this->assertEquals('authentication_required', $summary['status']);
        $this->assertEquals('Account not connected', $summary['status_message']);
        $this->assertFalse($summary['is_healthy']);
        $this->assertTrue($summary['requires_user_intervention']);
    }

    /** @test */
    public function it_shows_connection_issues_when_api_connectivity_fails()
    {
        // Create a health status
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'token_expires_at' => now()->addHour(),
        ]);

        // Mock successful token validation but failed API connectivity
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('getAccessToken')->andReturn([
            'access_token' => 'valid_token',
            'expires_in' => 3600,
        ]);

        $mockDrive = Mockery::mock(Drive::class);
        $mockDrive->about = Mockery::mock();
        $mockDrive->about->shouldReceive('get')
            ->with(['fields' => 'user'])
            ->once()
            ->andThrow(new \Exception('Network error'));

        $this->app->instance(GoogleClient::class, $mockClient);
        $this->app->instance(Drive::class, $mockDrive);

        // Check connection health
        $result = $this->healthService->checkConnectionHealth($this->user, 'google-drive');

        // Verify connection issues status
        $this->assertFalse($result);
        
        $summary = $this->healthService->getHealthSummary($this->user, 'google-drive');
        $this->assertEquals('connection_issues', $summary['status']);
        $this->assertEquals('Connection has some issues but is functional', $summary['status_message']);
        $this->assertFalse($summary['is_healthy']);
        $this->assertFalse($summary['requires_user_intervention']);
    }

    /** @test */
    public function it_eliminates_token_warnings_when_connection_is_operational()
    {
        // Create a health status with token expiring soon
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'token_expires_at' => now()->addMinutes(30), // Expiring soon
        ]);

        // Mock successful token refresh and API connectivity
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('refreshToken')
            ->once()
            ->andReturn([
                'access_token' => 'new_access_token',
                'expires_in' => 3600,
            ]);
        $mockClient->shouldReceive('setAccessToken')->once();
        $mockClient->shouldReceive('getAccessToken')->andReturn([
            'access_token' => 'new_access_token',
            'expires_in' => 3600,
        ]);

        $mockDrive = Mockery::mock(Drive::class);
        $mockDrive->about = Mockery::mock();
        $mockDrive->about->shouldReceive('get')
            ->with(['fields' => 'user'])
            ->once()
            ->andReturn((object) ['user' => (object) ['displayName' => 'Test User']]);

        $this->app->instance(GoogleClient::class, $mockClient);
        $this->app->instance(Drive::class, $mockDrive);

        // Check connection health
        $this->healthService->checkConnectionHealth($this->user, 'google-drive');

        $summary = $this->healthService->getHealthSummary($this->user, 'google-drive');
        
        // Should show healthy status without token warnings
        $this->assertEquals('healthy', $summary['status']);
        $this->assertEquals('Connected and working properly', $summary['status_message']);
        $this->assertTrue($summary['is_healthy']);
        $this->assertFalse($summary['token_expiring_soon']);
        $this->assertFalse($summary['token_expired']);
    }

    /** @test */
    public function it_handles_network_errors_with_retry_logic()
    {
        // Create a health status
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'connection_issues',
            'token_expires_at' => now()->subHour(),
        ]);

        // Mock network error on first attempt, success on retry
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('refreshToken')
            ->twice()
            ->andReturnUsing(function () {
                static $callCount = 0;
                $callCount++;
                
                if ($callCount === 1) {
                    throw new \Exception('Connection refused');
                }
                
                return [
                    'access_token' => 'new_access_token',
                    'expires_in' => 3600,
                ];
            });
        $mockClient->shouldReceive('setAccessToken')->once();
        $mockClient->shouldReceive('getAccessToken')->andReturn([
            'access_token' => 'new_access_token',
            'expires_in' => 3600,
        ]);

        $mockDrive = Mockery::mock(Drive::class);
        $mockDrive->about = Mockery::mock();
        $mockDrive->about->shouldReceive('get')
            ->with(['fields' => 'user'])
            ->once()
            ->andReturn((object) ['user' => (object) ['displayName' => 'Test User']]);

        $this->app->instance(GoogleClient::class, $mockClient);
        $this->app->instance(Drive::class, $mockDrive);

        // Check connection health
        $result = $this->healthService->checkConnectionHealth($this->user, 'google-drive');

        // Should eventually succeed after retry
        $this->assertTrue($result);
        
        $summary = $this->healthService->getHealthSummary($this->user, 'google-drive');
        $this->assertEquals('healthy', $summary['status']);
    }
}