<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FileManagerService;
use App\Models\FileUpload;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileManagerServiceTest extends TestCase
{
    use RefreshDatabase;

    private FileManagerService $fileManagerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileManagerService = new FileManagerService();
        
        // Mock storage disk
        Storage::fake('public');
    }

    /** @test */
    public function bulk_delete_files_deletes_all_specified_files()
    {
        // Create test files
        $files = FileUpload::factory()->count(3)->create();
        $fileIds = $files->pluck('id')->toArray();

        // Create local files for testing
        foreach ($files as $file) {
            Storage::disk('public')->put('uploads/' . $file->filename, 'test content');
        }

        $deletedCount = $this->fileManagerService->bulkDeleteFiles($fileIds);

        $this->assertEquals(3, $deletedCount);
        $this->assertEquals(0, FileUpload::whereIn('id', $fileIds)->count());
        
        // Verify local files are deleted
        foreach ($files as $file) {
            Storage::disk('public')->assertMissing('uploads/' . $file->filename);
        }
    }

    /** @test */
    public function bulk_delete_files_continues_on_individual_failures()
    {
        // Create test files
        $files = FileUpload::factory()->count(3)->create();
        $fileIds = $files->pluck('id')->toArray();

        // Create local files for first two files only
        Storage::disk('public')->put('uploads/' . $files[0]->filename, 'test content');
        Storage::disk('public')->put('uploads/' . $files[1]->filename, 'test content');

        // Mock the deleteFile method to fail for the second file
        $service = $this->getMockBuilder(FileManagerService::class)
            ->onlyMethods(['deleteFile'])
            ->getMock();

        $service->expects($this->exactly(3))
            ->method('deleteFile')
            ->willReturnCallback(function ($file) use ($files) {
                if ($file->id === $files[1]->id) {
                    throw new \Exception('Simulated deletion failure');
                }
                $file->delete();
                return true;
            });

        Log::shouldReceive('error')->once();
        Log::shouldReceive('info')->once();

        $deletedCount = $service->bulkDeleteFiles($fileIds);

        // Should delete 2 out of 3 files (first and third succeed, second fails)
        $this->assertEquals(2, $deletedCount);
    }

    /** @test */
    public function bulk_delete_files_handles_google_drive_files()
    {
        // Create test files with Google Drive IDs
        $files = FileUpload::factory()->count(2)->create([
            'google_drive_file_id' => 'test_drive_id'
        ]);
        $fileIds = $files->pluck('id')->toArray();

        // Mock the FileUpload model's deleteFromGoogleDrive method
        $mockFile = $this->getMockBuilder(FileUpload::class)
            ->onlyMethods(['deleteFromGoogleDrive'])
            ->getMock();
        
        $mockFile->method('deleteFromGoogleDrive')->willReturn(true);

        $deletedCount = $this->fileManagerService->bulkDeleteFiles($fileIds);

        $this->assertEquals(2, $deletedCount);
        $this->assertEquals(0, FileUpload::whereIn('id', $fileIds)->count());
    }

    /** @test */
    public function bulk_delete_files_logs_operations()
    {
        $files = FileUpload::factory()->count(2)->create();
        $fileIds = $files->pluck('id')->toArray();

        Log::shouldReceive('info')->atLeast()->once(); // Allow multiple info logs
        Log::shouldReceive('error')->zeroOrMoreTimes(); // Allow error logs
        Log::shouldReceive('warning')->zeroOrMoreTimes(); // Allow warning logs (from Google Drive deletion)

        $deletedCount = $this->fileManagerService->bulkDeleteFiles($fileIds);
        
        $this->assertEquals(2, $deletedCount);
    }

    /** @test */
    public function bulk_delete_files_returns_zero_for_empty_array()
    {
        $deletedCount = $this->fileManagerService->bulkDeleteFiles([]);
        $this->assertEquals(0, $deletedCount);
    }

    /** @test */
    public function bulk_delete_files_handles_non_existent_file_ids()
    {
        // Try to delete files that don't exist
        $nonExistentIds = [999, 1000, 1001];
        
        $deletedCount = $this->fileManagerService->bulkDeleteFiles($nonExistentIds);
        
        // Should return 0 since no files were found to delete
        $this->assertEquals(0, $deletedCount);
    }

    /** @test */
    public function bulk_download_files_creates_zip_with_local_files()
    {
        // Create test files
        $files = FileUpload::factory()->count(3)->create([
            'original_filename' => 'test_file.txt',
            'filename' => 'stored_file.txt'
        ]);
        $fileIds = $files->pluck('id')->toArray();

        // Create local files
        foreach ($files as $index => $file) {
            Storage::disk('public')->put('uploads/' . $file->filename, "Test content for file {$index}");
        }

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $response = $this->fileManagerService->bulkDownloadFiles($fileIds);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
        $this->assertEquals('application/zip', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('bulk_download_', $response->headers->get('Content-Disposition'));
    }

    /** @test */
    public function bulk_download_files_skips_google_drive_files()
    {
        // Create files with Google Drive IDs (no local copies)
        $files = FileUpload::factory()->count(2)->create([
            'google_drive_file_id' => 'drive_123',
            'original_filename' => 'drive_file.txt'
        ]);
        $fileIds = $files->pluck('id')->toArray();

        Log::shouldReceive('warning')->times(2); // Should warn about skipped files
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $this->expectException(\Exception::class);

        $this->fileManagerService->bulkDownloadFiles($fileIds);
    }

    /** @test */
    public function bulk_download_files_handles_mixed_file_types()
    {
        // Create mix of local and Google Drive files
        $localFile = FileUpload::factory()->create([
            'original_filename' => 'local_file.txt',
            'filename' => 'local_stored.txt',
            'google_drive_file_id' => null
        ]);
        $driveFile = FileUpload::factory()->create([
            'original_filename' => 'drive_file.txt',
            'google_drive_file_id' => 'drive_123'
        ]);

        // Create local file content
        Storage::disk('public')->put('uploads/' . $localFile->filename, 'Local file content');

        $fileIds = [$localFile->id, $driveFile->id];

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('warning')->once(); // For the skipped Google Drive file
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $response = $this->fileManagerService->bulkDownloadFiles($fileIds);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }

    /** @test */
    public function bulk_download_files_handles_duplicate_filenames()
    {
        // Create files with same original filename
        $files = FileUpload::factory()->count(3)->create([
            'original_filename' => 'duplicate.txt'
        ]);
        $fileIds = $files->pluck('id')->toArray();

        // Create local files
        foreach ($files as $index => $file) {
            Storage::disk('public')->put('uploads/' . $file->filename, "Content {$index}");
        }

        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $response = $this->fileManagerService->bulkDownloadFiles($fileIds);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }

    /** @test */
    public function bulk_download_files_throws_exception_for_empty_file_list()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No files found for download');

        $this->fileManagerService->bulkDownloadFiles([]);
    }

    /** @test */
    public function bulk_download_files_throws_exception_for_non_existent_files()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No files found for download');

        $this->fileManagerService->bulkDownloadFiles([999, 1000]);
    }
}
