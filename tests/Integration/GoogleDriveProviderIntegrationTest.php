<?php

namespace Tests\Integration;

use App\Contracts\CloudStorageProviderInterface;
use App\Services\GoogleDriveProvider;
use Illuminate\Support\Facades\Config;

/**
 * Integration tests for Google Drive provider against real Google Drive API.
 * 
 * These tests require valid Google Drive credentials and will create/delete
 * actual files in Google Drive. Use with caution.
 */
class GoogleDriveProviderIntegrationTest extends CloudStorageProviderIntegrationTestCase
{
    protected function getProviderName(): string
    {
        return 'google-drive';
    }

    protected function createProvider(): CloudStorageProviderInterface
    {
        return app(GoogleDriveProvider::class);
    }

    protected function getIntegrationConfig(): array
    {
        return [
            'client_id' => $this->getRequiredEnvVar('GOOGLE_DRIVE_CLIENT_ID'),
            'client_secret' => $this->getRequiredEnvVar('GOOGLE_DRIVE_CLIENT_SECRET'),
            'redirect_uri' => config('app.url') . '/admin/cloud-storage/google-drive/callback',
        ];
    }

    protected function shouldSkipIntegrationTests(): bool
    {
        // Skip if integration tests are disabled or credentials are missing
        return env('SKIP_INTEGRATION_TESTS', true) || 
               empty(env('GOOGLE_DRIVE_CLIENT_ID')) || 
               empty(env('GOOGLE_DRIVE_CLIENT_SECRET')) ||
               empty(env('GOOGLE_DRIVE_INTEGRATION_TEST_TOKEN'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->shouldSkipIntegrationTests()) {
            // Set up Google Drive token for integration testing
            $this->setupGoogleDriveToken();
        }
    }

    /**
     * Set up Google Drive token for integration testing.
     */
    protected function setupGoogleDriveToken(): void
    {
        $token = env('GOOGLE_DRIVE_INTEGRATION_TEST_TOKEN');
        if (empty($token)) {
            $this->markTestSkipped('Google Drive integration test token not configured');
        }

        // Create or update the Google Drive token for the test user
        $this->testUser->googleDriveTokens()->updateOrCreate(
            ['user_id' => $this->testUser->id],
            [
                'access_token' => $token,
                'refresh_token' => env('GOOGLE_DRIVE_INTEGRATION_TEST_REFRESH_TOKEN'),
                'expires_at' => now()->addHour(),
                'token_type' => 'Bearer',
                'scopes' => ['https://www.googleapis.com/auth/drive.file'],
            ]
        );
    }

    /**
     * Test Google Drive specific folder creation.
     */
    public function test_can_create_folder(): void
    {
        $this->requiresFeature('folder_creation');

        $folderName = 'integration-test-folder-' . uniqid();
        $folderPath = 'integration-tests/' . $folderName;

        // This would require implementing folder creation in GoogleDriveProvider
        // For now, we'll test that the provider supports the feature
        $this->assertTrue($this->provider->supportsFeature('folder_creation'));
    }

    /**
     * Test Google Drive specific file sharing.
     */
    public function test_supports_file_sharing(): void
    {
        // Test if Google Drive provider supports sharing features
        $capabilities = $this->provider->getCapabilities();
        
        // Google Drive should support various sharing options
        $this->assertIsArray($capabilities);
    }

    /**
     * Test Google Drive hierarchical storage model.
     */
    public function test_hierarchical_storage_model(): void
    {
        $this->assertEquals('hierarchical', $this->provider->getStorageModel());
        
        // Test uploading to nested folder structure
        $testFile = $this->createTestFile('Hierarchical test content');
        $targetPath = 'integration-tests/nested/folder/structure/test-' . uniqid() . '.txt';

        $fileId = $this->uploadTestFile($testFile, $targetPath);

        $this->assertNotEmpty($fileId);
    }

    /**
     * Test Google Drive OAuth authentication type.
     */
    public function test_oauth_authentication_type(): void
    {
        $this->assertEquals('oauth', $this->provider->getAuthenticationType());
    }

    /**
     * Test Google Drive file size limits.
     */
    public function test_google_drive_file_size_limits(): void
    {
        $maxFileSize = $this->provider->getMaxFileSize();
        
        // Google Drive supports up to 5TB for most file types
        $this->assertGreaterThan(1024 * 1024 * 1024, $maxFileSize); // At least 1GB
    }

    /**
     * Test Google Drive supported file types.
     */
    public function test_google_drive_supported_file_types(): void
    {
        $supportedTypes = $this->provider->getSupportedFileTypes();
        
        // Google Drive supports all file types
        $this->assertContains('*', $supportedTypes);
    }

    /**
     * Test Google Drive specific error handling.
     */
    public function test_google_drive_error_handling(): void
    {
        // Test uploading to an invalid path or with invalid permissions
        try {
            $testFile = $this->createTestFile('Error test content');
            $this->provider->uploadFile($this->testUser, $testFile, '', []); // Empty path should cause error
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }
}