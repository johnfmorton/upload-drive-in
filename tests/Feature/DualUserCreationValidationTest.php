<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DualUserCreationValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        
        $this->employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $this->admin->id,
        ]);
    }

    public function test_admin_validation_requires_name_field()
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'email' => 'test@example.com',
            'action' => 'create',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertStringContainsString('name field is required', $response->getSession()->get('errors')->first('name'));
    }

    public function test_admin_validation_requires_email_field()
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Test User',
            'action' => 'create',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('email field is required', $response->getSession()->get('errors')->first('email'));
    }

    public function test_admin_validation_requires_action_field()
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors(['action']);
        $this->assertStringContainsString('select an action', $response->getSession()->get('errors')->first('action'));
    }

    public function test_admin_validation_validates_email_format()
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'action' => 'create',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('valid email address', $response->getSession()->get('errors')->first('email'));
    }

    public function test_admin_validation_validates_action_values()
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'action' => 'invalid_action',
        ]);

        $response->assertSessionHasErrors(['action']);
        $this->assertStringContainsString('selected action is invalid', $response->getSession()->get('errors')->first('action'));
    }

    public function test_admin_validation_validates_name_length()
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => str_repeat('a', 256), // 256 characters, exceeds max of 255
            'email' => 'test@example.com',
            'action' => 'create',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertStringContainsString('may not be greater than 255 characters', $response->getSession()->get('errors')->first('name'));
    }

    public function test_employee_validation_requires_name_field()
    {
        // Set username for the employee
        $this->employee->username = 'testemployee';
        $this->employee->save();
        
        $response = $this->actingAs($this->employee)->post("/employee/{$this->employee->username}/clients", [
            'email' => 'test@example.com',
            'action' => 'create',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertStringContainsString('name field is required', $response->getSession()->get('errors')->first('name'));
    }

    public function test_employee_validation_requires_email_field()
    {
        // Set username for the employee
        $this->employee->username = 'testemployee';
        $this->employee->save();
        
        $response = $this->actingAs($this->employee)->post("/employee/{$this->employee->username}/clients", [
            'name' => 'Test User',
            'action' => 'create',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('email field is required', $response->getSession()->get('errors')->first('email'));
    }

    public function test_employee_validation_requires_action_field()
    {
        // Set username for the employee
        $this->employee->username = 'testemployee';
        $this->employee->save();
        
        $response = $this->actingAs($this->employee)->post("/employee/{$this->employee->username}/clients", [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors(['action']);
        $this->assertStringContainsString('select an action', $response->getSession()->get('errors')->first('action'));
    }

    public function test_employee_validation_validates_email_format()
    {
        // Set username for the employee
        $this->employee->username = 'testemployee';
        $this->employee->save();
        
        $response = $this->actingAs($this->employee)->post("/employee/{$this->employee->username}/clients", [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'action' => 'create',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('valid email address', $response->getSession()->get('errors')->first('email'));
    }

    public function test_admin_successful_user_creation_without_invitation()
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'action' => 'create',
        ]);

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_employee_successful_user_creation_without_invitation()
    {
        // Set username for the employee
        $this->employee->username = 'testemployee';
        $this->employee->save();
        
        $response = $this->actingAs($this->employee)->post("/employee/{$this->employee->username}/clients", [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'action' => 'create',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'employee-client-created');
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_admin_successful_user_creation_with_invitation()
    {
        Mail::fake();
        
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'action' => 'create_and_invite',
        ]);

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
        
        Mail::assertSent(\App\Mail\ClientVerificationMail::class);
    }

    public function test_employee_successful_user_creation_with_invitation()
    {
        Mail::fake();
        
        // Set username for the employee
        $this->employee->username = 'testemployee';
        $this->employee->save();
        
        $response = $this->actingAs($this->employee)->post("/employee/{$this->employee->username}/clients", [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'action' => 'create_and_invite',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'employee-client-created-and-invited');
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
        
        Mail::assertSent(\App\Mail\ClientVerificationMail::class);
    }
}