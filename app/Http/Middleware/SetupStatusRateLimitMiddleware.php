<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limiting middleware for setup status checks and queue testing endpoints.
 * Prevents abuse of status checking and queue testing functionality.
 */
class SetupStatusRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '30', string $decayMinutes = '1'): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $this->logRateLimitExceeded($request, $key);
            
            return $this->buildResponse($key, $maxAttempts);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            RateLimiter::retriesLeft($key, $maxAttempts),
            RateLimiter::availableIn($key)
        );
    }

    /**
     * Resolve request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $userId = $request->user()?->id ?? 'guest';
        $ip = $request->ip();
        $route = $request->route()?->getName() ?? 'unknown';
        
        // Different limits for different types of operations
        $operation = $this->getOperationType($request);
        
        return "setup_status:{$operation}:{$userId}:{$ip}:{$route}";
    }

    /**
     * Determine the type of operation for more granular rate limiting.
     */
    protected function getOperationType(Request $request): string
    {
        $route = $request->route()?->getName() ?? '';
        
        if (str_contains($route, 'queue.test')) {
            return 'queue_test';
        }
        
        if (str_contains($route, 'status.refresh')) {
            return 'status_refresh';
        }
        
        return 'general';
    }

    /**
     * Log rate limit exceeded event.
     */
    protected function logRateLimitExceeded(Request $request, string $key): void
    {
        Log::warning('Setup status rate limit exceeded', [
            'user_id' => $request->user()?->id,
            'user_email' => $request->user()?->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => $request->route()?->getName(),
            'url' => $request->fullUrl(),
            'rate_limit_key' => $key,
            'operation_type' => $this->getOperationType($request),
            'timestamp' => now()->toISOString(),
        ]);

        // Also log to security channel for monitoring
        Log::channel('security')->warning('Rate limit exceeded for setup operations', [
            'user' => $request->user()?->email ?? 'guest',
            'ip' => $request->ip(),
            'route' => $request->route()?->getName(),
            'operation' => $this->getOperationType($request),
        ]);
    }

    /**
     * Build the rate limit response.
     */
    protected function buildResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = RateLimiter::availableIn($key);
        
        $response = response()->json([
            'success' => false,
            'message' => 'Too many requests. Please wait before trying again.',
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Rate limit exceeded for this operation',
                'retry_after_seconds' => $retryAfter,
                'max_attempts_per_minute' => $maxAttempts,
            ]
        ], 429);

        return $this->addHeaders($response, $maxAttempts, 0, $retryAfter);
    }

    /**
     * Add rate limit headers to response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $retriesLeft, int $retryAfter = null): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $retriesLeft),
        ]);

        if ($retryAfter !== null) {
            $response->headers->add([
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
                'Retry-After' => $retryAfter,
            ]);
        }

        return $response;
    }
}