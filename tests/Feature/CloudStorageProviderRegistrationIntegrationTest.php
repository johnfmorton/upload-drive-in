<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\CloudStorageFactory;
use App\Services\CloudStorageManager;
use App\Services\CloudConfigurationService;
use App\Services\GoogleDriveProvider;
use App\Providers\CloudStorageServiceProvider;

/**
 * Integration test for cloud storage provider registration and discovery system
 */
class CloudStorageProviderRegistrationIntegrationTest extends TestCase
{
    public function test_cloud_storage_service_provider_is_loaded(): void
    {
        // Assert
        $loadedProviders = array_keys($this->app->getLoadedProviders());
        $this->assertContains(CloudStorageServiceProvider::class, $loadedProviders);
    }

    public function test_core_services_are_registered_and_bound(): void
    {
        // Assert
        $this->assertTrue($this->app->bound(CloudStorageFactory::class));
        $this->assertTrue($this->app->bound(CloudStorageManager::class));
        $this->assertTrue($this->app->bound(CloudConfigurationService::class));
    }

    public function test_core_services_are_singletons(): void
    {
        // Act
        $factory1 = $this->app->make(CloudStorageFactory::class);
        $factory2 = $this->app->make(CloudStorageFactory::class);

        $manager1 = $this->app->make(CloudStorageManager::class);
        $manager2 = $this->app->make(CloudStorageManager::class);

        $config1 = $this->app->make(CloudConfigurationService::class);
        $config2 = $this->app->make(CloudConfigurationService::class);

        // Assert
        $this->assertSame($factory1, $factory2);
        $this->assertSame($manager1, $manager2);
        $this->assertSame($config1, $config2);
    }

    public function test_google_drive_provider_is_automatically_registered(): void
    {
        // Act
        $factory = $this->app->make(CloudStorageFactory::class);
        $registeredProviders = $factory->getRegisteredProviders();

        // Assert
        $this->assertArrayHasKey('google-drive', $registeredProviders);
        $this->assertEquals(GoogleDriveProvider::class, $registeredProviders['google-drive']);
    }

    public function test_provider_validation_works_correctly(): void
    {
        // Act
        $factory = $this->app->make(CloudStorageFactory::class);

        // Assert
        $this->assertTrue($factory->validateProvider(GoogleDriveProvider::class));
        $this->assertFalse($factory->validateProvider(\stdClass::class));
        $this->assertFalse($factory->validateProvider('NonExistentClass'));
    }

    public function test_provider_discovery_runs_without_errors(): void
    {
        // Act
        $factory = $this->app->make(CloudStorageFactory::class);
        $discovered = $factory->discoverProviders();

        // Assert
        $this->assertIsArray($discovered);
        // Discovery may or may not find additional providers, but it should not error
    }

    public function test_error_handler_interface_is_bound(): void
    {
        // Assert
        $this->assertTrue($this->app->bound(\App\Contracts\CloudStorageErrorHandlerInterface::class));
    }

    public function test_can_resolve_cloud_storage_manager_with_dependencies(): void
    {
        // Act
        $manager = $this->app->make(CloudStorageManager::class);

        // Assert
        $this->assertInstanceOf(CloudStorageManager::class, $manager);
    }

    public function test_provider_registration_system_is_functional(): void
    {
        // Arrange
        $factory = $this->app->make(CloudStorageFactory::class);

        // Act - Register a test provider (using GoogleDriveProvider as a valid implementation)
        $factory->register('test-provider', GoogleDriveProvider::class);
        $registeredProviders = $factory->getRegisteredProviders();

        // Assert
        $this->assertArrayHasKey('test-provider', $registeredProviders);
        $this->assertEquals(GoogleDriveProvider::class, $registeredProviders['test-provider']);
    }

    public function test_service_provider_provides_correct_services(): void
    {
        // Arrange
        $provider = new CloudStorageServiceProvider($this->app);

        // Act
        $providedServices = $provider->provides();

        // Assert
        $expectedServices = [
            CloudStorageFactory::class,
            CloudStorageManager::class,
            CloudConfigurationService::class,
        ];

        $this->assertEquals($expectedServices, $providedServices);
    }

    public function test_application_boots_successfully_with_cloud_storage_provider(): void
    {
        // This test verifies that the application can boot successfully
        // with the CloudStorageServiceProvider registered

        // Act & Assert - If we get here, the application booted successfully
        $this->assertTrue(true);
    }
}