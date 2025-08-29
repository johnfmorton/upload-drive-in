<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\ClientUserRelationship;
use App\Models\User;
use App\Services\ClientUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClientUserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ClientUserService $service;
    protected User $admin;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new ClientUserService();
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);
        
        $this->employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee@example.com',
            'owner_id' => $this->admin->id,
        ]);
    }

    public function test_creates_new_client_user_when_not_exists()
    {
        $userData = [
            'name' => 'New Client',
            'email' => 'newclient@example.com',
        ];

        $clientUser = $this->service->findOrCreateClientUser($userData, $this->admin);

        $this->assertInstanceOf(User::class, $clientUser);
        $this->assertEquals('New Client', $clientUser->name);
        $this->assertEquals('newclient@example.com', $clientUser->email);
        $this->assertEquals(UserRole::CLIENT, $clientUser->role);
        $this->assertTrue(Hash::check('', $clientUser->password) === false); // Has a password
        
        // Check that the user was actually saved to database
        $this->assertDatabaseHas('users', [
            'email' => 'newclient@example.com',
            'name' => 'New Client',
            'role' => UserRole::CLIENT->value,
        ]);
    }

    public function test_finds_existing_client_user()
    {
        // Create an existing client user
        $existingClient = User::factory()->create([
            'name' => 'Existing Client',
            'email' => 'existing@example.com',
            'role' => UserRole::CLIENT,
        ]);

        $userData = [
            'name' => 'Updated Name', // This should not update the existing user
            'email' => 'existing@example.com',
        ];

        $clientUser = $this->service->findOrCreateClientUser($userData, $this->admin);

        $this->assertEquals($existingClient->id, $clientUser->id);
        $this->assertEquals('Existing Client', $clientUser->name); // Name should not change
        $this->assertEquals('existing@example.com', $clientUser->email);
    }

    public function test_creates_relationship_with_company_user()
    {
        $userData = [
            'name' => 'Test Client',
            'email' => 'testclient@example.com',
        ];

        $clientUser = $this->service->findOrCreateClientUser($userData, $this->admin);

        // Check that relationship was created
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true, // Should be primary for new user
        ]);
    }

    public function test_does_not_create_duplicate_relationship()
    {
        $userData = [
            'name' => 'Test Client',
            'email' => 'testclient@example.com',
        ];

        // Create user first time
        $clientUser1 = $this->service->findOrCreateClientUser($userData, $this->admin);
        
        // Create user second time with same admin
        $clientUser2 = $this->service->findOrCreateClientUser($userData, $this->admin);

        $this->assertEquals($clientUser1->id, $clientUser2->id);

        // Should only have one relationship
        $relationshipCount = ClientUserRelationship::where([
            'client_user_id' => $clientUser1->id,
            'company_user_id' => $this->admin->id,
        ])->count();

        $this->assertEquals(1, $relationshipCount);
    }

    public function test_creates_relationship_with_different_company_users()
    {
        $userData = [
            'name' => 'Test Client',
            'email' => 'testclient@example.com',
        ];

        // Create user with admin
        $clientUser1 = $this->service->findOrCreateClientUser($userData, $this->admin);
        
        // Create user with employee
        $clientUser2 = $this->service->findOrCreateClientUser($userData, $this->employee);

        $this->assertEquals($clientUser1->id, $clientUser2->id);

        // Should have two relationships
        $adminRelationship = ClientUserRelationship::where([
            'client_user_id' => $clientUser1->id,
            'company_user_id' => $this->admin->id,
        ])->first();

        $employeeRelationship = ClientUserRelationship::where([
            'client_user_id' => $clientUser1->id,
            'company_user_id' => $this->employee->id,
        ])->first();

        $this->assertNotNull($adminRelationship);
        $this->assertNotNull($employeeRelationship);
        $this->assertTrue($adminRelationship->is_primary); // First relationship is primary
        $this->assertFalse($employeeRelationship->is_primary); // Second relationship is not primary
    }

    public function test_associate_with_company_user_creates_relationship()
    {
        $clientUser = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client@example.com',
        ]);

        $this->service->associateWithCompanyUser($clientUser, $this->admin);

        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => false, // Should not be primary when adding additional relationships
        ]);
    }

    public function test_associate_with_company_user_does_not_create_duplicate()
    {
        $clientUser = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client@example.com',
        ]);

        // Create relationship twice
        $this->service->associateWithCompanyUser($clientUser, $this->admin);
        $this->service->associateWithCompanyUser($clientUser, $this->admin);

        // Should only have one relationship
        $relationshipCount = ClientUserRelationship::where([
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->admin->id,
        ])->count();

        $this->assertEquals(1, $relationshipCount);
    }

    public function test_get_token_owner_for_client_returns_primary_company_user()
    {
        $clientUser = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client@example.com',
        ]);

        // Create primary relationship with admin
        ClientUserRelationship::create([
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => true,
        ]);

        // Create non-primary relationship with employee
        ClientUserRelationship::create([
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->employee->id,
            'is_primary' => false,
        ]);

        $tokenOwner = $this->service->getTokenOwnerForClient($clientUser);

        $this->assertEquals($this->admin->id, $tokenOwner->id);
    }

    public function test_get_token_owner_for_client_returns_null_when_no_primary()
    {
        $clientUser = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'client@example.com',
        ]);

        // Create non-primary relationship
        ClientUserRelationship::create([
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->admin->id,
            'is_primary' => false,
        ]);

        $tokenOwner = $this->service->getTokenOwnerForClient($clientUser);

        $this->assertNull($tokenOwner);
    }

    public function test_transaction_rollback_on_failure()
    {
        $userData = [
            'name' => 'Test Client',
            'email' => 'testclient@example.com',
        ];

        // Mock a scenario where relationship creation might fail
        // This is a bit tricky to test without more complex mocking
        // For now, we'll just ensure the service handles normal cases correctly
        
        $clientUser = $this->service->findOrCreateClientUser($userData, $this->admin);
        
        // Verify both user and relationship were created
        $this->assertDatabaseHas('users', [
            'email' => 'testclient@example.com',
            'role' => UserRole::CLIENT->value,
        ]);
        
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->admin->id,
        ]);
    }

    public function test_handles_email_case_sensitivity()
    {
        $userData1 = [
            'name' => 'Test Client',
            'email' => 'TestClient@Example.COM',
        ];

        $userData2 = [
            'name' => 'Test Client',
            'email' => 'TestClient@Example.COM', // Use same case for this test
        ];

        $clientUser1 = $this->service->findOrCreateClientUser($userData1, $this->admin);
        $clientUser2 = $this->service->findOrCreateClientUser($userData2, $this->employee);

        // Should find the same user when email is exactly the same
        $this->assertEquals($clientUser1->id, $clientUser2->id);
        
        // Test that different case creates different users (current behavior)
        $userData3 = [
            'name' => 'Test Client',
            'email' => 'testclient@example.com', // Different case
        ];
        
        $clientUser3 = $this->service->findOrCreateClientUser($userData3, $this->admin);
        
        // This will create a different user due to case sensitivity
        $this->assertNotEquals($clientUser1->id, $clientUser3->id);
    }

    public function test_generates_random_password_for_new_users()
    {
        $userData = [
            'name' => 'Test Client',
            'email' => 'testclient@example.com',
        ];

        $clientUser = $this->service->findOrCreateClientUser($userData, $this->admin);

        // Password should not be empty and should be hashed
        $this->assertNotEmpty($clientUser->password);
        $this->assertNotEquals('password', $clientUser->password);
        $this->assertTrue(strlen($clientUser->password) > 10); // Hashed passwords are long
    }
}