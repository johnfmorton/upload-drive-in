<?php

namespace App\Services;

use App\Enums\CloudStorageErrorType;
use App\Models\CloudStorageHealthStatus;
use App\Models\User;
use App\Notifications\CloudStorageConnectionAlert;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CloudStorageHealthService
{
    public function __construct(
        private readonly CloudStorageLogService $logService
    ) {}
    /**
     * Check the connection health for a specific provider.
     */
    public function checkConnectionHealth(User $user, string $provider): CloudStorageHealthStatus
    {
        $healthStatus = $this->getOrCreateHealthStatus($user, $provider);
        
        try {
            // Perform provider-specific health check
            $isHealthy = $this->performProviderHealthCheck($user, $provider);
            
            if ($isHealthy) {
                $this->recordSuccessfulOperation($user, $provider);
            } else {
                $this->markConnectionAsUnhealthy($user, $provider, 'Health check failed');
            }
        } catch (\Exception $e) {
            Log::error('Cloud storage health check failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
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
        
        return [
            'provider' => $provider,
            'status' => $healthStatus->status,
            'status_message' => $healthStatus->getStatusMessage(),
            'is_healthy' => $healthStatus->isHealthy(),
            'is_degraded' => $healthStatus->isDegraded(),
            'is_unhealthy' => $healthStatus->isUnhealthy(),
            'is_disconnected' => $healthStatus->isDisconnected(),
            'last_successful_operation' => $healthStatus->getTimeSinceLastSuccess(),
            'consecutive_failures' => $healthStatus->consecutive_failures,
            'requires_reconnection' => $healthStatus->requires_reconnection,
            'token_expires_at' => $healthStatus->token_expires_at?->toISOString(),
            'token_expiring_soon' => $healthStatus->isTokenExpiringSoon(),
            'token_expired' => $healthStatus->isTokenExpired(),
            'last_error_type' => $healthStatus->last_error_type,
            'last_error_message' => $healthStatus->last_error_message,
            'provider_specific_data' => $healthStatus->provider_specific_data,
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
}