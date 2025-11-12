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

    /**
     * Test S3 key generation with various inputs
     */
    public function test_generates_s3_key_correctly(): void
    {
        // Create settings for the user
        CloudStorageSetting::create([
            'user_id' => null, // System-level
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
            'value' => encrypt('AKIAIOSFODNN7EXAMPLE'),
            'is_encrypted' => true,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'secret_access_key',
            'value' => encrypt('wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY'),
            'is_encrypted' => true,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'region',
            'value' => 'us-east-1',
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'bucket',
            'value' => 'test-bucket',
        ]);

        // Mock the S3Client and its methods
        $mockS3Client = Mockery::mock(S3Client::class);
        $mockS3Client->shouldReceive('putObject')
            ->once()
            ->andReturnUsing(function ($params) {
                // Verify the key format
                $key = $params['Key'];
                $this->assertStringContainsString('/', $key);
                $this->assertStringContainsString('test.pdf', $key);
                
                return ['ETag' => '"abc123"'];
            });

        // Mock log service
        $this->logService->shouldReceive('logOperationStart')->andReturn('op-123');
        $this->logService->shouldReceive('logOperationSuccess')->once();
        
        // Mock error handler (in case of errors)
        $this->errorHandler->shouldReceive('classifyError')->andReturn(\App\Enums\CloudStorageErrorType::UNKNOWN_ERROR);

        // Use reflection to inject the mock S3Client
        $reflection = new \ReflectionClass($this->provider);
        $property = $reflection->getProperty('s3Client');
        $property->setAccessible(true);
        $property->setValue($this->provider, $mockS3Client);

        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue($this->provider, [
            'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
        ]);

        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        try {
            $key = $this->provider->uploadFile(
                $this->user,
                $tempFile,
                'client@example.com',
                ['original_filename' => 'test.pdf', 'mime_type' => 'application/pdf']
            );

            // Verify key format: should be client_example_com/test_TIMESTAMP_RANDOM.pdf
            $this->assertStringContainsString('/', $key);
            $this->assertStringContainsString('.pdf', $key);
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test S3 key generation sanitizes special characters
     */
    public function test_s3_key_generation_sanitizes_special_characters(): void
    {
        // Create settings
        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
            'value' => encrypt('AKIAIOSFODNN7EXAMPLE'),
            'is_encrypted' => true,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'secret_access_key',
            'value' => encrypt('wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY'),
            'is_encrypted' => true,
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'region',
            'value' => 'us-east-1',
        ]);

        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'bucket',
            'value' => 'test-bucket',
        ]);

        $mockS3Client = Mockery::mock(S3Client::class);
        $mockS3Client->shouldReceive('putObject')
            ->once()
            ->andReturnUsing(function ($params) {
                $key = $params['Key'];
                // Verify no special characters except allowed ones
                $this->assertDoesNotMatchRegularExpression('/[^a-zA-Z0-9\-_\.\/]/', $key);
                return ['ETag' => '"abc123"'];
            });

        $this->logService->shouldReceive('logOperationStart')->andReturn('op-123');
        $this->logService->shouldReceive('logOperationSuccess')->once();
        $this->errorHandler->shouldReceive('classifyError')->andReturn(\App\Enums\CloudStorageErrorType::UNKNOWN_ERROR);

        $reflection = new \ReflectionClass($this->provider);
        $property = $reflection->getProperty('s3Client');
        $property->setAccessible(true);
        $property->setValue($this->provider, $mockS3Client);

        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue($this->provider, [
            'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        try {
            $key = $this->provider->uploadFile(
                $this->user,
                $tempFile,
                'client+special@example.com',
                ['original_filename' => 'test file (1).pdf', 'mime_type' => 'application/pdf']
            );

            $this->assertIsString($key);
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Test custom endpoint support for S3-compatible services
     */
    public function test_initialize_with_custom_endpoint(): void
    {
        $config = [
            'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'region' => 'us-east-1',
            'bucket' => 'my-test-bucket',
            'endpoint' => 'https://s3.cloudflare.com',
        ];

        // Should not throw exception
        $this->provider->initialize($config);
        $this->assertTrue(true);
    }

    /**
     * Test validation accepts valid custom endpoint
     */
    public function test_validate_configuration_with_valid_custom_endpoint(): void
    {
        $config = [
            'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'region' => 'auto',
            'bucket' => 'my-test-bucket',
            'endpoint' => 'https://s3.us-west-004.backblazeb2.com',
        ];

        $errors = $this->provider->validateConfiguration($config);
        $this->assertEmpty($errors);
    }

    /**
     * Test has_valid_connection returns false when not configured
     */
    public function test_has_valid_connection_returns_false_when_not_configured(): void
    {
        $result = $this->provider->hasValidConnection($this->user);
        $this->assertFalse($result);
    }

    /**
     * Test get_available_storage_classes returns correct classes
     */
    public function test_get_available_storage_classes(): void
    {
        $classes = $this->provider->getAvailableStorageClasses();
        
        $this->assertIsArray($classes);
        $this->assertArrayHasKey('STANDARD', $classes);
        $this->assertArrayHasKey('STANDARD_IA', $classes);
        $this->assertArrayHasKey('GLACIER', $classes);
        $this->assertArrayHasKey('DEEP_ARCHIVE', $classes);
        $this->assertArrayHasKey('INTELLIGENT_TIERING', $classes);
        
        // Verify structure
        $this->assertArrayHasKey('name', $classes['STANDARD']);
        $this->assertArrayHasKey('description', $classes['STANDARD']);
        $this->assertArrayHasKey('cost_tier', $classes['STANDARD']);
    }

    /**
     * Test configuration validation with all valid fields
     */
    public function test_validate_configuration_comprehensive(): void
    {
        $config = [
            'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'region' => 'eu-west-1',
            'bucket' => 'my-company-files-2024',
            'endpoint' => 'https://s3.example.com',
        ];

        $errors = $this->provider->validateConfiguration($config);
        $this->assertEmpty($errors, 'Valid configuration should have no errors');
    }

    /**
     * Test multiple validation errors are returned
     */
    public function test_validate_configuration_returns_multiple_errors(): void
    {
        $config = [
            'access_key_id' => 'invalid',
            'secret_access_key' => 'short',
            'region' => 'INVALID!',
            'bucket' => 'INVALID_BUCKET',
        ];

        $errors = $this->provider->validateConfiguration($config);
        $this->assertGreaterThanOrEqual(4, count($errors));
    }

    /**
     * Test region validation accepts various formats
     */
    public function test_region_validation_accepts_various_formats(): void
    {
        $validRegions = [
            'us-east-1',
            'us-west-2',
            'eu-west-1',
            'ap-southeast-1',
            'auto', // For S3-compatible services
            'us-east-005', // For Backblaze B2
        ];

        foreach ($validRegions as $region) {
            $config = [
                'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
                'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
                'region' => $region,
                'bucket' => 'test-bucket',
            ];

            $errors = $this->provider->validateConfiguration($config);
            $this->assertEmpty($errors, "Region '{$region}' should be valid");
        }
    }

    /**
     * Test disconnect handles errors gracefully
     */
    public function test_disconnect_handles_errors_gracefully(): void
    {
        // Create settings that will be deleted
        CloudStorageSetting::create([
            'user_id' => null,
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
            'value' => 'AKIAIOSFODNN7EXAMPLE',
        ]);

        $this->logService->shouldReceive('logOAuthEvent')
            ->with('amazon-s3', $this->user, 'disconnect_start', true)
            ->once();

        $this->logService->shouldReceive('logOAuthEvent')
            ->with('amazon-s3', $this->user, 'disconnect_complete', true)
            ->once();

        // Should not throw exception even if there are issues
        $this->provider->disconnect($this->user);
        
        $this->assertTrue(true);
    }

    /**
     * Test access key validation with edge cases
     */
    public function test_access_key_validation_edge_cases(): void
    {
        $testCases = [
            ['AKIAIOSFODNN7EXAMPLE', true], // Valid: 20 uppercase alphanumeric
            ['AKIAIOSFODNN7EXAMPL', false], // Invalid: 19 characters
            ['AKIAIOSFODNN7EXAMPLEX', false], // Invalid: 21 characters
            ['akiaiosfodnn7example', false], // Invalid: lowercase
            ['AKIAIOSFODNN7EXAMPL!', false], // Invalid: special character
            ['AKIA1234567890123456', true], // Valid: with numbers
        ];

        foreach ($testCases as [$accessKey, $shouldBeValid]) {
            $config = [
                'access_key_id' => $accessKey,
                'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
                'region' => 'us-east-1',
                'bucket' => 'test-bucket',
            ];

            $errors = $this->provider->validateConfiguration($config);
            $hasError = in_array('Invalid AWS access_key_id format', $errors);

            if ($shouldBeValid) {
                $this->assertFalse($hasError, "Access key '{$accessKey}' should be valid");
            } else {
                $this->assertTrue($hasError, "Access key '{$accessKey}' should be invalid");
            }
        }
    }

    /**
     * Test secret key validation with edge cases
     */
    public function test_secret_key_validation_edge_cases(): void
    {
        $testCases = [
            ['wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY', true], // Valid: 40 characters
            ['wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKE', false], // Invalid: 39 characters
            ['wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEYX', false], // Invalid: 41 characters
            [str_repeat('a', 40), true], // Valid: exactly 40 characters
            [str_repeat('a', 39), false], // Invalid: 39 characters
        ];

        foreach ($testCases as [$secretKey, $shouldBeValid]) {
            $config = [
                'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
                'secret_access_key' => $secretKey,
                'region' => 'us-east-1',
                'bucket' => 'test-bucket',
            ];

            $errors = $this->provider->validateConfiguration($config);
            $hasError = in_array('Invalid AWS secret_access_key format', $errors);

            if ($shouldBeValid) {
                $this->assertFalse($hasError, "Secret key length " . strlen($secretKey) . " should be valid");
            } else {
                $this->assertTrue($hasError, "Secret key length " . strlen($secretKey) . " should be invalid");
            }
        }
    }
}