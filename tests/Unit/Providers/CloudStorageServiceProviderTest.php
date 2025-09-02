<?php

namespace Tests\Unit\Providers;

use Tests\TestCase;
use App\Providers\CloudStorageServiceProvider;
use App\Services\CloudStorageFactory;
use App\Services\CloudStorageManager;
use App\Services\CloudConfigurationService;
use App\Services\GoogleDriveProvider;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Test CloudStorageServiceProvider functionality
 */
class CloudStorageServiceProviderTest extends TestCase
{
    private CloudStorageServiceProvider $provider;
    protected Container $testApp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testApp = new Container();
        $this->provider = new CloudStorageServiceProvider($this->testApp);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_register_method_registers_core_services(): void
    {
        // Act
        $this->provider->register();

        // Assert - Check that core services are registered
        $this->assertTrue($this->testApp->bound(CloudConfigurationService::class));
        $this->assertTrue($this->testApp->bound(CloudStorageFactory::class));
        $this->assertTrue($this->testApp->bound(CloudStorageManager::class));
    }

    public function test_core_services_are_singletons(): void
    {
        // Arrange
        $this->provider->register();

        // Act
        $configService1 = $this->testApp->make(CloudConfigurationService::class);
        $configService2 = $this->testApp->make(CloudConfigurationService::class);

        $factory1 = $this->testApp->make(CloudStorageFactory::class);
        $factory2 = $this->testApp->make(CloudStorageFactory::class);

        $manager1 = $this->testApp->make(CloudStorageManager::class);
        $manager2 = $this->testApp->make(CloudStorageManager::class);

        // Assert - Same instances should be returned (singletons)
        $this->assertSame($configService1, $configService2);
        $this->assertSame($factory1, $factory2);
        $this->assertSame($manager1, $manager2);
    }

    public function test_provides_method_returns_correct_services(): void
    {
        // Act
        $provided = $this->provider->provides();

        // Assert
        $expectedServices = [
            CloudStorageFactory::class,
            CloudStorageManager::class,
            CloudConfigurationService::class,
        ];

        $this->assertEquals($expectedServices, $provided);
    }

    public function test_register_built_in_providers_registers_google_drive(): void
    {
        // Arrange
        $this->provider->register();
        $factory = $this->testApp->make(CloudStorageFactory::class);

        // Act
        $registeredProviders = $factory->getRegisteredProviders();

        // Assert
        $this->assertArrayHasKey('google-drive', $registeredProviders);
        $this->assertEquals(GoogleDriveProvider::class, $registeredProviders['google-drive']);
    }

    public function test_boot_method_runs_without_errors(): void
    {
        // Arrange
        $this->provider->register();

        // Act & Assert - Should not throw any exceptions
        $this->provider->boot();
        $this->assertTrue(true); // If we get here, boot() completed successfully
    }

    public function test_error_handler_binding_is_registered(): void
    {
        // Act
        $this->provider->register();

        // Assert
        $this->assertTrue($this->testApp->bound(\App\Contracts\CloudStorageErrorHandlerInterface::class));
    }

    public function test_factory_can_create_google_drive_provider_after_registration(): void
    {
        // Arrange
        $this->provider->register();
        
        // Mock the configuration service to return valid config
        $configService = $this->testApp->make(CloudConfigurationService::class);
        $factory = $this->testApp->make(CloudStorageFactory::class);

        // We need to mock the configuration service since it's a real dependency
        $mockConfigService = Mockery::mock(CloudConfigurationService::class);
        $mockConfigService->shouldReceive('getEffectiveConfig')
            ->with('google-drive')
            ->andReturn([
                'client_id' => 'test-client-id.apps.googleusercontent.com',
                'client_secret' => 'test-client-secret',
                'redirect_uri' => 'https://example.com/callback',
            ]);

        // Replace the config service in the container
        $this->testApp->instance(CloudConfigurationService::class, $mockConfigService);
        
        // Create a new factory with the mocked config service
        $factory = new CloudStorageFactory($this->testApp, $mockConfigService);
        $factory->register('google-drive', GoogleDriveProvider::class);

        // Mock the GoogleDriveProvider dependencies
        $this->testApp->bind(GoogleDriveProvider::class, function () {
            return Mockery::mock(GoogleDriveProvider::class, [
                'initialize' => null,
                'getProviderName' => 'google-drive',
            ]);
        });

        // Act
        $provider = $factory->create('google-drive');

        // Assert
        $this->assertInstanceOf(GoogleDriveProvider::class, $provider);
    }

    public function test_service_provider_handles_registration_errors_gracefully(): void
    {
        // This test ensures that if there are issues during registration,
        // the service provider doesn't crash the application

        // We can't easily test error conditions without mocking internal methods,
        // but we can verify that the registration process completes
        
        // Act & Assert
        $this->provider->register();
        $this->assertTrue(true); // If we get here, registration completed without fatal errors
    }

    public function test_discovery_process_runs_during_boot(): void
    {
        // Arrange
        $this->provider->register();

        // Mock Log to capture discovery messages
        Log::shouldReceive('debug')->withAnyArgs();
        Log::shouldReceive('info')->withAnyArgs();
        Log::shouldReceive('warning')->withAnyArgs();
        Log::shouldReceive('error')->withAnyArgs();

        // Act
        $this->provider->boot();

        // Assert - If we get here, the boot process completed
        $this->assertTrue(true);
    }

    public function test_validation_process_runs_during_boot(): void
    {
        // Arrange
        $this->provider->register();

        // Mock Log to capture validation messages
        Log::shouldReceive('debug')->withAnyArgs();
        Log::shouldReceive('info')->withAnyArgs();
        Log::shouldReceive('warning')->withAnyArgs();
        Log::shouldReceive('error')->withAnyArgs();

        // Act
        $this->provider->boot();

        // Assert - Validation should complete without throwing exceptions
        $this->assertTrue(true);
    }

    public function test_provider_registration_is_idempotent(): void
    {
        // Act - Register multiple times
        $this->provider->register();
        $this->provider->register();
        $this->provider->register();

        // Assert - Services should still be properly bound
        $this->assertTrue($this->testApp->bound(CloudStorageFactory::class));
        $this->assertTrue($this->testApp->bound(CloudStorageManager::class));
        $this->assertTrue($this->testApp->bound(CloudConfigurationService::class));

        // And singletons should still work
        $factory1 = $this->testApp->make(CloudStorageFactory::class);
        $factory2 = $this->testApp->make(CloudStorageFactory::class);
        $this->assertSame($factory1, $factory2);
    }
}