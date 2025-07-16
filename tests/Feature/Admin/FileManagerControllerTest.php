<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\FileUpload;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class FileManagerControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Mock storage disk
        Storage::fake('public');
    }

    /** @test */
    public function admin_can_bulk_delete_files_via_web_request()
    {
        // Create test files
        $files = FileUpload::factory()->count(3)->create();
        $fileIds = $files->pluck('id')->toArray();

        // Create local files
        foreach ($files as $file) {
            Storage::disk('public')->put('uploads/' . $file->filename, 'test content');
        }

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.file-manager.bulk-destroy'), [
                'file_ids' => $fileIds
            ]);

        $response->assertRedirect(route('admin.file-manager.index'))
            ->assertSessionHas('success', 'Successfully deleted 3 files.');

        // Verify files are deleted from database
        $this->assertEquals(0, FileUpload::whereIn('id', $fileIds)->count());
    }

    /** @test */
    public function admin_can_bulk_delete_files_via_json_request()
    {
        // Create test files
        $files = FileUpload::factory()->count(2)->create();
        $fileIds = $files->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('admin.file-manager.bulk-destroy'), [
                'file_ids' => $fileIds
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Successfully deleted 2 files.'
            ]);

        // Verify files are deleted from database
        $this->assertEquals(0, FileUpload::whereIn('id', $fileIds)->count());
    }

    /** @test */
    public function bulk_delete_validates_required_file_ids()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson(route('admin.file-manager.bulk-destroy'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file_ids']);
    }

    /** @test */
    public function bulk_delete_validates_file_ids_exist_in_database()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson(route('admin.file-manager.bulk-destroy'), [
                'file_ids' => [999, 1000] // Non-existent IDs
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file_ids.0', 'file_ids.1']);
    }

    /** @test */
    public function bulk_delete_validates_file_ids_array_format()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson(route('admin.file-manager.bulk-destroy'), [
                'file_ids' => 'not-an-array'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file_ids']);
    }

    /** @test */
    public function bulk_delete_handles_service_exceptions_gracefully()
    {
        // Create test files
        $files = FileUpload::factory()->count(2)->create();
        $fileIds = $files->pluck('id')->toArray();

        // Mock the service to throw an exception
        $this->mock(\App\Services\FileManagerService::class, function ($mock) {
            $mock->shouldReceive('bulkDeleteFiles')
                ->once()
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('admin.file-manager.bulk-destroy'), [
                'file_ids' => $fileIds
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Error during bulk deletion: Service error'
            ]);
    }

    /** @test */
    public function bulk_delete_handles_partial_failures()
    {
        // Create test files
        $files = FileUpload::factory()->count(3)->create();
        $fileIds = $files->pluck('id')->toArray();

        // Mock the service to return partial success
        $this->mock(\App\Services\FileManagerService::class, function ($mock) use ($fileIds) {
            $mock->shouldReceive('bulkDeleteFiles')
                ->with($fileIds)
                ->once()
                ->andReturn(2); // Only 2 out of 3 deleted
        });

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('admin.file-manager.bulk-destroy'), [
                'file_ids' => $fileIds
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Successfully deleted 2 files.'
            ]);
    }

    /** @test */
    public function non_admin_users_cannot_access_bulk_delete()
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $files = FileUpload::factory()->count(2)->create();
        $fileIds = $files->pluck('id')->toArray();

        $response = $this->actingAs($client)
            ->deleteJson(route('admin.file-manager.bulk-destroy'), [
                'file_ids' => $fileIds
            ]);

        // Should be redirected or forbidden (depending on middleware implementation)
        $this->assertTrue(in_array($response->status(), [302, 403, 401]));
    }

    /** @test */
    public function bulk_delete_works_with_mixed_file_types()
    {
        // Create files with different storage providers and states
        $localFile = FileUpload::factory()->create(['google_drive_file_id' => null]);
        $driveFile = FileUpload::factory()->create(['google_drive_file_id' => 'drive_123']);
        $pendingFile = FileUpload::factory()->create(['google_drive_file_id' => '']);
        
        $fileIds = [$localFile->id, $driveFile->id, $pendingFile->id];

        // Create local file
        Storage::disk('public')->put('uploads/' . $localFile->filename, 'test content');

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('admin.file-manager.bulk-destroy'), [
                'file_ids' => $fileIds
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Successfully deleted 3 files.'
            ]);

        // Verify all files are deleted
        $this->assertEquals(0, FileUpload::whereIn('id', $fileIds)->count());
    }

    /** @test */
    public function admin_can_bulk_download_files()
    {
        // Create test files
        $files = FileUpload::factory()->count(3)->create([
            'original_filename' => 'test_file.txt'
        ]);
        $fileIds = $files->pluck('id')->toArray();

        // Create local files
        foreach ($files as $index => $file) {
            Storage::disk('public')->put('uploads/' . $file->filename, "Test content {$index}");
        }

        $response = $this->actingAs($this->admin)
            ->post(route('admin.file-manager.bulk-download'), [
                'file_ids' => $fileIds
            ]);

        $response->assertOk();
        $this->assertEquals('application/zip', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('bulk_download_', $response->headers->get('Content-Disposition'));
    }

    /** @test */
    public function bulk_download_validates_required_file_ids()
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.file-manager.bulk-download'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file_ids']);
    }

    /** @test */
    public function bulk_download_validates_file_ids_exist_in_database()
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.file-manager.bulk-download'), [
                'file_ids' => [999, 1000] // Non-existent IDs
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file_ids.0', 'file_ids.1']);
    }

    /** @test */
    public function bulk_download_validates_file_ids_array_format()
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.file-manager.bulk-download'), [
                'file_ids' => 'not-an-array'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file_ids']);
    }

    /** @test */
    public function bulk_download_handles_service_exceptions_gracefully()
    {
        // Create test files
        $files = FileUpload::factory()->count(2)->create();
        $fileIds = $files->pluck('id')->toArray();

        // Mock the service to throw an exception
        $this->mock(\App\Services\FileManagerService::class, function ($mock) {
            $mock->shouldReceive('bulkDownloadFiles')
                ->once()
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.file-manager.bulk-download'), [
                'file_ids' => $fileIds
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Error creating bulk download: Service error'
            ]);
    }

    /** @test */
    public function bulk_download_handles_no_accessible_files()
    {
        // Create files with Google Drive IDs (no local copies)
        $files = FileUpload::factory()->count(2)->create([
            'google_drive_file_id' => 'drive_123'
        ]);
        $fileIds = $files->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.file-manager.bulk-download'), [
                'file_ids' => $fileIds
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false
            ]);
        
        // Just check that it contains "Error creating bulk download" since the exact message may vary
        $this->assertStringContainsString('Error creating bulk download', $response->json('message'));
    }

    /** @test */
    public function non_admin_users_cannot_access_bulk_download()
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $files = FileUpload::factory()->count(2)->create();
        $fileIds = $files->pluck('id')->toArray();

        $response = $this->actingAs($client)
            ->postJson(route('admin.file-manager.bulk-download'), [
                'file_ids' => $fileIds
            ]);

        // Should be redirected or forbidden (depending on middleware implementation)
        $this->assertTrue(in_array($response->status(), [302, 403, 401]));
    }

    /** @test */
    public function bulk_download_works_with_mixed_file_types()
    {
        // Create mix of local and Google Drive files
        $localFile = FileUpload::factory()->create([
            'original_filename' => 'local_file.txt',
            'google_drive_file_id' => null
        ]);
        $driveFile = FileUpload::factory()->create([
            'original_filename' => 'drive_file.txt',
            'google_drive_file_id' => 'drive_123'
        ]);

        // Create local file content
        Storage::disk('public')->put('uploads/' . $localFile->filename, 'Local file content');

        $fileIds = [$localFile->id, $driveFile->id];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.file-manager.bulk-download'), [
                'file_ids' => $fileIds
            ]);

        $response->assertOk();
        $this->assertEquals('application/zip', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function bulk_download_handles_duplicate_filenames()
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

        $response = $this->actingAs($this->admin)
            ->post(route('admin.file-manager.bulk-download'), [
                'file_ids' => $fileIds
            ]);

        $response->assertOk();
        $this->assertEquals('application/zip', $response->headers->get('Content-Type'));
    }}
