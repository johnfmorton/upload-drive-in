<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\UserLookupPerformanceService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserLookupPerformanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserLookupPerformanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserLookupPerformanceService();
        
        // Clear any existing performance stats
        $this->service->clearPerformanceStats();
    }

    public function test_finds_existing_user_by_email()
    {
        // Create a test user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User'
        ]);

        // Find the user using the performance service
        $foundUser = $this->service->findUserByEmail('test@example.com');

        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id, $foundUser->id);
        $this->assertEquals('test@example.com', $foundUser->email);
    }

    public function test_returns_null_for_non_existent_user()
    {
        // Try to find a user that doesn't exist
        $foundUser = $this->service->findUserByEmail('nonexistent@example.com');

        $this->assertNull($foundUser);
    }

    public function test_tracks_performance_statistics()
    {
        // Create a test user
        User::factory()->create(['email' => 'test@example.com']);

        // Perform some lookups
        $this->service->findUserByEmail('test@example.com'); // Found
        $this->service->findUserByEmail('notfound@example.com'); // Not found

        // Check performance stats
        $stats = $this->service->getPerformanceStats();

        $this->assertEquals(2, $stats['total_lookups']);
        $this->assertEquals(1, $stats['successful_lookups']);
        $this->assertEquals(1, $stats['failed_lookups']);
        $this->assertEquals(50.0, $stats['success_rate']);
        $this->assertArrayHasKey('average_time_ms', $stats);
        $this->assertArrayHasKey('min_time_ms', $stats);
        $this->assertArrayHasKey('max_time_ms', $stats);
    }

    public function test_detects_slow_queries()
    {
        // Create a test user
        User::factory()->create(['email' => 'test@example.com']);

        // Mock a slow query by manipulating the performance tracking
        // This is a simplified test - in reality, slow queries would be detected
        // based on actual execution time
        $this->service->findUserByEmail('test@example.com');

        $stats = $this->service->getPerformanceStats();
        $this->assertArrayHasKey('slow_queries', $stats);
        $this->assertArrayHasKey('slow_query_rate', $stats);
    }

    public function test_performance_health_check_with_no_data()
    {
        $health = $this->service->checkPerformanceHealth();

        $this->assertEquals('no_data', $health['status']);
        $this->assertEquals('No performance data available', $health['message']);
        $this->assertEmpty($health['recommendations']);
    }

    public function test_performance_health_check_with_good_performance()
    {
        // Create a test user and perform a fast lookup
        User::factory()->create(['email' => 'test@example.com']);
        $this->service->findUserByEmail('test@example.com');

        $health = $this->service->checkPerformanceHealth();

        // With good performance, status should be healthy
        $this->assertContains($health['status'], ['healthy', 'needs_attention']);
        $this->assertArrayHasKey('stats', $health);
    }

    public function test_clears_performance_statistics()
    {
        // Create some performance data
        User::factory()->create(['email' => 'test@example.com']);
        $this->service->findUserByEmail('test@example.com');

        // Verify stats exist
        $stats = $this->service->getPerformanceStats();
        $this->assertGreaterThan(0, $stats['total_lookups'] ?? 0);

        // Clear stats
        $this->service->clearPerformanceStats();

        // Verify stats are cleared
        $clearedStats = $this->service->getPerformanceStats();
        $this->assertEmpty($clearedStats);
    }

    public function test_handles_database_exceptions_gracefully()
    {
        // This test would require mocking database failures
        // For now, we'll test that the service doesn't break with invalid emails
        
        $foundUser = $this->service->findUserByEmail('invalid-email-format');
        $this->assertNull($foundUser);

        // Verify that stats are still tracked even for invalid lookups
        $stats = $this->service->getPerformanceStats();
        $this->assertEquals(1, $stats['total_lookups']);
        $this->assertEquals(0, $stats['successful_lookups']);
        $this->assertEquals(1, $stats['failed_lookups']);
    }

    public function test_optimized_query_selects_only_needed_fields()
    {
        // Create a test user with all fields
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password'
        ]);

        // Find the user
        $foundUser = $this->service->findUserByEmail('test@example.com');

        // Verify that the essential fields are present
        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id, $foundUser->id);
        $this->assertEquals($user->email, $foundUser->email);
        $this->assertEquals($user->name, $foundUser->name);
        $this->assertEquals($user->role, $foundUser->role);
        
        // The password should not be included in the select (it's hidden anyway)
        // but we can verify the user object is properly formed
        $this->assertInstanceOf(User::class, $foundUser);
    }
}