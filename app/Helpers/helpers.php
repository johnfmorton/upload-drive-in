<?php

use App\Helpers\SetupHelper;

if (! function_exists('format_bytes')) {
    /**
     * Format bytes to kb, mb, gb, tb
     *
     * @param  int $size The size in bytes
     * @param  int $precision The number of decimal places
     * @return string
     */
    function format_bytes(int $size, int $precision = 2): string
    {
        if ($size <= 0) {
            return '0 B';
        }

        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];

        $floorBase = floor($base);
        // Ensure the suffix index doesn't exceed the array bounds
        $suffixIndex = min($floorBase, count($suffixes) - 1);

        return round(pow(1024, $base - $floorBase), $precision) .' ' . $suffixes[$suffixIndex];
    }
}

if (! function_exists('is_setup_required')) {
    /**
     * Check if application setup is required
     */
    function is_setup_required(): bool
    {
        return SetupHelper::isSetupRequired();
    }
}

if (! function_exists('is_setup_complete')) {
    /**
     * Check if application setup is complete
     */
    function is_setup_complete(): bool
    {
        return SetupHelper::isSetupComplete();
    }
}

if (! function_exists('get_setup_step')) {
    /**
     * Get current setup step
     */
    function get_setup_step(): string
    {
        return SetupHelper::getCurrentSetupStep();
    }
}

if (! function_exists('should_bypass_setup')) {
    /**
     * Check if current request should bypass setup requirements
     */
    function should_bypass_setup(string $routeName = null, string $path = null): bool
    {
        return SetupHelper::shouldBypassSetup($routeName, $path);
    }
}
