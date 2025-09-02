<?php

namespace App\Services;

use App\Contracts\CloudStorageErrorHandlerInterface;
use App\Exceptions\CloudStorageException;
use Illuminate\Support\Facades\Log;

/**
 * Factory for creating provider-specific error handlers
 * 
 * Provides centralized creation and management of error handlers
 * for different cloud storage providers
 */
class CloudStorageErrorHandlerFactory
{
    /**
     * Registry of provider error handlers
     *
     * @var array<string, string>
     */
    private array $handlers = [];

    /**
     * Cache of instantiated error handlers
     *
     * @var array<string, CloudStorageErrorHandlerInterface>
     */
    private array $instances = [];

    /**
     * Create a new error handler factory
     */
    public function __construct()
    {
        $this->registerDefaultHandlers();
    }

    /**
     * Register default error handlers
     */
    private function registerDefaultHandlers(): void
    {
        $this->register('google-drive', GoogleDriveErrorHandler::class);
        $this->register('amazon-s3', S3ErrorHandler::class);
    }

    /**
     * Register an error handler for a provider
     *
     * @param string $providerName The provider name
     * @param string $handlerClass The error handler class name
     * @throws CloudStorageException If the handler class is invalid
     */
    public function register(string $providerName, string $handlerClass): void
    {
        if (!class_exists($handlerClass)) {
            throw new CloudStorageException("Error handler class '{$handlerClass}' does not exist");
        }

        if (!is_subclass_of($handlerClass, CloudStorageErrorHandlerInterface::class)) {
            throw new CloudStorageException(
                "Error handler class '{$handlerClass}' must implement CloudStorageErrorHandlerInterface"
            );
        }

        $this->handlers[$providerName] = $handlerClass;
        
        // Clear cached instance if it exists
        unset($this->instances[$providerName]);

        Log::debug('Registered error handler', [
            'provider' => $providerName,
            'handler_class' => $handlerClass
        ]);
    }

    /**
     * Create an error handler for the specified provider
     *
     * @param string $providerName The provider name
     * @return CloudStorageErrorHandlerInterface The error handler instance
     * @throws CloudStorageException If no handler is registered for the provider
     */
    public function create(string $providerName): CloudStorageErrorHandlerInterface
    {
        // Return cached instance if available
        if (isset($this->instances[$providerName])) {
            return $this->instances[$providerName];
        }

        if (!isset($this->handlers[$providerName])) {
            throw new CloudStorageException("No error handler registered for provider '{$providerName}'");
        }

        $handlerClass = $this->handlers[$providerName];
        
        try {
            $handler = app($handlerClass);
            
            if (!$handler instanceof CloudStorageErrorHandlerInterface) {
                throw new CloudStorageException(
                    "Error handler for provider '{$providerName}' does not implement CloudStorageErrorHandlerInterface"
                );
            }

            // Cache the instance
            $this->instances[$providerName] = $handler;

            Log::debug('Created error handler', [
                'provider' => $providerName,
                'handler_class' => $handlerClass
            ]);

            return $handler;
        } catch (\Exception $e) {
            throw new CloudStorageException(
                "Failed to create error handler for provider '{$providerName}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Check if an error handler is registered for a provider
     *
     * @param string $providerName The provider name
     * @return bool True if a handler is registered
     */
    public function hasHandler(string $providerName): bool
    {
        return isset($this->handlers[$providerName]);
    }

    /**
     * Get all registered provider names
     *
     * @return array<string> Array of provider names
     */
    public function getRegisteredProviders(): array
    {
        return array_keys($this->handlers);
    }

    /**
     * Get the handler class name for a provider
     *
     * @param string $providerName The provider name
     * @return string|null The handler class name, or null if not registered
     */
    public function getHandlerClass(string $providerName): ?string
    {
        return $this->handlers[$providerName] ?? null;
    }

    /**
     * Unregister an error handler for a provider
     *
     * @param string $providerName The provider name
     */
    public function unregister(string $providerName): void
    {
        unset($this->handlers[$providerName]);
        unset($this->instances[$providerName]);

        Log::debug('Unregistered error handler', [
            'provider' => $providerName
        ]);
    }

    /**
     * Clear all cached error handler instances
     */
    public function clearCache(): void
    {
        $this->instances = [];
        Log::debug('Cleared error handler cache');
    }

    /**
     * Get error handler statistics
     *
     * @return array Statistics about registered handlers
     */
    public function getStatistics(): array
    {
        return [
            'registered_providers' => count($this->handlers),
            'cached_instances' => count($this->instances),
            'providers' => array_keys($this->handlers),
            'cached_providers' => array_keys($this->instances)
        ];
    }

    /**
     * Validate all registered error handlers
     *
     * @return array<string, bool> Array of provider => validation_result
     */
    public function validateAllHandlers(): array
    {
        $results = [];

        foreach ($this->handlers as $providerName => $handlerClass) {
            try {
                $this->create($providerName);
                $results[$providerName] = true;
            } catch (\Exception $e) {
                $results[$providerName] = false;
                Log::warning('Error handler validation failed', [
                    'provider' => $providerName,
                    'handler_class' => $handlerClass,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Create error handler with fallback to default
     *
     * @param string $providerName The provider name
     * @param string|null $fallbackProvider Fallback provider name
     * @return CloudStorageErrorHandlerInterface The error handler instance
     * @throws CloudStorageException If no handler can be created
     */
    public function createWithFallback(string $providerName, ?string $fallbackProvider = null): CloudStorageErrorHandlerInterface
    {
        try {
            return $this->create($providerName);
        } catch (CloudStorageException $e) {
            if ($fallbackProvider && $this->hasHandler($fallbackProvider)) {
                Log::warning('Using fallback error handler', [
                    'requested_provider' => $providerName,
                    'fallback_provider' => $fallbackProvider,
                    'error' => $e->getMessage()
                ]);
                
                return $this->create($fallbackProvider);
            }

            throw $e;
        }
    }
}