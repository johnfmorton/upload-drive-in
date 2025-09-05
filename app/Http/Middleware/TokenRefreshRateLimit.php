<?php

namespace App\Http\Middleware;

use App\Services\TokenSecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class TokenRefreshRateLimit
{
    public function __construct(
        private TokenSecurityService $tokenSecurityService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Authentication required'
            ], 401);
        }

        // Check user-specific rate limit
        if (!$this->tokenSecurityService->checkUserRateLimit($user)) {
            $resetTime = $this->tokenSecurityService->getRateLimitResetTime($user);
            
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many token refresh attempts. Please try again later.',
                'remaining_attempts' => 0,
                'reset_time' => $resetTime?->toISOString(),
                'retry_after' => $resetTime?->diffInSeconds(now()),
            ], 429);
        }

        // Check IP-based rate limit
        if (!$this->tokenSecurityService->checkIpRateLimit()) {
            return response()->json([
                'error' => 'IP rate limit exceeded',
                'message' => 'Too many requests from this IP address. Please try again later.',
                'remaining_attempts' => $this->tokenSecurityService->getRemainingIpAttempts(),
            ], 429);
        }

        // Record the attempt
        $this->tokenSecurityService->recordRefreshAttempt($user);

        $response = $next($request);

        // Add rate limit headers to response
        $response->headers->set('X-RateLimit-Limit', '5');
        $response->headers->set('X-RateLimit-Remaining', (string) $this->tokenSecurityService->getRemainingUserAttempts($user));
        
        $resetTime = $this->tokenSecurityService->getRateLimitResetTime($user);
        if ($resetTime) {
            $response->headers->set('X-RateLimit-Reset', (string) $resetTime->timestamp);
        }

        return $response;
    }
}