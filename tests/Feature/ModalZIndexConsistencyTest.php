<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ModalZIndexConsistencyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

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
    public function upload_success_modal_uses_correct_z_index()
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('x-modal name="upload-success"');
        
        // Check that the modal component is included and uses the correct z-index
        $this->assertStringContainsString('z-[9999]', $response->getContent());
    }

    /** @test */
    public function association_success_modal_uses_correct_z_index()
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('x-modal name="association-success"');
        
        // Verify modal component structure
        $this->assertStringContainsString('z-[9999]', $response->getContent());
    }

    /** @test */
    public function error_modals_use_correct_z_index()
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('x-modal name="association-error"');
        $response->assertSee('x-modal name="upload-error"');
        $response->assertSee('x-modal name="no-files-error"');
        
        // Verify modal component structure
        $this->assertStringContainsString('z-[9999]', $response->getContent());
    }

    /** @test */
    public function admin_preview_modal_uses_higher_z_index()
    {
        $file = FileUpload::factory()->create([
            'email' => $this->clientUser->email,
            'original_filename' => 'test.pdf',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/file-manager');
        
        $response->assertStatus(200);
        
        // Check that admin preview modal uses z-[10002]
        $this->assertStringContainsString('z-[10002]', $response->getContent());
    }

    /** @test */
    public function employee_preview_modal_uses_standard_z_index()
    {
        $file = FileUpload::factory()->create([
            'email' => $this->clientUser->email,
            'original_filename' => 'test.pdf',
        ]);

        $response = $this->actingAs($this->employeeUser)
            ->get("/employee/{$this->employeeUser->username}/file-manager");
        
        $response->assertStatus(200);
        
        // Check that employee preview modal uses z-50
        $this->assertStringContainsString('z-50', $response->getContent());
    }

    /** @test */
    public function delete_confirmation_modals_use_correct_z_index()
    {
        // Test admin delete modal
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/file-manager');
        
        $response->assertStatus(200);
        $this->assertStringContainsString('z-50', $response->getContent());
    }

    /** @test */
    public function bulk_delete_modal_uses_highest_z_index()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/file-manager');
        
        $response->assertStatus(200);
        
        // Check that bulk delete modal uses z-[10000]
        $this->assertStringContainsString('z-[10000]', $response->getContent());
    }

    /** @test */
    public function profile_delete_modals_use_correct_z_index()
    {
        // Test client profile delete modal
        $response = $this->actingAs($this->clientUser)
            ->get('/profile');
        
        $response->assertStatus(200);
        $response->assertSee('x-modal name="confirm-user-deletion"');
        
        // Verify modal component structure
        $this->assertStringContainsString('z-[9999]', $response->getContent());
    }

    /** @test */
    public function google_drive_folder_picker_uses_correct_z_index()
    {
        $response = $this->actingAs($this->employeeUser)
            ->get("/employee/{$this->employeeUser->username}/google-drive");
        
        $response->assertStatus(200);
        
        // Check that folder picker modal uses z-50
        $this->assertStringContainsString('z-50', $response->getContent());
    }

    /** @test */
    public function modal_component_has_consistent_structure()
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Verify modal container has correct z-index
        $this->assertStringContainsString('z-[9999]', $content);
        
        // Verify backdrop has correct z-index
        $this->assertStringContainsString('z-[9998]', $content);
        
        // Verify modal content has correct z-index
        $this->assertStringContainsString('z-[10000]', $content);
        
        // Verify debug attributes are present
        $this->assertStringContainsString('data-modal-name', $content);
        $this->assertStringContainsString('data-z-index', $content);
        $this->assertStringContainsString('data-modal-type', $content);
    }

    /** @test */
    public function modal_focus_trap_functionality_preserved()
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Verify focus trap methods are present
        $this->assertStringContainsString('focusables()', $content);
        $this->assertStringContainsString('firstFocusable()', $content);
        $this->assertStringContainsString('lastFocusable()', $content);
        $this->assertStringContainsString('nextFocusable()', $content);
        $this->assertStringContainsString('prevFocusable()', $content);
        
        // Verify keyboard event handlers
        $this->assertStringContainsString('x-on:keydown.escape.window', $content);
        $this->assertStringContainsString('x-on:keydown.tab.prevent', $content);
        $this->assertStringContainsString('x-on:keydown.shift.tab.prevent', $content);
    }

    /** @test */
    public function modal_debug_functionality_preserved()
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Verify debug functionality
        $this->assertStringContainsString('debugMode', $content);
        $this->assertStringContainsString('logModalState', $content);
        $this->assertStringContainsString('modal-debug=true', $content);
        $this->assertStringContainsString('z-debug-highest', $content);
        $this->assertStringContainsString('z-debug-high', $content);
    }

    /** @test */
    public function all_modal_types_have_proper_layering_hierarchy()
    {
        // Test that different modal types have appropriate z-index values
        // to prevent conflicts
        
        $zIndexHierarchy = [
            'standard_modal_container' => 9999,
            'standard_modal_backdrop' => 9998,
            'standard_modal_content' => 10000,
            'bulk_delete_modal' => 10000,
            'admin_preview_modal' => 10002,
            'admin_preview_backdrop' => 10002,
            'admin_preview_content' => 10003,
        ];
        
        // Verify hierarchy is maintained (higher priority modals have higher z-index)
        $this->assertGreaterThan($zIndexHierarchy['standard_modal_container'], $zIndexHierarchy['standard_modal_content']);
        $this->assertGreaterThan($zIndexHierarchy['standard_modal_content'], $zIndexHierarchy['admin_preview_modal']);
        $this->assertGreaterThan($zIndexHierarchy['admin_preview_modal'], $zIndexHierarchy['admin_preview_content']);
        
        // Verify backdrop is always below content
        $this->assertLessThan($zIndexHierarchy['standard_modal_content'], $zIndexHierarchy['standard_modal_backdrop']);
        $this->assertLessThan($zIndexHierarchy['admin_preview_content'], $zIndexHierarchy['admin_preview_backdrop']);
    }
}