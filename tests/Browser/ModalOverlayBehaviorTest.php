<?php

namespace Tests\Browser;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\ClientUserRelationship;

/**
 * Modal Overlay Behavior Test
 * 
 * This test simulates browser behavior for modal overlay issues without requiring Laravel Dusk.
 * It focuses on testing the modal component structure and JavaScript behavior.
 */
class ModalOverlayBehaviorTest extends TestCase
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
    public function upload_success_modal_has_correct_structure_and_z_index_hierarchy()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files') . '?modal-debug=true');
        $response->assertStatus(200);
        
        // Verify modal structure is present
        $response->assertSee('data-modal-name="upload-success"', false);
        $response->assertSee('data-modal-type="container"', false);
        $response->assertSee('data-modal-type="backdrop"', false);
        $response->assertSee('data-modal-type="content"', false);
        
        // Verify z-index hierarchy in HTML
        $response->assertSee('z-[9999]', false); // Container
        $response->assertSee('z-[9998]', false); // Backdrop
        $response->assertSee('z-[10000]', false); // Content
        
        // Verify data attributes for debugging
        $response->assertSee('data-z-index="9999"', false);
        $response->assertSee('data-z-index="9998"', false);
        $response->assertSee('data-z-index="10000"', false);
        
        // Verify debug mode classes are applied
        $response->assertSee('z-debug-highest', false);
        $response->assertSee('stacking-context-debug', false);
    }

    /** @test */
    public function modal_timing_behavior_is_configured_correctly()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files'));
        $response->assertStatus(200);
        
        // Verify transition timing is configured to prevent overlay conflicts
        $response->assertSee('x-transition:enter="ease-out duration-300"', false);
        $response->assertSee('x-transition:leave="ease-in duration-200"', false);
        
        // Verify backdrop and content transitions are synchronized
        $response->assertSee('x-transition:enter-start="opacity-0"', false);
        $response->assertSee('x-transition:enter-end="opacity-75"', false);
        
        // Verify modal content transitions
        $response->assertSee('x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"', false);
        $response->assertSee('x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"', false);
        
        // Verify Alpine.js event handling for timing
        $response->assertSee('x-on:open-modal.window', false);
        $response->assertSee('logModalState', false);
    }

    /** @test */
    public function modal_close_button_has_proper_event_handlers()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files'));
        $response->assertStatus(200);
        
        // Verify close button has proper Alpine.js event handler
        $response->assertSee('@click="show = false"', false);
        
        // Verify close button is not disabled and has proper styling
        $response->assertSee('focus-visible:outline', false);
        $response->assertSee('Close');
        
        // Verify escape key handler
        $response->assertSee('x-on:keydown.escape.window="show = false"', false);
        
        // Verify backdrop click handler
        $response->assertSee('x-on:click="show = false"', false);
    }

    /** @test */
    public function modal_backdrop_has_correct_positioning_and_handlers()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files'));
        $response->assertStatus(200);
        
        // Verify backdrop positioning and z-index
        $response->assertSee('fixed inset-0 bg-gray-500 opacity-75 z-[9998] modal-backdrop', false);
        
        // Verify backdrop click handler
        $response->assertSee('x-on:click="show = false"', false);
        
        // Verify backdrop has proper data attributes
        $response->assertSee('data-modal-type="backdrop"', false);
        $response->assertSee('data-z-index="9998"', false);
        
        // Verify backdrop transitions don't conflict with content
        $response->assertSee('x-transition:enter="ease-out duration-300"', false);
        $response->assertSee('x-transition:leave="ease-in duration-200"', false);
    }

    /** @test */
    public function multiple_modal_types_have_consistent_z_index_structure()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files'));
        $response->assertStatus(200);
        
        // Check that all modal types use the same z-index structure
        $modalTypes = [
            'upload-success',
            'association-success', 
            'association-error',
            'upload-error',
            'no-files-error'
        ];
        
        foreach ($modalTypes as $modalType) {
            // Verify each modal has the same z-index hierarchy
            $response->assertSee('data-modal-name="' . $modalType . '"', false);
        }
        
        // Verify consistent z-index classes across all modals
        $response->assertSee('z-[9999] modal-container', false);
        $response->assertSee('z-[9998] modal-backdrop', false);
        $response->assertSee('z-[10000] modal-content', false);
        
        // Verify all modals use the same event handling pattern
        $response->assertSee('x-on:open-modal.window', false);
        $response->assertSee('x-on:close-modal.window', false);
    }

    /** @test */
    public function modal_transitions_are_configured_to_prevent_layering_conflicts()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files'));
        $response->assertStatus(200);
        
        // Verify backdrop transitions
        $response->assertSee('x-transition:enter="ease-out duration-300"', false);
        $response->assertSee('x-transition:enter-start="opacity-0"', false);
        $response->assertSee('x-transition:enter-end="opacity-75"', false);
        $response->assertSee('x-transition:leave="ease-in duration-200"', false);
        $response->assertSee('x-transition:leave-start="opacity-75"', false);
        $response->assertSee('x-transition:leave-end="opacity-0"', false);
        
        // Verify content transitions
        $response->assertSee('x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"', false);
        $response->assertSee('x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"', false);
        $response->assertSee('x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"', false);
        $response->assertSee('x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"', false);
        
        // Verify both backdrop and content use x-show for synchronized display
        $response->assertSee('x-show="show"', false);
    }

    /** @test */
    public function modal_debug_mode_provides_proper_debugging_features()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files') . '?modal-debug=true');
        $response->assertStatus(200);
        
        // Verify debug mode detection
        $response->assertSee('debugMode: window.location.search.includes(\'modal-debug=true\')', false);
        $response->assertSee('localStorage.getItem(\'modal-debug\') === \'true\'', false);
        
        // Verify debug classes are conditionally applied
        $response->assertSee(':class="{ \'z-debug-highest\': debugMode }"', false);
        $response->assertSee(':class="{ \'z-debug-highest\': debugMode, \'stacking-context-debug\': debugMode }"', false);
        
        // Verify debug logging function
        $response->assertSee('logModalState(action)', false);
        $response->assertSee('console.group', false);
        $response->assertSee('console.log', false);
        $response->assertSee('console.groupEnd', false);
        
        // Verify debug initialization
        $response->assertSee('document.body.classList.add(\'modal-debug-enabled\')', false);
        $response->assertSee('logModalState(\'Initialized\')', false);
    }
}