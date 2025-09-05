<?php

namespace Tests\Unit\Jobs;

use App\Jobs\TokenMaintenanceJob;
use App\Jobs\RefreshTokenJob;
use App\Models\GoogleDriveToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TokenMaintenanceJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_refreshes_tokens_expiring_within_30_minutes(): void
    {
        // Create user with token expiring in 20 minutes
        $user = User::factory()->create();
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(20),
            'requires_user_intervention' => false
        ]);

        $job = new TokenMaintenanceJob();
        $job->handle();

        // Should dispatch refresh job for expiring token
        Queue::assertPushed(RefreshTokenJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id && $job->provider === 'google-drive';
        });
    }

    public function test_does_not_refresh_tokens_requiring_user_intervention(): void
    {
        // Create user with token requiring intervention
        $user = User::factory()->create();
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(20),
            'requires_user_intervention' => true
        ]);

        $job = new TokenMaintenanceJob();
        $job->handle();

        // Should not dispatch refresh job
        Queue::assertNotPushed(RefreshTokenJob::class);
    }

    public function test_does_not_refresh_tokens_expiring_too_far_in_future(): void
    {
        // Create user with token expiring in 30 hours (beyond 24 hour scheduling limit)
        $user = User::factory()->create();
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addHours(30),
            'requires_user_intervention' => false
        ]);

        $job = new TokenMaintenanceJob();
        $job->handle();

        // Should not dispatch refresh job for tokens expiring too far in future
        Queue::assertNotPushed(RefreshTokenJob::class);
    }

    public function test_cleans_up_old_failed_refresh_attempts(): void
    {
        // Create token with old failed refresh attempt
        $user = User::factory()->create();
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'last_refresh_attempt_at' => now()->subDays(8),
            'refresh_failure_count' => 5
        ]);

        $job = new TokenMaintenanceJob();
        $job->handle();

        // Should reset failure count and attempt timestamp
        $token->refresh();
        $this->assertEquals(0, $token->refresh_failure_count);
        $this->assertNull($token->last_refresh_attempt_at);
    }

    public function test_schedules_proactive_refreshes_for_tokens_expiring_in_1_to_24_hours(): void
    {
        // Create user with token expiring in 2 hours
        $user = User::factory()->create();
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addHours(2),
            'proactive_refresh_scheduled_at' => null,
            'requires_user_intervention' => false
        ]);

        $job = new TokenMaintenanceJob();
        $job->handle();

        // Should schedule proactive refresh and mark as scheduled
        Queue::assertPushed(RefreshTokenJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id && 
                   $job->provider === 'google-drive' &&
                   $job->delay !== null;
        });

        $token->refresh();
        $this->assertNotNull($token->proactive_refresh_scheduled_at);
    }

    public function test_does_not_schedule_proactive_refresh_if_already_scheduled(): void
    {
        // Create user with token that already has proactive refresh scheduled
        $user = User::factory()->create();
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addHours(2),
            'proactive_refresh_scheduled_at' => now()->subMinutes(10),
            'requires_user_intervention' => false
        ]);

        $job = new TokenMaintenanceJob();
        $job->handle();

        // Should not schedule another proactive refresh
        Queue::assertNotPushed(RefreshTokenJob::class);
    }

    public function test_handles_tokens_without_expiration_gracefully(): void
    {
        // Create user with token without expiration date
        $user = User::factory()->create();
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => null
        ]);

        $job = new TokenMaintenanceJob();
        
        // Should not throw exception
        $this->expectNotToPerformAssertions();
        $job->handle();
    }

    public function test_uses_correct_queue_priorities(): void
    {
        // Create user with token expiring very soon (immediate refresh)
        $user1 = User::factory()->create();
        $token1 = GoogleDriveToken::factory()->create([
            'user_id' => $user1->id,
            'expires_at' => now()->addMinutes(5),
            'requires_user_intervention' => false
        ]);

        // Create user with token for proactive refresh
        $user2 = User::factory()->create();
        $token2 = GoogleDriveToken::factory()->create([
            'user_id' => $user2->id,
            'expires_at' => now()->addHours(2),
            'proactive_refresh_scheduled_at' => null,
            'requires_user_intervention' => false
        ]);

        $job = new TokenMaintenanceJob();
        $job->handle();

        // Immediate refresh should use high priority queue
        Queue::assertPushed(RefreshTokenJob::class, function ($job) use ($user1) {
            return $job->user->id === $user1->id && 
                   $job->queue === 'high';
        });

        // Proactive refresh should use maintenance queue
        Queue::assertPushed(RefreshTokenJob::class, function ($job) use ($user2) {
            return $job->user->id === $user2->id && 
                   $job->queue === 'maintenance';
        });
    }
}