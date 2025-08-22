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
}