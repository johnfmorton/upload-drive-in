<?php

namespace Tests\Unit\Services;

use App\Enums\CloudStorageErrorType;
use App\Enums\RecoveryStrategy;
use App\Enums\TokenRefreshErrorType;
use App\Jobs\PendingUploadRetryJob;
use App\Models\FileUpload;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageManager;
use App\Services\ConnectionRecoveryService;
use App\Services\HealthStatus;
use App\Services\RealTimeHealthValidator;
use App\Services\RecoveryResult;
use App\Services\TokenRefreshCoordinator;
use App\Services\TokenRenewalNotificationService;
use App\Services\RefreshResult;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class ConnectionRecoveryServiceTest extends TestCase
{
    use DatabaseTransactions;

    private ConnectionRecoveryService $service;
    private $mockStorageManager;
    private $mockHealthService;
    private $mockNotificationService;
    private $mockHealthValidator;
    private $mockTokenCoordinator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockStorageManager = Mockery::mock(CloudStorageManager::class);
        $this->mockHealthService = Mockery::mock(CloudStorageHealthService::class);
        $this->mockNotificationService = Mockery::mock(TokenRenewalNotificationService::class);
        $this->mockHealthValidator = Mockery::mock(RealTimeHealthValidator::class);
        $this->mockTokenCoordinator = Mockery::mock(TokenRefreshCoordinator::class);

        $this->service = new ConnectionRecoveryService(
            $this->mockStorageManager,
            $this->mockHealthService,
            $this->mockNotificationService,
            $this->mockHealthValidator
        );

        // Mock the token coordinator in the container
        $this->app->instance(TokenRefreshCoordinator::class, $this->mockTokenCoordinator);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_attempt_automatic_recovery_when_connection_is_healthy()
    {
        // Arrange
        $user = User::factory()->create();
        $provider = 'google-drive';

        $healthyStatus = Mockery::mock(HealthStatus::class);
        $healthyStatus->shouldReceive('isHealthy')->andReturn(true);

        $this->mockHealthValidator
            ->shouldReceive('validateConnectionHealth')
            ->with($user, $provider)
            ->once()
            ->andReturn($healthyStatus);

        // Mock finding no pending uploads
        FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'storage_provider' => $provider,
            'google_drive_file_id' => 'already_uploaded'
        ]);

        // Act
        $result = $this->service->attemptAutomaticRecovery($user, $provider);

        // Assert
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(RecoveryStrategy::NO_ACTION_NEEDED, $result->getStrategy());
        $this->assertEquals(__('messages.recovery_connection_healthy'), $result->getMessage());
    }

    public function test_attempt_automatic_recovery_with_token_refresh_strategy()
    {
        // Arrange
        $user = User::factory()->create();
        $provider = 'google-drive';

        $unhealthyStatus = Mockery::mock(HealthStatus::class);
        $unhealthyStatus->shouldReceive('isHealthy')->andReturn(false);

        $this->mockHealthValidator
            ->shouldReceive('validateConnectionHealth')
            ->with($user, $provider)
            ->once()
            ->andReturn($unhealthyStatus);

        // Mock health service to return token expired error
        $this->mockHealthService
            ->shouldReceive('getHealthStatus')
            ->with($user, $provider)
            ->andReturn((object)['last_error' => 'Token expired']);

        // Mock storage manager and error handler
        $mockProvider = Mockery::mock();
        $mockProvider->shouldReceive('getProviderName')->andReturn($provider);
        
        $mockErrorHandler = Mockery::mock();
        $mockErrorHandler->shouldReceive('classifyError')
            ->andReturn(CloudStorageErrorType::TOKEN_EXPIRED);

        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($user)
            ->andReturn($mockProvider);

        $this->mockStorageManager
            ->shouldReceive('getErrorHandler')
            ->with($provider)
            ->andReturn($mockErrorHandler);

        // Mock successful token refresh
        $successfulRefresh = RefreshResult::success(null, 'Token refreshed');
        $this->mockTokenCoordinator
            ->shouldReceive('coordinateRefresh')
            ->with($user, $provider)
            ->once()
            ->andReturn($successfulRefresh);

        // Mock notification service
        $this->mockNotificationService
            ->shouldReceive('sendConnectionRestoredNotification')
            ->with($user, $provider)
            ->once();

        // Act
        $result = $this->service->attemptAutomaticRecovery($user, $provider);

        // Assert
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(RecoveryStrategy::TOKEN_REFRESH, $result->getStrategy());
        $this->assertEquals(__('messages.recovery_token_refreshed_successfully'), $result->getMessage());
    }

    public function test_attempt_automatic_recovery_with_failed_token_refresh()
    {
        // Arrange
        $user = User::factory()->create();
        $provider = 'google-drive';

        $unhealthyStatus = Mockery::mock(HealthStatus::class);
        $unhealthyStatus->shouldReceive('isHealthy')->andReturn(false);

        $this->mockHealthValidator
            ->shouldReceive('validateConnectionHealth')
            ->with($user, $provider)
            ->once()
            ->andReturn($unhealthyStatus);

        // Mock health service to return token expired error
        $this->mockHealthService
            ->shouldReceive('getHealthStatus')
            ->with($user, $provider)
            ->andReturn((object)['last_error' => 'Token expired']);

        // Mock storage manager and error handler
        $mockProvider = Mockery::mock();
        $mockProvider->shouldReceive('getProviderName')->andReturn($provider);
        
        $mockErrorHandler = Mockery::mock();
        $mockErrorHandler->shouldReceive('classifyError')
            ->andReturn(CloudStorageErrorType::TOKEN_EXPIRED);

        $this->mockStorageManager
            ->shouldReceive('getUserProvider')
            ->with($user)
            ->andReturn($mockProvider);

        $this->mockStorageManager
            ->shouldReceive('getErrorHandler')
            ->with($provider)
            ->andReturn($mockErrorHandler);

        // Mock failed token refresh
        $failedRefresh = RefreshResult::failure('Refresh token expired', TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN, null);
        $this->mockTokenCoordinator
            ->shouldReceive('coordinateRefresh')
            ->with($user, $provider)
            ->once()
            ->andReturn($failedRefresh);

        // Mock notification service for failure
        $this->mockNotificationService
            ->shouldReceive('sendRefreshFailureNotification')
            ->with($user, $provider, TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN, 1, Mockery::any())
            ->once();

        // Act
        $result = $this->service->attemptAutomaticRecovery($user, $provider);

        // Assert
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(RecoveryStrategy::TOKEN_REFRESH, $result->getStrategy());
        $this->assertStringContains(__('messages.recovery_token_refresh_failed'), $result->getMessage());
    }

    public function test_retry_pending_uploads_with_valid_uploads()
    {
        // Arrange
        Queue::fake();
        
        $user = User::factory()->create();
        $provider = 'google-drive';

        // Create pending uploads
        $upload1 = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'storage_provider' => $provider,
            'google_drive_file_id' => null,
            'filename' => 'test1.txt'
        ]);

        $upload2 = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'storage_provider' => $provider,
            'google_drive_file_id' => null,
            'filename' => 'test2.txt'
        ]);

        // Create actual files for testing
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('uploads/' . $upload1->filename, 'test content 1');
        \Illuminate\Support\Facades\Storage::disk('public')->put('uploads/' . $upload2->filename, 'test content 2');

        // Act
        $this->service->retryPendingUploads($user, $provider);

        // Assert
        Queue::assertPushed(PendingUploadRetryJob::class, 2);
    }

    public function test_retry_pending_uploads_skips_missing_files()
    {
        // Arrange
        Queue::fake();
        Log::shouldReceive('info')->andReturn();
        Log::shouldReceive('warning')->andReturn();
        
        $user = User::factory()->create();
        $provider = 'google-drive';

        // Create upload with missing local file
        $upload = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'storage_provider' => $provider,
            'google_drive_file_id' => null,
            'filename' => 'missing.txt'
        ]);

        // Act
        $this->service->retryPendingUploads($user, $provider);

        // Assert
        Queue::assertNotPushed(PendingUploadRetryJob::class);
        
        // Verify the upload was marked with recovery status
        $upload->refresh();
        $this->assertNotNull($upload->error_details);
    }

    public function test_retry_pending_uploads_skips_exceeded_retry_limits()
    {
        // Arrange
        Queue::fake();
        Log::shouldReceive('info')->andReturn();
        Log::shouldReceive('warning')->andReturn();
        
        $user = User::factory()->create();
        $provider = 'google-drive';

        // Create upload that exceeded retry limits
        $upload = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'storage_provider' => $provider,
            'google_drive_file_id' => null,
            'retry_count' => 5, // Exceeds default limit
            'recovery_attempts' => 6 // Exceeds default limit
        ]);

        // Act
        $this->service->retryPendingUploads($user, $provider);

        // Assert
        Queue::assertNotPushed(PendingUploadRetryJob::class);
    }

    public function test_determine_recovery_strategy_for_different_error_types()
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('determineRecoveryStrategy');
        $method->setAccessible(true);

        // Test token expired error
        $strategy = $method->invoke($this->service, CloudStorageErrorType::TOKEN_EXPIRED);
        $this->assertEquals(RecoveryStrategy::TOKEN_REFRESH, $strategy);

        // Test network error
        $strategy = $method->invoke($this->service, CloudStorageErrorType::NETWORK_ERROR);
        $this->assertEquals(RecoveryStrategy::NETWORK_RETRY, $strategy);

        // Test quota exceeded
        $strategy = $method->invoke($this->service, CloudStorageErrorType::API_QUOTA_EXCEEDED);
        $this->assertEquals(RecoveryStrategy::QUOTA_WAIT, $strategy);

        // Test service unavailable
        $strategy = $method->invoke($this->service, CloudStorageErrorType::SERVICE_UNAVAILABLE);
        $this->assertEquals(RecoveryStrategy::SERVICE_RETRY, $strategy);

        // Test insufficient permissions
        $strategy = $method->invoke($this->service, CloudStorageErrorType::INSUFFICIENT_PERMISSIONS);
        $this->assertEquals(RecoveryStrategy::USER_INTERVENTION_REQUIRED, $strategy);

        // Test null error type
        $strategy = $method->invoke($this->service, null);
        $this->assertEquals(RecoveryStrategy::HEALTH_CHECK_RETRY, $strategy);
    }

    public function test_execute_network_retry_recovery_success()
    {
        // Arrange
        $user = User::factory()->create();
        $provider = 'google-drive';

        $successfulApiTest = Mockery::mock();
        $successfulApiTest->shouldReceive('isSuccessful')->andReturn(true);

        $this->mockHealthValidator
            ->shouldReceive('performLiveApiTest')
            ->with($user, $provider)
            ->once()
            ->andReturn($successfulApiTest);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('executeNetworkRetryRecovery');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->service, $user, $provider, 'test_op_id');

        // Assert
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(RecoveryStrategy::NETWORK_RETRY, $result->getStrategy());
        $this->assertEquals(__('messages.recovery_network_connectivity_restored'), $result->getMessage());
    }

    public function test_execute_network_retry_recovery_failure()
    {
        // Arrange
        $user = User::factory()->create();
        $provider = 'google-drive';

        $failedApiTest = Mockery::mock();
        $failedApiTest->shouldReceive('isSuccessful')->andReturn(false);

        $this->mockHealthValidator
            ->shouldReceive('performLiveApiTest')
            ->with($user, $provider)
            ->once()
            ->andReturn($failedApiTest);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('executeNetworkRetryRecovery');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->service, $user, $provider, 'test_op_id');

        // Assert
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(RecoveryStrategy::NETWORK_RETRY, $result->getStrategy());
        $this->assertEquals(__('messages.recovery_network_connectivity_still_failing'), $result->getMessage());
    }

    public function test_execute_user_intervention_required()
    {
        // Arrange
        $user = User::factory()->create();
        $provider = 'google-drive';
        $errorType = CloudStorageErrorType::INSUFFICIENT_PERMISSIONS;

        $this->mockNotificationService
            ->shouldReceive('sendRefreshFailureNotification')
            ->with($user, $provider, TokenRefreshErrorType::INVALID_REFRESH_TOKEN, 1, Mockery::any())
            ->once();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('executeUserInterventionRequired');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->service, $user, $provider, $errorType, 'test_op_id');

        // Assert
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(RecoveryStrategy::USER_INTERVENTION_REQUIRED, $result->getStrategy());
        $this->assertStringContains(__('messages.recovery_user_intervention_required'), $result->getMessage());
    }

    public function test_find_pending_uploads_filters_correctly()
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $provider = 'google-drive';

        // Create various uploads to test filtering
        $pendingUpload = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'storage_provider' => $provider,
            'google_drive_file_id' => null,
            'cloud_storage_error_type' => CloudStorageErrorType::NETWORK_ERROR->value,
            'recovery_attempts' => 1
        ]);

        $completedUpload = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'storage_provider' => $provider,
            'google_drive_file_id' => 'uploaded_123',
        ]);

        $otherUserUpload = FileUpload::factory()->create([
            'company_user_id' => $otherUser->id,
            'storage_provider' => $provider,
            'google_drive_file_id' => null,
        ]);

        $nonRecoverableUpload = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'storage_provider' => $provider,
            'google_drive_file_id' => null,
            'cloud_storage_error_type' => CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED->value,
            'recovery_attempts' => 1
        ]);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('findPendingUploads');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->service, $user, $provider);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($pendingUpload->id, $result->first()->id);
    }

    public function test_map_cloud_storage_error_to_token_error()
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapCloudStorageErrorToTokenError');
        $method->setAccessible(true);

        // Test various mappings
        $result = $method->invoke($this->service, CloudStorageErrorType::TOKEN_EXPIRED);
        $this->assertEquals(TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN, $result);

        $result = $method->invoke($this->service, CloudStorageErrorType::INVALID_CREDENTIALS);
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result);

        $result = $method->invoke($this->service, CloudStorageErrorType::NETWORK_ERROR);
        $this->assertEquals(TokenRefreshErrorType::NETWORK_TIMEOUT, $result);

        $result = $method->invoke($this->service, CloudStorageErrorType::API_QUOTA_EXCEEDED);
        $this->assertEquals(TokenRefreshErrorType::API_QUOTA_EXCEEDED, $result);

        $result = $method->invoke($this->service, CloudStorageErrorType::SERVICE_UNAVAILABLE);
        $this->assertEquals(TokenRefreshErrorType::SERVICE_UNAVAILABLE, $result);

        $result = $method->invoke($this->service, CloudStorageErrorType::FILE_NOT_FOUND);
        $this->assertEquals(TokenRefreshErrorType::UNKNOWN_ERROR, $result);
    }
}