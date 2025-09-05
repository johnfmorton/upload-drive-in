<?php

namespace Tests\Unit\Services;

use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\TokenMonitoringDashboardService;
use App\Services\TokenRefreshMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TokenMonitoringDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private TokenMonitoringDashboardService $dashboardService;
    private TokenRefreshMonitoringService $monitoringService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->monitoringService = $this->createMock(TokenRefreshMonitoringService::class);
        $this->dashboardService = new TokenMonitoringDashboardService($this->monitoringService);
        
        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_generates_comprehensive_dashboard_data()
    {
        // Mock performance metrics
        $mockPerformanceMetrics = [
            'refresh_operations' => [
                'total_operations' => 100,
                'successful_operations' => 95,
                'failed_operations' => 5,
                'success_rate' => 0.95,
                'failure_rate' => 0.05,
                'average_duration_ms' => 2500.0,
                'error_breakdown' => []
            ],
            'health_validation' => [
                'total_validations' => 50,
                'cache_hits' => 40,
                'cache_misses' => 10,
                'cache_hit_rate' => 0.8,
                'cache_miss_rate' => 0.2
            ],
            'api_connectivity' => [
                'total_tests' => 20,
                'successful_tests' => 19,
                'failed_tests' => 1,
                'success_rate' => 0.95,
                'average_response_time_ms' => 1200.0
            ],
            'alerting_status' => [
                'active_alerts' => [],
                'alert_count' => 0,
                'last_checked' => now()->toISOString()
            ],
            'system_health' => [
                'overall_status' => 'healthy',
                'token_refresh_health' => 'healthy',
                'api_connectivity_health' => 'healthy',
                'cache_performance_health' => 'healthy',
                'last_updated' => now()->toISOString()
            ]
        ];

        $this->monitoringService
            ->expects($this->atLeastOnce())
            ->method('getPerformanceMetrics')
            ->with('google-drive', 24)
            ->willReturn($mockPerformanceMetrics);

        // Create test data
        $users = User::factory()->count(10)->create();
        $connectedUsers = $users->take(8);
        
        foreach ($connectedUsers as $user) {
            GoogleDriveToken::factory()->create([
                'user_id' => $user->id,
                'expires_at' => now()->addDays(7),
                'requires_user_intervention' => false,
                'refresh_failure_count' => 0
            ]);
        }

        $dashboardData = $this->dashboardService->getDashboardData('google-drive', 24);

        // Verify structure
        $this->assertArrayHasKey('overview', $dashboardData);
        $this->assertArrayHasKey('performance_metrics', $dashboardData);
        $this->assertArrayHasKey('token_status_summary', $dashboardData);
        $this->assertArrayHasKey('recent_operations', $dashboardData);
        $this->assertArrayHasKey('health_trends', $dashboardData);
        $this->assertArrayHasKey('user_statistics', $dashboardData);
        $this->assertArrayHasKey('system_status', $dashboardData);
        $this->assertArrayHasKey('recommendations', $dashboardData);
        $this->assertArrayHasKey('generated_at', $dashboardData);

        // Verify overview metrics
        $overview = $dashboardData['overview'];
        $this->assertEquals(10, $overview['total_users']);
        $this->assertEquals(8, $overview['connected_users']);
        $this->assertEquals(0.95, $overview['success_rate']);
        $this->assertEquals(2500.0, $overview['average_refresh_time']);
        $this->assertEquals(0, $overview['active_alerts']);
        $this->assertEquals('healthy', $overview['overall_health']);
    }

    /** @test */
    public function it_calculates_token_status_summary_correctly()
    {
        $users = User::factory()->count(15)->create();
        
        // Create tokens with different statuses
        GoogleDriveToken::factory()->create([
            'user_id' => $users[0]->id,
            'expires_at' => now()->addDays(7), // Valid
            'requires_user_intervention' => false,
            'refresh_failure_count' => 0
        ]);
        
        GoogleDriveToken::factory()->create([
            'user_id' => $users[1]->id,
            'expires_at' => now()->subHours(1), // Expired
            'requires_user_intervention' => false,
            'refresh_failure_count' => 1
        ]);
        
        GoogleDriveToken::factory()->create([
            'user_id' => $users[2]->id,
            'expires_at' => now()->addMinutes(30), // Expiring soon
            'requires_user_intervention' => false,
            'refresh_failure_count' => 0
        ]);
        
        GoogleDriveToken::factory()->create([
            'user_id' => $users[3]->id,
            'expires_at' => now()->addHours(12), // Expiring warning
            'requires_user_intervention' => false,
            'refresh_failure_count' => 0
        ]);
        
        GoogleDriveToken::factory()->create([
            'user_id' => $users[4]->id,
            'expires_at' => now()->addDays(1),
            'requires_user_intervention' => true, // Requires attention
            'refresh_failure_count' => 5
        ]);
        
        GoogleDriveToken::factory()->create([
            'user_id' => $users[5]->id,
            'expires_at' => now()->addDays(2),
            'requires_user_intervention' => false,
            'refresh_failure_count' => 3 // Multiple failures
        ]);

        // Mock monitoring service
        $this->monitoringService
            ->method('getPerformanceMetrics')
            ->willReturn([
                'refresh_operations' => [
                    'success_rate' => 1.0,
                    'average_duration_ms' => 2000
                ],
                'alerting_status' => ['alert_count' => 0], 
                'system_health' => ['overall_status' => 'healthy']
            ]);

        $dashboardData = $this->dashboardService->getDashboardData('google-drive');
        $tokenSummary = $dashboardData['token_status_summary'];

        $this->assertEquals(15, $tokenSummary['total_users']);
        $this->assertEquals(6, $tokenSummary['connected_users']);
        $this->assertEquals(9, $tokenSummary['disconnected_users']);
        $this->assertEquals(1, $tokenSummary['expired_tokens']);
        $this->assertEquals(1, $tokenSummary['expiring_soon']);
        $this->assertEquals(1, $tokenSummary['expiring_warning']);
        $this->assertEquals(1, $tokenSummary['requiring_attention']);
        $this->assertEquals(1, $tokenSummary['multiple_failures']);
        
        // Check health distribution
        $healthDist = $tokenSummary['health_distribution'];
        $this->assertArrayHasKey('healthy', $healthDist);
        $this->assertArrayHasKey('warning', $healthDist);
        $this->assertArrayHasKey('critical', $healthDist);
    }

    /** @test */
    public function it_generates_recent_operations_activity_feed()
    {
        $users = User::factory()->count(3)->create();
        
        // Create tokens with recent activity
        foreach ($users as $index => $user) {
            GoogleDriveToken::factory()->create([
                'user_id' => $user->id,
                'last_refresh_attempt_at' => now()->subMinutes($index * 10),
                'last_successful_refresh_at' => $index % 2 === 0 ? now()->subMinutes($index * 10) : null,
                'refresh_failure_count' => $index % 2 === 0 ? 0 : 2
            ]);
        }

        // Mock monitoring service
        $this->monitoringService
            ->method('getPerformanceMetrics')
            ->willReturn([
                'refresh_operations' => [
                    'success_rate' => 1.0,
                    'average_duration_ms' => 2000
                ],
                'alerting_status' => ['alert_count' => 0], 
                'system_health' => ['overall_status' => 'healthy']
            ]);

        $dashboardData = $this->dashboardService->getDashboardData('google-drive');
        $recentOps = $dashboardData['recent_operations'];

        $this->assertIsArray($recentOps);
        $this->assertCount(3, $recentOps);
        
        foreach ($recentOps as $operation) {
            $this->assertArrayHasKey('id', $operation);
            $this->assertArrayHasKey('type', $operation);
            $this->assertArrayHasKey('user_id', $operation);
            $this->assertArrayHasKey('user_email', $operation);
            $this->assertArrayHasKey('status', $operation);
            $this->assertArrayHasKey('timestamp', $operation);
            $this->assertArrayHasKey('details', $operation);
            
            $this->assertEquals('token_refresh', $operation['type']);
            $this->assertContains($operation['status'], ['success', 'failure']);
        }
    }

    /** @test */
    public function it_generates_health_trends_over_time()
    {
        // Mock monitoring service
        $this->monitoringService
            ->method('getPerformanceMetrics')
            ->willReturn([
                'refresh_operations' => [
                    'success_rate' => 1.0,
                    'average_duration_ms' => 2000
                ],
                'alerting_status' => ['alert_count' => 0], 
                'system_health' => ['overall_status' => 'healthy']
            ]);

        $dashboardData = $this->dashboardService->getDashboardData('google-drive', 12);
        $healthTrends = $dashboardData['health_trends'];

        $this->assertIsArray($healthTrends);
        $this->assertCount(12, $healthTrends); // 12 hours = 12 data points
        
        foreach ($healthTrends as $trend) {
            $this->assertArrayHasKey('timestamp', $trend);
            $this->assertArrayHasKey('success_rate', $trend);
            $this->assertArrayHasKey('average_duration', $trend);
            $this->assertArrayHasKey('cache_hit_rate', $trend);
            $this->assertArrayHasKey('active_operations', $trend);
            
            // Validate ranges
            $this->assertGreaterThanOrEqual(0, $trend['success_rate']);
            $this->assertLessThanOrEqual(1, $trend['success_rate']);
            $this->assertGreaterThan(0, $trend['average_duration']);
            $this->assertGreaterThanOrEqual(0, $trend['cache_hit_rate']);
            $this->assertLessThanOrEqual(1, $trend['cache_hit_rate']);
            $this->assertGreaterThanOrEqual(0, $trend['active_operations']);
        }
    }

    /** @test */
    public function it_calculates_user_statistics_by_role()
    {
        // Create users with different roles
        $adminUsers = User::factory()->count(2)->admin()->create();
        $employeeUsers = User::factory()->count(5)->employee()->create();
        
        // Connect some users
        GoogleDriveToken::factory()->create([
            'user_id' => $adminUsers[0]->id,
            'requires_user_intervention' => false,
            'refresh_failure_count' => 1
        ]);
        
        GoogleDriveToken::factory()->create([
            'user_id' => $employeeUsers[0]->id,
            'requires_user_intervention' => true,
            'refresh_failure_count' => 3
        ]);
        
        GoogleDriveToken::factory()->create([
            'user_id' => $employeeUsers[1]->id,
            'requires_user_intervention' => false,
            'refresh_failure_count' => 0
        ]);

        // Mock monitoring service
        $this->monitoringService
            ->method('getPerformanceMetrics')
            ->willReturn([
                'refresh_operations' => [
                    'success_rate' => 1.0,
                    'average_duration_ms' => 2000
                ],
                'alerting_status' => ['alert_count' => 0], 
                'system_health' => ['overall_status' => 'healthy']
            ]);

        $dashboardData = $this->dashboardService->getDashboardData('google-drive');
        $userStats = $dashboardData['user_statistics'];

        $this->assertArrayHasKey('admin', $userStats);
        $this->assertArrayHasKey('employee', $userStats);
        
        // Check admin stats
        $adminStats = $userStats['admin'];
        $this->assertEquals(2, $adminStats['total_users']);
        $this->assertEquals(1, $adminStats['connected_users']);
        $this->assertEquals(0.5, $adminStats['connection_rate']);
        $this->assertEquals(0, $adminStats['users_needing_attention']);
        
        // Check employee stats
        $employeeStats = $userStats['employee'];
        $this->assertEquals(5, $employeeStats['total_users']);
        $this->assertEquals(2, $employeeStats['connected_users']);
        $this->assertEquals(0.4, $employeeStats['connection_rate']);
        $this->assertEquals(1, $employeeStats['users_needing_attention']);
    }

    /** @test */
    public function it_provides_system_status_indicators()
    {
        // Mock monitoring service
        $this->monitoringService
            ->method('getPerformanceMetrics')
            ->willReturn([
                'refresh_operations' => [
                    'success_rate' => 1.0,
                    'average_duration_ms' => 2000
                ],
                'alerting_status' => ['alert_count' => 0],
                'system_health' => ['overall_status' => 'healthy'],
                'api_connectivity' => ['success_rate' => 0.95]
            ]);

        $dashboardData = $this->dashboardService->getDashboardData('google-drive');
        $systemStatus = $dashboardData['system_status'];

        $this->assertArrayHasKey('queue_health', $systemStatus);
        $this->assertArrayHasKey('cache_health', $systemStatus);
        $this->assertArrayHasKey('database_health', $systemStatus);
        $this->assertArrayHasKey('api_health', $systemStatus);
        $this->assertArrayHasKey('overall_system_health', $systemStatus);
        $this->assertArrayHasKey('last_maintenance', $systemStatus);
        $this->assertArrayHasKey('next_maintenance', $systemStatus);
        
        // Validate health status values
        $validStatuses = ['healthy', 'warning', 'degraded', 'critical', 'unknown'];
        $this->assertContains($systemStatus['queue_health'], $validStatuses);
        $this->assertContains($systemStatus['cache_health'], $validStatuses);
        $this->assertContains($systemStatus['database_health'], $validStatuses);
        $this->assertContains($systemStatus['api_health'], $validStatuses);
        $this->assertContains($systemStatus['overall_system_health'], $validStatuses);
    }

    /** @test */
    public function it_generates_recommendations_based_on_system_state()
    {
        // Mock high failure rate scenario
        $this->monitoringService
            ->method('getPerformanceMetrics')
            ->willReturn([
                'refresh_operations' => [
                    'failure_rate' => 0.15, // Above 10% threshold
                    'average_duration_ms' => 6000 // Above 5s threshold
                ],
                'health_validation' => [
                    'cache_miss_rate' => 0.6 // Above 50% threshold
                ],
                'alerting_status' => ['alert_count' => 2],
                'system_health' => ['overall_status' => 'warning']
            ]);

        // Create tokens requiring attention
        $users = User::factory()->count(10)->create();
        foreach ($users as $user) {
            GoogleDriveToken::factory()->create([
                'user_id' => $user->id,
                'requires_user_intervention' => true
            ]);
        }

        $dashboardData = $this->dashboardService->getDashboardData('google-drive');
        $recommendations = $dashboardData['recommendations'];

        $this->assertIsArray($recommendations);
        $this->assertGreaterThan(0, count($recommendations));
        
        // Should have recommendations for high failure rate, slow operations, 
        // high cache miss rate, and tokens needing attention
        $recommendationTypes = array_column($recommendations, 'type');
        $this->assertContains('high_failure_rate', $recommendationTypes);
        $this->assertContains('slow_operations', $recommendationTypes);
        $this->assertContains('high_cache_miss', $recommendationTypes);
        $this->assertContains('tokens_need_attention', $recommendationTypes);
        
        foreach ($recommendations as $recommendation) {
            $this->assertArrayHasKey('type', $recommendation);
            $this->assertArrayHasKey('severity', $recommendation);
            $this->assertArrayHasKey('title', $recommendation);
            $this->assertArrayHasKey('description', $recommendation);
            $this->assertArrayHasKey('actions', $recommendation);
            
            $this->assertContains($recommendation['severity'], ['info', 'warning', 'critical']);
            $this->assertIsArray($recommendation['actions']);
        }
    }

    /** @test */
    public function it_exports_metrics_with_metadata()
    {
        // Mock monitoring service
        $this->monitoringService
            ->method('getPerformanceMetrics')
            ->willReturn([
                'refresh_operations' => [
                    'success_rate' => 1.0,
                    'average_duration_ms' => 2000
                ],
                'alerting_status' => ['alert_count' => 0], 
                'system_health' => ['overall_status' => 'healthy']
            ]);

        $exportedData = $this->dashboardService->exportMetrics('google-drive', 'json');

        $this->assertArrayHasKey('export', $exportedData);
        $this->assertArrayHasKey('overview', $exportedData);
        $this->assertArrayHasKey('performance_metrics', $exportedData);
        
        $exportMeta = $exportedData['export'];
        $this->assertEquals('json', $exportMeta['format']);
        $this->assertEquals('google-drive', $exportMeta['provider']);
        $this->assertEquals('1.0', $exportMeta['version']);
        $this->assertArrayHasKey('exported_at', $exportMeta);
    }

    /** @test */
    public function it_handles_empty_token_data_gracefully()
    {
        // No tokens in database
        
        // Mock monitoring service
        $this->monitoringService
            ->method('getPerformanceMetrics')
            ->willReturn([
                'refresh_operations' => [
                    'total_operations' => 0,
                    'successful_operations' => 0,
                    'failed_operations' => 0,
                    'success_rate' => 1.0,
                    'failure_rate' => 0.0,
                    'average_duration_ms' => 0,
                    'error_breakdown' => []
                ],
                'health_validation' => [
                    'total_validations' => 0,
                    'cache_hits' => 0,
                    'cache_misses' => 0,
                    'cache_hit_rate' => 1.0,
                    'cache_miss_rate' => 0.0
                ],
                'api_connectivity' => [
                    'success_rate' => 1.0
                ],
                'alerting_status' => ['alert_count' => 0],
                'system_health' => ['overall_status' => 'healthy']
            ]);

        $dashboardData = $this->dashboardService->getDashboardData('google-drive');

        $this->assertIsArray($dashboardData);
        $this->assertEquals(0, $dashboardData['overview']['connected_users']);
        $this->assertEquals(0, $dashboardData['token_status_summary']['connected_users']);
        $this->assertEmpty($dashboardData['recent_operations']);
        
        // Should still have maintenance recommendation
        $this->assertGreaterThan(0, count($dashboardData['recommendations']));
        $this->assertEquals('maintenance', $dashboardData['recommendations'][0]['type']);
    }

    /** @test */
    public function it_caches_expensive_calculations()
    {
        // Create test data
        User::factory()->count(5)->create();
        
        // Mock monitoring service
        $this->monitoringService
            ->method('getPerformanceMetrics')
            ->willReturn([
                'refresh_operations' => [
                    'success_rate' => 1.0,
                    'average_duration_ms' => 2000
                ],
                'alerting_status' => ['alert_count' => 0], 
                'system_health' => ['overall_status' => 'healthy']
            ]);

        // First call should hit database
        $dashboardData1 = $this->dashboardService->getDashboardData('google-drive');
        
        // Second call should use cache (verify by checking cache keys exist)
        $this->assertTrue(Cache::has('token_status_summary:google-drive'));
        $this->assertTrue(Cache::has('user_statistics:google-drive'));
        
        $dashboardData2 = $this->dashboardService->getDashboardData('google-drive');
        
        // Results should be identical
        $this->assertEquals($dashboardData1['token_status_summary'], $dashboardData2['token_status_summary']);
        $this->assertEquals($dashboardData1['user_statistics'], $dashboardData2['user_statistics']);
    }
}