<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Services\CloudStorageFeatureDetectionService;
use App\Services\CloudStorageGracefulDegradationService;
use App\Services\CloudStorageFeatureUtilizationService;
use App\Services\CloudStorageManager;
use App\Services\GoogleDriveProvider;
use App\Services\S3Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CloudStorageCapabilityDetectionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageFeatureDetectionService $featureDetectionService;
    private CloudStorageGracefulDegradationService $gracefulDegradationService;
    private CloudStorageFeatureUtilizationService $featureUtilizationService;
    private CloudStorageManager $storageManager;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->featureDetectionService = app(CloudStorageFeatureDetectionService::class);
        $this->gracefulDegradationService = app(CloudStorageGracefulDegradationService::class);
        $this->featureUtilizationService = app(CloudStorageFeatureUtilizationService::class);
        $this->storageManager = app(CloudStorageManager::class);
        $this->user = User::factory()->create();
    }

    public function test_google_drive_provider_capabilities_are_correctly_detected(): void
    {
        $capabilities = $this->featureDetectionService->getProviderCapabilities('google-drive');

        // Verify Google Drive specific capabilities
        $this->assertTrue($capabilities['folder_creation'] ?? false, 'Google Drive should support folder creation');
        $this->assertTrue($capabilities['file_upload'] ?? false, 'Google Drive should support file upload');
        $this->assertTrue($capabilities['file_delete'] ?? false, 'Google Drive should support file delete');
        $this->assertTrue($capabilities['oauth_authentication'] ?? false, 'Google Drive should support OAuth');
        $this->assertTrue($capabilities['hierarchical_storage'] ?? false, 'Google Drive should support hierarchical storage');
        $this->assertFalse($capabilities['presigned_urls'] ?? true, 'Google Drive should not support presigned URLs');
        $this->assertFalse($capabilities['storage_classes'] ?? true, 'Google Drive should not support storage classes');
    }

    public function test_s3_provider_capabilities_are_correctly_detected(): void
    {
        $capabilities = $this->featureDetectionService->getProviderCapabilities('amazon-s3');

        // Verify S3 specific capabilities
        $this->assertFalse($capabilities['folder_creation'] ?? true, 'S3 should not support real folder creation');
        $this->assertTrue($capabilities['file_upload'] ?? false, 'S3 should support file upload');
        $this->assertTrue($capabilities['file_delete'] ?? false, 'S3 should support file delete');
        $this->assertFalse($capabilities['oauth_authentication'] ?? true, 'S3 should not support OAuth');
        $this->assertTrue($capabilities['api_key_authentication'] ?? false, 'S3 should support API key authentication');
        $this->assertTrue($capabilities['flat_storage'] ?? false, 'S3 should support flat storage');
        $this->assertTrue($capabilities['presigned_urls'] ?? false, 'S3 should support presigned URLs');
        $this->assertTrue($capabilities['storage_classes'] ?? false, 'S3 should support storage classes');
        $this->assertTrue($capabilities['multipart_upload'] ?? false, 'S3 should support multipart upload');
    }

    public function test_feature_compatibility_matrix_shows_correct_differences(): void
    {
        $features = ['folder_creation', 'presigned_urls', 'oauth_authentication', 'storage_classes'];
        $matrix = $this->featureDetectionService->getFeatureCompatibilityMatrix($features);

        // Verify the matrix contains both providers
        $this->assertArrayHasKey('google-drive', $matrix);
        $this->assertArrayHasKey('amazon-s3', $matrix);

        // Verify Google Drive capabilities
        $this->assertTrue($matrix['google-drive']['folder_creation']);
        $this->assertFalse($matrix['google-drive']['presigned_urls']);
        $this->assertTrue($matrix['google-drive']['oauth_authentication']);
        $this->assertFalse($matrix['google-drive']['storage_classes']);

        // Verify S3 capabilities
        $this->assertFalse($matrix['amazon-s3']['folder_creation']);
        $this->assertTrue($matrix['amazon-s3']['presigned_urls']);
        $this->assertFalse($matrix['amazon-s3']['oauth_authentication']);
        $this->assertTrue($matrix['amazon-s3']['storage_classes']);
    }

    public function test_find_best_provider_for_features_selects_appropriate_provider(): void
    {
        // Test case 1: Need folder creation - should prefer Google Drive
        $result = $this->featureDetectionService->findBestProviderForFeatures(
            ['folder_creation', 'file_upload'],
            ['oauth_authentication']
        );

        $this->assertEquals('google-drive', $result['provider']);
        $this->assertGreaterThan(0, $result['score']);

        // Test case 2: Need presigned URLs - should prefer S3
        $result = $this->featureDetectionService->findBestProviderForFeatures(
            ['presigned_urls', 'file_upload'],
            ['storage_classes']
        );

        $this->assertEquals('amazon-s3', $result['provider']);
        $this->assertGreaterThan(0, $result['score']);

        // Test case 3: Need unsupported feature - should return null
        $result = $this->featureDetectionService->findBestProviderForFeatures(
            ['unsupported_feature']
        );

        $this->assertNull($result['provider']);
        $this->assertEquals(0, $result['score']);
    }

    public function test_get_providers_with_feature_returns_correct_providers(): void
    {
        // Test folder creation support
        $providers = $this->featureDetectionService->getProvidersWithFeature('folder_creation');
        $this->assertContains('google-drive', $providers);
        $this->assertNotContains('amazon-s3', $providers);

        // Test presigned URLs support
        $providers = $this->featureDetectionService->getProvidersWithFeature('presigned_urls');
        $this->assertContains('amazon-s3', $providers);
        $this->assertNotContains('google-drive', $providers);

        // Test file upload support (should be supported by all)
        $providers = $this->featureDetectionService->getProvidersWithFeature('file_upload');
        $this->assertContains('google-drive', $providers);
        $this->assertContains('amazon-s3', $providers);
    }

    public function test_feature_alternatives_provide_meaningful_workarounds(): void
    {
        // Test folder creation alternatives for S3
        $alternatives = $this->featureDetectionService->getFeatureAlternatives('folder_creation', 'amazon-s3');
        
        $this->assertNotEmpty($alternatives);
        $this->assertEquals('workaround', $alternatives[0]['type']);
        $this->assertStringContains('key prefixes', $alternatives[0]['description']);

        // Test OAuth alternatives for S3
        $alternatives = $this->featureDetectionService->getFeatureAlternatives('oauth_authentication', 'amazon-s3');
        
        $this->assertNotEmpty($alternatives);
        $this->assertEquals('alternative', $alternatives[0]['type']);
        $this->assertStringContains('API key authentication', $alternatives[0]['description']);
    }

    public function test_graceful_degradation_can_determine_degradation_possibility(): void
    {
        // Test that folder creation can be gracefully degraded for S3
        $canDegrade = $this->featureDetectionService->canGracefullyDegrade('folder_creation', 'amazon-s3');
        $this->assertTrue($canDegrade);

        // Test that OAuth can be gracefully degraded for S3
        $canDegrade = $this->featureDetectionService->canGracefullyDegrade('oauth_authentication', 'amazon-s3');
        $this->assertTrue($canDegrade);
    }

    public function test_provider_optimization_recommendations_are_provider_specific(): void
    {
        // Test Google Drive recommendations
        $recommendations = $this->featureDetectionService->getProviderOptimizationRecommendations('google-drive');
        
        $this->assertNotEmpty($recommendations);
        $featureNames = array_column($recommendations, 'feature');
        $this->assertContains('folder_creation', $featureNames);
        $this->assertContains('file_sharing', $featureNames);

        // Test S3 recommendations
        $recommendations = $this->featureDetectionService->getProviderOptimizationRecommendations('amazon-s3');
        
        $this->assertNotEmpty($recommendations);
        $featureNames = array_column($recommendations, 'feature');
        $this->assertContains('storage_classes', $featureNames);
        $this->assertContains('presigned_urls', $featureNames);
        $this->assertContains('multipart_upload', $featureNames);
    }

    public function test_storage_model_degradation_adapts_paths_correctly(): void
    {
        // Test flat storage adaptation (S3)
        $this->user->preferred_cloud_provider = 'amazon-s3';
        $this->user->save();

        $result = $this->gracefulDegradationService->handleStorageModelDegradation(
            $this->user, 
            'folder/subfolder/file.txt', 
            'create'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('flat_adaptation', $result['method']);
        $this->assertEquals('folder_subfolder_file.txt', $result['adapted_path']);

        // Test hierarchical storage adaptation (Google Drive)
        $this->user->preferred_cloud_provider = 'google-drive';
        $this->user->save();

        $result = $this->gracefulDegradationService->handleStorageModelDegradation(
            $this->user, 
            'folder\\subfolder\\file.txt', 
            'create'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('hierarchical_adaptation', $result['method']);
        $this->assertEquals('folder/subfolder/file.txt', $result['adapted_path']);
    }

    public function test_file_upload_optimization_varies_by_provider(): void
    {
        // Create a test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_file');
        file_put_contents($tempFile, str_repeat('x', 10 * 1024 * 1024)); // 10MB

        // Test Google Drive optimization
        $this->user->preferred_cloud_provider = 'google-drive';
        $this->user->save();

        $result = $this->featureUtilizationService->optimizeFileUpload(
            $this->user, 
            $tempFile, 
            'uploads/test_file.txt'
        );

        $this->assertEquals('resumable', $result['upload_type']);
        $this->assertTrue($result['create_folders'] ?? false);

        // Test S3 optimization
        $this->user->preferred_cloud_provider = 'amazon-s3';
        $this->user->save();

        $result = $this->featureUtilizationService->optimizeFileUpload(
            $this->user, 
            $tempFile, 
            'uploads/test_file.txt'
        );

        $this->assertEquals('simple', $result['upload_type']); // 10MB is below S3 multipart threshold
        $this->assertArrayHasKey('storage_class', $result);

        unlink($tempFile);
    }

    public function test_storage_class_recommendations_are_provider_appropriate(): void
    {
        $fileInfo = [
            'size' => 1024 * 1024, // 1MB
            'access_pattern' => 'infrequent',
            'retention_days' => 180
        ];

        // Test S3 storage class recommendation
        $this->user->preferred_cloud_provider = 'amazon-s3';
        $this->user->save();

        $result = $this->featureUtilizationService->getOptimalStorageClass($this->user, $fileInfo);

        $this->assertEquals('STANDARD_IA', $result['storage_class']);
        $this->assertStringContains('Infrequent access', $result['reason']);

        // Test Google Drive (no storage classes)
        $this->user->preferred_cloud_provider = 'google-drive';
        $this->user->save();

        $result = $this->featureUtilizationService->getOptimalStorageClass($this->user, $fileInfo);

        $this->assertEquals('standard', $result['storage_class']);
        $this->assertStringContains('automatic storage optimization', $result['reason']);
    }

    public function test_batch_operation_optimization_considers_provider_capabilities(): void
    {
        $operations = array_fill(0, 75, ['type' => 'upload']);

        // Test S3 batch optimization
        $this->user->preferred_cloud_provider = 'amazon-s3';
        $this->user->save();

        $result = $this->featureUtilizationService->optimizeBatchOperations($this->user, $operations);

        $this->assertEquals('parallel', $result['strategy']);
        $this->assertEquals(75, $result['batch_size']); // min(100, 75)
        $this->assertEquals(10, $result['parallel_limit']);

        // Test Google Drive batch optimization
        $this->user->preferred_cloud_provider = 'google-drive';
        $this->user->save();

        $result = $this->featureUtilizationService->optimizeBatchOperations($this->user, $operations);

        $this->assertEquals('batch_api', $result['strategy']);
        $this->assertEquals(50, $result['batch_size']); // min(50, 75)
        $this->assertEquals(5, $result['parallel_limit']);
    }

    public function test_performance_recommendations_reflect_provider_strengths(): void
    {
        $usageStats = [
            'avg_file_size' => 150 * 1024 * 1024, // 150MB
            'large_file_count' => 50,
            'total_files' => 100,
            'uploads_per_day' => 75
        ];

        // Test S3 performance recommendations
        $this->user->preferred_cloud_provider = 'amazon-s3';
        $this->user->save();

        $result = $this->featureUtilizationService->getPerformanceRecommendations($this->user, $usageStats);

        $this->assertNotEmpty($result);
        
        // Should recommend multipart upload for large files
        $multipartRec = collect($result)->firstWhere('type', 'multipart_upload');
        $this->assertNotNull($multipartRec);
        $this->assertTrue($multipartRec['applicable']);

        // Should recommend presigned URLs for high volume
        $presignedRec = collect($result)->firstWhere('type', 'presigned_urls');
        $this->assertNotNull($presignedRec);
        $this->assertTrue($presignedRec['applicable']);

        // Test Google Drive performance recommendations
        $this->user->preferred_cloud_provider = 'google-drive';
        $this->user->save();

        $result = $this->featureUtilizationService->getPerformanceRecommendations($this->user, $usageStats);

        $this->assertNotEmpty($result);
        
        // Should recommend resumable upload for large files
        $resumableRec = collect($result)->firstWhere('type', 'resumable_upload');
        $this->assertNotNull($resumableRec);
        $this->assertTrue($resumableRec['applicable']);
    }

    public function test_all_available_features_includes_features_from_all_providers(): void
    {
        $allFeatures = $this->featureDetectionService->getAllAvailableFeatures();

        // Should include Google Drive specific features
        $this->assertContains('folder_creation', $allFeatures);
        $this->assertContains('oauth_authentication', $allFeatures);
        $this->assertContains('hierarchical_storage', $allFeatures);

        // Should include S3 specific features
        $this->assertContains('presigned_urls', $allFeatures);
        $this->assertContains('storage_classes', $allFeatures);
        $this->assertContains('multipart_upload', $allFeatures);
        $this->assertContains('flat_storage', $allFeatures);
        $this->assertContains('api_key_authentication', $allFeatures);

        // Should include common features
        $this->assertContains('file_upload', $allFeatures);
        $this->assertContains('file_delete', $allFeatures);

        // Features should be unique
        $this->assertEquals(count($allFeatures), count(array_unique($allFeatures)));
    }
}