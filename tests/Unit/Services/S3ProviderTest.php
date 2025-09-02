<?php

namespace Tests\Unit\Services;

use App\Services\S3Provider;
use App\Services\S3ErrorHandler;
use App\Services\CloudStorageLogService;
use App\Models\User;
use App\Models\CloudStorageSetting;
use App\Exceptions\CloudStorageException;
use App\Exceptions\CloudStorageSetupException;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Command;
use Tests\TestCase;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class S3ProviderTest extends TestCase
{
    use RefreshDatabase;

    private S3Provider $provider;
    private S3ErrorHandler $errorHandler;
    private CloudStorageLogService $logService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->errorHandler = Mockery::mock(S3ErrorHandler::class);
        $this->logService = Mockery::mock(CloudStorageLogService::class);
        
        $this->provider = new S3Provider(
            $this->errorHandler,
            $this->logService
        );

        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_implements_cloud_storage_provider_interface(): void
    {
        $this->assertInstanceOf(\App\Contracts\CloudStorageProviderInterface::class, $this->provider);
    }

    public function test_get_provider_name(): void
    {
        $this->assertEquals('amazon-s3', $this->provider->getProviderName());
    }

    public function test_get_authentication_type(): void
    {
        $this->assertEquals('api_key', $this->provider->getAuthenticationType());
    }

    public function test_get_storage_model(): void
    {
        $this->assertEquals('flat', $this->provider->getStorageModel());
    }

    public function test_get_max_file_size(): void
    {
        $this->assertEquals(5497558138880, $this->provider->getMaxFileSize()); // 5TB
    }

    public function test_get_supported_file_types(): void
    {
        $this->assertEquals(['*'], $this->provider->getSupportedFileTypes());
    }

    public function test_get_capabilities(): void
    {
        $capabilities = $this->provider->getCapabilities();
        
        $this->assertIsArray($capabilities);
        $this->assertTrue($capabilities['file_upload']);
        $this->assertTrue($capabilities['file_delete']);
        $this->assertTrue($capabilities['presigned_urls']);
        $this->assertTrue($capabilities['storage_classes']);
        $this->assertFalse($capabilities['folder_creation']); // S3 uses key prefixes
        $this->assertFalse($capabilities['oauth_authentication']); // S3 uses API keys
        $this->assertTrue($capabilities['api_key_authentication']);
        $this->assertTrue($capabilities['flat_storage']);
        $this->assertFalse($capabilities['hierarchical_storage']);
    }

    public function test_supports_feature(): void
    {
        $this->assertTrue($this->provider->supportsFeature('file_upload'));
        $this->assertTrue($this->provider->supportsFeature('presigned_urls'));
        $this->assertFalse($this->provider->supportsFeature('folder_creation'));
        $this->assertFalse($this->provider->supportsFeature('oauth_authentication'));
        $this->assertFalse($this->provider->supportsFeature('nonexistent_feature'));
    }

    public function test_validate_configuration_with_valid_config(): void
    {
        $config = [
            'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'region' => 'us-east-1',
            'bucket' => 'my-test-bucket',
        ];

        $errors = $this->provider->validateConfiguration($config);
        $this->assertEmpty($errors);
    }

    public function test_validate_configuration_with_missing_required_keys(): void
    {
        $config = [
            'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            // Missing secret_access_key, region, bucket
        ];

        $errors = $this->provider->validateConfiguration($config);
        $this->assertCount(3, $errors);
        $this->assertContains('Missing required configuration key: secret_access_key', $errors);
        $this->assertContains('Missing required configuration key: region', $errors);
        $this->assertContains('Missing required configuration key: bucket', $errors);
    }

    public function test_validate_configuration_with_invalid_access_key_format(): void
    {
        $config = [
            'access_key_id' => 'invalid-key',
            'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'region' => 'us-east-1',
            'bucket' => 'my-test-bucket',
        ];

        $errors = $this->provider->validateConfiguration($config);
        $this->assertContains('Invalid AWS access_key_id format', $errors);
    }

    public function test_validate_configuration_with_invalid_secret_key_length(): void
    {
        $config = [
            'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'secret_access_key' => 'too-short',
            'region' => 'us-east-1',
            'bucket' => 'my-test-bucket',
        ];

        $errors = $this->provider->validateConfiguration($config);
        $this->assertContains('Invalid AWS secret_access_key format', $errors);
    }

    public function test_validate_configuration_with_invalid_region_format(): void
    {
        $config = [
            'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'region' => 'INVALID_REGION!',
            'bucket' => 'my-test-bucket',
        ];

        $errors = $this->provider->validateConfiguration($config);
        $this->assertContains('Invalid AWS region format', $errors);
    }

    public function test_validate_configuration_with_invalid_bucket_name(): void
    {
        $config = [
            'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'region' => 'us-east-1',
            'bucket' => 'INVALID_BUCKET_NAME',
        ];

        $errors = $this->provider->validateConfiguration($config);
        $this->assertContains('Invalid S3 bucket name format', $errors);
    }

    public function test_validate_configuration_with_invalid_endpoint(): void
    {
        $config = [
            'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'region' => 'us-east-1',
            'bucket' => 'my-test-bucket',
            'endpoint' => 'not-a-valid-url',
        ];

        $errors = $this->provider->validateConfiguration($config);
        $this->assertContains('Invalid endpoint URL format', $errors);
    }

    public function test_initialize_with_valid_config(): void
    {
        $config = [
            'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'region' => 'us-east-1',
            'bucket' => 'my-test-bucket',
        ];

        // Should not throw exception
        $this->provider->initialize($config);
        $this->assertTrue(true); // If we get here, initialization succeeded
    }

    public function test_initialize_with_invalid_config_throws_exception(): void
    {
        $config = [
            'access_key_id' => 'invalid',
            // Missing other required keys
        ];

        $this->expectException(CloudStorageSetupException::class);
        $this->expectExceptionMessage('S3 provider configuration is invalid');
        
        $this->provider->initialize($config);
    }

    public function test_oauth_methods_throw_feature_not_supported_exception(): void
    {
        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('S3 provider does not support OAuth authentication');
        
        $this->provider->handleAuthCallback($this->user, 'test-code');
    }

    public function test_get_auth_url_throws_feature_not_supported_exception(): void
    {
        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('S3 provider does not support OAuth authentication');
        
        $this->provider->getAuthUrl($this->user);
    }

    public function test_disconnect_clears_user_settings(): void
    {
        // Create some S3 settings for the user
        CloudStorageSetting::create([
            'user_id' => $this->user->id,
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
            'value' => 'AKIAIOSFODNN7EXAMPLE',
        ]);

        CloudStorageSetting::create([
            'user_id' => $this->user->id,
            'provider' => 'amazon-s3',
            'key' => 'secret_access_key',
            'value' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
        ]);

        $this->logService->shouldReceive('logOAuthEvent')
            ->with('amazon-s3', $this->user, 'disconnect_start', true)
            ->once();

        $this->logService->shouldReceive('logOAuthEvent')
            ->with('amazon-s3', $this->user, 'disconnect_complete', true)
            ->once();

        $this->provider->disconnect($this->user);

        // Verify settings were deleted
        $remainingSettings = CloudStorageSetting::where('user_id', $this->user->id)
            ->where('provider', 'amazon-s3')
            ->count();
        
        $this->assertEquals(0, $remainingSettings);
    }

    public function test_cleanup_clears_internal_state(): void
    {
        // Initialize provider first
        $config = [
            'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'region' => 'us-east-1',
            'bucket' => 'my-test-bucket',
        ];
        
        $this->provider->initialize($config);
        
        // Cleanup should not throw exception
        $this->provider->cleanup();
        $this->assertTrue(true); // If we get here, cleanup succeeded
    }

    /**
     * Test bucket name validation edge cases
     */
    public function test_bucket_name_validation_edge_cases(): void
    {
        $testCases = [
            // Valid names
            ['my-bucket', true],
            ['my-bucket-123', true],
            ['123-bucket', true],
            ['a' . str_repeat('b', 61), true], // 63 characters total
            
            // Invalid names
            ['ab', false], // Too short
            [str_repeat('a', 64), false], // Too long
            ['-bucket', false], // Starts with hyphen
            ['bucket-', false], // Ends with hyphen
            ['my--bucket', false], // Consecutive hyphens
            ['192.168.1.1', false], // IP address format
            ['My-Bucket', false], // Uppercase letters
            ['my_bucket', false], // Underscore
        ];

        foreach ($testCases as [$bucketName, $shouldBeValid]) {
            $config = [
                'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
                'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
                'region' => 'us-east-1',
                'bucket' => $bucketName,
            ];

            $errors = $this->provider->validateConfiguration($config);
            $hasError = in_array('Invalid S3 bucket name format', $errors);

            if ($shouldBeValid) {
                $this->assertFalse($hasError, "Bucket name '{$bucketName}' should be valid but was rejected");
            } else {
                $this->assertTrue($hasError, "Bucket name '{$bucketName}' should be invalid but was accepted");
            }
        }
    }
}