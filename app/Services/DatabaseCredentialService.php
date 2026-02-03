<?php

namespace App\Services;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDOException;

/**
 * Service for checking database credentials and providing detailed feedback.
 *
 * This service provides specific feedback for three scenarios:
 * 1. No database credentials configured
 * 2. Partial database credentials configured
 * 3. Full credentials configured but connection fails
 */
class DatabaseCredentialService
{
    /**
     * Required database environment variables for different connection types.
     */
    private const REQUIRED_FIELDS = [
        'mysql' => ['DB_CONNECTION', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME'],
        'mariadb' => ['DB_CONNECTION', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME'],
        'pgsql' => ['DB_CONNECTION', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME'],
        'sqlite' => ['DB_CONNECTION'],
    ];

    /**
     * Get a database config value by its env variable name.
     * Uses config() instead of env() for cached config compatibility in production.
     *
     * @param  string  $envName  The environment variable name (e.g., 'DB_HOST')
     * @return mixed The config value
     */
    private function getDbConfigValue(string $envName): mixed
    {
        $connection = Config::get('database.default');

        return match ($envName) {
            'DB_CONNECTION' => $connection,
            'DB_HOST' => Config::get("database.connections.{$connection}.host"),
            'DB_PORT' => Config::get("database.connections.{$connection}.port"),
            'DB_DATABASE' => Config::get("database.connections.{$connection}.database"),
            'DB_USERNAME' => Config::get("database.connections.{$connection}.username"),
            'DB_PASSWORD' => Config::get("database.connections.{$connection}.password"),
            default => null,
        };
    }

    /**
     * Check database credential status and return detailed feedback.
     *
     * @return array Status information with specific feedback
     */
    public function checkDatabaseCredentials(): array
    {
        try {
            // Get current database connection type using config() for cached config compatibility
            $connection = $this->getDbConfigValue('DB_CONNECTION');

            // Scenario 1: No database credentials at all
            if (empty($connection)) {
                return $this->buildResponse(
                    'incomplete',
                    'No database credentials configured',
                    'Please add database credentials to your .env file',
                    [
                        'scenario' => 'no_credentials',
                        'missing_fields' => ['DB_CONNECTION', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'],
                        'required_action' => 'Add all database configuration fields to .env file',
                    ]
                );
            }

            // Check if connection type is supported
            if (! isset(self::REQUIRED_FIELDS[$connection])) {
                return $this->buildResponse(
                    'error',
                    'Unsupported database connection type',
                    "Database connection type '{$connection}' is not supported",
                    [
                        'scenario' => 'unsupported_connection',
                        'connection_type' => $connection,
                        'supported_types' => array_keys(self::REQUIRED_FIELDS),
                    ]
                );
            }

            // Check for missing required fields
            $requiredFields = self::REQUIRED_FIELDS[$connection];
            $missingFields = $this->getMissingFields($requiredFields);

            // Scenario 2: Partial database credentials
            if (! empty($missingFields)) {
                return $this->buildResponse(
                    'incomplete',
                    'Partial database credentials configured',
                    'Some required database fields are missing from your .env file',
                    [
                        'scenario' => 'partial_credentials',
                        'connection_type' => $connection,
                        'missing_fields' => $missingFields,
                        'configured_fields' => $this->getConfiguredFields($requiredFields),
                        'required_action' => 'Add the missing database fields to your .env file',
                    ]
                );
            }

            // All required fields are present, now test the connection
            // Scenario 3: Full credentials but connection fails
            $connectionResult = $this->testDatabaseConnection();

            if ($connectionResult['success']) {
                return $this->buildResponse(
                    'completed',
                    'Database connection successful',
                    'Database credentials are valid and connection is working',
                    [
                        'scenario' => 'connection_successful',
                        'connection_type' => $connection,
                        'host' => $this->getDbConfigValue('DB_HOST'),
                        'database' => $this->getDbConfigValue('DB_DATABASE'),
                        'username' => $this->getDbConfigValue('DB_USERNAME'),
                    ]
                );
            } else {
                return $this->buildResponse(
                    'error',
                    'Database connection failed',
                    'Database credentials are configured but connection failed',
                    [
                        'scenario' => 'connection_failed',
                        'connection_type' => $connection,
                        'host' => $this->getDbConfigValue('DB_HOST'),
                        'database' => $this->getDbConfigValue('DB_DATABASE'),
                        'username' => $this->getDbConfigValue('DB_USERNAME'),
                        'error_message' => $connectionResult['error'],
                        'error_type' => $connectionResult['error_type'],
                        'required_action' => 'Verify database credentials and ensure database server is running',
                    ]
                );
            }

        } catch (Exception $e) {
            Log::error('Error checking database credentials', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->buildResponse(
                'error',
                'Error checking database credentials',
                'An unexpected error occurred while checking database credentials',
                [
                    'scenario' => 'check_error',
                    'error_message' => $e->getMessage(),
                    'required_action' => 'Check application logs for more details',
                ]
            );
        }
    }

    /**
     * Get missing required database fields.
     *
     * @param  array  $requiredFields  List of required field names
     * @return array List of missing field names
     */
    private function getMissingFields(array $requiredFields): array
    {
        $missingFields = [];

        foreach ($requiredFields as $field) {
            $value = $this->getDbConfigValue($field);
            if (empty($value)) {
                $missingFields[] = $field;
            }
        }

        // For non-SQLite connections, also check DB_PASSWORD
        $connection = $this->getDbConfigValue('DB_CONNECTION');
        if ($connection !== 'sqlite' && empty($this->getDbConfigValue('DB_PASSWORD'))) {
            $missingFields[] = 'DB_PASSWORD';
        }

        return $missingFields;
    }

    /**
     * Get configured database fields.
     *
     * @param  array  $requiredFields  List of required field names
     * @return array List of configured field names
     */
    private function getConfiguredFields(array $requiredFields): array
    {
        $configuredFields = [];

        foreach ($requiredFields as $field) {
            $value = $this->getDbConfigValue($field);
            if (! empty($value)) {
                $configuredFields[] = $field;
            }
        }

        // For non-SQLite connections, also check DB_PASSWORD
        $connection = $this->getDbConfigValue('DB_CONNECTION');
        if ($connection !== 'sqlite' && ! empty($this->getDbConfigValue('DB_PASSWORD'))) {
            $configuredFields[] = 'DB_PASSWORD';
        }

        return $configuredFields;
    }

    /**
     * Test database connection with timeout and detailed error handling.
     *
     * @return array Connection test result
     */
    private function testDatabaseConnection(): array
    {
        try {
            // Set a reasonable timeout for the connection test
            $originalTimeout = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', 5);

            // Test PDO connection
            $pdo = DB::connection()->getPdo();

            // Test a simple query
            DB::select('SELECT 1 as test');

            // Restore original timeout
            ini_set('default_socket_timeout', $originalTimeout);

            Log::info('Database connection test successful', [
                'connection' => $this->getDbConfigValue('DB_CONNECTION'),
                'host' => $this->getDbConfigValue('DB_HOST'),
                'database' => $this->getDbConfigValue('DB_DATABASE'),
            ]);

            return [
                'success' => true,
                'error' => null,
                'error_type' => null,
            ];

        } catch (PDOException $e) {
            // Restore original timeout
            if (isset($originalTimeout)) {
                ini_set('default_socket_timeout', $originalTimeout);
            }

            Log::warning('Database PDO connection failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'connection' => $this->getDbConfigValue('DB_CONNECTION'),
                'host' => $this->getDbConfigValue('DB_HOST'),
                'database' => $this->getDbConfigValue('DB_DATABASE'),
            ]);

            return [
                'success' => false,
                'error' => $this->sanitizeErrorMessage($e->getMessage()),
                'error_type' => 'PDO_ERROR',
            ];

        } catch (QueryException $e) {
            // Restore original timeout
            if (isset($originalTimeout)) {
                ini_set('default_socket_timeout', $originalTimeout);
            }

            Log::warning('Database query failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'connection' => $this->getDbConfigValue('DB_CONNECTION'),
                'host' => $this->getDbConfigValue('DB_HOST'),
                'database' => $this->getDbConfigValue('DB_DATABASE'),
            ]);

            return [
                'success' => false,
                'error' => $this->sanitizeErrorMessage($e->getMessage()),
                'error_type' => 'QUERY_ERROR',
            ];

        } catch (Exception $e) {
            // Restore original timeout
            if (isset($originalTimeout)) {
                ini_set('default_socket_timeout', $originalTimeout);
            }

            Log::error('Database connection test failed with unexpected error', [
                'error' => $e->getMessage(),
                'connection' => $this->getDbConfigValue('DB_CONNECTION'),
                'host' => $this->getDbConfigValue('DB_HOST'),
                'database' => $this->getDbConfigValue('DB_DATABASE'),
            ]);

            return [
                'success' => false,
                'error' => $this->sanitizeErrorMessage($e->getMessage()),
                'error_type' => 'GENERAL_ERROR',
            ];
        }
    }

    /**
     * Sanitize error messages to remove sensitive information.
     *
     * @param  string  $errorMessage  Raw error message
     * @return string Sanitized error message
     */
    private function sanitizeErrorMessage(string $errorMessage): string
    {
        // Remove password information from error messages
        $sanitized = preg_replace('/password[\'"]?\s*[:=]\s*[\'"]?[^\'"\s,)]+/i', 'password=***', $errorMessage);

        // Remove other potentially sensitive information
        $sanitized = preg_replace('/IDENTIFIED BY [\'"][^\'"]+[\'"]/', 'IDENTIFIED BY ***', $sanitized);

        return $sanitized;
    }

    /**
     * Build standardized response array.
     *
     * @param  string  $status  Status code (completed, incomplete, error)
     * @param  string  $message  Short status message
     * @param  string  $details  Detailed description
     * @param  array  $metadata  Additional metadata
     * @return array Standardized response
     */
    private function buildResponse(string $status, string $message, string $details, array $metadata = []): array
    {
        return [
            'status' => $status,
            'message' => $message,
            'details' => $details,
            'metadata' => array_merge([
                'checked_at' => now()->toISOString(),
                'service' => 'DatabaseCredentialService',
            ], $metadata),
        ];
    }

    /**
     * Get user-friendly instructions for fixing database configuration.
     *
     * @param  array  $credentialStatus  Result from checkDatabaseCredentials()
     * @return array Instructions for fixing the issue
     */
    public function getFixInstructions(array $credentialStatus): array
    {
        $scenario = $credentialStatus['metadata']['scenario'] ?? 'unknown';

        switch ($scenario) {
            case 'no_credentials':
                return [
                    'title' => 'Add Database Credentials',
                    'steps' => [
                        'Open your .env file in the project root directory',
                        'Add the following database configuration lines:',
                        'DB_CONNECTION=mysql (or mariadb, pgsql, sqlite)',
                        'DB_HOST=127.0.0.1 (your database server host)',
                        'DB_PORT=3306 (your database server port)',
                        'DB_DATABASE=your_database_name',
                        'DB_USERNAME=your_database_username',
                        'DB_PASSWORD=your_database_password',
                        'Save the .env file and refresh this page',
                    ],
                    'example' => $this->getExampleConfiguration(),
                ];

            case 'partial_credentials':
                $missingFields = $credentialStatus['metadata']['missing_fields'] ?? [];

                return [
                    'title' => 'Complete Database Configuration',
                    'steps' => [
                        'Open your .env file in the project root directory',
                        'Add or uncomment the following missing fields:',
                        ...array_map(fn ($field) => "- {$field}=your_value_here", $missingFields),
                        'Save the .env file and refresh this page',
                    ],
                    'missing_fields' => $missingFields,
                    'example' => $this->getExampleConfiguration(),
                ];

            case 'connection_failed':
                return [
                    'title' => 'Fix Database Connection',
                    'steps' => [
                        'Verify your database server is running',
                        'Check that the database name exists on your server',
                        'Verify the username and password are correct',
                        'Ensure the database host and port are accessible',
                        'Check firewall settings if using a remote database',
                        'Test the connection using a database client tool',
                        'Update your .env file with correct credentials',
                        'Refresh this page to test again',
                    ],
                    'error_details' => [
                        'error_message' => $credentialStatus['metadata']['error_message'] ?? 'Unknown error',
                        'error_type' => $credentialStatus['metadata']['error_type'] ?? 'Unknown',
                    ],
                ];

            default:
                return [
                    'title' => 'Database Configuration Help',
                    'steps' => [
                        'Check the application logs for more details',
                        'Verify your .env file has correct database settings',
                        'Ensure your database server is running',
                        'Contact your system administrator if needed',
                    ],
                ];
        }
    }

    /**
     * Get example database configuration.
     *
     * @return array Example configurations for different database types
     */
    private function getExampleConfiguration(): array
    {
        return [
            'mysql' => [
                'DB_CONNECTION=mysql',
                'DB_HOST=127.0.0.1',
                'DB_PORT=3306',
                'DB_DATABASE=your_database_name',
                'DB_USERNAME=your_username',
                'DB_PASSWORD=your_password',
            ],
            'mariadb' => [
                'DB_CONNECTION=mariadb',
                'DB_HOST=127.0.0.1',
                'DB_PORT=3306',
                'DB_DATABASE=your_database_name',
                'DB_USERNAME=your_username',
                'DB_PASSWORD=your_password',
            ],
            'postgresql' => [
                'DB_CONNECTION=pgsql',
                'DB_HOST=127.0.0.1',
                'DB_PORT=5432',
                'DB_DATABASE=your_database_name',
                'DB_USERNAME=your_username',
                'DB_PASSWORD=your_password',
            ],
            'sqlite' => [
                'DB_CONNECTION=sqlite',
                'DB_DATABASE=/path/to/database.sqlite',
            ],
        ];
    }
}
