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
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GoogleDrivePermissionErrorIntegrationTest extends TestCase
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
            'original_filename' => 'permission-test.pdf',
            'file_size' => 1024,
            'storage_provider' => 'google-drive'
        ]);

        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'access_token' => 'limited_access_token',
            'refresh_token' => 'limited_refresh_token',
            'expires_at' => now()->addHour(),
            'token_type' => 'Bearer',
            'scopes' => ['https://www.googleapis.com/auth/drive.readonly'] // Limited scope
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
    public function it_handles_insufficient_scope_permissions()
    {
        Notification::fake();
        Queue::fake();

        // Mock Google API insufficient permissions error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Insufficient Permission: Request had insufficient authentication scopes.', 403)
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify permission error was classified correctly
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

        // Verify notification was sent with guidance
        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class, function ($notification) {
            $mailData = $notification->toMail($this->user);
            return str_contains($mailData->subject, 'Google Drive Connection Issue') &&
                   str_contains($mailData->greeting, 'reconnect and grant full access');
        });

        // Job should not be retried for permission errors
        Queue::assertNotPushed(UploadToGoogleDrive::class);
    }

    /** @test */
    public function it_handles_folder_access_denied_errors()
    {
        Notification::fake();
        Queue::fake();

        // Mock Google API folder access denied error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('The user does not have sufficient permissions for folder', 403)
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify folder access error was classified correctly
        $this->fileUpload->refresh();
        $this->assertEquals('folder_access_denied', $this->fileUpload->cloud_storage_error_type);
        $this->assertStringContains('Cannot access the configured Google Drive folder', $this->fileUpload->cloud_storage_error_context['user_message']);
        $this->assertStringContains('check folder permissions', $this->fileUpload->cloud_storage_error_context['user_message']);

        // Verify health status reflects folder access issues
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('unhealthy', $healthStatus->status);
        $this->assertTrue($healthStatus->requires_reconnection);
        $this->assertEquals('folder_access_denied', $healthStatus->last_error_type);

        // Verify notification includes folder-specific guidance
        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class, function ($notification) {
            $mailData = $notification->toMail($this->user);
            return str_contains($mailData->subject, 'Google Drive Connection Issue') &&
                   str_contains($mailData->greeting, 'folder permissions');
        });
    }

    /** @test */
    public function it_handles_domain_policy_restrictions()
    {
        Notification::fake();
        Queue::fake();

        // Mock Google API domain policy restriction error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Domain policy restricts this action', 403)
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify domain policy error was classified correctly
        $this->fileUpload->refresh();
        $this->assertEquals('insufficient_permissions', $this->fileUpload->cloud_storage_error_type);
        $this->assertStringContains('Insufficient Google Drive permissions', $this->fileUpload->cloud_storage_error_context['user_message']);

        // Verify health status reflects policy restrictions
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('unhealthy', $healthStatus->status);
        $this->assertTrue($healthStatus->requires_reconnection);
        $this->assertEquals('insufficient_permissions', $healthStatus->last_error_type);

        // Verify notification includes domain policy context
        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class);
    }

    /** @test */
    public function it_provides_specific_guidance_for_different_permission_errors()
    {
        Notification::fake();

        // Test different permission error scenarios
        $permissionScenarios = [
            [
                'error_message' => 'Insufficient Permission: Request had insufficient authentication scopes.',
                'expected_guidance' => 'reconnect and grant full access',
                'expected_error_type' => 'insufficient_permissions'
            ],
            [
                'error_message' => 'The user does not have sufficient permissions for folder',
                'expected_guidance' => 'check folder permissions',
                'expected_error_type' => 'folder_access_denied'
            ],
            [
                'error_message' => 'Access denied. You do not have permission to access this resource.',
                'expected_guidance' => 'reconnect and grant full access',
                'expected_error_type' => 'insufficient_permissions'
            ]
        ];

        foreach ($permissionScenarios as $index => $scenario) {
            // Create a new upload for each scenario
            $upload = FileUpload::factory()->create([
                'uploaded_by_user_id' => $this->user->id,
                'original_filename' => "permission-test-{$index}.pdf",
                'storage_provider' => 'google-drive'
            ]);

            // Mock the specific error
            $this->mock(\Google\Service\Drive::class, function ($mock) use ($scenario) {
                $mock->shouldReceive('files')->andReturnSelf();
                $mock->shouldReceive('create')->andThrow(
                    new GoogleServiceException($scenario['error_message'], 403)
                );
            });

            $job = new UploadToGoogleDrive($upload);
            $job->handle();

            // Verify correct classification and guidance
            $upload->refresh();
            $this->assertEquals($scenario['expected_error_type'], $upload->cloud_storage_error_type);
            $this->assertStringContains($scenario['expected_guidance'], $upload->cloud_storage_error_context['user_message']);
        }
    }

    /** @test */
    public function it_handles_permission_recovery_after_reconnection()
    {
        Notification::fake();
        Queue::fake();

        // First, simulate permission error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Insufficient Permission: Request had insufficient authentication scopes.', 403)
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify initial permission failure
        $this->fileUpload->refresh();
        $this->assertEquals('insufficient_permissions', $this->fileUpload->cloud_storage_error_type);

        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('unhealthy', $healthStatus->status);
        $this->assertTrue($healthStatus->requires_reconnection);

        // Simulate successful reconnection with proper permissions
        $this->user->googleDriveToken->update([
            'scopes' => ['https://www.googleapis.com/auth/drive.file'] // Full scope
        ]);

        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $driveFile = new \Google\Service\Drive\DriveFile();
            $driveFile->setId('permission_recovered_123');
            $driveFile->setName('permission-test.pdf');
            
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn($driveFile);
        });

        // Clear the error to allow retry after reconnection
        $this->fileUpload->update([
            'cloud_storage_error_type' => null,
            'cloud_storage_error_context' => null
        ]);

        // Retry the job after reconnection
        $retryJob = new UploadToGoogleDrive($this->fileUpload);
        $retryJob->handle();

        // Verify successful recovery
        $this->fileUpload->refresh();
        $this->assertEquals('permission_recovered_123', $this->fileUpload->google_drive_file_id);
        $this->assertNull($this->fileUpload->cloud_storage_error_type);

        // Verify health status recovery
        $healthStatus->refresh();
        $this->assertEquals('healthy', $healthStatus->status);
        $this->assertFalse($healthStatus->requires_reconnection);
        $this->assertEquals(0, $healthStatus->consecutive_failures);
    }

    /** @test */
    public function it_handles_multiple_permission_failures_across_uploads()
    {
        Notification::fake();
        Queue::fake();

        // Create multiple uploads to test batch permission failures
        $uploads = FileUpload::factory()->count(5)->create([
            'uploaded_by_user_id' => $this->user->id,
            'storage_provider' => 'google-drive'
        ]);

        // Mock permission error for all uploads
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Insufficient Permission: Request had insufficient authentication scopes.', 403)
            );
        });

        // Process all uploads
        foreach ($uploads as $upload) {
            $job = new UploadToGoogleDrive($upload);
            $job->handle();
        }

        // Verify all uploads failed with permission errors
        foreach ($uploads as $upload) {
            $upload->refresh();
            $this->assertEquals('insufficient_permissions', $upload->cloud_storage_error_type);
        }

        // Verify health status reflects multiple permission failures
        $healthStatus = CloudStorageHealthStatus::where('user_id', $this->user->id)
            ->where('provider', 'google-drive')
            ->first();
        $this->assertEquals('unhealthy', $healthStatus->status);
        $this->assertTrue($healthStatus->requires_reconnection);
        $this->assertEquals(5, $healthStatus->consecutive_failures);

        // Should only send one notification despite multiple failures
        Notification::assertSentTo($this->user, CloudStorageConnectionAlert::class, function ($notification) {
            $mailData = $notification->toMail($this->user);
            return str_contains($mailData->subject, 'Google Drive Connection Issue');
        });
    }

    /** @test */
    public function it_provides_reconnection_url_in_error_context()
    {
        Queue::fake();

        // Mock permission error
        $this->mock(\Google\Service\Drive::class, function ($mock) {
            $mock->shouldReceive('files')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(
                new GoogleServiceException('Insufficient Permission: Request had insufficient authentication scopes.', 403)
            );
        });

        $job = new UploadToGoogleDrive($this->fileUpload);
        $job->handle();

        // Verify error context includes reconnection information
        $this->fileUpload->refresh();
        $errorContext = $this->fileUpload->cloud_storage_error_context;
        
        $this->assertArrayHasKey('user_message', $errorContext);
        $this->assertArrayHasKey('requires_reconnection', $errorContext);
        $this->assertTrue($errorContext['requires_reconnection']);
        
        // Should include guidance about reconnection
        $this->assertStringContains('reconnect', $errorContext['user_message']);
        $this->assertStringContains('grant full access', $errorContext['user_message']);
    }
}