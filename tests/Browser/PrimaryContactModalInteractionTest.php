<?php

namespace Tests\Browser;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\ClientUserRelationship;
use App\Enums\UserRole;

/**
 * Primary Contact Modal Interaction Test
 * 
 * This test simulates browser behavior for primary contact modal interactions
 * without requiring Laravel Dusk. It focuses on testing the modal component
 * structure, JavaScript behavior, and UI workflows.
 */
class PrimaryContactModalInteractionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee1;
    private User $employee2;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN,
            'name' => 'Admin User',
        ]);
        
        $this->employee1 = User::factory()->create([
            'email' => 'employee1@example.com',
            'role' => UserRole::EMPLOYEE,
            'name' => 'Employee One',
            'owner_id' => $this->admin->id,
        ]);
        
        $this->employee2 = User::factory()->create([
            'email' => 'employee2@example.com',
            'role' => UserRole::EMPLOYEE,
            'name' => 'Employee Two',
            'owner_id' => $this->admin->id,
        ]);
        
        $this->client = User::factory()->create([
            'email' => 'client@example.com',
            'role' => UserRole::CLIENT,
            'name' => 'Test Client',
        ]);
        
        // Create initial relationship
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);
    }

    /** @test */
    public function primary_contact_confirmation_modal_has_correct_structure()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.users.show', $this->client));
        
        $response->assertStatus(200);
        
        // Verify modal component is included
        $response->assertSee('x-show="showPrimaryContactConfirmation"', false);
        $response->assertSee('z-[9999]', false); // Correct z-index
        $response->assertSee('modal-backdrop', false); // Backdrop class
        
        // Verify modal content structure
        $response->assertSee('Change Primary Contact');
        $response->assertSee('Yes, Change Primary Contact');
        $response->assertSee('Cancel');
        
        // Verify Alpine.js data attributes
        $response->assertSee('x-data', false);
        $response->assertSee('showPrimaryContactConfirmation', false);
        $response->assertSee('newPrimaryContact', false);
    }

    /** @test */
    public function primary_contact_modal_follows_z_index_hierarchy()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.users.show', $this->client));
        
        $response->assertStatus(200);
        
        // Verify z-index hierarchy is correct
        $content = $response->getContent();
        
        // Modal container should have z-[9999]
        $this->assertStringContainsString('z-[9999]', $content);
        
        // Modal backdrop should have z-[9998]
        $this->assertStringContainsString('z-[9998]', $content);
        
        // Modal content should have z-[10000]
        $this->assertStringContainsString('z-[10000]', $content);
        
        // Verify debug attributes are present
        $this->assertStringContainsString('data-modal-name', $content);
        $this->assertStringContainsString('data-z-index', $content);
        $this->assertStringContainsString('data-modal-type', $content);
    }

    /** @test */
    public function primary_contact_selection_interface_displays_correctly()
    {
        // Add more team members to test interface
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->employee1->id,
            'is_primary' => false,
        ]);
        
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->employee2->id,
            'is_primary' => false,
        ]);
        
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.users.show', $this->client));
        
        $response->assertStatus(200);
        
        // Verify current primary contact is highlighted
        $response->assertSee('Current Primary Contact');
        $response->assertSee($this->admin->name);
        $response->assertSee('Primary Contact', false); // Badge text
        
        // Verify team member selection interface
        $response->assertSee($this->employee1->name);
        $response->assertSee($this->employee2->name);
        $response->assertSee('Make Primary');
        
        // Verify radio buttons for primary contact selection
        $response->assertSee('name="primary_contact"', false);
        $response->assertSee('value="' . $this->admin->id . '"', false);
        $response->assertSee('value="' . $this->employee1->id . '"', false);
        $response->assertSee('value="' . $this->employee2->id . '"', false);
    }

    /** @test */
    public function primary_contact_explanation_is_displayed()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.users.show', $this->client));
        
        $response->assertStatus(200);
        
        // Verify explanation section is present
        $response->assertSee('About Primary Contact');
        $response->assertSee('The primary contact receives file uploads and notifications');
        $response->assertSee('Only one team member can be the primary contact');
        
        // Verify visual styling classes
        $response->assertSee('bg-blue-50', false);
        $response->assertSee('border-blue-200', false);
        $response->assertSee('text-blue-800', false);
    }

    /** @test */
    public function modal_javascript_functionality_is_properly_structured()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.users.show', $this->client));
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Verify Alpine.js event handlers
        $this->assertStringContainsString('x-on:change="showPrimaryContactConfirmation = true', $content);
        $this->assertStringContainsString('x-on:click="confirmPrimaryContactChange()"', $content);
        $this->assertStringContainsString('x-on:click="showPrimaryContactConfirmation = false"', $content);
        
        // Verify modal state management
        $this->assertStringContainsString('showPrimaryContactConfirmation: false', $content);
        $this->assertStringContainsString('newPrimaryContact: \'\'', $content);
        
        // Verify transitions are properly configured
        $this->assertStringContainsString('x-transition:enter', $content);
        $this->assertStringContainsString('x-transition:leave', $content);
        $this->assertStringContainsString('ease-out duration-300', $content);
    }

    /** @test */
    public function modal_accessibility_attributes_are_present()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.users.show', $this->client));
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Verify ARIA attributes
        $this->assertStringContainsString('aria-labelledby="modal-title"', $content);
        $this->assertStringContainsString('role="dialog"', $content);
        $this->assertStringContainsString('aria-modal="true"', $content);
        
        // Verify semantic HTML structure
        $this->assertStringContainsString('id="modal-title"', $content);
        $this->assertStringContainsString('aria-hidden="true"', $content);
        
        // Verify keyboard interaction support
        $this->assertStringContainsString('focus:outline-none', $content);
        $this->assertStringContainsString('focus:ring-2', $content);
    }

    /** @test */
    public function team_assignment_form_handles_validation_errors()
    {
        $this->actingAs($this->admin);
        
        // Submit invalid form data
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [],
            'primary_contact' => '',
        ]);
        
        $response->assertSessionHasErrors(['team_members', 'primary_contact']);
        
        // Follow redirect to see error display
        $response = $this->get(route('admin.users.show', $this->client));
        
        $response->assertStatus(200);
        
        // Verify error display structure (assuming standard Laravel error display)
        $content = $response->getContent();
        
        // Check for error display elements
        $this->assertStringContainsString('alert', $content);
    }

    /** @test */
    public function primary_contact_change_workflow_completes_successfully()
    {
        // Add employee to team
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->employee1->id,
            'is_primary' => false,
        ]);
        
        $this->actingAs($this->admin);
        
        // Submit valid team assignment with new primary contact
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id, $this->employee1->id],
            'primary_contact' => $this->employee1->id,
        ]);
        
        $response->assertRedirect(route('admin.users.show', $this->client))
                ->assertSessionHas('success');
        
        // Follow redirect to verify UI updates
        $response = $this->get(route('admin.users.show', $this->client));
        
        $response->assertStatus(200);
        
        // Verify new primary contact is displayed
        $response->assertSee($this->employee1->name);
        $response->assertSee('Current Primary Contact');
        
        // Verify success message is displayed
        $response->assertSee('success', false);
    }

    /** @test */
    public function modal_loading_states_are_properly_configured()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.users.show', $this->client));
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Verify loading state elements
        $this->assertStringContainsString(':disabled="isProcessing"', $content);
        $this->assertStringContainsString('x-show="!isProcessing"', $content);
        $this->assertStringContainsString('x-show="isProcessing"', $content);
        $this->assertStringContainsString('disabled:opacity-50', $content);
        $this->assertStringContainsString('disabled:cursor-not-allowed', $content);
        
        // Verify loading spinner
        $this->assertStringContainsString('animate-spin', $content);
        $this->assertStringContainsString('Processing...', $content);
    }

    /** @test */
    public function modal_follows_design_system_standards()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.users.show', $this->client));
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Verify consistent styling classes
        $this->assertStringContainsString('bg-white', $content);
        $this->assertStringContainsString('rounded-lg', $content);
        $this->assertStringContainsString('shadow-xl', $content);
        $this->assertStringContainsString('overflow-hidden', $content);
        
        // Verify button styling consistency
        $this->assertStringContainsString('inline-flex justify-center', $content);
        $this->assertStringContainsString('rounded-md border', $content);
        $this->assertStringContainsString('px-4 py-2', $content);
        
        // Verify color scheme consistency
        $this->assertStringContainsString('bg-yellow-600', $content); // Warning color for primary action
        $this->assertStringContainsString('text-white', $content);
        $this->assertStringContainsString('hover:bg-yellow-700', $content);
    }
}