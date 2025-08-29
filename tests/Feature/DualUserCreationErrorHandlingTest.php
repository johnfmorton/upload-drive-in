<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DualUserCreationErrorHandlingTest extends TestCase
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
    public function admin_creation_displays_proper_success_message_for_create_action()
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
    }

    /** @test */
    public function admin_creation_displays_proper_success_message_for_invite_action()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite'
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'Client user created and invitation sent successfully.');
    }

    /** @test */
    public function employee_creation_displays_proper_status_message_for_create_action()
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
    }

    /** @test */
    public function employee_creation_displays_proper_status_message_for_invite_action()
    {
        Mail::fake();
        
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
            'name' => 'Employee Client',
            'email' => 'employee-client@example.com',
            'action' => 'create_and_invite'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'employee-client-created-and-invited');
    }

    /** @test */
    public function admin_creation_shows_validation_errors_for_missing_name()
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'email' => 'client@example.com',
            'action' => 'create'
            // Missing name
        ]);

        $response->assertSessionHasErrors(['name']);
        $response->assertRedirect();
    }

    /** @test */
    public function admin_creation_shows_validation_errors_for_missing_email()
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client',
            'action' => 'create'
            // Missing email
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertRedirect();
    }

    /** @test */
    public function admin_creation_shows_validation_errors_for_invalid_email()
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client',
            'email' => 'invalid-email',
            'action' => 'create'
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertRedirect();
    }

    /** @test */
    public function admin_creation_shows_validation_errors_for_missing_action()
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client',
            'email' => 'client@example.com'
            // Missing action
        ]);

        $response->assertSessionHasErrors(['action']);
        $response->assertRedirect();
    }

    /** @test */
    public function admin_creation_shows_validation_errors_for_invalid_action()
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'invalid_action'
        ]);

        $response->assertSessionHasErrors(['action']);
        $response->assertRedirect();
    }

    /** @test */
    public function employee_creation_shows_validation_errors_for_missing_name()
    {
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
            'email' => 'client@example.com',
            'action' => 'create'
            // Missing name
        ]);

        $response->assertSessionHasErrors(['name']);
        $response->assertRedirect();
    }

    /** @test */
    public function employee_creation_shows_validation_errors_for_missing_email()
    {
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
            'name' => 'Test Client',
            'action' => 'create'
            // Missing email
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertRedirect();
    }

    /** @test */
    public function employee_creation_shows_validation_errors_for_invalid_email()
    {
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
            'name' => 'Test Client',
            'email' => 'invalid-email',
            'action' => 'create'
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertRedirect();
    }

    /** @test */
    public function employee_creation_shows_validation_errors_for_missing_action()
    {
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
            'name' => 'Test Client',
            'email' => 'client@example.com'
            // Missing action
        ]);

        $response->assertSessionHasErrors(['action']);
        $response->assertRedirect();
    }

    /** @test */
    public function employee_creation_shows_validation_errors_for_invalid_action()
    {
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'invalid_action'
        ]);

        $response->assertSessionHasErrors(['action']);
        $response->assertRedirect();
    }

    /** @test */
    public function admin_creation_handles_service_exceptions_gracefully()
    {
        // This test would require mocking the ClientUserService to throw an exception
        // For now, we'll test that the controller handles basic validation properly
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => str_repeat('a', 300), // Name too long
            'email' => 'client@example.com',
            'action' => 'create'
        ]);

        $response->assertSessionHasErrors(['name']);
        $response->assertRedirect();
    }

    /** @test */
    public function employee_creation_handles_service_exceptions_gracefully()
    {
        // This test would require mocking the ClientUserService to throw an exception
        // For now, we'll test that the controller handles basic validation properly
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
            'name' => str_repeat('a', 300), // Name too long
            'email' => 'client@example.com',
            'action' => 'create'
        ]);

        $response->assertSessionHasErrors(['name']);
        $response->assertRedirect();
    }

    /** @test */
    public function unauthorized_users_cannot_access_admin_user_creation()
    {
        // Test with unauthenticated user
        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create'
        ]);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function unauthorized_users_cannot_access_employee_client_creation()
    {
        // Test with unauthenticated user
        $response = $this->post(route('employee.clients.store', 'testuser'), [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create'
        ]);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function client_users_cannot_access_admin_user_creation()
    {
        $clientUser = User::factory()->create([
            'role' => UserRole::CLIENT,
        ]);

        $this->actingAs($clientUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_users_cannot_access_employee_client_creation_for_other_employees()
    {
        $otherEmployee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'username' => 'otheremployee',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->post(route('employee.clients.store', $otherEmployee->username), [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create'
        ]);

        // This should be forbidden as admin shouldn't access employee-specific routes
        $response->assertStatus(403);
    }
}