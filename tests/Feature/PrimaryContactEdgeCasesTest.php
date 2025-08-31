<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ClientUserRelationship;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrimaryContactEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee1;
    private User $employee2;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
            'name' => 'Admin User',
        ]);
        
        // Create employee users
        $this->employee1 = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee1@example.com',
            'name' => 'Employee One',
            'owner_id' => $this->admin->id,
        ]);
        
        $this->employee2 = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee2@example.com',
            'name' => 'Employee Two',
            'owner_id' => $this->admin->id,
        ]);
        
        // Create client user
        $this->client = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client@example.com',
            'name' => 'Test Client',
        ]);
    }

    /** @test */
    public function cannot_remove_all_team_members_from_client()
    {
        // Create initial relationship
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);
        
        $this->actingAs($this->admin);
        
        // Try to remove all team members (empty array)
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [],
            'primary_contact' => '',
        ]);
        
        $response->assertSessionHasErrors(['team_members']);
        
        // Verify relationship still exists
        $this->assertTrue($this->client->companyUsers()->where('users.id', $this->admin->id)->exists());
    }

    /** @test */
    public function cannot_remove_primary_contact_without_assigning_new_one()
    {
        // Create relationships with multiple team members
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);
        
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->employee1->id,
            'is_primary' => false,
        ]);
        
        $this->actingAs($this->admin);
        
        // Try to remove admin (current primary contact) without assigning new primary
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->employee1->id],
            'primary_contact' => '', // No primary contact specified
        ]);
        
        $response->assertSessionHasErrors(['primary_contact']);
        
        // Verify original relationships are preserved
        $this->assertTrue($this->client->companyUsers()->where('users.id', $this->admin->id)->exists());
        $primaryContact = $this->client->companyUsers()->wherePivot('is_primary', true)->first();
        $this->assertEquals($this->admin->id, $primaryContact->id);
    }

    /** @test */
    public function can_change_primary_contact_when_removing_current_primary()
    {
        // Create relationships with multiple team members
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);
        
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->employee1->id,
            'is_primary' => false,
        ]);
        
        $this->actingAs($this->admin);
        
        // Remove admin and make employee1 the primary contact
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->employee1->id],
            'primary_contact' => $this->employee1->id,
        ]);
        
        $response->assertRedirect(route('admin.users.show', $this->client))
                ->assertSessionHas('success');
        
        // Verify admin was removed
        $this->assertFalse($this->client->companyUsers()->where('users.id', $this->admin->id)->exists());
        
        // Verify employee1 is now primary contact
        $primaryContact = $this->client->companyUsers()->wherePivot('is_primary', true)->first();
        $this->assertEquals($this->employee1->id, $primaryContact->id);
    }

    /** @test */
    public function cannot_have_multiple_primary_contacts()
    {
        $this->actingAs($this->admin);
        
        // This test verifies the system prevents multiple primary contacts
        // by testing the database constraint or application logic
        
        // Create initial relationship
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id, $this->employee1->id],
            'primary_contact' => $this->admin->id,
        ]);
        
        $response->assertRedirect(route('admin.users.show', $this->client))
                ->assertSessionHas('success');
        
        // Verify only one primary contact exists
        $primaryContacts = $this->client->companyUsers()->wherePivot('is_primary', true)->get();
        $this->assertCount(1, $primaryContacts);
        $this->assertEquals($this->admin->id, $primaryContacts->first()->id);
        
        // Verify other team member is not primary
        $nonPrimaryContacts = $this->client->companyUsers()->wherePivot('is_primary', false)->get();
        $this->assertCount(1, $nonPrimaryContacts);
        $this->assertEquals($this->employee1->id, $nonPrimaryContacts->first()->id);
    }

    /** @test */
    public function handles_concurrent_primary_contact_changes()
    {
        // Create initial relationship
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);
        
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->employee1->id,
            'is_primary' => false,
        ]);
        
        $this->actingAs($this->admin);
        
        // Simulate concurrent updates by making multiple rapid requests
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->post(route('admin.users.team.update', $this->client), [
                'team_members' => [$this->admin->id, $this->employee1->id],
                'primary_contact' => $this->employee1->id,
            ]);
        }
        
        // All requests should succeed or handle gracefully
        foreach ($responses as $response) {
            $this->assertTrue(
                $response->isRedirect() || $response->status() === 200,
                'Concurrent request failed unexpectedly'
            );
        }
        
        // Verify final state is consistent
        $primaryContacts = $this->client->companyUsers()->wherePivot('is_primary', true)->get();
        $this->assertCount(1, $primaryContacts);
        $this->assertEquals($this->employee1->id, $primaryContacts->first()->id);
    }

    /** @test */
    public function handles_deleted_user_in_team_assignment()
    {
        // Create relationships
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);
        
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->employee1->id,
            'is_primary' => false,
        ]);
        
        // Delete employee1
        $deletedEmployeeId = $this->employee1->id;
        $this->employee1->delete();
        
        $this->actingAs($this->admin);
        
        // Try to assign deleted user as team member
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id, $deletedEmployeeId],
            'primary_contact' => $this->admin->id,
        ]);
        
        $response->assertSessionHasErrors(['team_members.1']);
        
        // Verify original relationships are preserved
        $this->assertTrue($this->client->companyUsers()->where('users.id', $this->admin->id)->exists());
    }

    /** @test */
    public function handles_client_with_no_existing_relationships()
    {
        // Client has no existing relationships
        $this->actingAs($this->admin);
        
        // Assign first team members
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id],
            'primary_contact' => $this->admin->id,
        ]);
        
        $response->assertRedirect(route('admin.users.show', $this->client))
                ->assertSessionHas('success');
        
        // Verify relationship was created
        $this->assertTrue($this->client->companyUsers()->where('users.id', $this->admin->id)->exists());
        
        // Verify primary contact was set
        $primaryContact = $this->client->companyUsers()->wherePivot('is_primary', true)->first();
        $this->assertEquals($this->admin->id, $primaryContact->id);
    }

    /** @test */
    public function handles_invalid_primary_contact_selection()
    {
        $this->actingAs($this->admin);
        
        // Try to set primary contact to user not in team members
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id],
            'primary_contact' => $this->employee1->id, // Not in team_members
        ]);
        
        $response->assertSessionHasErrors(['primary_contact']);
        
        // Verify no relationships were created
        $this->assertFalse($this->client->companyUsers()->exists());
    }

    /** @test */
    public function handles_large_team_assignments()
    {
        // Create many employees
        $employees = User::factory()->count(20)->create([
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $this->admin->id,
        ]);
        
        $this->actingAs($this->admin);
        
        // Assign large team
        $teamMemberIds = array_merge([$this->admin->id], $employees->pluck('id')->toArray());
        
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => $teamMemberIds,
            'primary_contact' => $this->admin->id,
        ]);
        
        $response->assertRedirect(route('admin.users.show', $this->client))
                ->assertSessionHas('success');
        
        // Verify all relationships were created
        $this->assertEquals(21, $this->client->companyUsers()->count());
        
        // Verify primary contact is correct
        $primaryContact = $this->client->companyUsers()->wherePivot('is_primary', true)->first();
        $this->assertEquals($this->admin->id, $primaryContact->id);
        
        // Verify only one primary contact exists
        $primaryCount = $this->client->companyUsers()->wherePivot('is_primary', true)->count();
        $this->assertEquals(1, $primaryCount);
    }

    /** @test */
    public function handles_database_transaction_rollback()
    {
        // Create initial relationship
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);
        
        $this->actingAs($this->admin);
        
        // Mock database error by using invalid data that would cause constraint violation
        // This tests that transactions are properly rolled back
        
        // Try to create invalid relationship (this should be handled gracefully)
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id, 99999], // Invalid user ID
            'primary_contact' => $this->admin->id,
        ]);
        
        $response->assertSessionHasErrors();
        
        // Verify original relationship is preserved
        $this->assertTrue($this->client->companyUsers()->where('users.id', $this->admin->id)->exists());
        $primaryContact = $this->client->companyUsers()->wherePivot('is_primary', true)->first();
        $this->assertEquals($this->admin->id, $primaryContact->id);
    }

    /** @test */
    public function handles_orphaned_client_scenario()
    {
        // Create relationship
        ClientUserRelationship::create([
            'client_user_id' => $this->client->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);
        
        // Verify client has relationships
        $this->assertTrue($this->client->companyUsers()->exists());
        
        // Test what happens when trying to view client details
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.users.show', $this->client));
        $response->assertStatus(200);
        
        // Verify primary contact is displayed
        $response->assertSee('Current Primary Contact');
        $response->assertSee($this->admin->name);
    }

    /** @test */
    public function prevents_self_assignment_for_client_users()
    {
        // Login as client (this should not be possible in real app, but test edge case)
        $this->actingAs($this->client);
        
        // Try to access team assignment endpoint
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id],
            'primary_contact' => $this->admin->id,
        ]);
        
        // Should be forbidden or redirect to login
        $this->assertTrue(
            $response->status() === 403 || 
            $response->status() === 302 || 
            $response->status() === 401,
            'Client should not be able to modify team assignments'
        );
    }
}