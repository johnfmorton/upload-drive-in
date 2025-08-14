<?php

namespace App\Providers;

use App\Services\SetupService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Config;

class SetupServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SetupService::class, function ($app) {
            return new SetupService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Share setup state with all views for conditional rendering
        $this->shareSetupStateWithViews();
        
        // Register setup-related configuration
        $this->registerSetupConfiguration();
        
        // Bind setup service for dependency injection
        $this->bindSetupServiceForMiddleware();
    }

    /**
     * Share setup state with all views for conditional rendering
     */
    private function shareSetupStateWithViews(): void
    {
        try {
            View::composer('*', function ($view) {
                // Only check setup state for non-setup views to avoid recursion
                $viewName = $view->getName();
                if (!str_starts_with($viewName, 'setup.') && !str_starts_with($viewName, 'components.setup-')) {
                    try {
                        $setupService = app(SetupService::class);
                        $view->with([
                            'isSetupRequired' => $setupService->isSetupRequired(),
                            'setupStep' => $setupService->getSetupStep(),
                            'setupProgress' => $setupService->getSetupProgress(),
                        ]);
                    } catch (\Exception $e) {
                        // Silently fail if setup state cannot be determined
                        // This prevents breaking the application during bootstrap issues
                        $view->with([
                            'isSetupRequired' => false,
                            'setupStep' => 'complete',
                            'setupProgress' => 100,
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            // Silently fail during bootstrap if setup service cannot be created
            // This prevents breaking the application during initial deployment
        }
    }

    /**
     * Register setup-related configuration
     */
    private function registerSetupConfiguration(): void
    {
        // Merge setup-specific configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/setup.php', 'setup'
        );
    }

    /**
     * Bind setup service for middleware dependency injection
     */
    private function bindSetupServiceForMiddleware(): void
    {
        // Ensure setup service is available early in the request lifecycle
        $this->app->resolving('App\Http\Middleware\RequireSetupMiddleware', function ($middleware, $app) {
            // The middleware constructor already handles dependency injection
            // This ensures the service is available when needed
        });
    }
}