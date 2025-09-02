<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CloudStorageFeatureDetectionService;
use App\Services\CloudStorageManager;
use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use Mockery;

class CloudStorageFeatureDetectionServiceTest extends TestCase
{

    private CloudStorageFeatureDetectionService $service;
    private CloudStorageManager $mockStorageManager;
    private CloudStorageProviderInterface $mockProvider;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockStorageManager = Mockery::mock(CloudStorageManager::class);
        $this->mockProvider = Mockery::mock(CloudStorageProviderInterface::class);
        $this->service = new CloudStorageFeatureDetectionService($this->mockStorageManager);
        $this->user = new User(['id' => 1, 'email' => 'test@example.com']);
    }

    public function test_get_provider_capabilities_returns_capabilities_array(): void
    {
        $expectedCapabilities = [
            'folder_creation' => true,
            'file_upload' => true,
            'file_delete' => true,
            'oauth_authentication' => true,
        ];

        $this->mockStorageManager
            ->shouldReceive('getProvider')
            ->with('google-drive')
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getCapabilities')
            ->once()
            ->andReturn($expectedCapabilities);

        $result = $this->service->getProviderCapabilities('google-drive');

        $this->assertEquals($expectedCapabilities, $result);
    }

    public function test_get_provider_capabilities_handles_exception(): void
    {
        $this->mockStorageManager
            ->shouldReceive('getProvider')
            ->with('invalid-provider')
            ->once()
            ->andThrow(new \Exception('Provider not found'));

        $result = $this->service->getProviderCapabilities('invalid-provider');

        $this->assertEquals([], $result);
    }

    public function test_get_user_provider_capabilities_returns_user_provider_capabilities(): void
    {
        $expectedCapabilities = [
            'folder_creation' => true,
            'file_upload' => true,
            'presigned_urls' => false,
        ];

        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getCapabilities')
            ->once()
            ->andReturn($expectedCapabilities);

        $result = $this->service->getUserProviderCapabilities($this->user);

        $this->assertEquals($expectedCapabilities, $result);
    }

    public function test_is_feature_supported_returns_true_when_supported(): void
    {
        $this->mockStorageManager
            ->shouldReceive('getProvider')
            ->with('google-drive')
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('supportsFeature')
            ->with('folder_creation')
            ->once()
            ->andReturn(true);

        $result = $this->service->isFeatureSupported('google-drive', 'folder_creation');

        $this->assertTrue($result);
    }

    public function test_is_feature_supported_returns_false_when_not_supported(): void
    {
        $this->mockStorageManager
            ->shouldReceive('getProvider')
            ->with('amazon-s3')
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('supportsFeature')
            ->with('folder_creation')
            ->once()
            ->andReturn(false);

        $result = $this->service->isFeatureSupported('amazon-s3', 'folder_creation');

        $this->assertFalse($result);
    }

    public function test_is_feature_supported_for_user_returns_correct_result(): void
    {
        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('supportsFeature')
            ->with('presigned_urls')
            ->once()
            ->andReturn(true);

        $result = $this->service->isFeatureSupportedForUser($this->user, 'presigned_urls');

        $this->assertTrue($result);
    }

    public function test_get_providers_with_feature_returns_supporting_providers(): void
    {
        $availableProviders = ['google-drive', 'amazon-s3', 'azure-blob'];
        
        $this->mockStorageManager
            ->shouldReceive('getAvailableProviders')
            ->once()
            ->andReturn($availableProviders);

        // Mock each provider check
        foreach ($availableProviders as $index => $providerName) {
            $mockProvider = Mockery::mock(CloudStorageProviderInterface::class);
            
            $this->mockStorageManager
                ->shouldReceive('getProvider')
                ->with($providerName)
                ->once()
                ->andReturn($mockProvider);

            // Only google-drive and amazon-s3 support presigned_urls
            $supportsFeature = in_array($providerName, ['amazon-s3']);
            $mockProvider
                ->shouldReceive('supportsFeature')
                ->with('presigned_urls')
                ->once()
                ->andReturn($supportsFeature);
        }

        $result = $this->service->getProvidersWithFeature('presigned_urls');

        $this->assertEquals(['amazon-s3'], $result);
    }

    public function test_get_feature_compatibility_matrix_returns_complete_matrix(): void
    {
        $availableProviders = ['google-drive', 'amazon-s3'];
        $features = ['folder_creation', 'presigned_urls'];

        $this->mockStorageManager
            ->shouldReceive('getAvailableProviders')
            ->once()
            ->andReturn($availableProviders);

        // Mock capabilities for each provider
        foreach ($availableProviders as $providerName) {
            $mockProvider = Mockery::mock(CloudStorageProviderInterface::class);
            
            foreach ($features as $feature) {
                $this->mockStorageManager
                    ->shouldReceive('getProvider')
                    ->with($providerName)
                    ->once()
                    ->andReturn($mockProvider);

                $supportsFeature = ($providerName === 'google-drive' && $feature === 'folder_creation') ||
                                 ($providerName === 'amazon-s3' && $feature === 'presigned_urls');
                
                $mockProvider
                    ->shouldReceive('supportsFeature')
                    ->with($feature)
                    ->once()
                    ->andReturn($supportsFeature);
            }
        }

        $result = $this->service->getFeatureCompatibilityMatrix($features);

        $expected = [
            'google-drive' => [
                'folder_creation' => true,
                'presigned_urls' => false,
            ],
            'amazon-s3' => [
                'folder_creation' => false,
                'presigned_urls' => true,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_get_all_available_features_returns_unique_features(): void
    {
        $availableProviders = ['google-drive', 'amazon-s3'];

        $this->mockStorageManager
            ->shouldReceive('getAvailableProviders')
            ->once()
            ->andReturn($availableProviders);

        // Mock capabilities for each provider
        $googleDriveCapabilities = [
            'folder_creation' => true,
            'file_upload' => true,
            'oauth_authentication' => true,
        ];

        $s3Capabilities = [
            'file_upload' => true,
            'presigned_urls' => true,
            'storage_classes' => true,
        ];

        $googleDriveProvider = Mockery::mock(CloudStorageProviderInterface::class);
        $s3Provider = Mockery::mock(CloudStorageProviderInterface::class);

        $this->mockStorageManager
            ->shouldReceive('getProvider')
            ->with('google-drive')
            ->once()
            ->andReturn($googleDriveProvider);

        $this->mockStorageManager
            ->shouldReceive('getProvider')
            ->with('amazon-s3')
            ->once()
            ->andReturn($s3Provider);

        $googleDriveProvider
            ->shouldReceive('getCapabilities')
            ->once()
            ->andReturn($googleDriveCapabilities);

        $s3Provider
            ->shouldReceive('getCapabilities')
            ->once()
            ->andReturn($s3Capabilities);

        $result = $this->service->getAllAvailableFeatures();

        $expected = [
            'folder_creation',
            'file_upload',
            'oauth_authentication',
            'presigned_urls',
            'storage_classes',
        ];

        $this->assertEqualsCanonicalizing($expected, $result);
    }

    public function test_find_best_provider_for_features_returns_best_match(): void
    {
        $availableProviders = ['google-drive', 'amazon-s3'];
        $requiredFeatures = ['file_upload', 'file_delete'];
        $preferredFeatures = ['presigned_urls'];

        $this->mockStorageManager
            ->shouldReceive('getAvailableProviders')
            ->once()
            ->andReturn($availableProviders);

        // Mock current user provider
        $currentProvider = Mockery::mock(CloudStorageProviderInterface::class);
        $currentProvider
            ->shouldReceive('getProviderName')
            ->andReturn('google-drive');

        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($this->user)
            ->andReturn($currentProvider);

        // Mock feature support for each provider
        foreach ($availableProviders as $providerName) {
            $mockProvider = Mockery::mock(CloudStorageProviderInterface::class);
            
            foreach (array_merge($requiredFeatures, $preferredFeatures) as $feature) {
                $this->mockStorageManager
                    ->shouldReceive('getProvider')
                    ->with($providerName)
                    ->andReturn($mockProvider);

                // Both providers support required features, only S3 supports presigned_urls
                if (in_array($feature, $requiredFeatures)) {
                    $supportsFeature = true;
                } else {
                    $supportsFeature = ($providerName === 'amazon-s3' && $feature === 'presigned_urls');
                }
                
                $mockProvider
                    ->shouldReceive('supportsFeature')
                    ->with($feature)
                    ->andReturn($supportsFeature);
            }
        }

        $result = $this->service->findBestProviderForFeatures($requiredFeatures, $preferredFeatures, $this->user);

        // Google Drive should win because it's the user's current provider (gets bonus points)
        $this->assertEquals('google-drive', $result['provider']);
        $this->assertGreaterThan(0, $result['score']);
        $this->assertArrayHasKey('all_scores', $result);
    }

    public function test_find_best_provider_for_features_returns_null_when_no_provider_supports_required(): void
    {
        $availableProviders = ['google-drive', 'amazon-s3'];
        $requiredFeatures = ['unsupported_feature'];

        $this->mockStorageManager
            ->shouldReceive('getAvailableProviders')
            ->once()
            ->andReturn($availableProviders);

        // Mock feature support - no provider supports the required feature
        foreach ($availableProviders as $providerName) {
            $mockProvider = Mockery::mock(CloudStorageProviderInterface::class);
            
            $this->mockStorageManager
                ->shouldReceive('getProvider')
                ->with($providerName)
                ->once()
                ->andReturn($mockProvider);

            $mockProvider
                ->shouldReceive('supportsFeature')
                ->with('unsupported_feature')
                ->once()
                ->andReturn(false);
        }

        $result = $this->service->findBestProviderForFeatures($requiredFeatures);

        $this->assertNull($result['provider']);
        $this->assertEquals(0, $result['score']);
        $this->assertStringContainsString('No provider supports all required features', $result['message']);
    }

    public function test_get_feature_alternatives_returns_alternatives_for_folder_creation(): void
    {
        $alternatives = $this->service->getFeatureAlternatives('folder_creation', 'amazon-s3');

        $this->assertNotEmpty($alternatives);
        $this->assertEquals('workaround', $alternatives[0]['type']);
        $this->assertStringContainsString('key prefixes', $alternatives[0]['description']);
    }

    public function test_get_feature_alternatives_returns_alternatives_for_oauth_authentication(): void
    {
        $alternatives = $this->service->getFeatureAlternatives('oauth_authentication', 'amazon-s3');

        $this->assertNotEmpty($alternatives);
        $this->assertEquals('alternative', $alternatives[0]['type']);
        $this->assertStringContainsString('API key authentication', $alternatives[0]['description']);
    }

    public function test_get_feature_alternatives_returns_fallback_for_unknown_feature(): void
    {
        $alternatives = $this->service->getFeatureAlternatives('unknown_feature', 'google-drive');

        $this->assertNotEmpty($alternatives);
        $this->assertEquals('fallback', $alternatives[0]['type']);
        $this->assertStringContainsString('Feature not available', $alternatives[0]['description']);
    }

    public function test_can_gracefully_degrade_returns_true_for_supported_alternatives(): void
    {
        $result = $this->service->canGracefullyDegrade('folder_creation', 'amazon-s3');
        $this->assertTrue($result);
    }

    public function test_can_gracefully_degrade_returns_false_for_unsupported_alternatives(): void
    {
        // Mock the method to return only "not available" alternatives
        $service = Mockery::mock(CloudStorageFeatureDetectionService::class)->makePartial();
        $service->shouldReceive('getFeatureAlternatives')
            ->andReturn([
                [
                    'type' => 'not_available',
                    'description' => 'Feature not available',
                ]
            ]);

        $result = $service->canGracefullyDegrade('some_feature', 'some-provider');
        $this->assertFalse($result);
    }

    public function test_get_provider_optimization_recommendations_returns_google_drive_recommendations(): void
    {
        // Mock the service to return capabilities for Google Drive
        $service = Mockery::mock(CloudStorageFeatureDetectionService::class)->makePartial();
        $service->shouldReceive('getProviderCapabilities')
            ->with('google-drive')
            ->andReturn([
                'folder_creation' => true,
                'file_sharing' => true,
                'oauth_authentication' => true
            ]);

        $recommendations = $service->getProviderOptimizationRecommendations('google-drive');

        $this->assertNotEmpty($recommendations);
        
        // Check that we get recommendations for Google Drive features
        $featureNames = array_column($recommendations, 'feature');
        $this->assertContains('folder_creation', $featureNames);
    }

    public function test_get_provider_optimization_recommendations_returns_s3_recommendations(): void
    {
        // Mock the service to return capabilities for S3
        $service = Mockery::mock(CloudStorageFeatureDetectionService::class)->makePartial();
        $service->shouldReceive('getProviderCapabilities')
            ->with('amazon-s3')
            ->andReturn([
                'storage_classes' => true,
                'presigned_urls' => true,
                'multipart_upload' => true
            ]);

        $recommendations = $service->getProviderOptimizationRecommendations('amazon-s3');

        $this->assertNotEmpty($recommendations);
        
        // Check that we get recommendations for S3 features
        $featureNames = array_column($recommendations, 'feature');
        $this->assertContains('storage_classes', $featureNames);
        $this->assertContains('presigned_urls', $featureNames);
        $this->assertContains('multipart_upload', $featureNames);
    }

    public function test_get_provider_optimization_recommendations_returns_generic_for_unknown_provider(): void
    {
        $recommendations = $this->service->getProviderOptimizationRecommendations('unknown-provider');

        $this->assertNotEmpty($recommendations);
        $this->assertEquals('general', $recommendations[0]['feature']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}