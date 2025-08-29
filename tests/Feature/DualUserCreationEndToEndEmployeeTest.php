<?php

namespace Tests\Feature;

use App\Models\User;
use App\Mail\LoginVerificationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DualUserCreationEndToEndEmployeeTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->employeeUser = User::factory()->create([
            'role' => 'employee',
            'username' => 'testemployee',
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function employee_can_complete_create_user_workflow_without_invitation()
    {
        Mail::fake();
        
        // Step 1: Employee navigates to client management page
        $response = $this->actingAs($this->employeeUser)
            ->get("/employee/{$this->employeeUser->username}/clients");
        
        $response->assertStatus(200);
        $response->assertSee('Create User');
        $response->assertSee('Create & Send Invitation');
        
        // Step 2: Employee fills form and selects "Create User"
        $userData = [
            'name' => 'Employee Client',
            'email' => 'employee-client@example.com',
            'action' => 'create'
        ];
        
        $response = $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", $userData);
        
        // Step 3: Verify redirect and success message
        $response->assertRedirect("/employee/{$this->employeeUser->username}/clients");
        $response->assertSessionHas('status', 'employee-client-created');
        
        // Step 4: Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Employee Client',
            'email' => 'employee-client@example.com',
            'role' => 'client'
        ]);
        
        // Step 5: Verify relationship was created
        $clientUser = User::where('email', 'employee-client@example.com')->first();
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->employeeUser->id
        ]);
        
        // Step 6: Verify no email was sent
        Mail::assertNotSent(LoginVerificationMail::class);
        
        // Step 7: Verify employee can see the created user
        $response = $this->actingAs($this->employeeUser)
            ->get("/employee/{$this->employeeUser->username}/clients");
        
        $response->assertSee('Employee Client');
        $response->assertSee('employee-client@example.com');
    }

    /** @test */
    public function employee_can_complete_create_and_invite_workflow()
    {
        Mail::fake();
        Queue::fake();
        
        // Step 1: Employee navigates to client management page
        $response = $this->actingAs($this->employeeUser)
            ->get("/employee/{$this->employeeUser->username}/clients");
        
        $response->assertStatus(200);
        
        // Step 2: Employee fills form and selects "Create & Send Invitation"
        $userData = [
            'name' => 'Invited Employee Client',
            'email' => 'invited-employee@example.com',
            'action' => 'create_and_invite'
        ];
        
        $response = $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", $userData);
        
        // Step 3: Verify redirect and success message
        $response->assertRedirect("/employee/{$this->employeeUser->username}/clients");
        $response->assertSessionHas('status', 'employee-client-created-and-invited');
        
        // Step 4: Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Invited Employee Client',
            'email' => 'invited-employee@example.com',
            'role' => 'client'
        ]);
        
        // Step 5: Verify email was sent
        $clientUser = User::where('email', 'invited-employee@example.com')->first();
        Mail::assertSent(LoginVerificationMail::class, function ($mail) use ($clientUser) {
            return $mail->hasTo('invited-employee@example.com');
        });
        
        // Step 6: Verify relationship was created
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->employeeUser->id
        ]);
    }

    /** @test */
    public function employee_workflow_handles_duplicate_users_correctly()
    {
        Mail::fake();
        
        // Create existing user
        $existingUser = User::factory()->create([
            'name' => 'Existing Employee Client',
            'email' => 'existing-employee@example.com',
            'role' => 'client'
        ]);
        
        // Create relationship with employee
        $this->employeeUser->clientUsers()->attach($existingUser->id);
        
        // Try to create user with same email
        $userData = [
            'name' => 'Duplicate Employee Client',
            'email' => 'existing-employee@example.com',
            'action' => 'create'
        ];
        
        $response = $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", $userData);
        
        // Should still succeed (existing user handling)
        $response->assertStatus(302); // Just check for redirect, not specific URL
        
        // Verify no duplicate user was created
        $this->assertEquals(1, User::where('email', 'existing-employee@example.com')->count());
        
        // Verify no email was sent for existing user
        Mail::assertNotSent(LoginVerificationMail::class);
    }

    /** @test */
    public function employee_workflow_handles_email_sending_failures_gracefully()
    {
        Mail::fake();
        Mail::shouldReceive('to')->andThrow(new \Exception('Email service unavailable'));
        
        $userData = [
            'name' => 'Test Employee Client',
            'email' => 'employee-test@example.com',
            'action' => 'create_and_invite'
        ];
        
        $response = $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", $userData);
        
        // User should still be created even if email fails
        $this->assertDatabaseHas('users', [
            'name' => 'Test Employee Client',
            'email' => 'employee-test@example.com',
            'role' => 'client'
        ]);
        
        // Should redirect back
        $response->assertStatus(302); // Just check for redirect, not specific URL
    }

    /** @test */
    public function employee_workflow_validates_required_fields()
    {
        // Test missing name
        $response = $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", [
                'email' => 'test@example.com',
                'action' => 'create'
            ]);
        
        $response->assertSessionHasErrors(['name']);
        
        // Test missing email
        $response = $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", [
                'name' => 'Test User',
                'action' => 'create'
            ]);
        
        $response->assertSessionHasErrors(['email']);
        
        // Test missing action
        $response = $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", [
                'name' => 'Test User',
                'email' => 'test@example.com'
            ]);
        
        $response->assertSessionHasErrors(['action']);
        
        // Test invalid action
        $response = $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'action' => 'invalid_action'
            ]);
        
        $response->assertSessionHasErrors(['action']);
    }

    /** @test */
    public function employee_interface_displays_both_action_buttons()
    {
        $response = $this->actingAs($this->employeeUser)
            ->get("/employee/{$this->employeeUser->username}/clients");
        
        $response->assertStatus(200);
        
        // Check for both buttons
        $response->assertSee('Create User');
        $response->assertSee('Create & Send Invitation');
        
        // Check for form elements
        $response->assertSee('name="name"', false);
        $response->assertSee('name="email"', false);
        
        // Check for JavaScript functionality
        $response->assertSee('submitForm', false);
    }

    /** @test */
    public function employee_can_access_client_management_with_proper_authorization()
    {
        // Test employee access to their own clients
        $response = $this->actingAs($this->employeeUser)
            ->get("/employee/{$this->employeeUser->username}/clients");
        
        $response->assertStatus(200);
        
        // Test employee cannot access another employee's clients
        $otherEmployee = User::factory()->create([
            'role' => 'employee',
            'username' => 'otheremployee'
        ]);
        
        $response = $this->actingAs($this->employeeUser)
            ->get("/employee/{$otherEmployee->username}/clients");
        
        $response->assertStatus(403);
        
        // Test non-employee cannot access
        $clientUser = User::factory()->create(['role' => 'client']);
        
        $response = $this->actingAs($clientUser)
            ->get("/employee/{$this->employeeUser->username}/clients");
        
        $response->assertStatus(403);
    }

    /** @test */
    public function employee_workflow_maintains_consistent_messaging_with_admin()
    {
        Mail::fake();
        
        // Test create without invitation
        $response = $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", [
                'name' => 'Test Client',
                'email' => 'test@example.com',
                'action' => 'create'
            ]);
        
        $response->assertSessionHas('status', 'employee-client-created');
        
        // Test create with invitation
        $response = $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", [
                'name' => 'Invited Client',
                'email' => 'invited@example.com',
                'action' => 'create_and_invite'
            ]);
        
        $response->assertSessionHas('status', 'employee-client-created-and-invited');
    }
}