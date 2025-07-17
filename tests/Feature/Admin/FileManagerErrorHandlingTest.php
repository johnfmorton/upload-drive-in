<?php

namespace Tests\Feature\Admin;

use App\Exceptions\FileAccessException;
use App\Exceptions\FileManagerException;
use App\Exceptions\GoogleDriveException;
use App\Models\FileUpload;
use App\Models\User;
use App\Services\FileManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class FileManagerErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected FileUpload $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->file = FileUpload::factory()->create();
    }

    /** @test */
    public function it_handles_file_access_exceptions_gracefully()
    {
        // Mock the FileManagerService to throw a FileAccessException
        $this->mock(FileManagerService::class, function ($mock) {
            $mock->shouldReceive('getFileDetails')
                ->andThrow(new FileAccessException(
                    message: 'Access denied to file',
                    userMessage: 'You do not have permission to access this file.',
                    code: 403,
                    context: ['file_id' => $this->file->id]
                ));
        });

        // Test JSON response
        $response = $this->actingAs($this->admin)
            ->getJson("/admin/file-manager/{$this->file->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'You do not have permission to access this file.'
            ]);

        // Test HTML response
        $response = $this->actingAs($this->admin)
            ->get("/admin/file-manager/{$this->file->id}");

        $response->assertRedirect(route('admin.file-manager.index'))
            ->assertSessionHas('error', 'You do not have permission to access this file.');
    }

    /** @test */
    public function it_handles_google_drive_exceptions_gracefully()
    {
        // Mock the FileManagerService to throw a GoogleDriveException
        $this->mock(FileManagerService::class, function ($mock) {
            $mock->shouldReceive('downloadFile')
                ->andThrow(GoogleDriveException::tokenExpired($this->admin->id));
        });

        // Test JSON response
        $response = $this->actingAs($this->admin)
            ->getJson("/admin/file-manager/{$this->file->id}/download");

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Your Google Drive access has expired. Please reconnect your Google Drive account in the settings.'
            ]);

        // Test HTML response
        $response = $this->actingAs($this->admin)
            ->get("/admin/file-manager/{$this->file->id}/download");

        $response->assertRedirect()
            ->assertSessionHas('error', 'Your Google Drive access has expired. Please reconnect your Google Drive account in the settings.');
    }

    /** @test */
    public function it_handles_retryable_errors_with_retry_option()
    {
        // Mock the FileManagerService to throw a retryable exception
        $this->mock(FileManagerService::class, function ($mock) {
            $mock->shouldReceive('bulkDownloadFiles')
                ->andThrow(new FileManagerException(
                    message: 'Temporary server error',
                    userMessage: 'The server is temporarily unavailable. Please try again.',
                    code: 500,
                    context: ['operation' => 'bulk_download'],
                    isRetryable: true
                ));
        });

        // Test JSON response
        $response = $this->actingAs($this->admin)
            ->postJson("/admin/file-manager/bulk-download", [
                'file_ids' => [$this->file->id]
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'The server is temporarily unavailable. Please try again.',
                'is_retryable' => true
            ]);
    }

    /** @test */
    public function it_handles_quota_exceeded_errors_with_appropriate_message()
    {
        // Mock the FileManagerService to throw a quota exceeded exception
        $this->mock(FileManagerService::class, function ($mock) {
            $mock->shouldReceive('bulkDeleteFiles')
                ->andThrow(GoogleDriveException::quotaExceeded('bulk_delete'));
        });

        // Test JSON response
        $response = $this->actingAs($this->admin)
            ->postJson("/admin/file-manager/bulk-destroy", [
                'file_ids' => [$this->file->id]
            ]);

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Google Drive usage limit has been reached. Please try again later or contact support.'
            ]);
    }

    /** @test */
    public function it_handles_file_not_found_errors_properly()
    {
        // Mock the FileManagerService to throw a file not found exception
        $this->mock(FileManagerService::class, function ($mock) {
            $mock->shouldReceive('getFileDetails')
                ->andThrow(GoogleDriveException::fileNotFoundInDrive('fake-file-id', 'test.pdf'));
        });

        // Test JSON response
        $response = $this->actingAs($this->admin)
            ->getJson("/admin/file-manager/{$this->file->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'The file could not be found in Google Drive. It may have been deleted or moved.'
            ]);
    }
}