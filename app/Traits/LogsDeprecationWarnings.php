<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Trait for logging deprecation warnings in deprecated services.
 */
trait LogsDeprecationWarnings
{
    /**
     * Log a deprecation warning for a method call.
     */
    protected function logDeprecationWarning(string $method, string $replacement, array $additionalContext = []): void
    {
        if (!config('app.debug')) {
            return;
        }

        $context = array_merge([
            'deprecated_method' => $method,
            'replacement' => $replacement,
            'migration_guide' => 'Use CloudStorageManager to get provider instances instead of direct GoogleDriveService usage',
            'example' => '$provider = app(CloudStorageManager::class)->getProvider(\'google-drive\');',
            'documentation' => 'See cloud storage provider documentation for complete migration guide',
            'called_at' => now()->toISOString()
        ], $additionalContext);

        Log::warning('Deprecated GoogleDriveService method called', $context);
    }
}