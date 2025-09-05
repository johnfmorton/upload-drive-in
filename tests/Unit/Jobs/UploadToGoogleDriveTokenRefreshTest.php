<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\UploadToGoogleDrive;
use App\Models\FileUpload;
use App\Models\User;
use App\Services\CloudStorageManager;
use App\Services\CloudStorageHealthService;
use App\Services\CloudStorageLogService;
use App\Services\TokenRefreshCoordinator;
use App\Services\TokenRenewalNotificationService;
use App\Services\RefreshResult;
use App\Contracts\CloudStorageProviderInterface;
use App\Contracts\CloudStorageErrorHandlerInterface;
use App\Enums\UserRole;
use App\Enums\CloudStorageErrorType;
use App\Enums\TokenRefreshErrorType;
use App\Exceptions\CloudStorageException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Exception;

class UploadToGoogleDriveTokenRefreshTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Log::spy();
    }

    /**
     * Create a properly mocked provider
     */
    protected function createMockProvider(): CloudStorageProviderInterface
    {
        $provider = Mockery::mock(CloudStorageProviderInterface::class);
        $provider->shouldReceive('getProviderName')->andReturn('google-drive');
        $provider->shouldReceive('hasValidConnection')->andReturn(true);
        return $provider;
    }

    /**
     * Create a properly mocked error handler
     */
    protected function createMockErrorHandler(): CloudStorageErrorHandlerInterface
    {
        return Mockery::mock(CloudStorageErrorHandlerInterface::class);
    }

    /** @test */
    public function it_ensures_valid_token_before_upload()
    {
        Storage::disk('public')->put('uploads/test.txt', 'test content');
        
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $fileUpload = FileUpload::factory()->create([
            'filename' => 'test.txt',
            'uploaded_by_user_id' => $user->id,
        ]);

        // Mock services
        $storageManager = Mockery::mock(CloudStorageManager::class);
        $healthService = Mockery::mock(CloudStorageHealthService::class);
        $logService = Mockery::mock(CloudStorageLogService::class);
        $tokenCoordinator = Mockery::mock(TokenRefreshCoordinator::class);
        $notificationService = Mockery::mock(TokenRenewalNotificationService::class);

        // Mock provider
        $provider = $this->createMockProvider();
        $provider->shouldReceive('uploadFile')->andReturn('file-id-123');

        $errorHandler = $this->createMockErrorHandler();
        
        $storageManager->shouldReceive('getUserProvider')->with($user)->andReturn($provider);
        $storageManager->shouldReceive('getErrorHandler')->with('google-drive')->andReturn($errorHandler);

        // Mock successful token refresh
        $refreshResult = RefreshResult::success([], 'Token refreshed successfully');
        $tokenCoordinator->shouldReceive('coordinateRefresh')
            ->with($user, 'google-drive')
            ->andReturn($refreshResult);

        $healthService->shouldReceive('recordSuccessfulOperation')->once();

        // Mock the job to return the user for determineTargetUser
        $job = Mockery::mock(UploadToGoogleDrive::class, [$fileUpload])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $job->shouldReceive('determineTargetUser')->andReturn($user);

        $job->handle($storageManager, $healthService, $logService, $tokenCoordinator, $notificationService);

        $fileUpload->refresh();
        $this->assertEquals('file-id-123', $fileUpload->google_drive_file_id);
    }

    /** @test */
    public function it_identifies_token_related_errors_correctly()
    {
        $fileUpload = FileUpload::factory()->create();
        $job = new UploadToGoogleDrive($fileUpload);

        // Test CloudStorageErrorType classification
        $this->assertTrue($this->invokeMethod($job, 'isTokenRelatedError', [
            CloudStorageErrorType::INVALID_CREDENTIALS,
            new Exception('Test')
        ]));

        $this->assertTrue($this->invokeMethod($job, 'isTokenRelatedError', [
            CloudStorageErrorType::TOKEN_EXPIRED,
            new Exception('Test')
        ]));

        $this->assertTrue($this->invokeMethod($job, 'isTokenRelatedError', [
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            new Exception('Test')
        ]));

        // Test exception message classification
        $this->assertTrue($this->invokeMethod($job, 'isTokenRelatedError', [
            CloudStorageErrorType::UNKNOWN_ERROR,
            new Exception('Token expired')
        ]));

        $this->assertTrue($this->invokeMethod($job, 'isTokenRelatedError', [
            CloudStorageErrorType::UNKNOWN_ERROR,
            new Exception('invalid_grant')
        ]));

        $this->assertTrue($this->invokeMethod($job, 'isTokenRelatedError', [
            CloudStorageErrorType::UNKNOWN_ERROR,
            new Exception('unauthorized access')
        ]));

        // Test non-token errors
        $this->assertFalse($this->invokeMethod($job, 'isTokenRelatedError', [
            CloudStorageErrorType::NETWORK_ERROR,
            new Exception('Network timeout')
        ]));
    }

    /** @test */
    public function it_maps_cloud_storage_error_types_to_token_refresh_error_types()
    {
        $fileUpload = FileUpload::factory()->create();
        $job = new UploadToGoogleDrive($fileUpload);

        $mappings = [
            CloudStorageErrorType::INVALID_CREDENTIALS->value => TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
            CloudStorageErrorType::TOKEN_EXPIRED->value => TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS->value => TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
            CloudStorageErrorType::NETWORK_ERROR->value => TokenRefreshErrorType::NETWORK_TIMEOUT,
            CloudStorageErrorType::API_QUOTA_EXCEEDED->value => TokenRefreshErrorType::API_QUOTA_EXCEEDED,
            CloudStorageErrorType::SERVICE_UNAVAILABLE->value => TokenRefreshErrorType::SERVICE_UNAVAILABLE,
            CloudStorageErrorType::UNKNOWN_ERROR->value => TokenRefreshErrorType::UNKNOWN_ERROR,
        ];

        foreach ($mappings as $cloudErrorValue => $tokenError) {
            $cloudError = CloudStorageErrorType::from($cloudErrorValue);
            $result = $this->invokeMethod($job, 'mapToTokenRefreshErrorType', [$cloudError]);
            $this->assertEquals($tokenError, $result);
        }
    }

    /** @test */
    public function it_handles_token_refresh_failure_with_appropriate_notifications()
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $fileUpload = FileUpload::factory()->create([
            'uploaded_by_user_id' => $user->id,
        ]);

        $job = new UploadToGoogleDrive($fileUpload);
        $notificationService = Mockery::mock(TokenRenewalNotificationService::class);

        $errorType = TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN;
        $exception = new Exception('Refresh token expired');

        // Expect notification to be sent
        $notificationService->shouldReceive('handleTokenRefreshFailure')
            ->with($user, 'google-drive', $errorType, $exception, 1)
            ->once();

        $this->invokeMethod($job, 'handleTokenRefreshFailure', [
            $user,
            $errorType,
            $exception,
            $notificationService,
            'test-operation-id'
        ]);

        // Verify job retry settings for recoverable errors
        if ($errorType->isRecoverable()) {
            $this->assertGreaterThan(0, $job->tries);
        }
    }

    /**
     * Helper method to invoke protected/private methods for testing
     */
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}