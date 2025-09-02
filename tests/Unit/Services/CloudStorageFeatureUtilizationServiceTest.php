<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CloudStorageFeatureUtilizationService;
use App\Services\CloudStorageManager;
use App\Services\CloudStorageFeatureDetectionService;
use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use Mockery;

class CloudStorageFeatureUtilizationServiceTest extends TestCase
{

    private CloudStorageFeatureUtilizationService $service;
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
        
        $this->service = new CloudStorageFeatureUtilizationService(
            $this->mockStorageManager,
            $this->mockFeatureDetectionService
        );
        
        $this->user = new User(['id' => 1, 'email' => 'test@example.com']);
    }

    public function test_optimize_file_upload_for_google_drive_small_file(): void
    {
        $localPath = '/tmp/small_file.txt';
        $targetPath = 'uploads/small_file.txt';
        
        // Create a small test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_small_file');
        file_put_contents($tempFile, str_repeat('x', 1024)); // 1KB
        
        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->once()
            ->andReturn('google-drive');

        $this->mockProvider
            ->shouldReceive('supportsFeature')
            ->with('folder_creation')
            ->once()
            ->andReturn(true);

        $result = $this->service->optimizeFileUpload($this->user, $tempFile, $targetPath);

        $this->assertEquals('simple', $result['upload_type']);
        $this->assertTrue($result['create_folders']);
        $this->assertEquals('hierarchical', $result['folder_strategy']);

        unlink($tempFile);
    }

    public function test_optimize_file_upload_for_google_drive_large_file(): void
    {
        $localPath = '/tmp/large_file.txt';
        $targetPath = 'uploads/large_file.txt';
        
        // Create a large test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_large_file');
        file_put_contents($tempFile, str_repeat('x', 10 * 1024 * 1024)); // 10MB
        
        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->once()
            ->andReturn('google-drive');

        $this->mockProvider
            ->shouldReceive('supportsFeature')
            ->with('folder_creation')
            ->once()
            ->andReturn(true);

        $result = $this->service->optimizeFileUpload($this->user, $tempFile, $targetPath);

        $this->assertEquals('resumable', $result['upload_type']);
        $this->assertArrayHasKey('chunk_size', $result);
        $this->assertTrue($result['create_folders']);

        unlink($tempFile);
    }

    public function test_optimize_file_upload_for_s3_small_file(): void
    {
        $localPath = '/tmp/small_file.txt';
        $targetPath = 'uploads/small_file.txt';
        
        // Create a small test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_small_file');
        file_put_contents($tempFile, str_repeat('x', 1024)); // 1KB
        
        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->once()
            ->andReturn('amazon-s3');

        $this->mockProvider
            ->shouldReceive('supportsFeature')
            ->with('storage_classes')
            ->once()
            ->andReturn(true);

        $this->mockProvider
            ->shouldReceive('supportsFeature')
            ->with('tagging')
            ->once()
            ->andReturn(true);

        $this->mockProvider
            ->shouldReceive('supportsFeature')
            ->with('encryption_at_rest')
            ->once()
            ->andReturn(true);

        $result = $this->service->optimizeFileUpload($this->user, $tempFile, $targetPath);

        $this->assertEquals('simple', $result['upload_type']);
        $this->assertArrayHasKey('storage_class', $result);
        $this->assertArrayHasKey('tags', $result);
        $this->assertEquals('AES256', $result['server_side_encryption']);

        unlink($tempFile);
    }

    public function test_optimize_file_upload_for_s3_large_file(): void
    {
        $localPath = '/tmp/large_file.txt';
        $targetPath = 'uploads/large_file.txt';
        
        // Create a large test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_large_file');
        file_put_contents($tempFile, str_repeat('x', 150 * 1024 * 1024)); // 150MB
        
        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->once()
            ->andReturn('amazon-s3');

        $this->mockProvider
            ->shouldReceive('supportsFeature')
            ->with('multipart_upload')
            ->once()
            ->andReturn(true);

        $this->mockProvider
            ->shouldReceive('supportsFeature')
            ->with('storage_classes')
            ->once()
            ->andReturn(true);

        $this->mockProvider
            ->shouldReceive('supportsFeature')
            ->with('tagging')
            ->once()
            ->andReturn(true);

        $this->mockProvider
            ->shouldReceive('supportsFeature')
            ->with('encryption_at_rest')
            ->once()
            ->andReturn(true);

        $result = $this->service->optimizeFileUpload($this->user, $tempFile, $targetPath);

        $this->assertEquals('multipart', $result['upload_type']);
        $this->assertArrayHasKey('part_size', $result);
        $this->assertGreaterThanOrEqual(5 * 1024 * 1024, $result['part_size']); // Min 5MB

        unlink($tempFile);
    }

    public function test_get_optimal_storage_class_for_s3_frequent_access(): void
    {
        $fileInfo = [
            'size' => 1024 * 1024, // 1MB
            'access_pattern' => 'frequent',
            'retention_days' => 30
        ];

        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->once()
            ->andReturn('amazon-s3');

        $this->mockFeatureDetectionService
            ->shouldReceive('isFeatureSupportedForUser')
            ->with($this->user, 'storage_classes')
            ->once()
            ->andReturn(true);

        $result = $this->service->getOptimalStorageClass($this->user, $fileInfo);

        $this->assertEquals('STANDARD', $result['storage_class']);
        $this->assertStringContainsString('Frequent access', $result['reason']);
    }

    public function test_get_optimal_storage_class_for_s3_infrequent_access(): void
    {
        $fileInfo = [
            'size' => 1024 * 1024, // 1MB
            'access_pattern' => 'infrequent',
            'retention_days' => 180
        ];

        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->once()
            ->andReturn('amazon-s3');

        $this->mockFeatureDetectionService
            ->shouldReceive('isFeatureSupportedForUser')
            ->with($this->user, 'storage_classes')
            ->once()
            ->andReturn(true);

        $result = $this->service->getOptimalStorageClass($this->user, $fileInfo);

        $this->assertEquals('STANDARD_IA', $result['storage_class']);
        $this->assertStringContainsString('Infrequent access', $result['reason']);
    }

    public function test_get_optimal_storage_class_for_s3_archive(): void
    {
        $fileInfo = [
            'size' => 1024 * 1024, // 1MB
            'access_pattern' => 'archive',
            'retention_days' => 1000
        ];

        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->once()
            ->andReturn('amazon-s3');

        $this->mockFeatureDetectionService
            ->shouldReceive('isFeatureSupportedForUser')
            ->with($this->user, 'storage_classes')
            ->once()
            ->andReturn(true);

        $result = $this->service->getOptimalStorageClass($this->user, $fileInfo);

        $this->assertEquals('GLACIER', $result['storage_class']);
        $this->assertStringContainsString('Long-term archival', $result['reason']);
    }

    public function test_get_optimal_storage_class_for_azure_blob(): void
    {
        $fileInfo = [
            'size' => 1024 * 1024, // 1MB
            'access_pattern' => 'infrequent',
            'retention_days' => 180
        ];

        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->once()
            ->andReturn('azure-blob');

        $this->mockFeatureDetectionService
            ->shouldReceive('isFeatureSupportedForUser')
            ->with($this->user, 'storage_classes')
            ->once()
            ->andReturn(true);

        $result = $this->service->getOptimalStorageClass($this->user, $fileInfo);

        $this->assertEquals('Cool', $result['storage_class']);
        $this->assertStringContainsString('Infrequent access', $result['reason']);
    }

    public function test_get_optimal_storage_class_when_not_supported(): void
    {
        $fileInfo = [
            'size' => 1024 * 1024, // 1MB
            'access_pattern' => 'frequent',
            'retention_days' => 30
        ];

        $this->mockFeatureDetectionService
            ->shouldReceive('isFeatureSupportedForUser')
            ->with($this->user, 'storage_classes')
            ->once()
            ->andReturn(false);

        $result = $this->service->getOptimalStorageClass($this->user, $fileInfo);

        $this->assertEquals('standard', $result['storage_class']);
        $this->assertStringContainsString('does not support multiple storage classes', $result['reason']);
        $this->assertEquals('none', $result['cost_impact']);
    }

    public function test_optimize_batch_operations_for_s3(): void
    {
        $operations = array_fill(0, 150, ['type' => 'upload']);

        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->once()
            ->andReturn('amazon-s3');

        $this->mockFeatureDetectionService
            ->shouldReceive('isFeatureSupportedForUser')
            ->with($this->user, 'batch_operations')
            ->once()
            ->andReturn(true);

        $result = $this->service->optimizeBatchOperations($this->user, $operations);

        $this->assertEquals('parallel', $result['strategy']);
        $this->assertEquals(100, $result['batch_size']); // min(100, 150)
        $this->assertEquals(10, $result['parallel_limit']);
        $this->assertStringContainsString('S3 supports high concurrency', $result['reason']);
    }

    public function test_optimize_batch_operations_for_google_drive(): void
    {
        $operations = array_fill(0, 75, ['type' => 'upload']);

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
            ->with($this->user, 'batch_operations')
            ->once()
            ->andReturn(true);

        $result = $this->service->optimizeBatchOperations($this->user, $operations);

        $this->assertEquals('batch_api', $result['strategy']);
        $this->assertEquals(50, $result['batch_size']); // min(50, 75)
        $this->assertEquals(5, $result['parallel_limit']);
        $this->assertStringContainsString('batch API with rate limits', $result['reason']);
    }

    public function test_optimize_batch_operations_when_not_supported(): void
    {
        $operations = array_fill(0, 10, ['type' => 'upload']);

        $this->mockFeatureDetectionService
            ->shouldReceive('isFeatureSupportedForUser')
            ->with($this->user, 'batch_operations')
            ->once()
            ->andReturn(false);

        $result = $this->service->optimizeBatchOperations($this->user, $operations);

        $this->assertEquals('sequential', $result['strategy']);
        $this->assertEquals(1, $result['batch_size']);
        $this->assertEquals(1, $result['parallel_limit']);
        $this->assertStringContainsString('does not support batch operations', $result['reason']);
    }

    public function test_get_performance_recommendations_for_s3_with_large_files(): void
    {
        $usageStats = [
            'avg_file_size' => 150 * 1024 * 1024, // 150MB
            'large_file_count' => 50,
            'total_files' => 100,
            'uploads_per_day' => 75
        ];

        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->once()
            ->andReturn('amazon-s3');

        $this->mockFeatureDetectionService
            ->shouldReceive('getUserProviderCapabilities')
            ->with($this->user)
            ->once()
            ->andReturn([
                'multipart_upload' => true,
                'presigned_urls' => true
            ]);

        $result = $this->service->getPerformanceRecommendations($this->user, $usageStats);

        $this->assertNotEmpty($result);
        
        // Check for multipart upload recommendation
        $multipartRec = collect($result)->firstWhere('type', 'multipart_upload');
        $this->assertNotNull($multipartRec);
        $this->assertEquals('high', $multipartRec['impact']);
        $this->assertTrue($multipartRec['applicable']);

        // Check for presigned URLs recommendation
        $presignedRec = collect($result)->firstWhere('type', 'presigned_urls');
        $this->assertNotNull($presignedRec);
        $this->assertEquals('high', $presignedRec['impact']);
        $this->assertTrue($presignedRec['applicable']);

        // Check for high volume recommendation
        $highVolumeRec = collect($result)->firstWhere('type', 'high_volume');
        $this->assertNotNull($highVolumeRec);
        $this->assertEquals('medium', $highVolumeRec['impact']);
    }

    public function test_get_performance_recommendations_for_google_drive(): void
    {
        $usageStats = [
            'avg_file_size' => 10 * 1024 * 1024, // 10MB
            'large_file_count' => 20,
            'total_files' => 100,
            'uploads_per_day' => 25
        ];

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
            ->shouldReceive('getUserProviderCapabilities')
            ->with($this->user)
            ->once()
            ->andReturn([
                'resumable_upload' => true
            ]);

        $result = $this->service->getPerformanceRecommendations($this->user, $usageStats);

        $this->assertNotEmpty($result);
        
        // Check for resumable upload recommendation
        $resumableRec = collect($result)->firstWhere('type', 'resumable_upload');
        $this->assertNotNull($resumableRec);
        $this->assertEquals('medium', $resumableRec['impact']);
        $this->assertTrue($resumableRec['applicable']);
    }

    public function test_get_error_recovery_strategy_for_s3_rate_limit(): void
    {
        $error = new \Exception('SlowDown: Please reduce your request rate');
        $context = ['operation' => 'upload'];

        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->once()
            ->andReturn('amazon-s3');

        $result = $this->service->getErrorRecoveryStrategy($this->user, $error, $context);

        $this->assertTrue($result['retry']);
        $this->assertEquals(5000, $result['retry_delay']); // 5 seconds
        $this->assertEquals(3, $result['max_retries']);
        $this->assertFalse($result['user_action_required']);
    }

    public function test_get_error_recovery_strategy_for_google_drive_auth_error(): void
    {
        $error = new \Exception('invalid_grant: Token has been expired or revoked');
        $context = ['operation' => 'upload'];

        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->once()
            ->andReturn('google-drive');

        $result = $this->service->getErrorRecoveryStrategy($this->user, $error, $context);

        $this->assertFalse($result['retry']);
        $this->assertEquals('reauthorize', $result['alternative_action']);
        $this->assertTrue($result['user_action_required']);
    }

    public function test_get_error_recovery_strategy_generic(): void
    {
        $error = new \Exception('Generic error');
        $context = ['operation' => 'upload'];

        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->once()
            ->andReturn('unknown-provider');

        $result = $this->service->getErrorRecoveryStrategy($this->user, $error, $context);

        $this->assertTrue($result['retry']);
        $this->assertEquals(2000, $result['retry_delay']); // 2 seconds
        $this->assertEquals(2, $result['max_retries']);
        $this->assertFalse($result['user_action_required']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}