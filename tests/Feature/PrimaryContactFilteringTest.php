<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrimaryContactFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_clients_by_primary_contact_status()
    {
        // Create admin user
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);

        // Create client users
        $client1 = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client1@example.com',
            'name' => 'Client One',
        ]);

        $client2 = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client2@example.com',
            'name' => 'Client Two',
        ]);

        // Associate clients with admin - client1 as primary contact, client2 as regular
        $client1->companyUsers()->attach($admin->id, ['is_primary' => true]);
        $client2->companyUsers()->attach($admin->id, ['is_primary' => false]);

        // Test all clients view
        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertStatus(200);
        $response->assertSee('Client One');
        $response->assertSee('Client Two');

        // Test primary contact filter
        $response = $this->actingAs($admin)->get(route('admin.users.index', ['filter' => 'primary_contact']));
        $response->assertStatus(200);
        $response->assertSee('Client One');
        $response->assertDontSee('Client Two');
    }

    public function test_employee_can_filter_clients_by_primary_contact_status()
    {
        // Create admin and employee
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee@example.com',
            'username' => 'employee',
            'owner_id' => $admin->id,
        ]);

        // Create client users
        $client1 = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client1@example.com',
            'name' => 'Client One',
        ]);

        $client2 = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client2@example.com',
            'name' => 'Client Two',
        ]);

        // Associate clients with employee - client1 as primary contact, client2 as regular
        $client1->companyUsers()->attach($employee->id, ['is_primary' => true]);
        $client2->companyUsers()->attach($employee->id, ['is_primary' => false]);

        // Test all clients view
        $response = $this->actingAs($employee)->get(route('employee.clients.index', ['username' => 'employee']));
        $response->assertStatus(200);
        $response->assertSee('Client One');
        $response->assertSee('Client Two');

        // Test primary contact filter
        $response = $this->actingAs($employee)->get(route('employee.clients.index', [
            'username' => 'employee',
            'filter' => 'primary_contact'
        ]));
        $response->assertStatus(200);
        $response->assertSee('Client One');
        $response->assertDontSee('Client Two');
    }

    public function test_primary_contact_indicators_are_displayed()
    {
        // Create admin user
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);

        // Create client user
        $client = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client@example.com',
            'name' => 'Test Client',
        ]);

        // Associate client with admin as primary contact
        $client->companyUsers()->attach($admin->id, ['is_primary' => true]);

        // Test that primary contact indicator is shown
        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertStatus(200);
        
        // Check that the client data includes primary contact status
        $clients = $response->viewData('clients');
        $clientData = $clients->first();
        $this->assertTrue($clientData->is_primary_contact_for_current_user);
    }

    public function test_filtering_works_with_mixed_primary_contact_statuses()
    {
        // Create admin and employee
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee@example.com',
            'owner_id' => $admin->id,
        ]);

        // Create multiple clients
        $clients = User::factory()->count(5)->create(['role' => UserRole::CLIENT]);

        // Admin is primary contact for clients 0, 1
        $clients[0]->companyUsers()->attach($admin->id, ['is_primary' => true]);
        $clients[1]->companyUsers()->attach($admin->id, ['is_primary' => true]);

        // Admin is non-primary contact for client 2
        $clients[2]->companyUsers()->attach($admin->id, ['is_primary' => false]);

        // Employee is primary contact for client 3
        $clients[3]->companyUsers()->attach($employee->id, ['is_primary' => true]);

        // Client 4 has no relationship with admin

        $this->actingAs($admin);

        // Test unfiltered view shows all clients (no filtering applied)
        $response = $this->get(route('admin.users.index'));
        $response->assertStatus(200);
        
        // Verify all clients are shown in unfiltered view
        $viewClients = $response->viewData('clients');
        $this->assertGreaterThanOrEqual(5, $viewClients->total()); // Should have at least our 5 test clients

        // Test primary contact filter shows only clients 0, 1
        $response = $this->get(route('admin.users.index', ['filter' => 'primary_contact']));
        $response->assertStatus(200);
        $response->assertSee($clients[0]->name);
        $response->assertSee($clients[1]->name);
        $response->assertDontSee($clients[2]->name); // Non-primary
        $response->assertDontSee($clients[3]->name); // Employee's client, admin not primary
        $response->assertDontSee($clients[4]->name); // No relationship with admin
    }

    public function test_filtering_handles_empty_results()
    {
        // Create admin with no primary contact relationships
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);

        $client = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client@example.com',
            'name' => 'Test Client',
        ]);

        // Create non-primary relationship
        $client->companyUsers()->attach($admin->id, ['is_primary' => false]);

        $this->actingAs($admin);

        // Test primary contact filter shows no results
        $response = $this->get(route('admin.users.index', ['filter' => 'primary_contact']));
        $response->assertStatus(200);
        $response->assertDontSee($client->name);

        // Verify empty state message or appropriate handling
        $clients = $response->viewData('clients');
        $this->assertCount(0, $clients);
    }

    public function test_filtering_preserves_other_query_parameters()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);

        $client = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client@example.com',
            'name' => 'Test Client',
        ]);

        $client->companyUsers()->attach($admin->id, ['is_primary' => true]);

        $this->actingAs($admin);

        // Test with multiple query parameters
        $response = $this->get(route('admin.users.index', [
            'filter' => 'primary_contact',
            'search' => 'Test',
            'sort' => 'name'
        ]));

        $response->assertStatus(200);
        
        // Verify parameters are preserved in pagination links or form actions
        $content = $response->getContent();
        $this->assertStringContainsString('filter=primary_contact', $content);
    }

    public function test_employee_filtering_respects_ownership()
    {
        // Create two separate admin organizations
        $admin1 = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin1@example.com',
        ]);

        $admin2 = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin2@example.com',
        ]);

        $employee1 = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee1@example.com',
            'username' => 'employee1',
            'owner_id' => $admin1->id,
        ]);

        $employee2 = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee2@example.com',
            'username' => 'employee2',
            'owner_id' => $admin2->id,
        ]);

        // Create clients for each organization
        $client1 = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client1@example.com',
            'name' => 'Client One',
        ]);

        $client2 = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client2@example.com',
            'name' => 'Client Two',
        ]);

        // Employee1 is primary contact for client1
        $client1->companyUsers()->attach($employee1->id, ['is_primary' => true]);

        // Employee2 is primary contact for client2
        $client2->companyUsers()->attach($employee2->id, ['is_primary' => true]);

        // Test employee1 can only see their primary contact clients
        $this->actingAs($employee1);

        $response = $this->get(route('employee.clients.index', [
            'username' => 'employee1',
            'filter' => 'primary_contact'
        ]));

        $response->assertStatus(200);
        $response->assertSee('Client One');
        $response->assertDontSee('Client Two'); // Different organization
    }

    public function test_filtering_performance_with_large_datasets()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);

        // Create many clients
        $clients = User::factory()->count(100)->create(['role' => UserRole::CLIENT]);

        // Make admin primary contact for half of them
        foreach ($clients->take(50) as $client) {
            $client->companyUsers()->attach($admin->id, ['is_primary' => true]);
        }

        // Make admin non-primary contact for the other half
        foreach ($clients->skip(50) as $client) {
            $client->companyUsers()->attach($admin->id, ['is_primary' => false]);
        }

        $this->actingAs($admin);

        // Measure query performance
        $startTime = microtime(true);

        $response = $this->get(route('admin.users.index', ['filter' => 'primary_contact']));

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);

        // Verify correct number of results (limited by pagination)
        $clients = $response->viewData('clients');
        $this->assertLessThanOrEqual(10, $clients->count()); // Default pagination limit
        $this->assertEquals(50, $clients->total()); // Total count should be 50

        // Verify reasonable performance (less than 1 second)
        $this->assertLessThan(1.0, $executionTime, 'Primary contact filtering took too long');
    }

    public function test_filtering_works_with_pagination()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);

        // Create enough clients to trigger pagination
        $clients = User::factory()->count(30)->create(['role' => UserRole::CLIENT]);

        // Make admin primary contact for all of them
        foreach ($clients as $client) {
            $client->companyUsers()->attach($admin->id, ['is_primary' => true]);
        }

        $this->actingAs($admin);

        // Test first page
        $response = $this->get(route('admin.users.index', [
            'filter' => 'primary_contact',
            'page' => 1
        ]));

        $response->assertStatus(200);

        // Verify pagination links preserve filter
        $content = $response->getContent();
        $this->assertStringContainsString('filter=primary_contact', $content);

        // Test second page if pagination is configured
        $response = $this->get(route('admin.users.index', [
            'filter' => 'primary_contact',
            'page' => 2
        ]));

        $response->assertStatus(200);
    }

    public function test_filter_parameter_validation()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin);

        // Test with invalid filter value
        $response = $this->get(route('admin.users.index', ['filter' => 'invalid_filter']));

        $response->assertStatus(200); // Should handle gracefully

        // Test with malicious filter value
        $response = $this->get(route('admin.users.index', ['filter' => '<script>alert("xss")</script>']));

        $response->assertStatus(200); // Should handle gracefully and sanitize
    }
}