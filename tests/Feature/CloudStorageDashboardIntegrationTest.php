<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CloudStorageHealthStatus;
use App\Services\CloudStorageHealthService;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CloudStorageDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->admin()->create();
        $this->employeeUser = User::factory()->employee()->create();
    }

    /** @test */
    public function admin_dashboard_shows_consistent_status_with_backend()
    {
        // Create health status with successful token refresh capability
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->adminUser->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'token_expires_at' => now()->addHour(),
        ]);

        // Mock successful API connectivity
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
            ->andReturn((object) ['user' => (object) ['displayName' => 'Test User']]);

        $this->app->instance(GoogleClient::class, $mockClient);
        $this->app->instance(Drive::class, $mockDrive);

        // Test dashboard page
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Connected and working properly');
        $response->assertDontSee('Token refresh needed');
        $response->assertDontSee('Token will refresh soon');

        // Test status API endpoint
        $apiResponse = $this->actingAs($this->adminUser)
            ->get('/admin/cloud-storage/status');
        
        $apiResponse->assertStatus(200);
        $data = $apiResponse->json();
        
        $this->assertEquals('healthy', $data['google-drive']['status']);
        $this->assertEquals('Connected and working properly', $data['google-drive']['status_message']);
        $this->assertTrue($data['google-drive']['is_healthy']);
        $this->assertFalse($data['google-drive']['requires_user_intervention']);
    }

    /** @test */
    public function employee_dashboard_shows_consistent_status_with_backend()
    {
        // Create health status requiring authentication
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->employeeUser->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'last_error_message' => 'Token expired',
            'requires_reconnection' => true,
        ]);

        // Test dashboard page
        $response = $this->actingAs($this->employeeUser)->get('/employee/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Account not connected');
        $response->assertSee('Connect to Google Drive');

        // Test status API endpoint
        $apiResponse = $this->actingAs($this->employeeUser)
            ->get('/employee/cloud-storage/status');
        
        $apiResponse->assertStatus(200);
        $data = $apiResponse->json();
        
        $this->assertEquals('authentication_required', $data['google-drive']['status']);
        $this->assertEquals('Account not connected', $data['google-drive']['status_message']);
        $this->assertFalse($data['google-drive']['is_healthy']);
        $this->assertTrue($data['google-drive']['requires_user_intervention']);
    }

    /** @test */
    public function test_connection_button_results_are_consistent_with_displayed_status()
    {
        // Create health status
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->adminUser->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'token_expires_at' => now()->subHour(),
        ]);

        // Mock successful token refresh and API test
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

        // Test connection via API
        $testResponse = $this->actingAs($this->adminUser)
            ->post('/admin/cloud-storage/test-connection', [
                'provider' => 'google-drive'
            ]);

        $testResponse->assertStatus(200);
        $testData = $testResponse->json();
        
        $this->assertTrue($testData['success']);
        $this->assertEquals('Connection test successful', $testData['message']);

        // Verify status is updated consistently
        $statusResponse = $this->actingAs($this->adminUser)
            ->get('/admin/cloud-storage/status');
        
        $statusData = $statusResponse->json();
        $this->assertEquals('healthy', $statusData['google-drive']['status']);
        $this->assertEquals('Connected and working properly', $statusData['google-drive']['status_message']);
    }

    /** @test */
    public function dashboard_handles_connection_issues_gracefully()
    {
        // Create health status
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->adminUser->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'token_expires_at' => now()->addHour(),
        ]);

        // Mock API connectivity failure
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
            ->andThrow(new \Exception('Service temporarily unavailable'));

        $this->app->instance(GoogleClient::class, $mockClient);
        $this->app->instance(Drive::class, $mockDrive);

        // Test connection
        $testResponse = $this->actingAs($this->adminUser)
            ->post('/admin/cloud-storage/test-connection', [
                'provider' => 'google-drive'
            ]);

        $testResponse->assertStatus(200);
        $testData = $testResponse->json();
        
        $this->assertFalse($testData['success']);
        $this->assertStringContains('Connection test failed', $testData['message']);

        // Verify dashboard shows connection issues
        $statusResponse = $this->actingAs($this->adminUser)
            ->get('/admin/cloud-storage/status');
        
        $statusData = $statusResponse->json();
        $this->assertEquals('connection_issues', $statusData['google-drive']['status']);
        $this->assertEquals('Connection has some issues but is functional', $statusData['google-drive']['status_message']);
        $this->assertFalse($statusData['google-drive']['requires_user_intervention']);
    }

    /** @test */
    public function status_refresh_triggers_comprehensive_status_checks()
    {
        // Create health status with old data
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->adminUser->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'token_expires_at' => now()->subHour(),
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

        // Trigger status refresh
        $refreshResponse = $this->actingAs($this->adminUser)
            ->post('/admin/cloud-storage/refresh-status');

        $refreshResponse->assertStatus(200);
        $refreshData = $refreshResponse->json();
        
        $this->assertTrue($refreshData['success']);
        $this->assertEquals('healthy', $refreshData['statuses']['google-drive']['status']);
        $this->assertEquals('Connected and working properly', $refreshData['statuses']['google-drive']['status_message']);

        // Verify the health status was updated in database
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->adminUser->id)
            ->where('provider', 'google-drive')
            ->first();
        
        $this->assertEquals('healthy', $healthStatus->status);
        $this->assertEquals('healthy', $healthStatus->consolidated_status);
        $this->assertNotNull($healthStatus->updated_at);
        $this->assertTrue($healthStatus->updated_at->isAfter(now()->subMinute()));
    }

    /** @test */
    public function widget_javascript_updates_display_correctly()
    {
        // Create health status
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->adminUser->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
        ]);

        // Test that the dashboard includes the widget with correct data
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');
        
        $response->assertStatus(200);
        
        // Check that the widget component is included
        $response->assertSee('cloud-storage-status-widget');
        
        // Check that JavaScript variables are set correctly
        $response->assertSee('window.cloudStorageStatus');
        
        // Verify the status data structure in the JavaScript
        $content = $response->getContent();
        $this->assertStringContains('"status":"healthy"', $content);
        $this->assertStringContains('"status_message":"Connected and working properly"', $content);
        $this->assertStringContains('"is_healthy":true', $content);
    }
}