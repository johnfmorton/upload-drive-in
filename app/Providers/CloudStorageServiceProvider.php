<?php

namespace App\Providers;

use App\Services\CloudStorageFactory;
use App\Services\CloudStorageManager;
use App\Services\CloudConfigurationService;
use App\Services\GoogleDriveProvider;
use App\Contracts\CloudStorageProviderInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

/**
 * Service provider for cloud storage system
 * 
 * Handles automatic provider discovery, registration, and service binding
 * for the cloud storage abstraction layer.
 */
class CloudStorageServiceProvider extends ServiceProvider
{
    /**
     * Register cloud storage services
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerCoreServices();
        $this->registerProviders();
        $this->registerErrorHandlers();
    }

    /**
     * Bootstrap cloud storage services
     *
     * @return void
     */
    public function boot(): void
    {
        $this->discoverAndRegisterProviders();
        $this->validateRegisteredProviders();
    }

    /**
     * Register core cloud storage services
     *
     * @return void
     */
    private function registerCoreServices(): void
    {
        // Register CloudConfigurationService as singleton
        $this->app->singleton(CloudConfigurationService::class, function ($app) {
            return new CloudConfigurationService();
        });

        // Register CloudStorageFactory as singleton
        $this->app->singleton(CloudStorageFactory::class, function ($app) {
            return new CloudStorageFactory(
                $app,
                $app->make(CloudConfigurationService::class)
            );
        });

        // Register CloudStorageManager as singleton
        $this->app->singleton(CloudStorageManager::class, function ($app) {
            return new CloudStorageManager(
                $app->make(CloudStorageFactory::class),
                $app->make(CloudConfigurationService::class)
            );
        });

        // Register feature detection and utilization services
        $this->registerFeatureServices();
        
        // Register configuration validation and health check services
        $this->registerValidationAndHealthServices();

        Log::debug('CloudStorageServiceProvider: Core services registered');
    }

    /**
     * Register feature detection and utilization services
     *
     * @return void
     */
    private function registerFeatureServices(): void
    {
        // Register CloudStorageFeatureDetectionService as singleton
        $this->app->singleton(\App\Services\CloudStorageFeatureDetectionService::class, function ($app) {
            return new \App\Services\CloudStorageFeatureDetectionService(
                $app->make(CloudStorageManager::class)
            );
        });

        // Register CloudStorageGracefulDegradationService as singleton
        $this->app->singleton(\App\Services\CloudStorageGracefulDegradationService::class, function ($app) {
            return new \App\Services\CloudStorageGracefulDegradationService(
                $app->make(CloudStorageManager::class),
                $app->make(\App\Services\CloudStorageFeatureDetectionService::class)
            );
        });

        // Register CloudStorageFeatureUtilizationService as singleton
        $this->app->singleton(\App\Services\CloudStorageFeatureUtilizationService::class, function ($app) {
            return new \App\Services\CloudStorageFeatureUtilizationService(
                $app->make(CloudStorageManager::class),
                $app->make(\App\Services\CloudStorageFeatureDetectionService::class)
            );
        });

        Log::debug('CloudStorageServiceProvider: Feature services registered');
    }

    /**
     * Register configuration validation and health check services
     *
     * @return void
     */
    private function registerValidationAndHealthServices(): void
    {
        // Register CloudStorageConfigurationValidationService as singleton
        $this->app->singleton(\App\Services\CloudStorageConfigurationValidationService::class, function ($app) {
            return new \App\Services\CloudStorageConfigurationValidationService(
                $app->make(CloudConfigurationService::class),
                $app->make(CloudStorageFactory::class)
            );
        });

        // Register CloudStorageProviderHealthCheckService as singleton
        $this->app->singleton(\App\Services\CloudStorageProviderHealthCheckService::class, function ($app) {
            return new \App\Services\CloudStorageProviderHealthCheckService(
                $app->make(CloudStorageManager::class),
                $app->make(\App\Services\CloudStorageHealthService::class),
                $app->make(\App\Services\CloudStorageConfigurationValidationService::class),
                $app->make(\App\Services\CloudStorageLogService::class)
            );
        });

        // Register CloudStorageProviderMonitoringService as singleton
        $this->app->singleton(\App\Services\CloudStorageProviderMonitoringService::class, function ($app) {
            return new \App\Services\CloudStorageProviderMonitoringService(
                $app->make(\App\Services\CloudStorageProviderHealthCheckService::class),
                $app->make(\App\Services\CloudStorageConfigurationValidationService::class),
                $app->make(\App\Services\CloudStorageHealthService::class),
                $app->make(\App\Services\CloudStorageLogService::class)
            );
        });

        Log::debug('CloudStorageServiceProvider: Validation and health services registered');
    }

    /**
     * Register cloud storage providers
     *
     * @return void
     */
    private function registerProviders(): void
    {
        $factory = $this->app->make(CloudStorageFactory::class);

        // Register built-in providers
        $this->registerBuiltInProviders($factory);

        Log::debug('CloudStorageServiceProvider: Built-in providers registered');
    }

    /**
     * Register built-in cloud storage providers
     *
     * @param CloudStorageFactory $factory
     * @return void
     */
    private function registerBuiltInProviders(CloudStorageFactory $factory): void
    {
        try {
            // Register Google Drive provider
            $factory->register('google-drive', GoogleDriveProvider::class);

            // Register other providers when they become available
            // $factory->register('amazon-s3', S3Provider::class);
            // $factory->register('azure-blob', AzureBlobProvider::class);
            // $factory->register('dropbox', DropboxProvider::class);

            Log::debug('CloudStorageServiceProvider: Built-in providers registered successfully');

        } catch (\Exception $e) {
            Log::error('CloudStorageServiceProvider: Failed to register built-in providers', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Register error handlers for cloud storage providers
     *
     * @return void
     */
    private function registerErrorHandlers(): void
    {
        // Register default error handler binding
        $this->app->bind(
            \App\Contracts\CloudStorageErrorHandlerInterface::class,
            \App\Services\GoogleDriveErrorHandler::class
        );

        Log::debug('CloudStorageServiceProvider: Error handlers registered');
    }

    /**
     * Discover and register providers automatically
     *
     * @return void
     */
    private function discoverAndRegisterProviders(): void
    {
        try {
            $factory = $this->app->make(CloudStorageFactory::class);
            
            // Discover providers in standard locations
            $discoveredProviders = $factory->discoverProviders([
                app_path('Services'),
                app_path('Providers/CloudStorage'),
            ]);

            // Register discovered providers
            foreach ($discoveredProviders as $name => $className) {
                try {
                    // Skip if already registered
                    $registered = $factory->getRegisteredProviders();
                    if (isset($registered[$name])) {
                        continue;
                    }

                    $factory->register($name, $className);
                    
                    Log::debug('CloudStorageServiceProvider: Auto-discovered provider registered', [
                        'name' => $name,
                        'class' => $className
                    ]);

                } catch (\Exception $e) {
                    Log::warning('CloudStorageServiceProvider: Failed to register discovered provider', [
                        'name' => $name,
                        'class' => $className,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if (count($discoveredProviders) > 0) {
                Log::info('CloudStorageServiceProvider: Provider discovery completed', [
                    'discovered_count' => count($discoveredProviders),
                    'providers' => array_keys($discoveredProviders)
                ]);
            }

        } catch (\Exception $e) {
            Log::error('CloudStorageServiceProvider: Provider discovery failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate all registered providers
     *
     * @return void
     */
    private function validateRegisteredProviders(): void
    {
        try {
            $factory = $this->app->make(CloudStorageFactory::class);
            $registeredProviders = $factory->getRegisteredProviders();

            $validProviders = [];
            $invalidProviders = [];

            foreach ($registeredProviders as $name => $className) {
                if ($factory->validateProvider($className)) {
                    $validProviders[] = $name;
                } else {
                    $invalidProviders[] = ['name' => $name, 'class' => $className];
                }
            }

            if (!empty($invalidProviders)) {
                Log::warning('CloudStorageServiceProvider: Invalid providers detected', [
                    'invalid_providers' => $invalidProviders
                ]);
            }

            Log::info('CloudStorageServiceProvider: Provider validation completed', [
                'total_providers' => count($registeredProviders),
                'valid_providers' => count($validProviders),
                'invalid_providers' => count($invalidProviders),
                'valid_provider_names' => $validProviders
            ]);

        } catch (\Exception $e) {
            Log::error('CloudStorageServiceProvider: Provider validation failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the services provided by the provider
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            CloudStorageFactory::class,
            CloudStorageManager::class,
            CloudConfigurationService::class,
            \App\Services\CloudStorageFeatureDetectionService::class,
            \App\Services\CloudStorageGracefulDegradationService::class,
            \App\Services\CloudStorageFeatureUtilizationService::class,
            \App\Services\CloudStorageConfigurationValidationService::class,
            \App\Services\CloudStorageProviderHealthCheckService::class,
            \App\Services\CloudStorageProviderMonitoringService::class,
        ];
    }
}