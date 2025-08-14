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

class SetupMiddlewareIntegrationTest extends TestCase
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
        
        parent::tearDown();
    }

    public function test_require_setup_middleware_redirects_when_setup_needed(): void
    {
        // Ensure setup is required
        $this->ensureSetupRequired();
        
        $protectedRoutes = [
            '/',
            '/admin/dashboard',
            '/admin/users',
            '/client/dashboard',
            '/employee/dashboard',
            '/profile',
            '/login'
        ];
        
        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            
            $response->assertRedirect();
            $this->assertTrue(
                str_contains($response->headers->get('Location'), '/setup'),
                "Route {$route} should redirect to setup"
            );
        }
    }

    public function test_require_setup_middleware_allows_setup_routes(): void
    {
        // Ensure setup is required
        $this->ensureSetupRequired();
        
        $setupRoutes = [
            '/setup',
            '/setup/welcome',
            '/setup/database',
            '/setup/admin',
            '/setup/storage',
            '/setup/complete'
        ];
        
        foreach ($setupRoutes as $route) {
            $response = $this->get($route);
            
            // Should not redirect (status 200 or other non-redirect status)
            $this->assertNotEquals(302, $response->getStatus(), "Setup route {$route} should not redirect");
        }
    }

    public function test_require_setup_middleware_allows_asset_routes(): void
    {
        // Ensure setup is required
        $this->ensureSetupRequired();
        
        $assetRoutes = [
            '/build/app.js',
            '/build/app.css',
            '/storage/file.pdf',
            '/images/logo.png',
            '/favicon.ico',
            '/robots.txt',
            '/health'
        ];
        
        foreach ($assetRoutes as $route) {
            $response = $this->get($route);
            
            // Should not redirect to setup (may return 404 if file doesn't exist, but not redirect)
            $this->assertNotEquals(302, $response->getStatus(), "Asset route {$route} should not redirect to setup");
        }
    }

    public function test_setup_complete_middleware_blocks_setup_routes_when_complete(): void
    {
        // Complete the setup
        $this->completeSetup();
        
        $setupRoutes = [
            '/setup',
            '/setup/welcome',
            '/setup/database',
            '/setup/admin',
            '/setup/storage',
            '/setup/complete'
        ];
        
        foreach ($setupRoutes as $route) {
            $response = $this->get($route);
            
            $response->assertStatus(404, "Setup route {$route} should return 404 when setup is complete");
        }
    }

    public function test_setup_complete_middleware_allows_normal_routes_when_complete(): void
    {
        // Complete the setup
        $this->completeSetup();
        
        $normalRoutes = [
            '/health',
            '/robots.txt',
            '/favicon.ico'
        ];
        
        foreach ($normalRoutes as $route) {
            $response = $this->get($route);
            
            // Should not return 404 (may return other status codes based on route implementation)
            $this->assertNotEquals(404, $response->getStatus(), "Normal route {$route} should be accessible when setup is complete");
        }
    }

    public function test_middleware_integration_with_authentication_routes(): void
    {
        // Ensure setup is required
        $this->ensureSetupRequired();
        
        $authRoutes = [
            '/login',
            '/register',
            '/password/reset',
            '/password/confirm'
        ];
        
        foreach ($authRoutes as $route) {
            $response = $this->get($route);
            
            $response->assertRedirect();
            $this->assertTrue(
                str_contains($response->headers->get('Location'), '/setup'),
                "Auth route {$route} should redirect to setup when setup is required"
            );
        }
    }

    public function test_middleware_integration_with_api_routes(): void
    {
        // Ensure setup is required
        $this->ensureSetupRequired();
        
        $apiRoutes = [
            '/api/user',
            '/api/files',
            '/api/uploads'
        ];
        
        foreach ($apiRoutes as $route) {
            $response = $this->get($route, ['Accept' => 'application/json']);
            
            // API routes should also redirect to setup (or return appropriate JSON error)
            $this->assertTrue(
                $response->isRedirect() || $response->status() === 401 || $response->status() === 403,
                "API route {$route} should be protected when setup is required"
            );
        }
    }

    public function test_setup_state_persistence_across_requests(): void
    {
        $setupService = app(SetupService::class);
        
        // Initially setup should be required
        $this->assertTrue($setupService->isSetupRequired());
        
        // Complete database step
        $setupService->updateSetupStep('database', true);
        
        // Make a request to trigger middleware
        $response = $this->get('/setup/admin');
        $response->assertStatus(200);
        
        // Create new service instance to test persistence
        $newSetupService = app(SetupService::class);
        $steps = $newSetupService->getSetupSteps();
        
        $this->assertTrue($steps['database']['completed']);
        $this->assertNotNull($steps['database']['completed_at']);
    }

    public function test_middleware_handles_concurrent_requests(): void
    {
        // Simulate concurrent requests during setup
        $this->ensureSetupRequired();
        
        $responses = [];
        
        // Make multiple concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->get('/admin/dashboard');
        }
        
        // All should redirect to setup
        foreach ($responses as $index => $response) {
            $response->assertRedirect();
            $this->assertTrue(
                str_contains($response->headers->get('Location'), '/setup'),
                "Concurrent request {$index} should redirect to setup"
            );
        }
    }

    public function test_middleware_with_different_http_methods(): void
    {
        $this->ensureSetupRequired();
        
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        
        foreach ($methods as $method) {
            $response = $this->call($method, '/admin/dashboard');
            
            $response->assertRedirect();
            $this->assertTrue(
                str_contains($response->headers->get('Location'), '/setup'),
                "Method {$method} should redirect to setup when setup is required"
            );
        }
    }

    public function test_middleware_with_ajax_requests(): void
    {
        $this->ensureSetupRequired();
        
        $response = $this->get('/admin/dashboard', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        
        $response->assertRedirect();
        $this->assertTrue(
            str_contains($response->headers->get('Location'), '/setup'),
            "AJAX requests should redirect to setup when setup is required"
        );
    }

    public function test_middleware_with_json_requests(): void
    {
        $this->ensureSetupRequired();
        
        $response = $this->json('GET', '/admin/dashboard');
        
        // JSON requests should also be redirected or return appropriate error
        $this->assertTrue(
            $response->isRedirect() || $response->status() >= 400,
            "JSON requests should be handled appropriately when setup is required"
        );
    }

    public function test_setup_step_routing_integration(): void
    {
        $this->ensureSetupRequired();
        
        $stepRoutes = [
            '/setup/step/welcome' => '/setup/welcome',
            '/setup/step/database' => '/setup/database',
            '/setup/step/admin' => '/setup/admin',
            '/setup/step/storage' => '/setup/storage',
            '/setup/step/complete' => '/setup/complete'
        ];
        
        foreach ($stepRoutes as $stepRoute => $expectedRedirect) {
            $response = $this->get($stepRoute);
            
            $response->assertRedirect($expectedRedirect);
        }
    }

    public function test_invalid_setup_step_routing(): void
    {
        $this->ensureSetupRequired();
        
        $response = $this->get('/setup/step/invalid');
        
        $response->assertRedirect('/setup/welcome');
    }

    public function test_middleware_performance_under_load(): void
    {
        $this->ensureSetupRequired();
        
        $startTime = microtime(true);
        
        // Make 50 requests
        for ($i = 0; $i < 50; $i++) {
            $response = $this->get('/admin/dashboard');
            $response->assertRedirect();
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Should complete 50 requests in under 2 seconds
        $this->assertLessThan(2.0, $executionTime, 'Middleware performance under load is too slow');
    }

    public function test_setup_completion_flow_integration(): void
    {
        // Start with setup required
        $this->ensureSetupRequired();
        
        // Verify setup routes are accessible
        $response = $this->get('/setup/welcome');
        $response->assertStatus(200);
        
        // Verify normal routes redirect
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect();
        
        // Complete setup
        $this->completeSetup();
        
        // Verify setup routes are now blocked
        $response = $this->get('/setup/welcome');
        $response->assertStatus(404);
        
        // Verify normal routes are accessible (may require authentication)
        $response = $this->get('/health');
        $response->assertStatus(200);
    }

    public function test_middleware_with_custom_headers(): void
    {
        $this->ensureSetupRequired();
        
        $response = $this->get('/admin/dashboard', [
            'User-Agent' => 'Custom Bot/1.0',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Custom-Header' => 'test-value'
        ]);
        
        $response->assertRedirect();
        $this->assertTrue(
            str_contains($response->headers->get('Location'), '/setup'),
            "Requests with custom headers should redirect to setup when setup is required"
        );
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
        
        // Clear setup cache
        Cache::flush();
        
        // Force setup service to re-evaluate
        $setupService = app(SetupService::class);
        $setupService->clearSetupCache();
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