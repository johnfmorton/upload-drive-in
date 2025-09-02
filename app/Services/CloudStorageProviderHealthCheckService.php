<?php

namespace App\Services;

use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use App\Models\CloudStorageHealthStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CloudStorageProviderHealthCheckService
{
    public function __construct(
        private readonly CloudStorageManager $storageManager,
        private readonly CloudStorageHealthService $healthService,
        private readonly CloudStorageConfigurationValidationService $configValidationService,
        private readonly CloudStorageLogService $logService
    ) {}

    /**
     * Perform comprehensive health check for all providers and users
     */
    public function performComprehensiveHealthCheck(): array
    {
        $startTime = microtime(true);
        
        Log::info('Starting comprehensive cloud storage health check');

        $results = [
            'overall_status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'duration_ms' => 0,
            'configuration_validation' => [],
            'provider_health' => [],
            'user_health' => [],
            'summary' => [
                'total_providers' => 0,
                'healthy_providers' => 0,
                'unhealthy_providers' => 0,
                'total_users' => 0,
                'users_with_healthy_connections' => 0,
                'users_with_issues' => 0,
            ],
            'recommendations' => [],
        ];

        try {
            // 1. Validate all provider configurations
            $results['configuration_validation'] = $this->configValidationService->validateAllProviderConfigurations();
            
            // 2. Check provider-level health
            $results['provider_health'] = $this->checkAllProvidersHealth();
            
            // 3. Check user-specific health
            $results['user_health'] = $this->checkAllUsersHealth();
            
            // 4. Calculate summary statistics
            $results['summary'] = $this->calculateHealthSummary($results);
            
            // 5. Generate recommendations
            $results['recommendations'] = $this->generateHealthRecommendations($results);
            
            // 6. Determine overall status
            $results['overall_status'] = $this->determineOverallHealthStatus($results);
            
        } catch (\Exception $e) {
            Log::error('Comprehensive health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $results['overall_status'] = 'error';
            $results['error'] = $e->getMessage();
        }

        $results['duration_ms'] = round((microtime(true) - $startTime) * 1000, 2);
        
        Log::info('Comprehensive cloud storage health check completed', [
            'overall_status' => $results['overall_status'],
            'duration_ms' => $results['duration_ms'],
            'summary' => $results['summary'],
        ]);

        return $results;
    }

    /**
     * Check health of all configured providers
     */
    public function checkAllProvidersHealth(): array
    {
        $providerHealth = [];
        $configService = app(CloudConfigurationService::class);
        $supportedProviders = $configService->getSupportedProviders();

        foreach ($supportedProviders as $providerName) {
            $providerHealth[$providerName] = $this->checkProviderHealth($providerName);
        }

        return $providerHealth;
    }

    /**
     * Check health of a specific provider
     */
    public function checkProviderHealth(string $providerName): array
    {
        $result = [
            'provider_name' => $providerName,
            'status' => 'unknown',
            'is_configured' => false,
            'is_enabled' => false,
            'configuration_valid' => false,
            'can_instantiate' => false,
            'connectivity_test' => null,
            'errors' => [],
            'warnings' => [],
            'last_checked' => now()->toISOString(),
        ];

        try {
            // 1. Check if provider is configured
            $configService = app(CloudConfigurationService::class);
            $result['is_configured'] = $configService->isProviderConfigured($providerName);
            
            // 2. Check if provider is enabled
            $providerConfig = config("cloud-storage.providers.{$providerName}");
            $result['is_enabled'] = $providerConfig['enabled'] ?? false;
            
            // 3. Validate configuration
            $configValidation = $this->configValidationService->validateProviderConfiguration($providerName);
            $result['configuration_valid'] = $configValidation['is_valid'];
            
            if (!$configValidation['is_valid']) {
                $result['errors'] = array_merge($result['errors'], $configValidation['errors']);
            }
            
            if (!empty($configValidation['warnings'])) {
                $result['warnings'] = array_merge($result['warnings'], $configValidation['warnings']);
            }

            // 4. Try to instantiate provider
            if ($result['configuration_valid']) {
                try {
                    $config = $configService->getEffectiveConfig($providerName);
                    $provider = $this->storageManager->factory->create($providerName, $config);
                    $result['can_instantiate'] = true;
                    
                    // 5. Test basic connectivity (if provider supports it)
                    $result['connectivity_test'] = $this->testProviderConnectivity($provider, $providerName);
                    
                } catch (\Exception $e) {
                    $result['can_instantiate'] = false;
                    $result['errors'][] = "Failed to instantiate provider: {$e->getMessage()}";
                }
            }

            // 6. Determine overall provider status
            if (!$result['is_configured']) {
                $result['status'] = 'not_configured';
            } elseif (!$result['is_enabled']) {
                $result['status'] = 'disabled';
            } elseif (!$result['configuration_valid']) {
                $result['status'] = 'configuration_invalid';
            } elseif (!$result['can_instantiate']) {
                $result['status'] = 'instantiation_failed';
            } elseif ($result['connectivity_test'] && !$result['connectivity_test']['success']) {
                $result['status'] = 'connectivity_failed';
            } else {
                $result['status'] = 'healthy';
            }

        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['errors'][] = "Health check failed: {$e->getMessage()}";
            
            Log::error('Provider health check failed', [
                'provider' => $providerName,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Test basic connectivity for a provider
     */
    private function testProviderConnectivity(CloudStorageProviderInterface $provider, string $providerName): array
    {
        $result = [
            'success' => false,
            'test_type' => 'basic_connectivity',
            'response_time_ms' => 0,
            'error' => null,
        ];

        try {
            $startTime = microtime(true);
            
            // For providers that support basic connectivity testing without user context
            if (method_exists($provider, 'testConnectivity')) {
                $provider->testConnectivity();
                $result['success'] = true;
            } else {
                // Skip connectivity test for providers that require user context
                $result['success'] = true;
                $result['test_type'] = 'skipped_requires_user_context';
            }
            
            $result['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
            
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            $result['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::debug('Provider connectivity test failed', [
                'provider' => $providerName,
                'error' => $e->getMessage(),
                'response_time_ms' => $result['response_time_ms'],
            ]);
        }

        return $result;
    }

    /**
     * Check health for all users with cloud storage connections
     */
    public function checkAllUsersHealth(): array
    {
        $userHealth = [];
        
        // Get all users who have cloud storage connections
        $users = User::whereHas('cloudStorageHealthStatuses')->get();
        
        foreach ($users as $user) {
            $userHealth[$user->id] = $this->checkUserHealth($user);
        }

        return $userHealth;
    }

    /**
     * Check health for a specific user across all their providers
     */
    public function checkUserHealth(User $user): array
    {
        $result = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'providers' => [],
            'overall_status' => 'healthy',
            'healthy_providers' => 0,
            'unhealthy_providers' => 0,
            'last_checked' => now()->toISOString(),
        ];

        // Get all providers this user has health status records for
        $healthStatuses = $user->cloudStorageHealthStatuses;
        
        foreach ($healthStatuses as $healthStatus) {
            $providerName = $healthStatus->provider;
            
            try {
                // Get fresh health summary for this user and provider
                $healthSummary = $this->healthService->getHealthSummary($user, $providerName);
                
                $result['providers'][$providerName] = [
                    'status' => $healthSummary['consolidated_status'],
                    'is_healthy' => $healthSummary['is_healthy'],
                    'requires_reconnection' => $healthSummary['requires_reconnection'],
                    'consecutive_failures' => $healthSummary['consecutive_failures'],
                    'last_successful_operation' => $healthSummary['last_successful_operation'],
                    'last_error_message' => $healthSummary['last_error_message'],
                    'token_expires_at' => $healthSummary['token_expires_at'],
                    'token_expiring_soon' => $healthSummary['token_expiring_soon'],
                ];

                if ($healthSummary['is_healthy']) {
                    $result['healthy_providers']++;
                } else {
                    $result['unhealthy_providers']++;
                }

            } catch (\Exception $e) {
                $result['providers'][$providerName] = [
                    'status' => 'error',
                    'is_healthy' => false,
                    'error' => $e->getMessage(),
                ];
                $result['unhealthy_providers']++;
                
                Log::error('User health check failed for provider', [
                    'user_id' => $user->id,
                    'provider' => $providerName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Determine overall user status
        if ($result['unhealthy_providers'] === 0) {
            $result['overall_status'] = 'healthy';
        } elseif ($result['healthy_providers'] > 0) {
            $result['overall_status'] = 'partially_healthy';
        } else {
            $result['overall_status'] = 'unhealthy';
        }

        return $result;
    }

    /**
     * Calculate summary statistics from health check results
     */
    private function calculateHealthSummary(array $results): array
    {
        $summary = [
            'total_providers' => 0,
            'healthy_providers' => 0,
            'unhealthy_providers' => 0,
            'total_users' => 0,
            'users_with_healthy_connections' => 0,
            'users_with_issues' => 0,
            'configuration_issues' => 0,
        ];

        // Provider statistics
        if (isset($results['provider_health'])) {
            $summary['total_providers'] = count($results['provider_health']);
            
            foreach ($results['provider_health'] as $providerHealth) {
                if ($providerHealth['status'] === 'healthy') {
                    $summary['healthy_providers']++;
                } else {
                    $summary['unhealthy_providers']++;
                }
            }
        }

        // User statistics
        if (isset($results['user_health'])) {
            $summary['total_users'] = count($results['user_health']);
            
            foreach ($results['user_health'] as $userHealth) {
                if ($userHealth['overall_status'] === 'healthy') {
                    $summary['users_with_healthy_connections']++;
                } else {
                    $summary['users_with_issues']++;
                }
            }
        }

        // Configuration statistics
        if (isset($results['configuration_validation'])) {
            $summary['configuration_issues'] = $results['configuration_validation']['summary']['invalid_count'];
        }

        return $summary;
    }

    /**
     * Generate health recommendations based on results
     */
    private function generateHealthRecommendations(array $results): array
    {
        $recommendations = [];

        // Configuration recommendations
        if (isset($results['configuration_validation']['invalid'])) {
            foreach ($results['configuration_validation']['invalid'] as $provider => $validation) {
                $recommendations[] = [
                    'type' => 'configuration',
                    'priority' => 'high',
                    'provider' => $provider,
                    'message' => "Fix configuration issues for {$provider}",
                    'details' => $validation['errors'],
                ];
            }
        }

        // Provider health recommendations
        if (isset($results['provider_health'])) {
            foreach ($results['provider_health'] as $provider => $health) {
                if ($health['status'] !== 'healthy' && $health['is_enabled']) {
                    $recommendations[] = [
                        'type' => 'provider_health',
                        'priority' => 'medium',
                        'provider' => $provider,
                        'message' => "Address {$provider} health issues",
                        'details' => $health['errors'],
                    ];
                }
            }
        }

        // User health recommendations
        if (isset($results['user_health'])) {
            $usersWithIssues = array_filter($results['user_health'], function ($userHealth) {
                return $userHealth['overall_status'] !== 'healthy';
            });

            if (count($usersWithIssues) > 0) {
                $recommendations[] = [
                    'type' => 'user_health',
                    'priority' => 'medium',
                    'message' => count($usersWithIssues) . ' users have cloud storage connection issues',
                    'details' => 'Review individual user connections and send reconnection notifications',
                ];
            }
        }

        // Overall system recommendations
        $summary = $results['summary'] ?? [];
        if (($summary['healthy_providers'] ?? 0) === 0 && ($summary['total_providers'] ?? 0) > 0) {
            $recommendations[] = [
                'type' => 'system',
                'priority' => 'critical',
                'message' => 'No healthy cloud storage providers available',
                'details' => 'System cannot process file uploads until at least one provider is healthy',
            ];
        }

        return $recommendations;
    }

    /**
     * Determine overall health status
     */
    private function determineOverallHealthStatus(array $results): string
    {
        $summary = $results['summary'] ?? [];

        // Critical: No healthy providers
        if (($summary['healthy_providers'] ?? 0) === 0 && ($summary['total_providers'] ?? 0) > 0) {
            return 'critical';
        }

        // Warning: Configuration issues or some unhealthy providers
        if (($summary['configuration_issues'] ?? 0) > 0 || ($summary['unhealthy_providers'] ?? 0) > 0) {
            return 'warning';
        }

        // Degraded: Some users have issues
        if (($summary['users_with_issues'] ?? 0) > 0) {
            return 'degraded';
        }

        return 'healthy';
    }

    /**
     * Get cached health check results
     */
    public function getCachedHealthCheck(): ?array
    {
        return Cache::get('cloud_storage_comprehensive_health_check');
    }

    /**
     * Cache health check results
     */
    public function cacheHealthCheck(array $results, int $ttlMinutes = 5): void
    {
        Cache::put('cloud_storage_comprehensive_health_check', $results, now()->addMinutes($ttlMinutes));
    }

    /**
     * Perform health check with caching
     */
    public function performHealthCheckWithCaching(bool $forceRefresh = false): array
    {
        if (!$forceRefresh) {
            $cached = $this->getCachedHealthCheck();
            if ($cached !== null) {
                Log::debug('Returning cached comprehensive health check results');
                return $cached;
            }
        }

        $results = $this->performComprehensiveHealthCheck();
        $this->cacheHealthCheck($results);

        return $results;
    }
}