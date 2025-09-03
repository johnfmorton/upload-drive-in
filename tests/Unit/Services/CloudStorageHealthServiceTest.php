<?php

namespace Tests\Unit\Services;

use App\Enums\CloudStorageErrorType;
use App\Models\CloudStorageHealthStatus;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CloudStorageHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageHealthService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $logService = new \App\Services\CloudStorageLogService();
        $storageManager = $this->createMock(\App\Services\CloudStorageManager::class);
        $this->service = new CloudStorageHealthService($logService, $storageManager);
        $this->user = User::factory()->create();
    }

    public function test_check_connection_health_creates_health_status_if_not_exists(): void
    {
        $this->assertDatabaseMissing('cloud_storage_health_statuses', [
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
        ]);

        $healthStatus = $this->service->checkConnectionHealth($this->user, 'google-drive');

        $this->assertDatabaseHas('cloud_storage_health_statuses', [
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
        ]);

        $this->assertInstanceOf(CloudStorageHealthStatus::class, $healthStatus);
    }

    public function test_check_connection_health_with_valid_token(): void
    {
        // Create a valid Google Drive token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'token_type' => 'access',
        ]);

        Log::shouldReceive('info')->once();

        $healthStatus = $this->service->checkConnectionHealth($this->user, 'google-drive');

        $this->assertEquals('healthy', $healthStatus->status);
        $this->assertEquals(0, $healthStatus->consecutive_failures);
        $this->assertNotNull($healthStatus->last_successful_operation_at);
    }

    public function test_check_connection_health_without_token(): void
    {
        Log::shouldReceive('warning')->once();

        $healthStatus = $this->service->checkConnectionHealth($this->user, 'google-drive');

        $this->assertContains($healthStatus->status, ['healthy', 'degraded', 'unhealthy']);
        $this->assertGreaterThanOrEqual(0, $healthStatus->consecutive_failures);
    }

    public function test_mark_connection_as_unhealthy(): void
    {
        Log::shouldReceive('warning')->once();

        $this->service->markConnectionAsUnhealthy(
            $this->user,
            'google-drive',
            'Test error message',
            CloudStorageErrorType::TOKEN_EXPIRED
        );

        $this->assertDatabaseHas('cloud_storage_health_statuses', [
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy', // First failure should still be healthy
            'consecutive_failures' => 1,
            'last_error_message' => 'Test error message',
            'last_error_type' => 'token_expired',
            'requires_reconnection' => true,
        ]);
    }

    public function test_mark_connection_as_unhealthy_with_multiple_failures(): void
    {
        Log::shouldReceive('warning')->times(5);

        // First failure - should be healthy
        $this->service->markConnectionAsUnhealthy($this->user, 'google-drive', 'Error 1');
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)->first();
        $this->assertEquals('healthy', $healthStatus->status);

        // Second failure - should be degraded
        $this->service->markConnectionAsUnhealthy($this->user, 'google-drive', 'Error 2');
        $healthStatus->refresh();
        $this->assertEquals('degraded', $healthStatus->status);

        // Fifth failure - should be unhealthy
        $this->service->markConnectionAsUnhealthy($this->user, 'google-drive', 'Error 3');
        $this->service->markConnectionAsUnhealthy($this->user, 'google-drive', 'Error 4');
        $this->service->markConnectionAsUnhealthy($this->user, 'google-drive', 'Error 5');
        $healthStatus->refresh();
        $this->assertEquals('unhealthy', $healthStatus->status);
        $this->assertEquals(5, $healthStatus->consecutive_failures);
    }

    public function test_record_successful_operation(): void
    {
        Log::shouldReceive('info')->once();

        // First create an unhealthy status
        $healthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consecutive_failures' => 3,
            'last_error_message' => 'Previous error',
            'requires_reconnection' => true,
        ]);

        $providerData = ['folder_id' => 'test123'];
        $this->service->recordSuccessfulOperation($this->user, 'google-drive', $providerData);

        $healthStatus->refresh();
        $this->assertEquals('healthy', $healthStatus->status);
        $this->assertEquals(0, $healthStatus->consecutive_failures);
        $this->assertNull($healthStatus->last_error_message);
        $this->assertNull($healthStatus->last_error_type);
        $this->assertFalse($healthStatus->requires_reconnection);
        $this->assertNotNull($healthStatus->last_successful_operation_at);
        $this->assertEquals($providerData, $healthStatus->provider_specific_data);
    }

    public function test_get_health_summary(): void
    {
        $healthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'degraded',
            'consecutive_failures' => 2,
            'last_error_type' => 'network_error',
            'last_error_message' => 'Connection timeout',
            'last_successful_operation_at' => now()->subHour(),
            'token_expires_at' => now()->addHours(12),
            'requires_reconnection' => false,
            'provider_specific_data' => ['folder_id' => 'test123'],
        ]);

        $summary = $this->service->getHealthSummary($this->user, 'google-drive');

        $this->assertEquals('google-drive', $summary['provider']);
        $this->assertEquals('degraded', $summary['status']);
        $this->assertEquals('Connection has some issues but is functional', $summary['status_message']);
        $this->assertFalse($summary['is_healthy']);
        $this->assertTrue($summary['is_degraded']);
        $this->assertFalse($summary['is_unhealthy']);
        $this->assertFalse($summary['is_disconnected']);
        $this->assertStringContainsString('ago', $summary['last_successful_operation']);
        $this->assertEquals(2, $summary['consecutive_failures']);
        $this->assertFalse($summary['requires_reconnection']);
        $this->assertTrue($summary['token_expiring_soon']);
        $this->assertFalse($summary['token_expired']);
        $this->assertEquals('network_error', $summary['last_error_type']);
        $this->assertEquals('Connection timeout', $summary['last_error_message']);
        $this->assertEquals(['folder_id' => 'test123'], $summary['provider_specific_data']);
    }

    public function test_get_all_providers_health(): void
    {
        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
        ]);

        $allHealth = $this->service->getAllProvidersHealth($this->user);

        $this->assertCount(1, $allHealth); // Only google-drive is supported currently
        $this->assertEquals('google-drive', $allHealth->first()['provider']);
        $this->assertEquals('healthy', $allHealth->first()['status']);
    }

    public function test_update_token_expiration(): void
    {
        Log::shouldReceive('info')->once();

        $expiresAt = now()->addDays(7);
        $this->service->updateTokenExpiration($this->user, 'google-drive', $expiresAt);

        $this->assertDatabaseHas('cloud_storage_health_statuses', [
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'token_expires_at' => $expiresAt,
        ]);
    }

    public function test_get_users_with_expiring_tokens(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // User 1: Token expires in 12 hours (should be included)
        CloudStorageHealthStatus::create([
            'user_id' => $user1->id,
            'provider' => 'google-drive',
            'token_expires_at' => now()->addHours(12),
        ]);

        // User 2: Token expires in 48 hours (should not be included)
        CloudStorageHealthStatus::create([
            'user_id' => $user2->id,
            'provider' => 'google-drive',
            'token_expires_at' => now()->addHours(48),
        ]);

        // User 3: Token already expired (should not be included)
        CloudStorageHealthStatus::create([
            'user_id' => $user3->id,
            'provider' => 'google-drive',
            'token_expires_at' => now()->subHour(),
        ]);

        $expiringUsers = $this->service->getUsersWithExpiringTokens('google-drive', 24);

        $this->assertCount(1, $expiringUsers);
        $this->assertEquals($user1->id, $expiringUsers->first()->user_id);
    }

    public function test_get_users_with_unhealthy_connections(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        CloudStorageHealthStatus::create([
            'user_id' => $user1->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
        ]);

        CloudStorageHealthStatus::create([
            'user_id' => $user2->id,
            'provider' => 'google-drive',
            'status' => 'degraded',
        ]);

        CloudStorageHealthStatus::create([
            'user_id' => $user3->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
        ]);

        $unhealthyUsers = $this->service->getUsersWithUnhealthyConnections('google-drive');

        $this->assertCount(2, $unhealthyUsers);
        $userIds = $unhealthyUsers->pluck('user_id')->toArray();
        $this->assertContains($user1->id, $userIds);
        $this->assertContains($user2->id, $userIds);
        $this->assertNotContains($user3->id, $userIds);
    }

    public function test_cleanup_old_health_records(): void
    {
        $oldDate = now()->subDays(100);

        // Create old disconnected record (should be deleted)
        $oldRecord = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'disconnected',
        ]);
        // Force update the timestamp using raw SQL to bypass Laravel's timestamp handling
        \DB::table('cloud_storage_health_statuses')
            ->where('id', $oldRecord->id)
            ->update(['updated_at' => $oldDate]);

        // Create old healthy record (should not be deleted)
        $user2 = User::factory()->create();
        $healthyRecord = CloudStorageHealthStatus::factory()->create([
            'user_id' => $user2->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
        ]);
        \DB::table('cloud_storage_health_statuses')
            ->where('id', $healthyRecord->id)
            ->update(['updated_at' => $oldDate]);

        // Create recent disconnected record (should not be deleted)
        $user3 = User::factory()->create();
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $user3->id,
            'provider' => 'google-drive',
            'status' => 'disconnected',
        ]);

        $deletedCount = $this->service->cleanupOldHealthRecords(90);

        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseMissing('cloud_storage_health_statuses', [
            'user_id' => $this->user->id,
        ]);
        $this->assertDatabaseHas('cloud_storage_health_statuses', [
            'user_id' => $user2->id,
        ]);
        $this->assertDatabaseHas('cloud_storage_health_statuses', [
            'user_id' => $user3->id,
        ]);
    }

    public function test_should_require_reconnection_logic(): void
    {
        Log::shouldReceive('warning')->times(3);

        // Token expired should require reconnection
        $this->service->markConnectionAsUnhealthy(
            $this->user,
            'google-drive',
            'Token expired',
            CloudStorageErrorType::TOKEN_EXPIRED
        );

        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)->first();
        $this->assertTrue($healthStatus->requires_reconnection);

        // Insufficient permissions should require reconnection
        $user2 = User::factory()->create();
        $this->service->markConnectionAsUnhealthy(
            $user2,
            'google-drive',
            'Insufficient permissions',
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS
        );

        $healthStatus2 = CloudStorageHealthStatus::where('user_id', $user2->id)->first();
        $this->assertTrue($healthStatus2->requires_reconnection);

        // Network error should not require reconnection
        $user3 = User::factory()->create();
        $this->service->markConnectionAsUnhealthy(
            $user3,
            'google-drive',
            'Network error',
            CloudStorageErrorType::NETWORK_ERROR
        );

        $healthStatus3 = CloudStorageHealthStatus::where('user_id', $user3->id)->first();
        $this->assertFalse($healthStatus3->requires_reconnection);
    }
}