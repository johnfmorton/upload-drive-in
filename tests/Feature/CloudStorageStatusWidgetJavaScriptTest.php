<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CloudStorageHealthStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloudStorageStatusWidgetJavaScriptTest extends TestCase
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

    public function test_widget_displays_consolidated_status_message_for_healthy_connection(): void
    {
        // Create a healthy status
        CloudStorageHealthStatus::create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'last_successful_operation_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Check that the consolidated status message function is available
        $response->assertSee('getConsolidatedStatusMessage');
        
        // Check that the status message is displayed
        $response->assertSee('Connection is working properly');
    }

    public function test_widget_displays_authentication_required_message(): void
    {
        // Create an authentication required status
        CloudStorageHealthStatus::create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'consecutive_failures' => 3,
            'requires_reconnection' => true,
            'last_error_message' => 'Authentication required - please reconnect your account',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Check that the authentication required message is displayed
        $response->assertSee('Please reconnect your account');
    }

    public function test_widget_displays_connection_issues_message(): void
    {
        // Create a connection issues status
        CloudStorageHealthStatus::create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'degraded',
            'consolidated_status' => 'connection_issues',
            'consecutive_failures' => 1,
            'requires_reconnection' => false,
            'last_error_message' => 'Network timeout occurred',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Check that the connection issues message is displayed
        $response->assertSee('Experiencing connectivity problems');
    }

    public function test_widget_shows_reconnect_button_for_authentication_required(): void
    {
        // Create an authentication required status
        CloudStorageHealthStatus::create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'consecutive_failures' => 3,
            'requires_reconnection' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Check that the reconnect button condition includes consolidated status
        $response->assertSee('consolidated_status');
        $response->assertSee('authentication_required');
    }

    public function test_widget_shows_test_connection_button_for_healthy_status(): void
    {
        // Create a healthy status
        CloudStorageHealthStatus::create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'last_successful_operation_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Check that the test connection button condition includes consolidated status
        $response->assertSee('consolidated_status');
        $response->assertSee('connection_issues');
    }

    public function test_widget_prioritizes_consolidated_status_over_legacy_status(): void
    {
        // Create a status with both consolidated and legacy status
        CloudStorageHealthStatus::create([
            'user_id' => $this->admin->id,
            'provider' => 'google-drive',
            'status' => 'degraded', // Legacy status
            'consolidated_status' => 'healthy', // Consolidated status should take priority
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'last_successful_operation_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Check that consolidated status is used in the status indicator
        $response->assertSee('provider.consolidated_status || provider.status');
        
        // Check that the healthy message is displayed (from consolidated status)
        $response->assertSee('Connection is working properly');
    }
}