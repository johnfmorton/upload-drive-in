<?php

namespace App\Services;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use Throwable;
use PDOException;
use Illuminate\Database\QueryException;

/**
 * Service for detecting application setup completion status.
 * 
 * This service checks various setup requirements including database connectivity,
 * Google Drive configuration, and admin user existence to determine if the
 * application is properly configured.
 * 
 * Enhanced with comprehensive error handling, timeout management, and fallback mechanisms.
 */
class SetupDetectionService
{
    /**
     * Timeout for database operations in seconds.
     */
    private const DB_TIMEOUT = 5;
    
    /**
     * Cache TTL for fallback results in seconds.
     */
    private const FALLBACK_CACHE_TTL = 300; // 5 minutes
    
    /**
     * Cache key prefix for fallback data.
     */
    private const FALLBACK_CACHE_PREFIX = 'setup_detection_fallback_';
    /**
     * Check if the complete setup is finished.
     * 
     * @return bool True if all setup requirements are met
     */
    public function isSetupComplete(): bool
    {
        // If setup is disabled, consider it complete (setup process not available)
        if (!$this->isSetupEnabled()) {
            return true;
        }
        
        return $this->getDatabaseStatus() 
            && $this->getMailStatus()
            && $this->getGoogleDriveStatus() 
            && $this->getAdminUserStatus()
            && $this->getMigrationStatus();
    }

    /**
     * Check if the setup process is enabled.
     * 
     * @return bool True if setup is enabled
     */
    public function isSetupEnabled(): bool
    {
        $enabled = config('setup.enabled', false);
        
        // Handle string values from environment
        if (is_string($enabled)) {
            return strtolower($enabled) === 'true';
        }
        
        return (bool) $enabled;
    }

    /**
     * Check if database connection is properly configured and working.
     * 
     * @return bool True if database is accessible
     */
    public function getDatabaseStatus(): bool
    {
        try {
            // Clear configuration cache if we're in setup mode to ensure fresh config reads
            $this->clearConfigCacheIfInSetup();
            
            // Use the new DatabaseCredentialService for better checking
            $databaseCredentialService = app(\App\Services\DatabaseCredentialService::class);
            $credentialStatus = $databaseCredentialService->checkDatabaseCredentials();
            
            return $credentialStatus['status'] === 'completed';
            
        } catch (Exception $e) {
            Log::error('Error checking database status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Check if mail server configuration is properly set up.
     * 
     * @return bool True if mail is configured
     */
    public function getMailStatus(): bool
    {
        // Clear configuration cache if we're in setup mode to ensure fresh config reads
        $this->clearConfigCacheIfInSetup();
        
        $mailer = env('MAIL_MAILER');
        $host = env('MAIL_HOST');
        $port = env('MAIL_PORT');
        $username = env('MAIL_USERNAME');
        $password = env('MAIL_PASSWORD');
        $fromAddress = env('MAIL_FROM_ADDRESS');

        // Basic required fields
        if (empty($mailer) || empty($host) || empty($port) || empty($fromAddress)) {
            return false;
        }

        // Validate email format
        if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Environment-aware validation for username/password
        if ($this->isLocalDevelopmentMailSetup($host, $port)) {
            // In local development (Mailpit, etc.), username/password can be null
            return true;
        }

        // For production/non-local setups, require username and password
        return !empty($username) && !empty($password);
    }

    /**
     * Check if Google Drive credentials are configured.
     * 
     * @return bool True if Google Drive is configured
     */
    public function getGoogleDriveStatus(): bool
    {
        // Clear configuration cache if we're in setup mode to ensure fresh config reads
        $this->clearConfigCacheIfInSetup();
        
        $clientId = config('services.google.client_id') ?: env('GOOGLE_DRIVE_CLIENT_ID');
        $clientSecret = config('services.google.client_secret') ?: env('GOOGLE_DRIVE_CLIENT_SECRET');
        
        return !empty($clientId) && !empty($clientSecret);
    }

    /**
     * Check if at least one admin user exists in the system.
     * 
     * @return bool True if admin user exists
     */
    public function getAdminUserStatus(): bool
    {
        $cacheKey = self::FALLBACK_CACHE_PREFIX . 'admin_user_status';
        
        try {
            // Execute with timeout to prevent hanging
            $result = $this->executeWithTimeout(function () {
                return User::where('role', UserRole::ADMIN)->exists();
            }, self::DB_TIMEOUT);
            
            // Cache successful result
            if ($result !== null) {
                Cache::put($cacheKey, $result, self::FALLBACK_CACHE_TTL);
            }
            
            return $result ?? false;
            
        } catch (PDOException $e) {
            Log::warning('Admin user check failed - PDO error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return $this->getFallbackResult($cacheKey, false);
            
        } catch (QueryException $e) {
            Log::warning('Admin user check failed - Query error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return $this->getFallbackResult($cacheKey, false);
            
        } catch (Exception $e) {
            Log::error('Admin user check failed - Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getFallbackResult($cacheKey, false);
            
        } catch (Throwable $e) {
            Log::critical('Admin user check failed - Critical error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getFallbackResult($cacheKey, false);
        }
    }

    /**
     * Check if database migrations have been run successfully.
     * 
     * @return bool True if required database tables exist
     */
    public function getMigrationStatus(): bool
    {
        $cacheKey = self::FALLBACK_CACHE_PREFIX . 'migration_status';
        
        try {
            // Core tables that must exist for the application to function
            $requiredTables = [
                'users',
                'sessions', 
                'password_reset_tokens',
                'jobs',
                'failed_jobs',
                'file_uploads',
                'email_validations',
                'upload_tokens',
                'google_drive_tokens',
                'client_user_relationships'
            ];

            $result = $this->executeWithTimeout(function () use ($requiredTables) {
                foreach ($requiredTables as $table) {
                    if (!Schema::hasTable($table)) {
                        Log::debug('Required table missing', ['table' => $table]);
                        return false;
                    }
                }
                return true;
            }, self::DB_TIMEOUT);
            
            // Cache successful result
            if ($result !== null) {
                Cache::put($cacheKey, $result, self::FALLBACK_CACHE_TTL);
            }
            
            return $result ?? false;
            
        } catch (PDOException $e) {
            Log::warning('Migration status check failed - PDO error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return $this->getFallbackResult($cacheKey, false);
            
        } catch (QueryException $e) {
            Log::warning('Migration status check failed - Query error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return $this->getFallbackResult($cacheKey, false);
            
        } catch (Exception $e) {
            Log::error('Migration status check failed - Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getFallbackResult($cacheKey, false);
            
        } catch (Throwable $e) {
            Log::critical('Migration status check failed - Critical error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getFallbackResult($cacheKey, false);
        }
    }

    /**
     * Get queue health status by analyzing recent job processing and failed jobs.
     * 
     * @return array Queue health information with status and details
     */
    public function getQueueHealthStatus(): array
    {
        $cacheKey = self::FALLBACK_CACHE_PREFIX . 'queue_health_status';
        
        try {
            $result = $this->executeWithTimeout(function () {
                // Check if jobs table exists first
                if (!Schema::hasTable('jobs') || !Schema::hasTable('failed_jobs')) {
                    return [
                        'status' => 'incomplete',
                        'message' => 'Queue tables not found - migrations may not be complete',
                        'details' => [],
                        'fallback' => false
                    ];
                }

                $now = Carbon::now();
                $last24Hours = $now->copy()->subHours(24);

                // Count pending jobs (jobs still in queue)
                $pendingJobs = DB::table('jobs')->count();

                // Count failed jobs in last 24 hours
                $recentFailedJobs = DB::table('failed_jobs')
                    ->where('failed_at', '>=', $last24Hours)
                    ->count();

                // Get total failed jobs
                $totalFailedJobs = DB::table('failed_jobs')->count();

                // Check for stalled jobs (jobs that have been reserved for more than 1 hour)
                $stalledJobs = DB::table('jobs')
                    ->whereNotNull('reserved_at')
                    ->where('reserved_at', '<', $now->copy()->subHour()->timestamp)
                    ->count();

                // Try to get recent job activity from QueueTestService if available
                $recentJobActivity = 0;
                try {
                    $queueTestService = app(\App\Services\QueueTestService::class);
                    $healthMetrics = $queueTestService->getQueueHealthMetrics();
                    
                    // Use test job statistics as a proxy for recent activity
                    if (isset($healthMetrics['test_job_statistics']['test_jobs_24h'])) {
                        $recentJobActivity = $healthMetrics['test_job_statistics']['test_jobs_24h'];
                    }
                } catch (Exception $e) {
                    Log::debug('Could not get test job statistics for queue health', [
                        'error' => $e->getMessage()
                    ]);
                }

                // Determine status based on metrics
                if ($recentFailedJobs > 10 || $stalledJobs > 5) {
                    $status = 'error';
                    $message = 'Queue worker appears to have issues';
                } elseif ($pendingJobs > 0 && $recentFailedJobs === 0) {
                    $status = 'working';
                    $message = 'Queue worker is processing jobs successfully';
                } elseif ($recentJobActivity > 0 && $recentFailedJobs === 0) {
                    // Recent test job activity indicates worker is functioning
                    $status = 'working';
                    $message = 'Queue worker is functioning properly';
                } elseif ($pendingJobs === 0 && $recentFailedJobs === 0 && $recentJobActivity === 0) {
                    // No recent activity and no recent failures - healthy idle state
                    $status = 'idle';
                    $message = 'Queue worker is idle - no recent activity';
                } elseif ($recentFailedJobs > 5) {
                    // Significant number of recent failures
                    $status = 'needs_attention';
                    $message = 'Queue worker may need attention - multiple recent failures detected';
                } elseif ($recentFailedJobs > 0 && $recentFailedJobs <= 5 && $stalledJobs === 0) {
                    // Some recent failures but not excessive and no stalled jobs
                    // This suggests the worker might be functional now, just had some issues
                    $status = 'idle';
                    $message = 'Queue worker is idle - use test button to verify functionality';
                } else {
                    // Fallback for other scenarios
                    $status = 'idle';
                    $message = 'Queue worker appears idle';
                }

                return [
                    'status' => $status,
                    'message' => $message,
                    'details' => [
                        'recent_jobs' => $recentJobActivity, // Use test job activity as proxy for recent jobs
                        'pending_jobs' => $pendingJobs,
                        'recent_failed_jobs' => $recentFailedJobs,
                        'total_failed_jobs' => $totalFailedJobs,
                        'stalled_jobs' => $stalledJobs,
                        'checked_at' => $now->toISOString()
                    ],
                    'fallback' => false
                ];
            }, self::DB_TIMEOUT);
            
            if ($result !== null) {
                // Cache successful result
                Cache::put($cacheKey, $result, self::FALLBACK_CACHE_TTL);
                return $result;
            }
            
            throw new Exception('Timeout occurred during queue health check');
            
        } catch (PDOException $e) {
            Log::warning('Queue health check failed - PDO error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return $this->getFallbackQueueStatus($cacheKey, $e);
            
        } catch (QueryException $e) {
            Log::warning('Queue health check failed - Query error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return $this->getFallbackQueueStatus($cacheKey, $e);
            
        } catch (Exception $e) {
            Log::error('Queue health check failed - Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getFallbackQueueStatus($cacheKey, $e);
            
        } catch (Throwable $e) {
            Log::critical('Queue health check failed - Critical error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getFallbackQueueStatus($cacheKey, $e);
        }
    }

    /**
     * Get comprehensive status array for all setup steps.
     * 
     * @return array<string, array> Status information for each setup step
     */
    public function getAllStepStatuses(): array
    {
        $statuses = [];

        // Database status with detailed feedback
        try {
            // Clear configuration cache if we're in setup mode to ensure fresh config reads
            $this->clearConfigCacheIfInSetup();
            
            // Use the new DatabaseCredentialService for detailed checking
            $databaseCredentialService = app(\App\Services\DatabaseCredentialService::class);
            $credentialStatus = $databaseCredentialService->checkDatabaseCredentials();
            
            // Map the detailed status to our setup status format
            $statuses['database'] = [
                'status' => $credentialStatus['status'],
                'message' => $credentialStatus['message'],
                'details' => [
                    'description' => $credentialStatus['details'],
                    'scenario' => $credentialStatus['metadata']['scenario'] ?? 'unknown',
                    'checked_at' => $credentialStatus['metadata']['checked_at'],
                    'metadata' => $credentialStatus['metadata']
                ]
            ];
            
            // Add fix instructions for incomplete or error states
            if (in_array($credentialStatus['status'], ['incomplete', 'error'])) {
                $fixInstructions = $databaseCredentialService->getFixInstructions($credentialStatus);
                $statuses['database']['details']['fix_instructions'] = $fixInstructions;
            }
            
        } catch (Exception $e) {
            Log::error('Error getting detailed database status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $statuses['database'] = [
                'status' => 'error',
                'message' => 'Error checking database status',
                'details' => [
                    'description' => 'An unexpected error occurred while checking database credentials',
                    'scenario' => 'check_error',
                    'error' => $e->getMessage(),
                    'checked_at' => Carbon::now()->toISOString()
                ]
            ];
        }

        // Mail status
        try {
            $mailStatus = $this->getMailStatus();
            $driver = env('MAIL_MAILER', 'not_set');
            if ($mailStatus) {
                $statuses['mail'] = [
                    'status' => 'completed',
                    'message' => 'Mail configuration is valid',
                    'details' => [
                        'driver' => $driver,
                        'checked_at' => Carbon::now()->toISOString()
                    ]
                ];
            } else {
                $statuses['mail'] = [
                    'status' => 'incomplete',
                    'message' => 'Mail server configuration not properly set up',
                    'details' => [
                        'driver' => $driver,
                        'checked_at' => Carbon::now()->toISOString()
                    ]
                ];
            }
        } catch (Exception $e) {
            $statuses['mail'] = [
                'status' => 'cannot_verify',
                'message' => 'Error checking mail status',
                'details' => [
                    'error' => $e->getMessage(),
                    'checked_at' => Carbon::now()->toISOString()
                ]
            ];
        }

        // Google Drive status
        try {
            $driveStatus = $this->getGoogleDriveStatus();
            $clientId = env('GOOGLE_DRIVE_CLIENT_ID');
            $hasClientId = !empty($clientId);
            $hasClientSecret = !empty(env('GOOGLE_DRIVE_CLIENT_SECRET'));
            
            if ($driveStatus) {
                $statuses['google_drive'] = [
                    'status' => 'completed',
                    'message' => 'Google Drive credentials are configured',
                    'details' => [
                        'client_id' => $hasClientId ? 'Configured' : 'Missing',
                        'client_secret' => $hasClientSecret ? 'Configured' : 'Missing',
                        'checked_at' => Carbon::now()->toISOString()
                    ]
                ];
            } else {
                $statuses['google_drive'] = [
                    'status' => 'incomplete',
                    'message' => 'Google Drive credentials not configured',
                    'details' => [
                        'client_id' => $hasClientId ? 'Configured' : 'Missing',
                        'client_secret' => $hasClientSecret ? 'Configured' : 'Missing',
                        'checked_at' => Carbon::now()->toISOString()
                    ]
                ];
            }
        } catch (Exception $e) {
            $statuses['google_drive'] = [
                'status' => 'cannot_verify',
                'message' => 'Error checking Google Drive status',
                'details' => [
                    'error' => $e->getMessage(),
                    'checked_at' => Carbon::now()->toISOString()
                ]
            ];
        }

        // Migration status
        try {
            $migrationStatus = $this->getMigrationStatus();
            if ($migrationStatus) {
                $statuses['migrations'] = [
                    'status' => 'completed',
                    'message' => 'Database migrations have been run',
                    'details' => [
                        'checked_at' => Carbon::now()->toISOString()
                    ]
                ];
            } else {
                $statuses['migrations'] = [
                    'status' => 'incomplete',
                    'message' => 'Database migrations need to be run',
                    'details' => [
                        'checked_at' => Carbon::now()->toISOString()
                    ]
                ];
            }
        } catch (Exception $e) {
            $statuses['migrations'] = [
                'status' => 'cannot_verify',
                'message' => 'Error checking migration status',
                'details' => [
                    'error' => $e->getMessage(),
                    'checked_at' => Carbon::now()->toISOString()
                ]
            ];
        }

        // Admin user status
        try {
            $adminStatus = $this->getAdminUserStatus();
            if ($adminStatus) {
                $statuses['admin_user'] = [
                    'status' => 'completed',
                    'message' => 'Admin user exists in the system',
                    'details' => [
                        'checked_at' => Carbon::now()->toISOString()
                    ]
                ];
            } else {
                $statuses['admin_user'] = [
                    'status' => 'incomplete',
                    'message' => 'No admin user found in the system',
                    'details' => [
                        'checked_at' => Carbon::now()->toISOString()
                    ]
                ];
            }
        } catch (Exception $e) {
            $statuses['admin_user'] = [
                'status' => 'cannot_verify',
                'message' => 'Error checking admin user status',
                'details' => [
                    'error' => $e->getMessage(),
                    'checked_at' => Carbon::now()->toISOString()
                ]
            ];
        }

        // Queue health status
        $queueHealth = $this->getQueueHealthStatus();
        $statuses['queue_worker'] = $queueHealth;

        // Setup enabled status
        $setupEnabled = $this->isSetupEnabled();
        $statuses['setup_enabled'] = [
            'status' => $setupEnabled ? 'enabled' : 'disabled',
            'message' => $setupEnabled ? 'Setup process is enabled' : 'Setup process is disabled',
            'details' => [
                'enabled' => $setupEnabled,
                'env_value' => env('APP_SETUP_ENABLED'),
                'config_value' => config('setup.enabled'),
                'checked_at' => Carbon::now()->toISOString()
            ]
        ];

        return $statuses;
    }

    /**
     * Get array of missing setup requirements.
     * 
     * @return array<string> Array of missing requirement descriptions
     */
    public function getMissingRequirements(): array
    {
        $missing = [];

        if (!$this->getDatabaseStatus()) {
            $missing[] = 'Database connection not configured or not accessible';
        }

        if (!$this->getMailStatus()) {
            $missing[] = 'Mail server configuration not properly set up';
        }

        if (!$this->getGoogleDriveStatus()) {
            $missing[] = 'Google Drive credentials not configured';
        }

        if (!$this->getMigrationStatus()) {
            $missing[] = 'Database migrations need to be run';
        }

        if (!$this->getAdminUserStatus()) {
            $missing[] = 'No admin user found in the system';
        }

        return $missing;
    }

    /**
     * Check if database environment variables are configured.
     * 
     * @return bool True if database config is present
     */
    private function isDatabaseConfigured(): bool
    {
        $connection = env('DB_CONNECTION');
        
        // For SQLite, just check if the connection is set
        if ($connection === 'sqlite') {
            return !empty($connection);
        }
        
        // For MySQL/MariaDB/PostgreSQL, check required credentials
        if (in_array($connection, ['mysql', 'mariadb', 'pgsql'])) {
            // Check if we're in a development environment that auto-provides DB credentials
            if ($this->isDevelopmentEnvironmentWithAutoDb()) {
                // In development environments like DDEV, check if .env file has proper config
                return $this->isEnvFileDbConfigComplete();
            }
            
            // In production/normal environments, check actual environment variables
            $host = env('DB_HOST');
            $database = env('DB_DATABASE');
            $username = env('DB_USERNAME');
            
            return !empty($connection) && !empty($host) && !empty($database) && !empty($username);
        }
        
        return !empty($connection);
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
        
        if (!file_exists($envPath)) {
            Log::info('No .env file found, database configuration incomplete');
            return false;
        }
        
        $envContent = file_get_contents($envPath);
        $connection = env('DB_CONNECTION');
        
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
            
            Log::info('Checked .env file database configuration', [
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
     * Determine if this is a local development mail setup that doesn't require authentication.
     * 
     * @param string|null $host
     * @param string|null $port
     * @return bool True if this appears to be a local development setup
     */
    private function isLocalDevelopmentMailSetup(?string $host, ?string $port): bool
    {
        if (empty($host) || empty($port)) {
            return false;
        }

        // Check if we're in local environment
        $isLocalEnv = app()->environment('local') || env('APP_ENV') === 'local';
        
        // Check for common local development mail configurations
        $isLocalHost = in_array($host, ['127.0.0.1', 'localhost', 'mailpit', 'mailhog']);
        
        // Mailpit default port is 1025, MailHog uses 1025, some use 8025
        $isLocalPort = in_array($port, ['1025', '8025']);
        
        return $isLocalEnv && $isLocalHost && $isLocalPort;
    }

    /**
     * Execute a function with timeout handling.
     * 
     * @param callable $callback The function to execute
     * @param int $timeout Timeout in seconds
     * @return mixed The result of the callback or null on timeout
     * @throws Exception If the callback throws an exception
     */
    private function executeWithTimeout(callable $callback, int $timeout)
    {
        // Set database timeout if possible
        try {
            $originalTimeout = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', $timeout);
            
            $result = $callback();
            
            // Restore original timeout
            ini_set('default_socket_timeout', $originalTimeout);
            
            return $result;
            
        } catch (Exception $e) {
            // Restore original timeout on error
            if (isset($originalTimeout)) {
                ini_set('default_socket_timeout', $originalTimeout);
            }
            throw $e;
        }
    }

    /**
     * Get fallback result from cache or return default.
     * 
     * @param string $cacheKey Cache key to check
     * @param mixed $default Default value if no cache available
     * @return mixed Cached value or default
     */
    private function getFallbackResult(string $cacheKey, $default)
    {
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            Log::info('Using cached fallback result', [
                'cache_key' => $cacheKey,
                'cached_value' => $cached
            ]);
            return $cached;
        }
        
        Log::warning('No cached fallback available, using default', [
            'cache_key' => $cacheKey,
            'default_value' => $default
        ]);
        
        return $default;
    }

    /**
     * Get fallback queue status with enhanced error context.
     * 
     * @param string $cacheKey Cache key to check
     * @param Throwable $exception The exception that occurred
     * @return array Fallback queue status
     */
    private function getFallbackQueueStatus(string $cacheKey, Throwable $exception): array
    {
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null && is_array($cached)) {
            Log::info('Using cached queue health fallback', [
                'cache_key' => $cacheKey,
                'cached_status' => $cached['status'] ?? 'unknown'
            ]);
            
            // Mark as fallback data
            $cached['fallback'] = true;
            $cached['fallback_reason'] = 'Service temporarily unavailable';
            $cached['last_error'] = $exception->getMessage();
            
            return $cached;
        }
        
        Log::warning('No cached queue health available, returning error state', [
            'cache_key' => $cacheKey,
            'error' => $exception->getMessage()
        ]);
        
        return [
            'status' => 'cannot_verify',
            'message' => 'Unable to check queue status - service temporarily unavailable',
            'details' => [
                'error' => $exception->getMessage(),
                'error_type' => get_class($exception),
                'fallback' => true,
                'fallback_reason' => 'No cached data available',
                'checked_at' => Carbon::now()->toISOString()
            ],
            'fallback' => true
        ];
    }

    /**
     * Clear configuration cache if we're in setup mode and configuration has changed.
     * This prevents cached configuration from interfering with setup detection.
     * 
     * @return void
     */
    private function clearConfigCacheIfInSetup(): void
    {
        // Only clear cache if we're likely in setup mode
        if ($this->isLikelyInSetupMode()) {
            $cacheKey = 'setup_config_hash';
            $currentConfigHash = $this->getEnvironmentConfigHash();
            $cachedConfigHash = Cache::get($cacheKey);
            
            // If configuration has changed, clear caches
            if ($cachedConfigHash !== $currentConfigHash) {
                try {
                    // Clear configuration cache to ensure fresh env() reads
                    \Illuminate\Support\Facades\Artisan::call('config:clear');
                    
                    // Clear setup-related application cache
                    $this->clearSetupRelatedCache();
                    
                    // Update the cached config hash
                    Cache::put($cacheKey, $currentConfigHash, 300); // Cache for 5 minutes
                    
                    Log::debug('Configuration cache cleared due to environment changes during setup', [
                        'previous_hash' => $cachedConfigHash,
                        'current_hash' => $currentConfigHash
                    ]);
                } catch (Exception $e) {
                    Log::warning('Failed to clear configuration cache during setup', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Generate a hash of key environment configuration values.
     * 
     * @return string Hash of current configuration
     */
    private function getEnvironmentConfigHash(): string
    {
        $configValues = [
            'MAIL_MAILER' => env('MAIL_MAILER'),
            'MAIL_HOST' => env('MAIL_HOST'),
            'MAIL_PORT' => env('MAIL_PORT'),
            'MAIL_USERNAME' => env('MAIL_USERNAME'),
            'MAIL_PASSWORD' => env('MAIL_PASSWORD'),
            'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS'),
            'GOOGLE_DRIVE_CLIENT_ID' => env('GOOGLE_DRIVE_CLIENT_ID'),
            'GOOGLE_DRIVE_CLIENT_SECRET' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
            'DB_CONNECTION' => env('DB_CONNECTION'),
            'DB_HOST' => env('DB_HOST'),
            'DB_DATABASE' => env('DB_DATABASE'),
            'DB_USERNAME' => env('DB_USERNAME'),
        ];
        
        return md5(serialize($configValues));
    }

    /**
     * Clear setup-related cache entries without flushing all cache.
     * 
     * @return void
     */
    private function clearSetupRelatedCache(): void
    {
        $setupCacheKeys = [
            'setup_state_required',
            'setup_state_complete',
            'setup_status_detailed_statuses',
            'setup_status_detailed_statuses_fallback',
            'setup_status_summary',
            'setup_status_summary_fallback',
            self::FALLBACK_CACHE_PREFIX . 'database_status',
            self::FALLBACK_CACHE_PREFIX . 'admin_user_status',
            self::FALLBACK_CACHE_PREFIX . 'migration_status',
            self::FALLBACK_CACHE_PREFIX . 'queue_health_status',
        ];
        
        foreach ($setupCacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Determine if we're likely in setup mode based on request context.
     * 
     * @return bool True if we appear to be in setup mode
     */
    private function isLikelyInSetupMode(): bool
    {
        // Check if we're being called from setup-related routes or contexts
        $request = request();
        
        if ($request) {
            $path = $request->path();
            $route = $request->route();
            
            // Check for setup-related paths
            if (str_contains($path, 'setup') || str_contains($path, 'instructions')) {
                return true;
            }
            
            // Check for setup-related route names
            if ($route && $route->getName()) {
                $routeName = $route->getName();
                if (str_contains($routeName, 'setup') || str_contains($routeName, 'instructions')) {
                    return true;
                }
            }
        }
        
        // Check if we're being called from setup-related controllers or services
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($backtrace as $trace) {
            if (isset($trace['class'])) {
                $className = $trace['class'];
                if (str_contains($className, 'Setup') || str_contains($className, 'Instruction')) {
                    return true;
                }
            }
        }
        
        return false;
    }
}