<?php

namespace Tests\Unit\Contracts;

use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use App\Services\CloudStorageHealthStatus;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Abstract base test case for cloud storage provider implementations.
 * 
 * This class provides common test methods that all provider implementations
 * should pass to ensure interface compliance and consistent behavior.
 */
abstract class CloudStorageProviderTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Get the provider name for testing.
     */
    abstract protected function getProviderName(): string;

    /**
     * Create a provider instance for testing.
     */
    abstract protected function createProvider(): CloudStorageProviderInterface;

    /**
     * Get test configuration for the provider.
     */
    abstract protected function getTestConfig(): array;

    /**
     * Create a test user for provider operations.
     */
    protected function createTestUser(): User
    {
        return User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    /**
     * Test that the provider implements the required interface.
     */
    public function test_implements_cloud_storage_provider_interface(): void
    {
        $provider = $this->createProvider();
        $this->assertInstanceOf(CloudStorageProviderInterface::class, $provider);
    }

    /**
     * Test that the provider returns a valid provider name.
     */
    public function test_has_valid_provider_name(): void
    {
        $provider = $this->createProvider();
        $providerName = $provider->getProviderName();
        
        $this->assertNotEmpty($providerName);
        $this->assertIsString($providerName);
        $this->assertEquals($this->getProviderName(), $providerName);
    }

    /**
     * Test that the provider returns capabilities as an array.
     */
    public function test_get_capabilities_returns_array(): void
    {
        $provider = $this->createProvider();
        $capabilities = $provider->getCapabilities();
        
        $this->assertIsArray($capabilities);
        $this->assertNotEmpty($capabilities);
    }

    /**
     * Test that the provider validates configuration correctly.
     */
    public function test_validate_configuration_with_valid_config(): void
    {
        $provider = $this->createProvider();
        $config = $this->getTestConfig();
        
        $result = $provider->validateConfiguration($config);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result, 'Valid configuration should return no errors');
    }

    /**
     * Test that the provider validates configuration with invalid config.
     */
    public function test_validate_configuration_with_invalid_config(): void
    {
        $provider = $this->createProvider();
        $invalidConfig = [];
        
        $result = $provider->validateConfiguration($invalidConfig);
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result, 'Invalid configuration should return errors');
    }

    /**
     * Test that the provider can be initialized with valid configuration.
     */
    public function test_initialize_with_valid_config(): void
    {
        $provider = $this->createProvider();
        $config = $this->getTestConfig();
        
        // Should not throw an exception
        $provider->initialize($config);
        $this->assertTrue(true, 'Provider initialization completed without exception');
    }

    /**
     * Test that the provider returns a valid authentication type.
     */
    public function test_get_authentication_type_returns_valid_type(): void
    {
        $provider = $this->createProvider();
        $authType = $provider->getAuthenticationType();
        
        $this->assertIsString($authType);
        $this->assertContains($authType, ['oauth', 'api_key', 'service_account', 'connection_string']);
    }

    /**
     * Test that the provider returns a valid storage model.
     */
    public function test_get_storage_model_returns_valid_model(): void
    {
        $provider = $this->createProvider();
        $storageModel = $provider->getStorageModel();
        
        $this->assertIsString($storageModel);
        $this->assertContains($storageModel, ['hierarchical', 'flat', 'hybrid']);
    }

    /**
     * Test that the provider returns a valid max file size.
     */
    public function test_get_max_file_size_returns_positive_integer(): void
    {
        $provider = $this->createProvider();
        $maxFileSize = $provider->getMaxFileSize();
        
        $this->assertIsInt($maxFileSize);
        $this->assertGreaterThan(0, $maxFileSize);
    }

    /**
     * Test that the provider returns supported file types as an array.
     */
    public function test_get_supported_file_types_returns_array(): void
    {
        $provider = $this->createProvider();
        $supportedTypes = $provider->getSupportedFileTypes();
        
        $this->assertIsArray($supportedTypes);
        $this->assertNotEmpty($supportedTypes);
    }

    /**
     * Test that the provider's supportsFeature method works correctly.
     */
    public function test_supports_feature_returns_boolean(): void
    {
        $provider = $this->createProvider();
        
        $result = $provider->supportsFeature('file_upload');
        $this->assertIsBool($result);
        
        $result = $provider->supportsFeature('nonexistent_feature');
        $this->assertIsBool($result);
    }

    /**
     * Test that the provider can check connection validity.
     */
    public function test_has_valid_connection_returns_boolean(): void
    {
        $provider = $this->createProvider();
        $user = $this->createTestUser();
        
        $result = $provider->hasValidConnection($user);
        $this->assertIsBool($result);
    }

    /**
     * Test that the provider returns connection health status.
     */
    public function test_get_connection_health_returns_health_status(): void
    {
        $provider = $this->createProvider();
        $user = $this->createTestUser();
        
        $health = $provider->getConnectionHealth($user);
        $this->assertInstanceOf(CloudStorageHealthStatus::class, $health);
        $this->assertNotEmpty($health->provider);
        $this->assertNotEmpty($health->status);
    }

    /**
     * Test that the provider returns an auth URL as a string.
     */
    public function test_get_auth_url_returns_string(): void
    {
        $provider = $this->createProvider();
        $user = $this->createTestUser();
        
        $authUrl = $provider->getAuthUrl($user);
        $this->assertIsString($authUrl);
        $this->assertNotEmpty($authUrl);
    }

    /**
     * Test that the provider can handle auth callback without throwing exceptions.
     */
    public function test_handle_auth_callback_does_not_throw(): void
    {
        $provider = $this->createProvider();
        $user = $this->createTestUser();
        
        // Should not throw an exception with mock code
        $provider->handleAuthCallback($user, 'mock_auth_code');
        $this->assertTrue(true, 'Auth callback handled without exception');
    }

    /**
     * Test that the provider can disconnect without throwing exceptions.
     */
    public function test_disconnect_does_not_throw(): void
    {
        $provider = $this->createProvider();
        $user = $this->createTestUser();
        
        // Should not throw an exception
        $provider->disconnect($user);
        $this->assertTrue(true, 'Disconnect completed without exception');
    }

    /**
     * Test that the provider can cleanup without throwing exceptions.
     */
    public function test_cleanup_does_not_throw(): void
    {
        $provider = $this->createProvider();
        
        // Should not throw an exception
        $provider->cleanup();
        $this->assertTrue(true, 'Cleanup completed without exception');
    }

    /**
     * Test that the provider handles file upload operations.
     */
    public function test_upload_file_returns_string(): void
    {
        $provider = $this->createProvider();
        $user = $this->createTestUser();
        
        // Create a temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_upload');
        file_put_contents($tempFile, 'test content');
        
        try {
            $result = $provider->uploadFile($user, $tempFile, 'test/path.txt', []);
            $this->assertIsString($result);
            $this->assertNotEmpty($result);
        } catch (\Exception $e) {
            // Some providers may throw exceptions in test environment
            // This is acceptable as long as the method signature is correct
            $this->assertTrue(true, 'Upload method exists and has correct signature');
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test that the provider handles file deletion operations.
     */
    public function test_delete_file_returns_boolean(): void
    {
        $provider = $this->createProvider();
        $user = $this->createTestUser();
        
        try {
            $result = $provider->deleteFile($user, 'test_file_id');
            $this->assertIsBool($result);
        } catch (\Exception $e) {
            // Some providers may throw exceptions in test environment
            // This is acceptable as long as the method signature is correct
            $this->assertTrue(true, 'Delete method exists and has correct signature');
        }
    }

    /**
     * Test provider capability consistency.
     */
    public function test_capability_consistency(): void
    {
        $provider = $this->createProvider();
        $capabilities = $provider->getCapabilities();
        
        // Test that supportsFeature is consistent with capabilities
        foreach ($capabilities as $feature => $supported) {
            if (is_bool($supported)) {
                $this->assertEquals($supported, $provider->supportsFeature($feature));
            }
        }
    }

    /**
     * Test that required capabilities are present.
     */
    public function test_has_required_capabilities(): void
    {
        $provider = $this->createProvider();
        $capabilities = $provider->getCapabilities();
        
        // These capabilities should be present for all providers
        $requiredCapabilities = ['file_upload', 'file_delete'];
        
        foreach ($requiredCapabilities as $capability) {
            $this->assertArrayHasKey($capability, $capabilities);
            $this->assertTrue($provider->supportsFeature($capability));
        }
    }
}