<?php

namespace App\Services;

use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use App\Exceptions\CloudStorageException;
use Illuminate\Support\Facades\Log;

/**
 * Central service for coordinating all cloud storage operations and provider management
 * 
 * This service acts as the main entry point for all cloud storage operations,
 * handling provider resolution, user preferences, and fallback mechanisms.
 */
class CloudStorageManager
{
    public function __construct(
        private CloudStorageFactory $factory,
        private CloudConfigurationService $configService
    ) {}

    /**
     * Get a cloud storage provider instance
     *
     * @param string|null $providerName Provider name (null for default)
     * @param User|null $user User for user-specific provider resolution
     * @return CloudStorageProviderInterface
     * @throws CloudStorageException
     */
    public function getProvider(string $providerName = null, User $user = null): CloudStorageProviderInterface
    {
        try {
            // If no provider specified, determine the appropriate one
            if ($providerName === null) {
                $providerName = $user ? $this->getUserPreferredProvider($user) : $this->getDefaultProviderName();
            }

            // Validate provider is configured
            if (!$this->configService->isProviderConfigured($providerName)) {
                throw new CloudStorageException("Provider '{$providerName}' is not configured");
            }

            // Create and return provider instance
            $provider = $this->factory->createForUser($user, $providerName);
            
            Log::debug('CloudStorageManager: Provider resolved', [
                'provider' => $providerName,
                'user_id' => $user?->id,
                'provider_class' => get_class($provider)
            ]);

            return $provider;

        } catch (\Exception $e) {
            Log::error('CloudStorageManager: Failed to get provider', [
                'provider' => $providerName,
                'user_id' => $user?->id,
                'error' => $e->getMessage()
            ]);

            // Try fallback if enabled and not already trying fallback
            if ($this->shouldTryFallback($providerName)) {
                return $this->getFallbackProvider($user);
            }

            throw new CloudStorageException(
                message: "Failed to get cloud storage provider: " . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Get the default cloud storage provider
     *
     * @return CloudStorageProviderInterface
     * @throws CloudStorageException
     */
    public function getDefaultProvider(): CloudStorageProviderInterface
    {
        $defaultProviderName = $this->getDefaultProviderName();
        return $this->getProvider($defaultProviderName);
    }

    /**
     * Get the user's preferred cloud storage provider
     *
     * @param User $user
     * @return CloudStorageProviderInterface
     * @throws CloudStorageException
     */
    public function getUserProvider(User $user): CloudStorageProviderInterface
    {
        $userProviderName = $this->getUserPreferredProvider($user);
        return $this->getProvider($userProviderName, $user);
    }

    /**
     * Get all registered provider instances
     *
     * @return array<string, CloudStorageProviderInterface>
     */
    public function getAllProviders(): array
    {
        $providers = [];
        $registeredProviders = $this->factory->getRegisteredProviders();

        foreach ($registeredProviders as $name => $className) {
            try {
                if ($this->configService->isProviderConfigured($name)) {
                    $providers[$name] = $this->factory->create($name);
                }
            } catch (\Exception $e) {
                Log::warning('CloudStorageManager: Failed to instantiate provider', [
                    'provider' => $name,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $providers;
    }

    /**
     * Get names of all available (configured) providers
     *
     * @return array<string>
     */
    public function getAvailableProviders(): array
    {
        $available = [];
        $registeredProviders = $this->factory->getRegisteredProviders();

        foreach (array_keys($registeredProviders) as $name) {
            if ($this->configService->isProviderConfigured($name)) {
                $available[] = $name;
            }
        }

        return $available;
    }

    /**
     * Validate all configured providers
     *
     * @return array Validation results keyed by provider name
     */
    public function validateAllProviders(): array
    {
        $results = [];
        $availableProviders = $this->getAvailableProviders();

        foreach ($availableProviders as $providerName) {
            try {
                $config = $this->configService->getProviderConfig($providerName);
                $provider = $this->factory->create($providerName);
                $validationErrors = $provider->validateConfiguration($config);
                
                $results[$providerName] = [
                    'valid' => empty($validationErrors),
                    'errors' => $validationErrors,
                    'capabilities' => $provider->getCapabilities()
                ];
            } catch (\Exception $e) {
                $results[$providerName] = [
                    'valid' => false,
                    'errors' => ['initialization' => $e->getMessage()],
                    'capabilities' => []
                ];
            }
        }

        return $results;
    }

    /**
     * Get capabilities for a specific provider
     *
     * @param string $providerName
     * @return array
     * @throws CloudStorageException
     */
    public function getProviderCapabilities(string $providerName): array
    {
        $provider = $this->getProvider($providerName);
        return $provider->getCapabilities();
    }

    /**
     * Switch a user's preferred provider
     *
     * @param User $user
     * @param string $providerName
     * @return void
     * @throws CloudStorageException
     */
    public function switchUserProvider(User $user, string $providerName): void
    {
        // Validate provider exists and is configured
        if (!$this->configService->isProviderConfigured($providerName)) {
            throw new CloudStorageException("Provider '{$providerName}' is not configured");
        }

        // Test provider can be instantiated
        $this->getProvider($providerName, $user);

        // Store user preference (this would typically be in a user settings table)
        // For now, we'll use a simple approach - this can be enhanced later
        $user->update(['preferred_cloud_provider' => $providerName]);

        Log::info('CloudStorageManager: User provider switched', [
            'user_id' => $user->id,
            'new_provider' => $providerName
        ]);
    }

    /**
     * Get the default provider name from configuration
     *
     * @return string
     */
    private function getDefaultProviderName(): string
    {
        return config('cloud-storage.default', 'google-drive');
    }

    /**
     * Get the user's preferred provider name
     *
     * @param User $user
     * @return string
     */
    private function getUserPreferredProvider(User $user): string
    {
        // Check if user has a preferred provider set
        if (isset($user->preferred_cloud_provider) && $user->preferred_cloud_provider) {
            return $user->preferred_cloud_provider;
        }

        // Fall back to default
        return $this->getDefaultProviderName();
    }

    /**
     * Check if fallback should be attempted
     *
     * @param string|null $currentProvider
     * @return bool
     */
    private function shouldTryFallback(string $currentProvider = null): bool
    {
        $fallbackConfig = config('cloud-storage.fallback', []);
        
        return ($fallbackConfig['enabled'] ?? false) && 
               $currentProvider !== null && 
               !in_array($currentProvider, $fallbackConfig['order'] ?? []);
    }

    /**
     * Get a fallback provider based on configuration
     *
     * @param User|null $user
     * @return CloudStorageProviderInterface
     * @throws CloudStorageException
     */
    private function getFallbackProvider(User $user = null): CloudStorageProviderInterface
    {
        $fallbackOrder = config('cloud-storage.fallback.order', ['google-drive']);
        
        foreach ($fallbackOrder as $providerName) {
            try {
                if ($this->configService->isProviderConfigured($providerName)) {
                    Log::info('CloudStorageManager: Using fallback provider', [
                        'provider' => $providerName,
                        'user_id' => $user?->id
                    ]);
                    
                    return $this->factory->createForUser($user, $providerName);
                }
            } catch (\Exception $e) {
                Log::warning('CloudStorageManager: Fallback provider failed', [
                    'provider' => $providerName,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        throw new CloudStorageException('No fallback providers available');
    }
}