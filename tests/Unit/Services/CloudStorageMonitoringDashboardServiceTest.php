<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CloudStorageMonitoringDashboardService;
use App\Services\CloudStorageLogService;
use App\Services\CloudStoragePerformanceMetricsService;
use App\Services\CloudStorageErrorTrackingService;
use App\Services\CloudStorageHealthService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class CloudStorageMonitoringDashboardServiceTest extends TestCase
{
    use RefreshDatabase;
    private CloudStorageMonitoringDashboardService $service;
    private $logService;
    private $performanceService;
    private $errorTrackingService;
    private $healthService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logService = Mockery::mock(CloudStorageLogService::class);
        $this->performanceService = Mockery::mock(CloudStoragePerformanceMetricsService::class);
        $this->errorTrackingService = Mockery::mock(CloudStorageErrorTrackingService::class);
        $this->healthService = Mockery::mock(CloudStorageHealthService::class);
        
        $this->service = new CloudStorageMonitoringDashboardService(
            $this->logService,
            $this->performanceService,
            $this->errorTrackingService,
            $this->healthService
        );
        
        $this->user = User::factory()->create();
    }

    public function test_gets_dashboard_data_successfully()
    {
        // Mock the service responses
        $this->logService->shouldReceive('getMetricsSummary')
            ->with('google-drive', 24)
            ->andReturn([
                'token_refresh' => ['attempts' => 10, 'successes' => 9, 'failures' => 1, 'success_rate' => 90],
                'api_connectivity' => ['successes' => 15, 'failures' => 2],
                'status_distribution' => ['healthy' => 8, 'authentication_required' => 2],
                'cache_performance' => ['hits' => 100, 'misses' => 10],
            ]);

        $this->performanceService->shouldReceive('getPerformanceSummary')
            ->with('google-drive', 24)
            ->andReturn([
                'provider' => 'google-drive',
                'overall' => [
                    'total_operations' => 25,
                    'successful_operations' => 23,
                    'failed_operations' => 2,
                    'success_rate' => 92.0,
                    'avg_response_time_ms' => 1500.0,
                ],
                'operations' => [],
            ]);

        $this->errorTrackingService->shouldReceive('getErrorStatistics')
            ->with('google-drive', $this->user, 24)
            ->andReturn([
                'total_errors' => 3,
                'error_rate_per_hour' => 0.125,
                'error_types' => ['network_error' => 2, 'timeout' => 1],
                'operations' => ['upload' => 2, 'delete' => 1],
            ]);

        $this->healthService->shouldReceive('getHealthStatus')
            ->with($this->user, 'google-drive')
            ->andReturn((object) [
                'consolidated_status' => 'healthy',
                'last_checked_at' => now(),
                'error_type' => null,
            ]);

        $this->errorTrackingService->shouldReceive('getActiveAlerts')
            ->with('google-drive', $this->user)
            ->andReturn([]);

        $dashboardData = $this->service->getDashboardData('google-drive', $this->user, 24);

        $this->assertEquals('google-drive', $dashboardData['provider']);
        $this->assertEquals($this->user->id, $dashboardData['user_id']);
        $this->assertEquals(24, $dashboardData['time_range']['hours']);
        $this->assertArrayHasKey('overview', $dashboardData);
        $this->assertArrayHasKey('performance', $dashboardData);
        $this->assertArrayHasKey('errors', $dashboardData);
        $this->assertArrayHasKey('health', $dashboardData);
        $this->assertArrayHasKey('alerts', $dashboardData);
        $this->assertArrayHasKey('trends', $dashboardData);
        $this->assertArrayHasKey('generated_at', $dashboardData);
    }

    public function test_gets_dashboard_data_without_user()
    {
        $this->logService->shouldReceive('getMetricsSummary')
            ->with('google-drive', 24)
            ->andReturn([
                'token_refresh' => ['attempts' => 10, 'successes' => 9, 'failures' => 1, 'success_rate' => 90],
                'api_connectivity' => ['successes' => 15, 'failures' => 2],
                'status_distribution' => ['healthy' => 8, 'authentication_required' => 2],
                'cache_performance' => ['hits' => 100, 'misses' => 10],
            ]);

        $dashboardData = $this->service->getDashboardData('google-drive', null, 24);

        $this->assertEquals('google-drive', $dashboardData['provider']);
        $this->assertNull($dashboardData['user_id']);
        $this->assertArrayHasKey('overview', $dashboardData);
    }

    public function test_gets_system_dashboard_data()
    {
        $systemData = $this->service->getSystemDashboardData(24);

        $this->assertEquals(24, $systemData['time_range']['hours']);
        $this->assertArrayHasKey('system_overview', $systemData);
        $this->assertArrayHasKey('provider_comparison', $systemData);
        $this->assertArrayHasKey('top_issues', $systemData);
        $this->assertArrayHasKey('system_health', $systemData);
        $this->assertArrayHasKey('capacity_metrics', $systemData);
        $this->assertArrayHasKey('generated_at', $systemData);
    }

    public function test_gets_real_time_data()
    {
        $realTimeData = $this->service->getRealTimeData('google-drive', $this->user);

        $this->assertEquals('google-drive', $realTimeData['provider']);
        $this->assertEquals($this->user->id, $realTimeData['user_id']);
        $this->assertArrayHasKey('timestamp', $realTimeData);
        $this->assertArrayHasKey('current_status', $realTimeData);
        $this->assertArrayHasKey('active_operations', $realTimeData);
        $this->assertArrayHasKey('recent_errors', $realTimeData);
        $this->assertArrayHasKey('performance_snapshot', $realTimeData);
        $this->assertArrayHasKey('alert_status', $realTimeData);
    }

    public function test_gets_historical_trends()
    {
        $trends = $this->service->getHistoricalTrends('google-drive', $this->user, 7);

        $this->assertEquals('google-drive', $trends['provider']);
        $this->assertEquals($this->user->id, $trends['user_id']);
        $this->assertEquals(7, $trends['period_days']);
        $this->assertArrayHasKey('trends', $trends);
        $this->assertArrayHasKey('operation_volume', $trends['trends']);
        $this->assertArrayHasKey('success_rate', $trends['trends']);
        $this->assertArrayHasKey('response_time', $trends['trends']);
        $this->assertArrayHasKey('error_rate', $trends['trends']);
        $this->assertArrayHasKey('throughput', $trends['trends']);
        $this->assertArrayHasKey('generated_at', $trends);
    }

    public function test_gets_provider_health_summary()
    {
        // Mock User::all() to return test users
        $users = collect([
            $this->user,
            User::factory()->create(),
        ]);

        // Mock the health service calls
        $this->healthService->shouldReceive('getHealthStatus')
            ->twice()
            ->andReturn(
                (object) [
                    'consolidated_status' => 'healthy',
                    'last_checked_at' => now(),
                    'error_type' => null,
                ],
                (object) [
                    'consolidated_status' => 'authentication_required',
                    'last_checked_at' => now()->subMinutes(5),
                    'error_type' => null,
                ]
            );

        $this->performanceService->shouldReceive('getProviderHealthScore')
            ->twice()
            ->andReturn(['health_score' => 95.0], ['health_score' => 75.0]);

        // Use reflection to call the private method for testing
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getUsersForProvider');
        $method->setAccessible(true);
        
        // Mock the getUsersForProvider method by overriding it
        $service = Mockery::mock(CloudStorageMonitoringDashboardService::class)->makePartial();
        $service->shouldReceive('getUsersForProvider')
            ->with('google-drive')
            ->andReturn($users->toArray());

        $service->__construct(
            $this->logService,
            $this->performanceService,
            $this->errorTrackingService,
            $this->healthService
        );

        $healthSummary = $service->getProviderHealthSummary('google-drive');

        $this->assertEquals('google-drive', $healthSummary['provider']);
        $this->assertEquals(2, $healthSummary['total_users']);
        $this->assertArrayHasKey('health_distribution', $healthSummary);
        $this->assertArrayHasKey('user_details', $healthSummary);
        $this->assertArrayHasKey('overall_health_score', $healthSummary);
        $this->assertArrayHasKey('generated_at', $healthSummary);
    }

    public function test_calculates_overview_metrics_correctly()
    {
        $this->logService->shouldReceive('getMetricsSummary')
            ->with('google-drive', 24)
            ->andReturn([
                'token_refresh' => ['attempts' => 20, 'successes' => 18, 'failures' => 2, 'success_rate' => 90],
                'api_connectivity' => ['successes' => 30, 'failures' => 5],
                'status_distribution' => ['healthy' => 15, 'authentication_required' => 3],
                'cache_performance' => ['hits' => 200, 'misses' => 20],
            ]);

        // Use reflection to test the private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getOverviewMetrics');
        $method->setAccessible(true);

        $overview = $method->invoke($this->service, 'google-drive', $this->user, 24);

        $this->assertEquals(55, $overview['total_operations']); // 20 + 30 + 5
        $this->assertEquals(90, $overview['success_rate']);
        $this->assertEquals(7, $overview['total_errors']); // 2 + 5
        $this->assertArrayHasKey('uptime_percentage', $overview);
        $this->assertArrayHasKey('active_users', $overview);
    }

    public function test_handles_service_errors_gracefully()
    {
        // Mock services to throw exceptions
        $this->logService->shouldReceive('getMetricsSummary')
            ->andThrow(new \Exception('Service unavailable'));

        $this->performanceService->shouldReceive('getPerformanceSummary')
            ->andThrow(new \Exception('Performance service error'));

        $this->errorTrackingService->shouldReceive('getErrorStatistics')
            ->andThrow(new \Exception('Error tracking service error'));

        $this->healthService->shouldReceive('getHealthStatus')
            ->andThrow(new \Exception('Health service error'));

        $this->errorTrackingService->shouldReceive('getActiveAlerts')
            ->andReturn([]);

        // The service should handle errors gracefully and still return data
        $dashboardData = $this->service->getDashboardData('google-drive', $this->user, 24);

        $this->assertEquals('google-drive', $dashboardData['provider']);
        $this->assertArrayHasKey('health', $dashboardData);
        // Health should show error state
        $this->assertEquals('unknown', $dashboardData['health']['status']);
        $this->assertFalse($dashboardData['health']['is_healthy']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}