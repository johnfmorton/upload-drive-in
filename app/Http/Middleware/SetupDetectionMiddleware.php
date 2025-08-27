<?php

namespace App\Http\Middleware;

use App\Services\SetupDetectionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to detect setup completion and redirect to instructions when needed.
 * 
 * This middleware replaces the complex setup wizard middleware with a simple
 * detection system that redirects to setup instructions when setup is incomplete.
 */
class SetupDetectionMiddleware
{
    /**
     * The setup detection service instance.
     */
    public function __construct(
        private SetupDetectionService $setupDetectionService
    ) {
        \Log::info('SetupDetectionMiddleware: Constructor called - middleware is being instantiated');
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Debug logging
            \Log::info('SetupDetectionMiddleware: Processing request', [
                'path' => $request->path(),
                'method' => $request->method(),
                'route_name' => $request->route()?->getName()
            ]);

            // Check if setup is enabled first
            $setupEnabled = $this->setupDetectionService->isSetupEnabled();
            \Log::info('SetupDetectionMiddleware: Setup enabled check', [
                'setup_enabled' => $setupEnabled
            ]);

            // If setup is disabled, allow all requests through
            if (!$setupEnabled) {
                \Log::info('SetupDetectionMiddleware: Setup disabled, allowing all access');
                return $next($request);
            }

            // Allow setup instructions route to pass through
            if ($this->isSetupInstructionsRoute($request)) {
                \Log::info('SetupDetectionMiddleware: Allowing setup instructions route', [
                    'path' => $request->path(),
                    'route_name' => $request->route()?->getName()
                ]);
                return $next($request);
            }

            // Allow essential routes that should always be accessible
            if ($this->isExemptRoute($request)) {
                \Log::info('SetupDetectionMiddleware: Allowing exempt route');
                return $next($request);
            }

            // Check if setup is complete
            $setupComplete = $this->setupDetectionService->isSetupComplete();
            \Log::info('SetupDetectionMiddleware: Setup status check', [
                'setup_complete' => $setupComplete,
                'missing_requirements' => $this->setupDetectionService->getMissingRequirements()
            ]);

            if (!$setupComplete) {
                \Log::info('SetupDetectionMiddleware: Setup incomplete, redirecting to instructions');
                
                // Handle AJAX requests differently
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'error' => 'Setup required',
                        'message' => 'Application setup is required before accessing this resource.',
                        'redirect' => route('setup.instructions'),
                        'missing_requirements' => $this->setupDetectionService->getMissingRequirements()
                    ], 503);
                }
                
                // Redirect to setup instructions
                return redirect()->route('setup.instructions');
            }

            \Log::info('SetupDetectionMiddleware: Setup complete, allowing normal access');
            // Setup is complete, allow normal application access
            return $next($request);
            
        } catch (\Exception $e) {
            \Log::error('SetupDetectionMiddleware: Exception occurred', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // If there's an exception, assume setup is required and redirect
            return redirect()->route('setup.instructions');
        }
    }

    /**
     * Check if the current route is the setup instructions route.
     */
    private function isSetupInstructionsRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        $path = $request->path();
        
        \Log::info('SetupDetectionMiddleware: Checking if setup instructions route', [
            'route_name' => $routeName,
            'path' => $path,
            'is_setup_instructions' => $routeName === 'setup.instructions'
        ]);
        
        return $routeName === 'setup.instructions';
    }

    /**
     * Check if the current route should be exempt from setup requirements.
     */
    private function isExemptRoute(Request $request): bool
    {
        $path = $request->path();
        
        // Asset-related paths that should always be accessible
        $exemptPaths = [
            'build',
            'storage',
            'images',
            'css',
            'js',
            'fonts',
            'assets',
            'favicon.ico',
            'robots.txt',
        ];
        
        foreach ($exemptPaths as $exemptPath) {
            if (str_starts_with($path, $exemptPath . '/') || $path === $exemptPath) {
                return true;
            }
        }
        
        // Health check and monitoring routes
        $routeName = $request->route()?->getName();
        $exemptRoutes = [
            'health',
            'health.check',
        ];
        
        if ($routeName && in_array($routeName, $exemptRoutes)) {
            return true;
        }
        
        return false;
    }
}