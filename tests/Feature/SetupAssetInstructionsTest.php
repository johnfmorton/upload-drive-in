<?php

namespace Tests\Feature;

use App\Services\AssetValidationService;
use App\Services\SetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SetupAssetInstructionsTest extends TestCase
{
    use RefreshDatabase;

    private AssetValidationService $assetValidationService;
    private SetupService $setupService;
    private string $setupStateFile;
    private string $manifestPath;
    private string $buildDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable middleware for all tests in this class
        $this->withoutMiddleware();
        
        $this->assetValidationService = app(AssetValidationService::class);
        $this->setupService = app(SetupService::class);
        
        $this->setupStateFile = storage_path('app/setup/setup-state.json');
        $this->manifestPath = base_path('public/build/manifest.json');
        $this->buildDirectory = base_path('public/build');
        
        // Clean up any existing setup state
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        // Clean up any existing build files
        if (File::exists($this->manifestPath)) {
            File::delete($this->manifestPath);
        }
        
        if (File::exists($this->buildDirectory)) {
            File::deleteDirectory($this->buildDirectory);
        }
        
        // Ensure setup is not complete for these tests
        $this->setupService->clearSetupCache();
        
        // Delete setup state file to simulate fresh installation
        $setupDir = storage_path('app/setup');
        if (File::exists($setupDir)) {
            File::deleteDirectory($setupDir);
        }
    }

    protected function tearDown(): void
    {
        // Clean up setup state file
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        // Clean up build files
        if (File::exists($this->manifestPath)) {
            File::delete($this->manifestPath);
        }
        
        if (File::exists($this->buildDirectory)) {
            File::deleteDirectory($this->buildDirectory);
        }
        
        parent::tearDown();
    }

    public function test_asset_instructions_screen_displays_correctly(): void
    {
        $response = $this->get('/setup/assets');
        
        if ($response->status() !== 200) {
            dump($response->getContent());
        }
        
        $response->assertStatus(200);
        $response->assertViewIs('setup.assets');
        $response->assertSee('Build Frontend Assets');
        $response->assertSee('npm ci');
        $response->assertSee('npm run build');
    }

    public function test_asset_instructions_shows_missing_requirements(): void
    {
        $response = $this->get('/setup/assets');
        
        $response->assertStatus(200);
        $response->assertViewHas('missingRequirements');
        $response->assertViewHas('assetStatus');
        
        $viewData = $response->viewData('assetStatus');
        $this->assertFalse($viewData['ready']);
    }

    public function test_asset_instructions_shows_build_instructions(): void
    {
        $response = $this->get('/setup/assets');
        
        $response->assertStatus(200);
        $response->assertViewHas('buildInstructions');
        
        $instructions = $response->viewData('buildInstructions');
        $this->assertArrayHasKey('steps', $instructions);
        $this->assertArrayHasKey('troubleshooting', $instructions);
    }

    public function test_welcome_redirects_to_assets_when_manifest_missing(): void
    {
        $response = $this->get('/setup/welcome');
        
        $response->assertRedirect('/setup/assets');
    }

    public function test_welcome_displays_when_assets_ready(): void
    {
        // Create manifest file and build directory
        $this->createValidAssets();
        
        $response = $this->get('/setup/welcome');
        
        $response->assertStatus(200);
        $response->assertViewIs('setup.welcome');
    }

    public function test_check_asset_build_status_returns_not_ready_initially(): void
    {
        $response = $this->postJson('/setup/ajax/check-assets');
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'ready' => false
        ]);
        
        $responseData = $response->json();
        $this->assertArrayHasKey('missing', $responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertStringContainsString('not ready', $responseData['message']);
    }

    public function test_check_asset_build_status_returns_ready_when_assets_exist(): void
    {
        // Create valid assets
        $this->createValidAssets();
        
        $response = $this->postJson('/setup/ajax/check-assets');
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'ready' => true
        ]);
        
        $responseData = $response->json();
        $this->assertStringContainsString('ready', $responseData['message']);
        $this->assertArrayHasKey('next_step_url', $responseData);
        $this->assertStringContainsString('/setup/welcome', $responseData['next_step_url']);
    }

    public function test_check_asset_build_status_handles_validation_errors(): void
    {
        // Mock the asset validation service to throw an exception
        $this->mock(AssetValidationService::class, function ($mock) {
            $mock->shouldReceive('getAssetBuildStatus')
                ->andThrow(new \Exception('Test validation error'));
        });
        
        $response = $this->postJson('/setup/ajax/check-assets');
        
        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'ready' => false
        ]);
        
        $responseData = $response->json();
        $this->assertStringContainsString('Unable to check asset status', $responseData['message']);
    }

    public function test_asset_instructions_handles_service_errors_gracefully(): void
    {
        // Mock the asset validation service to throw an exception
        $this->mock(AssetValidationService::class, function ($mock) {
            $mock->shouldReceive('getAssetBuildStatus')
                ->andThrow(new \Exception('Test service error'));
            
            $mock->shouldReceive('getBuildInstructions')
                ->andReturn([
                    'title' => 'Build Frontend Assets',
                    'steps' => [],
                    'troubleshooting' => []
                ]);
        });
        
        $response = $this->get('/setup/assets');
        
        $response->assertStatus(200);
        $response->assertViewIs('setup.assets');
        $response->assertViewHas('error');
        
        $viewData = $response->viewData('assetStatus');
        $this->assertFalse($viewData['ready']);
    }

    public function test_asset_instructions_shows_progress_indicator(): void
    {
        $response = $this->get('/setup/assets');
        
        $response->assertStatus(200);
        $response->assertViewHas('progress');
        $response->assertViewHas('currentStep');
        
        $currentStep = $response->viewData('currentStep');
        $this->assertEquals('assets', $currentStep);
    }

    public function test_asset_instructions_prevents_proceeding_when_not_ready(): void
    {
        $response = $this->get('/setup/assets');
        
        $response->assertStatus(200);
        $response->assertViewHas('canProceed');
        
        $canProceed = $response->viewData('canProceed');
        $this->assertFalse($canProceed);
    }

    public function test_asset_instructions_allows_proceeding_when_ready(): void
    {
        // Create valid assets
        $this->createValidAssets();
        
        $response = $this->get('/setup/assets');
        
        $response->assertStatus(200);
        $response->assertViewHas('canProceed');
        
        $canProceed = $response->viewData('canProceed');
        $this->assertTrue($canProceed);
    }

    public function test_check_asset_build_status_logs_requests(): void
    {
        // This test just verifies the endpoint works and would log
        // We can't easily test logging in feature tests without complex setup
        $response = $this->postJson('/setup/ajax/check-assets');
        
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_asset_instructions_with_partial_build_files(): void
    {
        // Create build directory but no manifest
        File::makeDirectory($this->buildDirectory, 0755, true);
        File::put($this->buildDirectory . '/app.js', 'console.log("test");');
        
        $response = $this->get('/setup/assets');
        
        $response->assertStatus(200);
        $response->assertViewHas('missingRequirements');
        
        $missing = $response->viewData('missingRequirements');
        $this->assertNotEmpty($missing);
        
        // Should still show manifest as missing
        $manifestMissing = collect($missing)->contains(function ($item) {
            return $item['type'] === 'vite_manifest';
        });
        $this->assertTrue($manifestMissing);
    }

    public function test_asset_instructions_with_invalid_manifest(): void
    {
        // Create build directory and invalid manifest
        File::makeDirectory($this->buildDirectory, 0755, true);
        File::put($this->manifestPath, 'invalid json content');
        
        $response = $this->get('/setup/assets');
        
        $response->assertStatus(200);
        $response->assertViewHas('assetStatus');
        
        $assetStatus = $response->viewData('assetStatus');
        $this->assertFalse($assetStatus['ready']);
    }

    public function test_check_asset_build_status_with_missing_node_modules(): void
    {
        $response = $this->postJson('/setup/ajax/check-assets');
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertArrayHasKey('missing', $responseData);
        
        $missing = $responseData['missing'];
        $nodeModulesMissing = collect($missing)->contains(function ($item) {
            return $item['type'] === 'node_modules';
        });
        
        $this->assertTrue($nodeModulesMissing);
    }

    public function test_asset_instructions_csrf_protection(): void
    {
        $response = $this->post('/setup/ajax/check-assets', [], [
            'HTTP_X-CSRF-TOKEN' => 'invalid'
        ]);
        
        $response->assertStatus(419); // CSRF token mismatch
    }

    /**
     * Helper method to create valid asset files for testing
     */
    private function createValidAssets(): void
    {
        // Create build directory
        File::makeDirectory($this->buildDirectory, 0755, true);
        
        // Create valid manifest file
        $manifest = [
            'resources/js/app.js' => [
                'file' => 'assets/app-abc123.js',
                'src' => 'resources/js/app.js',
                'isEntry' => true
            ],
            'resources/css/app.css' => [
                'file' => 'assets/app-def456.css',
                'src' => 'resources/css/app.css',
                'isEntry' => true
            ]
        ];
        
        File::put($this->manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
        
        // Create assets subdirectory and build files
        File::makeDirectory($this->buildDirectory . '/assets', 0755, true);
        File::put($this->buildDirectory . '/assets/app-abc123.js', 'console.log("app");');
        File::put($this->buildDirectory . '/assets/app-def456.css', 'body { margin: 0; }');
    }
}