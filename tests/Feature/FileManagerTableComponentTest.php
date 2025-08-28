<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\FileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileManagerTableComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_admin_can_view_file_table_component()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create some test files
        FileUpload::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/admin/file-manager');

        $response->assertStatus(200);
        $response->assertSee('x-show="viewMode === \'table\'"', false);
        $response->assertSee('min-w-full divide-y divide-gray-200');
        $response->assertSee('sticky left-0 bg-gray-50 z-10');
        $response->assertSee('sticky right-0 bg-gray-50 z-10');
    }

    public function test_employee_can_view_file_table_component()
    {
        $employee = User::factory()->create(['role' => 'employee', 'username' => 'testuser']);
        
        // Create some test files for this employee
        FileUpload::factory()->count(2)->create(['uploaded_by_user_id' => $employee->id]);

        $response = $this->actingAs($employee)->get('/employee/testuser/file-manager');

        $response->assertStatus(200);
        $response->assertSee('x-show="viewMode === \'table\'"', false);
        $response->assertSee('min-w-full divide-y divide-gray-200');
    }

    public function test_table_component_includes_sortable_headers()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/file-manager');

        $response->assertStatus(200);
        $response->assertSee('x-on:click="column.sortable ? sortBy(column.key) : null"', false);
        $response->assertSee('sortDirection === \'asc\' ? \'▲\' : \'▼\'', false);
    }

    public function test_table_component_includes_column_resizing()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/file-manager');

        $response->assertStatus(200);
        $response->assertSee('cursor-col-resize');
        $response->assertSee('x-on:mousedown="startColumnResize($event, column.key)"', false);
    }

    public function test_table_component_includes_action_buttons()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/file-manager');

        $response->assertStatus(200);
        $response->assertSee('x-on:click="previewFile(file)"', false);
        $response->assertSee('x-on:click="downloadFile(file)"', false);
        $response->assertSee('x-on:click="deleteFile(file)"', false);
    }

    public function test_table_component_includes_selection_functionality()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/file-manager');

        $response->assertStatus(200);
        $response->assertSee('x-model="selectAll"', false);
        $response->assertSee('x-model="selectedFiles"', false);
        $response->assertSee(':value="file.id"', false);
    }

    public function test_table_component_includes_empty_state()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/file-manager');

        $response->assertStatus(200);
        // The current admin file manager has its own empty state implementation
        // This test will pass once the component is integrated
        $response->assertSee('text-center py-12');
    }

    public function test_table_component_responsive_design()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/file-manager');

        $response->assertStatus(200);
        $response->assertSee('overflow-x-auto');
        $response->assertSee('overflow-hidden');
    }
}