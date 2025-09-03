<?php

namespace Tests\Unit\Services;

use App\Enums\ProviderAvailabilityStatus;
use App\Services\CloudStorageProviderAvailabilityService;
use Tests\TestCase;

class CloudStorageProviderAvailabilityServiceTest extends TestCase
{
    private CloudStorageProviderAvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CloudStorageProviderAvailabilityService();
    }

    public function test_get_available_providers_returns_only_fully_available()
    {
        $availableProviders = $this->service->getAvailableProviders();
        
        $this->assertIsArray($availableProviders);
        $this->assertContains('google-drive', $availableProviders);
        $this->assertNotContains('s3', $availableProviders);
        $this->assertNotContains('onedrive', $availableProviders);
        $this->assertNotContains('dropbox', $availableProviders);
    }

    public function test_get_coming_soon_providers_returns_correct_providers()
    {
        $comingSoonProviders = $this->service->getComingSoonProviders();
        
        $this->assertIsArray($comingSoonProviders);
        $this->assertContains('s3', $comingSoonProviders);
        $this->assertContains('onedrive', $comingSoonProviders);
        $this->assertContains('dropbox', $comingSoonProviders);
        $this->assertNotContains('google-drive', $comingSoonProviders);
    }

    public function test_is_provider_fully_functional_returns_correct_status()
    {
        $this->assertTrue($this->service->isProviderFullyFunctional('google-drive'));
        $this->assertFalse($this->service->isProviderFullyFunctional('s3'));
        $this->assertFalse($this->service->isProviderFullyFunctional('onedrive'));
        $this->assertFalse($this->service->isProviderFullyFunctional('dropbox'));
        $this->assertFalse($this->service->isProviderFullyFunctional('unknown-provider'));
    }

    public function test_get_provider_availability_status_returns_correct_string()
    {
        $this->assertEquals('fully_available', $this->service->getProviderAvailabilityStatus('google-drive'));
        $this->assertEquals('coming_soon', $this->service->getProviderAvailabilityStatus('s3'));
        $this->assertEquals('coming_soon', $this->service->getProviderAvailabilityStatus('onedrive'));
        $this->assertEquals('coming_soon', $this->service->getProviderAvailabilityStatus('dropbox'));
        $this->assertEquals('deprecated', $this->service->getProviderAvailabilityStatus('unknown-provider'));
    }

    public function test_get_provider_availability_status_enum_returns_correct_enum()
    {
        $this->assertEquals(ProviderAvailabilityStatus::FULLY_AVAILABLE, $this->service->getProviderAvailabilityStatusEnum('google-drive'));
        $this->assertEquals(ProviderAvailabilityStatus::COMING_SOON, $this->service->getProviderAvailabilityStatusEnum('s3'));
        $this->assertEquals(ProviderAvailabilityStatus::COMING_SOON, $this->service->getProviderAvailabilityStatusEnum('onedrive'));
        $this->assertEquals(ProviderAvailabilityStatus::COMING_SOON, $this->service->getProviderAvailabilityStatusEnum('dropbox'));
        $this->assertEquals(ProviderAvailabilityStatus::DEPRECATED, $this->service->getProviderAvailabilityStatusEnum('unknown-provider'));
    }

    public function test_get_all_providers_with_status_returns_complete_data()
    {
        $providersWithStatus = $this->service->getAllProvidersWithStatus();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $providersWithStatus);
        $this->assertGreaterThanOrEqual(4, $providersWithStatus->count()); // At least 4 providers
        
        // Test Google Drive data
        $googleDriveData = $providersWithStatus->get('google-drive');
        $this->assertNotNull($googleDriveData);
        $this->assertEquals(ProviderAvailabilityStatus::FULLY_AVAILABLE, $googleDriveData['status']);
        $this->assertEquals('Google Drive', $googleDriveData['label']);
        $this->assertTrue($googleDriveData['selectable']);
        $this->assertTrue($googleDriveData['visible']);
        $this->assertEquals('Available', $googleDriveData['status_label']);
        
        // Test that we have coming soon providers
        $comingSoonProviders = $providersWithStatus->filter(fn($data) => $data['status'] === ProviderAvailabilityStatus::COMING_SOON);
        $this->assertGreaterThan(0, $comingSoonProviders->count());
    }

    public function test_get_visible_providers_excludes_deprecated()
    {
        $visibleProviders = $this->service->getVisibleProviders();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $visibleProviders);
        $this->assertGreaterThanOrEqual(4, $visibleProviders->count()); // At least 4 providers are visible
        
        // Google Drive should be visible
        $this->assertTrue($visibleProviders->has('google-drive'));
        
        // All visible providers should have visible = true
        foreach ($visibleProviders as $providerData) {
            $this->assertTrue($providerData['visible']);
        }
    }

    public function test_get_selectable_providers_returns_only_available()
    {
        $selectableProviders = $this->service->getSelectableProviders();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $selectableProviders);
        $this->assertCount(1, $selectableProviders); // Only Google Drive is selectable
        
        $this->assertTrue($selectableProviders->has('google-drive'));
        $this->assertFalse($selectableProviders->has('s3'));
        $this->assertFalse($selectableProviders->has('onedrive'));
        $this->assertFalse($selectableProviders->has('dropbox'));
    }

    public function test_get_default_provider_returns_first_available()
    {
        $defaultProvider = $this->service->getDefaultProvider();
        
        $this->assertEquals('google-drive', $defaultProvider);
    }

    public function test_is_valid_provider_selection_validates_correctly()
    {
        $this->assertTrue($this->service->isValidProviderSelection('google-drive'));
        $this->assertFalse($this->service->isValidProviderSelection('s3'));
        $this->assertFalse($this->service->isValidProviderSelection('onedrive'));
        $this->assertFalse($this->service->isValidProviderSelection('dropbox'));
        $this->assertFalse($this->service->isValidProviderSelection('unknown-provider'));
    }

    public function test_get_provider_configuration_for_frontend_returns_correct_format()
    {
        $frontendConfig = $this->service->getProviderConfigurationForFrontend();
        
        $this->assertIsArray($frontendConfig);
        $this->assertGreaterThanOrEqual(4, count($frontendConfig)); // At least 4 providers
        
        // Test Google Drive configuration
        $this->assertArrayHasKey('google-drive', $frontendConfig);
        $googleDriveConfig = $frontendConfig['google-drive'];
        $this->assertEquals('Google Drive', $googleDriveConfig['label']);
        $this->assertEquals('fully_available', $googleDriveConfig['status']);
        $this->assertEquals('Available', $googleDriveConfig['status_label']);
        $this->assertTrue($googleDriveConfig['selectable']);
        $this->assertTrue($googleDriveConfig['default']);
        
        // Test that we have coming soon providers
        $comingSoonProviders = array_filter($frontendConfig, fn($config) => $config['status'] === 'coming_soon');
        $this->assertGreaterThan(0, count($comingSoonProviders));
        
        // Test that all configs have required keys
        foreach ($frontendConfig as $providerName => $config) {
            $this->assertArrayHasKey('label', $config);
            $this->assertArrayHasKey('status', $config);
            $this->assertArrayHasKey('status_label', $config);
            $this->assertArrayHasKey('selectable', $config);
            $this->assertArrayHasKey('default', $config);
        }
    }

    public function test_update_provider_status_changes_status()
    {
        // Initially S3 is coming soon
        $this->assertEquals(ProviderAvailabilityStatus::COMING_SOON, $this->service->getProviderAvailabilityStatusEnum('s3'));
        
        // Update S3 to fully available
        $this->service->updateProviderStatus('s3', ProviderAvailabilityStatus::FULLY_AVAILABLE);
        
        // Verify the change
        $this->assertEquals(ProviderAvailabilityStatus::FULLY_AVAILABLE, $this->service->getProviderAvailabilityStatusEnum('s3'));
        $this->assertTrue($this->service->isProviderFullyFunctional('s3'));
        $this->assertTrue($this->service->isValidProviderSelection('s3'));
        
        // Verify it's now in available providers
        $availableProviders = $this->service->getAvailableProviders();
        $this->assertContains('s3', $availableProviders);
    }

    public function test_provider_availability_status_enum_methods()
    {
        // Test label method
        $this->assertEquals('Available', ProviderAvailabilityStatus::FULLY_AVAILABLE->label());
        $this->assertEquals('Coming Soon', ProviderAvailabilityStatus::COMING_SOON->label());
        $this->assertEquals('Deprecated', ProviderAvailabilityStatus::DEPRECATED->label());
        $this->assertEquals('Under Maintenance', ProviderAvailabilityStatus::MAINTENANCE->label());
        
        // Test isSelectable method
        $this->assertTrue(ProviderAvailabilityStatus::FULLY_AVAILABLE->isSelectable());
        $this->assertFalse(ProviderAvailabilityStatus::COMING_SOON->isSelectable());
        $this->assertFalse(ProviderAvailabilityStatus::DEPRECATED->isSelectable());
        $this->assertFalse(ProviderAvailabilityStatus::MAINTENANCE->isSelectable());
        
        // Test isVisible method
        $this->assertTrue(ProviderAvailabilityStatus::FULLY_AVAILABLE->isVisible());
        $this->assertTrue(ProviderAvailabilityStatus::COMING_SOON->isVisible());
        $this->assertFalse(ProviderAvailabilityStatus::DEPRECATED->isVisible());
        $this->assertTrue(ProviderAvailabilityStatus::MAINTENANCE->isVisible());
    }
}