<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\GoogleDriveProvider;
use App\Services\GoogleDriveService;
use App\Services\GoogleDriveErrorHandler;
use App\Services\CloudStorageLogService;
use App\Exceptions\CloudStorageSetupException;
use Mockery;

/**
 * Test enhanced GoogleDriveProvider methods for the new interface
 */
class GoogleDriveProviderEnhancedTest extends TestCase
{
    private GoogleDriveProvider $provider;
    private GoogleDriveService $driveService;
    private GoogleDriveErrorHandler $errorHandler;
    private CloudStorageLogService $logService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driveService = Mockery::mock(GoogleDriveService::class);
        $this->errorHandler = Mockery::mock(GoogleDriveErrorHandler::class);
        $this->logService = Mockery::mock(CloudStorageLogService::class);

        $this->provider = new GoogleDriveProvider(
            $this->driveService,
            $this->errorHandler,
            $this->logService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_capabilities_returns_correct_capabilities(): void
    {
        // Act
        $capabilities = $this->provider->getCapabilities();

        // Assert
        $this->assertIsArray($capabilities);
        
        // Check for expected capabilities
        $expectedCapabilities = [
            'folder_creation' => true,
            'file_upload' => true,
            'file_delete' => true,
            'folder_delete' => true,
            'file_download' => true,
            'file_metadata' => true,
            'oauth_authentication' => true,
            'hierarchical_storage' => true,
            'file_sharing' => true,
            'version_history' => true,
            'search' => true,
            'batch_operations' => false,
            'presigned_urls' => false,
            'storage_classes' => false,
            'encryption_at_rest' => true,
            'access_control' => true,
        ];

        foreach ($expectedCapabilities as $capability => $expected) {
            $this->assertArrayHasKey($capability, $capabilities);
            $this->assertEquals($expected, $capabilities[$capability], "Capability {$capability} mismatch");
        }
    }

    public function test_validate_configuration_returns_empty_array_for_valid_config(): void
    {
        // Arrange
        $validConfig = [
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'valid-client-secret-123',
            'redirect_uri' => 'https://example.com/callback',
        ];

        // Act
        $errors = $this->provider->validateConfiguration($validConfig);

        // Assert
        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    public function test_validate_configuration_returns_errors_for_missing_required_keys(): void
    {
        // Arrange
        $invalidConfig = [];

        // Act
        $errors = $this->provider->validateConfiguration($invalidConfig);

        // Assert
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
        
        $this->assertContains('Missing required configuration key: client_id', $errors);
        $this->assertContains('Missing required configuration key: client_secret', $errors);
        $this->assertContains('Missing required configuration key: redirect_uri', $errors);
    }

    public function test_validate_configuration_returns_error_for_invalid_client_id_format(): void
    {
        // Arrange
        $invalidConfig = [
            'client_id' => 'invalid-client-id',
            'client_secret' => 'valid-client-secret-123',
            'redirect_uri' => 'https://example.com/callback',
        ];

        // Act
        $errors = $this->provider->validateConfiguration($invalidConfig);

        // Assert
        $this->assertContains('Invalid Google OAuth client_id format', $errors);
    }

    public function test_validate_configuration_returns_error_for_invalid_redirect_uri(): void
    {
        // Arrange
        $invalidConfig = [
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'valid-client-secret-123',
            'redirect_uri' => 'not-a-valid-url',
        ];

        // Act
        $errors = $this->provider->validateConfiguration($invalidConfig);

        // Assert
        $this->assertContains('Invalid redirect_uri format - must be a valid URL', $errors);
    }

    public function test_validate_configuration_returns_error_for_short_client_secret(): void
    {
        // Arrange
        $invalidConfig = [
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'short',
            'redirect_uri' => 'https://example.com/callback',
        ];

        // Act
        $errors = $this->provider->validateConfiguration($invalidConfig);

        // Assert
        $this->assertContains('Google OAuth client_secret appears to be too short', $errors);
    }

    public function test_initialize_succeeds_with_valid_configuration(): void
    {
        // Arrange
        $validConfig = [
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'valid-client-secret-123',
            'redirect_uri' => 'https://example.com/callback',
        ];

        // Act & Assert - Should not throw exception
        $this->provider->initialize($validConfig);
        $this->assertTrue(true); // If we get here, initialization succeeded
    }

    public function test_initialize_throws_exception_with_invalid_configuration(): void
    {
        // Arrange
        $invalidConfig = [
            'client_id' => 'invalid-client-id',
        ];

        // Expect
        $this->expectException(CloudStorageSetupException::class);
        $this->expectExceptionMessage('Google Drive provider configuration is invalid');

        // Act
        $this->provider->initialize($invalidConfig);
    }

    public function test_get_authentication_type_returns_oauth(): void
    {
        // Act
        $authType = $this->provider->getAuthenticationType();

        // Assert
        $this->assertEquals('oauth', $authType);
    }

    public function test_get_storage_model_returns_hierarchical(): void
    {
        // Act
        $storageModel = $this->provider->getStorageModel();

        // Assert
        $this->assertEquals('hierarchical', $storageModel);
    }

    public function test_get_max_file_size_returns_5gb(): void
    {
        // Act
        $maxSize = $this->provider->getMaxFileSize();

        // Assert
        $this->assertEquals(5368709120, $maxSize); // 5GB in bytes
    }

    public function test_get_supported_file_types_returns_all_types(): void
    {
        // Act
        $supportedTypes = $this->provider->getSupportedFileTypes();

        // Assert
        $this->assertIsArray($supportedTypes);
        $this->assertEquals(['*'], $supportedTypes);
    }

    public function test_supports_feature_returns_true_for_supported_features(): void
    {
        // Test supported features
        $supportedFeatures = [
            'folder_creation',
            'file_upload',
            'file_delete',
            'oauth_authentication',
            'hierarchical_storage',
        ];

        foreach ($supportedFeatures as $feature) {
            $this->assertTrue(
                $this->provider->supportsFeature($feature),
                "Feature {$feature} should be supported"
            );
        }
    }

    public function test_supports_feature_returns_false_for_unsupported_features(): void
    {
        // Test unsupported features
        $unsupportedFeatures = [
            'batch_operations',
            'presigned_urls',
            'storage_classes',
        ];

        foreach ($unsupportedFeatures as $feature) {
            $this->assertFalse(
                $this->provider->supportsFeature($feature),
                "Feature {$feature} should not be supported"
            );
        }
    }

    public function test_supports_feature_returns_false_for_unknown_features(): void
    {
        // Act
        $result = $this->provider->supportsFeature('unknown_feature');

        // Assert
        $this->assertFalse($result);
    }

    public function test_cleanup_completes_without_errors(): void
    {
        // Act & Assert - Should not throw exception
        $this->provider->cleanup();
        $this->assertTrue(true); // If we get here, cleanup completed successfully
    }

    public function test_get_provider_name_returns_google_drive(): void
    {
        // Act
        $providerName = $this->provider->getProviderName();

        // Assert
        $this->assertEquals('google-drive', $providerName);
    }

    public function test_validate_configuration_handles_empty_values(): void
    {
        // Arrange
        $configWithEmptyValues = [
            'client_id' => '',
            'client_secret' => '',
            'redirect_uri' => '',
        ];

        // Act
        $errors = $this->provider->validateConfiguration($configWithEmptyValues);

        // Assert
        $this->assertCount(3, $errors);
        $this->assertContains('Missing required configuration key: client_id', $errors);
        $this->assertContains('Missing required configuration key: client_secret', $errors);
        $this->assertContains('Missing required configuration key: redirect_uri', $errors);
    }

    public function test_validate_configuration_handles_null_values(): void
    {
        // Arrange
        $configWithNullValues = [
            'client_id' => null,
            'client_secret' => null,
            'redirect_uri' => null,
        ];

        // Act
        $errors = $this->provider->validateConfiguration($configWithNullValues);

        // Assert
        $this->assertCount(3, $errors);
        $this->assertContains('Missing required configuration key: client_id', $errors);
        $this->assertContains('Missing required configuration key: client_secret', $errors);
        $this->assertContains('Missing required configuration key: redirect_uri', $errors);
    }

    public function test_validate_configuration_allows_extra_keys(): void
    {
        // Arrange
        $configWithExtraKeys = [
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'valid-client-secret-123',
            'redirect_uri' => 'https://example.com/callback',
            'extra_key' => 'extra_value',
            'another_extra' => 'another_value',
        ];

        // Act
        $errors = $this->provider->validateConfiguration($configWithExtraKeys);

        // Assert
        $this->assertEmpty($errors); // Extra keys should not cause validation errors
    }

    public function test_works_with_cloud_storage_factory(): void
    {
        // Arrange
        $factory = app(\App\Services\CloudStorageFactory::class);
        
        // Act - Register the provider
        $factory->register('google-drive', GoogleDriveProvider::class);
        
        // Assert - Provider is registered
        $registeredProviders = $factory->getRegisteredProviders();
        $this->assertArrayHasKey('google-drive', $registeredProviders);
        $this->assertEquals(GoogleDriveProvider::class, $registeredProviders['google-drive']);
        
        // Assert - Provider can be validated
        $this->assertTrue($factory->validateProvider(GoogleDriveProvider::class));
        
        // Note: We don't test actual creation here because it requires valid configuration
        // and dependencies that are better tested in integration tests
    }
}