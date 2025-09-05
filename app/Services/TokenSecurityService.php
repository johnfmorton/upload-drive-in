<?php

namespace App\Services;

use App\Models\User;
use App\Models\GoogleDriveToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Http\Request as HttpRequest;
use Carbon\Carbon;

class TokenSecurityService
{
    private const RATE_LIMIT_KEY_PREFIX = 'token_refresh_rate_limit';
    private const IP_RATE_LIMIT_KEY_PREFIX = 'token_refresh_ip_rate_limit';
    private const MAX_ATTEMPTS_PER_HOUR = 5;
    private const MAX_IP_ATTEMPTS_PER_HOUR = 20;
    private const RATE_LIMIT_WINDOW = 3600; // 1 hour in seconds

    /**
     * Check if user has exceeded rate limit for token refresh attempts
     */
    public function checkUserRateLimit(User $user): bool
    {
        $key = $this->getUserRateLimitKey($user->id);
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= self::MAX_ATTEMPTS_PER_HOUR) {
            $this->logSecurityEvent('rate_limit_exceeded', [
                'user_id' => $user->id,
                'attempts' => $attempts,
                'limit' => self::MAX_ATTEMPTS_PER_HOUR,
                'ip_address' => Request::ip(),
            ]);
            return false;
        }
        
        return true;
    }

    /**
     * Check if IP address has exceeded rate limit for token refresh attempts
     */
    public function checkIpRateLimit(string $ipAddress = null): bool
    {
        $ip = $ipAddress ?? Request::ip();
        $key = $this->getIpRateLimitKey($ip);
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= self::MAX_IP_ATTEMPTS_PER_HOUR) {
            $this->logSecurityEvent('ip_rate_limit_exceeded', [
                'ip_address' => $ip,
                'attempts' => $attempts,
                'limit' => self::MAX_IP_ATTEMPTS_PER_HOUR,
            ]);
            return false;
        }
        
        return true;
    }

    /**
     * Record a token refresh attempt for rate limiting
     */
    public function recordRefreshAttempt(User $user, string $ipAddress = null): void
    {
        $ip = $ipAddress ?? Request::ip();
        
        // Record user-based attempt
        $userKey = $this->getUserRateLimitKey($user->id);
        $userAttempts = Cache::get($userKey, 0);
        Cache::put($userKey, $userAttempts + 1, self::RATE_LIMIT_WINDOW);
        
        // Record IP-based attempt
        $ipKey = $this->getIpRateLimitKey($ip);
        $ipAttempts = Cache::get($ipKey, 0);
        Cache::put($ipKey, $ipAttempts + 1, self::RATE_LIMIT_WINDOW);
        
        $this->logSecurityEvent('refresh_attempt_recorded', [
            'user_id' => $user->id,
            'ip_address' => $ip,
            'user_attempts' => $userAttempts + 1,
            'ip_attempts' => $ipAttempts + 1,
        ]);
    }

    /**
     * Reset rate limit for a user (used after successful operations)
     */
    public function resetUserRateLimit(User $user): void
    {
        $key = $this->getUserRateLimitKey($user->id);
        Cache::forget($key);
        
        $this->logSecurityEvent('rate_limit_reset', [
            'user_id' => $user->id,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Rotate token on successful refresh for improved security
     */
    public function rotateTokenOnRefresh(GoogleDriveToken $token, array $newTokenData): GoogleDriveToken
    {
        $oldAccessToken = substr($token->access_token, 0, 10) . '...';
        $oldRefreshToken = substr($token->refresh_token, 0, 10) . '...';
        
        // Update token with new data
        $token->update([
            'access_token' => $newTokenData['access_token'],
            'refresh_token' => $newTokenData['refresh_token'] ?? $token->refresh_token,
            'expires_at' => isset($newTokenData['expires_in']) 
                ? now()->addSeconds($newTokenData['expires_in'])
                : $token->expires_at,
            'last_successful_refresh_at' => now(),
            'refresh_failure_count' => 0,
        ]);
        
        $this->logSecurityEvent('token_rotated', [
            'user_id' => $token->user_id,
            'token_id' => $token->id,
            'old_access_token' => $oldAccessToken,
            'old_refresh_token' => $oldRefreshToken,
            'new_expires_at' => $token->expires_at,
            'ip_address' => Request::ip(),
        ]);
        
        return $token->fresh();
    }

    /**
     * Create audit trail for token refresh failure
     */
    public function auditRefreshFailure(User $user, \Exception $exception, array $context = []): void
    {
        $this->logSecurityEvent('token_refresh_failure', [
            'user_id' => $user->id,
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'error_class' => get_class($exception),
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
            'context' => $context,
            'stack_trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Create audit trail for user intervention
     */
    public function auditUserIntervention(User $user, string $action, array $context = []): void
    {
        $this->logSecurityEvent('user_intervention', [
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
            'context' => $context,
        ]);
    }

    /**
     * Log authentication-related security events
     */
    public function logAuthenticationEvent(string $event, User $user, array $context = []): void
    {
        $this->logSecurityEvent('authentication_event', [
            'event' => $event,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
            'context' => $context,
        ]);
    }

    /**
     * Get remaining attempts for user rate limit
     */
    public function getRemainingUserAttempts(User $user): int
    {
        $key = $this->getUserRateLimitKey($user->id);
        $attempts = Cache::get($key, 0);
        return max(0, self::MAX_ATTEMPTS_PER_HOUR - $attempts);
    }

    /**
     * Get remaining attempts for IP rate limit
     */
    public function getRemainingIpAttempts(string $ipAddress = null): int
    {
        $ip = $ipAddress ?? Request::ip();
        $key = $this->getIpRateLimitKey($ip);
        $attempts = Cache::get($key, 0);
        return max(0, self::MAX_IP_ATTEMPTS_PER_HOUR - $attempts);
    }

    /**
     * Get time until rate limit resets
     */
    public function getRateLimitResetTime(User $user): ?Carbon
    {
        $key = $this->getUserRateLimitKey($user->id);
        
        try {
            // Try Redis-specific method if available
            $store = Cache::getStore();
            if (method_exists($store, 'getRedis')) {
                $ttl = $store->getRedis()->ttl($key);
                if ($ttl > 0) {
                    return now()->addSeconds($ttl);
                }
            } else {
                // For non-Redis stores, check if key exists and estimate TTL
                if (Cache::has($key)) {
                    // Since we can't get exact TTL from non-Redis stores,
                    // return the rate limit window duration as an estimate
                    return now()->addSeconds(self::RATE_LIMIT_WINDOW);
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            Log::warning('Failed to get rate limit reset time', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'cache_driver' => config('cache.default')
            ]);
        }
        
        return null;
    }

    /**
     * Generate user-specific rate limit cache key
     */
    private function getUserRateLimitKey(int $userId): string
    {
        return self::RATE_LIMIT_KEY_PREFIX . '_user_' . $userId;
    }

    /**
     * Generate IP-specific rate limit cache key
     */
    private function getIpRateLimitKey(string $ipAddress): string
    {
        return self::IP_RATE_LIMIT_KEY_PREFIX . '_ip_' . str_replace('.', '_', $ipAddress);
    }

    /**
     * Log security events with structured format
     */
    private function logSecurityEvent(string $event, array $data): void
    {
        Log::channel('security')->info('Token Security Event', [
            'event' => $event,
            'timestamp' => now()->toISOString(),
            'data' => $data,
        ]);
    }
}