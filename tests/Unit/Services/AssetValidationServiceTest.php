<?php

namespace Tests\Unit\Services;

use App\Services\AssetValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AssetValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AssetValidationService $assetValidationService;
    private string $testManifestPath;
    private string $testBuildPath;
    private string $testPackageJsonPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->assetValidationService = new AssetValidationService();
        
        // Set up test paths
        $this->testManifestPath = base_path('public/build/manifest.json');
        $this->testBuildPath = base_path('public/build');
        $this->testPackageJsonPath = base_path('package.json');
        
        // Clean up any existing test files
        $this->cleanupTestFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    private function cleanupTestFiles(): void
    {
        if (File::exists($this->testManifestPath)) {
            File::delete($this->testManifestPath);
        }
        
        if (File::exists($this->testBuildPath) && File::isDirectory($this->testBuildPath)) {
            File::deleteDirectory($this->testBuildPath);
        }
    }

    private function createTestManifest(array $content = null): void
    {
        if (!File::exists(dirname($this->testManifestPath))) {
            File::makeDirectory(dirname($this->testManifestPath), 0755, true);
        }
        
        $defaultContent = [
            'resources/js/app.js' => [
                'file' => 'assets/app-abc123.js',
                'isEntry' => true,
            ],
            'resources/css/app.css' => [
                'file' => 'assets/app-def456.css',
                'isEntry' => true,
            ],
        ];
        
        File::put($this->testManifestPath, json_encode($content ?? $defaultContent, JSON_PRETTY_PRINT));
    }

    private function createTestBuildDirectory(bool $withFiles = true): void
    {
        if (!File::exists($this->testBuildPath)) {
            File::makeDirectory($this->testBuildPath, 0755, true);
        }
        
        if ($withFiles) {
            File::put($this->testBuildPath . '/app-abc123.js', '// Test JS file');
            File::put($this->testBuildPath . '/app-def456.css', '/* Test CSS file */');
        }
    }

    public function test_validate_vite_manifest_returns_false_when_manifest_missing(): void
    {
        $this->assertFalse($this->assetValidationService->validateViteManifest());
    }

    public function test_validate_vite_manifest_returns_true_when_manifest_exists_and_valid(): void
    {
        $this->createTestManifest();
        
        $this->assertTrue($this->assetValidationService->validateViteManifest());
    }

    public function test_validate_vite_manifest_returns_false_when_manifest_contains_invalid_json(): void
    {
        if (!File::exists(dirname($this->testManifestPath))) {
            File::makeDirectory(dirname($this->testManifestPath), 0755, true);
        }
        
        File::put($this->testManifestPath, '{ invalid json content');
        
        $this->assertFalse($this->assetValidationService->validateViteManifest());
    }

    public function test_validate_vite_manifest_returns_true_when_disabled_in_config(): void
    {
        Config::set('setup.asset_checks.vite_manifest_required', false);
        
        $service = new AssetValidationService();
        $this->assertTrue($service->validateViteManifest());
    }

    public function test_get_manifest_path_returns_correct_path(): void
    {
        $expectedPath = base_path('public/build/manifest.json');
        $this->assertEquals($expectedPath, $this->assetValidationService->getManifestPath());
    }

    public function test_get_manifest_path_uses_custom_config(): void
    {
        Config::set('setup.asset_paths.vite_manifest', 'custom/path/manifest.json');
        
        $service = new AssetValidationService();
        $expectedPath = base_path('custom/path/manifest.json');
        $this->assertEquals($expectedPath, $service->getManifestPath());
    }

    public function test_get_build_directory_path_returns_correct_path(): void
    {
        $expectedPath = base_path('public/build');
        $this->assertEquals($expectedPath, $this->assetValidationService->getBuildDirectoryPath());
    }

    public function test_validate_build_directory_returns_false_when_directory_missing(): void
    {
        $this->assertFalse($this->assetValidationService->validateBuildDirectory());
    }

    public function test_validate_build_directory_returns_false_when_directory_empty(): void
    {
        $this->createTestBuildDirectory(false); // Create directory but no files
        
        $this->assertFalse($this->assetValidationService->validateBuildDirectory());
    }

    public function test_validate_build_directory_returns_true_when_directory_has_files(): void
    {
        $this->createTestBuildDirectory(true);
        
        $this->assertTrue($this->assetValidationService->validateBuildDirectory());
    }

    public function test_get_build_instructions_returns_correct_structure(): void
    {
        $instructions = $this->assetValidationService->getBuildInstructions();
        
        $this->assertIsArray($instructions);
        $this->assertArrayHasKey('title', $instructions);
        $this->assertArrayHasKey('description', $instructions);
        $this->assertArrayHasKey('steps', $instructions);
        $this->assertArrayHasKey('troubleshooting', $instructions);
        
        $this->assertIsArray($instructions['steps']);
        $this->assertCount(2, $instructions['steps']);
        
        // Check first step structure
        $firstStep = $instructions['steps'][0];
        $this->assertArrayHasKey('title', $firstStep);
        $this->assertArrayHasKey('command', $firstStep);
        $this->assertArrayHasKey('description', $firstStep);
        $this->assertEquals('npm ci', $firstStep['command']);
        
        // Check second step structure
        $secondStep = $instructions['steps'][1];
        $this->assertEquals('npm run build', $secondStep['command']);
        
        $this->assertIsArray($instructions['troubleshooting']);
        $this->assertArrayHasKey('Node.js not installed', $instructions['troubleshooting']);
    }

    public function test_check_node_environment_returns_correct_structure(): void
    {
        $environment = $this->assetValidationService->checkNodeEnvironment();
        
        $this->assertIsArray($environment);
        $this->assertArrayHasKey('package_json_exists', $environment);
        $this->assertArrayHasKey('node_modules_exists', $environment);
        $this->assertArrayHasKey('package_lock_exists', $environment);
        $this->assertArrayHasKey('vite_config_exists', $environment);
        
        // All should be boolean values
        foreach ($environment as $key => $value) {
            $this->assertIsBool($value, "Key {$key} should be boolean");
        }
    }

    public function test_check_node_environment_detects_existing_files(): void
    {
        // package.json should exist in a real Laravel project
        $environment = $this->assetValidationService->checkNodeEnvironment();
        
        // In test environment, package.json should exist
        $this->assertTrue($environment['package_json_exists']);
        
        // vite.config.js should exist in a Laravel project with Vite
        $this->assertTrue($environment['vite_config_exists']);
    }

    public function test_get_asset_validation_results_returns_complete_structure(): void
    {
        $results = $this->assetValidationService->getAssetValidationResults();
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('vite_manifest_exists', $results);
        $this->assertArrayHasKey('build_directory_exists', $results);
        $this->assertArrayHasKey('node_environment', $results);
        $this->assertArrayHasKey('manifest_path', $results);
        $this->assertArrayHasKey('build_directory_path', $results);
        
        $this->assertIsBool($results['vite_manifest_exists']);
        $this->assertIsBool($results['build_directory_exists']);
        $this->assertIsArray($results['node_environment']);
        $this->assertIsString($results['manifest_path']);
        $this->assertIsString($results['build_directory_path']);
    }

    public function test_are_asset_requirements_met_returns_false_when_manifest_missing(): void
    {
        $this->assertFalse($this->assetValidationService->areAssetRequirementsMet());
    }

    public function test_are_asset_requirements_met_returns_false_when_build_directory_missing(): void
    {
        $this->createTestManifest();
        
        // Ensure build directory doesn't exist
        if (File::exists($this->testBuildPath)) {
            File::deleteDirectory($this->testBuildPath);
        }
        
        $this->assertFalse($this->assetValidationService->areAssetRequirementsMet());
    }

    public function test_are_asset_requirements_met_returns_true_when_all_requirements_met(): void
    {
        $this->createTestManifest();
        $this->createTestBuildDirectory(true);
        
        $this->assertTrue($this->assetValidationService->areAssetRequirementsMet());
    }

    public function test_are_asset_requirements_met_returns_true_when_disabled_in_config(): void
    {
        Config::set('setup.asset_checks.vite_manifest_required', false);
        
        $service = new AssetValidationService();
        $this->assertTrue($service->areAssetRequirementsMet());
    }

    public function test_get_asset_paths_returns_configured_paths(): void
    {
        $paths = $this->assetValidationService->getAssetPaths();
        
        $this->assertIsArray($paths);
        $this->assertArrayHasKey('vite_manifest', $paths);
        $this->assertArrayHasKey('build_directory', $paths);
        $this->assertArrayHasKey('package_json', $paths);
        
        $this->assertEquals('public/build/manifest.json', $paths['vite_manifest']);
        $this->assertEquals('public/build', $paths['build_directory']);
        $this->assertEquals('package.json', $paths['package_json']);
    }

    public function test_get_asset_checks_returns_configured_checks(): void
    {
        $checks = $this->assetValidationService->getAssetChecks();
        
        $this->assertIsArray($checks);
        $this->assertArrayHasKey('vite_manifest_required', $checks);
        $this->assertArrayHasKey('node_environment_check', $checks);
        $this->assertArrayHasKey('build_instructions_enabled', $checks);
        
        // Default values should be true
        $this->assertTrue($checks['vite_manifest_required']);
        $this->assertTrue($checks['node_environment_check']);
        $this->assertTrue($checks['build_instructions_enabled']);
    }

    public function test_get_missing_asset_requirements_returns_empty_when_all_met(): void
    {
        $this->createTestManifest();
        $this->createTestBuildDirectory(true);
        
        $missing = $this->assetValidationService->getMissingAssetRequirements();
        
        $this->assertIsArray($missing);
        $this->assertEmpty($missing);
    }

    public function test_get_missing_asset_requirements_includes_manifest_when_missing(): void
    {
        $missing = $this->assetValidationService->getMissingAssetRequirements();
        
        $this->assertIsArray($missing);
        $this->assertNotEmpty($missing);
        
        $manifestMissing = collect($missing)->firstWhere('type', 'vite_manifest');
        $this->assertNotNull($manifestMissing);
        $this->assertEquals('vite_manifest', $manifestMissing['type']);
        $this->assertStringContainsString('manifest file is missing', $manifestMissing['message']);
        $this->assertStringContainsString('npm run build', $manifestMissing['solution']);
    }

    public function test_get_missing_asset_requirements_includes_build_directory_when_missing(): void
    {
        $this->createTestManifest(); // Create manifest but not build directory
        
        // Ensure build directory doesn't exist
        if (File::exists($this->testBuildPath)) {
            File::deleteDirectory($this->testBuildPath);
        }
        
        $missing = $this->assetValidationService->getMissingAssetRequirements();
        
        $buildMissing = collect($missing)->firstWhere('type', 'build_directory');
        $this->assertNotNull($buildMissing);
        $this->assertEquals('build_directory', $buildMissing['type']);
        $this->assertStringContainsString('Build directory', $buildMissing['message']);
    }

    public function test_get_asset_build_status_returns_complete_status(): void
    {
        $status = $this->assetValidationService->getAssetBuildStatus();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('ready', $status);
        $this->assertArrayHasKey('checks', $status);
        $this->assertArrayHasKey('missing', $status);
        $this->assertArrayHasKey('next_step', $status);
        
        $this->assertIsBool($status['ready']);
        $this->assertIsArray($status['checks']);
        $this->assertIsArray($status['missing']);
        $this->assertIsString($status['next_step']);
    }

    public function test_get_asset_build_status_shows_ready_false_when_assets_missing(): void
    {
        $status = $this->assetValidationService->getAssetBuildStatus();
        
        $this->assertFalse($status['ready']);
        $this->assertEquals('assets', $status['next_step']);
        $this->assertNotEmpty($status['missing']);
    }

    public function test_get_asset_build_status_shows_ready_true_when_assets_present(): void
    {
        $this->createTestManifest();
        $this->createTestBuildDirectory(true);
        
        $status = $this->assetValidationService->getAssetBuildStatus();
        
        $this->assertTrue($status['ready']);
        $this->assertEquals('database', $status['next_step']);
        $this->assertEmpty($status['missing']);
    }

    public function test_validate_vite_manifest_handles_empty_manifest_file(): void
    {
        if (!File::exists(dirname($this->testManifestPath))) {
            File::makeDirectory(dirname($this->testManifestPath), 0755, true);
        }
        
        File::put($this->testManifestPath, '');
        
        $this->assertFalse($this->assetValidationService->validateViteManifest());
    }

    public function test_validate_vite_manifest_accepts_empty_json_object(): void
    {
        $this->createTestManifest([]);
        
        $this->assertTrue($this->assetValidationService->validateViteManifest());
    }

    public function test_service_uses_custom_asset_paths_from_config(): void
    {
        Config::set('setup.asset_paths', [
            'vite_manifest' => 'custom/manifest.json',
            'build_directory' => 'custom/build',
            'package_json' => 'custom/package.json',
        ]);
        
        $service = new AssetValidationService();
        $paths = $service->getAssetPaths();
        
        $this->assertEquals('custom/manifest.json', $paths['vite_manifest']);
        $this->assertEquals('custom/build', $paths['build_directory']);
        $this->assertEquals('custom/package.json', $paths['package_json']);
    }

    public function test_service_uses_custom_asset_checks_from_config(): void
    {
        Config::set('setup.asset_checks', [
            'vite_manifest_required' => false,
            'node_environment_check' => false,
            'build_instructions_enabled' => false,
        ]);
        
        $service = new AssetValidationService();
        $checks = $service->getAssetChecks();
        
        $this->assertFalse($checks['vite_manifest_required']);
        $this->assertFalse($checks['node_environment_check']);
        $this->assertFalse($checks['build_instructions_enabled']);
    }

    public function test_validate_build_directory_handles_file_instead_of_directory(): void
    {
        // Create a file where the build directory should be
        $buildPath = $this->assetValidationService->getBuildDirectoryPath();
        $buildDir = dirname($buildPath);
        
        if (!File::exists($buildDir)) {
            File::makeDirectory($buildDir, 0755, true);
        }
        
        File::put($buildPath, 'This is a file, not a directory');
        
        $this->assertFalse($this->assetValidationService->validateBuildDirectory());
        
        // Clean up
        File::delete($buildPath);
    }

    public function test_check_node_environment_uses_custom_package_json_path(): void
    {
        Config::set('setup.asset_paths.package_json', 'custom/package.json');
        
        $service = new AssetValidationService();
        $environment = $service->checkNodeEnvironment();
        
        // Should check for custom path (which won't exist)
        $this->assertFalse($environment['package_json_exists']);
    }
}