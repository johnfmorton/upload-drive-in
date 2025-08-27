<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure setup routes are only accessible when setup is enabled.
 * 
 * When APP_SETUP_ENABLED=false, this middleware will return a 404 response
 * to prevent access to setup-related functionality in production.
 */
class RequireSetupEnabledMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if setup is enabled directly from config
        $setupEnabled = config('setup.enabled', false);
        
        // Handle string values from environment
        if (is_string($setupEnabled)) {
            $setupEnabled = strtolower($setupEnabled) === 'true';
        }
        
        if (!$setupEnabled) {
            // Return 404 when setup is disabled to hide setup functionality
            abort(404);
        }

        return $next($request);
    }
}