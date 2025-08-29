<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_login_with_password(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'password' => bcrypt('password123'),
        ]);

        $this->assertTrue($employee->canLoginWithPassword());
    }

    public function test_employee_can_access_client_management_page(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $admin->id,
            'username' => 'testemployee',
        ]);

        $response = $this->actingAs($employee)
            ->get(route('employee.clients.index', ['username' => $employee->username]));

        $response->assertStatus(200);
        $response->assertViewIs('employee.client-management.index');
    }

    public function test_employee_can_create_client_user_without_invitation(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $admin->id,
            'username' => 'testemployee',
        ]);

        $clientData = [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create',
        ];

        $response = $this->actingAs($employee)
            ->post(route('employee.clients.store', ['username' => $employee->username]), $clientData);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'client-created');

        // Verify client user was created
        $this->assertDatabaseHas('users', [
            'email' => 'client@example.com',
            'role' => UserRole::CLIENT->value,
        ]);

        // Verify relationship was created
        $clientUser = User::where('email', 'client@example.com')->first();
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $employee->id,
        ]);
    }

    public function test_employee_can_create_client_user_with_invitation(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $admin->id,
            'username' => 'testemployee',
        ]);

        $clientData = [
            'name' => 'Test Client',
            'email' => 'client2@example.com',
            'action' => 'create_and_invite',
        ];

        $response = $this->actingAs($employee)
            ->post(route('employee.clients.store', ['username' => $employee->username]), $clientData);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'client-created-and-invited');

        // Verify client user was created
        $this->assertDatabaseHas('users', [
            'email' => 'client2@example.com',
            'role' => UserRole::CLIENT->value,
        ]);

        // Verify relationship was created
        $clientUser = User::where('email', 'client2@example.com')->first();
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $employee->id,
        ]);
    }
}