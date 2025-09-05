<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Services\TokenStatusService;
use App\Services\ProactiveRefreshScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class TokenStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private TokenStatusService $tokenStatusService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        // Mock the ProactiveRefreshScheduler
        $mockScheduler = $this->createMock(ProactiveRefreshScheduler::class);
        $this->tokenStatusService = new TokenStatusService($mockScheduler);
    }

    /** @test */
    public function returns_null_for_unsupported_providers()
    {
        $status = $this->tokenStatusService->getTokenStatus($this->user, 'unsupported-provider');
        
        $this->assertNull($status);
    }

    /** @test */
    public function returns_not_connected_when_no_token_exists()
    {
        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');
        
        $this->assertIsArray($status);
        $this->assertFalse($status['exists']);
        $this->assertEquals('not_connected', $status['status']);
        $this->assertStringContainsString('not connected', $status['message']);
    }

    /** @test */
    public function returns_healthy_status_for_valid_token()
    {
        $issuedAt = now()->subDays(2);
        $expiresAt = now()->addHours(4);
        
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => $issuedAt,
            'expires_at' => $expiresAt,
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
            'scopes' => ['https://www.googleapis.com/auth/drive.file'],
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertTrue($status['exists']);
        $this->assertEquals('healthy', $status['status']);
        $this->assertEquals('green', $status['health_indicator']);
        $this->assertEquals('Token is healthy and valid', $status['message']);
        
        // Verify lifecycle information
        $this->assertEquals($issuedAt->format('Y-m-d H:i:s'), $status['issued_at']->format('Y-m-d H:i:s'));
        $this->assertEquals($expiresAt->format('Y-m-d H:i:s'), $status['expires_at']->format('Y-m-d H:i:s'));
        $this->assertFalse($status['is_expired']);
        $this->assertFalse($status['is_expiring_soon']);
        $this->assertTrue($status['can_be_refreshed']);
        
        // Verify scopes
        $this->assertEquals(['https://www.googleapis.com/auth/drive.file'], $status['scopes']);
    }

    /** @test */
    public function detects_expiring_soon_tokens()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addMinutes(10), // Expiring in 10 minutes
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertEquals('expiring_soon', $status['status']);
        $this->assertEquals('yellow', $status['health_indicator']);
        $this->assertTrue($status['is_expiring_soon']);
        $this->assertFalse($status['is_expired']);
        $this->assertStringContainsString('automatically renewed soon', $status['message']);
    }

    /** @test */
    public function detects_expired_but_refreshable_tokens()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->subMinutes(30), // Expired 30 minutes ago
            'refresh_failure_count' => 2, // Some failures but under limit
            'requires_user_intervention' => false,
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertEquals('expired_refreshable', $status['status']);
        $this->assertEquals('yellow', $status['health_indicator']);
        $this->assertTrue($status['is_expired']);
        $this->assertTrue($status['can_be_refreshed']);
        $this->assertStringContainsString('automatically refreshed', $status['message']);
    }

    /** @test */
    public function detects_expired_manual_intervention_required()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->subHours(2),
            'refresh_failure_count' => 5, // Max failures reached
            'requires_user_intervention' => false, // Will be set to true by canBeRefreshed logic
            'refresh_token' => null, // No refresh token available
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertEquals('expired_manual', $status['status']);
        $this->assertEquals('red', $status['health_indicator']);
        $this->assertTrue($status['is_expired']);
        $this->assertFalse($status['can_be_refreshed']);
        $this->assertStringContainsString('manual reconnection', $status['message']);
    }

    /** @test */
    public function detects_requires_user_intervention_status()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addHours(1),
            'refresh_failure_count' => 3,
            'requires_user_intervention' => true,
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertEquals('requires_intervention', $status['status']);
        $this->assertEquals('red', $status['health_indicator']);
        $this->assertTrue($status['requires_user_intervention']);
        $this->assertStringContainsString('manual reconnection', $status['message']);
    }

    /** @test */
    public function detects_healthy_with_warnings_status()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addHours(4),
            'refresh_failure_count' => 2, // Some failures but token still valid
            'requires_user_intervention' => false,
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertEquals('healthy_with_warnings', $status['status']);
        $this->assertEquals('yellow', $status['health_indicator']);
        $this->assertStringContainsString('2 recent refresh failure(s)', $status['message']);
    }

    /** @test */
    public function calculates_next_renewal_time_correctly()
    {
        $expiresAt = now()->addHours(2);
        $expectedRenewalTime = $expiresAt->copy()->subMinutes(15);
        
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => $expiresAt,
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertNotNull($status['next_renewal_at']);
        $this->assertEquals($expectedRenewalTime->format('Y-m-d H:i'), $status['next_renewal_at']->format('Y-m-d H:i'));
        $this->assertNotNull($status['next_renewal_at_human']);
    }

    /** @test */
    public function formats_time_until_expiration_correctly()
    {
        // Test that the expires_in_human field is populated
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addHours(2), // 2 hours from now
            'refresh_failure_count' => 0,
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        // Just verify the field exists and is not null
        $this->assertNotNull($status['expires_in_human']);
        $this->assertIsString($status['expires_in_human']);
        
        // Test with expired token
        $token->update(['expires_at' => now()->subMinutes(30)]);
        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');
        
        // For expired tokens, expires_in_human should be null
        $this->assertNull($status['expires_in_human']);
    }

    /** @test */
    public function includes_refresh_history_information()
    {
        $lastRefreshAt = now()->subHours(2);
        $lastAttemptAt = now()->subMinutes(30);
        
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addHours(4),
            'last_successful_refresh_at' => $lastRefreshAt,
            'last_refresh_attempt_at' => $lastAttemptAt,
            'refresh_failure_count' => 1,
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertEquals($lastRefreshAt->format('Y-m-d H:i:s'), $status['last_successful_refresh_at']->format('Y-m-d H:i:s'));
        $this->assertEquals($lastAttemptAt->format('Y-m-d H:i:s'), $status['last_refresh_attempt_at']->format('Y-m-d H:i:s'));
        $this->assertNotNull($status['last_successful_refresh_human']);
        $this->assertNotNull($status['last_refresh_attempt_human']);
        $this->assertEquals(1, $status['refresh_failure_count']);
    }

    /** @test */
    public function handles_multiple_providers_correctly()
    {
        // Create tokens for different scenarios
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addHours(4),
            'refresh_failure_count' => 0,
        ]);

        $statuses = $this->tokenStatusService->getMultipleTokenStatuses($this->user, ['google-drive', 'microsoft-teams']);

        $this->assertIsArray($statuses);
        $this->assertArrayHasKey('google-drive', $statuses);
        $this->assertArrayHasKey('microsoft-teams', $statuses);
        
        // Google Drive should have token info
        $this->assertTrue($statuses['google-drive']['exists']);
        
        // Microsoft Teams should return null (not supported yet)
        $this->assertNull($statuses['microsoft-teams']);
    }

    /** @test */
    public function identifies_tokens_needing_attention()
    {
        // Create one healthy token and one requiring intervention
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addHours(4),
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
        ]);

        $needingAttention = $this->tokenStatusService->getTokensNeedingAttention($this->user, ['google-drive']);

        // Should be empty since token is healthy
        $this->assertEmpty($needingAttention);

        // Update token to require intervention
        GoogleDriveToken::where('user_id', $this->user->id)->update([
            'requires_user_intervention' => true,
            'refresh_failure_count' => 5,
        ]);

        $needingAttention = $this->tokenStatusService->getTokensNeedingAttention($this->user, ['google-drive']);

        $this->assertNotEmpty($needingAttention);
        $this->assertArrayHasKey('google-drive', $needingAttention);
        $this->assertEquals('requires_intervention', $needingAttention['google-drive']['status']);
    }

    /** @test */
    public function handles_tokens_without_expiration_date()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => null, // No expiration date
            'refresh_failure_count' => 0,
        ]);

        $status = $this->tokenStatusService->getTokenStatus($this->user, 'google-drive');

        $this->assertTrue($status['exists']);
        $this->assertNull($status['expires_at']);
        $this->assertNull($status['expires_at_human']);
        $this->assertNull($status['expires_in_human']);
        $this->assertNull($status['next_renewal_at']);
        $this->assertFalse($status['is_expired']);
        $this->assertFalse($status['is_expiring_soon']);
    }
}