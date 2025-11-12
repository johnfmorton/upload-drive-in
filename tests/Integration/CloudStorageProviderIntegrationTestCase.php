<?php

namespace Tests\Integration;

use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Base test case for integration testing with real cloud storage provider APIs.
 * 
 * This class provides helpers for testing against actual provider APIs
 * while handling authentication, cleanup, and error scenarios.
 */
abstract class CloudStorageProviderIntegrationTestCase extends TestCase
{
    use RefreshDatabase;

    protected CloudStorageProviderInterface $provider;
    protected User $testUser;
    protected array $createdFiles = [];
    protected array $createdFolders = [];

    /**
     * Get the provider name for integration testing.
     */
    abstract protected function getProviderName(): string;

    /**
     * Create a provider instance for integration testing.
     */
    abstract protected function createProvider(): CloudStorageProviderInterface;

    /**
     * Get integration test configuration.
     */
    abstract protected function getIntegrationConfig(): array;

    /**
     * Check if integration tests should be skipped.
     */
    abstract protected function shouldSkipIntegrationTests(): bool;

    /**
     * Set up the integration test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->shouldSkipIntegrationTests()) {
            $this->markTestSkipped('Integration tests are disabled or not configured');
        }

        $this->provider = $this->createProvider();
        $this->testUser = $this->createTestUser();
        
        // Initialize provider with integration config
        try {
            $this->provider->initialize($this->getIntegrationConfig());
        } catch (\Exception $e) {
            $this->markTestSkipped('Provider initialization failed: ' . $e->getMessage());
        }

        Log::info('Starting integration test', [
            'provider' => $this->getProviderName(),
            'test' => $this->getName(),
        ]);
    }

    /**
     * Clean up after integration tests.
     */
    protected function tearDown(): void
    {
        if (!$this->shouldSkipIntegrationTests()) {
            $this->cleanupTestFiles();
            $this->cleanupTestFolders();
        }

        Log::info('Completed integration test', [
            'provider' => $this->getProviderName(),
            'test' => $this->getName(),
        ]);

        parent::tearDown();
    }

    /**
     * Create a test user for integration testing.
     */
    protected function createTestUser(): User
    {
        return User::factory()->create([
            'email' => 'integration-test@example.com',
            'name' => 'Integration Test User',
        ]);
    }

    /**
     * Create a temporary test file.
     */
    protected function createTestFile(string $content = 'Integration test content', string $extension = 'txt'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'integration_test_') . '.' . $extension;
        file_put_contents($tempFile, $content);
        return $tempFile;
    }

    /**
     * Upload a test file and track it for cleanup.
     */
    protected function uploadTestFile(string $localPath, string $targetPath, array $metadata = []): string
    {
        $fileId = $this->provider->uploadFile($this->testUser, $localPath, $targetPath, $metadata);
        
        $this->createdFiles[] = [
            'file_id' => $fileId,
            'target_path' => $targetPath,
            'local_path' => $localPath,
        ];

        return $fileId;
    }

    /**
     * Create a test folder and track it for cleanup.
     */
    protected function createTestFolder(string $folderPath): string
    {
        // This would need to be implemented based on provider capabilities
        // For now, we'll track the folder path for cleanup
        $this->createdFolders[] = $folderPath;
        return $folderPath;
    }

    /**
     * Clean up test files from the provider.
     */
    protected function cleanupTestFiles(): void
    {
        foreach ($this->createdFiles as $file) {
            try {
                $this->provider->deleteFile($this->testUser, $file['file_id']);
                Log::info('Cleaned up test file', ['file_id' => $file['file_id']]);
            } catch (\Exception $e) {
                Log::warning('Failed to cleanup test file', [
                    'file_id' => $file['file_id'],
                    'error' => $e->getMessage(),
                ]);
            }

            // Clean up local temp file if it exists
            if (isset($file['local_path']) && file_exists($file['local_path'])) {
                unlink($file['local_path']);
            }
        }
    }

    /**
     * Clean up test folders from the provider.
     */
    protected function cleanupTestFolders(): void
    {
        // Folder cleanup would be provider-specific
        // This is a placeholder for providers that support folder operations
        foreach ($this->createdFolders as $folderPath) {
            Log::info('Test folder cleanup needed', ['folder_path' => $folderPath]);
        }
    }

    /**
     * Test basic file upload functionality.
     */
    public function test_can_upload_file(): void
    {
        $testFile = $this->createTestFile('Integration test upload content');
        $targetPath = 'integration-tests/test-upload-' . uniqid() . '.txt';

        $fileId = $this->uploadTestFile($testFile, $targetPath);

        $this->assertNotEmpty($fileId);
        $this->assertIsString($fileId);
    }

    /**
     * Test basic file deletion functionality.
     */
    public function test_can_delete_file(): void
    {
        // First upload a file
        $testFile = $this->createTestFile('Integration test delete content');
        $targetPath = 'integration-tests/test-delete-' . uniqid() . '.txt';
        $fileId = $this->uploadTestFile($testFile, $targetPath);

        // Then delete it
        $result = $this->provider->deleteFile($this->testUser, $fileId);

        $this->assertTrue($result);

        // Remove from cleanup list since we already deleted it
        $this->createdFiles = array_filter($this->createdFiles, function ($file) use ($fileId) {
            return $file['file_id'] !== $fileId;
        });
    }

    /**
     * Test connection health check.
     */
    public function test_connection_health_check(): void
    {
        $health = $this->provider->getConnectionHealth($this->testUser);

        $this->assertNotNull($health);
        $this->assertTrue($health->isHealthy() || $health->isDegraded());
        $this->assertNotEmpty($health->status);
    }

    /**
     * Test provider capabilities.
     */
    public function test_provider_capabilities(): void
    {
        $capabilities = $this->provider->getCapabilities();

        $this->assertIsArray($capabilities);
        $this->assertNotEmpty($capabilities);
        $this->assertArrayHasKey('file_upload', $capabilities);
        $this->assertArrayHasKey('file_delete', $capabilities);
    }

    /**
     * Test file upload with metadata.
     */
    public function test_can_upload_file_with_metadata(): void
    {
        $testFile = $this->createTestFile('Integration test with metadata');
        $targetPath = 'integration-tests/test-metadata-' . uniqid() . '.txt';
        $metadata = [
            'description' => 'Integration test file',
            'category' => 'test',
            'uploaded_by' => 'integration-test',
        ];

        $fileId = $this->uploadTestFile($testFile, $targetPath, $metadata);

        $this->assertNotEmpty($fileId);
        $this->assertIsString($fileId);
    }

    /**
     * Test uploading different file types.
     */
    public function test_can_upload_different_file_types(): void
    {
        $supportedTypes = $this->provider->getSupportedFileTypes();
        
        if (in_array('*', $supportedTypes) || in_array('image/jpeg', $supportedTypes)) {
            // Create a simple JPEG-like file (not a real image, just for testing)
            $testFile = $this->createTestFile('fake jpeg content', 'jpg');
            $targetPath = 'integration-tests/test-image-' . uniqid() . '.jpg';

            $fileId = $this->uploadTestFile($testFile, $targetPath);

            $this->assertNotEmpty($fileId);
        }

        if (in_array('*', $supportedTypes) || in_array('application/pdf', $supportedTypes)) {
            // Create a simple PDF-like file (not a real PDF, just for testing)
            $testFile = $this->createTestFile('fake pdf content', 'pdf');
            $targetPath = 'integration-tests/test-document-' . uniqid() . '.pdf';

            $fileId = $this->uploadTestFile($testFile, $targetPath);

            $this->assertNotEmpty($fileId);
        }
    }

    /**
     * Test large file upload (within limits).
     */
    public function test_can_upload_large_file(): void
    {
        $maxFileSize = $this->provider->getMaxFileSize();
        
        // Create a file that's 1MB or 10% of max size, whichever is smaller
        $testSize = min(1024 * 1024, intval($maxFileSize * 0.1));
        $content = str_repeat('A', $testSize);
        
        $testFile = $this->createTestFile($content);
        $targetPath = 'integration-tests/test-large-' . uniqid() . '.txt';

        $fileId = $this->uploadTestFile($testFile, $targetPath);

        $this->assertNotEmpty($fileId);
        $this->assertIsString($fileId);
    }

    /**
     * Test error handling for invalid operations.
     */
    public function test_handles_invalid_file_deletion(): void
    {
        $this->expectException(\Exception::class);
        
        $this->provider->deleteFile($this->testUser, 'nonexistent-file-id');
    }

    /**
     * Helper method to check if a provider feature is available.
     */
    protected function requiresFeature(string $feature): void
    {
        if (!$this->provider->supportsFeature($feature)) {
            $this->markTestSkipped("Provider does not support feature: {$feature}");
        }
    }

    /**
     * Helper method to get environment variable or skip test.
     */
    protected function getRequiredEnvVar(string $key): string
    {
        $value = env($key);
        if (empty($value)) {
            $this->markTestSkipped("Required environment variable {$key} is not set");
        }
        return $value;
    }
}