<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\CloudStorageManager;
use App\Services\CloudStorageFactory;
use App\Services\CloudConfigurationService;
use App\Services\GoogleDriveProvider;
use App\Services\S3Provider;
use App\Models\User;
use App\Models\CloudStorageSetting;
use App\Exceptions\CloudStorageException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class CloudStorageManagerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageManager $manager;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration
        Config::set('cloud-storage.default', 'google-drive');
        Config::set('cloud-storage.fallback', [
            'enabled' => true,
            'order' => ['google-drive', 'amazon-s3']
        ]);
        Config::set('cloud-storage.providers.google-drive', [
            'driver' => 'google-drive',
            'class' => GoogleDriveProvider::class,
            'error_handler' => \App\Services\GoogleDriveErrorHandler::class,
            'auth_type' => 'oauth',
            'storage_model' => 'hierarchical',
            'config' => [
                'client_id' => 'test-client-id',
                'client_secret' => 'test-client-secret',
                'redirect_uri' => 'http://localhost/callback',
            ],
            'features' => [
                'folder_creation' => true,
                'file_upload' => true,
                'file_delete' => true,
                'max_file_size' => 5368709120,
            ],
        ]);
        Config::set('cloud-storage.providers.amazon-s3', [
            'driver' => 'amazon-s3',
            'class' => S3Provider::class,
            'error_handler' => \App\Services\S3ErrorHandler::class,
            'auth_type' => 'api_key',
            'storage_model' => 'flat',
            'config' => [
                'access_key_id' => 'test-access-key',
                'secret_access_key' => 'test-secret-key',
                'region' => 'us-east-1',
                'bucket' => 'test-bucket',
            ],
            'features' => [
                'folder_creation' => false,
                'file_upload' => true,
                'file_delete' => true,
                'max_file_size' => 5497558138880,
            ],
        ]);

        // Create CloudStorageSetting records for configured providers
        CloudStorageSetting::create([
            'provider' => 'google-drive',
            'key' => 'client_id',
            'value' => 'test-client-id',
            'is_encrypted' => false,
        ]);
        CloudStorageSetting::create([
            'provider' => 'google-drive',
            'key' => 'client_secret',
            'value' => 'test-client-secret',
            'is_encrypted' => true,
        ]);
        CloudStorageSetting::create([
            'provider' => 'google-drive',
            'key' => 'redirect_uri',
            'value' => 'http://localhost/callback',
            'is_encrypted' => false,
        ]);
        CloudStorageSetting::create([
            'provider' => 'amazon-s3',
            'key' => 'access_key_id',
            'value' => 'test-access-key',
            'is_encrypted' => false,
        ]);
        CloudStorageSetting::create([
            'provider' => 'amazon-s3',
            'key' => 'secret_access_key',
            'value' => 'test-secret-key',
            'is_encrypted' => true,
        ]);
        CloudStorageSetting::create([
            'provider' => 'amazon-s3',
            'key' => 'region',
            'value' => 'us-east-1',
            'is_encrypted' => false,
        ]);
        CloudStorageSetting::create([
            'provider' => 'amazon-s3',
            'key' => 'bucket',
            'value' => 'test-bucket',
            'is_encrypted' => false,
        ]);

        $this->manager = app(CloudStorageManager::class);
        $this->user = User::factory()->create();
    }

    public function test_can_resolve_default_provider(): void
    {
        $provider = $this->manager->getDefaultProvider();

        $this->assertInstanceOf(GoogleDriveProvider::class, $provider);
        $this->assertEquals('google-drive', $provider->getProviderName());
    }

    public function test_can_resolve_specific_provider(): void
    {
        $provider = $this->manager->getProvider('amazon-s3');

        $this->assertInstanceOf(S3Provider::class, $provider);
        $this->assertEquals('amazon-s3', $provider->getProviderName());
    }

    public function test_can_resolve_user_preferred_provider(): void
    {
        $this->user->update(['preferred_cloud_provider' => 'amazon-s3']);

        $provider = $this->manager->getUserProvider($this->user);

        $this->assertInstanceOf(S3Provider::class, $provider);
        $this->assertEquals('amazon-s3', $provider->getProviderName());
    }

    public function test_falls_back_to_default_when_user_has_no_preference(): void
    {
        $this->user->update(['preferred_cloud_provider' => null]);

        $provider = $this->manager->getUserProvider($this->user);

        $this->assertInstanceOf(GoogleDriveProvider::class, $provider);
        $this->assertEquals('google-drive', $provider->getProviderName());
    }

    public function test_can_switch_user_provider(): void
    {
        $this->assertNull($this->user->preferred_cloud_provider);

        $this->manager->switchUserProvider($this->user, 'amazon-s3');

        $this->user->refresh();
        $this->assertEquals('amazon-s3', $this->user->preferred_cloud_provider);
    }

    public function test_switch_user_provider_validates_provider_exists(): void
    {
        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage("Provider 'nonexistent' is not configured");

        $this->manager->switchUserProvider($this->user, 'nonexistent');
    }

    public function test_can_get_available_providers(): void
    {
        $providers = $this->manager->getAvailableProviders();

        $this->assertContains('google-drive', $providers);
        $this->assertContains('amazon-s3', $providers);
        $this->assertCount(2, $providers);
    }

    public function test_can_get_all_provider_instances(): void
    {
        $providers = $this->manager->getAllProviders();

        $this->assertArrayHasKey('google-drive', $providers);
        $this->assertArrayHasKey('amazon-s3', $providers);
        $this->assertInstanceOf(GoogleDriveProvider::class, $providers['google-drive']);
        $this->assertInstanceOf(S3Provider::class, $providers['amazon-s3']);
    }

    public function test_can_validate_all_providers(): void
    {
        $results = $this->manager->validateAllProviders();

        $this->assertArrayHasKey('google-drive', $results);
        $this->assertArrayHasKey('amazon-s3', $results);

        foreach ($results as $providerName => $result) {
            $this->assertArrayHasKey('valid', $result);
            $this->assertArrayHasKey('errors', $result);
            $this->assertArrayHasKey('capabilities', $result);
            $this->assertIsBool($result['valid']);
            $this->assertIsArray($result['errors']);
            $this->assertIsArray($result['capabilities']);
        }
    }

    public function test_can_get_provider_capabilities(): void
    {
        $capabilities = $this->manager->getProviderCapabilities('google-drive');

        $this->assertIsArray($capabilities);
        $this->assertArrayHasKey('folder_creation', $capabilities);
        $this->assertArrayHasKey('file_upload', $capabilities);
        $this->assertTrue($capabilities['folder_creation']);
        $this->assertTrue($capabilities['file_upload']);
    }

    public function test_provider_resolution_with_fallback_when_enabled(): void
    {
        // Configure fallback
        Config::set('cloud-storage.fallback.enabled', true);
        Config::set('cloud-storage.fallback.order', ['google-drive', 'amazon-s3']);

        // Remove google-drive configuration to force fallback
        CloudStorageSetting::where('provider', 'google-drive')->delete();

        $provider = $this->manager->getProvider();

        // Should fall back to amazon-s3
        $this->assertInstanceOf(S3Provider::class, $provider);
        $this->assertEquals('amazon-s3', $provider->getProviderName());
    }

    public function test_throws_exception_when_no_providers_available(): void
    {
        // Remove all provider configurations
        CloudStorageSetting::truncate();

        $this->expectException(CloudStorageException::class);

        $this->manager->getProvider();
    }

    public function test_validate_provider_health_returns_health_status(): void
    {
        $provider = $this->manager->getProvider('google-drive');
        $healthStatus = $this->manager->validateProviderHealth($provider, $this->user);

        $this->assertIsArray($healthStatus);
        $this->assertArrayHasKey('healthy', $healthStatus);
        $this->assertArrayHasKey('provider', $healthStatus);
        $this->assertArrayHasKey('status', $healthStatus);
        $this->assertArrayHasKey('last_checked', $healthStatus);
        $this->assertArrayHasKey('errors', $healthStatus);
        $this->assertArrayHasKey('details', $healthStatus);

        $this->assertIsBool($healthStatus['healthy']);
        $this->assertEquals('google-drive', $healthStatus['provider']);
        $this->assertIsArray($healthStatus['errors']);
        $this->assertIsArray($healthStatus['details']);
    }

    public function test_validate_all_providers_health_returns_status_for_each(): void
    {
        $healthStatuses = $this->manager->validateAllProvidersHealth($this->user);

        $this->assertArrayHasKey('google-drive', $healthStatuses);
        $this->assertArrayHasKey('amazon-s3', $healthStatuses);

        foreach ($healthStatuses as $providerName => $status) {
            $this->assertArrayHasKey('healthy', $status);
            $this->assertArrayHasKey('provider', $status);
            $this->assertArrayHasKey('status', $status);
            $this->assertArrayHasKey('errors', $status);
            $this->assertEquals($providerName, $status['provider']);
        }
    }

    public function test_get_best_provider_for_user_returns_preferred_when_healthy(): void
    {
        $this->user->update(['preferred_cloud_provider' => 'amazon-s3']);

        $provider = $this->manager->getBestProviderForUser($this->user);

        $this->assertInstanceOf(S3Provider::class, $provider);
        $this->assertEquals('amazon-s3', $provider->getProviderName());
    }

    public function test_get_best_provider_for_user_falls_back_when_preferred_unhealthy(): void
    {
        // Set user preference to a provider that will be "unhealthy"
        $this->user->update(['preferred_cloud_provider' => 'amazon-s3']);
        
        // Remove S3 configuration to make it unhealthy
        CloudStorageSetting::where('provider', 'amazon-s3')->delete();

        $provider = $this->manager->getBestProviderForUser($this->user);

        // Should fall back to google-drive
        $this->assertInstanceOf(GoogleDriveProvider::class, $provider);
        $this->assertEquals('google-drive', $provider->getProviderName());
    }

    public function test_get_best_provider_throws_exception_when_no_healthy_providers(): void
    {
        // Remove all provider configurations
        CloudStorageSetting::truncate();

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('No healthy providers available for user');

        $this->manager->getBestProviderForUser($this->user);
    }

    public function test_provider_resolution_logs_appropriate_messages(): void
    {
        Log::shouldReceive('debug')->once()->with(
            'CloudStorageManager: Provider resolved',
            \Mockery::type('array')
        );

        $this->manager->getProvider('google-drive');
    }

    public function test_provider_switching_logs_appropriate_messages(): void
    {
        Log::shouldReceive('info')->once()->with(
            'CloudStorageManager: User provider switched',
            \Mockery::type('array')
        );

        $this->manager->switchUserProvider($this->user, 'amazon-s3');
    }

    public function test_fallback_provider_logs_appropriate_messages(): void
    {
        // Remove google-drive configuration to force fallback
        CloudStorageSetting::where('provider', 'google-drive')->delete();

        Log::shouldReceive('info')->once()->with(
            'CloudStorageManager: Using fallback provider',
            \Mockery::type('array')
        );

        $this->manager->getProvider();
    }

    public function test_integration_with_real_service_container(): void
    {
        // Test that the manager can be resolved from the service container
        $manager = app(CloudStorageManager::class);
        
        $this->assertInstanceOf(CloudStorageManager::class, $manager);
        
        // Test that it can resolve providers
        $provider = $manager->getProvider('google-drive');
        $this->assertInstanceOf(GoogleDriveProvider::class, $provider);
    }

    public function test_concurrent_provider_access_is_safe(): void
    {
        // Test that multiple concurrent accesses don't cause issues
        $providers = [];
        
        for ($i = 0; $i < 5; $i++) {
            $providers[] = $this->manager->getProvider('google-drive');
        }
        
        foreach ($providers as $provider) {
            $this->assertInstanceOf(GoogleDriveProvider::class, $provider);
            $this->assertEquals('google-drive', $provider->getProviderName());
        }
    }

    public function test_provider_configuration_changes_are_reflected(): void
    {
        // Initially should have both providers
        $providers = $this->manager->getAvailableProviders();
        $this->assertContains('google-drive', $providers);
        $this->assertContains('amazon-s3', $providers);

        // Remove S3 configuration
        CloudStorageSetting::where('provider', 'amazon-s3')->delete();

        // Create new manager instance to reflect changes
        $newManager = app(CloudStorageManager::class);
        $providers = $newManager->getAvailableProviders();
        
        $this->assertContains('google-drive', $providers);
        $this->assertNotContains('amazon-s3', $providers);
    }
}