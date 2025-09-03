<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CloudStorageHealthStatus;
use App\Services\CloudStorageHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnhancedDashboardStatusWidgetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'role' => 'admin'
        ]);
    }

    /** @test */
    public function dashboard_widget_renders_with_enhanced_features()
    {
        // Create a health status record
        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'last_checked_at' => now(),
            'consecutive_failures' => 0,
            'is_healthy' => true,
            'is_degraded' => false,
            'is_unhealthy' => false,
            'is_disconnected' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        
        // Check that the enhanced widget features are present
        $response->assertSee('Cloud Storage Status');
        $response->assertSee('Connection Health:');
        $response->assertSee('cloudStorageStatusWidget');
        
        // Check for enhanced JavaScript functions
        $response->assertSee('initializeWidget');
        $response->assertSee('handleVisibilityChange');
        $response->assertSee('handleOnlineStatus');
        $response->assertSee('getActionableErrorMessage');
        $response->assertSee('formatTimestamp');
    }

    /** @test */
    public function widget_includes_enhanced_status_indicators()
    {
        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'last_checked_at' => now(),
            'consecutive_failures' => 0,
            'is_healthy' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        
        // Check for enhanced visual indicators
        $response->assertSee('animate-ping');
        $response->assertSee('transition-all duration-300');
        $response->assertSee('getStatusIndicatorClass');
        $response->assertSee('getConnectionQualityClass');
        $response->assertSee('isProviderProcessing');
    }

    /** @test */
    public function widget_includes_enhanced_error_handling()
    {
        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'last_checked_at' => now(),
            'consecutive_failures' => 2,
            'last_error_message' => 'Token expired',
            'last_error_type' => 'authentication_error',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        
        // Check for enhanced error handling features
        $response->assertSee('getActionableErrorMessage');
        $response->assertSee('getRecoveryInstructions');
        $response->assertSee('bg-red-50 border border-red-200');
        $response->assertSee('Recommended Action:');
    }

    /** @test */
    public function widget_includes_real_time_update_features()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        
        // Check for real-time update features
        $response->assertSee('x-on:visibility-change.window');
        $response->assertSee('x-on:focus.window');
        $response->assertSee('x-on:online.window');
        $response->assertSee('x-on:offline.window');
        $response->assertSee('startPeriodicRefresh');
        $response->assertSee('handleRefreshError');
        $response->assertSee('updateProvidersData');
    }

    /** @test */
    public function widget_includes_enhanced_notification_system()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        
        // Check for enhanced notification features
        $response->assertSee('showSuccess');
        $response->assertSee('showError');
        $response->assertSee('fixed top-4 right-4');
        $response->assertSee('bg-green-100 border border-green-400');
        $response->assertSee('bg-red-100 border border-red-400');
    }

    /** @test */
    public function admin_status_endpoint_returns_enhanced_data()
    {
        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'last_checked_at' => now(),
            'consecutive_failures' => 0,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/admin/cloud-storage/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'providers' => [
                    '*' => [
                        'provider',
                        'consolidated_status',
                        'last_checked_at',
                        'consecutive_failures',
                    ]
                ],
                'pending_uploads',
                'failed_uploads',
            ]);
    }
}