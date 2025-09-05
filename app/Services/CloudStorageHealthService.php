<?php

namespace App\Services;

use App\Enums\CloudStorageErrorType;
use App\Models\CloudStorageHealthStatus;
use App\Models\User;
use App\Notifications\CloudStorageConnectionAlert;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CloudStorageHealthService
{
    public function __construct(
        private readonly CloudStorageLogService $logService,
        private readonly CloudStorageManager $storageManager,
        private readonly ?PerformanceOptimizedHealthValidator $performanceValidator = null
    ) {}
    /**
     * Ensure valid token by attempting refresh if needed with comprehensive error handling.
     * This method proactively attempts token refresh during status checks and tracks detailed error information.
     * Implements caching for successful token refresh results (5 minutes) and rate limiting.
     */
    public function ensureValidToken(User $user, string $provider): bool
    {
        // Check cache for recent successful token validation
        $cacheKey = "token_valid_{$user->id}_{$provider}";
        $cachedResult = Cache::get($cacheKey);
        
        if ($cachedResult !== null) {
            $this->logService->logCacheOperation('get', $cacheKey, true, [
                'operation' => 'token_validation_cache_hit',
                'user_id' => $user->id,
                'provider' => $provider,
                'cached_result' => $cachedResult
            ]);
            
            Log::debug('Using cached token validation result', [
                'user_id' => $user->id,
                'provider' => $provider,
                'cached_result' => $cachedResult,
            ]);
            return $cachedResult;
        }
        
        $this->logService->logCacheOperation('get', $cacheKey, false, [
            'operation' => 'token_validation_cache_miss',
            'user_id' => $user->id,
            'provider' => $provider
        ]);
        
        // Check rate limiting for token refresh attempts
        if (!$this->canAttemptTokenRefresh($user, $provider)) {
            Log::info('Token refresh rate limited', [
                'user_id' => $user->id,
                'provider' => $provider,
            ]);
            return false;
        }
        
        try {
            $healthStatus = $this->getOrCreateHealthStatus($user, $provider);
            
            // Update last token refresh attempt timestamp
            $healthStatus->update([
                'last_token_refresh_attempt_at' => now(),
            ]);
            
            $result = $this->ensureValidTokenWithProvider($user, $provider, $healthStatus);
            
            // Update token refresh failure count and error details
            if ($result['success']) {
                // Reset failure count on success and clear error details
                $healthStatus->update([
                    'token_refresh_failures' => 0,
                    'last_error_type' => null,
                    'last_error_message' => null,
                    'last_error_context' => null,
                ]);
                
                // Cache successful result for 5 minutes
                Cache::put($cacheKey, true, now()->addMinutes(5));
                $this->logService->logCacheOperation('put', $cacheKey, null, [
                    'operation' => 'token_validation_cache_store_success',
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'ttl_minutes' => 5
                ]);
                
                Log::info('Token validation successful', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'attempt_info' => $result['attempt'] ?? null,
                ]);
            } else {
                // Increment failure count and store error details
                $errorType = $result['error_type'] ?? null;
                $requiresUserIntervention = $result['requires_user_intervention'] ?? true;
                $isRecoverable = $result['is_recoverable'] ?? false;
                
                $healthStatus->increment('token_refresh_failures');
                $healthStatus->update([
                    'last_error_type' => $errorType?->value,
                    'last_error_message' => $result['error'] ?? 'Unknown error',
                    'last_error_context' => [
                        'requires_user_intervention' => $requiresUserIntervention,
                        'is_recoverable' => $isRecoverable,
                        'attempts_made' => $result['attempts_made'] ?? 1,
                        'original_error' => $result['original_error'] ?? null,
                        'http_code' => $result['http_code'] ?? null,
                        'timestamp' => now()->toISOString(),
                    ],
                ]);
                
                // Cache failed result for 1 minute to prevent immediate retries
                Cache::put($cacheKey, false, now()->addMinute());
                $this->logService->logCacheOperation('put', $cacheKey, null, [
                    'operation' => 'token_validation_cache_store_failure',
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'ttl_minutes' => 1
                ]);
                
                Log::warning('Token validation failed', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'error' => $result['error'],
                    'error_type' => $errorType?->value,
                    'requires_user_intervention' => $requiresUserIntervention,
                    'is_recoverable' => $isRecoverable,
                    'token_refresh_failures' => $healthStatus->fresh()->token_refresh_failures,
                ]);
            }
            
            return $result['success'];
            
        } catch (\Exception $e) {
            Log::error('Token validation failed with exception', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            
            // Increment failure count on exception and store error details
            $healthStatus = $this->getOrCreateHealthStatus($user, $provider);
            $healthStatus->increment('token_refresh_failures');
            $healthStatus->update([
                'last_error_type' => CloudStorageErrorType::UNKNOWN_ERROR->value,
                'last_error_message' => $e->getMessage(),
                'last_error_context' => [
                    'requires_user_intervention' => true,
                    'is_recoverable' => false,
                    'exception_class' => get_class($e),
                    'timestamp' => now()->toISOString(),
                ],
            ]);
            
            // Cache failed result for 1 minute to prevent immediate retries
            Cache::put($cacheKey, false, now()->addMinute());
            
            return false;
        }
    }

    /**
     * Test actual API connectivity to validate operational capability.
     * Implements caching for API connectivity test results (2 minutes) and rate limiting.
     */
    public function testApiConnectivity(User $user, string $provider): bool
    {
        // Check cache for recent API connectivity test result
        $cacheKey = "api_connectivity_{$user->id}_{$provider}";
        $cachedResult = Cache::get($cacheKey);
        
        if ($cachedResult !== null) {
            Log::debug('Using cached API connectivity test result', [
                'user_id' => $user->id,
                'provider' => $provider,
                'cached_result' => $cachedResult,
            ]);
            return $cachedResult;
        }
        
        // Check rate limiting for connectivity tests
        if (!$this->canAttemptConnectivityTest($user, $provider)) {
            Log::info('API connectivity test rate limited', [
                'user_id' => $user->id,
                'provider' => $provider,
            ]);
            // Return last known result if available, otherwise assume false
            $healthStatus = $this->getOrCreateHealthStatus($user, $provider);
            $lastResult = $healthStatus->operational_test_result;
            return $lastResult['success'] ?? false;
        }
        
        try {
            $result = $this->testProviderApiConnectivity($user, $provider);
            
            // Store the test result
            $healthStatus = $this->getOrCreateHealthStatus($user, $provider);
            $healthStatus->update([
                'operational_test_result' => [
                    'success' => $result,
                    'tested_at' => now()->toISOString(),
                    'test_type' => 'api_connectivity',
                ],
            ]);
            
            // Cache result for 2 minutes
            Cache::put($cacheKey, $result, now()->addMinutes(2));
            
            Log::debug('API connectivity test result', [
                'user_id' => $user->id,
                'provider' => $provider,
                'success' => $result,
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('API connectivity test failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            
            // Store the failure result
            $healthStatus = $this->getOrCreateHealthStatus($user, $provider);
            $healthStatus->update([
                'operational_test_result' => [
                    'success' => false,
                    'tested_at' => now()->toISOString(),
                    'test_type' => 'api_connectivity',
                    'error' => $e->getMessage(),
                ],
            ]);
            
            // Cache failed result for 30 seconds to prevent immediate retries
            Cache::put($cacheKey, false, now()->addSeconds(30));
            
            return false;
        }
    }

    /**
     * Determine consolidated status using RealTimeHealthValidator for accurate live validation.
     * Enhanced with real-time API calls and auto-correction of inconsistent health status.
     */
    public function determineConsolidatedStatus(User $user, string $provider): string
    {
        $startTime = microtime(true);
        
        try {
            $healthStatus = $this->getOrCreateHealthStatus($user, $provider);
            
            // Check rate limiting for health checks to prevent API abuse
            if (!$this->canPerformHealthCheck($user, $provider)) {
                // Use last known consolidated status if available and not too old
                if ($healthStatus->consolidated_status && 
                    $healthStatus->last_live_validation_at && 
                    $healthStatus->last_live_validation_at->isAfter(now()->subMinutes(5))) {
                    
                    Log::info('Using cached status due to rate limiting', [
                        'user_id' => $user->id,
                        'provider' => $provider,
                        'cached_status' => $healthStatus->consolidated_status,
                        'last_validation' => $healthStatus->last_live_validation_at->toISOString(),
                    ]);
                    
                    return $healthStatus->consolidated_status;
                }
            }
            
            // Use PerformanceOptimizedHealthValidator if available, otherwise fall back to RealTimeHealthValidator
            if ($this->performanceValidator) {
                $realTimeHealthStatus = $this->performanceValidator->validateConnectionHealth($user, $provider);
            } else {
                $realTimeHealthStatus = $this->createRealTimeValidator()->validateConnectionHealth($user, $provider);
            }
            
            // Map HealthStatus to consolidated status string
            $consolidatedStatus = $realTimeHealthStatus->getStatus();
            
            // Update the health status record with live validation results
            $this->updateHealthStatusFromLiveValidation($healthStatus, $realTimeHealthStatus);
            
            // Detect and auto-correct inconsistent health status
            $this->detectAndCorrectInconsistentStatus($user, $provider, $healthStatus, $consolidatedStatus);
            
            $determinationTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logService->logStatusDetermination($user, $provider, $consolidatedStatus, 
                'Status determined using real-time validation', [
                'live_validation_used' => true,
                'validation_details' => $realTimeHealthStatus->getValidationDetails(),
                'determination_time_ms' => $determinationTime,
                'validated_at' => $realTimeHealthStatus->getValidatedAt()?->toISOString(),
            ]);
            
            Log::debug("Consolidated status determined via real-time validation: {$consolidatedStatus}", [
                'user_id' => $user->id,
                'provider' => $provider,
                'is_healthy' => $realTimeHealthStatus->isHealthy(),
                'error_message' => $realTimeHealthStatus->getErrorMessage(),
                'determination_time_ms' => $determinationTime,
            ]);
            
            return $consolidatedStatus;
            
        } catch (\Exception $e) {
            $reason = 'Exception occurred during real-time status determination: ' . $e->getMessage();
            $this->logService->logStatusDetermination($user, $provider, 'connection_issues', $reason, [
                'exception' => true,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'determination_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);
            
            Log::error('Failed to determine consolidated status via real-time validation', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 'connection_issues';
        }
    }

    /**
     * Check the connection health for a specific provider.
     */
    public function checkConnectionHealth(User $user, string $provider): CloudStorageHealthStatus
    {
        $healthStatus = $this->getOrCreateHealthStatus($user, $provider);
        
        try {
            // Use enhanced status determination logic
            $consolidatedStatus = $this->determineConsolidatedStatus($user, $provider);
            
            // Update the consolidated status
            $healthStatus->update([
                'consolidated_status' => $consolidatedStatus,
            ]);
            
            // Map consolidated status to legacy status for backward compatibility
            $legacyStatus = $this->mapConsolidatedToLegacyStatus($consolidatedStatus);
            
            if ($consolidatedStatus === 'healthy') {
                $this->recordSuccessfulOperation($user, $provider);
            } else {
                $errorMessage = $this->getErrorMessageForConsolidatedStatus($consolidatedStatus);
                $this->markConnectionAsUnhealthy($user, $provider, $errorMessage);
            }
            
        } catch (\Exception $e) {
            Log::error('Cloud storage health check failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            
            // Update consolidated status to connection_issues on exception
            $healthStatus->update([
                'consolidated_status' => 'connection_issues',
            ]);
            
            $this->markConnectionAsUnhealthy($user, $provider, $e->getMessage());
        }
        
        return $healthStatus->fresh();
    }

    /**
     * Mark a connection as unhealthy with a specific reason.
     */
    public function markConnectionAsUnhealthy(User $user, string $provider, string $reason, ?CloudStorageErrorType $errorType = null): void
    {
        $healthStatus = $this->getOrCreateHealthStatus($user, $provider);
        $previousStatus = $healthStatus->status;
        
        $consecutiveFailures = $healthStatus->consecutive_failures + 1;
        $status = $this->determineStatusFromFailures($consecutiveFailures);
        
        $healthStatus->update([
            'status' => $status,
            'consecutive_failures' => $consecutiveFailures,
            'last_error_message' => $reason,
            'last_error_type' => $errorType?->value,
            'requires_reconnection' => $this->shouldRequireReconnection($errorType),
        ]);
        
        // Log health status change if status actually changed
        if ($previousStatus !== $status) {
            $this->logService->logHealthStatusChange(
                $provider,
                $user,
                $previousStatus,
                $status,
                $errorType,
                $reason,
                [
                    'consecutive_failures' => $consecutiveFailures,
                    'requires_reconnection' => $this->shouldRequireReconnection($errorType),
                ]
            );
        }
    }

    /**
     * Record a successful operation for a provider.
     */
    public function recordSuccessfulOperation(User $user, string $provider, ?array $providerData = null): void
    {
        $healthStatus = $this->getOrCreateHealthStatus($user, $provider);
        $previousStatus = $healthStatus->status;
        
        $updateData = [
            'status' => 'healthy',
            'last_successful_operation_at' => now(),
            'consecutive_failures' => 0,
            'last_error_type' => null,
            'last_error_message' => null,
            'requires_reconnection' => false,
        ];
        
        // Sync token expiration data for providers that support it
        try {
            $providerInstance = $this->storageManager->getProvider($provider);
            if (method_exists($providerInstance, 'getTokenExpiration')) {
                $tokenExpiration = $providerInstance->getTokenExpiration($user);
                if ($tokenExpiration) {
                    $updateData['token_expires_at'] = $tokenExpiration;
                }
            }
        } catch (\Exception $e) {
            Log::debug('Failed to sync token expiration data', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
        }
        
        if ($providerData) {
            $updateData['provider_specific_data'] = array_merge(
                $healthStatus->provider_specific_data ?? [],
                $providerData
            );
        }
        
        $healthStatus->update($updateData);
        
        // Log health status change if status actually changed
        if ($previousStatus !== 'healthy') {
            $this->logService->logHealthStatusChange(
                $provider,
                $user,
                $previousStatus,
                'healthy',
                null,
                null,
                [
                    'consecutive_failures_cleared' => $healthStatus->consecutive_failures,
                    'provider_data' => $providerData,
                ]
            );
        }
    }

    /**
     * Get health summary for a specific user and provider with live validation timestamps and results.
     */
    public function getHealthSummary(User $user, string $provider): array
    {
        $healthStatus = $this->getOrCreateHealthStatus($user, $provider);
        
        // Auto-validate and recalculate consolidated status if it seems inconsistent
        $consolidatedStatus = $healthStatus->consolidated_status;
        $liveValidationResult = null;
        
        // Check for inconsistencies that indicate stale data
        $needsRecalculation = false;
        
        if (!$consolidatedStatus) {
            // No consolidated status set - calculate it
            $needsRecalculation = true;
        } elseif ($healthStatus->status === 'healthy' && in_array($consolidatedStatus, ['not_connected', 'authentication_required'])) {
            // Clear inconsistency: status is healthy but consolidated shows not connected or auth required
            $needsRecalculation = true;
            Log::info('Detected stale consolidated status, recalculating', [
                'user_id' => $user->id,
                'provider' => $provider,
                'status' => $healthStatus->status,
                'consolidated_status' => $consolidatedStatus
            ]);
        } elseif ($healthStatus->last_successful_operation_at && 
                  in_array($consolidatedStatus, ['not_connected', 'authentication_required']) && 
                  $healthStatus->last_successful_operation_at->isAfter(now()->subHours(24))) {
            // Had successful operations recently but shows not connected or auth required
            $needsRecalculation = true;
            Log::info('Detected inconsistent status with recent successful operations', [
                'user_id' => $user->id,
                'provider' => $provider,
                'last_success' => $healthStatus->last_successful_operation_at->toISOString(),
                'consolidated_status' => $consolidatedStatus
            ]);
        }
        
        if ($needsRecalculation) {
            // Use performance-optimized validation for recalculation
            if ($this->performanceValidator) {
                $liveValidationResult = $this->performanceValidator->validateConnectionHealth($user, $provider);
            } else {
                $liveValidationResult = $this->createRealTimeValidator()->validateConnectionHealth($user, $provider);
            }
            $consolidatedStatus = $liveValidationResult->getStatus();
            
            // Update the database with the corrected status and live validation results
            $this->updateHealthStatusFromLiveValidation($healthStatus, $liveValidationResult);
            
            Log::info('Auto-corrected consolidated status using live validation', [
                'user_id' => $user->id,
                'provider' => $provider,
                'old_status' => $healthStatus->consolidated_status,
                'new_status' => $consolidatedStatus,
                'live_validation_used' => true
            ]);
        }
        
        $isHealthy = $consolidatedStatus === 'healthy';
        
        // Get live validation timestamps from health status record
        $liveValidationTimestamps = $this->getLiveValidationTimestamps($healthStatus);
        
        return [
            'provider' => $provider,
            'status' => $healthStatus->status,
            'consolidated_status' => $consolidatedStatus,
            'status_message' => $healthStatus->getConsolidatedStatusMessage(),
            'is_healthy' => $isHealthy,
            'is_degraded' => $healthStatus->isDegraded(),
            'is_unhealthy' => $healthStatus->isUnhealthy(),
            'is_disconnected' => $healthStatus->isDisconnected(),
            'last_successful_operation' => $healthStatus->getTimeSinceLastSuccess(),
            'last_successful_operation_at' => $healthStatus->last_successful_operation_at?->toISOString(),
            'consecutive_failures' => $healthStatus->consecutive_failures,
            'requires_reconnection' => $healthStatus->requires_reconnection,
            'token_expires_at' => $healthStatus->token_expires_at?->toISOString(),
            // Only show token warnings when connection is NOT healthy
            'token_expiring_soon' => !$isHealthy && $healthStatus->isTokenExpiringSoon(),
            'token_expired' => !$isHealthy && $healthStatus->isTokenExpired(),
            'last_error_type' => $healthStatus->last_error_type,
            'last_error_message' => $healthStatus->last_error_message,
            'provider_specific_data' => $healthStatus->provider_specific_data,
            'token_refresh_working' => $healthStatus->isTokenRefreshWorking(),
            'last_token_refresh_attempt' => $healthStatus->last_token_refresh_attempt_at?->toISOString(),
            'operational_test_result' => $healthStatus->operational_test_result,
            
            // Live validation timestamps and results
            'live_validation' => [
                'last_validation_at' => $liveValidationTimestamps['last_validation_at'],
                'last_validation_result' => $liveValidationTimestamps['last_validation_result'],
                'api_connectivity_last_tested_at' => $liveValidationTimestamps['api_connectivity_last_tested_at'],
                'api_connectivity_result' => $liveValidationTimestamps['api_connectivity_result'],
                'validation_details' => $liveValidationResult?->getValidationDetails(),
                'cache_ttl_seconds' => $liveValidationResult?->getCacheTtlSeconds(),
            ],
            
            // Rate limiting information
            'rate_limits' => $this->getRateLimitStatus($user, $provider),
        ];
    }

    /**
     * Get health status for all providers for a user.
     */
    public function getAllProvidersHealth(User $user): Collection
    {
        $providers = $this->getSupportedProviders();
        
        return collect($providers)->map(function ($provider) use ($user) {
            return $this->getHealthSummary($user, $provider);
        });
    }

    /**
     * Update token expiration information.
     */
    public function updateTokenExpiration(User $user, string $provider, ?Carbon $expiresAt): void
    {
        $healthStatus = $this->getOrCreateHealthStatus($user, $provider);
        
        $healthStatus->update([
            'token_expires_at' => $expiresAt,
        ]);
        
        Log::info('Cloud storage token expiration updated', [
            'user_id' => $user->id,
            'provider' => $provider,
            'expires_at' => $expiresAt?->toISOString(),
        ]);
    }

    /**
     * Get users with expiring tokens (within specified hours).
     */
    public function getUsersWithExpiringTokens(string $provider, int $hoursFromNow = 24): Collection
    {
        $expirationThreshold = now()->addHours($hoursFromNow);
        
        return CloudStorageHealthStatus::where('provider', $provider)
            ->where('token_expires_at', '<=', $expirationThreshold)
            ->where('token_expires_at', '>', now())
            ->with('user')
            ->get();
    }

    /**
     * Get users with unhealthy connections.
     */
    public function getUsersWithUnhealthyConnections(string $provider): Collection
    {
        return CloudStorageHealthStatus::where('provider', $provider)
            ->whereIn('status', ['unhealthy', 'degraded'])
            ->with('user')
            ->get();
    }

    /**
     * Clean up old health status records (older than specified days).
     */
    public function cleanupOldHealthRecords(int $daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        return CloudStorageHealthStatus::where('updated_at', '<', $cutoffDate)
            ->where('status', 'disconnected')
            ->delete();
    }

    /**
     * Send notifications for users with expiring tokens.
     */
    public function notifyUsersWithExpiringTokens(string $provider, int $hoursFromNow = 24): int
    {
        $usersWithExpiringTokens = $this->getUsersWithExpiringTokens($provider, $hoursFromNow);
        $notificationCount = 0;
        
        foreach ($usersWithExpiringTokens as $healthStatus) {
            $user = $healthStatus->user;
            if (!$user) {
                continue;
            }
            
            // Check if we've already sent a notification recently (within last 6 hours)
            $lastNotificationKey = "token_expiring_notification_{$user->id}_{$provider}";
            $lastNotificationTime = cache()->get($lastNotificationKey);
            
            if ($lastNotificationTime && now()->diffInHours($lastNotificationTime) < 6) {
                continue; // Skip to prevent spam
            }
            
            try {
                $user->notify(new CloudStorageConnectionAlert(
                    $provider,
                    'token_expiring',
                    $healthStatus
                ));
                
                // Cache the notification time
                cache()->put($lastNotificationKey, now(), now()->addHours(6));
                $notificationCount++;
                
                Log::info('Token expiration notification sent', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'expires_at' => $healthStatus->token_expires_at,
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to send token expiration notification', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $notificationCount;
    }

    /**
     * Send notifications for users with unhealthy connections.
     */
    public function notifyUsersWithUnhealthyConnections(string $provider): int
    {
        $usersWithUnhealthyConnections = $this->getUsersWithUnhealthyConnections($provider);
        $notificationCount = 0;
        
        foreach ($usersWithUnhealthyConnections as $healthStatus) {
            $user = $healthStatus->user;
            if (!$user) {
                continue;
            }
            
            // Only notify for multiple consecutive failures (3 or more)
            if ($healthStatus->consecutive_failures < 3) {
                continue;
            }
            
            // Check if we've already sent a notification recently (within last 12 hours)
            $lastNotificationKey = "unhealthy_connection_notification_{$user->id}_{$provider}";
            $lastNotificationTime = cache()->get($lastNotificationKey);
            
            if ($lastNotificationTime && now()->diffInHours($lastNotificationTime) < 12) {
                continue; // Skip to prevent spam
            }
            
            try {
                $alertType = $healthStatus->consecutive_failures >= 5 ? 'multiple_failures' : 'connection_unhealthy';
                
                $user->notify(new CloudStorageConnectionAlert(
                    $provider,
                    $alertType,
                    $healthStatus
                ));
                
                // Cache the notification time
                cache()->put($lastNotificationKey, now(), now()->addHours(12));
                $notificationCount++;
                
                Log::info('Unhealthy connection notification sent', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'consecutive_failures' => $healthStatus->consecutive_failures,
                    'alert_type' => $alertType,
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to send unhealthy connection notification', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $notificationCount;
    }

    /**
     * Get or create health status for a user and provider.
     */
    public function getOrCreateHealthStatus(User $user, string $provider): CloudStorageHealthStatus
    {
        return CloudStorageHealthStatus::firstOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'status' => 'disconnected',
                'consecutive_failures' => 0,
                'requires_reconnection' => false,
            ]
        );
    }

    /**
     * Perform provider-specific health check.
     */
    private function performProviderHealthCheck(User $user, string $provider): bool
    {
        // This would be implemented by specific provider services
        // For now, we'll return true as a placeholder
        try {
            $providerInstance = $this->storageManager->getProvider($provider);
            return $providerInstance->hasValidConnection($user);
        } catch (\Exception $e) {
            Log::warning('Failed to perform provider health check', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check Google Drive specific health.
     */
    /**
     * Ensure valid token for any provider using CloudStorageManager.
     */
    private function ensureValidTokenWithProvider(User $user, string $provider, CloudStorageHealthStatus $healthStatus): array
    {
        try {
            $providerInstance = $this->storageManager->getProvider($provider);
            
            // Check if provider supports token validation
            if (method_exists($providerInstance, 'validateAndRefreshToken')) {
                $result = $providerInstance->validateAndRefreshToken($user);
                return [
                    'success' => $result,
                    'error' => $result ? null : 'Token validation failed',
                    'provider' => $provider
                ];
            }
            
            // Fallback to basic connection check
            $hasConnection = $providerInstance->hasValidConnection($user);
            return [
                'success' => $hasConnection,
                'error' => $hasConnection ? null : 'No valid connection found',
                'provider' => $provider
            ];
            
        } catch (\Exception $e) {
            Log::error('Provider token validation failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => $provider,
                'error_type' => CloudStorageErrorType::UNKNOWN_ERROR,
                'requires_user_intervention' => true,
                'is_recoverable' => false
            ];
        }
    }

    /**
     * Test API connectivity for any provider using CloudStorageManager.
     */
    private function testProviderApiConnectivity(User $user, string $provider): bool
    {
        try {
            $providerInstance = $this->storageManager->getProvider($provider);
            
            // Check if provider supports API connectivity testing
            if (method_exists($providerInstance, 'testApiConnectivity')) {
                return $providerInstance->testApiConnectivity($user);
            }
            
            // Fallback to basic connection check
            return $providerInstance->hasValidConnection($user);
            
        } catch (\Exception $e) {
            Log::error('Provider API connectivity test failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Determine status based on consecutive failures.
     */
    private function determineStatusFromFailures(int $consecutiveFailures): string
    {
        return match (true) {
            $consecutiveFailures >= 5 => 'unhealthy',
            $consecutiveFailures >= 2 => 'degraded',
            default => 'healthy',
        };
    }

    /**
     * Determine if reconnection should be required based on error type.
     */
    private function shouldRequireReconnection(?CloudStorageErrorType $errorType): bool
    {
        if (!$errorType) {
            return false;
        }

        return match ($errorType) {
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => true,
            default => false,
        };
    }

    /**
     * Ensure valid Google Drive token with comprehensive error handling.
     * Returns detailed result information including error types and recovery options.
     */
    private function ensureValidGoogleDriveTokenWithErrorHandling(User $user, CloudStorageHealthStatus $healthStatus): array
    {
        try {
            $googleDriveService = app(GoogleDriveService::class);
            
            // Check if we should implement exponential backoff based on recent failures
            $failures = $healthStatus->token_refresh_failures ?? 0;
            if ($failures > 0 && $this->shouldApplyBackoff($healthStatus)) {
                $backoffDelay = $this->calculateBackoffDelay($failures);
                Log::info('Applying exponential backoff for token refresh', [
                    'user_id' => $user->id,
                    'failures' => $failures,
                    'backoff_delay_seconds' => $backoffDelay,
                ]);
                
                // Don't actually sleep here as this is called during status checks
                // Instead, return a temporary failure to prevent excessive API calls
                return [
                    'success' => false,
                    'error' => 'Token refresh temporarily delayed due to recent failures',
                    'error_type' => CloudStorageErrorType::SERVICE_UNAVAILABLE,
                    'requires_user_intervention' => false,
                    'is_recoverable' => true,
                    'backoff_delay' => $backoffDelay,
                ];
            }
            
            // Attempt token validation and refresh
            $result = $googleDriveService->validateAndRefreshToken($user);
            
            if ($result) {
                return ['success' => true];
            } else {
                // If the simple method returns false, we need more details
                // This is a fallback for the existing method
                return [
                    'success' => false,
                    'error' => 'Token validation failed',
                    'error_type' => CloudStorageErrorType::TOKEN_EXPIRED,
                    'requires_user_intervention' => true,
                    'is_recoverable' => false,
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Google Drive token validation failed with exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => CloudStorageErrorType::UNKNOWN_ERROR,
                'requires_user_intervention' => true,
                'is_recoverable' => false,
            ];
        }
    }

    /**
     * Ensure valid Google Drive token by attempting refresh if needed.
     * Legacy method for backward compatibility.
     */
    private function ensureValidGoogleDriveToken(User $user): bool
    {
        try {
            $googleDriveService = app(GoogleDriveService::class);
            return $googleDriveService->validateAndRefreshToken($user);
        } catch (\Exception $e) {
            Log::error('Google Drive token validation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if exponential backoff should be applied based on recent failures.
     */
    private function shouldApplyBackoff(CloudStorageHealthStatus $healthStatus): bool
    {
        $failures = $healthStatus->token_refresh_failures ?? 0;
        $lastAttempt = $healthStatus->last_token_refresh_attempt_at;
        
        if ($failures < 2 || !$lastAttempt) {
            return false;
        }
        
        $backoffDelay = $this->calculateBackoffDelay($failures);
        $nextAllowedAttempt = $lastAttempt->addSeconds($backoffDelay);
        
        return now()->isBefore($nextAllowedAttempt);
    }

    /**
     * Calculate exponential backoff delay based on failure count.
     */
    private function calculateBackoffDelay(int $failures): int
    {
        // Exponential backoff: 30s, 60s, 120s, 300s (5min), then cap at 300s
        $baseDelay = 30; // 30 seconds base delay
        $maxDelay = 300; // 5 minutes maximum delay
        
        $delay = $baseDelay * pow(2, min($failures - 1, 4));
        return min($delay, $maxDelay);
    }



    /**
     * Map consolidated status to legacy status for backward compatibility.
     */
    private function mapConsolidatedToLegacyStatus(string $consolidatedStatus): string
    {
        return match ($consolidatedStatus) {
            'healthy' => 'healthy',
            'authentication_required' => 'unhealthy',
            'connection_issues' => 'degraded',
            'not_connected' => 'disconnected',
            default => 'unhealthy',
        };
    }

    /**
     * Get error message for consolidated status.
     */
    private function getErrorMessageForConsolidatedStatus(string $consolidatedStatus): string
    {
        return match ($consolidatedStatus) {
            'authentication_required' => 'Authentication required - please reconnect your account',
            'connection_issues' => 'Connection issues detected - please check your network and try again',
            'not_connected' => 'Account not connected - please set up your cloud storage connection',
            default => 'Unknown connection issue',
        };
    }

    /**
     * Get list of supported providers.
     */
    private function getSupportedProviders(): array
    {
        try {
            return $this->storageManager->getAvailableProviders();
        } catch (\Exception $e) {
            Log::warning('Failed to get available providers from storage manager', [
                'error' => $e->getMessage()
            ]);
            
            // Fallback to hardcoded list
            return [
                'google-drive',
                'amazon-s3',
                // Future providers can be added here
                // 'dropbox',
                // 'onedrive',
            ];
        }
    }

    /**
     * Check if a token refresh attempt is allowed based on rate limiting.
     * Prevents API quota exhaustion by limiting refresh attempts.
     */
    private function canAttemptTokenRefresh(User $user, string $provider): bool
    {
        $rateLimitKey = "token_refresh_rate_limit_{$user->id}_{$provider}";
        $attempts = Cache::get($rateLimitKey, 0);
        
        // Allow maximum 10 token refresh attempts per hour
        $maxAttempts = 10;
        $windowMinutes = 60;
        
        if ($attempts >= $maxAttempts) {
            Log::warning('Token refresh rate limit exceeded', [
                'user_id' => $user->id,
                'provider' => $provider,
                'attempts' => $attempts,
                'max_attempts' => $maxAttempts,
                'window_minutes' => $windowMinutes,
            ]);
            return false;
        }
        
        // Increment attempt counter
        Cache::put($rateLimitKey, $attempts + 1, now()->addMinutes($windowMinutes));
        
        return true;
    }

    /**
     * Check if an API connectivity test is allowed based on rate limiting.
     * Prevents excessive API calls by limiting connectivity test frequency.
     */
    private function canAttemptConnectivityTest(User $user, string $provider): bool
    {
        $rateLimitKey = "connectivity_test_rate_limit_{$user->id}_{$provider}";
        $attempts = Cache::get($rateLimitKey, 0);
        
        // Allow maximum 20 connectivity tests per hour
        $maxAttempts = 20;
        $windowMinutes = 60;
        
        if ($attempts >= $maxAttempts) {
            Log::warning('API connectivity test rate limit exceeded', [
                'user_id' => $user->id,
                'provider' => $provider,
                'attempts' => $attempts,
                'max_attempts' => $maxAttempts,
                'window_minutes' => $windowMinutes,
            ]);
            return false;
        }
        
        // Increment attempt counter
        Cache::put($rateLimitKey, $attempts + 1, now()->addMinutes($windowMinutes));
        
        return true;
    }

    /**
     * Clear rate limiting cache for a user and provider.
     * Useful for testing or manual intervention.
     */
    public function clearRateLimits(User $user, string $provider): void
    {
        $tokenRefreshKey = "token_refresh_rate_limit_{$user->id}_{$provider}";
        $connectivityTestKey = "connectivity_test_rate_limit_{$user->id}_{$provider}";
        
        Cache::forget($tokenRefreshKey);
        Cache::forget($connectivityTestKey);
        
        Log::info('Rate limits cleared for user and provider', [
            'user_id' => $user->id,
            'provider' => $provider,
        ]);
    }

    /**
     * Clear all caches for a user and provider.
     * Useful for forcing fresh status checks.
     */
    public function clearCaches(User $user, string $provider): void
    {
        $tokenValidKey = "token_valid_{$user->id}_{$provider}";
        $apiConnectivityKey = "api_connectivity_{$user->id}_{$provider}";
        
        Cache::forget($tokenValidKey);
        Cache::forget($apiConnectivityKey);
        
        // Also clear rate limits
        $this->clearRateLimits($user, $provider);
        
        Log::info('All caches cleared for user and provider', [
            'user_id' => $user->id,
            'provider' => $provider,
        ]);
    }

    /**
     * Get rate limiting status for a user and provider.
     * Returns information about current rate limiting state.
     */
    public function getRateLimitStatus(User $user, string $provider): array
    {
        $tokenRefreshKey = "token_refresh_rate_limit_{$user->id}_{$provider}";
        $connectivityTestKey = "connectivity_test_rate_limit_{$user->id}_{$provider}";
        $healthCheckKey = "health_check_rate_limit_{$user->id}_{$provider}";
        
        $tokenRefreshAttempts = Cache::get($tokenRefreshKey, 0);
        $connectivityTestAttempts = Cache::get($connectivityTestKey, 0);
        $healthCheckAttempts = Cache::get($healthCheckKey, 0);
        
        return [
            'token_refresh' => [
                'attempts' => $tokenRefreshAttempts,
                'max_attempts' => 10,
                'window_minutes' => 60,
                'can_attempt' => $tokenRefreshAttempts < 10,
            ],
            'connectivity_test' => [
                'attempts' => $connectivityTestAttempts,
                'max_attempts' => 20,
                'window_minutes' => 60,
                'can_attempt' => $connectivityTestAttempts < 20,
            ],
            'health_check' => [
                'attempts' => $healthCheckAttempts,
                'max_attempts' => 6,
                'window_minutes' => 1,
                'can_attempt' => $healthCheckAttempts < 6,
            ],
        ];
    }

    /**
     * Perform enhanced token validation with detailed error tracking and proactive refresh.
     * Returns comprehensive validation result with specific error types and recovery suggestions.
     */
    private function performEnhancedTokenValidation(User $user, string $provider, CloudStorageHealthStatus $healthStatus): array
    {
        try {
            // Check if we have a token at all
            $hasToken = $this->checkTokenExists($user, $provider);
            if (!$hasToken) {
                return [
                    'valid' => false,
                    'reason' => 'No authentication token found',
                    'error_type' => CloudStorageErrorType::TOKEN_EXPIRED,
                    'requires_user_intervention' => true,
                    'is_recoverable' => false,
                    'suggested_action' => 'reconnect',
                ];
            }
            
            // Check token expiration before attempting validation
            $tokenExpiration = $this->getTokenExpiration($user, $provider);
            if ($tokenExpiration && $tokenExpiration->isPast()) {
                Log::info('Token is expired, attempting proactive refresh', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'expired_at' => $tokenExpiration->toISOString(),
                ]);
                
                // Attempt proactive token refresh
                $refreshResult = $this->attemptProactiveTokenRefresh($user, $provider, $healthStatus);
                if (!$refreshResult['success']) {
                    return [
                        'valid' => false,
                        'reason' => 'Token expired and refresh failed: ' . $refreshResult['error'],
                        'error_type' => $refreshResult['error_type'] ?? CloudStorageErrorType::TOKEN_EXPIRED,
                        'requires_user_intervention' => $refreshResult['requires_user_intervention'] ?? true,
                        'is_recoverable' => $refreshResult['is_recoverable'] ?? false,
                        'suggested_action' => 'reconnect',
                        'refresh_details' => $refreshResult,
                    ];
                }
            }
            
            // Perform actual token validation
            $validationResult = $this->validateTokenWithProvider($user, $provider);
            if (!$validationResult['valid']) {
                // If validation fails, try one more refresh attempt
                if ($this->canAttemptTokenRefresh($user, $provider)) {
                    Log::info('Token validation failed, attempting recovery refresh', [
                        'user_id' => $user->id,
                        'provider' => $provider,
                        'validation_error' => $validationResult['error'] ?? 'Unknown error',
                    ]);
                    
                    $recoveryRefreshResult = $this->attemptProactiveTokenRefresh($user, $provider, $healthStatus);
                    if ($recoveryRefreshResult['success']) {
                        // Re-validate after successful refresh
                        $revalidationResult = $this->validateTokenWithProvider($user, $provider);
                        if ($revalidationResult['valid']) {
                            return [
                                'valid' => true,
                                'reason' => 'Token validated successfully after recovery refresh',
                                'recovery_refresh_performed' => true,
                                'original_validation_error' => $validationResult,
                            ];
                        }
                    }
                }
                
                return [
                    'valid' => false,
                    'reason' => 'Token validation failed: ' . ($validationResult['error'] ?? 'Unknown error'),
                    'error_type' => $validationResult['error_type'] ?? CloudStorageErrorType::TOKEN_EXPIRED,
                    'requires_user_intervention' => $validationResult['requires_user_intervention'] ?? true,
                    'is_recoverable' => $validationResult['is_recoverable'] ?? false,
                    'suggested_action' => 'reconnect',
                    'validation_details' => $validationResult,
                ];
            }
            
            return [
                'valid' => true,
                'reason' => 'Token validation successful',
                'validation_details' => $validationResult,
            ];
            
        } catch (\Exception $e) {
            Log::error('Enhanced token validation failed with exception', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'valid' => false,
                'reason' => 'Token validation exception: ' . $e->getMessage(),
                'error_type' => CloudStorageErrorType::UNKNOWN_ERROR,
                'requires_user_intervention' => true,
                'is_recoverable' => false,
                'suggested_action' => 'reconnect',
                'exception' => true,
            ];
        }
    }

    /**
     * Perform enhanced API connectivity testing with comprehensive diagnostics.
     * Tests multiple API endpoints and provides detailed failure analysis.
     */
    private function performEnhancedConnectivityTest(User $user, string $provider, CloudStorageHealthStatus $healthStatus): array
    {
        try {
            // Check if we can skip connectivity test due to recent success
            $lastSuccessfulTest = $healthStatus->operational_test_result['tested_at'] ?? null;
            if ($lastSuccessfulTest) {
                $lastTestTime = Carbon::parse($lastSuccessfulTest);
                if ($lastTestTime->isAfter(now()->subMinutes(2)) && 
                    ($healthStatus->operational_test_result['success'] ?? false)) {
                    
                    return [
                        'connected' => true,
                        'reason' => 'Recent successful connectivity test (cached result)',
                        'cached_result' => true,
                        'last_test_time' => $lastTestTime->toISOString(),
                    ];
                }
            }
            
            // Perform basic connectivity test
            $basicConnectivityResult = $this->performBasicConnectivityTest($user, $provider);
            if (!$basicConnectivityResult['connected']) {
                return [
                    'connected' => false,
                    'reason' => 'Basic connectivity test failed: ' . $basicConnectivityResult['error'],
                    'error_type' => $basicConnectivityResult['error_type'] ?? CloudStorageErrorType::NETWORK_ERROR,
                    'is_temporary' => $basicConnectivityResult['is_temporary'] ?? true,
                    'suggested_action' => 'retry',
                    'basic_test_details' => $basicConnectivityResult,
                ];
            }
            
            // Perform advanced connectivity tests
            $advancedConnectivityResult = $this->performAdvancedConnectivityTests($user, $provider);
            if (!$advancedConnectivityResult['all_passed']) {
                return [
                    'connected' => false,
                    'reason' => 'Advanced connectivity tests failed: ' . $advancedConnectivityResult['summary'],
                    'error_type' => CloudStorageErrorType::API_ERROR,
                    'is_temporary' => $advancedConnectivityResult['is_temporary'] ?? true,
                    'suggested_action' => $advancedConnectivityResult['suggested_action'] ?? 'retry',
                    'basic_test_details' => $basicConnectivityResult,
                    'advanced_test_details' => $advancedConnectivityResult,
                ];
            }
            
            // Update health status with successful test result
            $healthStatus->update([
                'operational_test_result' => [
                    'success' => true,
                    'tested_at' => now()->toISOString(),
                    'test_type' => 'enhanced_connectivity',
                    'basic_test' => $basicConnectivityResult,
                    'advanced_tests' => $advancedConnectivityResult,
                ],
            ]);
            
            return [
                'connected' => true,
                'reason' => 'All connectivity tests passed successfully',
                'basic_test_details' => $basicConnectivityResult,
                'advanced_test_details' => $advancedConnectivityResult,
            ];
            
        } catch (\Exception $e) {
            Log::error('Enhanced connectivity test failed with exception', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Update health status with failed test result
            $healthStatus->update([
                'operational_test_result' => [
                    'success' => false,
                    'tested_at' => now()->toISOString(),
                    'test_type' => 'enhanced_connectivity',
                    'error' => $e->getMessage(),
                ],
            ]);
            
            return [
                'connected' => false,
                'reason' => 'Connectivity test exception: ' . $e->getMessage(),
                'error_type' => CloudStorageErrorType::UNKNOWN_ERROR,
                'is_temporary' => false,
                'suggested_action' => 'reconnect',
                'exception' => true,
            ];
        }
    }

    /**
     * Perform additional health checks beyond basic token and connectivity validation.
     * Includes permission checks, quota validation, and provider-specific diagnostics.
     */
    private function performAdditionalHealthChecks(User $user, string $provider, CloudStorageHealthStatus $healthStatus): array
    {
        try {
            $checks = [];
            $allPassed = true;
            $issues = [];
            
            // Check 1: Verify permissions
            $permissionCheck = $this->checkProviderPermissions($user, $provider);
            $checks['permissions'] = $permissionCheck;
            if (!$permissionCheck['valid']) {
                $allPassed = false;
                $issues[] = 'Insufficient permissions: ' . $permissionCheck['error'];
            }
            
            // Check 2: Validate quota/storage limits
            $quotaCheck = $this->checkProviderQuota($user, $provider);
            $checks['quota'] = $quotaCheck;
            if (!$quotaCheck['valid']) {
                $allPassed = false;
                $issues[] = 'Quota issue: ' . $quotaCheck['error'];
            }
            
            // Check 3: Test file operations capability
            $operationsCheck = $this->checkFileOperationsCapability($user, $provider);
            $checks['file_operations'] = $operationsCheck;
            if (!$operationsCheck['valid']) {
                $allPassed = false;
                $issues[] = 'File operations issue: ' . $operationsCheck['error'];
            }
            
            // Check 4: Provider-specific health diagnostics
            $providerSpecificCheck = $this->performProviderSpecificHealthCheck($user, $provider);
            $checks['provider_specific'] = $providerSpecificCheck;
            if (!$providerSpecificCheck['valid']) {
                $allPassed = false;
                $issues[] = 'Provider-specific issue: ' . $providerSpecificCheck['error'];
            }
            
            if (!$allPassed) {
                return [
                    'healthy' => false,
                    'reason' => 'Additional health checks failed: ' . implode('; ', $issues),
                    'suggested_status' => $this->determineSuggestedStatusFromHealthChecks($checks),
                    'checks' => $checks,
                    'issues' => $issues,
                ];
            }
            
            return [
                'healthy' => true,
                'reason' => 'All additional health checks passed',
                'checks' => $checks,
            ];
            
        } catch (\Exception $e) {
            Log::error('Additional health checks failed with exception', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'healthy' => false,
                'reason' => 'Additional health checks exception: ' . $e->getMessage(),
                'suggested_status' => 'connection_issues',
                'exception' => true,
            ];
        }
    }

    /**
     * Check if a token exists for the user and provider.
     */
    private function checkTokenExists(User $user, string $provider): bool
    {
        try {
            $providerInstance = $this->storageManager->getProvider($provider);
            
            if (method_exists($providerInstance, 'hasToken')) {
                return $providerInstance->hasToken($user);
            }
            
            // Fallback to checking connection
            return $providerInstance->hasValidConnection($user);
            
        } catch (\Exception $e) {
            Log::debug('Token existence check failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get token expiration time for the user and provider.
     */
    private function getTokenExpiration(User $user, string $provider): ?Carbon
    {
        try {
            $providerInstance = $this->storageManager->getProvider($provider);
            
            if (method_exists($providerInstance, 'getTokenExpiration')) {
                return $providerInstance->getTokenExpiration($user);
            }
            
            // Fallback to health status record
            $healthStatus = $this->getOrCreateHealthStatus($user, $provider);
            return $healthStatus->token_expires_at;
            
        } catch (\Exception $e) {
            Log::debug('Token expiration check failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Attempt proactive token refresh with comprehensive error handling.
     */
    private function attemptProactiveTokenRefresh(User $user, string $provider, CloudStorageHealthStatus $healthStatus): array
    {
        try {
            // Check if we can attempt refresh (rate limiting)
            if (!$this->canAttemptTokenRefresh($user, $provider)) {
                return [
                    'success' => false,
                    'error' => 'Token refresh rate limited',
                    'error_type' => CloudStorageErrorType::SERVICE_UNAVAILABLE,
                    'requires_user_intervention' => false,
                    'is_recoverable' => true,
                ];
            }
            
            // Check exponential backoff
            if ($this->shouldApplyBackoff($healthStatus)) {
                $backoffDelay = $this->calculateBackoffDelay($healthStatus->token_refresh_failures ?? 0);
                return [
                    'success' => false,
                    'error' => "Token refresh delayed due to recent failures (backoff: {$backoffDelay}s)",
                    'error_type' => CloudStorageErrorType::SERVICE_UNAVAILABLE,
                    'requires_user_intervention' => false,
                    'is_recoverable' => true,
                    'backoff_delay' => $backoffDelay,
                ];
            }
            
            $providerInstance = $this->storageManager->getProvider($provider);
            
            // Update attempt timestamp
            $healthStatus->update([
                'last_token_refresh_attempt_at' => now(),
            ]);
            
            if (method_exists($providerInstance, 'refreshToken')) {
                $result = $providerInstance->refreshToken($user);
                
                if ($result) {
                    // Reset failure count on success
                    $healthStatus->update([
                        'token_refresh_failures' => 0,
                        'last_error_type' => null,
                        'last_error_message' => null,
                    ]);
                    
                    Log::info('Proactive token refresh successful', [
                        'user_id' => $user->id,
                        'provider' => $provider,
                    ]);
                    
                    return [
                        'success' => true,
                        'method' => 'refreshToken',
                    ];
                } else {
                    // Increment failure count
                    $healthStatus->increment('token_refresh_failures');
                    
                    return [
                        'success' => false,
                        'error' => 'Token refresh returned false',
                        'error_type' => CloudStorageErrorType::TOKEN_EXPIRED,
                        'requires_user_intervention' => true,
                        'is_recoverable' => false,
                    ];
                }
            }
            
            // Fallback to validateAndRefreshToken method
            if (method_exists($providerInstance, 'validateAndRefreshToken')) {
                $result = $providerInstance->validateAndRefreshToken($user);
                
                if ($result) {
                    $healthStatus->update([
                        'token_refresh_failures' => 0,
                        'last_error_type' => null,
                        'last_error_message' => null,
                    ]);
                    
                    Log::info('Proactive token validation and refresh successful', [
                        'user_id' => $user->id,
                        'provider' => $provider,
                    ]);
                    
                    return [
                        'success' => true,
                        'method' => 'validateAndRefreshToken',
                    ];
                } else {
                    $healthStatus->increment('token_refresh_failures');
                    
                    return [
                        'success' => false,
                        'error' => 'Token validation and refresh returned false',
                        'error_type' => CloudStorageErrorType::TOKEN_EXPIRED,
                        'requires_user_intervention' => true,
                        'is_recoverable' => false,
                    ];
                }
            }
            
            return [
                'success' => false,
                'error' => 'Provider does not support token refresh',
                'error_type' => CloudStorageErrorType::UNSUPPORTED_OPERATION,
                'requires_user_intervention' => true,
                'is_recoverable' => false,
            ];
            
        } catch (\Exception $e) {
            // Increment failure count on exception
            $healthStatus->increment('token_refresh_failures');
            $healthStatus->update([
                'last_error_type' => CloudStorageErrorType::UNKNOWN_ERROR->value,
                'last_error_message' => $e->getMessage(),
            ]);
            
            Log::error('Proactive token refresh failed with exception', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => 'Token refresh exception: ' . $e->getMessage(),
                'error_type' => CloudStorageErrorType::UNKNOWN_ERROR,
                'requires_user_intervention' => true,
                'is_recoverable' => false,
                'exception' => true,
            ];
        }
    }

    /**
     * Validate token with provider using appropriate method.
     */
    private function validateTokenWithProvider(User $user, string $provider): array
    {
        try {
            $providerInstance = $this->storageManager->getProvider($provider);
            
            if (method_exists($providerInstance, 'validateToken')) {
                $result = $providerInstance->validateToken($user);
                return [
                    'valid' => $result,
                    'method' => 'validateToken',
                    'error' => $result ? null : 'Token validation returned false',
                ];
            }
            
            if (method_exists($providerInstance, 'hasValidConnection')) {
                $result = $providerInstance->hasValidConnection($user);
                return [
                    'valid' => $result,
                    'method' => 'hasValidConnection',
                    'error' => $result ? null : 'Connection validation returned false',
                ];
            }
            
            return [
                'valid' => false,
                'error' => 'Provider does not support token validation',
                'error_type' => CloudStorageErrorType::UNSUPPORTED_OPERATION,
            ];
            
        } catch (\Exception $e) {
            Log::error('Token validation with provider failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'valid' => false,
                'error' => 'Token validation exception: ' . $e->getMessage(),
                'error_type' => CloudStorageErrorType::UNKNOWN_ERROR,
                'exception' => true,
            ];
        }
    }

    /**
     * Perform basic connectivity test.
     */
    private function performBasicConnectivityTest(User $user, string $provider): array
    {
        try {
            $providerInstance = $this->storageManager->getProvider($provider);
            
            if (method_exists($providerInstance, 'testBasicConnectivity')) {
                $result = $providerInstance->testBasicConnectivity($user);
                return [
                    'connected' => $result,
                    'method' => 'testBasicConnectivity',
                    'error' => $result ? null : 'Basic connectivity test returned false',
                ];
            }
            
            // Fallback to API connectivity test
            if (method_exists($providerInstance, 'testApiConnectivity')) {
                $result = $providerInstance->testApiConnectivity($user);
                return [
                    'connected' => $result,
                    'method' => 'testApiConnectivity',
                    'error' => $result ? null : 'API connectivity test returned false',
                ];
            }
            
            // Final fallback to connection check
            $result = $providerInstance->hasValidConnection($user);
            return [
                'connected' => $result,
                'method' => 'hasValidConnection',
                'error' => $result ? null : 'Connection check returned false',
            ];
            
        } catch (\Exception $e) {
            Log::error('Basic connectivity test failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'connected' => false,
                'error' => 'Basic connectivity test exception: ' . $e->getMessage(),
                'error_type' => CloudStorageErrorType::NETWORK_ERROR,
                'is_temporary' => true,
                'exception' => true,
            ];
        }
    }

    /**
     * Perform advanced connectivity tests.
     */
    private function performAdvancedConnectivityTests(User $user, string $provider): array
    {
        $tests = [];
        $allPassed = true;
        $issues = [];
        
        try {
            $providerInstance = $this->storageManager->getProvider($provider);
            
            // Test 1: List files/folders capability
            if (method_exists($providerInstance, 'testListCapability')) {
                $listTest = $providerInstance->testListCapability($user);
                $tests['list_capability'] = $listTest;
                if (!$listTest) {
                    $allPassed = false;
                    $issues[] = 'Cannot list files/folders';
                }
            }
            
            // Test 2: Upload capability (dry run)
            if (method_exists($providerInstance, 'testUploadCapability')) {
                $uploadTest = $providerInstance->testUploadCapability($user);
                $tests['upload_capability'] = $uploadTest;
                if (!$uploadTest) {
                    $allPassed = false;
                    $issues[] = 'Cannot upload files';
                }
            }
            
            // Test 3: API rate limit status
            if (method_exists($providerInstance, 'checkRateLimitStatus')) {
                $rateLimitTest = $providerInstance->checkRateLimitStatus($user);
                $tests['rate_limit_status'] = $rateLimitTest;
                if (!$rateLimitTest) {
                    $allPassed = false;
                    $issues[] = 'API rate limit exceeded';
                }
            }
            
            return [
                'all_passed' => $allPassed,
                'tests' => $tests,
                'summary' => $allPassed ? 'All advanced tests passed' : implode('; ', $issues),
                'is_temporary' => $this->areIssuesTemporary($issues),
                'suggested_action' => $allPassed ? null : $this->getSuggestedActionForIssues($issues),
            ];
            
        } catch (\Exception $e) {
            Log::error('Advanced connectivity tests failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'all_passed' => false,
                'tests' => $tests,
                'summary' => 'Advanced connectivity tests exception: ' . $e->getMessage(),
                'is_temporary' => false,
                'suggested_action' => 'reconnect',
                'exception' => true,
            ];
        }
    }

    /**
     * Helper methods for status determination.
     */
    private function determineStatusFromTokenValidation(array $tokenResult): string
    {
        if (isset($tokenResult['error_type'])) {
            return match ($tokenResult['error_type']) {
                CloudStorageErrorType::TOKEN_EXPIRED => 'authentication_required',
                CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => 'authentication_required',
                CloudStorageErrorType::NETWORK_ERROR => 'connection_issues',
                CloudStorageErrorType::API_ERROR => 'connection_issues',
                default => 'authentication_required',
            };
        }
        
        return 'authentication_required';
    }

    private function determineStatusFromConnectivityTest(array $connectivityResult): string
    {
        if (isset($connectivityResult['is_temporary']) && $connectivityResult['is_temporary']) {
            return 'connection_issues';
        }
        
        if (isset($connectivityResult['error_type'])) {
            return match ($connectivityResult['error_type']) {
                CloudStorageErrorType::NETWORK_ERROR => 'connection_issues',
                CloudStorageErrorType::API_ERROR => 'connection_issues',
                CloudStorageErrorType::SERVICE_UNAVAILABLE => 'connection_issues',
                default => 'connection_issues',
            };
        }
        
        return 'connection_issues';
    }

    private function determineSuggestedStatusFromHealthChecks(array $checks): string
    {
        // Analyze the types of failures to suggest appropriate status
        $hasPermissionIssues = !($checks['permissions']['valid'] ?? true);
        $hasQuotaIssues = !($checks['quota']['valid'] ?? true);
        $hasOperationIssues = !($checks['file_operations']['valid'] ?? true);
        
        if ($hasPermissionIssues) {
            return 'authentication_required';
        }
        
        if ($hasQuotaIssues || $hasOperationIssues) {
            return 'connection_issues';
        }
        
        return 'connection_issues';
    }

    private function areIssuesTemporary(array $issues): bool
    {
        // Check if issues are likely temporary (rate limits, network issues)
        $temporaryKeywords = ['rate limit', 'network', 'timeout', 'unavailable'];
        
        foreach ($issues as $issue) {
            foreach ($temporaryKeywords as $keyword) {
                if (stripos($issue, $keyword) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }

    private function getSuggestedActionForIssues(array $issues): string
    {
        if ($this->areIssuesTemporary($issues)) {
            return 'retry';
        }
        
        // Check for permission-related issues
        foreach ($issues as $issue) {
            if (stripos($issue, 'permission') !== false || stripos($issue, 'unauthorized') !== false) {
                return 'reconnect';
            }
        }
        
        return 'retry';
    }

    /**
     * Placeholder methods for additional health checks.
     * These should be implemented based on specific provider capabilities.
     */
    private function checkProviderPermissions(User $user, string $provider): array
    {
        try {
            $providerInstance = $this->storageManager->getProvider($provider);
            
            if (method_exists($providerInstance, 'checkPermissions')) {
                $result = $providerInstance->checkPermissions($user);
                return [
                    'valid' => $result,
                    'error' => $result ? null : 'Insufficient permissions',
                ];
            }
            
            // Default to valid if no permission check is available
            return ['valid' => true];
            
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Permission check failed: ' . $e->getMessage(),
            ];
        }
    }

    private function checkProviderQuota(User $user, string $provider): array
    {
        try {
            $providerInstance = $this->storageManager->getProvider($provider);
            
            if (method_exists($providerInstance, 'checkQuota')) {
                $result = $providerInstance->checkQuota($user);
                return [
                    'valid' => $result,
                    'error' => $result ? null : 'Quota exceeded or unavailable',
                ];
            }
            
            // Default to valid if no quota check is available
            return ['valid' => true];
            
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Quota check failed: ' . $e->getMessage(),
            ];
        }
    }

    private function checkFileOperationsCapability(User $user, string $provider): array
    {
        try {
            $providerInstance = $this->storageManager->getProvider($provider);
            
            if (method_exists($providerInstance, 'testFileOperations')) {
                $result = $providerInstance->testFileOperations($user);
                return [
                    'valid' => $result,
                    'error' => $result ? null : 'File operations not available',
                ];
            }
            
            // Default to valid if no file operations test is available
            return ['valid' => true];
            
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'File operations test failed: ' . $e->getMessage(),
            ];
        }
    }

    private function performProviderSpecificHealthCheck(User $user, string $provider): array
    {
        try {
            $providerInstance = $this->storageManager->getProvider($provider);
            
            if (method_exists($providerInstance, 'performHealthCheck')) {
                $result = $providerInstance->performHealthCheck($user);
                return [
                    'valid' => $result,
                    'error' => $result ? null : 'Provider-specific health check failed',
                ];
            }
            
            // Default to valid if no provider-specific check is available
            return ['valid' => true];
            
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Provider-specific health check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update health status record with live validation results from RealTimeHealthValidator.
     */
    private function updateHealthStatusFromLiveValidation(CloudStorageHealthStatus $healthStatus, HealthStatus $liveValidationResult): void
    {
        $updateData = [
            'consolidated_status' => $liveValidationResult->getStatus(),
            'last_live_validation_at' => $liveValidationResult->getValidatedAt(),
            'live_validation_result' => $liveValidationResult->toArray(),
        ];

        // Update API connectivity test results if available
        $validationDetails = $liveValidationResult->getValidationDetails();
        if (isset($validationDetails['api_test'])) {
            $updateData['api_connectivity_last_tested_at'] = now();
            $updateData['api_connectivity_result'] = $validationDetails['api_test'];
        }

        // Update error information if validation failed
        if (!$liveValidationResult->isHealthy()) {
            $updateData['last_error_message'] = $liveValidationResult->getErrorMessage();
            $updateData['last_error_type'] = $liveValidationResult->getErrorType();
            $updateData['requires_reconnection'] = $liveValidationResult->getStatus() === 'authentication_required';
        } else {
            // Clear error information on successful validation
            $updateData['last_error_message'] = null;
            $updateData['last_error_type'] = null;
            $updateData['requires_reconnection'] = false;
            $updateData['consecutive_failures'] = 0;
        }

        $healthStatus->update($updateData);

        Log::debug('Updated health status from live validation', [
            'user_id' => $healthStatus->user_id,
            'provider' => $healthStatus->provider,
            'status' => $liveValidationResult->getStatus(),
            'is_healthy' => $liveValidationResult->isHealthy(),
        ]);
    }

    /**
     * Detect and auto-correct inconsistent health status (healthy status with expired tokens).
     */
    private function detectAndCorrectInconsistentStatus(User $user, string $provider, CloudStorageHealthStatus $healthStatus, string $liveStatus): void
    {
        $storedStatus = $healthStatus->consolidated_status;
        
        // Check for inconsistencies between stored and live status
        if ($storedStatus && $storedStatus !== $liveStatus) {
            Log::info('Detected inconsistent health status, auto-correcting', [
                'user_id' => $user->id,
                'provider' => $provider,
                'stored_status' => $storedStatus,
                'live_status' => $liveStatus,
                'auto_correction_applied' => true,
            ]);

            // Update the stored status to match live validation
            $healthStatus->update(['consolidated_status' => $liveStatus]);
        }

        // Check for specific inconsistency: healthy status with expired tokens
        if ($liveStatus === 'healthy' && $healthStatus->token_expires_at && $healthStatus->token_expires_at->isPast()) {
            Log::warning('Detected healthy status with expired token - investigating', [
                'user_id' => $user->id,
                'provider' => $provider,
                'status' => $liveStatus,
                'token_expires_at' => $healthStatus->token_expires_at->toISOString(),
                'token_expired_hours_ago' => $healthStatus->token_expires_at->diffInHours(now()),
            ]);

            // This suggests the token was successfully refreshed but expiration wasn't updated
            // Try to get fresh token expiration from provider
            try {
                $freshExpiration = $this->getTokenExpiration($user, $provider);
                if ($freshExpiration && $freshExpiration->isFuture()) {
                    $healthStatus->update(['token_expires_at' => $freshExpiration]);
                    Log::info('Auto-corrected token expiration time', [
                        'user_id' => $user->id,
                        'provider' => $provider,
                        'old_expiration' => $healthStatus->token_expires_at->toISOString(),
                        'new_expiration' => $freshExpiration->toISOString(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::debug('Failed to get fresh token expiration for auto-correction', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Check if a health check can be performed (rate limiting for API abuse prevention).
     */
    private function canPerformHealthCheck(User $user, string $provider): bool
    {
        $rateLimitKey = "health_check_rate_limit_{$user->id}_{$provider}";
        $maxChecksPerMinute = 6; // Allow 6 health checks per minute (every 10 seconds)
        
        $currentCount = Cache::get($rateLimitKey, 0);
        
        if ($currentCount >= $maxChecksPerMinute) {
            Log::debug('Health check rate limited', [
                'user_id' => $user->id,
                'provider' => $provider,
                'current_count' => $currentCount,
                'max_per_minute' => $maxChecksPerMinute,
            ]);
            return false;
        }
        
        // Increment the counter with 1-minute TTL
        Cache::put($rateLimitKey, $currentCount + 1, now()->addMinute());
        
        return true;
    }









    /**
     * Get live validation timestamps from health status record.
     */
    private function getLiveValidationTimestamps(CloudStorageHealthStatus $healthStatus): array
    {
        return [
            'last_validation_at' => $healthStatus->last_live_validation_at?->toISOString(),
            'last_validation_result' => $healthStatus->live_validation_result,
            'api_connectivity_last_tested_at' => $healthStatus->api_connectivity_last_tested_at?->toISOString(),
            'api_connectivity_result' => $healthStatus->api_connectivity_result,
        ];
    }

    /**
     * Create a RealTimeHealthValidator instance without circular dependency.
     * This creates a simplified validator that doesn't depend on this service.
     */
    private function createRealTimeValidator(): RealTimeHealthValidator
    {
        // Create a simplified health service for the validator that doesn't use real-time validation
        // to avoid circular dependency
        $simplifiedHealthService = new class($this->logService, $this->storageManager) extends CloudStorageHealthService {
            public function __construct(
                CloudStorageLogService $logService,
                CloudStorageManager $storageManager
            ) {
                parent::__construct($logService, $storageManager);
            }

            // Override determineConsolidatedStatus to use the original implementation
            // without real-time validation to avoid circular dependency
            public function determineConsolidatedStatus(\App\Models\User $user, string $provider): string
            {
                // Use basic token and connectivity validation without real-time validator
                try {
                    $healthStatus = $this->getOrCreateHealthStatus($user, $provider);
                    
                    // Check rate limiting
                    if (!$this->canAttemptTokenRefresh($user, $provider) && 
                        !$this->canAttemptConnectivityTest($user, $provider)) {
                        
                        if ($healthStatus->consolidated_status) {
                            return $healthStatus->consolidated_status;
                        }
                    }
                    
                    // Basic token validation
                    $tokenValid = $this->ensureValidToken($user, $provider);
                    if (!$tokenValid) {
                        return 'authentication_required';
                    }
                    
                    // Basic API connectivity test
                    $apiConnected = $this->testApiConnectivity($user, $provider);
                    if (!$apiConnected) {
                        return 'connection_issues';
                    }
                    
                    return 'healthy';
                    
                } catch (\Exception $e) {
                    return 'connection_issues';
                }
            }
        };

        return new RealTimeHealthValidator($simplifiedHealthService, $this->storageManager);
    }
}