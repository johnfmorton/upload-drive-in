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
    use RefreshDatabase;

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
        $user = User::factory()->create();

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

        $this->manager->switchUserProvider($user, 'amazon-s3');

        $user->refresh();
        $this->assertEquals('amazon-s3', $user->preferred_cloud_provider);
    }

    public function test_switch_user_provider_throws_exception_for_unconfigured_provider(): void
    {
        $user = User::factory()->create();

        $this->mockConfigService
            ->shouldReceive('isProviderConfigured')
            ->with('unconfigured')
            ->once()
            ->andReturn(false);

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage("Provider 'unconfigured' is not configured");

        $this->manager->switchUserProvider($user, 'unconfigured');
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}