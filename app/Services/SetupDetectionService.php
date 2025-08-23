<?php

namespace App\Services;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
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
            && $this->getAdminUserStatus();
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