<?php

namespace App\Services;

use App\Contracts\CloudStorageProviderInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Service for detecting and managing cloud storage provider capabilities and features
 * 
 * This service provides methods to check provider capabilities, detect available features,
 * and implement graceful degradation when features are not supported.
 */
class CloudStorageFeatureDetectionService
{
    public function __construct(
        private CloudStorageManager $storageManager
    ) {}

    /**
     * Get all capabilities for a specific provider
     *
     * @param string $providerName The provider name
     * @return array Array of capabilities with their support status
     */
    public function getProviderCapabilities(string $providerName): array
    {
        try {
            $provider = $this->storageManager->getProvider($providerName);
            return $provider->getCapabilities();
        } catch (\Exception $e) {
            Log::warning('Failed to get provider capabilities', [
                'provider' => $providerName,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get capabilities for a user's preferred provider
     *
     * @param User $user The user
     * @return array Array of capabilities with their support status
     */
    public function getUserProviderCapabilities(User $user): array
    {
        try {
            $provider = $this->storageManager->getUserProvider($user);
            return $provider->getCapabilities();
        } catch (\Exception $e) {
            Log::warning('Failed to get user provider capabilities', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Check if a specific feature is supported by a provider
     *
     * @param string $providerName The provider name
     * @param string $feature The feature to check
     * @return bool True if the feature is supported
     */
    public function isFeatureSupported(string $providerName, string $feature): bool
    {
        try {
            $provider = $this->storageManager->getProvider($providerName);
            return $provider->supportsFeature($feature);
        } catch (\Exception $e) {
            Log::warning('Failed to check feature support', [
                'provider' => $providerName,
                'feature' => $feature,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if a feature is supported by the user's preferred provider
     *
     * @param User $user The user
     * @param string $feature The feature to check
     * @return bool True if the feature is supported
     */
    public function isFeatureSupportedForUser(User $user, string $feature): bool
    {
        try {
            $provider = $this->storageManager->getUserProvider($user);
            return $provider->supportsFeature($feature);
        } catch (\Exception $e) {
            Log::warning('Failed to check user feature support', [
                'user_id' => $user->id,
                'feature' => $feature,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get all providers that support a specific feature
     *
     * @param string $feature The feature to check
     * @return array Array of provider names that support the feature
     */
    public function getProvidersWithFeature(string $feature): array
    {
        $supportingProviders = [];
        $availableProviders = $this->storageManager->getAvailableProviders();

        foreach ($availableProviders as $providerName) {
            if ($this->isFeatureSupported($providerName, $feature)) {
                $supportingProviders[] = $providerName;
            }
        }

        return $supportingProviders;
    }

    /**
     * Get a feature compatibility matrix for all available providers
     *
     * @param array $features Optional array of specific features to check
     * @return array Matrix of providers and their feature support
     */
    public function getFeatureCompatibilityMatrix(array $features = null): array
    {
        $matrix = [];
        $availableProviders = $this->storageManager->getAvailableProviders();

        // If no specific features provided, get all unique features from all providers
        if ($features === null) {
            $features = $this->getAllAvailableFeatures();
        }

        foreach ($availableProviders as $providerName) {
            $matrix[$providerName] = [];
            foreach ($features as $feature) {
                $matrix[$providerName][$feature] = $this->isFeatureSupported($providerName, $feature);
            }
        }

        return $matrix;
    }

    /**
     * Get all unique features available across all providers
     *
     * @return array Array of all available feature names
     */
    public function getAllAvailableFeatures(): array
    {
        $allFeatures = [];
        $availableProviders = $this->storageManager->getAvailableProviders();

        foreach ($availableProviders as $providerName) {
            $capabilities = $this->getProviderCapabilities($providerName);
            $allFeatures = array_merge($allFeatures, array_keys($capabilities));
        }

        return array_unique($allFeatures);
    }

    /**
     * Find the best provider for a set of required features
     *
     * @param array $requiredFeatures Array of required feature names
     * @param array $preferredFeatures Array of preferred (but not required) feature names
     * @param User|null $user Optional user to consider their current provider
     * @return array Result with 'provider' name and 'score' indicating feature match
     */
    public function findBestProviderForFeatures(
        array $requiredFeatures, 
        array $preferredFeatures = [], 
        User $user = null
    ): array {
        $availableProviders = $this->storageManager->getAvailableProviders();
        $scores = [];

        foreach ($availableProviders as $providerName) {
            $score = 0;
            $supportsAllRequired = true;

            // Check required features (must all be supported)
            foreach ($requiredFeatures as $feature) {
                if ($this->isFeatureSupported($providerName, $feature)) {
                    $score += 10; // High weight for required features
                } else {
                    $supportsAllRequired = false;
                    break;
                }
            }

            // Skip providers that don't support all required features
            if (!$supportsAllRequired) {
                continue;
            }

            // Check preferred features (bonus points)
            foreach ($preferredFeatures as $feature) {
                if ($this->isFeatureSupported($providerName, $feature)) {
                    $score += 1; // Lower weight for preferred features
                }
            }

            // Bonus for user's current provider (to avoid unnecessary switching)
            if ($user && $this->storageManager->getUserProvider($user)->getProviderName() === $providerName) {
                $score += 5;
            }

            $scores[$providerName] = $score;
        }

        if (empty($scores)) {
            return [
                'provider' => null,
                'score' => 0,
                'message' => 'No provider supports all required features'
            ];
        }

        // Find the provider with the highest score
        $bestProvider = array_keys($scores, max($scores))[0];

        return [
            'provider' => $bestProvider,
            'score' => $scores[$bestProvider],
            'all_scores' => $scores
        ];
    }

    /**
     * Get feature alternatives when a feature is not supported
     *
     * @param string $feature The unsupported feature
     * @param string $providerName The provider name
     * @return array Array of alternative approaches or workarounds
     */
    public function getFeatureAlternatives(string $feature, string $providerName): array
    {
        $alternatives = [];

        switch ($feature) {
            case 'folder_creation':
                if ($providerName === 'amazon-s3') {
                    $alternatives[] = [
                        'type' => 'workaround',
                        'description' => 'Use key prefixes to simulate folder structure',
                        'implementation' => 'Add folder path as prefix to file keys'
                    ];
                }
                break;

            case 'hierarchical_storage':
                $alternatives[] = [
                    'type' => 'workaround',
                    'description' => 'Simulate hierarchy using naming conventions',
                    'implementation' => 'Use path separators in file names'
                ];
                break;

            case 'oauth_authentication':
                $alternatives[] = [
                    'type' => 'alternative',
                    'description' => 'Use API key authentication instead',
                    'implementation' => 'Configure provider with API credentials'
                ];
                break;

            case 'presigned_urls':
                $alternatives[] = [
                    'type' => 'fallback',
                    'description' => 'Use direct file serving through application',
                    'implementation' => 'Stream files through application server'
                ];
                break;

            case 'version_history':
                $alternatives[] = [
                    'type' => 'workaround',
                    'description' => 'Implement application-level versioning',
                    'implementation' => 'Store multiple versions with timestamp suffixes'
                ];
                break;

            case 'search':
                $alternatives[] = [
                    'type' => 'workaround',
                    'description' => 'Use local database search with metadata',
                    'implementation' => 'Index file metadata in local database'
                ];
                break;

            default:
                $alternatives[] = [
                    'type' => 'fallback',
                    'description' => 'Feature not available for this provider',
                    'implementation' => 'Consider switching to a provider that supports this feature'
                ];
                break;
        }

        return $alternatives;
    }

    /**
     * Check if graceful degradation is possible for a feature
     *
     * @param string $feature The feature to check
     * @param string $providerName The provider name
     * @return bool True if graceful degradation is possible
     */
    public function canGracefullyDegrade(string $feature, string $providerName): bool
    {
        $alternatives = $this->getFeatureAlternatives($feature, $providerName);
        
        // Check if any alternatives are workarounds or fallbacks (not just "not available")
        foreach ($alternatives as $alternative) {
            if (in_array($alternative['type'], ['workaround', 'fallback', 'alternative'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get provider-specific feature utilization recommendations
     *
     * @param string $providerName The provider name
     * @return array Array of recommendations for optimal feature usage
     */
    public function getProviderOptimizationRecommendations(string $providerName): array
    {
        $recommendations = [];
        $capabilities = $this->getProviderCapabilities($providerName);

        switch ($providerName) {
            case 'google-drive':
                if ($capabilities['folder_creation'] ?? false) {
                    $recommendations[] = [
                        'feature' => 'folder_creation',
                        'recommendation' => 'Organize files in folders for better user experience',
                        'benefit' => 'Improved file organization and navigation'
                    ];
                }
                if ($capabilities['file_sharing'] ?? false) {
                    $recommendations[] = [
                        'feature' => 'file_sharing',
                        'recommendation' => 'Use Google Drive sharing for collaboration',
                        'benefit' => 'Native collaboration features'
                    ];
                }
                break;

            case 'amazon-s3':
                if ($capabilities['storage_classes'] ?? false) {
                    $recommendations[] = [
                        'feature' => 'storage_classes',
                        'recommendation' => 'Use appropriate storage classes for cost optimization',
                        'benefit' => 'Reduced storage costs for infrequently accessed files'
                    ];
                }
                if ($capabilities['presigned_urls'] ?? false) {
                    $recommendations[] = [
                        'feature' => 'presigned_urls',
                        'recommendation' => 'Use presigned URLs for direct client uploads',
                        'benefit' => 'Reduced server load and faster uploads'
                    ];
                }
                if ($capabilities['multipart_upload'] ?? false) {
                    $recommendations[] = [
                        'feature' => 'multipart_upload',
                        'recommendation' => 'Use multipart upload for large files',
                        'benefit' => 'Better reliability and performance for large files'
                    ];
                }
                break;

            default:
                $recommendations[] = [
                    'feature' => 'general',
                    'recommendation' => 'Review provider capabilities for optimization opportunities',
                    'benefit' => 'Better utilization of provider-specific features'
                ];
                break;
        }

        return $recommendations;
    }
}