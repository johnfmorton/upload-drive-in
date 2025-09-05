<?php

namespace App\Services;

use App\Models\GoogleDriveToken;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Service for providing monitoring dashboard data for token health metrics
 * and system performance visualization
 */
class TokenMonitoringDashboardService
{
    public function __construct(
        private TokenRefreshMonitoringService $monitoringService
    ) {}

    /**
     * Get comprehensive dashboard data for token monitoring
     */
    public function getDashboardData(string $provider = 'google-drive', int $hours = 24): array
    {
        return [
            'overview' => $this->getOverviewMetrics($provider, $hours),
            'performance_metrics' => $this->monitoringService->getPerformanceMetrics($provider, $hours),
            'token_status_summary' => $this->getTokenStatusSummary($provider),
            'recent_operations' => $this->getRecentOperations($provider, 50),
            'health_trends' => $this->getHealthTrends($provider, $hours),
            'user_statistics' => $this->getUserStatistics($provider),
            'system_status' => $this->getSystemStatus($provider),
            'recommendations' => $this->getRecommendations($provider),
            'generated_at' => now()->toISOString()
        ];
    }

    /**
     * Get overview metrics for the dashboard header
     */
    private function getOverviewMetrics(string $provider, int $hours): array
    {
        $performanceMetrics = $this->monitoringService->getPerformanceMetrics($provider, $hours);
        $tokenStats = $this->getTokenStatusSummary($provider);
        
        return [
            'total_users' => $tokenStats['total_users'],
            'connected_users' => $tokenStats['connected_users'],
            'tokens_expiring_soon' => $tokenStats['expiring_soon'],
            'tokens_requiring_attention' => $tokenStats['requiring_attention'],
            'success_rate' => $performanceMetrics['refresh_operations']['success_rate'],
            'average_refresh_time' => $performanceMetrics['refresh_operations']['average_duration_ms'],
            'active_alerts' => $performanceMetrics['alerting_status']['alert_count'],
            'overall_health' => $performanceMetrics['system_health']['overall_status']
        ];
    }

    /**
     * Get token status summary across all users
     */
    private function getTokenStatusSummary(string $provider): array
    {
        $cacheKey = "token_status_summary:{$provider}";
        
        return Cache::remember($cacheKey, 300, function () use ($provider) {
            if ($provider !== 'google-drive') {
                return $this->getEmptyTokenSummary();
            }

            $now = now();
            $soonThreshold = $now->copy()->addMinutes(60); // Expiring within 1 hour
            $warningThreshold = $now->copy()->addHours(24); // Expiring within 24 hours

            $totalUsers = User::count();
            
            $tokenStats = GoogleDriveToken::selectRaw('
                COUNT(*) as total_tokens,
                COUNT(CASE WHEN expires_at IS NOT NULL AND expires_at > ? THEN 1 END) as valid_tokens,
                COUNT(CASE WHEN expires_at IS NOT NULL AND expires_at <= ? THEN 1 END) as expired_tokens,
                COUNT(CASE WHEN expires_at IS NOT NULL AND expires_at <= ? AND expires_at > ? THEN 1 END) as expiring_soon,
                COUNT(CASE WHEN expires_at IS NOT NULL AND expires_at <= ? AND expires_at > ? THEN 1 END) as expiring_warning,
                COUNT(CASE WHEN requires_user_intervention = 1 THEN 1 END) as requiring_attention,
                COUNT(CASE WHEN refresh_failure_count >= 3 THEN 1 END) as multiple_failures,
                AVG(refresh_failure_count) as avg_failure_count,
                MAX(last_successful_refresh_at) as last_successful_refresh
            ', [
                $now, // valid tokens
                $now, // expired tokens
                $soonThreshold, $now, // expiring soon
                $warningThreshold, $soonThreshold, // expiring warning
            ])->first();

            return [
                'total_users' => $totalUsers,
                'connected_users' => $tokenStats->total_tokens ?? 0,
                'disconnected_users' => $totalUsers - ($tokenStats->total_tokens ?? 0),
                'valid_tokens' => $tokenStats->valid_tokens ?? 0,
                'expired_tokens' => $tokenStats->expired_tokens ?? 0,
                'expiring_soon' => $tokenStats->expiring_soon ?? 0,
                'expiring_warning' => $tokenStats->expiring_warning ?? 0,
                'requiring_attention' => $tokenStats->requiring_attention ?? 0,
                'multiple_failures' => $tokenStats->multiple_failures ?? 0,
                'average_failure_count' => round($tokenStats->avg_failure_count ?? 0, 2),
                'last_successful_refresh' => $tokenStats->last_successful_refresh,
                'health_distribution' => $this->getTokenHealthDistribution()
            ];
        });
    }

    /**
     * Get token health distribution
     */
    private function getTokenHealthDistribution(): array
    {
        $now = now();
        
        return [
            'healthy' => GoogleDriveToken::where('expires_at', '>', $now->copy()->addHours(24))
                ->where('requires_user_intervention', false)
                ->where('refresh_failure_count', '<', 3)
                ->count(),
            'warning' => GoogleDriveToken::where('expires_at', '<=', $now->copy()->addHours(24))
                ->where('expires_at', '>', $now->copy()->addMinutes(60))
                ->where('requires_user_intervention', false)
                ->count(),
            'critical' => GoogleDriveToken::where(function ($query) use ($now) {
                $query->where('expires_at', '<=', $now->copy()->addMinutes(60))
                      ->orWhere('requires_user_intervention', true)
                      ->orWhere('refresh_failure_count', '>=', 3);
            })->count()
        ];
    }

    /**
     * Get recent operations for activity feed
     */
    private function getRecentOperations(string $provider, int $limit): array
    {
        // This would typically come from a dedicated operations log table
        // For now, we'll simulate recent operations based on token data
        $recentTokens = GoogleDriveToken::with('user')
            ->whereNotNull('last_refresh_attempt_at')
            ->orderBy('last_refresh_attempt_at', 'desc')
            ->limit($limit)
            ->get();

        return $recentTokens->map(function ($token) {
            $wasSuccessful = $token->last_successful_refresh_at && 
                           $token->last_successful_refresh_at->gte($token->last_refresh_attempt_at);
            
            return [
                'id' => uniqid('op_'),
                'type' => 'token_refresh',
                'user_id' => $token->user_id,
                'user_email' => $token->user->email ?? 'Unknown',
                'status' => $wasSuccessful ? 'success' : 'failure',
                'timestamp' => $token->last_refresh_attempt_at?->toISOString(),
                'duration_ms' => null, // Would come from monitoring service
                'error_type' => $wasSuccessful ? null : 'unknown',
                'details' => [
                    'failure_count' => $token->refresh_failure_count,
                    'requires_intervention' => $token->requires_user_intervention,
                    'expires_at' => $token->expires_at?->toISOString()
                ]
            ];
        })->toArray();
    }

    /**
     * Get health trends over time
     */
    private function getHealthTrends(string $provider, int $hours): array
    {
        $intervals = min($hours, 24); // Max 24 data points
        $intervalSize = max(1, $hours / $intervals);
        
        $trends = [];
        $now = now();
        
        for ($i = $intervals - 1; $i >= 0; $i--) {
            $timestamp = $now->copy()->subHours($i * $intervalSize);
            
            // Simulate trend data - in a real implementation, this would come from stored metrics
            $trends[] = [
                'timestamp' => $timestamp->toISOString(),
                'success_rate' => $this->simulateSuccessRate($timestamp),
                'average_duration' => $this->simulateAverageDuration($timestamp),
                'cache_hit_rate' => $this->simulateCacheHitRate($timestamp),
                'active_operations' => $this->simulateActiveOperations($timestamp)
            ];
        }
        
        return $trends;
    }

    /**
     * Get user statistics
     */
    private function getUserStatistics(string $provider): array
    {
        if ($provider !== 'google-drive') {
            return [];
        }

        return Cache::remember("user_statistics:{$provider}", 600, function () {
            $userStats = DB::table('users')
                ->leftJoin('google_drive_tokens', 'users.id', '=', 'google_drive_tokens.user_id')
                ->selectRaw('
                    users.role,
                    COUNT(users.id) as total_users,
                    COUNT(google_drive_tokens.id) as connected_users,
                    COUNT(CASE WHEN google_drive_tokens.requires_user_intervention = 1 THEN 1 END) as users_needing_attention,
                    AVG(google_drive_tokens.refresh_failure_count) as avg_failure_count
                ')
                ->groupBy('users.role')
                ->get();

            $statistics = [];
            foreach ($userStats as $stat) {
                $statistics[$stat->role] = [
                    'total_users' => $stat->total_users,
                    'connected_users' => $stat->connected_users,
                    'connection_rate' => $stat->total_users > 0 ? round($stat->connected_users / $stat->total_users, 4) : 0,
                    'users_needing_attention' => $stat->users_needing_attention,
                    'average_failure_count' => round($stat->avg_failure_count ?? 0, 2)
                ];
            }

            return $statistics;
        });
    }

    /**
     * Get system status indicators
     */
    private function getSystemStatus(string $provider): array
    {
        $performanceMetrics = $this->monitoringService->getPerformanceMetrics($provider, 1);
        
        return [
            'queue_health' => $this->getQueueHealth(),
            'cache_health' => $this->getCacheHealth(),
            'database_health' => $this->getDatabaseHealth(),
            'api_health' => $performanceMetrics['api_connectivity']['success_rate'] > 0.9 ? 'healthy' : 'degraded',
            'overall_system_health' => $performanceMetrics['system_health']['overall_status'],
            'last_maintenance' => $this->getLastMaintenanceTime(),
            'next_maintenance' => $this->getNextMaintenanceTime()
        ];
    }

    /**
     * Get recommendations based on current system state
     */
    private function getRecommendations(string $provider): array
    {
        $recommendations = [];
        $performanceMetrics = $this->monitoringService->getPerformanceMetrics($provider, 24);
        $tokenStats = $this->getTokenStatusSummary($provider);
        
        // High failure rate recommendation
        if ($performanceMetrics['refresh_operations']['failure_rate'] > 0.1) {
            $recommendations[] = [
                'type' => 'high_failure_rate',
                'severity' => 'critical',
                'title' => 'High Token Refresh Failure Rate',
                'description' => 'Token refresh failure rate is above 10%. Investigate API connectivity and token validity.',
                'actions' => [
                    'Check Google Drive API status',
                    'Review error logs for common failure patterns',
                    'Verify OAuth application configuration',
                    'Consider implementing additional retry logic'
                ]
            ];
        }
        
        // Many tokens requiring attention
        if ($tokenStats['requiring_attention'] > 5) {
            $recommendations[] = [
                'type' => 'tokens_need_attention',
                'severity' => 'warning',
                'title' => 'Multiple Tokens Require User Intervention',
                'description' => "{$tokenStats['requiring_attention']} tokens require user intervention for reconnection.",
                'actions' => [
                    'Notify affected users to reconnect their accounts',
                    'Review token expiration policies',
                    'Consider implementing automated user notifications',
                    'Check for common causes of token invalidation'
                ]
            ];
        }
        
        // High cache miss rate
        if ($performanceMetrics['health_validation']['cache_miss_rate'] > 0.5) {
            $recommendations[] = [
                'type' => 'high_cache_miss',
                'severity' => 'warning',
                'title' => 'High Health Validation Cache Miss Rate',
                'description' => 'Cache miss rate is above 50%, indicating frequent cache invalidation or insufficient caching.',
                'actions' => [
                    'Review cache TTL settings',
                    'Investigate cache invalidation patterns',
                    'Consider increasing cache duration for stable connections',
                    'Monitor Redis/cache server performance'
                ]
            ];
        }
        
        // Slow refresh operations
        if ($performanceMetrics['refresh_operations']['average_duration_ms'] > 5000) {
            $recommendations[] = [
                'type' => 'slow_operations',
                'severity' => 'warning',
                'title' => 'Slow Token Refresh Operations',
                'description' => 'Average refresh time exceeds 5 seconds, which may impact user experience.',
                'actions' => [
                    'Investigate network latency to Google APIs',
                    'Review token refresh implementation for optimization opportunities',
                    'Consider implementing connection pooling',
                    'Monitor Google API response times'
                ]
            ];
        }
        
        // Proactive maintenance recommendations
        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'maintenance',
                'severity' => 'info',
                'title' => 'System Running Smoothly',
                'description' => 'All metrics are within normal ranges. Consider these proactive maintenance tasks.',
                'actions' => [
                    'Review and clean up old failed refresh attempts',
                    'Update monitoring thresholds based on current performance',
                    'Test disaster recovery procedures',
                    'Review user feedback for potential improvements'
                ]
            ];
        }
        
        return $recommendations;
    }

    /**
     * Get empty token summary for unsupported providers
     */
    private function getEmptyTokenSummary(): array
    {
        return [
            'total_users' => 0,
            'connected_users' => 0,
            'disconnected_users' => 0,
            'valid_tokens' => 0,
            'expired_tokens' => 0,
            'expiring_soon' => 0,
            'expiring_warning' => 0,
            'requiring_attention' => 0,
            'multiple_failures' => 0,
            'average_failure_count' => 0,
            'last_successful_refresh' => null,
            'health_distribution' => ['healthy' => 0, 'warning' => 0, 'critical' => 0]
        ];
    }

    /**
     * Simulate success rate for trends (replace with real data)
     */
    private function simulateSuccessRate(Carbon $timestamp): float
    {
        // Simulate some variation in success rate
        $baseRate = 0.95;
        $variation = sin($timestamp->hour / 24 * 2 * pi()) * 0.05;
        return max(0.8, min(1.0, $baseRate + $variation));
    }

    /**
     * Simulate average duration for trends (replace with real data)
     */
    private function simulateAverageDuration(Carbon $timestamp): float
    {
        // Simulate some variation in duration
        $baseDuration = 2000; // 2 seconds
        $variation = cos($timestamp->hour / 24 * 2 * pi()) * 500;
        return max(1000, $baseDuration + $variation);
    }

    /**
     * Simulate cache hit rate for trends (replace with real data)
     */
    private function simulateCacheHitRate(Carbon $timestamp): float
    {
        // Simulate cache hit rate variation
        $baseRate = 0.85;
        $variation = sin(($timestamp->hour + 6) / 24 * 2 * pi()) * 0.1;
        return max(0.5, min(1.0, $baseRate + $variation));
    }

    /**
     * Simulate active operations for trends (replace with real data)
     */
    private function simulateActiveOperations(Carbon $timestamp): int
    {
        // Simulate business hours pattern
        $hour = $timestamp->hour;
        if ($hour >= 9 && $hour <= 17) {
            return rand(5, 20); // Business hours
        } else {
            return rand(0, 5); // Off hours
        }
    }

    /**
     * Get queue health status
     */
    private function getQueueHealth(): string
    {
        try {
            // Check if queue is processing jobs
            $failedJobs = DB::table('failed_jobs')->count();
            $pendingJobs = DB::table('jobs')->count();
            
            if ($failedJobs > 100) {
                return 'critical';
            } elseif ($failedJobs > 10 || $pendingJobs > 1000) {
                return 'warning';
            } else {
                return 'healthy';
            }
        } catch (\Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Get cache health status
     */
    private function getCacheHealth(): string
    {
        try {
            Cache::put('health_check', 'test', 10);
            $result = Cache::get('health_check');
            Cache::forget('health_check');
            
            return $result === 'test' ? 'healthy' : 'degraded';
        } catch (\Exception $e) {
            return 'critical';
        }
    }

    /**
     * Get database health status
     */
    private function getDatabaseHealth(): string
    {
        try {
            DB::select('SELECT 1');
            return 'healthy';
        } catch (\Exception $e) {
            return 'critical';
        }
    }

    /**
     * Get last maintenance time
     */
    private function getLastMaintenanceTime(): ?string
    {
        // This would typically come from a maintenance log
        return Cache::get('last_maintenance_time');
    }

    /**
     * Get next scheduled maintenance time
     */
    private function getNextMaintenanceTime(): string
    {
        // Schedule maintenance for next Sunday at 2 AM
        $nextSunday = now()->next('Sunday')->setTime(2, 0);
        return $nextSunday->toISOString();
    }

    /**
     * Export dashboard data for external monitoring systems
     */
    public function exportMetrics(string $provider, string $format = 'json'): array
    {
        $data = $this->getDashboardData($provider);
        
        // Add export metadata
        $data['export'] = [
            'format' => $format,
            'exported_at' => now()->toISOString(),
            'provider' => $provider,
            'version' => '1.0'
        ];
        
        return $data;
    }
}