<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\FileUpload;
use App\Services\FileManagerService;
use App\Services\FilePreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;

class FileManagerControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private FileUpload $testFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN
        ]);
        
        // Create test file
        $this->testFile = FileUpload::factory()->create([
            'original_filename' => 'test-document.pdf',
            'filename' => 'test-file-123.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'email' => 'test@example.com'
        ]);
        
        Storage::fake('public');
    }

    public function test_download_file_success_local_file()
    {
        // Create a fake file in storage
        Storage::disk('public')->put('uploads/' . $this->testFile->filename, 'test content');
        
        // Mock the FileManagerService with proper expectation
        $mockService = Mockery::mock(FileManagerService::class);
        $mockService->shouldReceive('downloadFile')
            ->with(Mockery::type(FileUpload::class), Mockery::type(User::class))
            ->once()
            ->andReturn(response()->streamDownload(function () {
                echo 'test content';
            }, 'test-document.pdf'));
        
        $this->app->instance(FileManagerService::class, $mockService);
        
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.download', $this->testFile));
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=test-document.pdf');
    }

    public function test_download_file_success_google_drive()
    {
        // Update test file to have Google Drive ID
        $this->testFile->update(['google_drive_file_id' => 'google-drive-file-123']);
        
        // Mock the FileManagerService
        $mockService = Mockery::mock(FileManagerService::class);
        $mockService->shouldReceive('downloadFile')
            ->with(Mockery::type(FileUpload::class), Mockery::type(User::class))
            ->once()
            ->andReturn(response()->streamDownload(function () {
                echo 'google drive content';
            }, 'test-document.pdf'));
        
        $this->app->instance(FileManagerService::class, $mockService);
        
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.download', $this->testFile));
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=test-document.pdf');
    }

    public function test_download_file_not_found()
    {
        // Mock the FileManagerService to throw exception
        $mockService = Mockery::mock(FileManagerService::class);
        $mockService->shouldReceive('downloadFile')
            ->with(Mockery::type(FileUpload::class), Mockery::type(User::class))
            ->once()
            ->andThrow(new \Exception('File not found in local storage or Google Drive.'));
        
        $this->app->instance(FileManagerService::class, $mockService);
        
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.download', $this->testFile));
        
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Error downloading file: File not found in local storage or Google Drive.');
    }

    public function test_download_file_json_response_error()
    {
        // Mock the FileManagerService to throw exception
        $mockService = Mockery::mock(FileManagerService::class);
        $mockService->shouldReceive('downloadFile')
            ->with(Mockery::type(FileUpload::class), Mockery::type(User::class))
            ->once()
            ->andThrow(new \Exception('Download failed'));
        
        $this->app->instance(FileManagerService::class, $mockService);
        
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.file-manager.download', $this->testFile));
        
        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Error downloading file: Download failed'
        ]);
    }

    public function test_download_file_requires_admin_access()
    {
        $regularUser = User::factory()->create([
            'role' => \App\Enums\UserRole::CLIENT
        ]);
        
        $response = $this->actingAs($regularUser)
            ->get(route('admin.file-manager.download', $this->testFile));
        
        // The admin routes are protected by middleware, so non-admin users get 403
        $response->assertStatus(403);
    }

    public function test_download_file_requires_authentication()
    {
        $response = $this->get(route('admin.file-manager.download', $this->testFile));
        
        // Unauthenticated users should be redirected to login
        $response->assertRedirect();
    }

    public function test_bulk_download_success()
    {
        $file2 = FileUpload::factory()->create([
            'original_filename' => 'test-document-2.pdf',
            'filename' => 'test-file-456.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'email' => 'test2@example.com'
        ]);
        
        // Mock the FileManagerService
        $mockService = Mockery::mock(FileManagerService::class);
        $mockService->shouldReceive('bulkDownloadFiles')
            ->with([$this->testFile->id, $file2->id])
            ->once()
            ->andReturn(response()->streamDownload(function () {
                echo 'zip content';
            }, 'bulk_download.zip', ['Content-Type' => 'application/zip']));
        
        $this->app->instance(FileManagerService::class, $mockService);
        
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.file-manager.bulk-download'), [
                'file_ids' => [$this->testFile->id, $file2->id]
            ]);
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
    }

    public function test_bulk_download_validation_error()
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.file-manager.bulk-download'), [
                'file_ids' => []
            ]);
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors('file_ids');
    }

    public function test_bulk_download_invalid_file_ids()
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.file-manager.bulk-download'), [
                'file_ids' => [999, 1000] // Non-existent file IDs
            ]);
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors('file_ids.0');
    }

    public function test_bulk_download_service_error()
    {
        // Mock the FileManagerService to throw exception
        $mockService = Mockery::mock(FileManagerService::class);
        $mockService->shouldReceive('bulkDownloadFiles')
            ->with([$this->testFile->id])
            ->once()
            ->andThrow(new \Exception('Bulk download failed'));
        
        $this->app->instance(FileManagerService::class, $mockService);
        
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.file-manager.bulk-download'), [
                'file_ids' => [$this->testFile->id]
            ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Error creating bulk download: Bulk download failed');
    }

    public function test_bulk_download_json_response_error()
    {
        // Mock the FileManagerService to throw exception
        $mockService = Mockery::mock(FileManagerService::class);
        $mockService->shouldReceive('bulkDownloadFiles')
            ->with([$this->testFile->id])
            ->once()
            ->andThrow(new \Exception('Bulk download failed'));
        
        $this->app->instance(FileManagerService::class, $mockService);
        
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.file-manager.bulk-download'), [
                'file_ids' => [$this->testFile->id]
            ]);
        
        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Error creating bulk download: Bulk download failed'
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}