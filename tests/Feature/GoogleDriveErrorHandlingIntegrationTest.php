<?php

namespace Tests\Feature;

use App\Enums\CloudStorageErrorType;
use App\Jobs\UploadToGoogleDrive;
use App\Models\CloudStorageHealthStatus;
use App\Models\FileUpload;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Notifications\CloudStorageConnectionAlert;
use App\Services\GoogleDriveProvider;
use Google\Service\Drive;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GoogleDriveErrorHandlingIntegrationTest extends TestCase
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
            'original_filename' => 'test-file.pdf',
            'file_size' => 1024,
            'storage_provider' => 'google-drive'
        ]);

        // Create a valid Google Drive token
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'valid_access_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => now()->addHour(),
            'token_type' => 'Bearer',
            'scopes' => ['https://www.googleapis.com/auth/drive.file']
        ]);

        // Initialize health status
        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'last_successful_operation_at' => now()->subMinutes(5),
            'consecutive_failures' => 0
        ]);
    }

    /** @test */
    public function it_handles_token_expiration_and_recovery_flow()
    {
        Notification::fake();
        Queue::fake();

        // Mock Google API to return token expired error
        $this->mockGoogleServiceException(401, 'Token has been expired or revoked');

        // Dispatch upload job
        UploadToGoogleDrive::dispatchSync($this->fileUpload);

        // Verify file upload was marked with token expired error
        $this->fileUpload->refresh();
        $this->assertEquals('token_expired', $this->fileUpload->cloud_storage_error_type);
        $this->assertStringContains('Google Drive connection has expired', $this->fileUpload->cloud_storage_error_context['user_message']);

        // Verify health status was updated
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('unhealthy', $healthStatus->status);
        $this->assertEquals('token_expired', $healthStatus->last_error_type);
        $this->assertTrue($healthStatus->requires_reconnection);

        // Verify notification was sent
        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class);

        // Simulate successful token refresh
        $this->mockSuccessfulTokenRefresh();
        
        // Simulate reconnection
        $provider = app(GoogleDriveProvider::class);
        $provider->handleAuthCallback($this->user, 'new_auth_code');

        // Verify health status was restored
        $healthStatus->refresh();
        $this->assertEquals('healthy', $healthStatus->status);
        $this->assertFalse($healthStatus->requires_reconnection);
        $this->assertEquals(0, $healthStatus->consecutive_failures);
    }

    /** @test */
    public function it_handles_api_quota_exceeded_with_retry_behavior()
    {
        Queue::fake();

        // Mock Google API to return quota exceeded error
        $this->mockGoogleServiceException(429, 'Rate Limit Exceeded');

        // Dispatch upload job
        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify file upload was marked with quota exceeded error
        $this->fileUpload->refresh();
        $this->assertEquals('api_quota_exceeded', $this->fileUpload->cloud_storage_error_type);
        $this->assertStringContains('Google Drive API limit reached', $this->fileUpload->cloud_storage_error_context['user_message']);

        // Verify health status shows degraded performance
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('degraded', $healthStatus->status);
        $this->assertEquals('api_quota_exceeded', $healthStatus->last_error_type);

        // Verify job was released back to queue for retry
        Queue::assertPushed(UploadToGoogleDrive::class, function ($job) {
            return $job->attempts() === 1;
        });

        // Simulate successful retry after quota reset
        $this->mockSuccessfulUpload();
        
        $retryJob = new UploadToGoogleDrive($this->fileUpload);
        $retryJob->handle();

        // Verify file upload was successful
        $this->fileUpload->refresh();
        $this->assertNotNull($this->fileUpload->google_drive_file_id);
        $this->assertNull($this->fileUpload->cloud_storage_error_type);

        // Verify health status was restored
        $healthStatus->refresh();
        $this->assertEquals('healthy', $healthStatus->status);
        $this->assertEquals(0, $healthStatus->consecutive_failures);
    }

    /** @test */
    public function it_handles_network_failure_simulation_and_recovery()
    {
        Queue::fake();

        // Mock network connection error
        Http::fake([
            'www.googleapis.com/*' => Http::response('', 500),
            'oauth2.googleapis.com/*' => Http::response('', 500)
        ]);

        // Mock cURL error for network failure
        $this->mockNetworkError();

        // Dispatch upload job
        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify file upload was marked with network error
        $this->fileUpload->refresh();
        $this->assertEquals('network_error', $this->fileUpload->cloud_storage_error_type);
        $this->assertStringContains('Network connection issue', $this->fileUpload->cloud_storage_error_context['user_message']);

        // Verify health status shows network issues
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('degraded', $healthStatus->status);
        $this->assertEquals('network_error', $healthStatus->last_error_type);
        $this->assertEquals(1, $healthStatus->consecutive_failures);

        // Verify job was released for retry
        Queue::assertPushed(UploadToGoogleDrive::class);

        // Simulate network recovery
        Http::fake([
            'www.googleapis.com/*' => Http::response(['id' => 'file123'], 200),
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'new_token'], 200)
        ]);

        $this->mockSuccessfulUpload();

        // Retry the job
        $retryJob = new UploadToGoogleDrive($this->fileUpload);
        $retryJob->handle();

        // Verify successful recovery
        $this->fileUpload->refresh();
        $this->assertNotNull($this->fileUpload->google_drive_file_id);
        $this->assertNull($this->fileUpload->cloud_storage_error_type);

        // Verify health status recovery
        $healthStatus->refresh();
        $this->assertEquals('healthy', $healthStatus->status);
        $this->assertEquals(0, $healthStatus->consecutive_failures);
    }

    /** @test */
    public function it_handles_permission_error_scenarios_with_user_guidance()
    {
        Notification::fake();

        // Mock Google API to return insufficient permissions error
        $this->mockGoogleServiceException(403, 'Insufficient Permission');

        // Dispatch upload job
        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify file upload was marked with permission error
        $this->fileUpload->refresh();
        $this->assertEquals('insufficient_permissions', $this->fileUpload->cloud_storage_error_type);
        $this->assertStringContains('Insufficient Google Drive permissions', $this->fileUpload->cloud_storage_error_context['user_message']);
        $this->assertStringContains('reconnect and grant full access', $this->fileUpload->cloud_storage_error_context['user_message']);

        // Verify health status requires reconnection
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('unhealthy', $healthStatus->status);
        $this->assertTrue($healthStatus->requires_reconnection);
        $this->assertEquals('insufficient_permissions', $healthStatus->last_error_type);

        // Verify notification includes guidance
        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class, function ($notification) {
            $mailData = $notification->toMail($this->user);
            return str_contains($mailData->subject, 'Google Drive Connection Issue') &&
                   str_contains($mailData->greeting, 'reconnect and grant full access');
        });
    }

    /** @test */
    public function it_verifies_dashboard_status_updates_during_error_conditions()
    {
        // Create multiple file uploads to test batch scenarios
        $uploads = FileUpload::factory()->count(3)->create([
            'uploaded_by_user_id' => $this->user->id,
            'storage_provider' => 'google-drive'
        ]);

        // Mock consecutive failures
        $this->mockGoogleServiceException(401, 'Token has been expired or revoked');

        foreach ($uploads as $upload) {
            $job = new UploadToGoogleDrive($upload);
            $job->handle();
        }

        // Verify health status reflects multiple failures
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        
        $this->assertEquals('unhealthy', $healthStatus->status);
        $this->assertEquals(3, $healthStatus->consecutive_failures);
        $this->assertTrue($healthStatus->requires_reconnection);

        // Test dashboard status endpoint
        $response = $this->actingAs($this->user)
            ->get('/admin/dashboard/cloud-storage-status');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consecutive_failures' => 3,
            'requires_reconnection' => true,
            'pending_uploads_count' => 3
        ]);

        // Verify error details are included
        $response->assertJsonFragment([
            'last_error_type' => 'token_expired',
            'user_friendly_message' => 'Your Google Drive connection has expired. Please reconnect to continue uploading files.'
        ]);
    }

    /** @test */
    public function it_tests_notification_delivery_for_various_error_scenarios()
    {
        Notification::fake();
        Mail::fake();

        // Test token expiration notification
        $this->mockGoogleServiceException(401, 'Token has been expired or revoked');
        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class, function ($notification) {
            $mailData = $notification->toMail($this->user);
            return str_contains($mailData->subject, 'Google Drive Connection Issue') &&
                   str_contains($mailData->greeting, 'connection has expired');
        });

        // Reset notifications
        Notification::fake();

        // Test multiple consecutive failures trigger alert
        $uploads = FileUpload::factory()->count(5)->create([
            'uploaded_by_user_id' => $this->user->id,
            'storage_provider' => 'google-drive'
        ]);

        $this->mockGoogleServiceException(500, 'Internal Server Error');

        foreach ($uploads as $upload) {
            $job = new UploadToGoogleDrive($upload);
            $job->handle();
        }

        // Verify escalated notification for multiple failures
        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class, function ($notification) {
            $mailData = $notification->toMail($this->user);
            return str_contains($mailData->subject, 'Multiple Google Drive Upload Failures') &&
                   str_contains($mailData->greeting, 'consecutive failures');
        });

        // Test notification throttling
        Notification::fake();
        
        // Additional failures should not trigger more notifications (throttled)
        $additionalUploads = FileUpload::factory()->count(3)->create([
            'uploaded_by_user_id' => $this->user->id,
            'storage_provider' => 'google-drive'
        ]);

        foreach ($additionalUploads as $upload) {
            $job = new UploadToGoogleDrive($upload);
            $job->handle();
        }

        // Should not send additional notifications due to throttling
        Notification::assertNotSentTo($this->user, CloudStorageConnectionAlert::class);
    }

    /** @test */
    public function it_tests_bulk_retry_functionality_after_error_recovery()
    {
        Queue::fake();

        // Create multiple failed uploads
        $uploads = FileUpload::factory()->count(5)->create([
            'uploaded_by_user_id' => $this->user->id,
            'storage_provider' => 'google-drive',
            'cloud_storage_error_type' => 'token_expired',
            'cloud_storage_error_context' => [
                'error_type' => 'token_expired',
                'user_message' => 'Google Drive connection has expired'
            ]
        ]);

        // Simulate successful reconnection
        $this->mockSuccessfulTokenRefresh();
        $this->mockSuccessfulUpload();

        // Test bulk retry endpoint
        $response = $this->actingAs($this->user)
            ->post('/admin/file-manager/bulk-retry', [
                'provider' => 'google-drive'
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
            'message' => 'Bulk retry initiated for 5 failed uploads'
        ]);

        // Verify jobs were queued for retry
        Queue::assertPushed(UploadToGoogleDrive::class, 5);

        // Verify health status was updated
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('healthy', $healthStatus->status);
        $this->assertEquals(0, $healthStatus->consecutive_failures);
    }

    private function mockGoogleServiceException(int $code, string $message): void
    {
        $this->mock(Drive::class, function ($mock) use ($code, $message) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(new GoogleServiceException($message, $code));
        });
    }

    private function mockNetworkError(): void
    {
        $this->mock(Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(new \Exception('cURL error 6: Could not resolve host'));
        });
    }

    private function mockSuccessfulUpload(): void
    {
        $this->mock(Drive::class, function ($mock) {
            $driveFile = new \Google\Service\Drive\DriveFile();
            $driveFile->setId('file123');
            $driveFile->setName('test-file.pdf');
            
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn($driveFile);
        });
    }

    private function mockSuccessfulTokenRefresh(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in' => 3600,
                'token_type' => 'Bearer'
            ], 200)
        ]);
    }
}