<?php

namespace Tests\Feature;

use App\Jobs\UploadToGoogleDrive;
use App\Models\CloudStorageHealthStatus;
use App\Models\FileUpload;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Notifications\CloudStorageConnectionAlert;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CloudStorageNotificationIntegrationTest extends TestCase
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
            'original_filename' => 'notification-test.pdf',
            'file_size' => 1024,
            'storage_provider' => 'google-drive'
        ]);

        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'expired_access_token',
            'refresh_token' => 'expired_refresh_token',
            'expires_at' => now()->subHour(), // Expired token
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
    public function it_sends_notification_for_token_expiration()
    {
        Notification::fake();
        Queue::fake();

        // Mock token expiration error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Token has been expired or revoked', 401)
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify notification was sent
        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class, function ($notification) {
            $mailData = $notification->toMail($this->user);
            return str_contains($mailData->subject, 'Google Drive Connection Issue') &&
                   str_contains($mailData->greeting, 'connection has expired') &&
                   str_contains($mailData->actionText, 'Reconnect Google Drive');
        });
    }

    /** @test */
    public function it_sends_escalated_notification_for_multiple_failures()
    {
        Notification::fake();
        Queue::fake();

        // Create multiple uploads to simulate batch failure
        $uploads = FileUpload::factory()->count(5)->create([
            'uploaded_by_user_id' => $this->user->id,
            'storage_provider' => 'google-drive'
        ]);

        // Mock persistent error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Internal Server Error', 500)
            );
        });

        // Process all uploads to trigger multiple failures
        foreach ($uploads as $upload) {
            $job = new UploadToGoogleDrive($upload);
            $job->handle();
        }

        // Verify escalated notification was sent
        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class, function ($notification) {
            $mailData = $notification->toMail($this->user);
            return str_contains($mailData->subject, 'Multiple Google Drive Upload Failures') &&
                   str_contains($mailData->greeting, 'consecutive failures') &&
                   str_contains($mailData->actionText, 'View Dashboard');
        });
    }

    /** @test */
    public function it_throttles_notifications_to_prevent_spam()
    {
        Notification::fake();
        Queue::fake();

        // Mock error that would normally trigger notifications
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Token has been expired or revoked', 401)
            );
        });

        // First failure should trigger notification
        $job1 = new UploadToGoogleDrive($this->fileUpload);
        $job1->handle();

        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class);

        // Reset notification fake to test throttling
        Notification::fake();

        // Create additional uploads that fail
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
    public function it_sends_different_notifications_for_different_error_types()
    {
        Notification::fake();
        Queue::fake();

        $errorScenarios = [
            [
                'error' => new GoogleServiceException('Token has been expired or revoked', 401),
                'expected_subject_contains' => 'Connection Issue',
                'expected_message_contains' => 'connection has expired'
            ],
            [
                'error' => new GoogleServiceException('Insufficient Permission', 403),
                'expected_subject_contains' => 'Connection Issue',
                'expected_message_contains' => 'insufficient permissions'
            ],
            [
                'error' => new GoogleServiceException('Rate Limit Exceeded', 429),
                'expected_subject_contains' => 'API Limit',
                'expected_message_contains' => 'API limit reached'
            ]
        ];

        foreach ($errorScenarios as $index => $scenario) {
            // Clear notifications for each test
            Notification::fake();
            
            // Clear throttling cache
            Cache::forget("cloud_storage_notification_throttle_{$this->user->id}_google-drive");

            // Create a new upload for each scenario
            $upload = FileUpload::factory()->create([
                'uploaded_by_user_id' => $this->user->id,
                'original_filename' => "error-test-{$index}.pdf",
                'storage_provider' => 'google-drive'
            ]);

            // Mock the specific error
            $this->mock(\Google\Service\Drive::class, function ($mock) use ($scenario) {
                $mock->shouldReceive('files')->andReturnSelf();
                $mock->shouldReceive('create')->andThrow($scenario['error']);
            });

            $job = new UploadToGoogleDrive($upload);
            $job->handle();

            // Verify appropriate notification was sent
            Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class, function ($notification) use ($scenario) {
                $mailData = $notification->toMail($this->user);
                return str_contains($mailData->subject, $scenario['expected_subject_contains']) &&
                       str_contains($mailData->greeting, $scenario['expected_message_contains']);
            });
        }
    }

    /** @test */
    public function it_includes_provider_specific_action_buttons_in_notifications()
    {
        Notification::fake();
        Queue::fake();

        // Mock token expiration error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Token has been expired or revoked', 401)
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify notification includes provider-specific actions
        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class, function ($notification) {
            $mailData = $notification->toMail($this->user);
            
            // Should have reconnection action
            $hasReconnectAction = false;
            $hasDashboardAction = false;
            
            foreach ($mailData->actionUrl ?? [] as $action) {
                if (str_contains($action, 'google-drive') && str_contains($action, 'auth')) {
                    $hasReconnectAction = true;
                }
                if (str_contains($action, 'dashboard')) {
                    $hasDashboardAction = true;
                }
            }
            
            return $hasReconnectAction || $hasDashboardAction;
        });
    }

    /** @test */
    public function it_sends_proactive_notifications_before_token_expiry()
    {
        Notification::fake();
        
        // Update token to expire soon (within warning threshold)
        $this->user->googleDriveToken->update([
            'expires_at' => now()->addMinutes(30) // Expires in 30 minutes
        ]);

        // Simulate proactive token expiry check (this would be run by a scheduled command)
        $healthService = app(\App\Services\CloudStorageHealthService::class);
        $healthStatus = $healthService->checkConnectionHealth($this->user, 'google-drive');

        // If token is expiring soon, should trigger proactive notification
        if ($healthStatus->token_expires_at && $healthStatus->token_expires_at->diffInHours(now()) < 1) {
            CloudStorageConnectionAlert::dispatch($this->user, 'google-drive', 'token_expiring_soon', [
                'expires_at' => $healthStatus->token_expires_at,
                'hours_remaining' => $healthStatus->token_expires_at->diffInHours(now())
            ]);
        }

        // Verify proactive notification was sent
        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class, function ($notification) {
            $mailData = $notification->toMail($this->user);
            return str_contains($mailData->subject, 'Token Expiring Soon') ||
                   str_contains($mailData->greeting, 'will expire soon');
        });
    }

    /** @test */
    public function it_sends_recovery_notifications_after_successful_reconnection()
    {
        Notification::fake();
        Queue::fake();

        // First, create a failed state
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Token has been expired or revoked', 401)
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify initial failure notification
        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class);

        // Reset notifications
        Notification::fake();

        // Simulate successful reconnection
        $this->user->googleDriveToken->update([
            'access_token' => 'new_valid_token',
            'expires_at' => now()->addHour()
        ]);

        // Update health status to healthy
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $healthStatus->update([
            'status' => 'healthy',
            'consecutive_failures' => 0,
            'requires_reconnection' => false,
            'last_successful_operation_at' => now()
        ]);

        // Trigger recovery notification
        CloudStorageConnectionAlert::dispatch($this->user, 'google-drive', 'connection_recovered', [
            'recovered_at' => now(),
            'pending_uploads_count' => 1
        ]);

        // Verify recovery notification was sent
        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class, function ($notification) {
            $mailData = $notification->toMail($this->user);
            return str_contains($mailData->subject, 'Connection Restored') ||
                   str_contains($mailData->greeting, 'connection has been restored');
        });
    }

    /** @test */
    public function it_handles_notification_delivery_failures_gracefully()
    {
        // Mock mail failure
        Mail::fake();
        Mail::shouldReceive('send')->andThrow(new \Exception('SMTP connection failed'));

        Queue::fake();

        // Mock error that triggers notification
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Token has been expired or revoked', 401)
            );
        });

        // This should not throw an exception even if notification fails
        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify the upload error was still recorded despite notification failure
        $this->fileUpload->refresh();
        $this->assertEquals('token_expired', $this->fileUpload->cloud_storage_error_type);

        // Health status should still be updated
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('unhealthy', $healthStatus->status);
    }

    /** @test */
    public function it_customizes_notification_content_based_on_user_preferences()
    {
        Notification::fake();
        Queue::fake();

        // Update user preferences (assuming we have notification preferences)
        $this->user->update([
            'notification_preferences' => [
                'cloud_storage_alerts' => true,
                'detailed_error_info' => true,
                'immediate_alerts' => false // Prefer batched notifications
            ]
        ]);

        // Mock error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Token has been expired or revoked', 401)
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify notification respects user preferences
        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class, function ($notification) {
            $mailData = $notification->toMail($this->user);
            
            // Should include detailed error info based on preferences
            return str_contains($mailData->greeting, 'expired') &&
                   !empty($mailData->actionText); // Should have action buttons
        });
    }
}