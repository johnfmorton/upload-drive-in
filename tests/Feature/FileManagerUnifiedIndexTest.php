<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\FileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

class FileManagerUnifiedIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->employeeUser = User::factory()->create([
            'role' => 'employee',
            'username' => 'test-employee',
            'email_verified_at' => now(),
        ]);
    }

    public function test_unified_component_renders_for_admin()
    {
        // Create some test files
        FileUpload::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index'));

        $response->assertStatus(200);
        
        // Check that the unified component structure is present
        $response->assertSee('file-manager');
        $response->assertSee('data-lazy-container');
        $response->assertSee('x-data="fileManager(', false);
    }

    public function test_unified_component_renders_for_employee()
    {
        // Create some test files for the employee
        FileUpload::factory()->count(2)->create([
            'uploaded_by_user_id' => $this->employeeUser->id
        ]);

        $response = $this->actingAs($this->employeeUser)
            ->get(route('employee.file-manager.index', ['username' => $this->employeeUser->username]));

        $response->assertStatus(200);
        
        // Check that the unified component structure is present
        $response->assertSee('file-manager');
        $response->assertSee('data-lazy-container');
        $response->assertSee('x-data="fileManager(', false);
    }

    public function test_component_handles_empty_file_list()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index'));

        $response->assertStatus(200);
        $response->assertSee('No files uploaded yet');
        $response->assertSee('Files uploaded through your public upload form will appear here');
    }

    public function test_component_includes_required_sections()
    {
        FileUpload::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index'));

        $response->assertStatus(200);
        
        // Check for key sections that should be present
        $response->assertSee('Header Section', false);
        $response->assertSee('Toolbar', false);
        $response->assertSee('Advanced Filters Panel', false);
        $response->assertSee('File Content Area', false);
    }

    public function test_component_passes_statistics_correctly()
    {
        // Create files with different statuses
        FileUpload::factory()->create(['google_drive_file_id' => 'uploaded_file_123']);
        FileUpload::factory()->create(['google_drive_file_id' => null]); // pending

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index'));

        $response->assertStatus(200);
        
        // Check that statistics are included in the Alpine.js data
        $response->assertSee('"total":2', false);
        $response->assertSee('"pending":1', false);
    }
}