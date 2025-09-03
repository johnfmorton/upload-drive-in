<?php

namespace Tests\Feature;

use App\Services\CloudStorageProviderAvailabilityService;
use App\Enums\ProviderAvailabilityStatus;
use Tests\TestCase;

class CloudStorageProviderAvailabilityServiceIntegrationTest extends TestCase
{
    public function test_service_can_be_resolved_from_container()
    {
        $service = $this->app->make(CloudStorageProviderAvailabilityService::class);
        
        $this->assertInstanceOf(CloudStorageProviderAvailabilityService::class, $service);
    }

    public function test_service_loads_configuration_correctly()
    {
        $service = $this->app->make(CloudStorageProviderAvailabilityService::class);
        
        // Google Drive should be available based on config
        $this->assertTrue($service->isProviderFullyFunctional('google-drive'));
        $this->assertEquals('google-drive', $service->getDefaultProvider());
        
        // Other providers should be coming soon based on config
        $comingSoonProviders = $service->getComingSoonProviders();
        $this->assertContains('amazon-s3', $comingSoonProviders);
        $this->assertContains('microsoft-teams', $comingSoonProviders);
        $this->assertContains('dropbox', $comingSoonProviders);
    }

    public function test_service_provides_frontend_configuration()
    {
        $service = $this->app->make(CloudStorageProviderAvailabilityService::class);
        
        $frontendConfig = $service->getProviderConfigurationForFrontend();
        
        $this->assertIsArray($frontendConfig);
        $this->assertArrayHasKey('google-drive', $frontendConfig);
        
        // Google Drive should be selectable and default
        $googleDriveConfig = $frontendConfig['google-drive'];
        $this->assertTrue($googleDriveConfig['selectable']);
        $this->assertTrue($googleDriveConfig['default']);
        $this->assertEquals('fully_available', $googleDriveConfig['status']);
    }

    public function test_service_validates_provider_selections()
    {
        $service = $this->app->make(CloudStorageProviderAvailabilityService::class);
        
        // Valid selection
        $this->assertTrue($service->isValidProviderSelection('google-drive'));
        
        // Invalid selections (coming soon providers)
        $this->assertFalse($service->isValidProviderSelection('amazon-s3'));
        $this->assertFalse($service->isValidProviderSelection('microsoft-teams'));
        $this->assertFalse($service->isValidProviderSelection('dropbox'));
        
        // Unknown provider
        $this->assertFalse($service->isValidProviderSelection('unknown-provider'));
    }

    public function test_service_handles_configuration_changes()
    {
        // Temporarily change configuration
        config(['cloud-storage.provider_availability.amazon-s3' => 'fully_available']);
        
        // Create new service instance to pick up config changes
        $service = new CloudStorageProviderAvailabilityService();
        
        // Amazon S3 should now be available
        $this->assertTrue($service->isProviderFullyFunctional('amazon-s3'));
        $this->assertTrue($service->isValidProviderSelection('amazon-s3'));
        
        $availableProviders = $service->getAvailableProviders();
        $this->assertContains('amazon-s3', $availableProviders);
    }
}