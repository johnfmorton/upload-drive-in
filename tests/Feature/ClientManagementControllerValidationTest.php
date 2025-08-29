<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementControllerValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_parameter_is_required(): void
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
            // Missing action parameter
        ];

        $response = $this->actingAs($employee)
            ->post(route('employee.clients.store', ['username' => $employee->username]), $clientData);

        $response->assertSessionHasErrors(['action']);
    }

    public function test_action_parameter_must_be_valid(): void
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
            'action' => 'invalid_action',
        ];

        $response = $this->actingAs($employee)
            ->post(route('employee.clients.store', ['username' => $employee->username]), $clientData);

        $response->assertSessionHasErrors(['action']);
    }
}