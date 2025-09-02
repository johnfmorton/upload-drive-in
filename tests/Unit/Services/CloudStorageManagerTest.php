<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CloudStorageManager;
use App\Services\CloudStorageFactory;
use App\Services\CloudConfigurationService;
use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use App\Exceptions\CloudStorageException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class CloudStorageManagerTest extends TestCase
{

    private CloudStorageManager $manager;
    private CloudStorageFactory $mockFactory;
    private CloudConfigurationService $mockConfigService;
    private CloudStorageProviderInterface $mockProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockFactory = Mockery::mock(CloudStorageFactory::class);
        $this->mockConfigService = Mockery::mock(CloudConfigurationService::class);
        $this->mockProvider = Mockery::mock(CloudStorageProviderInterface::class);

        $this->manager = new CloudStorageManager(
            $this->mockFactory,
            $this->mockConfigService
        );
    }

    public function test_get_provider_returns_default_when_no_provider_specified(): void
    {
        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('google-drive')
            ->once()
            ->andReturn(true);

        $this->mockFactory
            ->shouldReceive('createForUser')
            ->with(null, 'google-drive')
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->andReturn('google-drive');

        $result = $this->manager->getProvider();

        $this->assertInstanceOf(CloudStorageProviderInterface::class, $result);
    }

    public function test_get_provider_returns_specified_provider(): void
    {
        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('amazon-s3')
            ->once()
            ->andReturn(true);

        $this->mockFactory
            ->shouldReceive('createForUser')
            ->with(null, 'amazon-s3')
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $result = $this->manager->getProvider('amazon-s3');

        $this->assertInstanceOf(CloudStorageProviderInterface::class, $result);
    }

    public function test_get_provider_throws_exception_when_provider_not_configured(): void
    {
        // Mock config to disable fallback for this test
        config(['cloud-storage.fallback.enabled' => false]);
        
        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('unconfigured-provider')
            ->once()
            ->andReturn(false);

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage("Provider 'unconfigured-provider' is not configured");

        $this->manager->getProvider('unconfigured-provider');
    }

    public function test_get_user_provider_uses_user_preference(): void
    {
        $user = User::factory()->make(['preferred_cloud_provider' => 'amazon-s3']);

        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('amazon-s3')
            ->once()
            ->andReturn(true);

        $this->mockFactory
            ->shouldReceive('createForUser')
            ->with($user, 'amazon-s3')
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $result = $this->manager->getUserProvider($user);

        $this->assertInstanceOf(CloudStorageProviderInterface::class, $result);
    }

    public function test_get_user_provider_falls_back_to_default_when_no_preference(): void
    {
        $user = User::factory()->make(['preferred_cloud_provider' => null]);

        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('google-drive')
            ->once()
            ->andReturn(true);

        $this->mockFactory
            ->shouldReceive('createForUser')
            ->with($user, 'google-drive')
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->andReturn('google-drive');

        $result = $this->manager->getUserProvider($user);

        $this->assertInstanceOf(CloudStorageProviderInterface::class, $result);
    }

    public function test_get_available_providers_returns_configured_providers(): void
    {
        $this->mockFactory
            ->shouldReceive('getRegisteredProviders')
            ->once()
            ->andReturn([
                'google-drive' => 'App\Services\GoogleDriveProvider',
                'amazon-s3' => 'App\Services\S3Provider',
                'unconfigured' => 'App\Services\UnconfiguredProvider',
            ]);

        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('google-drive')
            ->once()
            ->andReturn(true);

        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('amazon-s3')
            ->once()
            ->andReturn(true);

        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('unconfigured')
            ->once()
            ->andReturn(false);

        $result = $this->manager->getAvailableProviders();

        $this->assertEquals(['google-drive', 'amazon-s3'], $result);
    }

    public function test_switch_user_provider_updates_user_preference(): void
    {
        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('setAttribute')->andReturn(null);
        $mockUser->shouldReceive('getAttribute')
            ->with('preferred_cloud_provider')
            ->andReturn(null);
        $mockUser->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);
        $mockUser->id = 1;
        $mockUser->shouldReceive('update')
            ->with(['preferred_cloud_provider' => 'amazon-s3'])
            ->once();

        $mockHealthStatus = Mockery::mock('App\Services\CloudStorageHealthStatus');
        $mockHealthStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockHealthStatus->shouldReceive('getStatus')->andReturn('healthy');
        $mockHealthStatus->shouldReceive('getLastChecked')->andReturn(now());
        $mockHealthStatus->shouldReceive('getErrors')->andReturn([]);

        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('amazon-s3')
            ->twice() // Called once in switchUserProvider and once in getProvider
            ->andReturn(true);

        $this->mockFactory
            ->shouldReceive('createForUser')
            ->with($mockUser, 'amazon-s3')
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->andReturn('amazon-s3');

        $this->mockProvider
            ->shouldReceive('getConnectionHealth')
            ->with($mockUser)
            ->once()
            ->andReturn($mockHealthStatus);

        $this->mockProvider
            ->shouldReceive('hasValidConnection')
            ->with($mockUser)
            ->andReturn(true);

        $this->mockProvider
            ->shouldReceive('getCapabilities')
            ->andReturn(['file_upload' => true]);

        $this->manager->switchUserProvider($mockUser, 'amazon-s3');
        
        // The assertion is implicit in the mock expectations above
        $this->assertTrue(true); // Explicit assertion to avoid risky test warning
    }

    public function test_switch_user_provider_throws_exception_for_unconfigured_provider(): void
    {
        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('setAttribute')->andReturn(null);
        $mockUser->id = 1;

        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('unconfigured')
            ->once()
            ->andReturn(false);

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage("Provider 'unconfigured' is not configured");

        $this->manager->switchUserProvider($mockUser, 'unconfigured');
    }

    public function test_get_provider_capabilities_returns_provider_capabilities(): void
    {
        $expectedCapabilities = [
            'folder_creation' => true,
            'file_upload' => true,
            'presigned_urls' => false,
        ];

        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('google-drive')
            ->once()
            ->andReturn(true);

        $this->mockFactory
            ->shouldReceive('createForUser')
            ->with(null, 'google-drive')
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->andReturn('google-drive');

        $this->mockProvider
            ->shouldReceive('getCapabilities')
            ->once()
            ->andReturn($expectedCapabilities);

        $result = $this->manager->getProviderCapabilities('google-drive');

        $this->assertEquals($expectedCapabilities, $result);
    }

    public function test_validate_all_providers_returns_validation_results(): void
    {
        $this->mockFactory
            ->shouldReceive('getRegisteredProviders')
            ->once()
            ->andReturn([
                'google-drive' => 'App\Services\GoogleDriveProvider',
            ]);

        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('google-drive')
            ->once()
            ->andReturn(true);

        $this->mockConfigService
            ->shouldReceive('getProviderConfig')
            ->with('google-drive')
            ->once()
            ->andReturn(['client_id' => 'test', 'client_secret' => 'secret']);

        $this->mockFactory
            ->shouldReceive('create')
            ->with('google-drive')
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('validateConfiguration')
            ->once()
            ->andReturn([]);

        $this->mockProvider
            ->shouldReceive('getCapabilities')
            ->once()
            ->andReturn(['folder_creation' => true]);

        $result = $this->manager->validateAllProviders();

        $this->assertArrayHasKey('google-drive', $result);
        $this->assertTrue($result['google-drive']['valid']);
        $this->assertEmpty($result['google-drive']['errors']);
        $this->assertEquals(['folder_creation' => true], $result['google-drive']['capabilities']);
    }

    public function test_validate_provider_health_returns_health_status(): void
    {
        $user = User::factory()->make();
        $mockHealthStatus = Mockery::mock('App\Services\CloudStorageHealthStatus');
        
        $mockHealthStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockHealthStatus->shouldReceive('getStatus')->andReturn('healthy');
        $mockHealthStatus->shouldReceive('getLastChecked')->andReturn(now());
        $mockHealthStatus->shouldReceive('getErrors')->andReturn([]);

        $this->mockProvider
            ->shouldReceive('getConnectionHealth')
            ->with($user)
            ->once()
            ->andReturn($mockHealthStatus);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->andReturn('google-drive');

        $this->mockProvider
            ->shouldReceive('hasValidConnection')
            ->with($user)
            ->andReturn(true);

        $this->mockProvider
            ->shouldReceive('getCapabilities')
            ->andReturn(['folder_creation' => true]);

        $result = $this->manager->validateProviderHealth($this->mockProvider, $user);

        $this->assertTrue($result['healthy']);
        $this->assertEquals('google-drive', $result['provider']);
        $this->assertEquals('healthy', $result['status']);
        $this->assertIsArray($result['errors']);
        $this->assertIsArray($result['details']);
    }

    public function test_validate_all_providers_health_returns_status_for_each(): void
    {
        $user = User::factory()->make();
        $mockHealthStatus = Mockery::mock('App\Services\CloudStorageHealthStatus');
        
        $mockHealthStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockHealthStatus->shouldReceive('getStatus')->andReturn('healthy');
        $mockHealthStatus->shouldReceive('getLastChecked')->andReturn(now());
        $mockHealthStatus->shouldReceive('getErrors')->andReturn([]);

        $this->mockFactory
            ->shouldReceive('getRegisteredProviders')
            ->once()
            ->andReturn([
                'google-drive' => 'App\Services\GoogleDriveProvider',
            ]);

        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('google-drive')
            ->twice() // Called once in getAvailableProviders and once in validateAllProvidersHealth
            ->andReturn(true);

        $this->mockFactory
            ->shouldReceive('createForUser')
            ->with($user, 'google-drive')
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->andReturn('google-drive');

        $this->mockProvider
            ->shouldReceive('getConnectionHealth')
            ->with($user)
            ->once()
            ->andReturn($mockHealthStatus);

        $this->mockProvider
            ->shouldReceive('hasValidConnection')
            ->with($user)
            ->andReturn(true);

        $this->mockProvider
            ->shouldReceive('getCapabilities')
            ->andReturn(['folder_creation' => true]);

        $result = $this->manager->validateAllProvidersHealth($user);

        $this->assertArrayHasKey('google-drive', $result);
        $this->assertTrue($result['google-drive']['healthy']);
        $this->assertEquals('google-drive', $result['google-drive']['provider']);
    }

    public function test_get_best_provider_for_user_returns_preferred_when_healthy(): void
    {
        $user = User::factory()->make(['preferred_cloud_provider' => 'google-drive']);
        $mockHealthStatus = Mockery::mock('App\Services\CloudStorageHealthStatus');
        
        $mockHealthStatus->shouldReceive('isHealthy')->andReturn(true);
        $mockHealthStatus->shouldReceive('getStatus')->andReturn('healthy');
        $mockHealthStatus->shouldReceive('getLastChecked')->andReturn(now());
        $mockHealthStatus->shouldReceive('getErrors')->andReturn([]);

        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('google-drive')
            ->once()
            ->andReturn(true);

        $this->mockFactory
            ->shouldReceive('createForUser')
            ->with($user, 'google-drive')
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->andReturn('google-drive');

        $this->mockProvider
            ->shouldReceive('getConnectionHealth')
            ->with($user)
            ->once()
            ->andReturn($mockHealthStatus);

        $this->mockProvider
            ->shouldReceive('hasValidConnection')
            ->with($user)
            ->andReturn(true);

        $this->mockProvider
            ->shouldReceive('getCapabilities')
            ->andReturn(['folder_creation' => true]);

        $result = $this->manager->getBestProviderForUser($user);

        $this->assertSame($this->mockProvider, $result);
    }

    public function test_get_best_provider_throws_exception_when_no_healthy_providers(): void
    {
        $user = User::factory()->make(['preferred_cloud_provider' => 'google-drive']);
        $mockHealthStatus = Mockery::mock('App\Services\CloudStorageHealthStatus');
        
        $mockHealthStatus->shouldReceive('isHealthy')->andReturn(false);
        $mockHealthStatus->shouldReceive('getStatus')->andReturn('unhealthy');
        $mockHealthStatus->shouldReceive('getLastChecked')->andReturn(now());
        $mockHealthStatus->shouldReceive('getErrors')->andReturn(['Connection failed']);

        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('google-drive')
            ->once()
            ->andReturn(true);

        $this->mockFactory
            ->shouldReceive('createForUser')
            ->with($user, 'google-drive')
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('getProviderName')
            ->andReturn('google-drive');

        $this->mockProvider
            ->shouldReceive('getConnectionHealth')
            ->with($user)
            ->once()
            ->andReturn($mockHealthStatus);

        $this->mockProvider
            ->shouldReceive('hasValidConnection')
            ->with($user)
            ->andReturn(false);

        $this->mockProvider
            ->shouldReceive('getCapabilities')
            ->andReturn(['folder_creation' => true]);

        // Mock config for fallback
        config(['cloud-storage.fallback.order' => ['google-drive']]);

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('No healthy providers available for user');

        $this->manager->getBestProviderForUser($user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}