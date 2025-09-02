<?php

namespace Tests\Unit;

use App\Enums\CloudStorageErrorType;
use App\Models\CloudStorageHealthStatus;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CloudStorageHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageHealthService $healthService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $logService = new \App\Services\CloudStorageLogService();
        $this->healthService = new CloudStorageHealthService($logService);
        $this->user = User::factory()->create();
        
        // Fake notifications to prevent actual sending during tests
        Notification::fake();
    }

    public function test_can_create_health_status_for_new_user(): void
    {
        $healthStatus = $this->healthService->getOrCreateHealthStatus($this->user, 'google-drive');
        
        $this->assertInstanceOf(CloudStorageHealthStatus::class, $healthStatus);
        $this->assertEquals($this->user->id, $healthStatus->user_id);
        $this->assertEquals('google-drive', $healthStatus->provider);
        $this->assertEquals('disconnected', $healthStatus->status);
        $this->assertEquals(0, $healthStatus->consecutive_failures);
        $this->assertFalse($healthStatus->requires_reconnection);
    }

    public function test_can_record_successful_operation(): void
    {
        $providerData = [
            'last_upload_file_id' => 'test-file-id',
            'last_upload_at' => now()->toISOString(),
        ];
        
        $this->healthService->recordSuccessfulOperation($this->user, 'google-drive', $providerData);
        
        $healthStatus = $this->healthService->getOrCreateHealthStatus($this->user, 'google-drive');
        
        $this->assertEquals('healthy', $healthStatus->status);
        $this->assertEquals(0, $healthStatus->consecutive_failures);
        $this->assertFalse($healthStatus->requires_reconnection);
        $this->assertNotNull($healthStatus->last_successful_operation_at);
        $this->assertEquals($providerData, $healthStatus->provider_specific_data);
    }

    public function test_can_mark_connection_as_unhealthy(): void
    {
        $reason = 'Token expired';
        $errorType = CloudStorageErrorType::TOKEN_EXPIRED;
        
        $this->healthService->markConnectionAsUnhealthy($this->user, 'google-drive', $reason, $errorType);
        
        $healthStatus = $this->healthService->getOrCreateHealthStatus($this->user, 'google-drive');
        
        $this->assertEquals('healthy', $healthStatus->status); // First failure = healthy
        $this->assertEquals(1, $healthStatus->consecutive_failures);
        $this->assertEquals($reason, $healthStatus->last_error_message);
        $this->assertEquals($errorType->value, $healthStatus->last_error_type);
        $this->assertTrue($healthStatus->requires_reconnection);
    }

    public function test_consecutive_failures_determine_status(): void
    {
        // First failure - should be healthy
        $this->healthService->markConnectionAsUnhealthy($this->user, 'google-drive', 'Error 1');
        $healthStatus = $this->healthService->getOrCreateHealthStatus($this->user, 'google-drive');
        $this->assertEquals('healthy', $healthStatus->status);
        
        // Second failure - should be degraded
        $this->healthService->markConnectionAsUnhealthy($this->user, 'google-drive', 'Error 2');
        $healthStatus = $healthStatus->fresh();
        $this->assertEquals('degraded', $healthStatus->status);
        
        // Add more failures to reach unhealthy
        $this->healthService->markConnectionAsUnhealthy($this->user, 'google-drive', 'Error 3');
        $this->healthService->markConnectionAsUnhealthy($this->user, 'google-drive', 'Error 4');
        $this->healthService->markConnectionAsUnhealthy($this->user, 'google-drive', 'Error 5');
        $healthStatus = $healthStatus->fresh();
        $this->assertEquals('unhealthy', $healthStatus->status);
        $this->assertEquals(5, $healthStatus->consecutive_failures);
    }

    public function test_can_update_token_expiration(): void
    {
        $expiresAt = Carbon::now()->addHours(2);
        
        $this->healthService->updateTokenExpiration($this->user, 'google-drive', $expiresAt);
        
        $healthStatus = $this->healthService->getOrCreateHealthStatus($this->user, 'google-drive');
        $this->assertEquals($expiresAt->toDateTimeString(), $healthStatus->token_expires_at->toDateTimeString());
    }

    public function test_can_get_users_with_expiring_tokens(): void
    {
        // Create users with different token expiration times
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        
        // User 1: Token expires in 2 hours (should be included)
        $this->healthService->updateTokenExpiration($user1, 'google-drive', Carbon::now()->addHours(2));
        
        // User 2: Token expires in 48 hours (should not be included)
        $this->healthService->updateTokenExpiration($user2, 'google-drive', Carbon::now()->addHours(48));
        
        // User 3: Token already expired (should not be included)
        $this->healthService->updateTokenExpiration($user3, 'google-drive', Carbon::now()->subHours(1));
        
        $usersWithExpiringTokens = $this->healthService->getUsersWithExpiringTokens('google-drive', 24);
        
        $this->assertCount(1, $usersWithExpiringTokens);
        $this->assertEquals($user1->id, $usersWithExpiringTokens->first()->user_id);
    }

    public function test_can_get_users_with_unhealthy_connections(): void
    {
        // Create users with different health statuses
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        
        // User 1: Unhealthy connection
        $this->healthService->markConnectionAsUnhealthy($user1, 'google-drive', 'Multiple failures');
        $this->healthService->markConnectionAsUnhealthy($user1, 'google-drive', 'Multiple failures');
        $this->healthService->markConnectionAsUnhealthy($user1, 'google-drive', 'Multiple failures');
        $this->healthService->markConnectionAsUnhealthy($user1, 'google-drive', 'Multiple failures');
        $this->healthService->markConnectionAsUnhealthy($user1, 'google-drive', 'Multiple failures');
        
        // User 2: Degraded connection
        $this->healthService->markConnectionAsUnhealthy($user2, 'google-drive', 'Some failures');
        $this->healthService->markConnectionAsUnhealthy($user2, 'google-drive', 'Some failures');
        
        // User 3: Healthy connection
        $this->healthService->recordSuccessfulOperation($user3, 'google-drive');
        
        $usersWithUnhealthyConnections = $this->healthService->getUsersWithUnhealthyConnections('google-drive');
        
        $this->assertCount(2, $usersWithUnhealthyConnections); // Both unhealthy and degraded
        $userIds = $usersWithUnhealthyConnections->pluck('user_id')->toArray();
        $this->assertContains($user1->id, $userIds);
        $this->assertContains($user2->id, $userIds);
        $this->assertNotContains($user3->id, $userIds);
    }

    public function test_can_get_health_summary(): void
    {
        // Set up a health status with various data
        $this->healthService->recordSuccessfulOperation($this->user, 'google-drive', [
            'last_upload_file_id' => 'test-file-id',
        ]);
        
        $expiresAt = Carbon::now()->addHours(2);
        $this->healthService->updateTokenExpiration($this->user, 'google-drive', $expiresAt);
        
        $summary = $this->healthService->getHealthSummary($this->user, 'google-drive');
        
        $this->assertEquals('google-drive', $summary['provider']);
        $this->assertEquals('healthy', $summary['status']);
        $this->assertTrue($summary['is_healthy']);
        $this->assertFalse($summary['is_degraded']);
        $this->assertFalse($summary['is_unhealthy']);
        $this->assertFalse($summary['is_disconnected']);
        $this->assertEquals(0, $summary['consecutive_failures']);
        $this->assertFalse($summary['requires_reconnection']);
        $this->assertTrue($summary['token_expiring_soon']);
        $this->assertFalse($summary['token_expired']);
        $this->assertArrayHasKey('last_upload_file_id', $summary['provider_specific_data']);
    }

    public function test_can_cleanup_old_health_records(): void
    {
        // Create old disconnected health status
        $oldHealthStatus = CloudStorageHealthStatus::factory()
            ->disconnected()
            ->create([
                'user_id' => $this->user->id,
                'provider' => 'google-drive',
            ]);
        
        // Manually update timestamps to make it old
        $oldHealthStatus->timestamps = false;
        $oldHealthStatus->created_at = Carbon::now()->subDays(100);
        $oldHealthStatus->updated_at = Carbon::now()->subDays(100);
        $oldHealthStatus->save();
        
        // Create recent health status
        $recentHealthStatus = CloudStorageHealthStatus::factory()
            ->disconnected()
            ->create([
                'user_id' => User::factory()->create()->id,
                'provider' => 'google-drive',
                'created_at' => Carbon::now()->subDays(10),
                'updated_at' => Carbon::now()->subDays(10),
            ]);
        
        $deletedCount = $this->healthService->cleanupOldHealthRecords(90);
        
        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseMissing('cloud_storage_health_statuses', ['id' => $oldHealthStatus->id]);
        $this->assertDatabaseHas('cloud_storage_health_statuses', ['id' => $recentHealthStatus->id]);
    }
}