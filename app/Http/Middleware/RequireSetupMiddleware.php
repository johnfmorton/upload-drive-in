<?php

namespace App\Http\Middleware;

use App\Services\SetupService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class RequireSetupMiddleware
{
    /**
     * The setup service instance.
     */
    public function __construct(
        private SetupService $setupService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip setup checks if bootstrap checks are disabled
        if (!Config::get('setup.bootstrap_checks', true)) {
            return $next($request);
        }

        // Allow setup routes to pass through
        if ($this->isSetupRoute($request)) {
            return $next($request);
        }

        // Allow essential assets and health checks
        if ($this->isExemptRoute($request)) {
            return $next($request);
        }

        // Check if setup is required
        try {
            if ($this->setupService->isSetupRequired()) {
                // Get redirect route from configuration
                $redirectRoute = Config::get('setup.redirect_route', 'setup.welcome');
                
                // Handle AJAX requests differently
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'error' => 'Setup required',
                        'message' => 'Application setup is required before accessing this resource.',
                        'redirect' => route($redirectRoute)
                    ], 503);
                }
                
                // Redirect to setup wizard
                return redirect()->route($redirectRoute);
            }
        } catch (\Exception $e) {
            // If setup check fails, assume setup is required
            // This prevents breaking the application during bootstrap issues
            $redirectRoute = Config::get('setup.redirect_route', 'setup.welcome');
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'error' => 'Setup check failed',
                    'message' => 'Unable to determine setup status. Please complete setup.',
                    'redirect' => route($redirectRoute)
                ], 503);
            }
            
            return redirect()->route($redirectRoute);
        }

        return $next($request);
    }

    /**
     * Check if the current route is a setup route.
     */
    private function isSetupRoute(Request $request): bool
    {
        $path = $request->path();
        $routePrefix = Config::get('setup.route_prefix', 'setup');
        
        // Allow all setup routes based on configured prefix
        if (str_starts_with($path, $routePrefix)) {
            return true;
        }

        // Allow setup route names
        $routeName = $request->route()?->getName();
        if ($routeName && str_starts_with($routeName, $routePrefix . '.')) {
            return true;
        }

        return false;
    }

    /**
     * Check if the current route should be exempt from setup requirements.
     */
    private function isExemptRoute(Request $request): bool
    {
        $path = $request->path();
        
        // Check configured exempt routes
        $exemptRoutes = Config::get('setup.exempt_routes', []);
        if (in_array($path, $exemptRoutes)) {
            return true;
        }

        // Check configured exempt path patterns
        $exemptPaths = Config::get('setup.exempt_paths', []);
        foreach ($exemptPaths as $pattern) {
            if ($this->matchesPattern($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a path matches a pattern (supports wildcards)
     */
    private function matchesPattern(string $path, string $pattern): bool
    {
        // Convert wildcard pattern to regex
        $regex = str_replace(['*', '?'], ['.*', '.'], $pattern);
        $regex = '/^' . $regex . '$/i';
        
        return preg_match($regex, $path) === 1;
    }
}