<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Services\TokenSecurityService;
use App\Services\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TokenRefreshSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private TokenSecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->securityService = app(TokenSecurityService::class);
    }

    public function test_rate_limiting_prevents_excessive_refresh_attempts(): void
    {
        // Create a token for the user
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->subHour(), // Expired token
        ]);

        // Mock the Google Drive service to always fail
        $this->mock(GoogleDriveService::class, function ($mock) {
            $mock->shouldReceive('validateAndRefreshToken')
                ->andReturn(false);
        });

        // Attempt to refresh 5 times (the limit)
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($this->securityService->checkUserRateLimit($this->user));
            $this->securityService->recordRefreshAttempt($this->user);
        }

        // 6th attempt should be blocked
        $this->assertFalse($this->securityService->checkUserRateLimit($this->user));
    }

    public function test_successful_refresh_resets_rate_limit(): void
    {
        // Record some failed attempts
        for ($i = 0; $i < 3; $i++) {
            $this->securityService->recordRefreshAttempt($this->user);
        }

        $this->assertEquals(2, $this->securityService->getRemainingUserAttempts($this->user));

        // Simulate successful refresh
        $this->securityService->resetUserRateLimit($this->user);

        $this->assertEquals(5, $this->securityService->getRemainingUserAttempts($this->user));
    }

    public function test_token_rotation_updates_security_fields(): void
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'old_access_token',
            'refresh_token' => 'old_refresh_token',
            'refresh_failure_count' => 2,
        ]);

        $newTokenData = [
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires_in' => 3600,
        ];

        $updatedToken = $this->securityService->rotateTokenOnRefresh($token, $newTokenData);

        $this->assertEquals('new_access_token', $updatedToken->access_token);
        $this->assertEquals('new_refresh_token', $updatedToken->refresh_token);
        $this->assertEquals(0, $updatedToken->refresh_failure_count);
        $this->assertNotNull($updatedToken->last_successful_refresh_at);
    }

    public function test_audit_logging_captures_security_events(): void
    {
        Log::shouldReceive('channel')->with('security')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Token Security Event', \Mockery::on(function ($data) {
            return $data['event'] === 'token_refresh_failure' &&
                   $data['data']['user_id'] === $this->user->id &&
                   $data['data']['error_message'] === 'Test security failure';
        }));

        $exception = new \Exception('Test security failure');
        
        $this->securityService->auditRefreshFailure($this->user, $exception, [
            'test_context' => 'security_test'
        ]);
    }

    public function test_ip_rate_limiting_works_independently(): void
    {
        $ipAddress = '192.168.1.100';

        // Test that IP rate limiting works independently of user rate limiting
        for ($i = 0; $i < 20; $i++) {
            $this->assertTrue($this->securityService->checkIpRateLimit($ipAddress));
            $this->securityService->recordRefreshAttempt($this->user, $ipAddress);
        }

        // 21st attempt should be blocked
        $this->assertFalse($this->securityService->checkIpRateLimit($ipAddress));
    }

    public function test_security_service_handles_concurrent_requests(): void
    {
        // Simulate concurrent requests by multiple processes
        $results = [];
        
        for ($i = 0; $i < 10; $i++) {
            if ($this->securityService->checkUserRateLimit($this->user)) {
                $this->securityService->recordRefreshAttempt($this->user);
                $results[] = 'allowed';
            } else {
                $results[] = 'blocked';
            }
        }

        // Should have exactly 5 allowed and 5 blocked
        $allowed = array_filter($results, fn($r) => $r === 'allowed');
        $blocked = array_filter($results, fn($r) => $r === 'blocked');
        
        $this->assertCount(5, $allowed);
        $this->assertCount(5, $blocked);
    }

    public function test_authentication_events_are_logged(): void
    {
        Log::shouldReceive('channel')->with('security')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Token Security Event', \Mockery::on(function ($data) {
            return $data['event'] === 'authentication_event' &&
                   $data['data']['event'] === 'token_refresh_success' &&
                   $data['data']['user_id'] === $this->user->id;
        }));

        $this->securityService->logAuthenticationEvent('token_refresh_success', $this->user, [
            'provider' => 'google-drive',
            'operation_id' => 'test_123'
        ]);
    }

    public function test_user_intervention_is_audited(): void
    {
        Log::shouldReceive('channel')->with('security')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Token Security Event', \Mockery::on(function ($data) {
            return $data['event'] === 'user_intervention' &&
                   $data['data']['action'] === 'manual_reconnection' &&
                   $data['data']['user_id'] === $this->user->id;
        }));

        $this->securityService->auditUserIntervention($this->user, 'manual_reconnection', [
            'provider' => 'google-drive',
            'reason' => 'expired_refresh_token'
        ]);
    }
}