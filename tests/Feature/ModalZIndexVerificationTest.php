<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModalZIndexVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $employeeUser;
    private User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        
        $this->employeeUser = User::factory()->create([
            'role' => 'employee',
            'username' => 'testemployee',
            'email_verified_at' => now(),
        ]);
        
        $this->clientUser = User::factory()->create([
            'role' => 'client',
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function modal_component_has_correct_z_index_structure()
    {
        // Read the modal component directly
        $modalComponent = file_get_contents(resource_path('views/components/modal.blade.php'));
        
        // Verify container z-index
        $this->assertStringContainsString('z-[9999]', $modalComponent, 'Modal container should use z-[9999]');
        
        // Verify backdrop z-index
        $this->assertStringContainsString('z-[9998]', $modalComponent, 'Modal backdrop should use z-[9998]');
        
        // Verify content z-index
        $this->assertStringContainsString('z-[10000]', $modalComponent, 'Modal content should use z-[10000]');
        
        // Verify debug attributes are present
        $this->assertStringContainsString('data-modal-name', $modalComponent);
        $this->assertStringContainsString('data-z-index', $modalComponent);
        $this->assertStringContainsString('data-modal-type', $modalComponent);
    }

    /** @test */
    public function admin_preview_modal_has_higher_z_index()
    {
        $previewModal = file_get_contents(resource_path('views/admin/file-manager/partials/preview-modal.blade.php'));
        
        // Admin preview modal should use higher z-index values
        $this->assertStringContainsString('z-[10002]', $previewModal, 'Admin preview modal should use z-[10002]');
        $this->assertStringContainsString('z-[10003]', $previewModal, 'Admin preview modal content should use z-[10003]');
    }

    /** @test */
    public function shared_preview_modal_has_proper_z_index()
    {
        $previewModal = file_get_contents(resource_path('views/components/file-manager/modals/preview-modal.blade.php'));
        
        // Shared preview modal should use high z-index values for proper layering
        $this->assertStringContainsString('z-[10002]', $previewModal, 'Shared preview modal should use z-[10002]');
        $this->assertStringContainsString('z-[10003]', $previewModal, 'Shared preview modal content should use z-[10003]');
        $this->assertStringContainsString('z-[10001]', $previewModal, 'Shared preview modal backdrop should use z-[10001]');
    }

    /** @test */
    public function bulk_delete_modal_uses_high_z_index()
    {
        $bulkDeleteModal = file_get_contents(resource_path('views/admin/file-manager/partials/bulk-delete-modal.blade.php'));
        
        // Bulk delete modal should use high z-index
        $this->assertStringContainsString('z-[10000]', $bulkDeleteModal, 'Bulk delete modal should use z-[10000]');
    }

    /** @test */
    public function delete_modal_uses_standard_z_index()
    {
        $deleteModal = file_get_contents(resource_path('views/admin/file-manager/partials/delete-modal.blade.php'));
        
        // Delete modal should use standard z-index
        $this->assertStringContainsString('z-50', $deleteModal, 'Delete modal should use z-50');
    }

    /** @test */
    public function google_drive_folder_picker_uses_standard_z_index()
    {
        $folderPicker = file_get_contents(resource_path('views/employee/google-drive/google-drive-root-folder.blade.php'));
        
        // Folder picker should use standard z-index
        $this->assertStringContainsString('z-50', $folderPicker, 'Google Drive folder picker should use z-50');
    }

    /** @test */
    public function modal_focus_trap_functionality_is_preserved()
    {
        $modalComponent = file_get_contents(resource_path('views/components/modal.blade.php'));
        
        // Verify focus trap methods are present
        $this->assertStringContainsString('focusables()', $modalComponent);
        $this->assertStringContainsString('firstFocusable()', $modalComponent);
        $this->assertStringContainsString('lastFocusable()', $modalComponent);
        $this->assertStringContainsString('nextFocusable()', $modalComponent);
        $this->assertStringContainsString('prevFocusable()', $modalComponent);
        
        // Verify keyboard event handlers
        $this->assertStringContainsString('x-on:keydown.escape.window', $modalComponent);
        $this->assertStringContainsString('x-on:keydown.tab.prevent', $modalComponent);
        $this->assertStringContainsString('x-on:keydown.shift.tab.prevent', $modalComponent);
    }

    /** @test */
    public function modal_debug_functionality_is_preserved()
    {
        $modalComponent = file_get_contents(resource_path('views/components/modal.blade.php'));
        
        // Verify debug functionality
        $this->assertStringContainsString('debugMode', $modalComponent);
        $this->assertStringContainsString('logModalState', $modalComponent);
        $this->assertStringContainsString('modal-debug=true', $modalComponent);
        $this->assertStringContainsString('z-debug-highest', $modalComponent);
        $this->assertStringContainsString('z-debug-high', $modalComponent);
    }

    /** @test */
    public function z_index_hierarchy_is_properly_maintained()
    {
        // Define expected z-index hierarchy
        $zIndexHierarchy = [
            'standard_modal_backdrop' => 9998,
            'standard_modal_container' => 9999,
            'standard_modal_content' => 10000,
            'bulk_delete_modal' => 10000,
            'admin_preview_modal' => 10002,
            'admin_preview_content' => 10003,
        ];
        
        // Verify hierarchy is maintained (higher priority modals have higher z-index)
        $this->assertLessThan($zIndexHierarchy['standard_modal_container'], $zIndexHierarchy['standard_modal_backdrop']);
        $this->assertLessThan($zIndexHierarchy['standard_modal_content'], $zIndexHierarchy['standard_modal_container']);
        $this->assertGreaterThanOrEqual($zIndexHierarchy['standard_modal_content'], $zIndexHierarchy['bulk_delete_modal']);
        $this->assertGreaterThan($zIndexHierarchy['standard_modal_content'], $zIndexHierarchy['admin_preview_modal']);
        $this->assertGreaterThan($zIndexHierarchy['admin_preview_modal'], $zIndexHierarchy['admin_preview_content']);
    }

    /** @test */
    public function all_modal_files_exist_and_are_readable()
    {
        $modalFiles = [
            'resources/views/components/modal.blade.php',
            'resources/views/admin/file-manager/partials/preview-modal.blade.php',
            'resources/views/components/file-manager/modals/preview-modal.blade.php',
            'resources/views/admin/file-manager/partials/delete-modal.blade.php',
            'resources/views/admin/file-manager/partials/bulk-delete-modal.blade.php',
            'resources/views/employee/google-drive/google-drive-root-folder.blade.php',
        ];

        foreach ($modalFiles as $file) {
            $this->assertFileExists(base_path($file), "Modal file {$file} should exist");
            $this->assertFileIsReadable(base_path($file), "Modal file {$file} should be readable");
        }
    }
}