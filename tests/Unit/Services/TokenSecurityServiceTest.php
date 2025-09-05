<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Services\TokenSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Mockery;
use Tests\TestCase;
use Carbon\Carbon;

class TokenSecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    private TokenSecurityService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TokenSecurityService();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_check_user_rate_limit_allows_requests_under_limit(): void
    {
        Request::shouldReceive('ip')->andReturn('127.0.0.1');
        
        $result = $this->service->checkUserRateLimit($this->user);
        
        $this->assertTrue($result);
    }

    public function test_check_user_rate_limit_blocks_requests_over_limit(): void
    {
        Request::shouldReceive('ip')->andReturn('127.0.0.1');
        
        // Simulate 5 attempts (the limit)
        for ($i = 0; $i < 5; $i++) {
            $this->service->recordRefreshAttempt($this->user);
        }
        
        $result = $this->service->checkUserRateLimit($this->user);
        
        $this->assertFalse($result);
    }

    public function test_check_ip_rate_limit_allows_requests_under_limit(): void
    {
        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        
        $result = $this->service->checkIpRateLimit('192.168.1.1');
        
        $this->assertTrue($result);
    }

    public function test_check_ip_rate_limit_blocks_requests_over_limit(): void
    {
        $ipAddress = '192.168.1.1';
        Request::shouldReceive('ip')->andReturn($ipAddress);
        
        // Simulate 20 attempts (the limit)
        for ($i = 0; $i < 20; $i++) {
            $this->service->recordRefreshAttempt($this->user, $ipAddress);
        }
        
        $result = $this->service->checkIpRateLimit($ipAddress);
        
        $this->assertFalse($result);
    }

    public function test_record_refresh_attempt_increments_counters(): void
    {
        $ipAddress = '192.168.1.1';
        Request::shouldReceive('ip')->andReturn($ipAddress);
        
        $this->service->recordRefreshAttempt($this->user, $ipAddress);
        
        $this->assertEquals(4, $this->service->getRemainingUserAttempts($this->user));
        $this->assertEquals(19, $this->service->getRemainingIpAttempts($ipAddress));
    }

    public function test_reset_user_rate_limit_clears_counter(): void
    {
        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        
        // Record some attempts
        $this->service->recordRefreshAttempt($this->user);
        $this->service->recordRefreshAttempt($this->user);
        
        $this->assertEquals(3, $this->service->getRemainingUserAttempts($this->user));
        
        // Reset the limit
        $this->service->resetUserRateLimit($this->user);
        
        $this->assertEquals(5, $this->service->getRemainingUserAttempts($this->user));
    }

    public function test_rotate_token_on_refresh_updates_token_data(): void
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'old_access_token',
            'refresh_token' => 'old_refresh_token',
            'expires_at' => now()->addHour(),
        ]);

        $newTokenData = [
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires_in' => 3600,
        ];

        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('header')->with('User-Agent')->andReturn('Test Agent');

        $updatedToken = $this->service->rotateTokenOnRefresh($token, $newTokenData);

        $this->assertEquals('new_access_token', $updatedToken->access_token);
        $this->assertEquals('new_refresh_token', $updatedToken->refresh_token);
        $this->assertNotNull($updatedToken->last_successful_refresh_at);
        $this->assertEquals(0, $updatedToken->refresh_failure_count);
    }

    public function test_audit_refresh_failure_logs_security_event(): void
    {
        $exception = new \Exception('Test failure');
        
        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('header')->with('User-Agent')->andReturn('Test Agent');
        
        Log::shouldReceive('channel')->with('security')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $this->service->auditRefreshFailure($this->user, $exception, ['test' => 'context']);
    }

    public function test_audit_user_intervention_logs_security_event(): void
    {
        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('header')->with('User-Agent')->andReturn('Test Agent');
        
        Log::shouldReceive('channel')->with('security')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $this->service->auditUserIntervention($this->user, 'manual_reconnection', ['test' => 'context']);
    }

    public function test_log_authentication_event_logs_security_event(): void
    {
        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        Request::shouldReceive('header')->with('User-Agent')->andReturn('Test Agent');
        
        Log::shouldReceive('channel')->with('security')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $this->service->logAuthenticationEvent('login_success', $this->user, ['test' => 'context']);
    }

    public function test_get_remaining_user_attempts_returns_correct_count(): void
    {
        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        
        $this->assertEquals(5, $this->service->getRemainingUserAttempts($this->user));
        
        $this->service->recordRefreshAttempt($this->user);
        $this->assertEquals(4, $this->service->getRemainingUserAttempts($this->user));
        
        $this->service->recordRefreshAttempt($this->user);
        $this->assertEquals(3, $this->service->getRemainingUserAttempts($this->user));
    }

    public function test_get_remaining_ip_attempts_returns_correct_count(): void
    {
        $ipAddress = '192.168.1.1';
        
        $this->assertEquals(20, $this->service->getRemainingIpAttempts($ipAddress));
        
        Request::shouldReceive('ip')->andReturn($ipAddress);
        $this->service->recordRefreshAttempt($this->user, $ipAddress);
        $this->assertEquals(19, $this->service->getRemainingIpAttempts($ipAddress));
    }

    public function test_get_rate_limit_reset_time_returns_correct_time(): void
    {
        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        
        // Record an attempt to set the cache
        $this->service->recordRefreshAttempt($this->user);
        
        $resetTime = $this->service->getRateLimitResetTime($this->user);
        
        $this->assertInstanceOf(Carbon::class, $resetTime);
        $this->assertTrue($resetTime->isFuture());
    }

    public function test_security_logging_includes_structured_data(): void
    {
        $exception = new \Exception('Test failure', 500);
        
        Request::shouldReceive('ip')->andReturn('192.168.1.100');
        Request::shouldReceive('header')->with('User-Agent')->andReturn('Mozilla/5.0 Test Browser');
        
        Log::shouldReceive('channel')->with('security')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Token Security Event', \Mockery::on(function ($data) {
            return $data['event'] === 'token_refresh_failure' &&
                   $data['data']['user_id'] === $this->user->id &&
                   $data['data']['error_message'] === 'Test failure' &&
                   $data['data']['error_code'] === 500 &&
                   $data['data']['ip_address'] === '192.168.1.100' &&
                   $data['data']['user_agent'] === 'Mozilla/5.0 Test Browser' &&
                   isset($data['timestamp']);
        }));

        $this->service->auditRefreshFailure($this->user, $exception);
    }

    public function test_rate_limit_respects_ttl(): void
    {
        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        
        // Record attempts up to the limit
        for ($i = 0; $i < 5; $i++) {
            $this->service->recordRefreshAttempt($this->user);
        }
        
        // Should be blocked
        $this->assertFalse($this->service->checkUserRateLimit($this->user));
        
        // Manually clear the cache to simulate TTL expiry
        Cache::forget('token_refresh_rate_limit_user_' . $this->user->id);
        
        // Should be allowed again
        $this->assertTrue($this->service->checkUserRateLimit($this->user));
    }

    public function test_concurrent_rate_limit_checks_are_thread_safe(): void
    {
        Request::shouldReceive('ip')->andReturn('192.168.1.1');
        
        // Simulate concurrent requests by checking and recording simultaneously
        $results = [];
        
        for ($i = 0; $i < 10; $i++) {
            $canProceed = $this->service->checkUserRateLimit($this->user);
            if ($canProceed) {
                $this->service->recordRefreshAttempt($this->user);
                $results[] = true;
            } else {
                $results[] = false;
            }
        }
        
        // Should have exactly 5 successful attempts and 5 blocked
        $successful = array_filter($results);
        $this->assertCount(5, $successful);
    }
}