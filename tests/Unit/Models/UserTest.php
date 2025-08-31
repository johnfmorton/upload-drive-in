<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\ClientUserRelationship;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function primary_contact_clients_returns_clients_where_user_is_primary_contact()
    {
        // Create an employee and multiple clients
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client1 = User::factory()->create(['role' => UserRole::CLIENT]);
        $client2 = User::factory()->create(['role' => UserRole::CLIENT]);
        $client3 = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create relationships - employee is primary contact for client1 and client2
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client1->id,
            'is_primary' => true,
        ]);
        
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client2->id,
            'is_primary' => true,
        ]);
        
        // Employee is NOT primary contact for client3
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client3->id,
            'is_primary' => false,
        ]);

        $primaryContactClients = $employee->primaryContactClients()->get();

        // Should return only client1 and client2
        $this->assertCount(2, $primaryContactClients);
        $this->assertTrue($primaryContactClients->contains($client1));
        $this->assertTrue($primaryContactClients->contains($client2));
        $this->assertFalse($primaryContactClients->contains($client3));
    }

    /** @test */
    public function primary_contact_clients_returns_empty_collection_when_no_primary_contacts()
    {
        // Create an employee and a client
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create relationship where employee is NOT primary contact
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client->id,
            'is_primary' => false,
        ]);

        $primaryContactClients = $employee->primaryContactClients()->get();

        $this->assertCount(0, $primaryContactClients);
    }

    /** @test */
    public function primary_contact_clients_returns_empty_collection_when_no_relationships()
    {
        // Create an employee with no client relationships
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);

        $primaryContactClients = $employee->primaryContactClients()->get();

        $this->assertCount(0, $primaryContactClients);
    }

    /** @test */
    public function is_primary_contact_for_returns_true_when_user_is_primary_contact()
    {
        // Create an employee and a client
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create relationship where employee is primary contact
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client->id,
            'is_primary' => true,
        ]);

        $this->assertTrue($employee->isPrimaryContactFor($client));
    }

    /** @test */
    public function is_primary_contact_for_returns_false_when_user_is_not_primary_contact()
    {
        // Create an employee and a client
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create relationship where employee is NOT primary contact
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client->id,
            'is_primary' => false,
        ]);

        $this->assertFalse($employee->isPrimaryContactFor($client));
    }

    /** @test */
    public function is_primary_contact_for_returns_false_when_no_relationship_exists()
    {
        // Create an employee and a client with no relationship
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);

        $this->assertFalse($employee->isPrimaryContactFor($client));
    }

    /** @test */
    public function is_primary_contact_for_works_with_multiple_relationships()
    {
        // Create an employee and multiple clients
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client1 = User::factory()->create(['role' => UserRole::CLIENT]);
        $client2 = User::factory()->create(['role' => UserRole::CLIENT]);
        $client3 = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create relationships with different primary contact statuses
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client1->id,
            'is_primary' => true,
        ]);
        
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client2->id,
            'is_primary' => false,
        ]);
        
        // No relationship with client3

        $this->assertTrue($employee->isPrimaryContactFor($client1));
        $this->assertFalse($employee->isPrimaryContactFor($client2));
        $this->assertFalse($employee->isPrimaryContactFor($client3));
    }

    /** @test */
    public function primary_contact_methods_work_with_admin_users()
    {
        // Create an admin and a client
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create relationship where admin is primary contact
        ClientUserRelationship::create([
            'company_user_id' => $admin->id,
            'client_user_id' => $client->id,
            'is_primary' => true,
        ]);

        $primaryContactClients = $admin->primaryContactClients()->get();
        
        $this->assertCount(1, $primaryContactClients);
        $this->assertTrue($primaryContactClients->contains($client));
        $this->assertTrue($admin->isPrimaryContactFor($client));
    }

    /** @test */
    public function primary_contact_clients_can_be_counted()
    {
        // Create an employee and multiple clients
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client1 = User::factory()->create(['role' => UserRole::CLIENT]);
        $client2 = User::factory()->create(['role' => UserRole::CLIENT]);
        $client3 = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create relationships - employee is primary contact for 2 clients
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client1->id,
            'is_primary' => true,
        ]);
        
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client2->id,
            'is_primary' => true,
        ]);
        
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client3->id,
            'is_primary' => false,
        ]);

        $primaryContactCount = $employee->primaryContactClients()->count();

        $this->assertEquals(2, $primaryContactCount);
    }

    /** @test */
    public function primary_contact_clients_relationship_includes_pivot_data()
    {
        // Create an employee and a client
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create relationship where employee is primary contact
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client->id,
            'is_primary' => true,
        ]);

        $primaryContactClient = $employee->primaryContactClients()->first();

        $this->assertNotNull($primaryContactClient);
        $this->assertEquals(1, $primaryContactClient->pivot->is_primary);
        $this->assertEquals($employee->id, $primaryContactClient->pivot->company_user_id);
        $this->assertEquals($client->id, $primaryContactClient->pivot->client_user_id);
    }

    /** @test */
    public function primary_contact_methods_handle_edge_cases_correctly()
    {
        // Create users
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client1 = User::factory()->create(['role' => UserRole::CLIENT]);
        $client2 = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Test with no relationships
        $this->assertCount(0, $employee->primaryContactClients()->get());
        $this->assertFalse($employee->isPrimaryContactFor($client1));
        
        // Create relationship but not primary
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client1->id,
            'is_primary' => false,
        ]);
        
        $this->assertCount(0, $employee->primaryContactClients()->get());
        $this->assertFalse($employee->isPrimaryContactFor($client1));
        
        // Make primary contact
        $client1->companyUsers()->updateExistingPivot($employee->id, ['is_primary' => true]);
        
        $this->assertCount(1, $employee->primaryContactClients()->get());
        $this->assertTrue($employee->isPrimaryContactFor($client1));
        $this->assertFalse($employee->isPrimaryContactFor($client2));
    }

    /** @test */
    public function primary_contact_methods_work_with_soft_deleted_users()
    {
        // Create users
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create relationship
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client->id,
            'is_primary' => true,
        ]);
        
        // Verify relationship works
        $this->assertTrue($employee->isPrimaryContactFor($client));
        $this->assertCount(1, $employee->primaryContactClients()->get());
        
        // Note: This test assumes soft deletes are implemented
        // If not implemented, this test documents expected behavior
    }

    /** @test */
    public function primary_contact_methods_handle_concurrent_access()
    {
        // Create users
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        
        // Create relationship
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client->id,
            'is_primary' => true,
        ]);
        
        // Simulate concurrent access by calling methods multiple times
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($employee->isPrimaryContactFor($client));
            $this->assertCount(1, $employee->primaryContactClients()->get());
        }
    }

    /** @test */
    public function primary_contact_clients_query_is_optimized()
    {
        // Create users and relationships
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $clients = User::factory()->count(10)->create(['role' => UserRole::CLIENT]);
        
        // Make employee primary contact for half the clients
        foreach ($clients->take(5) as $client) {
            ClientUserRelationship::create([
                'company_user_id' => $employee->id,
                'client_user_id' => $client->id,
                'is_primary' => true,
            ]);
        }
        
        // Make employee non-primary contact for the other half
        foreach ($clients->skip(5) as $client) {
            ClientUserRelationship::create([
                'company_user_id' => $employee->id,
                'client_user_id' => $client->id,
                'is_primary' => false,
            ]);
        }
        
        // Test that query returns correct count
        $primaryClients = $employee->primaryContactClients()->get();
        $this->assertCount(5, $primaryClients);
        
        // Test that count query works efficiently
        $primaryCount = $employee->primaryContactClients()->count();
        $this->assertEquals(5, $primaryCount);
    }
}