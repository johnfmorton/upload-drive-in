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
        // Merge base translations and overrides, then flatten under the 'messages' group
        $baseFile     = resource_path('lang/en/messages.php');
        $overrideFile = resource_path('lang/en/messages.override.php');

        // Load base messages
        $messages = [];
        if (file_exists($baseFile)) {
            $loaded = require $baseFile;
            $messages = is_array($loaded) ? $loaded : [];
        }

        // Merge user overrides
        if (file_exists($overrideFile)) {
            $loadedOverride = require $overrideFile;
            if (is_array($loadedOverride)) {
                $messages = array_merge($messages, $loadedOverride);
            }
        }

        // Flatten keys into "messages.{key}" format
        $lines = [];
        foreach ($messages as $key => $value) {
            if (! is_string($key)) {
                continue;
            }
            $lines["messages.$key"] = $value;
        }

        // Register flattened messages with the translator for the 'en' locale
        app('translator')->addLines($lines, 'en');
    }
}
