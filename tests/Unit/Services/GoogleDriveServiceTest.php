<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\GoogleDriveService;
use App\Models\User;
use App\Models\GoogleDriveToken;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mockery;
use Exception;

class GoogleDriveServiceTest extends TestCase
{
    private GoogleDriveService $service;
    private User $user;
    private GoogleDriveToken $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new GoogleDriveService();
        
        // Create test user and token
        $this->user = User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN
        ]);
        
        $this->token = GoogleDriveToken::create([
            'user_id' => $this->user->id,
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/drive']
        ]);
    }

    public function test_download_file_success()
    {
        // Mock the Google Drive service
        $mockDriveService = Mockery::mock(Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockDriveService->files = $mockFilesResource;

        // Mock file metadata response
        $mockFile = Mockery::mock(DriveFile::class);
        $mockFile->shouldReceive('getName')->andReturn('test-file.txt');
        $mockFile->shouldReceive('getSize')->andReturn('1024');

        $mockFilesResource->shouldReceive('get')
            ->with('test-file-id', ['fields' => 'id,name,size,mimeType'])
            ->once()
            ->andReturn($mockFile);

        $mockFilesResource->shouldReceive('get')
            ->with('test-file-id', ['alt' => 'media'])
            ->once()
            ->andReturn('file content');

        // Mock the service creation
        $partialMock = Mockery::mock(GoogleDriveService::class)->makePartial();
        $partialMock->shouldReceive('getDriveService')
            ->with($this->user)
            ->andReturn($mockDriveService);

        $result = $partialMock->downloadFile($this->user, 'test-file-id');

        $this->assertEquals('file content', $result);
    }

    public function test_download_file_not_found()
    {
        // Mock the Google Drive service
        $mockDriveService = Mockery::mock(Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockDriveService->files = $mockFilesResource;

        $mockFilesResource->shouldReceive('get')
            ->with('non-existent-file-id', ['fields' => 'id,name,size,mimeType'])
            ->once()
            ->andReturn(null);

        // Mock the service creation
        $partialMock = Mockery::mock(GoogleDriveService::class)->makePartial();
        $partialMock->shouldReceive('getDriveService')
            ->with($this->user)
            ->andReturn($mockDriveService);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File not found: non-existent-file-id');

        $partialMock->downloadFile($this->user, 'non-existent-file-id');
    }

    public function test_download_file_stream_success()
    {
        // Mock the Google Drive service
        $mockDriveService = Mockery::mock(Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockDriveService->files = $mockFilesResource;

        // Mock file metadata response
        $mockFile = Mockery::mock(DriveFile::class);
        $mockFile->shouldReceive('getName')->andReturn('large-file.pdf');
        $mockFile->shouldReceive('getSize')->andReturn('10485760'); // 10MB

        $mockFilesResource->shouldReceive('get')
            ->with('large-file-id', ['fields' => 'id,name,size,mimeType'])
            ->once()
            ->andReturn($mockFile);

        $mockFilesResource->shouldReceive('get')
            ->with('large-file-id', ['alt' => 'media'])
            ->once()
            ->andReturn('large file content');

        // Mock the service creation
        $partialMock = Mockery::mock(GoogleDriveService::class)->makePartial();
        $partialMock->shouldReceive('getDriveService')
            ->with($this->user)
            ->andReturn($mockDriveService);

        $result = $partialMock->downloadFileStream($this->user, 'large-file-id');

        $this->assertIsResource($result);
        fclose($result);
    }

    public function test_get_file_metadata_success()
    {
        // Mock the Google Drive service
        $mockDriveService = Mockery::mock(Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockDriveService->files = $mockFilesResource;

        // Mock file metadata response
        $mockFile = Mockery::mock(DriveFile::class);
        $mockFile->shouldReceive('getName')->andReturn('document.pdf');
        $mockFile->shouldReceive('getSize')->andReturn('2048');
        $mockFile->shouldReceive('getMimeType')->andReturn('application/pdf');

        $mockFilesResource->shouldReceive('get')
            ->with('file-id', [
                'fields' => 'id,name,size,mimeType,createdTime,modifiedTime,parents'
            ])
            ->once()
            ->andReturn($mockFile);

        // Mock the service creation
        $partialMock = Mockery::mock(GoogleDriveService::class)->makePartial();
        $partialMock->shouldReceive('getDriveService')
            ->with($this->user)
            ->andReturn($mockDriveService);

        $result = $partialMock->getFileMetadata($this->user, 'file-id');

        $this->assertInstanceOf(DriveFile::class, $result);
        $this->assertEquals('document.pdf', $result->getName());
        $this->assertEquals('2048', $result->getSize());
        $this->assertEquals('application/pdf', $result->getMimeType());
    }

    public function test_get_file_metadata_not_found()
    {
        // Mock the Google Drive service
        $mockDriveService = Mockery::mock(Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockDriveService->files = $mockFilesResource;

        $mockFilesResource->shouldReceive('get')
            ->with('non-existent-id', [
                'fields' => 'id,name,size,mimeType,createdTime,modifiedTime,parents'
            ])
            ->once()
            ->andReturn(null);

        // Mock the service creation
        $partialMock = Mockery::mock(GoogleDriveService::class)->makePartial();
        $partialMock->shouldReceive('getDriveService')
            ->with($this->user)
            ->andReturn($mockDriveService);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File not found: non-existent-id');

        $partialMock->getFileMetadata($this->user, 'non-existent-id');
    }

    public function test_download_file_api_exception()
    {
        // Mock the Google Drive service
        $mockDriveService = Mockery::mock(Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockDriveService->files = $mockFilesResource;

        $mockFilesResource->shouldReceive('get')
            ->with('error-file-id', ['fields' => 'id,name,size,mimeType'])
            ->once()
            ->andThrow(new Exception('Google API Error'));

        // Mock the service creation
        $partialMock = Mockery::mock(GoogleDriveService::class)->makePartial();
        $partialMock->shouldReceive('getDriveService')
            ->with($this->user)
            ->andReturn($mockDriveService);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Google API Error');

        $partialMock->downloadFile($this->user, 'error-file-id');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}