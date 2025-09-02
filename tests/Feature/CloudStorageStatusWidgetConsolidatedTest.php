<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CloudStorageHealthStatus;
use App\Services\CloudStorageHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloudStorageStatusWidgetConsolidatedTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    public function test_dashboard_widget_shows_consolidated_status_without_token_warnings_when_healthy(): void
    {
        // Create a healthy status with expired token (but working refresh)
        CloudStorageHealthStatus::create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'token_expires_at' => now()->subHour(), // Expired token
            'last_successful_operation_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // The response should contain the consolidated status message
        $response->assertSee('Connection is working properly');
        
        // Should NOT contain token warning messages
        $response->assertDontSee('Token will refresh soon');
        $response->assertDontSee('Token refresh needed');
    }

    public function test_dashboard_widget_shows_authentication_required_when_token_refresh_fails(): void
    {
        // Create an authentication required status
        CloudStorageHealthStatus::create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'consecutive_failures' => 3,
            'requires_reconnection' => true,
            'token_expires_at' => now()->subHour(), // Expired token
            'last_error_message' => 'Authentication required - please reconnect your account',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // The response should contain the consolidated status message
        $response->assertSee('Please reconnect your account');
    }

    public function test_cloud_storage_status_endpoint_returns_consolidated_status(): void
    {
        // Create a healthy status
        CloudStorageHealthStatus::create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'token_expires_at' => now()->addHours(2), // Expiring soon but healthy
            'last_successful_operation_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.cloud-storage.status'));

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertArrayHasKey('providers', $data);
        
        $googleDriveProvider = collect($data['providers'])->firstWhere('provider', 'google-drive');
        $this->assertNotNull($googleDriveProvider);
        
        // Verify consolidated status is returned
        $this->assertEquals('healthy', $googleDriveProvider['consolidated_status']);
        $this->assertEquals('Connection is working properly', $googleDriveProvider['status_message']);
        $this->assertTrue($googleDriveProvider['is_healthy']);
        
        // Verify token warnings are suppressed when healthy
        $this->assertFalse($googleDriveProvider['token_expiring_soon']);
        $this->assertFalse($googleDriveProvider['token_expired']);
    }

    public function test_cloud_storage_status_endpoint_shows_token_warnings_when_not_healthy(): void
    {
        // Create an authentication required status with expired token
        CloudStorageHealthStatus::create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'consecutive_failures' => 3,
            'requires_reconnection' => true,
            'token_expires_at' => now()->subHour(), // Expired token
            'last_error_message' => 'Authentication required - please reconnect your account',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.cloud-storage.status'));

        $response->assertStatus(200);
        
        $data = $response->json();
        $googleDriveProvider = collect($data['providers'])->firstWhere('provider', 'google-drive');
        $this->assertNotNull($googleDriveProvider);
        
        // Verify consolidated status is returned
        $this->assertEquals('authentication_required', $googleDriveProvider['consolidated_status']);
        $this->assertEquals('Please reconnect your account', $googleDriveProvider['status_message']);
        $this->assertFalse($googleDriveProvider['is_healthy']);
        
        // Verify token warnings are shown when not healthy
        $this->assertTrue($googleDriveProvider['token_expired']);
        $this->assertTrue($googleDriveProvider['requires_reconnection']);
    }
}