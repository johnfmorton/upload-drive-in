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

        // Check for asset requirements first (before database checks)
        try {
            if (!$this->setupService->areAssetsValid()) {
                return $this->handleAssetMissing($request);
            }
        } catch (\Exception $e) {
            // Catch Vite manifest exceptions and other asset-related errors
            return $this->handleAssetMissing($request);
        }

        // Check if setup is required (database and other checks)
        try {
            if ($this->setupService->isSetupRequired()) {
                // Determine the appropriate setup step
                $setupStep = $this->setupService->getSetupStep();
                $redirectRoute = $this->getSetupRouteForStep($setupStep);
                
                // Handle AJAX requests differently
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'error' => 'Setup required',
                        'message' => 'Application setup is required before accessing this resource.',
                        'redirect' => route($redirectRoute),
                        'step' => $setupStep
                    ], 503);
                }
                
                // Redirect to appropriate setup step
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
     * Handle requests when assets are missing
     */
    private function handleAssetMissing(Request $request): Response
    {
        $assetRoute = 'setup.assets';
        
        // Handle AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'error' => 'Assets missing',
                'message' => 'Frontend assets need to be built before the application can run.',
                'redirect' => route($assetRoute),
                'step' => 'assets'
            ], 503);
        }
        
        // Redirect to asset build instructions
        return redirect()->route($assetRoute);
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

        // Additional asset-related exemptions
        if ($this->isAssetRelatedRoute($request)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the current route is asset-related and should be exempt
     */
    private function isAssetRelatedRoute(Request $request): bool
    {
        $path = $request->path();
        $routeName = $request->route()?->getName();
        
        // Asset-related paths that should always be accessible
        $assetPaths = [
            'build',
            'storage',
            'images',
            'css',
            'js',
            'fonts',
            'assets',
        ];
        
        foreach ($assetPaths as $assetPath) {
            if (str_starts_with($path, $assetPath . '/') || $path === $assetPath) {
                return true;
            }
        }
        
        // Asset-related route names
        $assetRoutes = [
            'setup.ajax.check-assets',
        ];
        
        if ($routeName && in_array($routeName, $assetRoutes)) {
            return true;
        }
        
        return false;
    }

    /**
     * Get the appropriate setup route for a given step
     */
    private function getSetupRouteForStep(string $step): string
    {
        return match ($step) {
            'assets' => 'setup.assets',
            'welcome' => 'setup.welcome',
            'database' => 'setup.database',
            'admin' => 'setup.admin',
            'storage' => 'setup.storage',
            'complete' => 'setup.complete',
            default => Config::get('setup.redirect_route', 'setup.welcome')
        };
    }

    /**
     * Check if a path matches a pattern (supports wildcards)
     */
    private function matchesPattern(string $path, string $pattern): bool
    {
        // Escape special regex characters except * and ?
        $escaped = preg_quote($pattern, '/');
        
        // Convert escaped wildcards back to regex patterns
        $regex = str_replace(['\*', '\?'], ['.*', '.'], $escaped);
        $regex = '/^' . $regex . '$/i';
        
        return preg_match($regex, $path) === 1;
    }
}