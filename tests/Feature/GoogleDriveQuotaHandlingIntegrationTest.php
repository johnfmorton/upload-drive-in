<?php

namespace Tests\Feature;

use App\Jobs\UploadToGoogleDrive;
use App\Models\CloudStorageHealthStatus;
use App\Models\FileUpload;
use App\Models\GoogleDriveToken;
use App\Models\User;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GoogleDriveQuotaHandlingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private FileUpload $fileUpload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com'
        ]);

        $this->fileUpload = FileUpload::factory()->create([
            'uploaded_by_user_id' => $this->user->id,
            'original_filename' => 'quota-test.pdf',
            'file_size' => 5120,
            'storage_provider' => 'google-drive'
        ]);

        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => now()->addHour(),
            'token_type' => 'Bearer',
            'scopes' => ['https://www.googleapis.com/auth/drive.file']
        ]);

        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'last_successful_operation_at' => now()->subMinutes(5),
            'consecutive_failures' => 0
        ]);
    }

    /** @test */
    public function it_handles_api_rate_limit_exceeded()
    {
        Queue::fake();

        // Mock Google API rate limit error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Rate Limit Exceeded', 429)
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify quota exceeded error was classified correctly
        $this->fileUpload->refresh();
        $this->assertEquals('api_quota_exceeded', $this->fileUpload->cloud_storage_error_type);
        $this->assertStringContains('Google Drive API limit reached', $this->fileUpload->cloud_storage_error_context['user_message']);

        // Verify health status shows degraded performance
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('degraded', $healthStatus->status);
        $this->assertEquals('api_quota_exceeded', $healthStatus->last_error_type);

        // Verify job was released for retry with delay
        Queue::assertPushed(UploadToGoogleDrive::class);
    }

    /** @test */
    public function it_handles_daily_quota_exceeded()
    {
        Queue::fake();

        // Mock Google API daily quota exceeded error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Daily Limit Exceeded', 403)
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify quota exceeded error was classified correctly
        $this->fileUpload->refresh();
        $this->assertEquals('api_quota_exceeded', $this->fileUpload->cloud_storage_error_type);
        $this->assertStringContains('Google Drive API limit reached', $this->fileUpload->cloud_storage_error_context['user_message']);

        // Verify health status reflects quota issues
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('degraded', $healthStatus->status);
        $this->assertEquals('api_quota_exceeded', $healthStatus->last_error_type);
    }

    /** @test */
    public function it_handles_user_rate_limit_exceeded()
    {
        Queue::fake();

        // Mock Google API user rate limit error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('User Rate Limit Exceeded', 429)
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify quota exceeded error was classified correctly
        $this->fileUpload->refresh();
        $this->assertEquals('api_quota_exceeded', $this->fileUpload->cloud_storage_error_type);
        $this->assertStringContains('Google Drive API limit reached', $this->fileUpload->cloud_storage_error_context['user_message']);

        // Verify retry was scheduled
        Queue::assertPushed(UploadToGoogleDrive::class);
    }

    /** @test */
    public function it_recovers_after_quota_reset()
    {
        Queue::fake();

        // First, simulate quota exceeded
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Rate Limit Exceeded', 429)
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify initial quota failure
        $this->fileUpload->refresh();
        $this->assertEquals('api_quota_exceeded', $this->fileUpload->cloud_storage_error_type);

        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('degraded', $healthStatus->status);

        // Now simulate quota reset and successful upload
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $driveFile = new \Google\Service\Drive\DriveFile();
            $driveFile->setId('quota_recovered_123');
            $driveFile->setName('quota-test.pdf');
            
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn($driveFile);
        });

        // Clear the error to allow retry
        $this->fileUpload->update([
            'cloud_storage_error_type' => null,
            'cloud_storage_error_context' => null
        ]);

        // Retry the job after quota reset
        $retryJob = new UploadToGoogleDrive($this->fileUpload);
        $retryJob->handle();

        // Verify successful recovery
        $this->fileUpload->refresh();
        $this->assertEquals('quota_recovered_123', $this->fileUpload->google_drive_file_id);
        $this->assertNull($this->fileUpload->cloud_storage_error_type);

        // Verify health status recovery
        $healthStatus->refresh();
        $this->assertEquals('healthy', $healthStatus->status);
        $this->assertEquals(0, $healthStatus->consecutive_failures);
    }

    /** @test */
    public function it_handles_batch_uploads_during_quota_limits()
    {
        Queue::fake();

        // Create multiple uploads to test batch quota handling
        $uploads = FileUpload::factory()->count(10)->create([
            'uploaded_by_user_id' => $this->user->id,
            'storage_provider' => 'google-drive'
        ]);

        $callCount = 0;
        $this->mock(\Google\Service\Drive::class, function ($mock) use (&$callCount) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                
                // First 3 succeed, then quota exceeded for the rest
                if ($callCount <= 3) {
                    $driveFile = new \Google\Service\Drive\DriveFile();
                    $driveFile->setId('batch_success_' . $callCount);
                    $driveFile->setName('test-file.pdf');
                    return $driveFile;
                } else {
                    throw new GoogleServiceException('Rate Limit Exceeded', 429);
                }
            });
        });

        // Process all uploads
        foreach ($uploads as $upload) {
            $job = new UploadToGoogleDrive($upload);
            $job->handle();
        }

        // Verify mixed results
        $uploads->each(function ($upload) {
            $upload->refresh();
        });

        $successfulUploads = $uploads->filter(function ($upload) {
            return !is_null($upload->google_drive_file_id);
        });

        $quotaFailedUploads = $uploads->filter(function ($upload) {
            return $upload->cloud_storage_error_type === 'api_quota_exceeded';
        });

        // Should have 3 successful and 7 quota-failed uploads
        $this->assertEquals(3, $successfulUploads->count());
        $this->assertEquals(7, $quotaFailedUploads->count());

        // Health status should reflect quota issues
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('degraded', $healthStatus->status);
        $this->assertEquals('api_quota_exceeded', $healthStatus->last_error_type);
    }

    /** @test */
    public function it_implements_exponential_backoff_for_quota_retries()
    {
        Queue::fake();

        // Mock quota exceeded error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Rate Limit Exceeded', 429)
            );
        });

        // Process the job multiple times to test retry behavior
        $job = new UploadToGoogleDrive($this->fileUpload);
        
        // First attempt
        $job->handle();
        $this->fileUpload->refresh();
        $this->assertEquals('api_quota_exceeded', $this->fileUpload->cloud_storage_error_type);

        // Verify job was released for retry
        Queue::assertPushed(UploadToGoogleDrive::class);

        // Clear error for second attempt
        $this->fileUpload->update([
            'cloud_storage_error_type' => null,
            'cloud_storage_error_context' => null
        ]);

        // Second attempt (should still fail but with different retry delay)
        $secondJob = new UploadToGoogleDrive($this->fileUpload);
        $secondJob->handle();

        $this->fileUpload->refresh();
        $this->assertEquals('api_quota_exceeded', $this->fileUpload->cloud_storage_error_type);

        // Verify exponential backoff is applied (job should be released again)
        Queue::assertPushed(UploadToGoogleDrive::class, 2);
    }

    /** @test */
    public function it_stops_retrying_after_max_attempts_for_quota_errors()
    {
        Queue::fake();

        // Mock persistent quota exceeded error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Daily Limit Exceeded', 403)
            );
        });

        // Simulate multiple retry attempts
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->fileUpload->update([
                'cloud_storage_error_type' => null,
                'cloud_storage_error_context' => null
            ]);

            $job = new UploadToGoogleDrive($this->fileUpload);
            $job->handle();

            $this->fileUpload->refresh();
            $this->assertEquals('api_quota_exceeded', $this->fileUpload->cloud_storage_error_type);
        }

        // After max attempts, the job should not be retried further
        // Health status should reflect persistent quota issues
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('degraded', $healthStatus->status);
        $this->assertEquals('api_quota_exceeded', $healthStatus->last_error_type);
        $this->assertGreaterThanOrEqual(3, $healthStatus->consecutive_failures);
    }

    /** @test */
    public function it_provides_quota_reset_time_information()
    {
        Queue::fake();

        // Mock quota exceeded with retry-after header information
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $exception = new GoogleServiceException('Rate Limit Exceeded', 429);
            // Simulate retry-after header in the exception context
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow($exception);
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify error context includes timing information
        $this->fileUpload->refresh();
        $this->assertEquals('api_quota_exceeded', $this->fileUpload->cloud_storage_error_type);
        
        $errorContext = $this->fileUpload->cloud_storage_error_context;
        $this->assertArrayHasKey('user_message', $errorContext);
        $this->assertStringContains('API limit reached', $errorContext['user_message']);
        
        // Should include some indication of when uploads will resume
        $this->assertStringContains('resume', $errorContext['user_message']);
    }
}