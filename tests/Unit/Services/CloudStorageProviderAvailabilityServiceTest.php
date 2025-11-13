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
        $this->assertContains('amazon-s3', $availableProviders);
    }

    public function test_get_coming_soon_providers_returns_correct_providers()
    {
        $comingSoonProviders = $this->service->getComingSoonProviders();
        
        $this->assertIsArray($comingSoonProviders);
        $this->assertContains('microsoft-teams', $comingSoonProviders);
        $this->assertNotContains('google-drive', $comingSoonProviders);
        $this->assertNotContains('amazon-s3', $comingSoonProviders);
    }

    public function test_is_provider_fully_functional_returns_correct_status()
    {
        $this->assertTrue($this->service->isProviderFullyFunctional('google-drive'));
        $this->assertTrue($this->service->isProviderFullyFunctional('amazon-s3'));
        $this->assertFalse($this->service->isProviderFullyFunctional('microsoft-teams'));
        $this->assertFalse($this->service->isProviderFullyFunctional('unknown-provider'));
    }

    public function test_get_provider_availability_status_returns_correct_string()
    {
        $this->assertEquals('fully_available', $this->service->getProviderAvailabilityStatus('google-drive'));
        $this->assertEquals('fully_available', $this->service->getProviderAvailabilityStatus('amazon-s3'));
        $this->assertEquals('coming_soon', $this->service->getProviderAvailabilityStatus('microsoft-teams'));
        $this->assertEquals('deprecated', $this->service->getProviderAvailabilityStatus('unknown-provider'));
    }

    public function test_get_provider_availability_status_enum_returns_correct_enum()
    {
        $this->assertEquals(ProviderAvailabilityStatus::FULLY_AVAILABLE, $this->service->getProviderAvailabilityStatusEnum('google-drive'));
        $this->assertEquals(ProviderAvailabilityStatus::FULLY_AVAILABLE, $this->service->getProviderAvailabilityStatusEnum('amazon-s3'));
        $this->assertEquals(ProviderAvailabilityStatus::COMING_SOON, $this->service->getProviderAvailabilityStatusEnum('microsoft-teams'));
        $this->assertEquals(ProviderAvailabilityStatus::DEPRECATED, $this->service->getProviderAvailabilityStatusEnum('unknown-provider'));
    }

    public function test_get_all_providers_with_status_returns_complete_data()
    {
        $providersWithStatus = $this->service->getAllProvidersWithStatus();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $providersWithStatus);
        $this->assertGreaterThanOrEqual(3, $providersWithStatus->count()); // At least 3 providers
        
        // Test Google Drive data
        $googleDriveData = $providersWithStatus->get('google-drive');
        $this->assertNotNull($googleDriveData);
        $this->assertEquals(ProviderAvailabilityStatus::FULLY_AVAILABLE, $googleDriveData['status']);
        $this->assertEquals('Google Drive', $googleDriveData['label']);
        $this->assertTrue($googleDriveData['selectable']);
        $this->assertTrue($googleDriveData['visible']);
        $this->assertEquals('Available', $googleDriveData['status_label']);
        
        // Test Amazon S3 data
        $s3Data = $providersWithStatus->get('amazon-s3');
        $this->assertNotNull($s3Data);
        $this->assertEquals(ProviderAvailabilityStatus::FULLY_AVAILABLE, $s3Data['status']);
        $this->assertEquals('Amazon S3', $s3Data['label']);
        $this->assertTrue($s3Data['selectable']);
        $this->assertTrue($s3Data['visible']);
        $this->assertEquals('Available', $s3Data['status_label']);
        
        // Test that we have coming soon providers
        $comingSoonProviders = $providersWithStatus->filter(fn($data) => $data['status'] === ProviderAvailabilityStatus::COMING_SOON);
        $this->assertGreaterThan(0, $comingSoonProviders->count());
    }

    public function test_get_visible_providers_excludes_deprecated()
    {
        $visibleProviders = $this->service->getVisibleProviders();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $visibleProviders);
        $this->assertGreaterThanOrEqual(3, $visibleProviders->count()); // At least 3 providers are visible
        
        // Google Drive and Amazon S3 should be visible
        $this->assertTrue($visibleProviders->has('google-drive'));
        $this->assertTrue($visibleProviders->has('amazon-s3'));
        
        // All visible providers should have visible = true
        foreach ($visibleProviders as $providerData) {
            $this->assertTrue($providerData['visible']);
        }
    }

    public function test_get_selectable_providers_returns_only_available()
    {
        $selectableProviders = $this->service->getSelectableProviders();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $selectableProviders);
        $this->assertCount(2, $selectableProviders); // Google Drive and Amazon S3 are selectable
        
        $this->assertTrue($selectableProviders->has('google-drive'));
        $this->assertTrue($selectableProviders->has('amazon-s3'));
        $this->assertFalse($selectableProviders->has('microsoft-teams'));
    }

    public function test_get_default_provider_returns_first_available()
    {
        $defaultProvider = $this->service->getDefaultProvider();
        
        $this->assertEquals('google-drive', $defaultProvider);
    }

    public function test_is_valid_provider_selection_validates_correctly()
    {
        $this->assertTrue($this->service->isValidProviderSelection('google-drive'));
        $this->assertTrue($this->service->isValidProviderSelection('amazon-s3'));
        $this->assertFalse($this->service->isValidProviderSelection('microsoft-teams'));
        $this->assertFalse($this->service->isValidProviderSelection('unknown-provider'));
    }

    public function test_get_provider_configuration_for_frontend_returns_correct_format()
    {
        $frontendConfig = $this->service->getProviderConfigurationForFrontend();
        
        $this->assertIsArray($frontendConfig);
        $this->assertGreaterThanOrEqual(3, count($frontendConfig)); // At least 3 providers
        
        // Test Google Drive configuration
        $this->assertArrayHasKey('google-drive', $frontendConfig);
        $googleDriveConfig = $frontendConfig['google-drive'];
        $this->assertEquals('Google Drive', $googleDriveConfig['label']);
        $this->assertEquals('fully_available', $googleDriveConfig['status']);
        $this->assertEquals('Available', $googleDriveConfig['status_label']);
        $this->assertTrue($googleDriveConfig['selectable']);
        $this->assertTrue($googleDriveConfig['default']);
        
        // Test Amazon S3 configuration
        $this->assertArrayHasKey('amazon-s3', $frontendConfig);
        $s3Config = $frontendConfig['amazon-s3'];
        $this->assertEquals('Amazon S3', $s3Config['label']);
        $this->assertEquals('fully_available', $s3Config['status']);
        $this->assertEquals('Available', $s3Config['status_label']);
        $this->assertTrue($s3Config['selectable']);
        $this->assertFalse($s3Config['default']); // Google Drive is default
        
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
        // Initially Microsoft Teams is coming soon
        $this->assertEquals(ProviderAvailabilityStatus::COMING_SOON, $this->service->getProviderAvailabilityStatusEnum('microsoft-teams'));
        
        // Update Microsoft Teams to fully available
        $this->service->updateProviderStatus('microsoft-teams', ProviderAvailabilityStatus::FULLY_AVAILABLE);
        
        // Verify the change
        $this->assertEquals(ProviderAvailabilityStatus::FULLY_AVAILABLE, $this->service->getProviderAvailabilityStatusEnum('microsoft-teams'));
        $this->assertTrue($this->service->isProviderFullyFunctional('microsoft-teams'));
        $this->assertTrue($this->service->isValidProviderSelection('microsoft-teams'));
        
        // Verify it's now in available providers
        $availableProviders = $this->service->getAvailableProviders();
        $this->assertContains('microsoft-teams', $availableProviders);
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