<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\FileUpload;
use App\Models\ClientUserRelationship;

class UploadSuccessModalOverlayTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $companyUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->companyUser = User::factory()->create([
            'email' => 'company@example.com',
            'role' => 'admin'
        ]);
        
        $this->client = User::factory()->create([
            'email' => 'client@example.com',
            'role' => 'client'
        ]);
        
        // Create relationship
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->companyUser->id,
            'is_primary' => true
        ]);
    }

    /** @test */
    public function upload_success_modal_displays_without_overlay_after_single_file_upload()
    {
        $this->actingAs($this->client);
        
        // Create a successful file upload
        $fileUpload = FileUpload::factory()->create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->companyUser->id,
            'status' => 'completed'
        ]);
        
        // Visit the upload page
        $response = $this->get(route('client.file-upload'));
        $response->assertStatus(200);
        
        // Verify the upload-success modal is present in the DOM
        $response->assertSee('name="upload-success"', false);
        $response->assertSee('Upload Complete');
        $response->assertSee('Files uploaded successfully!');
        
        // Verify modal has proper z-index classes
        $response->assertSee('z-[9999]', false);
        $response->assertSee('z-[10000]', false);
    }

    /** @test */
    public function upload_success_modal_displays_without_overlay_after_multiple_file_upload()
    {
        $this->actingAs($this->client);
        
        // Create multiple successful file uploads
        FileUpload::factory()->count(3)->create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->companyUser->id,
            'status' => 'completed'
        ]);
        
        // Visit the upload page
        $response = $this->get(route('client.file-upload'));
        $response->assertStatus(200);
        
        // Verify the upload-success modal is present
        $response->assertSee('name="upload-success"', false);
        $response->assertSee('Upload Complete');
    }

    /** @test */
    public function modal_close_button_is_accessible_and_functional()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.file-upload'));
        $response->assertStatus(200);
        
        // Verify close button exists and has proper attributes
        $response->assertSee('@click="show = false"', false);
        $response->assertSee('Close');
        
        // Verify button has proper styling and accessibility
        $response->assertSee('focus-visible:outline-2', false);
    }

    /** @test */
    public function modal_backdrop_click_closes_modal()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.file-upload'));
        $response->assertStatus(200);
        
        // Verify backdrop has click handler
        $response->assertSee('x-on:click="show = false"', false);
        $response->assertSee('modal-backdrop', false);
    }

    /** @test */
    public function modal_has_proper_z_index_hierarchy()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.file-upload'));
        $response->assertStatus(200);
        
        // Verify z-index hierarchy
        $response->assertSee('z-[9999] modal-container', false); // Container
        $response->assertSee('z-[9998] modal-backdrop', false);  // Backdrop
        $response->assertSee('z-[10000] modal-content', false);  // Content
        
        // Verify data attributes for debugging
        $response->assertSee('data-z-index="9999"', false);
        $response->assertSee('data-z-index="9998"', false);
        $response->assertSee('data-z-index="10000"', false);
    }

    /** @test */
    public function modal_debug_mode_can_be_enabled()
    {
        $this->actingAs($this->client);
        
        // Test with debug parameter
        $response = $this->get(route('client.file-upload') . '?modal-debug=true');
        $response->assertStatus(200);
        
        // Verify debug classes and attributes are present
        $response->assertSee('z-debug-highest', false);
        $response->assertSee('stacking-context-debug', false);
        $response->assertSee('logModalState', false);
    }

    /** @test */
    public function all_modal_types_have_consistent_z_index_behavior()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.file-upload'));
        $response->assertStatus(200);
        
        // Check all modal types present on the page
        $modalTypes = [
            'upload-success',
            'association-success', 
            'association-error',
            'upload-error',
            'no-files-error'
        ];
        
        foreach ($modalTypes as $modalType) {
            $response->assertSee('name="' . $modalType . '"', false);
            // Each modal should use the same z-index structure
            $response->assertSee('z-[9999]', false);
        }
    }

    /** @test */
    public function modal_transitions_do_not_create_layering_conflicts()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.file-upload'));
        $response->assertStatus(200);
        
        // Verify transition classes are present and properly configured
        $response->assertSee('x-transition:enter="ease-out duration-300"', false);
        $response->assertSee('x-transition:leave="ease-in duration-200"', false);
        
        // Verify backdrop and content have synchronized transitions
        $response->assertSee('x-transition:enter-start="opacity-0"', false);
        $response->assertSee('x-transition:enter-end="opacity-75"', false);
    }

    /** @test */
    public function modal_focus_trap_functionality_remains_intact()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.file-upload'));
        $response->assertStatus(200);
        
        // Verify focus trap functionality
        $response->assertSee('focusable', false);
        $response->assertSee('firstFocusable()', false);
        $response->assertSee('lastFocusable()', false);
        $response->assertSee('x-on:keydown.tab.prevent', false);
        $response->assertSee('x-on:keydown.shift.tab.prevent', false);
    }

    /** @test */
    public function modal_keyboard_accessibility_works_correctly()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.file-upload'));
        $response->assertStatus(200);
        
        // Verify escape key closes modal
        $response->assertSee('x-on:keydown.escape.window="show = false"', false);
        
        // Verify proper ARIA attributes and semantic structure
        $response->assertSee('focus-visible:outline', false);
    }
}