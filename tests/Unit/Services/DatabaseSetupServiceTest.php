<?php

namespace Tests\Unit\Services;

use App\Exceptions\DatabaseSetupException;
use App\Services\DatabaseSetupService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PDO;
use PDOException;
use Tests\TestCase;

class DatabaseSetupServiceTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseSetupService $service;
    private string $testDatabasePath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new DatabaseSetupService();
        $this->testDatabasePath = storage_path('app/test-database.sqlite');
        
        // Clean up any existing test database
        if (File::exists($this->testDatabasePath)) {
            File::delete($this->testDatabasePath);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test database file
        if (File::exists($this->testDatabasePath)) {
            File::delete($this->testDatabasePath);
        }
        
        parent::tearDown();
    }

    public function test_detect_database_type_returns_configured_type(): void
    {
        // Test without triggering database connections
        $originalDefault = config('database.default');
        
        Config::set('database.default', 'mysql');
        $this->assertEquals('mysql', $this->service->detectDatabaseType());
        
        Config::set('database.default', 'sqlite');
        $this->assertEquals('sqlite', $this->service->detectDatabaseType());
        
        // Restore original config
        Config::set('database.default', $originalDefault);
    }

    public function test_validate_database_config_returns_valid_for_sqlite(): void
    {
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', $this->testDatabasePath);
        
        $result = $this->service->validateDatabaseConfig();
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_database_config_returns_invalid_for_missing_sqlite_path(): void
    {
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', null);
        
        $result = $this->service->validateDatabaseConfig();
        
        $this->assertFalse($result['valid']);
        $this->assertContains('SQLite database path is not configured', $result['errors']);
    }

    public function test_validate_database_config_returns_invalid_for_missing_mysql_config(): void
    {
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql', [
            'host' => '',
            'port' => '',
            'database' => '',
            'username' => '',
            'password' => ''
        ]);
        
        $result = $this->service->validateDatabaseConfig();
        
        $this->assertFalse($result['valid']);
        $this->assertContains('MySQL host is required', $result['errors']);
        $this->assertContains('MySQL port is required', $result['errors']);
        $this->assertContains('MySQL database is required', $result['errors']);
        $this->assertContains('MySQL username is required', $result['errors']);
    }

    public function test_initialize_sqlite_database_creates_file_successfully(): void
    {
        Config::set('database.connections.sqlite.database', $this->testDatabasePath);
        
        $result = $this->service->initializeSQLiteDatabase();
        
        $this->assertTrue($result);
        $this->assertTrue(File::exists($this->testDatabasePath));
        $this->assertTrue(is_writable($this->testDatabasePath));
    }

    public function test_initialize_sqlite_database_creates_directory_if_needed(): void
    {
        $nestedPath = storage_path('app/nested/test/database.sqlite');
        Config::set('database.connections.sqlite.database', $nestedPath);
        
        $result = $this->service->initializeSQLiteDatabase();
        
        $this->assertTrue($result);
        $this->assertTrue(File::exists($nestedPath));
        $this->assertTrue(File::exists(dirname($nestedPath)));
        
        // Clean up
        File::deleteDirectory(storage_path('app/nested'));
    }

    public function test_initialize_sqlite_database_throws_exception_for_invalid_path(): void
    {
        Config::set('database.connections.sqlite.database', null);
        
        $this->expectException(DatabaseSetupException::class);
        $this->expectExceptionMessage('SQLite database path not configured');
        
        $this->service->initializeSQLiteDatabase();
    }

    public function test_test_mysql_connection_succeeds_with_valid_config(): void
    {
        $config = [
            'host' => 'localhost',
            'port' => '3306',
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_pass'
        ];
        
        // Skip this test in CI/testing environment where MySQL might not be available
        $this->markTestSkipped('MySQL connection test requires actual MySQL server');
    }

    public function test_test_mysql_connection_throws_exception_for_missing_config(): void
    {
        $config = [
            'host' => '',
            'port' => '',
            'database' => '',
            'username' => ''
        ];
        
        $this->expectException(DatabaseSetupException::class);
        $this->expectExceptionMessage('MySQL host is required');
        
        $this->service->testMySQLConnection($config);
    }

    public function test_run_migrations_executes_successfully(): void
    {
        // Mock Artisan facade
        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once()
            ->andReturn(0);
            
        Artisan::shouldReceive('call')
            ->with('migrate', ['--force' => true])
            ->once()
            ->andReturn(0);
            
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Migration completed successfully');
        
        $result = $this->service->runMigrations();
        
        $this->assertTrue($result);
    }

    public function test_run_migrations_throws_exception_on_failure(): void
    {
        // Mock Artisan facade to return failure
        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once()
            ->andReturn(0);
            
        Artisan::shouldReceive('call')
            ->with('migrate', ['--force' => true])
            ->once()
            ->andReturn(1);
            
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Migration failed');
        
        $this->expectException(DatabaseSetupException::class);
        $this->expectExceptionMessage('Migration command returned exit code 1');
        
        $this->service->runMigrations();
    }

    public function test_get_database_status_returns_comprehensive_info(): void
    {
        $status = $this->service->getDatabaseStatus();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('type', $status);
        $this->assertArrayHasKey('connected', $status);
        $this->assertArrayHasKey('migrations_run', $status);
        $this->assertArrayHasKey('tables_exist', $status);
        $this->assertArrayHasKey('errors', $status);
        
        // In test environment, these should be true
        $this->assertEquals('sqlite', $status['type']);
        $this->assertTrue($status['connected']);
        $this->assertTrue($status['migrations_run']);
        $this->assertTrue($status['tables_exist']);
    }

    public function test_validate_database_config_handles_unsupported_type(): void
    {
        Config::set('database.default', 'unsupported_db');
        
        $result = $this->service->validateDatabaseConfig();
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Unsupported database type: unsupported_db', $result['errors']);
    }

    public function test_initialize_sqlite_database_handles_permission_errors(): void
    {
        // Create a read-only directory to simulate permission error
        $readOnlyDir = storage_path('app/readonly');
        File::makeDirectory($readOnlyDir, 0444, true);
        
        $readOnlyPath = $readOnlyDir . '/database.sqlite';
        Config::set('database.connections.sqlite.database', $readOnlyPath);
        
        $this->expectException(DatabaseSetupException::class);
        
        try {
            $this->service->initializeSQLiteDatabase();
        } finally {
            // Clean up - change permissions back to allow deletion
            chmod($readOnlyDir, 0755);
            File::deleteDirectory($readOnlyDir);
        }
    }

    public function test_get_database_status_handles_connection_errors(): void
    {
        // Temporarily break the database connection
        Config::set('database.connections.sqlite.database', '/invalid/path/database.sqlite');
        
        $status = $this->service->getDatabaseStatus();
        
        $this->assertFalse($status['connected']);
        $this->assertNotEmpty($status['errors']);
    }

    public function test_mysql_connection_returns_detailed_error_information(): void
    {
        $config = [
            'host' => 'nonexistent-host',
            'port' => '3306',
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_pass'
        ];
        
        try {
            $this->service->testMySQLConnection($config);
            $this->fail('Expected DatabaseSetupException was not thrown');
        } catch (DatabaseSetupException $e) {
            $this->assertStringContainsString('Unable to connect to MySQL database', $e->getUserMessage());
            $this->assertNotEmpty($e->getTroubleshootingSteps());
            $this->assertArrayHasKey('database_type', $e->getContext());
            $this->assertEquals('mysql', $e->getContext()['database_type']);
        }
    }

    public function test_mysql_connection_provides_specific_error_messages(): void
    {
        // Test with empty host to trigger validation error
        $config = [
            'host' => '',
            'port' => '3306',
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_pass'
        ];
        
        try {
            $this->service->testMySQLConnection($config);
            $this->fail('Expected DatabaseSetupException was not thrown');
        } catch (DatabaseSetupException $e) {
            $this->assertStringContainsString('MySQL host is required', $e->getMessage());
            $this->assertStringContainsString('invalid', strtolower($e->getUserMessage()));
        }
    }

    public function test_mysql_connection_provides_hosting_instructions(): void
    {
        $config = [
            'host' => 'invalid-host',
            'port' => '3306',
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_pass'
        ];
        
        try {
            $this->service->testMySQLConnection($config);
            $this->fail('Expected DatabaseSetupException was not thrown');
        } catch (DatabaseSetupException $e) {
            $this->assertTrue(property_exists($e, 'details'));
            if (property_exists($e, 'details') && !empty($e->details)) {
                $this->assertArrayHasKey('hosting_instructions', $e->details);
                $hostingInstructions = $e->details['hosting_instructions'];
                $this->assertArrayHasKey('cpanel', $hostingInstructions);
                $this->assertArrayHasKey('plesk', $hostingInstructions);
                $this->assertArrayHasKey('vps_dedicated', $hostingInstructions);
            }
        }
    }

    public function test_database_config_request_validation_provides_helpful_messages(): void
    {
        $request = new \App\Http\Requests\DatabaseConfigRequest();
        
        // Test validation messages
        $messages = $request->messages();
        
        $this->assertStringContainsString('localhost', $messages['mysql_host.required']);
        $this->assertStringContainsString('3306', $messages['mysql_port.required']);
        $this->assertStringContainsString('no spaces', $messages['mysql_database.regex']);
    }

    public function test_sqlite_initialization_provides_detailed_error_information(): void
    {
        // Test with invalid path
        Config::set('database.connections.sqlite.database', '/invalid/readonly/path/database.sqlite');
        
        try {
            $this->service->initializeSQLiteDatabase();
            $this->fail('Expected DatabaseSetupException was not thrown');
        } catch (DatabaseSetupException $e) {
            $this->assertNotEmpty($e->getTroubleshootingSteps());
            $this->assertStringContainsString('permission', strtolower($e->getUserMessage()));
        }
    }

    public function test_database_status_includes_comprehensive_information(): void
    {
        $status = $this->service->getDatabaseStatus();
        
        // Verify all required fields are present
        $requiredFields = ['type', 'connected', 'migrations_run', 'tables_exist', 'errors'];
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $status);
        }
        
        // Verify data types
        $this->assertIsString($status['type']);
        $this->assertIsBool($status['connected']);
        $this->assertIsBool($status['migrations_run']);
        $this->assertIsBool($status['tables_exist']);
        $this->assertIsArray($status['errors']);
    }

    public function test_mysql_validation_includes_permission_checks(): void
    {
        // This test would require a real MySQL connection, so we'll skip it
        // but document what it should test
        $this->markTestSkipped('Permission checks require actual MySQL connection');
        
        // This test should verify:
        // - SELECT permission testing
        // - CREATE permission testing
        // - INSERT, UPDATE, DELETE permission testing
        // - ALTER permission testing (needed for migrations)
        // - Proper reporting of missing permissions
    }

    public function test_mysql_version_compatibility_checking(): void
    {
        // This test would require a real MySQL connection, so we'll skip it
        $this->markTestSkipped('Version checking requires actual MySQL connection');
        
        // This test should verify:
        // - MySQL version detection
        // - Compatibility warnings for old versions
        // - Proper handling of version information
    }

    /**
     * Mock PDO connection for MySQL tests
     */
    private function mockPDOConnection(): void
    {
        // This is a simplified mock - in a real test environment,
        // you might want to use a more sophisticated mocking approach
        // or test against a real test database
    }
}