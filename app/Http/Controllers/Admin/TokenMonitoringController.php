<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TokenMonitoringDashboardService;
use App\Services\TokenRefreshMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for token monitoring dashboard and API endpoints
 */
class TokenMonitoringController extends Controller
{
    public function __construct(
        private TokenMonitoringDashboardService $dashboardService,
        private TokenRefreshMonitoringService $monitoringService
    ) {}

    /**
     * Display the token monitoring dashboard
     */
    public function dashboard(Request $request): View
    {
        $provider = $request->get('provider', 'google-drive');
        $hours = (int) $request->get('hours', 24);
        
        $dashboardData = $this->dashboardService->getDashboardData($provider, $hours);
        
        return view('admin.token-monitoring.dashboard', [
            'dashboardData' => $dashboardData,
            'provider' => $provider,
            'hours' => $hours
        ]);
    }

    /**
     * Get dashboard data as JSON for AJAX updates
     */
    public function dashboardData(Request $request): JsonResponse
    {
        $provider = $request->get('provider', 'google-drive');
        $hours = (int) $request->get('hours', 24);
        
        $dashboardData = $this->dashboardService->getDashboardData($provider, $hours);
        
        return response()->json($dashboardData);
    }

    /**
     * Get performance metrics
     */
    public function performanceMetrics(Request $request): JsonResponse
    {
        $provider = $request->get('provider', 'google-drive');
        $hours = (int) $request->get('hours', 24);
        
        $metrics = $this->monitoringService->getPerformanceMetrics($provider, $hours);
        
        return response()->json($metrics);
    }

    /**
     * Get log analysis queries
     */
    public function logAnalysisQueries(): JsonResponse
    {
        $queries = $this->monitoringService->getLogAnalysisQueries();
        
        return response()->json($queries);
    }

    /**
     * Export dashboard data
     */
    public function exportData(Request $request): JsonResponse
    {
        $provider = $request->get('provider', 'google-drive');
        $format = $request->get('format', 'json');
        
        $exportData = $this->dashboardService->exportMetrics($provider, $format);
        
        $filename = "token-monitoring-{$provider}-" . now()->format('Y-m-d-H-i-s') . ".{$format}";
        
        return response()->json($exportData)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Reset metrics (for testing/maintenance)
     */
    public function resetMetrics(Request $request): JsonResponse
    {
        $provider = $request->get('provider', 'google-drive');
        
        $this->monitoringService->resetMetrics($provider);
        
        return response()->json([
            'success' => true,
            'message' => __('messages.token_monitoring.metrics_reset_success', ['provider' => $provider]),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get real-time system status
     */
    public function systemStatus(Request $request): JsonResponse
    {
        $provider = $request->get('provider', 'google-drive');
        
        $metrics = $this->monitoringService->getPerformanceMetrics($provider, 1); // Last hour
        
        return response()->json([
            'status' => $metrics['system_health']['overall_status'],
            'alerts' => $metrics['alerting_status']['active_alerts'],
            'alert_count' => $metrics['alerting_status']['alert_count'],
            'last_updated' => now()->toISOString()
        ]);
    }

    /**
     * Get health trends data for charts
     */
    public function healthTrends(Request $request): JsonResponse
    {
        $provider = $request->get('provider', 'google-drive');
        $hours = (int) $request->get('hours', 24);
        
        $dashboardData = $this->dashboardService->getDashboardData($provider, $hours);
        
        return response()->json($dashboardData['health_trends']);
    }

    /**
     * Get recent operations for activity feed
     */
    public function recentOperations(Request $request): JsonResponse
    {
        $provider = $request->get('provider', 'google-drive');
        $limit = (int) $request->get('limit', 50);
        
        $dashboardData = $this->dashboardService->getDashboardData($provider, 24);
        $operations = array_slice($dashboardData['recent_operations'], 0, $limit);
        
        return response()->json($operations);
    }
}