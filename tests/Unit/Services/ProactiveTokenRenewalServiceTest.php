<?php

namespace Tests\Unit\Services;

use App\Enums\TokenRefreshErrorType;
use App\Jobs\RefreshTokenJob;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\ProactiveTokenRenewalService;
use App\Services\RefreshResult;
use App\Services\TokenRefreshCoordinator;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Mockery;

class ProactiveTokenRenewalServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProactiveTokenRenewalService $service;
    private TokenRefreshCoordinator $mockCoordinator;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockCoordinator = Mockery::mock(TokenRefreshCoordinator::class);
        $this->service = new ProactiveTokenRenewalService($this->mockCoordinator);
        
        $this->user = User::factory()->create();
        
        Queue::fake();
        Log::spy();
    }

    public function test_refresh_token_if_needed_with_unsupported_provider(): void
    {
        $result = $this->service->refreshTokenIfNeeded($this->user, 'unsupported-provider');

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(TokenRefreshErrorType::UNKNOWN_ERROR, $result->getErrorType());
        $this->assertStringContainsString('not supported', $result->message);
    }

    public function test_refresh_token_if_needed_with_no_token(): void
    {
        $result = $this->service->refreshTokenIfNeeded($this->user, 'google-drive');

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result->getErrorType());
        $this->assertStringContainsString('No authentication token found', $result->message);
    }

    public function test_refresh_token_if_needed_with_token_not_expiring_soon(): void
    {
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addHours(2), // Not expiring soon
            'refresh_token' => 'valid_refresh_token',
            'requires_user_intervention' => false,
            'refresh_failure_count' => 0,
        ]);

        $result = $this->service->refreshTokenIfNeeded($this->user, 'google-drive');

        $this->assertTrue($result->isSuccessful());
        $this->assertTrue($result->wasAlreadyValid);
        $this->assertStringContainsString('not expiring soon', $result->message);
    }

    public function test_refresh_token_if_needed_with_token_that_cannot_be_refreshed(): void
    {
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addMinutes(10), // Expiring soon
            'refresh_token' => null, // No refresh token
            'requires_user_intervention' => true,
            'refresh_failure_count' => 5,
        ]);

        $result = $this->service->refreshTokenIfNeeded($this->user, 'google-drive');

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result->getErrorType());
        $this->assertStringContainsString('user re-authentication', $result->message);
    }

    public function test_refresh_token_if_needed_successful_refresh(): void
    {
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addMinutes(10), // Expiring soon
            'refresh_token' => 'valid_refresh_token',
            'requires_user_intervention' => false,
            'refresh_failure_count' => 0,
        ]);

        $successResult = RefreshResult::success(['access_token' => 'new_token'], 'Token refreshed');
        $this->mockCoordinator
            ->shouldReceive('coordinateRefresh')
            ->once()
            ->with($this->user, 'google-drive')
            ->andReturn($successResult);

        $result = $this->service->refreshTokenIfNeeded($this->user, 'google-drive');

        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->wasAlreadyValid);
        $this->assertEquals('Token refreshed', $result->message);
    }

    public function test_refresh_token_if_needed_failed_refresh(): void
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addMinutes(10), // Expiring soon
            'refresh_token' => 'valid_refresh_token',
            'requires_user_intervention' => false,
            'refresh_failure_count' => 0,
        ]);

        $exception = new Exception('Network timeout');
        $failureResult = RefreshResult::failure(
            TokenRefreshErrorType::NETWORK_TIMEOUT,
            $exception,
            'Network timeout occurred'
        );
        
        $this->mockCoordinator
            ->shouldReceive('coordinateRefresh')
            ->once()
            ->with($this->user, 'google-drive')
            ->andReturn($failureResult);

        $result = $this->service->refreshTokenIfNeeded($this->user, 'google-drive');

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(TokenRefreshErrorType::NETWORK_TIMEOUT, $result->getErrorType());
        
        // Verify failure was handled
        $token->refresh();
        $this->assertEquals(1, $token->refresh_failure_count);
    }

    public function test_schedule_preemptive_refresh_with_unsupported_provider(): void
    {
        $expiresAt = now()->addHours(1);
        
        $this->service->schedulePreemptiveRefresh($this->user, 'unsupported-provider', $expiresAt);

        Queue::assertNothingPushed();
    }

    public function test_schedule_preemptive_refresh_with_past_refresh_time(): void
    {
        $expiresAt = now()->addMinutes(10); // Refresh time would be in the past
        
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => $expiresAt,
        ]);

        $this->service->schedulePreemptiveRefresh($this->user, 'google-drive', $expiresAt);

        Queue::assertPushed(RefreshTokenJob::class, function ($job) {
            return $job->user->id === $this->user->id 
                && $job->provider === 'google-drive'
                && $job->queue === 'high';
        });
    }

    public function test_schedule_preemptive_refresh_with_future_refresh_time(): void
    {
        $expiresAt = now()->addHours(2); // Refresh time will be in the future
        
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => $expiresAt,
        ]);

        $this->service->schedulePreemptiveRefresh($this->user, 'google-drive', $expiresAt);

        Queue::assertPushed(RefreshTokenJob::class, function ($job) {
            return $job->user->id === $this->user->id 
                && $job->provider === 'google-drive'
                && $job->queue === 'maintenance';
        });

        // Verify token was updated with scheduled time
        $token = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertNotNull($token->proactive_refresh_scheduled_at);
    }

    public function test_handle_refresh_failure_updates_token_tracking(): void
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_failure_count' => 2,
        ]);

        $exception = new Exception('Network timeout');
        
        $this->service->handleRefreshFailure(
            $this->user, 
            'google-drive', 
            TokenRefreshErrorType::NETWORK_TIMEOUT, 
            $exception
        );

        $token->refresh();
        $this->assertEquals(3, $token->refresh_failure_count);
        $this->assertNotNull($token->last_refresh_attempt_at);
    }

    public function test_handle_refresh_failure_sends_notification_for_user_intervention_errors(): void
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_failure_count' => 0,
        ]);

        $exception = new Exception('Invalid refresh token');
        
        $this->service->handleRefreshFailure(
            $this->user, 
            'google-drive', 
            TokenRefreshErrorType::INVALID_REFRESH_TOKEN, 
            $exception
        );

        $token->refresh();
        $this->assertNotNull($token->last_notification_sent_at);
    }

    public function test_handle_refresh_failure_schedules_retry_for_recoverable_errors(): void
    {
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_failure_count' => 1,
            'refresh_token' => 'valid_refresh_token',
            'requires_user_intervention' => false,
        ]);

        $exception = new Exception('Network timeout');
        
        $this->service->handleRefreshFailure(
            $this->user, 
            'google-drive', 
            TokenRefreshErrorType::NETWORK_TIMEOUT, 
            $exception
        );

        Queue::assertPushed(RefreshTokenJob::class, function ($job) {
            return $job->user->id === $this->user->id 
                && $job->provider === 'google-drive'
                && $job->queue === 'maintenance';
        });
    }

    public function test_handle_refresh_failure_does_not_schedule_retry_for_non_recoverable_errors(): void
    {
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_failure_count' => 1,
        ]);

        $exception = new Exception('Invalid refresh token');
        
        $this->service->handleRefreshFailure(
            $this->user, 
            'google-drive', 
            TokenRefreshErrorType::INVALID_REFRESH_TOKEN, 
            $exception
        );

        Queue::assertNotPushed(RefreshTokenJob::class);
    }

    public function test_schedule_proactive_refresh_for_expiring_tokens(): void
    {
        // Create tokens in various states
        $expiringToken = GoogleDriveToken::factory()->create([
            'user_id' => User::factory()->create()->id,
            'expires_at' => now()->addMinutes(20), // Within 30-minute window
            'proactive_refresh_scheduled_at' => null,
            'requires_user_intervention' => false,
            'refresh_token' => 'valid_refresh_token',
        ]);

        $alreadyScheduledToken = GoogleDriveToken::factory()->create([
            'user_id' => User::factory()->create()->id,
            'expires_at' => now()->addMinutes(20),
            'proactive_refresh_scheduled_at' => now()->addMinutes(5), // Already scheduled
            'requires_user_intervention' => false,
            'refresh_token' => 'valid_refresh_token',
        ]);

        $interventionRequiredToken = GoogleDriveToken::factory()->create([
            'user_id' => User::factory()->create()->id,
            'expires_at' => now()->addMinutes(20),
            'proactive_refresh_scheduled_at' => null,
            'requires_user_intervention' => true, // Requires intervention
            'refresh_token' => 'valid_refresh_token',
        ]);

        $summary = $this->service->scheduleProactiveRefreshForExpiringTokens();

        $this->assertEquals(1, $summary['total_expiring_tokens']);
        $this->assertEquals(1, $summary['scheduled']);
        $this->assertEquals(0, $summary['skipped']);
        $this->assertEquals(0, $summary['errors']);

        Queue::assertPushed(RefreshTokenJob::class, 1);
    }

    public function test_should_send_notification_logic(): void
    {
        // Test notification for user intervention errors (always send)
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'refresh_failure_count' => 1,
        ]);

        $shouldSend = $this->invokePrivateMethod(
            $this->service, 
            'shouldSendNotification', 
            [$this->user, 'google-drive', TokenRefreshErrorType::INVALID_REFRESH_TOKEN]
        );
        
        $this->assertTrue($shouldSend);

        // Test notification for recoverable errors (only after 3+ failures)
        $shouldSend = $this->invokePrivateMethod(
            $this->service, 
            'shouldSendNotification', 
            [$this->user, 'google-drive', TokenRefreshErrorType::NETWORK_TIMEOUT]
        );
        
        $this->assertFalse($shouldSend);

        // Update failure count and test again
        $token->update(['refresh_failure_count' => 3]);
        
        $shouldSend = $this->invokePrivateMethod(
            $this->service, 
            'shouldSendNotification', 
            [$this->user, 'google-drive', TokenRefreshErrorType::NETWORK_TIMEOUT]
        );
        
        $this->assertTrue($shouldSend);
    }

    public function test_send_refresh_failure_notification_updates_timestamp(): void
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'last_notification_sent_at' => null,
        ]);

        $exception = new Exception('Test error');
        
        $this->invokePrivateMethod(
            $this->service, 
            'sendRefreshFailureNotification', 
            [$this->user, 'google-drive', TokenRefreshErrorType::NETWORK_TIMEOUT, $exception]
        );

        $token->refresh();
        $this->assertNotNull($token->last_notification_sent_at);
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}