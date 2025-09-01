<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\UploadToGoogleDrive;
use App\Models\FileUpload;
use App\Models\User;
use App\Services\GoogleDriveService;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Exception;
use Google\Service\Exception as GoogleServiceException;
use Google\Exception as GoogleException;

class UploadToGoogleDriveJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Log::spy();
    }

    /** @test */
    public function it_classifies_google_service_exceptions_correctly()
    {
        $fileUpload = FileUpload::factory()->create();
        $job = new UploadToGoogleDrive($fileUpload);

        // Test transient errors
        $transientException = new GoogleServiceException('Rate limit exceeded', 429);
        $this->assertTrue($this->invokeMethod($job, 'isTransientError', [$transientException]));

        $serverErrorException = new GoogleServiceException('Internal server error', 500);
        $this->assertTrue($this->invokeMethod($job, 'isTransientError', [$serverErrorException]));

        // Test permanent errors
        $authException = new GoogleServiceException('Invalid credentials', 401);
        $this->assertFalse($this->invokeMethod($job, 'isTransientError', [$authException]));

        $notFoundException = new GoogleServiceException('File not found', 404);
        $this->assertFalse($this->invokeMethod($job, 'isTransientError', [$notFoundException]));
    }

    /** @test */
    public function it_classifies_google_client_exceptions_as_transient()
    {
        $fileUpload = FileUpload::factory()->create();
        $job = new UploadToGoogleDrive($fileUpload);

        $googleException = new GoogleException('Connection timeout');
        $this->assertTrue($this->invokeMethod($job, 'isTransientError', [$googleException]));
    }

    /** @test */
    public function it_classifies_network_errors_as_transient()
    {
        $fileUpload = FileUpload::factory()->create();
        $job = new UploadToGoogleDrive($fileUpload);

        $networkException = new Exception('Connection timed out');
        $this->assertTrue($this->invokeMethod($job, 'isTransientError', [$networkException]));

        $curlException = new Exception('CURL error: timeout');
        $this->assertTrue($this->invokeMethod($job, 'isTransientError', [$curlException]));
    }

    /** @test */
    public function it_classifies_unknown_errors_as_permanent()
    {
        $fileUpload = FileUpload::factory()->create();
        $job = new UploadToGoogleDrive($fileUpload);

        $unknownException = new Exception('Some unknown error');
        $this->assertFalse($this->invokeMethod($job, 'isTransientError', [$unknownException]));
    }

    /** @test */
    public function it_records_detailed_error_information()
    {
        $fileUpload = FileUpload::factory()->create();
        $job = new UploadToGoogleDrive($fileUpload);

        $exception = new GoogleServiceException('Rate limit exceeded', 429);
        $context = ['test_context' => 'value'];

        $errorDetails = $this->invokeMethod($job, 'getErrorDetails', [$exception, $context]);

        $this->assertEquals(GoogleServiceException::class, $errorDetails['error_type']);
        $this->assertEquals('Rate limit exceeded', $errorDetails['error_message']);
        $this->assertEquals(429, $errorDetails['error_code']);
        $this->assertTrue($errorDetails['is_transient']);
        $this->assertEquals($context, $errorDetails['context']);
        $this->assertArrayHasKey('timestamp', $errorDetails);
        $this->assertArrayHasKey('file', $errorDetails);
        $this->assertArrayHasKey('line', $errorDetails);
    }

    /** @test */
    public function it_records_error_in_database()
    {
        $fileUpload = FileUpload::factory()->create([
            'retry_count' => 0,
            'last_error' => null,
            'error_details' => null,
        ]);

        $job = new UploadToGoogleDrive($fileUpload);
        $exception = new Exception('Test error');
        $context = ['test' => 'context'];

        $this->invokeMethod($job, 'recordError', [$fileUpload, $exception, $context]);

        $fileUpload->refresh();
        $this->assertEquals(1, $fileUpload->retry_count);
        $this->assertEquals('Test error', $fileUpload->last_error);
        $this->assertNotNull($fileUpload->error_details);
        $this->assertNotNull($fileUpload->last_processed_at);
    }

    /** @test */
    public function it_fails_immediately_for_missing_files()
    {
        $fileUpload = FileUpload::factory()->create([
            'filename' => 'nonexistent.txt'
        ]);

        $driveService = Mockery::mock(GoogleDriveService::class);
        $job = new UploadToGoogleDrive($fileUpload);

        // Mock the fail method to track if it's called
        $job = Mockery::mock(UploadToGoogleDrive::class, [$fileUpload])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $job->shouldReceive('fail')->once();

        $job->handle($driveService);

        $fileUpload->refresh();
        $this->assertNotNull($fileUpload->last_error);
        $this->assertStringContainsString('Source file not found', $fileUpload->last_error);
    }

    /** @test */
    public function it_uses_exponential_backoff()
    {
        $fileUpload = FileUpload::factory()->create();
        $job = new UploadToGoogleDrive($fileUpload);

        $this->assertEquals([60, 300, 900], $job->backoff);
    }

    /** @test */
    public function it_handles_successful_upload_and_clears_errors()
    {
        Storage::disk('public')->put('uploads/test.txt', 'test content');
        
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Create a mock Google Drive token for the user
        $user->googleDriveToken()->create([
            'access_token' => 'test-token',
            'refresh_token' => 'test-refresh-token',
            'expires_at' => now()->addHour(),
        ]);
        
        $fileUpload = FileUpload::factory()->create([
            'filename' => 'test.txt',
            'uploaded_by_user_id' => $user->id,
            'last_error' => 'Previous error',
            'error_details' => ['previous' => 'error'],
            'retry_count' => 1,
        ]);

        $driveService = Mockery::mock(GoogleDriveService::class);
        $driveService->shouldReceive('uploadFileForUser')
            ->once()
            ->andReturn('google-drive-file-id-123');

        $job = new UploadToGoogleDrive($fileUpload);
        $job->handle($driveService);

        $fileUpload->refresh();
        $this->assertEquals('google-drive-file-id-123', $fileUpload->google_drive_file_id);
        $this->assertNull($fileUpload->last_error);
        $this->assertNull($fileUpload->error_details);
        $this->assertNotNull($fileUpload->last_processed_at);
    }

    /** @test */
    public function it_records_configuration_errors()
    {
        Storage::disk('public')->put('uploads/test.txt', 'test content');
        
        $fileUpload = FileUpload::factory()->create([
            'filename' => 'test.txt',
            'uploaded_by_user_id' => null,
            'company_user_id' => null,
        ]);

        $driveService = Mockery::mock(GoogleDriveService::class);
        
        $job = new UploadToGoogleDrive($fileUpload);
        
        try {
            $job->handle($driveService);
        } catch (Exception $e) {
            // Expected to throw exception
            $this->assertStringContainsString('No user with Google Drive connection', $e->getMessage());
        }

        $fileUpload->refresh();
        $this->assertNotNull($fileUpload->last_error);
        $this->assertNotNull($fileUpload->error_details);
    }

    /** @test */
    public function it_handles_failed_method_correctly()
    {
        $fileUpload = FileUpload::factory()->create();
        $job = new UploadToGoogleDrive($fileUpload);
        $exception = new Exception('Final failure');

        $job->failed($exception);

        $fileUpload->refresh();
        $this->assertNotNull($fileUpload->last_error);
        $this->assertNotNull($fileUpload->error_details);
        $this->assertNotNull($fileUpload->last_processed_at);

        Log::shouldHaveReceived('error')
            ->with('Google Drive upload job permanently failed after all retries', Mockery::any());
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