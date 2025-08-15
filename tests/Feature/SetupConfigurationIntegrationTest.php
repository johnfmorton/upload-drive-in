<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Test integration between setup configuration and routing.
 */
class SetupConfigurationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure we're in setup mode for these tests
        Config::set('app.setup_complete', false);
    }

    public function test_setup_configuration_and_routing_integration(): void
    {
        // Test that configuration steps match available routes
        $configSteps = Config::get('setup.steps');
        $this->assertIsArray($configSteps);
        $this->assertContains('assets', $configSteps);

        // Verify each step (except welcome which is handled specially) has a route
        foreach ($configSteps as $step) {
            if ($step !== 'welcome') {
                $routeName = "setup.{$step}";
                $this->assertTrue(Route::has($routeName), "Route {$routeName} should exist for config step {$step}");
            }
        }
    }

    public function test_asset_validation_configuration_is_accessible(): void
    {
        $assetPaths = Config::get('setup.asset_paths');
        $this->assertIsArray($assetPaths);
        $this->assertArrayHasKey('vite_manifest', $assetPaths);
        $this->assertArrayHasKey('build_directory', $assetPaths);
        $this->assertArrayHasKey('package_json', $assetPaths);

        $assetChecks = Config::get('setup.asset_checks');
        $this->assertIsArray($assetChecks);
        $this->assertArrayHasKey('vite_manifest_required', $assetChecks);
        $this->assertArrayHasKey('node_environment_check', $assetChecks);
        $this->assertArrayHasKey('build_instructions_enabled', $assetChecks);
    }

    public function test_setup_route_prefix_matches_configuration(): void
    {
        $configPrefix = Config::get('setup.route_prefix');
        $this->assertEquals('setup', $configPrefix);

        // Verify routes use this prefix
        $setupRoute = Route::getRoutes()->getByName('setup.assets');
        $this->assertNotNull($setupRoute);
        $this->assertStringStartsWith($configPrefix . '/', $setupRoute->uri());
    }

    public function test_setup_redirect_route_configuration_works(): void
    {
        $redirectRoute = Config::get('setup.redirect_route');
        $this->assertEquals('setup.welcome', $redirectRoute);

        // Verify the redirect route exists
        $this->assertTrue(Route::has($redirectRoute));
    }

    public function test_setup_exempt_paths_configuration_is_comprehensive(): void
    {
        $exemptPaths = Config::get('setup.exempt_paths');
        $this->assertIsArray($exemptPaths);

        // Verify common asset patterns are included
        $expectedPatterns = [
            'build/*',
            '*.css',
            '*.js',
            '*.png',
            '*.svg',
            'manifest.json'
        ];

        foreach ($expectedPatterns as $pattern) {
            $this->assertContains($pattern, $exemptPaths, "Exempt paths should include {$pattern}");
        }
    }

    public function test_setup_checks_configuration_includes_asset_validation(): void
    {
        $checks = Config::get('setup.checks');
        $this->assertIsArray($checks);
        $this->assertArrayHasKey('asset_validation', $checks);
        $this->assertTrue($checks['asset_validation']);
    }

    public function test_setup_configuration_can_be_overridden_by_environment(): void
    {
        // Test that environment variables can override configuration
        $originalValue = Config::get('setup.asset_checks.vite_manifest_required');
        
        // Override via config (simulating environment variable)
        Config::set('setup.asset_checks.vite_manifest_required', false);
        $this->assertFalse(Config::get('setup.asset_checks.vite_manifest_required'));

        // Restore original value
        Config::set('setup.asset_checks.vite_manifest_required', $originalValue);
    }

    public function test_setup_ajax_routes_are_properly_configured(): void
    {
        $ajaxRoutes = [
            'setup.ajax.check-assets',
            'setup.ajax.test-database',
            'setup.ajax.validate-email',
            'setup.ajax.refresh-csrf-token'
        ];

        foreach ($ajaxRoutes as $routeName) {
            $this->assertTrue(Route::has($routeName), "AJAX route {$routeName} should be registered");
            
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route);
            
            // Verify AJAX routes are under the setup prefix
            $this->assertStringStartsWith('setup/ajax/', $route->uri());
        }
    }

    public function test_setup_step_routing_matches_configuration_order(): void
    {
        $configSteps = Config::get('setup.steps');
        $this->assertIsArray($configSteps);

        // Verify the first step is assets (for requirement 1.4)
        $this->assertEquals('assets', $configSteps[0]);

        // Verify step routing works for all configured steps
        foreach ($configSteps as $step) {
            $response = $this->get("/setup/step/{$step}");
            $expectedRoute = $step === 'welcome' ? 'setup.welcome' : "setup.{$step}";
            $response->assertRedirect(route($expectedRoute));
        }
    }

    public function test_setup_configuration_supports_progress_tracking(): void
    {
        // Verify configuration supports progress tracking (requirement 6.1)
        $steps = Config::get('setup.steps');
        $this->assertIsArray($steps);
        $this->assertGreaterThan(1, count($steps));

        // Verify caching configuration for performance
        $cacheEnabled = Config::get('setup.cache_state');
        $this->assertIsBool($cacheEnabled);

        $cacheTtl = Config::get('setup.cache_ttl');
        $this->assertIsInt($cacheTtl);
        $this->assertGreaterThan(0, $cacheTtl);
    }
}