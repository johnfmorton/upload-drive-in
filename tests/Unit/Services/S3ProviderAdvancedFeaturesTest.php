<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\S3Provider;
use App\Services\S3ErrorHandler;
use App\Services\CloudStorageLogService;
use App\Models\User;
use App\Models\CloudStorageSetting;
use App\Exceptions\CloudStorageException;
use Aws\S3\S3Client;
use Aws\Command;
use Aws\Result;
use Aws\Exception\AwsException;
use Mockery;
class S3ProviderAdvancedFeaturesTest extends TestCase
{

    private S3Provider $provider;
    private S3Client $mockS3Client;
    private S3ErrorHandler $mockErrorHandler;
    private CloudStorageLogService $mockLogService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockS3Client = Mockery::mock(S3Client::class);
        $this->mockErrorHandler = Mockery::mock(S3ErrorHandler::class);
        $this->mockLogService = Mockery::mock(CloudStorageLogService::class);

        $this->provider = new S3Provider($this->mockErrorHandler, $this->mockLogService);

        // Create test user (mock)
        $this->user = Mockery::mock(User::class);
        $this->user->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->user->shouldReceive('setAttribute')->andReturnSelf();
        $this->user->id = 1;

        // Mock the S3 client creation
        $this->mockS3ClientCreation();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockS3ClientCreation(): void
    {
        // Use reflection to set the private s3Client property
        $reflection = new \ReflectionClass($this->provider);
        $s3ClientProperty = $reflection->getProperty('s3Client');
        $s3ClientProperty->setAccessible(true);
        $s3ClientProperty->setValue($this->provider, $this->mockS3Client);

        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue($this->provider, [
            'access_key_id' => 'AKIAIOSFODNN7EXAMPLE',
            'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
        ]);
    }

    public function test_generate_presigned_url_for_download(): void
    {
        $fileId = 'test-folder/test-file.txt';
        $expirationMinutes = 60;
        $expectedUrl = 'https://test-bucket.s3.amazonaws.com/test-folder/test-file.txt?X-Amz-Signature=example';

        // Mock logging
        $this->mockLogService->shouldReceive('logOperationStart')
            ->once()
            ->andReturn('operation-id-123');

        $this->mockLogService->shouldReceive('logOperationSuccess')
            ->once();

        // Mock S3 client methods
        $mockCommand = Mockery::mock(Command::class);
        $this->mockS3Client->shouldReceive('getCommand')
            ->once()
            ->with('GetObject', [
                'Bucket' => 'test-bucket',
                'Key' => $fileId,
            ])
            ->andReturn($mockCommand);

        $mockRequest = Mockery::mock();
        $mockRequest->shouldReceive('getUri')
            ->once()
            ->andReturn($expectedUrl);

        $this->mockS3Client->shouldReceive('createPresignedRequest')
            ->once()
            ->with($mockCommand, "+{$expirationMinutes} minutes")
            ->andReturn($mockRequest);

        $result = $this->provider->generatePresignedUrl($this->user, $fileId, $expirationMinutes, 'download');

        $this->assertEquals($expectedUrl, $result);
    }

    public function test_generate_presigned_url_for_upload(): void
    {
        $fileId = 'test-folder/test-file.txt';
        $expirationMinutes = 30;
        $expectedUrl = 'https://test-bucket.s3.amazonaws.com/test-folder/test-file.txt?X-Amz-Signature=upload-example';

        // Mock logging
        $this->mockLogService->shouldReceive('logOperationStart')
            ->once()
            ->andReturn('operation-id-123');

        $this->mockLogService->shouldReceive('logOperationSuccess')
            ->once();

        // Mock S3 client methods
        $mockCommand = Mockery::mock(Command::class);
        $this->mockS3Client->shouldReceive('getCommand')
            ->once()
            ->with('PutObject', [
                'Bucket' => 'test-bucket',
                'Key' => $fileId,
            ])
            ->andReturn($mockCommand);

        $mockRequest = Mockery::mock();
        $mockRequest->shouldReceive('getUri')
            ->once()
            ->andReturn($expectedUrl);

        $this->mockS3Client->shouldReceive('createPresignedRequest')
            ->once()
            ->with($mockCommand, "+{$expirationMinutes} minutes")
            ->andReturn($mockRequest);

        $result = $this->provider->generatePresignedUrl($this->user, $fileId, $expirationMinutes, 'upload');

        $this->assertEquals($expectedUrl, $result);
    }

    public function test_generate_presigned_url_unsupported_operation(): void
    {
        $fileId = 'test-folder/test-file.txt';

        // Mock logging
        $this->mockLogService->shouldReceive('logOperationStart')
            ->once()
            ->andReturn('operation-id-123');

        $this->mockLogService->shouldReceive('logOperationFailure')
            ->once();

        $this->mockErrorHandler->shouldReceive('classifyError')
            ->once()
            ->andReturn(\App\Enums\CloudStorageErrorType::FEATURE_NOT_SUPPORTED);

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('Unsupported presigned URL operation: invalid');

        $this->provider->generatePresignedUrl($this->user, $fileId, 60, 'invalid');
    }

    public function test_set_storage_class_success(): void
    {
        $fileId = 'test-folder/test-file.txt';
        $storageClass = 'STANDARD_IA';

        // Mock logging
        $this->mockLogService->shouldReceive('logOperationStart')
            ->once()
            ->andReturn('operation-id-123');

        $this->mockLogService->shouldReceive('logOperationSuccess')
            ->once();

        // Mock S3 client copyObject method
        $this->mockS3Client->shouldReceive('copyObject')
            ->once()
            ->with([
                'Bucket' => 'test-bucket',
                'Key' => $fileId,
                'CopySource' => 'test-bucket/' . $fileId,
                'StorageClass' => $storageClass,
                'MetadataDirective' => 'COPY',
            ]);

        $result = $this->provider->setStorageClass($this->user, $fileId, $storageClass);

        $this->assertTrue($result);
    }

    public function test_set_storage_class_invalid_class(): void
    {
        $fileId = 'test-folder/test-file.txt';
        $storageClass = 'INVALID_CLASS';

        // Mock logging
        $this->mockLogService->shouldReceive('logOperationStart')
            ->once()
            ->andReturn('operation-id-123');

        $this->mockLogService->shouldReceive('logOperationFailure')
            ->once();

        $this->mockErrorHandler->shouldReceive('classifyError')
            ->once()
            ->andReturn(\App\Enums\CloudStorageErrorType::INVALID_PARAMETER);

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('Invalid storage class: INVALID_CLASS');

        $this->provider->setStorageClass($this->user, $fileId, $storageClass);
    }

    public function test_get_available_storage_classes(): void
    {
        $storageClasses = $this->provider->getAvailableStorageClasses();

        $this->assertIsArray($storageClasses);
        $this->assertArrayHasKey('STANDARD', $storageClasses);
        $this->assertArrayHasKey('STANDARD_IA', $storageClasses);
        $this->assertArrayHasKey('GLACIER', $storageClasses);
        $this->assertArrayHasKey('DEEP_ARCHIVE', $storageClasses);

        // Check structure of storage class information
        $this->assertArrayHasKey('name', $storageClasses['STANDARD']);
        $this->assertArrayHasKey('description', $storageClasses['STANDARD']);
        $this->assertArrayHasKey('cost_tier', $storageClasses['STANDARD']);
        $this->assertArrayHasKey('retrieval_time', $storageClasses['STANDARD']);
    }

    public function test_optimize_upload_small_file(): void
    {
        $localPath = '/tmp/small-file.txt';
        $options = ['mime_type' => 'text/plain'];

        // Mock file_exists and filesize
        $this->mockFileSystem($localPath, 1048576); // 1MB

        $optimizations = $this->provider->optimizeUpload($this->user, $localPath, $options);

        $this->assertIsArray($optimizations);
        $this->assertFalse($optimizations['use_multipart']);
        $this->assertNull($optimizations['part_size']);
        $this->assertEquals('STANDARD', $optimizations['storage_class']);
        $this->assertArrayHasKey('metadata', $optimizations);
    }

    public function test_optimize_upload_large_file(): void
    {
        $localPath = '/tmp/large-file.zip';
        $options = ['mime_type' => 'application/zip', 'access_pattern' => 'infrequent'];

        // Mock file_exists and filesize for large file (200MB)
        $this->mockFileSystem($localPath, 209715200);

        $optimizations = $this->provider->optimizeUpload($this->user, $localPath, $options);

        $this->assertIsArray($optimizations);
        $this->assertTrue($optimizations['use_multipart']);
        $this->assertNotNull($optimizations['part_size']);
        $this->assertEquals('STANDARD_IA', $optimizations['storage_class']);
        $this->assertArrayHasKey('metadata', $optimizations);
    }

    public function test_set_file_metadata_success(): void
    {
        $fileId = 'test-folder/test-file.txt';
        $metadata = ['custom_key' => 'custom_value', 'category' => 'documents'];

        // Mock logging
        $this->mockLogService->shouldReceive('logOperationStart')
            ->once()
            ->andReturn('operation-id-123');

        $this->mockLogService->shouldReceive('logOperationSuccess')
            ->once();

        // Mock headObject to get current metadata
        $this->mockS3Client->shouldReceive('headObject')
            ->once()
            ->with([
                'Bucket' => 'test-bucket',
                'Key' => $fileId,
            ])
            ->andReturn(['Metadata' => ['existing_key' => 'existing_value']]);

        // Mock copyObject to update metadata
        $this->mockS3Client->shouldReceive('copyObject')
            ->once()
            ->with([
                'Bucket' => 'test-bucket',
                'Key' => $fileId,
                'CopySource' => 'test-bucket/' . $fileId,
                'Metadata' => [
                    'existing_key' => 'existing_value',
                    'custom_key' => 'custom_value',
                    'category' => 'documents',
                ],
                'MetadataDirective' => 'REPLACE',
            ]);

        $result = $this->provider->setFileMetadata($this->user, $fileId, $metadata);

        $this->assertTrue($result);
    }

    public function test_get_file_metadata_success(): void
    {
        $fileId = 'test-folder/test-file.txt';
        $expectedMetadata = ['custom_key' => 'custom_value', 'category' => 'documents'];

        // Mock logging
        $this->mockLogService->shouldReceive('logOperationStart')
            ->once()
            ->andReturn('operation-id-123');

        $this->mockLogService->shouldReceive('logOperationSuccess')
            ->once();

        // Mock headObject
        $this->mockS3Client->shouldReceive('headObject')
            ->once()
            ->with([
                'Bucket' => 'test-bucket',
                'Key' => $fileId,
            ])
            ->andReturn(['Metadata' => $expectedMetadata]);

        $result = $this->provider->getFileMetadata($this->user, $fileId);

        $this->assertEquals($expectedMetadata, $result);
    }

    public function test_add_file_tags_success(): void
    {
        $fileId = 'test-folder/test-file.txt';
        $tags = ['important', 'document'];

        // Mock logging
        $this->mockLogService->shouldReceive('logOperationStart')
            ->once()
            ->andReturn('operation-id-123');

        $this->mockLogService->shouldReceive('logOperationSuccess')
            ->once();

        // Mock getObjectTagging to get current tags
        $this->mockS3Client->shouldReceive('getObjectTagging')
            ->once()
            ->with([
                'Bucket' => 'test-bucket',
                'Key' => $fileId,
            ])
            ->andReturn(['TagSet' => [['Key' => 'existing', 'Value' => 'existing']]]);

        // Mock putObjectTagging to set updated tags
        $this->mockS3Client->shouldReceive('putObjectTagging')
            ->once()
            ->with([
                'Bucket' => 'test-bucket',
                'Key' => $fileId,
                'Tagging' => [
                    'TagSet' => [
                        ['Key' => 'existing', 'Value' => 'existing'],
                        ['Key' => 'important', 'Value' => 'important'],
                        ['Key' => 'document', 'Value' => 'document'],
                    ]
                ],
            ]);

        $result = $this->provider->addFileTags($this->user, $fileId, $tags);

        $this->assertTrue($result);
    }

    public function test_get_file_tags_success(): void
    {
        $fileId = 'test-folder/test-file.txt';
        $expectedTags = ['important', 'document'];

        // Mock logging
        $this->mockLogService->shouldReceive('logOperationStart')
            ->once()
            ->andReturn('operation-id-123');

        $this->mockLogService->shouldReceive('logOperationSuccess')
            ->once();

        // Mock getObjectTagging
        $this->mockS3Client->shouldReceive('getObjectTagging')
            ->once()
            ->with([
                'Bucket' => 'test-bucket',
                'Key' => $fileId,
            ])
            ->andReturn([
                'TagSet' => [
                    ['Key' => 'important', 'Value' => 'important'],
                    ['Key' => 'document', 'Value' => 'document'],
                ]
            ]);

        $result = $this->provider->getFileTags($this->user, $fileId);

        $this->assertEquals($expectedTags, $result);
    }

    public function test_get_optimization_recommendations(): void
    {
        $context = [
            'file_size' => 209715200, // 200MB
            'mime_type' => 'application/pdf',
            'access_pattern' => 'infrequent',
            'sensitive' => true,
        ];

        $recommendations = $this->provider->getOptimizationRecommendations($this->user, $context);

        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);

        // Check for expected recommendations
        $recommendationTypes = array_column($recommendations, 'type');
        $this->assertContains('upload_optimization', $recommendationTypes);
        $this->assertContains('cost_optimization', $recommendationTypes);
        $this->assertContains('security_optimization', $recommendationTypes);

        // Check recommendation structure
        foreach ($recommendations as $recommendation) {
            $this->assertArrayHasKey('type', $recommendation);
            $this->assertArrayHasKey('title', $recommendation);
            $this->assertArrayHasKey('description', $recommendation);
            $this->assertArrayHasKey('priority', $recommendation);
            $this->assertArrayHasKey('implementation', $recommendation);
        }
    }

    private function mockFileSystem(string $path, int $size): void
    {
        // Mock file_exists
        $this->app->bind('files', function () use ($path, $size) {
            $mock = Mockery::mock(\Illuminate\Filesystem\Filesystem::class);
            $mock->shouldReceive('exists')->with($path)->andReturn(true);
            $mock->shouldReceive('size')->with($path)->andReturn($size);
            return $mock;
        });

        // Create a global function mock for file_exists and filesize
        if (!function_exists('file_exists_mock')) {
            function file_exists_mock($filename) {
                return $filename === '/tmp/small-file.txt' || $filename === '/tmp/large-file.zip';
            }
        }

        if (!function_exists('filesize_mock')) {
            function filesize_mock($filename) {
                return match ($filename) {
                    '/tmp/small-file.txt' => 1048576,
                    '/tmp/large-file.zip' => 209715200,
                    default => 0,
                };
            }
        }
    }
}