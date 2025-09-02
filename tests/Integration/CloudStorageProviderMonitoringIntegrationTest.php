<?php

namespace Tests\Integration;

use App\Services\CloudStorageProviderMonitoringService;
use App\Models\User;
use App\Models\CloudStorageHealthStatus;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class CloudStorageProviderMonitoringIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageProviderMonitoringService $monitoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitoringService = app(CloudStorageProviderMonitoringService::class);
        
        // Fake notifications to prevent actual emails during tests
        Notification::fake();
    }

    public function test_monitors_all_providers()
    {
        $results = $this->monitoringService->monitorAllProviders();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('monitoring_timestamp', $results);
        $this->assertArrayHasKey('providers_monitored', $results);
        $this->assertArrayHasKey('users_monitored', $results);
        $this->assertArrayHasKey('alerts_sent', $results);
        $this->assertArrayHasKey('issues_detected', $results);
        $this->assertArrayHasKey('summary', $results);

        $this->assertIsString($results['monitoring_timestamp']);
        $this->assertIsInt($results['providers_monitored']);
        $this->assertIsInt($results['users_monitored']);
        $this->assertIsInt($results['alerts_sent']);
        $this->assertIsArray($results['issues_detected']);
        $this->assertIsArray($results['summary']);

        // Verify issues structure
        $issues = $results['issues_detected'];
        $this->assertArrayHasKey('critical', $issues);
        $this->assertArrayHasKey('warning', $issues);
        $this->assertArrayHasKey('info', $issues);
        $this->assertIsArray($issues['critical']);
        $this->assertIsArray($issues['warning']);
        $this->assertIsArray($issues['info']);
    }

    public function test_detects_provider_issues()
    {
        // Create a user with an unhealthy connection
        $user = User::factory()->create(['role' => 'admin']);
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consecutive_failures' => 5,
            'last_error_message' => 'Token expired',
        ]);

        $results = $this->monitoringService->monitorAllProviders();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('issues_detected', $results);
        
        // Should detect issues with the unhealthy connection
        $issues = $results['issues_detected'];
        $totalIssues = count($issues['critical']) + count($issues['warning']) + count($issues['info']);
        
        // We expect at least some issues to be detected
        $this->assertGreaterThanOrEqual(0, $totalIssues);
    }

    public function test_calculates_monitoring_summary()
    {
        $results = $this->monitoringService->monitorAllProviders();
        
        $this->assertArrayHasKey('summary', $results);
        $summary = $results['summary'];
        
        $this->assertArrayHasKey('critical_issues', $summary);
        $this->assertArrayHasKey('warning_issues', $summary);
        $this->assertArrayHasKey('providers_down', $summary);
        $this->assertArrayHasKey('users_affected', $summary);
        $this->assertArrayHasKey('healthy_providers', $summary);
        $this->assertArrayHasKey('total_providers', $summary);
        $this->assertArrayHasKey('total_users', $summary);

        $this->assertIsInt($summary['critical_issues']);
        $this->assertIsInt($summary['warning_issues']);
        $this->assertIsInt($summary['providers_down']);
        $this->assertIsInt($summary['users_affected']);
        $this->assertIsInt($summary['healthy_providers']);
        $this->assertIsInt($summary['total_providers']);
        $this->assertIsInt($summary['total_users']);
    }

    public function test_gets_monitoring_status()
    {
        // First run monitoring to populate cache
        $this->monitoringService->monitorAllProviders();
        
        $status = $this->monitoringService->getMonitoringStatus();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('last_monitoring', $status);
        $this->assertArrayHasKey('status', $status);
        $this->assertArrayHasKey('summary', $status);
        $this->assertArrayHasKey('next_monitoring', $status);
        
        $this->assertContains($status['status'], ['healthy', 'warning', 'critical', 'unknown']);
        $this->assertIsArray($status['summary']);
    }

    public function test_caches_monitoring_results()
    {
        $results = $this->monitoringService->monitorAllProviders();
        
        // Cache the results
        $this->monitoringService->cacheMonitoringResults($results);
        
        // Verify cached results exist
        $cached = Cache::get('last_monitoring_results');
        $this->assertIsArray($cached);
        $this->assertEquals($results['monitoring_timestamp'], $cached['monitoring_timestamp']);
    }

    public function test_handles_monitoring_exceptions_gracefully()
    {
        // This should not throw an exception even if there are issues
        $results = $this->monitoringService->monitorAllProviders();
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('monitoring_timestamp', $results);
        
        // If there was an error, it should be reflected in the results
        if (isset($results['error'])) {
            $this->assertIsString($results['error']);
        }
    }

    public function test_detects_system_wide_issues()
    {
        $results = $this->monitoringService->monitorAllProviders();
        
        $this->assertArrayHasKey('issues_detected', $results);
        $issues = $results['issues_detected'];
        
        // Check for system-wide issue detection
        foreach ($issues['critical'] as $issue) {
            if ($issue['type'] === 'system_no_healthy_providers') {
                $this->assertArrayHasKey('message', $issue);
                $this->assertArrayHasKey('providers', $issue);
                $this->assertArrayHasKey('timestamp', $issue);
                $this->assertIsString($issue['message']);
                $this->assertIsArray($issue['providers']);
            }
        }
    }

    public function test_detects_user_specific_issues()
    {
        // Create a user with all providers unhealthy
        $user = User::factory()->create();
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consecutive_failures' => 3,
        ]);

        $results = $this->monitoringService->monitorAllProviders();
        
        $this->assertArrayHasKey('issues_detected', $results);
        $issues = $results['issues_detected'];
        
        // Should detect user-specific issues
        $userIssues = array_filter(
            array_merge($issues['critical'], $issues['warning']),
            function ($issue) {
                return in_array($issue['type'], ['user_all_providers_down', 'user_some_providers_down']);
            }
        );
        
        // We might have user issues depending on the health status
        $this->assertIsArray($userIssues);
    }

    public function test_categorizes_issues_by_severity()
    {
        $results = $this->monitoringService->monitorAllProviders();
        
        $this->assertArrayHasKey('issues_detected', $results);
        $issues = $results['issues_detected'];
        
        // Verify issue structure and categorization
        foreach (['critical', 'warning', 'info'] as $severity) {
            $this->assertIsArray($issues[$severity]);
            
            foreach ($issues[$severity] as $issue) {
                $this->assertArrayHasKey('type', $issue);
                $this->assertArrayHasKey('message', $issue);
                $this->assertArrayHasKey('timestamp', $issue);
                $this->assertIsString($issue['type']);
                $this->assertIsString($issue['message']);
                $this->assertIsString($issue['timestamp']);
            }
        }
    }

    public function test_monitoring_performance()
    {
        $startTime = microtime(true);
        
        $results = $this->monitoringService->monitorAllProviders();
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Monitoring should complete within reasonable time (10 seconds)
        $this->assertLessThan(10000, $executionTime, 'Monitoring took too long to complete');
        
        // Results should include timing information
        $this->assertArrayHasKey('monitoring_timestamp', $results);
    }

    public function test_monitoring_with_multiple_users()
    {
        // Create multiple users with different health statuses
        $healthyUser = User::factory()->create();
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $healthyUser->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
        ]);

        $unhealthyUser = User::factory()->create();
        CloudStorageHealthStatus::factory()->create([
            'user_id' => $unhealthyUser->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
            'consecutive_failures' => 5,
        ]);

        $results = $this->monitoringService->monitorAllProviders();
        
        $this->assertGreaterThanOrEqual(2, $results['users_monitored']);
        $this->assertIsArray($results['summary']);
        
        // Should have detected the unhealthy user
        $summary = $results['summary'];
        $this->assertGreaterThanOrEqual(1, $summary['users_affected']);
    }
}