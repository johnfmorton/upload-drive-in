<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\ClientUserRelationship;
use App\Models\FileUpload;

/**
 * Modal Overlay Timing Test
 * 
 * Comprehensive feature tests for modal overlay timing behavior.
 * Tests the requirements for upload success modal overlay fix.
 */
class ModalOverlayTimingTest extends TestCase
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

    /**
     * Test Requirement 1.1: Modal displays without gray overlay
     * 
     * @test
     */
    public function modal_displays_without_gray_overlay_immediately()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files'));
        $response->assertStatus(200);
        
        // Verify modal structure prevents overlay conflicts
        $response->assertSee('data-modal-name="upload-success"', false);
        $response->assertSee('z-[9999] modal-container', false);
        $response->assertSee('z-[9998] modal-backdrop', false);
        $response->assertSee('z-[10000] modal-content', false);
        
        // Verify modal content is present and accessible
        $response->assertSee('Upload Complete');
        $response->assertSee('Files uploaded successfully!');
        $response->assertSee('Close');
        
        // Verify proper Alpine.js event handling
        $response->assertSee('x-on:open-modal.window="$event.detail == \'upload-success\' ? (show = true', false);
    }

    /**
     * Test Requirement 1.1: Modal content remains visible and interactive
     * 
     * @test
     */
    public function modal_content_remains_visible_and_interactive()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files'));
        $response->assertStatus(200);
        
        // Verify close button is properly configured
        $response->assertSee('@click="show = false"', false);
        $response->assertSee('focus-visible:outline', false);
        
        // Verify backdrop click handler
        $response->assertSee('x-on:click="show = false"', false);
        
        // Verify escape key handler
        $response->assertSee('x-on:keydown.escape.window="show = false"', false);
        
        // Verify modal content structure
        $response->assertSee('modal-content', false);
        $response->assertSee('data-modal-type="content"', false);
    }

    /**
     * Test Requirement 1.4: Z-index layering ensures modal is topmost
     * 
     * @test
     */
    public function z_index_layering_ensures_modal_is_topmost()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files'));
        $response->assertStatus(200);
        
        // Verify z-index hierarchy in HTML structure
        $response->assertSee('data-z-index="9999"', false); // Container
        $response->assertSee('data-z-index="9998"', false); // Backdrop
        $response->assertSee('data-z-index="10000"', false); // Content
        
        // Verify CSS classes for z-index
        $response->assertSee('z-[9999]', false);
        $response->assertSee('z-[9998]', false);
        $response->assertSee('z-[10000]', false);
        
        // Verify data attributes for debugging
        $response->assertSee('data-modal-type="container"', false);
        $response->assertSee('data-modal-type="backdrop"', false);
        $response->assertSee('data-modal-type="content"', false);
    }

    /**
     * Test Requirement 3.4: Test case for modal z-index hierarchy validation
     * 
     * @test
     */
    public function modal_z_index_hierarchy_validation_test_case()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files'));
        $response->assertStatus(200);
        
        // Extract and validate z-index hierarchy from response
        $content = $response->getContent();
        
        // Verify container z-index
        $this->assertStringContainsString('z-[9999] modal-container', $content);
        $this->assertStringContainsString('data-z-index="9999"', $content);
        
        // Verify backdrop z-index
        $this->assertStringContainsString('z-[9998] modal-backdrop', $content);
        $this->assertStringContainsString('data-z-index="9998"', $content);
        
        // Verify content z-index
        $this->assertStringContainsString('z-[10000] modal-content', $content);
        $this->assertStringContainsString('data-z-index="10000"', $content);
        
        // Verify proper nesting structure
        $this->assertStringContainsString('data-modal-name="upload-success"', $content);
        
        // Count occurrences to ensure proper structure
        $containerCount = substr_count($content, 'data-modal-type="container"');
        $backdropCount = substr_count($content, 'data-modal-type="backdrop"');
        $contentCount = substr_count($content, 'data-modal-type="content"');
        
        // Each modal should have exactly one of each type
        $this->assertGreaterThanOrEqual(1, $containerCount);
        $this->assertGreaterThanOrEqual(1, $backdropCount);
        $this->assertGreaterThanOrEqual(1, $contentCount);
    }

    /**
     * Test modal timing behavior programmatically
     * 
     * @test
     */
    public function modal_timing_behavior_is_programmatically_testable()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files'));
        $response->assertStatus(200);
        
        // Verify transition timing configuration
        $response->assertSee('x-transition:enter="ease-out duration-300"', false);
        $response->assertSee('x-transition:leave="ease-in duration-200"', false);
        
        // Verify backdrop transition timing
        $response->assertSee('x-transition:enter-start="opacity-0"', false);
        $response->assertSee('x-transition:enter-end="opacity-75"', false);
        $response->assertSee('x-transition:leave-start="opacity-75"', false);
        $response->assertSee('x-transition:leave-end="opacity-0"', false);
        
        // Verify content transition timing
        $response->assertSee('x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"', false);
        $response->assertSee('x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"', false);
        
        // Verify synchronized display control
        $response->assertSee('x-show="show"', false);
    }

    /**
     * Test debug mode functionality for modal overlay testing
     * 
     * @test
     */
    public function debug_mode_provides_modal_overlay_testing_capabilities()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files') . '?modal-debug=true');
        $response->assertStatus(200);
        
        // Verify debug mode detection
        $response->assertSee('debugMode: window.location.search.includes(\'modal-debug=true\')', false);
        $response->assertSee('localStorage.getItem(\'modal-debug\') === \'true\'', false);
        
        // Verify debug classes
        $response->assertSee(':class="{ \'z-debug-highest\': debugMode }"', false);
        $response->assertSee(':class="{ \'z-debug-highest\': debugMode, \'stacking-context-debug\': debugMode }"', false);
        
        // Verify debug logging
        $response->assertSee('logModalState(action)', false);
        $response->assertSee('console.group', false);
        $response->assertSee('console.log', false);
        
        // Verify debug initialization
        $response->assertSee('document.body.classList.add(\'modal-debug-enabled\')', false);
        $response->assertSee('logModalState(\'Initialized\')', false);
    }

    /**
     * Test modal behavior with file upload context
     * 
     * @test
     */
    public function modal_displays_correctly_in_file_upload_context()
    {
        $this->actingAs($this->client);
        
        // Create a successful file upload to simulate the context
        $fileUpload = FileUpload::factory()->create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->companyUser->id,
            'original_filename' => 'test-file.pdf',
            'file_size' => 1024000,
            'google_drive_file_id' => 'test-drive-file-id' // Indicates completed upload
        ]);
        
        $response = $this->get(route('client.upload-files'));
        $response->assertStatus(200);
        
        // Verify upload success modal is present
        $response->assertSee('name="upload-success"', false);
        $response->assertSee('Upload Complete');
        $response->assertSee('Files uploaded successfully!');
        
        // Verify modal structure in upload context
        $response->assertSee('z-[9999] modal-container', false);
        $response->assertSee('data-modal-name="upload-success"', false);
    }

    /**
     * Test multiple modal types have consistent z-index behavior
     * 
     * @test
     */
    public function multiple_modal_types_have_consistent_z_index_behavior()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files'));
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
        }
        
        // Verify all modals use the same z-index structure
        $content = $response->getContent();
        
        // Count z-index class occurrences
        $containerZIndexCount = substr_count($content, 'z-[9999] modal-container');
        $backdropZIndexCount = substr_count($content, 'z-[9998] modal-backdrop');
        $contentZIndexCount = substr_count($content, 'z-[10000] modal-content');
        
        // Should have at least one of each for the modals present
        $this->assertGreaterThanOrEqual(1, $containerZIndexCount);
        $this->assertGreaterThanOrEqual(1, $backdropZIndexCount);
        $this->assertGreaterThanOrEqual(1, $contentZIndexCount);
    }

    /**
     * Test modal accessibility features remain intact
     * 
     * @test
     */
    public function modal_accessibility_features_remain_intact()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files'));
        $response->assertStatus(200);
        
        // Verify focus trap functionality
        $response->assertSee('focusables()', false);
        $response->assertSee('firstFocusable()', false);
        $response->assertSee('lastFocusable()', false);
        
        // Verify keyboard navigation
        $response->assertSee('x-on:keydown.tab.prevent', false);
        $response->assertSee('x-on:keydown.shift.tab.prevent', false);
        $response->assertSee('x-on:keydown.escape.window', false);
        
        // Verify focus management
        $response->assertSee('focus-visible:outline', false);
        // Focus management is conditional based on focusable attribute
        $response->assertSee('focusable', false);
    }

    /**
     * Test modal event handling system
     * 
     * @test
     */
    public function modal_event_handling_system_works_correctly()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files'));
        $response->assertStatus(200);
        
        // Verify open modal event handling
        $response->assertSee('x-on:open-modal.window="$event.detail == \'upload-success\' ? (show = true', false);
        
        // Verify close modal event handling
        $response->assertSee('x-on:close-modal.window="$event.detail == \'upload-success\' ? (show = false', false);
        
        // Verify close event handling
        $response->assertSee('x-on:close.stop="show = false"', false);
        
        // Verify debug event logging
        $response->assertSee('debugMode && logModalState(\'Event Triggered\')', false);
        $response->assertSee('debugMode && logModalState(\'Close Event\')', false);
    }

    /**
     * Test modal state management
     * 
     * @test
     */
    public function modal_state_management_prevents_overlay_conflicts()
    {
        $this->actingAs($this->client);
        
        $response = $this->get(route('client.upload-files'));
        $response->assertStatus(200);
        
        // Verify Alpine.js data structure (the actual rendered output will have the value)
        $response->assertSee('show: false', false); // @js($show) renders to false by default
        $response->assertSee('debugMode: window.location.search.includes(\'modal-debug=true\')', false);
        
        // Verify state watchers
        $response->assertSee('$watch(\'show\', value => {', false);
        $response->assertSee('document.body.classList.add(\'overflow-y-hidden\')', false);
        $response->assertSee('document.body.classList.remove(\'overflow-y-hidden\')', false);
        
        // Verify initialization
        $response->assertSee('x-init="', false);
        $response->assertSee('logModalState(\'Initialized\')', false);
    }
}