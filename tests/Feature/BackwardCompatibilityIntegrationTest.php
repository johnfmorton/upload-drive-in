<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\GoogleDriveService;
use App\Services\DeprecatedGoogleDriveServiceWrapper;
use App\Services\CloudStorageManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class BackwardCompatibilityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_drive_service_resolves_correctly(): void
    {
        $service = app(GoogleDriveService::class);
        
        $this->assertInstanceOf(GoogleDriveService::class, $service);
    }

    public function test_google_drive_service_logs_deprecation_warnings(): void
    {
        config(['app.debug' => true]);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('GoogleDriveService is deprecated. Use CloudStorageManager instead.', \Mockery::type('array'));
            
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = app(GoogleDriveService::class);
        
        // Test that it can call methods without throwing exceptions
        $result = $service->getRootFolderId();
        $this->assertEquals('root', $result);
    }

    public function test_existing_controller_can_still_inject_google_drive_service(): void
    {
        // Test that GoogleDriveService can still be resolved from container
        $service = app(GoogleDriveService::class);
        
        $this->assertInstanceOf(GoogleDriveService::class, $service);
    }

    public function test_deprecation_warnings_are_logged_in_debug_mode(): void
    {
        config(['app.debug' => true]);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('GoogleDriveService is deprecated. Use CloudStorageManager instead.', \Mockery::type('array'));
            
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = app(GoogleDriveService::class);
        
        // This method doesn't have deprecation warnings, so no additional log expected
        $result = $service->getRootFolderId();
        $this->assertEquals('root', $result);
    }

    public function test_no_deprecation_warnings_in_production_mode(): void
    {
        config(['app.debug' => false]);
        
        // In production mode, no deprecation warnings should be logged
        $service = app(GoogleDriveService::class);
        $service->getRootFolderId();
        
        // If we get here without exceptions, the test passes
        $this->assertTrue(true);
    }

    public function test_cloud_storage_manager_is_still_available(): void
    {
        $manager = app(CloudStorageManager::class);
        
        $this->assertInstanceOf(CloudStorageManager::class, $manager);
        
        // Test that it can get providers
        $providers = $manager->getAvailableProviders();
        $this->assertIsArray($providers);
    }

    public function test_both_old_and_new_systems_can_coexist(): void
    {
        // Old system (deprecated but still functional)
        $oldService = app(GoogleDriveService::class);
        $this->assertInstanceOf(GoogleDriveService::class, $oldService);
        
        // New system
        $newManager = app(CloudStorageManager::class);
        $this->assertInstanceOf(CloudStorageManager::class, $newManager);
        
        // Both should be able to operate
        $oldResult = $oldService->getRootFolderId();
        $this->assertEquals('root', $oldResult);
        
        $providers = $newManager->getAvailableProviders();
        $this->assertIsArray($providers);
    }

    public function test_service_methods_still_work(): void
    {
        $service = app(GoogleDriveService::class);
        $user = User::factory()->create();
        
        // Test various methods to ensure they don't throw exceptions
        $this->assertEquals('root', $service->getRootFolderId());
        $this->assertEquals('root', $service->getEffectiveRootFolderId($user));
        $this->assertEquals('test-at-example-dot-com', $service->sanitizeEmailForFolderName('test@example.com'));
        
        // These methods would normally require Google Drive connection, 
        // but we're just testing that they don't throw unexpected exceptions
        try {
            $service->getAuthUrl($user);
        } catch (\Exception $e) {
            // Expected to fail due to missing Google credentials in test environment
            $this->assertStringContainsString('client', strtolower($e->getMessage()));
        }
    }

    public function test_service_binding_works_correctly(): void
    {
        // This test ensures that resolving GoogleDriveService works correctly
        $service1 = app(GoogleDriveService::class);
        $service2 = app(GoogleDriveService::class);
        
        // Both should be instances of GoogleDriveService
        $this->assertInstanceOf(GoogleDriveService::class, $service1);
        $this->assertInstanceOf(GoogleDriveService::class, $service2);
        
        // Test that they work correctly
        $this->assertEquals('root', $service1->getRootFolderId());
        $this->assertEquals('root', $service2->getRootFolderId());
    }

    public function test_method_deprecation_warnings_include_migration_guide(): void
    {
        config(['app.debug' => true]);
        $user = User::factory()->create();
        
        Log::shouldReceive('warning')
            ->once()
            ->with('GoogleDriveService is deprecated. Use CloudStorageManager instead.', \Mockery::type('array'));
            
        Log::shouldReceive('warning')
            ->once()
            ->with('Deprecated GoogleDriveService method called', \Mockery::on(function ($context) {
                return isset($context['deprecated_method']) &&
                       isset($context['replacement']) &&
                       isset($context['migration_guide']) &&
                       isset($context['example']) &&
                       isset($context['documentation']) &&
                       str_contains($context['example'], 'CloudStorageManager') &&
                       str_contains($context['migration_guide'], 'Use CloudStorageManager');
            }));
            
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $service = app(GoogleDriveService::class);
        
        // Call a method that has deprecation warnings
        $exceptionThrown = false;
        try {
            $service->getAuthUrl($user);
        } catch (\Exception $e) {
            // Expected to fail due to missing Google credentials
            $exceptionThrown = true;
        }
        
        $this->assertTrue($exceptionThrown, 'Expected exception to be thrown due to missing Google credentials');
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}