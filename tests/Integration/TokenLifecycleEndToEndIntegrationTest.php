<?php

namespace Tests\Integration;

use App\Enums\TokenRefreshErrorType;
use App\Jobs\RefreshTokenJob;
use App\Jobs\UploadToGoogleDrive;
use App\Mail\ConnectionRestoredMail;
use App\Mail\TokenExpiredMail;
use App\Mail\TokenRefreshFailedMail;
use App\Models\CloudStorageHealthStatus;
use App\Models\FileUpload;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\ConnectionRecoveryService;
use App\Services\GoogleDriveService;
use App\Services\ProactiveTokenRenewalService;
use App\Services\RealTimeHealthValidator;
use App\Services\TokenRefreshCoordinator;
use App\Services\TokenRenewalNotificationService;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * End-to-end integration tests for the complete token lifecycle management system.
 * 
 * These tests verify the entire flow from token expiration through automatic renewal,
 * upload job behavior, dashboard status accuracy, notification delivery, and recovery.
 */
class TokenLifecycleEndToEndIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private GoogleDriveService $driveService;
    private CloudStorageHealthService $healthService;
    private ProactiveTokenRenewalService $renewalService;
    private TokenRefreshCoordinator $coordinator;
    private RealTimeHealthValidator $validator;
    private ConnectionRecoveryService $recoveryService;
    private TokenRenewalNotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->driveService = app(GoogleDriveService::class);
        $this->healthService = app(CloudStorageHealthService::class);
        $this->renewalService = app(ProactiveTokenRenewalService::class);
        $this->coordinator = app(TokenRefreshCoordinator::class);
        $this->validator = app(RealTimeHealthValidator::class);
        $this->recoveryService = app(ConnectionRecoveryService::class);
        $this->notificationService = app(TokenRenewalNotificationService::class);

        Storage::fake('public');
        Mail::fake();
        Queue::fake();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function complete_token_refresh_flow_from_expiration_to_renewal(): void
    {
        // Arrange - Create expired token with valid refresh token
        $expiredToken = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
            'refresh_failure_count' => 0,
            'requires_user_intervention' => false,
        ]);

        // Mock successful token refresh
        $this->mockSuccessfulTokenRefresh();

        // Act - Trigger proactive token renewal
        $result = $this->renewalService->refreshTokenIfNeeded($this->user, 'google-drive');

        // Assert - Token should be refreshed successfully
        $this->assertTrue($result->isSuccessful());
        
        $refreshedToken = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('new_access_token', $refreshedToken->access_token);
        $this->assertEquals('new_refresh_token', $refreshedToken->refresh_token);
        $this->assertNotNull($refreshedToken->last_successful_refresh_at);
        $this->assertEquals(0, $refreshedToken->refresh_failure_count);
        $this->assertFalse($refreshedToken->requires_user_intervention);

        // Verify health status is updated
        $healthStatus = $this->healthService->checkConnectionHealth($this->user, 'google-drive');
        $this->assertEquals('healthy', $healthStatus->consolidated_status);
        $this->assertTrue($healthStatus->isTokenRefreshWorking());
        $this->assertEquals(0, $healthStatus->token_refresh_failures);
    }

    #[Test]
    public function upload_job_behavior_with_expired_tokens_and_automatic_refresh(): void
    {
        // Arrange - Create expired token and file upload
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => $this->user->id,
            'filename' => 'test-file.txt',
            'original_filename' => 'test-file.txt',
        ]);

        Storage::disk('public')->put('uploads/test-file.txt', 'test content');

        // Mock successful token refresh and file upload
        $this->mockSuccessfulTokenRefreshAndUpload();

        // Act - Process upload job
        $job = new UploadToGoogleDrive($fileUpload);
        $job->handle(
            app(\App\Services\CloudStorageManager::class),
            $this->healthService,
            app(\App\Services\CloudStorageLogService::class)
        );

        // Assert - Upload should succeed after automatic token refresh
        $fileUpload->refresh();
        $this->assertEquals('uploaded_file_id', $fileUpload->google_drive_file_id);
        $this->assertEquals('google-drive', $fileUpload->cloud_storage_provider);
        $this->assertNull($fileUpload->cloud_storage_error_type);

        // Verify token was refreshed
        $token = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('new_access_token', $token->access_token);
        $this->assertNotNull($token->last_successful_refresh_at);
    }

    #[Test]
    public function dashboard_status_accuracy_during_token_refresh_scenarios(): void
    {
        // Scenario 1: Expired token that can be refreshed
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->mockSuccessfulTokenRefresh();

        $healthStatus = $this->validator->validateConnectionHealth($this->user, 'google-drive');
        $this->assertTrue($healthStatus->isHealthy());
        $this->assertEquals('Connection is working properly', $healthStatus->getMessage());

        // Scenario 2: Expired refresh token requiring re-authentication
        $this->user->googleDriveTokens()->delete();
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'expired_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->mockFailedTokenRefresh();

        $healthStatus = $this->validator->validateConnectionHealth($this->user, 'google-drive');
        $this->assertFalse($healthStatus->isHealthy());
        $this->assertStringContains('authentication', strtolower($healthStatus->getMessage()));

        // Scenario 3: Network issues during refresh
        $this->user->googleDriveTokens()->delete();
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->mockNetworkErrorDuringRefresh();

        $healthStatus = $this->validator->validateConnectionHealth($this->user, 'google-drive');
        $this->assertFalse($healthStatus->isHealthy());
        $this->assertStringContains('connection', strtolower($healthStatus->getMessage()));
    }

    #[Test]
    public function notification_delivery_and_throttling_behavior(): void
    {
        // Test immediate notification for expired refresh token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'expired_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
            'last_notification_sent_at' => null,
        ]);

        $this->mockFailedTokenRefresh();

        // Trigger notification
        $this->notificationService->sendTokenExpiredNotification($this->user, 'google-drive');

        // Assert notification was sent
        Mail::assertSent(TokenExpiredMail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });

        // Test throttling - second notification within 24 hours should be blocked
        $this->notificationService->sendTokenExpiredNotification($this->user, 'google-drive');

        // Should still only have one notification
        Mail::assertSentTimes(TokenExpiredMail::class, 1);

        // Test notification after throttling period
        $token = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $token->update(['last_notification_sent_at' => Carbon::now()->subHours(25)]);

        $this->notificationService->sendTokenExpiredNotification($this->user, 'google-drive');

        // Should send second notification
        Mail::assertSentTimes(TokenExpiredMail::class, 2);
    }

    #[Test]
    public function concurrent_user_scenarios_and_race_condition_handling(): void
    {
        // Create expired token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->mockSuccessfulTokenRefresh();

        // Simulate concurrent refresh attempts
        $lockKey = "token_refresh_{$this->user->id}_google-drive";
        
        // First process acquires lock
        $lock1 = Cache::lock($lockKey, 30);
        $this->assertTrue($lock1->get());

        // Second process should wait and then find token already refreshed
        $result1 = $this->coordinator->coordinateRefresh($this->user, 'google-drive');
        $this->assertTrue($result1->isSuccessful());

        // Release first lock
        $lock1->release();

        // Third process should find token already valid
        $result2 = $this->coordinator->coordinateRefresh($this->user, 'google-drive');
        $this->assertTrue($result2->wasAlreadyValid);

        // Verify only one actual refresh occurred
        $token = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('new_access_token', $token->access_token);
    }

    #[Test]
    public function automatic_recovery_of_pending_uploads_after_connection_restoration(): void
    {
        // Create failed uploads due to connection issues
        $failedUpload1 = FileUpload::factory()->create([
            'company_user_id' => $this->user->id,
            'filename' => 'failed-upload-1.txt',
            'original_filename' => 'Failed Upload 1.txt',
            'cloud_storage_error_type' => 'connection_error',
            'recovery_attempts' => 1,
        ]);

        $failedUpload2 = FileUpload::factory()->create([
            'company_user_id' => $this->user->id,
            'filename' => 'failed-upload-2.txt',
            'original_filename' => 'Failed Upload 2.txt',
            'cloud_storage_error_type' => 'authentication_error',
            'recovery_attempts' => 0,
        ]);

        // Create test files
        Storage::disk('public')->put('uploads/failed-upload-1.txt', 'content 1');
        Storage::disk('public')->put('uploads/failed-upload-2.txt', 'content 2');

        // Create valid token (connection restored)
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->addHours(1),
        ]);

        $this->mockSuccessfulApiConnectivity();

        // Act - Attempt automatic recovery
        $result = $this->recoveryService->attemptAutomaticRecovery($this->user, 'google-drive');

        // Assert - Recovery should succeed
        $this->assertTrue($result->isSuccessful());

        // Verify retry jobs were dispatched
        Queue::assertPushed(\App\Jobs\PendingUploadRetryJob::class, 2);

        // Verify connection restored notification
        Mail::assertSent(ConnectionRestoredMail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }

    #[Test]
    public function token_refresh_failure_escalation_and_notification(): void
    {
        // Create token with multiple previous failures
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'problematic_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
            'refresh_failure_count' => 2,
            'last_refresh_attempt_at' => Carbon::now()->subMinutes(10),
        ]);

        $this->mockFailedTokenRefresh();

        // Act - Attempt refresh (should be 3rd failure)
        $result = $this->renewalService->refreshTokenIfNeeded($this->user, 'google-drive');

        // Assert - Should fail and trigger notifications
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN, $result->getErrorType());

        // Verify failure count increased
        $token = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals(3, $token->refresh_failure_count);
        $this->assertTrue($token->requires_user_intervention);

        // Verify notification was sent
        Mail::assertSent(TokenRefreshFailedMail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });

        // Verify health status reflects the issue
        $healthStatus = $this->healthService->checkConnectionHealth($this->user, 'google-drive');
        $this->assertEquals('authentication_required', $healthStatus->consolidated_status);
        $this->assertFalse($healthStatus->isTokenRefreshWorking());
    }

    #[Test]
    public function proactive_token_refresh_scheduling_and_execution(): void
    {
        // Create token expiring in 10 minutes (within proactive refresh window)
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'soon_to_expire_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->addMinutes(10),
            'proactive_refresh_scheduled_at' => null,
        ]);

        $this->mockSuccessfulTokenRefresh();

        // Act - Check if proactive refresh is needed
        $result = $this->renewalService->refreshTokenIfNeeded($this->user, 'google-drive');

        // Assert - Should trigger proactive refresh
        $this->assertTrue($result->isSuccessful());
        $this->assertTrue($result->wasTokenRefreshed());

        // Verify token was refreshed
        $token = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('new_access_token', $token->access_token);
        $this->assertNotNull($token->last_successful_refresh_at);
        $this->assertNotNull($token->proactive_refresh_scheduled_at);
    }

    #[Test]
    public function upload_job_retry_coordination_with_token_refresh(): void
    {
        // Create expired token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => $this->user->id,
            'filename' => 'retry-test.txt',
            'original_filename' => 'Retry Test.txt',
        ]);

        Storage::disk('public')->put('uploads/retry-test.txt', 'retry content');

        // Mock token refresh success but initial upload failure, then success on retry
        $this->mockTokenRefreshSuccessWithUploadRetry();

        // Act - Process upload job
        $job = new UploadToGoogleDrive($fileUpload);
        $job->handle(
            app(\App\Services\CloudStorageManager::class),
            $this->healthService,
            app(\App\Services\CloudStorageLogService::class)
        );

        // Assert - Upload should eventually succeed
        $fileUpload->refresh();
        $this->assertEquals('retry_success_file_id', $fileUpload->google_drive_file_id);
        $this->assertNull($fileUpload->cloud_storage_error_type);

        // Verify token was refreshed
        $token = GoogleDriveToken::where('user_id', $this->user->id)->first();
        $this->assertEquals('new_access_token', $token->access_token);
    }

    #[Test]
    public function health_status_cache_invalidation_during_token_operations(): void
    {
        // Create expired token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => Carbon::now()->subMinutes(30),
        ]);

        // Initial health check should show unhealthy status
        $this->mockFailedApiConnectivity();
        $healthStatus1 = $this->validator->validateConnectionHealth($this->user, 'google-drive');
        $this->assertFalse($healthStatus1->isHealthy());

        // Refresh token
        $this->mockSuccessfulTokenRefresh();
        $result = $this->renewalService->refreshTokenIfNeeded($this->user, 'google-drive');
        $this->assertTrue($result->isSuccessful());

        // Health check should now show healthy status (cache should be invalidated)
        $this->mockSuccessfulApiConnectivity();
        $healthStatus2 = $this->validator->validateConnectionHealth($this->user, 'google-drive');
        $this->assertTrue($healthStatus2->isHealthy());

        // Verify cache was properly invalidated and updated
        $cacheKey = "health_status_{$this->user->id}_google-drive";
        $cachedStatus = Cache::get($cacheKey);
        $this->assertNotNull($cachedStatus);
        $this->assertTrue($cachedStatus->isHealthy());
    }

    // Mock helper methods

    private function mockSuccessfulTokenRefresh(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(true)->once();
        $mockClient->shouldReceive('getRefreshToken')->andReturn('valid_refresh_token')->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->with('valid_refresh_token')
            ->andReturn([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 3600,
            ])
            ->once();
        $mockClient->shouldReceive('getAccessToken')->andReturn('new_access_token')->atLeast()->once();

        $this->replaceDriveServiceClient($mockClient);
    }

    private function mockFailedTokenRefresh(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(true)->once();
        $mockClient->shouldReceive('getRefreshToken')->andReturn('expired_refresh_token')->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->andThrow(new \Exception('invalid_grant: Token has been expired or revoked'))
            ->once();

        $this->replaceDriveServiceClient($mockClient);
    }

    private function mockNetworkErrorDuringRefresh(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(true)->once();
        $mockClient->shouldReceive('getRefreshToken')->andReturn('valid_refresh_token')->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->andThrow(new \Exception('Network error: Connection timeout'))
            ->once();

        $this->replaceDriveServiceClient($mockClient);
    }

    private function mockSuccessfulTokenRefreshAndUpload(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->atLeast()->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(true, false)->atLeast()->once();
        $mockClient->shouldReceive('getRefreshToken')->andReturn('valid_refresh_token')->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->andReturn([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 3600,
            ])
            ->once();
        $mockClient->shouldReceive('getAccessToken')->andReturn('new_access_token')->atLeast()->once();

        $mockDrive = Mockery::mock(Drive::class);
        $mockFiles = Mockery::mock();
        $mockFiles->shouldReceive('create')->andReturn((object)['id' => 'uploaded_file_id'])->once();
        $mockDrive->files = $mockFiles;

        $this->replaceDriveServiceMocks($mockClient, $mockDrive);
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

    private function mockTokenRefreshSuccessWithUploadRetry(): void
    {
        $mockClient = Mockery::mock(GoogleClient::class);
        $mockClient->shouldReceive('setAccessToken')->atLeast()->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(true, false)->atLeast()->once();
        $mockClient->shouldReceive('getRefreshToken')->andReturn('valid_refresh_token')->once();
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->andReturn([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 3600,
            ])
            ->once();
        $mockClient->shouldReceive('getAccessToken')->andReturn('new_access_token')->atLeast()->once();

        $mockDrive = Mockery::mock(Drive::class);
        $mockFiles = Mockery::mock();
        // First upload attempt fails, second succeeds
        $mockFiles->shouldReceive('create')
            ->andThrow(new \Exception('Temporary upload error'))
            ->once();
        $mockFiles->shouldReceive('create')
            ->andReturn((object)['id' => 'retry_success_file_id'])
            ->once();
        $mockDrive->files = $mockFiles;

        $this->replaceDriveServiceMocks($mockClient, $mockDrive);
    }

    private function replaceDriveServiceClient(GoogleClient $mockClient): void
    {
        $reflection = new \ReflectionClass($this->driveService);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->driveService, $mockClient);
    }

    private function replaceDriveServiceMocks(GoogleClient $mockClient, ?Drive $mockDrive = null): void
    {
        $reflection = new \ReflectionClass($this->driveService);
        
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->driveService, $mockClient);

        if ($mockDrive) {
            $driveProperty = $reflection->getProperty('drive');
            $driveProperty->setAccessible(true);
            $driveProperty->setValue($this->driveService, $mockDrive);
        }
    }
}