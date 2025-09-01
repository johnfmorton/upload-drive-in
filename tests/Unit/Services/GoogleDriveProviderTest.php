<?php

namespace Tests\Unit\Services;

use App\Contracts\CloudStorageProviderInterface;
use App\Enums\CloudStorageErrorType;
use App\Exceptions\CloudStorageException;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\CloudStorageHealthStatus;
use App\Services\GoogleDriveErrorHandler;
use App\Services\GoogleDriveProvider;
use App\Services\GoogleDriveService;
use Carbon\Carbon;
use Exception;
use Google\Service\Exception as GoogleServiceException;
use Mockery;
use PHPUnit\Framework\TestCase;

class GoogleDriveProviderTest extends TestCase
{
    private GoogleDriveProvider $provider;
    private GoogleDriveService $mockDriveService;
    private GoogleDriveErrorHandler $mockErrorHandler;
    private User $mockUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDriveService = Mockery::mock(GoogleDriveService::class);
        $this->mockErrorHandler = Mockery::mock(GoogleDriveErrorHandler::class);
        
        $this->provider = new GoogleDriveProvider(
            $this->mockDriveService,
            $this->mockErrorHandler
        );

        $this->mockUser = Mockery::mock(User::class);
        $this->mockUser->shouldReceive('getAttribute')->with('id')->andReturn(1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_implements_cloud_storage_provider_interface()
    {
        $this->assertInstanceOf(CloudStorageProviderInterface::class, $this->provider);
    }

    public function test_it_returns_correct_provider_name()
    {
        $this->assertEquals('google-drive', $this->provider->getProviderName());
    }

    public function test_it_uploads_file_successfully()
    {
        $localPath = 'uploads/test.pdf';
        $targetPath = 'client@example.com';
        $metadata = [
            'original_filename' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'description' => 'Test file'
        ];
        $expectedFileId = 'google-drive-file-id-123';

        $this->mockDriveService
            ->shouldReceive('uploadFileForUser')
            ->once()
            ->with(
                $this->mockUser,
                $localPath,
                $targetPath,
                'test.pdf',
                'application/pdf',
                'Test file'
            )
            ->andReturn($expectedFileId);

        $result = $this->provider->uploadFile($this->mockUser, $localPath, $targetPath, $metadata);

        $this->assertEquals($expectedFileId, $result);
    }

    public function test_it_handles_upload_failure_with_error_classification()
    {
        $localPath = 'uploads/test.pdf';
        $targetPath = 'client@example.com';
        $metadata = ['original_filename' => 'test.pdf'];
        
        $originalException = new GoogleServiceException('Token expired', 401);
        $errorType = CloudStorageErrorType::TOKEN_EXPIRED;

        $this->mockDriveService
            ->shouldReceive('uploadFileForUser')
            ->once()
            ->andThrow($originalException);

        $this->mockErrorHandler
            ->shouldReceive('classifyError')
            ->once()
            ->with($originalException)
            ->andReturn($errorType);

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('google-drive error');

        $this->provider->uploadFile($this->mockUser, $localPath, $targetPath, $metadata);
    }

    public function test_it_deletes_file_successfully()
    {
        $fileId = 'google-drive-file-id-123';

        $this->mockDriveService
            ->shouldReceive('deleteFile')
            ->once()
            ->with($this->user, $fileId)
            ->andReturn(true);

        $result = $this->provider->deleteFile($this->user, $fileId);

        $this->assertTrue($result);
    }

    public function test_it_handles_delete_failure_with_error_classification()
    {
        $fileId = 'google-drive-file-id-123';
        $originalException = new GoogleServiceException('File not found', 404);
        $errorType = CloudStorageErrorType::FILE_NOT_FOUND;

        $this->mockDriveService
            ->shouldReceive('deleteFile')
            ->once()
            ->with($this->user, $fileId)
            ->andThrow($originalException);

        $this->mockErrorHandler
            ->shouldReceive('classifyError')
            ->once()
            ->with($originalException)
            ->andReturn($errorType);

        $this->expectException(CloudStorageException::class);

        $this->provider->deleteFile($this->user, $fileId);
    }

    public function test_it_returns_disconnected_health_status_when_no_token()
    {
        // No token exists for this user
        $healthStatus = $this->provider->getConnectionHealth($this->user);

        $this->assertTrue($healthStatus->isDisconnected());
        $this->assertEquals('google-drive', $healthStatus->provider);
        $this->assertTrue($healthStatus->requiresReconnection);
    }

    public function test_it_returns_unhealthy_status_for_expired_token_without_refresh()
    {
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => Carbon::now()->subHour(),
            'refresh_token' => null
        ]);

        $healthStatus = $this->provider->getConnectionHealth($this->user);

        $this->assertTrue($healthStatus->isUnhealthy());
        $this->assertEquals(CloudStorageErrorType::TOKEN_EXPIRED, $healthStatus->lastErrorType);
        $this->assertTrue($healthStatus->requiresReconnection);
    }

    public function test_it_returns_healthy_status_for_valid_connection()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => Carbon::now()->addHour(),
            'refresh_token' => 'refresh-token'
        ]);

        $this->mockDriveService
            ->shouldReceive('getValidToken')
            ->once()
            ->with($this->user)
            ->andReturn($token);

        $mockDriveService = Mockery::mock(\Google\Service\Drive::class);
        $mockAbout = Mockery::mock();
        $mockUser = Mockery::mock();
        $mockStorageQuota = Mockery::mock();
        $mockAboutService = Mockery::mock();

        $this->mockDriveService
            ->shouldReceive('getDriveService')
            ->once()
            ->with($this->user)
            ->andReturn($mockDriveService);

        $mockDriveService->about = $mockAboutService;
        $mockAboutService
            ->shouldReceive('get')
            ->once()
            ->with(['fields' => 'user,storageQuota'])
            ->andReturn($mockAbout);

        $mockAbout
            ->shouldReceive('getStorageQuota')
            ->once()
            ->andReturn($mockStorageQuota);

        $mockAbout
            ->shouldReceive('getUser')
            ->once()
            ->andReturn($mockUser);

        $mockUser
            ->shouldReceive('getEmailAddress')
            ->once()
            ->andReturn('user@example.com');

        $mockStorageQuota
            ->shouldReceive('getUsage')
            ->once()
            ->andReturn(1000000); // 1MB

        $mockStorageQuota
            ->shouldReceive('getLimit')
            ->once()
            ->andReturn(15000000000); // 15GB

        $healthStatus = $this->provider->getConnectionHealth($this->user);

        $this->assertTrue($healthStatus->isHealthy());
        $this->assertEquals('google-drive', $healthStatus->provider);
        $this->assertFalse($healthStatus->requiresReconnection);
    }

    public function test_it_returns_degraded_status_for_high_quota_usage()
    {
        $token = GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => Carbon::now()->addHour(),
            'refresh_token' => 'refresh-token'
        ]);

        $this->mockDriveService
            ->shouldReceive('getValidToken')
            ->once()
            ->with($this->user)
            ->andReturn($token);

        $mockDriveService = Mockery::mock(\Google\Service\Drive::class);
        $mockAbout = Mockery::mock();
        $mockUser = Mockery::mock();
        $mockStorageQuota = Mockery::mock();
        $mockAboutService = Mockery::mock();

        $this->mockDriveService
            ->shouldReceive('getDriveService')
            ->once()
            ->with($this->user)
            ->andReturn($mockDriveService);

        $mockDriveService->about = $mockAboutService;
        $mockAboutService
            ->shouldReceive('get')
            ->once()
            ->with(['fields' => 'user,storageQuota'])
            ->andReturn($mockAbout);

        $mockAbout
            ->shouldReceive('getStorageQuota')
            ->once()
            ->andReturn($mockStorageQuota);

        $mockAbout
            ->shouldReceive('getUser')
            ->once()
            ->andReturn($mockUser);

        $mockUser
            ->shouldReceive('getEmailAddress')
            ->once()
            ->andReturn('user@example.com');

        $mockStorageQuota
            ->shouldReceive('getUsage')
            ->once()
            ->andReturn(14500000000); // 14.5GB (96.7% of 15GB)

        $mockStorageQuota
            ->shouldReceive('getLimit')
            ->once()
            ->andReturn(15000000000); // 15GB

        $healthStatus = $this->provider->getConnectionHealth($this->user);

        $this->assertTrue($healthStatus->isDegraded());
        $this->assertEquals('google-drive', $healthStatus->provider);
    }

    public function test_it_handles_oauth_callback_successfully()
    {
        $code = 'oauth-authorization-code';

        $this->mockDriveService
            ->shouldReceive('handleCallback')
            ->once()
            ->with($this->user, $code);

        $this->provider->handleAuthCallback($this->user, $code);

        // If no exception is thrown, the test passes
        $this->assertTrue(true);
    }

    public function test_it_handles_oauth_callback_failure()
    {
        $code = 'invalid-code';
        $originalException = new Exception('Invalid authorization code');
        $errorType = CloudStorageErrorType::INVALID_CREDENTIALS;

        $this->mockDriveService
            ->shouldReceive('handleCallback')
            ->once()
            ->with($this->user, $code)
            ->andThrow($originalException);

        $this->mockErrorHandler
            ->shouldReceive('classifyError')
            ->once()
            ->with($originalException)
            ->andReturn($errorType);

        $this->expectException(CloudStorageException::class);

        $this->provider->handleAuthCallback($this->user, $code);
    }

    public function test_it_generates_auth_url_successfully()
    {
        $expectedUrl = 'https://accounts.google.com/oauth/authorize?...';

        $this->mockDriveService
            ->shouldReceive('getAuthUrl')
            ->once()
            ->with($this->user)
            ->andReturn($expectedUrl);

        $result = $this->provider->getAuthUrl($this->user);

        $this->assertEquals($expectedUrl, $result);
    }

    public function test_it_handles_auth_url_generation_failure()
    {
        $originalException = new Exception('Failed to generate auth URL');

        $this->mockDriveService
            ->shouldReceive('getAuthUrl')
            ->once()
            ->with($this->user)
            ->andThrow($originalException);

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('Failed to generate Google Drive authorization URL');

        $this->provider->getAuthUrl($this->user);
    }

    public function test_it_disconnects_user_account_successfully()
    {
        $this->mockDriveService
            ->shouldReceive('disconnect')
            ->once()
            ->with($this->user);

        $this->provider->disconnect($this->user);

        // If no exception is thrown, the test passes
        $this->assertTrue(true);
    }

    public function test_it_handles_disconnect_failure_gracefully()
    {
        $this->mockDriveService
            ->shouldReceive('disconnect')
            ->once()
            ->with($this->user)
            ->andThrow(new Exception('Disconnect failed'));

        // Should not throw exception - disconnect failures should be handled gracefully
        $this->provider->disconnect($this->user);

        $this->assertTrue(true);
    }

    public function test_it_checks_valid_connection_for_healthy_status()
    {
        GoogleDriveToken::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => Carbon::now()->addHour(),
            'refresh_token' => 'refresh-token'
        ]);

        $token = GoogleDriveToken::where('user_id', $this->user->id)->first();

        $this->mockDriveService
            ->shouldReceive('getValidToken')
            ->once()
            ->with($this->user)
            ->andReturn($token);

        $mockDriveService = Mockery::mock(\Google\Service\Drive::class);
        $mockAbout = Mockery::mock();
        $mockUser = Mockery::mock();
        $mockStorageQuota = Mockery::mock();
        $mockAboutService = Mockery::mock();

        $this->mockDriveService
            ->shouldReceive('getDriveService')
            ->once()
            ->with($this->user)
            ->andReturn($mockDriveService);

        $mockDriveService->about = $mockAboutService;
        $mockAboutService
            ->shouldReceive('get')
            ->once()
            ->with(['fields' => 'user,storageQuota'])
            ->andReturn($mockAbout);

        $mockAbout->shouldReceive('getStorageQuota')->once()->andReturn($mockStorageQuota);
        $mockAbout->shouldReceive('getUser')->once()->andReturn($mockUser);
        $mockUser->shouldReceive('getEmailAddress')->once()->andReturn('user@example.com');
        $mockStorageQuota->shouldReceive('getUsage')->once()->andReturn(1000000);
        $mockStorageQuota->shouldReceive('getLimit')->once()->andReturn(15000000000);

        $result = $this->provider->hasValidConnection($this->user);

        $this->assertTrue($result);
    }

    public function test_it_checks_valid_connection_for_disconnected_status()
    {
        // No token exists for this user
        $result = $this->provider->hasValidConnection($this->user);

        $this->assertFalse($result);
    }

    public function test_it_gets_file_metadata_successfully()
    {
        $fileId = 'google-drive-file-id-123';
        $mockFile = Mockery::mock();

        $mockFile->shouldReceive('getId')->once()->andReturn($fileId);
        $mockFile->shouldReceive('getName')->once()->andReturn('test.pdf');
        $mockFile->shouldReceive('getSize')->once()->andReturn(1024);
        $mockFile->shouldReceive('getMimeType')->once()->andReturn('application/pdf');
        $mockFile->shouldReceive('getCreatedTime')->once()->andReturn('2023-01-01T00:00:00Z');
        $mockFile->shouldReceive('getModifiedTime')->once()->andReturn('2023-01-02T00:00:00Z');
        $mockFile->shouldReceive('getParents')->once()->andReturn(['parent-folder-id']);

        $this->mockDriveService
            ->shouldReceive('getFileMetadata')
            ->once()
            ->with($this->user, $fileId)
            ->andReturn($mockFile);

        $result = $this->provider->getFileMetadata($this->user, $fileId);

        $this->assertIsArray($result);
        $this->assertEquals($fileId, $result['id']);
        $this->assertEquals('test.pdf', $result['name']);
        $this->assertEquals(1024, $result['size']);
        $this->assertEquals('application/pdf', $result['mime_type']);
    }

    public function test_it_downloads_file_successfully()
    {
        $fileId = 'google-drive-file-id-123';
        $expectedContent = 'file content here';

        $this->mockDriveService
            ->shouldReceive('downloadFile')
            ->once()
            ->with($this->user, $fileId)
            ->andReturn($expectedContent);

        $result = $this->provider->downloadFile($this->user, $fileId);

        $this->assertEquals($expectedContent, $result);
    }
}