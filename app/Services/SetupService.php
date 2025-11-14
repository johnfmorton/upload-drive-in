<?php

namespace App\Services;

use App\Models\User;
use App\Enums\UserRole;
use App\Services\AssetValidationService;
use App\Services\SetupSecurityService;
use App\Services\EnvironmentFileService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class SetupService
{
    private const CACHE_KEY = 'setup_state';
    private const STATE_VERSION = '1.0';
    private const MAX_BACKUP_FILES = 5;
    
    private string $stateFile;
    private string $backupDirectory;
    private array $steps;
    private array $checks;
    private bool $cacheEnabled;
    private int $cacheTtl;
    private AssetValidationService $assetValidationService;
    private SetupSecurityService $securityService;
    private EnvironmentFileService $environmentFileService;

    public function __construct(
        AssetValidationService $assetValidationService,
        SetupSecurityService $securityService,
        EnvironmentFileService $environmentFileService
    ) {
        $this->assetValidationService = $assetValidationService;
        $this->securityService = $securityService;
        $this->environmentFileService = $environmentFileService;
        $this->stateFile = Config::get('setup.state_file', 'setup/setup-state.json');
        $this->backupDirectory = Config::get('setup.backup_directory', 'setup/backups');
        
        $steps = Config::get('setup.steps', ['assets', 'welcome', 'database', 'admin', 'storage', 'complete']);
        $this->steps = is_array($steps) ? $steps : ['assets', 'welcome', 'database', 'admin', 'storage', 'complete'];
        
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
        \Log::info('Starting setup checks');
        
        // Don't check if setup is already complete - we want fresh checks
        
        // Perform individual checks based on configuration
        try {
            // Asset validation check (first priority)
            if ($this->checks['asset_validation'] ?? true) {
                \Log::info('Checking assets...');
                if (!$this->areAssetsValid()) {
                    \Log::info('Setup required: Assets not valid');
                    return true;
                }
                \Log::info('Assets check passed');
            }
            
            // Database configuration check (before attempting connection)
            if ($this->checks['database_connectivity'] ?? true) {
                \Log::info('Checking database configuration...');
                if (!$this->isDatabaseConfigurationComplete()) {
                    \Log::info('Setup required: Database configuration incomplete');
                    return true;
                }
                \Log::info('Database configuration check passed');
                
                \Log::info('Checking database connectivity...');
                DB::connection()->getPdo();
                \Log::info('Database connectivity check passed');
            }
            
            // Migrations check
            if ($this->checks['migrations_run'] ?? true) {
                \Log::info('Checking migrations...');
                if (!Schema::hasTable('users')) {
                    \Log::info('Setup required: Users table does not exist');
                    return true;
                }
                \Log::info('Migrations check passed');
            }
            
            // Admin user check
            if ($this->checks['admin_user_exists'] ?? true) {
                \Log::info('Checking admin users...');
                $adminExists = User::where('role', UserRole::ADMIN)->exists();
                if (!$adminExists) {
                    \Log::info('Setup required: No admin users found');
                    return true;
                }
                \Log::info('Admin user check passed');
            }
            
            // Cloud storage check
            if ($this->checks['cloud_storage_configured'] ?? true) {
                \Log::info('Checking cloud storage...');
                if (!$this->isCloudStorageConfigured()) {
                    \Log::info('Setup required: Cloud storage not configured');
                    return true;
                }
                \Log::info('Cloud storage check passed');
            }
            
            // If all checks pass, mark setup as complete
            \Log::info('All setup checks passed - marking setup as complete');
            $this->markSetupComplete();
            return false;
            
        } catch (\Exception $e) {
            // Database connection failed or other issues
            \Log::info('Setup required: Exception occurred - ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Get the current setup step that needs to be completed
     */
    public function getSetupStep(): string
    {
        $state = $this->getSetupStateInternal();
        
        // If no state exists, start with assets
        if (empty($state['current_step'])) {
            return 'assets';
        }
        
        // Check each step to determine what's needed
        try {
            // Asset validation step (first priority)
            if (!$this->areAssetsValid()) {
                return 'assets';
            }
            
            // Database step
            if (!$this->isDatabaseConfigurationComplete() || !$this->isDatabaseConfigured()) {
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
            // If there's an error, start from assets step
            return 'assets';
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
     * Reset setup state when inconsistencies are detected
     */
    private function resetSetupState(): void
    {
        try {
            // Remove the setup state file
            $stateFile = storage_path('app/' . $this->stateFile);
            if (File::exists($stateFile)) {
                File::delete($stateFile);
            }
            
            // Clear any cached state
            $this->clearSetupCache();
            
            \Log::info('Setup state reset due to inconsistency');
        } catch (\Exception $e) {
            \Log::error('Failed to reset setup state: ' . $e->getMessage());
        }
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
        
        // Create backup before modifying state if file exists
        $stateFile = storage_path('app/' . $this->stateFile);
        if (File::exists($stateFile) && File::size($stateFile) > 0) {
            try {
                $this->createStateBackup();
            } catch (\Exception $e) {
                // Log backup failure but continue with update
                \Log::warning('Failed to create setup state backup: ' . $e->getMessage());
            }
        }
        
        $state = $this->getSetupStateInternal();
        $state['steps'][$step] = [
            'completed' => $completed,
            'completed_at' => $completed ? now()->toISOString() : null,
        ];
        
        if ($completed) {
            $state['current_step'] = $this->getNextStep($step);
            
            // Update asset checks if this is the assets step
            if ($step === 'assets') {
                $this->updateAssetChecks($state);
            }
        }
        
        $this->saveSetupState($state);
        
        // Clear cache when setup state changes
        $this->clearSetupCache();
    }

    /**
     * Update asset checks in the setup state
     */
    public function updateAssetChecks(array &$state = null): void
    {
        if ($state === null) {
            $state = $this->getSetupStateInternal();
        }
        
        $assetResults = $this->getAssetValidationResults();
        $nodeEnv = $assetResults['node_environment'] ?? [];
        
        $state['asset_checks'] = [
            'vite_manifest_exists' => $assetResults['vite_manifest_exists'] ?? false,
            'build_directory_exists' => $assetResults['build_directory_exists'] ?? false,
            'node_environment_ready' => ($nodeEnv['package_json_exists'] ?? false) && 
                                       ($nodeEnv['node_modules_exists'] ?? false),
            'build_instructions_shown' => $state['asset_checks']['build_instructions_shown'] ?? false,
        ];
        
        if ($state !== null) {
            $this->saveSetupState($state);
            $this->clearSetupCache();
        }
    }

    /**
     * Mark asset build instructions as shown
     */
    public function markAssetInstructionsShown(): void
    {
        $state = $this->getSetupStateInternal();
        $state['asset_checks']['build_instructions_shown'] = true;
        $this->saveSetupState($state);
        $this->clearSetupCache();
    }

    /**
     * Get the setup state from file (public method)
     */
    public function getSetupState(): array
    {
        try {
            return $this->getSetupStateInternal();
        } catch (\RuntimeException $e) {
            // If state is corrupted, try to recover or recreate
            $this->recreateSetupState();
            return $this->getSetupStateInternal();
        }
    }

    /**
     * Get the setup state from file (internal method) with security validation
     */
    private function getSetupStateInternal(): array
    {
        // Use secure file read
        $result = $this->securityService->secureFileRead($this->stateFile);
        
        if (!$result['success']) {
            if (str_contains($result['message'], 'does not exist')) {
                return $this->createInitialSetupState();
            }
            throw new \RuntimeException('Failed to read setup state: ' . $result['message']);
        }
        
        $decoded = json_decode($result['content'], true);
        
        if ($decoded === null) {
            throw new \RuntimeException('Setup state file contains invalid JSON');
        }
        
        // Validate state integrity if hash is present
        if (isset($decoded['integrity_hash'])) {
            $expectedHash = $this->generateStateIntegrityHash($decoded);
            if ($decoded['integrity_hash'] !== $expectedHash) {
                $this->securityService->logSecurityEvent('setup_state_integrity_violation', [
                    'expected_hash' => $expectedHash,
                    'actual_hash' => $decoded['integrity_hash']
                ]);
                
                throw new \RuntimeException('Setup state integrity check failed');
            }
        }
        
        return $decoded;
    }

    /**
     * Save the setup state to file with security validation
     */
    private function saveSetupState(array $state): void
    {
        // Add metadata to state
        $state['last_updated_at'] = now()->toISOString();
        $state['version'] = self::STATE_VERSION;
        
        // Add integrity hash
        $state['integrity_hash'] = $this->generateStateIntegrityHash($state);
        
        $content = json_encode($state, JSON_PRETTY_PRINT);
        
        // Use secure file write
        $result = $this->securityService->secureFileWrite($this->stateFile, $content, 0644);
        
        if (!$result['success']) {
            throw new \RuntimeException('Failed to save setup state: ' . $result['message']);
        }

        // Log state change for audit
        $this->securityService->logSecurityEvent('setup_state_updated', [
            'current_step' => $state['current_step'] ?? 'unknown',
            'setup_complete' => $state['setup_complete'] ?? false
        ]);
    }

    /**
     * Create initial setup state structure
     */
    private function createInitialSetupState(): array
    {
        $state = [
            'version' => self::STATE_VERSION,
            'setup_complete' => false,
            'current_step' => 'assets',
            'started_at' => now()->toISOString(),
            'steps' => [],
            'asset_checks' => [
                'vite_manifest_exists' => false,
                'build_directory_exists' => false,
                'node_environment_ready' => false,
                'build_instructions_shown' => false,
            ],
            'recovery_info' => [
                'backup_created' => false,
                'interruption_detected' => false,
                'last_recovery_at' => null,
            ],
        ];
        
        foreach ($this->steps as $step) {
            $state['steps'][$step] = [
                'completed' => false,
                'completed_at' => null,
                'started_at' => null,
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
     * Check if assets are valid and built
     */
    public function areAssetsValid(): bool
    {
        return $this->assetValidationService->areAssetRequirementsMet();
    }

    /**
     * Get asset validation results
     */
    public function getAssetValidationResults(): array
    {
        return $this->assetValidationService->getAssetValidationResults();
    }

    /**
     * Get asset build instructions
     */
    public function getAssetBuildInstructions(): array
    {
        return $this->assetValidationService->getBuildInstructions();
    }

    /**
     * Get missing asset requirements
     */
    public function getMissingAssetRequirements(): array
    {
        return $this->assetValidationService->getMissingAssetRequirements();
    }

    /**
     * Get asset build status for real-time checking
     */
    public function getAssetBuildStatus(): array
    {
        return $this->assetValidationService->getAssetBuildStatus();
    }

    /**
     * Check if database configuration is complete (has all required credentials)
     */
    public function isDatabaseConfigurationComplete(): bool
    {
        $connection = Config::get('database.default');
        
        // For SQLite, just check if the connection is set
        if ($connection === 'sqlite') {
            return true; // SQLite doesn't need credentials
        }
        
        // For MySQL/MariaDB/PostgreSQL, check required credentials
        if (in_array($connection, ['mysql', 'mariadb', 'pgsql'])) {
            // Check if we're in a development environment that auto-provides DB credentials
            $isDevelopmentEnvironment = $this->isDevelopmentEnvironmentWithAutoDb();
            
            if ($isDevelopmentEnvironment) {
                // In development environments like DDEV, check if .env file has proper config
                return $this->isEnvFileDbConfigComplete();
            }
            
            // In production/normal environments, check actual environment variables
            $host = env('DB_HOST');
            $database = env('DB_DATABASE');
            $username = env('DB_USERNAME');
            
            // If any required credential is missing/empty, configuration is incomplete
            if (empty($host) || empty($database) || empty($username)) {
                \Log::info('Database configuration incomplete', [
                    'connection' => $connection,
                    'has_host' => !empty($host),
                    'has_database' => !empty($database),
                    'has_username' => !empty($username)
                ]);
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check if we're in a development environment that auto-provides database credentials
     */
    private function isDevelopmentEnvironmentWithAutoDb(): bool
    {
        // Check for DDEV environment
        if (env('DDEV_PROJECT') || env('IS_DDEV_PROJECT')) {
            return true;
        }
        
        // Check for other common development environments
        if (env('LARAVEL_SAIL') || env('APP_ENV') === 'local') {
            // Additional checks to see if DB credentials are auto-provided
            $host = env('DB_HOST');
            $database = env('DB_DATABASE');
            $username = env('DB_USERNAME');
            
            // If all credentials are simple/default values, likely auto-provided
            if ($host === 'db' && $database === 'db' && $username === 'db') {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if .env file has complete database configuration
     */
    private function isEnvFileDbConfigComplete(): bool
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            \Log::info('No .env file found, database configuration incomplete');
            return false;
        }
        
        $envContent = File::get($envPath);
        $connection = Config::get('database.default');
        
        // For MySQL/MariaDB/PostgreSQL, check if required fields are uncommented and have values
        if (in_array($connection, ['mysql', 'mariadb', 'pgsql'])) {
            $requiredFields = ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME'];
            $foundFields = [];
            
            foreach ($requiredFields as $field) {
                // Check if field exists and is not commented out
                if (preg_match('/^' . preg_quote($field) . '=(.+)$/m', $envContent, $matches)) {
                    $value = trim($matches[1], '"\'');
                    if (!empty($value)) {
                        $foundFields[] = $field;
                    }
                }
            }
            
            $isComplete = count($foundFields) === count($requiredFields);
            
            \Log::info('Checked .env file database configuration', [
                'connection' => $connection,
                'required_fields' => $requiredFields,
                'found_fields' => $foundFields,
                'is_complete' => $isComplete
            ]);
            
            return $isComplete;
        }
        
        return true;
    }

    /**
     * Check if database is properly configured
     */
    public function isDatabaseConfigured(): bool
    {
        try {
            // First check if configuration is complete
            if (!$this->isDatabaseConfigurationComplete()) {
                return false;
            }
            
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
     * Get detailed setup progress information
     */
    public function getDetailedProgress(): array
    {
        $steps = $this->getSetupSteps();
        $currentStep = $this->getSetupStep();
        $progress = $this->getSetupProgress();
        
        $completedSteps = array_filter($steps, fn($step) => $step['completed'] ?? false);
        $remainingSteps = array_filter($steps, fn($step) => !($step['completed'] ?? false));
        
        return [
            'current_step' => $currentStep,
            'progress_percentage' => $progress,
            'total_steps' => count($steps),
            'completed_steps' => count($completedSteps),
            'remaining_steps' => count($remainingSteps),
            'steps' => $steps,
            'estimated_time_remaining' => $this->getEstimatedTimeRemaining($remainingSteps),
            'setup_started_at' => $this->getSetupStartTime(),
            'current_step_started_at' => $this->getCurrentStepStartTime(),
        ];
    }

    /**
     * Get estimated time remaining for setup completion
     */
    public function getEstimatedTimeRemaining(array $remainingSteps = null): string
    {
        if ($remainingSteps === null) {
            $steps = $this->getSetupSteps();
            $remainingSteps = array_filter($steps, fn($step) => !($step['completed'] ?? false));
        }

        $estimatedMinutes = [
            'assets' => 3,
            'welcome' => 1,
            'database' => 3,
            'admin' => 2,
            'storage' => 4,
            'complete' => 1
        ];

        $totalMinutes = 0;
        foreach ($remainingSteps as $stepKey => $step) {
            $totalMinutes += $estimatedMinutes[$stepKey] ?? 2;
        }

        if ($totalMinutes <= 1) {
            return '< 1 minute';
        } elseif ($totalMinutes < 60) {
            return "{$totalMinutes} minutes";
        } else {
            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours} hour" . ($hours > 1 ? 's' : '');
        }
    }

    /**
     * Get setup start time
     */
    public function getSetupStartTime(): ?string
    {
        $state = $this->getSetupStateInternal();
        return $state['started_at'] ?? null;
    }

    /**
     * Get current step start time
     */
    public function getCurrentStepStartTime(): ?string
    {
        $state = $this->getSetupStateInternal();
        $currentStep = $this->getSetupStep();
        
        return $state['steps'][$currentStep]['started_at'] ?? null;
    }

    /**
     * Mark step as started (for timing tracking)
     */
    public function markStepStarted(string $step): void
    {
        if (!in_array($step, $this->steps)) {
            throw new \InvalidArgumentException("Invalid setup step: {$step}");
        }
        
        $state = $this->getSetupStateInternal();
        
        if (!isset($state['steps'][$step]['started_at'])) {
            $state['steps'][$step]['started_at'] = now()->toISOString();
            $this->saveSetupState($state);
        }
    }

    /**
     * Get step completion details for visual feedback
     */
    public function getStepCompletionDetails(string $step): array
    {
        $details = [
            'assets' => [
                'title' => 'Frontend Assets Built',
                'message' => 'All CSS and JavaScript files have been compiled and are ready for use.',
                'details' => [
                    'Vite manifest file created successfully',
                    'CSS files compiled and optimized',
                    'JavaScript files bundled and minified',
                    'Asset versioning configured'
                ]
            ],
            'welcome' => [
                'title' => 'System Requirements Verified',
                'message' => 'All system requirements have been checked and verified.',
                'details' => [
                    'PHP version and extensions verified',
                    'File permissions checked',
                    'Directory structure validated',
                    'Environment configuration verified'
                ]
            ],
            'database' => [
                'title' => 'Database Configured',
                'message' => 'Database connection established and migrations completed.',
                'details' => [
                    'Database connection tested successfully',
                    'Database tables created',
                    'Initial data seeded',
                    'Database configuration saved'
                ]
            ],
            'admin' => [
                'title' => 'Administrator Account Created',
                'message' => 'Your administrator account has been created successfully.',
                'details' => [
                    'Admin user account created',
                    'Password securely hashed',
                    'Admin role assigned',
                    'Account ready for login'
                ]
            ],
            'storage' => [
                'title' => 'Cloud Storage Configured',
                'message' => 'Cloud storage provider has been configured and tested.',
                'details' => [
                    'Storage provider credentials validated',
                    'Connection tested successfully',
                    'Upload permissions verified',
                    'Storage configuration saved'
                ]
            ],
            'complete' => [
                'title' => 'Setup Complete!',
                'message' => 'Your installation is now complete and ready to use.',
                'details' => [
                    'All setup steps completed successfully',
                    'Application configuration finalized',
                    'Security settings applied',
                    'System ready for production use'
                ]
            ]
        ];

        return $details[$step] ?? [
            'title' => ucfirst($step) . ' Complete',
            'message' => 'This step has been completed successfully.',
            'details' => []
        ];
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

            // Check if backup directory is writable
            $backupDir = storage_path('app/' . $this->backupDirectory);
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }
            if (!is_writable($backupDir)) {
                $issues[] = 'Setup backup directory is not writable: ' . $backupDir;
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

    /**
     * Validate setup state integrity
     */
    public function validateSetupStateIntegrity(): array
    {
        $issues = [];
        $state = $this->getSetupStateInternal();

        // Check required fields
        $requiredFields = ['setup_complete', 'current_step', 'started_at', 'steps'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $state)) {
                $issues[] = "Missing required field: {$field}";
            }
        }

        // Check state version compatibility
        if (isset($state['version']) && $state['version'] !== self::STATE_VERSION) {
            $issues[] = "State version mismatch. Expected: " . self::STATE_VERSION . ", Found: " . $state['version'];
        }

        // Validate steps structure
        if (isset($state['steps']) && is_array($state['steps'])) {
            foreach ($this->steps as $step) {
                if (!isset($state['steps'][$step])) {
                    $issues[] = "Missing step configuration: {$step}";
                    continue;
                }

                $stepData = $state['steps'][$step];
                if (!is_array($stepData)) {
                    $issues[] = "Invalid step data structure for: {$step}";
                    continue;
                }

                if (!array_key_exists('completed', $stepData)) {
                    $issues[] = "Missing 'completed' field for step: {$step}";
                }

                if (!array_key_exists('completed_at', $stepData)) {
                    $issues[] = "Missing 'completed_at' field for step: {$step}";
                }

                // Validate timestamp format if present
                if ($stepData['completed_at'] !== null) {
                    try {
                        \Carbon\Carbon::parse($stepData['completed_at']);
                    } catch (\Exception $e) {
                        $issues[] = "Invalid timestamp format for step {$step}: " . $stepData['completed_at'];
                    }
                }
            }
        }

        // Validate current step
        if (isset($state['current_step']) && !in_array($state['current_step'], [...$this->steps, 'complete'])) {
            $issues[] = "Invalid current step: " . $state['current_step'];
        }

        // Validate timestamps
        if (isset($state['started_at'])) {
            try {
                \Carbon\Carbon::parse($state['started_at']);
            } catch (\Exception $e) {
                $issues[] = "Invalid started_at timestamp: " . $state['started_at'];
            }
        }

        if (isset($state['completed_at'])) {
            try {
                \Carbon\Carbon::parse($state['completed_at']);
            } catch (\Exception $e) {
                $issues[] = "Invalid completed_at timestamp: " . $state['completed_at'];
            }
        }

        return $issues;
    }

    /**
     * Create backup of current setup state
     */
    public function createStateBackup(): string
    {
        $backupDir = storage_path('app/' . $this->backupDirectory);
        
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupFile = $backupDir . "/setup-state-backup-{$timestamp}.json";
        
        $stateFile = storage_path('app/' . $this->stateFile);
        
        if (File::exists($stateFile)) {
            File::copy($stateFile, $backupFile);
            $this->cleanupOldBackups();
        }

        return $backupFile;
    }

    /**
     * Restore setup state from backup
     */
    public function restoreStateFromBackup(string $backupFile): bool
    {
        if (!File::exists($backupFile)) {
            return false;
        }

        try {
            // Validate backup file before restoring
            $backupContent = File::get($backupFile);
            $backupState = json_decode($backupContent, true);
            
            if ($backupState === null) {
                return false;
            }

            // Create backup of current state before restoring
            $this->createStateBackup();

            // Restore the backup
            $stateFile = storage_path('app/' . $this->stateFile);
            File::copy($backupFile, $stateFile);

            // Clear cache after restoration
            $this->clearSetupCache();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get available backup files
     */
    public function getAvailableBackups(): array
    {
        $backupDir = storage_path('app/' . $this->backupDirectory);
        
        if (!File::exists($backupDir)) {
            return [];
        }

        $backups = [];
        $files = File::files($backupDir);
        
        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.json') && 
                str_starts_with($file->getFilename(), 'setup-state-backup-')) {
                
                $backups[] = [
                    'file' => $file->getPathname(),
                    'filename' => $file->getFilename(),
                    'created_at' => \Carbon\Carbon::createFromTimestamp($file->getMTime()),
                    'size' => $file->getSize(),
                ];
            }
        }

        // Sort by creation time (newest first)
        usort($backups, fn($a, $b) => $b['created_at']->timestamp - $a['created_at']->timestamp);

        return $backups;
    }

    /**
     * Clean up old backup files
     */
    private function cleanupOldBackups(): void
    {
        $backups = $this->getAvailableBackups();
        
        if (count($backups) > self::MAX_BACKUP_FILES) {
            $backupsToDelete = array_slice($backups, self::MAX_BACKUP_FILES);
            
            foreach ($backupsToDelete as $backup) {
                File::delete($backup['file']);
            }
        }
    }

    /**
     * Detect and resume interrupted setup
     */
    public function detectAndResumeSetup(): array
    {
        $resumeInfo = [
            'interrupted' => false,
            'resumed_from' => null,
            'issues_found' => [],
            'actions_taken' => [],
        ];

        try {
            // Check if state file exists and is valid
            $stateFile = storage_path('app/' . $this->stateFile);
            
            if (!File::exists($stateFile)) {
                $resumeInfo['actions_taken'][] = 'Created initial setup state';
                return $resumeInfo;
            }

            // Try to read and validate state - this will throw exception if corrupted
            try {
                $state = $this->getSetupStateInternal();
                $integrityIssues = $this->validateSetupStateIntegrity();
            } catch (\RuntimeException $e) {
                // State file is corrupted
                $resumeInfo['interrupted'] = true;
                $resumeInfo['issues_found'][] = 'Setup state file is corrupted: ' . $e->getMessage();
                
                // Try to recover from backup
                $backups = $this->getAvailableBackups();
                
                if (!empty($backups)) {
                    $latestBackup = $backups[0];
                    
                    if ($this->restoreStateFromBackup($latestBackup['file'])) {
                        $resumeInfo['resumed_from'] = $latestBackup['filename'];
                        $resumeInfo['actions_taken'][] = 'Restored state from backup: ' . $latestBackup['filename'];
                    } else {
                        $resumeInfo['actions_taken'][] = 'Failed to restore from backup, recreating state';
                        $this->recreateSetupState();
                    }
                } else {
                    $resumeInfo['actions_taken'][] = 'No backups available, recreating state';
                    $this->recreateSetupState();
                }
                
                return $resumeInfo;
            }
            
            if (!empty($integrityIssues)) {
                $resumeInfo['interrupted'] = true;
                $resumeInfo['issues_found'] = $integrityIssues;
                
                // Try to recover from backup
                $backups = $this->getAvailableBackups();
                
                if (!empty($backups)) {
                    $latestBackup = $backups[0];
                    
                    if ($this->restoreStateFromBackup($latestBackup['file'])) {
                        $resumeInfo['resumed_from'] = $latestBackup['filename'];
                        $resumeInfo['actions_taken'][] = 'Restored state from backup: ' . $latestBackup['filename'];
                    } else {
                        $resumeInfo['actions_taken'][] = 'Failed to restore from backup, recreating state';
                        $this->recreateSetupState();
                    }
                } else {
                    $resumeInfo['actions_taken'][] = 'No backups available, recreating state';
                    $this->recreateSetupState();
                }
            }

            // Detect incomplete steps and resume
            $currentStep = $this->getSetupStep();
            $state = $this->getSetupStateInternal();
            
            // Check for steps that were started but not completed
            foreach ($state['steps'] ?? [] as $stepName => $stepData) {
                if (isset($stepData['started_at']) && !($stepData['completed'] ?? false)) {
                    $resumeInfo['interrupted'] = true;
                    $resumeInfo['actions_taken'][] = "Detected incomplete step: {$stepName}";
                }
            }

            // Auto-detect current step based on system state
            $detectedStep = $this->autoDetectCurrentStep();
            if ($detectedStep !== $currentStep) {
                $resumeInfo['actions_taken'][] = "Updated current step from {$currentStep} to {$detectedStep}";
                $this->updateCurrentStep($detectedStep);
            }

        } catch (\Exception $e) {
            $resumeInfo['interrupted'] = true;
            $resumeInfo['issues_found'][] = 'Exception during setup detection: ' . $e->getMessage();
            $resumeInfo['actions_taken'][] = 'Recreated setup state due to exception';
            $this->recreateSetupState();
        }

        return $resumeInfo;
    }

    /**
     * Generate integrity hash for setup state
     */
    private function generateStateIntegrityHash(array $state): string
    {
        // Remove the hash itself from calculation
        $stateForHash = $state;
        unset($stateForHash['integrity_hash']);
        
        // Sort keys for consistent hashing
        ksort($stateForHash);
        
        return hash('sha256', json_encode($stateForHash));
    }

    /**
     * Securely update database environment variables
     */
    public function updateDatabaseEnvironment(array $databaseConfig): array
    {
        // Sanitize database configuration
        $sanitizationResult = $this->securityService->sanitizeDatabaseConfig($databaseConfig);
        
        if (!empty($sanitizationResult['violations'])) {
            return [
                'success' => false,
                'message' => 'Database configuration validation failed',
                'violations' => $sanitizationResult['violations']
            ];
        }

        $sanitizedConfig = $sanitizationResult['sanitized'];
        
        // Prepare environment updates
        $envUpdates = [];
        
        if (isset($sanitizedConfig['database'])) {
            $envUpdates['DB_DATABASE'] = $sanitizedConfig['database'];
        }
        
        if (isset($sanitizedConfig['username'])) {
            $envUpdates['DB_USERNAME'] = $sanitizedConfig['username'];
        }
        
        if (isset($sanitizedConfig['password'])) {
            $envUpdates['DB_PASSWORD'] = $sanitizedConfig['password'];
        }
        
        if (isset($sanitizedConfig['host'])) {
            $envUpdates['DB_HOST'] = $sanitizedConfig['host'];
        }
        
        if (isset($sanitizedConfig['port'])) {
            $envUpdates['DB_PORT'] = (string) $sanitizedConfig['port'];
        }

        // Update environment file securely
        return $this->environmentFileService->updateEnvironmentFile($envUpdates);
    }

    /**
     * Securely update cloud storage environment variables
     */
    public function updateStorageEnvironment(array $storageConfig): array
    {
        Log::info('Starting storage environment update', [
            'config_keys' => array_keys($storageConfig)
        ]);
        
        // Sanitize storage configuration
        $sanitizationResult = $this->securityService->sanitizeStorageConfig($storageConfig);
        
        Log::info('Storage config sanitization result', [
            'violations' => $sanitizationResult['violations'],
            'sanitized_keys' => array_keys($sanitizationResult['sanitized'])
        ]);
        
        if (!empty($sanitizationResult['violations'])) {
            Log::warning('Storage configuration validation failed', [
                'violations' => $sanitizationResult['violations']
            ]);
            return [
                'success' => false,
                'message' => 'Storage configuration validation failed',
                'violations' => $sanitizationResult['violations']
            ];
        }

        $sanitizedConfig = $sanitizationResult['sanitized'];
        
        // Prepare environment updates
        $envUpdates = [];
        
        if (isset($sanitizedConfig['client_id'])) {
            $envUpdates['GOOGLE_DRIVE_CLIENT_ID'] = $sanitizedConfig['client_id'];
        }
        
        if (isset($sanitizedConfig['client_secret'])) {
            $envUpdates['GOOGLE_DRIVE_CLIENT_SECRET'] = $sanitizedConfig['client_secret'];
        }

        Log::info('Prepared environment updates', [
            'update_keys' => array_keys($envUpdates)
        ]);

        // Update environment file securely
        $result = $this->environmentFileService->updateEnvironmentFile($envUpdates);
        
        Log::info('Environment file update result', [
            'success' => $result['success'],
            'message' => $result['message'],
            'violations' => $result['violations'] ?? []
        ]);

        // Clear configuration cache to ensure new values are loaded
        if ($result['success']) {
            try {
                \Illuminate\Support\Facades\Artisan::call('config:clear');
                Log::info('Configuration cache cleared after environment update');
            } catch (\Exception $e) {
                Log::warning('Failed to clear configuration cache', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $result;
    }

    /**
     * Update session cookie environment variables based on APP_URL
     */
    public function updateSessionCookieEnvironment(): array
    {
        Log::info('Starting session cookie environment update');
        
        // Get current APP_URL
        $appUrl = env('APP_URL', '');
        
        if (empty($appUrl)) {
            Log::warning('APP_URL is not set, cannot generate session cookie configuration');
            return [
                'success' => false,
                'message' => 'APP_URL is not configured',
                'violations' => ['APP_URL must be set before configuring session cookies']
            ];
        }

        // Generate session cookie configuration
        $sessionConfig = $this->environmentFileService->generateSessionCookieConfiguration($appUrl);
        
        Log::info('Generated session cookie configuration', [
            'app_url' => $appUrl,
            'config' => $sessionConfig
        ]);

        // Update environment file with session cookie settings
        $result = $this->environmentFileService->updateEnvironmentFile($sessionConfig);
        
        Log::info('Session cookie environment update result', [
            'success' => $result['success'],
            'message' => $result['message']
        ]);

        // Clear configuration cache to ensure new values are loaded
        if ($result['success']) {
            try {
                \Illuminate\Support\Facades\Artisan::call('config:clear');
                Log::info('Configuration cache cleared after session cookie update');
            } catch (\Exception $e) {
                Log::warning('Failed to clear configuration cache', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $result;
    }

    /**
     * Generate complete environment configuration including session cookies
     * 
     * @param array $config Configuration array with keys: app_url, database, storage
     * @return array Environment variables to be written
     */
    public function generateCompleteEnvironmentConfiguration(array $config): array
    {
        $envVars = [];

        // Application configuration
        if (isset($config['app_url'])) {
            $envVars['APP_URL'] = $config['app_url'];
            
            // Generate session cookie configuration based on APP_URL
            $sessionConfig = $this->environmentFileService->generateSessionCookieConfiguration($config['app_url']);
            $envVars = array_merge($envVars, $sessionConfig);
        }

        // Database configuration
        if (isset($config['database'])) {
            $dbConfig = $config['database'];
            if (isset($dbConfig['connection'])) {
                $envVars['DB_CONNECTION'] = $dbConfig['connection'];
            }
            if (isset($dbConfig['host'])) {
                $envVars['DB_HOST'] = $dbConfig['host'];
            }
            if (isset($dbConfig['port'])) {
                $envVars['DB_PORT'] = (string) $dbConfig['port'];
            }
            if (isset($dbConfig['database'])) {
                $envVars['DB_DATABASE'] = $dbConfig['database'];
            }
            if (isset($dbConfig['username'])) {
                $envVars['DB_USERNAME'] = $dbConfig['username'];
            }
            if (isset($dbConfig['password'])) {
                $envVars['DB_PASSWORD'] = $dbConfig['password'];
            }
        }

        // Storage configuration
        if (isset($config['storage'])) {
            $storageConfig = $config['storage'];
            if (isset($storageConfig['client_id'])) {
                $envVars['GOOGLE_DRIVE_CLIENT_ID'] = $storageConfig['client_id'];
            }
            if (isset($storageConfig['client_secret'])) {
                $envVars['GOOGLE_DRIVE_CLIENT_SECRET'] = $storageConfig['client_secret'];
            }
        }

        Log::info('Generated complete environment configuration', [
            'variable_count' => count($envVars),
            'has_session_config' => isset($envVars['SESSION_SECURE_COOKIE']),
            'app_url' => $config['app_url'] ?? 'not set'
        ]);

        return $envVars;
    }

    /**
     * Validate setup input with security checks
     */
    public function validateSetupInput(string $inputType, array $input): array
    {
        return match ($inputType) {
            'database' => $this->securityService->sanitizeDatabaseConfig($input),
            'admin_user' => $this->securityService->sanitizeAdminUserInput($input),
            'storage' => $this->securityService->sanitizeStorageConfig($input),
            default => [
                'sanitized' => $input,
                'violations' => ['Unknown input type']
            ]
        };
    }

    /**
     * Create secure setup session
     */
    public function createSecureSetupSession(): array
    {
        $sessionData = [
            'setup_started_at' => now()->toISOString(),
            'setup_token' => $this->securityService->generateSecureToken(),
            'current_step' => 'assets',
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ];

        session(['setup_session' => $sessionData]);

        $this->securityService->logSecurityEvent('setup_session_created', [
            'session_token' => substr($sessionData['setup_token'], 0, 8) . '...',
            'ip_address' => $sessionData['ip_address']
        ]);

        return $sessionData;
    }

    /**
     * Validate current setup session
     */
    public function validateSetupSession(): array
    {
        $sessionData = session('setup_session', []);
        
        if (empty($sessionData)) {
            Log::debug('Setup session validation failed: No session data found');
            return [
                'valid' => false,
                'violations' => ['No setup session found']
            ];
        }

        $result = $this->securityService->validateSetupSession($sessionData);
        
        if (!$result['valid']) {
            Log::debug('Setup session validation failed', [
                'violations' => $result['violations'],
                'session_data_keys' => array_keys($sessionData)
            ]);
        }

        return $result;
    }

    /**
     * Clear setup session securely
     */
    public function clearSetupSession(): void
    {
        $sessionData = session('setup_session', []);
        
        if (!empty($sessionData)) {
            $this->securityService->logSecurityEvent('setup_session_cleared', [
                'session_token' => isset($sessionData['setup_token']) 
                    ? substr($sessionData['setup_token'], 0, 8) . '...' 
                    : 'unknown'
            ]);
        }

        session()->forget('setup_session');
    }

    /**
     * Get setup security status
     */
    public function getSetupSecurityStatus(): array
    {
        $status = [
            'environment_file_secure' => false,
            'setup_state_secure' => false,
            'session_valid' => false,
            'backups_available' => false,
            'violations' => []
        ];

        try {
            // Check environment file
            $envValidation = $this->environmentFileService->validateEnvironmentFile();
            $status['environment_file_secure'] = $envValidation['valid'];
            if (!$envValidation['valid']) {
                $status['violations'] = array_merge($status['violations'], $envValidation['violations']);
            }

            // Check setup state integrity
            try {
                $this->getSetupStateInternal();
                $status['setup_state_secure'] = true;
            } catch (\Exception $e) {
                $status['violations'][] = 'Setup state integrity check failed';
            }

            // Check session validity
            $sessionValidation = $this->validateSetupSession();
            $status['session_valid'] = $sessionValidation['valid'];
            if (!$sessionValidation['valid']) {
                $status['violations'] = array_merge($status['violations'], $sessionValidation['violations']);
            }

            // Check backup availability
            $backups = $this->environmentFileService->getAvailableBackups();
            $status['backups_available'] = !empty($backups);

        } catch (\Exception $e) {
            $status['violations'][] = 'Security status check failed: ' . $e->getMessage();
        }

        return $status;
    }

    /**
     * Auto-detect current setup step based on system state
     */
    public function autoDetectCurrentStep(): string
    {
        try {
            // Check assets first
            if (!$this->areAssetsValid()) {
                return 'assets';
            }

            // Check database
            if (!$this->isDatabaseConfigured()) {
                return 'database';
            }

            // Check admin user
            if (!$this->isAdminUserCreated()) {
                return 'admin';
            }

            // Check cloud storage
            if (!$this->isCloudStorageConfigured()) {
                return 'storage';
            }

            // All checks passed
            return 'complete';

        } catch (\Exception $e) {
            // If any check fails, start from assets
            return 'assets';
        }
    }

    /**
     * Update current step in state
     */
    private function updateCurrentStep(string $step): void
    {
        $state = $this->getSetupStateInternal();
        $state['current_step'] = $step;
        $this->saveSetupState($state);
        $this->clearSetupCache();
    }

    /**
     * Recreate setup state from scratch
     */
    private function recreateSetupState(): void
    {
        // Create backup of corrupted state if it exists
        $stateFile = storage_path('app/' . $this->stateFile);
        if (File::exists($stateFile)) {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $corruptedBackup = storage_path('app/' . $this->backupDirectory . "/corrupted-state-{$timestamp}.json");
            
            $backupDir = dirname($corruptedBackup);
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }
            
            File::move($stateFile, $corruptedBackup);
        }

        // Create fresh state
        $this->createInitialSetupState();
    }

    /**
     * Clean up setup state after successful completion
     */
    public function cleanupAfterCompletion(): void
    {
        try {
            // Create final backup before cleanup
            $this->createStateBackup();

            // Clear all setup-related cache
            $this->clearSetupCache();

            // Remove temporary setup files if any
            $tempDir = storage_path('app/setup/temp');
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }

            // Clean up old backups (keep only the most recent ones)
            $this->cleanupOldBackups();

            // Mark cleanup as completed in state
            $state = $this->getSetupStateInternal();
            $state['cleanup_completed'] = true;
            $state['cleanup_completed_at'] = now()->toISOString();
            $this->saveSetupState($state);

        } catch (\Exception $e) {
            // Log cleanup failure but don't throw exception
            \Log::warning('Setup cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * Get setup recovery information
     */
    public function getRecoveryInfo(): array
    {
        return [
            'state_file_exists' => File::exists(storage_path('app/' . $this->stateFile)),
            'state_file_size' => File::exists(storage_path('app/' . $this->stateFile)) 
                ? File::size(storage_path('app/' . $this->stateFile)) 
                : 0,
            'backup_count' => count($this->getAvailableBackups()),
            'latest_backup' => $this->getLatestBackupInfo(),
            'integrity_issues' => $this->validateSetupStateIntegrity(),
            'environment_issues' => $this->validateSetupEnvironment(),
        ];
    }

    /**
     * Get latest backup information
     */
    private function getLatestBackupInfo(): ?array
    {
        $backups = $this->getAvailableBackups();
        return !empty($backups) ? $backups[0] : null;
    }
}