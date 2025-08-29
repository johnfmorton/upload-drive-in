<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\LoginVerificationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DualUserCreationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);

        // Create employee user
        $this->employeeUser = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee@example.com',
            'username' => 'testemployee',
        ]);
    }

    /** @test */
    public function admin_can_create_user_without_sending_invitation()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create'
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'Client user created successfully. You can provide them with their login link manually.');

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'role' => UserRole::CLIENT,
        ]);

        // Verify relationship was created
        $clientUser = User::where('email', 'client@example.com')->first();
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->adminUser->id,
        ]);

        // Verify no email was sent
        Mail::assertNothingSent();
    }

    /** @test */
    public function admin_can_create_user_and_send_invitation()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client Invited',
            'email' => 'invited@example.com',
            'action' => 'create_and_invite'
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'Client user created and invitation sent successfully.');

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Test Client Invited',
            'email' => 'invited@example.com',
            'role' => UserRole::CLIENT,
        ]);

        // Verify relationship was created
        $clientUser = User::where('email', 'invited@example.com')->first();
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->adminUser->id,
        ]);

        // Verify invitation email was sent
        Mail::assertSent(LoginVerificationMail::class, function ($mail) {
            return $mail->hasTo('invited@example.com');
        });
    }

    /** @test */
    public function employee_can_create_user_without_sending_invitation()
    {
        Mail::fake();
        
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
            'name' => 'Employee Client',
            'email' => 'employee-client@example.com',
            'action' => 'create'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'employee-client-created');

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Employee Client',
            'email' => 'employee-client@example.com',
            'role' => UserRole::CLIENT,
        ]);

        // Verify relationship was created
        $clientUser = User::where('email', 'employee-client@example.com')->first();
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->employeeUser->id,
        ]);

        // Verify no email was sent
        Mail::assertNothingSent();
    }

    /** @test */
    public function employee_can_create_user_and_send_invitation()
    {
        Mail::fake();
        
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
            'name' => 'Employee Client Invited',
            'email' => 'employee-invited@example.com',
            'action' => 'create_and_invite'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'employee-client-created-and-invited');

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Employee Client Invited',
            'email' => 'employee-invited@example.com',
            'role' => UserRole::CLIENT,
        ]);

        // Verify relationship was created
        $clientUser = User::where('email', 'employee-invited@example.com')->first();
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->employeeUser->id,
        ]);

        // Verify invitation email was sent
        Mail::assertSent(LoginVerificationMail::class, function ($mail) {
            return $mail->hasTo('employee-invited@example.com');
        });
    }
    /**
 @test */
    public function admin_creation_handles_duplicate_users_correctly()
    {
        Mail::fake();
        
        // Create existing user
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'role' => UserRole::CLIENT,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Updated Name',
            'email' => 'existing@example.com',
            'action' => 'create_and_invite'
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        // Verify relationship was created even for existing user
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $existingUser->id,
            'company_user_id' => $this->adminUser->id,
        ]);

        // Verify invitation email was still sent
        Mail::assertSent(LoginVerificationMail::class);
    }

    /** @test */
    public function employee_creation_handles_duplicate_users_correctly()
    {
        Mail::fake();
        
        // Create existing user
        $existingUser = User::factory()->create([
            'email' => 'existing-employee@example.com',
            'role' => UserRole::CLIENT,
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
            'name' => 'Updated Name',
            'email' => 'existing-employee@example.com',
            'action' => 'create_and_invite'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'employee-client-created-and-invited');

        // Verify relationship was created even for existing user
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $existingUser->id,
            'company_user_id' => $this->employeeUser->id,
        ]);

        // Verify invitation email was still sent
        Mail::assertSent(LoginVerificationMail::class);
    }

    /** @test */
    public function admin_creation_requires_valid_action_parameter()
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'action' => 'invalid_action'
        ]);

        $response->assertSessionHasErrors(['action']);
        
        // Verify user was not created
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function employee_creation_requires_valid_action_parameter()
    {
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'action' => 'invalid_action'
        ]);

        $response->assertSessionHasErrors(['action']);
        
        // Verify user was not created
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function admin_creation_requires_action_parameter()
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            // Missing action parameter
        ]);

        $response->assertSessionHasErrors(['action']);
        
        // Verify user was not created
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function employee_creation_requires_action_parameter()
    {
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            // Missing action parameter
        ]);

        $response->assertSessionHasErrors(['action']);
        
        // Verify user was not created
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function admin_creation_handles_email_sending_failures_gracefully()
    {
        // Mock mail to throw exception
        Mail::shouldReceive('to')->andThrow(new \Exception('Mail server error'));
        
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'action' => 'create_and_invite'
        ]);

        // Should still redirect successfully
        $response->assertRedirect(route('admin.users.index'));
        
        // User should still be created
        $this->assertDatabaseHas('users', [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'role' => UserRole::CLIENT,
        ]);

        // Relationship should still be created
        $clientUser = User::where('email', 'test@example.com')->first();
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->adminUser->id,
        ]);
    }

    /** @test */
    public function employee_creation_handles_email_sending_failures_gracefully()
    {
        // Mock mail to throw exception
        Mail::shouldReceive('to')->andThrow(new \Exception('Mail server error'));
        
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'action' => 'create_and_invite'
        ]);

        // Should still redirect successfully
        $response->assertRedirect();
        
        // User should still be created
        $this->assertDatabaseHas('users', [
            'name' => 'Test Client',
            'email' => 'test@example.com',
            'role' => UserRole::CLIENT,
        ]);

        // Relationship should still be created
        $clientUser = User::where('email', 'test@example.com')->first();
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->employeeUser->id,
        ]);
    }
}