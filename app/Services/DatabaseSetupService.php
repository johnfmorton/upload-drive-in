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
     * @return array Connection test result with detailed information
     * @throws DatabaseSetupException If connection fails
     */
    public function testMySQLConnection(?array $config = null): array
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

        $result = [
            'success' => false,
            'message' => '',
            'details' => [],
            'troubleshooting' => [],
            'hosting_instructions' => []
        ];

        try {
            // First, test basic connectivity without database name
            $this->testMySQLConnectivity($config, $result);
            
            // Then test database access
            $this->testMySQLDatabaseAccess($config, $result);
            
            // Test permissions
            $this->testMySQLPermissions($config, $result);
            
            // Check MySQL version compatibility
            $this->checkMySQLVersion($config, $result);
            
            $result['success'] = true;
            $result['message'] = 'Database connection successful!';
            
            Log::info('MySQL connection test successful', [
                'host' => $config['host'],
                'database' => $config['database'],
                'details' => $result['details']
            ]);
            
            return $result;
            
        } catch (PDOException $e) {
            $result['success'] = false;
            $result['message'] = $this->getMySQLErrorMessage($e);
            $result['troubleshooting'] = $this->getMySQLTroubleshootingSteps($e, $config);
            $result['hosting_instructions'] = $this->getMySQLHostingInstructions($config);
            
            $context = [
                'host' => $config['host'],
                'database' => $config['database'],
                'port' => $config['port'],
                'pdo_error_code' => $e->getCode(),
                'error_info' => $e->errorInfo ?? []
            ];
            
            Log::error('MySQL connection test failed', array_merge($context, [
                'error_message' => $e->getMessage(),
                'troubleshooting_provided' => count($result['troubleshooting'])
            ]));
            
            throw DatabaseSetupException::connectionFailedWithDetails('mysql', $e->getMessage(), $context, $result);
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
                $result = $this->testMySQLConnection();
                $status['connected'] = $result['success'];
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

    /**
     * Test basic MySQL connectivity without database selection.
     * 
     * @param array $config Database configuration
     * @param array &$result Result array to populate
     * @throws PDOException If connection fails
     */
    private function testMySQLConnectivity(array $config, array &$result): void
    {
        // Test connection to MySQL server without selecting database
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
        
        $result['details']['server_connection'] = 'Successfully connected to MySQL server';
    }

    /**
     * Test MySQL database access.
     * 
     * @param array $config Database configuration
     * @param array &$result Result array to populate
     * @throws PDOException If database access fails
     */
    private function testMySQLDatabaseAccess(array $config, array &$result): void
    {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
        
        // Test basic query
        $pdo->query('SELECT 1');
        
        $result['details']['database_access'] = "Successfully accessed database '{$config['database']}'";
    }

    /**
     * Test MySQL user permissions.
     * 
     * @param array $config Database configuration
     * @param array &$result Result array to populate
     */
    private function testMySQLPermissions(array $config, array &$result): void
    {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ]);

        $permissions = [];
        
        try {
            // Test SELECT permission
            $pdo->query('SELECT 1');
            $permissions[] = 'SELECT';
        } catch (PDOException $e) {
            // SELECT permission missing
        }

        try {
            // Test CREATE permission by attempting to create a temporary table
            $pdo->exec('CREATE TEMPORARY TABLE test_permissions_check (id INT)');
            $permissions[] = 'CREATE';
            
            // Test INSERT permission
            $pdo->exec('INSERT INTO test_permissions_check (id) VALUES (1)');
            $permissions[] = 'INSERT';
            
            // Test UPDATE permission
            $pdo->exec('UPDATE test_permissions_check SET id = 2 WHERE id = 1');
            $permissions[] = 'UPDATE';
            
            // Test DELETE permission
            $pdo->exec('DELETE FROM test_permissions_check WHERE id = 2');
            $permissions[] = 'DELETE';
            
            // Test DROP permission
            $pdo->exec('DROP TEMPORARY TABLE test_permissions_check');
            $permissions[] = 'DROP';
            
        } catch (PDOException $e) {
            // Some permissions are missing, but we'll continue
        }

        try {
            // Test ALTER permission (needed for migrations)
            $pdo->exec('CREATE TEMPORARY TABLE test_alter_check (id INT)');
            $pdo->exec('ALTER TABLE test_alter_check ADD COLUMN name VARCHAR(255)');
            $pdo->exec('DROP TEMPORARY TABLE test_alter_check');
            $permissions[] = 'ALTER';
        } catch (PDOException $e) {
            // ALTER permission missing
        }

        $result['details']['permissions'] = 'User has permissions: ' . implode(', ', $permissions);
        
        $requiredPermissions = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'ALTER', 'DROP'];
        $missingPermissions = array_diff($requiredPermissions, $permissions);
        
        if (!empty($missingPermissions)) {
            $result['details']['missing_permissions'] = 'Missing permissions: ' . implode(', ', $missingPermissions);
        }
    }

    /**
     * Check MySQL version compatibility.
     * 
     * @param array $config Database configuration
     * @param array &$result Result array to populate
     */
    private function checkMySQLVersion(array $config, array &$result): void
    {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ]);
        
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        $result['details']['mysql_version'] = "MySQL version: {$version}";
        
        if (version_compare($version, '5.7.0', '<')) {
            $result['details']['version_warning'] = 'MySQL version is older than recommended (5.7.0+)';
        } else {
            $result['details']['version_status'] = 'MySQL version is compatible';
        }
    }

    /**
     * Get user-friendly MySQL error message.
     * 
     * @param PDOException $e The PDO exception
     * @return string User-friendly error message
     */
    private function getMySQLErrorMessage(PDOException $e): string
    {
        $errorCode = $e->getCode();
        $message = $e->getMessage();
        
        return match ($errorCode) {
            1045 => 'Access denied: Invalid username or password',
            1049 => 'Database does not exist',
            1044 => 'Access denied: User does not have permission to access this database',
            2002 => 'Connection failed: Cannot connect to MySQL server (server may be down or unreachable)',
            2003 => 'Connection failed: Cannot connect to MySQL server on specified port',
            2005 => 'Connection failed: Unknown MySQL server host',
            2006 => 'Connection lost: MySQL server has gone away',
            2013 => 'Connection timeout: Lost connection to MySQL server during query',
            1040 => 'Too many connections: MySQL server has reached maximum connection limit',
            1203 => 'Connection limit reached: User has exceeded maximum connections',
            default => "Database connection failed: {$message}"
        };
    }

    /**
     * Get MySQL troubleshooting steps based on error.
     * 
     * @param PDOException $e The PDO exception
     * @param array $config Database configuration
     * @return array Troubleshooting steps
     */
    private function getMySQLTroubleshootingSteps(PDOException $e, array $config): array
    {
        $errorCode = $e->getCode();
        
        $commonSteps = [
            'Verify that MySQL server is running and accessible',
            'Check firewall settings if connecting to a remote database',
            'Ensure the MySQL service is started on the server'
        ];
        
        $specificSteps = match ($errorCode) {
            1045 => [
                'Double-check the username and password',
                'Verify the user exists in MySQL: SELECT User FROM mysql.user;',
                'Check if the user has the correct password',
                'Try connecting with a MySQL client using the same credentials'
            ],
            1049 => [
                "Create the database '{$config['database']}' if it doesn't exist",
                "Run: CREATE DATABASE `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
                'Verify database name spelling and case sensitivity',
                'Check if you have permission to access the database'
            ],
            1044 => [
                "Grant permissions to user '{$config['username']}' for database '{$config['database']}'",
                "Run: GRANT ALL PRIVILEGES ON `{$config['database']}`.* TO '{$config['username']}'@'%';",
                'Run: FLUSH PRIVILEGES;',
                'Contact your database administrator if you cannot grant permissions'
            ],
            2002, 2003 => [
                "Verify MySQL server is running on {$config['host']}:{$config['port']}",
                'Check if the host address is correct',
                'Verify the port number (default MySQL port is 3306)',
                'Test connectivity: telnet ' . $config['host'] . ' ' . $config['port']
            ],
            2005 => [
                "Verify the hostname '{$config['host']}' is correct",
                'Check DNS resolution if using a domain name',
                'Try using an IP address instead of hostname',
                'Verify network connectivity to the database server'
            ],
            default => [
                'Check MySQL server logs for additional error information',
                'Verify all connection parameters are correct',
                'Test the connection using a MySQL client tool',
                'Contact your hosting provider or database administrator'
            ]
        };
        
        return array_merge($specificSteps, $commonSteps);
    }

    /**
     * Get database creation instructions for common hosting providers.
     * 
     * @param array $config Database configuration
     * @return array Hosting-specific instructions
     */
    private function getMySQLHostingInstructions(array $config): array
    {
        return [
            'cpanel' => [
                'title' => 'cPanel Instructions',
                'steps' => [
                    '1. Log into your cPanel account',
                    '2. Navigate to "MySQL Databases" in the Databases section',
                    '3. Create a new database with name: ' . $config['database'],
                    '4. Create a new MySQL user with username: ' . $config['username'],
                    '5. Add the user to the database with "All Privileges"',
                    '6. Note the full database name (usually prefixed with your account name)',
                    '7. Use the full database name in your configuration'
                ]
            ],
            'plesk' => [
                'title' => 'Plesk Instructions',
                'steps' => [
                    '1. Log into your Plesk control panel',
                    '2. Go to "Databases" in the left sidebar',
                    '3. Click "Add Database"',
                    '4. Enter database name: ' . $config['database'],
                    '5. Create a database user with username: ' . $config['username'],
                    '6. Set a secure password for the user',
                    '7. Grant all privileges to the user for this database'
                ]
            ],
            'shared_hosting' => [
                'title' => 'Shared Hosting (General)',
                'steps' => [
                    '1. Access your hosting control panel',
                    '2. Look for "MySQL Databases" or "Database Management"',
                    '3. Create database: ' . $config['database'],
                    '4. Create user: ' . $config['username'],
                    '5. Assign user to database with full privileges',
                    '6. Note any prefix added to database/username',
                    '7. Use the full names in your configuration'
                ]
            ],
            'vps_dedicated' => [
                'title' => 'VPS/Dedicated Server',
                'steps' => [
                    '1. Connect to your server via SSH',
                    '2. Log into MySQL as root: mysql -u root -p',
                    '3. Create database: CREATE DATABASE `' . $config['database'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;',
                    '4. Create user: CREATE USER \'' . $config['username'] . '\'@\'localhost\' IDENTIFIED BY \'your_password\';',
                    '5. Grant privileges: GRANT ALL PRIVILEGES ON `' . $config['database'] . '`.* TO \'' . $config['username'] . '\'@\'localhost\';',
                    '6. Reload privileges: FLUSH PRIVILEGES;',
                    '7. Exit MySQL: EXIT;'
                ]
            ],
            'cloud_providers' => [
                'title' => 'Cloud Database Services',
                'steps' => [
                    '1. AWS RDS: Create RDS instance, note endpoint and port',
                    '2. Google Cloud SQL: Create instance, configure authorized networks',
                    '3. Azure Database: Create server, configure firewall rules',
                    '4. DigitalOcean: Create managed database cluster',
                    '5. Use the provided connection details in your configuration',
                    '6. Ensure your server IP is whitelisted in firewall rules'
                ]
            ]
        ];
    }

    /**
     * Get field validation hints for database configuration.
     * 
     * @return array Field hints and examples
     */
    public function getFieldHints(): array
    {
        return [
            'mysql_host' => [
                'hint' => 'The hostname or IP address of your MySQL server',
                'examples' => ['localhost', '127.0.0.1', 'mysql.example.com', 'db.hosting-provider.com'],
                'common_values' => [
                    'local' => 'localhost',
                    'docker' => 'mysql',
                    'shared_hosting' => 'localhost'
                ]
            ],
            'mysql_port' => [
                'hint' => 'The port number MySQL is running on',
                'examples' => ['3306', '3307', '33060'],
                'common_values' => [
                    'default' => '3306',
                    'alternative' => '3307'
                ]
            ],
            'mysql_database' => [
                'hint' => 'The name of your database (letters, numbers, and underscores only)',
                'examples' => ['upload_drive_in', 'myapp_production', 'website_db'],
                'validation' => 'Only letters, numbers, and underscores allowed. Maximum 64 characters.'
            ],
            'mysql_username' => [
                'hint' => 'MySQL user with access to the database',
                'examples' => ['root', 'app_user', 'website_admin'],
                'common_values' => [
                    'local' => 'root',
                    'production' => 'app_user'
                ]
            ],
            'mysql_password' => [
                'hint' => 'Password for the MySQL user (leave empty if no password)',
                'security_note' => 'Use a strong password for production environments'
            ]
        ];
    }

    /**
     * Get common database configuration templates.
     * 
     * @return array Configuration templates for different environments
     */
    public function getConfigurationTemplates(): array
    {
        return [
            'local_development' => [
                'name' => 'Local Development',
                'description' => 'Typical local development setup with XAMPP, WAMP, or MAMP',
                'config' => [
                    'host' => 'localhost',
                    'port' => '3306',
                    'username' => 'root',
                    'password' => '',
                    'database' => 'upload_drive_in'
                ],
                'notes' => [
                    'Default MySQL installation usually has no root password',
                    'Database name can be anything you prefer',
                    'Make sure MySQL service is running'
                ]
            ],
            'shared_hosting' => [
                'name' => 'Shared Hosting',
                'description' => 'Common shared hosting providers (cPanel, Plesk)',
                'config' => [
                    'host' => 'localhost',
                    'port' => '3306',
                    'username' => 'username_dbuser',
                    'password' => 'your_password',
                    'database' => 'username_dbname'
                ],
                'notes' => [
                    'Database and username are usually prefixed with your account name',
                    'Check your hosting control panel for exact names',
                    'Password is set when creating the database user'
                ]
            ],
            'cloud_database' => [
                'name' => 'Cloud Database',
                'description' => 'Cloud database services (AWS RDS, Google Cloud SQL)',
                'config' => [
                    'host' => 'your-instance.region.rds.amazonaws.com',
                    'port' => '3306',
                    'username' => 'admin',
                    'password' => 'your_secure_password',
                    'database' => 'upload_drive_in'
                ],
                'notes' => [
                    'Use the endpoint provided by your cloud provider',
                    'Ensure your server IP is whitelisted',
                    'Use SSL connections when available'
                ]
            ]
        ];
    }

    /**
     * Validate field input and provide specific feedback.
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @return array Validation result with feedback
     */
    public function validateField(string $field, $value): array
    {
        $result = [
            'valid' => true,
            'message' => '',
            'suggestion' => ''
        ];

        switch ($field) {
            case 'mysql_host':
                if (empty($value)) {
                    $result['valid'] = false;
                    $result['message'] = 'Host is required';
                    $result['suggestion'] = 'Try "localhost" for local development';
                } elseif (strlen($value) > 255) {
                    $result['valid'] = false;
                    $result['message'] = 'Host must not exceed 255 characters';
                } elseif (!$this->isValidHostname($value)) {
                    $result['valid'] = false;
                    $result['message'] = 'Invalid hostname format';
                    $result['suggestion'] = 'Use a valid hostname or IP address';
                }
                break;

            case 'mysql_port':
                $port = (int) $value;
                if (empty($value)) {
                    $result['valid'] = false;
                    $result['message'] = 'Port is required';
                    $result['suggestion'] = 'Default MySQL port is 3306';
                } elseif ($port < 1 || $port > 65535) {
                    $result['valid'] = false;
                    $result['message'] = 'Port must be between 1 and 65535';
                    $result['suggestion'] = 'Most MySQL servers use port 3306';
                }
                break;

            case 'mysql_database':
                if (empty($value)) {
                    $result['valid'] = false;
                    $result['message'] = 'Database name is required';
                    $result['suggestion'] = 'Try "upload_drive_in" or similar';
                } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
                    $result['valid'] = false;
                    $result['message'] = 'Only letters, numbers, and underscores allowed';
                    $result['suggestion'] = 'Remove spaces and special characters';
                } elseif (strlen($value) > 64) {
                    $result['valid'] = false;
                    $result['message'] = 'Database name must not exceed 64 characters';
                }
                break;

            case 'mysql_username':
                if (empty($value)) {
                    $result['valid'] = false;
                    $result['message'] = 'Username is required';
                    $result['suggestion'] = 'Try "root" for local development';
                } elseif (strlen($value) > 32) {
                    $result['valid'] = false;
                    $result['message'] = 'Username must not exceed 32 characters';
                }
                break;

            case 'mysql_password':
                if (strlen($value) > 255) {
                    $result['valid'] = false;
                    $result['message'] = 'Password must not exceed 255 characters';
                }
                break;
        }

        return $result;
    }

    /**
     * Check if a hostname is valid.
     * 
     * @param string $hostname
     * @return bool
     */
    private function isValidHostname(string $hostname): bool
    {
        // Allow localhost
        if ($hostname === 'localhost') {
            return true;
        }

        // Check if it's a valid IP address
        if (filter_var($hostname, FILTER_VALIDATE_IP)) {
            return true;
        }

        // Check if it's a valid hostname
        return filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }
}
