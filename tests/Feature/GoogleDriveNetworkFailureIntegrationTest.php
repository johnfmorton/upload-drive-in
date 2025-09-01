<?php

namespace Tests\Feature;

use App\Jobs\UploadToGoogleDrive;
use App\Models\CloudStorageHealthStatus;
use App\Models\FileUpload;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\GoogleDriveProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GoogleDriveNetworkFailureIntegrationTest extends TestCase
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
            'original_filename' => 'network-test.pdf',
            'file_size' => 2048,
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
    public function it_handles_dns_resolution_failure()
    {
        Queue::fake();

        // Mock DNS resolution failure
        Http::fake([
            'www.googleapis.com/*' => Http::response('Connection failed', 500),
            'oauth2.googleapis.com/*' => Http::response('Connection failed', 500)
        ]);

        // Mock the Google Drive service to throw a network error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new \Exception('cURL error 6: Could not resolve host: www.googleapis.com')
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify network error was classified correctly
        $this->fileUpload->refresh();
        $this->assertEquals('network_error', $this->fileUpload->cloud_storage_error_type);
        $this->assertStringContains('Network connection issue', $this->fileUpload->cloud_storage_error_context['user_message']);

        // Verify health status reflects network issues
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('degraded', $healthStatus->status);
        $this->assertEquals('network_error', $healthStatus->last_error_type);
        $this->assertEquals(1, $healthStatus->consecutive_failures);

        // Verify job was queued for retry
        Queue::assertPushed(UploadToGoogleDrive::class);
    }

    /** @test */
    public function it_handles_connection_timeout()
    {
        Queue::fake();

        // Mock connection timeout
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new \Exception('cURL error 28: Operation timed out after 30000 milliseconds')
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify timeout was classified as network error
        $this->fileUpload->refresh();
        $this->assertEquals('network_error', $this->fileUpload->cloud_storage_error_type);
        $this->assertStringContains('Network connection issue', $this->fileUpload->cloud_storage_error_context['user_message']);

        // Verify retry was scheduled
        Queue::assertPushed(UploadToGoogleDrive::class);
    }

    /** @test */
    public function it_handles_ssl_certificate_errors()
    {
        Queue::fake();

        // Mock SSL certificate error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new \Exception('cURL error 60: SSL certificate problem: unable to get local issuer certificate')
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify SSL error was classified as network error
        $this->fileUpload->refresh();
        $this->assertEquals('network_error', $this->fileUpload->cloud_storage_error_type);
        $this->assertStringContains('Network connection issue', $this->fileUpload->cloud_storage_error_context['user_message']);

        // Verify health status shows network issues
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('degraded', $healthStatus->status);
        $this->assertEquals('network_error', $healthStatus->last_error_type);
    }

    /** @test */
    public function it_recovers_from_network_failure_after_connection_restored()
    {
        Queue::fake();

        // First, simulate network failure
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new \Exception('cURL error 7: Failed to connect to www.googleapis.com')
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify initial failure
        $this->fileUpload->refresh();
        $this->assertEquals('network_error', $this->fileUpload->cloud_storage_error_type);

        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('degraded', $healthStatus->status);
        $this->assertEquals(1, $healthStatus->consecutive_failures);

        // Now simulate network recovery
        Http::fake([
            'www.googleapis.com/*' => Http::response(['id' => 'recovered_file_123'], 200),
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'new_token'], 200)
        ]);

        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $driveFile = new \Google\Service\Drive\DriveFile();
            $driveFile->setId('recovered_file_123');
            $driveFile->setName('network-test.pdf');
            
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn($driveFile);
        });

        // Clear the error to allow retry
        $this->fileUpload->update([
            'cloud_storage_error_type' => null,
            'cloud_storage_error_context' => null
        ]);

        // Retry the job
        $retryJob = new UploadToGoogleDrive($this->fileUpload);
        $retryJob->handle();

        // Verify successful recovery
        $this->fileUpload->refresh();
        $this->assertEquals('recovered_file_123', $this->fileUpload->google_drive_file_id);
        $this->assertNull($this->fileUpload->cloud_storage_error_type);

        // Verify health status recovery
        $healthStatus->refresh();
        $this->assertEquals('healthy', $healthStatus->status);
        $this->assertEquals(0, $healthStatus->consecutive_failures);
        $this->assertNotNull($healthStatus->last_successful_operation_at);
    }

    /** @test */
    public function it_escalates_after_multiple_network_failures()
    {
        Queue::fake();

        // Simulate multiple consecutive network failures
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new \Exception('cURL error 7: Failed to connect to www.googleapis.com')
            );
        });

        // Create multiple uploads to simulate batch failure
        $uploads = FileUpload::factory()->count(5)->create([
            'uploaded_by_user_id' => $this->user->id,
            'storage_provider' => 'google-drive'
        ]);

        foreach ($uploads as $upload) {
            $job = new UploadToGoogleDrive($upload);
            $job->handle();
        }

        // Verify health status shows escalated failure
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        
        $this->assertEquals('unhealthy', $healthStatus->status);
        $this->assertEquals(5, $healthStatus->consecutive_failures);
        $this->assertEquals('network_error', $healthStatus->last_error_type);

        // Verify all uploads were marked with network error
        foreach ($uploads as $upload) {
            $upload->refresh();
            $this->assertEquals('network_error', $upload->cloud_storage_error_type);
        }
    }

    /** @test */
    public function it_handles_intermittent_network_issues()
    {
        Queue::fake();

        // Create multiple uploads
        $uploads = FileUpload::factory()->count(3)->create([
            'uploaded_by_user_id' => $this->user->id,
            'storage_provider' => 'google-drive'
        ]);

        $callCount = 0;
        $this->mock(\Google\Service\Drive::class, function ($mock) use (&$callCount) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                
                // Fail on odd calls, succeed on even calls (intermittent failure)
                if ($callCount % 2 === 1) {
                    throw new \Exception('cURL error 7: Failed to connect to www.googleapis.com');
                }
                
                $driveFile = new \Google\Service\Drive\DriveFile();
                $driveFile->setId('intermittent_success_' . $callCount);
                $driveFile->setName('test-file.pdf');
                return $driveFile;
            });
        });

        // Process uploads
        foreach ($uploads as $upload) {
            $job = new UploadToGoogleDrive($upload);
            $job->handle();
        }

        // Verify mixed results - some failed, some succeeded
        $uploads->each(function ($upload) {
            $upload->refresh();
        });

        $failedUploads = $uploads->filter(function ($upload) {
            return $upload->cloud_storage_error_type === 'network_error';
        });

        $successfulUploads = $uploads->filter(function ($upload) {
            return !is_null($upload->google_drive_file_id);
        });

        // Should have both failures and successes due to intermittent nature
        $this->assertGreaterThan(0, $failedUploads->count());
        $this->assertGreaterThan(0, $successfulUploads->count());

        // Health status should reflect partial degradation
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        
        // Status could be degraded or healthy depending on the last operation
        $this->assertContains($healthStatus->status, ['healthy', 'degraded']);
    }
}