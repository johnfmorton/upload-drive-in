<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminUserSearchPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);
    }

    public function test_users_table_has_required_indexes_for_search()
    {
        // Skip this test for SQLite as it doesn't support SHOW INDEX
        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('Index checking not supported in SQLite test environment');
        }
        
        // Check if email index exists (should exist for uniqueness)
        $emailIndexes = DB::select("SHOW INDEX FROM users WHERE Column_name = 'email'");
        $this->assertNotEmpty($emailIndexes, 'Email column should have an index');
        
        // Check if name column has an index (may need to be added for search performance)
        $nameIndexes = DB::select("SHOW INDEX FROM users WHERE Column_name = 'name'");
        
        // If no index exists on name, we should recommend adding one
        if (empty($nameIndexes)) {
            $this->markTestIncomplete('Consider adding an index on the name column for better search performance');
        }
    }

    public function test_search_query_performance_with_large_dataset()
    {
        // Create a large dataset
        User::factory()->count(1000)->create([
            'role' => UserRole::CLIENT,
            'username' => null, // Avoid unique constraint issues
        ]);
        
        $this->actingAs($this->admin);
        
        // Enable query logging
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        $response = $this->get('/admin/users?search=test');
        $endTime = microtime(true);
        
        $queries = DB::getQueryLog();
        
        $response->assertStatus(200);
        
        // Performance assertions
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(1.0, $executionTime, "Search with 1000 records took {$executionTime}s, should be under 1s");
        
        // Find the main search query
        $searchQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'LIKE') && str_contains($query['query'], 'role');
        });
        
        $this->assertNotNull($searchQuery);
        $this->assertLessThan(50, $searchQuery['time'], 'Individual query should execute in under 50ms');
    }

    public function test_search_query_uses_proper_where_clause_optimization()
    {
        // Create test data
        User::factory()->count(100)->create(['role' => UserRole::CLIENT, 'username' => null]);
        User::factory()->count(50)->create(['role' => UserRole::ADMIN, 'username' => null]);
        User::factory()->count(50)->create(['role' => UserRole::EMPLOYEE, 'username' => null]);
        
        $this->actingAs($this->admin);
        
        DB::enableQueryLog();
        
        $response = $this->get('/admin/users?search=test');
        
        $queries = DB::getQueryLog();
        
        // Find the main query
        $mainQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'role') && str_contains($query['query'], 'LIKE');
        });
        
        $this->assertNotNull($mainQuery);
        
        // Verify role filter comes before LIKE clauses for better performance
        $queryString = $mainQuery['query'];
        $rolePosition = strpos($queryString, 'role');
        $likePosition = strpos($queryString, 'LIKE');
        
        $this->assertLessThan($likePosition, $rolePosition, 'Role filter should come before LIKE clauses for better performance');
    }

    public function test_pagination_query_efficiency()
    {
        // Create enough data to trigger pagination
        User::factory()->count(50)->create([
            'role' => UserRole::CLIENT,
            'name' => 'Paginated User',
            'username' => null,
        ]);
        
        $this->actingAs($this->admin);
        
        DB::enableQueryLog();
        
        $response = $this->get('/admin/users?search=Paginated&page=2');
        
        $queries = DB::getQueryLog();
        
        $response->assertStatus(200);
        
        // Should have pagination-related queries
        $hasCountQuery = collect($queries)->contains(function ($query) {
            return str_contains($query['query'], 'count(*)');
        });
        
        $hasLimitQuery = collect($queries)->contains(function ($query) {
            return str_contains(strtolower($query['query']), 'limit');
        });
        
        $this->assertTrue($hasCountQuery, 'Should have a count query for pagination');
        $this->assertTrue($hasLimitQuery, 'Should have a query with LIMIT for pagination');
        
        // All queries should be reasonably fast
        foreach ($queries as $query) {
            $this->assertLessThan(100, $query['time'], 'Query should execute in under 100ms');
        }
    }

    public function test_search_with_primary_contact_filter_query_optimization()
    {
        // Create test data with relationships
        $clients = User::factory()->count(20)->create(['role' => UserRole::CLIENT, 'username' => null]);
        
        foreach ($clients as $client) {
            $client->companyUsers()->attach($this->admin->id, ['is_primary' => true]);
        }
        
        $this->actingAs($this->admin);
        
        DB::enableQueryLog();
        
        $response = $this->get('/admin/users?search=test&filter=primary_contact');
        
        $queries = DB::getQueryLog();
        
        $response->assertStatus(200);
        
        // Find the complex query with joins
        $complexQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'client_user_relationships') &&
                   str_contains($query['query'], 'LIKE');
        });
        
        $this->assertNotNull($complexQuery, 'Should have a query with relationship joins');
        
        // Complex query should still be reasonably fast
        $this->assertLessThan(100, $complexQuery['time'], 'Complex query with joins should execute in under 100ms');
    }

    public function test_search_parameter_binding_prevents_sql_injection()
    {
        $this->actingAs($this->admin);
        
        DB::enableQueryLog();
        
        // Attempt SQL injection
        $maliciousInput = "'; DROP TABLE users; --";
        $response = $this->get('/admin/users?search=' . urlencode($maliciousInput));
        
        $queries = DB::getQueryLog();
        
        $response->assertStatus(200);
        
        // Find the search query
        $searchQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'LIKE');
        });
        
        $this->assertNotNull($searchQuery);
        
        // Verify parameters are properly bound (should have ? placeholders)
        $this->assertStringContainsString('?', $searchQuery['query']);
        
        // Verify the malicious input is not directly in query (should be parameterized)
        $this->assertStringNotContainsString('DROP TABLE', $searchQuery['query']);
        $this->assertStringNotContainsString($maliciousInput, $searchQuery['query']);
        
        // Verify users table still exists
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
    }

    public function test_search_query_limit_prevents_resource_exhaustion()
    {
        // Create a very large dataset
        User::factory()->count(2000)->create([
            'role' => UserRole::CLIENT,
            'name' => 'Performance Test User',
            'username' => null,
        ]);
        
        $this->actingAs($this->admin);
        
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        $response = $this->get('/admin/users?search=Performance');
        $endTime = microtime(true);
        
        $queries = DB::getQueryLog();
        
        $response->assertStatus(200);
        
        // Even with 2000 matching records, should be fast due to pagination
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(2.0, $executionTime, "Search with 2000 records took {$executionTime}s");
        
        // Verify LIMIT clause is used somewhere in the queries
        $hasLimitQuery = collect($queries)->contains(function ($query) {
            return str_contains(strtolower($query['query']), 'limit');
        });
        
        $this->assertTrue($hasLimitQuery, 'Should use LIMIT to prevent loading all records');
    }

    public function test_search_memory_usage_remains_reasonable()
    {
        // Create large dataset
        User::factory()->count(500)->create([
            'role' => UserRole::CLIENT,
            'username' => null,
        ]);
        
        $this->actingAs($this->admin);
        
        $memoryBefore = memory_get_usage(true);
        
        $response = $this->get('/admin/users?search=test');
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        $response->assertStatus(200);
        
        // Memory usage should be reasonable (less than 10MB for this operation)
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, "Search used {$memoryUsed} bytes of memory");
    }

    public function test_concurrent_search_requests_performance()
    {
        // Create test data
        User::factory()->count(100)->create([
            'role' => UserRole::CLIENT,
            'name' => 'Concurrent Test User',
            'username' => null,
        ]);
        
        $this->actingAs($this->admin);
        
        // Simulate concurrent requests by running multiple searches quickly
        $startTime = microtime(true);
        
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->get('/admin/users?search=Concurrent');
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
            $response->assertSee('Concurrent Test User');
        }
        
        // Total time for 5 requests should be reasonable
        $this->assertLessThan(5.0, $totalTime, "5 concurrent searches took {$totalTime}s");
        
        // Average time per request should be reasonable
        $averageTime = $totalTime / 5;
        $this->assertLessThan(1.0, $averageTime, "Average search time was {$averageTime}s");
    }

    public function test_search_with_empty_results_performance()
    {
        // Create some data that won't match
        User::factory()->count(100)->create([
            'role' => UserRole::CLIENT,
            'name' => 'Different Name',
            'username' => null,
        ]);
        
        $this->actingAs($this->admin);
        
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        $response = $this->get('/admin/users?search=NonExistentTerm');
        $endTime = microtime(true);
        
        $queries = DB::getQueryLog();
        
        $response->assertStatus(200);
        $response->assertSee('No client users match your search');
        
        // Empty results should still be fast
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(0.5, $executionTime, "Empty search took {$executionTime}s");
        
        // Should still execute the same optimized queries
        $searchQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'LIKE');
        });
        
        $this->assertNotNull($searchQuery);
        $this->assertLessThan(50, $searchQuery['time'], 'Empty search query should still be fast');
    }
}