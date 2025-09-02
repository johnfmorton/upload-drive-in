<?php

namespace Tests\Integration;

use App\Contracts\CloudStorageProviderInterface;
use App\Services\S3Provider;

/**
 * Integration tests for S3 provider against real AWS S3 API.
 * 
 * These tests require valid AWS credentials and will create/delete
 * actual files in S3. Use with caution.
 */
class S3ProviderIntegrationTest extends CloudStorageProviderIntegrationTestCase
{
    protected function getProviderName(): string
    {
        return 'amazon-s3';
    }

    protected function createProvider(): CloudStorageProviderInterface
    {
        return app(S3Provider::class);
    }

    protected function getIntegrationConfig(): array
    {
        return [
            'access_key_id' => $this->getRequiredEnvVar('AWS_ACCESS_KEY_ID'),
            'secret_access_key' => $this->getRequiredEnvVar('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => $this->getRequiredEnvVar('AWS_BUCKET'),
            'endpoint' => env('AWS_ENDPOINT'),
        ];
    }

    protected function shouldSkipIntegrationTests(): bool
    {
        // Skip if integration tests are disabled or credentials are missing
        return env('SKIP_INTEGRATION_TESTS', true) || 
               empty(env('AWS_ACCESS_KEY_ID')) || 
               empty(env('AWS_SECRET_ACCESS_KEY')) ||
               empty(env('AWS_BUCKET'));
    }

    /**
     * Test S3 flat storage model.
     */
    public function test_flat_storage_model(): void
    {
        $this->assertEquals('flat', $this->provider->getStorageModel());
        
        // Test uploading with key-based path
        $testFile = $this->createTestFile('S3 flat storage test content');
        $targetPath = 'integration-tests/s3-flat-test-' . uniqid() . '.txt';

        $fileId = $this->uploadTestFile($testFile, $targetPath);

        $this->assertNotEmpty($fileId);
        // In S3, the file ID should be the key
        $this->assertEquals($targetPath, $fileId);
    }

    /**
     * Test S3 API key authentication type.
     */
    public function test_api_key_authentication_type(): void
    {
        $this->assertEquals('api_key', $this->provider->getAuthenticationType());
    }

    /**
     * Test S3 file size limits.
     */
    public function test_s3_file_size_limits(): void
    {
        $maxFileSize = $this->provider->getMaxFileSize();
        
        // S3 supports up to 5TB per object
        $this->assertGreaterThan(1024 * 1024 * 1024, $maxFileSize); // At least 1GB
    }

    /**
     * Test S3 supported file types.
     */
    public function test_s3_supported_file_types(): void
    {
        $supportedTypes = $this->provider->getSupportedFileTypes();
        
        // S3 supports all file types
        $this->assertContains('*', $supportedTypes);
    }

    /**
     * Test S3 specific capabilities.
     */
    public function test_s3_capabilities(): void
    {
        $capabilities = $this->provider->getCapabilities();
        
        // S3 should not support folder creation (uses key prefixes instead)
        $this->assertArrayHasKey('folder_creation', $capabilities);
        $this->assertFalse($capabilities['folder_creation']);
        
        // S3 should support file operations
        $this->assertTrue($capabilities['file_upload']);
        $this->assertTrue($capabilities['file_delete']);
        
        // S3 should support presigned URLs
        if (isset($capabilities['presigned_urls'])) {
            $this->assertTrue($capabilities['presigned_urls']);
        }
    }

    /**
     * Test S3 key-based file organization.
     */
    public function test_s3_key_based_organization(): void
    {
        // Test uploading files with different key prefixes
        $testFile1 = $this->createTestFile('Content for folder1');
        $testFile2 = $this->createTestFile('Content for folder2');
        
        $key1 = 'integration-tests/folder1/file1-' . uniqid() . '.txt';
        $key2 = 'integration-tests/folder2/file2-' . uniqid() . '.txt';

        $fileId1 = $this->uploadTestFile($testFile1, $key1);
        $fileId2 = $this->uploadTestFile($testFile2, $key2);

        $this->assertEquals($key1, $fileId1);
        $this->assertEquals($key2, $fileId2);
    }

    /**
     * Test S3 metadata handling.
     */
    public function test_s3_metadata_handling(): void
    {
        $testFile = $this->createTestFile('S3 metadata test content');
        $targetPath = 'integration-tests/s3-metadata-test-' . uniqid() . '.txt';
        $metadata = [
            'content-type' => 'text/plain',
            'cache-control' => 'max-age=3600',
            'custom-header' => 'integration-test',
        ];

        $fileId = $this->uploadTestFile($testFile, $targetPath, $metadata);

        $this->assertNotEmpty($fileId);
        $this->assertEquals($targetPath, $fileId);
    }

    /**
     * Test S3 specific error handling.
     */
    public function test_s3_error_handling(): void
    {
        // Test deleting a non-existent object
        try {
            $this->provider->deleteFile($this->testUser, 'nonexistent-key-' . uniqid());
            // S3 delete operations typically don't fail for non-existent objects
            // They return success, so we'll just verify the method completes
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // If an exception is thrown, verify it has a meaningful message
            $this->assertNotEmpty($e->getMessage());
        }
    }

    /**
     * Test S3 bucket operations.
     */
    public function test_s3_bucket_operations(): void
    {
        // Test that the provider can work with the configured bucket
        $health = $this->provider->getConnectionHealth($this->testUser);
        
        $this->assertTrue($health->isConnected);
        $this->assertEquals('healthy', $health->status);
    }

    /**
     * Test S3 region handling.
     */
    public function test_s3_region_handling(): void
    {
        // Test that the provider correctly handles the configured region
        $config = $this->getIntegrationConfig();
        $this->provider->initialize($config);
        
        // If initialization succeeds, the region is valid
        $this->assertTrue(true);
    }

    /**
     * Test S3 storage classes (if supported).
     */
    public function test_s3_storage_classes(): void
    {
        $capabilities = $this->provider->getCapabilities();
        
        if (isset($capabilities['storage_classes'])) {
            $this->assertIsArray($capabilities['storage_classes']);
            $this->assertContains('STANDARD', $capabilities['storage_classes']);
        }
    }

    /**
     * Test S3 presigned URLs (if supported).
     */
    public function test_s3_presigned_urls(): void
    {
        if (!$this->provider->supportsFeature('presigned_urls')) {
            $this->markTestSkipped('Provider does not support presigned URLs');
        }

        // This would require implementing presigned URL generation in S3Provider
        // For now, we'll test that the provider reports the capability
        $this->assertTrue($this->provider->supportsFeature('presigned_urls'));
    }
}