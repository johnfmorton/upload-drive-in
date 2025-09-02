<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CloudStorageAdvancedFeaturesService;
use App\Services\CloudStorageManager;
use App\Services\CloudStorageLogService;
use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use App\Exceptions\CloudStorageException;
use Mockery;
class CloudStorageAdvancedFeaturesServiceTest extends TestCase
{

    private CloudStorageAdvancedFeaturesService $service;
    private CloudStorageManager $mockStorageManager;
    private CloudStorageLogService $mockLogService;
    private CloudStorageProviderInterface $mockProvider;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockStorageManager = Mockery::mock(CloudStorageManager::class);
        $this->mockLogService = Mockery::mock(CloudStorageLogService::class);
        $this->mockProvider = Mockery::mock(CloudStorageProviderInterface::class);

        $this->service = new CloudStorageAdvancedFeaturesService(
            $this->mockStorageManager,
            $this->mockLogService
        );

        // Create test user (mock)
        $this->user = Mockery::mock(User::class);
        $this->user->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->user->shouldReceive('setAttribute')->andReturnSelf();
        $this->user->id = 1;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_presigned_url_success(): void
    {
        $fileId = 'test-file-id';
        $expirationMinutes = 60;
        $operation = 'download';
        $expectedUrl = 'https://example.com/presigned-url';

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $this->mockProvider->shouldReceive('generatePresignedUrl')
            ->once()
            ->with($this->user, $fileId, $expirationMinutes, $operation)
            ->andReturn($expectedUrl);

        $this->mockStorageManager->shouldReceive('getProvider')
            ->once()
            ->with(null, $this->user)
            ->andReturn($this->mockProvider);

        $result = $this->service->generatePresignedUrl($this->user, $fileId, $expirationMinutes, $operation);

        $this->assertIsArray($result);
        $this->assertEquals($expectedUrl, $result['url']);
        $this->assertEquals('amazon-s3', $result['provider']);
        $this->assertTrue($result['supported']);
        $this->assertEquals($operation, $result['operation']);
        $this->assertNotNull($result['expires_at']);
    }

    public function test_generate_presigned_url_not_supported(): void
    {
        $fileId = 'test-file-id';
        $expirationMinutes = 60;
        $operation = 'download';

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('google-drive');

        $this->mockProvider->shouldReceive('generatePresignedUrl')
            ->once()
            ->with($this->user, $fileId, $expirationMinutes, $operation)
            ->andReturn(null);

        $this->mockStorageManager->shouldReceive('getProvider')
            ->once()
            ->with(null, $this->user)
            ->andReturn($this->mockProvider);

        $result = $this->service->generatePresignedUrl($this->user, $fileId, $expirationMinutes, $operation);

        $this->assertIsArray($result);
        $this->assertNull($result['url']);
        $this->assertEquals('google-drive', $result['provider']);
        $this->assertFalse($result['supported']);
        $this->assertEquals($operation, $result['operation']);
        $this->assertNull($result['expires_at']);
    }

    public function test_set_storage_class_success(): void
    {
        $fileId = 'test-file-id';
        $storageClass = 'STANDARD_IA';

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $this->mockProvider->shouldReceive('setStorageClass')
            ->once()
            ->with($this->user, $fileId, $storageClass)
            ->andReturn(true);

        $this->mockStorageManager->shouldReceive('getProvider')
            ->once()
            ->with(null, $this->user)
            ->andReturn($this->mockProvider);

        $result = $this->service->setStorageClass($this->user, $fileId, $storageClass);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('amazon-s3', $result['provider']);
        $this->assertEquals($storageClass, $result['storage_class']);
        $this->assertTrue($result['supported']);
    }

    public function test_set_storage_class_not_supported(): void
    {
        $fileId = 'test-file-id';
        $storageClass = 'STANDARD_IA';

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('google-drive');

        $this->mockProvider->shouldReceive('setStorageClass')
            ->once()
            ->with($this->user, $fileId, $storageClass)
            ->andThrow(new CloudStorageException(
                'Feature not supported',
                \App\Enums\CloudStorageErrorType::FEATURE_NOT_SUPPORTED,
                [],
                'google-drive'
            ));

        $this->mockStorageManager->shouldReceive('getProvider')
            ->once()
            ->with(null, $this->user)
            ->andReturn($this->mockProvider);

        $result = $this->service->setStorageClass($this->user, $fileId, $storageClass);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('google-drive', $result['provider']);
        $this->assertEquals($storageClass, $result['storage_class']);
        $this->assertFalse($result['supported']);
        $this->assertEquals('Storage classes not supported by this provider', $result['message']);
    }

    public function test_get_available_storage_classes(): void
    {
        $expectedStorageClasses = [
            'STANDARD' => ['name' => 'Standard', 'description' => 'General purpose storage'],
            'STANDARD_IA' => ['name' => 'Standard-IA', 'description' => 'Infrequent access storage'],
        ];

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $this->mockProvider->shouldReceive('getAvailableStorageClasses')
            ->once()
            ->andReturn($expectedStorageClasses);

        $this->mockStorageManager->shouldReceive('getProvider')
            ->once()
            ->with(null, $this->user)
            ->andReturn($this->mockProvider);

        $result = $this->service->getAvailableStorageClasses($this->user);

        $this->assertIsArray($result);
        $this->assertEquals('amazon-s3', $result['provider']);
        $this->assertEquals($expectedStorageClasses, $result['storage_classes']);
        $this->assertTrue($result['supported']);
    }

    public function test_optimize_upload(): void
    {
        $localPath = '/tmp/test-file.txt';
        $options = ['mime_type' => 'text/plain'];
        $expectedOptimizations = [
            'use_multipart' => false,
            'storage_class' => 'STANDARD',
            'metadata' => ['optimization_applied' => 'true'],
        ];

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $this->mockProvider->shouldReceive('optimizeUpload')
            ->once()
            ->with($this->user, $localPath, $options)
            ->andReturn($expectedOptimizations);

        $this->mockStorageManager->shouldReceive('getProvider')
            ->once()
            ->with(null, $this->user)
            ->andReturn($this->mockProvider);

        // Mock file_exists and filesize
        $this->mockFileSystem($localPath, 1024);

        $result = $this->service->optimizeUpload($this->user, $localPath, $options);

        $this->assertIsArray($result);
        $this->assertEquals('amazon-s3', $result['provider']);
        $this->assertEquals($expectedOptimizations, $result['optimizations']);
        $this->assertEquals(1024, $result['file_size']);
    }

    public function test_set_file_metadata_success(): void
    {
        $fileId = 'test-file-id';
        $metadata = ['custom_key' => 'custom_value'];

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $this->mockProvider->shouldReceive('setFileMetadata')
            ->once()
            ->with($this->user, $fileId, $metadata)
            ->andReturn(true);

        $this->mockStorageManager->shouldReceive('getProvider')
            ->once()
            ->with(null, $this->user)
            ->andReturn($this->mockProvider);

        $result = $this->service->setFileMetadata($this->user, $fileId, $metadata);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('amazon-s3', $result['provider']);
        $this->assertEquals(['custom_key'], $result['metadata_keys']);
    }

    public function test_get_file_metadata_success(): void
    {
        $fileId = 'test-file-id';
        $expectedMetadata = [
            'id' => $fileId,
            'name' => 'test-file.txt',
            'custom_key' => 'custom_value',
        ];

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $this->mockProvider->shouldReceive('getFileMetadata')
            ->once()
            ->with($this->user, $fileId)
            ->andReturn($expectedMetadata);

        $this->mockStorageManager->shouldReceive('getProvider')
            ->once()
            ->with(null, $this->user)
            ->andReturn($this->mockProvider);

        $result = $this->service->getFileMetadata($this->user, $fileId);

        $this->assertIsArray($result);
        $this->assertEquals('amazon-s3', $result['provider']);
        $this->assertEquals($expectedMetadata, $result['metadata']);
    }

    public function test_add_file_tags_success(): void
    {
        $fileId = 'test-file-id';
        $tags = ['important', 'document'];

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $this->mockProvider->shouldReceive('addFileTags')
            ->once()
            ->with($this->user, $fileId, $tags)
            ->andReturn(true);

        $this->mockStorageManager->shouldReceive('getProvider')
            ->once()
            ->with(null, $this->user)
            ->andReturn($this->mockProvider);

        $result = $this->service->addFileTags($this->user, $fileId, $tags);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('amazon-s3', $result['provider']);
        $this->assertEquals($tags, $result['tags_added']);
    }

    public function test_get_file_tags_success(): void
    {
        $fileId = 'test-file-id';
        $expectedTags = ['important', 'document'];

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $this->mockProvider->shouldReceive('getFileTags')
            ->once()
            ->with($this->user, $fileId)
            ->andReturn($expectedTags);

        $this->mockStorageManager->shouldReceive('getProvider')
            ->once()
            ->with(null, $this->user)
            ->andReturn($this->mockProvider);

        $result = $this->service->getFileTags($this->user, $fileId);

        $this->assertIsArray($result);
        $this->assertEquals('amazon-s3', $result['provider']);
        $this->assertEquals($expectedTags, $result['tags']);
    }

    public function test_get_optimization_recommendations(): void
    {
        $context = ['file_size' => 1048576, 'mime_type' => 'text/plain'];
        $expectedRecommendations = [
            [
                'type' => 'upload_optimization',
                'title' => 'Use Multipart Upload',
                'description' => 'For better performance',
                'priority' => 'high',
            ],
        ];

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $this->mockProvider->shouldReceive('getOptimizationRecommendations')
            ->once()
            ->with($this->user, $context)
            ->andReturn($expectedRecommendations);

        $this->mockStorageManager->shouldReceive('getProvider')
            ->once()
            ->with(null, $this->user)
            ->andReturn($this->mockProvider);

        $result = $this->service->getOptimizationRecommendations($this->user, $context);

        $this->assertIsArray($result);
        $this->assertEquals('amazon-s3', $result['provider']);
        $this->assertEquals($expectedRecommendations, $result['recommendations']);
        $this->assertEquals($context, $result['context']);
    }

    public function test_get_advanced_feature_capabilities(): void
    {
        $providers = [
            'amazon-s3' => $this->mockProvider,
            'google-drive' => Mockery::mock(CloudStorageProviderInterface::class),
        ];

        $this->mockStorageManager->shouldReceive('getAllProviders')
            ->once()
            ->andReturn($providers);

        // Mock S3 provider capabilities
        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');
        $this->mockProvider->shouldReceive('getCapabilities')
            ->andReturn([
                'presigned_urls' => true,
                'metadata_support' => true,
            ]);
        $this->mockProvider->shouldReceive('getAvailableStorageClasses')
            ->andReturn(['STANDARD' => [], 'STANDARD_IA' => []]);

        // Mock Google Drive provider capabilities
        $providers['google-drive']->shouldReceive('getProviderName')
            ->andReturn('google-drive');
        $providers['google-drive']->shouldReceive('getCapabilities')
            ->andReturn([
                'presigned_urls' => false,
                'metadata_support' => true,
            ]);
        $providers['google-drive']->shouldReceive('getAvailableStorageClasses')
            ->andReturn([]);

        $result = $this->service->getAdvancedFeatureCapabilities($this->user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('amazon-s3', $result);
        $this->assertArrayHasKey('google-drive', $result);

        // Check S3 capabilities
        $s3Capabilities = $result['amazon-s3'];
        $this->assertEquals('amazon-s3', $s3Capabilities['provider_name']);
        $this->assertTrue($s3Capabilities['presigned_urls']);
        $this->assertTrue($s3Capabilities['storage_classes']);
        $this->assertTrue($s3Capabilities['metadata_support']);
        $this->assertTrue($s3Capabilities['tagging_support']);

        // Check Google Drive capabilities
        $gdCapabilities = $result['google-drive'];
        $this->assertEquals('google-drive', $gdCapabilities['provider_name']);
        $this->assertFalse($gdCapabilities['presigned_urls']);
        $this->assertFalse($gdCapabilities['storage_classes']);
        $this->assertTrue($gdCapabilities['metadata_support']);
        $this->assertTrue($gdCapabilities['tagging_support']);
    }

    public function test_bulk_optimize_files_success(): void
    {
        $fileIds = ['file1', 'file2'];
        $optimizations = [
            'storage_class' => 'STANDARD_IA',
            'metadata' => ['category' => 'documents'],
            'tags' => ['bulk-optimized'],
        ];

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $this->mockStorageManager->shouldReceive('getProvider')
            ->once()
            ->with(null, $this->user)
            ->andReturn($this->mockProvider);

        // Mock successful operations for both files
        $this->mockProvider->shouldReceive('setStorageClass')
            ->twice()
            ->andReturn(true);

        $this->mockProvider->shouldReceive('setFileMetadata')
            ->twice()
            ->andReturn(true);

        $this->mockProvider->shouldReceive('addFileTags')
            ->twice()
            ->andReturn(true);

        $result = $this->service->bulkOptimizeFiles($this->user, $fileIds, $optimizations);

        $this->assertIsArray($result);
        $this->assertEquals('amazon-s3', $result['provider']);
        $this->assertEquals(2, $result['total_files']);
        $this->assertEquals(2, $result['successful']);
        $this->assertEquals(0, $result['failed']);
        $this->assertCount(2, $result['results']);

        // Check individual file results
        foreach ($result['results'] as $fileResult) {
            $this->assertTrue($fileResult['success']);
            $this->assertContains('storage_class', $fileResult['applied_optimizations']);
            $this->assertContains('metadata', $fileResult['applied_optimizations']);
            $this->assertContains('tags', $fileResult['applied_optimizations']);
            $this->assertEmpty($fileResult['errors']);
        }
    }

    public function test_bulk_optimize_files_partial_failure(): void
    {
        $fileIds = ['file1', 'file2'];
        $optimizations = ['storage_class' => 'STANDARD_IA'];

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $this->mockStorageManager->shouldReceive('getProvider')
            ->once()
            ->with(null, $this->user)
            ->andReturn($this->mockProvider);

        // Mock success for first file, failure for second
        $this->mockProvider->shouldReceive('setStorageClass')
            ->with($this->user, 'file1', 'STANDARD_IA')
            ->once()
            ->andReturn(true);

        $this->mockProvider->shouldReceive('setStorageClass')
            ->with($this->user, 'file2', 'STANDARD_IA')
            ->once()
            ->andThrow(new \Exception('Storage class operation failed'));

        $result = $this->service->bulkOptimizeFiles($this->user, $fileIds, $optimizations);

        $this->assertIsArray($result);
        $this->assertEquals('amazon-s3', $result['provider']);
        $this->assertEquals(2, $result['total_files']);
        $this->assertEquals(1, $result['successful']);
        $this->assertEquals(1, $result['failed']);

        // Check first file (success)
        $this->assertTrue($result['results'][0]['success']);
        $this->assertContains('storage_class', $result['results'][0]['applied_optimizations']);

        // Check second file (failure)
        $this->assertFalse($result['results'][1]['success']);
        $this->assertNotEmpty($result['results'][1]['errors']);
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
    }
}