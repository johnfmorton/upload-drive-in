<?php

namespace App\Exceptions;

/**
 * Exception for database setup-related errors.
 * 
 * Provides specific error handling for database connectivity,
 * migration failures, and configuration issues.
 */
class DatabaseSetupException extends SetupException
{
    /**
     * Create exception for database connection failure.
     */
    public static function connectionFailed(string $databaseType, string $technicalError, array $context = []): self
    {
        $userMessage = match ($databaseType) {
            'mysql' => 'Unable to connect to MySQL database. Please verify your connection settings.',
            'sqlite' => 'Unable to access SQLite database file. Please check file permissions.',
            default => 'Database connection failed. Please check your configuration.',
        };

        $troubleshootingSteps = match ($databaseType) {
            'mysql' => [
                'Verify that MySQL server is running and accessible',
                'Check that the database name, username, and password are correct',
                'Ensure the MySQL user has proper permissions for the database',
                'Verify that the host and port are correct',
                'Check firewall settings if connecting to a remote database',
                'Test the connection using a MySQL client tool'
            ],
            'sqlite' => [
                'Ensure the database directory exists and is writable',
                'Check that the web server has read/write permissions to the database file',
                'Verify that the SQLite PHP extension is installed and enabled',
                'Check that there is sufficient disk space available',
                'Ensure the database path is correct in your configuration'
            ],
            default => [
                'Check your database configuration settings',
                'Verify that the database server is running',
                'Ensure proper permissions are set'
            ]
        };

        return new self(
            "Database connection failed: {$technicalError}",
            500,
            null,
            $userMessage,
            $troubleshootingSteps,
            array_merge(['database_type' => $databaseType], $context)
        );
    }

    /**
     * Create exception for migration failure.
     */
    public static function migrationFailed(string $technicalError, array $context = []): self
    {
        $userMessage = 'Database migration failed. The database structure could not be created.';
        
        $troubleshootingSteps = [
            'Ensure the database user has CREATE, ALTER, and DROP permissions',
            'Check that there is sufficient disk space for the database',
            'Verify that no other process is using the database exclusively',
            'Review the Laravel logs for detailed migration error information',
            'Try running migrations manually using: php artisan migrate --force',
            'If using MySQL, ensure the database charset is set to utf8mb4'
        ];

        return new self(
            "Migration failed: {$technicalError}",
            500,
            null,
            $userMessage,
            $troubleshootingSteps,
            $context
        );
    }

    /**
     * Create exception for file permission issues.
     */
    public static function permissionDenied(string $path, string $operation = 'access'): self
    {
        $userMessage = "Permission denied when trying to {$operation} database file or directory.";
        
        $troubleshootingSteps = [
            'Check that the web server user has read/write permissions to the database directory',
            'Ensure the database file (if it exists) is writable by the web server',
            'Verify that SELinux or similar security systems are not blocking access',
            'Check that the parent directory has proper permissions (755 or 775)',
            'If using SQLite, ensure the database file has 644 or 664 permissions',
            'Try running: chmod 755 ' . dirname($path) . ' && chmod 664 ' . $path
        ];

        return new self(
            "Permission denied for path: {$path}",
            403,
            null,
            $userMessage,
            $troubleshootingSteps,
            ['path' => $path, 'operation' => $operation]
        );
    }

    /**
     * Create exception for invalid database configuration.
     */
    public static function invalidConfiguration(array $errors, string $databaseType): self
    {
        $userMessage = 'Database configuration is invalid. Please check your settings.';
        
        $troubleshootingSteps = [
            'Verify all required database configuration fields are filled',
            'Check that database credentials are correct',
            'Ensure the database name contains only valid characters',
            'For MySQL: verify host and port are accessible',
            'For SQLite: ensure the database path is valid and writable',
            'Review the specific validation errors listed below'
        ];

        return new self(
            'Invalid database configuration: ' . implode(', ', $errors),
            400,
            null,
            $userMessage,
            $troubleshootingSteps,
            ['database_type' => $databaseType, 'validation_errors' => $errors]
        );
    }

    /**
     * Create exception for database initialization failure.
     */
    public static function initializationFailed(string $databaseType, string $technicalError): self
    {
        $userMessage = "Failed to initialize {$databaseType} database. Setup cannot continue.";
        
        $troubleshootingSteps = match ($databaseType) {
            'sqlite' => [
                'Ensure the storage directory exists and is writable',
                'Check that PHP has permission to create files in the database directory',
                'Verify that the SQLite PHP extension is properly installed',
                'Check available disk space in the storage directory',
                'Try creating the database file manually and setting proper permissions'
            ],
            'mysql' => [
                'Verify that the MySQL database exists',
                'Check that the MySQL user has CREATE and ALTER permissions',
                'Ensure the MySQL server version is compatible (5.7+ or 8.0+)',
                'Verify that the database charset is set to utf8mb4',
                'Check MySQL server logs for additional error information'
            ],
            default => [
                'Check database server status and connectivity',
                'Verify database permissions and configuration',
                'Review application logs for detailed error information'
            ]
        ];

        return new self(
            "Database initialization failed: {$technicalError}",
            500,
            null,
            $userMessage,
            $troubleshootingSteps,
            ['database_type' => $databaseType]
        );
    }

    /**
     * Create exception for database connection failure with detailed information.
     */
    public static function connectionFailedWithDetails(string $databaseType, string $technicalError, array $context = [], array $details = []): self
    {
        $userMessage = match ($databaseType) {
            'mysql' => 'Unable to connect to MySQL database. Please verify your connection settings.',
            'sqlite' => 'Unable to access SQLite database file. Please check file permissions.',
            default => 'Database connection failed. Please check your configuration.',
        };

        // Use detailed troubleshooting if provided, otherwise fall back to basic steps
        $troubleshootingSteps = $details['troubleshooting'] ?? match ($databaseType) {
            'mysql' => [
                'Verify that MySQL server is running and accessible',
                'Check that the database name, username, and password are correct',
                'Ensure the MySQL user has proper permissions for the database',
                'Verify that the host and port are correct',
                'Check firewall settings if connecting to a remote database',
                'Test the connection using a MySQL client tool'
            ],
            'sqlite' => [
                'Ensure the database directory exists and is writable',
                'Check that the web server has read/write permissions to the database file',
                'Verify that the SQLite PHP extension is installed and enabled',
                'Check that there is sufficient disk space available',
                'Ensure the database path is correct in your configuration'
            ],
            default => [
                'Check your database configuration settings',
                'Verify that the database server is running',
                'Ensure proper permissions are set'
            ]
        };

        $exception = new self(
            "Database connection failed: {$technicalError}",
            500,
            null,
            $userMessage,
            $troubleshootingSteps,
            array_merge(['database_type' => $databaseType], $context)
        );

        // Add detailed information if provided
        if (!empty($details)) {
            $exception->details = $details;
        }

        return $exception;
    }
}