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
        private readonly CloudStorageLogService $logService
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
            
            $result = match ($provider) {
                'google-drive' => $this->ensureValidGoogleDriveTokenWithErrorHandling($user, $healthStatus),
                default => ['success' => false, 'error' => 'Unsupported provider'],
            };
            
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
            $result = match ($provider) {
                'google-drive' => $this->testGoogleDriveApiConnectivity($user),
                default => false,
            };
            
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
     * Determine consolidated status that prioritizes operational capability over token age.
     */
    public function determineConsolidatedStatus(User $user, string $provider): string
    {
        try {
            $startTime = microtime(true);
            
            // 1. Try to ensure valid token
            $tokenValid = $this->ensureValidToken($user, $provider);
            
            if (!$tokenValid) {
                $reason = 'Token validation failed - refresh token may be expired or invalid';
                $this->logService->logStatusDetermination($user, $provider, 'authentication_required', $reason, [
                    'token_validation_result' => false,
                    'determination_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
                ]);
                
                Log::debug('Consolidated status: authentication_required (token validation failed)', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                ]);
                return 'authentication_required';
            }
            
            // 2. Test actual API connectivity
            $apiConnected = $this->testApiConnectivity($user, $provider);
            
            if (!$apiConnected) {
                $reason = 'API connectivity test failed - network issues or API problems';
                $this->logService->logStatusDetermination($user, $provider, 'connection_issues', $reason, [
                    'token_validation_result' => true,
                    'api_connectivity_result' => false,
                    'determination_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
                ]);
                
                Log::debug('Consolidated status: connection_issues (API connectivity failed)', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                ]);
                return 'connection_issues';
            }
            
            // 3. If both token and API work, it's healthy
            $reason = 'Token is valid and API connectivity confirmed';
            $this->logService->logStatusDetermination($user, $provider, 'healthy', $reason, [
                'token_validation_result' => true,
                'api_connectivity_result' => true,
                'determination_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);
            
            Log::debug('Consolidated status: healthy (token valid and API connected)', [
                'user_id' => $user->id,
                'provider' => $provider,
            ]);
            return 'healthy';
            
        } catch (\Exception $e) {
            $reason = 'Exception occurred during status determination: ' . $e->getMessage();
            $this->logService->logStatusDetermination($user, $provider, 'connection_issues', $reason, [
                'exception' => true,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ]);
            
            Log::error('Failed to determine consolidated status', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
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
        
        // Sync token expiration data for Google Drive
        if ($provider === 'google-drive') {
            $token = $user->googleDriveToken;
            if ($token && $token->expires_at) {
                $updateData['token_expires_at'] = $token->expires_at;
            }
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
     * Get health summary for a specific user and provider.
     */
    public function getHealthSummary(User $user, string $provider): array
    {
        $healthStatus = $this->getOrCreateHealthStatus($user, $provider);
        
        // Auto-validate and recalculate consolidated status if it seems inconsistent
        $consolidatedStatus = $healthStatus->consolidated_status;
        
        // Check for inconsistencies that indicate stale data
        $needsRecalculation = false;
        
        if (!$consolidatedStatus) {
            // No consolidated status set - calculate it
            $needsRecalculation = true;
        } elseif ($healthStatus->status === 'healthy' && $consolidatedStatus === 'not_connected') {
            // Clear inconsistency: status is healthy but consolidated shows not connected
            $needsRecalculation = true;
            Log::info('Detected stale consolidated status, recalculating', [
                'user_id' => $user->id,
                'provider' => $provider,
                'status' => $healthStatus->status,
                'consolidated_status' => $consolidatedStatus
            ]);
        } elseif ($healthStatus->last_successful_operation_at && 
                  $consolidatedStatus === 'not_connected' && 
                  $healthStatus->last_successful_operation_at->isAfter(now()->subHours(24))) {
            // Had successful operations recently but shows not connected
            $needsRecalculation = true;
            Log::info('Detected inconsistent status with recent successful operations', [
                'user_id' => $user->id,
                'provider' => $provider,
                'last_success' => $healthStatus->last_successful_operation_at->toISOString(),
                'consolidated_status' => $consolidatedStatus
            ]);
        }
        
        if ($needsRecalculation) {
            $consolidatedStatus = $this->determineConsolidatedStatus($user, $provider);
            
            // Update the database with the corrected status
            $healthStatus->update(['consolidated_status' => $consolidatedStatus]);
            
            Log::info('Auto-corrected consolidated status', [
                'user_id' => $user->id,
                'provider' => $provider,
                'old_status' => $healthStatus->consolidated_status,
                'new_status' => $consolidatedStatus
            ]);
        }
        
        $isHealthy = $consolidatedStatus === 'healthy';
        
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
        return match ($provider) {
            'google-drive' => $this->checkGoogleDriveHealth($user),
            default => true,
        };
    }

    /**
     * Check Google Drive specific health.
     */
    private function checkGoogleDriveHealth(User $user): bool
    {
        try {
            // Check if user has a token
            $token = $user->googleDriveToken;
            if (!$token) {
                return false;
            }
            
            // Sync token expiration data with health status
            $this->updateTokenExpiration($user, 'google-drive', $token->expires_at);
            
            // For a more thorough health check, we could integrate with GoogleDriveService
            // to perform an actual API call, but for basic health monitoring,
            // checking token existence and expiration is sufficient
            
            // Check if token is expired
            if ($token->expires_at && $token->expires_at->isPast()) {
                // Token is expired, but we might be able to refresh it
                return $token->refresh_token !== null;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Google Drive health check failed', [
                'user_id' => $user->id,
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
     * Test Google Drive API connectivity.
     */
    private function testGoogleDriveApiConnectivity(User $user): bool
    {
        try {
            $googleDriveService = app(GoogleDriveService::class);
            return $googleDriveService->testApiConnectivity($user);
        } catch (\Exception $e) {
            Log::error('Google Drive API connectivity test failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
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
        return [
            'google-drive',
            // Future providers can be added here
            // 'dropbox',
            // 'onedrive',
        ];
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
        
        $tokenRefreshAttempts = Cache::get($tokenRefreshKey, 0);
        $connectivityTestAttempts = Cache::get($connectivityTestKey, 0);
        
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
        ];
    }
}