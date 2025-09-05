<?php

namespace Tests\Integration;

use App\Http\Controllers\Admin\CloudStorageController;
use App\Http\Controllers\CloudStorageDashboardController;
use App\Models\CloudStorageHealthStatus;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\RealTimeHealthValidator;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration tests for dashboard status accuracy during token refresh scenarios.
 * 
 * These tests verify that the dashboard displays accurate real-time status
 * information that reflects the actual connection state, especially during
 * token refresh operations.
 */
class TokenRefreshDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $employeeUser;
    private CloudStorageHealthService $healthService;
    private RealTimeHealthValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->employeeUser = User::factory()->create(['role' => 'employee']);
        $this->healthService = app(CloudStorageHealthService::class);
        $this->validator = app(RealTimeHealthValidator::class);

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function admin_dashboard_shows_accurate_status_after_token_refresh(): void
    {
        // Arrange - Create expired token that can be refreshed
        GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->mockSuccessfulTokenRefreshAndApi();

        // Act - Get dashboard status
        $controller = new CloudStorageDashboardController();
        $request = Request::create('/admin/dashboard/cloud-storage-status');
        $request->setUserResolver(fn() => $this->adminUser);
        
        $response = $controller->getStatus($request);
        $data = $response->getData(true);

        // Assert - Should show healthy status after automatic refresh
        $this->assertEquals('healthy', $data['consolidated_status']);
        $this->assertEquals('Connection is working properly', $data['status_message']);
        $this->assertTrue($data['is_connected']);
        $this->assertNotNull($data['last_success_at']);
        $this->assertEquals(0, $data['token_refresh_failures']);

        // Verify token was actually refreshed
        $token = GoogleDriveToken::where('user_id', $this->adminUser->id)->first();
        $this->assertEquals('new_access_token', $token->access_token);
        $this->assertNotNull($token->last_successful_refresh_at);
    }

    #[Test]
    public function test_connection_button_performs_same_validation_as_dashboard(): void
    {
        // Arrange - Create token requiring refresh
        GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->mockSuccessfulTokenRefreshAndApi();

        // Act - Test connection via button
        $controller = new CloudStorageController();
        $request = Request::create('/admin/cloud-storage/google-drive/test-connection', 'POST');
        $request->setUserResolver(fn() => $this->adminUser);
        
        $response = $controller->testConnection($request, 'google-drive');
        $testData = $response->getData(true);

        // Get dashboard status
        $dashboardController = new CloudStorageDashboardController();
        $dashboardRequest = Request::create('/admin/dashboard/cloud-storage-status');
        $dashboardRequest->setUserResolver(fn() => $this->adminUser);
        
        $dashboardResponse = $dashboardController->getStatus($dashboardRequest);
        $dashboardData = $dashboardResponse->getData(true);

        // Assert - Both should show identical status
        $this->assertEquals($testData['success'], $dashboardData['is_connected']);
        $this->assertEquals($testData['status'], $dashboardData['consolidated_status']);
        $this->assertStringContainsString('successful', strtolower($testData['message']));
        $this->assertStringContainsString('working', strtolower($dashboardData['status_message']));
    }

    #[Test]
    public function dashboard_shows_authentication_required_for_expired_refresh_token(): void
    {
        // Arrange - Create token with expired refresh token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'expired_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->mockFailedTokenRefresh();

        // Act - Get dashboard status
        $controller = new CloudStorageDashboardController();
        $request = Request::create('/admin/dashboard/cloud-storage-status');
        $request->setUserResolver(fn() => $this->adminUser);
        
        $response = $controller->getStatus($request);
        $data = $response->getData(true);

        // Assert - Should show authentication required
        $this->assertEquals('authentication_required', $data['consolidated_status']);
        $this->assertStringContainsString('reconnect', strtolower($data['status_message']));
        $this->assertFalse($data['is_connected']);
        $this->assertGreaterThan(0, $data['token_refresh_failures']);
        $this->assertFalse($data['token_refresh_working']);
    }

    #[Test]
    public function dashboard_shows_connection_issues_for_network_problems(): void
    {
        // Arrange - Create valid token but simulate network issues
        GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->addHours(1),
        ]);

        $this->mockNetworkErrorDuringApiTest();

        // Act - Get dashboard status
        $controller = new CloudStorageDashboardController();
        $request = Request::create('/admin/dashboard/cloud-storage-status');
        $request->setUserResolver(fn() => $this->adminUser);
        
        $response = $controller->getStatus($request);
        $data = $response->getData(true);

        // Assert - Should show connection issues
        $this->assertEquals('connection_issues', $data['consolidated_status']);
        $this->assertStringContainsString('connectivity', strtolower($data['status_message']));
        $this->assertFalse($data['is_connected']);
        $this->assertTrue($data['token_refresh_working']); // Token refresh works, API doesn't
    }

    #[Test]
    public function employee_dashboard_shows_accurate_status_independently(): void
    {
        // Arrange - Admin has healthy connection, employee has expired token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'access_token' => 'admin_valid_token',
            'refresh_token' => 'admin_refresh_token',
            'expires_at' => Carbon::now()->addHours(1),
        ]);

        GoogleDriveToken::factory()->create([
            'user_id' => $this->employeeUser->id,
            'access_token' => 'employee_expired_token',
            'refresh_token' => 'employee_expired_refresh',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        // Mock different responses for different users
        $this->mockUserSpecificResponses();

        // Act - Get status for both users
        $controller = new CloudStorageDashboardController();
        
        $adminRequest = Request::create('/admin/dashboard/cloud-storage-status');
        $adminRequest->setUserResolver(fn() => $this->adminUser);
        $adminResponse = $controller->getStatus($adminRequest);
        $adminData = $adminResponse->getData(true);

        $employeeRequest = Request::create('/employee/dashboard/cloud-storage-status');
        $employeeRequest->setUserResolver(fn() => $this->employeeUser);
        $employeeResponse = $controller->getStatus($employeeRequest);
        $employeeData = $employeeResponse->getData(true);

        // Assert - Each user should see their own status
        $this->assertEquals('healthy', $adminData['consolidated_status']);
        $this->assertTrue($adminData['is_connected']);

        $this->assertEquals('authentication_required', $employeeData['consolidated_status']);
        $this->assertFalse($employeeData['is_connected']);
    }

    #[Test]
    public function dashboard_status_updates_in_real_time_during_token_operations(): void
    {
        // Arrange - Create expired token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        // Initial status should show issues
        $this->mockFailedApiConnectivity();
        
        $controller = new CloudStorageDashboardController();
        $request = Request::create('/admin/dashboard/cloud-storage-status');
        $request->setUserResolver(fn() => $this->adminUser);
        
        $response1 = $controller->getStatus($request);
        $data1 = $response1->getData(true);
        
        $this->assertFalse($data1['is_connected']);

        // Simulate token refresh happening
        $this->mockSuccessfulTokenRefreshAndApi();
        
        // Trigger refresh through health service
        $this->healthService->checkConnectionHealth($this->adminUser, 'google-drive');

        // Status should now show healthy
        $response2 = $controller->getStatus($request);
        $data2 = $response2->getData(true);
        
        $this->assertTrue($data2['is_connected']);
        $this->assertEquals('healthy', $data2['consolidated_status']);
        
        // Verify the status actually changed
        $this->assertNotEquals($data1['consolidated_status'], $data2['consolidated_status']);
    }

    #[Test]
    public function dashboard_shows_detailed_token_information(): void
    {
        // Arrange - Create token with specific timestamps
        $issuedAt = Carbon::now()->subDays(2);
        $expiresAt = Carbon::now()->addMinutes(45);
        $lastRefreshAt = Carbon::now()->subHours(1);

        GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => $expiresAt,
            'created_at' => $issuedAt,
            'last_successful_refresh_at' => $lastRefreshAt,
            'proactive_refresh_scheduled_at' => $expiresAt->copy()->subMinutes(15),
        ]);

        $this->mockSuccessfulApiConnectivity();

        // Act - Get dashboard status
        $controller = new CloudStorageDashboardController();
        $request = Request::create('/admin/dashboard/cloud-storage-status');
        $request->setUserResolver(fn() => $this->adminUser);
        
        $response = $controller->getStatus($request);
        $data = $response->getData(true);

        // Assert - Should include detailed token information
        $this->assertArrayHasKey('token_info', $data);
        $tokenInfo = $data['token_info'];
        
        $this->assertArrayHasKey('issued_at', $tokenInfo);
        $this->assertArrayHasKey('expires_at', $tokenInfo);
        $this->assertArrayHasKey('last_refresh_at', $tokenInfo);
        $this->assertArrayHasKey('next_refresh_scheduled_at', $tokenInfo);
        $this->assertArrayHasKey('time_until_expiration', $tokenInfo);
        
        $this->assertEquals($issuedAt->toISOString(), $tokenInfo['issued_at']);
        $this->assertEquals($expiresAt->toISOString(), $tokenInfo['expires_at']);
        $this->assertEquals($lastRefreshAt->toISOString(), $tokenInfo['last_refresh_at']);
    }

    #[Test]
    public function dashboard_handles_missing_token_gracefully(): void
    {
        // Arrange - User with no Google Drive token
        // (No token created)

        // Act - Get dashboard status
        $controller = new CloudStorageDashboardController();
        $request = Request::create('/admin/dashboard/cloud-storage-status');
        $request->setUserResolver(fn() => $this->adminUser);
        
        $response = $controller->getStatus($request);
        $data = $response->getData(true);

        // Assert - Should show not connected status
        $this->assertEquals('not_connected', $data['consolidated_status']);
        $this->assertFalse($data['is_connected']);
        $this->assertStringContainsString('not connected', strtolower($data['status_message']));
        $this->assertNull($data['token_info']);
    }

    #[Test]
    public function dashboard_caches_status_appropriately(): void
    {
        // Arrange - Create valid token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->adminUser->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->addHours(1),
        ]);

        $this->mockSuccessfulApiConnectivity();

        // Act - Make multiple requests
        $controller = new CloudStorageDashboardController();
        $request = Request::create('/admin/dashboard/cloud-storage-status');
        $request->setUserResolver(fn() => $this->adminUser);
        
        $response1 = $controller->getStatus($request);
        $data1 = $response1->getData(true);
        
        $response2 = $controller->getStatus($request);
        $data2 = $response2->getData(true);

        // Assert - Both should return same data (from cache)
        $this->assertEquals($data1['consolidated_status'], $data2['consolidated_status']);
        $this->assertEquals($data1['last_validation_at'], $data2['last_validation_at']);
        
        // Verify cache key exists
        $cacheKey = "health_status_{$this->adminUser->id}_google-drive";
        $this->assertTrue(Cache::has($cacheKey));
    }

    // Mock helper methods

    private function mockSuccessfulTokenRefreshAndApi(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->atLeast()->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(true, false)->atLeast()->once();
        $mockClient->shouldReceive('getRefreshToken')->andReturn('valid_refresh_token')->atLeast()->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->andReturn([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 3600,
            ])
            ->atLeast()->once();
        $mockClient->shouldReceive('getAccessToken')->andReturn('new_access_token')->atLeast()->once();

        $mockDrive = Mockery::mock(Drive::class);
        $mockAbout = Mockery::mock();
        $mockAbout->shouldReceive('get')
            ->with(['fields' => 'user'])
            ->andReturn((object)['user' => (object)['displayName' => 'Test User']])
            ->atLeast()->once();
        $mockDrive->about = $mockAbout;

        $this->replaceMocksInServices($mockClient, $mockDrive);
    }

    private function mockFailedTokenRefresh(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->atLeast()->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(true)->atLeast()->once();
        $mockClient->shouldReceive('getRefreshToken')->andReturn('expired_refresh_token')->atLeast()->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->andThrow(new \Exception('invalid_grant: Token has been expired or revoked'))
            ->atLeast()->once();

        $this->replaceMocksInServices($mockClient);
    }

    private function mockNetworkErrorDuringApiTest(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->atLeast()->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(false)->atLeast()->once();
        $mockClient->shouldReceive('getAccessToken')->andReturn('valid_access_token')->atLeast()->once();

        $mockDrive = Mockery::mock(Drive::class);
        $mockAbout = Mockery::mock();
        $mockAbout->shouldReceive('get')
            ->andThrow(new \Exception('Network error: Connection timeout'))
            ->atLeast()->once();
        $mockDrive->about = $mockAbout;

        $this->replaceMocksInServices($mockClient, $mockDrive);
    }

    private function mockSuccessfulApiConnectivity(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->atLeast()->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(false)->atLeast()->once();
        $mockClient->shouldReceive('getAccessToken')->andReturn('valid_access_token')->atLeast()->once();

        $mockDrive = Mockery::mock(Drive::class);
        $mockAbout = Mockery::mock();
        $mockAbout->shouldReceive('get')
            ->with(['fields' => 'user'])
            ->andReturn((object)['user' => (object)['displayName' => 'Test User']])
            ->atLeast()->once();
        $mockDrive->about = $mockAbout;

        $this->replaceMocksInServices($mockClient, $mockDrive);
    }

    private function mockFailedApiConnectivity(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->atLeast()->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(true)->atLeast()->once();
        $mockClient->shouldReceive('getRefreshToken')->andReturn('expired_refresh_token')->atLeast()->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->andThrow(new \Exception('invalid_grant'))
            ->atLeast()->once();

        $this->replaceMocksInServices($mockClient);
    }

    private function mockUserSpecificResponses(): void
    {
        // This is a simplified mock - in reality, you'd need more sophisticated mocking
        // to handle different responses for different users
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->atLeast()->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(false, true)->atLeast()->once();
        $mockClient->shouldReceive('getAccessToken')->andReturn('valid_access_token')->atLeast()->once();
        $mockClient->shouldReceive('getRefreshToken')->andReturn('expired_refresh_token')->atLeast()->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->andThrow(new \Exception('invalid_grant'))
            ->atLeast()->once();

        $mockDrive = Mockery::mock(Drive::class);
        $mockAbout = Mockery::mock();
        $mockAbout->shouldReceive('get')
            ->andReturn((object)['user' => (object)['displayName' => 'Test User']])
            ->atLeast()->once();
        $mockDrive->about = $mockAbout;

        $this->replaceMocksInServices($mockClient, $mockDrive);
    }

    private function replaceMocksInServices(?GoogleClient $mockClient = null, ?Drive $mockDrive = null): void
    {
        // Replace mocks in GoogleDriveService
        $driveService = app(\App\Services\GoogleDriveService::class);
        $reflection = new \ReflectionClass($driveService);
        
        if ($mockClient) {
            $clientProperty = $reflection->getProperty('client');
            $clientProperty->setAccessible(true);
            $clientProperty->setValue($driveService, $mockClient);
        }

        if ($mockDrive) {
            $driveProperty = $reflection->getProperty('drive');
            $driveProperty->setAccessible(true);
            $driveProperty->setValue($driveService, $mockDrive);
        }

        // Replace in health service
        $healthReflection = new \ReflectionClass($this->healthService);
        $driveServiceProperty = $healthReflection->getProperty('googleDriveService');
        $driveServiceProperty->setAccessible(true);
        $driveServiceProperty->setValue($this->healthService, $driveService);

        // Replace in validator
        $validatorReflection = new \ReflectionClass($this->validator);
        $validatorDriveServiceProperty = $validatorReflection->getProperty('googleDriveService');
        $validatorDriveServiceProperty->setAccessible(true);
        $validatorDriveServiceProperty->setValue($this->validator, $driveService);
    }
}