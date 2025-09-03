<?php

namespace App\Services;

use App\Enums\ProviderAvailabilityStatus;
use Illuminate\Support\Collection;

/**
 * Service to manage cloud storage provider availability status
 * 
 * Determines which providers are fully functional vs "coming soon"
 * and provides configuration for UI display and selection logic.
 */
class CloudStorageProviderAvailabilityService
{
    /**
     * Initialize the service with configuration
     */
    public function __construct()
    {
        $this->loadProviderStatusFromConfig();
    }

    /**
     * Load provider status from configuration
     * 
     * @return void
     */
    private function loadProviderStatusFromConfig(): void
    {
        $configuredStatuses = config('cloud-storage.provider_availability', []);
        $this->providerStatus = [];

        foreach ($configuredStatuses as $provider => $statusString) {
            $this->providerStatus[$provider] = ProviderAvailabilityStatus::from($statusString);
        }

        // Add fallback for any missing providers
        $this->addFallbackProviders();
    }

    /**
     * Add fallback providers that might not be in config
     * 
     * @return void
     */
    private function addFallbackProviders(): void
    {
        $fallbackProviders = [
            's3' => ProviderAvailabilityStatus::COMING_SOON,
            'onedrive' => ProviderAvailabilityStatus::COMING_SOON,
        ];

        foreach ($fallbackProviders as $provider => $status) {
            if (!isset($this->providerStatus[$provider])) {
                $this->providerStatus[$provider] = $status;
            }
        }
    }
    /**
     * Provider availability configuration
     * 
     * @var array<string, ProviderAvailabilityStatus>
     */
    private array $providerStatus;

    /**
     * Get all available (fully functional) providers
     * 
     * @return array<string>
     */
    public function getAvailableProviders(): array
    {
        return collect($this->providerStatus)
            ->filter(fn(ProviderAvailabilityStatus $status) => $status === ProviderAvailabilityStatus::FULLY_AVAILABLE)
            ->keys()
            ->toArray();
    }

    /**
     * Get providers marked as "coming soon"
     * 
     * @return array<string>
     */
    public function getComingSoonProviders(): array
    {
        return collect($this->providerStatus)
            ->filter(fn(ProviderAvailabilityStatus $status) => $status === ProviderAvailabilityStatus::COMING_SOON)
            ->keys()
            ->toArray();
    }

    /**
     * Check if a provider is fully functional
     * 
     * @param string $provider
     * @return bool
     */
    public function isProviderFullyFunctional(string $provider): bool
    {
        $status = $this->providerStatus[$provider] ?? null;
        return $status === ProviderAvailabilityStatus::FULLY_AVAILABLE;
    }

    /**
     * Get the availability status for a specific provider
     * 
     * @param string $provider
     * @return string
     */
    public function getProviderAvailabilityStatus(string $provider): string
    {
        $status = $this->providerStatus[$provider] ?? ProviderAvailabilityStatus::DEPRECATED;
        return $status->value;
    }

    /**
     * Get the availability status enum for a specific provider
     * 
     * @param string $provider
     * @return ProviderAvailabilityStatus
     */
    public function getProviderAvailabilityStatusEnum(string $provider): ProviderAvailabilityStatus
    {
        return $this->providerStatus[$provider] ?? ProviderAvailabilityStatus::DEPRECATED;
    }

    /**
     * Get all providers with their availability status
     * 
     * @return Collection<string, array{status: ProviderAvailabilityStatus, label: string, selectable: bool, visible: bool}>
     */
    public function getAllProvidersWithStatus(): Collection
    {
        return collect($this->providerStatus)->map(function (ProviderAvailabilityStatus $status, string $provider) {
            return [
                'status' => $status,
                'label' => $this->getProviderDisplayName($provider),
                'selectable' => $status->isSelectable(),
                'visible' => $status->isVisible(),
                'status_label' => $status->label(),
            ];
        });
    }

    /**
     * Get providers that should be shown in UI (excludes deprecated)
     * 
     * @return Collection<string, array{status: ProviderAvailabilityStatus, label: string, selectable: bool}>
     */
    public function getVisibleProviders(): Collection
    {
        return $this->getAllProvidersWithStatus()
            ->filter(fn(array $data) => $data['visible']);
    }

    /**
     * Get providers that can be selected by users
     * 
     * @return Collection<string, array{status: ProviderAvailabilityStatus, label: string}>
     */
    public function getSelectableProviders(): Collection
    {
        return $this->getAllProvidersWithStatus()
            ->filter(fn(array $data) => $data['selectable']);
    }

    /**
     * Get the default provider (first available provider)
     * 
     * @return string|null
     */
    public function getDefaultProvider(): ?string
    {
        $availableProviders = $this->getAvailableProviders();
        return $availableProviders[0] ?? null;
    }

    /**
     * Check if a provider selection is valid
     * 
     * @param string $provider
     * @return bool
     */
    public function isValidProviderSelection(string $provider): bool
    {
        return $this->isProviderFullyFunctional($provider);
    }

    /**
     * Get human-readable display name for a provider
     * 
     * @param string $provider
     * @return string
     */
    private function getProviderDisplayName(string $provider): string
    {
        return match ($provider) {
            'google-drive' => 'Google Drive',
            's3' => 'Amazon S3',
            'onedrive' => 'Microsoft OneDrive',
            'dropbox' => 'Dropbox',
            default => ucfirst(str_replace('-', ' ', $provider)),
        };
    }

    /**
     * Get provider configuration for frontend use
     * 
     * @return array<string, array{label: string, status: string, selectable: bool, default: bool}>
     */
    public function getProviderConfigurationForFrontend(): array
    {
        $defaultProvider = $this->getDefaultProvider();
        
        return $this->getVisibleProviders()
            ->map(function (array $data, string $provider) use ($defaultProvider) {
                return [
                    'label' => $data['label'],
                    'status' => $data['status']->value,
                    'status_label' => $data['status_label'],
                    'selectable' => $data['selectable'],
                    'default' => $provider === $defaultProvider,
                ];
            })
            ->toArray();
    }

    /**
     * Update provider availability status (for future admin configuration)
     * 
     * @param string $provider
     * @param ProviderAvailabilityStatus $status
     * @return void
     */
    public function updateProviderStatus(string $provider, ProviderAvailabilityStatus $status): void
    {
        $this->providerStatus[$provider] = $status;
    }
}