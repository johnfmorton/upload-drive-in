<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CleanupFailedRefreshAttemptsJob;
use App\Models\GoogleDriveToken;
use App\Models\CloudStorageHealthStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanupFailedRefreshAttemptsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleans_up_old_failed_refresh_records(): void
    {
        $user = User::factory()->create();
        
        // Create token with old failed refresh attempt
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'last_refresh_attempt_at' => now()->subDays(8),
            'refresh_failure_count' => 5
        ]);

        $job = new CleanupFailedRefreshAttemptsJob();
        $job->handle();

        // Should reset failure count and attempt timestamp
        $token->refresh();
        $this->assertEquals(0, $token->refresh_failure_count);
        $this->assertNull($token->last_refresh_attempt_at);
    }

    public function test_preserves_recent_failed_refresh_records(): void
    {
        $user = User::factory()->create();
        
        // Create token with recent failed refresh attempt
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'last_refresh_attempt_at' => now()->subDays(3),
            'refresh_failure_count' => 2
        ]);

        $job = new CleanupFailedRefreshAttemptsJob();
        $job->handle();

        // Should preserve recent failure data
        $token->refresh();
        $this->assertEquals(2, $token->refresh_failure_count);
        $this->assertNotNull($token->last_refresh_attempt_at);
    }

    public function test_resets_stale_proactive_refresh_flags(): void
    {
        $user = User::factory()->create();
        
        // Create token with stale proactive refresh flag
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'proactive_refresh_scheduled_at' => now()->subHours(3),
            'expires_at' => now()->addHours(1) // Still valid
        ]);

        $job = new CleanupFailedRefreshAttemptsJob();
        $job->handle();

        // Should reset stale proactive refresh flag
        $token->refresh();
        $this->assertNull($token->proactive_refresh_scheduled_at);
    }

    public function test_preserves_recent_proactive_refresh_flags(): void
    {
        $user = User::factory()->create();
        
        // Create token with recent proactive refresh flag
        $scheduledAt = now()->subMinutes(30);
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'proactive_refresh_scheduled_at' => $scheduledAt,
            'expires_at' => now()->addHours(1)
        ]);

        $job = new CleanupFailedRefreshAttemptsJob();
        $job->handle();

        // Should preserve recent proactive refresh flag
        $token->refresh();
        $this->assertEquals($scheduledAt->timestamp, $token->proactive_refresh_scheduled_at->timestamp);
    }

    public function test_does_not_reset_flags_for_expired_tokens(): void
    {
        $user = User::factory()->create();
        
        // Create expired token with stale proactive refresh flag
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'proactive_refresh_scheduled_at' => now()->subHours(3),
            'expires_at' => now()->subMinutes(30) // Already expired
        ]);

        $job = new CleanupFailedRefreshAttemptsJob();
        $job->handle();

        // Should not reset flag for expired tokens
        $token->refresh();
        $this->assertNotNull($token->proactive_refresh_scheduled_at);
    }

    public function test_cleans_up_old_notification_records(): void
    {
        $user = User::factory()->create();
        
        // Create token with old notification data
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'last_notification_sent_at' => now()->subDays(35),
            'notification_failure_count' => 3
        ]);

        $job = new CleanupFailedRefreshAttemptsJob();
        $job->handle();

        // Should reset old notification data
        $token->refresh();
        $this->assertNull($token->last_notification_sent_at);
        $this->assertEquals(0, $token->notification_failure_count);
    }

    public function test_preserves_recent_notification_records(): void
    {
        $user = User::factory()->create();
        
        // Create token with recent notification data
        $notificationDate = now()->subDays(15);
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'last_notification_sent_at' => $notificationDate,
            'notification_failure_count' => 1
        ]);

        $job = new CleanupFailedRefreshAttemptsJob();
        $job->handle();

        // Should preserve recent notification data
        $token->refresh();
        $this->assertEquals($notificationDate->timestamp, $token->last_notification_sent_at->timestamp);
        $this->assertEquals(1, $token->notification_failure_count);
    }

    public function test_cleans_up_orphaned_health_records(): void
    {
        // Create health record for non-existent user
        $healthRecord = CloudStorageHealthStatus::factory()->create([
            'user_id' => 99999 // Non-existent user ID
        ]);

        $job = new CleanupFailedRefreshAttemptsJob();
        $job->handle();

        // Should delete orphaned health record
        $this->assertDatabaseMissing('cloud_storage_health_statuses', [
            'id' => $healthRecord->id
        ]);
    }

    public function test_cleans_up_very_old_health_records_for_inactive_users(): void
    {
        $user = User::factory()->create();
        
        // Create very old health record for user with no recent activity
        $healthRecord = CloudStorageHealthStatus::factory()->create([
            'user_id' => $user->id,
            'updated_at' => now()->subDays(95)
        ]);

        $job = new CleanupFailedRefreshAttemptsJob();
        $job->handle();

        // Should delete very old health record for inactive user
        $this->assertDatabaseMissing('cloud_storage_health_statuses', [
            'id' => $healthRecord->id
        ]);
    }

    public function test_preserves_old_health_records_for_active_users(): void
    {
        $user = User::factory()->create();
        
        // Create very old health record
        $healthRecord = CloudStorageHealthStatus::factory()->create([
            'user_id' => $user->id,
            'updated_at' => now()->subDays(95)
        ]);

        // Create recent file upload to make user active
        \App\Models\FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'created_at' => now()->subDays(15)
        ]);

        $job = new CleanupFailedRefreshAttemptsJob();
        $job->handle();

        // Should preserve health record for active user
        $this->assertDatabaseHas('cloud_storage_health_statuses', [
            'id' => $healthRecord->id
        ]);
    }

    public function test_handles_multiple_cleanup_operations_in_single_run(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Create various records that need cleanup
        $oldFailedToken = GoogleDriveToken::factory()->create([
            'user_id' => $user1->id,
            'last_refresh_attempt_at' => now()->subDays(10),
            'refresh_failure_count' => 3
        ]);

        $staleScheduledToken = GoogleDriveToken::factory()->create([
            'user_id' => $user2->id,
            'proactive_refresh_scheduled_at' => now()->subHours(4),
            'expires_at' => now()->addHours(2)
        ]);

        $orphanedHealth = CloudStorageHealthStatus::factory()->create([
            'user_id' => 88888 // Non-existent user
        ]);

        $job = new CleanupFailedRefreshAttemptsJob();
        $job->handle();

        // All cleanup operations should have been performed
        $oldFailedToken->refresh();
        $this->assertEquals(0, $oldFailedToken->refresh_failure_count);

        $staleScheduledToken->refresh();
        $this->assertNull($staleScheduledToken->proactive_refresh_scheduled_at);

        $this->assertDatabaseMissing('cloud_storage_health_statuses', [
            'id' => $orphanedHealth->id
        ]);
    }
}