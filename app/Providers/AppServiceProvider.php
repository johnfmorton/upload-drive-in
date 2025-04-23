<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Merge translation overrides from messages.override.php
        $baseFile = resource_path('lang/en/messages.php');
        $overrideFile = resource_path('lang/en/messages.override.php');

        if (file_exists($baseFile)) {
            $messages = require $baseFile;

            if (file_exists($overrideFile)) {
                $messages = array_merge($messages, require $overrideFile);
            }

            app('translator')->addLines($messages, 'en');
        }
    }
}
