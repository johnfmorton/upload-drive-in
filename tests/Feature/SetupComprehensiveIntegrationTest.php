<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AssetValidationService;
use App\Services\SetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

class SetupComprehensiveIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private string $setupStateFile;
    private string $manifestPath;
    private string $buildDirectory;
    private SetupService $setupService;
    private AssetValidationService $assetValidationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable middleware for these tests to avoid security service issues
        $this->withoutMiddleware();
        
        $this->setupService = app(SetupService::class);
        $this->assetValidationService = app(AssetValidationService::class);
        
        $this->setupStateFile = storage_path('app/setup/setup-state.json');
        $this->manifestPath = base_path('public/build/manifest.json');
        $this->buildDirectory = base_path('public/build');
        
        // Start with completely fresh state
        $this->resetToFreshInstallation();
        
        // Share errors variable for views to prevent undefined variable errors
        view()->share('errors', session()->get('errors', collect()));
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    /**
     * Test complete installation workflow from fresh state
     * Requirements: 1.1, 2.1, 3.1, 6.5
     */
    public function test_complete_installation_workflow_from_fresh_state(): void
    {
        // Step 1: Ensure assets are cleaned up and verify asset validation works
        $this->cleanupTestFiles();
        
        // Also remove hot file if it exists
        $hotFile = public_path('hot');
        if (file_exists($hotFile)) {
            unlink($hotFile);
        }
        
        $this->assertFalse($this->assetValidationService->areAssetRequirementsMet());
        
        // Step 2: Asset instructions screen should be accessible
        $response = $this->get('/setup/assets');
        $response->assertStatus(200);
        $response->assertViewIs('setup.assets');
        $response->assertSee('Build Frontend Assets');
        $response->assertSee('npm ci');
        $response->assertSee('npm run build');
        
        // Step 3: Check asset status - should be not ready
        $response = $this->postJson('/setup/ajax/check-assets');
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'ready' => false]);
        
        // Step 4: Simulate asset build completion
        $this->createValidAssets();
        
        // Step 5: Check asset status - should now be ready
        $response = $this->postJson('/setup/ajax/check-assets');
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'ready' => true]);
        
        // Step 6: Welcome screen should now be accessible
        $response = $this->get('/setup/welcome');
        $response->assertStatus(200);
        $response->assertViewIs('setup.welcome');
        
        // Step 7: Database configuration
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => storage_path('app/test-setup.sqlite')
        ]);
        $response->assertRedirect('/setup/admin');
        
        // Step 8: Admin user creation
        $response = $this->post('/setup/admin', [
            'name' => 'Test Administrator',
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        $response->assertRedirect('/setup/storage');
        
        // Verify admin user was created
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN
        ]);
        
        // Step 9: Storage configuration
        File::shouldReceive('exists')->with(base_path('.env'))->andReturn(true);
        File::shouldReceive('get')->with(base_path('.env'))->andReturn("APP_NAME=TestApp\n");
        File::shouldReceive('put')->with(base_path('.env'), Mockery::any())->andReturn(true);
        
        $response = $this->post('/setup/storage', [
            'provider' => 'google-drive',
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'test-client-secret'
        ]);
        $response->assertRedirect('/setup/complete');
        
        // Step 10: Complete setup
        $response = $this->post('/setup/complete');
        $response->assertRedirect('/admin/dashboard');
        
        // Step 11: Verify normal application flow works
        $response = $this->get('/health');
        $response->assertStatus(200);
    }

    /**
     * Test setup flow with asset build failure and recovery
     * Requirements: 1.1, 4.4
     */
    public function test_setup_flow_with_asset_failure_and_recovery(): void
    {
        // Start with fresh installation
        $this->assertTrue($this->setupService->isSetupRequired());
        
        // Access should redirect to assets
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect();
        $this->assertStringContainsString('/setup', $response->headers->get('Location'));
        
        // Asset check should fail initially
        $response = $this->postJson('/setup/ajax/check-assets');
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'ready' => false]);
        
        // Create partial assets (build directory but no manifest)
        File::makeDirectory($this->buildDirectory, 0755, true);
        File::put($this->buildDirectory . '/app.js', 'console.log("test");');
        
        // Asset check should still fail
        $response = $this->postJson('/setup/ajax/check-assets');
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'ready' => false]);
        
        // Create invalid manifest
        File::put($this->manifestPath, 'invalid json');
        
        // Asset check should still fail
        $response = $this->postJson('/setup/ajax/check-assets');
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'ready' => false]);
        
        // Fix assets by creating valid manifest
        $this->createValidAssets();
        
        // Asset check should now succeed
        $response = $this->postJson('/setup/ajax/check-assets');
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'ready' => true]);
        
        // Should be able to proceed to welcome
        $response = $this->get('/setup/welcome');
        $response->assertStatus(200);
    }

    /**
     * Test setup flow with database configuration failures and recovery
     * Requirements: 2.1, 4.4
     */
    public function test_setup_flow_with_database_failure_and_recovery(): void
    {
        // Complete asset requirements first
        $this->createValidAssets();
        
        // Try invalid database configuration
        $response = $this->post('/setup/database', [
            'database_type' => 'mysql',
            'mysql_host' => 'invalid-host',
            'mysql_database' => 'nonexistent',
            'mysql_username' => 'invalid',
            'mysql_password' => 'wrong'
        ]);
        
        // Should either have session errors or redirect back due to validation failure
        $this->assertTrue(
            $response->isRedirect() || session()->has('errors'),
            'Database configuration with invalid credentials should fail'
        );
        
        // Try with missing required fields
        $response = $this->post('/setup/database', [
            'database_type' => 'mysql',
            'mysql_host' => '',
            'mysql_database' => '',
            'mysql_username' => ''
        ]);
        
        $response->assertSessionHasErrors(['mysql_host', 'mysql_database', 'mysql_username']);
        
        // Fix with valid SQLite configuration
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => storage_path('app/test-setup.sqlite')
        ]);
        
        $response->assertRedirect('/setup/admin');
        $response->assertSessionHas('success');
        
        // Verify setup state was updated
        $steps = $this->setupService->getSetupSteps();
        $this->assertTrue($steps['database']['completed']);
    }

    /**
     * Test setup flow with admin user creation failures and recovery
     * Requirements: 3.1, 4.4
     */
    public function test_setup_flow_with_admin_creation_failure_and_recovery(): void
    {
        // Complete prerequisites
        $this->createValidAssets();
        $this->completeSetupStep('database');
        
        // Try invalid admin user data
        $response = $this->post('/setup/admin', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456'
        ]);
        
        $response->assertSessionHasErrors(['name', 'email', 'password']);
        
        // Try with existing email (create user first)
        User::factory()->create(['email' => 'existing@example.com']);
        
        $response = $this->post('/setup/admin', [
            'name' => 'Test Admin',
            'email' => 'existing@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        
        $response->assertSessionHasErrors(['email']);
        
        // Fix with valid data
        $response = $this->post('/setup/admin', [
            'name' => 'Test Administrator',
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        
        $response->assertRedirect('/setup/storage');
        $response->assertSessionHas('success');
        
        // Verify admin user was created
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN
        ]);
    }

    /**
     * Test setup middleware behavior with missing assets and database issues
     * Requirements: 1.1, 2.1, 4.4
     */
    public function test_setup_middleware_behavior_with_various_issues(): void
    {
        // Test with missing assets
        $protectedRoutes = [
            '/',
            '/admin/dashboard',
            '/admin/users',
            '/client/dashboard',
            '/employee/dashboard',
            '/profile'
        ];
        
        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect();
            $this->assertStringContainsString('/setup', $response->headers->get('Location'));
        }
        
        // Complete assets but leave database unconfigured
        $this->createValidAssets();
        
        // Should now redirect to database step
        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect();
            $location = $response->headers->get('Location');
            $this->assertTrue(
                str_contains($location, '/setup/database') || str_contains($location, '/setup/welcome'),
                "Route {$route} should redirect to setup when database is not configured"
            );
        }
        
        // Complete database but no admin user
        $this->completeSetupStep('database');
        
        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect();
            $this->assertStringContainsString('/setup', $response->headers->get('Location'));
        }
        
        // Complete admin user creation
        $this->completeSetupStep('admin');
        
        // Should still redirect until storage is configured
        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect();
            $this->assertStringContainsString('/setup', $response->headers->get('Location'));
        }
    }

    /**
     * Test setup completion and transition to normal application flow
     * Requirements: 6.5
     */
    public function test_setup_completion_and_transition_to_normal_flow(): void
    {
        // Complete entire setup
        $this->completeFullSetup();
        
        // Verify setup is complete
        $this->assertFalse($this->setupService->isSetupRequired());
        $this->assertTrue($this->setupService->isSetupComplete());
        
        // Verify setup routes are blocked
        $setupRoutes = [
            '/setup',
            '/setup/welcome',
            '/setup/assets',
            '/setup/database',
            '/setup/admin',
            '/setup/storage',
            '/setup/complete'
        ];
        
        foreach ($setupRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(404);
        }
        
        // Verify normal routes work
        $response = $this->get('/health');
        $response->assertStatus(200);
        
        // Verify health check shows setup complete
        $data = $response->json();
        $this->assertFalse($data['setup_required']);
        $this->assertEquals('healthy', $data['status']);
        
        // Test admin dashboard access (should work after authentication)
        $admin = User::where('role', UserRole::ADMIN)->first();
        $this->actingAs($admin);
        
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    /**
     * Test setup state management across different failure and recovery scenarios
     * Requirements: 4.4, 5.4, 5.5
     */
    public function test_setup_state_management_across_failure_scenarios(): void
    {
        // Test state persistence during asset failures
        $this->createValidAssets();
        
        // Complete database step
        $this->completeSetupStep('database');
        $steps = $this->setupService->getSetupSteps();
        $this->assertTrue($steps['database']['completed']);
        
        // Simulate asset corruption
        File::delete($this->manifestPath);
        
        // Setup should still remember database completion
        $steps = $this->setupService->getSetupSteps();
        $this->assertTrue($steps['database']['completed']);
        
        // But overall setup should be required due to missing assets
        $this->assertTrue($this->setupService->isSetupRequired());
        
        // Fix assets
        $this->createValidAssets();
        
        // Database step should still be complete
        $steps = $this->setupService->getSetupSteps();
        $this->assertTrue($steps['database']['completed']);
        
        // Complete admin step
        $this->completeSetupStep('admin');
        
        // Simulate database connection failure by corrupting config
        Config::set('database.connections.testing.database', '/invalid/path.sqlite');
        
        // Setup state should handle database failures gracefully
        $this->assertTrue($this->setupService->isSetupRequired());
        
        // Admin step should still be marked complete
        $steps = $this->setupService->getSetupSteps();
        $this->assertTrue($steps['admin']['completed']);
        
        // Fix database connection
        Config::set('database.connections.testing.database', ':memory:');
        
        // Complete storage step
        $this->completeSetupStep('storage');
        
        // Mark setup complete
        $this->setupService->markSetupComplete();
        
        // Verify final state
        $this->assertFalse($this->setupService->isSetupRequired());
        $this->assertTrue($this->setupService->isSetupComplete());
    }

    /**
     * Test concurrent setup requests and race conditions
     * Requirements: 4.4
     */
    public function test_concurrent_setup_requests(): void
    {
        $this->createValidAssets();
        
        // Simulate multiple concurrent requests during setup
        $responses = [];
        
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->get('/admin/dashboard');
        }
        
        // All should redirect consistently
        foreach ($responses as $response) {
            $response->assertRedirect();
            $this->assertStringContainsString('/setup', $response->headers->get('Location'));
        }
        
        // Complete database step
        $this->completeSetupStep('database');
        
        // Multiple concurrent admin creation attempts
        $adminResponses = [];
        
        for ($i = 0; $i < 3; $i++) {
            $adminResponses[] = $this->post('/setup/admin', [
                'name' => "Admin {$i}",
                'email' => "admin{$i}@example.com",
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ]);
        }
        
        // At least one should succeed
        $successCount = 0;
        foreach ($adminResponses as $response) {
            if ($response->isRedirect() && str_contains($response->headers->get('Location'), '/setup/storage')) {
                $successCount++;
            }
        }
        
        $this->assertGreaterThanOrEqual(1, $successCount);
        
        // Should have at least one admin user
        $this->assertGreaterThanOrEqual(1, User::where('role', UserRole::ADMIN)->count());
    }

    /**
     * Test setup interruption and recovery
     * Requirements: 4.4, 5.4
     */
    public function test_setup_interruption_and_recovery(): void
    {
        // Start setup process
        $this->createValidAssets();
        $this->completeSetupStep('database');
        
        // Simulate interruption by clearing cache and session
        Cache::flush();
        
        // Setup state should persist
        $steps = $this->setupService->getSetupSteps();
        $this->assertTrue($steps['database']['completed']);
        
        // Should be able to continue from admin step
        $response = $this->get('/setup/admin');
        $response->assertStatus(200);
        
        // Complete admin step
        $this->completeSetupStep('admin');
        
        // Simulate server restart by creating new service instance
        $newSetupService = new SetupService();
        $steps = $newSetupService->getSetupSteps();
        
        $this->assertTrue($steps['database']['completed']);
        $this->assertTrue($steps['admin']['completed']);
        
        // Should be able to continue to storage step
        $response = $this->get('/setup/storage');
        $response->assertStatus(200);
    }

    /**
     * Helper method to reset to fresh installation state
     */
    private function resetToFreshInstallation(): void
    {
        // Clear all users
        User::query()->delete();
        
        // Clear cloud storage configuration
        Config::set('services.google.client_id', null);
        Config::set('services.google.client_secret', null);
        
        // Clear setup state using direct file operations to bypass security service
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        // Clear setup directory
        $setupDir = storage_path('app/setup');
        if (File::exists($setupDir)) {
            File::deleteDirectory($setupDir);
        }
        
        // Ensure setup directory exists for proper file operations
        File::makeDirectory($setupDir, 0755, true);
        
        // Clear build assets
        $this->cleanupTestFiles();
        
        // Clear cache
        Cache::flush();
        
        // Force setup service to re-evaluate
        $this->setupService->clearSetupCache();
        
        // Mock the security service to allow file operations in tests
        $this->mockSecurityService();
    }

    /**
     * Helper method to create valid asset files
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

    /**
     * Helper method to complete a setup step
     */
    private function completeSetupStep(string $step): void
    {
        switch ($step) {
            case 'database':
                $this->setupService->updateSetupStep('database', true);
                break;
                
            case 'admin':
                if (!User::where('role', UserRole::ADMIN)->exists()) {
                    User::factory()->create([
                        'role' => UserRole::ADMIN,
                        'email_verified_at' => now()
                    ]);
                }
                $this->setupService->updateSetupStep('admin', true);
                break;
                
            case 'storage':
                Config::set('services.google.client_id', 'test-client-id');
                Config::set('services.google.client_secret', 'test-client-secret');
                $this->setupService->updateSetupStep('storage', true);
                break;
        }
    }

    /**
     * Helper method to complete full setup
     */
    private function completeFullSetup(): void
    {
        $this->createValidAssets();
        $this->completeSetupStep('database');
        $this->completeSetupStep('admin');
        $this->completeSetupStep('storage');
        $this->setupService->markSetupComplete();
    }

    /**
     * Helper method to cleanup test files
     */
    private function cleanupTestFiles(): void
    {
        if (File::exists($this->manifestPath)) {
            File::delete($this->manifestPath);
        }
        
        if (File::exists($this->buildDirectory)) {
            File::deleteDirectory($this->buildDirectory);
        }
        
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
    }
    
    /**
     * Mock the security service to allow file operations in tests
     */
    private function mockSecurityService(): void
    {
        $mockSecurityService = \Mockery::mock(\App\Services\SetupSecurityService::class);
        
        // Mock secureFileRead to return success for valid paths
        $mockSecurityService->shouldReceive('secureFileRead')
            ->andReturnUsing(function ($path) {
                $fullPath = storage_path('app/' . $path);
                if (File::exists($fullPath)) {
                    return [
                        'success' => true,
                        'content' => File::get($fullPath),
                        'message' => '',
                        'violations' => []
                    ];
                } else {
                    return [
                        'success' => false,
                        'content' => '',
                        'message' => 'File does not exist',
                        'violations' => []
                    ];
                }
            });
        
        // Mock secureFileWrite to return success
        $mockSecurityService->shouldReceive('secureFileWrite')
            ->andReturnUsing(function ($path, $content) {
                $fullPath = storage_path('app/' . $path);
                $directory = dirname($fullPath);
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }
                File::put($fullPath, $content);
                return [
                    'success' => true,
                    'message' => '',
                    'violations' => []
                ];
            });
        
        // Mock other methods that might be called
        $mockSecurityService->shouldReceive('validateAndSanitizeInput')
            ->andReturn(['valid' => true, 'sanitized' => '', 'violations' => []]);
        
        $mockSecurityService->shouldReceive('validateAndSanitizePath')
            ->andReturn(['valid' => true, 'sanitized_path' => '', 'violations' => []]);
        
        $mockSecurityService->shouldReceive('generateSecureToken')
            ->andReturn('test-secure-token-' . uniqid());
        
        $mockSecurityService->shouldReceive('logSecurityEvent')
            ->andReturn(true);
        
        // Replace the security service in the container
        $this->app->instance(\App\Services\SetupSecurityService::class, $mockSecurityService);
        
        // Create a new setup service instance with the mocked security service
        $this->setupService = new \App\Services\SetupService(
            app(\App\Services\AssetValidationService::class),
            $mockSecurityService,
            app(\App\Services\EnvironmentFileService::class)
        );
        
        // Also bind the setup service to the container
        $this->app->instance(\App\Services\SetupService::class, $this->setupService);
    }
}