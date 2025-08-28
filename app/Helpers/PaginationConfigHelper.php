<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class PaginationConfigHelper
{
    /**
     * Validate and normalize pagination configuration values.
     *
     * @param int|string|null $value The value to validate
     * @param int $default The default value to use if validation fails
     * @param int $min The minimum allowed value
     * @param int $max The maximum allowed value
     * @return int The validated and normalized value
     */
    public static function validatePaginationValue($value, int $default, int $min, int $max): int
    {
        // Convert to integer if possible
        if (is_string($value) && is_numeric($value)) {
            $value = (int) $value;
        }

        // If not an integer or invalid, use default
        if (!is_int($value) || $value < 1) {
            return $default;
        }

        // Apply min/max constraints
        return max($min, min($max, $value));
    }

    /**
     * Log the pagination configuration on application startup.
     *
     * @return void
     */
    public static function logPaginationConfiguration(): void
    {
        $envValue = env('FILE_MANAGER_ITEMS_PER_PAGE');
        $configValue = config('file-manager.pagination.items_per_page');
        $minValue = config('file-manager.pagination.min_items_per_page');
        $maxValue = config('file-manager.pagination.max_items_per_page');

        $source = $envValue !== null ? 'environment' : 'default';
        
        // Determine if the environment value was modified due to validation
        $wasModified = false;
        if ($envValue !== null) {
            $originalValue = is_numeric($envValue) ? (int) $envValue : null;
            if ($originalValue !== null && $originalValue !== $configValue) {
                $wasModified = true;
            }
        }

        $logData = [
            'items_per_page' => $configValue,
            'source' => $source,
            'min_items_per_page' => $minValue,
            'max_items_per_page' => $maxValue,
        ];

        if ($envValue !== null) {
            $logData['env_value'] = $envValue;
            if ($wasModified) {
                $logData['validation_applied'] = true;
                $logData['original_env_value'] = $originalValue;
            }
        }

        Log::info('File manager pagination configuration loaded', $logData);

        // Log warnings for invalid configurations
        if ($wasModified) {
            Log::warning('File manager pagination environment value was adjusted due to validation constraints', [
                'original_value' => $originalValue,
                'adjusted_value' => $configValue,
                'min_allowed' => $minValue,
                'max_allowed' => $maxValue,
            ]);
        }
    }

    /**
     * Get the validated pagination configuration.
     *
     * @return array
     */
    public static function getPaginationConfig(): array
    {
        return [
            'items_per_page' => config('file-manager.pagination.items_per_page'),
            'min_items_per_page' => config('file-manager.pagination.min_items_per_page'),
            'max_items_per_page' => config('file-manager.pagination.max_items_per_page'),
        ];
    }
}