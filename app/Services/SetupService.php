<?php

namespace App\Services;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class SetupService
{
    private const CACHE_KEY = 'setup_state';
    
    private string $stateFile;
    private array $steps;
    private array $checks;
    private bool $cacheEnabled;
    private int $cacheTtl;

    public function __construct()
    {
        $this->stateFile = Config::get('setup.state_file', 'setup/setup-state.json');
        
        $steps = Config::get('setup.steps', ['welcome', 'database', 'admin', 'storage', 'complete']);
        $this->steps = is_array($steps) ? $steps : ['welcome', 'database', 'admin', 'storage', 'complete'];
        
        $checks = Config::get('setup.checks', []);
        $this->checks = is_array($checks) ? $checks : [];
        
        $this->cacheEnabled = (bool) Config::get('setup.cache_state', true);
        $this->cacheTtl = (int) Config::get('setup.cache_ttl', 300);
    }

    /**
     * Check if the application requires initial setup
     */
    public function isSetupRequired(): bool
    {
        // Use cached result if available and caching is enabled
        if ($this->cacheEnabled) {
            $cached = Cache::get(self::CACHE_KEY . '_required');
            if ($cached !== null) {
                return $cached;
            }
        }

        $required = $this->performSetupChecks();

        // Cache the result if caching is enabled
        if ($this->cacheEnabled) {
            Cache::put(self::CACHE_KEY . '_required', $required, $this->cacheTtl);
        }

        return $required;
    }

    /**
     * Perform the actual setup requirement checks
     */
    private function performSetupChecks(): bool
    {
        // Check if setup is already marked as complete
        if ($this->isSetupComplete()) {
            return false;
        }

        // Perform individual checks based on configuration
        try {
            // Database connectivity check
            if ($this->checks['database_connectivity'] ?? true) {
                DB::connection()->getPdo();
            }
            
            // Migrations check
            if ($this->checks['migrations_run'] ?? true) {
                if (!Schema::hasTable('users')) {
                    return true;
                }
            }
            
            // Admin user check
            if ($this->checks['admin_user_exists'] ?? true) {
                $adminExists = User::where('role', UserRole::ADMIN)->exists();
                if (!$adminExists) {
                    return true;
                }
            }
            
            // Cloud storage check
            if ($this->checks['cloud_storage_configured'] ?? true) {
                if (!$this->isCloudStorageConfigured()) {
                    return true;
                }
            }
            
            // If all checks pass, mark setup as complete
            $this->markSetupComplete();
            return false;
            
        } catch (\Exception $e) {
            // Database connection failed or other issues
            return true;
        }
    }

    /**
     * Get the current setup step that needs to be completed
     */
    public function getSetupStep(): string
    {
        $state = $this->getSetupStateInternal();
        
        // If no state exists, start with welcome
        if (empty($state['current_step'])) {
            return 'welcome';
        }
        
        // Check each step to determine what's needed
        try {
            // Database step
            if (!$this->isDatabaseConfigured()) {
                return 'database';
            }
            
            // Admin user step
            if (!$this->isAdminUserCreated()) {
                return 'admin';
            }
            
            // Storage step
            if (!$this->isCloudStorageConfigured()) {
                return 'storage';
            }
            
            // All steps complete
            return 'complete';
            
        } catch (\Exception $e) {
            // If there's an error, start from database step
            return 'database';
        }
    }

    /**
     * Mark setup as complete
     */
    public function markSetupComplete(): void
    {
        $state = $this->getSetupStateInternal();
        $state['setup_complete'] = true;
        $state['completed_at'] = now()->toISOString();
        $state['current_step'] = 'complete';
        
        $this->saveSetupState($state);
        
        // Clear cache when setup state changes
        $this->clearSetupCache();
    }

    /**
     * Create the initial admin user
     */
    public function createInitialAdmin(array $data): User
    {
        $user = User::create([
            'name' => $data['name'] ?? 'Administrator',
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => UserRole::ADMIN,
        ]);
        
        // Set email as verified since this is the initial admin
        $user->email_verified_at = now();
        $user->save();
        
        // Update setup state
        $this->updateSetupStep('admin', true);
        
        return $user;
    }

    /**
     * Check if setup is marked as complete
     */
    public function isSetupComplete(): bool
    {
        $state = $this->getSetupStateInternal();
        return $state['setup_complete'] ?? false;
    }

    /**
     * Update a specific setup step status
     */
    public function updateSetupStep(string $step, bool $completed = true): void
    {
        if (!in_array($step, $this->steps)) {
            throw new \InvalidArgumentException("Invalid setup step: {$step}");
        }
        
        $state = $this->getSetupStateInternal();
        $state['steps'][$step] = [
            'completed' => $completed,
            'completed_at' => $completed ? now()->toISOString() : null,
        ];
        
        if ($completed) {
            $state['current_step'] = $this->getNextStep($step);
        }
        
        $this->saveSetupState($state);
        
        // Clear cache when setup state changes
        $this->clearSetupCache();
    }

    /**
     * Get the setup state from file (public method)
     */
    public function getSetupState(): array
    {
        return $this->getSetupStateInternal();
    }

    /**
     * Get the setup state from file (internal method)
     */
    private function getSetupStateInternal(): array
    {
        $filePath = storage_path('app/' . $this->stateFile);
        
        if (!File::exists($filePath)) {
            return $this->createInitialSetupState();
        }
        
        $content = File::get($filePath);
        return json_decode($content, true) ?? $this->createInitialSetupState();
    }

    /**
     * Save the setup state to file
     */
    private function saveSetupState(array $state): void
    {
        $filePath = storage_path('app/' . $this->stateFile);
        $directory = dirname($filePath);
        
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        
        File::put($filePath, json_encode($state, JSON_PRETTY_PRINT));
    }

    /**
     * Create initial setup state structure
     */
    private function createInitialSetupState(): array
    {
        $state = [
            'setup_complete' => false,
            'current_step' => 'welcome',
            'started_at' => now()->toISOString(),
            'steps' => [],
        ];
        
        foreach ($this->steps as $step) {
            $state['steps'][$step] = [
                'completed' => false,
                'completed_at' => null,
            ];
        }
        
        $this->saveSetupState($state);
        return $state;
    }

    /**
     * Get the next step in the setup process
     */
    private function getNextStep(string $currentStep): string
    {
        $currentIndex = array_search($currentStep, $this->steps);
        
        if ($currentIndex === false || $currentIndex >= count($this->steps) - 1) {
            return 'complete';
        }
        
        return $this->steps[$currentIndex + 1];
    }

    /**
     * Check if database is properly configured
     */
    public function isDatabaseConfigured(): bool
    {
        try {
            DB::connection()->getPdo();
            return Schema::hasTable('users');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if admin user has been created
     */
    public function isAdminUserCreated(): bool
    {
        try {
            return User::where('role', UserRole::ADMIN)->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if cloud storage is configured
     */
    public function isCloudStorageConfigured(): bool
    {
        // Check if Google Drive credentials are configured
        $clientId = Config::get('services.google.client_id');
        $clientSecret = Config::get('services.google.client_secret');
        
        return !empty($clientId) && !empty($clientSecret);
    }



    /**
     * Get all setup steps with their status
     */
    public function getSetupSteps(): array
    {
        $state = $this->getSetupStateInternal();
        return $state['steps'] ?? [];
    }

    /**
     * Get setup progress as percentage
     */
    public function getSetupProgress(): int
    {
        $steps = $this->getSetupSteps();
        $completedSteps = array_filter($steps, fn($step) => $step['completed'] ?? false);
        
        if (empty($steps)) {
            return 0;
        }
        
        return (int) round((count($completedSteps) / count($steps)) * 100);
    }

    /**
     * Clear setup-related cache
     */
    public function clearSetupCache(): void
    {
        if ($this->cacheEnabled) {
            Cache::forget(self::CACHE_KEY . '_required');
            Cache::forget(self::CACHE_KEY . '_step');
            Cache::forget(self::CACHE_KEY . '_progress');
        }
    }

    /**
     * Get setup configuration
     */
    public function getSetupConfig(): array
    {
        return [
            'steps' => $this->steps,
            'checks' => $this->checks,
            'cache_enabled' => $this->cacheEnabled,
            'cache_ttl' => $this->cacheTtl,
        ];
    }

    /**
     * Validate setup environment
     */
    public function validateSetupEnvironment(): array
    {
        $issues = [];

        try {
            // Check if storage directory is writable
            $storageDir = storage_path('app/setup');
            if (!File::exists($storageDir)) {
                File::makeDirectory($storageDir, 0755, true);
            }
            if (!is_writable($storageDir)) {
                $issues[] = 'Setup storage directory is not writable: ' . $storageDir;
            }

            // Check if cache is available if caching is enabled
            if ($this->cacheEnabled) {
                try {
                    Cache::put('setup_test', 'test', 1);
                    Cache::forget('setup_test');
                } catch (\Exception $e) {
                    $issues[] = 'Cache is not available but setup caching is enabled: ' . $e->getMessage();
                }
            }

            // Check database connectivity if database checks are enabled
            if ($this->checks['database_connectivity'] ?? true) {
                try {
                    DB::connection()->getPdo();
                } catch (\Exception $e) {
                    $issues[] = 'Database connection failed: ' . $e->getMessage();
                }
            }

        } catch (\Exception $e) {
            $issues[] = 'Setup environment validation failed: ' . $e->getMessage();
        }

        return $issues;
    }
}