<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Test setup routing functionality.
 */
class SetupRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure we're in setup mode for these tests
        Config::set('app.setup_complete', false);
    }

    public function test_setup_routes_are_registered(): void
    {
        $setupRoutes = [
            'setup.assets',
            'setup.welcome',
            'setup.database',
            'setup.admin',
            'setup.storage',
            'setup.complete'
        ];

        foreach ($setupRoutes as $routeName) {
            $this->assertTrue(Route::has($routeName), "Route {$routeName} should be registered");
        }
    }

    public function test_setup_ajax_routes_are_registered(): void
    {
        $ajaxRoutes = [
            'setup.ajax.check-assets',
            'setup.ajax.test-database',
            'setup.ajax.validate-email',
            'setup.ajax.refresh-csrf-token',
            'setup.ajax.recovery-info',
            'setup.ajax.restore-backup',
            'setup.ajax.force-recovery'
        ];

        foreach ($ajaxRoutes as $routeName) {
            $this->assertTrue(Route::has($routeName), "AJAX route {$routeName} should be registered");
        }
    }

    public function test_setup_step_dynamic_routing_works(): void
    {
        $stepMappings = [
            'assets' => 'setup.assets',
            'welcome' => 'setup.welcome', 
            'database' => 'setup.database',
            'admin' => 'setup.admin',
            'storage' => 'setup.storage',
            'complete' => 'setup.complete'
        ];

        foreach ($stepMappings as $step => $expectedRoute) {
            $response = $this->get("/setup/step/{$step}");
            $response->assertRedirect(route($expectedRoute));
        }
    }

    public function test_setup_step_dynamic_routing_handles_invalid_step(): void
    {
        $response = $this->get('/setup/step/invalid');
        // Invalid steps should return 404 due to route constraint
        $response->assertStatus(404);
    }

    public function test_setup_routes_have_proper_prefix(): void
    {
        $setupRoutes = Route::getRoutes()->getByName('setup.assets');
        $this->assertNotNull($setupRoutes);
        $this->assertStringStartsWith('setup/', $setupRoutes->uri());
    }

    public function test_setup_routes_have_required_middleware(): void
    {
        $setupRoute = Route::getRoutes()->getByName('setup.assets');
        $this->assertNotNull($setupRoute);
        
        $middleware = $setupRoute->middleware();
        $this->assertContains('web', $middleware);
        $this->assertContains(\App\Http\Middleware\RequireSetupMiddleware::class, $middleware);
        $this->assertContains(\App\Http\Middleware\ExtendSetupSession::class, $middleware);
        $this->assertContains('throttle:60,1', $middleware);
    }

    public function test_setup_configuration_routes_match_config(): void
    {
        $configSteps = Config::get('setup.steps');
        $this->assertIsArray($configSteps);
        
        // Verify each step has a corresponding route
        foreach ($configSteps as $step) {
            if ($step !== 'welcome') { // welcome is handled differently
                $routeName = "setup.{$step}";
                $this->assertTrue(Route::has($routeName), "Route {$routeName} should exist for config step {$step}");
            }
        }
    }

    public function test_setup_route_names_follow_convention(): void
    {
        $expectedRoutes = [
            'setup.assets' => 'GET',
            'setup.welcome' => 'GET',
            'setup.database' => 'GET',
            'setup.admin' => 'GET',
            'setup.storage' => 'GET',
            'setup.complete' => 'GET',
            'setup.database.configure' => 'POST',
            'setup.admin.create' => 'POST',
            'setup.storage.configure' => 'POST',
            'setup.finish' => 'POST'
        ];

        foreach ($expectedRoutes as $routeName => $method) {
            $this->assertTrue(Route::has($routeName), "Route {$routeName} should be registered");
            
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertContains($method, $route->methods(), "Route {$routeName} should support {$method} method");
        }
    }

    public function test_setup_ajax_routes_follow_convention(): void
    {
        $expectedAjaxRoutes = [
            'setup.ajax.check-assets' => 'POST',
            'setup.ajax.test-database' => 'POST',
            'setup.ajax.validate-email' => 'POST',
            'setup.ajax.refresh-csrf-token' => 'POST',
            'setup.ajax.recovery-info' => 'GET',
            'setup.ajax.restore-backup' => 'POST',
            'setup.ajax.force-recovery' => 'POST'
        ];

        foreach ($expectedAjaxRoutes as $routeName => $method) {
            $this->assertTrue(Route::has($routeName), "AJAX route {$routeName} should be registered");
            
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertContains($method, $route->methods(), "AJAX route {$routeName} should support {$method} method");
        }
    }

    public function test_setup_routes_use_correct_controller(): void
    {
        $setupRoutes = [
            'setup.assets',
            'setup.welcome',
            'setup.database',
            'setup.admin',
            'setup.storage',
            'setup.complete'
        ];

        foreach ($setupRoutes as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route);
            
            $action = $route->getAction();
            $this->assertStringStartsWith('App\Http\Controllers\SetupController', $action['controller']);
        }
    }

    public function test_setup_step_route_constraint_works(): void
    {
        // Valid steps should work
        $validSteps = ['assets', 'welcome', 'database', 'admin', 'storage', 'complete'];
        foreach ($validSteps as $step) {
            $response = $this->get("/setup/step/{$step}");
            $response->assertRedirect(); // Should redirect to the appropriate setup route
        }
    }

    public function test_setup_routes_are_grouped_properly(): void
    {
        $setupRoute = Route::getRoutes()->getByName('setup.assets');
        $this->assertNotNull($setupRoute);
        
        // Check that the route has the setup prefix
        $this->assertStringStartsWith('setup/', $setupRoute->uri());
        
        // Check that middleware is applied at group level
        $middleware = $setupRoute->middleware();
        $this->assertContains('web', $middleware);
        $this->assertContains(\App\Http\Middleware\RequireSetupMiddleware::class, $middleware);
        $this->assertContains(\App\Http\Middleware\ExtendSetupSession::class, $middleware);
    }
}