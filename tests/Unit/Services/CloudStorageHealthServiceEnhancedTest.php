<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\CloudStorageHealthStatus;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageLogService;
use App\Services\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CloudStorageHealthServiceEnhancedTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageHealthService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $logService = new CloudStorageLogService();
        $this->service = new CloudStorageHealthService($logService);
        $this->user = User::factory()->create();
        
        // Mock Log facade to prevent actual logging during tests
        Log::shouldReceive('debug')->andReturn(null);
        Log::shouldReceive('error')->andReturn(null);
        Log::shouldReceive('info')->andReturn(null);
        Log::shouldReceive('warning')->andReturn(null);
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('log')->andReturn(null);
    }

    public function test_ensure_valid_token_calls_google_drive_service(): void
    {
        // Mock GoogleDriveService
        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->expects($this->once())
            ->method('validateAndRefreshToken')
            ->with($this->user)
            ->willReturn(true);

        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        $result = $this->service->ensureValidToken($this->user, 'google-drive');

        $this->assertTrue($result);
        
        // Check that health status was created and updated
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
            
        $this->assertNotNull($healthStatus);
        $this->assertNotNull($healthStatus->last_token_refresh_attempt_at);
        $this->assertEquals(0, $healthStatus->token_refresh_failures);
    }

    public function test_ensure_valid_token_handles_failure(): void
    {
        // Mock GoogleDriveService to return false
        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->expects($this->once())
            ->method('validateAndRefreshToken')
            ->with($this->user)
            ->willReturn(false);

        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        $result = $this->service->ensureValidToken($this->user, 'google-drive');

        $this->assertFalse($result);
        
        // Check that failure count was incremented
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
            
        $this->assertNotNull($healthStatus);
        $this->assertEquals(1, $healthStatus->token_refresh_failures);
    }

    public function test_test_api_connectivity_calls_google_drive_service(): void
    {
        // Mock GoogleDriveService
        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->expects($this->once())
            ->method('testApiConnectivity')
            ->with($this->user)
            ->willReturn(true);

        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        $result = $this->service->testApiConnectivity($this->user, 'google-drive');

        $this->assertTrue($result);
        
        // Check that operational test result was stored
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
            
        $this->assertNotNull($healthStatus);
        $this->assertNotNull($healthStatus->operational_test_result);
        $this->assertTrue($healthStatus->operational_test_result['success']);
    }

    public function test_determine_consolidated_status_returns_healthy_when_both_token_and_api_work(): void
    {
        // Mock GoogleDriveService
        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->expects($this->once())
            ->method('validateAndRefreshToken')
            ->with($this->user)
            ->willReturn(true);
        $mockGoogleDriveService->expects($this->once())
            ->method('testApiConnectivity')
            ->with($this->user)
            ->willReturn(true);

        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        $result = $this->service->determineConsolidatedStatus($this->user, 'google-drive');

        $this->assertEquals('healthy', $result);
    }

    public function test_determine_consolidated_status_returns_authentication_required_when_token_fails(): void
    {
        // Mock GoogleDriveService
        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->expects($this->once())
            ->method('validateAndRefreshToken')
            ->with($this->user)
            ->willReturn(false);
        // API connectivity should not be called if token validation fails
        $mockGoogleDriveService->expects($this->never())
            ->method('testApiConnectivity');

        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        $result = $this->service->determineConsolidatedStatus($this->user, 'google-drive');

        $this->assertEquals('authentication_required', $result);
    }

    public function test_determine_consolidated_status_returns_connection_issues_when_api_fails(): void
    {
        // Mock GoogleDriveService
        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        $mockGoogleDriveService->expects($this->once())
            ->method('validateAndRefreshToken')
            ->with($this->user)
            ->willReturn(true);
        $mockGoogleDriveService->expects($this->once())
            ->method('testApiConnectivity')
            ->with($this->user)
            ->willReturn(false);

        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        $result = $this->service->determineConsolidatedStatus($this->user, 'google-drive');

        $this->assertEquals('connection_issues', $result);
    }

    public function test_check_connection_health_uses_enhanced_logic(): void
    {
        // Mock GoogleDriveService with more specific expectations
        $mockGoogleDriveService = $this->createMock(GoogleDriveService::class);
        
        // The validateAndRefreshToken method will be called once during determineConsolidatedStatus
        $mockGoogleDriveService->expects($this->once())
            ->method('validateAndRefreshToken')
            ->with($this->user)
            ->willReturn(true);
            
        // The testApiConnectivity method will be called once during determineConsolidatedStatus
        $mockGoogleDriveService->expects($this->once())
            ->method('testApiConnectivity')
            ->with($this->user)
            ->willReturn(true);

        $this->app->instance(GoogleDriveService::class, $mockGoogleDriveService);

        $healthStatus = $this->service->checkConnectionHealth($this->user, 'google-drive');

        // The consolidated status should be set
        $this->assertNotNull($healthStatus->consolidated_status);
        
        // If both token validation and API connectivity work, it should be healthy
        $this->assertEquals('healthy', $healthStatus->consolidated_status, 
            "Expected 'healthy' but got '{$healthStatus->consolidated_status}'. " .
            "Last error: " . ($healthStatus->last_error_message ?? 'none')
        );
        $this->assertEquals('healthy', $healthStatus->status);
    }

    public function test_get_health_summary_uses_consolidated_status(): void
    {
        // Create a health status with consolidated status
        $healthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
        ]);

        $summary = $this->service->getHealthSummary($this->user, 'google-drive');

        $this->assertEquals('google-drive', $summary['provider']);
        $this->assertEquals('healthy', $summary['consolidated_status']);
        $this->assertEquals('Connection is working properly', $summary['status_message']);
        $this->assertTrue($summary['is_healthy']);
        $this->assertFalse($summary['token_expiring_soon']); // Should be false when healthy
        $this->assertFalse($summary['token_expired']); // Should be false when healthy
    }

    public function test_get_health_summary_shows_token_warnings_when_not_healthy(): void
    {
        // Create a health status with authentication required
        $healthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'consecutive_failures' => 3,
            'requires_reconnection' => true,
            'token_expires_at' => now()->subHour(), // Expired token
        ]);

        $summary = $this->service->getHealthSummary($this->user, 'google-drive');

        $this->assertEquals('authentication_required', $summary['consolidated_status']);
        $this->assertEquals('Please reconnect your account', $summary['status_message']);
        $this->assertFalse($summary['is_healthy']);
        $this->assertTrue($summary['token_expired']); // Should show token warnings when not healthy
    }
}