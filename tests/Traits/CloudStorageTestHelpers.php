<?php

namespace Tests\Traits;

use App\Models\User;
use App\Models\CloudStorageSetting;
use App\Models\GoogleDriveToken;
use Tests\Mocks\MockCloudStorageProvider;
use Tests\Mocks\FailingMockCloudStorageProvider;
use Illuminate\Support\Facades\Config;

/**
 * Trait providing common helper methods for cloud storage testing.
 */
trait CloudStorageTestHelpers
{
    /**
     * Create a test user with cloud storage configuration.
     */
    protected function createUserWithCloudStorage(array $userAttributes = [], array $storageConfig = []): User
    {
        $user = User::factory()->create($userAttributes);

        if (!empty($storageConfig)) {
            foreach ($storageConfig as $provider => $config) {
                CloudStorageSetting::create([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'settings' => $config,
                ]);
            }
        }

        return $user;
    }

    /**
     * Create a test user with Google Drive token.
     */
    protected function createUserWithGoogleDriveToken(array $userAttributes = [], array $tokenAttributes = []): User
    {
        $user = User::factory()->create($userAttributes);

        $defaultTokenAttributes = [
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_at' => now()->addHour(),
            'token_type' => 'Bearer',
            'scopes' => ['https://www.googleapis.com/auth/drive.file'],
        ];

        GoogleDriveToken::create(array_merge($defaultTokenAttributes, $tokenAttributes, [
            'user_id' => $user->id,
        ]));

        return $user;
    }

    /**
     * Create a temporary test file with specified content.
     */
    protected function createTempFile(string $content = 'test content', string $extension = 'txt'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'cloud_storage_test_') . '.' . $extension;
        file_put_contents($tempFile, $content);
        return $tempFile;
    }

    /**
     * Create a large temporary test file.
     */
    protected function createLargeTempFile(int $sizeInBytes = 1024 * 1024): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'cloud_storage_large_test_') . '.txt';
        $content = str_repeat('A', $sizeInBytes);
        file_put_contents($tempFile, $content);
        return $tempFile;
    }

    /**
     * Clean up temporary files.
     */
    protected function cleanupTempFiles(array $files): void
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Set up mock cloud storage provider.
     */
    protected function setupMockProvider(array $capabilities = [], bool $shouldFail = false): MockCloudStorageProvider
    {
        if ($shouldFail) {
            $provider = new FailingMockCloudStorageProvider();
        } else {
            $provider = new MockCloudStorageProvider();
            if (!empty($capabilities)) {
                $provider->setCapabilities($capabilities);
            }
        }

        $this->app->bind('mock-provider', function () use ($provider) {
            return $provider;
        });

        return $provider;
    }

    /**
     * Configure cloud storage settings for testing.
     */
    protected function configureCloudStorage(array $providers = []): void
    {
        $defaultConfig = [
            'default' => 'mock-provider',
            'providers' => [
                'mock-provider' => [
                    'driver' => 'mock-provider',
                    'class' => MockCloudStorageProvider::class,
                    'auth_type' => 'api_key',
                    'storage_model' => 'hierarchical',
                    'config' => [
                        'api_key' => 'test-api-key',
                        'endpoint' => 'https://mock.example.com',
                    ],
                    'features' => [
                        'file_upload' => true,
                        'file_delete' => true,
                        'folder_creation' => true,
                        'folder_delete' => true,
                    ],
                ],
            ],
        ];

        $config = array_merge_recursive($defaultConfig, ['providers' => $providers]);
        Config::set('cloud-storage', $config);
    }

    /**
     * Assert that a file was uploaded to the mock provider.
     */
    protected function assertFileWasUploaded(MockCloudStorageProvider $provider, string $targetPath): void
    {
        $this->assertTrue(
            $provider->wasFileUploaded($targetPath),
            "File was not uploaded to path: {$targetPath}"
        );
    }

    /**
     * Assert that a file was deleted from the mock provider.
     */
    protected function assertFileWasDeleted(MockCloudStorageProvider $provider, string $fileId): void
    {
        $this->assertTrue(
            $provider->wasFileDeleted($fileId),
            "File was not deleted: {$fileId}"
        );
    }

    /**
     * Assert that a user was authenticated with the mock provider.
     */
    protected function assertUserWasAuthenticated(MockCloudStorageProvider $provider, int $userId): void
    {
        $this->assertTrue(
            $provider->wasUserAuthenticated($userId),
            "User was not authenticated: {$userId}"
        );
    }

    /**
     * Assert that a user was disconnected from the mock provider.
     */
    protected function assertUserWasDisconnected(MockCloudStorageProvider $provider, int $userId): void
    {
        $this->assertTrue(
            $provider->wasUserDisconnected($userId),
            "User was not disconnected: {$userId}"
        );
    }

    /**
     * Get uploaded files from mock provider.
     */
    protected function getUploadedFiles(MockCloudStorageProvider $provider): array
    {
        return $provider->getUploadedFiles();
    }

    /**
     * Get deleted files from mock provider.
     */
    protected function getDeletedFiles(MockCloudStorageProvider $provider): array
    {
        return $provider->getDeletedFiles();
    }

    /**
     * Create test metadata array.
     */
    protected function createTestMetadata(array $additional = []): array
    {
        return array_merge([
            'description' => 'Test file upload',
            'category' => 'test',
            'uploaded_by' => 'test-user',
            'content_type' => 'text/plain',
        ], $additional);
    }

    /**
     * Skip test if integration tests are disabled.
     */
    protected function skipIfIntegrationTestsDisabled(): void
    {
        if (env('SKIP_INTEGRATION_TESTS', true)) {
            $this->markTestSkipped('Integration tests are disabled');
        }
    }

    /**
     * Skip test if required environment variable is missing.
     */
    protected function skipIfEnvMissing(string $envVar): void
    {
        if (empty(env($envVar))) {
            $this->markTestSkipped("Required environment variable {$envVar} is not set");
        }
    }

    /**
     * Assert provider capabilities.
     */
    protected function assertProviderCapabilities(array $expectedCapabilities, array $actualCapabilities): void
    {
        foreach ($expectedCapabilities as $capability => $expected) {
            $this->assertArrayHasKey($capability, $actualCapabilities, "Capability '{$capability}' is missing");
            $this->assertEquals($expected, $actualCapabilities[$capability], "Capability '{$capability}' has wrong value");
        }
    }

    /**
     * Assert provider configuration validation.
     */
    protected function assertConfigurationValidation(array $config, array $expectedErrors, $provider): void
    {
        $errors = $provider->validateConfiguration($config);
        
        if (empty($expectedErrors)) {
            $this->assertEmpty($errors, 'Configuration should be valid but has errors: ' . implode(', ', $errors));
        } else {
            $this->assertNotEmpty($errors, 'Configuration should have errors but is valid');
            foreach ($expectedErrors as $expectedError) {
                $this->assertContains($expectedError, $errors, "Expected error '{$expectedError}' not found");
            }
        }
    }

    /**
     * Create test configuration for a provider.
     */
    protected function createTestConfig(string $provider): array
    {
        return match ($provider) {
            'google-drive' => [
                'client_id' => 'test-client-id',
                'client_secret' => 'test-client-secret',
                'redirect_uri' => 'https://example.com/callback',
            ],
            'amazon-s3' => [
                'access_key_id' => 'test-access-key',
                'secret_access_key' => 'test-secret-key',
                'region' => 'us-east-1',
                'bucket' => 'test-bucket',
            ],
            'azure-blob' => [
                'connection_string' => 'DefaultEndpointsProtocol=https;AccountName=test;AccountKey=test;EndpointSuffix=core.windows.net',
                'container' => 'test-container',
            ],
            default => [
                'api_key' => 'test-api-key',
                'endpoint' => 'https://api.example.com',
            ],
        };
    }

    /**
     * Assert health status properties.
     */
    protected function assertHealthStatus($health, bool $expectedConnected, string $expectedStatus): void
    {
        $this->assertNotNull($health, 'Health status should not be null');
        $this->assertEquals($expectedConnected, $health->isConnected, 'Connection status mismatch');
        $this->assertEquals($expectedStatus, $health->status, 'Health status mismatch');
        $this->assertNotNull($health->lastChecked, 'Last checked should not be null');
    }

    /**
     * Create a test file with specific MIME type.
     */
    protected function createTestFileWithMimeType(string $mimeType): string
    {
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            'text/csv' => 'csv',
            'application/json' => 'json',
            default => 'txt',
        };

        $content = match ($mimeType) {
            'application/json' => '{"test": "data"}',
            'text/csv' => "name,email\nTest User,test@example.com",
            default => 'Test file content for ' . $mimeType,
        };

        return $this->createTempFile($content, $extension);
    }
}