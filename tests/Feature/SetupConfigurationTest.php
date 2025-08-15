<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Test setup configuration functionality.
 */
class SetupConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_configuration_has_required_asset_validation_settings(): void
    {
        $config = Config::get('setup');

        // Verify asset validation configuration exists
        $this->assertArrayHasKey('asset_paths', $config);
        $this->assertArrayHasKey('asset_checks', $config);

        // Verify asset paths are configured
        $assetPaths = $config['asset_paths'];
        $this->assertArrayHasKey('vite_manifest', $assetPaths);
        $this->assertArrayHasKey('build_directory', $assetPaths);
        $this->assertArrayHasKey('package_json', $assetPaths);

        // Verify asset checks are configured
        $assetChecks = $config['asset_checks'];
        $this->assertArrayHasKey('vite_manifest_required', $assetChecks);
        $this->assertArrayHasKey('node_environment_check', $assetChecks);
        $this->assertArrayHasKey('build_instructions_enabled', $assetChecks);
    }

    public function test_setup_configuration_includes_assets_step(): void
    {
        $config = Config::get('setup');

        $this->assertArrayHasKey('steps', $config);
        $this->assertContains('assets', $config['steps']);
        
        // Verify assets is the first step
        $this->assertEquals('assets', $config['steps'][0]);
    }

    public function test_setup_configuration_has_asset_validation_check_enabled(): void
    {
        $config = Config::get('setup');

        $this->assertArrayHasKey('checks', $config);
        $this->assertArrayHasKey('asset_validation', $config['checks']);
        $this->assertTrue($config['checks']['asset_validation']);
    }

    public function test_setup_configuration_exempt_paths_include_asset_files(): void
    {
        $config = Config::get('setup');

        $this->assertArrayHasKey('exempt_paths', $config);
        $exemptPaths = $config['exempt_paths'];

        // Verify common asset file patterns are exempt
        $this->assertContains('build/*', $exemptPaths);
        $this->assertContains('*.css', $exemptPaths);
        $this->assertContains('*.js', $exemptPaths);
        $this->assertContains('*.png', $exemptPaths);
        $this->assertContains('*.svg', $exemptPaths);
        $this->assertContains('manifest.json', $exemptPaths);
    }

    public function test_setup_configuration_can_be_customized_via_environment(): void
    {
        // Test asset manifest requirement can be disabled
        Config::set('setup.asset_checks.vite_manifest_required', false);
        $this->assertFalse(Config::get('setup.asset_checks.vite_manifest_required'));

        // Test node environment check can be disabled
        Config::set('setup.asset_checks.node_environment_check', false);
        $this->assertFalse(Config::get('setup.asset_checks.node_environment_check'));

        // Test build instructions can be disabled
        Config::set('setup.asset_checks.build_instructions_enabled', false);
        $this->assertFalse(Config::get('setup.asset_checks.build_instructions_enabled'));
    }

    public function test_setup_configuration_asset_paths_are_valid(): void
    {
        $config = Config::get('setup');
        $assetPaths = $config['asset_paths'];

        // Verify paths are strings and not empty
        $this->assertIsString($assetPaths['vite_manifest']);
        $this->assertNotEmpty($assetPaths['vite_manifest']);

        $this->assertIsString($assetPaths['build_directory']);
        $this->assertNotEmpty($assetPaths['build_directory']);

        $this->assertIsString($assetPaths['package_json']);
        $this->assertNotEmpty($assetPaths['package_json']);

        // Verify paths are relative to project root
        $this->assertStringStartsWith('public/', $assetPaths['vite_manifest']);
        $this->assertStringStartsWith('public/', $assetPaths['build_directory']);
        $this->assertEquals('package.json', $assetPaths['package_json']);
    }

    public function test_setup_configuration_has_proper_step_order(): void
    {
        $config = Config::get('setup');
        $steps = $config['steps'];

        $expectedOrder = ['assets', 'welcome', 'database', 'admin', 'storage', 'complete'];
        $this->assertEquals($expectedOrder, $steps);
    }

    public function test_setup_configuration_caching_settings_work(): void
    {
        // Test cache state setting
        Config::set('setup.cache_state', true);
        $this->assertTrue(Config::get('setup.cache_state'));

        Config::set('setup.cache_state', false);
        $this->assertFalse(Config::get('setup.cache_state'));

        // Test cache TTL setting
        Config::set('setup.cache_ttl', 600);
        $this->assertEquals(600, Config::get('setup.cache_ttl'));
    }

    public function test_setup_configuration_bootstrap_checks_setting_works(): void
    {
        Config::set('setup.bootstrap_checks', true);
        $this->assertTrue(Config::get('setup.bootstrap_checks'));

        Config::set('setup.bootstrap_checks', false);
        $this->assertFalse(Config::get('setup.bootstrap_checks'));
    }

    public function test_setup_configuration_route_settings_are_valid(): void
    {
        $config = Config::get('setup');

        $this->assertArrayHasKey('route_prefix', $config);
        $this->assertEquals('setup', $config['route_prefix']);

        $this->assertArrayHasKey('redirect_route', $config);
        $this->assertEquals('setup.welcome', $config['redirect_route']);
    }
}