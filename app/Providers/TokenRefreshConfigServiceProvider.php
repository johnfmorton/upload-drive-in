<?php

namespace App\Providers;

use App\Services\TokenRefreshConfigService;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for token refresh configuration services.
 */
class TokenRefreshConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(TokenRefreshConfigService::class, function ($app) {
            return new TokenRefreshConfigService();
        });

        // Register alias for easier access
        $this->app->alias(TokenRefreshConfigService::class, 'token-refresh-config');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration file
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/token-refresh.php' => config_path('token-refresh.php'),
            ], 'token-refresh-config');

            $this->publishes([
                __DIR__ . '/../../.env.token-refresh.example' => base_path('.env.token-refresh.example'),
            ], 'token-refresh-env');
        }

        // Load configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/token-refresh.php',
            'token-refresh'
        );
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            TokenRefreshConfigService::class,
            'token-refresh-config',
        ];
    }
}