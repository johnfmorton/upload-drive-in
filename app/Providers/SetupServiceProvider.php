<?php

namespace App\Providers;

use App\Services\SetupService;
use Illuminate\Support\ServiceProvider;

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
        //
    }
}