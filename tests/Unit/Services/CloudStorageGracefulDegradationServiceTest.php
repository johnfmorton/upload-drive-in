<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CloudStorageGracefulDegradationService;
use App\Services\CloudStorageManager;
use App\Services\CloudStorageFeatureDetectionService;
use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use App\Models\FileUpload;
use App\Exceptions\CloudStorageException;
use App\Enums\CloudStorageErrorType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Mockery;

class CloudStorageGracefulDegradationServiceTest extends TestCase
{

    private CloudStorageGracefulDegradationService $service;
    private CloudStorageManager $mockStorageManager;
    private CloudStorageFeatureDetectionService $mockFeatureDetectionService;
    private CloudStorageProviderInterface $mockProvider;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockStorageManager = Mockery::mock(CloudStorageManager::class);
        $this->mockFeatureDetectionService = Mockery::mock(CloudStorageFeatureDetectionService::class);
        $this->mockProvider = Mockery::mock(CloudStorageProviderInterface::class);
        
        $this->service = new CloudStorageGracefulDegradationService(
            $this->mockStorageManager,
            $this->mockFeatureDetectionService
        );
        
        $this->user = new User(['id' => 1, 'email' => 'test@example.com']);

        // Mock the route helper function
        if (!function_exists('route')) {
            function route($name, $parameters = []) {
                return 'http://localhost/file/proxy/1/file_123/token';
            }
        }
    }

    public function test_create_folder_with_degradation_uses_native_when_supported(): void
    {
        $folderPath = 'test/folder';
        
        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->once()
            ->andReturn('google-drive');

        $this->mockFeatureDetectionService
            ->shouldReceive('isFeatureSupportedForUser')
            ->with($this->user, 'folder_creation')
            ->once()
            ->andReturn(true);

        // Mock successful native folder creation
        $this->mockProvider
            ->shouldReceive('uploadFile')
            ->once()
            ->andReturn('folder_id_123');

        // Create a temporary placeholder file for the test
        Storage::fake('local');
        Storage::put('.folder_placeholder', 'placeholder content');

        $result = $this->service->createFolderWithDegradation($this->user, $folderPath);

        $this->assertTrue($result['success']);
        $this->assertEquals('native', $result['method']);
        $this->assertEquals('folder_id_123', $result['folder_id']);
    }

    public function test_create_folder_with_degradation_falls_back_when_native_fails(): void
    {
        $folderPath = 'test/folder';
        
        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $this->mockFeatureDetectionService
            ->shouldReceive('isFeatureSupportedForUser')
            ->with($this->user, 'folder_creation')
            ->once()
            ->andReturn(true);

        // Mock native folder creation failure
        $this->mockProvider
            ->shouldReceive('uploadFile')
            ->once()
            ->andThrow(new \Exception('Native folder creation failed'));

        $result = $this->service->createFolderWithDegradation($this->user, $folderPath);

        $this->assertTrue($result['success']);
        $this->assertEquals('implicit', $result['method']);
        $this->assertNull($result['folder_id']);
    }

    public function test_create_folder_with_degradation_uses_placeholder_for_unsupported_providers(): void
    {
        $folderPath = 'test/folder';
        
        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->andReturn('generic-provider');

        $this->mockFeatureDetectionService
            ->shouldReceive('isFeatureSupportedForUser')
            ->with($this->user, 'folder_creation')
            ->once()
            ->andReturn(false);

        // Mock successful placeholder file upload
        $this->mockProvider
            ->shouldReceive('uploadFile')
            ->once()
            ->andReturn('placeholder_file_id');

        $result = $this->service->createFolderWithDegradation($this->user, $folderPath);

        $this->assertTrue($result['success']);
        $this->assertEquals('placeholder', $result['method']);
        $this->assertEquals('placeholder_file_id', $result['folder_id']);
    }

    public function test_upload_file_with_degradation_uses_standard_upload(): void
    {
        // Create a small test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_file');
        file_put_contents($tempFile, str_repeat('x', 1024)); // 1KB
        
        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $this->mockProvider
            ->shouldReceive('getStorageModel')
            ->andReturn('flat');

        // Mock successful upload
        $this->mockProvider
            ->shouldReceive('uploadFile')
            ->once()
            ->andReturn('file_id_123');

        $result = $this->service->uploadFileWithDegradation($this->user, $tempFile, 'uploads/test_file.txt');

        $this->assertTrue($result['success']);
        $this->assertEquals('standard_with_path_adaptation', $result['method']);
        $this->assertEquals('file_id_123', $result['file_id']);

        unlink($tempFile);
    }

    public function test_get_file_url_with_degradation_returns_result(): void
    {
        $this->markTestSkipped('Requires route configuration for proxy URL generation');
    }

    public function test_search_files_with_degradation_returns_result(): void
    {
        $this->markTestSkipped('Requires database for metadata search functionality');
    }

    public function test_handle_storage_model_degradation_adapts_for_flat_storage(): void
    {
        $path = 'folder/subfolder/file.txt';
        
        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getStorageModel')
            ->once()
            ->andReturn('flat');

        $result = $this->service->handleStorageModelDegradation($this->user, $path, 'create');

        $this->assertTrue($result['success']);
        $this->assertEquals('flat_adaptation', $result['method']);
        $this->assertEquals('folder_subfolder_file.txt', $result['adapted_path']);
    }

    public function test_handle_storage_model_degradation_adapts_for_hierarchical_storage(): void
    {
        $path = 'folder\\subfolder\\file.txt';
        
        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getStorageModel')
            ->once()
            ->andReturn('hierarchical');

        $result = $this->service->handleStorageModelDegradation($this->user, $path, 'create');

        $this->assertTrue($result['success']);
        $this->assertEquals('hierarchical_adaptation', $result['method']);
        $this->assertEquals('folder/subfolder/file.txt', $result['adapted_path']);
    }

    public function test_handle_storage_model_degradation_adapts_for_hybrid_storage(): void
    {
        $path = 'folder\\subfolder\\file.txt';
        
        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getStorageModel')
            ->once()
            ->andReturn('hybrid');

        $result = $this->service->handleStorageModelDegradation($this->user, $path, 'create');

        $this->assertTrue($result['success']);
        $this->assertEquals('hybrid_adaptation', $result['method']);
        $this->assertEquals('folder/subfolder/file.txt', $result['adapted_path']);
    }

    public function test_handle_storage_model_degradation_passes_through_for_unknown_model(): void
    {
        $path = 'folder/file.txt';
        
        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getStorageModel')
            ->once()
            ->andReturn('unknown');

        $result = $this->service->handleStorageModelDegradation($this->user, $path, 'create');

        $this->assertTrue($result['success']);
        $this->assertEquals('passthrough', $result['method']);
        $this->assertEquals($path, $result['adapted_path']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}