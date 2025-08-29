<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QueueWorkerTestSecurityService
{
    /**
     * Validate and sanitize queue worker test request data.
     */
    public function validateTestRequest(array $data): array
    {
        $validator = Validator::make($data, [
            'timeout' => 'sometimes|integer|min:5|max:120',
            'force' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->sanitizeData($validator->validated());
    }

    /**
     * Validate and sanitize cached queue worker status data.
     */
    public function validateCachedStatus(mixed $cachedData): ?array
    {
        if (!is_array($cachedData)) {
            return null;
        }

        $validator = Validator::make($cachedData, [
            'status' => 'required|string|in:not_tested,testing,completed,failed,timeout',
            'message' => 'required|string|max:500',
            'test_completed_at' => 'nullable|string|date',
            'processing_time' => 'nullable|numeric|min:0|max:300',
            'error_message' => 'nullable|string|max:1000',
            'test_job_id' => 'nullable|string|max:100',
            'details' => 'nullable|string|max:2000',
            'can_retry' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            // Log validation failure for security monitoring
            \Log::warning('Invalid cached queue worker status data detected', [
                'data' => $cachedData,
                'errors' => $validator->errors()->toArray(),
            ]);
            return null;
        }

        return $this->sanitizeData($validator->validated());
    }

    /**
     * Validate queue worker status update data.
     */
    public function validateStatusUpdate(array $data): array
    {
        $validator = Validator::make($data, [
            'status' => 'required|string|in:not_tested,testing,completed,failed,timeout',
            'message' => 'required|string|max:500',
            'test_completed_at' => 'nullable|string|date',
            'processing_time' => 'nullable|numeric|min:0|max:300',
            'error_message' => 'nullable|string|max:1000',
            'test_job_id' => 'nullable|string|max:100',
            'details' => 'nullable|string|max:2000',
            'can_retry' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->sanitizeData($validator->validated());
    }

    /**
     * Sanitize data to prevent XSS and other security issues.
     */
    private function sanitizeData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Strip tags and encode special characters
                $sanitized[$key] = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
            } elseif (is_numeric($value)) {
                $sanitized[$key] = $value;
            } elseif (is_bool($value)) {
                $sanitized[$key] = $value;
            } elseif (is_null($value)) {
                $sanitized[$key] = null;
            } else {
                // For arrays or other types, convert to string and sanitize
                if (is_array($value)) {
                    $sanitized[$key] = htmlspecialchars(strip_tags(json_encode($value)), ENT_QUOTES, 'UTF-8');
                } else {
                    $sanitized[$key] = htmlspecialchars(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
                }
            }
        }

        return $sanitized;
    }

    /**
     * Check if user has exceeded security thresholds.
     */
    public function checkSecurityThresholds(string $identifier): array
    {
        $config = config('setup.queue_worker_test');
        
        // Check for suspicious activity patterns
        $suspiciousActivity = $this->detectSuspiciousActivity($identifier);
        
        return [
            'is_suspicious' => $suspiciousActivity,
            'rate_limit_remaining' => $this->getRateLimitRemaining($identifier, $config),
            'cooldown_remaining' => $this->getCooldownRemaining($identifier, $config),
        ];
    }

    /**
     * Detect suspicious activity patterns.
     */
    private function detectSuspiciousActivity(string $identifier): bool
    {
        $cacheKey = "security:suspicious_activity:{$identifier}";
        $attempts = Cache::get($cacheKey, 0);
        
        // Consider suspicious if more than 20 attempts in the last hour
        return $attempts > 20;
    }

    /**
     * Record security event for monitoring.
     */
    public function recordSecurityEvent(string $event, array $context = []): void
    {
        \Log::channel('security')->info("Queue worker test security event: {$event}", $context);
    }

    /**
     * Get remaining rate limit attempts.
     */
    private function getRateLimitRemaining(string $identifier, array $config): int
    {
        $rateLimitKey = 'queue_worker_test:' . $identifier;
        $maxAttempts = $config['rate_limit']['max_attempts'];
        
        // This is a simplified check - in reality, Laravel's RateLimiter
        // doesn't expose remaining attempts directly
        return max(0, $maxAttempts - 1);
    }

    /**
     * Get remaining cooldown time.
     */
    private function getCooldownRemaining(string $identifier, array $config): int
    {
        $cooldownKey = $config['cache_keys']['cooldown'] . $identifier;
        $cooldownEnd = Cache::get($cooldownKey);
        
        if (!$cooldownEnd) {
            return 0;
        }
        
        return max(0, $cooldownEnd - time());
    }

    /**
     * Clear security data for identifier (for testing purposes).
     */
    public function clearSecurityData(string $identifier): void
    {
        $config = config('setup.queue_worker_test');
        
        // Clear rate limit
        Cache::forget('queue_worker_test:' . $identifier);
        
        // Clear cooldown
        Cache::forget($config['cache_keys']['cooldown'] . $identifier);
        
        // Clear suspicious activity tracking
        Cache::forget("security:suspicious_activity:{$identifier}");
    }
}