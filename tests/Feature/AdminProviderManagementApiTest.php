<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use App\Services\CloudStorageManager;
use App\Services\CloudConfigurationService;
use App\Services\CloudStorageHealthService;
use App\Models\CloudStorageHealthStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class AdminProviderManagementApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email_verified_at' => now(),
            'two_factor_enabled' => false, // Disable 2FA for tests
        ]);

        $this->clientUser = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_can_access_provider_management_page()
    {
        $response = $this->actingAs($this->adminUser)
                         ->get('/admin/cloud-storage/provider-management');

        $response->assertStatus(200)
                 ->assertSee('Provider Management')
                 ->assertViewIs('admin.cloud-storage.provider-management');
    }

    public function test_client_cannot_access_provider_management_page()
    {
        $response = $this->actingAs($this->clientUser)
                         ->get('/admin/cloud-storage/provider-management');

        $response->assertStatus(403);
    }

    public function test_get_provider_details_returns_correct_data()
    {
        // Mock the CloudStorageManager
        $mockManager = Mockery::mock(CloudStorageManager::class);
        $mockProvider = Mockery::mock(\App\Contracts\CloudStorageProviderInterface::class);
        $mockConfigService = Mockery::mock(CloudConfigurationService::class);
        $mockHealthService = Mockery::mock(CloudStorageHealthService::class);

        $mockManager->shouldReceive('getAvailableProviders')
                   ->andReturn(['google-drive']);
        
        $mockManager->shouldReceive('getProvider')
                   ->with('google-drive')
                   ->andReturn($mockProvider);

        $mockProvider->shouldReceive('getCapabilities')
                    ->andReturn(['file_upload' => true, 'folder_creation' => true]);
        $mockProvider->shouldReceive('getAuthenticationType')
                    ->andReturn('oauth');
        $mockProvider->shouldReceive('getStorageModel')
                    ->andReturn('hierarchical');
        $mockProvider->shouldReceive('getMaxFileSize')
                    ->andReturn(5368709120);
        $mockProvider->shouldReceive('getSupportedFileTypes')
                    ->andReturn(['*']);
        $mockProvider->shouldReceive('hasValidConnection')
                    ->andReturn(true);

        $mockConfigService->shouldReceive('getProviderConfig')
                         ->with('google-drive')
                         ->andReturn(['client_id' => 'test_id']);
        $mockConfigService->shouldReceive('isProviderConfigured')
                         ->with('google-drive')
                         ->andReturn(true);

        $healthStatus = new CloudStorageHealthStatus([
            'user_id' => $this->adminUser->id,
            'provider' => 'google-drive',
            'status' => 'connected',
            'consolidated_status' => 'healthy',
            'last_check_at' => now(),
        ]);

        $mockHealthService->shouldReceive('checkConnectionHealth')
                         ->with($this->adminUser, 'google-drive')
                         ->andReturn($healthStatus);

        $this->app->instance(CloudStorageManager::class, $mockManager);
        $this->app->instance(CloudConfigurationService::class, $mockConfigService);
        $this->app->instance(CloudStorageHealthService::class, $mockHealthService);

        $response = $this->actingAs($this->adminUser)
                         ->getJson('/admin/cloud-storage/providers/google-drive/details');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'provider' => [
                         'name' => 'google-drive',
                         'display_name' => 'Google drive',
                         'auth_type' => 'oauth',
                         'storage_model' => 'hierarchical',
                         'is_configured' => true,
                         'has_connection' => true,
                     ]
                 ]);
    }

    public function test_get_provider_details_returns_404_for_invalid_provider()
    {
        $mockManager = Mockery::mock(CloudStorageManager::class);
        $mockManager->shouldReceive('getAvailableProviders')
                   ->andReturn(['google-drive']);

        $this->app->instance(CloudStorageManager::class, $mockManager);

        $response = $this->actingAs($this->adminUser)
                         ->getJson('/admin/cloud-storage/providers/invalid-provider/details');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'error' => 'Provider not found'
                 ]);
    }

    public function test_validate_provider_config_validates_correctly()
    {
        $mockManager = Mockery::mock(CloudStorageManager::class);
        $mockProvider = Mockery::mock(\App\Contracts\CloudStorageProviderInterface::class);

        $mockManager->shouldReceive('getAvailableProviders')
                   ->andReturn(['google-drive']);
        
        $mockManager->shouldReceive('getProvider')
                   ->with('google-drive')
                   ->andReturn($mockProvider);

        $mockProvider->shouldReceive('validateConfiguration')
                    ->with(['client_id' => 'test_id'])
                    ->andReturn([]); // No validation errors

        $this->app->instance(CloudStorageManager::class, $mockManager);

        $response = $this->actingAs($this->adminUser)
                         ->postJson('/admin/cloud-storage/providers/google-drive/validate', [
                             'client_id' => 'test_id'
                         ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Configuration is valid'
                 ]);
    }

    public function test_validate_provider_config_returns_validation_errors()
    {
        $mockManager = Mockery::mock(CloudStorageManager::class);
        $mockProvider = Mockery::mock(\App\Contracts\CloudStorageProviderInterface::class);

        $mockManager->shouldReceive('getAvailableProviders')
                   ->andReturn(['google-drive']);
        
        $mockManager->shouldReceive('getProvider')
                   ->with('google-drive')
                   ->andReturn($mockProvider);

        $mockProvider->shouldReceive('validateConfiguration')
                    ->with(['client_id' => ''])
                    ->andReturn(['client_id' => 'Client ID is required']);

        $this->app->instance(CloudStorageManager::class, $mockManager);

        $response = $this->actingAs($this->adminUser)
                         ->postJson('/admin/cloud-storage/providers/google-drive/validate', [
                             'client_id' => ''
                         ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Configuration validation failed',
                     'validation_errors' => ['client_id' => 'Client ID is required']
                 ]);
    }

    public function test_update_provider_config_updates_successfully()
    {
        $mockManager = Mockery::mock(CloudStorageManager::class);
        $mockProvider = Mockery::mock(\App\Contracts\CloudStorageProviderInterface::class);
        $mockConfigService = Mockery::mock(CloudConfigurationService::class);

        $mockManager->shouldReceive('getAvailableProviders')
                   ->andReturn(['google-drive']);
        
        $mockManager->shouldReceive('getProvider')
                   ->with('google-drive')
                   ->andReturn($mockProvider);

        $mockProvider->shouldReceive('validateConfiguration')
                    ->with(['client_id' => 'new_test_id'])
                    ->andReturn([]); // No validation errors

        $mockConfigService->shouldReceive('setProviderConfig')
                         ->with('google-drive', ['client_id' => 'new_test_id'])
                         ->once();

        $this->app->instance(CloudStorageManager::class, $mockManager);
        $this->app->instance(CloudConfigurationService::class, $mockConfigService);

        $response = $this->actingAs($this->adminUser)
                         ->putJson('/admin/cloud-storage/providers/google-drive/config', [
                             'client_id' => 'new_test_id'
                         ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Provider configuration updated successfully'
                 ]);
    }

    public function test_update_provider_config_fails_with_validation_errors()
    {
        $mockManager = Mockery::mock(CloudStorageManager::class);
        $mockProvider = Mockery::mock(\App\Contracts\CloudStorageProviderInterface::class);

        $mockManager->shouldReceive('getAvailableProviders')
                   ->andReturn(['google-drive']);
        
        $mockManager->shouldReceive('getProvider')
                   ->with('google-drive')
                   ->andReturn($mockProvider);

        $mockProvider->shouldReceive('validateConfiguration')
                    ->with(['client_id' => ''])
                    ->andReturn(['client_id' => 'Client ID is required']);

        $this->app->instance(CloudStorageManager::class, $mockManager);

        $response = $this->actingAs($this->adminUser)
                         ->putJson('/admin/cloud-storage/providers/google-drive/config', [
                             'client_id' => ''
                         ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'error' => 'Configuration validation failed',
                     'validation_errors' => ['client_id' => 'Client ID is required']
                 ]);
    }

    public function test_set_user_provider_updates_successfully()
    {
        $mockManager = Mockery::mock(CloudStorageManager::class);

        $mockManager->shouldReceive('getAvailableProviders')
                   ->andReturn(['google-drive', 'amazon-s3']);
        
        $mockManager->shouldReceive('switchUserProvider')
                   ->with($this->adminUser, 'amazon-s3')
                   ->once();

        $this->app->instance(CloudStorageManager::class, $mockManager);

        $response = $this->actingAs($this->adminUser)
                         ->postJson('/admin/cloud-storage/set-provider', [
                             'provider' => 'amazon-s3'
                         ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Provider preference updated successfully',
                     'provider' => 'amazon-s3'
                 ]);
    }

    public function test_set_user_provider_fails_for_unavailable_provider()
    {
        $mockManager = Mockery::mock(CloudStorageManager::class);

        $mockManager->shouldReceive('getAvailableProviders')
                   ->andReturn(['google-drive']);

        $this->app->instance(CloudStorageManager::class, $mockManager);

        $response = $this->actingAs($this->adminUser)
                         ->postJson('/admin/cloud-storage/set-provider', [
                             'provider' => 'unavailable-provider'
                         ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'error' => 'Provider not available'
                 ]);
    }

    public function test_test_connection_returns_success_for_healthy_provider()
    {
        $mockHealthService = Mockery::mock(CloudStorageHealthService::class);

        $healthStatus = new CloudStorageHealthStatus([
            'user_id' => $this->adminUser->id,
            'provider' => 'google-drive',
            'status' => 'connected',
            'consolidated_status' => 'healthy',
            'last_check_at' => now(),
            'last_successful_operation_at' => now(),
        ]);

        $mockHealthService->shouldReceive('checkConnectionHealth')
                         ->with($this->adminUser, 'google-drive')
                         ->andReturn($healthStatus);

        $this->app->instance(CloudStorageHealthService::class, $mockHealthService);

        $response = $this->actingAs($this->adminUser)
                         ->postJson('/admin/cloud-storage/test', [
                             'provider' => 'google-drive'
                         ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'consolidated_status' => 'healthy'
                 ]);
    }

    public function test_test_connection_returns_failure_for_unhealthy_provider()
    {
        $mockHealthService = Mockery::mock(CloudStorageHealthService::class);

        $healthStatus = new CloudStorageHealthStatus([
            'user_id' => $this->adminUser->id,
            'provider' => 'google-drive',
            'status' => 'error',
            'consolidated_status' => 'connection_issues',
            'last_check_at' => now(),
            'last_error_message' => 'Connection timeout',
        ]);

        $mockHealthService->shouldReceive('checkConnectionHealth')
                         ->with($this->adminUser, 'google-drive')
                         ->andReturn($healthStatus);

        $this->app->instance(CloudStorageHealthService::class, $mockHealthService);

        $response = $this->actingAs($this->adminUser)
                         ->postJson('/admin/cloud-storage/test', [
                             'provider' => 'google-drive'
                         ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => false,
                     'consolidated_status' => 'connection_issues'
                 ]);
    }

    public function test_provider_management_page_loads_with_correct_data()
    {
        // Mock services
        $mockManager = Mockery::mock(CloudStorageManager::class);
        $mockProvider = Mockery::mock(\App\Contracts\CloudStorageProviderInterface::class);
        $mockConfigService = Mockery::mock(CloudConfigurationService::class);
        $mockHealthService = Mockery::mock(CloudStorageHealthService::class);

        $mockManager->shouldReceive('getAvailableProviders')
                   ->andReturn(['google-drive']);
        
        $mockManager->shouldReceive('getProvider')
                   ->with('google-drive')
                   ->andReturn($mockProvider);

        $mockManager->shouldReceive('getUserProvider')
                   ->with($this->adminUser)
                   ->andReturn($mockProvider);

        $mockManager->shouldReceive('getDefaultProvider')
                   ->andReturn($mockProvider);

        $mockProvider->shouldReceive('getProviderName')
                    ->andReturn('google-drive');
        $mockProvider->shouldReceive('getCapabilities')
                    ->andReturn(['file_upload' => true]);
        $mockProvider->shouldReceive('getAuthenticationType')
                    ->andReturn('oauth');
        $mockProvider->shouldReceive('getStorageModel')
                    ->andReturn('hierarchical');
        $mockProvider->shouldReceive('getMaxFileSize')
                    ->andReturn(5368709120);
        $mockProvider->shouldReceive('getSupportedFileTypes')
                    ->andReturn(['*']);
        $mockProvider->shouldReceive('hasValidConnection')
                    ->andReturn(true);

        $mockConfigService->shouldReceive('getProviderConfig')
                         ->with('google-drive')
                         ->andReturn(['client_id' => 'test_id']);
        $mockConfigService->shouldReceive('isProviderConfigured')
                         ->with('google-drive')
                         ->andReturn(true);

        $healthStatus = new CloudStorageHealthStatus([
            'user_id' => $this->adminUser->id,
            'provider' => 'google-drive',
            'status' => 'connected',
            'consolidated_status' => 'healthy',
            'last_check_at' => now(),
        ]);

        $mockHealthService->shouldReceive('checkConnectionHealth')
                         ->with($this->adminUser, 'google-drive')
                         ->andReturn($healthStatus);

        $this->app->instance(CloudStorageManager::class, $mockManager);
        $this->app->instance(CloudConfigurationService::class, $mockConfigService);
        $this->app->instance(CloudStorageHealthService::class, $mockHealthService);

        $response = $this->actingAs($this->adminUser)
                         ->get('/admin/cloud-storage/provider-management');

        $response->assertStatus(200)
                 ->assertViewHas('providersData')
                 ->assertViewHas('currentProvider', 'google-drive')
                 ->assertViewHas('defaultProvider', 'google-drive');
    }

    public function test_error_handling_when_services_fail()
    {
        // Mock manager to throw exception
        $mockManager = Mockery::mock(CloudStorageManager::class);
        $mockManager->shouldReceive('getAvailableProviders')
                   ->andThrow(new \Exception('Service unavailable'));

        $this->app->instance(CloudStorageManager::class, $mockManager);

        Log::shouldReceive('error')->once();

        $response = $this->actingAs($this->adminUser)
                         ->get('/admin/cloud-storage/provider-management');

        $response->assertRedirect('/admin/cloud-storage')
                 ->assertSessionHas('error', 'Failed to load provider management interface');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}