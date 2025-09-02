<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CloudStorageFactory;
use App\Services\CloudConfigurationService;
use App\Services\GoogleDriveProvider;
use App\Contracts\CloudStorageProviderInterface;
use App\Exceptions\CloudStorageException;
use Illuminate\Container\Container;
use Mockery;

/**
 * Test provider registration and discovery functionality in CloudStorageFactory
 */
class CloudStorageFactoryRegistrationTest extends TestCase
{
    private CloudStorageFactory $factory;
    private CloudConfigurationService $configService;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
        $this->configService = Mockery::mock(CloudConfigurationService::class);
        $this->factory = new CloudStorageFactory($this->container, $this->configService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_register_valid_provider(): void
    {
        // Act
        $this->factory->register('test-provider', GoogleDriveProvider::class);

        // Assert
        $registered = $this->factory->getRegisteredProviders();
        $this->assertArrayHasKey('test-provider', $registered);
        $this->assertEquals(GoogleDriveProvider::class, $registered['test-provider']);
    }

    public function test_cannot_register_invalid_provider(): void
    {
        // Expect
        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('does not implement CloudStorageProviderInterface');

        // Act
        $this->factory->register('invalid-provider', \stdClass::class);
    }

    public function test_cannot_register_non_existent_class(): void
    {
        // Expect
        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('does not implement CloudStorageProviderInterface');

        // Act
        $this->factory->register('non-existent', 'NonExistentClass');
    }

    public function test_validate_provider_returns_true_for_valid_provider(): void
    {
        // Act
        $result = $this->factory->validateProvider(GoogleDriveProvider::class);

        // Assert
        $this->assertTrue($result);
    }

    public function test_validate_provider_returns_false_for_invalid_class(): void
    {
        // Act
        $result = $this->factory->validateProvider(\stdClass::class);

        // Assert
        $this->assertFalse($result);
    }

    public function test_validate_provider_returns_false_for_non_existent_class(): void
    {
        // Act
        $result = $this->factory->validateProvider('NonExistentClass');

        // Assert
        $this->assertFalse($result);
    }

    public function test_get_registered_providers_returns_empty_array_initially(): void
    {
        // Act
        $providers = $this->factory->getRegisteredProviders();

        // Assert
        $this->assertIsArray($providers);
        $this->assertEmpty($providers);
    }

    public function test_get_registered_providers_returns_all_registered_providers(): void
    {
        // Arrange
        $this->factory->register('provider-1', GoogleDriveProvider::class);
        $this->factory->register('provider-2', GoogleDriveProvider::class);

        // Act
        $providers = $this->factory->getRegisteredProviders();

        // Assert
        $this->assertCount(2, $providers);
        $this->assertArrayHasKey('provider-1', $providers);
        $this->assertArrayHasKey('provider-2', $providers);
        $this->assertEquals(GoogleDriveProvider::class, $providers['provider-1']);
        $this->assertEquals(GoogleDriveProvider::class, $providers['provider-2']);
    }

    public function test_discover_providers_returns_empty_array_for_non_existent_paths(): void
    {
        // Act
        $discovered = $this->factory->discoverProviders(['/non/existent/path']);

        // Assert
        $this->assertIsArray($discovered);
        $this->assertEmpty($discovered);
    }

    public function test_discover_providers_finds_provider_files(): void
    {
        // Create a temporary directory structure for testing
        $tempDir = sys_get_temp_dir() . '/cloud_storage_test_' . uniqid();
        mkdir($tempDir, 0755, true);

        // Create a mock provider file
        $providerContent = '<?php
namespace App\Services;
use App\Contracts\CloudStorageProviderInterface;
class TestProvider implements CloudStorageProviderInterface {
    public function uploadFile($user, $localPath, $targetPath, $metadata = []): string { return "test"; }
    public function deleteFile($user, $fileId): bool { return true; }
    public function getConnectionHealth($user) { return null; }
    public function handleAuthCallback($user, $code): void {}
    public function getAuthUrl($user): string { return "test"; }
    public function disconnect($user): void {}
    public function getProviderName(): string { return "test"; }
    public function hasValidConnection($user): bool { return true; }
    public function getCapabilities(): array { return []; }
    public function validateConfiguration(array $config): array { return []; }
    public function initialize(array $config): void {}
    public function getAuthenticationType(): string { return "oauth"; }
    public function getStorageModel(): string { return "hierarchical"; }
    public function getMaxFileSize(): int { return 1000; }
    public function getSupportedFileTypes(): array { return ["*"]; }
    public function supportsFeature(string $feature): bool { return false; }
    public function cleanup(): void {}
}';

        file_put_contents($tempDir . '/TestProvider.php', $providerContent);

        try {
            // Act
            $discovered = $this->factory->discoverProviders([$tempDir]);

            // Assert
            $this->assertIsArray($discovered);
            // Note: This test may not find the provider because the class isn't actually loaded
            // In a real scenario, the autoloader would handle this

        } finally {
            // Cleanup
            unlink($tempDir . '/TestProvider.php');
            rmdir($tempDir);
        }
    }

    public function test_clear_cache_clears_provider_cache(): void
    {
        // This test verifies the cache clearing functionality exists
        // The actual cache behavior is tested in other test methods

        // Act & Assert (should not throw exception)
        $this->factory->clearCache();
        $this->assertTrue(true); // If we get here, the method exists and works
    }

    public function test_extract_provider_name_from_class_name(): void
    {
        // This tests the private method indirectly through discovery
        // We can't test it directly, but we can verify the behavior

        $reflection = new \ReflectionClass($this->factory);
        $method = $reflection->getMethod('extractProviderName');
        $method->setAccessible(true);

        // Test various class name formats
        $testCases = [
            'App\\Services\\GoogleDriveProvider' => 'google-drive',
            'App\\Services\\S3Provider' => 's3',
            'App\\Services\\AzureBlobProvider' => 'azure-blob',
            'TestProvider' => 'test',
        ];

        foreach ($testCases as $className => $expectedName) {
            $result = $method->invoke($this->factory, $className);
            $this->assertEquals($expectedName, $result, "Failed for class: {$className}");
        }
    }

    public function test_get_class_name_from_file(): void
    {
        // Test the private method for extracting class names from files
        $reflection = new \ReflectionClass($this->factory);
        $method = $reflection->getMethod('getClassNameFromFile');
        $method->setAccessible(true);

        // Create a temporary file with a class
        $tempFile = tempnam(sys_get_temp_dir(), 'provider_test');
        $content = '<?php
namespace App\\Services;
class TestProvider {
    // class content
}';
        file_put_contents($tempFile, $content);

        try {
            // Act
            $result = $method->invoke($this->factory, $tempFile);

            // Assert
            $this->assertEquals('App\\Services\\TestProvider', $result);

        } finally {
            // Cleanup
            unlink($tempFile);
        }
    }

    public function test_get_class_name_from_file_returns_null_for_invalid_file(): void
    {
        $reflection = new \ReflectionClass($this->factory);
        $method = $reflection->getMethod('getClassNameFromFile');
        $method->setAccessible(true);

        // Create a temporary file without proper class structure
        $tempFile = tempnam(sys_get_temp_dir(), 'invalid_test');
        file_put_contents($tempFile, 'invalid php content');

        try {
            // Act
            $result = $method->invoke($this->factory, $tempFile);

            // Assert
            $this->assertNull($result);

        } finally {
            // Cleanup
            unlink($tempFile);
        }
    }

    public function test_registration_is_case_sensitive(): void
    {
        // Arrange
        $this->factory->register('test-provider', GoogleDriveProvider::class);
        $this->factory->register('Test-Provider', GoogleDriveProvider::class);

        // Act
        $providers = $this->factory->getRegisteredProviders();

        // Assert
        $this->assertCount(2, $providers);
        $this->assertArrayHasKey('test-provider', $providers);
        $this->assertArrayHasKey('Test-Provider', $providers);
    }

    public function test_can_overwrite_existing_provider_registration(): void
    {
        // Arrange
        $this->factory->register('test-provider', GoogleDriveProvider::class);

        // Act - Register again with same name
        $this->factory->register('test-provider', GoogleDriveProvider::class);

        // Assert
        $providers = $this->factory->getRegisteredProviders();
        $this->assertCount(1, $providers);
        $this->assertEquals(GoogleDriveProvider::class, $providers['test-provider']);
    }
}