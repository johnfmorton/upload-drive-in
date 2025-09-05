<?php

namespace Tests\Unit\Services;

use App\Jobs\RefreshTokenJob;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\ProactiveRefreshScheduler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProactiveRefreshSchedulerTest extends TestCase
{
    use RefreshDatabase;

    private ProactiveRefreshScheduler $scheduler;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->scheduler = new ProactiveRefreshScheduler();
    }

    public function test_schedules_immediate_refresh_for_tokens_expiring_very_soon(): void
    {
        $user = User::factory()->create();
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(10), // Within 15 minute buffer
            'requires_user_intervention' => false
        ]);

        $result = $this->scheduler->scheduleRefreshForToken($token);

        $this->assertTrue($result);
        
        // Should dispatch immediate refresh job on high priority queue
        Queue::assertPushed(RefreshTokenJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id && 
                   $job->provider === 'google-drive' &&
                   $job->queue === 'high' &&
                   $job->delay === null;
        });

        // Should mark as scheduled
        $token->refresh();
        $this->assertNotNull($token->proactive_refresh_scheduled_at);
    }

    public function test_schedules_delayed_refresh_for_tokens_expiring_later(): void
    {
        $user = User::factory()->create();
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addHours(2),
            'requires_user_intervention' => false
        ]);

        $result = $this->scheduler->scheduleRefreshForToken($token);

        $this->assertTrue($result);
        
        // Should dispatch delayed refresh job on maintenance queue
        Queue::assertPushed(RefreshTokenJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id && 
                   $job->provider === 'google-drive' &&
                   $job->queue === 'maintenance' &&
                   $job->delay !== null;
        });

        // Should mark as scheduled
        $token->refresh();
        $this->assertNotNull($token->proactive_refresh_scheduled_at);
    }

    public function test_does_not_schedule_refresh_for_token_without_expiration(): void
    {
        $user = User::factory()->create();
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => null
        ]);

        $result = $this->scheduler->scheduleRefreshForToken($token);

        $this->assertFalse($result);
        Queue::assertNotPushed(RefreshTokenJob::class);
    }

    public function test_does_not_schedule_refresh_for_token_requiring_user_intervention(): void
    {
        $user = User::factory()->create();
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addHours(1),
            'requires_user_intervention' => true
        ]);

        $result = $this->scheduler->scheduleRefreshForToken($token);

        $this->assertFalse($result);
        Queue::assertNotPushed(RefreshTokenJob::class);
    }

    public function test_does_not_schedule_refresh_too_far_in_future(): void
    {
        $user = User::factory()->create();
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addHours(30), // Beyond 24 hour limit
            'requires_user_intervention' => false
        ]);

        $result = $this->scheduler->scheduleRefreshForToken($token);

        $this->assertFalse($result);
        Queue::assertNotPushed(RefreshTokenJob::class);
    }

    public function test_schedules_refresh_for_user_with_token(): void
    {
        $user = User::factory()->create();
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addHours(1)
        ]);

        $result = $this->scheduler->scheduleRefreshForUser($user, 'google-drive');

        $this->assertTrue($result);
        Queue::assertPushed(RefreshTokenJob::class);
    }

    public function test_does_not_schedule_refresh_for_user_without_token(): void
    {
        $user = User::factory()->create();
        // No token created

        $result = $this->scheduler->scheduleRefreshForUser($user, 'google-drive');

        $this->assertFalse($result);
        Queue::assertNotPushed(RefreshTokenJob::class);
    }

    public function test_schedules_all_expiring_tokens(): void
    {
        // Create tokens expiring within 30 minutes
        $user1 = User::factory()->create();
        $token1 = GoogleDriveToken::factory()->create([
            'user_id' => $user1->id,
            'expires_at' => now()->addMinutes(20),
            'requires_user_intervention' => false,
            'proactive_refresh_scheduled_at' => null
        ]);

        $user2 = User::factory()->create();
        $token2 = GoogleDriveToken::factory()->create([
            'user_id' => $user2->id,
            'expires_at' => now()->addMinutes(25),
            'requires_user_intervention' => false,
            'proactive_refresh_scheduled_at' => null
        ]);

        // Create token that should be skipped (requires intervention)
        $user3 = User::factory()->create();
        $token3 = GoogleDriveToken::factory()->create([
            'user_id' => $user3->id,
            'expires_at' => now()->addMinutes(15),
            'requires_user_intervention' => true,
            'proactive_refresh_scheduled_at' => null
        ]);

        $results = $this->scheduler->scheduleAllExpiringTokens(30);

        $this->assertEquals(3, $results['total']);
        $this->assertEquals(2, $results['scheduled']);
        $this->assertEquals(0, $results['failed']);
        $this->assertEquals(1, $results['skipped']);

        // Should have scheduled 2 refresh jobs
        Queue::assertPushed(RefreshTokenJob::class, 2);
    }

    public function test_cancels_scheduled_refresh(): void
    {
        $user = User::factory()->create();
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'proactive_refresh_scheduled_at' => now()
        ]);

        $result = $this->scheduler->cancelScheduledRefresh($token);

        $this->assertTrue($result);
        
        $token->refresh();
        $this->assertNull($token->proactive_refresh_scheduled_at);
    }

    public function test_handles_scheduling_errors_gracefully(): void
    {
        $user = User::factory()->create();
        
        // Create a token with invalid expires_at to cause an error
        $token = new GoogleDriveToken();
        $token->user_id = $user->id;
        $token->expires_at = null; // This will cause an error in calculateRefreshTime
        $token->requires_user_intervention = false;
        $token->user = $user;

        $result = $this->scheduler->scheduleRefreshForToken($token);

        $this->assertFalse($result);
    }

    public function test_uses_correct_refresh_buffer_time(): void
    {
        // Set custom buffer time in config
        config(['cloud-storage.token_refresh_buffer_minutes' => 20]);

        $user = User::factory()->create();
        $expiresAt = now()->addMinutes(25);
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => $expiresAt,
            'requires_user_intervention' => false
        ]);

        $result = $this->scheduler->scheduleRefreshForToken($token);

        $this->assertTrue($result);
        
        // Should schedule for 20 minutes before expiration (5 minutes from now)
        Queue::assertPushed(RefreshTokenJob::class, function ($job) {
            // Job should be delayed (not immediate)
            return $job->delay !== null;
        });
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}