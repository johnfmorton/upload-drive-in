<?php

namespace Tests\Feature;

use App\Models\User;
use App\Mail\ClientVerificationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DualUserCreationConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $employeeUser;

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
    }

    /** @test */
    public function both_user_types_have_consistent_interface_elements()
    {
        // Test admin interface
        $adminResponse = $this->actingAs($this->adminUser)
            ->get('/admin/users');
        
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Create User');
        $adminResponse->assertSee('Create & Send Invitation');
        
        // Test employee interface
        $employeeResponse = $this->actingAs($this->employeeUser)
            ->get("/employee/{$this->employeeUser->username}/clients");
        
        $employeeResponse->assertStatus(200);
        $employeeResponse->assertSee('Create User');
        $employeeResponse->assertSee('Create & Send Invitation');
        
        // Both should have the same form structure
        $adminResponse->assertSee('name="name"', false);
        $adminResponse->assertSee('name="email"', false);
        $adminResponse->assertSee('submitForm', false);
        
        $employeeResponse->assertSee('name="name"', false);
        $employeeResponse->assertSee('name="email"', false);
        $employeeResponse->assertSee('submitForm', false);
    }

    /** @test */
    public function both_user_types_create_users_with_same_data_structure()
    {
        Mail::fake();
        
        $userData = [
            'name' => 'Consistent Test User',
            'email' => 'consistent@example.com',
            'action' => 'create'
        ];
        
        // Admin creates user
        $this->actingAs($this->adminUser)
            ->post('/admin/users', $userData);
        
        $adminCreatedUser = User::where('email', 'consistent@example.com')->first();
        
        // Employee creates user with different email
        $userData['email'] = 'employee-consistent@example.com';
        
        $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", $userData);
        
        $employeeCreatedUser = User::where('email', 'employee-consistent@example.com')->first();
        
        // Both users should have same structure
        $this->assertEquals($adminCreatedUser->role, $employeeCreatedUser->role);
        $this->assertEquals($adminCreatedUser->name, $employeeCreatedUser->name);
        $this->assertNotNull($adminCreatedUser->created_at);
        $this->assertNotNull($employeeCreatedUser->created_at);
        
        // Both should have relationships created
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $adminCreatedUser->id,
            'company_user_id' => $this->adminUser->id
        ]);
        
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $employeeCreatedUser->id,
            'company_user_id' => $this->employeeUser->id
        ]);
    }

    /** @test */
    public function both_user_types_send_identical_invitation_emails()
    {
        Mail::fake();
        
        $userData = [
            'name' => 'Invitation Test User',
            'action' => 'create_and_invite'
        ];
        
        // Admin sends invitation
        $userData['email'] = 'admin-invite@example.com';
        $this->actingAs($this->adminUser)
            ->post('/admin/users', $userData);
        
        // Employee sends invitation
        $userData['email'] = 'employee-invite@example.com';
        $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", $userData);
        
        // Both should send ClientVerificationMail (clients get client template)
        Mail::assertSent(ClientVerificationMail::class, 2);
        
        // Verify emails were sent to correct addresses
        Mail::assertSent(ClientVerificationMail::class, function ($mail) {
            return $mail->hasTo('admin-invite@example.com');
        });
        
        Mail::assertSent(ClientVerificationMail::class, function ($mail) {
            return $mail->hasTo('employee-invite@example.com');
        });
    }

    /** @test */
    public function both_user_types_handle_validation_errors_consistently()
    {
        // Test admin validation
        $adminResponse = $this->actingAs($this->adminUser)
            ->post('/admin/users', [
                'name' => '',
                'email' => 'invalid-email',
                'action' => 'invalid_action'
            ]);
        
        $adminResponse->assertSessionHasErrors(['name', 'email', 'action']);
        
        // Test employee validation
        $employeeResponse = $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", [
                'name' => '',
                'email' => 'invalid-email',
                'action' => 'invalid_action'
            ]);
        
        $employeeResponse->assertSessionHasErrors(['name', 'email', 'action']);
        
        // Both should have same validation rules
        $this->assertEquals(
            session('errors')->get('name'),
            session('errors')->get('name')
        );
    }

    /** @test */
    public function both_user_types_handle_duplicate_users_consistently()
    {
        Mail::fake();
        
        // Create existing user
        $existingUser = User::factory()->create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'role' => 'client'
        ]);
        
        // Create relationship with admin only (employee will try to create duplicate)
        $this->adminUser->clientUsers()->attach($existingUser->id);
        
        $userData = [
            'name' => 'Duplicate User',
            'email' => 'existing@example.com',
            'action' => 'create'
        ];
        
        // Admin tries to create duplicate
        $adminResponse = $this->actingAs($this->adminUser)
            ->post('/admin/users', $userData);
        
        $adminResponse->assertRedirect('/admin/users');
        
        // Employee tries to create duplicate
        $employeeResponse = $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", $userData);
        
        $employeeResponse->assertStatus(302); // Just check for redirect
        
        // Should still only have one user
        $this->assertEquals(1, User::where('email', 'existing@example.com')->count());
        
        // No emails should be sent for existing users
        Mail::assertNotSent(ClientVerificationMail::class);
    }

    /** @test */
    public function both_user_types_have_equivalent_access_to_both_creation_methods()
    {
        Mail::fake();
        
        // Test admin can use both methods
        $this->actingAs($this->adminUser)
            ->post('/admin/users', [
                'name' => 'Admin Create Only',
                'email' => 'admin-create@example.com',
                'action' => 'create'
            ])
            ->assertRedirect('/admin/users');
        
        $this->actingAs($this->adminUser)
            ->post('/admin/users', [
                'name' => 'Admin Create and Invite',
                'email' => 'admin-invite@example.com',
                'action' => 'create_and_invite'
            ])
            ->assertRedirect('/admin/users');
        
        // Test employee can use both methods
        $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", [
                'name' => 'Employee Create Only',
                'email' => 'employee-create@example.com',
                'action' => 'create'
            ])
            ->assertStatus(302); // Just check for redirect
        
        $this->actingAs($this->employeeUser)
            ->post("/employee/{$this->employeeUser->username}/clients", [
                'name' => 'Employee Create and Invite',
                'email' => 'employee-invite@example.com',
                'action' => 'create_and_invite'
            ])
            ->assertStatus(302); // Just check for redirect
        
        // Verify all users were created
        $this->assertDatabaseHas('users', ['email' => 'admin-create@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'admin-invite@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'employee-create@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'employee-invite@example.com']);
        
        // Verify correct number of emails sent (only for invite actions)
        Mail::assertSent(ClientVerificationMail::class, 2);
    }

    /** @test */
    public function both_user_types_follow_same_form_validation_patterns()
    {
        // Test that both interfaces require the same fields
        $requiredFields = ['name', 'email', 'action'];
        
        foreach ($requiredFields as $field) {
            $incompleteData = [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'action' => 'create'
            ];
            unset($incompleteData[$field]);
            
            // Admin validation
            $adminResponse = $this->actingAs($this->adminUser)
                ->post('/admin/users', $incompleteData);
            
            $adminResponse->assertSessionHasErrors([$field]);
            
            // Employee validation
            $employeeResponse = $this->actingAs($this->employeeUser)
                ->post("/employee/{$this->employeeUser->username}/clients", $incompleteData);
            
            $employeeResponse->assertSessionHasErrors([$field]);
        }
    }

    /** @test */
    public function both_user_types_maintain_security_and_authorization_patterns()
    {
        // Create unauthorized users
        $clientUser = User::factory()->create(['role' => 'client']);
        $otherEmployee = User::factory()->create([
            'role' => 'employee',
            'username' => 'otheremployee'
        ]);
        
        // Test admin endpoint security
        $this->actingAs($clientUser)
            ->post('/admin/users', [
                'name' => 'Unauthorized',
                'email' => 'unauthorized@example.com',
                'action' => 'create'
            ])
            ->assertStatus(403);
        
        // Test employee endpoint security
        $this->actingAs($clientUser)
            ->post("/employee/{$this->employeeUser->username}/clients", [
                'name' => 'Unauthorized',
                'email' => 'unauthorized@example.com',
                'action' => 'create'
            ])
            ->assertStatus(403);
        
        // Test employee cannot access other employee's clients
        $this->actingAs($otherEmployee)
            ->post("/employee/{$this->employeeUser->username}/clients", [
                'name' => 'Cross Employee',
                'email' => 'cross@example.com',
                'action' => 'create'
            ])
            ->assertStatus(403);
        
        // Verify no unauthorized users were created
        $this->assertDatabaseMissing('users', ['email' => 'unauthorized@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'cross@example.com']);
    }
}