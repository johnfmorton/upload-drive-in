<?php

namespace App\Http\Middleware;

use App\Services\SetupService;
use Closure;
use Illuminate\Http\Request;
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
        // Allow setup routes to pass through
        if ($this->isSetupRoute($request)) {
            return $next($request);
        }

        // Allow essential assets and health checks
        if ($this->isExemptRoute($request)) {
            return $next($request);
        }

        // Check if setup is required
        if ($this->setupService->isSetupRequired()) {
            // Redirect to setup wizard
            return redirect('/setup/welcome');
        }

        return $next($request);
    }

    /**
     * Check if the current route is a setup route.
     */
    private function isSetupRoute(Request $request): bool
    {
        $path = $request->path();
        
        // Allow all setup routes
        if (str_starts_with($path, 'setup')) {
            return true;
        }

        // Allow setup route names
        $routeName = $request->route()?->getName();
        if ($routeName && str_starts_with($routeName, 'setup.')) {
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
        
        // Allow static assets
        if (str_starts_with($path, 'build/') || 
            str_starts_with($path, 'storage/') ||
            str_starts_with($path, 'images/') ||
            str_starts_with($path, 'css/') ||
            str_starts_with($path, 'js/')) {
            return true;
        }

        // Allow common asset files
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/i', $path)) {
            return true;
        }

        // Allow health check endpoints
        if (in_array($path, ['health', 'ping', 'status'])) {
            return true;
        }

        // Allow favicon and robots.txt
        if (in_array($path, ['favicon.ico', 'robots.txt', 'sitemap.xml'])) {
            return true;
        }

        return false;
    }
}