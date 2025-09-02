<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use App\Services\CloudStorageManager;
use App\Services\CloudConfigurationService;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageFactory;
use App\Services\GoogleDriveProvider;
use App\Services\S3Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderManagementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email_verified_at' => now(),
            'two_factor_enabled' => false, // Disable 2FA for tests
        ]);
    }

    public function test_complete_provider_management_workflow()
    {
        // Set 2FA session as verified for admin user
        session(['two_factor_verified' => true]);
        
        // Test accessing the provider management page
        $response = $this->actingAs($this->adminUser)
                         ->get('/admin/cloud-storage/provider-management');

        $response->assertStatus(200)
                 ->assertSee('Provider Management');

        // Test getting available providers
        $response = $this->actingAs($this->adminUser)
                         ->getJson('/admin/cloud-storage/providers');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'providers' => [
                         '*' => [
                             'name',
                             'display_name',
                             'capabilities',
                             'auth_type',
                             'storage_model'
                         ]
                     ],
                     'default_provider'
                 ]);

        // Test provider details endpoint
        $response = $this->actingAs($this->adminUser)
                         ->getJson('/admin/cloud-storage/providers/google-drive/details');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'provider' => [
                         'name',
                         'display_name',
                         'capabilities',
                         'auth_type',
                         'storage_model',
                         'is_configured',
                         'has_connection'
                     ]
                 ]);

        // Test configuration validation
        $response = $this->actingAs($this->adminUser)
                         ->postJson('/admin/cloud-storage/providers/google-drive/validate', [
                             'client_id' => 'test_client_id',
                             'client_secret' => 'test_client_secret'
                         ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message'
                 ]);

        // Test connection testing
        $response = $this->actingAs($this->adminUser)
                         ->postJson('/admin/cloud-storage/test', [
                             'provider' => 'google-drive'
                         ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message'
                 ]);
    }

    public function test_provider_management_with_real_services()
    {
        // Get real instances of services
        $manager = app(CloudStorageManager::class);
        $factory = app(CloudStorageFactory::class);
        $configService = app(CloudConfigurationService::class);

        // Test that services are properly configured
        $this->assertInstanceOf(CloudStorageManager::class, $manager);
        $this->assertInstanceOf(CloudStorageFactory::class, $factory);
        $this->assertInstanceOf(CloudConfigurationService::class, $configService);

        // Test getting available providers
        $availableProviders = $manager->getAvailableProviders();
        $this->assertIsArray($availableProviders);
        $this->assertContains('google-drive', $availableProviders);

        // Test getting provider instances
        $googleDriveProvider = $manager->getProvider('google-drive');
        $this->assertInstanceOf(GoogleDriveProvider::class, $googleDriveProvider);

        // Test provider capabilities
        $capabilities = $googleDriveProvider->getCapabilities();
        $this->assertIsArray($capabilities);
        $this->assertArrayHasKey('file_upload', $capabilities);

        // Test provider metadata
        $this->assertEquals('google-drive', $googleDriveProvider->getProviderName());
        $this->assertEquals('oauth', $googleDriveProvider->getAuthenticationType());
        $this->assertEquals('hierarchical', $googleDriveProvider->getStorageModel());
    }

    public function test_provider_configuration_management()
    {
        $configService = app(CloudConfigurationService::class);

        // Test getting provider configuration
        $config = $configService->getProviderConfig('google-drive');
        $this->assertIsArray($config);

        // Test checking if provider is configured
        $isConfigured = $configService->isProviderConfigured('google-drive');
        $this->assertIsBool($isConfigured);

        // Test getting all provider configurations
        $allConfigs = $configService->getAllProviderConfigs();
        $this->assertIsArray($allConfigs);
        $this->assertArrayHasKey('google-drive', $allConfigs);
    }

    public function test_provider_health_checking()
    {
        $healthService = app(CloudStorageHealthService::class);

        // Test health check for a provider
        $healthStatus = $healthService->checkConnectionHealth($this->adminUser, 'google-drive');
        
        $this->assertNotNull($healthStatus);
        $this->assertEquals($this->adminUser->id, $healthStatus->user_id);
        $this->assertEquals('google-drive', $healthStatus->provider);
        $this->assertNotNull($healthStatus->consolidated_status);
    }

    public function test_provider_switching()
    {
        $manager = app(CloudStorageManager::class);

        // Get current user provider
        $currentProvider = $manager->getUserProvider($this->adminUser);
        $this->assertNotNull($currentProvider);

        // Test switching to a different provider (if available)
        $availableProviders = $manager->getAvailableProviders();
        if (count($availableProviders) > 1) {
            $newProvider = $availableProviders[1];
            $manager->switchUserProvider($this->adminUser, $newProvider);
            
            // Verify the switch
            $updatedProvider = $manager->getUserProvider($this->adminUser);
            $this->assertEquals($newProvider, $updatedProvider->getProviderName());
        }
    }

    public function test_error_handling_for_invalid_operations()
    {
        // Test invalid provider name
        $response = $this->actingAs($this->adminUser)
                         ->getJson('/admin/cloud-storage/providers/invalid-provider/details');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'error' => 'Provider not found'
                 ]);

        // Test setting invalid provider
        $response = $this->actingAs($this->adminUser)
                         ->postJson('/admin/cloud-storage/set-provider', [
                             'provider' => 'invalid-provider'
                         ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'error' => 'Provider not available'
                 ]);

        // Test validation with invalid provider
        $response = $this->actingAs($this->adminUser)
                         ->postJson('/admin/cloud-storage/providers/invalid-provider/validate', [
                             'client_id' => 'test'
                         ]);

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'error' => 'Provider not found'
                 ]);
    }

    public function test_authorization_requirements()
    {
        $clientUser = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email_verified_at' => now(),
        ]);

        // Test that client users cannot access provider management
        $response = $this->actingAs($clientUser)
                         ->get('/admin/cloud-storage/provider-management');

        $response->assertStatus(403);

        // Test that client users cannot access provider APIs
        $response = $this->actingAs($clientUser)
                         ->getJson('/admin/cloud-storage/providers/google-drive/details');

        $response->assertStatus(403);
    }

    public function test_csrf_protection()
    {
        // Test that CSRF protection is enabled for POST requests
        $response = $this->actingAs($this->adminUser)
                         ->post('/admin/cloud-storage/set-provider', [
                             'provider' => 'google-drive'
                         ]);

        $response->assertStatus(419); // CSRF token mismatch
    }

    public function test_validation_requirements()
    {
        // Test that provider parameter is required
        $response = $this->actingAs($this->adminUser)
                         ->postJson('/admin/cloud-storage/set-provider', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['provider']);

        // Test that provider parameter is required for testing
        $response = $this->actingAs($this->adminUser)
                         ->postJson('/admin/cloud-storage/test', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['provider']);
    }
}