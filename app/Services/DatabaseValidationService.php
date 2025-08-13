<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

/**
 * Service for handling database validation and providing troubleshooting guidance.
 * 
 * This service provides detailed validation and error handling for database
 * configuration during the setup process, with specific guidance for common issues.
 */
class DatabaseValidationService
{
    /**
     * Validate MySQL connection parameters with detailed error reporting.
     *
     * @param array $config MySQL configuration array
     * @return array Validation result with detailed errors and suggestions
     */
    public function validateMySQLConnection(array $config): array
    {
        $result = [
            'valid' => false,
            'errors' => [],
            'suggestions' => []
        ];

        // Validate required parameters
        $requiredParams = ['host', 'port', 'database', 'username'];
        foreach ($requiredParams as $param) {
            if (empty($config[$param])) {
                $result['errors'][] = "Missing required parameter: {$param}";
                $result['suggestions'][] = $this->getMySQLParameterSuggestion($param);
            }
        }

        if (!empty($result['errors'])) {
            return $result;
        }

        // Test connection with detailed error handling
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
            $pdo = new PDO($dsn, $config['username'], $config['password'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);

            // Test with a simple query
            $pdo->query('SELECT 1');
            $result['valid'] = true;

            Log::info('MySQL connection validation successful', [
                'host' => $config['host'],
                'database' => $config['database']
            ]);

        } catch (PDOException $e) {
            $result['errors'][] = $this->parseMySQLError($e);
            $result['suggestions'] = $this->getMySQLTroubleshootingSuggestions($e);
            
            Log::warning('MySQL connection validation failed', [
                'error' => $e->getMessage(),
                'config' => array_merge($config, ['password' => '[REDACTED]'])
            ]);
        }

        return $result;
    }

    /**
     * Validate SQLite database path and permissions with detailed error reporting.
     *
     * @param string $databasePath Path to SQLite database file
     * @return array Validation result with detailed errors and suggestions
     */
    public function validateSQLiteDatabase(string $databasePath): array
    {
        $result = [
            'valid' => false,
            'errors' => [],
            'suggestions' => []
        ];

        // Validate path
        if (empty($databasePath)) {
            $result['errors'][] = 'SQLite database path cannot be empty';
            $result['suggestions'][] = 'Provide a valid file path for the SQLite database';
            return $result;
        }

        $databaseDir = dirname($databasePath);

        // Check directory existence and permissions
        if (!File::exists($databaseDir)) {
            try {
                File::makeDirectory($databaseDir, 0755, true);
            } catch (Exception $e) {
                $result['errors'][] = "Cannot create database directory: {$databaseDir}";
                $result['suggestions'][] = 'Ensure the parent directory has write permissions';
                $result['suggestions'][] = 'Check that the web server user has permission to create directories';
                return $result;
            }
        }

        if (!is_writable($databaseDir)) {
            $result['errors'][] = "Database directory is not writable: {$databaseDir}";
            $result['suggestions'][] = "Set directory permissions: chmod 755 {$databaseDir}";
            $result['suggestions'][] = 'Ensure the web server user owns the directory';
            return $result;
        }

        // Check database file
        if (File::exists($databasePath)) {
            if (!is_writable($databasePath)) {
                $result['errors'][] = "Database file is not writable: {$databasePath}";
                $result['suggestions'][] = "Set file permissions: chmod 644 {$databasePath}";
                $result['suggestions'][] = 'Ensure the web server user owns the file';
                return $result;
            }
        } else {
            // Try to create the database file
            try {
                File::put($databasePath, '');
                chmod($databasePath, 0644);
            } catch (Exception $e) {
                $result['errors'][] = "Cannot create database file: {$databasePath}";
                $result['suggestions'][] = 'Check directory permissions and disk space';
                $result['suggestions'][] = 'Ensure the web server user has write access';
                return $result;
            }
        }

        // Test SQLite connection
        try {
            $pdo = new PDO("sqlite:{$databasePath}");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('SELECT 1');
            
            $result['valid'] = true;
            
            Log::info('SQLite database validation successful', ['path' => $databasePath]);
            
        } catch (PDOException $e) {
            $result['errors'][] = "SQLite connection failed: " . $e->getMessage();
            $result['suggestions'][] = 'Verify the database file is not corrupted';
            $result['suggestions'][] = 'Check that SQLite extension is installed in PHP';
            
            Log::warning('SQLite database validation failed', [
                'error' => $e->getMessage(),
                'path' => $databasePath
            ]);
        }

        return $result;
    }

    /**
     * Test database connectivity and provide detailed status.
     *
     * @param string $type Database type ('mysql' or 'sqlite')
     * @param array $config Database configuration
     * @return array Connectivity test result
     */
    public function testDatabaseConnectivity(string $type, array $config): array
    {
        switch ($type) {
            case 'mysql':
                return $this->validateMySQLConnection($config);
            case 'sqlite':
                $path = $config['database'] ?? database_path('database.sqlite');
                return $this->validateSQLiteDatabase($path);
            default:
                return [
                    'valid' => false,
                    'errors' => ["Unsupported database type: {$type}"],
                    'suggestions' => ['Use either MySQL or SQLite database']
                ];
        }
    }

    /**
     * Get troubleshooting suggestions for common database setup issues.
     *
     * @param string $type Database type
     * @param array $errors Array of error messages
     * @return array Array of troubleshooting suggestions
     */
    public function getTroubleshootingSuggestions(string $type, array $errors): array
    {
        $suggestions = [];

        if ($type === 'mysql') {
            $suggestions[] = 'Verify MySQL server is running';
            $suggestions[] = 'Check firewall settings for MySQL port (default 3306)';
            $suggestions[] = 'Confirm database exists and user has proper permissions';
            $suggestions[] = 'Test connection using mysql command line client';
        } elseif ($type === 'sqlite') {
            $suggestions[] = 'Ensure PHP SQLite extension is installed';
            $suggestions[] = 'Check file and directory permissions';
            $suggestions[] = 'Verify sufficient disk space is available';
            $suggestions[] = 'Confirm the web server user has write access';
        }

        // Add error-specific suggestions
        foreach ($errors as $error) {
            if (str_contains($error, 'Connection refused')) {
                $suggestions[] = 'MySQL server may not be running or accessible';
            } elseif (str_contains($error, 'Access denied')) {
                $suggestions[] = 'Check MySQL username and password';
                $suggestions[] = 'Verify user has permissions for the specified database';
            } elseif (str_contains($error, 'Unknown database')) {
                $suggestions[] = 'Create the database first or check the database name';
            } elseif (str_contains($error, 'not writable')) {
                $suggestions[] = 'Fix file/directory permissions using chmod';
                $suggestions[] = 'Ensure web server user owns the files';
            }
        }

        return array_unique($suggestions);
    }

    /**
     * Parse MySQL PDO exception and return user-friendly error message.
     *
     * @param PDOException $e The PDO exception
     * @return string User-friendly error message
     */
    private function parseMySQLError(PDOException $e): string
    {
        $message = $e->getMessage();
        
        if (str_contains($message, 'Connection refused')) {
            return 'Cannot connect to MySQL server. Server may be down or unreachable.';
        } elseif (str_contains($message, 'Access denied')) {
            return 'MySQL access denied. Check username and password.';
        } elseif (str_contains($message, 'Unknown database')) {
            return 'MySQL database does not exist. Please create the database first.';
        } elseif (str_contains($message, 'timeout')) {
            return 'Connection to MySQL server timed out. Check network connectivity.';
        } else {
            return "MySQL connection error: {$message}";
        }
    }

    /**
     * Get troubleshooting suggestions for MySQL errors.
     *
     * @param PDOException $e The PDO exception
     * @return array Array of suggestions
     */
    private function getMySQLTroubleshootingSuggestions(PDOException $e): array
    {
        $message = $e->getMessage();
        $suggestions = [];

        if (str_contains($message, 'Connection refused')) {
            $suggestions[] = 'Start MySQL server: sudo systemctl start mysql';
            $suggestions[] = 'Check if MySQL is running: sudo systemctl status mysql';
            $suggestions[] = 'Verify MySQL port is open: netstat -tlnp | grep :3306';
        } elseif (str_contains($message, 'Access denied')) {
            $suggestions[] = 'Verify username and password are correct';
            $suggestions[] = 'Grant permissions: GRANT ALL ON database.* TO \'user\'@\'host\'';
            $suggestions[] = 'Check user exists: SELECT User FROM mysql.user WHERE User=\'username\'';
        } elseif (str_contains($message, 'Unknown database')) {
            $suggestions[] = 'Create database: CREATE DATABASE database_name';
            $suggestions[] = 'List databases: SHOW DATABASES';
            $suggestions[] = 'Check database name spelling';
        }

        return $suggestions;
    }

    /**
     * Get parameter-specific suggestions for MySQL configuration.
     *
     * @param string $param Parameter name
     * @return string Suggestion for the parameter
     */
    private function getMySQLParameterSuggestion(string $param): string
    {
        switch ($param) {
            case 'host':
                return 'Use localhost for local MySQL server, or IP address for remote server';
            case 'port':
                return 'Default MySQL port is 3306';
            case 'database':
                return 'Specify the name of the MySQL database to use';
            case 'username':
                return 'Provide a MySQL user with appropriate permissions';
            default:
                return "Configure the {$param} parameter";
        }
    }
}