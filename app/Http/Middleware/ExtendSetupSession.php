<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Extend session lifetime for setup pages to prevent CSRF token expiration
 * during the setup process.
 */
class ExtendSetupSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extend session lifetime to 2 hours for setup pages
        config(['session.lifetime' => 120]);
        
        // Regenerate CSRF token on GET requests to ensure it's fresh
        if ($request->isMethod('GET')) {
            $request->session()->regenerateToken();
        }
        
        return $next($request);
    }
}