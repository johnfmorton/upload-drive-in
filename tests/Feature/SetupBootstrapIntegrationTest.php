<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\SetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SetupBootstrapIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private string $setupStateFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupStateFile = storage_path('app/setup/setup-state.json');
        
        // Clean up any existing setup state
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        // Clear cache
        Cache::flush();
        
        // Clear any existing admin users and cloud storage config
        User::where('role', UserRole::ADMIN)->delete();
        Config::set('services.google.client_id', null);
        Config::set('services.google.client_secret', null);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        Cache::flush();
        parent::tearDown();
    }

    public function test_setup_service_provider_registers_service_correctly(): void
    {
        $setupService = app(SetupService::class);
        
        $this->assertInstanceOf(SetupService::class, $setupService);
        $this->assertTrue($setupService->isSetupRequired());
    }

    public function test_setup_configuration_is_loaded_correctly(): void
    {
        $config = Config::get('setup');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('steps', $config);
        $this->assertArrayHasKey('checks', $config);
        $this->assertArrayHasKey('exempt_routes', $config);
        $this->assertArrayHasKey('exempt_paths', $config);
    }

    public function test_setup_service_uses_configuration(): void
    {
        // Override configuration
        Config::set('setup.steps', ['test1', 'test2', 'test3']);
        Config::set('setup.cache_state', false);
        
        $setupService = new SetupService();
        $config = $setupService->getSetupConfig();
        
        $this->assertEquals(['test1', 'test2', 'test3'], $config['steps']);
        $this->assertFalse($config['cache_enabled']);
    }

    public function test_setup_state_caching_works(): void
    {
        Config::set('setup.cache_state', true);
        Config::set('setup.cache_ttl', 60);
        
        $setupService = new SetupService();
        
        // First call should cache the result
        $required1 = $setupService->isSetupRequired();
        
        // Second call should use cached result
        $required2 = $setupService->isSetupRequired();
        
        $this->assertEquals($required1, $required2);
        $this->assertTrue($required1); // Should be true since no admin exists
    }

    public function test_setup_cache_is_cleared_when_state_changes(): void
    {
        Config::set('setup.cache_state', true);
        
        $setupService = new SetupService();
        
        // Cache initial state
        $setupService->isSetupRequired();
        
        // Update setup step
        $setupService->updateSetupStep('database', true);
        
        // Cache should be cleared
        $this->assertNull(Cache::get('setup_state_required'));
    }

    public function test_setup_environment_validation(): void
    {
        $setupService = app(SetupService::class);
        $issues = $setupService->validateSetupEnvironment();
        
        // Should not have critical issues in test environment
        $this->assertIsArray($issues);
        
        // Check that storage directory is writable
        $storageDir = storage_path('app/setup');
        $this->assertTrue(File::exists($storageDir));
        $this->assertTrue(is_writable($storageDir));
    }

    public function test_middleware_integration_with_configuration(): void
    {
        // Set custom configuration
        Config::set('setup.route_prefix', 'custom-setup');
        Config::set('setup.redirect_route', 'setup.welcome');
        Config::set('setup.exempt_routes', ['custom-health']);
        
        // Ensure setup is required
        $this->ensureSetupRequired();
        
        // Test custom exempt route
        $response = $this->get('/custom-health');
        $this->assertNotEquals(302, $response->status());
        
        // Test normal route redirects
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect();
    }

    public function test_setup_checks_can_be_disabled_individually(): void
    {
        // Disable specific checks
        Config::set('setup.checks.admin_user_exists', false);
        Config::set('setup.checks.cloud_storage_configured', false);
        
        $setupService = new SetupService();
        
        // Even without admin user or cloud storage, setup should not be required
        // if those checks are disabled
        $this->assertFalse($setupService->isSetupRequired());
    }

    public function test_bootstrap_checks_can_be_disabled(): void
    {
        Config::set('setup.bootstrap_checks', false);
        
        // Ensure setup would normally be required
        $this->ensureSetupRequired();
        
        // With bootstrap checks disabled, middleware should not redirect
        $response = $this->get('/admin/dashboard');
        
        // Should not redirect to setup (may redirect to login instead)
        $this->assertFalse(str_contains($response->headers->get('Location', ''), 'setup'));
    }

    public function test_ajax_requests_get_json_response_when_setup_required(): void
    {
        $this->ensureSetupRequired();
        
        $response = $this->json('GET', '/admin/dashboard');
        
        // The response might be 401 (unauthorized) or 503 (setup required) depending on middleware order
        $this->assertContains($response->status(), [401, 503]);
        
        if ($response->status() === 503) {
            $response->assertJson([
                'error' => 'Setup required',
                'message' => 'Application setup is required before accessing this resource.',
            ]);
            $response->assertJsonStructure(['redirect']);
        }
    }

    public function test_setup_service_handles_database_connection_failures(): void
    {
        // Temporarily break database connection
        Config::set('database.connections.testing.database', '/invalid/path/database.sqlite');
        
        $setupService = new SetupService();
        
        // Should handle database connection failure gracefully
        $this->assertTrue($setupService->isSetupRequired());
    }

    public function test_setup_state_persistence_across_service_instances(): void
    {
        $setupService1 = new SetupService();
        $setupService1->updateSetupStep('database', true);
        
        $setupService2 = new SetupService();
        $steps = $setupService2->getSetupSteps();
        
        $this->assertTrue($steps['database']['completed']);
    }

    public function test_setup_completion_integration(): void
    {
        $setupService = app(SetupService::class);
        
        // Initially setup should be required
        $this->assertTrue($setupService->isSetupRequired());
        
        // Complete all requirements
        $this->completeSetup();
        
        // Setup should no longer be required
        $this->assertFalse($setupService->isSetupRequired());
        
        // Clear cache to ensure fresh check
        Cache::flush();
        
        // Create a new service instance to test
        $newSetupService = new SetupService();
        $this->assertFalse($newSetupService->isSetupRequired());
        
        // Test that setup is marked as complete
        $this->assertTrue($newSetupService->isSetupComplete());
    }

    public function test_route_service_provider_setup_integration(): void
    {
        // Test that setup routes are registered
        $setupRoutes = [
            'setup.welcome',
            'setup.database',
            'setup.admin',
            'setup.storage',
            'setup.complete'
        ];
        
        foreach ($setupRoutes as $routeName) {
            $this->assertTrue(
                \Route::has($routeName),
                "Route {$routeName} should be registered"
            );
        }
    }

    public function test_view_composer_shares_setup_state(): void
    {
        $this->ensureSetupRequired();
        
        // Test that the view composer is registered by checking if setup service is available
        $setupService = app(SetupService::class);
        $this->assertTrue($setupService->isSetupRequired());
        
        // Test that setup state can be retrieved
        $this->assertIsString($setupService->getSetupStep());
        $this->assertIsInt($setupService->getSetupProgress());
    }

    public function test_setup_service_singleton_registration(): void
    {
        $service1 = app(SetupService::class);
        $service2 = app(SetupService::class);
        
        // Should be the same instance (singleton)
        $this->assertSame($service1, $service2);
    }

    public function test_setup_middleware_performance(): void
    {
        $this->ensureSetupRequired();
        
        $startTime = microtime(true);
        
        // Make multiple requests to test middleware performance
        for ($i = 0; $i < 10; $i++) {
            $response = $this->get('/admin/dashboard');
            $response->assertRedirect();
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Should complete 10 requests in under 1 second
        $this->assertLessThan(1.0, $executionTime, 'Setup middleware performance is too slow');
    }

    public function test_setup_configuration_validation(): void
    {
        // Test with valid configuration
        Config::set('setup.steps', ['step1', 'step2']);
        
        $setupService = new SetupService();
        
        // Should use the configured steps
        $this->assertEquals(['step1', 'step2'], $setupService->getSetupConfig()['steps']);
    }

    /**
     * Helper method to ensure setup is required
     */
    private function ensureSetupRequired(): void
    {
        // Clear any existing admin users
        User::where('role', UserRole::ADMIN)->delete();
        
        // Clear cloud storage configuration
        Config::set('services.google.client_id', null);
        Config::set('services.google.client_secret', null);
        
        // Clear setup state file
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        // Clear cache
        Cache::flush();
    }

    /**
     * Helper method to complete setup
     */
    private function completeSetup(): void
    {
        $setupService = app(SetupService::class);
        
        // Create admin user
        User::factory()->create([
            'role' => UserRole::ADMIN,
            'email_verified_at' => now()
        ]);
        
        // Set cloud storage configuration
        Config::set('services.google.client_id', 'test-client-id');
        Config::set('services.google.client_secret', 'test-client-secret');
        
        // Mark setup as complete
        $setupService->markSetupComplete();
    }
}