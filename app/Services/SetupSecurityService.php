<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Security service for setup-related operations.
 * Handles input validation, sanitization, and security checks.
 */
class SetupSecurityService
{
    /**
     * Sanitize and validate setup status request parameters.
     */
    public function sanitizeStatusRequest(array $input): array
    {
        $sanitized = [];
        $violations = [];

        // Sanitize step parameter if present
        if (isset($input['step'])) {
            $step = $this->sanitizeStepName($input['step']);
            if ($step) {
                $sanitized['step'] = $step;
            } else {
                $violations[] = 'Invalid step name provided';
            }
        }

        // Sanitize delay parameter for queue tests
        if (isset($input['delay'])) {
            $delay = $this->sanitizeDelay($input['delay']);
            if ($delay !== null) {
                $sanitized['delay'] = $delay;
            } else {
                $violations[] = 'Invalid delay value provided';
            }
        }

        // Sanitize test_job_id parameter
        if (isset($input['test_job_id'])) {
            $jobId = $this->sanitizeJobId($input['test_job_id']);
            if ($jobId) {
                $sanitized['test_job_id'] = $jobId;
            } else {
                $violations[] = 'Invalid test job ID format';
            }
        }

        // Log sanitization attempt for security monitoring
        if (!empty($violations)) {
            $this->logSecurityEvent('input_sanitization_failed', [
                'violations' => $violations,
                'original_input' => array_keys($input),
            ]);
        }

        return [
            'sanitized' => $sanitized,
            'violations' => $violations,
            'is_valid' => empty($violations)
        ];
    }

    /**
     * Sanitize step name parameter.
     */
    protected function sanitizeStepName(mixed $step): ?string
    {
        if (!is_string($step)) {
            return null;
        }

        // Remove any non-alphanumeric characters except underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9_]/', '', $step);
        
        // Validate against allowed step names from config
        $allowedSteps = config('setup-security.validation.allowed_steps', [
            'database',
            'mail', 
            'google_drive',
            'migrations',
            'admin_user',
            'queue_worker'
        ]);

        return in_array($sanitized, $allowedSteps) ? $sanitized : null;
    }

    /**
     * Sanitize delay parameter for queue tests.
     */
    protected function sanitizeDelay(mixed $delay): ?int
    {
        if (is_string($delay) && is_numeric($delay)) {
            $delay = (int) $delay;
        }

        if (!is_int($delay)) {
            return null;
        }

        // Limit delay to configured bounds
        $limits = config('setup-security.validation.delay_limits', ['min' => 0, 'max' => 60]);
        return max($limits['min'], min($limits['max'], $delay));
    }

    /**
     * Sanitize test job ID parameter.
     */
    protected function sanitizeJobId(mixed $jobId): ?string
    {
        if (!is_string($jobId)) {
            return null;
        }

        // Remove any characters that aren't part of a valid job ID (including letters for 'test')
        $sanitized = preg_replace('/[^a-zA-Z0-9\-_]/', '', $jobId);
        
        // Validate format using configured pattern
        $pattern = config('setup-security.validation.job_id_pattern', '/^test_[a-f0-9\-]{36}$/');
        if (preg_match($pattern, $sanitized)) {
            return $sanitized;
        }

        return null;
    }

    /**
     * Validate request origin and headers for security.
     */
    public function validateRequestSecurity(\Illuminate\Http\Request $request): array
    {
        $issues = [];

        // Check for suspicious user agents
        $userAgent = $request->userAgent();
        if (empty($userAgent) || $this->isSuspiciousUserAgent($userAgent)) {
            $issues[] = 'Suspicious or missing user agent';
        }

        // Check for excessive request frequency from same IP
        $ip = $request->ip();
        if ($this->isExcessiveRequestFrequency($ip)) {
            $issues[] = 'Excessive request frequency detected';
        }

        // Check for required headers in AJAX requests
        if ($request->expectsJson()) {
            if (!$request->hasHeader('X-Requested-With')) {
                $issues[] = 'Missing X-Requested-With header for AJAX request';
            }
        }

        return [
            'is_secure' => empty($issues),
            'issues' => $issues,
            'risk_level' => $this->calculateRiskLevel($issues)
        ];
    }

    /**
     * Check if user agent appears suspicious.
     */
    protected function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousPatterns = config('setup-security.request_security.suspicious_user_agents', [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i',
            '/python/i',
        ]);

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for excessive request frequency from IP.
     */
    protected function isExcessiveRequestFrequency(string $ip): bool
    {
        // This is a simple check - in production you might want to use Redis
        // or a more sophisticated rate limiting system
        $cacheKey = "security_check_ip_{$ip}";
        $requests = cache()->get($cacheKey, 0);
        
        // Get max requests from config
        $maxRequests = config('setup-security.request_security.max_requests_per_minute', 100);
        
        if ($requests > $maxRequests) {
            return true;
        }

        cache()->put($cacheKey, $requests + 1, 60);
        return false;
    }

    /**
     * Calculate risk level based on security issues.
     */
    protected function calculateRiskLevel(array $issues): string
    {
        $count = count($issues);
        
        if ($count === 0) {
            return 'low';
        } elseif ($count <= 2) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    /**
     * Log security event for monitoring.
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        $logChannel = config('setup-security.monitoring.log_channel', 'security');
        
        $logData = array_merge([
            'event' => $event,
            'timestamp' => now()->toISOString(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'request_id' => request()->header('X-Request-ID') ?? Str::uuid(),
        ], $context);

        Log::channel($logChannel)->info("Setup security event: {$event}", $logData);
    }

    /**
     * Securely read a file with path validation and error handling.
     * 
     * @param string $filePath The file path to read
     * @return array Result with success status, content, and message
     */
    public function secureFileRead(string $filePath): array
    {
        try {
            // Validate the file path for security
            $pathValidation = $this->validateFilePath($filePath);
            if (!$pathValidation['is_valid']) {
                return [
                    'success' => false,
                    'content' => null,
                    'message' => 'Invalid file path: ' . implode(', ', $pathValidation['violations'])
                ];
            }

            // Check if file exists
            if (!file_exists($filePath)) {
                return [
                    'success' => false,
                    'content' => null,
                    'message' => 'File does not exist: ' . $filePath
                ];
            }

            // Check if file is readable
            if (!is_readable($filePath)) {
                return [
                    'success' => false,
                    'content' => null,
                    'message' => 'File is not readable: ' . $filePath
                ];
            }

            // Read file content
            $content = file_get_contents($filePath);
            
            if ($content === false) {
                return [
                    'success' => false,
                    'content' => null,
                    'message' => 'Failed to read file content: ' . $filePath
                ];
            }

            return [
                'success' => true,
                'content' => $content,
                'message' => 'File read successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'content' => null,
                'message' => 'Error reading file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate file path for security concerns.
     * 
     * @param string $filePath The file path to validate
     * @return array Validation result
     */
    private function validateFilePath(string $filePath): array
    {
        $violations = [];
        
        // Check for path traversal attempts
        if (strpos($filePath, '..') !== false) {
            $violations[] = 'Path traversal detected';
        }
        
        // Check for null bytes
        if (strpos($filePath, "\0") !== false) {
            $violations[] = 'Null byte detected';
        }
        
        // Ensure path is within allowed directories (storage path)
        $realPath = realpath(dirname($filePath));
        $allowedPaths = [
            realpath(storage_path()),
            realpath(storage_path('app')),
            realpath(storage_path('logs'))
        ];
        
        $isAllowed = false;
        foreach ($allowedPaths as $allowedPath) {
            if ($allowedPath && strpos($realPath, $allowedPath) === 0) {
                $isAllowed = true;
                break;
            }
        }
        
        if (!$isAllowed) {
            $violations[] = 'Path outside allowed directories';
        }
        
        return [
            'is_valid' => empty($violations),
            'violations' => $violations
        ];
    }

    /**
     * Check if request should be blocked based on security assessment.
     */
    public function shouldBlockRequest(\Illuminate\Http\Request $request): bool
    {
        $security = $this->validateRequestSecurity($request);
        
        // Block high-risk requests
        if ($security['risk_level'] === 'high') {
            $this->logSecurityEvent('request_blocked_high_risk', [
                'issues' => $security['issues'],
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
            return true;
        }

        return false;
    }
}