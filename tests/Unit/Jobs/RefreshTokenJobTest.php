<?php

namespace Tests\Unit\Jobs;

use App\Enums\TokenRefreshErrorType;
use App\Jobs\RefreshTokenJob;
use App\Models\User;
use App\Services\ProactiveTokenRenewalService;
use App\Services\RefreshResult;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class RefreshTokenJobTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProactiveTokenRenewalService $mockRenewalService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->mockRenewalService = Mockery::mock(ProactiveTokenRenewalService::class);
        
        Log::spy();
    }

    public function test_handle_successful_refresh(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        $successResult = RefreshResult::success(['access_token' => 'new_token'], 'Token refreshed');
        
        $this->mockRenewalService
            ->shouldReceive('refreshTokenIfNeeded')
            ->once()
            ->with($this->user, 'google-drive')
            ->andReturn($successResult);

        $job->handle($this->mockRenewalService);

        // Job should complete without throwing exceptions
        $this->assertTrue(true);
    }

    public function test_handle_already_valid_token(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        $alreadyValidResult = RefreshResult::alreadyValid('Token is still valid');
        
        $this->mockRenewalService
            ->shouldReceive('refreshTokenIfNeeded')
            ->once()
            ->with($this->user, 'google-drive')
            ->andReturn($alreadyValidResult);

        $job->handle($this->mockRenewalService);

        // Job should complete without throwing exceptions
        $this->assertTrue(true);
    }

    public function test_handle_refresh_failure_with_recoverable_error(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        $exception = new Exception('Network timeout');
        $failureResult = RefreshResult::failure(
            TokenRefreshErrorType::NETWORK_TIMEOUT,
            $exception,
            'Network timeout occurred'
        );
        
        $this->mockRenewalService
            ->shouldReceive('refreshTokenIfNeeded')
            ->once()
            ->with($this->user, 'google-drive')
            ->andReturn($failureResult);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Network timeout');

        $job->handle($this->mockRenewalService);
    }

    public function test_handle_refresh_failure_with_non_recoverable_error(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        $exception = new Exception('Invalid refresh token');
        $failureResult = RefreshResult::failure(
            TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
            $exception,
            'Invalid refresh token'
        );
        
        $this->mockRenewalService
            ->shouldReceive('refreshTokenIfNeeded')
            ->once()
            ->with($this->user, 'google-drive')
            ->andReturn($failureResult);

        // Mock the fail method to verify it's called
        $job = Mockery::mock(RefreshTokenJob::class, [$this->user, 'google-drive'])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        
        $job->shouldReceive('fail')
            ->once()
            ->with(Mockery::type(Exception::class));

        $job->handle($this->mockRenewalService);
    }

    public function test_handle_exception_during_refresh(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        $exception = new Exception('Unexpected error');
        
        $this->mockRenewalService
            ->shouldReceive('refreshTokenIfNeeded')
            ->once()
            ->with($this->user, 'google-drive')
            ->andThrow($exception);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unexpected error');

        $job->handle($this->mockRenewalService);
    }

    public function test_configure_retry_behavior_for_network_timeout(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        // Use reflection to call private method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('configureRetryBehavior');
        $method->setAccessible(true);
        
        $method->invoke($job, TokenRefreshErrorType::NETWORK_TIMEOUT);
        
        // Network timeout allows 5 retries + 1 initial attempt = 6 tries
        $this->assertEquals(6, $job->tries);
        $this->assertNotEmpty($job->backoff);
    }

    public function test_configure_retry_behavior_for_invalid_refresh_token(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        // Use reflection to call private method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('configureRetryBehavior');
        $method->setAccessible(true);
        
        $method->invoke($job, TokenRefreshErrorType::INVALID_REFRESH_TOKEN);
        
        // Invalid refresh token allows 0 retries + 1 initial attempt = 1 try
        $this->assertEquals(1, $job->tries);
    }

    public function test_classify_error_network_timeout(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        $exception = new Exception('Connection timeout occurred');
        
        // Use reflection to call private method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('classifyError');
        $method->setAccessible(true);
        
        $errorType = $method->invoke($job, $exception);
        
        $this->assertEquals(TokenRefreshErrorType::NETWORK_TIMEOUT, $errorType);
    }

    public function test_classify_error_invalid_refresh_token(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        $exception = new Exception('invalid_grant: Invalid refresh token');
        
        // Use reflection to call private method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('classifyError');
        $method->setAccessible(true);
        
        $errorType = $method->invoke($job, $exception);
        
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $errorType);
    }

    public function test_classify_error_expired_refresh_token(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        $exception = new Exception('Refresh token has expired');
        
        // Use reflection to call private method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('classifyError');
        $method->setAccessible(true);
        
        $errorType = $method->invoke($job, $exception);
        
        $this->assertEquals(TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN, $errorType);
    }

    public function test_classify_error_api_quota_exceeded(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        $exception = new Exception('API quota exceeded');
        
        // Use reflection to call private method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('classifyError');
        $method->setAccessible(true);
        
        $errorType = $method->invoke($job, $exception);
        
        $this->assertEquals(TokenRefreshErrorType::API_QUOTA_EXCEEDED, $errorType);
    }

    public function test_classify_error_service_unavailable(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        $exception = new Exception('Service temporarily unavailable');
        
        // Use reflection to call private method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('classifyError');
        $method->setAccessible(true);
        
        $errorType = $method->invoke($job, $exception);
        
        $this->assertEquals(TokenRefreshErrorType::SERVICE_UNAVAILABLE, $errorType);
    }

    public function test_classify_error_unknown(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        $exception = new Exception('Some unknown error occurred');
        
        // Use reflection to call private method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('classifyError');
        $method->setAccessible(true);
        
        $errorType = $method->invoke($job, $exception);
        
        $this->assertEquals(TokenRefreshErrorType::UNKNOWN_ERROR, $errorType);
    }

    public function test_failed_method_handles_failure_properly(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        $exception = new Exception('Network timeout');
        
        // Mock the ProactiveTokenRenewalService
        $mockRenewalService = Mockery::mock(ProactiveTokenRenewalService::class);
        $mockRenewalService
            ->shouldReceive('handleRefreshFailure')
            ->once()
            ->with(
                $this->user,
                'google-drive',
                TokenRefreshErrorType::NETWORK_TIMEOUT,
                $exception
            );
        
        // Bind the mock to the container
        $this->app->instance(ProactiveTokenRenewalService::class, $mockRenewalService);
        
        $job->failed($exception);
        
        // Verify the mock expectations were met
        $this->assertTrue(true);
    }

    public function test_job_tags(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        $tags = $job->tags();
        
        $this->assertContains('token-refresh', $tags);
        $this->assertContains("user:{$this->user->id}", $tags);
        $this->assertContains('provider:google-drive', $tags);
    }

    public function test_job_backoff(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        // Set some backoff values
        $job->backoff = [30, 60, 120];
        
        $backoff = $job->backoff();
        
        $this->assertEquals([30, 60, 120], $backoff);
    }

    public function test_job_retry_until(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        $retryUntil = $job->retryUntil();
        
        // Should be approximately 1 hour from now
        $expectedTime = now()->addHour();
        $this->assertEqualsWithDelta(
            $expectedTime->timestamp,
            $retryUntil->getTimestamp(),
            60 // Allow 1 minute difference
        );
    }

    public function test_job_timeout(): void
    {
        $job = new RefreshTokenJob($this->user, 'google-drive');
        
        $this->assertEquals(120, $job->timeout);
    }
}