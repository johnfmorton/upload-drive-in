<?php

namespace Tests\Unit\Jobs;

use App\Enums\CloudStorageErrorType;
use App\Enums\UserRole;
use App\Jobs\PendingUploadRetryJob;
use App\Jobs\UploadToGoogleDrive;
use App\Models\FileUpload;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageManager;
use App\Services\TokenRenewalNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class PendingUploadRetryJobTest extends TestCase
{
    use DatabaseTransactions;

    private $mockStorageManager;
    private $mockHealthService;
    private $mockNotificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockStorageManager = Mockery::mock(CloudStorageManager::class);
        $this->mockHealthService = Mockery::mock(CloudStorageHealthService::class);
        $this->mockNotificationService = Mockery::mock(TokenRenewalNotificationService::class);

        // Bind mocks to container
        $this->app->instance(CloudStorageManager::class, $this->mockStorageManager);
        $this->app->instance(CloudStorageHealthService::class, $this->mockHealthService);
        $this->app->instance(TokenRenewalNotificationService::class, $this->mockNotificationService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_successful_retry()
    {
        // Arrange
        Queue::fake();
        Storage::fake('public');
        
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'google_drive_file_id' => null,
            'filename' => 'test.txt',
            'retry_count' => 1,
            'recovery_attempts' => 1
        ]);

        // Create the local file
        Storage::disk('public')->put('uploads/' . $fileUpload->filename, 'test content');

        $provider = 'google-drive';
        $recoveryOperationId = 'recovery_123';

        // Mock health service to return healthy status
        $this->mockHealthService
            ->shouldReceive('getHealthSummary')
            ->with($user, $provider)
            ->once()
            ->andReturn(['is_healthy' => true, 'status' => 'healthy']);

        $job = new PendingUploadRetryJob($fileUpload, $provider, $recoveryOperationId);

        // Act
        $job->handle($this->mockStorageManager, $this->mockHealthService, $this->mockNotificationService);

        // Assert
        Queue::assertPushed(UploadToGoogleDrive::class);
        
        $fileUpload->refresh();
        $this->assertEquals(2, $fileUpload->recovery_attempts);
        $this->assertNotNull($fileUpload->last_processed_at);
        $this->assertNull($fileUpload->retry_recommended_at);
    }

    public function test_handle_skips_already_uploaded_file()
    {
        // Arrange
        Queue::fake();
        Log::shouldReceive('info')->andReturn();
        
        $user = User::factory()->create();
        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'google_drive_file_id' => 'already_uploaded_123', // Already uploaded
            'filename' => 'test.txt'
        ]);

        $provider = 'google-drive';
        $recoveryOperationId = 'recovery_123';

        $job = new PendingUploadRetryJob($fileUpload, $provider, $recoveryOperationId);

        // Act
        $job->handle($this->mockStorageManager, $this->mockHealthService, $this->mockNotificationService);

        // Assert
        Queue::assertNotPushed(UploadToGoogleDrive::class);
    }

    public function test_handle_skips_file_with_missing_local_file()
    {
        // Arrange
        Queue::fake();
        Storage::fake('public');
        Log::shouldReceive('info')->andReturn();
        Log::shouldReceive('warning')->andReturn();
        
        $user = User::factory()->create();
        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'google_drive_file_id' => null,
            'filename' => 'missing.txt',
            'retry_count' => 1
        ]);

        // Don't create the local file - it's missing

        $provider = 'google-drive';
        $recoveryOperationId = 'recovery_123';

        $job = new PendingUploadRetryJob($fileUpload, $provider, $recoveryOperationId);

        // Act
        $job->handle($this->mockStorageManager, $this->mockHealthService, $this->mockNotificationService);

        // Assert
        Queue::assertNotPushed(UploadToGoogleDrive::class);
        
        $fileUpload->refresh();
        $this->assertStringContains(__('messages.recovery_local_file_no_longer_exists'), $fileUpload->last_error);
    }

    public function test_handle_skips_file_exceeding_retry_limits()
    {
        // Arrange
        Queue::fake();
        Log::shouldReceive('info')->andReturn();
        
        $user = User::factory()->create();
        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'google_drive_file_id' => null,
            'filename' => 'test.txt',
            'retry_count' => 5, // Exceeds default limit
            'recovery_attempts' => 6 // Exceeds default limit
        ]);

        $provider = 'google-drive';
        $recoveryOperationId = 'recovery_123';

        $job = new PendingUploadRetryJob($fileUpload, $provider, $recoveryOperationId);

        // Act
        $job->handle($this->mockStorageManager, $this->mockHealthService, $this->mockNotificationService);

        // Assert
        Queue::assertNotPushed(UploadToGoogleDrive::class);
    }

    public function test_handle_defers_when_connection_unhealthy()
    {
        // Arrange
        Storage::fake('public');
        
        $user = User::factory()->create();
        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'google_drive_file_id' => null,
            'filename' => 'test.txt',
            'retry_count' => 1
        ]);

        // Create the local file
        Storage::disk('public')->put('uploads/' . $fileUpload->filename, 'test content');

        $provider = 'google-drive';
        $recoveryOperationId = 'recovery_123';

        // Mock health service to return unhealthy status
        $this->mockHealthService
            ->shouldReceive('getHealthSummary')
            ->with($user, $provider)
            ->once()
            ->andReturn(['is_healthy' => false, 'status' => 'connection_issues']);

        Log::shouldReceive('info')->andReturn();
        Log::shouldReceive('warning')->andReturn();

        $job = new PendingUploadRetryJob($fileUpload, $provider, $recoveryOperationId);

        // Act & Assert
        $this->expectException(\Illuminate\Queue\MaxAttemptsExceededException::class);
        $job->handle($this->mockStorageManager, $this->mockHealthService, $this->mockNotificationService);
    }

    public function test_handle_no_target_user_found()
    {
        // Arrange
        Queue::fake();
        Storage::fake('public');
        Log::shouldReceive('info')->andReturn();
        Log::shouldReceive('error')->andReturn();
        
        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => null,
            'uploaded_by_user_id' => null,
            'google_drive_file_id' => null,
            'filename' => 'test.txt'
        ]);

        // Create the local file
        Storage::disk('public')->put('uploads/' . $fileUpload->filename, 'test content');

        $provider = 'google-drive';
        $recoveryOperationId = 'recovery_123';

        $job = new PendingUploadRetryJob($fileUpload, $provider, $recoveryOperationId);

        // Act
        $job->handle($this->mockStorageManager, $this->mockHealthService, $this->mockNotificationService);

        // Assert
        Queue::assertNotPushed(UploadToGoogleDrive::class);
        
        $fileUpload->refresh();
        $this->assertStringContains(__('messages.recovery_no_target_user_found'), $fileUpload->last_error);
    }

    public function test_handle_clears_cloud_storage_error()
    {
        // Arrange
        Queue::fake();
        Storage::fake('public');
        
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'google_drive_file_id' => null,
            'filename' => 'test.txt',
            'cloud_storage_error_type' => CloudStorageErrorType::NETWORK_ERROR->value,
            'cloud_storage_error_context' => ['error' => 'Network timeout']
        ]);

        // Create the local file
        Storage::disk('public')->put('uploads/' . $fileUpload->filename, 'test content');

        $provider = 'google-drive';
        $recoveryOperationId = 'recovery_123';

        // Mock health service to return healthy status
        $this->mockHealthService
            ->shouldReceive('getHealthSummary')
            ->with($user, $provider)
            ->once()
            ->andReturn(['is_healthy' => true, 'status' => 'healthy']);

        $job = new PendingUploadRetryJob($fileUpload, $provider, $recoveryOperationId);

        // Act
        $job->handle($this->mockStorageManager, $this->mockHealthService, $this->mockNotificationService);

        // Assert
        $fileUpload->refresh();
        $this->assertNull($fileUpload->cloud_storage_error_type);
        $this->assertNull($fileUpload->cloud_storage_error_context);
    }

    public function test_failed_sends_notification()
    {
        // Arrange
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => $user->id,
            'google_drive_file_id' => null,
            'filename' => 'test.txt'
        ]);

        $provider = 'google-drive';
        $recoveryOperationId = 'recovery_123';

        $exception = new \Exception('Test failure');

        // Mock notification service
        $this->mockNotificationService
            ->shouldReceive('sendRefreshFailureNotification')
            ->with(
                $user,
                $provider,
                \App\Enums\TokenRefreshErrorType::UNKNOWN_ERROR,
                Mockery::any(),
                Mockery::type('string')
            )
            ->once();

        Log::shouldReceive('error')->andReturn();

        $job = new PendingUploadRetryJob($fileUpload, $provider, $recoveryOperationId);

        // Act
        $job->failed($exception);

        // Assert
        $fileUpload->refresh();
        $this->assertStringContains(__('messages.recovery_retry_job_permanently_failed'), $fileUpload->last_error);
        $this->assertNotNull($fileUpload->error_details);
        $this->assertTrue($fileUpload->error_details['recovery_permanently_failed'] ?? false);
    }

    public function test_should_retry_upload_logic()
    {
        // Arrange
        $fileUpload = FileUpload::factory()->create([
            'google_drive_file_id' => null,
            'retry_count' => 1,
            'recovery_attempts' => 1
        ]);

        $provider = 'google-drive';
        $recoveryOperationId = 'recovery_123';

        $job = new PendingUploadRetryJob($fileUpload, $provider, $recoveryOperationId);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('shouldRetryUpload');
        $method->setAccessible(true);

        // Act & Assert - should retry
        $this->assertTrue($method->invoke($job));

        // Test with already uploaded file
        $fileUpload->update(['google_drive_file_id' => 'uploaded_123']);
        $this->assertFalse($method->invoke($job));

        // Test with exceeded retry limits
        $fileUpload->update([
            'google_drive_file_id' => null,
            'retry_count' => 5,
            'recovery_attempts' => 6
        ]);
        $this->assertFalse($method->invoke($job));

        // Test with non-recoverable error
        $fileUpload->update([
            'retry_count' => 1,
            'recovery_attempts' => 1,
            'cloud_storage_error_type' => CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED->value
        ]);
        $this->assertFalse($method->invoke($job));
    }

    public function test_determine_target_user_priority()
    {
        // Arrange
        $companyUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $uploadedByUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $adminUser = User::factory()->create(['role' => UserRole::ADMIN]);

        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => $companyUser->id,
            'uploaded_by_user_id' => $uploadedByUser->id,
            'google_drive_file_id' => null
        ]);

        $provider = 'google-drive';
        $recoveryOperationId = 'recovery_123';

        $job = new PendingUploadRetryJob($fileUpload, $provider, $recoveryOperationId);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineTargetUser');
        $method->setAccessible(true);

        // Act & Assert - should prioritize company user
        $result = $method->invoke($job);
        $this->assertEquals($companyUser->id, $result->id);

        // Test with no company user
        $fileUpload->update(['company_user_id' => null]);
        $result = $method->invoke($job);
        $this->assertEquals($uploadedByUser->id, $result->id);

        // Test with no specific users - should fallback to admin
        $fileUpload->update(['uploaded_by_user_id' => null]);
        $result = $method->invoke($job);
        $this->assertEquals($adminUser->id, $result->id);
    }

    public function test_unique_id_generation()
    {
        // Arrange
        $fileUpload = FileUpload::factory()->create(['id' => 123]);
        $provider = 'google-drive';
        $recoveryOperationId = 'recovery_123';

        $job = new PendingUploadRetryJob($fileUpload, $provider, $recoveryOperationId);

        // Act
        $uniqueId = $job->uniqueId();

        // Assert
        $this->assertEquals('pending_upload_retry_123_google-drive', $uniqueId);
    }

    public function test_retry_until_timeout()
    {
        // Arrange
        $fileUpload = FileUpload::factory()->create();
        $provider = 'google-drive';
        $recoveryOperationId = 'recovery_123';

        $job = new PendingUploadRetryJob($fileUpload, $provider, $recoveryOperationId);

        // Act
        $retryUntil = $job->retryUntil();

        // Assert
        $this->assertInstanceOf(\DateTime::class, $retryUntil);
        $this->assertGreaterThan(now(), $retryUntil);
        $this->assertLessThanOrEqual(now()->addMinutes(30), $retryUntil);
    }
}