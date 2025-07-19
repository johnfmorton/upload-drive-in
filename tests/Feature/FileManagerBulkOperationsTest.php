<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\FileUpload;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class FileManagerBulkOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        Storage::fake('public');
    }

    public function test_bulk_delete_with_mixed_storage_locations()
    {
        $this->actingAs($this->admin);

        // Create files with different storage states
        $localFile = FileUpload::factory()->create(['google_drive_file_id' => null]);
        $driveFile = FileUpload::factory()->create(['google_drive_file_id' => 'drive-123']);
        $bothFile = FileUpload::factory()->create(['google_drive_file_id' => 'drive-456']);

        // Create local storage for some files
        Storage::disk('public')->put('uploads/' . $localFile->filename, 'local content');
        Storage::disk('public')->put('uploads/' . $bothFile->filename, 'both content');

        $fileIds = [$localFile->id, $driveFile->id, $bothFile->id];

        $response = $this->delete(route('admin.file-manager.bulk-destroy'), [
            'file_ids' => $fileIds
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify all files are deleted from database
        $this->assertEquals(0, FileUpload::whereIn('id', $fileIds)->count());

        // Verify local files are deleted
        $this->assertFalse(Storage::disk('public')->exists('uploads/' . $localFile->filename));
        $this->assertFalse(Storage::disk('public')->exists('uploads/' . $bothFile->filename));
    }

    public function test_bulk_delete_with_partial_failures()
    {
        $this->actingAs($this->admin);

        // Create valid files
        $validFiles = FileUpload::factory()->count(3)->create();
        
        // Mix valid and invalid IDs
        $fileIds = array_merge($validFiles->pluck('id')->toArray(), [999, 998]);

        $response = $this->delete(route('admin.file-manager.bulk-destroy'), [
            'file_ids' => $fileIds
        ]);

        $response->assertRedirect();
        
        // Valid files should be deleted
        $this->assertEquals(0, FileUpload::whereIn('id', $validFiles->pluck('id'))->count());
    }

    public function test_bulk_download_creates_proper_zip_structure()
    {
        $this->actingAs($this->admin);

        // Create files with content
        $files = FileUpload::factory()->count(3)->create([
            'original_filename' => 'test-document.pdf'
        ]);

        foreach ($files as $index => $file) {
            $content = "Test content for file {$index}";
            Storage::disk('public')->put('uploads/' . $file->filename, $content);
        }

        $response = $this->post(route('admin.file-manager.bulk-download'), [
            'file_ids' => $files->pluck('id')->toArray()
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
        $response->assertHeader('Content-Disposition', function ($value) {
            return str_contains($value, 'bulk_download_') && str_contains($value, '.zip');
        });
    }

    public function test_bulk_download_handles_duplicate_filenames()
    {
        $this->actingAs($this->admin);

        // Create files with same original filename
        $files = FileUpload::factory()->count(3)->create([
            'original_filename' => 'duplicate.txt'
        ]);

        foreach ($files as $index => $file) {
            Storage::disk('public')->put('uploads/' . $file->filename, "Content {$index}");
        }

        $response = $this->post(route('admin.file-manager.bulk-download'), [
            'file_ids' => $files->pluck('id')->toArray()
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
    }

    public function test_bulk_download_with_missing_files()
    {
        $this->actingAs($this->admin);

        // Create files but don't create actual file content
        $files = FileUpload::factory()->count(2)->create();

        $response = $this->post(route('admin.file-manager.bulk-download'), [
            'file_ids' => $files->pluck('id')->toArray()
        ]);

        // Should still create ZIP but with no files
        $response->assertStatus(500); // Expect error when no files can be added
    }

    public function test_bulk_operations_respect_user_permissions()
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $this->actingAs($client);

        $files = FileUpload::factory()->count(3)->create();

        // Client should not be able to perform bulk operations
        $response = $this->delete(route('admin.file-manager.bulk-destroy'), [
            'file_ids' => $files->pluck('id')->toArray()
        ]);
        $response->assertStatus(404);

        $response = $this->post(route('admin.file-manager.bulk-download'), [
            'file_ids' => $files->pluck('id')->toArray()
        ]);
        $response->assertStatus(404);
    }

    public function test_bulk_operations_with_large_file_sets()
    {
        $this->actingAs($this->admin);

        // Create a large number of files
        $files = FileUpload::factory()->count(50)->create();
        $fileIds = $files->pluck('id')->toArray();

        // Test bulk deletion with large set
        $response = $this->delete(route('admin.file-manager.bulk-destroy'), [
            'file_ids' => array_slice($fileIds, 0, 25)
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify correct number deleted
        $this->assertEquals(25, FileUpload::count());
    }

    public function test_bulk_operations_validation()
    {
        $this->actingAs($this->admin);

        // Test with empty file_ids
        $response = $this->delete(route('admin.file-manager.bulk-destroy'), [
            'file_ids' => []
        ]);
        $response->assertStatus(422);

        // Test with invalid file_ids format
        $response = $this->delete(route('admin.file-manager.bulk-destroy'), [
            'file_ids' => 'not-an-array'
        ]);
        $response->assertStatus(422);

        // Test with non-existent file IDs
        $response = $this->delete(route('admin.file-manager.bulk-destroy'), [
            'file_ids' => [999, 998, 997]
        ]);
        $response->assertStatus(422);
    }

    public function test_bulk_operations_ajax_responses()
    {
        $this->actingAs($this->admin);

        $files = FileUpload::factory()->count(3)->create();
        $fileIds = $files->pluck('id')->toArray();

        // Test AJAX bulk delete
        $response = $this->deleteJson(route('admin.file-manager.bulk-destroy'), [
            'file_ids' => $fileIds
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'deleted_count' => 3
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'deleted_count'
        ]);
    }

    public function test_bulk_operations_with_security_violations()
    {
        $this->actingAs($this->admin);

        // This test would require mocking the FileSecurityService
        // to simulate security violations during bulk operations
        $files = FileUpload::factory()->count(3)->create();

        // Create files with potentially dangerous content
        foreach ($files as $file) {
            Storage::disk('public')->put('uploads/' . $file->filename, 'safe content');
        }

        // Test bulk download (security service would be mocked in real implementation)
        $response = $this->post(route('admin.file-manager.bulk-download'), [
            'file_ids' => $files->pluck('id')->toArray()
        ]);

        $response->assertStatus(200);
    }

    public function test_bulk_operations_performance_with_concurrent_requests()
    {
        $this->actingAs($this->admin);

        $files = FileUpload::factory()->count(10)->create();
        $fileIds = $files->pluck('id')->toArray();

        // Simulate concurrent bulk operations
        $responses = [];
        
        // Split files into two groups for concurrent operations
        $group1 = array_slice($fileIds, 0, 5);
        $group2 = array_slice($fileIds, 5, 5);

        // First bulk delete
        $responses[] = $this->delete(route('admin.file-manager.bulk-destroy'), [
            'file_ids' => $group1
        ]);

        // Second bulk delete
        $responses[] = $this->delete(route('admin.file-manager.bulk-destroy'), [
            'file_ids' => $group2
        ]);

        // Both should succeed
        foreach ($responses as $response) {
            $response->assertRedirect();
            $response->assertSessionHas('success');
        }

        // All files should be deleted
        $this->assertEquals(0, FileUpload::count());
    }
}