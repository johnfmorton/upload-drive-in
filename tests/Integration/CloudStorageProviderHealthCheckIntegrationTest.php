<?php

namespace Tests\Integration;

use App\Services\CloudStorageProviderHealthCheckService;
use App\Models\User;
use App\Models\CloudStorageHealthStatus;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class CloudStorageProviderHealthCheckIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageProviderHealthCheckService $healthCheckService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->healthCheckService = app(CloudStorageProviderHealthCheckService::class);
    }

    public function test_performs_comprehensive_health_check()
    {
        $results = $this->healthCheckService->performComprehensiveHealthCheck();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('overall_status', $results);
        $this->assertArrayHasKey('timestamp', $results);
        $this->assertArrayHasKey('duration_ms', $results);
        $this->assertArrayHasKey('configuration_validation', $results);
        $this->assertArrayHasKey('provider_health', $results);
        $this->assertArrayHasKey('user_health', $results);
        $this->assertArrayHasKey('summary', $results);
        $this->assertArrayHasKey('recommendations', $results);

        $this->assertContains($results['overall_status'], ['healthy', 'degraded', 'warning', 'critical', 'error']);
        $this->assertIsNumeric($results['duration_ms']);
        $this->assertGreaterThan(0, $results['duration_ms']);
    }

    public function test_checks_all_providers_health()
    {
        $results = $this->healthCheckService->checkAllProvidersHealth();

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);

        foreach ($results as $provider => $health) {
            $this->assertIsString($provider);
            $this->assertIsArray($health);
            $this->assertArrayHasKey('provider_name', $health);
            $this->assertArrayHasKey('status', $health);
            $this->assertArrayHasKey('is_configured', $health);
            $this->assertArrayHasKey('is_enabled', $health);
            $this->assertArrayHasKey('configuration_valid', $health);
            $this->assertArrayHasKey('can_instantiate', $health);
            $this->assertArrayHasKey('last_checked', $health);

            $this->assertEquals($provider, $health['provider_name']);
            $this->assertIsBool($health['is_configured']);
            $this->assertIsBool($health['is_enabled']);
            $this->assertIsBool($health['configuration_valid']);
            $this->assertIsBool($health['can_instantiate']);
        }
    }

    public function test_checks_specific_provider_health()
    {
        $results = $this->healthCheckService->checkProviderHealth('google-drive');

        $this->assertIsArray($results);
        $this->assertEquals('google-drive', $results['provider_name']);
        $this->assertArrayHasKey('status', $results);
        $this->assertArrayHasKey('is_configured', $results);
        $this->assertArrayHasKey('is_enabled', $results);
        $this->assertArrayHasKey('configuration_valid', $results);
        $this->assertArrayHasKey('can_instantiate', $results);
        $this->assertArrayHasKey('errors', $results);
        $this->assertArrayHasKey('warnings', $results);
        $this->assertArrayHasKey('last_checked', $results);

        $this->assertContains($results['status'], [
            'healthy', 'not_configured', 'disabled', 'configuration_invalid',
            'instantiation_failed', 'connectivity_failed', 'error'
        ]);
    }

    public function test_checks_all_users_health()
    {
        // Create a test user with health status
        $user = User::factory()->create();
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
        ]);

        $results = $this->healthCheckService->checkAllUsersHealth();

        $this->assertIsArray($results);
        
        if (!empty($results)) {
            foreach ($results as $userId => $health) {
                $this->assertIsInt($userId);
                $this->assertIsArray($health);
                $this->assertArrayHasKey('user_id', $health);
                $this->assertArrayHasKey('user_email', $health);
                $this->assertArrayHasKey('providers', $health);
                $this->assertArrayHasKey('overall_status', $health);
                $this->assertArrayHasKey('healthy_providers', $health);
                $this->assertArrayHasKey('unhealthy_providers', $health);
                $this->assertArrayHasKey('last_checked', $health);

                $this->assertEquals($userId, $health['user_id']);
                $this->assertContains($health['overall_status'], ['healthy', 'partially_healthy', 'unhealthy']);
                $this->assertIsInt($health['healthy_providers']);
                $this->assertIsInt($health['unhealthy_providers']);
            }
        }
    }

    public function test_checks_specific_user_health()
    {
        $user = User::factory()->create();
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
        ]);

        $results = $this->healthCheckService->checkUserHealth($user);

        $this->assertIsArray($results);
        $this->assertEquals($user->id, $results['user_id']);
        $this->assertEquals($user->email, $results['user_email']);
        $this->assertArrayHasKey('providers', $results);
        $this->assertArrayHasKey('overall_status', $results);
        $this->assertArrayHasKey('healthy_providers', $results);
        $this->assertArrayHasKey('unhealthy_providers', $results);

        $this->assertContains($results['overall_status'], ['healthy', 'partially_healthy', 'unhealthy']);
        $this->assertIsInt($results['healthy_providers']);
        $this->assertIsInt($results['unhealthy_providers']);
    }

    public function test_caches_health_check_results()
    {
        // Clear any existing cache
        Cache::forget('cloud_storage_comprehensive_health_check');

        // First call should perform the check and cache results
        $results1 = $this->healthCheckService->performHealthCheckWithCaching();
        $this->assertIsArray($results1);

        // Second call should return cached results
        $results2 = $this->healthCheckService->performHealthCheckWithCaching();
        $this->assertIsArray($results2);

        // Results should be identical (from cache)
        $this->assertEquals($results1['timestamp'], $results2['timestamp']);
    }

    public function test_forces_refresh_of_cached_results()
    {
        // First call to populate cache
        $results1 = $this->healthCheckService->performHealthCheckWithCaching();
        
        // Force refresh should get new results
        $results2 = $this->healthCheckService->performHealthCheckWithCaching(true);
        
        $this->assertIsArray($results1);
        $this->assertIsArray($results2);
        
        // Timestamps should be different (new check performed)
        $this->assertNotEquals($results1['timestamp'], $results2['timestamp']);
    }

    public function test_gets_cached_health_check_results()
    {
        // Should return null when no cache exists
        $cached = $this->healthCheckService->getCachedHealthCheck();
        $this->assertNull($cached);

        // Perform a check to populate cache
        $results = $this->healthCheckService->performHealthCheckWithCaching();
        
        // Should now return cached results
        $cached = $this->healthCheckService->getCachedHealthCheck();
        $this->assertIsArray($cached);
        $this->assertEquals($results['timestamp'], $cached['timestamp']);
    }

    public function test_caches_health_check_with_custom_ttl()
    {
        $results = $this->healthCheckService->performComprehensiveHealthCheck();
        
        // Cache with 1 minute TTL
        $this->healthCheckService->cacheHealthCheck($results, 1);
        
        $cached = $this->healthCheckService->getCachedHealthCheck();
        $this->assertIsArray($cached);
        $this->assertEquals($results['timestamp'], $cached['timestamp']);
    }

    public function test_handles_health_check_exceptions_gracefully()
    {
        // This should not throw an exception even if there are issues
        $results = $this->healthCheckService->performComprehensiveHealthCheck();
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('overall_status', $results);
        
        // If there was an error, it should be reflected in the status
        if (isset($results['error'])) {
            $this->assertEquals('error', $results['overall_status']);
        }
    }

    public function test_generates_appropriate_recommendations()
    {
        $results = $this->healthCheckService->performComprehensiveHealthCheck();
        
        $this->assertArrayHasKey('recommendations', $results);
        $this->assertIsArray($results['recommendations']);
        
        foreach ($results['recommendations'] as $recommendation) {
            $this->assertArrayHasKey('type', $recommendation);
            $this->assertArrayHasKey('priority', $recommendation);
            $this->assertArrayHasKey('message', $recommendation);
            
            $this->assertContains($recommendation['type'], [
                'configuration', 'provider_health', 'user_health', 'system'
            ]);
            $this->assertContains($recommendation['priority'], [
                'critical', 'high', 'medium', 'low'
            ]);
            $this->assertIsString($recommendation['message']);
        }
    }

    public function test_calculates_accurate_summary_statistics()
    {
        $results = $this->healthCheckService->performComprehensiveHealthCheck();
        
        $this->assertArrayHasKey('summary', $results);
        $summary = $results['summary'];
        
        $this->assertArrayHasKey('total_providers', $summary);
        $this->assertArrayHasKey('healthy_providers', $summary);
        $this->assertArrayHasKey('unhealthy_providers', $summary);
        $this->assertArrayHasKey('total_users', $summary);
        $this->assertArrayHasKey('users_with_healthy_connections', $summary);
        $this->assertArrayHasKey('users_with_issues', $summary);
        $this->assertArrayHasKey('configuration_issues', $summary);
        
        // Verify mathematical consistency
        $this->assertEquals(
            $summary['healthy_providers'] + $summary['unhealthy_providers'],
            $summary['total_providers']
        );
        
        $this->assertEquals(
            $summary['users_with_healthy_connections'] + $summary['users_with_issues'],
            $summary['total_users']
        );
    }
}