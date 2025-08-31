<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ClientUserRelationship;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseConstraintTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_create_multiple_non_primary_relationships_for_same_client()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $employee1 = User::factory()->create(['role' => UserRole::EMPLOYEE, 'owner_id' => $admin->id]);
        $employee2 = User::factory()->create(['role' => UserRole::EMPLOYEE, 'owner_id' => $admin->id]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);

        // Create first relationship (primary)
        ClientUserRelationship::create([
            'client_user_id' => $client->id,
            'company_user_id' => $admin->id,
            'is_primary' => true,
        ]);

        // Create second relationship (non-primary)
        ClientUserRelationship::create([
            'client_user_id' => $client->id,
            'company_user_id' => $employee1->id,
            'is_primary' => false,
        ]);

        // Create third relationship (non-primary)
        ClientUserRelationship::create([
            'client_user_id' => $client->id,
            'company_user_id' => $employee2->id,
            'is_primary' => false,
        ]);

        // Verify all relationships exist
        $this->assertEquals(3, $client->companyUsers()->count());
        $this->assertEquals(1, $client->companyUsers()->wherePivot('is_primary', true)->count());
        $this->assertEquals(2, $client->companyUsers()->wherePivot('is_primary', false)->count());
    }

    /** @test */
    public function cannot_create_multiple_primary_relationships_for_same_client()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE, 'owner_id' => $admin->id]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);

        // Create first primary relationship
        ClientUserRelationship::create([
            'client_user_id' => $client->id,
            'company_user_id' => $admin->id,
            'is_primary' => true,
        ]);

        // Try to create second primary relationship (should fail)
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        ClientUserRelationship::create([
            'client_user_id' => $client->id,
            'company_user_id' => $employee->id,
            'is_primary' => true,
        ]);
    }
}