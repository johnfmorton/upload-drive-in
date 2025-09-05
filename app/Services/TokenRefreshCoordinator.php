<?php

namespace App\Services;

use App\Enums\TokenRefreshErrorType;
use App\Models\GoogleDriveToken;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Cache\LockTimeoutException;

/**
 * Coordinates token refresh operations to prevent duplicate refreshes
 * and ensure thread-safe token management
 */
class TokenRefreshCoordinator
{
    private const REFRESH_LOCK_TTL = 30; // seconds
    private const LOCK_WAIT_TIMEOUT = 5; // seconds to wait for lock

    public function __construct(
        private GoogleDriveService $googleDriveService,
        private TokenRefreshMonitoringService $monitoringService,
        private TokenSecurityService $securityService
    ) {}

    /**
     * Coordinate a token refresh operation with mutex locking
     * 
     * @param User $user The user whose token needs refreshing
     * @param string $provider The cloud storage provider (e.g., 'google-drive')
     * @return RefreshResult The result of the refresh operation
     */
    public function coordinateRefresh(User $user, string $provider): RefreshResult
    {
        $operationId = uniqid('refresh_', true);
        $lockKey = $this->getLockKey($user->id, $provider);

        // Log operation start with monitoring service
        $this->monitoringService->logRefreshOperationStart($user, $provider, $operationId, [
            'trigger' => 'coordinated_refresh',
            'lock_key' => $lockKey
        ]);

        Log::info('Token refresh coordination initiated', [
            'user_id' => $user->id,
            'provider' => $provider,
            'operation_id' => $operationId,
            'lock_key' => $lockKey
        ]);

        try {
            $result = Cache::lock($lockKey, self::REFRESH_LOCK_TTL)
                ->block(self::LOCK_WAIT_TIMEOUT, function () use ($user, $provider, $operationId) {
                    return $this->performCoordinatedRefresh($user, $provider, $operationId);
                });

            // Log success or failure based on result
            if ($result->isSuccessful()) {
                $this->monitoringService->logRefreshOperationSuccess($user, $provider, $operationId, [
                    'result_type' => $result->wasAlreadyValid ? 'already_valid' : 
                                   ($result->wasRefreshedByAnotherProcess ? 'refreshed_by_another' : 'refreshed'),
                    'was_already_valid' => $result->wasAlreadyValid,
                    'was_refreshed_by_another_process' => $result->wasRefreshedByAnotherProcess
                ]);
            } else {
                $this->monitoringService->logRefreshOperationFailure(
                    $user, 
                    $provider, 
                    $operationId, 
                    $result->errorType ?? TokenRefreshErrorType::UNKNOWN_ERROR,
                    $result->exception ?? new Exception($result->message),
                    ['coordination_failure' => false]
                );
            }

            return $result;
        } catch (LockTimeoutException $e) {
            Log::warning('Token refresh lock timeout', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId,
                'timeout_seconds' => self::LOCK_WAIT_TIMEOUT
            ]);

            $result = RefreshResult::failure(
                TokenRefreshErrorType::UNKNOWN_ERROR,
                $e,
                'Token refresh operation timed out waiting for lock'
            );

            $this->monitoringService->logRefreshOperationFailure(
                $user, 
                $provider, 
                $operationId, 
                TokenRefreshErrorType::UNKNOWN_ERROR,
                $e,
                ['coordination_failure' => true, 'failure_type' => 'lock_timeout']
            );

            return $result;
        } catch (Exception $e) {
            Log::error('Token refresh coordination failed', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId,
                'error' => $e->getMessage()
            ]);

            $result = RefreshResult::failure(
                TokenRefreshErrorType::UNKNOWN_ERROR,
                $e,
                'Token refresh coordination failed'
            );

            $this->monitoringService->logRefreshOperationFailure(
                $user, 
                $provider, 
                $operationId, 
                TokenRefreshErrorType::UNKNOWN_ERROR,
                $e,
                ['coordination_failure' => true, 'failure_type' => 'exception']
            );

            return $result;
        }
    }

    /**
     * Perform the actual token refresh within the lock
     */
    private function performCoordinatedRefresh(User $user, string $provider, string $operationId): RefreshResult
    {
        Log::info('Performing coordinated token refresh', [
            'user_id' => $user->id,
            'provider' => $provider,
            'operation_id' => $operationId
        ]);

        // Check rate limits before proceeding
        if (!$this->securityService->checkUserRateLimit($user)) {
            $error = 'User rate limit exceeded for token refresh';
            $exception = new Exception($error);
            
            $this->securityService->auditRefreshFailure($user, $exception, [
                'operation_id' => $operationId,
                'provider' => $provider,
                'reason' => 'rate_limit_exceeded'
            ]);
            
            return RefreshResult::failure(
                TokenRefreshErrorType::UNKNOWN_ERROR,
                $exception,
                $error
            );
        }

        if (!$this->securityService->checkIpRateLimit()) {
            $error = 'IP rate limit exceeded for token refresh';
            $exception = new Exception($error);
            
            $this->securityService->auditRefreshFailure($user, $exception, [
                'operation_id' => $operationId,
                'provider' => $provider,
                'reason' => 'ip_rate_limit_exceeded'
            ]);
            
            return RefreshResult::failure(
                TokenRefreshErrorType::UNKNOWN_ERROR,
                $exception,
                $error
            );
        }

        // Check if another process already refreshed the token
        $token = $this->getCurrentToken($user, $provider);
        
        if (!$token) {
            Log::warning('No token found for user', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId
            ]);

            return RefreshResult::failure(
                TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
                new Exception('No token found for user'),
                'No authentication token found'
            );
        }

        // Check if token is not expiring soon (still valid and doesn't need refresh)
        if (!$token->isExpiringSoon()) {
            Log::info('Token is still valid and not expiring soon', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId,
                'expires_at' => $token->expires_at?->toISOString()
            ]);

            return RefreshResult::alreadyValid(
                'Token is still valid and does not need refreshing'
            );
        }

        // Check if token has already been refreshed by another process
        // (token was expiring soon when we started, but now it's not expired)
        if (!$token->hasExpired()) {
            Log::info('Token was already refreshed by another process', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId,
                'expires_at' => $token->expires_at?->toISOString()
            ]);

            return RefreshResult::refreshedByAnotherProcess(
                'Token was already refreshed by another process'
            );
        }

        // Perform the actual token refresh
        try {
            Log::info('Starting actual token refresh', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId
            ]);

            $refreshSuccess = $this->googleDriveService->validateAndRefreshToken($user);

            if ($refreshSuccess) {
                Log::info('Token refresh completed successfully', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'operation_id' => $operationId
                ]);

                // Get the updated token data
                $updatedToken = $this->getCurrentToken($user, $provider);
                $tokenData = $updatedToken ? $updatedToken->toGoogleToken() : null;

                return RefreshResult::success(
                    tokenData: $tokenData,
                    message: 'Token refreshed successfully'
                );
            } else {
                Log::error('Token refresh returned false', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'operation_id' => $operationId
                ]);

                return RefreshResult::failure(
                    TokenRefreshErrorType::UNKNOWN_ERROR,
                    new Exception('Token refresh returned false'),
                    'Token refresh failed for unknown reason'
                );
            }
        } catch (Exception $e) {
            Log::error('Token refresh threw exception', [
                'user_id' => $user->id,
                'provider' => $provider,
                'operation_id' => $operationId,
                'error' => $e->getMessage(),
                'error_class' => get_class($e)
            ]);

            // Classify the error type
            $errorType = $this->classifyRefreshError($e);

            return RefreshResult::failure(
                $errorType,
                $e,
                $e->getMessage()
            );
        }
    }

    /**
     * Get the current token for the user and provider
     */
    private function getCurrentToken(User $user, string $provider): ?GoogleDriveToken
    {
        if ($provider !== 'google-drive') {
            return null;
        }

        return GoogleDriveToken::where('user_id', $user->id)->first();
    }

    /**
     * Generate a unique lock key for the user and provider
     */
    private function getLockKey(int $userId, string $provider): string
    {
        return "token_refresh_{$userId}_{$provider}";
    }

    /**
     * Classify the type of refresh error for appropriate handling
     */
    private function classifyRefreshError(Exception $exception): TokenRefreshErrorType
    {
        $message = strtolower($exception->getMessage());

        // Network-related errors
        if (str_contains($message, 'timeout') || 
            str_contains($message, 'connection') ||
            str_contains($message, 'network')) {
            return TokenRefreshErrorType::NETWORK_TIMEOUT;
        }

        // Invalid refresh token errors
        if (str_contains($message, 'invalid_grant') ||
            str_contains($message, 'invalid refresh token') ||
            str_contains($message, 'refresh token') && str_contains($message, 'invalid')) {
            return TokenRefreshErrorType::INVALID_REFRESH_TOKEN;
        }

        // Expired refresh token errors
        if (str_contains($message, 'expired') && str_contains($message, 'refresh')) {
            return TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN;
        }

        // API quota errors
        if (str_contains($message, 'quota') ||
            str_contains($message, 'rate limit') ||
            str_contains($message, 'too many requests')) {
            return TokenRefreshErrorType::API_QUOTA_EXCEEDED;
        }

        // Service unavailable errors
        if (str_contains($message, 'service unavailable') ||
            str_contains($message, 'temporarily unavailable') ||
            str_contains($message, '503')) {
            return TokenRefreshErrorType::SERVICE_UNAVAILABLE;
        }

        // Default to unknown error
        return TokenRefreshErrorType::UNKNOWN_ERROR;
    }

    /**
     * Check if a token refresh is currently in progress for the user
     */
    public function isRefreshInProgress(User $user, string $provider): bool
    {
        $lockKey = $this->getLockKey($user->id, $provider);
        
        // Try to acquire the lock with 0 timeout - if we can't get it immediately,
        // it means another process is holding it
        try {
            $lock = Cache::lock($lockKey, 1);
            if ($lock->get()) {
                // We got the lock, so no refresh is in progress
                $lock->release();
                return false;
            }
            return true;
        } catch (Exception $e) {
            // If we can't check the lock, assume no refresh is in progress
            return false;
        }
    }

    /**
     * Get the remaining TTL for a refresh lock (for monitoring purposes)
     */
    public function getRefreshLockTtl(User $user, string $provider): ?int
    {
        $lockKey = $this->getLockKey($user->id, $provider);
        
        try {
            $store = Cache::getStore();
            if (method_exists($store, 'getRedis')) {
                return $store->getRedis()->ttl($lockKey);
            } else {
                // For non-Redis stores, check if lock exists and return estimated TTL
                if (Cache::has($lockKey)) {
                    return self::LOCK_TIMEOUT_SECONDS;
                }
                return 0; // Lock doesn't exist
            }
        } catch (Exception $e) {
            return null;
        }
    }
}