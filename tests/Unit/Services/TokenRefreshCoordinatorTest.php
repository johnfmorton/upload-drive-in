<?php

namespace Tests\Unit\Services;

use App\Enums\TokenRefreshErrorType;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\GoogleDriveService;
use App\Services\RefreshResult;
use App\Services\TokenRefreshCoordinator;
use Exception;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class TokenRefreshCoordinatorTest extends TestCase
{
    use RefreshDatabase;

    private TokenRefreshCoordinator $coordinator;
    private GoogleDriveService $mockGoogleDriveService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockGoogleDriveService = Mockery::mock(GoogleDriveService::class);
        $this->coordinator = new TokenRefreshCoordinator($this->mockGoogleDriveService);
        
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_coordinate_refresh_returns_already_valid_when_token_not_expiring(): void
    {
        // Create a token that expires in 2 hours (not expiring soon within 15 minutes)
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->addHours(2),
            'refresh_token' => 'valid_refresh_token',
        ]);

        $result = $this->coordinator->coordinateRefresh($this->user, 'google-drive');

        $this->assertTrue($result->isSuccessful());
        $this->assertTrue($result->wasAlreadyValid);
        $this->assertFalse($result->wasRefreshedByAnotherProcess);
        $this->assertStringContainsString('still valid', $result->message);
    }

    public function test_coordinate_refresh_returns_refreshed_by_another_process_when_token_was_refreshed(): void
    {
        // Create a token that has already expired
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->subMinutes(5), // Already expired
            'refresh_token' => 'valid_refresh_token',
        ]);

        // Simulate another process refreshing the token during our operation
        Cache::shouldReceive('lock')
            ->once()
            ->with("token_refresh_{$this->user->id}_google-drive", 30)
            ->andReturnUsing(function ($key, $ttl) use ($token) {
                $mockLock = Mockery::mock();
                $mockLock->shouldReceive('block')
                    ->once()
                    ->with(5, Mockery::type('callable'))
                    ->andReturnUsing(function ($timeout, $callback) use ($token) {
                        // Simulate token being refreshed by another process
                        // Token was expired, but now it's refreshed and still expiring soon but not expired
                        $token->update(['expires_at' => now()->addMinutes(10)]);
                        return $callback();
                    });
                return $mockLock;
            });

        $result = $this->coordinator->coordinateRefresh($this->user, 'google-drive');

        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->wasAlreadyValid);
        $this->assertTrue($result->wasRefreshedByAnotherProcess);
        $this->assertStringContainsString('another process', $result->message);
    }

    public function test_coordinate_refresh_performs_actual_refresh_when_token_expired(): void
    {
        // Create an expired token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->subMinutes(5),
            'refresh_token' => 'valid_refresh_token',
        ]);

        // Mock successful refresh
        $this->mockGoogleDriveService
            ->shouldReceive('validateAndRefreshToken')
            ->once()
            ->with($this->user)
            ->andReturn(true);

        Cache::shouldReceive('lock')
            ->once()
            ->with("token_refresh_{$this->user->id}_google-drive", 30)
            ->andReturnUsing(function ($key, $ttl) {
                $mockLock = Mockery::mock();
                $mockLock->shouldReceive('block')
                    ->once()
                    ->with(5, Mockery::type('callable'))
                    ->andReturnUsing(function ($timeout, $callback) {
                        return $callback();
                    });
                return $mockLock;
            });

        $result = $this->coordinator->coordinateRefresh($this->user, 'google-drive');

        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->wasAlreadyValid);
        $this->assertFalse($result->wasRefreshedByAnotherProcess);
        $this->assertTrue($result->wasTokenRefreshed());
        $this->assertEquals('Token refreshed successfully', $result->message);
    }

    public function test_coordinate_refresh_handles_lock_timeout(): void
    {
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->subMinutes(5),
            'refresh_token' => 'valid_refresh_token',
        ]);

        Cache::shouldReceive('lock')
            ->once()
            ->with("token_refresh_{$this->user->id}_google-drive", 30)
            ->andReturnUsing(function ($key, $ttl) {
                $mockLock = Mockery::mock();
                $mockLock->shouldReceive('block')
                    ->once()
                    ->with(5, Mockery::type('callable'))
                    ->andThrow(new LockTimeoutException());
                return $mockLock;
            });

        $result = $this->coordinator->coordinateRefresh($this->user, 'google-drive');

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(TokenRefreshErrorType::UNKNOWN_ERROR, $result->getErrorType());
        $this->assertStringContainsString('timed out', $result->message);
    }

    public function test_coordinate_refresh_handles_refresh_service_exception(): void
    {
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->subMinutes(5),
            'refresh_token' => 'valid_refresh_token',
        ]);

        $exception = new Exception('Invalid refresh token');
        
        $this->mockGoogleDriveService
            ->shouldReceive('validateAndRefreshToken')
            ->once()
            ->with($this->user)
            ->andThrow($exception);

        Cache::shouldReceive('lock')
            ->once()
            ->with("token_refresh_{$this->user->id}_google-drive", 30)
            ->andReturnUsing(function ($key, $ttl) {
                $mockLock = Mockery::mock();
                $mockLock->shouldReceive('block')
                    ->once()
                    ->with(5, Mockery::type('callable'))
                    ->andReturnUsing(function ($timeout, $callback) {
                        return $callback();
                    });
                return $mockLock;
            });

        $result = $this->coordinator->coordinateRefresh($this->user, 'google-drive');

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result->getErrorType());
        $this->assertEquals($exception, $result->getException());
    }

    public function test_coordinate_refresh_returns_failure_when_no_token_exists(): void
    {
        // No token exists for this user

        Cache::shouldReceive('lock')
            ->once()
            ->with("token_refresh_{$this->user->id}_google-drive", 30)
            ->andReturnUsing(function ($key, $ttl) {
                $mockLock = Mockery::mock();
                $mockLock->shouldReceive('block')
                    ->once()
                    ->with(5, Mockery::type('callable'))
                    ->andReturnUsing(function ($timeout, $callback) {
                        return $callback();
                    });
                return $mockLock;
            });

        $result = $this->coordinator->coordinateRefresh($this->user, 'google-drive');

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result->getErrorType());
        $this->assertStringContainsString('No authentication token found', $result->message);
    }

    public function test_coordinate_refresh_handles_refresh_service_returning_false(): void
    {
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->subMinutes(5),
            'refresh_token' => 'valid_refresh_token',
        ]);

        $this->mockGoogleDriveService
            ->shouldReceive('validateAndRefreshToken')
            ->once()
            ->with($this->user)
            ->andReturn(false);

        Cache::shouldReceive('lock')
            ->once()
            ->with("token_refresh_{$this->user->id}_google-drive", 30)
            ->andReturnUsing(function ($key, $ttl) {
                $mockLock = Mockery::mock();
                $mockLock->shouldReceive('block')
                    ->once()
                    ->with(5, Mockery::type('callable'))
                    ->andReturnUsing(function ($timeout, $callback) {
                        return $callback();
                    });
                return $mockLock;
            });

        $result = $this->coordinator->coordinateRefresh($this->user, 'google-drive');

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(TokenRefreshErrorType::UNKNOWN_ERROR, $result->getErrorType());
        $this->assertStringContainsString('unknown reason', $result->message);
    }

    public function test_is_refresh_in_progress_returns_true_when_lock_exists(): void
    {
        Cache::shouldReceive('lock')
            ->once()
            ->with("token_refresh_{$this->user->id}_google-drive", 1)
            ->andReturnUsing(function ($key, $ttl) {
                $mockLock = Mockery::mock();
                $mockLock->shouldReceive('get')
                    ->once()
                    ->andReturn(false); // Can't get lock, so refresh is in progress
                return $mockLock;
            });

        $result = $this->coordinator->isRefreshInProgress($this->user, 'google-drive');

        $this->assertTrue($result);
    }

    public function test_is_refresh_in_progress_returns_false_when_no_lock_exists(): void
    {
        Cache::shouldReceive('lock')
            ->once()
            ->with("token_refresh_{$this->user->id}_google-drive", 1)
            ->andReturnUsing(function ($key, $ttl) {
                $mockLock = Mockery::mock();
                $mockLock->shouldReceive('get')
                    ->once()
                    ->andReturn(true); // Got lock successfully
                $mockLock->shouldReceive('release')
                    ->once();
                return $mockLock;
            });

        $result = $this->coordinator->isRefreshInProgress($this->user, 'google-drive');

        $this->assertFalse($result);
    }

    public function test_classify_refresh_error_identifies_network_timeout(): void
    {
        $coordinator = new TokenRefreshCoordinator($this->mockGoogleDriveService);
        $reflection = new \ReflectionClass($coordinator);
        $method = $reflection->getMethod('classifyRefreshError');
        $method->setAccessible(true);

        $exception = new Exception('Connection timeout occurred');
        $result = $method->invoke($coordinator, $exception);

        $this->assertEquals(TokenRefreshErrorType::NETWORK_TIMEOUT, $result);
    }

    public function test_classify_refresh_error_identifies_invalid_refresh_token(): void
    {
        $coordinator = new TokenRefreshCoordinator($this->mockGoogleDriveService);
        $reflection = new \ReflectionClass($coordinator);
        $method = $reflection->getMethod('classifyRefreshError');
        $method->setAccessible(true);

        $exception = new Exception('invalid_grant: Invalid refresh token');
        $result = $method->invoke($coordinator, $exception);

        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result);
    }

    public function test_classify_refresh_error_identifies_expired_refresh_token(): void
    {
        $coordinator = new TokenRefreshCoordinator($this->mockGoogleDriveService);
        $reflection = new \ReflectionClass($coordinator);
        $method = $reflection->getMethod('classifyRefreshError');
        $method->setAccessible(true);

        $exception = new Exception('Refresh token has expired');
        $result = $method->invoke($coordinator, $exception);

        $this->assertEquals(TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN, $result);
    }

    public function test_classify_refresh_error_identifies_api_quota_exceeded(): void
    {
        $coordinator = new TokenRefreshCoordinator($this->mockGoogleDriveService);
        $reflection = new \ReflectionClass($coordinator);
        $method = $reflection->getMethod('classifyRefreshError');
        $method->setAccessible(true);

        $exception = new Exception('Quota exceeded for this request');
        $result = $method->invoke($coordinator, $exception);

        $this->assertEquals(TokenRefreshErrorType::API_QUOTA_EXCEEDED, $result);
    }

    public function test_classify_refresh_error_identifies_service_unavailable(): void
    {
        $coordinator = new TokenRefreshCoordinator($this->mockGoogleDriveService);
        $reflection = new \ReflectionClass($coordinator);
        $method = $reflection->getMethod('classifyRefreshError');
        $method->setAccessible(true);

        $exception = new Exception('Service temporarily unavailable');
        $result = $method->invoke($coordinator, $exception);

        $this->assertEquals(TokenRefreshErrorType::SERVICE_UNAVAILABLE, $result);
    }

    public function test_classify_refresh_error_defaults_to_unknown_error(): void
    {
        $coordinator = new TokenRefreshCoordinator($this->mockGoogleDriveService);
        $reflection = new \ReflectionClass($coordinator);
        $method = $reflection->getMethod('classifyRefreshError');
        $method->setAccessible(true);

        $exception = new Exception('Some unexpected error message');
        $result = $method->invoke($coordinator, $exception);

        $this->assertEquals(TokenRefreshErrorType::UNKNOWN_ERROR, $result);
    }
}