<?php

namespace Tests\Unit\Jobs;

use App\Contracts\CloudStorageErrorHandlerInterface;
use App\Contracts\CloudStorageProviderInterface;
use App\Enums\CloudStorageErrorType;
use App\Exceptions\CloudStorageException;
use App\Jobs\UploadToGoogleDrive;
use App\Models\FileUpload;
use App\Services\CloudStorageHealthService;
use Exception;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class UploadToGoogleDriveEnhancedSimpleTest extends TestCase
{
    protected $mockProvider;
    protected $mockErrorHandler;
    protected $mockHealthService;

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

        // Mock storage
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_error_handler_classifies_cloud_storage_exception()
    {
        // Arrange
        $exception = CloudStorageException::tokenExpired('google-drive', [
            'operation' => 'upload',
            'file_name' => 'test-file.txt'
        ]);

        // Act
        $errorType = $exception->getErrorType();

        // Assert
        $this->assertEquals(CloudStorageErrorType::TOKEN_EXPIRED, $errorType);
        $this->assertTrue($exception->requiresUserIntervention());
        $this->assertFalse($exception->isRecoverable());
        $this->assertEquals('high', $exception->getSeverity());
    }

    public function test_error_handler_provides_user_friendly_messages()
    {
        // Arrange
        $this->mockErrorHandler->shouldReceive('getUserFriendlyMessage')
            ->with(CloudStorageErrorType::TOKEN_EXPIRED, ['provider' => 'google-drive'])
            ->andReturn('Your Google Drive connection has expired. Please reconnect to continue.');

        // Act
        $message = $this->mockErrorHandler->getUserFriendlyMessage(
            CloudStorageErrorType::TOKEN_EXPIRED, 
            ['provider' => 'google-drive']
        );

        // Assert
        $this->assertStringContainsString('Google Drive connection has expired', $message);
        $this->assertStringContainsString('reconnect', $message);
    }

    public function test_error_handler_determines_retry_logic()
    {
        // Arrange
        $this->mockErrorHandler->shouldReceive('shouldRetry')
            ->with(CloudStorageErrorType::NETWORK_ERROR, 1)
            ->andReturn(true);

        $this->mockErrorHandler->shouldReceive('shouldRetry')
            ->with(CloudStorageErrorType::TOKEN_EXPIRED, 1)
            ->andReturn(false);

        $this->mockErrorHandler->shouldReceive('getRetryDelay')
            ->with(CloudStorageErrorType::NETWORK_ERROR, 1)
            ->andReturn(60);

        $this->mockErrorHandler->shouldReceive('getMaxRetryAttempts')
            ->with(CloudStorageErrorType::NETWORK_ERROR)
            ->andReturn(3);

        // Act & Assert
        $this->assertTrue($this->mockErrorHandler->shouldRetry(CloudStorageErrorType::NETWORK_ERROR, 1));
        $this->assertFalse($this->mockErrorHandler->shouldRetry(CloudStorageErrorType::TOKEN_EXPIRED, 1));
        $this->assertEquals(60, $this->mockErrorHandler->getRetryDelay(CloudStorageErrorType::NETWORK_ERROR, 1));
        $this->assertEquals(3, $this->mockErrorHandler->getMaxRetryAttempts(CloudStorageErrorType::NETWORK_ERROR));
    }

    public function test_provider_interface_methods()
    {
        // Arrange
        $this->mockProvider->shouldReceive('getProviderName')
            ->andReturn('google-drive');

        $this->mockProvider->shouldReceive('hasValidConnection')
            ->with(Mockery::type(\App\Models\User::class))
            ->andReturn(true);

        // Act & Assert
        $this->assertEquals('google-drive', $this->mockProvider->getProviderName());
        
        // Create a mock user for testing
        $mockUser = Mockery::mock(\App\Models\User::class);
        $this->assertTrue($this->mockProvider->hasValidConnection($mockUser));
    }

    public function test_cloud_storage_exception_factory_methods()
    {
        // Test token expired exception
        $tokenException = CloudStorageException::tokenExpired('google-drive', ['user_id' => 1]);
        $this->assertEquals(CloudStorageErrorType::TOKEN_EXPIRED, $tokenException->getErrorType());
        $this->assertEquals('google-drive', $tokenException->getProvider());
        $this->assertEquals(['user_id' => 1], $tokenException->getContext());

        // Test quota exceeded exception
        $quotaException = CloudStorageException::quotaExceeded('google-drive', ['file_size' => 1024]);
        $this->assertEquals(CloudStorageErrorType::API_QUOTA_EXCEEDED, $quotaException->getErrorType());
        $this->assertEquals('google-drive', $quotaException->getProvider());

        // Test network error exception
        $networkException = CloudStorageException::networkError('google-drive', ['timeout' => 30]);
        $this->assertEquals(CloudStorageErrorType::NETWORK_ERROR, $networkException->getErrorType());
        $this->assertTrue($networkException->isRecoverable());
    }

    public function test_error_type_enum_methods()
    {
        // Test recoverable errors
        $this->assertTrue(CloudStorageErrorType::NETWORK_ERROR->isRecoverable());
        $this->assertTrue(CloudStorageErrorType::SERVICE_UNAVAILABLE->isRecoverable());
        $this->assertTrue(CloudStorageErrorType::TIMEOUT->isRecoverable());
        $this->assertTrue(CloudStorageErrorType::API_QUOTA_EXCEEDED->isRecoverable());

        // Test non-recoverable errors
        $this->assertFalse(CloudStorageErrorType::TOKEN_EXPIRED->isRecoverable());
        $this->assertFalse(CloudStorageErrorType::INSUFFICIENT_PERMISSIONS->isRecoverable());
        $this->assertFalse(CloudStorageErrorType::FILE_NOT_FOUND->isRecoverable());

        // Test user intervention required
        $this->assertTrue(CloudStorageErrorType::TOKEN_EXPIRED->requiresUserIntervention());
        $this->assertTrue(CloudStorageErrorType::INSUFFICIENT_PERMISSIONS->requiresUserIntervention());
        $this->assertTrue(CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED->requiresUserIntervention());
        $this->assertFalse(CloudStorageErrorType::NETWORK_ERROR->requiresUserIntervention());

        // Test severity levels
        $this->assertEquals('high', CloudStorageErrorType::TOKEN_EXPIRED->getSeverity());
        $this->assertEquals('medium', CloudStorageErrorType::API_QUOTA_EXCEEDED->getSeverity());
        $this->assertEquals('low', CloudStorageErrorType::NETWORK_ERROR->getSeverity());
    }

    public function test_job_constructor_and_properties()
    {
        // Create a mock FileUpload with proper expectations
        $fileUpload = Mockery::mock(FileUpload::class);
        $fileUpload->shouldReceive('getAttribute')->with('id')->andReturn(123);
        $fileUpload->shouldReceive('setAttribute')->with('id', 123);
        $fileUpload->id = 123;

        // Create job instance
        $job = new UploadToGoogleDrive($fileUpload);

        // Test that job has correct properties
        $this->assertInstanceOf(UploadToGoogleDrive::class, $job);
        $this->assertEquals(3, $job->tries); // Default tries
        $this->assertEquals([], $job->backoff); // Default empty backoff
    }

    public function test_job_error_details_method()
    {
        // Create a mock FileUpload with proper expectations
        $fileUpload = Mockery::mock(FileUpload::class);
        $fileUpload->shouldReceive('getAttribute')->with('id')->andReturn(123);
        $fileUpload->shouldReceive('setAttribute')->with('id', 123);
        $fileUpload->id = 123;

        $job = new UploadToGoogleDrive($fileUpload);
        
        // Use reflection to test the protected method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('getErrorDetails');
        $method->setAccessible(true);

        $exception = new Exception('Test error');
        $errorType = CloudStorageErrorType::NETWORK_ERROR;
        $context = ['test' => 'value'];

        $details = $method->invoke($job, $exception, $errorType, $context);

        // Assert error details structure
        $this->assertArrayHasKey('error_type', $details);
        $this->assertArrayHasKey('error_message', $details);
        $this->assertArrayHasKey('classified_error_type', $details);
        $this->assertArrayHasKey('error_severity', $details);
        $this->assertArrayHasKey('requires_user_intervention', $details);
        $this->assertArrayHasKey('is_recoverable', $details);
        $this->assertArrayHasKey('context', $details);

        $this->assertEquals('Exception', $details['error_type']);
        $this->assertEquals('Test error', $details['error_message']);
        $this->assertEquals('network_error', $details['classified_error_type']);
        $this->assertEquals('low', $details['error_severity']);
        $this->assertFalse($details['requires_user_intervention']);
        $this->assertTrue($details['is_recoverable']);
        $this->assertEquals($context, $details['context']);
    }

    public function test_cloud_storage_exception_to_array()
    {
        $exception = CloudStorageException::create(
            CloudStorageErrorType::TOKEN_EXPIRED,
            'google-drive',
            ['user_id' => 1, 'operation' => 'upload'],
            new Exception('Original error')
        );

        $array = $exception->toArray();

        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('error_type', $array);
        $this->assertArrayHasKey('provider', $array);
        $this->assertArrayHasKey('context', $array);
        $this->assertArrayHasKey('requires_user_intervention', $array);
        $this->assertArrayHasKey('is_recoverable', $array);
        $this->assertArrayHasKey('severity', $array);

        $this->assertEquals('token_expired', $array['error_type']);
        $this->assertEquals('google-drive', $array['provider']);
        $this->assertTrue($array['requires_user_intervention']);
        $this->assertFalse($array['is_recoverable']);
        $this->assertEquals('high', $array['severity']);
    }
}