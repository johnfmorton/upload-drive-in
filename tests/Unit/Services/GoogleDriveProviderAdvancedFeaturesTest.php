<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\GoogleDriveProvider;
use App\Services\GoogleDriveService;
use App\Services\GoogleDriveErrorHandler;
use App\Services\CloudStorageLogService;
use App\Models\User;
use App\Exceptions\CloudStorageException;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Mockery;
class GoogleDriveProviderAdvancedFeaturesTest extends TestCase
{

    private GoogleDriveProvider $provider;
    private GoogleDriveService $mockDriveService;
    private GoogleDriveErrorHandler $mockErrorHandler;
    private CloudStorageLogService $mockLogService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDriveService = Mockery::mock(GoogleDriveService::class);
        $this->mockErrorHandler = Mockery::mock(GoogleDriveErrorHandler::class);
        $this->mockLogService = Mockery::mock(CloudStorageLogService::class);

        $this->provider = new GoogleDriveProvider(
            $this->mockDriveService,
            $this->mockErrorHandler,
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

    public function test_generate_presigned_url_returns_null(): void
    {
        $fileId = 'google-drive-file-id';
        $expirationMinutes = 60;

        $result = $this->provider->generatePresignedUrl($this->user, $fileId, $expirationMinutes, 'download');

        $this->assertNull($result);
    }

    public function test_generate_presigned_url_non_download_returns_null(): void
    {
        $fileId = 'google-drive-file-id';
        $expirationMinutes = 60;

        $result = $this->provider->generatePresignedUrl($this->user, $fileId, $expirationMinutes, 'upload');

        $this->assertNull($result);
    }

    public function test_set_storage_class_throws_exception(): void
    {
        $fileId = 'google-drive-file-id';
        $storageClass = 'STANDARD_IA';

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('Google Drive does not support storage classes');

        $this->provider->setStorageClass($this->user, $fileId, $storageClass);
    }

    public function test_get_available_storage_classes_returns_empty(): void
    {
        $storageClasses = $this->provider->getAvailableStorageClasses();

        $this->assertIsArray($storageClasses);
        $this->assertEmpty($storageClasses);
    }

    public function test_optimize_upload_small_file(): void
    {
        $localPath = '/tmp/small-file.txt';
        $options = ['mime_type' => 'text/plain'];

        // Mock file_exists and filesize
        $this->mockFileSystem($localPath, 1048576); // 1MB

        $optimizations = $this->provider->optimizeUpload($this->user, $localPath, $options);

        $this->assertIsArray($optimizations);
        $this->assertFalse($optimizations['use_resumable_upload']);
        $this->assertNull($optimizations['chunk_size']);
        $this->assertFalse($optimizations['convert_to_google_format']);
        $this->assertArrayHasKey('metadata', $optimizations);
    }

    public function test_optimize_upload_large_file(): void
    {
        $localPath = '/tmp/large-file.zip';
        $options = ['mime_type' => 'application/zip'];

        // Mock file_exists and filesize for large file (10MB)
        $this->mockFileSystem($localPath, 10485760);

        $optimizations = $this->provider->optimizeUpload($this->user, $localPath, $options);

        $this->assertIsArray($optimizations);
        $this->assertTrue($optimizations['use_resumable_upload']);
        $this->assertNotNull($optimizations['chunk_size']);
        $this->assertFalse($optimizations['convert_to_google_format']);
        $this->assertArrayHasKey('metadata', $optimizations);
    }

    public function test_optimize_upload_office_document_with_conversion(): void
    {
        $localPath = '/tmp/document.docx';
        $options = [
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'convert_to_google_format' => true,
        ];

        // Mock file_exists and filesize
        $this->mockFileSystem($localPath, 2097152); // 2MB

        $optimizations = $this->provider->optimizeUpload($this->user, $localPath, $options);

        $this->assertIsArray($optimizations);
        $this->assertFalse($optimizations['use_resumable_upload']); // Under 5MB threshold
        $this->assertTrue($optimizations['convert_to_google_format']);
        $this->assertArrayHasKey('metadata', $optimizations);
    }

    public function test_set_file_metadata_success(): void
    {
        $fileId = 'google-drive-file-id';
        $metadata = ['custom_key' => 'custom_value', 'category' => 'documents'];

        // Mock logging
        $this->mockLogService->shouldReceive('logOperationStart')
            ->once()
            ->andReturn('operation-id-123');

        $this->mockLogService->shouldReceive('logOperationSuccess')
            ->once();

        // Mock Drive service
        $mockDriveService = Mockery::mock(Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockDriveService->files = $mockFilesResource;

        $this->mockDriveService->shouldReceive('getDriveService')
            ->once()
            ->with($this->user)
            ->andReturn($mockDriveService);

        // Mock the update call
        $mockFilesResource->shouldReceive('update')
            ->once()
            ->with($fileId, Mockery::type(DriveFile::class));

        $result = $this->provider->setFileMetadata($this->user, $fileId, $metadata);

        $this->assertTrue($result);
    }

    public function test_get_file_metadata_success(): void
    {
        $fileId = 'google-drive-file-id';
        $expectedMetadata = [
            'id' => $fileId,
            'name' => 'test-file.txt',
            'size' => '1024',
            'mime_type' => 'text/plain',
            'created_time' => '2023-01-01T00:00:00Z',
            'modified_time' => '2023-01-02T00:00:00Z',
            'parents' => ['parent-folder-id'],
            'web_view_link' => 'https://drive.google.com/file/d/' . $fileId . '/view',
            'web_content_link' => 'https://drive.google.com/uc?id=' . $fileId,
            'properties' => ['custom_key' => 'custom_value'],
        ];

        // Mock logging
        $this->mockLogService->shouldReceive('logOperationStart')
            ->once()
            ->andReturn('operation-id-123');

        $this->mockLogService->shouldReceive('logOperationSuccess')
            ->once();

        // Mock Drive service
        $mockDriveService = Mockery::mock(Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockDriveService->files = $mockFilesResource;

        $this->mockDriveService->shouldReceive('getDriveService')
            ->once()
            ->with($this->user)
            ->andReturn($mockDriveService);

        // Mock DriveFile object
        $mockFile = Mockery::mock(DriveFile::class);
        $mockFile->shouldReceive('getId')->andReturn($fileId);
        $mockFile->shouldReceive('getName')->andReturn('test-file.txt');
        $mockFile->shouldReceive('getSize')->andReturn('1024');
        $mockFile->shouldReceive('getMimeType')->andReturn('text/plain');
        $mockFile->shouldReceive('getCreatedTime')->andReturn('2023-01-01T00:00:00Z');
        $mockFile->shouldReceive('getModifiedTime')->andReturn('2023-01-02T00:00:00Z');
        $mockFile->shouldReceive('getParents')->andReturn(['parent-folder-id']);
        $mockFile->shouldReceive('getWebViewLink')->andReturn('https://drive.google.com/file/d/' . $fileId . '/view');
        $mockFile->shouldReceive('getWebContentLink')->andReturn('https://drive.google.com/uc?id=' . $fileId);
        $mockFile->shouldReceive('getProperties')->andReturn(['custom_key' => 'custom_value']);

        // Mock the get call
        $mockFilesResource->shouldReceive('get')
            ->once()
            ->with($fileId, [
                'fields' => 'id,name,size,mimeType,createdTime,modifiedTime,properties,parents,webViewLink,webContentLink'
            ])
            ->andReturn($mockFile);

        $result = $this->provider->getFileMetadata($this->user, $fileId);

        $this->assertEquals($expectedMetadata, $result);
    }

    public function test_add_file_tags_success(): void
    {
        $fileId = 'google-drive-file-id';
        $tags = ['important', 'document'];

        // Mock logging
        $this->mockLogService->shouldReceive('logOperationStart')
            ->once()
            ->andReturn('operation-id-123');

        $this->mockLogService->shouldReceive('logOperationSuccess')
            ->once();

        // Mock Drive service
        $mockDriveService = Mockery::mock(Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockDriveService->files = $mockFilesResource;

        $this->mockDriveService->shouldReceive('getDriveService')
            ->once()
            ->with($this->user)
            ->andReturn($mockDriveService);

        // Mock getting current properties
        $mockFile = Mockery::mock(DriveFile::class);
        $mockFile->shouldReceive('getProperties')
            ->andReturn(['existing_key' => 'existing_value']);

        $mockFilesResource->shouldReceive('get')
            ->once()
            ->with($fileId, ['fields' => 'properties'])
            ->andReturn($mockFile);

        // Mock the update call
        $mockFilesResource->shouldReceive('update')
            ->once()
            ->with($fileId, Mockery::type(DriveFile::class));

        $result = $this->provider->addFileTags($this->user, $fileId, $tags);

        $this->assertTrue($result);
    }

    public function test_get_file_tags_success(): void
    {
        $fileId = 'google-drive-file-id';
        $expectedTags = ['important', 'document'];

        // Mock logging
        $this->mockLogService->shouldReceive('logOperationStart')
            ->once()
            ->andReturn('operation-id-123');

        $this->mockLogService->shouldReceive('logOperationSuccess')
            ->once();

        // Mock Drive service
        $mockDriveService = Mockery::mock(Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockDriveService->files = $mockFilesResource;

        $this->mockDriveService->shouldReceive('getDriveService')
            ->once()
            ->with($this->user)
            ->andReturn($mockDriveService);

        // Mock DriveFile object with tags in properties
        $mockFile = Mockery::mock(DriveFile::class);
        $mockFile->shouldReceive('getProperties')
            ->andReturn(['tags' => 'important,document']);

        // Mock the get call
        $mockFilesResource->shouldReceive('get')
            ->once()
            ->with($fileId, ['fields' => 'properties'])
            ->andReturn($mockFile);

        $result = $this->provider->getFileTags($this->user, $fileId);

        $this->assertEquals($expectedTags, $result);
    }

    public function test_get_file_tags_no_tags(): void
    {
        $fileId = 'google-drive-file-id';

        // Mock logging
        $this->mockLogService->shouldReceive('logOperationStart')
            ->once()
            ->andReturn('operation-id-123');

        $this->mockLogService->shouldReceive('logOperationSuccess')
            ->once();

        // Mock Drive service
        $mockDriveService = Mockery::mock(Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockDriveService->files = $mockFilesResource;

        $this->mockDriveService->shouldReceive('getDriveService')
            ->once()
            ->with($this->user)
            ->andReturn($mockDriveService);

        // Mock DriveFile object with no tags
        $mockFile = Mockery::mock(DriveFile::class);
        $mockFile->shouldReceive('getProperties')
            ->andReturn(['other_key' => 'other_value']);

        // Mock the get call
        $mockFilesResource->shouldReceive('get')
            ->once()
            ->with($fileId, ['fields' => 'properties'])
            ->andReturn($mockFile);

        $result = $this->provider->getFileTags($this->user, $fileId);

        $this->assertEquals([], $result);
    }

    public function test_get_optimization_recommendations(): void
    {
        $context = [
            'file_size' => 10485760, // 10MB
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'access_pattern' => 'collaborative',
            'important' => true,
        ];

        $recommendations = $this->provider->getOptimizationRecommendations($this->user, $context);

        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);

        // Check for expected recommendations
        $recommendationTypes = array_column($recommendations, 'type');
        $this->assertContains('upload_optimization', $recommendationTypes);
        $this->assertContains('collaboration_optimization', $recommendationTypes);
        $this->assertContains('backup_optimization', $recommendationTypes);

        // Check recommendation structure
        foreach ($recommendations as $recommendation) {
            $this->assertArrayHasKey('type', $recommendation);
            $this->assertArrayHasKey('title', $recommendation);
            $this->assertArrayHasKey('description', $recommendation);
            $this->assertArrayHasKey('priority', $recommendation);
            $this->assertArrayHasKey('implementation', $recommendation);
        }
    }

    public function test_get_optimization_recommendations_with_quota_warning(): void
    {
        $context = [
            'file_size' => 1048576, // 1MB
            'mime_type' => 'text/plain',
        ];

        // Mock getConnectionHealth to return high quota usage
        $mockHealthStatus = Mockery::mock(\App\Services\CloudStorageHealthStatus::class);
        $mockHealthStatus->providerSpecificData = ['quota_usage_percent' => 85];

        $this->provider = Mockery::mock(GoogleDriveProvider::class)->makePartial();
        $this->provider->shouldReceive('getConnectionHealth')
            ->with($this->user)
            ->andReturn($mockHealthStatus);

        $recommendations = $this->provider->getOptimizationRecommendations($this->user, $context);

        $this->assertIsArray($recommendations);
        
        // Check for quota management recommendation
        $recommendationTypes = array_column($recommendations, 'type');
        $this->assertContains('quota_optimization', $recommendationTypes);
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
        if (!function_exists('file_exists_mock_gd')) {
            function file_exists_mock_gd($filename) {
                return in_array($filename, [
                    '/tmp/small-file.txt',
                    '/tmp/large-file.zip',
                    '/tmp/document.docx'
                ]);
            }
        }

        if (!function_exists('filesize_mock_gd')) {
            function filesize_mock_gd($filename) {
                return match ($filename) {
                    '/tmp/small-file.txt' => 1048576,
                    '/tmp/large-file.zip' => 10485760,
                    '/tmp/document.docx' => 2097152,
                    default => 0,
                };
            }
        }
    }
}