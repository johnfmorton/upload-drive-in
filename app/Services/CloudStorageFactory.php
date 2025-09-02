<?php

namespace App\Services;

use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use App\Exceptions\CloudStorageException;
use Illuminate\Support\Facades\Log;
use Illuminate\Container\Container;

/**
 * Factory service for instantiating and registering cloud storage providers
 * 
 * This service handles the creation of provider instances, provider registration,
 * and validation of provider implementations.
 */
class CloudStorageFactory
{
    /**
     * Registry of provider names to class names
     *
     * @var array<string, string>
     */
    private array $providers = [];

    /**
     * Cache of instantiated providers
     *
     * @var array<string, CloudStorageProviderInterface>
     */
    private array $providerCache = [];

    public function __construct(
        private Container $container,
        private CloudConfigurationService $configService
    ) {}

    /**
     * Create a cloud storage provider instance
     *
     * @param string $providerName Provider name
     * @param array $config Optional configuration override
     * @return CloudStorageProviderInterface
     * @throws CloudStorageException
     */
    public function create(string $providerName, array $config = []): CloudStorageProviderInterface
    {
        // Check if provider is registered
        if (!isset($this->providers[$providerName])) {
            throw new CloudStorageException("Provider '{$providerName}' is not registered");
        }

        // Use cache if available and no config override
        $cacheKey = $providerName . '_' . md5(serialize($config));
        if (empty($config) && isset($this->providerCache[$cacheKey])) {
            return $this->providerCache[$cacheKey];
        }

        try {
            $className = $this->providers[$providerName];
            
            // Validate class exists and implements interface
            if (!class_exists($className)) {
                throw new CloudStorageException("Provider class '{$className}' does not exist");
            }

            if (!$this->validateProvider($className)) {
                throw new CloudStorageException("Provider class '{$className}' does not implement CloudStorageProviderInterface");
            }

            // Get configuration
            $effectiveConfig = empty($config) 
                ? $this->configService->getEffectiveConfig($providerName)
                : array_merge($this->configService->getEffectiveConfig($providerName), $config);

            // Create provider instance
            $provider = $this->container->make($className);

            // Initialize provider with configuration
            $provider->initialize($effectiveConfig);

            // Cache the provider if no config override
            if (empty($config)) {
                $this->providerCache[$cacheKey] = $provider;
            }

            Log::debug('CloudStorageFactory: Provider created', [
                'provider' => $providerName,
                'class' => $className,
                'cached' => empty($config)
            ]);

            return $provider;

        } catch (\Exception $e) {
            Log::error('CloudStorageFactory: Failed to create provider', [
                'provider' => $providerName,
                'class' => $this->providers[$providerName] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            throw new CloudStorageException(
                message: "Failed to create provider '{$providerName}': " . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Create a provider instance for a specific user
     *
     * @param User|null $user User for user-specific configuration
     * @param string|null $providerName Provider name (null for user's preferred)
     * @return CloudStorageProviderInterface
     * @throws CloudStorageException
     */
    public function createForUser(User $user = null, string $providerName = null): CloudStorageProviderInterface
    {
        // If no provider specified, use default
        if ($providerName === null) {
            $providerName = config('cloud-storage.default', 'google-drive');
        }

        // Get user-specific configuration if user provided
        $config = [];
        if ($user) {
            // This could be extended to include user-specific settings
            // For now, we use the standard configuration
            $config = $this->configService->getEffectiveConfig($providerName);
        }

        return $this->create($providerName, $config);
    }

    /**
     * Register a cloud storage provider
     *
     * @param string $name Provider name
     * @param string $className Provider class name
     * @return void
     * @throws CloudStorageException
     */
    public function register(string $name, string $className): void
    {
        // Validate provider class
        if (!$this->validateProvider($className)) {
            throw new CloudStorageException("Cannot register provider '{$name}': class '{$className}' does not implement CloudStorageProviderInterface");
        }

        $this->providers[$name] = $className;

        Log::debug('CloudStorageFactory: Provider registered', [
            'name' => $name,
            'class' => $className
        ]);
    }

    /**
     * Get all registered providers
     *
     * @return array<string, string> Provider names mapped to class names
     */
    public function getRegisteredProviders(): array
    {
        return $this->providers;
    }

    /**
     * Validate that a provider class implements the required interface
     *
     * @param string $className
     * @return bool
     */
    public function validateProvider(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        $reflection = new \ReflectionClass($className);
        return $reflection->implementsInterface(CloudStorageProviderInterface::class);
    }

    /**
     * Discover providers automatically by scanning for classes
     * 
     * This method can be used to automatically find provider classes
     * that implement the CloudStorageProviderInterface
     *
     * @param array $searchPaths Paths to search for provider classes
     * @return array<string, string> Discovered providers
     */
    public function discoverProviders(array $searchPaths = []): array
    {
        $discovered = [];
        
        // Default search paths
        if (empty($searchPaths)) {
            $searchPaths = [
                app_path('Services'),
                app_path('Providers'),
            ];
        }

        foreach ($searchPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $files = glob($path . '/*Provider.php');
            
            foreach ($files as $file) {
                $className = $this->getClassNameFromFile($file);
                
                if ($className && $this->validateProvider($className)) {
                    // Extract provider name from class name
                    $providerName = $this->extractProviderName($className);
                    $discovered[$providerName] = $className;
                }
            }
        }

        Log::debug('CloudStorageFactory: Providers discovered', [
            'count' => count($discovered),
            'providers' => array_keys($discovered)
        ]);

        return $discovered;
    }

    /**
     * Clear the provider cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->providerCache = [];
        Log::debug('CloudStorageFactory: Provider cache cleared');
    }

    /**
     * Get class name from file path
     *
     * @param string $filePath
     * @return string|null
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        
        // Extract namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches)) {
            $namespace = $namespaceMatches[1];
        } else {
            return null;
        }

        // Extract class name
        if (preg_match('/class\s+(\w+)/', $content, $classMatches)) {
            $className = $classMatches[1];
            return $namespace . '\\' . $className;
        }

        return null;
    }

    /**
     * Extract provider name from class name
     *
     * @param string $className
     * @return string
     */
    private function extractProviderName(string $className): string
    {
        // Get just the class name without namespace
        $shortName = class_basename($className);
        
        // Remove 'Provider' suffix and convert to kebab-case
        $name = str_replace('Provider', '', $shortName);
        
        // Convert PascalCase to kebab-case
        $name = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $name));
        
        return $name;
    }

    /**
     * Get error handler for a specific provider
     *
     * @param string $providerName
     * @return \App\Contracts\CloudStorageErrorHandlerInterface
     * @throws CloudStorageException
     */
    public function getErrorHandler(string $providerName): \App\Contracts\CloudStorageErrorHandlerInterface
    {
        try {
            // Get provider configuration to find error handler class
            $config = config("cloud-storage.providers.{$providerName}");
            
            if (!$config || !isset($config['error_handler'])) {
                throw new CloudStorageException("No error handler configured for provider '{$providerName}'");
            }
            
            $errorHandlerClass = $config['error_handler'];
            
            if (!class_exists($errorHandlerClass)) {
                throw new CloudStorageException("Error handler class '{$errorHandlerClass}' does not exist");
            }
            
            $errorHandler = $this->container->make($errorHandlerClass);
            
            if (!$errorHandler instanceof \App\Contracts\CloudStorageErrorHandlerInterface) {
                throw new CloudStorageException("Error handler class '{$errorHandlerClass}' does not implement CloudStorageErrorHandlerInterface");
            }
            
            return $errorHandler;
            
        } catch (\Exception $e) {
            Log::error('CloudStorageFactory: Failed to create error handler', [
                'provider' => $providerName,
                'error' => $e->getMessage()
            ]);
            
            throw new CloudStorageException(
                "Failed to create error handler for provider '{$providerName}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}