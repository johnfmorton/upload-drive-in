<?php

namespace Tests\Integration;

use App\Contracts\CloudStorageProviderInterface;
use App\Services\S3Provider;
use App\Exceptions\CloudStorageException;
use Illuminate\Support\Facades\Log;

/**
 * Integration tests for S3 provider against real AWS S3 API.
 * 
 * These tests require valid AWS credentials and will create/delete
 * actual files in S3. Use with caution.
 * 
 * Environment Variables Required:
 * - AWS_ACCESS_KEY_ID: AWS access key ID
 * - AWS_SECRET_ACCESS_KEY: AWS secret access key
 * - AWS_BUCKET: S3 bucket name for testing
 * - AWS_DEFAULT_REGION: AWS region (default: us-east-1)
 * - AWS_ENDPOINT: (Optional) Custom endpoint for S3-compatible services
 * - SKIP_INTEGRATION_TESTS: Set to false to enable integration tests
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

    // ========================================
    // FULL UPLOAD WORKFLOW TESTS
    // ========================================

    /**
     * Test full upload workflow with real S3
     * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5
     */
    public function test_full_upload_workflow(): void
    {
        Log::info('Starting full S3 upload workflow test');

        // Create test file with specific content
        $content = 'Integration test content for full workflow - ' . now()->toISOString();
        $testFile = $this->createTestFile($content);
        $targetPath = 'test-client@example.com';
        $originalFilename = 'integration-test-' . uniqid() . '.txt';

        // Prepare metadata
        $metadata = [
            'original_filename' => $originalFilename,
            'mime_type' => 'text/plain',
            'description' => 'Full workflow integration test',
        ];

        // Upload file
        $fileId = $this->uploadTestFile($testFile, $targetPath, $metadata);

        // Verify upload
        $this->assertNotEmpty($fileId);
        $this->assertIsString($fileId);
        $this->assertStringContainsString($targetPath, $fileId);
        $this->assertStringContainsString('.txt', $fileId);

        // Verify file can be accessed (health check)
        $health = $this->provider->getConnectionHealth($this->testUser);
        $this->assertTrue($health->isHealthy());

        Log::info('Full S3 upload workflow test completed successfully', [
            'file_id' => $fileId,
        ]);
    }

    /**
     * Test S3 flat storage model with key-based organization
     * Requirements: 5.2
     */
    public function test_flat_storage_model(): void
    {
        $this->assertEquals('flat', $this->provider->getStorageModel());
        
        // Test uploading with key-based path (client email)
        $testFile = $this->createTestFile('S3 flat storage test content');
        $targetPath = 'client-email@example.com';

        $fileId = $this->uploadTestFile($testFile, $targetPath);

        $this->assertNotEmpty($fileId);
        // In S3, the file ID should contain the target path as prefix
        $this->assertStringContainsString($targetPath, $fileId);
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
        
        $this->assertTrue($health->isHealthy());
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

    // ========================================
    // FILE DELETION TESTS
    // ========================================

    /**
     * Test file deletion workflow
     * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5
     */
    public function test_file_deletion_workflow(): void
    {
        Log::info('Starting S3 file deletion workflow test');

        // First upload a file
        $testFile = $this->createTestFile('Content to be deleted');
        $targetPath = 'delete-test@example.com';
        $fileId = $this->uploadTestFile($testFile, $targetPath);

        $this->assertNotEmpty($fileId);

        // Now delete it
        $result = $this->provider->deleteFile($this->testUser, $fileId);

        $this->assertTrue($result);

        // Remove from cleanup list since we already deleted it
        $this->createdFiles = array_filter($this->createdFiles, function ($file) use ($fileId) {
            return $file['file_id'] !== $fileId;
        });

        Log::info('S3 file deletion workflow test completed successfully');
    }

    /**
     * Test deleting non-existent file
     * Requirements: 4.4
     */
    public function test_delete_nonexistent_file(): void
    {
        // S3 delete operations typically don't fail for non-existent objects
        // They return success, so we'll verify the method completes without exception
        $result = $this->provider->deleteFile($this->testUser, 'nonexistent-key-' . uniqid());
        
        // S3 returns success even for non-existent objects
        $this->assertTrue($result);
    }

    // ========================================
    // HEALTH CHECK TESTS
    // ========================================

    /**
     * Test comprehensive health check
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
     */
    public function test_comprehensive_health_check(): void
    {
        Log::info('Starting comprehensive S3 health check test');

        $health = $this->provider->getConnectionHealth($this->testUser);

        // Verify health status structure
        $this->assertNotNull($health);
        $this->assertTrue($health->isHealthy());
        $this->assertEquals('healthy', $health->status);
        $this->assertNotNull($health->lastSuccessfulOperation);

        // Verify provider-specific data
        $this->assertArrayHasKey('bucket', $health->providerSpecificData);
        $this->assertArrayHasKey('region', $health->providerSpecificData);
        $this->assertArrayHasKey('health_check_successful', $health->providerSpecificData);
        $this->assertTrue($health->providerSpecificData['health_check_successful']);

        Log::info('Comprehensive S3 health check test completed successfully', [
            'bucket' => $health->providerSpecificData['bucket'],
            'region' => $health->providerSpecificData['region'],
        ]);
    }

    /**
     * Test health check with invalid credentials
     * Requirements: 8.3, 8.4
     */
    public function test_health_check_with_invalid_credentials(): void
    {
        // Create a new provider instance with invalid credentials
        $invalidProvider = app(S3Provider::class);
        
        try {
            $invalidProvider->initialize([
                'access_key_id' => 'AKIAINVALIDKEY123456',
                'secret_access_key' => 'InvalidSecretKey1234567890123456789012',
                'region' => 'us-east-1',
                'bucket' => 'nonexistent-bucket-' . uniqid(),
            ]);

            $health = $invalidProvider->getConnectionHealth($this->testUser);

            // Should return unhealthy status
            $this->assertFalse($health->isHealthy());
            $this->assertNotEmpty($health->lastErrorMessage);
            $this->assertTrue($health->requiresReconnection);
        } catch (CloudStorageException $e) {
            // Exception is also acceptable for invalid credentials
            $this->assertNotEmpty($e->getMessage());
        }
    }

    // ========================================
    // PRESIGNED URL TESTS
    // ========================================

    /**
     * Test presigned URL generation for download
     * Requirements: 10.1, 10.4
     */
    public function test_generate_presigned_url_for_download(): void
    {
        $this->requiresFeature('presigned_urls');

        Log::info('Starting presigned URL generation test');

        // Upload a test file first
        $testFile = $this->createTestFile('Content for presigned URL test');
        $targetPath = 'presigned-test@example.com';
        $fileId = $this->uploadTestFile($testFile, $targetPath);

        // Generate presigned URL for download
        $presignedUrl = $this->provider->generatePresignedUrl(
            $this->testUser,
            $fileId,
            60, // 60 minutes expiration
            'download'
        );

        $this->assertNotEmpty($presignedUrl);
        $this->assertIsString($presignedUrl);
        $this->assertStringStartsWith('https://', $presignedUrl);
        $this->assertStringContainsString($fileId, $presignedUrl);

        Log::info('Presigned URL generated successfully', [
            'file_id' => $fileId,
            'url_length' => strlen($presignedUrl),
        ]);
    }

    /**
     * Test presigned URL generation for upload
     * Requirements: 10.4
     */
    public function test_generate_presigned_url_for_upload(): void
    {
        $this->requiresFeature('presigned_urls');

        $fileId = 'integration-tests/presigned-upload-' . uniqid() . '.txt';

        $presignedUrl = $this->provider->generatePresignedUrl(
            $this->testUser,
            $fileId,
            30, // 30 minutes expiration
            'upload'
        );

        $this->assertNotEmpty($presignedUrl);
        $this->assertIsString($presignedUrl);
        $this->assertStringStartsWith('https://', $presignedUrl);
    }

    /**
     * Test presigned URL generation for delete
     * Requirements: 10.4
     */
    public function test_generate_presigned_url_for_delete(): void
    {
        $this->requiresFeature('presigned_urls');

        // Upload a test file first
        $testFile = $this->createTestFile('Content for presigned delete test');
        $targetPath = 'presigned-delete-test@example.com';
        $fileId = $this->uploadTestFile($testFile, $targetPath);

        $presignedUrl = $this->provider->generatePresignedUrl(
            $this->testUser,
            $fileId,
            15, // 15 minutes expiration
            'delete'
        );

        $this->assertNotEmpty($presignedUrl);
        $this->assertIsString($presignedUrl);
        $this->assertStringStartsWith('https://', $presignedUrl);
    }

    // ========================================
    // S3-COMPATIBLE SERVICE TESTS
    // ========================================

    /**
     * Test S3-compatible service support with custom endpoint
     * Requirements: 14.1, 14.2, 14.3, 14.4, 14.5
     */
    public function test_s3_compatible_service_with_custom_endpoint(): void
    {
        $customEndpoint = env('AWS_ENDPOINT');
        
        if (empty($customEndpoint)) {
            $this->markTestSkipped('Custom endpoint not configured for S3-compatible service testing');
        }

        Log::info('Starting S3-compatible service test', [
            'endpoint' => $customEndpoint,
        ]);

        // Create a new provider instance with custom endpoint
        $compatibleProvider = app(S3Provider::class);
        $compatibleProvider->initialize([
            'access_key_id' => $this->getRequiredEnvVar('AWS_ACCESS_KEY_ID'),
            'secret_access_key' => $this->getRequiredEnvVar('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => $this->getRequiredEnvVar('AWS_BUCKET'),
            'endpoint' => $customEndpoint,
        ]);

        // Test health check with custom endpoint
        $health = $compatibleProvider->getConnectionHealth($this->testUser);
        $this->assertTrue($health->isHealthy());

        // Test file upload with custom endpoint
        $testFile = $this->createTestFile('S3-compatible service test content');
        $targetPath = 's3-compatible-test@example.com';
        $metadata = [
            'original_filename' => 'compatible-test.txt',
            'mime_type' => 'text/plain',
        ];

        $fileId = $compatibleProvider->uploadFile($this->testUser, $testFile, $targetPath, $metadata);
        $this->assertNotEmpty($fileId);

        // Clean up
        $compatibleProvider->deleteFile($this->testUser, $fileId);

        Log::info('S3-compatible service test completed successfully');
    }

    // ========================================
    // MULTIPART UPLOAD TESTS
    // ========================================

    /**
     * Test multipart upload for large files
     * Requirements: 10.3
     */
    public function test_multipart_upload_for_large_files(): void
    {
        Log::info('Starting multipart upload test');

        // Create a file larger than 50MB threshold (using 60MB for testing)
        $fileSize = 60 * 1024 * 1024; // 60MB
        $content = str_repeat('A', $fileSize);
        $testFile = $this->createTestFile($content);

        $targetPath = 'multipart-test@example.com';
        $originalFilename = 'large-file-' . uniqid() . '.txt';

        // Test optimizeUpload method
        $optimizations = $this->provider->optimizeUpload($this->testUser, $testFile, [
            'target_path' => $targetPath,
            'original_filename' => $originalFilename,
            'mime_type' => 'text/plain',
            'multipart_threshold' => 50 * 1024 * 1024, // 50MB
            'chunk_size' => 10 * 1024 * 1024, // 10MB chunks
            'track_progress' => true,
        ]);

        $this->assertTrue($optimizations['use_multipart']);
        $this->assertNotNull($optimizations['part_size']);
        $this->assertGreaterThan(0, $optimizations['total_parts']);
        $this->assertTrue($optimizations['upload_completed']);
        $this->assertArrayHasKey('upload_result', $optimizations);

        // Track the uploaded file for cleanup
        $fileId = $optimizations['upload_result']['key'];
        $this->createdFiles[] = [
            'file_id' => $fileId,
            'target_path' => $targetPath,
            'local_path' => $testFile,
        ];

        Log::info('Multipart upload test completed successfully', [
            'file_size' => $fileSize,
            'parts_uploaded' => $optimizations['upload_result']['parts_uploaded'],
        ]);
    }

    // ========================================
    // METADATA OPERATIONS TESTS
    // ========================================

    /**
     * Test file metadata operations
     * Requirements: 10.1
     */
    public function test_file_metadata_operations(): void
    {
        Log::info('Starting file metadata operations test');

        // Upload a test file
        $testFile = $this->createTestFile('Content for metadata test');
        $targetPath = 'metadata-test@example.com';
        $fileId = $this->uploadTestFile($testFile, $targetPath);

        // Get initial metadata
        $initialMetadata = $this->provider->getFileMetadata($this->testUser, $fileId);
        $this->assertIsArray($initialMetadata);

        // Set custom metadata
        $customMetadata = [
            'category' => 'integration-test',
            'priority' => 'high',
            'test_timestamp' => now()->toISOString(),
        ];

        $result = $this->provider->setFileMetadata($this->testUser, $fileId, $customMetadata);
        $this->assertTrue($result);

        // Get updated metadata
        $updatedMetadata = $this->provider->getFileMetadata($this->testUser, $fileId);
        $this->assertArrayHasKey('category', $updatedMetadata);
        $this->assertEquals('integration-test', $updatedMetadata['category']);

        Log::info('File metadata operations test completed successfully');
    }

    /**
     * Test file tagging operations
     * Requirements: 10.1
     */
    public function test_file_tagging_operations(): void
    {
        Log::info('Starting file tagging operations test');

        // Upload a test file
        $testFile = $this->createTestFile('Content for tagging test');
        $targetPath = 'tagging-test@example.com';
        $fileId = $this->uploadTestFile($testFile, $targetPath);

        // Add tags
        $tags = ['integration-test', 'automated', 'temporary'];
        $result = $this->provider->addFileTags($this->testUser, $fileId, $tags);
        $this->assertTrue($result);

        // Get tags
        $retrievedTags = $this->provider->getFileTags($this->testUser, $fileId);
        $this->assertIsArray($retrievedTags);
        $this->assertCount(3, $retrievedTags);
        $this->assertContains('integration-test', $retrievedTags);
        $this->assertContains('automated', $retrievedTags);
        $this->assertContains('temporary', $retrievedTags);

        Log::info('File tagging operations test completed successfully');
    }

    // ========================================
    // STORAGE CLASS TESTS
    // ========================================

    /**
     * Test storage class management
     * Requirements: 10.2, 10.5
     */
    public function test_storage_class_management(): void
    {
        Log::info('Starting storage class management test');

        // Get available storage classes
        $storageClasses = $this->provider->getAvailableStorageClasses();
        $this->assertIsArray($storageClasses);
        $this->assertArrayHasKey('STANDARD', $storageClasses);
        $this->assertArrayHasKey('STANDARD_IA', $storageClasses);
        $this->assertArrayHasKey('GLACIER', $storageClasses);
        $this->assertArrayHasKey('DEEP_ARCHIVE', $storageClasses);

        // Upload a test file
        $testFile = $this->createTestFile('Content for storage class test');
        $targetPath = 'storage-class-test@example.com';
        $fileId = $this->uploadTestFile($testFile, $targetPath);

        // Set storage class to STANDARD_IA
        $result = $this->provider->setStorageClass($this->testUser, $fileId, 'STANDARD_IA');
        $this->assertTrue($result);

        Log::info('Storage class management test completed successfully');
    }

    /**
     * Test invalid storage class handling
     * Requirements: 10.2
     */
    public function test_invalid_storage_class_handling(): void
    {
        // Upload a test file
        $testFile = $this->createTestFile('Content for invalid storage class test');
        $targetPath = 'invalid-storage-class-test@example.com';
        $fileId = $this->uploadTestFile($testFile, $targetPath);

        // Try to set invalid storage class
        $this->expectException(CloudStorageException::class);
        $this->provider->setStorageClass($this->testUser, $fileId, 'INVALID_CLASS');
    }

    // ========================================
    // ADDITIONAL CAPABILITY TESTS
    // ========================================

    /**
     * Test S3 presigned URLs capability reporting
     * Requirements: 5.3
     */
    public function test_s3_presigned_urls_capability(): void
    {
        $this->assertTrue($this->provider->supportsFeature('presigned_urls'));
    }
}