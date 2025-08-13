<?php

namespace App\Services;

use App\Exceptions\DatabaseSetupException;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

/**
 * Service for handling database setup operations during initial application setup.
 * 
 * This service provides functionality for:
 * - Database type detection
 * - SQLite database file creation and validation
 * - MySQL connection testing and validation
 * - Migration execution
 */
class DatabaseSetupService
{
    /**
     * Detect the configured database type from environment variables.
     * 
     * @return string The database type ('sqlite', 'mysql', etc.)
     */
    public function detectDatabaseType(): string
    {
        return config('database.default', 'sqlite');
    }

    /**
     * Validate the current database configuration.
     * 
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public function validateDatabaseConfig(): array
    {
        $databaseType = $this->detectDatabaseType();
        
        switch ($databaseType) {
            case 'sqlite':
                return $this->validateSQLiteConfig();
            case 'mysql':
                return $this->validateMySQLConfig();
            default:
                return [
                    'valid' => false,
                    'errors' => ["Unsupported database type: {$databaseType}"]
                ];
        }
    }

    /**
     * Initialize SQLite database file if it doesn't exist.
     * 
     * @return bool True if successful
     * @throws DatabaseSetupException If initialization fails
     */
    public function initializeSQLiteDatabase(): bool
    {
        $databasePath = config('database.connections.sqlite.database');
        
        if (!$databasePath) {
            throw DatabaseSetupException::invalidConfiguration(
                ['SQLite database path not configured'],
                'sqlite'
            );
        }

        try {
            // Create directory if it doesn't exist
            $databaseDir = dirname($databasePath);
            if (!File::exists($databaseDir)) {
                if (!File::makeDirectory($databaseDir, 0755, true)) {
                    throw DatabaseSetupException::permissionDenied($databaseDir, 'create directory');
                }
            }

            // Check directory permissions
            if (!is_writable($databaseDir)) {
                throw DatabaseSetupException::permissionDenied($databaseDir, 'write to directory');
            }

            // Create database file if it doesn't exist
            if (!File::exists($databasePath)) {
                if (!File::put($databasePath, '')) {
                    throw DatabaseSetupException::permissionDenied($databasePath, 'create database file');
                }
                
                if (!chmod($databasePath, 0644)) {
                    Log::warning('Could not set database file permissions', ['path' => $databasePath]);
                }
            }

            // Check database file permissions
            if (!is_writable($databasePath)) {
                throw DatabaseSetupException::permissionDenied($databasePath, 'write to database file');
            }

            // Test that we can connect to the database
            $pdo = new PDO("sqlite:{$databasePath}");
            $pdo->exec('SELECT 1');
            
            Log::info('SQLite database initialized successfully', ['path' => $databasePath]);
            return true;
            
        } catch (PDOException $e) {
            throw DatabaseSetupException::connectionFailed('sqlite', $e->getMessage(), [
                'path' => $databasePath,
                'pdo_error_code' => $e->getCode()
            ]);
        } catch (DatabaseSetupException $e) {
            throw $e;
        } catch (Exception $e) {
            throw DatabaseSetupException::initializationFailed('sqlite', $e->getMessage());
        }
    }

    /**
     * Test MySQL connection with the provided configuration.
     * 
     * @param array|null $config Optional configuration array, uses default if null
     * @return bool True if connection successful
     * @throws DatabaseSetupException If connection fails
     */
    public function testMySQLConnection(?array $config = null): bool
    {
        $config = $config ?? config('database.connections.mysql');
        
        // Validate required configuration fields
        $requiredFields = ['host', 'port', 'database', 'username'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            throw DatabaseSetupException::invalidConfiguration(
                array_map(fn($field) => "MySQL {$field} is required", $missingFields),
                'mysql'
            );
        }

        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);
            
            // Test the connection with a simple query
            $pdo->query('SELECT 1');
            
            // Check MySQL version compatibility
            $version = $pdo->query('SELECT VERSION()')->fetchColumn();
            if (version_compare($version, '5.7.0', '<')) {
                Log::warning('MySQL version may be incompatible', [
                    'version' => $version,
                    'recommended' => '5.7.0+'
                ]);
            }
            
            Log::info('MySQL connection test successful', [
                'host' => $config['host'],
                'database' => $config['database'],
                'version' => $version
            ]);
            return true;
            
        } catch (PDOException $e) {
            $context = [
                'host' => $config['host'],
                'database' => $config['database'],
                'port' => $config['port'],
                'pdo_error_code' => $e->getCode()
            ];
            
            throw DatabaseSetupException::connectionFailed('mysql', $e->getMessage(), $context);
        }
    }

    /**
     * Execute database migrations.
     * 
     * @return bool True if migrations successful
     * @throws DatabaseSetupException If migrations fail
     */
    public function runMigrations(): bool
    {
        try {
            // Clear any cached configuration
            Artisan::call('config:clear');
            
            // Check database connectivity before running migrations
            DB::connection()->getPdo();
            
            // Run migrations with detailed output capture
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            
            if ($exitCode === 0) {
                Log::info('Database migrations completed successfully', [
                    'output' => $output
                ]);
                return true;
            } else {
                throw DatabaseSetupException::migrationFailed(
                    "Migration command returned exit code {$exitCode}. Output: {$output}"
                );
            }
            
        } catch (PDOException $e) {
            throw DatabaseSetupException::connectionFailed(
                $this->detectDatabaseType(),
                $e->getMessage(),
                ['operation' => 'migration']
            );
        } catch (DatabaseSetupException $e) {
            throw $e;
        } catch (Exception $e) {
            throw DatabaseSetupException::migrationFailed($e->getMessage(), [
                'exception_type' => get_class($e)
            ]);
        }
    }

    /**
     * Get comprehensive database status information.
     * 
     * @return array Status information including connectivity, migrations, etc.
     */
    public function getDatabaseStatus(): array
    {
        $databaseType = $this->detectDatabaseType();
        $status = [
            'type' => $databaseType,
            'connected' => false,
            'migrations_run' => false,
            'tables_exist' => false,
            'errors' => []
        ];

        try {
            // Test basic connectivity
            if ($databaseType === 'sqlite') {
                $status['connected'] = $this->testSQLiteConnection();
            } elseif ($databaseType === 'mysql') {
                $status['connected'] = $this->testMySQLConnection();
            }

            if ($status['connected']) {
                // Check if migrations table exists
                $status['migrations_run'] = $this->checkMigrationsTable();
                
                // Check if essential tables exist
                $status['tables_exist'] = $this->checkEssentialTables();
            }
            
        } catch (Exception $e) {
            $status['errors'][] = $e->getMessage();
        }

        return $status;
    }

    /**
     * Validate SQLite configuration.
     * 
     * @return array Validation result
     */
    private function validateSQLiteConfig(): array
    {
        $errors = [];
        $databasePath = config('database.connections.sqlite.database');
        
        if (!$databasePath) {
            $errors[] = 'SQLite database path is not configured';
        } else {
            $databaseDir = dirname($databasePath);
            
            // Check if directory exists or can be created
            if (!File::exists($databaseDir) && !File::makeDirectory($databaseDir, 0755, true)) {
                $errors[] = "Cannot create database directory: {$databaseDir}";
            }
            
            // Check directory permissions
            if (File::exists($databaseDir) && !is_writable($databaseDir)) {
                $errors[] = "Database directory is not writable: {$databaseDir}";
            }
            
            // If database file exists, check if it's writable
            if (File::exists($databasePath) && !is_writable($databasePath)) {
                $errors[] = "Database file is not writable: {$databasePath}";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate MySQL configuration.
     * 
     * @return array Validation result
     */
    private function validateMySQLConfig(): array
    {
        $errors = [];
        $config = config('database.connections.mysql');
        
        $requiredFields = ['host', 'port', 'database', 'username'];
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                $errors[] = "MySQL {$field} is required";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Test SQLite connection.
     * 
     * @return bool True if connection successful
     */
    private function testSQLiteConnection(): bool
    {
        try {
            $databasePath = config('database.connections.sqlite.database');
            if (!$databasePath || !File::exists($databasePath)) {
                return false;
            }
            
            $pdo = new PDO("sqlite:{$databasePath}");
            $pdo->exec('SELECT 1');
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if migrations table exists.
     * 
     * @return bool True if migrations table exists
     */
    private function checkMigrationsTable(): bool
    {
        try {
            DB::table('migrations')->count();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if essential application tables exist.
     * 
     * @return bool True if essential tables exist
     */
    private function checkEssentialTables(): bool
    {
        $essentialTables = ['users', 'file_uploads', 'google_drive_tokens'];
        
        try {
            foreach ($essentialTables as $table) {
                DB::table($table)->count();
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}