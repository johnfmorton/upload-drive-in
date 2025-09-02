<?php

namespace App\Console\Commands;

use App\Services\CloudStorageProviderHealthCheckService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ComprehensiveCloudStorageHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cloud-storage:health-check 
                            {--provider= : Check specific provider only}
                            {--user= : Check specific user ID only}
                            {--json : Output results in JSON format}
                            {--cache : Use cached results if available}
                            {--force-refresh : Force refresh of cached results}
                            {--notify : Send notifications for issues found}
                            {--detailed : Include detailed diagnostic information}';

    /**
     * The console command description.
     */
    protected $description = 'Perform comprehensive health check of cloud storage system';

    public function __construct(
        private readonly CloudStorageProviderHealthCheckService $healthCheckService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $provider = $this->option('provider');
        $userId = $this->option('user');
        $jsonOutput = $this->option('json');
        $useCache = $this->option('cache');
        $forceRefresh = $this->option('force-refresh');
        $shouldNotify = $this->option('notify');
        $detailed = $this->option('detailed');

        if (!$jsonOutput) {
            $this->info('🏥 Comprehensive Cloud Storage Health Check');
            $this->info('==========================================');
            $this->newLine();
        }

        try {
            $results = $this->performHealthCheck($provider, $userId, $useCache, $forceRefresh);

            if (!$jsonOutput) {
                $this->displayResults($results, $detailed);
            } else {
                $this->line(json_encode($results, JSON_PRETTY_PRINT));
            }

            if ($shouldNotify) {
                $this->sendNotifications($results);
            }

            // Log results
            $this->logResults($results);

            // Return appropriate exit code based on overall status
            return match ($results['overall_status']) {
                'healthy' => self::SUCCESS,
                'degraded', 'warning' => 1, // Warning exit code
                'critical', 'error' => self::FAILURE,
                default => self::SUCCESS,
            };

        } catch (\Exception $e) {
            if ($jsonOutput) {
                $this->line(json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], JSON_PRETTY_PRINT));
            } else {
                $this->error('❌ Health check failed: ' . $e->getMessage());
            }

            Log::error('Comprehensive health check command failed', [
                'error' => $e->getMessage(),
                'provider' => $provider,
                'user_id' => $userId,
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Perform the health check
     */
    private function performHealthCheck(?string $provider, ?string $userId, bool $useCache, bool $forceRefresh): array
    {
        if ($provider || $userId) {
            // Specific checks don't use caching
            return $this->performSpecificHealthCheck($provider, $userId);
        }

        if ($useCache && !$forceRefresh) {
            return $this->healthCheckService->performHealthCheckWithCaching(false);
        } elseif ($forceRefresh) {
            return $this->healthCheckService->performHealthCheckWithCaching(true);
        } else {
            return $this->healthCheckService->performComprehensiveHealthCheck();
        }
    }

    /**
     * Perform specific health check for provider or user
     */
    private function performSpecificHealthCheck(?string $provider, ?string $userId): array
    {
        $results = [
            'overall_status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'scope' => 'specific',
        ];

        if ($provider && $userId) {
            // Check specific provider for specific user
            $user = \App\Models\User::findOrFail($userId);
            $results['user_health'] = [$userId => $this->healthCheckService->checkUserHealth($user)];
            $results['provider_health'] = [$provider => $this->healthCheckService->checkProviderHealth($provider)];
            $results['scope'] = "user_{$userId}_provider_{$provider}";
        } elseif ($provider) {
            // Check specific provider
            $results['provider_health'] = [$provider => $this->healthCheckService->checkProviderHealth($provider)];
            $results['scope'] = "provider_{$provider}";
        } elseif ($userId) {
            // Check specific user
            $user = \App\Models\User::findOrFail($userId);
            $results['user_health'] = [$userId => $this->healthCheckService->checkUserHealth($user)];
            $results['scope'] = "user_{$userId}";
        }

        return $results;
    }

    /**
     * Display health check results
     */
    private function displayResults(array $results, bool $detailed): void
    {
        // Overall status
        $statusIcon = match ($results['overall_status']) {
            'healthy' => '✅',
            'degraded' => '⚠️',
            'warning' => '⚠️',
            'critical' => '🚨',
            'error' => '❌',
            default => '❓',
        };

        $this->info("Overall Status: {$statusIcon} " . strtoupper($results['overall_status']));
        $this->line("Timestamp: {$results['timestamp']}");
        if (isset($results['duration_ms'])) {
            $this->line("Duration: {$results['duration_ms']}ms");
        }
        $this->newLine();

        // Summary
        if (isset($results['summary'])) {
            $this->displaySummary($results['summary']);
        }

        // Configuration validation
        if (isset($results['configuration_validation']) && $detailed) {
            $this->displayConfigurationValidation($results['configuration_validation']);
        }

        // Provider health
        if (isset($results['provider_health'])) {
            $this->displayProviderHealth($results['provider_health'], $detailed);
        }

        // User health
        if (isset($results['user_health']) && $detailed) {
            $this->displayUserHealth($results['user_health']);
        }

        // Recommendations
        if (isset($results['recommendations']) && !empty($results['recommendations'])) {
            $this->displayRecommendations($results['recommendations']);
        }
    }

    /**
     * Display summary information
     */
    private function displaySummary(array $summary): void
    {
        $this->info('📊 Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Providers', $summary['total_providers'] ?? 0],
                ['Healthy Providers', $summary['healthy_providers'] ?? 0],
                ['Unhealthy Providers', $summary['unhealthy_providers'] ?? 0],
                ['Total Users', $summary['total_users'] ?? 0],
                ['Users with Healthy Connections', $summary['users_with_healthy_connections'] ?? 0],
                ['Users with Issues', $summary['users_with_issues'] ?? 0],
                ['Configuration Issues', $summary['configuration_issues'] ?? 0],
            ]
        );
        $this->newLine();
    }

    /**
     * Display configuration validation results
     */
    private function displayConfigurationValidation(array $configValidation): void
    {
        $this->info('⚙️  Configuration Validation:');
        
        $summary = $configValidation['summary'];
        if ($summary['invalid_count'] === 0) {
            $this->line('  ✅ All provider configurations are valid');
        } else {
            $this->error("  ❌ {$summary['invalid_count']} providers have configuration issues");
            
            foreach ($configValidation['invalid'] as $provider => $validation) {
                $this->line("    • {$provider}:");
                foreach ($validation['errors'] as $error) {
                    $this->line("      - {$error}");
                }
            }
        }
        $this->newLine();
    }

    /**
     * Display provider health results
     */
    private function displayProviderHealth(array $providerHealth, bool $detailed): void
    {
        $this->info('🔌 Provider Health:');
        
        foreach ($providerHealth as $provider => $health) {
            $statusIcon = match ($health['status']) {
                'healthy' => '✅',
                'not_configured' => '⚪',
                'disabled' => '⏸️',
                'configuration_invalid' => '❌',
                'instantiation_failed' => '🚫',
                'connectivity_failed' => '🔌',
                'error' => '❌',
                default => '❓',
            };

            $this->line("  {$statusIcon} {$provider}: " . strtoupper($health['status']));
            
            if ($detailed) {
                $this->line("    Configured: " . ($health['is_configured'] ? '✅' : '❌'));
                $this->line("    Enabled: " . ($health['is_enabled'] ? '✅' : '❌'));
                $this->line("    Config Valid: " . ($health['configuration_valid'] ? '✅' : '❌'));
                $this->line("    Can Instantiate: " . ($health['can_instantiate'] ? '✅' : '❌'));
                
                if ($health['connectivity_test']) {
                    $test = $health['connectivity_test'];
                    $this->line("    Connectivity: " . ($test['success'] ? '✅' : '❌') . 
                               " ({$test['response_time_ms']}ms)");
                }
                
                if (!empty($health['errors'])) {
                    foreach ($health['errors'] as $error) {
                        $this->line("    ❌ {$error}");
                    }
                }
                
                if (!empty($health['warnings'])) {
                    foreach ($health['warnings'] as $warning) {
                        $this->line("    ⚠️  {$warning}");
                    }
                }
            }
        }
        $this->newLine();
    }

    /**
     * Display user health results
     */
    private function displayUserHealth(array $userHealth): void
    {
        $this->info('👥 User Health:');
        
        if (empty($userHealth)) {
            $this->line('  No users with cloud storage connections found');
            $this->newLine();
            return;
        }

        foreach ($userHealth as $userId => $health) {
            $statusIcon = match ($health['overall_status']) {
                'healthy' => '✅',
                'partially_healthy' => '⚠️',
                'unhealthy' => '❌',
                default => '❓',
            };

            $this->line("  {$statusIcon} {$health['user_email']} ({$userId}): " . 
                       strtoupper($health['overall_status']));
            $this->line("    Healthy: {$health['healthy_providers']}, " .
                       "Unhealthy: {$health['unhealthy_providers']}");
            
            foreach ($health['providers'] as $provider => $providerHealth) {
                $providerIcon = $providerHealth['is_healthy'] ? '✅' : '❌';
                $this->line("    {$providerIcon} {$provider}: {$providerHealth['status']}");
                
                if (!$providerHealth['is_healthy'] && isset($providerHealth['last_error_message'])) {
                    $this->line("      Error: {$providerHealth['last_error_message']}");
                }
            }
        }
        $this->newLine();
    }

    /**
     * Display recommendations
     */
    private function displayRecommendations(array $recommendations): void
    {
        $this->warn('💡 Recommendations:');
        
        foreach ($recommendations as $recommendation) {
            $priorityIcon = match ($recommendation['priority']) {
                'critical' => '🚨',
                'high' => '🔴',
                'medium' => '🟡',
                'low' => '🟢',
                default => '💡',
            };

            $this->line("  {$priorityIcon} {$recommendation['message']}");
            
            if (isset($recommendation['details']) && is_array($recommendation['details'])) {
                foreach ($recommendation['details'] as $detail) {
                    $this->line("    - {$detail}");
                }
            } elseif (isset($recommendation['details'])) {
                $this->line("    {$recommendation['details']}");
            }
        }
        $this->newLine();
    }

    /**
     * Send notifications for issues found
     */
    private function sendNotifications(array $results): void
    {
        $this->info('📧 Sending notifications...');
        
        // This would integrate with the existing notification system
        // For now, we'll just log what notifications would be sent
        
        $notificationCount = 0;
        
        if (isset($results['user_health'])) {
            foreach ($results['user_health'] as $userId => $health) {
                if ($health['overall_status'] !== 'healthy') {
                    // Would send notification to user
                    $this->line("  📧 Would notify user {$health['user_email']} about connection issues");
                    $notificationCount++;
                }
            }
        }
        
        if ($notificationCount === 0) {
            $this->line('  ✅ No notifications needed - all systems healthy');
        } else {
            $this->line("  📧 {$notificationCount} notifications would be sent");
        }
        
        $this->newLine();
    }

    /**
     * Log health check results
     */
    private function logResults(array $results): void
    {
        Log::info('Comprehensive cloud storage health check completed', [
            'overall_status' => $results['overall_status'],
            'duration_ms' => $results['duration_ms'] ?? null,
            'summary' => $results['summary'] ?? null,
            'recommendations_count' => count($results['recommendations'] ?? []),
        ]);

        // Log critical issues
        if ($results['overall_status'] === 'critical') {
            Log::critical('Cloud storage system is in critical state', [
                'recommendations' => $results['recommendations'] ?? [],
            ]);
        }
    }
}