<?php

namespace Tests\Feature;

use App\Models\User;
use App\Mail\LoginVerificationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DualUserCreationEndToEndAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function admin_can_complete_create_user_workflow_without_invitation()
    {
        Mail::fake();
        
        // Step 1: Admin navigates to users page
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/users');
        
        $response->assertStatus(200);
        $response->assertSee('Create User');
        $response->assertSee('Create & Send Invitation');
        
        // Step 2: Admin fills form and selects "Create User"
        $userData = [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create'
        ];
        
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/users', $userData);
        
        // Step 3: Verify redirect and success message
        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success', 'Client user created successfully. You can provide them with their login link manually.');
        
        // Step 4: Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'role' => 'client'
        ]);
        
        // Step 5: Verify relationship was created
        $clientUser = User::where('email', 'client@example.com')->first();
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->adminUser->id
        ]);
        
        // Step 6: Verify no email was sent
        Mail::assertNotSent(LoginVerificationMail::class);
        
        // Step 7: Verify admin can see the created user
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/users');
        
        $response->assertSee('Test Client');
        $response->assertSee('client@example.com');
    }

    /** @test */
    public function admin_can_complete_create_and_invite_workflow()
    {
        Mail::fake();
        Queue::fake();
        
        // Step 1: Admin navigates to users page
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/users');
        
        $response->assertStatus(200);
        
        // Step 2: Admin fills form and selects "Create & Send Invitation"
        $userData = [
            'name' => 'Invited Client',
            'email' => 'invited@example.com',
            'action' => 'create_and_invite'
        ];
        
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/users', $userData);
        
        // Step 3: Verify redirect and success message
        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success', 'Client user created and invitation sent successfully.');
        
        // Step 4: Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Invited Client',
            'email' => 'invited@example.com',
            'role' => 'client'
        ]);
        
        // Step 5: Verify email was sent
        $clientUser = User::where('email', 'invited@example.com')->first();
        Mail::assertSent(LoginVerificationMail::class, function ($mail) use ($clientUser) {
            return $mail->hasTo('invited@example.com');
        });
        
        // Step 6: Verify relationship was created
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->adminUser->id
        ]);
    }

    /** @test */
    public function admin_workflow_handles_duplicate_users_correctly()
    {
        Mail::fake();
        
        // Create existing user
        $existingUser = User::factory()->create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'role' => 'client'
        ]);
        
        // Create relationship with admin
        $this->adminUser->clientUsers()->attach($existingUser->id);
        
        // Try to create user with same email
        $userData = [
            'name' => 'Duplicate User',
            'email' => 'existing@example.com',
            'action' => 'create'
        ];
        
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/users', $userData);
        
        // Should still succeed (existing user handling)
        $response->assertRedirect('/admin/users');
        
        // Verify no duplicate user was created
        $this->assertEquals(1, User::where('email', 'existing@example.com')->count());
        
        // Verify no email was sent for existing user
        Mail::assertNotSent(LoginVerificationMail::class);
    }

    /** @test */
    public function admin_workflow_handles_email_sending_failures_gracefully()
    {
        Mail::fake();
        Mail::shouldReceive('to')->andThrow(new \Exception('Email service unavailable'));
        
        $userData = [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite'
        ];
        
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/users', $userData);
        
        // User should still be created even if email fails
        $this->assertDatabaseHas('users', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'role' => 'client'
        ]);
        
        // Should show appropriate error message
        $response->assertRedirect('/admin/users');
    }

    /** @test */
    public function admin_workflow_validates_required_fields()
    {
        // Test missing name
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/users', [
                'email' => 'test@example.com',
                'action' => 'create'
            ]);
        
        $response->assertSessionHasErrors(['name']);
        
        // Test missing email
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/users', [
                'name' => 'Test User',
                'action' => 'create'
            ]);
        
        $response->assertSessionHasErrors(['email']);
        
        // Test missing action
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/users', [
                'name' => 'Test User',
                'email' => 'test@example.com'
            ]);
        
        $response->assertSessionHasErrors(['action']);
        
        // Test invalid action
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/users', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'action' => 'invalid_action'
            ]);
        
        $response->assertSessionHasErrors(['action']);
    }

    /** @test */
    public function admin_interface_displays_both_action_buttons()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/users');
        
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
    public function admin_can_access_users_page_with_proper_authorization()
    {
        // Test admin access
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/users');
        
        $response->assertStatus(200);
        
        // Test non-admin cannot access
        $clientUser = User::factory()->create(['role' => 'client']);
        
        $response = $this->actingAs($clientUser)
            ->get('/admin/users');
        
        $response->assertStatus(403);
    }
}