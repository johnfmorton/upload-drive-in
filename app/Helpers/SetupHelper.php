<?php

namespace App\Helpers;

use App\Services\SetupService;
use Illuminate\Support\Facades\App;

class SetupHelper
{
    private static ?SetupService $setupService = null;

    /**
     * Get the setup service instance
     */
    private static function getSetupService(): SetupService
    {
        if (self::$setupService === null) {
            self::$setupService = App::make(SetupService::class);
        }
        
        return self::$setupService;
    }

    /**
     * Check if setup is required
     */
    public static function isSetupRequired(): bool
    {
        try {
            return self::getSetupService()->isSetupRequired();
        } catch (\Exception $e) {
            // If there's an error checking setup status, assume setup is required
            return true;
        }
    }

    /**
     * Check if setup is complete
     */
    public static function isSetupComplete(): bool
    {
        try {
            return self::getSetupService()->isSetupComplete();
        } catch (\Exception $e) {
            // If there's an error, assume setup is not complete
            return false;
        }
    }

    /**
     * Get current setup step
     */
    public static function getCurrentSetupStep(): string
    {
        try {
            return self::getSetupService()->getSetupStep();
        } catch (\Exception $e) {
            // Default to welcome step if there's an error
            return 'welcome';
        }
    }

    /**
     * Get setup progress percentage
     */
    public static function getSetupProgress(): int
    {
        try {
            return self::getSetupService()->getSetupProgress();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check if a specific setup step is completed
     */
    public static function isStepCompleted(string $step): bool
    {
        try {
            $steps = self::getSetupService()->getSetupSteps();
            return $steps[$step]['completed'] ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all setup steps with status
     */
    public static function getSetupSteps(): array
    {
        try {
            return self::getSetupService()->getSetupSteps();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if current request should bypass setup requirements
     */
    public static function shouldBypassSetup(string $routeName = null, string $path = null): bool
    {
        // Allow setup routes
        if ($routeName && str_starts_with($routeName, 'setup.')) {
            return true;
        }
        
        if ($path && str_starts_with($path, '/setup')) {
            return true;
        }
        
        // Allow asset requests
        if ($path && (
            str_starts_with($path, '/build/') ||
            str_starts_with($path, '/css/') ||
            str_starts_with($path, '/js/') ||
            str_starts_with($path, '/images/') ||
            str_ends_with($path, '.css') ||
            str_ends_with($path, '.js') ||
            str_ends_with($path, '.png') ||
            str_ends_with($path, '.jpg') ||
            str_ends_with($path, '.jpeg') ||
            str_ends_with($path, '.gif') ||
            str_ends_with($path, '.svg') ||
            str_ends_with($path, '.ico')
        )) {
            return true;
        }
        
        // Allow health check endpoints
        if ($path && (
            $path === '/health' ||
            $path === '/up' ||
            str_starts_with($path, '/health/')
        )) {
            return true;
        }
        
        return false;
    }

    /**
     * Get the setup wizard URL for the current step
     */
    public static function getSetupUrl(string $step = null): string
    {
        $step = $step ?? self::getCurrentSetupStep();
        
        return match ($step) {
            'welcome' => route('setup.welcome'),
            'database' => route('setup.database'),
            'admin' => route('setup.admin'),
            'storage' => route('setup.storage'),
            'complete' => route('setup.complete'),
            default => route('setup.welcome'),
        };
    }

    /**
     * Get setup step display name
     */
    public static function getStepDisplayName(string $step): string
    {
        return match ($step) {
            'welcome' => 'Welcome',
            'database' => 'Database Configuration',
            'admin' => 'Admin User Creation',
            'storage' => 'Cloud Storage Setup',
            'complete' => 'Setup Complete',
            default => ucfirst($step),
        };
    }

    /**
     * Get setup step description
     */
    public static function getStepDescription(string $step): string
    {
        return match ($step) {
            'welcome' => 'Welcome to Upload Drive-in setup wizard',
            'database' => 'Configure your database connection and run migrations',
            'admin' => 'Create your administrator account',
            'storage' => 'Configure cloud storage providers',
            'complete' => 'Setup completed successfully',
            default => '',
        };
    }
}