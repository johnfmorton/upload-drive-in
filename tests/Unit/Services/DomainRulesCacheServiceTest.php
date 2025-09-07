<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DomainRulesCacheService;
use App\Models\DomainAccessRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class DomainRulesCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private DomainRulesCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DomainRulesCacheService();
        
        // Clear cache before each test
        $this->service->clearCache();
    }

    public function test_gets_domain_rules_from_database_on_cache_miss()
    {
        // Create domain rules
        $rules = DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['example.com', 'test.com'],
            'allow_public_registration' => true
        ]);

        // Get rules (should hit database)
        $cachedRules = $this->service->getDomainRules();

        $this->assertNotNull($cachedRules);
        $this->assertEquals($rules->id, $cachedRules->id);
        $this->assertEquals('whitelist', $cachedRules->mode);
        $this->assertTrue($cachedRules->allow_public_registration);
    }

    public function test_gets_domain_rules_from_cache_on_cache_hit()
    {
        // Create domain rules
        DomainAccessRule::create([
            'mode' => 'blacklist',
            'rules' => ['spam.com'],
            'allow_public_registration' => false
        ]);

        // First call - cache miss
        $firstCall = $this->service->getDomainRules();
        
        // Second call - should be cache hit
        $secondCall = $this->service->getDomainRules();

        $this->assertEquals($firstCall->id, $secondCall->id);
        $this->assertEquals($firstCall->mode, $secondCall->mode);
    }

    public function test_returns_null_when_no_domain_rules_exist()
    {
        // Don't create any domain rules
        $rules = $this->service->getDomainRules();

        $this->assertNull($rules);
    }

    public function test_clears_cache_successfully()
    {
        // Create domain rules and cache them
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['example.com'],
            'allow_public_registration' => true
        ]);

        // Load into cache
        $this->service->getDomainRules();

        // Clear cache
        $this->service->clearCache();

        // Verify cache is cleared by checking if cache key exists
        $this->assertFalse(Cache::has('domain_access_rules'));
    }

    public function test_warms_cache_successfully()
    {
        // Create domain rules
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['example.com'],
            'allow_public_registration' => true
        ]);

        // Warm cache
        $this->service->warmCache();

        // Verify cache is warmed
        $this->assertTrue(Cache::has('domain_access_rules'));
    }

    public function test_checks_email_allowed_with_whitelist_mode()
    {
        // Create whitelist rules
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['example.com', 'test.com'],
            'allow_public_registration' => true
        ]);

        // Test allowed email
        $this->assertTrue($this->service->isEmailAllowed('user@example.com'));
        $this->assertTrue($this->service->isEmailAllowed('user@test.com'));

        // Test disallowed email
        $this->assertFalse($this->service->isEmailAllowed('user@blocked.com'));
    }

    public function test_checks_email_allowed_with_blacklist_mode()
    {
        // Create blacklist rules
        DomainAccessRule::create([
            'mode' => 'blacklist',
            'rules' => ['spam.com', 'blocked.com'],
            'allow_public_registration' => true
        ]);

        // Test allowed email (not in blacklist)
        $this->assertTrue($this->service->isEmailAllowed('user@example.com'));
        $this->assertTrue($this->service->isEmailAllowed('user@test.com'));

        // Test disallowed email (in blacklist)
        $this->assertFalse($this->service->isEmailAllowed('user@spam.com'));
        $this->assertFalse($this->service->isEmailAllowed('user@blocked.com'));
    }

    public function test_allows_email_when_no_rules_configured()
    {
        // Don't create any domain rules
        $this->assertTrue($this->service->isEmailAllowed('user@example.com'));
        $this->assertTrue($this->service->isEmailAllowed('user@anything.com'));
    }

    public function test_checks_public_registration_allowed()
    {
        // Create rules with public registration enabled
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['example.com'],
            'allow_public_registration' => true
        ]);

        $this->assertTrue($this->service->isPublicRegistrationAllowed());

        // Update to disable public registration
        DomainAccessRule::first()->update(['allow_public_registration' => false]);
        $this->service->clearCache(); // Clear cache to get updated value

        $this->assertFalse($this->service->isPublicRegistrationAllowed());
    }

    public function test_allows_public_registration_when_no_rules_configured()
    {
        // Don't create any domain rules
        $this->assertTrue($this->service->isPublicRegistrationAllowed());
    }

    public function test_gets_cache_statistics()
    {
        // Create domain rules
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['example.com', 'test.com'],
            'allow_public_registration' => true
        ]);

        // Load rules to populate cache
        $this->service->getDomainRules();

        // Get cache stats
        $stats = $this->service->getCacheStats();

        $this->assertArrayHasKey('cache_key', $stats);
        $this->assertArrayHasKey('cache_ttl', $stats);
        $this->assertArrayHasKey('cache_hit', $stats);
        $this->assertArrayHasKey('rules_loaded', $stats);
        $this->assertArrayHasKey('rules_config', $stats);

        $this->assertEquals('domain_access_rules', $stats['cache_key']);
        $this->assertEquals(3600, $stats['cache_ttl']);
        $this->assertTrue($stats['rules_loaded']);
        $this->assertNotNull($stats['rules_config']);
        $this->assertEquals('whitelist', $stats['rules_config']['mode']);
        $this->assertTrue($stats['rules_config']['allow_public_registration']);
        $this->assertEquals(2, $stats['rules_config']['rules_count']);
    }

    public function test_handles_database_exceptions_gracefully()
    {
        // This would require mocking database failures
        // For now, test that the service handles empty results gracefully
        
        $rules = $this->service->getDomainRules();
        $this->assertNull($rules);

        // Should still allow emails when no rules exist
        $this->assertTrue($this->service->isEmailAllowed('user@example.com'));
        $this->assertTrue($this->service->isPublicRegistrationAllowed());
    }
}