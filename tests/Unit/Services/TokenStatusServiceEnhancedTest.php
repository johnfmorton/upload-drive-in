<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TokenStatusService;
use App\Services\ProactiveRefreshScheduler;
use App\Models\User;
use App\Models\GoogleDriveToken;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TokenStatusServiceEnhancedTest extends TestCase
{
    use RefreshDatabase;

    private TokenStatusService $tokenStatusService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tokenStatusService = new TokenStatusService(
            app(ProactiveRefreshScheduler::class)
        );
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_provides_comprehensive_token_status_for_healthy_token()
    {
        // Create a healthy token
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addHours(2),
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
            'last_successful_refresh_at' => now()->subHour(),
            'scopes' => ['https://www.googleapis.com/auth/drive.file'],
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertTrue($status['exists']);
        $this->assertEquals('healthy', $status['status']);
        $this->assertEquals('green', $status['health_indicator']);
        $this->assertFalse($status['is_expired']);
        $this->assertFalse($status['is_expiring_soon']);
        $this->assertTrue($status['can_be_refreshed']);
        $this->assertTrue($status['can_manually_refresh']);
        $this->assertNotNull($status['issued_at_human']);
        $this->assertNotNull($status['issued_ago_human']);
        $this->assertNotNull($status['expires_at_human']);
        $this->assertNotNull($status['expires_in_human']);
        $this->assertNotNull($status['next_renewal_at_human']);
        $this->assertNotNull($status['last_successful_refresh_human']);
        $this->assertEquals(0, $status['refresh_failure_count']);
        $this->assertNull($status['last_error']);
        $this->assertNotNull($status['validated_at']);
    }

    /** @test */
    public function it_shows_expiring_soon_status_for_tokens_expiring_within_15_minutes()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addMinutes(10),
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertEquals('expiring_soon', $status['status']);
        $this->assertEquals('yellow', $status['health_indicator']);
        $this->assertTrue($status['is_expiring_soon']);
        $this->assertFalse($status['is_expired']);
        $this->assertNotNull($status['time_until_expiration_seconds']);
        $this->assertTrue($status['time_until_expiration_seconds'] < 900); // Less than 15 minutes
    }

    /** @test */
    public function it_shows_expired_refreshable_status_for_expired_tokens_with_refresh_token()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->subHour(),
            'refresh_token' => 'valid_refresh_token',
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertEquals('expired_refreshable', $status['status']);
        $this->assertEquals('yellow', $status['health_indicator']);
        $this->assertTrue($status['is_expired']);
        $this->assertTrue($status['can_be_refreshed']);
        $this->assertTrue($status['can_manually_refresh']);
    }

    /** @test */
    public function it_shows_expired_manual_status_for_expired_tokens_without_refresh_token()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->subHour(),
            'refresh_token' => null,
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertEquals('expired_manual', $status['status']);
        $this->assertEquals('red', $status['health_indicator']);
        $this->assertTrue($status['is_expired']);
        $this->assertFalse($status['can_be_refreshed']);
        $this->assertFalse($status['can_manually_refresh']);
    }

    /** @test */
    public function it_shows_requires_intervention_status_for_tokens_needing_manual_action()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addHour(),
            'refresh_token' => 'valid_refresh_token',
            'refresh_failure_count' => 5,
            'requires_user_intervention' => true,
            'last_refresh_attempt_at' => now()->subMinutes(30),
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertEquals('requires_intervention', $status['status']);
        $this->assertEquals('red', $status['health_indicator']);
        $this->assertTrue($status['requires_user_intervention']);
        $this->assertFalse($status['can_manually_refresh']);
        $this->assertNotNull($status['last_error']);
        $this->assertEquals('requires_intervention', $status['last_error']['type']);
    }

    /** @test */
    public function it_shows_healthy_with_warnings_for_tokens_with_recent_failures()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addHours(2),
            'refresh_token' => 'valid_refresh_token',
            'refresh_failure_count' => 2,
            'requires_user_intervention' => false,
            'last_successful_refresh_at' => now()->subHour(),
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertEquals('healthy_with_warnings', $status['status']);
        $this->assertEquals('yellow', $status['health_indicator']);
        $this->assertEquals(2, $status['refresh_failure_count']);
        $this->assertTrue($status['can_manually_refresh']);
        $this->assertNotNull($status['last_error']);
        $this->assertEquals('refresh_failure', $status['last_error']['type']);
    }

    /** @test */
    public function it_formats_time_until_expiration_correctly()
    {
        // Test basic time formatting - just verify the method returns something reasonable
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addMinutes(30),
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertNotNull($status['expires_in_human']);
        $this->assertIsString($status['expires_in_human']);
        $this->assertNotEmpty($status['expires_in_human']);
        
        // Test that it contains some time unit
        $this->assertTrue(
            str_contains($status['expires_in_human'], 'minute') || 
            str_contains($status['expires_in_human'], 'hour') || 
            str_contains($status['expires_in_human'], 'day'),
            "Expected time format to contain time units. Got: {$status['expires_in_human']}"
        );
    }

    /** @test */
    public function it_calculates_next_renewal_time_correctly()
    {
        $expiresAt = now()->addHours(2);
        $expectedRenewalTime = $expiresAt->copy()->subMinutes(15);

        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => $expiresAt,
            'refresh_token' => 'valid_refresh_token',
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertNotNull($status['next_renewal_at']);
        $this->assertEquals(
            $expectedRenewalTime->format('M j, Y \a\t g:i A'),
            $status['next_renewal_at_human']
        );
    }

    /** @test */
    public function it_handles_tokens_without_expiration_date()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => null,
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertNull($status['expires_at']);
        $this->assertNull($status['expires_at_human']);
        $this->assertNull($status['expires_in_human']);
        $this->assertNull($status['time_until_expiration_seconds']);
        $this->assertNull($status['next_renewal_at']);
        $this->assertNull($status['next_renewal_at_human']);
        $this->assertFalse($status['is_expired']);
        $this->assertFalse($status['is_expiring_soon']);
    }

    /** @test */
    public function it_includes_scopes_and_token_type_information()
    {
        $scopes = [
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/drive'
        ];

        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'scopes' => $scopes,
            'token_type' => 'Bearer',
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertEquals($scopes, $status['scopes']);
        $this->assertEquals('Bearer', $status['token_type']);
    }

    /** @test */
    public function it_returns_not_connected_status_when_no_token_exists()
    {
        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertFalse($status['exists']);
        $this->assertEquals('not_connected', $status['status']);
        $this->assertArrayHasKey('message', $status);
    }

    /** @test */
    public function it_returns_null_for_unsupported_providers()
    {
        $status = $this->tokenStatusService->getTokenStatus($this->user, 'unsupported-provider');

        $this->assertNull($status);
    }

    /** @test */
    public function can_manually_refresh_returns_correct_values()
    {
        // Test case 1: Can refresh - has token and refresh token, no intervention needed
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_token' => 'valid_refresh_token',
            'requires_user_intervention' => false,
        ]);

        $this->assertTrue($this->tokenStatusService->canManuallyRefresh($this->user, 'google-drive'));

        // Test case 2: Cannot refresh - requires intervention
        $token->update(['requires_user_intervention' => true]);
        $this->assertFalse($this->tokenStatusService->canManuallyRefresh($this->user, 'google-drive'));

        // Test case 3: Cannot refresh - no refresh token
        $token->update([
            'requires_user_intervention' => false,
            'refresh_token' => null
        ]);
        $this->assertFalse($this->tokenStatusService->canManuallyRefresh($this->user, 'google-drive'));

        // Test case 4: Cannot refresh - no token at all
        $token->delete();
        $this->assertFalse($this->tokenStatusService->canManuallyRefresh($this->user, 'google-drive'));

        // Test case 5: Cannot refresh - unsupported provider
        $this->assertFalse($this->tokenStatusService->canManuallyRefresh($this->user, 'unsupported-provider'));
    }

    /** @test */
    public function it_includes_validated_at_timestamp_for_real_time_updates()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
        ]);

        $beforeCall = now();
        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');
        $afterCall = now();

        $this->assertNotNull($status['validated_at']);
        
        $validatedAt = Carbon::parse($status['validated_at']);
        $this->assertTrue($validatedAt->between($beforeCall, $afterCall));
    }
}