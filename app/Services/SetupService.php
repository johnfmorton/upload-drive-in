<?php

namespace App\Services;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;

class SetupService
{
    private const SETUP_STATE_FILE = 'setup/setup-state.json';
    private const SETUP_STEPS = [
        'welcome',
        'database',
        'admin',
        'storage',
        'complete'
    ];

    /**
     * Check if the application requires initial setup
     */
    public function isSetupRequired(): bool
    {
        // Check if setup is already marked as complete
        if ($this->isSetupComplete()) {
            return false;
        }

        // Check database connectivity and admin user existence
        try {
            // Test database connection
            DB::connection()->getPdo();
            
            // Check if migrations have been run
            if (!Schema::hasTable('users')) {
                return true;
            }
            
            // Check if admin user exists
            $adminExists = User::where('role', UserRole::ADMIN)->exists();
            
            if (!$adminExists) {
                return true;
            }
            
            // Check if cloud storage is configured
            if (!$this->isCloudStorageConfigured()) {
                return true;
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
        $state = $this->getSetupState();
        
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
        $state = $this->getSetupState();
        $state['setup_complete'] = true;
        $state['completed_at'] = now()->toISOString();
        $state['current_step'] = 'complete';
        
        $this->saveSetupState($state);
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
        $state = $this->getSetupState();
        return $state['setup_complete'] ?? false;
    }

    /**
     * Update a specific setup step status
     */
    public function updateSetupStep(string $step, bool $completed = true): void
    {
        if (!in_array($step, self::SETUP_STEPS)) {
            throw new \InvalidArgumentException("Invalid setup step: {$step}");
        }
        
        $state = $this->getSetupState();
        $state['steps'][$step] = [
            'completed' => $completed,
            'completed_at' => $completed ? now()->toISOString() : null,
        ];
        
        if ($completed) {
            $state['current_step'] = $this->getNextStep($step);
        }
        
        $this->saveSetupState($state);
    }

    /**
     * Get the setup state from file
     */
    private function getSetupState(): array
    {
        $filePath = storage_path('app/' . self::SETUP_STATE_FILE);
        
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
        $filePath = storage_path('app/' . self::SETUP_STATE_FILE);
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
        
        foreach (self::SETUP_STEPS as $step) {
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
        $currentIndex = array_search($currentStep, self::SETUP_STEPS);
        
        if ($currentIndex === false || $currentIndex >= count(self::SETUP_STEPS) - 1) {
            return 'complete';
        }
        
        return self::SETUP_STEPS[$currentIndex + 1];
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
        $state = $this->getSetupState();
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
}