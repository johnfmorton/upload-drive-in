<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\FileUpload;
use App\Models\GoogleDriveToken;
use App\Services\GoogleDriveService;
use App\Services\GoogleDriveProvider;
use App\Services\CloudStorageHealthService;
use App\Jobs\UploadToGoogleDrive;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoogleDriveEnhancedReconnectionTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com'
        ]);

        $this->employeeUser = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee@example.com',
            'username' => 'employee'
        ]);
    }

    public function test_oauth_callback_success_with_reconnection_and_retry()
    {
        // Arrange
        Queue::fake();
        
        // Create pending uploads that should be retried
        $upload1 = FileUpload::factory()->create([
            'company_user_id' => $this->adminUser->id,
            'google_drive_file_id' => null,
            'cloud_storage_error_type' => 'token_expired',
            'original_filename' => 'test1.pdf'
        ]);

        $upload2 = FileUpload::factory()->create([
            'uploaded_by_user_id' => $this->adminUser->id,
            'google_drive_file_id' => null,
            'cloud_storage_error_type' => 'insufficient_permissions',
            'original_filename' => 'test2.docx'
        ]);

        // Mock the Google Drive service
        $this->mock(GoogleDriveService::class, function ($mock) {
            $mock->shouldReceive('handleCallback')
                ->once()
                ->with($this->adminUser, 'test_auth_code')
                ->andReturn(true);
        });

        // Mock the provider for health check
        $this->mock(GoogleDriveProvider::class, function ($mock) {
            $mock->shouldReceive('getConnectionHealth')
                ->once()
                ->with($this->adminUser)
                ->andReturn(\App\Services\CloudStorageHealthStatus::healthy('google-drive'));
        });

        // Prepare state parameter
        $state = base64_encode(json_encode([
            'user_id' => $this->adminUser->id,
            'user_type' => 'admin',
            'is_reconnection' => true,
            'timestamp' => now()->timestamp
        ]));

        // Act
        $response = $this->get(route('google-drive.unified-callback', [
            'code' => 'test_auth_code',
            'state' => $state
        ]));

        // Assert
        $response->assertRedirect(route('admin.cloud-storage.index'));
        $response->assertSessionHas('success');
        
        $successMessage = session('success');
        $this->assertStringContainsString('Successfully connected to Google Drive', $successMessage);
        $this->assertStringContainsString('pending uploads have been queued', $successMessage);

        // Verify uploads were queued for retry
        Queue::assertPushed(UploadToGoogleDrive::class, 2);

        // Verify upload records were updated
        $upload1->refresh();
        $upload2->refresh();
        $this->assertNull($upload1->cloud_storage_error_type);
        $this->assertNull($upload2->cloud_storage_error_type);
        $this->assertNotNull($upload1->retry_recommended_at);
        $this->assertNotNull($upload2->retry_recommended_at);
    }

    public function test_oauth_callback_success_without_reconnection()
    {
        // Arrange
        Queue::fake();

        // Mock the Google Drive service
        $this->mock(GoogleDriveService::class, function ($mock) {
            $mock->shouldReceive('handleCallback')
                ->once()
                ->with($this->adminUser, 'test_auth_code')
                ->andReturn(true);
        });

        // Mock the provider for health check
        $this->mock(GoogleDriveProvider::class, function ($mock) {
            $mock->shouldReceive('getConnectionHealth')
                ->once()
                ->with($this->adminUser)
                ->andReturn(\App\Services\CloudStorageHealthStatus::healthy('google-drive'));
        });

        // Prepare state parameter (not a reconnection)
        $state = base64_encode(json_encode([
            'user_id' => $this->adminUser->id,
            'user_type' => 'admin',
            'is_reconnection' => false,
            'timestamp' => now()->timestamp
        ]));

        // Act
        $response = $this->get(route('google-drive.unified-callback', [
            'code' => 'test_auth_code',
            'state' => $state
        ]));

        // Assert
        $response->assertRedirect(route('admin.cloud-storage.index'));
        $response->assertSessionHas('success', 'Successfully connected to Google Drive!');

        // Verify no jobs were queued since it's not a reconnection
        Queue::assertNothingPushed();
    }

    public function test_oauth_callback_employee_user_success()
    {
        // Arrange
        Queue::fake();

        // Mock the Google Drive service
        $this->mock(GoogleDriveService::class, function ($mock) {
            $mock->shouldReceive('handleCallback')
                ->once()
                ->with($this->employeeUser, 'test_auth_code')
                ->andReturn(true);
        });

        // Mock the provider for health check
        $this->mock(GoogleDriveProvider::class, function ($mock) {
            $mock->shouldReceive('getConnectionHealth')
                ->once()
                ->with($this->employeeUser)
                ->andReturn(\App\Services\CloudStorageHealthStatus::healthy('google-drive'));
        });

        // Prepare state parameter
        $state = base64_encode(json_encode([
            'user_id' => $this->employeeUser->id,
            'user_type' => 'employee',
            'is_reconnection' => false,
            'timestamp' => now()->timestamp
        ]));

        // Act
        $response = $this->get(route('google-drive.unified-callback', [
            'code' => 'test_auth_code',
            'state' => $state
        ]));

        // Assert
        $response->assertRedirect(route('employee.cloud-storage.index', ['username' => 'employee']));
        $response->assertSessionHas('success', 'Successfully connected to Google Drive!');
    }

    public function test_oauth_callback_missing_code()
    {
        // Arrange
        $state = base64_encode(json_encode([
            'user_id' => $this->adminUser->id,
            'user_type' => 'admin'
        ]));

        // Act
        $response = $this->get(route('google-drive.unified-callback', [
            'state' => $state
        ]));

        // Assert
        $response->assertRedirect(route('admin.cloud-storage.index'));
        $response->assertSessionHas('error', 'Authorization code not provided.');
    }

    public function test_oauth_callback_oauth_error_response()
    {
        // Arrange
        $state = base64_encode(json_encode([
            'user_id' => $this->adminUser->id,
            'user_type' => 'admin'
        ]));

        // Act
        $response = $this->get(route('google-drive.unified-callback', [
            'error' => 'access_denied',
            'error_description' => 'User denied access',
            'state' => $state
        ]));

        // Assert
        $response->assertRedirect(route('admin.cloud-storage.index'));
        $response->assertSessionHas('error', 'Authorization failed: User denied access');
    }

    public function test_oauth_callback_missing_state()
    {
        // Act
        $response = $this->get(route('google-drive.unified-callback', [
            'code' => 'test_auth_code'
        ]));

        // Assert
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'State parameter missing.');
    }

    public function test_oauth_callback_invalid_state()
    {
        // Act
        $response = $this->get(route('google-drive.unified-callback', [
            'code' => 'test_auth_code',
            'state' => 'invalid_state'
        ]));

        // Assert
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Invalid state parameter.');
    }

    public function test_oauth_callback_user_not_found()
    {
        // Arrange
        $state = base64_encode(json_encode([
            'user_id' => 99999, // Non-existent user
            'user_type' => 'admin'
        ]));

        // Act
        $response = $this->get(route('google-drive.unified-callback', [
            'code' => 'test_auth_code',
            'state' => $state
        ]));

        // Assert
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'User not found.');
    }

    public function test_oauth_callback_service_exception()
    {
        // Arrange
        $this->mock(GoogleDriveService::class, function ($mock) {
            $mock->shouldReceive('handleCallback')
                ->once()
                ->with($this->adminUser, 'test_auth_code')
                ->andThrow(new \Exception('OAuth service error'));
        });

        $state = base64_encode(json_encode([
            'user_id' => $this->adminUser->id,
            'user_type' => 'admin'
        ]));

        // Act
        $response = $this->get(route('google-drive.unified-callback', [
            'code' => 'test_auth_code',
            'state' => $state
        ]));

        // Assert
        $response->assertRedirect(route('admin.cloud-storage.index'));
        $response->assertSessionHas('error');
        
        $errorMessage = session('error');
        $this->assertStringContainsString('OAuth service error', $errorMessage);
    }

    public function test_oauth_callback_connection_validation_failure()
    {
        // Arrange
        $this->mock(GoogleDriveService::class, function ($mock) {
            $mock->shouldReceive('handleCallback')
                ->once()
                ->with($this->adminUser, 'test_auth_code')
                ->andReturn(true);
        });

        // Mock the provider to return unhealthy status
        $this->mock(GoogleDriveProvider::class, function ($mock) {
            $mock->shouldReceive('getConnectionHealth')
                ->once()
                ->with($this->adminUser)
                ->andReturn(\App\Services\CloudStorageHealthStatus::unhealthy(
                    'google-drive',
                    consecutiveFailures: 1,
                    lastErrorType: \App\Enums\CloudStorageErrorType::TOKEN_EXPIRED,
                    lastErrorMessage: 'Token validation failed',
                    requiresReconnection: true
                ));
        });

        $state = base64_encode(json_encode([
            'user_id' => $this->adminUser->id,
            'user_type' => 'admin'
        ]));

        // Act
        $response = $this->get(route('google-drive.unified-callback', [
            'code' => 'test_auth_code',
            'state' => $state
        ]));

        // Assert
        $response->assertRedirect(route('admin.cloud-storage.index'));
        $response->assertSessionHas('error', 'Connection established but validation failed. Please try reconnecting again.');
    }

    public function test_oauth_callback_specific_error_handling()
    {
        // Test invalid_grant error
        $this->mock(GoogleDriveService::class, function ($mock) {
            $mock->shouldReceive('handleCallback')
                ->once()
                ->andThrow(new \Exception('invalid_grant: Token expired'));
        });

        $state = base64_encode(json_encode([
            'user_id' => $this->adminUser->id,
            'user_type' => 'admin'
        ]));

        $response = $this->get(route('google-drive.unified-callback', [
            'code' => 'test_auth_code',
            'state' => $state
        ]));

        $response->assertRedirect(route('admin.cloud-storage.index'));
        $response->assertSessionHas('error', 'The authorization code has expired. Please try connecting again.');
    }

    public function test_oauth_callback_only_retries_connection_error_uploads()
    {
        // Arrange
        Queue::fake();

        // Create uploads with different error types
        $retryableUpload = FileUpload::factory()->create([
            'company_user_id' => $this->adminUser->id,
            'google_drive_file_id' => null,
            'cloud_storage_error_type' => 'token_expired', // Should be retried
            'original_filename' => 'retryable.pdf'
        ]);

        $nonRetryableUpload = FileUpload::factory()->create([
            'company_user_id' => $this->adminUser->id,
            'google_drive_file_id' => null,
            'cloud_storage_error_type' => 'file_too_large', // Should NOT be retried
            'original_filename' => 'too_large.zip'
        ]);

        $completedUpload = FileUpload::factory()->create([
            'company_user_id' => $this->adminUser->id,
            'google_drive_file_id' => 'existing-file-id', // Already completed
            'original_filename' => 'completed.pdf'
        ]);

        $this->mock(GoogleDriveService::class, function ($mock) {
            $mock->shouldReceive('handleCallback')->once()->andReturn(true);
        });

        $this->mock(GoogleDriveProvider::class, function ($mock) {
            $mock->shouldReceive('getConnectionHealth')
                ->once()
                ->andReturn(\App\Services\CloudStorageHealthStatus::healthy('google-drive'));
        });

        $state = base64_encode(json_encode([
            'user_id' => $this->adminUser->id,
            'user_type' => 'admin',
            'is_reconnection' => true
        ]));

        // Act
        $response = $this->get(route('google-drive.unified-callback', [
            'code' => 'test_auth_code',
            'state' => $state
        ]));

        // Assert
        $response->assertRedirect(route('admin.cloud-storage.index'));
        
        $successMessage = session('success');
        $this->assertStringContainsString('1 pending uploads have been queued', $successMessage);

        // Verify only the retryable upload was queued
        Queue::assertPushed(UploadToGoogleDrive::class, 1);

        // Verify only the retryable upload was updated
        $retryableUpload->refresh();
        $nonRetryableUpload->refresh();
        $completedUpload->refresh();

        $this->assertNull($retryableUpload->cloud_storage_error_type);
        $this->assertEquals('file_too_large', $nonRetryableUpload->cloud_storage_error_type);
        $this->assertEquals('existing-file-id', $completedUpload->google_drive_file_id);
    }

    public function test_oauth_callback_respects_retry_limit()
    {
        // Arrange
        Queue::fake();

        // Create upload that was retried recently (should be skipped)
        $recentUpload = FileUpload::factory()->create([
            'company_user_id' => $this->adminUser->id,
            'google_drive_file_id' => null,
            'cloud_storage_error_type' => 'token_expired',
            'retry_recommended_at' => now()->subMinutes(2), // Too recent
            'original_filename' => 'recent.pdf'
        ]);

        // Create upload that can be retried
        $oldUpload = FileUpload::factory()->create([
            'company_user_id' => $this->adminUser->id,
            'google_drive_file_id' => null,
            'cloud_storage_error_type' => 'token_expired',
            'retry_recommended_at' => now()->subMinutes(10), // Old enough
            'original_filename' => 'old.pdf'
        ]);

        $this->mock(GoogleDriveService::class, function ($mock) {
            $mock->shouldReceive('handleCallback')->once()->andReturn(true);
        });

        $this->mock(GoogleDriveProvider::class, function ($mock) {
            $mock->shouldReceive('getConnectionHealth')
                ->once()
                ->andReturn(\App\Services\CloudStorageHealthStatus::healthy('google-drive'));
        });

        $state = base64_encode(json_encode([
            'user_id' => $this->adminUser->id,
            'user_type' => 'admin',
            'is_reconnection' => true
        ]));

        // Act
        $response = $this->get(route('google-drive.unified-callback', [
            'code' => 'test_auth_code',
            'state' => $state
        ]));

        // Assert
        $response->assertRedirect(route('admin.cloud-storage.index'));
        
        $successMessage = session('success');
        $this->assertStringContainsString('1 pending uploads have been queued', $successMessage);

        // Verify only one job was queued
        Queue::assertPushed(UploadToGoogleDrive::class, 1);

        // Verify only the old upload was updated
        $recentUpload->refresh();
        $oldUpload->refresh();

        $this->assertEquals('token_expired', $recentUpload->cloud_storage_error_type); // Unchanged
        $this->assertNull($oldUpload->cloud_storage_error_type); // Cleared
    }
}