<?php

namespace App\Http\Middleware;

use App\Services\TokenRefreshConfigService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if token refresh features are enabled.
 */
class TokenRefreshFeatureMiddleware
{
    public function __construct(
        private TokenRefreshConfigService $configService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (!$this->configService->isFeatureEnabled($feature)) {
            abort(404, "Feature '{$feature}' is not enabled");
        }

        return $next($request);
    }
}