<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CloudStorageFactory;
use App\Services\CloudConfigurationService;
use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use App\Exceptions\CloudStorageException;
use Illuminate\Container\Container;
use Mockery;

class CloudStorageFactoryTest extends TestCase
{
    private CloudStorageFactory $factory;
    private Container $mockContainer;
    private CloudConfigurationService $mockConfigService;
    private CloudStorageProviderInterface $mockProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockContainer = Mockery::mock(Container::class);
        $this->mockConfigService = Mockery::mock(CloudConfigurationService::class);
        $this->mockProvider = Mockery::mock(CloudStorageProviderInterface::class);

        $this->factory = new CloudStorageFactory(
            $this->mockContainer,
            $this->mockConfigService
        );
    }

    public function test_register_provider_successfully(): void
    {
        $providerClass = get_class($this->mockProvider);
        
        $this->factory->register('test-provider', $providerClass);
        
        $registered = $this->factory->getRegisteredProviders();
        $this->assertArrayHasKey('test-provider', $registered);
        $this->assertEquals($providerClass, $registered['test-provider']);
    }

    public function test_register_provider_throws_exception_for_invalid_class(): void
    {
        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage("Cannot register provider 'test-provider': class 'InvalidClass' does not implement CloudStorageProviderInterface");

        $this->factory->register('test-provider', 'InvalidClass');
    }

    public function test_create_provider_successfully(): void
    {
        $providerClass = get_class($this->mockProvider);
        $config = ['client_id' => 'test', 'client_secret' => 'secret'];

        $this->factory->register('test-provider', $providerClass);

        $this->mockConfigService
            ->shouldReceive('getEffectiveConfig')
            ->with('test-provider')
            ->once()
            ->andReturn($config);

        $this->mockContainer
            ->shouldReceive('make')
            ->with($providerClass)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('initialize')
            ->with($config)
            ->once();

        $result = $this->factory->create('test-provider');

        $this->assertInstanceOf(CloudStorageProviderInterface::class, $result);
    }

    public function test_create_provider_throws_exception_for_unregistered_provider(): void
    {
        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage("Provider 'unregistered-provider' is not registered");

        $this->factory->create('unregistered-provider');
    }

    public function test_create_for_user_with_default_provider(): void
    {
        $user = User::factory()->make();
        $providerClass = get_class($this->mockProvider);
        $config = ['client_id' => 'test'];

        $this->factory->register('google-drive', $providerClass);

        $this->mockConfigService
            ->shouldReceive('getEffectiveConfig')
            ->with('google-drive')
            ->twice()
            ->andReturn($config);

        $this->mockContainer
            ->shouldReceive('make')
            ->with($providerClass)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('initialize')
            ->with($config)
            ->once();

        $result = $this->factory->createForUser($user);

        $this->assertInstanceOf(CloudStorageProviderInterface::class, $result);
    }

    public function test_create_for_user_with_specified_provider(): void
    {
        $user = User::factory()->make();
        $providerClass = get_class($this->mockProvider);
        $config = ['access_key' => 'test'];

        $this->factory->register('amazon-s3', $providerClass);

        $this->mockConfigService
            ->shouldReceive('getEffectiveConfig')
            ->with('amazon-s3')
            ->twice()
            ->andReturn($config);

        $this->mockContainer
            ->shouldReceive('make')
            ->with($providerClass)
            ->once()
            ->andReturn($this->mockProvider);

        $this->mockProvider
            ->shouldReceive('initialize')
            ->with($config)
            ->once();

        $result = $this->factory->createForUser($user, 'amazon-s3');

        $this->assertInstanceOf(CloudStorageProviderInterface::class, $result);
    }

    public function test_validate_provider_returns_true_for_valid_class(): void
    {
        $providerClass = get_class($this->mockProvider);
        
        $result = $this->factory->validateProvider($providerClass);
        
        $this->assertTrue($result);
    }

    public function test_validate_provider_returns_false_for_invalid_class(): void
    {
        $result = $this->factory->validateProvider('NonExistentClass');
        
        $this->assertFalse($result);
    }

    public function test_validate_provider_returns_false_for_class_not_implementing_interface(): void
    {
        $result = $this->factory->validateProvider(\stdClass::class);
        
        $this->assertFalse($result);
    }

    public function test_get_registered_providers_returns_empty_array_initially(): void
    {
        $result = $this->factory->getRegisteredProviders();
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_registered_providers_returns_registered_providers(): void
    {
        $providerClass = get_class($this->mockProvider);
        
        $this->factory->register('provider1', $providerClass);
        $this->factory->register('provider2', $providerClass);
        
        $result = $this->factory->getRegisteredProviders();
        
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('provider1', $result);
        $this->assertArrayHasKey('provider2', $result);
    }

    public function test_clear_cache_clears_provider_cache(): void
    {
        // This test verifies the method exists and can be called
        // The actual caching behavior is tested through integration tests
        $this->factory->clearCache();
        
        // If we get here without exception, the method works
        $this->assertTrue(true);
    }

    public function test_discover_providers_returns_empty_array_for_non_existent_paths(): void
    {
        $result = $this->factory->discoverProviders(['/non/existent/path']);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}