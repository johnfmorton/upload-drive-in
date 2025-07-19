<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\FileUpload;
use App\Models\User;
use App\Models\ClientUserRelationship;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class FileManagerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->client = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create employee-client relationship
        ClientUserRelationship::create([
            'company_user_id' => $this->employee->id,
            'client_user_id' => $this->client->id,
            'is_primary' => true
        ]);
        
        Storage::fake('public');
    }

    public function test_complete_file_management_workflow_for_admin()
    {
        $this->actingAs($this->admin);

        // Create test files
        $files = FileUpload::factory()->count(3)->create([
            'client_user_id' => $this->client->id
        ]);

        // Test file listing
        $response = $this->get(route('admin.file-manager.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.file-manager.index');
        $response->assertViewHas('files');

        // Test file details view
        $response = $this->get(route('admin.file-manager.show', $files->first()));
        $response->assertStatus(200);
        $response->assertViewIs('admin.file-manager.show');

        // Test file update
        $response = $this->put(route('admin.file-manager.update', $files->first()), [
            'message' => 'Updated test message'
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify update
        $this->assertDatabaseHas('file_uploads', [
            'id' => $files->first()->id,
            'message' => 'Updated test message'
        ]);

        // Test file deletion
        $response = $this->delete(route('admin.file-manager.destroy', $files->first()));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify deletion
        $this->assertDatabaseMissing('file_uploads', [
            'id' => $files->first()->id
        ]);
    }

    public function test_bulk_operations_workflow()
    {
        $this->actingAs($this->admin);

        // Create test files
        $files = FileUpload::factory()->count(5)->create();
        $fileIds = $files->pluck('id')->toArray();

        // Test bulk deletion
        $response = $this->delete(route('admin.file-manager.bulk-destroy'), [
            'file_ids' => array_slice($fileIds, 0, 3)
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify bulk deletion
        $this->assertEquals(2, FileUpload::count());

        // Create files with local storage for bulk download test
        $remainingFiles = FileUpload::all();
        foreach ($remainingFiles as $file) {
            Storage::disk('public')->put('uploads/' . $file->filename, 'test content');
        }

        // Test bulk download
        $response = $this->post(route('admin.file-manager.bulk-download'), [
            'file_ids' => $remainingFiles->pluck('id')->toArray()
        ]);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
    }

    public function test_file_preview_and_download_workflow()
    {
        $this->actingAs($this->admin);

        // Create image file for preview
        $imageContent = $this->createTestImage();
        Storage::disk('public')->put('uploads/test-image.jpg', $imageContent);
        
        $imageFile = FileUpload::factory()->create([
            'filename' => 'test-image.jpg',
            'original_filename' => 'test-image.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => strlen($imageContent)
        ]);

        // Test file preview
        $response = $this->get(route('admin.file-manager.preview', $imageFile));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');

        // Test thumbnail generation
        $response = $this->get(route('admin.file-manager.thumbnail', $imageFile));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');

        // Test file download
        $response = $this->get(route('admin.file-manager.download', $imageFile));
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=test-image.jpg');
    }

    public function test_permission_based_access_workflow()
    {
        // Test client access to own files
        $this->actingAs($this->client);
        
        $clientFile = FileUpload::factory()->create(['client_user_id' => $this->client->id]);
        $otherFile = FileUpload::factory()->create(['client_user_id' => $this->admin->id]);

        // Client can preview their own file
        Storage::disk('public')->put('uploads/' . $clientFile->filename, 'test content');
        $response = $this->get(route('admin.file-manager.preview', $clientFile));
        $response->assertStatus(200);

        // Client cannot preview other's file
        $response = $this->get(route('admin.file-manager.preview', $otherFile));
        $response->assertStatus(404);

        // Test employee access to managed client files
        $this->actingAs($this->employee);
        
        // Employee can access managed client's file
        $response = $this->get(route('admin.file-manager.preview', $clientFile));
        $response->assertStatus(200);

        // Employee cannot access admin's file
        $response = $this->get(route('admin.file-manager.preview', $otherFile));
        $response->assertStatus(404);
    }

    public function test_ajax_requests_workflow()
    {
        $this->actingAs($this->admin);

        FileUpload::factory()->count(3)->create();

        // Test AJAX file listing
        $response = $this->getJson(route('admin.file-manager.index'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'files',
            'statistics',
            'filter_options'
        ]);

        // Test AJAX file details
        $file = FileUpload::first();
        $response = $this->getJson(route('admin.file-manager.show', $file));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'file'
        ]);

        // Test AJAX bulk deletion
        $fileIds = FileUpload::pluck('id')->toArray();
        $response = $this->deleteJson(route('admin.file-manager.bulk-destroy'), [
            'file_ids' => $fileIds
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_filtering_and_search_workflow()
    {
        $this->actingAs($this->admin);

        // Create files with different attributes
        FileUpload::factory()->create([
            'original_filename' => 'important-document.pdf',
            'mime_type' => 'application/pdf',
            'email' => 'test@example.com',
            'created_at' => now()->subDays(5)
        ]);
        
        FileUpload::factory()->create([
            'original_filename' => 'image-file.jpg',
            'mime_type' => 'image/jpeg',
            'email' => 'other@example.com',
            'created_at' => now()
        ]);

        // Test search filtering
        $response = $this->get(route('admin.file-manager.index', ['search' => 'important']));
        $response->assertStatus(200);
        $response->assertSee('important-document.pdf');
        $response->assertDontSee('image-file.jpg');

        // Test file type filtering
        $response = $this->get(route('admin.file-manager.index', ['file_type' => 'image']));
        $response->assertStatus(200);
        $response->assertSee('image-file.jpg');
        $response->assertDontSee('important-document.pdf');

        // Test date range filtering
        $response = $this->get(route('admin.file-manager.index', [
            'date_from' => now()->subDays(1)->toDateString(),
            'date_to' => now()->toDateString()
        ]));
        $response->assertStatus(200);
        $response->assertSee('image-file.jpg');
        $response->assertDontSee('important-document.pdf');

        // Test email filtering
        $response = $this->get(route('admin.file-manager.index', ['user_email' => 'test@example.com']));
        $response->assertStatus(200);
        $response->assertSee('important-document.pdf');
        $response->assertDontSee('image-file.jpg');
    }

    public function test_error_handling_workflow()
    {
        $this->actingAs($this->admin);

        // Test accessing non-existent file
        $response = $this->get(route('admin.file-manager.show', 999));
        $response->assertStatus(404);

        // Test invalid bulk operation
        $response = $this->deleteJson(route('admin.file-manager.bulk-destroy'), [
            'file_ids' => [999, 998]
        ]);
        $response->assertStatus(422);

        // Test unauthorized access
        $this->actingAs($this->client);
        $response = $this->get(route('admin.file-manager.index'));
        $response->assertStatus(404);
    }

    public function test_pending_uploads_processing_workflow()
    {
        $this->actingAs($this->admin);

        // Create pending uploads
        FileUpload::factory()->count(3)->create(['google_drive_file_id' => null]);
        FileUpload::factory()->count(2)->create(['google_drive_file_id' => 'completed']);

        // Test processing pending uploads
        $response = $this->post(route('admin.file-manager.process-pending'));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_responsive_layout_elements()
    {
        $this->actingAs($this->admin);

        FileUpload::factory()->count(5)->create();

        $response = $this->get(route('admin.file-manager.index'));
        $response->assertStatus(200);

        // Check for responsive layout elements
        $response->assertSee('file-manager'); // Main container
        $response->assertSee('bulk-actions'); // Bulk action controls
        $response->assertSee('file-grid'); // File grid/table
        $response->assertSee('search'); // Search functionality
    }

    private function createTestImage(): string
    {
        // Create a simple test image using GD
        $image = imagecreate(100, 100);
        $white = imagecolorallocate($image, 255, 255, 255);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $white);
        imagesetpixel($image, 50, 50, $red);
        
        ob_start();
        imagejpeg($image);
        $content = ob_get_clean();
        imagedestroy($image);
        
        return $content;
    }
}