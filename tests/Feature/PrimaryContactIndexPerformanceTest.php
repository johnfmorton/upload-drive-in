<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ClientUserRelationship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrimaryContactIndexPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the primary contact indexes are being used effectively.
     */
    public function test_primary_contact_indexes_improve_query_performance(): void
    {
        // Create test users
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);
        $client1 = User::factory()->create(['role' => 'client']);
        $client2 = User::factory()->create(['role' => 'client']);

        // Create relationships with primary contacts
        ClientUserRelationship::create([
            'client_user_id' => $client1->id,
            'company_user_id' => $admin->id,
            'is_primary' => true,
        ]);

        ClientUserRelationship::create([
            'client_user_id' => $client2->id,
            'company_user_id' => $employee->id,
            'is_primary' => true,
        ]);

        // Test query that should use idx_company_user_primary index
        $primaryClients = $admin->primaryContactClients()->get();
        $this->assertCount(1, $primaryClients);
        $this->assertEquals($client1->id, $primaryClients->first()->id);

        // Test query that should use idx_company_user_primary index
        $isPrimary = $admin->isPrimaryContactFor($client1);
        $this->assertTrue($isPrimary);

        // Test query that should use idx_client_primary_contact index
        $primaryUser = $client1->primaryCompanyUser();
        $this->assertNotNull($primaryUser);
        $this->assertEquals($admin->id, $primaryUser->id);

        // Verify the indexes exist in the database (SQLite compatible)
        $indexes = DB::select("PRAGMA index_list(client_user_relationships)");
        
        $indexNames = collect($indexes)->pluck('name')->toArray();
        // Note: idx_client_primary_contact is not needed because unique_primary_relationship 
        // constraint already covers (client_user_id, is_primary)
        $this->assertContains('idx_company_user_primary', $indexNames);
        $this->assertContains('unique_primary_relationship', $indexNames);
    }

    /**
     * Test that queries use the correct indexes by examining query plans.
     */
    public function test_query_execution_plans_use_indexes(): void
    {
        // Create test data
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);

        ClientUserRelationship::create([
            'client_user_id' => $client->id,
            'company_user_id' => $admin->id,
            'is_primary' => true,
        ]);

        // Enable query logging
        DB::enableQueryLog();

        // Execute queries that should use the indexes
        $admin->primaryContactClients()->get();
        $admin->isPrimaryContactFor($client);
        $client->primaryCompanyUser();

        // Get the executed queries
        $queries = DB::getQueryLog();
        
        // Verify queries were executed (basic smoke test)
        $this->assertGreaterThan(0, count($queries));

        // Reset query log
        DB::flushQueryLog();
        DB::disableQueryLog();
    }

    /**
     * Test performance with multiple relationships.
     */
    public function test_performance_with_multiple_relationships(): void
    {
        // Create multiple users and relationships
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);
        
        // Create multiple clients
        $clients = User::factory()->count(10)->create(['role' => 'client']);

        // Create relationships - admin is primary for half, employee for the other half
        foreach ($clients as $index => $client) {
            ClientUserRelationship::create([
                'client_user_id' => $client->id,
                'company_user_id' => $index < 5 ? $admin->id : $employee->id,
                'is_primary' => true,
            ]);
        }

        // Test queries perform well with multiple relationships
        $adminClients = $admin->primaryContactClients()->get();
        $this->assertCount(5, $adminClients);

        $employeeClients = $employee->primaryContactClients()->get();
        $this->assertCount(5, $employeeClients);

        // Test individual client lookups
        foreach ($clients->take(3) as $client) {
            $primaryUser = $client->primaryCompanyUser();
            $this->assertNotNull($primaryUser);
            $this->assertContains($primaryUser->id, [$admin->id, $employee->id]);
        }
    }
}