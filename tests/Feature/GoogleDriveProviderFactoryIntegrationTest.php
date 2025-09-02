<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\CloudStorageFactory;
use App\Services\GoogleDriveProvider;
use App\Contracts\CloudStorageProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration test to verify GoogleDriveProvider works correctly with CloudStorageFactory
 */
class GoogleDriveProviderFactoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = app(CloudStorageFactory::class);
    }

    public function test_google_drive_provider_is_registered_automatically(): void
    {
        // Act
        $registeredProviders = $this->factory->getRegisteredProviders();

        // Assert
        $this->assertArrayHasKey('google-drive', $registeredProviders);
        $this->assertEquals(GoogleDriveProvider::class, $registeredProviders['google-drive']);
    }

    public function test_google_drive_provider_can_be_validated(): void
    {
        // Act
        $isValid = $this->factory->validateProvider(GoogleDriveProvider::class);

        // Assert
        $this->assertTrue($isValid);
    }

    public function test_google_drive_provider_implements_interface(): void
    {
        // Arrange
        $config = [
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'test-client-secret-123456789',
            'redirect_uri' => 'https://example.com/callback',
        ];

        // Act - Create provider directly (bypassing factory creation which requires full dependencies)
        $provider = new GoogleDriveProvider(
            app(\App\Services\GoogleDriveService::class),
            app(\App\Services\GoogleDriveErrorHandler::class),
            app(\App\Services\CloudStorageLogService::class)
        );

        // Assert
        $this->assertInstanceOf(CloudStorageProviderInterface::class, $provider);
        $this->assertEquals('google-drive', $provider->getProviderName());
        
        // Test enhanced interface methods
        $this->assertEquals('oauth', $provider->getAuthenticationType());
        $this->assertEquals('hierarchical', $provider->getStorageModel());
        $this->assertEquals(5368709120, $provider->getMaxFileSize()); // 5GB
        $this->assertEquals(['*'], $provider->getSupportedFileTypes());
        
        // Test capabilities
        $capabilities = $provider->getCapabilities();
        $this->assertIsArray($capabilities);
        $this->assertTrue($capabilities['folder_creation']);
        $this->assertTrue($capabilities['file_upload']);
        $this->assertTrue($capabilities['oauth_authentication']);
        $this->assertFalse($capabilities['presigned_urls']);
        
        // Test feature support
        $this->assertTrue($provider->supportsFeature('folder_creation'));
        $this->assertTrue($provider->supportsFeature('file_upload'));
        $this->assertFalse($provider->supportsFeature('presigned_urls'));
        $this->assertFalse($provider->supportsFeature('unknown_feature'));
        
        // Test configuration validation
        $validationErrors = $provider->validateConfiguration($config);
        $this->assertEmpty($validationErrors);
        
        // Test initialization
        $provider->initialize($config);
        $this->assertTrue(true); // If we get here, initialization succeeded
        
        // Test cleanup
        $provider->cleanup();
        $this->assertTrue(true); // If we get here, cleanup succeeded
    }

    public function test_google_drive_provider_validates_configuration_correctly(): void
    {
        // Arrange
        $provider = new GoogleDriveProvider(
            app(\App\Services\GoogleDriveService::class),
            app(\App\Services\GoogleDriveErrorHandler::class),
            app(\App\Services\CloudStorageLogService::class)
        );

        // Test invalid configuration
        $invalidConfig = [
            'client_id' => 'invalid-client-id', // Missing .apps.googleusercontent.com
            'client_secret' => 'short', // Too short
            'redirect_uri' => 'not-a-url', // Invalid URL
        ];

        // Act
        $errors = $provider->validateConfiguration($invalidConfig);

        // Assert
        $this->assertNotEmpty($errors);
        $this->assertContains('Invalid Google OAuth client_id format', $errors);
        $this->assertContains('Google OAuth client_secret appears to be too short', $errors);
        $this->assertContains('Invalid redirect_uri format - must be a valid URL', $errors);
    }
}