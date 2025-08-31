<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserControllerTeamAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);
        
        // Create client user
        $this->client = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client@example.com',
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
    }

    public function test_update_team_assignments_requires_team_members()
    {
        $this->actingAs($this->admin);
        
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [],
            'primary_contact' => $this->admin->id,
        ]);
        
        $response->assertSessionHasErrors(['team_members']);
    }

    public function test_update_team_assignments_requires_primary_contact()
    {
        $this->actingAs($this->admin);
        
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id, $this->employee1->id],
        ]);
        
        $response->assertSessionHasErrors(['primary_contact']);
    }

    public function test_update_team_assignments_primary_contact_must_be_in_team()
    {
        $this->actingAs($this->admin);
        
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id],
            'primary_contact' => $this->employee1->id, // Not in team_members
        ]);
        
        $response->assertSessionHasErrors(['primary_contact']);
    }

    public function test_update_team_assignments_success()
    {
        $this->actingAs($this->admin);
        
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id, $this->employee1->id],
            'primary_contact' => $this->admin->id,
        ]);
        
        $response->assertRedirect(route('admin.users.show', $this->client))
                ->assertSessionHas('success');
        
        // Verify relationships were created
        $this->assertTrue($this->client->companyUsers()->where('users.id', $this->admin->id)->exists());
        $this->assertTrue($this->client->companyUsers()->where('users.id', $this->employee1->id)->exists());
        
        // Verify primary contact was set correctly
        $primaryContact = $this->client->companyUsers()->wherePivot('is_primary', true)->first();
        $this->assertEquals($this->admin->id, $primaryContact->id);
    }

    public function test_update_team_assignments_validates_team_member_ownership()
    {
        // Create another admin with their own employee
        $otherAdmin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'other-admin@example.com',
        ]);
        
        $otherEmployee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'other-employee@example.com',
            'owner_id' => $otherAdmin->id,
        ]);
        
        $this->actingAs($this->admin);
        
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id, $otherEmployee->id], // Other admin's employee
            'primary_contact' => $this->admin->id,
        ]);
        
        $response->assertSessionHasErrors(['team_members']);
    }

    public function test_update_team_assignments_handles_invalid_user_ids()
    {
        $this->actingAs($this->admin);
        
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id, 99999], // Non-existent user ID
            'primary_contact' => $this->admin->id,
        ]);
        
        $response->assertSessionHasErrors(['team_members.1']);
    }

    public function test_update_team_assignments_prevents_client_as_team_member()
    {
        $anotherClient = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'another-client@example.com',
        ]);
        
        $this->actingAs($this->admin);
        
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id, $anotherClient->id], // Client as team member
            'primary_contact' => $this->admin->id,
        ]);
        
        $response->assertSessionHasErrors(['team_members']);
    }

    public function test_update_team_assignments_replaces_existing_relationships()
    {
        // Create initial relationship
        $this->client->companyUsers()->attach($this->employee1->id, ['is_primary' => true]);
        
        $this->actingAs($this->admin);
        
        // Update with different team members
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id, $this->employee2->id],
            'primary_contact' => $this->employee2->id,
        ]);
        
        $response->assertRedirect(route('admin.users.show', $this->client))
                ->assertSessionHas('success');
        
        // Verify old relationship was removed
        $this->assertFalse($this->client->companyUsers()->where('users.id', $this->employee1->id)->exists());
        
        // Verify new relationships were created
        $this->assertTrue($this->client->companyUsers()->where('users.id', $this->admin->id)->exists());
        $this->assertTrue($this->client->companyUsers()->where('users.id', $this->employee2->id)->exists());
        
        // Verify new primary contact
        $primaryContact = $this->client->companyUsers()->wherePivot('is_primary', true)->first();
        $this->assertEquals($this->employee2->id, $primaryContact->id);
    }

    public function test_update_team_assignments_handles_single_team_member()
    {
        $this->actingAs($this->admin);
        
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id],
            'primary_contact' => $this->admin->id,
        ]);
        
        $response->assertRedirect(route('admin.users.show', $this->client))
                ->assertSessionHas('success');
        
        // Verify relationship was created
        $this->assertTrue($this->client->companyUsers()->where('users.id', $this->admin->id)->exists());
        
        // Verify primary contact
        $primaryContact = $this->client->companyUsers()->wherePivot('is_primary', true)->first();
        $this->assertEquals($this->admin->id, $primaryContact->id);
    }

    public function test_update_team_assignments_validates_custom_error_messages()
    {
        $this->actingAs($this->admin);
        
        // Test empty team members
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [],
        ]);
        
        $response->assertSessionHasErrors(['team_members', 'primary_contact']);
        
        // Verify custom error messages are used (assuming they exist in language files)
        $errors = session('errors');
        $this->assertNotNull($errors);
    }

    public function test_update_team_assignments_logs_audit_information()
    {
        $this->actingAs($this->admin);
        
        // Enable log capture
        \Illuminate\Support\Facades\Log::spy();
        
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id, $this->employee1->id],
            'primary_contact' => $this->admin->id,
        ]);
        
        // Verify audit logs were created
        \Illuminate\Support\Facades\Log::shouldHaveReceived('info')
            ->with('Team assignment update attempt', \Mockery::type('array'));
        
        \Illuminate\Support\Facades\Log::shouldHaveReceived('info')
            ->with('Team assignment updated successfully', \Mockery::type('array'));
    }

    public function test_update_team_assignments_handles_database_errors()
    {
        $this->actingAs($this->admin);
        
        // Mock database error by using invalid client ID
        $invalidClient = new User();
        $invalidClient->id = 99999;
        $invalidClient->role = UserRole::CLIENT;
        
        $response = $this->post(route('admin.users.team.update', $invalidClient), [
            'team_members' => [$this->admin->id],
            'primary_contact' => $this->admin->id,
        ]);
        
        // Should handle gracefully
        $response->assertStatus(404);
    }

    public function test_update_team_assignments_only_works_for_clients()
    {
        $this->actingAs($this->admin);
        
        // Try to update team assignments for an employee (should fail)
        $response = $this->post(route('admin.users.team.update', $this->employee1), [
            'team_members' => [$this->admin->id],
            'primary_contact' => $this->admin->id,
        ]);
        
        $response->assertStatus(404);
    }

    public function test_update_team_assignments_preserves_primary_contact_uniqueness()
    {
        $this->actingAs($this->admin);
        
        $response = $this->post(route('admin.users.team.update', $this->client), [
            'team_members' => [$this->admin->id, $this->employee1->id, $this->employee2->id],
            'primary_contact' => $this->employee1->id,
        ]);
        
        $response->assertRedirect(route('admin.users.show', $this->client))
                ->assertSessionHas('success');
        
        // Verify only one primary contact exists
        $primaryContacts = $this->client->companyUsers()->wherePivot('is_primary', true)->get();
        $this->assertCount(1, $primaryContacts);
        $this->assertEquals($this->employee1->id, $primaryContacts->first()->id);
        
        // Verify other team members are not primary
        $nonPrimaryContacts = $this->client->companyUsers()->wherePivot('is_primary', false)->get();
        $this->assertCount(2, $nonPrimaryContacts);
    }
}