<?php

namespace Tests\Unit;

use App\Models\GoogleDriveToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GoogleDriveTokenTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_is_expiring_soon_returns_true_when_token_expires_within_default_minutes(): void
    {
        // Token expires in 10 minutes (less than default 15 minutes)
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $this->assertTrue($token->isExpiringSoon());
    }

    public function test_is_expiring_soon_returns_false_when_token_expires_after_default_minutes(): void
    {
        // Token expires in 20 minutes (more than default 15 minutes)
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => Carbon::now()->addMinutes(20),
        ]);

        $this->assertFalse($token->isExpiringSoon());
    }

    public function test_is_expiring_soon_with_custom_minutes(): void
    {
        // Token expires in 25 minutes
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => Carbon::now()->addMinutes(25),
        ]);

        // Should return true when checking for 30 minutes
        $this->assertTrue($token->isExpiringSoon(30));
        
        // Should return false when checking for 20 minutes
        $this->assertFalse($token->isExpiringSoon(20));
    }

    public function test_is_expiring_soon_returns_false_when_no_expiration_date(): void
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => null,
        ]);

        $this->assertFalse($token->isExpiringSoon());
    }

    public function test_can_be_refreshed_returns_true_when_conditions_met(): void
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_token' => 'valid_refresh_token',
            'requires_user_intervention' => false,
            'refresh_failure_count' => 2,
        ]);

        $this->assertTrue($token->canBeRefreshed());
    }

    public function test_can_be_refreshed_returns_false_when_no_refresh_token(): void
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_token' => null,
            'requires_user_intervention' => false,
            'refresh_failure_count' => 0,
        ]);

        $this->assertFalse($token->canBeRefreshed());
    }

    public function test_can_be_refreshed_returns_false_when_user_intervention_required(): void
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_token' => 'valid_refresh_token',
            'requires_user_intervention' => true,
            'refresh_failure_count' => 0,
        ]);

        $this->assertFalse($token->canBeRefreshed());
    }

    public function test_can_be_refreshed_returns_false_when_too_many_failures(): void
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_token' => 'valid_refresh_token',
            'requires_user_intervention' => false,
            'refresh_failure_count' => 5,
        ]);

        $this->assertFalse($token->canBeRefreshed());
    }

    public function test_should_schedule_proactive_refresh_returns_true_when_conditions_met(): void
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => Carbon::now()->addMinutes(25), // Expires within 30 minutes
            'refresh_token' => 'valid_refresh_token',
            'proactive_refresh_scheduled_at' => null,
            'requires_user_intervention' => false,
            'refresh_failure_count' => 0,
        ]);

        $this->assertTrue($token->shouldScheduleProactiveRefresh());
    }

    public function test_should_schedule_proactive_refresh_returns_false_when_not_expiring_soon(): void
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => Carbon::now()->addMinutes(45), // Expires after 30 minutes
            'refresh_token' => 'valid_refresh_token',
            'proactive_refresh_scheduled_at' => null,
            'requires_user_intervention' => false,
            'refresh_failure_count' => 0,
        ]);

        $this->assertFalse($token->shouldScheduleProactiveRefresh());
    }

    public function test_should_schedule_proactive_refresh_returns_false_when_already_scheduled(): void
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => Carbon::now()->addMinutes(25),
            'refresh_token' => 'valid_refresh_token',
            'proactive_refresh_scheduled_at' => Carbon::now()->addMinutes(10),
            'requires_user_intervention' => false,
            'refresh_failure_count' => 0,
        ]);

        $this->assertFalse($token->shouldScheduleProactiveRefresh());
    }

    public function test_should_schedule_proactive_refresh_returns_false_when_cannot_be_refreshed(): void
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => Carbon::now()->addMinutes(25),
            'refresh_token' => null, // No refresh token
            'proactive_refresh_scheduled_at' => null,
            'requires_user_intervention' => false,
            'refresh_failure_count' => 0,
        ]);

        $this->assertFalse($token->shouldScheduleProactiveRefresh());
    }

    public function test_mark_refresh_failure_increments_failure_count(): void
    {
        Log::shouldReceive('warning')->once();

        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_failure_count' => 2,
            'requires_user_intervention' => false,
        ]);

        $exception = new \Exception('Test error');
        $token->markRefreshFailure($exception);

        $this->assertEquals(3, $token->fresh()->refresh_failure_count);
        $this->assertNotNull($token->fresh()->last_refresh_attempt_at);
        $this->assertFalse($token->fresh()->requires_user_intervention);
    }

    public function test_mark_refresh_failure_sets_user_intervention_after_max_failures(): void
    {
        Log::shouldReceive('warning')->once();

        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_failure_count' => 4, // One less than max
            'requires_user_intervention' => false,
        ]);

        $exception = new \Exception('Test error');
        $token->markRefreshFailure($exception);

        $this->assertEquals(5, $token->fresh()->refresh_failure_count);
        $this->assertTrue($token->fresh()->requires_user_intervention);
    }

    public function test_mark_refresh_success_resets_failure_tracking(): void
    {
        Log::shouldReceive('info')->once();

        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_failure_count' => 3,
            'requires_user_intervention' => true,
            'proactive_refresh_scheduled_at' => Carbon::now()->addMinutes(10),
        ]);

        $token->markRefreshSuccess();

        $refreshedToken = $token->fresh();
        $this->assertEquals(0, $refreshedToken->refresh_failure_count);
        $this->assertFalse($refreshedToken->requires_user_intervention);
        $this->assertNull($refreshedToken->proactive_refresh_scheduled_at);
        $this->assertNotNull($refreshedToken->last_refresh_attempt_at);
        $this->assertNotNull($refreshedToken->last_successful_refresh_at);
    }

    public function test_fillable_fields_include_new_tracking_fields(): void
    {
        $token = new GoogleDriveToken();
        $fillable = $token->getFillable();

        $expectedNewFields = [
            'last_refresh_attempt_at',
            'refresh_failure_count',
            'last_successful_refresh_at',
            'proactive_refresh_scheduled_at',
            'health_check_failures',
            'requires_user_intervention',
            'last_notification_sent_at',
            'notification_failure_count',
        ];

        foreach ($expectedNewFields as $field) {
            $this->assertContains($field, $fillable, "Field {$field} should be fillable");
        }
    }

    public function test_casts_include_new_tracking_fields(): void
    {
        $token = new GoogleDriveToken();
        $casts = $token->getCasts();

        $expectedCasts = [
            'last_refresh_attempt_at' => 'datetime',
            'last_successful_refresh_at' => 'datetime',
            'proactive_refresh_scheduled_at' => 'datetime',
            'last_notification_sent_at' => 'datetime',
            'requires_user_intervention' => 'boolean',
            'refresh_failure_count' => 'integer',
            'health_check_failures' => 'integer',
            'notification_failure_count' => 'integer',
        ];

        foreach ($expectedCasts as $field => $expectedCast) {
            $this->assertEquals($expectedCast, $casts[$field], "Field {$field} should be cast to {$expectedCast}");
        }
    }

    public function test_new_fields_can_be_mass_assigned(): void
    {
        $now = Carbon::now();
        
        $token = GoogleDriveToken::create([
            'user_id' => $this->user->id,
            'access_token' => 'test_token',
            'refresh_token' => 'test_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => $now->addHour(),
            'scopes' => ['drive.file'],
            'last_refresh_attempt_at' => $now,
            'refresh_failure_count' => 2,
            'last_successful_refresh_at' => $now->subMinutes(30),
            'proactive_refresh_scheduled_at' => $now->addMinutes(15),
            'health_check_failures' => 1,
            'requires_user_intervention' => false,
            'last_notification_sent_at' => $now->subHour(),
            'notification_failure_count' => 0,
        ]);

        $this->assertInstanceOf(GoogleDriveToken::class, $token);
        $this->assertEquals(2, $token->refresh_failure_count);
        $this->assertEquals(1, $token->health_check_failures);
        $this->assertFalse($token->requires_user_intervention);
        $this->assertEquals(0, $token->notification_failure_count);
    }
}
