<?php

namespace Tests\Unit\Services;

use App\Contracts\CloudStorageProviderInterface;
use App\Enums\CloudStorageErrorType;
use App\Exceptions\CloudStorageException;
use App\Models\User;
use App\Services\GoogleDriveErrorHandler;
use App\Services\GoogleDriveProvider;
use App\Services\GoogleDriveService;
use Exception;
use Google\Service\Exception as GoogleServiceException;
use Mockery;
use PHPUnit\Framework\TestCase;

class GoogleDriveProviderSimpleTest extends TestCase
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
            ->with($this->mockUser, $fileId)
            ->andReturn(true);

        $result = $this->provider->deleteFile($this->mockUser, $fileId);

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
            ->with($this->mockUser, $fileId)
            ->andThrow($originalException);

        $this->mockErrorHandler
            ->shouldReceive('classifyError')
            ->once()
            ->with($originalException)
            ->andReturn($errorType);

        $this->expectException(CloudStorageException::class);

        $this->provider->deleteFile($this->mockUser, $fileId);
    }

    public function test_it_handles_oauth_callback_successfully()
    {
        $code = 'oauth-authorization-code';

        $this->mockDriveService
            ->shouldReceive('handleCallback')
            ->once()
            ->with($this->mockUser, $code);

        $this->provider->handleAuthCallback($this->mockUser, $code);

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
            ->with($this->mockUser, $code)
            ->andThrow($originalException);

        $this->mockErrorHandler
            ->shouldReceive('classifyError')
            ->once()
            ->with($originalException)
            ->andReturn($errorType);

        $this->expectException(CloudStorageException::class);

        $this->provider->handleAuthCallback($this->mockUser, $code);
    }

    public function test_it_generates_auth_url_successfully()
    {
        $expectedUrl = 'https://accounts.google.com/oauth/authorize?...';

        $this->mockDriveService
            ->shouldReceive('getAuthUrl')
            ->once()
            ->with($this->mockUser)
            ->andReturn($expectedUrl);

        $result = $this->provider->getAuthUrl($this->mockUser);

        $this->assertEquals($expectedUrl, $result);
    }

    public function test_it_handles_auth_url_generation_failure()
    {
        $originalException = new Exception('Failed to generate auth URL');

        $this->mockDriveService
            ->shouldReceive('getAuthUrl')
            ->once()
            ->with($this->mockUser)
            ->andThrow($originalException);

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('Failed to generate Google Drive authorization URL');

        $this->provider->getAuthUrl($this->mockUser);
    }

    public function test_it_disconnects_user_account_successfully()
    {
        $this->mockDriveService
            ->shouldReceive('disconnect')
            ->once()
            ->with($this->mockUser);

        $this->provider->disconnect($this->mockUser);

        // If no exception is thrown, the test passes
        $this->assertTrue(true);
    }

    public function test_it_handles_disconnect_failure_gracefully()
    {
        $this->mockDriveService
            ->shouldReceive('disconnect')
            ->once()
            ->with($this->mockUser)
            ->andThrow(new Exception('Disconnect failed'));

        // Should not throw exception - disconnect failures should be handled gracefully
        $this->provider->disconnect($this->mockUser);

        $this->assertTrue(true);
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
            ->with($this->mockUser, $fileId)
            ->andReturn($mockFile);

        $result = $this->provider->getFileMetadata($this->mockUser, $fileId);

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
            ->with($this->mockUser, $fileId)
            ->andReturn($expectedContent);

        $result = $this->provider->downloadFile($this->mockUser, $fileId);

        $this->assertEquals($expectedContent, $result);
    }
}