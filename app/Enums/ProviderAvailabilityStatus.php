<?php

namespace App\Enums;

enum ProviderAvailabilityStatus: string
{
    case FULLY_AVAILABLE = 'fully_available';
    case COMING_SOON = 'coming_soon';
    case DEPRECATED = 'deprecated';
    case MAINTENANCE = 'maintenance';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::FULLY_AVAILABLE => 'Available',
            self::COMING_SOON => 'Coming Soon',
            self::DEPRECATED => 'Deprecated',
            self::MAINTENANCE => 'Under Maintenance',
        };
    }

    /**
     * Check if provider is selectable by users
     */
    public function isSelectable(): bool
    {
        return $this === self::FULLY_AVAILABLE;
    }

    /**
     * Check if provider should be shown in UI
     */
    public function isVisible(): bool
    {
        return $this !== self::DEPRECATED;
    }
}