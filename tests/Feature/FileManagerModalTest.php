<?php

namespace Tests\Feature;

use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileManagerModalTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['role' => \App\Enums\UserRole::ADMIN]);
    }

    public function test_file_manager_index_loads_with_modal_functionality()
    {
        // Create some test files
        $files = FileUpload::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index'));

        $response->assertStatus(200);
        
        // Check that the modal HTML is present
        $response->assertSee('showConfirmDialog');
        $response->assertSee('confirmDialogTitle');
        $response->assertSee('confirmDialogMessage');
        $response->assertSee('handleBackgroundClick');
        
        // Check that enhanced modal state properties are present
        $response->assertSee('modalDebugInfo');
        $response->assertSee('modalPreventClose');
        $response->assertSee('modalInitialized');
        
        // Check that modal recovery methods are present
        $response->assertSee('recoverModalState');
        $response->assertSee('logModalError');
    }

    public function test_delete_modal_has_proper_structure()
    {
        $file = FileUpload::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index'));

        $response->assertStatus(200);
        
        // Check modal container has proper z-index
        $response->assertSee('z-[9999]');
        
        // Check modal panel has proper z-index
        $response->assertSee('z-[10000]');
        
        // Check background click handler is properly set
        $response->assertSee('handleBackgroundClick($event)');
        
        // Check that the modal has proper ARIA attributes
        $response->assertSee('confirm-modal-title');
        $response->assertSee('role');
        $response->assertSee('aria-modal');
    }

    public function test_modal_javascript_functions_are_defined()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index'));

        $response->assertStatus(200);
        
        // Check that all enhanced modal methods are present in the JavaScript
        $response->assertSee('showConfirmation(title, message, action, type');
        $response->assertSee('confirmAction()');
        $response->assertSee('cancelConfirmation()');
        $response->assertSee('recoverModalState()');
        $response->assertSee('logModalError(error, context)');
        $response->assertSee('handleBackgroundClick(event)');
        $response->assertSee('debugModal()');
    }

    public function test_modal_timeout_and_recovery_functionality()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index'));

        $response->assertStatus(200);
        
        // Check that timeout functionality is present
        $response->assertSee('modalCloseTimeout');
        $response->assertSee('clearTimeout');
        $response->assertSee('setTimeout');
        
        // Check that auto-recovery is set to 30 seconds
        $response->assertSee('30000');
        
        // Check that recovery logging is present
        $response->assertSee('Modal auto-recovery triggered');
    }

    public function test_enhanced_modal_state_properties()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index'));

        $response->assertStatus(200);
        
        // Check that all enhanced state properties are initialized
        $response->assertSee('modalDebugInfo: null');
        $response->assertSee('modalPreventClose: false');
        $response->assertSee('modalInitialized: false');
        $response->assertSee('modalCloseTimeout: null');
    }

    public function test_modal_debug_information_structure()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index'));

        $response->assertStatus(200);
        
        // Check that debug info includes proper fields
        $response->assertSee('timestamp: Date.now()');
        $response->assertSee('title: title');
        $response->assertSee('type: type');
        $response->assertSee('actionType: typeof action');
        $response->assertSee('closeReason:');
        $response->assertSee('closedAt: Date.now()');
    }

    public function test_modal_z_index_layering()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.file-manager.index'));

        $response->assertStatus(200);
        
        // Check that modals have proper z-index layering
        // Delete confirmation modal: z-[9999]
        $response->assertSee('z-[9999]');
        
        // Preview modal should have higher z-index: z-[10002]
        $response->assertSee('z-[10002]');
        
        // Bulk delete modal should have intermediate z-index: z-[10000]
        $response->assertSee('z-[10000]');
    }
}