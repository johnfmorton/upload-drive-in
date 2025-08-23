<?php

namespace App\Services;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Exception;

/**
 * Service for detecting application setup completion status.
 * 
 * This service checks various setup requirements including database connectivity,
 * Google Drive configuration, and admin user existence to determine if the
 * application is properly configured.
 */
class SetupDetectionService
{
    /**
     * Check if the complete setup is finished.
     * 
     * @return bool True if all setup requirements are met
     */
    public function isSetupComplete(): bool
    {
        return $this->getDatabaseStatus() 
            && $this->getMailStatus()
            && $this->getGoogleDriveStatus() 
            && $this->getAdminUserStatus()
            && $this->getMigrationStatus();
    }

    /**
     * Check if database connection is properly configured and working.
     * 
     * @return bool True if database is accessible
     */
    public function getDatabaseStatus(): bool
    {
        try {
            // Check if database environment variables are configured
            if (!$this->isDatabaseConfigured()) {
                return false;
            }

            // Test database connection by running a simple query
            DB::connection()->getPdo();
            
            // Verify we can query the database
            DB::select('SELECT 1');
            
            return true;
        } catch (Exception $e) {
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
        try {
            // Try to query the database directly
            // If database is not accessible, this will throw an exception
            return User::where('role', UserRole::ADMIN)->exists();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if database migrations have been run successfully.
     * 
     * @return bool True if required database tables exist
     */
    public function getMigrationStatus(): bool
    {
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

            foreach ($requiredTables as $table) {
                if (!Schema::hasTable($table)) {
                    return false;
                }
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get queue health status by analyzing recent job processing and failed jobs.
     * 
     * @return array Queue health information with status and details
     */
    public function getQueueHealthStatus(): array
    {
        try {
            // Check if jobs table exists first
            if (!Schema::hasTable('jobs') || !Schema::hasTable('failed_jobs')) {
                return [
                    'status' => 'incomplete',
                    'message' => 'Queue tables not found - migrations may not be complete',
                    'details' => []
                ];
            }

            $now = Carbon::now();
            $last24Hours = $now->subHours(24);

            // Count recent job activity
            $recentJobs = DB::table('jobs')
                ->where('created_at', '>=', $last24Hours->timestamp)
                ->count();

            // Count failed jobs in last 24 hours
            $recentFailedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', $last24Hours)
                ->count();

            // Get total failed jobs
            $totalFailedJobs = DB::table('failed_jobs')->count();

            // Check for stalled jobs (jobs that have been reserved for more than 1 hour)
            $stalledJobs = DB::table('jobs')
                ->whereNotNull('reserved_at')
                ->where('reserved_at', '<', $now->subHour()->timestamp)
                ->count();

            // Determine status based on metrics
            if ($recentFailedJobs > 10 || $stalledJobs > 5) {
                $status = 'error';
                $message = 'Queue worker appears to have issues';
            } elseif ($recentJobs > 0 && $recentFailedJobs === 0) {
                $status = 'working';
                $message = 'Queue worker is processing jobs successfully';
            } elseif ($recentJobs === 0 && $recentFailedJobs === 0) {
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
                    'recent_jobs' => $recentJobs,
                    'recent_failed_jobs' => $recentFailedJobs,
                    'total_failed_jobs' => $totalFailedJobs,
                    'stalled_jobs' => $stalledJobs,
                    'checked_at' => $now->toISOString()
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'cannot_verify',
                'message' => 'Unable to check queue status',
                'details' => [
                    'error' => $e->getMessage()
                ]
            ];
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

        // Database status
        try {
            $dbStatus = $this->getDatabaseStatus();
            if ($dbStatus) {
                $connectionName = config('database.default');
                $driver = config("database.connections.{$connectionName}.driver");
                $statuses['database'] = [
                    'status' => 'completed',
                    'message' => 'Database connection is working',
                    'details' => [
                        'connection_name' => $connectionName,
                        'driver' => $driver,
                        'checked_at' => Carbon::now()->toISOString()
                    ]
                ];
            } else {
                $statuses['database'] = [
                    'status' => 'cannot_verify',
                    'message' => 'Database connection not configured or not accessible',
                    'details' => [
                        'checked_at' => Carbon::now()->toISOString()
                    ]
                ];
            }
        } catch (Exception $e) {
            $statuses['database'] = [
                'status' => 'cannot_verify',
                'message' => 'Error checking database status',
                'details' => [
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
        $host = env('DB_HOST');
        $database = env('DB_DATABASE');
        $username = env('DB_USERNAME');

        return !empty($connection) && !empty($host) && !empty($database) && !empty($username);
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
}