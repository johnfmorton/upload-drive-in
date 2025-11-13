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
        
        // Google Drive and Amazon S3 should be available based on config
        $this->assertTrue($service->isProviderFullyFunctional('google-drive'));
        $this->assertTrue($service->isProviderFullyFunctional('amazon-s3'));
        $this->assertEquals('google-drive', $service->getDefaultProvider());
        
        // Other providers should be coming soon based on config
        $comingSoonProviders = $service->getComingSoonProviders();
        $this->assertContains('microsoft-teams', $comingSoonProviders);
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
        
        // Valid selections
        $this->assertTrue($service->isValidProviderSelection('google-drive'));
        $this->assertTrue($service->isValidProviderSelection('amazon-s3'));
        
        // Invalid selections (coming soon providers)
        $this->assertFalse($service->isValidProviderSelection('microsoft-teams'));
        
        // Unknown provider
        $this->assertFalse($service->isValidProviderSelection('unknown-provider'));
    }

    public function test_service_handles_configuration_changes()
    {
        // Temporarily change configuration
        config(['cloud-storage.provider_availability.microsoft-teams' => 'fully_available']);
        
        // Create new service instance to pick up config changes
        $service = new CloudStorageProviderAvailabilityService();
        
        // Microsoft Teams should now be available
        $this->assertTrue($service->isProviderFullyFunctional('microsoft-teams'));
        $this->assertTrue($service->isValidProviderSelection('microsoft-teams'));
        
        $availableProviders = $service->getAvailableProviders();
        $this->assertContains('microsoft-teams', $availableProviders);
    }
}