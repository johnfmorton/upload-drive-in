<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageManager;
use App\Services\CloudStorageLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloudStorageHealthServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_service_uses_storage_manager_for_provider_operations()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock the CloudStorageManager and provider
        $mockManager = $this->createMock(CloudStorageManager::class);
        $mockProvider = $this->createMock(\App\Contracts\CloudStorageProviderInterface::class);
        $mockLogService = $this->createMock(CloudStorageLogService::class);

        $mockProvider->method('getProviderName')->willReturn('google-drive');
        $mockProvider->method('hasValidConnection')->willReturn(true);

        $mockManager->method('getProvider')->willReturn($mockProvider);
        $mockManager->method('getAvailableProviders')->willReturn(['google-drive', 'amazon-s3']);

        // Create health service with mocked dependencies
        $healthService = new CloudStorageHealthService($mockLogService, $mockManager);

        // Test that the service uses the storage manager
        $healthSummary = $healthService->getHealthSummary($user, 'google-drive');

        $this->assertIsArray($healthSummary);
        $this->assertEquals('google-drive', $healthSummary['provider']);
        $this->assertTrue($healthSummary['is_healthy']);
    }

    public function test_health_service_gets_all_providers_from_storage_manager()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock the CloudStorageManager
        $mockManager = $this->createMock(CloudStorageManager::class);
        $mockProvider1 = $this->createMock(\App\Contracts\CloudStorageProviderInterface::class);
        $mockProvider2 = $this->createMock(\App\Contracts\CloudStorageProviderInterface::class);
        $mockLogService = $this->createMock(CloudStorageLogService::class);

        $mockProvider1->method('getProviderName')->willReturn('google-drive');
        $mockProvider1->method('hasValidConnection')->willReturn(true);
        
        $mockProvider2->method('getProviderName')->willReturn('amazon-s3');
        $mockProvider2->method('hasValidConnection')->willReturn(false);

        $mockManager->method('getAvailableProviders')->willReturn(['google-drive', 'amazon-s3']);
        $mockManager->method('getProvider')
            ->willReturnCallback(function($providerName) use ($mockProvider1, $mockProvider2) {
                return $providerName === 'google-drive' ? $mockProvider1 : $mockProvider2;
            });

        // Create health service with mocked dependencies
        $healthService = new CloudStorageHealthService($mockLogService, $mockManager);

        // Test getting all providers health
        $allProvidersHealth = $healthService->getAllProvidersHealth($user);

        $this->assertCount(2, $allProvidersHealth);
        
        $googleDriveHealth = $allProvidersHealth->firstWhere('provider', 'google-drive');
        $this->assertNotNull($googleDriveHealth);
        $this->assertTrue($googleDriveHealth['is_healthy']);
        
        $s3Health = $allProvidersHealth->firstWhere('provider', 'amazon-s3');
        $this->assertNotNull($s3Health);
        $this->assertFalse($s3Health['is_healthy']);
    }

    public function test_health_service_handles_provider_not_available()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock the CloudStorageManager to throw exception
        $mockManager = $this->createMock(CloudStorageManager::class);
        $mockLogService = $this->createMock(CloudStorageLogService::class);

        $mockManager->method('getProvider')
            ->willThrowException(new \Exception('Provider not available'));
        $mockManager->method('getAvailableProviders')->willReturn(['google-drive']);

        // Create health service with mocked dependencies
        $healthService = new CloudStorageHealthService($mockLogService, $mockManager);

        // Test that it handles the exception gracefully
        $healthSummary = $healthService->getHealthSummary($user, 'google-drive');

        $this->assertIsArray($healthSummary);
        $this->assertEquals('google-drive', $healthSummary['provider']);
        $this->assertFalse($healthSummary['is_healthy']);
    }

    public function test_health_service_determines_consolidated_status_with_storage_manager()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Mock the CloudStorageManager and provider
        $mockManager = $this->createMock(CloudStorageManager::class);
        $mockProvider = $this->createMock(\App\Contracts\CloudStorageProviderInterface::class);
        $mockLogService = $this->createMock(CloudStorageLogService::class);

        $mockProvider->method('getProviderName')->willReturn('google-drive');
        $mockProvider->method('hasValidConnection')->willReturn(true);

        $mockManager->method('getProvider')->willReturn($mockProvider);

        // Create health service with mocked dependencies
        $healthService = new CloudStorageHealthService($mockLogService, $mockManager);

        // Test consolidated status determination
        $consolidatedStatus = $healthService->determineConsolidatedStatus($user, 'google-drive');

        $this->assertEquals('healthy', $consolidatedStatus);
    }
}