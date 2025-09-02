<?php

namespace Tests\Examples;

use Tests\Unit\Contracts\CloudStorageProviderTestCase;
use Tests\Mocks\MockCloudStorageProvider;
use App\Contracts\CloudStorageProviderInterface;

/**
 * Example test showing how to use the CloudStorageProviderTestCase
 * for testing a new cloud storage provider.
 * 
 * This example uses the MockCloudStorageProvider to demonstrate
 * the testing patterns without requiring external API access.
 */
class ExampleProviderTest extends CloudStorageProviderTestCase
{
    protected function getProviderName(): string
    {
        return 'mock-provider'; // Use the actual mock provider name
    }

    protected function createProvider(): CloudStorageProviderInterface
    {
        $provider = new MockCloudStorageProvider();
        
        // Configure the mock provider for this example
        $provider->setCapabilities([
            'file_upload' => true,
            'file_delete' => true,
            'folder_creation' => true,
            'folder_delete' => false, // Example: this provider doesn't support folder deletion
            'presigned_urls' => true,
        ]);
        
        return $provider;
    }

    protected function getTestConfig(): array
    {
        return [
            'api_key' => 'example-api-key-12345',
            'endpoint' => 'https://api.example-provider.com',
            'region' => 'us-west-2',
        ];
    }

    /**
     * Example of testing provider-specific capabilities.
     */
    public function test_provider_supports_presigned_urls(): void
    {
        $provider = $this->createProvider();
        
        $this->assertTrue($provider->supportsFeature('presigned_urls'));
        $this->assertFalse($provider->supportsFeature('folder_delete'));
    }

    /**
     * Example of testing configuration validation with provider-specific rules.
     */
    public function test_validates_api_key_format(): void
    {
        $provider = $this->createProvider();
        
        // Test with missing required fields (the mock provider requires api_key and endpoint)
        $invalidConfig = [
            // Missing api_key and endpoint
        ];
        
        $errors = $provider->validateConfiguration($invalidConfig);
        $this->assertNotEmpty($errors);
        $this->assertContains('API key is required', $errors);
        $this->assertContains('Endpoint is required', $errors);
    }

    /**
     * Example of testing file upload with metadata.
     */
    public function test_can_upload_file_with_custom_metadata(): void
    {
        $provider = $this->createProvider();
        $user = $this->createTestUser();
        
        // Create a test file
        $tempFile = tempnam(sys_get_temp_dir(), 'example_test');
        file_put_contents($tempFile, 'Example file content');
        
        $metadata = [
            'content_type' => 'text/plain',
            'cache_control' => 'max-age=3600',
            'custom_tag' => 'example-upload',
        ];
        
        try {
            $fileId = $provider->uploadFile($user, $tempFile, 'examples/test.txt', $metadata);
            
            $this->assertNotEmpty($fileId);
            $this->assertIsString($fileId);
            
            // Verify the mock provider recorded the upload
            $mockProvider = $provider; // Cast to access mock methods
            $this->assertTrue($mockProvider->wasFileUploaded('examples/test.txt'));
            
            $uploadedFiles = $mockProvider->getUploadedFiles();
            $this->assertCount(1, $uploadedFiles);
            $this->assertEquals($metadata, $uploadedFiles[0]['metadata']);
            
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Example of testing error handling.
     */
    public function test_handles_upload_failure_gracefully(): void
    {
        $provider = $this->createProvider();
        $user = $this->createTestUser();
        
        // Configure the mock to fail uploads
        $provider->setShouldFailUpload(true);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'example_test');
        file_put_contents($tempFile, 'Example file content');
        
        try {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('Mock upload failure');
            
            $provider->uploadFile($user, $tempFile, 'examples/test.txt');
            
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Example of testing authentication flow.
     */
    public function test_authentication_flow(): void
    {
        $provider = $this->createProvider();
        $user = $this->createTestUser();
        
        // Test getting auth URL
        $authUrl = $provider->getAuthUrl($user);
        $this->assertStringContainsString('user_id=' . $user->id, $authUrl);
        
        // Test handling auth callback
        $provider->handleAuthCallback($user, 'example-auth-code-12345');
        
        // Verify the mock provider recorded the authentication
        $this->assertTrue($provider->wasUserAuthenticated($user->id));
        
        $authCallbacks = $provider->getAuthCallbacks();
        $this->assertCount(1, $authCallbacks);
        $this->assertEquals('example-auth-code-12345', $authCallbacks[0]['code']);
    }

    /**
     * Example of testing connection health.
     */
    public function test_connection_health_reporting(): void
    {
        $provider = $this->createProvider();
        $user = $this->createTestUser();
        
        // Test healthy connection
        $provider->setHasValidConnection(true);
        $provider->setHealthStatus('healthy');
        
        $health = $provider->getConnectionHealth($user);
        $this->assertTrue($health->isHealthy());
        $this->assertEquals('mock-provider', $health->provider);
        
        // Test unhealthy connection
        $provider->setHasValidConnection(false);
        $provider->setHealthStatus('connection_failed');
        
        $health = $provider->getConnectionHealth($user);
        $this->assertFalse($health->isHealthy());
        $this->assertTrue($health->isDisconnected());
    }
}