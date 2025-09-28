<?php

namespace Tests\Integration;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminUserSearchIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        
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

    public function test_complete_search_workflow_from_request_to_database()
    {
        // Create test data
        $clients = [
            User::factory()->create([
                'role' => UserRole::CLIENT,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]),
            User::factory()->create([
                'role' => UserRole::CLIENT,
                'name' => 'Jane Smith',
                'email' => 'jane@company.com',
            ]),
            User::factory()->create([
                'role' => UserRole::CLIENT,
                'name' => 'Bob Johnson',
                'email' => 'bob@test.org',
            ]),
        ];
        
        $this->actingAs($this->admin);
        
        // Test the complete workflow
        $response = $this->get('/admin/users?search=John');
        
        $response->assertStatus(200);
        
        // Verify database query was executed correctly
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'role' => UserRole::CLIENT,
        ]);
        
        // Verify response contains expected data
        $response->assertSee('John Doe');
        $response->assertSee('Bob Johnson'); // Contains "John" in last name
        $response->assertDontSee('Jane Smith');
        
        // Verify search term is preserved in view
        $response->assertSee('value="John"', false);
    }

    public function test_search_database_query_optimization()
    {
        // Create test data
        User::factory()->count(50)->create([
            'role' => UserRole::CLIENT,
            'name' => 'Test User',
        ]);
        
        $this->actingAs($this->admin);
        
        // Enable query logging
        DB::enableQueryLog();
        
        $response = $this->get('/admin/users?search=Test');
        
        $queries = DB::getQueryLog();
        
        $response->assertStatus(200);
        
        // Verify that search uses efficient database queries
        $this->assertNotEmpty($queries);
        
        // Find the main search query
        $searchQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'LIKE') && 
                   str_contains($query['query'], 'role');
        });
        
        $this->assertNotNull($searchQuery, 'Search query not found in query log');
        
        // Verify query structure
        $this->assertStringContainsString('name', $searchQuery['query']);
        $this->assertStringContainsString('email', $searchQuery['query']);
        $this->assertStringContainsString('or', strtolower($searchQuery['query']));
        
        // Verify parameters are properly bound
        $this->assertCount(3, $searchQuery['bindings']); // role, search term (2x for name and email)
    }

    public function test_search_with_primary_contact_filter_database_integration()
    {
        // Create test data with relationships
        $client1 = User::factory()->create([
            'role' => UserRole::CLIENT,
            'name' => 'Primary John',
            'email' => 'primary.john@example.com',
        ]);
        
        $client2 = User::factory()->create([
            'role' => UserRole::CLIENT,
            'name' => 'Secondary John',
            'email' => 'secondary.john@example.com',
        ]);
        
        // Set up primary contact relationship
        $client1->companyUsers()->attach($this->admin->id, ['is_primary' => true]);
        $client2->companyUsers()->attach($this->employee->id, ['is_primary' => true]);
        
        $this->actingAs($this->admin);
        
        // Enable query logging
        DB::enableQueryLog();
        
        $response = $this->get('/admin/users?search=John&filter=primary_contact');
        
        $queries = DB::getQueryLog();
        
        $response->assertStatus(200);
        
        // Should see only the primary contact for the current admin
        $response->assertSee('Primary John');
        $response->assertDontSee('Secondary John');
        
        // Verify complex query with joins was executed
        $complexQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'client_user_relationships') &&
                   str_contains($query['query'], 'LIKE') &&
                   str_contains($query['query'], 'is_primary');
        });
        
        $this->assertNotNull($complexQuery, 'Complex search with filter query not found');
    }

    public function test_pagination_integration_with_search()
    {
        // Create enough data to trigger pagination
        User::factory()->count(25)->create([
            'role' => UserRole::CLIENT,
            'name' => 'Paginated User',
        ]);
        
        $this->actingAs($this->admin);
        
        // Test first page
        $response = $this->get('/admin/users?search=Paginated&page=1');
        $response->assertStatus(200);
        $response->assertSee('Paginated User');
        
        // Test second page
        $response = $this->get('/admin/users?search=Paginated&page=2');
        $response->assertStatus(200);
        
        // Verify pagination links preserve search parameters
        $response->assertSee('search=Paginated', false);
    }

    public function test_search_performance_with_large_dataset()
    {
        // Create a larger dataset
        User::factory()->count(200)->create([
            'role' => UserRole::CLIENT,
        ]);
        
        $this->actingAs($this->admin);
        
        $startTime = microtime(true);
        $response = $this->get('/admin/users?search=test');
        $endTime = microtime(true);
        
        $response->assertStatus(200);
        
        // Should complete within performance requirements (2 seconds)
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(2.0, $executionTime, "Search took {$executionTime} seconds, exceeding 2 second limit");
    }

    public function test_search_handles_database_constraints_properly()
    {
        $this->actingAs($this->admin);
        
        // Test with various constraint scenarios
        $testCases = [
            'search with apostrophe' => "O'Connor",
            'search with quotes' => 'John "Johnny" Doe',
            'search with backslash' => 'Test\\User',
            'search with percent sign' => 'Test%User',
            'search with underscore' => 'Test_User',
        ];
        
        foreach ($testCases as $description => $searchTerm) {
            $response = $this->get('/admin/users?search=' . urlencode($searchTerm));
            
            $response->assertStatus(200, "Failed for: {$description}");
            
            // Verify database integrity is maintained
            $this->assertDatabaseHas('users', [
                'email' => 'admin@example.com',
            ]);
        }
    }

    public function test_search_transaction_integrity()
    {
        $this->actingAs($this->admin);
        
        // Create test data
        User::factory()->create([
            'role' => UserRole::CLIENT,
            'name' => 'Transaction Test',
            'email' => 'transaction@test.com',
        ]);
        
        // Perform multiple concurrent-like searches
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->get('/admin/users?search=Transaction');
        }
        
        // All should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
            $response->assertSee('Transaction Test');
        }
        
        // Verify data integrity
        $this->assertDatabaseHas('users', [
            'name' => 'Transaction Test',
            'email' => 'transaction@test.com',
        ]);
    }

    public function test_search_with_special_database_characters()
    {
        // Create users with special characters that might affect database queries
        $specialUsers = [
            User::factory()->create([
                'role' => UserRole::CLIENT,
                'name' => "User with 'apostrophe'",
                'email' => 'apostrophe@test.com',
            ]),
            User::factory()->create([
                'role' => UserRole::CLIENT,
                'name' => 'User with "quotes"',
                'email' => 'quotes@test.com',
            ]),
            User::factory()->create([
                'role' => UserRole::CLIENT,
                'name' => 'User with % percent',
                'email' => 'percent@test.com',
            ]),
        ];
        
        $this->actingAs($this->admin);
        
        // Test searching for each special character
        $response = $this->get('/admin/users?search=' . urlencode("'apostrophe'"));
        $response->assertStatus(200);
        $response->assertSee("User with 'apostrophe'");
        
        $response = $this->get('/admin/users?search=' . urlencode('"quotes"'));
        $response->assertStatus(200);
        $response->assertSee('User with "quotes"');
        
        $response = $this->get('/admin/users?search=' . urlencode('% percent'));
        $response->assertStatus(200);
        $response->assertSee('User with % percent');
    }

    public function test_search_maintains_data_consistency_across_requests()
    {
        // Create test data
        $testUser = User::factory()->create([
            'role' => UserRole::CLIENT,
            'name' => 'Consistency Test',
            'email' => 'consistency@test.com',
        ]);
        
        $this->actingAs($this->admin);
        
        // Perform the same search multiple times
        $firstResponse = $this->get('/admin/users?search=Consistency');
        $secondResponse = $this->get('/admin/users?search=Consistency');
        $thirdResponse = $this->get('/admin/users?search=Consistency');
        
        // All responses should show the same users (not comparing full content due to dynamic elements)
        // Instead, verify they all contain the same user data
        foreach ([$firstResponse, $secondResponse, $thirdResponse] as $response) {
            $response->assertSee('Consistency Test');
        }
        
        // All should contain the test user
        foreach ([$firstResponse, $secondResponse, $thirdResponse] as $response) {
            $response->assertStatus(200);
            $response->assertSee('Consistency Test');
        }
    }

    public function test_search_error_handling_with_database_issues()
    {
        $this->actingAs($this->admin);
        
        // Test with extremely long search term that might cause database issues
        $veryLongSearch = str_repeat('a', 1000);
        
        $response = $this->get('/admin/users?search=' . urlencode($veryLongSearch));
        
        // Should handle gracefully with validation error
        $response->assertSessionHasErrors(['search']);
        
        // Database should remain intact
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
        ]);
    }

    public function test_search_index_utilization()
    {
        // Create test data
        User::factory()->count(100)->create([
            'role' => UserRole::CLIENT,
        ]);
        
        $this->actingAs($this->admin);
        
        // Enable query logging
        DB::enableQueryLog();
        
        $response = $this->get('/admin/users?search=test');
        
        $queries = DB::getQueryLog();
        
        $response->assertStatus(200);
        
        // Verify that queries are using indexes efficiently
        // This is a basic check - in a real scenario, you'd use EXPLAIN queries
        $mainQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'users') && 
                   str_contains($query['query'], 'LIKE');
        });
        
        $this->assertNotNull($mainQuery);
        
        // Verify query execution time is reasonable
        $this->assertLessThan(100, $mainQuery['time'], 'Query execution time too high, may indicate missing indexes');
    }
}