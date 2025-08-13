<?php

namespace App\Http\Middleware;

use App\Services\SetupService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetupCompleteMiddleware
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
        // Check if setup is complete
        if ($this->setupService->isSetupComplete()) {
            // If setup is complete and trying to access setup routes, return 404
            if ($this->isSetupRoute($request)) {
                abort(404);
            }
        }

        return $next($request);
    }

    /**
     * Check if the current route is a setup route.
     */
    private function isSetupRoute(Request $request): bool
    {
        $path = $request->path();
        
        // Check if path starts with setup
        if (str_starts_with($path, 'setup')) {
            return true;
        }

        // Check route name
        $routeName = $request->route()?->getName();
        if ($routeName && str_starts_with($routeName, 'setup.')) {
            return true;
        }

        return false;
    }
}