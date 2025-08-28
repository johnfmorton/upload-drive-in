<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\FileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FileManagerGridComponentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_render_file_grid_component()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $files = FileUpload::factory()->count(3)->create();
        
        $this->actingAs($admin);
        
        $response = $this->get('/admin/file-manager');
        
        $response->assertStatus(200);
        $response->assertViewHas('files');
    }

    /** @test */
    public function employee_can_render_file_grid_component()
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'username' => 'testemployee'
        ]);
        
        $files = FileUpload::factory()->count(3)->create([
            'uploaded_by_user_id' => $employee->id
        ]);
        
        $this->actingAs($employee);
        
        $response = $this->get('/employee/testemployee/file-manager');
        
        $response->assertStatus(200);
        $response->assertViewHas('files');
    }

    /** @test */
    public function file_grid_component_includes_required_elements()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $file = FileUpload::factory()->create([
            'original_filename' => 'test-file.pdf',
            'file_size' => 1024000,
            'email' => 'test@example.com'
        ]);
        
        $this->actingAs($admin);
        
        $response = $this->get('/admin/file-manager');
        
        $response->assertStatus(200);
        
        // Check for grid structure
        $response->assertSee('grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4', false);
        
        // Check for file selection
        $response->assertSee('x-model="selectedFiles"', false);
        
        // Check for action buttons
        $response->assertSee('previewFile(file)', false);
        $response->assertSee('downloadFile(file)', false);
        $response->assertSee('deleteFile(file)', false);
        
        // Check for file info display
        $response->assertSee('formatBytes(file.file_size)', false);
        $response->assertSee('formatDate(file.created_at)', false);
    }
}