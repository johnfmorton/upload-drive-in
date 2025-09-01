<?php

namespace Tests\Unit\Jobs;

use App\Contracts\CloudStorageErrorHandlerInterface;
use App\Contracts\CloudStorageProviderInterface;
use App\Enums\CloudStorageErrorType;
use App\Enums\UserRole;
use App\Exceptions\CloudStorageException;
use App\Jobs\UploadToGoogleDrive;
use App\Models\FileUpload;
use App\Models\User;
use App\Services\CloudStorageHealthService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class UploadToGoogleDriveEnhancedTest extends TestCase
{
    use DatabaseTransactions;

    protected $mockProvider;
    protected $mockErrorHandler;
    protected $mockHealthService;
    protected $user;
    protected $fileUpload;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock services
        $this->mockProvider = Mockery::mock(CloudStorageProviderInterface::class);
        $this->mockErrorHandler = Mockery::mock(CloudStorageErrorHandlerInterface::class);
        $this->mockHealthService = Mockery::mock(CloudStorageHealthService::class);

        // Bind mocks to container
        $this->app->instance(CloudStorageProviderInterface::class, $this->mockProvider);
        $this->app->instance(CloudStorageErrorHandlerInterface::class, $this->mockErrorHandler);
        $this->app->instance(CloudStorageHealthService::class, $this->mockHealthService);

        // Create test user
        $this->user = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);

        // Create test file upload
        $this->fileUpload = FileUpload::factory()->create([
            'filename' => 'test-file.txt',
            'original_filename' => 'test-file.txt',
            'mime_type' => 'text/plain',
            'email' => 'client@example.com',
            'message' => 'Test upload',
            'uploaded_by_user_id' => $this->user->id,
        ]);

        // Mock storage
        Storage::fake('public');
        Storage::disk('public')->put('uploads/' . $this->fileUpload->filename, 'test content');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_successful_upload_with_provider_interface()
    {
        // Arrange
        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('google-drive');
        
        $this->mockProvider->shouldReceive('hasValidConnection')
            ->with($this->user)
            ->andReturn(true);

        $this->mockProvider->shouldReceive('uploadFile')
            ->once()
            ->with(
                $this->user,
                'uploads/' . $this->fileUpload->filename,
                'client@example.com',
                Mockery::type('array')
            )
            ->andReturn('cloud-file-id-123');

        $this->mockHealthService->shouldReceive('recordSuccessfulOperation')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('array'));

        // The error handler should not be called on successful uploads
        $this->mockErrorHandler->shouldNotReceive('classifyError');

        // Act
        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle($this->mockProvider, $this->mockErrorHandler, $this->mockHealthService);

        // Assert
        $this->fileUpload->refresh();
        $this->assertEquals('cloud-file-id-123', $this->fileUpload->google_drive_file_id);
        $this->assertEquals('google-drive', $this->fileUpload->cloud_storage_provider);
        $this->assertNull($this->fileUpload->cloud_storage_error_type);
        $this->assertNull($this->fileUpload->last_error);
        $this->assertFalse(Storage::disk('public')->exists('uploads/' . $this->fileUpload->filename));
    }

    public function test_handles_cloud_storage_exception_with_error_classification()
    {
        // Arrange
        $exception = CloudStorageException::tokenExpired('google-drive', [
            'operation' => 'upload',
            'file_name' => 'test-file.txt'
        ]);

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('google-drive');
        
        $this->mockProvider->shouldReceive('hasValidConnection')
            ->with($this->user)
            ->andReturn(true);

        $this->mockProvider->shouldReceive('uploadFile')
            ->once()
            ->andThrow($exception);

        $this->mockErrorHandler->shouldReceive('shouldRetry')
            ->with(CloudStorageErrorType::TOKEN_EXPIRED, 1)
            ->andReturn(false);

        $this->mockErrorHandler->shouldReceive('getMaxRetryAttempts')
            ->with(CloudStorageErrorType::TOKEN_EXPIRED)
            ->andReturn(0);

        $this->mockHealthService->shouldReceive('markConnectionAsUnhealthy')
            ->once()
            ->with($this->user, 'google-drive', Mockery::type('string'), CloudStorageErrorType::TOKEN_EXPIRED);

        // Act & Assert
        $job = new UploadToGoogleDrive($this->fileUpload);
        
        $this->expectException(CloudStorageException::class);
        
        try {
            $job->handle($this->mockProvider, $this->mockErrorHandler, $this->mockHealthService);
        } catch (CloudStorageException $e) {
            // Verify error was recorded in database
            $this->fileUpload->refresh();
            $this->assertEquals('google-drive', $this->fileUpload->cloud_storage_provider);
            $this->assertEquals('token_expired', $this->fileUpload->cloud_storage_error_type);
            $this->assertNotNull($this->fileUpload->cloud_storage_error_context);
            $this->assertNotNull($this->fileUpload->connection_health_at_failure);
            
            throw $e;
        }
    }

    public function test_handles_generic_exception_with_error_classification()
    {
        // Arrange
        $exception = new Exception('Network connection failed');

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('google-drive');
        
        $this->mockProvider->shouldReceive('hasValidConnection')
            ->with($this->user)
            ->andReturn(true);

        $this->mockProvider->shouldReceive('uploadFile')
            ->once()
            ->andThrow($exception);

        $this->mockErrorHandler->shouldReceive('classifyError')
            ->with($exception)
            ->andReturn(CloudStorageErrorType::NETWORK_ERROR);

        $this->mockErrorHandler->shouldReceive('shouldRetry')
            ->with(CloudStorageErrorType::NETWORK_ERROR, 1)
            ->andReturn(true);

        $this->mockErrorHandler->shouldReceive('getMaxRetryAttempts')
            ->with(CloudStorageErrorType::NETWORK_ERROR)
            ->andReturn(3);

        $this->mockErrorHandler->shouldReceive('getRetryDelay')
            ->with(CloudStorageErrorType::NETWORK_ERROR, 1)
            ->andReturn(60);

        $this->mockHealthService->shouldReceive('markConnectionAsUnhealthy')
            ->once()
            ->with($this->user, 'google-drive', 'Network connection failed', CloudStorageErrorType::NETWORK_ERROR);

        // Act & Assert
        $job = new UploadToGoogleDrive($this->fileUpload);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Network connection failed');
        
        try {
            $job->handle($this->mockProvider, $this->mockErrorHandler, $this->mockHealthService);
        } catch (Exception $e) {
            // Verify error was recorded and retry logic was applied
            $this->fileUpload->refresh();
            $this->assertEquals('google-drive', $this->fileUpload->cloud_storage_provider);
            $this->assertEquals('network_error', $this->fileUpload->cloud_storage_error_type);
            $this->assertEquals([60], $job->backoff);
            
            throw $e;
        }
    }

    public function test_determines_target_user_priority_order()
    {
        // Arrange - Create different users
        $companyUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $uploaderUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $adminUser = User::factory()->create(['role' => UserRole::ADMIN]);

        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => $companyUser->id,
            'uploaded_by_user_id' => $uploaderUser->id,
        ]);

        $this->mockProvider->shouldReceive('hasValidConnection')
            ->with($companyUser)
            ->andReturn(true);

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('google-drive');

        // Act
        $job = new UploadToGoogleDrive($fileUpload);
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineTargetUser');
        $method->setAccessible(true);
        
        $targetUser = $method->invoke($job, $fileUpload, $this->mockProvider);

        // Assert - Should prioritize company_user_id
        $this->assertEquals($companyUser->id, $targetUser->id);
    }

    public function test_falls_back_to_admin_when_target_user_has_no_connection()
    {
        // Arrange
        $companyUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $adminUser = User::factory()->create(['role' => UserRole::ADMIN]);

        $fileUpload = FileUpload::factory()->create([
            'company_user_id' => $companyUser->id,
        ]);

        $this->mockProvider->shouldReceive('hasValidConnection')
            ->with($companyUser)
            ->andReturn(false);

        $this->mockProvider->shouldReceive('hasValidConnection')
            ->with($adminUser)
            ->andReturn(true);

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('google-drive');

        // Act
        $job = new UploadToGoogleDrive($fileUpload);
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('determineTargetUser');
        $method->setAccessible(true);
        
        $targetUser = $method->invoke($job, $fileUpload, $this->mockProvider);

        // Assert - Should fall back to admin
        $this->assertEquals($adminUser->id, $targetUser->id);
    }

    public function test_handles_missing_local_file()
    {
        // Arrange - Remove the local file
        Storage::disk('public')->delete('uploads/' . $this->fileUpload->filename);

        $this->mockHealthService->shouldReceive('markConnectionAsUnhealthy')
            ->once();

        // The error handler should not be called for missing files (handled directly)
        $this->mockErrorHandler->shouldNotReceive('classifyError');

        // Act
        $job = new UploadToGoogleDrive($this->fileUpload);
        
        // Create a spy to track if fail was called
        $failCalled = false;
        $originalJob = $job;
        $job = Mockery::mock(UploadToGoogleDrive::class, [$this->fileUpload])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        
        $job->shouldReceive('fail')
            ->once()
            ->andReturnUsing(function($exception) use (&$failCalled) {
                $failCalled = true;
                return null;
            });

        try {
            $job->handle($this->mockProvider, $this->mockErrorHandler, $this->mockHealthService);
        } catch (\Exception $e) {
            // Expected to fail
        }

        // Assert
        $this->fileUpload->refresh();
        $this->assertEquals('file_not_found', $this->fileUpload->cloud_storage_error_type);
        $this->assertTrue($failCalled);
    }

    public function test_job_failed_method_records_final_failure()
    {
        // Arrange
        $exception = new Exception('Final failure');

        $this->mockErrorHandler->shouldReceive('classifyError')
            ->with($exception)
            ->andReturn(CloudStorageErrorType::UNKNOWN_ERROR);

        $this->mockHealthService->shouldReceive('markConnectionAsUnhealthy')
            ->once()
            ->with(Mockery::type(User::class), 'google-drive', 'Final failure', CloudStorageErrorType::UNKNOWN_ERROR);

        // Act
        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->failed($exception);

        // Assert
        $this->fileUpload->refresh();
        $this->assertEquals('unknown_error', $this->fileUpload->cloud_storage_error_type);
        $this->assertNotNull($this->fileUpload->cloud_storage_error_context);
        $this->assertTrue($this->fileUpload->cloud_storage_error_context['final_failure'] ?? false);
    }

    public function test_retry_delay_is_set_based_on_error_type()
    {
        // Arrange
        $exception = new Exception('Rate limit exceeded');

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('google-drive');
        
        $this->mockProvider->shouldReceive('hasValidConnection')
            ->with($this->user)
            ->andReturn(true);

        $this->mockProvider->shouldReceive('uploadFile')
            ->once()
            ->andThrow($exception);

        $this->mockErrorHandler->shouldReceive('classifyError')
            ->with($exception)
            ->andReturn(CloudStorageErrorType::API_QUOTA_EXCEEDED);

        $this->mockErrorHandler->shouldReceive('shouldRetry')
            ->with(CloudStorageErrorType::API_QUOTA_EXCEEDED, 1)
            ->andReturn(true);

        $this->mockErrorHandler->shouldReceive('getMaxRetryAttempts')
            ->with(CloudStorageErrorType::API_QUOTA_EXCEEDED)
            ->andReturn(1);

        $this->mockErrorHandler->shouldReceive('getRetryDelay')
            ->with(CloudStorageErrorType::API_QUOTA_EXCEEDED, 1)
            ->andReturn(3600); // 1 hour

        $this->mockHealthService->shouldReceive('markConnectionAsUnhealthy')
            ->once();

        // Act
        $job = new UploadToGoogleDrive($this->fileUpload);
        
        try {
            $job->handle($this->mockProvider, $this->mockErrorHandler, $this->mockHealthService);
        } catch (Exception $e) {
            // Expected to throw
        }

        // Assert
        $this->assertEquals([3600], $job->backoff);
    }

    public function test_max_tries_is_updated_based_on_error_type()
    {
        // Arrange
        $exception = new Exception('Network error');

        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('google-drive');
        
        $this->mockProvider->shouldReceive('hasValidConnection')
            ->with($this->user)
            ->andReturn(true);

        $this->mockProvider->shouldReceive('uploadFile')
            ->once()
            ->andThrow($exception);

        $this->mockErrorHandler->shouldReceive('classifyError')
            ->with($exception)
            ->andReturn(CloudStorageErrorType::NETWORK_ERROR);

        $this->mockErrorHandler->shouldReceive('shouldRetry')
            ->with(CloudStorageErrorType::NETWORK_ERROR, 1)
            ->andReturn(true);

        $this->mockErrorHandler->shouldReceive('getMaxRetryAttempts')
            ->with(CloudStorageErrorType::NETWORK_ERROR)
            ->andReturn(3);

        $this->mockErrorHandler->shouldReceive('getRetryDelay')
            ->with(CloudStorageErrorType::NETWORK_ERROR, 1)
            ->andReturn(60);

        $this->mockHealthService->shouldReceive('markConnectionAsUnhealthy')
            ->once();

        // Act
        $job = new UploadToGoogleDrive($this->fileUpload);
        $originalTries = $job->tries;
        
        try {
            $job->handle($this->mockProvider, $this->mockErrorHandler, $this->mockHealthService);
        } catch (Exception $e) {
            // Expected to throw
        }

        // Assert - tries should be updated to max attempts + 1
        $this->assertEquals(4, $job->tries); // 3 max attempts + 1
    }
}