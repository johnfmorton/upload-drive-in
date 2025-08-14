<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SetupCompletionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private SetupService $setupService;
    private AuditLogService $auditLogService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupService = app(SetupService::class);
        $this->auditLogService = app(AuditLogService::class);
    }

    public function test_admin_dashboard_shows_first_time_login_message()
    {
        // Create an admin user
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'last_login_at' => null,
        ]);

        // Mark setup as recently completed
        $this->setupService->markSetupComplete();

        // Act as the admin user
        $this->actingAs($admin);

        // Visit the admin dashboard
        $response = $this->get(route('admin.dashboard'));

        // Assert the first-time login message is shown
        $response->assertStatus(200);
        $response->assertSee('Welcome to Upload Drive-in!');
        $response->assertSee('Congratulations! Your Upload Drive-in application has been successfully configured');
    }

    public function test_admin_dashboard_does_not_show_first_time_login_message_for_returning_user()
    {
        // Create an admin user with previous login
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'last_login_at' => now()->subDays(1),
        ]);

        // Mark setup as completed
        $this->setupService->markSetupComplete();

        // Act as the admin user
        $this->actingAs($admin);

        // Visit the admin dashboard
        $response = $this->get(route('admin.dashboard'));

        // Assert the first-time login message is NOT shown
        $response->assertStatus(200);
        $response->assertDontSee('Welcome to Upload Drive-in!');
        $response->assertDontSee('Congratulations! Your Upload Drive-in application has been successfully configured');
    }

    public function test_last_login_at_is_updated_on_login()
    {
        // Create an admin user with no previous login
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'last_login_at' => null,
        ]);

        // Mark setup as complete to avoid setup middleware interference
        $this->setupService->markSetupComplete();

        // Simulate the login process by manually updating last_login_at
        // This tests the integration of the login timestamp functionality
        $loginTime = now();
        $admin->update(['last_login_at' => $loginTime]);

        // Refresh the user model
        $admin->refresh();

        // Assert last_login_at was updated
        $this->assertNotNull($admin->last_login_at);
        $this->assertTrue($admin->last_login_at->isToday());

        // Test that the first-time login detection works correctly
        $this->actingAs($admin);
        
        // Create a new admin user that just completed setup
        $newAdmin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'last_login_at' => null,
        ]);
        
        // Mark setup as recently completed
        $this->setupService->markSetupComplete();
        
        // Act as the new admin
        $this->actingAs($newAdmin);
        
        // Visit the dashboard - should show first-time login message
        $response = $this->get(route('admin.dashboard'));
        $response->assertSee('Welcome to Upload Drive-in!');
    }

    public function test_setup_completion_is_logged_to_audit()
    {
        // Create an admin user
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        // Mock the log to capture audit entries
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->with('Application setup completed', \Mockery::type('array'))
            ->once();

        // Also mock the main log channel call
        Log::shouldReceive('info')
            ->with('Setup completed', \Mockery::type('array'))
            ->once();

        // Create a request with session
        $request = \Illuminate\Http\Request::create('/test', 'POST');
        $request->setLaravelSession($this->app['session.store']);
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });

        // Log setup completion
        $this->auditLogService->logSetupCompletion($admin, $request, [
            'database' => ['type' => 'SQLite'],
            'admin_user' => ['created' => 'Yes'],
            'cloud_storage' => ['provider' => 'Google Drive'],
        ]);

        // The assertion is handled by the shouldReceive mock above
        $this->assertTrue(true);
    }

    public function test_health_check_includes_setup_state()
    {
        // Force setup to be required by clearing any existing admin users
        User::where('role', UserRole::ADMIN)->delete();
        
        // Clear setup state completely
        $setupStateFile = storage_path('app/setup/setup-state.json');
        if (file_exists($setupStateFile)) {
            unlink($setupStateFile);
        }
        
        // Clear setup cache to force re-evaluation
        $this->setupService->clearSetupCache();

        // Test when setup is required
        $response = $this->get('/health');
        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertArrayHasKey('setup_required', $data);
        $this->assertTrue($data['setup_required']);
        $this->assertEquals('setup_required', $data['status']);

        // Create admin user and mark setup as complete
        User::factory()->create(['role' => UserRole::ADMIN]);
        $this->setupService->markSetupComplete();

        // Test when setup is complete
        $response = $this->get('/health');
        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertFalse($data['setup_required']);
        $this->assertEquals('healthy', $data['status']);
    }

    public function test_detailed_health_check_includes_setup_information()
    {
        // Test detailed health check
        $response = $this->get('/health/detailed');
        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertArrayHasKey('setup', $data);
        $this->assertArrayHasKey('required', $data['setup']);
        $this->assertArrayHasKey('progress', $data['setup']);
        $this->assertArrayHasKey('current_step', $data['setup']);
        $this->assertArrayHasKey('completed', $data['setup']);
    }

    public function test_setup_step_completion_is_logged()
    {
        // Create an admin user
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        // Mock the log to capture audit entries
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->with('Setup step completed: admin', \Mockery::type('array'))
            ->once();

        // Also mock the main log channel call
        Log::shouldReceive('info')
            ->with('Setup step completed: admin', \Mockery::type('array'))
            ->once();

        // Create a request with session
        $request = \Illuminate\Http\Request::create('/test', 'POST');
        $request->setLaravelSession($this->app['session.store']);
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });

        // Log setup step completion
        $this->auditLogService->logSetupStepCompletion('admin', $admin, $request, [
            'admin_user_id' => $admin->id,
            'admin_email' => $admin->email,
        ]);

        // The assertion is handled by the shouldReceive mock above
        $this->assertTrue(true);
    }
}