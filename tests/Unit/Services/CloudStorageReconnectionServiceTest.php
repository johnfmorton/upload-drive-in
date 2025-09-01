<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CloudStorageReconnectionService;
use App\Services\CloudStorageHealthService;
use App\Services\GoogleDriveProvider;
use App\Services\CloudStorageHealthStatus;
use App\Models\User;
use App\Models\FileUpload;
use App\Jobs\UploadToGoogleDrive;
use App\Enums\CloudStorageErrorType;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Mockery;

class CloudStorageReconnectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageReconnectionService $service;
    private CloudStorageHealthService $healthService;
    private GoogleDriveProvider $provider;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->healthService = Mockery::mock(CloudStorageHealthService::class);
        $this->provider = Mockery::mock(GoogleDriveProvider::class);
        $this->service = new CloudStorageReconnectionService($this->healthService);

        $this->user = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com'
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_initiate_reconnection_success()
    {
        // Arrange
        $provider = 'google-drive';
        $expectedAuthUrl = 'https://accounts.google.com/oauth/authorize?test=1';

        $this->healthService->shouldReceive('markConnectionAsUnhealthy')
            ->once()
            ->with(
                $this->user,
                $provider,
                'User initiated reconnection',
                CloudStorageErrorType::TOKEN_EXPIRED
            );

        $this->provider->shouldReceive('getAuthUrl')
            ->once()
            ->with($this->user, true)
            ->andReturn($expectedAuthUrl);

        // Act
        $result = $this->service->initiateReconnection($this->user, $provider, $this->provider);

        // Assert
        $this->assertEquals($expectedAuthUrl, $result);
    }

    public function test_initiate_reconnection_failure()
    {
        // Arrange
        $provider = 'google-drive';
        $exception = new \Exception('OAuth configuration error');

        $this->healthService->shouldReceive('markConnectionAsUnhealthy')
            ->once();

        $this->provider->shouldReceive('getAuthUrl')
            ->once()
            ->andThrow($exception);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OAuth configuration error');

        $this->service->initiateReconnection($this->user, $provider, $this->provider);
    }

    public function test_validate_connection_success()
    {
        // Arrange
        $provider = 'google-drive';
        $healthStatus = CloudStorageHealthStatus::healthy($provider);

        $this->provider->shouldReceive('getConnectionHealth')
            ->once()
            ->with($this->user)
            ->andReturn($healthStatus);

        $this->healthService->shouldReceive('recordSuccessfulOperation')
            ->once()
            ->with(
                $this->user,
                $provider,
                Mockery::on(function ($data) {
                    return $data['operation'] === 'reconnection_validation' &&
                           isset($data['validated_at']) &&
                           $data['health_status'] === 'healthy';
                })
            );

        // Act
        $result = $this->service->validateConnection($this->user, $provider, $this->provider);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('healthy', $result['health_status']);
        $this->assertFalse($result['requires_reconnection']);
    }

    public function test_validate_connection_failure()
    {
        // Arrange
        $provider = 'google-drive';
        $healthStatus = CloudStorageHealthStatus::unhealthy(
            $provider,
            consecutiveFailures: 1,
            lastErrorType: CloudStorageErrorType::TOKEN_EXPIRED,
            lastErrorMessage: 'Token expired',
            requiresReconnection: true
        );

        $this->provider->shouldReceive('getConnectionHealth')
            ->once()
            ->with($this->user)
            ->andReturn($healthStatus);

        // Act
        $result = $this->service->validateConnection($this->user, $provider, $this->provider);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('unhealthy', $result['health_status']);
        $this->assertTrue($result['requires_reconnection']);
        $this->assertEquals('Token expired', $result['last_error']);
    }

    public function test_retry_pending_uploads_success()
    {
        // Arrange
        Queue::fake();
        $provider = 'google-drive';

        // Create pending uploads with connection errors
        $upload1 = FileUpload::factory()->create([
            'company_user_id' => $this->user->id,
            'google_drive_file_id' => null,
            'cloud_storage_error_type' => 'token_expired',
            'original_filename' => 'test1.pdf'
        ]);

        $upload2 = FileUpload::factory()->create([
            'uploaded_by_user_id' => $this->user->id,
            'google_drive_file_id' => null,
            'cloud_storage_error_type' => 'insufficient_permissions',
            'original_filename' => 'test2.docx'
        ]);

        // Create upload that shouldn't be retried (different error type)
        FileUpload::factory()->create([
            'company_user_id' => $this->user->id,
            'google_drive_file_id' => null,
            'cloud_storage_error_type' => 'file_too_large',
            'original_filename' => 'test3.zip'
        ]);

        // Act
        $result = $this->service->retryPendingUploads($this->user, $provider, 50);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['total_found']);
        $this->assertEquals(2, $result['successfully_queued']);
        $this->assertEquals(0, $result['failed_to_queue']);

        // Verify jobs were dispatched
        Queue::assertPushed(UploadToGoogleDrive::class, 2);

        // Verify uploads were updated
        $upload1->refresh();
        $upload2->refresh();
        $this->assertNull($upload1->cloud_storage_error_type);
        $this->assertNull($upload2->cloud_storage_error_type);
        $this->assertNotNull($upload1->retry_recommended_at);
        $this->assertNotNull($upload2->retry_recommended_at);
    }

    public function test_retry_pending_uploads_no_uploads_found()
    {
        // Arrange
        Queue::fake();
        $provider = 'google-drive';

        // Create upload that's already completed
        FileUpload::factory()->create([
            'company_user_id' => $this->user->id,
            'google_drive_file_id' => 'some-file-id',
            'original_filename' => 'completed.pdf'
        ]);

        // Act
        $result = $this->service->retryPendingUploads($this->user, $provider, 50);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['total_found']);
        $this->assertEquals(0, $result['successfully_queued']);
        $this->assertEquals(0, $result['failed_to_queue']);

        Queue::assertNothingPushed();
    }

    public function test_handle_reconnection_failure_token_expired()
    {
        // Arrange
        $provider = 'google-drive';
        $exception = new \Exception('invalid_grant: Token has expired');
        $context = ['provider_display_name' => 'Google Drive'];

        $this->healthService->shouldReceive('markConnectionAsUnhealthy')
            ->once()
            ->with(
                $this->user,
                $provider,
                $exception->getMessage(),
                CloudStorageErrorType::TOKEN_EXPIRED
            );

        // Act
        $result = $this->service->handleReconnectionFailure($this->user, $provider, $exception, $context);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('token_expired', $result['error_type']);
        $this->assertStringContainsString('authorization code has expired', $result['user_message']);
        $this->assertTrue($result['can_retry']);
        $this->assertTrue($result['requires_admin_intervention']); // TOKEN_EXPIRED requires user intervention
        $this->assertContains('Try connecting again', $result['recommended_actions']);
    }

    public function test_handle_reconnection_failure_access_denied()
    {
        // Arrange
        $provider = 'google-drive';
        $exception = new \Exception('access_denied: User denied access');
        $context = ['provider_display_name' => 'Google Drive'];

        $this->healthService->shouldReceive('markConnectionAsUnhealthy')
            ->once()
            ->with(
                $this->user,
                $provider,
                $exception->getMessage(),
                CloudStorageErrorType::INSUFFICIENT_PERMISSIONS
            );

        // Act
        $result = $this->service->handleReconnectionFailure($this->user, $provider, $exception, $context);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('insufficient_permissions', $result['error_type']);
        $this->assertStringContainsString('Access was denied', $result['user_message']);
        $this->assertTrue($result['can_retry']);
        $this->assertTrue($result['requires_admin_intervention']);
        $this->assertContains('Grant all requested permissions when prompted by Google Drive', $result['recommended_actions']);
    }

    public function test_handle_reconnection_failure_invalid_client()
    {
        // Arrange
        $provider = 'google-drive';
        $exception = new \Exception('invalid_client: Client authentication failed');
        $context = ['provider_display_name' => 'Google Drive'];

        $this->healthService->shouldReceive('markConnectionAsUnhealthy')
            ->once()
            ->with(
                $this->user,
                $provider,
                $exception->getMessage(),
                CloudStorageErrorType::INVALID_CREDENTIALS
            );

        // Act
        $result = $this->service->handleReconnectionFailure($this->user, $provider, $exception, $context);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('invalid_credentials', $result['error_type']);
        $this->assertStringContainsString('Invalid Google Drive configuration', $result['user_message']);
        $this->assertFalse($result['can_retry']);
        $this->assertTrue($result['requires_admin_intervention']);
        $this->assertContains('Contact your administrator', $result['recommended_actions']);
    }

    public function test_retry_pending_uploads_respects_recent_retry_limit()
    {
        // Arrange
        Queue::fake();
        $provider = 'google-drive';

        // Create upload that was retried recently (should be skipped)
        FileUpload::factory()->create([
            'company_user_id' => $this->user->id,
            'google_drive_file_id' => null,
            'cloud_storage_error_type' => 'token_expired',
            'retry_recommended_at' => now()->subMinutes(2), // Too recent
            'original_filename' => 'recent.pdf'
        ]);

        // Create upload that can be retried
        FileUpload::factory()->create([
            'company_user_id' => $this->user->id,
            'google_drive_file_id' => null,
            'cloud_storage_error_type' => 'token_expired',
            'retry_recommended_at' => now()->subMinutes(10), // Old enough
            'original_filename' => 'old.pdf'
        ]);

        // Act
        $result = $this->service->retryPendingUploads($this->user, $provider, 50);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['total_found']);
        $this->assertEquals(1, $result['successfully_queued']);

        Queue::assertPushed(UploadToGoogleDrive::class, 1);
    }

    public function test_retry_pending_uploads_respects_limit()
    {
        // Arrange
        Queue::fake();
        $provider = 'google-drive';
        $limit = 2;

        // Create 3 retryable uploads
        for ($i = 1; $i <= 3; $i++) {
            FileUpload::factory()->create([
                'company_user_id' => $this->user->id,
                'google_drive_file_id' => null,
                'cloud_storage_error_type' => 'token_expired',
                'original_filename' => "test{$i}.pdf",
                'created_at' => now()->subMinutes($i) // Ensure consistent ordering
            ]);
        }

        // Act
        $result = $this->service->retryPendingUploads($this->user, $provider, $limit);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['total_found']); // Limited to 2
        $this->assertEquals(2, $result['successfully_queued']);

        Queue::assertPushed(UploadToGoogleDrive::class, 2);
    }

    public function test_validate_connection_handles_exception()
    {
        // Arrange
        $provider = 'google-drive';
        $exception = new \Exception('Connection test failed');

        $this->provider->shouldReceive('getConnectionHealth')
            ->once()
            ->with($this->user)
            ->andThrow($exception);

        // Act
        $result = $this->service->validateConnection($this->user, $provider, $this->provider);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Connection test failed', $result['error']);
        $this->assertEquals('unhealthy', $result['health_status']);
    }
}