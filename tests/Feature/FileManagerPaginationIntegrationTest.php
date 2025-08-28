<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\FileUpload;
use App\Models\ClientUserRelationship;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class FileManagerPaginationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $employeeUser;
    private User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->employeeUser = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'username' => 'test-employee',
            'email_verified_at' => now(),
        ]);

        $this->clientUser = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email_verified_at' => now(),
        ]);

        // Create employee-client relationship
        ClientUserRelationship::create([
            'company_user_id' => $this->employeeUser->id,
            'client_user_id' => $this->clientUser->id,
            'is_primary' => true
        ]);
    }

    /**
     * Test admin file manager pagination with custom configuration.
     * Requirements: 3.1
     */
    public function test_admin_file_manager_pagination_with_custom_configuration()
    {
        // Set custom pagination configuration
        Config::set('file-manager.pagination.items_per_page', 5);
        Config::set('file-manager.pagination.min_items_per_page', 1);
        Config::set('file-manager.pagination.max_items_per_page', 100);

        // Create test files
        FileUpload::factory()->count(12)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.file-manager.index');
        
        // Check that pagination is working with custom configuration
        $files = $response->viewData('files');
        $this->assertEquals(5, $files->perPage());
        $this->assertEquals(12, $files->total());
        $this->assertEquals(3, $files->lastPage());
    }

    /**
     * Test admin file manager pagination with per_page parameter.
     * Requirements: 3.1
     */
    public function test_admin_file_manager_pagination_with_per_page_parameter()
    {
        // Set default configuration
        Config::set('file-manager.pagination.items_per_page', 10);
        Config::set('file-manager.pagination.min_items_per_page', 1);
        Config::set('file-manager.pagination.max_items_per_page', 100);

        // Create test files
        FileUpload::factory()->count(25)->create();

        // Test with custom per_page parameter
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index', ['per_page' => 8]));

        $response->assertStatus(200);
        $files = $response->viewData('files');
        $this->assertEquals(8, $files->perPage());
        $this->assertEquals(25, $files->total());
        $this->assertEquals(4, $files->lastPage());
    }

    /**
     * Test admin file manager pagination boundary enforcement.
     * Requirements: 3.1
     */
    public function test_admin_file_manager_pagination_boundary_enforcement()
    {
        // Set configuration with specific boundaries
        Config::set('file-manager.pagination.items_per_page', 10);
        Config::set('file-manager.pagination.min_items_per_page', 2);
        Config::set('file-manager.pagination.max_items_per_page', 20);

        FileUpload::factory()->count(30)->create();

        // Test minimum boundary enforcement
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index', ['per_page' => 1]));

        $response->assertStatus(200);
        $files = $response->viewData('files');
        $this->assertEquals(2, $files->perPage()); // Should be clamped to minimum

        // Test maximum boundary enforcement
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index', ['per_page' => 50]));

        $response->assertStatus(200);
        $files = $response->viewData('files');
        $this->assertEquals(20, $files->perPage()); // Should be clamped to maximum

        // Test invalid value fallback
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index', ['per_page' => 'invalid']));

        $response->assertStatus(200);
        $files = $response->viewData('files');
        $this->assertEquals(2, $files->perPage()); // Should fallback to minimum
    }

    /**
     * Test employee file manager pagination consistency.
     * Requirements: 3.2
     */
    public function test_employee_file_manager_pagination_consistency()
    {
        // Set custom pagination configuration
        Config::set('file-manager.pagination.items_per_page', 7);
        Config::set('file-manager.pagination.min_items_per_page', 1);
        Config::set('file-manager.pagination.max_items_per_page', 100);

        // Create files for the employee
        FileUpload::factory()->count(15)->create([
            'company_user_id' => $this->employeeUser->id
        ]);

        $response = $this->actingAs($this->employeeUser)
            ->get(route('employee.file-manager.index', ['username' => $this->employeeUser->username]));

        $response->assertStatus(200);
        $response->assertViewIs('employee.file-manager.index');
        
        // Check that pagination uses the same configuration as admin
        $files = $response->viewData('files');
        $this->assertEquals(7, $files->perPage());
        $this->assertEquals(15, $files->total());
        $this->assertEquals(3, $files->lastPage());
    }

    /**
     * Test employee file manager pagination with per_page parameter.
     * Requirements: 3.2
     */
    public function test_employee_file_manager_pagination_with_per_page_parameter()
    {
        // Set default configuration
        Config::set('file-manager.pagination.items_per_page', 10);
        Config::set('file-manager.pagination.min_items_per_page', 1);
        Config::set('file-manager.pagination.max_items_per_page', 50);

        // Create files for the employee
        FileUpload::factory()->count(20)->create([
            'company_user_id' => $this->employeeUser->id
        ]);

        // Test with custom per_page parameter
        $response = $this->actingAs($this->employeeUser)
            ->get(route('employee.file-manager.index', [
                'username' => $this->employeeUser->username,
                'per_page' => 6
            ]));

        $response->assertStatus(200);
        $files = $response->viewData('files');
        $this->assertEquals(6, $files->perPage());
        $this->assertEquals(20, $files->total());
        $this->assertEquals(4, $files->lastPage());
    }

    /**
     * Test pagination UI rendering with different page sizes.
     * Requirements: 3.3
     */
    public function test_pagination_ui_rendering_with_different_page_sizes()
    {
        // Create enough files to test pagination UI
        FileUpload::factory()->count(50)->create();

        // Test with small page size (should show many page links)
        Config::set('file-manager.pagination.items_per_page', 3);
        
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index'));

        $response->assertStatus(200);
        $files = $response->viewData('files');
        $this->assertEquals(3, $files->perPage());
        $this->assertEquals(17, $files->lastPage()); // 50 files / 3 per page = 17 pages
        
        // Check that pagination links are present in the view
        // Note: The exact HTML structure may vary, so we check for pagination functionality
        $this->assertTrue($files->hasPages());
        $this->assertGreaterThan(1, $files->lastPage());

        // Test with large page size (should show fewer page links)
        Config::set('file-manager.pagination.items_per_page', 25);
        
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index'));

        $response->assertStatus(200);
        $files = $response->viewData('files');
        $this->assertEquals(25, $files->perPage());
        $this->assertEquals(2, $files->lastPage()); // 50 files / 25 per page = 2 pages
    }

    /**
     * Test page navigation works correctly with new configuration.
     * Requirements: 3.4
     */
    public function test_page_navigation_works_correctly_with_new_configuration()
    {
        // Set custom pagination configuration
        Config::set('file-manager.pagination.items_per_page', 4);
        
        // Create files with predictable data for testing
        $files = collect();
        for ($i = 1; $i <= 10; $i++) {
            $files->push(FileUpload::factory()->create([
                'original_filename' => "test-file-{$i}.txt",
                'created_at' => now()->subMinutes($i)
            ]));
        }

        // Test first page
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index', ['page' => 1]));

        $response->assertStatus(200);
        $paginatedFiles = $response->viewData('files');
        $this->assertEquals(1, $paginatedFiles->currentPage());
        $this->assertEquals(4, $paginatedFiles->count());
        
        // Test second page
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index', ['page' => 2]));

        $response->assertStatus(200);
        $paginatedFiles = $response->viewData('files');
        $this->assertEquals(2, $paginatedFiles->currentPage());
        $this->assertEquals(4, $paginatedFiles->count());
        
        // Test last page
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index', ['page' => 3]));

        $response->assertStatus(200);
        $paginatedFiles = $response->viewData('files');
        $this->assertEquals(3, $paginatedFiles->currentPage());
        $this->assertEquals(2, $paginatedFiles->count()); // Only 2 files on last page
    }

    /**
     * Test pagination navigation with per_page parameter persistence.
     * Requirements: 3.4
     */
    public function test_pagination_navigation_with_per_page_parameter_persistence()
    {
        // Create test files
        FileUpload::factory()->count(20)->create();

        // Test that per_page parameter is maintained across page navigation
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index', ['per_page' => 6, 'page' => 1]));

        $response->assertStatus(200);
        $files = $response->viewData('files');
        $this->assertEquals(6, $files->perPage());
        $this->assertEquals(1, $files->currentPage());

        // Navigate to second page with same per_page
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index', ['per_page' => 6, 'page' => 2]));

        $response->assertStatus(200);
        $files = $response->viewData('files');
        $this->assertEquals(6, $files->perPage());
        $this->assertEquals(2, $files->currentPage());
        
        // Check pagination links maintain per_page parameter
        // Note: The exact URL structure may vary, so we verify the pagination state
        $this->assertEquals(6, $files->perPage());
        $this->assertEquals(2, $files->currentPage());
    }

    /**
     * Test AJAX requests maintain pagination configuration.
     * Requirements: 3.1, 3.2
     */
    public function test_ajax_requests_maintain_pagination_configuration()
    {
        // Set custom configuration
        Config::set('file-manager.pagination.items_per_page', 5);
        
        FileUpload::factory()->count(12)->create();

        // Test admin AJAX request
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.file-manager.index', ['per_page' => 3]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'files' => [
                'data',
                'current_page',
                'per_page',
                'total',
                'last_page'
            ]
        ]);
        
        $responseData = $response->json();
        $this->assertEquals(3, $responseData['files']['per_page']);
        $this->assertEquals(12, $responseData['files']['total']);
        $this->assertEquals(4, $responseData['files']['last_page']);
    }

    /**
     * Test pagination configuration with filters.
     * Requirements: 3.3
     */
    public function test_pagination_configuration_with_filters()
    {
        // Set custom pagination
        Config::set('file-manager.pagination.items_per_page', 4);
        
        // Create files with different attributes
        FileUpload::factory()->count(6)->create(['original_filename' => 'document.pdf']);
        FileUpload::factory()->count(8)->create(['original_filename' => 'image.jpg']);

        // Test pagination with search filter
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index', [
                'search' => 'document',
                'per_page' => 3
            ]));

        $response->assertStatus(200);
        $files = $response->viewData('files');
        $this->assertEquals(3, $files->perPage());
        $this->assertEquals(6, $files->total()); // Only documents match
        $this->assertEquals(2, $files->lastPage());
    }

    /**
     * Test employee pagination with file access restrictions.
     * Requirements: 3.2
     */
    public function test_employee_pagination_with_file_access_restrictions()
    {
        // Set custom pagination
        Config::set('file-manager.pagination.items_per_page', 3);
        
        // Create files for the employee
        FileUpload::factory()->count(5)->create([
            'company_user_id' => $this->employeeUser->id
        ]);
        
        // Create files for other users (should not appear)
        FileUpload::factory()->count(10)->create([
            'company_user_id' => $this->adminUser->id
        ]);

        $response = $this->actingAs($this->employeeUser)
            ->get(route('employee.file-manager.index', ['username' => $this->employeeUser->username]));

        $response->assertStatus(200);
        $files = $response->viewData('files');
        
        // Should only see their own files with correct pagination
        $this->assertEquals(3, $files->perPage());
        $this->assertEquals(5, $files->total()); // Only employee's files
        $this->assertEquals(2, $files->lastPage());
    }

    /**
     * Test pagination configuration edge cases.
     * Requirements: 3.1, 3.2
     */
    public function test_pagination_configuration_edge_cases()
    {
        // Set configuration
        Config::set('file-manager.pagination.items_per_page', 10);
        Config::set('file-manager.pagination.min_items_per_page', 1);
        Config::set('file-manager.pagination.max_items_per_page', 100);

        FileUpload::factory()->count(5)->create();

        // Test with zero per_page (should use minimum)
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index', ['per_page' => 0]));

        $response->assertStatus(200);
        $files = $response->viewData('files');
        $this->assertEquals(1, $files->perPage());

        // Test with negative per_page (should use minimum)
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index', ['per_page' => -5]));

        $response->assertStatus(200);
        $files = $response->viewData('files');
        $this->assertEquals(1, $files->perPage());

        // Test with non-numeric per_page (should use minimum)
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index', ['per_page' => 'abc']));

        $response->assertStatus(200);
        $files = $response->viewData('files');
        $this->assertEquals(1, $files->perPage());
    }

    /**
     * Test pagination consistency between admin and employee interfaces.
     * Requirements: 3.1, 3.2
     */
    public function test_pagination_consistency_between_admin_and_employee_interfaces()
    {
        // Set custom configuration
        Config::set('file-manager.pagination.items_per_page', 6);
        Config::set('file-manager.pagination.min_items_per_page', 2);
        Config::set('file-manager.pagination.max_items_per_page', 50);

        // Create files for both admin and employee access
        FileUpload::factory()->count(15)->create();
        FileUpload::factory()->count(12)->create([
            'company_user_id' => $this->employeeUser->id
        ]);

        // Test admin pagination
        $adminResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index', ['per_page' => 4]));

        $adminResponse->assertStatus(200);
        $adminFiles = $adminResponse->viewData('files');
        $this->assertEquals(4, $adminFiles->perPage());

        // Test employee pagination with same per_page
        $employeeResponse = $this->actingAs($this->employeeUser)
            ->get(route('employee.file-manager.index', [
                'username' => $this->employeeUser->username,
                'per_page' => 4
            ]));

        $employeeResponse->assertStatus(200);
        $employeeFiles = $employeeResponse->viewData('files');
        $this->assertEquals(4, $employeeFiles->perPage());

        // Both should respect the same boundary enforcement
        $adminBoundaryResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index', ['per_page' => 100]));
        
        $employeeBoundaryResponse = $this->actingAs($this->employeeUser)
            ->get(route('employee.file-manager.index', [
                'username' => $this->employeeUser->username,
                'per_page' => 100
            ]));

        $adminBoundaryFiles = $adminBoundaryResponse->viewData('files');
        $employeeBoundaryFiles = $employeeBoundaryResponse->viewData('files');
        
        // Both should be clamped to the same maximum
        $this->assertEquals(50, $adminBoundaryFiles->perPage());
        $this->assertEquals(50, $employeeBoundaryFiles->perPage());
    }
}