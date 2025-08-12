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
use Illuminate\Support\Facades\Storage;
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

        // Mock response with body
        $mockResponse = Mockery::mock();
        $mockBody = Mockery::mock();
        $mockBody->shouldReceive('getContents')->andReturn('file content');
        $mockResponse->shouldReceive('getBody')->andReturn($mockBody);

        $mockFilesResource->shouldReceive('get')
            ->with('test-file-id', ['alt' => 'media'])
            ->once()
            ->andReturn($mockResponse);

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

        // Mock response with body
        $mockResponse = Mockery::mock();
        $mockBody = Mockery::mock();
        $mockBody->shouldReceive('getContents')->andReturn('large file content');
        $mockResponse->shouldReceive('getBody')->andReturn($mockBody);

        $mockFilesResource->shouldReceive('get')
            ->with('large-file-id', ['alt' => 'media'])
            ->once()
            ->andReturn($mockResponse);

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

    /**
     * Test getRootFolderId always returns 'root' as default.
     */
    public function test_get_root_folder_id_returns_default()
    {
        $result = $this->service->getRootFolderId();
        
        $this->assertEquals('root', $result);
    }

    /**
     * Test getEffectiveRootFolderId returns user's configured folder.
     */
    public function test_get_effective_root_folder_id_with_user_setting()
    {
        $this->user->google_drive_root_folder_id = 'user-specific-folder-id';
        
        $result = $this->service->getEffectiveRootFolderId($this->user);
        
        $this->assertEquals('user-specific-folder-id', $result);
    }

    /**
     * Test getEffectiveRootFolderId returns 'root' when user has no setting.
     */
    public function test_get_effective_root_folder_id_with_no_user_setting()
    {
        $this->user->google_drive_root_folder_id = null;
        
        $result = $this->service->getEffectiveRootFolderId($this->user);
        
        $this->assertEquals('root', $result);
    }

    /**
     * Test getEffectiveRootFolderId returns 'root' when user setting is empty string.
     */
    public function test_get_effective_root_folder_id_with_empty_user_setting()
    {
        $this->user->google_drive_root_folder_id = '';
        
        $result = $this->service->getEffectiveRootFolderId($this->user);
        
        $this->assertEquals('root', $result);
    }

    /**
     * Test findUserFolderId uses effective root folder ID.
     */
    public function test_find_user_folder_id_uses_effective_root_folder()
    {
        $this->user->google_drive_root_folder_id = 'custom-root-folder';
        
        // Mock the Google Drive service
        $mockDriveService = Mockery::mock(Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockDriveService->files = $mockFilesResource;

        // Mock the files list response
        $mockFilesList = Mockery::mock();
        $mockFilesList->shouldReceive('getFiles')->andReturn([]);
        
        $expectedQuery = "name = 'User: test-at-example-dot-com' and mimeType = 'application/vnd.google-apps.folder' and 'custom-root-folder' in parents and trashed = false";
        
        $mockFilesResource->shouldReceive('listFiles')
            ->with([
                'q' => $expectedQuery,
                'fields' => 'files(id, name)',
                'pageSize' => 1
            ])
            ->once()
            ->andReturn($mockFilesList);

        // Mock the service creation
        $partialMock = Mockery::mock(GoogleDriveService::class)->makePartial();
        $partialMock->shouldReceive('getDriveService')
            ->with($this->user)
            ->andReturn($mockDriveService);

        $result = $partialMock->findUserFolderId($this->user, 'test@example.com');
        
        $this->assertNull($result);
    }

    /**
     * Test getOrCreateUserFolderId uses effective root folder ID for creation.
     */
    public function test_get_or_create_user_folder_id_uses_effective_root_folder()
    {
        $this->user->google_drive_root_folder_id = 'custom-root-folder';
        
        // Mock the Google Drive service
        $mockDriveService = Mockery::mock(Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockDriveService->files = $mockFilesResource;

        // Mock the files list response (folder not found)
        $mockFilesList = Mockery::mock();
        $mockFilesList->shouldReceive('getFiles')->andReturn([]);
        
        $mockFilesResource->shouldReceive('listFiles')->andReturn($mockFilesList);

        // Mock folder creation
        $mockCreatedFolder = Mockery::mock(DriveFile::class);
        $mockCreatedFolder->shouldReceive('getId')->andReturn('new-folder-id');
        
        $mockFilesResource->shouldReceive('create')
            ->with(Mockery::on(function ($folderMetadata) {
                return $folderMetadata->getParents() === ['custom-root-folder'] &&
                       $folderMetadata->getName() === 'User: test-at-example-dot-com';
            }), ['fields' => 'id'])
            ->once()
            ->andReturn($mockCreatedFolder);

        // Mock the service creation
        $partialMock = Mockery::mock(GoogleDriveService::class)->makePartial();
        $partialMock->shouldReceive('getDriveService')
            ->with($this->user)
            ->andReturn($mockDriveService);

        $result = $partialMock->getOrCreateUserFolderId($this->user, 'test@example.com');
        
        $this->assertEquals('new-folder-id', $result);
    }

    /**
     * Test uploadFileForUser uses effective root folder ID.
     */
    public function test_upload_file_for_user_uses_effective_root_folder()
    {
        $this->user->google_drive_root_folder_id = 'employee-root-folder';
        $this->user->role = \App\Enums\UserRole::EMPLOYEE;
        $this->user->save();
        
        // Mock storage facade
        Storage::fake('public');
        Storage::disk('public')->put('test-file.txt', 'test content');

        // Mock the Google Drive service
        $mockDriveService = Mockery::mock(Drive::class);
        $mockFilesResource = Mockery::mock();
        $mockDriveService->files = $mockFilesResource;

        // Mock folder search (not found)
        $mockFilesList = Mockery::mock();
        $mockFilesList->shouldReceive('getFiles')->andReturn([]);
        
        $expectedQuery = "name = 'User: client-at-example-dot-com' and mimeType = 'application/vnd.google-apps.folder' and 'employee-root-folder' in parents and trashed = false";
        
        $mockFilesResource->shouldReceive('listFiles')
            ->with([
                'q' => $expectedQuery,
                'fields' => 'files(id, name)',
                'pageSize' => 1
            ])
            ->once()
            ->andReturn($mockFilesList);

        // Mock folder creation
        $mockCreatedFolder = Mockery::mock(\Google\Service\Drive\DriveFile::class);
        $mockCreatedFolder->shouldReceive('getId')->andReturn('client-folder-id');
        
        $mockFilesResource->shouldReceive('create')
            ->with(Mockery::on(function ($folderMetadata) {
                return $folderMetadata->getParents() === ['employee-root-folder'];
            }), ['fields' => 'id'])
            ->once()
            ->andReturn($mockCreatedFolder);

        // Mock file upload
        $mockUploadedFile = Mockery::mock(\Google\Service\Drive\DriveFile::class);
        $mockUploadedFile->shouldReceive('getId')->andReturn('uploaded-file-id');
        
        $mockFilesResource->shouldReceive('create')
            ->with(Mockery::any(), Mockery::on(function ($options) {
                return isset($options['data']) && isset($options['mimeType']);
            }))
            ->once()
            ->andReturn($mockUploadedFile);

        // Create a partial mock that allows us to override specific methods
        $partialMock = Mockery::mock(GoogleDriveService::class)->makePartial();
        
        // Mock the getDriveService method to return our mock
        $partialMock->shouldReceive('getDriveService')
            ->with($this->user)
            ->andReturn($mockDriveService);

        $result = $partialMock->uploadFileForUser(
            $this->user,
            'test-file.txt',
            'client@example.com',
            'test-file.txt',
            'text/plain'
        );
        
        $this->assertEquals('uploaded-file-id', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}