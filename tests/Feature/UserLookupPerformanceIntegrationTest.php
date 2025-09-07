<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DomainAccessRule;
use App\Services\UserLookupPerformanceService;
use App\Services\DomainRulesCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class UserLookupPerformanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        
        // Clear performance stats and cache before each test
        app(UserLookupPerformanceService::class)->clearPerformanceStats();
        app(DomainRulesCacheService::class)->clearCache();
    }

    public function test_existing_user_lookup_uses_performance_service()
    {
        // Create an existing user
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com'
        ]);

        // Create domain rules to ensure caching is tested
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['example.com'],
            'allow_public_registration' => false
        ]);

        // Make request to validate email endpoint
        $response = $this->postJson('/validate-email', [
            'email' => 'admin@example.com'
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Verify performance stats were recorded
        $performanceService = app(UserLookupPerformanceService::class);
        $stats = $performanceService->getPerformanceStats();

        $this->assertGreaterThan(0, $stats['total_lookups']);
        $this->assertEquals(1, $stats['successful_lookups']);
        $this->assertEquals(0, $stats['failed_lookups']);
        $this->assertEquals(100.0, $stats['success_rate']);
    }

    public function test_new_user_lookup_uses_performance_service()
    {
        // Create domain rules that allow public registration
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['example.com'],
            'allow_public_registration' => true
        ]);

        // Make request for non-existent user
        $response = $this->postJson('/validate-email', [
            'email' => 'newuser@example.com'
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Verify performance stats were recorded
        $performanceService = app(UserLookupPerformanceService::class);
        $stats = $performanceService->getPerformanceStats();

        $this->assertGreaterThan(0, $stats['total_lookups']);
        $this->assertEquals(0, $stats['successful_lookups']);
        $this->assertEquals(1, $stats['failed_lookups']);
        $this->assertEquals(0.0, $stats['success_rate']);
    }

    public function test_domain_rules_caching_works()
    {
        // Create domain rules
        $rules = DomainAccessRule::create([
            'mode' => 'blacklist',
            'rules' => ['spam.com'],
            'allow_public_registration' => true
        ]);

        $cacheService = app(DomainRulesCacheService::class);

        // First call should hit database
        $firstCall = $cacheService->getDomainRules();
        $this->assertNotNull($firstCall);
        $this->assertEquals($rules->id, $firstCall->id);

        // Verify cache is populated
        $this->assertTrue(Cache::has('domain_access_rules'));

        // Second call should hit cache
        $secondCall = $cacheService->getDomainRules();
        $this->assertEquals($firstCall->id, $secondCall->id);
    }

    public function test_cache_is_cleared_when_domain_rules_updated()
    {
        // Create domain rules
        $rules = DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['example.com'],
            'allow_public_registration' => true
        ]);

        $cacheService = app(DomainRulesCacheService::class);

        // Load rules into cache
        $cacheService->getDomainRules();
        $this->assertTrue(Cache::has('domain_access_rules'));

        // Update the rules (this should trigger the observer to clear cache)
        $rules->update(['allow_public_registration' => false]);

        // Cache should be cleared and repopulated with new data
        $updatedRules = $cacheService->getDomainRules();
        $this->assertFalse($updatedRules->allow_public_registration);
    }

    public function test_performance_monitoring_detects_multiple_lookups()
    {
        // Create some users
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
        $client = User::factory()->create(['email' => 'client@example.com']); // Default is CLIENT role

        // Create domain rules
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['example.com'],
            'allow_public_registration' => false
        ]);

        // Make multiple requests
        $this->postJson('/validate-email', ['email' => 'admin@example.com']);
        $this->postJson('/validate-email', ['email' => 'client@example.com']);
        $this->postJson('/validate-email', ['email' => 'nonexistent@example.com']);

        // Check performance stats
        $performanceService = app(UserLookupPerformanceService::class);
        $stats = $performanceService->getPerformanceStats();

        $this->assertEquals(3, $stats['total_lookups']);
        $this->assertEquals(2, $stats['successful_lookups']);
        $this->assertEquals(1, $stats['failed_lookups']);
        $this->assertEquals(66.67, $stats['success_rate']);
        $this->assertArrayHasKey('average_time_ms', $stats);
    }

    public function test_performance_health_check_integration()
    {
        // Create a user and perform lookup
        User::factory()->create(['email' => 'test@example.com']);
        
        $this->postJson('/validate-email', ['email' => 'test@example.com']);

        // Check health
        $performanceService = app(UserLookupPerformanceService::class);
        $health = $performanceService->checkPerformanceHealth();

        $this->assertContains($health['status'], ['healthy', 'needs_attention']);
        $this->assertArrayHasKey('stats', $health);
        $this->assertGreaterThan(0, $health['stats']['total_lookups']);
    }

    public function test_optimized_indexes_improve_query_performance()
    {
        // Create multiple users to test index effectiveness
        $users = User::factory()->count(100)->create();
        
        $performanceService = app(UserLookupPerformanceService::class);
        
        // Perform lookups on various users
        $totalTime = 0;
        $lookupCount = 10;
        
        for ($i = 0; $i < $lookupCount; $i++) {
            $user = $users->random();
            $startTime = microtime(true);
            $performanceService->findUserByEmail($user->email);
            $totalTime += (microtime(true) - $startTime) * 1000;
        }
        
        $averageTime = $totalTime / $lookupCount;
        
        // With proper indexing, average lookup should be fast
        // This is a reasonable threshold for indexed queries
        $this->assertLessThan(50, $averageTime, 'Average lookup time should be under 50ms with proper indexing');
        
        // Verify performance stats show good performance
        $stats = $performanceService->getPerformanceStats();
        $this->assertEquals($lookupCount, $stats['total_lookups']);
        $this->assertEquals($lookupCount, $stats['successful_lookups']);
        $this->assertEquals(100.0, $stats['success_rate']);
    }
}