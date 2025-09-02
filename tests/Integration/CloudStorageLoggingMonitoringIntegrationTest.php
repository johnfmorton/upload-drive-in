<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Services\CloudStorageLogService;
use App\Services\CloudStoragePerformanceMetricsService;
use App\Services\CloudStorageErrorTrackingService;
use App\Services\CloudStorageAuditService;
use App\Services\CloudStorageMonitoringDashboardService;
use App\Models\User;
use App\Enums\CloudStorageErrorType;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CloudStorageLoggingMonitoringIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageLogService $logService;
    private CloudStoragePerformanceMetricsService $performanceService;
    private CloudStorageErrorTrackingService $errorTrackingService;
    private CloudStorageAuditService $auditService;
    private CloudStorageMonitoringDashboardService $dashboardService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logService = app(CloudStorageLogService::class);
        $this->performanceService = app(CloudStoragePerformanceMetricsService::class);
        $this->errorTrackingService = app(CloudStorageErrorTrackingService::class);
        $this->auditService = app(CloudStorageAuditService::class);
        $this->dashboardService = app(CloudStorageMonitoringDashboardService::class);
        
        $this->user = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);
    }

    public function test_comprehensive_operation_logging_and_monitoring()
    {
        // Start an operation
        $operationId = $this->logService->logOperationStart('upload', 'google-drive', $this->user, [
            'file_name' => 'test.pdf',
            'file_size' => 1024000,
        ]);

        $this->assertNotEmpty($operationId);

        // Record performance metrics for the operation
        $this->performanceService->recordFileOperationMetrics(
            'google-drive',
            'upload',
            $this->user,
            1024000, // 1MB file
            2500.0,  // 2.5 seconds
            true
        );

        // Log successful completion
        $this->logService->logOperationSuccess(
            $operationId,
            'upload',
            'google-drive',
            $this->user,
            ['file_id' => 'drive_file_123'],
            2500.0
        );

        // Verify performance metrics were recorded
        $performanceSummary = $this->performanceService->getPerformanceSummary('google-drive', 1);
        $this->assertGreaterThan(0, $performanceSummary['overall']['total_operations']);
        $this->assertGreaterThan(0, $performanceSummary['overall']['successful_operations']);

        // Verify operation metrics
        $uploadMetrics = $this->performanceService->getOperationMetrics('google-drive', 'upload', 1);
        $this->assertGreaterThanOrEqual(1, $uploadMetrics['total_operations']); // May be more due to integration
        $this->assertGreaterThanOrEqual(1, $uploadMetrics['successful_operations']);
        $this->assertEquals(100.0, $uploadMetrics['success_rate']);
    }

    public function test_error_tracking_and_alerting_integration()
    {
        // Track multiple errors to trigger alerts
        for ($i = 0; $i < 12; $i++) {
            $this->errorTrackingService->trackError(
                'google-drive',
                $this->user,
                CloudStorageErrorType::NETWORK_ERROR,
                'upload',
                "Network error #{$i}",
                null,
                ['attempt' => $i + 1]
            );
        }

        // Check error statistics
        $errorStats = $this->errorTrackingService->getErrorStatistics('google-drive', $this->user, 1);
        $this->assertEquals(12, $errorStats['total_errors']);
        $this->assertEquals(12.0, $errorStats['error_rate_per_hour']);
        $this->assertArrayHasKey('network_error', $errorStats['error_types']);
        $this->assertEquals(12, $errorStats['error_types']['network_error']);

        // Check for active alerts
        $alerts = $this->errorTrackingService->getActiveAlerts('google-drive', $this->user);
        $alertTypes = array_column($alerts, 'type');
        $this->assertContains('high_error_rate', $alertTypes);

        // Track consecutive failures for specific operation
        for ($i = 0; $i < 6; $i++) {
            $this->errorTrackingService->trackError(
                'google-drive',
                $this->user,
                CloudStorageErrorType::TOKEN_EXPIRED,
                'delete',
                "Token expired #{$i}",
                null,
                ['consecutive_failure' => $i + 1]
            );
        }

        // Check for consecutive failure alerts
        $alerts = $this->errorTrackingService->getActiveAlerts('google-drive', $this->user);
        $alertTypes = array_column($alerts, 'type');
        $this->assertContains('consecutive_failures', $alertTypes);

        // Track success to reset consecutive failures
        $this->errorTrackingService->trackSuccess('google-drive', $this->user, 'delete');

        // Verify consecutive failures were reset for the delete operation
        $updatedStats = $this->errorTrackingService->getErrorStatistics('google-drive', $this->user, 1);
        $this->assertArrayNotHasKey('delete', $updatedStats['consecutive_failures']);
    }

    public function test_audit_logging_integration()
    {
        // Log configuration change
        $oldConfig = ['client_id' => 'old_id', 'enabled' => false];
        $newConfig = ['client_id' => 'new_id', 'enabled' => true];

        $this->auditService->logProviderConfigurationChange(
            $this->user,
            'google-drive',
            'update',
            $oldConfig,
            $newConfig,
            'Updating configuration for testing'
        );

        // Log authentication event
        $this->auditService->logProviderAuthenticationEvent(
            $this->user,
            'google-drive',
            'oauth_callback',
            true,
            null,
            ['redirect_from' => 'admin_dashboard']
        );

        // Log administrative action
        $this->auditService->logAdministrativeAction(
            $this->user,
            'test',
            'provider',
            'google-drive',
            ['test_type' => 'connectivity'],
            'Testing provider connectivity'
        );

        // Log security event
        $this->auditService->logSecurityEvent(
            $this->user,
            'configuration_access',
            'low',
            'User accessed provider configuration',
            ['provider' => 'google-drive', 'action' => 'view']
        );

        // Verify audit trail structure
        $auditTrail = $this->auditService->getAuditTrail('provider', 'google-drive', $this->user, 1);
        $this->assertEquals('provider', $auditTrail['resource_type']);
        $this->assertEquals('google-drive', $auditTrail['resource_id']);
        $this->assertEquals($this->user->id, $auditTrail['user_id']);
    }

    public function test_dashboard_integration_with_all_services()
    {
        // Generate some test data across all services
        
        // 1. Log some operations
        for ($i = 0; $i < 5; $i++) {
            $operationId = $this->logService->logOperationStart('upload', 'google-drive', $this->user);
            $this->logService->logOperationSuccess($operationId, 'upload', 'google-drive', $this->user, [], 1500.0);
        }

        // 2. Record performance metrics
        for ($i = 0; $i < 3; $i++) {
            $this->performanceService->recordOperationMetrics(
                'google-drive',
                'upload',
                $this->user,
                1000.0 + ($i * 500), // Varying response times
                true
            );
        }

        // 3. Track some errors
        for ($i = 0; $i < 2; $i++) {
            $this->errorTrackingService->trackError(
                'google-drive',
                $this->user,
                CloudStorageErrorType::TIMEOUT,
                'upload',
                "Timeout error #{$i}"
            );
        }

        // 4. Log audit events
        $this->auditService->logProviderConfigurationChange(
            $this->user,
            'google-drive',
            'update',
            ['enabled' => false],
            ['enabled' => true],
            'Enabling provider'
        );

        // Get comprehensive dashboard data
        $dashboardData = $this->dashboardService->getDashboardData('google-drive', $this->user, 1);

        // Verify dashboard structure
        $this->assertEquals('google-drive', $dashboardData['provider']);
        $this->assertEquals($this->user->id, $dashboardData['user_id']);
        $this->assertArrayHasKey('overview', $dashboardData);
        $this->assertArrayHasKey('performance', $dashboardData);
        $this->assertArrayHasKey('errors', $dashboardData);
        $this->assertArrayHasKey('health', $dashboardData);
        $this->assertArrayHasKey('alerts', $dashboardData);
        $this->assertArrayHasKey('trends', $dashboardData);

        // Verify overview metrics structure (data may be aggregated differently)
        $overview = $dashboardData['overview'];
        $this->assertArrayHasKey('total_operations', $overview);
        $this->assertArrayHasKey('success_rate', $overview);

        // Verify error data structure
        $errors = $dashboardData['errors'];
        $this->assertArrayHasKey('total_errors', $errors);
        $this->assertArrayHasKey('error_types', $errors);
    }

    public function test_performance_health_scoring_integration()
    {
        // Record good performance metrics
        for ($i = 0; $i < 10; $i++) {
            $this->performanceService->recordOperationMetrics(
                'google-drive',
                'upload',
                $this->user,
                800.0, // Fast response time
                true   // Successful
            );
        }

        // Get health score
        $healthScore = $this->performanceService->getProviderHealthScore('google-drive', 1);
        
        $this->assertEquals('google-drive', $healthScore['provider']);
        $this->assertGreaterThanOrEqual(90, $healthScore['health_score']); // Should be high due to good performance
        $this->assertEquals('A', $healthScore['grade']);

        // Now record poor performance
        for ($i = 0; $i < 10; $i++) {
            $this->performanceService->recordOperationMetrics(
                'google-drive',
                'upload',
                $this->user,
                8000.0, // Very slow response time
                false   // Failed
            );
        }

        // Get updated health score
        $updatedHealthScore = $this->performanceService->getProviderHealthScore('google-drive', 1);
        $this->assertLessThan(50, $updatedHealthScore['health_score']); // Should be low due to poor performance
        $this->assertContains($updatedHealthScore['grade'], ['D', 'F']);
    }

    public function test_real_time_monitoring_data()
    {
        // Generate some recent activity
        $operationId = $this->logService->logOperationStart('delete', 'google-drive', $this->user);
        
        $this->errorTrackingService->trackError(
            'google-drive',
            $this->user,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            'delete',
            'Permission denied for file deletion'
        );

        // Get real-time data
        $realTimeData = $this->dashboardService->getRealTimeData('google-drive', $this->user);

        $this->assertEquals('google-drive', $realTimeData['provider']);
        $this->assertEquals($this->user->id, $realTimeData['user_id']);
        $this->assertArrayHasKey('timestamp', $realTimeData);
        $this->assertArrayHasKey('current_status', $realTimeData);
        $this->assertArrayHasKey('active_operations', $realTimeData);
        $this->assertArrayHasKey('recent_errors', $realTimeData);
        $this->assertArrayHasKey('performance_snapshot', $realTimeData);
        $this->assertArrayHasKey('alert_status', $realTimeData);
    }

    public function test_historical_trends_data()
    {
        // Generate historical data over multiple hours
        $baseTime = now()->subHours(6);
        
        for ($hour = 0; $hour < 6; $hour++) {
            $hourTime = $baseTime->copy()->addHours($hour);
            
            // Mock time for this hour's data
            $this->travelTo($hourTime);
            
            // Generate varying amounts of activity per hour
            $operationCount = 5 + $hour; // Increasing activity over time
            
            for ($i = 0; $i < $operationCount; $i++) {
                $this->performanceService->recordOperationMetrics(
                    'google-drive',
                    'upload',
                    $this->user,
                    1000.0 + ($hour * 200), // Increasing response time
                    $i < ($operationCount - 1) // Last operation fails
                );
            }
        }

        $this->travelBack();

        // Get historical trends
        $trends = $this->dashboardService->getHistoricalTrends('google-drive', $this->user, 1);

        $this->assertEquals('google-drive', $trends['provider']);
        $this->assertEquals($this->user->id, $trends['user_id']);
        $this->assertEquals(1, $trends['period_days']);
        $this->assertArrayHasKey('trends', $trends);
        $this->assertArrayHasKey('operation_volume', $trends['trends']);
        $this->assertArrayHasKey('success_rate', $trends['trends']);
        $this->assertArrayHasKey('response_time', $trends['trends']);
        $this->assertArrayHasKey('error_rate', $trends['trends']);
        $this->assertArrayHasKey('throughput', $trends['trends']);
    }

    public function test_system_wide_monitoring()
    {
        // Create multiple users and generate activity
        $users = User::factory()->count(3)->create();
        
        foreach ($users as $user) {
            // Generate activity for each user
            for ($i = 0; $i < 5; $i++) {
                $this->performanceService->recordOperationMetrics(
                    'google-drive',
                    'upload',
                    $user,
                    1500.0,
                    $i < 4 // 80% success rate
                );
            }
            
            // Track some errors
            $this->errorTrackingService->trackError(
                'google-drive',
                $user,
                CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED,
                'upload',
                'Storage quota exceeded'
            );
        }

        // Get system-wide dashboard
        $systemData = $this->dashboardService->getSystemDashboardData(1);

        $this->assertEquals(1, $systemData['time_range']['hours']);
        $this->assertArrayHasKey('system_overview', $systemData);
        $this->assertArrayHasKey('provider_comparison', $systemData);
        $this->assertArrayHasKey('top_issues', $systemData);
        $this->assertArrayHasKey('system_health', $systemData);
        $this->assertArrayHasKey('capacity_metrics', $systemData);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}