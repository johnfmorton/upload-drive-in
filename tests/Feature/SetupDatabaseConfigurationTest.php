<?php

namespace Tests\Feature;

use App\Services\DatabaseSetupService;
use App\Services\SetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SetupDatabaseConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private string $testDatabasePath;
    private string $setupStateFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testDatabasePath = storage_path('app/test-setup-database.sqlite');
        $this->setupStateFile = storage_path('app/setup/setup-state.json');
        
        // Clean up any existing test files
        if (File::exists($this->testDatabasePath)) {
            File::delete($this->testDatabasePath);
        }
        
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists($this->testDatabasePath)) {
            File::delete($this->testDatabasePath);
        }
        
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        parent::tearDown();
    }

    public function test_sqlite_database_setup_creates_file_successfully(): void
    {
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => $this->testDatabasePath
        ]);
        
        $response->assertRedirect('/setup/admin');
        $response->assertSessionHas('success');
        $response->assertSessionMissing('errors');
        
        // Verify database file was created
        $this->assertTrue(File::exists($this->testDatabasePath));
        $this->assertTrue(is_writable($this->testDatabasePath));
    }

    public function test_sqlite_database_setup_creates_nested_directories(): void
    {
        $nestedPath = storage_path('app/nested/directory/database.sqlite');
        
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => $nestedPath
        ]);
        
        $response->assertRedirect('/setup/admin');
        $response->assertSessionHas('success');
        
        // Verify nested directories and file were created
        $this->assertTrue(File::exists($nestedPath));
        $this->assertTrue(File::exists(dirname($nestedPath)));
        
        // Clean up
        File::deleteDirectory(storage_path('app/nested'));
    }

    public function test_sqlite_database_setup_validation_errors(): void
    {
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => '' // Empty path
        ]);
        
        $response->assertSessionHasErrors(['sqlite_path']);
        $response->assertSessionHasErrorsIn('default', ['sqlite_path']);
    }

    public function test_sqlite_database_setup_handles_permission_errors(): void
    {
        // Try to create database in a location that should fail
        $invalidPath = '/root/database.sqlite'; // Should not be writable
        
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => $invalidPath
        ]);
        
        $response->assertSessionHasErrors();
        $response->assertSessionHas('error');
    }

    public function test_mysql_database_setup_validation_requires_all_fields(): void
    {
        $response = $this->post('/setup/database', [
            'database_type' => 'mysql',
            'mysql_host' => '',
            'mysql_port' => '',
            'mysql_database' => '',
            'mysql_username' => '',
            'mysql_password' => ''
        ]);
        
        $response->assertSessionHasErrors([
            'mysql_host',
            'mysql_port', 
            'mysql_database',
            'mysql_username'
        ]);
        
        // Password is optional, so should not have error
        $response->assertSessionMissing('errors.mysql_password');
    }

    public function test_mysql_database_setup_validates_port_format(): void
    {
        $response = $this->post('/setup/database', [
            'database_type' => 'mysql',
            'mysql_host' => 'localhost',
            'mysql_port' => 'invalid_port',
            'mysql_database' => 'test_db',
            'mysql_username' => 'test_user',
            'mysql_password' => 'test_pass'
        ]);
        
        $response->assertSessionHasErrors(['mysql_port']);
    }

    public function test_mysql_database_setup_validates_host_format(): void
    {
        $response = $this->post('/setup/database', [
            'database_type' => 'mysql',
            'mysql_host' => 'invalid host name with spaces',
            'mysql_port' => '3306',
            'mysql_database' => 'test_db',
            'mysql_username' => 'test_user',
            'mysql_password' => 'test_pass'
        ]);
        
        $response->assertSessionHasErrors(['mysql_host']);
    }

    public function test_database_setup_form_displays_sqlite_fields_by_default(): void
    {
        $response = $this->get('/setup/database');
        
        $response->assertStatus(200);
        $response->assertSee('SQLite Database Path');
        $response->assertSee('MySQL Configuration');
        $response->assertSee('Database Type');
    }

    public function test_database_setup_form_shows_current_configuration(): void
    {
        // Set current database configuration
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.host', 'localhost');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.database', 'current_db');
        Config::set('database.connections.mysql.username', 'current_user');
        
        $response = $this->get('/setup/database');
        
        $response->assertStatus(200);
        $response->assertSee('localhost');
        $response->assertSee('3306');
        $response->assertSee('current_db');
        $response->assertSee('current_user');
    }

    public function test_database_setup_runs_migrations_after_configuration(): void
    {
        // Mock Artisan to verify migrations are called
        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once();
            
        Artisan::shouldReceive('call')
            ->with('migrate', ['--force' => true])
            ->once()
            ->andReturn(0);
            
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Migration completed successfully');
        
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => $this->testDatabasePath
        ]);
        
        $response->assertRedirect('/setup/admin');
        $response->assertSessionHas('success');
    }

    public function test_database_setup_handles_migration_failures(): void
    {
        // Mock Artisan to simulate migration failure
        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once();
            
        Artisan::shouldReceive('call')
            ->with('migrate', ['--force' => true])
            ->once()
            ->andReturn(1); // Non-zero exit code indicates failure
            
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Migration failed: Table already exists');
        
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => $this->testDatabasePath
        ]);
        
        $response->assertSessionHas('error');
        $response->assertSessionHasErrors();
    }

    public function test_database_setup_updates_environment_configuration(): void
    {
        // Mock File operations to test .env updates
        File::shouldReceive('exists')
            ->with(base_path('.env'))
            ->andReturn(true);
        
        File::shouldReceive('get')
            ->with(base_path('.env'))
            ->andReturn("APP_NAME=TestApp\nDB_CONNECTION=mysql\n");
        
        File::shouldReceive('put')
            ->with(base_path('.env'), \Mockery::pattern('/DB_CONNECTION=sqlite/'))
            ->andReturn(true);
        
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => $this->testDatabasePath
        ]);
        
        $response->assertRedirect('/setup/admin');
    }

    public function test_database_setup_preserves_existing_env_values(): void
    {
        $existingEnv = "APP_NAME=MyApp\nAPP_ENV=production\nDB_CONNECTION=mysql\nOTHER_VALUE=keep_this\n";
        
        File::shouldReceive('exists')
            ->with(base_path('.env'))
            ->andReturn(true);
        
        File::shouldReceive('get')
            ->with(base_path('.env'))
            ->andReturn($existingEnv);
        
        File::shouldReceive('put')
            ->with(base_path('.env'), \Mockery::on(function ($content) {
                return str_contains($content, 'APP_NAME=MyApp') &&
                       str_contains($content, 'OTHER_VALUE=keep_this') &&
                       str_contains($content, 'DB_CONNECTION=sqlite');
            }))
            ->andReturn(true);
        
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => $this->testDatabasePath
        ]);
        
        $response->assertRedirect('/setup/admin');
    }

    public function test_database_setup_handles_readonly_env_file(): void
    {
        File::shouldReceive('exists')
            ->with(base_path('.env'))
            ->andReturn(true);
        
        // Simulate readonly file by making put() return false
        File::shouldReceive('get')
            ->with(base_path('.env'))
            ->andReturn("APP_NAME=TestApp\n");
        
        File::shouldReceive('put')
            ->with(base_path('.env'), \Mockery::any())
            ->andReturn(false);
        
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => $this->testDatabasePath
        ]);
        
        $response->assertSessionHas('error');
        $response->assertSessionHasErrors();
    }

    public function test_database_setup_validates_database_type_selection(): void
    {
        $response = $this->post('/setup/database', [
            'database_type' => 'invalid_type',
            'sqlite_path' => $this->testDatabasePath
        ]);
        
        $response->assertSessionHasErrors(['database_type']);
    }

    public function test_database_setup_shows_helpful_error_messages(): void
    {
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => '/invalid/path/database.sqlite'
        ]);
        
        $response->assertSessionHas('error');
        
        // Check that error message contains helpful information
        $errorMessage = session('error');
        $this->assertStringContainsString('database', strtolower($errorMessage));
    }

    public function test_database_setup_progress_tracking(): void
    {
        $setupService = app(SetupService::class);
        
        // Initially database step should not be completed
        $steps = $setupService->getSetupSteps();
        $this->assertFalse($steps['database']['completed'] ?? true);
        
        // Complete database setup
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => $this->testDatabasePath
        ]);
        
        $response->assertRedirect('/setup/admin');
        
        // Database step should now be completed
        $steps = $setupService->getSetupSteps();
        $this->assertTrue($steps['database']['completed'] ?? false);
        $this->assertNotNull($steps['database']['completed_at'] ?? null);
    }

    public function test_database_setup_connection_testing(): void
    {
        $databaseService = app(DatabaseSetupService::class);
        
        // Test SQLite connection after setup
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => $this->testDatabasePath
        ]);
        
        $response->assertRedirect('/setup/admin');
        
        // Verify we can connect to the database
        $status = $databaseService->getDatabaseStatus();
        $this->assertTrue($status['connected']);
        $this->assertEquals('sqlite', $status['type']);
    }
}