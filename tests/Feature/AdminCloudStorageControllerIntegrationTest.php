<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CloudStorageManager;
use App\Services\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminCloudStorageControllerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_available_providers()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Auth::login($admin);

        // Mock the CloudStorageManager
        $mockManager = $this->createMock(CloudStorageManager::class);
        $mockProvider = $this->createMock(\App\Contracts\CloudStorageProviderInterface::class);

        $mockProvider->method('getProviderName')->willReturn('google-drive');
        $mockProvider->method('getCapabilities')->willReturn(['file_upload', 'file_delete']);
        $mockProvider->method('getAuthenticationType')->willReturn('oauth');
        $mockProvider->method('getStorageModel')->willReturn('hierarchical');

        $mockManager->method('getAvailableProviders')->willReturn(['google-drive']);
        $mockManager->method('getProvider')->willReturn($mockProvider);
        $mockManager->method('getDefaultProvider')->willReturn($mockProvider);

        $this->app->instance(CloudStorageManager::class, $mockManager);

        $response = $this->getJson(route('admin.cloud-storage.providers'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'providers' => [
                    [
                        'name' => 'google-drive',
                        'display_name' => 'Google drive',
                        'capabilities' => ['file_upload', 'file_delete'],
                        'auth_type' => 'oauth',
                        'storage_model' => 'hierarchical',
                    ]
                ],
                'default_provider' => 'google-drive',
            ]);
    }

    public function test_admin_can_set_user_provider()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Auth::login($admin);

        // Mock the CloudStorageManager
        $mockManager = $this->createMock(CloudStorageManager::class);
        $mockManager->method('getAvailableProviders')->willReturn(['google-drive', 'amazon-s3']);
        $mockManager->expects($this->once())
            ->method('switchUserProvider')
            ->with($admin, 'amazon-s3');

        $this->app->instance(CloudStorageManager::class, $mockManager);

        $response = $this->postJson(route('admin.cloud-storage.set-provider'), [
            'provider' => 'amazon-s3'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Provider preference updated successfully',
                'provider' => 'amazon-s3',
            ]);
    }

    public function test_admin_cannot_set_unavailable_provider()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Auth::login($admin);

        // Mock the CloudStorageManager
        $mockManager = $this->createMock(CloudStorageManager::class);
        $mockManager->method('getAvailableProviders')->willReturn(['google-drive']);

        $this->app->instance(CloudStorageManager::class, $mockManager);

        $response = $this->postJson(route('admin.cloud-storage.set-provider'), [
            'provider' => 'invalid-provider'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Provider not available',
            ]);
    }

    public function test_admin_can_reconnect_provider_via_storage_manager()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Auth::login($admin);

        // Mock the CloudStorageManager
        $mockManager = $this->createMock(CloudStorageManager::class);
        $mockProvider = $this->createMock(\App\Contracts\CloudStorageProviderInterface::class);

        $mockProvider->method('getAuthUrl')->willReturn('https://example.com/auth');
        $mockManager->method('getProvider')->willReturn($mockProvider);

        $this->app->instance(CloudStorageManager::class, $mockManager);

        $response = $this->postJson(route('admin.cloud-storage.reconnect'), [
            'provider' => 'google-drive'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'redirect_url' => 'https://example.com/auth',
            ]);
    }

    public function test_admin_can_test_connection_via_storage_manager()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Auth::login($admin);

        // Mock the CloudStorageHealthService
        $mockHealthService = $this->createMock(\App\Services\CloudStorageHealthService::class);
        $mockHealthStatus = $this->createMock(\App\Models\CloudStorageHealthStatus::class);

        // Mock the consolidated_status property directly
        $mockHealthStatus->consolidated_status = 'healthy';
        $mockHealthStatus->status = 'healthy';
        $mockHealthStatus->requires_reconnection = false;
        $mockHealthStatus->last_successful_operation_at = now();

        $mockHealthStatus->method('getConsolidatedStatusMessage')
            ->willReturn('Connection is healthy');

        $mockHealthService->method('checkConnectionHealth')
            ->willReturn($mockHealthStatus);

        $this->app->instance(\App\Services\CloudStorageHealthService::class, $mockHealthService);

        $response = $this->postJson(route('admin.cloud-storage.test'), [
            'provider' => 'google-drive'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'consolidated_status' => 'healthy',
                'requires_reconnection' => false,
            ]);
    }

    public function test_admin_can_disconnect_provider_via_storage_manager()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Auth::login($admin);

        // Mock the CloudStorageManager
        $mockManager = $this->createMock(CloudStorageManager::class);
        $mockProvider = $this->createMock(\App\Contracts\CloudStorageProviderInterface::class);

        $mockProvider->expects($this->once())
            ->method('disconnect')
            ->with($admin);
        $mockProvider->method('getProviderName')->willReturn('google-drive');

        $mockManager->method('getProvider')->willReturn($mockProvider);

        $this->app->instance(CloudStorageManager::class, $mockManager);

        $response = $this->post(route('admin.cloud-storage.google-drive.disconnect'));

        $response->assertRedirect(route('admin.cloud-storage.index'))
            ->assertSessionHas('success');
    }
}