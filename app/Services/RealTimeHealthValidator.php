<?php

namespace App\Services;

use App\Models\User;
use App\Services\HealthStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Real-time health validator for accurate cloud storage connection status reporting.
 * Implements three-tier validation with appropriate caching strategies.
 */
class RealTimeHealthValidator
{
    private const HEALTHY_CACHE_TTL = 30; // 30 seconds for healthy status
    private const ERROR_CACHE_TTL = 10;   // 10 seconds for error status

    public function __construct(
        private readonly CloudStorageHealthService $healthService,
        private readonly CloudStorageManager $storageManager
    ) {}

    /**
     * Validate connection health with three-tier validation and caching.
     * 
     * @param User $user
     * @param string $provider
     * @return HealthStatus
     */
    public function validateConnectionHealth(User $user, string $provider): HealthStatus
    {
        $cacheKey = "real_time_health_{$user->id}_{$provider}";
        
        // Check cache first
        $cachedResult = Cache::get($cacheKey);
        if ($cachedResult !== null) {
            Log::debug('Using cached real-time health validation result', [
                'user_id' => $user->id,
                'provider' => $provider,
                'cached_status' => $cachedResult['status'],
            ]);
            
            return new HealthStatus(
                isHealthy: $cachedResult['is_healthy'],
                status: $cachedResult['status'],
                errorMessage: $cachedResult['error_message'],
                errorType: $cachedResult['error_type'],
                validationDetails: $cachedResult['validation_details'],
                validatedAt: $cachedResult['validated_at'] ? now()->parse($cachedResult['validated_at']) : null,
                cacheTtlSeconds: $cachedResult['cache_ttl_seconds']
            );
        }

        $startTime = microtime(true);
        $validationDetails = [
            'validation_type' => 'real_time_three_tier',
            'started_at' => now()->toISOString(),
        ];

        try {
            // Tier 1: Token Validation
            $tokenValidationResult = $this->validateToken($user, $provider);
            $validationDetails['token_validation'] = $tokenValidationResult;

            if (!$tokenValidationResult['valid']) {
                $healthStatus = HealthStatus::fromTokenError($tokenValidationResult);
                $this->cacheResult($cacheKey, $healthStatus);
                
                Log::info('Real-time health validation failed at token tier', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'validation_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                    'token_result' => $tokenValidationResult,
                ]);
                
                return $healthStatus;
            }

            // Tier 2: API Connectivity Test
            $apiTestResult = $this->performLiveApiTest($user, $provider);
            $validationDetails['api_test'] = $apiTestResult;

            if (!$apiTestResult['success']) {
                $healthStatus = HealthStatus::fromApiError($apiTestResult);
                $this->cacheResult($cacheKey, $healthStatus);
                
                Log::info('Real-time health validation failed at API tier', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'validation_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                    'api_result' => $apiTestResult,
                ]);
                
                return $healthStatus;
            }

            // Tier 3: Operational Capability Test
            $operationalResult = $this->validateOperationalCapability($user, $provider);
            $validationDetails['operational_test'] = $operationalResult;

            if (!$operationalResult['success']) {
                $healthStatus = HealthStatus::connectionIssues(
                    $operationalResult['error'] ?? 'Operational capability test failed',
                    $operationalResult['error_type'] ?? 'operational_error',
                    $validationDetails
                );
                $this->cacheResult($cacheKey, $healthStatus);
                
                Log::info('Real-time health validation failed at operational tier', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'validation_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                    'operational_result' => $operationalResult,
                ]);
                
                return $healthStatus;
            }

            // All tiers passed - connection is healthy
            $validationDetails['validation_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
            $validationDetails['all_tiers_passed'] = true;
            
            $healthStatus = HealthStatus::healthy($validationDetails);
            $this->cacheResult($cacheKey, $healthStatus);
            
            Log::info('Real-time health validation successful - all tiers passed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'validation_time_ms' => $validationDetails['validation_time_ms'],
            ]);
            
            return $healthStatus;

        } catch (\Exception $e) {
            $validationDetails['exception'] = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'class' => get_class($e),
            ];
            $validationDetails['validation_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

            $healthStatus = HealthStatus::connectionIssues(
                __('messages.health_validation_failed', ['message' => $e->getMessage()]),
                'validation_exception',
                $validationDetails
            );
            $this->cacheResult($cacheKey, $healthStatus);
            
            Log::error('Real-time health validation failed with exception', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
                'validation_time_ms' => $validationDetails['validation_time_ms'],
            ]);
            
            return $healthStatus;
        }
    }

    /**
     * Perform live API test using lightweight Google Drive about.get call.
     * 
     * @param User $user
     * @param string $provider
     * @return array
     */
    public function performLiveApiTest(User $user, string $provider): array
    {
        $startTime = microtime(true);
        
        try {
            $providerInstance = $this->storageManager->getProvider($provider);
            
            // For Google Drive, use the about.get API call for lightweight testing
            if ($provider === 'google-drive') {
                return $this->performGoogleDriveApiTest($user, $providerInstance);
            }
            
            // For other providers, use generic connectivity test
            return $this->performGenericApiTest($user, $provider, $providerInstance);
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::warning('Live API test failed with exception', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => 'api_exception',
                'duration_ms' => $duration,
                'test_type' => 'live_api_test',
                'tested_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Validate token exists and can be refreshed if needed.
     * 
     * @param User $user
     * @param string $provider
     * @return array
     */
    private function validateToken(User $user, string $provider): array
    {
        try {
            // Use the existing health service token validation
            $tokenValid = $this->healthService->ensureValidToken($user, $provider);
            
            return [
                'valid' => $tokenValid,
                'test_type' => 'token_validation',
                'tested_at' => now()->toISOString(),
            ];
            
        } catch (\Exception $e) {
            Log::warning('Token validation failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'valid' => false,
                'error' => $e->getMessage(),
                'error_type' => 'token_validation_error',
                'requires_user_intervention' => true,
                'test_type' => 'token_validation',
                'tested_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Perform Google Drive specific API test using about.get.
     * 
     * @param User $user
     * @param mixed $providerInstance
     * @return array
     */
    private function performGoogleDriveApiTest(User $user, $providerInstance): array
    {
        $startTime = microtime(true);
        
        try {
            // Use the Google Drive service to perform about.get API call
            if (method_exists($providerInstance, 'testApiConnectivity')) {
                $result = $providerInstance->testApiConnectivity($user);
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                
                return [
                    'success' => $result,
                    'test_type' => 'google_drive_about_get',
                    'duration_ms' => $duration,
                    'tested_at' => now()->toISOString(),
                ];
            }
            
            // Fallback to generic test if method doesn't exist
            return $this->performGenericApiTest($user, 'google-drive', $providerInstance);
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => 'google_drive_api_error',
                'test_type' => 'google_drive_about_get',
                'duration_ms' => $duration,
                'tested_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Perform generic API connectivity test.
     * 
     * @param User $user
     * @param string $provider
     * @param mixed $providerInstance
     * @return array
     */
    private function performGenericApiTest(User $user, string $provider, $providerInstance): array
    {
        $startTime = microtime(true);
        
        try {
            // Use the existing health service API connectivity test
            $result = $this->healthService->testApiConnectivity($user, $provider);
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => $result,
                'test_type' => 'generic_api_connectivity',
                'duration_ms' => $duration,
                'tested_at' => now()->toISOString(),
            ];
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => 'generic_api_error',
                'test_type' => 'generic_api_connectivity',
                'duration_ms' => $duration,
                'tested_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Validate operational capability (e.g., can create folders, upload files).
     * 
     * @param User $user
     * @param string $provider
     * @return array
     */
    private function validateOperationalCapability(User $user, string $provider): array
    {
        $startTime = microtime(true);
        
        try {
            $providerInstance = $this->storageManager->getProvider($provider);
            
            // For now, we'll consider API connectivity as sufficient for operational capability
            // In the future, this could be enhanced to test actual folder creation or file operations
            // but that would be more invasive and slower
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => true,
                'test_type' => 'operational_capability_basic',
                'duration_ms' => $duration,
                'tested_at' => now()->toISOString(),
                'note' => 'Basic operational capability validated through API connectivity',
            ];
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => 'operational_capability_error',
                'test_type' => 'operational_capability_basic',
                'duration_ms' => $duration,
                'tested_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Cache the health status result with appropriate TTL.
     * 
     * @param string $cacheKey
     * @param HealthStatus $healthStatus
     * @return void
     */
    private function cacheResult(string $cacheKey, HealthStatus $healthStatus): void
    {
        $ttl = $healthStatus->getCacheTtlSeconds() ?? self::ERROR_CACHE_TTL;
        
        Cache::put($cacheKey, $healthStatus->toArray(), now()->addSeconds($ttl));
        
        Log::debug('Cached real-time health validation result', [
            'cache_key' => $cacheKey,
            'status' => $healthStatus->getStatus(),
            'ttl_seconds' => $ttl,
        ]);
    }
}