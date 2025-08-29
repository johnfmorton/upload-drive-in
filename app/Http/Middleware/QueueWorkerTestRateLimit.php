<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class QueueWorkerTestRateLimit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $config = config('setup.queue_worker_test');
        $identifier = $this->getIdentifier($request);
        
        // Check cooldown period
        if ($this->isInCooldown($identifier, $config)) {
            return response()->json([
                'error' => 'Queue worker test is in cooldown period. Please wait before testing again.',
                'cooldown_remaining' => $this->getCooldownRemaining($identifier, $config),
            ], 429);
        }
        
        // Check rate limit
        $rateLimitKey = 'queue_worker_test:' . $identifier;
        $maxAttempts = $config['rate_limit']['max_attempts'];
        $decayMinutes = $config['rate_limit']['decay_minutes'];
        
        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);
            
            return response()->json([
                'error' => 'Too many queue worker test attempts. Please try again later.',
                'retry_after' => $retryAfter,
                'max_attempts' => $maxAttempts,
                'decay_minutes' => $decayMinutes,
            ], 429);
        }
        
        // Increment rate limit counter
        RateLimiter::hit($rateLimitKey, $decayMinutes * 60);
        
        $response = $next($request);
        
        // Set cooldown after successful test initiation
        if ($response->getStatusCode() === 200) {
            $this->setCooldown($identifier, $config);
        }
        
        return $response;
    }
    
    /**
     * Get unique identifier for rate limiting.
     */
    private function getIdentifier(Request $request): string
    {
        // Use IP address and user agent for identification
        return hash('sha256', $request->ip() . '|' . $request->userAgent());
    }
    
    /**
     * Check if the identifier is in cooldown period.
     */
    private function isInCooldown(string $identifier, array $config): bool
    {
        $cooldownKey = $config['cache_keys']['cooldown'] . $identifier;
        return Cache::has($cooldownKey);
    }
    
    /**
     * Get remaining cooldown time in seconds.
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
     * Set cooldown period for the identifier.
     */
    private function setCooldown(string $identifier, array $config): void
    {
        $cooldownKey = $config['cache_keys']['cooldown'] . $identifier;
        $cooldownSeconds = $config['cooldown']['seconds'];
        $cooldownEnd = time() + $cooldownSeconds;
        
        Cache::put($cooldownKey, $cooldownEnd, $cooldownSeconds);
    }
}