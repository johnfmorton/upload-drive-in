<?php

namespace App\Services;

use App\Models\User;
use App\Models\CloudStorageHealthStatus;
use App\Notifications\CloudStorageConnectionAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class CloudStorageProviderMonitoringService
{
    public function __construct(
        private readonly CloudStorageProviderHealthCheckService $healthCheckService,
        private readonly CloudStorageConfigurationValidationService $configValidationService,
        private readonly CloudStorageHealthService $healthService,
        private readonly CloudStorageLogService $logService
    ) {}

    /**
     * Monitor all providers and send alerts for issues
     */
    public function monitorAllProviders(): array
    {
        Log::info('Starting cloud storage provider monitoring');

        $results = [
            'monitoring_timestamp' => now()->toISOString(),
            'providers_monitored' => 0,
            'users_monitored' => 0,
            'alerts_sent' => 0,
            'issues_detected' => [],
            'summary' => [
                'critical_issues' => 0,
                'warning_issues' => 0,
                'providers_down' => 0,
                'users_affected' => 0,
            ],
        ];

        try {
            // 1. Check provider-level health
            $providerResults = $this->monitorProviderHealth();
            $results['providers_monitored'] = count($providerResults);
            
            // 2. Check user-level health
            $userResults = $this->monitorUserHealth();
            $results['users_monitored'] = count($userResults);
            
            // 3. Detect and categorize issues
            $issues = $this->detectIssues($providerResults, $userResults);
            $results['issues_detected'] = $issues;
            
            // 4. Send alerts for critical issues
            $alertResults = $this->sendAlertsForIssues($issues);
            $results['alerts_sent'] = $alertResults['total_alerts'];
            
            // 5. Calculate summary
            $results['summary'] = $this->calculateMonitoringSummary($issues, $providerResults, $userResults);
            
            // 6. Log monitoring results
            $this->logMonitoringResults($results);

        } catch (\Exception $e) {
            Log::error('Provider monitoring failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $results['error'] = $e->getMessage();
        }

        Log::info('Cloud storage provider monitoring completed', [
            'providers_monitored' => $results['providers_monitored'],
            'users_monitored' => $results['users_monitored'],
            'alerts_sent' => $results['alerts_sent'],
            'critical_issues' => $results['summary']['critical_issues'],
        ]);

        return $results;
    }

    /**
     * Monitor provider-level health
     */
    private function monitorProviderHealth(): array
    {
        $results = [];
        $configService = app(CloudConfigurationService::class);
        $supportedProviders = $configService->getSupportedProviders();

        foreach ($supportedProviders as $providerName) {
            try {
                $health = $this->healthCheckService->checkProviderHealth($providerName);
                $results[$providerName] = $health;
                
                // Cache provider health for quick access
                Cache::put("provider_health_{$providerName}", $health, now()->addMinutes(5));
                
            } catch (\Exception $e) {
                Log::error('Provider health monitoring failed', [
                    'provider' => $providerName,
                    'error' => $e->getMessage(),
                ]);
                
                $results[$providerName] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'last_checked' => now()->toISOString(),
                ];
            }
        }

        return $results;
    }

    /**
     * Monitor user-level health
     */
    private function monitorUserHealth(): array
    {
        $results = [];
        
        // Get users who have cloud storage connections
        $users = User::whereHas('cloudStorageHealthStatuses')->get();
        
        foreach ($users as $user) {
            try {
                $health = $this->healthCheckService->checkUserHealth($user);
                $results[$user->id] = $health;
                
                // Cache user health for quick access
                Cache::put("user_health_{$user->id}", $health, now()->addMinutes(5));
                
            } catch (\Exception $e) {
                Log::error('User health monitoring failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                
                $results[$user->id] = [
                    'user_id' => $user->id,
                    'overall_status' => 'error',
                    'error' => $e->getMessage(),
                    'last_checked' => now()->toISOString(),
                ];
            }
        }

        return $results;
    }

    /**
     * Detect issues from monitoring results
     */
    private function detectIssues(array $providerResults, array $userResults): array
    {
        $issues = [
            'critical' => [],
            'warning' => [],
            'info' => [],
        ];

        // Detect provider-level issues
        foreach ($providerResults as $provider => $health) {
            if ($health['status'] === 'error' || $health['status'] === 'connectivity_failed') {
                $issues['critical'][] = [
                    'type' => 'provider_down',
                    'provider' => $provider,
                    'message' => "Provider {$provider} is not operational",
                    'details' => $health['errors'] ?? [],
                    'timestamp' => now()->toISOString(),
                ];
            } elseif ($health['status'] === 'configuration_invalid') {
                $issues['warning'][] = [
                    'type' => 'provider_misconfigured',
                    'provider' => $provider,
                    'message' => "Provider {$provider} has configuration issues",
                    'details' => $health['errors'] ?? [],
                    'timestamp' => now()->toISOString(),
                ];
            } elseif (!$health['is_enabled'] && $health['is_configured']) {
                $issues['info'][] = [
                    'type' => 'provider_disabled',
                    'provider' => $provider,
                    'message' => "Provider {$provider} is configured but disabled",
                    'timestamp' => now()->toISOString(),
                ];
            }
        }

        // Detect user-level issues
        foreach ($userResults as $userId => $health) {
            if ($health['overall_status'] === 'unhealthy') {
                $issues['critical'][] = [
                    'type' => 'user_all_providers_down',
                    'user_id' => $userId,
                    'user_email' => $health['user_email'] ?? 'Unknown',
                    'message' => "User {$health['user_email']} has no working cloud storage connections",
                    'providers' => array_keys($health['providers'] ?? []),
                    'timestamp' => now()->toISOString(),
                ];
            } elseif ($health['overall_status'] === 'partially_healthy') {
                $unhealthyProviders = array_filter($health['providers'] ?? [], function ($provider) {
                    return !$provider['is_healthy'];
                });
                
                if (count($unhealthyProviders) > 0) {
                    $issues['warning'][] = [
                        'type' => 'user_some_providers_down',
                        'user_id' => $userId,
                        'user_email' => $health['user_email'] ?? 'Unknown',
                        'message' => "User {$health['user_email']} has some cloud storage connection issues",
                        'unhealthy_providers' => array_keys($unhealthyProviders),
                        'timestamp' => now()->toISOString(),
                    ];
                }
            }
        }

        // Detect system-wide issues
        $healthyProviders = array_filter($providerResults, function ($health) {
            return $health['status'] === 'healthy';
        });

        if (empty($healthyProviders) && !empty($providerResults)) {
            $issues['critical'][] = [
                'type' => 'system_no_healthy_providers',
                'message' => 'No healthy cloud storage providers available system-wide',
                'providers' => array_keys($providerResults),
                'timestamp' => now()->toISOString(),
            ];
        }

        return $issues;
    }

    /**
     * Send alerts for detected issues
     */
    private function sendAlertsForIssues(array $issues): array
    {
        $results = [
            'total_alerts' => 0,
            'critical_alerts' => 0,
            'warning_alerts' => 0,
            'failed_alerts' => 0,
        ];

        // Send critical alerts immediately
        foreach ($issues['critical'] as $issue) {
            try {
                $this->sendCriticalAlert($issue);
                $results['critical_alerts']++;
                $results['total_alerts']++;
            } catch (\Exception $e) {
                Log::error('Failed to send critical alert', [
                    'issue' => $issue,
                    'error' => $e->getMessage(),
                ]);
                $results['failed_alerts']++;
            }
        }

        // Send warning alerts (with rate limiting)
        foreach ($issues['warning'] as $issue) {
            try {
                if ($this->shouldSendWarningAlert($issue)) {
                    $this->sendWarningAlert($issue);
                    $results['warning_alerts']++;
                    $results['total_alerts']++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to send warning alert', [
                    'issue' => $issue,
                    'error' => $e->getMessage(),
                ]);
                $results['failed_alerts']++;
            }
        }

        return $results;
    }

    /**
     * Send critical alert
     */
    private function sendCriticalAlert(array $issue): void
    {
        // Rate limit critical alerts to prevent spam
        $cacheKey = "critical_alert_" . md5(json_encode($issue));
        if (Cache::has($cacheKey)) {
            return; // Already sent recently
        }

        // Send to administrators
        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            try {
                $admin->notify(new CloudStorageConnectionAlert(
                    $issue['provider'] ?? 'system',
                    'critical_issue',
                    null,
                    $issue
                ));
            } catch (\Exception $e) {
                Log::error('Failed to send critical alert to admin', [
                    'admin_id' => $admin->id,
                    'issue' => $issue,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Cache to prevent duplicate alerts for 1 hour
        Cache::put($cacheKey, true, now()->addHour());

        Log::critical('Critical cloud storage issue detected', $issue);
    }

    /**
     * Send warning alert
     */
    private function sendWarningAlert(array $issue): void
    {
        // Rate limit warning alerts to prevent spam
        $cacheKey = "warning_alert_" . md5(json_encode($issue));
        if (Cache::has($cacheKey)) {
            return; // Already sent recently
        }

        // For user-specific issues, notify the user
        if (isset($issue['user_id'])) {
            $user = User::find($issue['user_id']);
            if ($user) {
                try {
                    $user->notify(new CloudStorageConnectionAlert(
                        $issue['unhealthy_providers'][0] ?? 'unknown',
                        'connection_warning',
                        null,
                        $issue
                    ));
                } catch (\Exception $e) {
                    Log::error('Failed to send warning alert to user', [
                        'user_id' => $user->id,
                        'issue' => $issue,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Cache to prevent duplicate alerts for 6 hours
        Cache::put($cacheKey, true, now()->addHours(6));

        Log::warning('Cloud storage warning detected', $issue);
    }

    /**
     * Check if warning alert should be sent (rate limiting)
     */
    private function shouldSendWarningAlert(array $issue): bool
    {
        $cacheKey = "warning_alert_" . md5(json_encode($issue));
        return !Cache::has($cacheKey);
    }

    /**
     * Calculate monitoring summary
     */
    private function calculateMonitoringSummary(array $issues, array $providerResults, array $userResults): array
    {
        return [
            'critical_issues' => count($issues['critical']),
            'warning_issues' => count($issues['warning']),
            'info_issues' => count($issues['info']),
            'providers_down' => count(array_filter($providerResults, function ($health) {
                return in_array($health['status'], ['error', 'connectivity_failed']);
            })),
            'users_affected' => count(array_filter($userResults, function ($health) {
                return $health['overall_status'] !== 'healthy';
            })),
            'healthy_providers' => count(array_filter($providerResults, function ($health) {
                return $health['status'] === 'healthy';
            })),
            'total_providers' => count($providerResults),
            'total_users' => count($userResults),
        ];
    }

    /**
     * Log monitoring results
     */
    private function logMonitoringResults(array $results): void
    {
        // Log to standard Laravel log
        Log::info('Cloud storage monitoring completed', [
            'providers_monitored' => $results['providers_monitored'],
            'users_monitored' => $results['users_monitored'],
            'critical_issues' => $results['summary']['critical_issues'],
            'warning_issues' => $results['summary']['warning_issues'],
            'alerts_sent' => $results['alerts_sent'],
        ]);
    }

    /**
     * Get monitoring status summary
     */
    public function getMonitoringStatus(): array
    {
        $lastMonitoring = Cache::get('last_monitoring_results');
        
        return [
            'last_monitoring' => $lastMonitoring['monitoring_timestamp'] ?? null,
            'status' => $this->determineMonitoringStatus($lastMonitoring),
            'summary' => $lastMonitoring['summary'] ?? [],
            'next_monitoring' => $this->getNextMonitoringTime(),
        ];
    }

    /**
     * Determine overall monitoring status
     */
    private function determineMonitoringStatus(?array $lastMonitoring): string
    {
        if (!$lastMonitoring) {
            return 'unknown';
        }

        $summary = $lastMonitoring['summary'] ?? [];
        
        if ($summary['critical_issues'] > 0) {
            return 'critical';
        }
        
        if ($summary['warning_issues'] > 0) {
            return 'warning';
        }
        
        return 'healthy';
    }

    /**
     * Get next scheduled monitoring time
     */
    private function getNextMonitoringTime(): ?string
    {
        // This would depend on how monitoring is scheduled
        // For now, assume it runs every 5 minutes
        $interval = config('cloud-storage.health_check.interval', 300); // 5 minutes
        return now()->addSeconds($interval)->toISOString();
    }

    /**
     * Cache monitoring results
     */
    public function cacheMonitoringResults(array $results): void
    {
        Cache::put('last_monitoring_results', $results, now()->addHour());
    }
}