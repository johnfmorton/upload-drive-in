<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SetupService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind cloud storage provider interfaces
        $this->app->bind(
            \App\Contracts\CloudStorageProviderInterface::class,
            \App\Services\GoogleDriveProvider::class
        );
        
        $this->app->bind(
            \App\Contracts\CloudStorageErrorHandlerInterface::class,
            \App\Services\GoogleDriveErrorHandler::class
        );
        
        // Backward compatibility: Add deprecation warnings to GoogleDriveService
        $this->app->extend(\App\Services\GoogleDriveService::class, function ($service, $app) {
            // Log deprecation warning when GoogleDriveService is resolved
            if (config('app.debug')) {
                \Log::warning('GoogleDriveService is deprecated. Use CloudStorageManager instead.', [
                    'migration_guide' => 'See documentation for migration guide',
                    'new_service' => \App\Services\CloudStorageManager::class,
                    'deprecated_service' => \App\Services\GoogleDriveService::class,
                    'resolved_at' => now()->toISOString()
                ]);
            }
            return $service;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        \App\Models\FileUpload::observe(\App\Observers\FileUploadObserver::class);
        
        // Initialize setup state checking
        $this->initializeSetupIntegration();
        
        // Log pagination configuration on startup
        $this->logPaginationConfiguration();
        
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

    /**
     * Initialize setup integration with the application
     */
    private function initializeSetupIntegration(): void
    {
        try {
            // Validate setup environment during bootstrap
            $setupService = $this->app->make(SetupService::class);
            $issues = $setupService->validateSetupEnvironment();
            
            if (!empty($issues)) {
                // Log setup environment issues
                foreach ($issues as $issue) {
                    \Log::warning('Setup environment issue: ' . $issue);
                }
            }
            
            // Register setup-related macros or helpers if needed
            $this->registerSetupHelpers();
            
        } catch (\Exception $e) {
            // Silently handle setup service initialization failures
            // This prevents breaking the application during bootstrap
            \Log::error('Setup service initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Register setup-related helpers and macros
     */
    private function registerSetupHelpers(): void
    {
        // Add any global helpers or macros related to setup
        // For example, view helpers for checking setup state
    }

    /**
     * Log pagination configuration on application startup
     */
    private function logPaginationConfiguration(): void
    {
        try {
            \App\Helpers\PaginationConfigHelper::logPaginationConfiguration();
        } catch (\Exception $e) {
            // Silently handle pagination configuration logging failures
            // This prevents breaking the application during bootstrap
            \Log::error('Pagination configuration logging failed: ' . $e->getMessage());
        }
    }
}
