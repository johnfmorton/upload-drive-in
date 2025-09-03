<?php

namespace Tests\Unit\Services;

use App\Enums\CloudStorageErrorType;
use App\Enums\ProviderAvailabilityStatus;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\CloudConfigurationService;
use App\Services\CloudStorageConfigurationValidationService;
use App\Services\CloudStorageErrorMessageService;
use App\Services\CloudStorageFactory;
use App\Services\CloudStorageProviderAvailabilityService;
use App\Services\GoogleDriveProvider;
use App\Services\ValidationResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CloudStorageConfigurationValidationPipelineTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageConfigurationValidationService $service;
    private CloudConfigurationService $configService;
    private CloudStorageFactory $factory;
    private CloudStorageErrorMessageService $errorMessageService;
    private CloudStorageProviderAvailabilityService $availabilityService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configService = Mockery::mock(CloudConfigurationService::class);
        $this->factory = Mockery::mock(CloudStorageFactory::class);
        $this->errorMessageService = Mockery::mock(CloudStorageErrorMessageService::class);
        $this->availabilityService = Mockery::mock(CloudStorageProviderAvailabilityService::class);

        $this->service = new CloudStorageConfigurationValidationService(
            $this->configService,
            $this->factory,
            $this->errorMessageService,
            $this->availabilityService
        );

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_validate_provider_selection_success()
    {
        // Arrange
        $provider = 'google-drive';
        
        $this->configService->shouldReceive('getSupportedProviders')
            ->andReturn(['google-drive', 'amazon-s3']);
        
        $this->availabilityService->shouldReceive('isProviderFullyFunctional')
            ->with($provider)
            ->andReturn(true);
        
        $this->configService->shouldReceive('getEffectiveConfig')
            ->with($provider)
            ->andReturn(['client_id' => 'test', 'client_secret' => 'secret']);
        
        $this->configService->shouldReceive('validateProviderConfig')
            ->with($provider, ['client_id' => 'test', 'client_secret' => 'secret'])
            ->andReturn([]);

        // Act
        $result = $this->service->validateProviderSelection($provider);

        // Assert
        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
        $this->assertEquals($provider, $result->metadata['provider']);
        $this->assertEquals('provider_selection', $result->metadata['validation_step']);
    }

    public function test_validate_provider_selection_unsupported_provider()
    {
        // Arrange
        $provider = 'unsupported-provider';
        
        $this->configService->shouldReceive('getSupportedProviders')
            ->andReturn(['google-drive', 'amazon-s3']);

        // Act
        $result = $this->service->validateProviderSelection($provider);

        // Assert
        $this->assertFalse($result->isValid);
        $this->assertContains("Provider 'unsupported-provider' is not supported", $result->errors);
        $this->assertStringContainsString('Please select a supported provider', $result->recommendedAction);
    }

    public function test_validate_provider_selection_provider_not_available()
    {
        // Arrange
        $provider = 'amazon-s3';
        
        $this->configService->shouldReceive('getSupportedProviders')
            ->andReturn(['google-drive', 'amazon-s3']);
        
        $this->availabilityService->shouldReceive('isProviderFullyFunctional')
            ->with($provider)
            ->andReturn(false);
        
        $this->availabilityService->shouldReceive('getProviderAvailabilityStatus')
            ->with($provider)
            ->andReturn('coming_soon');

        // Act
        $result = $this->service->validateProviderSelection($provider);

        // Assert
        $this->assertFalse($result->isValid);
        $this->assertContains("Provider 'amazon-s3' is not currently available (status: coming_soon)", $result->errors);
        $this->assertStringContainsString('Please select an available provider', $result->recommendedAction);
    }

    public function test_validate_provider_selection_configuration_errors()
    {
        // Arrange
        $provider = 'google-drive';
        
        $this->configService->shouldReceive('getSupportedProviders')
            ->andReturn(['google-drive']);
        
        $this->availabilityService->shouldReceive('isProviderFullyFunctional')
            ->with($provider)
            ->andReturn(true);
        
        $this->configService->shouldReceive('getEffectiveConfig')
            ->with($provider)
            ->andReturn(['client_id' => '']);
        
        $this->configService->shouldReceive('validateProviderConfig')
            ->with($provider, ['client_id' => ''])
            ->andReturn(['Client ID is required']);

        // Act
        $result = $this->service->validateProviderSelection($provider);

        // Assert
        $this->assertFalse($result->isValid);
        $this->assertContains('Client ID is required', $result->errors);
        $this->assertStringContainsString('Please configure the required settings', $result->recommendedAction);
    }

    public function test_validate_connection_setup_success_with_existing_auth()
    {
        // Arrange
        $user = User::factory()->create();
        $provider = 'google-drive';
        
        // Mock provider selection validation
        $this->configService->shouldReceive('getSupportedProviders')
            ->andReturn(['google-drive']);
        
        $this->availabilityService->shouldReceive('isProviderFullyFunctional')
            ->with($provider)
            ->andReturn(true);
        
        $this->configService->shouldReceive('getEffectiveConfig')
            ->with($provider)
            ->andReturn(['client_id' => 'test', 'client_secret' => 'secret']);
        
        $this->configService->shouldReceive('validateProviderConfig')
            ->andReturn([]);

        // Mock provider instantiation
        $mockProvider = Mockery::mock(GoogleDriveProvider::class);
        $this->factory->shouldReceive('create')
            ->with($provider, ['client_id' => 'test', 'client_secret' => 'secret'])
            ->andReturn($mockProvider);

        // Create existing token
        $user->googleDriveToken()->create([
            'access_token' => 'valid-token',
            'refresh_token' => 'valid-refresh-token',
            'expires_at' => now()->addHour()
        ]);

        // Act
        $result = $this->service->validateConnectionSetup($user, $provider);

        // Assert
        $this->assertTrue($result->isValid);
        $this->assertEquals($user->id, $result->metadata['user_id']);
        $this->assertTrue($result->metadata['has_existing_auth']);
        $this->assertEquals('connection_setup', $result->metadata['validation_step']);
    }

    public function test_validate_connection_setup_success_without_existing_auth()
    {
        // Arrange
        $user = User::factory()->create();
        $provider = 'google-drive';
        
        // Mock provider selection validation
        $this->configService->shouldReceive('getSupportedProviders')
            ->andReturn(['google-drive']);
        
        $this->availabilityService->shouldReceive('isProviderFullyFunctional')
            ->with($provider)
            ->andReturn(true);
        
        $this->configService->shouldReceive('getEffectiveConfig')
            ->with($provider)
            ->andReturn(['client_id' => 'test', 'client_secret' => 'secret']);
        
        $this->configService->shouldReceive('validateProviderConfig')
            ->andReturn([]);

        // Mock provider instantiation
        $mockProvider = Mockery::mock(GoogleDriveProvider::class);
        $this->factory->shouldReceive('create')
            ->with($provider, ['client_id' => 'test', 'client_secret' => 'secret'])
            ->andReturn($mockProvider);

        // Act
        $result = $this->service->validateConnectionSetup($user, $provider);

        // Assert
        $this->assertTrue($result->isValid);
        $this->assertFalse($result->metadata['has_existing_auth']);
        $this->assertContains('No existing authentication found. OAuth flow will be required.', $result->warnings);
        $this->assertStringContainsString("Click 'Connect' to authenticate", $result->recommendedAction);
    }

    public function test_validate_connection_setup_invalid_provider_selection()
    {
        // Arrange
        $user = User::factory()->create();
        $provider = 'unsupported-provider';
        
        $this->configService->shouldReceive('getSupportedProviders')
            ->andReturn(['google-drive']);

        // Act
        $result = $this->service->validateConnectionSetup($user, $provider);

        // Assert
        $this->assertFalse($result->isValid);
        $this->assertContains("Provider 'unsupported-provider' is not supported", $result->errors);
    }

    public function test_validate_connection_setup_provider_instantiation_failure()
    {
        // Arrange
        $user = User::factory()->create();
        $provider = 'google-drive';
        
        // Mock provider selection validation
        $this->configService->shouldReceive('getSupportedProviders')
            ->andReturn(['google-drive']);
        
        $this->availabilityService->shouldReceive('isProviderFullyFunctional')
            ->with($provider)
            ->andReturn(true);
        
        $this->configService->shouldReceive('getEffectiveConfig')
            ->with($provider)
            ->andReturn(['client_id' => 'test', 'client_secret' => 'secret']);
        
        $this->configService->shouldReceive('validateProviderConfig')
            ->andReturn([]);

        // Mock provider instantiation failure
        $this->factory->shouldReceive('create')
            ->with($provider, ['client_id' => 'test', 'client_secret' => 'secret'])
            ->andThrow(new \Exception('Provider instantiation failed'));

        // Act
        $result = $this->service->validateConnectionSetup($user, $provider);

        // Assert
        $this->assertFalse($result->isValid);
        $this->assertContains('Failed to initialize provider: Provider instantiation failed', $result->errors);
        $this->assertStringContainsString('Please check your configuration', $result->recommendedAction);
    }

    public function test_perform_comprehensive_validation_success_with_connectivity()
    {
        // Arrange
        $user = User::factory()->create();
        $provider = 'google-drive';
        
        // Mock all the dependencies for comprehensive validation
        $this->setupSuccessfulProviderMocks($provider);
        
        // Mock provider for connectivity test
        $mockProvider = Mockery::mock(GoogleDriveProvider::class);
        $mockProvider->shouldReceive('hasValidConnection')->andReturn(true);
        $this->factory->shouldReceive('create')->andReturn($mockProvider);

        // Create existing token
        $user->googleDriveToken()->create([
            'access_token' => 'valid-token',
            'refresh_token' => 'valid-refresh-token',
            'expires_at' => now()->addHour()
        ]);

        // Act
        $result = $this->service->performComprehensiveValidation($user, $provider);

        // Assert
        $this->assertTrue($result->isValid);
        $this->assertEquals('comprehensive', $result->metadata['validation_step']);
        $this->assertEquals('passed', $result->metadata['connectivity_test']);
        $this->assertTrue($result->metadata['feature_validation']['features_checked']);
        $this->assertTrue($result->metadata['performance_validation']['performance_validated']);
    }

    public function test_perform_comprehensive_validation_connectivity_failure()
    {
        // Arrange
        $user = User::factory()->create();
        $provider = 'google-drive';
        
        // Mock all the dependencies
        $this->setupSuccessfulProviderMocks($provider);
        
        // Mock provider for connectivity test failure
        $mockProvider = Mockery::mock(GoogleDriveProvider::class);
        $mockProvider->shouldReceive('hasValidConnection')->andReturn(false);
        $this->factory->shouldReceive('create')->andReturn($mockProvider);

        // Create existing token
        $user->googleDriveToken()->create([
            'access_token' => 'valid-token',
            'refresh_token' => 'valid-refresh-token',
            'expires_at' => now()->addHour()
        ]);

        // Act
        $result = $this->service->performComprehensiveValidation($user, $provider);

        // Assert
        $this->assertFalse($result->isValid);
        $this->assertContains('Google Drive API test failed', $result->errors);
        $this->assertStringContainsString('Please reconnect your Google Drive account', $result->recommendedAction);
    }

    public function test_perform_comprehensive_validation_no_authentication()
    {
        // Arrange
        $user = User::factory()->create();
        $provider = 'google-drive';
        
        // Mock all the dependencies
        $this->setupSuccessfulProviderMocks($provider);
        
        $mockProvider = Mockery::mock(GoogleDriveProvider::class);
        $this->factory->shouldReceive('create')->andReturn($mockProvider);

        // No existing token created

        // Act
        $result = $this->service->performComprehensiveValidation($user, $provider);

        // Assert
        $this->assertTrue($result->isValid);
        $this->assertContains('Authentication required - connectivity test skipped', $result->warnings);
        $this->assertEquals('skipped_no_auth', $result->metadata['connectivity_test']);
    }

    public function test_clear_validation_cache()
    {
        // Arrange
        $user = User::factory()->create();
        $provider = 'google-drive';
        
        // Set up cache
        Cache::put('cloud_storage_validation:provider_selection:google-drive', 'test', 300);
        Cache::put("cloud_storage_validation:connection_setup:{$user->id}:google-drive", 'test', 300);
        Cache::put("cloud_storage_validation:comprehensive:{$user->id}:google-drive", 'test', 300);

        // Act
        $this->service->clearValidationCache($provider, $user->id);

        // Assert
        $this->assertFalse(Cache::has("cloud_storage_validation:connection_setup:{$user->id}:google-drive"));
        $this->assertFalse(Cache::has("cloud_storage_validation:comprehensive:{$user->id}:google-drive"));
    }

    public function test_validation_result_caching()
    {
        // Arrange
        $provider = 'google-drive';
        $this->setupSuccessfulProviderMocks($provider);

        // Act - First call
        $result1 = $this->service->validateProviderSelection($provider);
        
        // Act - Second call (should be cached)
        $result2 = $this->service->validateProviderSelection($provider);

        // Assert
        $this->assertTrue($result1->isValid);
        $this->assertTrue($result2->isValid);
        $this->assertEquals($result1->metadata['provider'], $result2->metadata['provider']);
        
        // Verify cache was used (mocks should only be called once)
        $this->configService->shouldHaveReceived('getSupportedProviders')->once();
    }

    private function setupSuccessfulProviderMocks(string $provider): void
    {
        $this->configService->shouldReceive('getSupportedProviders')
            ->andReturn([$provider]);
        
        $this->availabilityService->shouldReceive('isProviderFullyFunctional')
            ->with($provider)
            ->andReturn(true);
        
        $this->configService->shouldReceive('getEffectiveConfig')
            ->with($provider)
            ->andReturn(['client_id' => 'test', 'client_secret' => 'secret']);
        
        $this->configService->shouldReceive('validateProviderConfig')
            ->andReturn([]);
    }
}