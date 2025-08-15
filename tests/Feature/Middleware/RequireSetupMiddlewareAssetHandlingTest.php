<?php

namespace Tests\Feature\Middleware;

use App\Services\AssetValidationService;
use App\Services\SetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RequireSetupMiddlewareAssetHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure bootstrap checks are enabled
        Config::set('setup.bootstrap_checks', true);
        
        // Clear any existing setup state
        $this->clearSetupState();
    }

    protected function tearDown(): void
    {
        $this->clearSetupState();
        parent::tearDown();
    }

    public function test_middleware_redirects_to_asset_instructions_when_manifest_missing(): void
    {
        // Ensure Vite manifest doesn't exist
        $this->ensureViteManifestMissing();
        
        // Try to access a route that should be protected by setup middleware
        $response = $this->get('/admin/dashboard');
        
        // Should redirect to asset build instructions
        $response->assertRedirect(route('setup.assets'));
    }

    public function test_middleware_handles_vite_manifest_exceptions_gracefully(): void
    {
        // Create a corrupted manifest file
        $this->createCorruptedViteManifest();
        
        // Try to access a route that should be protected by setup middleware
        $response = $this->get('/admin/dashboard');
        
        // Should redirect to asset build instructions instead of throwing 500 error
        $response->assertRedirect(route('setup.assets'));
    }

    public function test_middleware_allows_asset_related_requests(): void
    {
        // Ensure assets are missing
        $this->ensureViteManifestMissing();
        
        // Asset-related paths should be accessible
        $assetPaths = [
            '/build/app.js',
            '/css/app.css',
            '/js/app.js',
            '/images/logo.png',
            '/storage/uploads/file.pdf',
            '/fonts/font.woff2',
            '/assets/icon.svg',
        ];
        
        foreach ($assetPaths as $path) {
            $response = $this->get($path);
            
            // Should not redirect to setup (404 is expected for non-existent files)
            $this->assertNotEquals(302, $response->getStatusCode(), "Asset path {$path} was redirected to setup");
        }
    }

    public function test_middleware_allows_setup_routes_when_assets_missing(): void
    {
        // Ensure assets are missing
        $this->ensureViteManifestMissing();
        
        // Setup routes should be accessible
        $setupRoutes = [
            '/setup',
            '/setup/assets',
            '/setup/welcome',
            '/setup/database',
            '/setup/admin',
            '/setup/storage',
        ];
        
        foreach ($setupRoutes as $route) {
            $response = $this->get($route);
            
            // Should not redirect (200 or other non-redirect status expected)
            $this->assertNotEquals(302, $response->getStatusCode(), "Setup route {$route} was redirected");
        }
    }

    public function test_middleware_returns_json_for_ajax_requests_when_assets_missing(): void
    {
        // Ensure assets are missing
        $this->ensureViteManifestMissing();
        
        // Make an AJAX request to a route that doesn't require auth
        $response = $this->getJson('/health');
        
        // Health route should be exempt, so it should not return setup error
        $this->assertNotEquals(503, $response->getStatusCode());
        
        // Try a different route that should trigger setup middleware
        $response = $this->getJson('/admin/dashboard');
        
        // Should return JSON error response
        $response->assertStatus(503)
            ->assertJson([
                'error' => 'Assets missing',
                'step' => 'assets',
            ])
            ->assertJsonStructure([
                'error',
                'message',
                'redirect',
                'step',
            ]);
    }

    public function test_middleware_proceeds_to_database_check_when_assets_valid(): void
    {
        // Create valid Vite manifest
        $this->createValidViteManifest();
        
        // Try to access a protected route (should proceed to database check)
        $response = $this->get('/dashboard');
        
        // Should redirect to database setup (since database is not configured in test)
        $response->assertRedirect(route('setup.database'));
    }

    public function test_middleware_allows_exempt_routes_regardless_of_asset_status(): void
    {
        // Ensure assets are missing
        $this->ensureViteManifestMissing();
        
        // Configure exempt routes
        Config::set('setup.exempt_routes', ['health', 'ping']);
        
        // Exempt routes should be accessible
        $response = $this->get('/health');
        $this->assertNotEquals(302, $response->getStatusCode());
    }

    public function test_middleware_allows_exempt_paths_regardless_of_asset_status(): void
    {
        // Ensure assets are missing
        $this->ensureViteManifestMissing();
        
        // Configure exempt paths
        Config::set('setup.exempt_paths', ['api/public/*']);
        
        // Exempt paths should be accessible
        $response = $this->get('/api/public/data');
        $this->assertNotEquals(302, $response->getStatusCode());
    }

    public function test_asset_validation_service_integration(): void
    {
        // Test that middleware properly integrates with AssetValidationService
        $assetService = app(AssetValidationService::class);
        
        // Ensure assets are missing
        $this->ensureViteManifestMissing();
        
        // Verify service reports assets as invalid
        $this->assertFalse($assetService->areAssetRequirementsMet());
        
        // Middleware should redirect to assets
        $response = $this->get('/dashboard');
        $response->assertRedirect(route('setup.assets'));
    }

    public function test_setup_service_integration(): void
    {
        // Test that middleware properly integrates with SetupService
        $setupService = app(SetupService::class);
        
        // Ensure assets are missing
        $this->ensureViteManifestMissing();
        
        // Verify service reports assets as invalid
        $this->assertFalse($setupService->areAssetsValid());
        
        // Middleware should redirect to assets
        $response = $this->get('/dashboard');
        $response->assertRedirect(route('setup.assets'));
    }

    public function test_middleware_handles_multiple_asset_validation_calls(): void
    {
        // Ensure assets are missing
        $this->ensureViteManifestMissing();
        
        // Make multiple requests to ensure caching/performance is handled
        for ($i = 0; $i < 3; $i++) {
            $response = $this->get('/dashboard');
            $response->assertRedirect(route('setup.assets'));
        }
    }

    public function test_middleware_respects_bootstrap_checks_config(): void
    {
        // Disable bootstrap checks
        Config::set('setup.bootstrap_checks', false);
        
        // Ensure assets are missing
        $this->ensureViteManifestMissing();
        
        // Should allow access without checking assets
        $response = $this->get('/dashboard');
        
        // Should not redirect to setup
        $this->assertNotEquals(302, $response->getStatusCode());
    }

    /**
     * Ensure the Vite manifest file doesn't exist
     */
    private function ensureViteManifestMissing(): void
    {
        // Remove hot file to disable dev mode
        $hotFile = public_path('hot');
        if (File::exists($hotFile)) {
            File::delete($hotFile);
        }
        
        $manifestPath = public_path('build/manifest.json');
        if (File::exists($manifestPath)) {
            File::delete($manifestPath);
        }
        
        $buildDir = public_path('build');
        if (File::exists($buildDir)) {
            File::deleteDirectory($buildDir);
        }
        
        // Disable dev mode in config for this test
        Config::set('app.env', 'testing');
        putenv('DEV_MODE=false');
    }

    /**
     * Create a valid Vite manifest file
     */
    private function createValidViteManifest(): void
    {
        // Remove hot file to disable dev mode
        $hotFile = public_path('hot');
        if (File::exists($hotFile)) {
            File::delete($hotFile);
        }
        
        $buildDir = public_path('build');
        if (!File::exists($buildDir)) {
            File::makeDirectory($buildDir, 0755, true);
        }
        
        $manifestPath = public_path('build/manifest.json');
        $manifestContent = json_encode([
            'resources/js/app.js' => [
                'file' => 'assets/app-abc123.js',
                'isEntry' => true,
                'src' => 'resources/js/app.js',
            ],
            'resources/css/app.css' => [
                'file' => 'assets/app-def456.css',
                'isEntry' => true,
                'src' => 'resources/css/app.css',
            ],
        ], JSON_PRETTY_PRINT);
        
        File::put($manifestPath, $manifestContent);
        
        // Create some dummy asset files
        $assetsDir = public_path('build/assets');
        if (!File::exists($assetsDir)) {
            File::makeDirectory($assetsDir, 0755, true);
        }
        
        File::put(public_path('build/assets/app-abc123.js'), '// Dummy JS file');
        File::put(public_path('build/assets/app-def456.css'), '/* Dummy CSS file */');
        
        // Disable dev mode in config for this test
        Config::set('app.env', 'testing');
        putenv('DEV_MODE=false');
    }

    /**
     * Create a corrupted Vite manifest file
     */
    private function createCorruptedViteManifest(): void
    {
        // Remove hot file to disable dev mode
        $hotFile = public_path('hot');
        if (File::exists($hotFile)) {
            File::delete($hotFile);
        }
        
        $buildDir = public_path('build');
        if (!File::exists($buildDir)) {
            File::makeDirectory($buildDir, 0755, true);
        }
        
        $manifestPath = public_path('build/manifest.json');
        File::put($manifestPath, 'invalid json content {');
        
        // Disable dev mode in config for this test
        Config::set('app.env', 'testing');
        putenv('DEV_MODE=false');
    }

    /**
     * Clear setup state
     */
    private function clearSetupState(): void
    {
        $stateFile = storage_path('app/setup/setup-state.json');
        if (File::exists($stateFile)) {
            File::delete($stateFile);
        }
    }
}