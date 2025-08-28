<?php

namespace Tests\Feature;

use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GlobalFileRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function global_preview_route_works_with_model_binding_for_admin()
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create a file upload
        $file = FileUpload::factory()->create([
            'company_user_id' => $admin->id,
            'uploaded_by_user_id' => $admin->id,
            'filename' => 'test-file.txt',
            'original_filename' => 'test-file.txt',
            'file_size' => 1024,
            'mime_type' => 'text/plain'
        ]);

        // Create the actual file using the filename from the database
        Storage::disk('public')->put('uploads/' . $file->filename, 'Test content');

        // Test the global preview route
        $response = $this->actingAs($admin)
            ->get("/files/{$file->id}/preview");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    }

    /** @test */
    public function global_preview_route_works_with_model_binding_for_employee()
    {
        // Create employee user
        $employee = User::factory()->create(['role' => 'employee']);
        
        // Create a file upload accessible by the employee
        $file = FileUpload::factory()->create([
            'company_user_id' => $employee->id,
            'uploaded_by_user_id' => $employee->id,
            'filename' => 'employee-file.txt',
            'original_filename' => 'employee-file.txt',
            'file_size' => 2048,
            'mime_type' => 'text/plain'
        ]);

        // Create the actual file using the filename from the database
        Storage::disk('public')->put('uploads/' . $file->filename, 'Employee test content');

        // Test the global preview route
        $response = $this->actingAs($employee)
            ->get("/files/{$file->id}/preview");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    }

    /** @test */
    public function global_download_route_works_with_model_binding_for_admin()
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create a file upload
        $file = FileUpload::factory()->create([
            'company_user_id' => $admin->id,
            'uploaded_by_user_id' => $admin->id,
            'filename' => 'download-test.txt',
            'original_filename' => 'download-test.txt',
            'file_size' => 1024,
            'mime_type' => 'text/plain'
        ]);

        // Create the actual file using the filename from the database
        Storage::disk('public')->put('uploads/' . $file->filename, 'Download test content');

        // Test the global download route
        $response = $this->actingAs($admin)
            ->get("/files/{$file->id}/download");

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=' . $file->original_filename);
    }

    /** @test */
    public function global_download_route_works_with_model_binding_for_employee()
    {
        // Create employee user
        $employee = User::factory()->create(['role' => 'employee']);
        
        // Create a file upload accessible by the employee
        $file = FileUpload::factory()->create([
            'company_user_id' => $employee->id,
            'uploaded_by_user_id' => $employee->id,
            'filename' => 'employee-download.txt',
            'original_filename' => 'employee-download.txt',
            'file_size' => 2048,
            'mime_type' => 'text/plain'
        ]);

        // Create the actual file using the filename from the database
        Storage::disk('public')->put('uploads/' . $file->filename, 'Employee download content');

        // Test the global download route
        $response = $this->actingAs($employee)
            ->get("/files/{$file->id}/download");

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=' . $file->original_filename);
    }

    /** @test */
    public function global_routes_return_404_for_nonexistent_file()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Test preview route with non-existent file
        $response = $this->actingAs($admin)
            ->get('/files/99999/preview');
        $response->assertStatus(404);

        // Test download route with non-existent file
        $response = $this->actingAs($admin)
            ->get('/files/99999/download');
        $response->assertStatus(404);
    }

    /** @test */
    public function global_routes_deny_access_to_unauthorized_files()
    {
        // Create two users
        $user1 = User::factory()->create(['role' => 'employee']);
        $user2 = User::factory()->create(['role' => 'employee']);
        
        // Create a file owned by user1
        $file = FileUpload::factory()->create([
            'company_user_id' => $user1->id,
            'uploaded_by_user_id' => $user1->id,
            'filename' => 'private-file.txt',
            'original_filename' => 'private-file.txt',
            'file_size' => 1024,
            'mime_type' => 'text/plain'
        ]);

        // Create the actual file using the filename from the database
        Storage::disk('public')->put('uploads/' . $file->filename, 'Private content');

        // Test that user2 cannot access user1's file
        $response = $this->actingAs($user2)
            ->get("/files/{$file->id}/preview");
        $response->assertStatus(403);

        $response = $this->actingAs($user2)
            ->get("/files/{$file->id}/download");
        $response->assertStatus(403);
    }
}