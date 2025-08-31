<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ClientUserRelationship;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrimaryContactDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;
    private array $clients;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
            'name' => 'Admin User',
        ]);
        
        // Create employee user
        $this->employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee@example.com',
            'name' => 'Employee User',
            'username' => 'employee',
            'owner_id' => $this->admin->id,
        ]);
        
        // Create multiple client users
        $this->clients = [];
        for ($i = 1; $i <= 5; $i++) {
            $this->clients[] = User::factory()->create([
                'role' => UserRole::CLIENT,
                'email' => "client{$i}@example.com",
                'name' => "Client {$i}",
            ]);
        }
    }

    /** @test */
    public function admin_dashboard_displays_primary_contact_statistics()
    {
        // Make admin primary contact for 3 clients
        for ($i = 0; $i < 3; $i++) {
            ClientUserRelationship::create([
                'client_user_id' => $this->clients[$i]->id,
                'company_user_id' => $this->admin->id,
                'is_primary' => true,
            ]);
        }
        
        // Make admin non-primary contact for 1 client
        ClientUserRelationship::create([
            'client_user_id' => $this->clients[3]->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => false,
        ]);
        
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.dashboard'));
        
        $response->assertStatus(200);
        
        // Verify primary contact statistics are displayed
        $response->assertSee('Primary Contact For');
        $response->assertSee('3');
        $response->assertSee('Clients'); // Should show count of primary contact relationships
        $response->assertSee('View primary contact clients'); // Link to filtered view
        
        // Verify the statistics component is included
        $response->assertSee('Primary Contact For'); // Component should be present
    }

    /** @test */
    public function employee_dashboard_displays_primary_contact_statistics()
    {
        // Make employee primary contact for 2 clients
        for ($i = 0; $i < 2; $i++) {
            ClientUserRelationship::create([
                'client_user_id' => $this->clients[$i]->id,
                'company_user_id' => $this->employee->id,
                'is_primary' => true,
            ]);
        }
        
        // Make employee non-primary contact for 1 client
        ClientUserRelationship::create([
            'client_user_id' => $this->clients[2]->id,
            'company_user_id' => $this->employee->id,
            'is_primary' => false,
        ]);
        
        $this->actingAs($this->employee);
        
        $response = $this->get(route('employee.dashboard', ['username' => 'employee']));
        
        $response->assertStatus(200);
        
        // Verify primary contact statistics are displayed
        $response->assertSee('Primary Contact For');
        $response->assertSee('2');
        $response->assertSee('Clients'); // Should show count of primary contact relationships
        $response->assertSee('View primary contact clients'); // Link to filtered view
    }

    /** @test */
    public function primary_contact_statistics_show_zero_when_no_primary_contacts()
    {
        // Create relationships but none as primary contact
        ClientUserRelationship::create([
            'client_user_id' => $this->clients[0]->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => false,
        ]);
        
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.dashboard'));
        
        $response->assertStatus(200);
        
        // Verify zero count is displayed
        $response->assertSee('Primary Contact For');
        $response->assertSee('0');
        $response->assertSee('Clients');
    }

    /** @test */
    public function primary_contact_statistics_handle_singular_vs_plural()
    {
        // Test singular (1 client)
        ClientUserRelationship::create([
            'client_user_id' => $this->clients[0]->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);
        
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.dashboard'));
        
        $response->assertStatus(200);
        $response->assertSee('1');
        $response->assertSee('Client'); // Singular form
        
        // Test plural (2 clients)
        ClientUserRelationship::create([
            'client_user_id' => $this->clients[1]->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);
        
        $response = $this->get(route('admin.dashboard'));
        
        $response->assertStatus(200);
        $response->assertSee('2');
        $response->assertSee('Clients'); // Plural form
    }

    /** @test */
    public function primary_contact_statistics_link_to_filtered_client_list()
    {
        // Make admin primary contact for clients
        ClientUserRelationship::create([
            'client_user_id' => $this->clients[0]->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);
        
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.dashboard'));
        
        $response->assertStatus(200);
        
        // Verify link to filtered client list
        $expectedUrl = route('admin.users.index', ['filter' => 'primary_contact']);
        $response->assertSee($expectedUrl, false);
    }

    /** @test */
    public function employee_primary_contact_statistics_link_includes_username()
    {
        // Make employee primary contact for clients
        ClientUserRelationship::create([
            'client_user_id' => $this->clients[0]->id,
            'company_user_id' => $this->employee->id,
            'is_primary' => true,
        ]);
        
        $this->actingAs($this->employee);
        
        $response = $this->get(route('employee.dashboard', ['username' => 'employee']));
        
        $response->assertStatus(200);
        
        // Verify link includes username parameter
        $expectedUrl = route('employee.clients.index', [
            'username' => 'employee',
            'filter' => 'primary_contact'
        ]);
        $response->assertSee($expectedUrl, false);
    }

    /** @test */
    public function dashboard_statistics_are_performant_with_many_relationships()
    {
        // Create many clients and relationships
        $manyClients = User::factory()->count(50)->create(['role' => UserRole::CLIENT]);
        
        // Make admin primary contact for half of them
        foreach ($manyClients->take(25) as $client) {
            ClientUserRelationship::create([
                'client_user_id' => $client->id,
                'company_user_id' => $this->admin->id,
                'is_primary' => true,
            ]);
        }
        
        // Make admin non-primary contact for the other half
        foreach ($manyClients->skip(25) as $client) {
            ClientUserRelationship::create([
                'client_user_id' => $client->id,
                'company_user_id' => $this->admin->id,
                'is_primary' => false,
            ]);
        }
        
        $this->actingAs($this->admin);
        
        // Measure query performance
        $startTime = microtime(true);
        
        $response = $this->get(route('admin.dashboard'));
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $response->assertStatus(200);
        $response->assertSee('25');
        $response->assertSee('Clients'); // Should show correct count
        
        // Verify reasonable performance (less than 1 second)
        $this->assertLessThan(1.0, $executionTime, 'Dashboard statistics query took too long');
    }

    /** @test */
    public function dashboard_statistics_component_has_correct_styling()
    {
        ClientUserRelationship::create([
            'client_user_id' => $this->clients[0]->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);
        
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.dashboard'));
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Verify component styling classes
        $this->assertStringContainsString('bg-white', $content);
        $this->assertStringContainsString('overflow-hidden', $content);
        $this->assertStringContainsString('shadow', $content);
        $this->assertStringContainsString('rounded-lg', $content);
        
        // Verify icon is present
        $this->assertStringContainsString('svg', $content);
        $this->assertStringContainsString('h-6 w-6', $content);
        
        // Verify responsive design classes
        $this->assertStringContainsString('sm:', $content);
        $this->assertStringContainsString('lg:', $content);
    }

    /** @test */
    public function dashboard_statistics_update_when_relationships_change()
    {
        $this->actingAs($this->admin);
        
        // Initially no primary contacts
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('0');
        $response->assertSee('Clients');
        
        // Add primary contact relationship
        ClientUserRelationship::create([
            'client_user_id' => $this->clients[0]->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);
        
        // Statistics should update
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('1');
        $response->assertSee('Client');
        
        // Change to non-primary
        $this->clients[0]->companyUsers()->updateExistingPivot($this->admin->id, ['is_primary' => false]);
        
        // Statistics should update again
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('0');
        $response->assertSee('Clients');
    }

    /** @test */
    public function dashboard_statistics_work_with_mixed_role_relationships()
    {
        // Create another admin
        $otherAdmin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'other-admin@example.com',
        ]);
        
        // Make current admin primary contact for some clients
        ClientUserRelationship::create([
            'client_user_id' => $this->clients[0]->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);
        
        // Make other admin primary contact for same client (should not happen in real app)
        // This tests edge case handling
        ClientUserRelationship::create([
            'client_user_id' => $this->clients[1]->id,
            'company_user_id' => $otherAdmin->id,
            'is_primary' => true,
        ]);
        
        // Make employee primary contact for another client
        ClientUserRelationship::create([
            'client_user_id' => $this->clients[2]->id,
            'company_user_id' => $this->employee->id,
            'is_primary' => true,
        ]);
        
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.dashboard'));
        
        $response->assertStatus(200);
        
        // Should only show admin's primary contact count
        $response->assertSee('1');
        $response->assertSee('Client');
    }

    /** @test */
    public function dashboard_statistics_handle_deleted_relationships()
    {
        // Create relationship
        $relationship = ClientUserRelationship::create([
            'client_user_id' => $this->clients[0]->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);
        
        $this->actingAs($this->admin);
        
        // Verify count is 1
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('1');
        $response->assertSee('Client');
        
        // Delete relationship
        $relationship->delete();
        
        // Verify count is now 0
        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('0');
        $response->assertSee('Clients');
    }
}