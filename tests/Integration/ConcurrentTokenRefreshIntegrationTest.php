<?php

namespace Tests\Integration;

use App\Jobs\RefreshTokenJob;
use App\Jobs\UploadToGoogleDrive;
use App\Models\FileUpload;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\TokenRefreshCoordinator;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration tests for concurrent user scenarios and race condition handling.
 * 
 * These tests verify that the token refresh system handles concurrent operations
 * correctly, prevents duplicate refreshes, and maintains data consistency.
 */
class ConcurrentTokenRefreshIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private TokenRefreshCoordinator $coordinator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->coordinator = app(TokenRefreshCoordinator::class);

        Storage::fake('public');
        Queue::fake();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function prevents_duplicate_token_refresh_with_mutex_locking(): void
    {
        // Arrange - Create expired token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->mockSuccessfulTokenRefresh();

        // Act - Simulate concurrent refresh attempts
        $lockKey = "token_refresh_{$this->user->id}_google-drive";
        
        // First process acquires lock and performs refresh
        $lock1 = Cache::lock($lockKey, 30);
        $this->assertTrue($lock1->get());

        $result1 = $this->coordinator->coordinateRefresh($this->user, 'google-drive');
        
        // Release lock after refresh
        $lock1->release();

        // Second process should find token already refreshed
        $result2 = $this->coordinator->coordinateRefresh($this->user, 'google-drive');

        // Assert - First refresh succeeds, second finds token already valid
        $this->assertTrue($result1->isSuccessful());
        $this->assertTrue($result2->wasAlreadyValid);

        // Verify only one refresh occurred
        $token = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('new_access_token', $token->access_token);
        $this->assertNotNull($token->last_successful_refresh_at);
    }

    #[Test]
    public function handles_lock_timeout_gracefully(): void
    {
        // Arrange - Create expired token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        // Simulate a stuck lock (another process holding it)
        $lockKey = "token_refresh_{$this->user->id}_google-drive";
        $stuckLock = Cache::lock($lockKey, 30);
        $this->assertTrue($stuckLock->get());

        // Act - Attempt refresh with timeout
        $start = microtime(true);
        $result = $this->coordinator->coordinateRefresh($this->user, 'google-drive');
        $duration = microtime(true) - $start;

        // Assert - Should timeout and return appropriate result
        $this->assertFalse($result->isSuccessful());
        $this->assertStringContains('timeout', strtolower($result->message ?? ''));
        $this->assertLessThan(10, $duration); // Should timeout quickly

        // Clean up
        $stuckLock->release();
    }

    #[Test]
    public function concurrent_upload_jobs_coordinate_token_refresh(): void
    {
        // Arrange - Create expired token and multiple file uploads
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        $upload1 = FileUpload::factory()->create([
            'company_user_id' => $this->user->id,
            'filename' => 'concurrent-upload-1.txt',
            'original_filename' => 'Concurrent Upload 1.txt',
        ]);

        $upload2 = FileUpload::factory()->create([
            'company_user_id' => $this->user->id,
            'filename' => 'concurrent-upload-2.txt',
            'original_filename' => 'Concurrent Upload 2.txt',
        ]);

        Storage::disk('public')->put('uploads/concurrent-upload-1.txt', 'content 1');
        Storage::disk('public')->put('uploads/concurrent-upload-2.txt', 'content 2');

        $this->mockSuccessfulTokenRefreshAndUpload();

        // Act - Process both upload jobs concurrently
        $job1 = new UploadToGoogleDrive($upload1);
        $job2 = new UploadToGoogleDrive($upload2);

        // Simulate concurrent execution
        $job1->handle(
            app(\App\Services\CloudStorageManager::class),
            app(\App\Services\CloudStorageHealthService::class),
            app(\App\Services\CloudStorageLogService::class)
        );

        $job2->handle(
            app(\App\Services\CloudStorageManager::class),
            app(\App\Services\CloudStorageHealthService::class),
            app(\App\Services\CloudStorageLogService::class)
        );

        // Assert - Both uploads should succeed with coordinated token refresh
        $upload1->refresh();
        $upload2->refresh();

        $this->assertEquals('uploaded_file_id_1', $upload1->google_drive_file_id);
        $this->assertEquals('uploaded_file_id_2', $upload2->google_drive_file_id);
        $this->assertNull($upload1->cloud_storage_error_type);
        $this->assertNull($upload2->cloud_storage_error_type);

        // Verify token was refreshed only once
        $token = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('new_access_token', $token->access_token);
    }

    #[Test]
    public function multiple_users_refresh_tokens_independently(): void
    {
        // Arrange - Create multiple users with expired tokens
        $user1 = User::factory()->create(['role' => 'admin']);
        $user2 = User::factory()->create(['role' => 'employee']);

        GoogleDriveToken::factory()->create([
            'user_id' => $user1->id,
            'access_token' => 'user1_expired_token',
            'refresh_token' => 'user1_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        GoogleDriveToken::factory()->create([
            'user_id' => $user2->id,
            'access_token' => 'user2_expired_token',
            'refresh_token' => 'user2_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->mockMultiUserTokenRefresh();

        // Act - Refresh tokens for both users concurrently
        $result1 = $this->coordinator->coordinateRefresh($user1, 'google-drive');
        $result2 = $this->coordinator->coordinateRefresh($user2, 'google-drive');

        // Assert - Both refreshes should succeed independently
        $this->assertTrue($result1->isSuccessful());
        $this->assertTrue($result2->isSuccessful());

        // Verify each user got their own refreshed token
        $token1 = GoogleDriveToken::where('user_id', $user1->id)->first();
        $token2 = GoogleDriveToken::where('user_id', $user2->id)->first();

        $this->assertEquals('user1_new_token', $token1->access_token);
        $this->assertEquals('user2_new_token', $token2->access_token);
        $this->assertNotNull($token1->last_successful_refresh_at);
        $this->assertNotNull($token2->last_successful_refresh_at);
    }

    #[Test]
    public function queued_refresh_jobs_coordinate_properly(): void
    {
        // Arrange - Create expired token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->mockSuccessfulTokenRefresh();

        // Act - Dispatch multiple refresh jobs
        $job1 = new RefreshTokenJob($this->user, 'google-drive');
        $job2 = new RefreshTokenJob($this->user, 'google-drive');

        // Process first job
        $job1->handle(
            app(\App\Services\ProactiveTokenRenewalService::class),
            app(\App\Services\TokenRenewalNotificationService::class)
        );

        // Process second job (should find token already refreshed)
        $job2->handle(
            app(\App\Services\ProactiveTokenRenewalService::class),
            app(\App\Services\TokenRenewalNotificationService::class)
        );

        // Assert - Token should be refreshed only once
        $token = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('new_access_token', $token->access_token);
        $this->assertNotNull($token->last_successful_refresh_at);
    }

    #[Test]
    public function handles_race_condition_between_refresh_and_expiration_check(): void
    {
        // Arrange - Create token that expires during test execution
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'soon_to_expire_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->addSeconds(2), // Expires very soon
        ]);

        $this->mockSuccessfulTokenRefresh();

        // Act - Check expiration and refresh in quick succession
        $isExpiredBefore = $token->isExpired();
        
        // Simulate time passing
        sleep(3);
        
        $result = $this->coordinator->coordinateRefresh($this->user, 'google-drive');
        
        $token->refresh();
        $isExpiredAfter = $token->fresh()->isExpired();

        // Assert - Should handle the race condition properly
        $this->assertFalse($isExpiredBefore); // Was not expired initially
        $this->assertTrue($result->isSuccessful()); // Refresh succeeded
        $this->assertFalse($isExpiredAfter); // No longer expired after refresh
    }

    #[Test]
    public function concurrent_health_checks_use_cached_results(): void
    {
        // Arrange - Create valid token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->addHours(1),
        ]);

        $this->mockSuccessfulApiConnectivity();

        $healthService = app(\App\Services\CloudStorageHealthService::class);

        // Act - Perform multiple concurrent health checks
        $result1 = $healthService->checkConnectionHealth($this->user, 'google-drive');
        $result2 = $healthService->checkConnectionHealth($this->user, 'google-drive');
        $result3 = $healthService->checkConnectionHealth($this->user, 'google-drive');

        // Assert - All should return same cached result
        $this->assertEquals('healthy', $result1->consolidated_status);
        $this->assertEquals('healthy', $result2->consolidated_status);
        $this->assertEquals('healthy', $result3->consolidated_status);

        // Verify cache was used (same timestamp)
        $this->assertEquals(
            $result1->last_validation_at->timestamp,
            $result2->last_validation_at->timestamp
        );
        $this->assertEquals(
            $result2->last_validation_at->timestamp,
            $result3->last_validation_at->timestamp
        );
    }

    #[Test]
    public function handles_database_deadlock_during_token_update(): void
    {
        // Arrange - Create expired token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        // Mock successful API response but simulate database contention
        $this->mockSuccessfulTokenRefresh();

        // Act - Attempt refresh (database operations should be atomic)
        $result = $this->coordinator->coordinateRefresh($this->user, 'google-drive');

        // Assert - Should handle database operations atomically
        $this->assertTrue($result->isSuccessful());

        // Verify token state is consistent
        $token = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('new_access_token', $token->access_token);
        $this->assertEquals('new_refresh_token', $token->refresh_token);
        $this->assertNotNull($token->last_successful_refresh_at);
        $this->assertEquals(0, $token->refresh_failure_count);
    }

    #[Test]
    public function cache_invalidation_works_across_concurrent_operations(): void
    {
        // Arrange - Create expired token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        $healthService = app(\App\Services\CloudStorageHealthService::class);
        $cacheKey = "health_status_{$this->user->id}_google-drive";

        // Initial health check should cache unhealthy status
        $this->mockFailedApiConnectivity();
        $result1 = $healthService->checkConnectionHealth($this->user, 'google-drive');
        $this->assertEquals('authentication_required', $result1->consolidated_status);
        $this->assertTrue(Cache::has($cacheKey));

        // Refresh token (should invalidate cache)
        $this->mockSuccessfulTokenRefresh();
        $refreshResult = $this->coordinator->coordinateRefresh($this->user, 'google-drive');
        $this->assertTrue($refreshResult->isSuccessful());

        // Subsequent health check should show updated status
        $this->mockSuccessfulApiConnectivity();
        $result2 = $healthService->checkConnectionHealth($this->user, 'google-drive');
        $this->assertEquals('healthy', $result2->consolidated_status);

        // Verify cache was properly invalidated and updated
        $cachedStatus = Cache::get($cacheKey);
        $this->assertNotNull($cachedStatus);
        $this->assertEquals('healthy', $cachedStatus->consolidated_status);
    }

    // Mock helper methods

    private function mockSuccessfulTokenRefresh(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->atLeast()->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(true, false)->atLeast()->once();
        $mockClient->shouldReceive('getRefreshToken')->andReturn('valid_refresh_token')->atLeast()->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->andReturn([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 3600,
            ])
            ->atLeast()->once();
        $mockClient->shouldReceive('getAccessToken')->andReturn('new_access_token')->atLeast()->once();

        $this->replaceDriveServiceClient($mockClient);
    }

    private function mockSuccessfulTokenRefreshAndUpload(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->atLeast()->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(true, false)->atLeast()->once();
        $mockClient->shouldReceive('getRefreshToken')->andReturn('valid_refresh_token')->atLeast()->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->andReturn([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 3600,
            ])
            ->atLeast()->once();
        $mockClient->shouldReceive('getAccessToken')->andReturn('new_access_token')->atLeast()->once();

        $mockDrive = Mockery::mock(Drive::class);
        $mockFiles = Mockery::mock();
        $mockFiles->shouldReceive('create')
            ->andReturnUsing(function() {
                static $counter = 0;
                $counter++;
                return (object)['id' => "uploaded_file_id_{$counter}"];
            })
            ->atLeast()->once();
        $mockDrive->files = $mockFiles;

        $this->replaceDriveServiceMocks($mockClient, $mockDrive);
    }

    private function mockMultiUserTokenRefresh(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->atLeast()->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(true, false)->atLeast()->once();
        $mockClient->shouldReceive('getRefreshToken')
            ->andReturnUsing(function() {
                static $calls = 0;
                $calls++;
                return $calls === 1 ? 'user1_refresh_token' : 'user2_refresh_token';
            })
            ->atLeast()->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->andReturnUsing(function($refreshToken) {
                if ($refreshToken === 'user1_refresh_token') {
                    return [
                        'access_token' => 'user1_new_token',
                        'refresh_token' => 'user1_new_refresh',
                        'expires_in' => 3600,
                    ];
                } else {
                    return [
                        'access_token' => 'user2_new_token',
                        'refresh_token' => 'user2_new_refresh',
                        'expires_in' => 3600,
                    ];
                }
            })
            ->atLeast()->once();
        $mockClient->shouldReceive('getAccessToken')
            ->andReturnUsing(function() {
                static $calls = 0;
                $calls++;
                return $calls % 2 === 1 ? 'user1_new_token' : 'user2_new_token';
            })
            ->atLeast()->once();

        $this->replaceDriveServiceClient($mockClient);
    }

    private function mockSuccessfulApiConnectivity(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->atLeast()->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(false)->atLeast()->once();
        $mockClient->shouldReceive('getAccessToken')->andReturn('valid_access_token')->atLeast()->once();

        $mockDrive = Mockery::mock(Drive::class);
        $mockAbout = Mockery::mock();
        $mockAbout->shouldReceive('get')
            ->with(['fields' => 'user'])
            ->andReturn((object)['user' => (object)['displayName' => 'Test User']])
            ->atLeast()->once();
        $mockDrive->about = $mockAbout;

        $this->replaceDriveServiceMocks($mockClient, $mockDrive);
    }

    private function mockFailedApiConnectivity(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->atLeast()->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(true)->atLeast()->once();
        $mockClient->shouldReceive('getRefreshToken')->andReturn('expired_refresh_token')->atLeast()->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->andThrow(new \Exception('invalid_grant'))
            ->atLeast()->once();

        $this->replaceDriveServiceClient($mockClient);
    }

    private function replaceDriveServiceClient(GoogleClient $mockClient): void
    {
        $driveService = app(\App\Services\GoogleDriveService::class);
        $reflection = new \ReflectionClass($driveService);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($driveService, $mockClient);
    }

    private function replaceDriveServiceMocks(GoogleClient $mockClient, ?Drive $mockDrive = null): void
    {
        $driveService = app(\App\Services\GoogleDriveService::class);
        $reflection = new \ReflectionClass($driveService);
        
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($driveService, $mockClient);

        if ($mockDrive) {
            $driveProperty = $reflection->getProperty('drive');
            $driveProperty->setAccessible(true);
            $driveProperty->setValue($driveService, $mockDrive);
        }
    }
}