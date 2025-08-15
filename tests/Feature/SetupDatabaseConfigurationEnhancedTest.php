<?php

namespace Tests\Feature;

use App\Services\DatabaseSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Test enhanced database configuration with improved error handling.
 */
class SetupDatabaseConfigurationEnhancedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we're in setup mode for these tests
        Config::set('app.setup_complete', false);
        
        // Disable all middleware for setup testing
        $this->withoutMiddleware();
        
        // Mock setup service to avoid setup state issues
        $this->mock(\App\Services\SetupService::class, function ($mock) {
            $mock->shouldReceive('getSetupStep')->andReturn('database');
            $mock->shouldReceive('getSetupProgress')->andReturn(40);
        });
    }

    public function test_database_connection_test_returns_detailed_mysql_information(): void
    {
        $response = $this->postJson('/setup/ajax/test-database', [
            'database_type' => 'mysql',
            'host' => 'nonexistent-host',
            'port' => 3306,
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_pass'
        ]);

        // Debug the actual response
        if ($response->status() !== 400) {
            dump('Response status: ' . $response->status());
            dump('Response content: ' . $response->content());
        }
        
        $response->assertStatus(400);
        $response->assertJsonStructure([
            'success',
            'message',
            'technical_error',
            'troubleshooting',
            'hosting_instructions'
        ]);

        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertNotEmpty($data['troubleshooting']);
        $this->assertIsArray($data['troubleshooting']);
        $this->assertArrayHasKey('cpanel', $data['hosting_instructions']);
    }

    public function test_database_connection_test_validates_mysql_input(): void
    {
        $response = $this->postJson('/setup/ajax/test-database', [
            'database_type' => 'mysql',
            'host' => '', // Missing required field
            'port' => 3306,
            'database' => 'test_db',
            'username' => 'test_user'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['host']);
    }

    public function test_database_connection_test_handles_sqlite_configuration(): void
    {
        $testPath = storage_path('app/test-sqlite-connection.sqlite');
        
        // Clean up any existing test file
        if (file_exists($testPath)) {
            unlink($testPath);
        }

        $response = $this->postJson('/setup/ajax/test-database', [
            'database_type' => 'sqlite',
            'database' => 'test_database',
            'sqlite_path' => $testPath
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        $data = $response->json();
        $this->assertArrayHasKey('details', $data);
        $this->assertArrayHasKey('database_path', $data['details']);

        // Clean up
        if (file_exists($testPath)) {
            unlink($testPath);
        }
    }

    public function test_database_configuration_form_shows_helpful_validation_messages(): void
    {
        $response = $this->post('/setup/database', [
            'database_type' => 'mysql',
            'mysql_host' => '', // Invalid
            'mysql_port' => 'invalid', // Invalid
            'mysql_database' => 'invalid-name!', // Invalid characters
            'mysql_username' => '',
            'mysql_password' => ''
        ]);

        $response->assertSessionHasErrors([
            'mysql_host',
            'mysql_port',
            'mysql_database',
            'mysql_username'
        ]);

        // Check that error messages contain helpful hints
        $errors = session('errors');
        $this->assertStringContainsString('localhost', $errors->first('mysql_host'));
        $this->assertStringContainsString('3306', $errors->first('mysql_port'));
        $this->assertStringContainsString('underscores', $errors->first('mysql_database'));
    }

    public function test_database_configuration_provides_troubleshooting_on_connection_failure(): void
    {
        $response = $this->post('/setup/database', [
            'database_type' => 'mysql',
            'mysql_host' => 'nonexistent-host',
            'mysql_port' => 3306,
            'mysql_database' => 'test_db',
            'mysql_username' => 'test_user',
            'mysql_password' => 'test_pass'
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['mysql_connectivity']);
        
        // Check that troubleshooting information is provided
        $errors = session('errors');
        $connectivityError = $errors->first('mysql_connectivity');
        $this->assertNotEmpty($connectivityError);
        
        // Should also have troubleshooting hints
        if ($errors->has('mysql_troubleshooting')) {
            $troubleshooting = $errors->first('mysql_troubleshooting');
            $this->assertStringContainsString('Try these steps', $troubleshooting);
        }
    }

    public function test_database_connection_test_handles_unexpected_errors_gracefully(): void
    {
        // Mock the database setup service to throw an unexpected exception
        $this->mock(DatabaseSetupService::class, function ($mock) {
            $mock->shouldReceive('testMySQLConnection')
                ->andThrow(new \RuntimeException('Unexpected error'));
        });

        $response = $this->postJson('/setup/ajax/test-database', [
            'database_type' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_pass'
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'An unexpected error occurred while testing the database connection.'
        ]);

        $data = $response->json();
        $this->assertArrayHasKey('troubleshooting', $data);
        $this->assertContains('Check the application logs for detailed error information', $data['troubleshooting']);
    }

    public function test_sqlite_connection_test_provides_file_permission_details(): void
    {
        $readOnlyPath = storage_path('app/readonly-test.sqlite');
        
        // Create a file and make it read-only to simulate permission issues
        touch($readOnlyPath);
        chmod($readOnlyPath, 0444);

        $response = $this->postJson('/setup/ajax/test-database', [
            'database_type' => 'sqlite',
            'database' => 'test_database',
            'sqlite_path' => $readOnlyPath
        ]);

        // Should fail due to permission issues
        $response->assertStatus(400);
        
        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('permission', strtolower($data['message']));

        // Clean up
        chmod($readOnlyPath, 0644);
        unlink($readOnlyPath);
    }

    public function test_database_configuration_preserves_input_on_validation_failure(): void
    {
        $inputData = [
            'database_type' => 'mysql',
            'mysql_host' => 'localhost',
            'mysql_port' => 3306,
            'mysql_database' => 'valid_db_name',
            'mysql_username' => 'valid_user',
            'mysql_password' => 'valid_password'
        ];

        // This should fail due to connection issues (assuming no MySQL server)
        $response = $this->post('/setup/database', $inputData);

        $response->assertRedirect();
        
        // Check that input is preserved (except password)
        $this->assertEquals('mysql', old('database_type'));
        $this->assertEquals('localhost', old('mysql_host'));
        $this->assertEquals(3306, old('mysql_port'));
        $this->assertEquals('valid_db_name', old('mysql_database'));
        $this->assertEquals('valid_user', old('mysql_username'));
        $this->assertNull(old('mysql_password')); // Password should not be preserved
    }

    public function test_database_connection_test_includes_hosting_provider_instructions(): void
    {
        $response = $this->postJson('/setup/ajax/test-database', [
            'database_type' => 'mysql',
            'host' => 'invalid-host',
            'port' => 3306,
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'test_pass'
        ]);

        $response->assertStatus(400);
        
        $data = $response->json();
        $this->assertArrayHasKey('hosting_instructions', $data);
        
        $instructions = $data['hosting_instructions'];
        $this->assertArrayHasKey('cpanel', $instructions);
        $this->assertArrayHasKey('plesk', $instructions);
        $this->assertArrayHasKey('shared_hosting', $instructions);
        $this->assertArrayHasKey('vps_dedicated', $instructions);
        $this->assertArrayHasKey('cloud_providers', $instructions);
        
        // Verify structure of instructions
        foreach ($instructions as $provider => $info) {
            $this->assertArrayHasKey('title', $info);
            $this->assertArrayHasKey('steps', $info);
            $this->assertIsArray($info['steps']);
            $this->assertNotEmpty($info['steps']);
        }
    }

    public function test_database_field_validation_endpoint_works(): void
    {
        $response = $this->postJson('/setup/ajax/validate-database-field', [
            'field' => 'mysql_host',
            'value' => 'localhost'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'valid',
            'message',
            'suggestion'
        ]);

        $data = $response->json();
        $this->assertTrue($data['valid']);
    }

    public function test_database_field_validation_rejects_invalid_fields(): void
    {
        $response = $this->postJson('/setup/ajax/validate-database-field', [
            'field' => 'invalid_field',
            'value' => 'test'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['field']);
    }

    public function test_database_field_validation_provides_helpful_feedback(): void
    {
        // Test invalid database name
        $response = $this->postJson('/setup/ajax/validate-database-field', [
            'field' => 'mysql_database',
            'value' => 'invalid-name!'
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertFalse($data['valid']);
        $this->assertStringContainsString('underscores', $data['message']);
        $this->assertNotEmpty($data['suggestion']);
    }

    public function test_database_config_hints_endpoint_returns_helpful_information(): void
    {
        $response = $this->getJson('/setup/ajax/database-config-hints');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'hints' => [
                'mysql_host' => [
                    'hint',
                    'examples',
                    'common_values'
                ],
                'mysql_port' => [
                    'hint',
                    'examples',
                    'common_values'
                ]
            ],
            'templates' => [
                'local_development' => [
                    'name',
                    'description',
                    'config',
                    'notes'
                ]
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('mysql_host', $data['hints']);
        $this->assertArrayHasKey('local_development', $data['templates']);
    }

    public function test_enhanced_database_form_shows_real_time_validation(): void
    {
        $response = $this->get('/setup/database');

        $response->assertStatus(200);
        
        // Check that enhanced form elements are present
        $response->assertSee('mysql-field', false); // CSS class for MySQL fields
        $response->assertSee('test-mysql-connection', false); // Test button ID
        $response->assertSee('connection-progress', false); // Progress indicator
        $response->assertSee('Common Configurations'); // Configuration examples
    }

    public function test_database_form_includes_field_hints_and_examples(): void
    {
        $response = $this->get('/setup/database');

        $response->assertStatus(200);
        
        // Check for field hints and examples
        $response->assertSee('Examples:');
        $response->assertSee('localhost');
        $response->assertSee('3306');
        $response->assertSee('Only letters, numbers, and underscores allowed');
        $response->assertSee('Default MySQL port');
    }

    public function test_database_form_has_progressive_enhancement_features(): void
    {
        $response = $this->get('/setup/database');

        $response->assertStatus(200);
        
        // Check for progressive enhancement features
        $response->assertSee('toggle-password', false); // Password visibility toggle
        $response->assertSee('sqlite-custom-path', false); // SQLite custom path option
        $response->assertSee('show-hosting-help', false); // Hosting help button
        $response->assertSee('progress-bar', false); // Connection test progress bar
    }

    public function test_database_form_provides_configuration_templates(): void
    {
        $response = $this->get('/setup/database');

        $response->assertStatus(200);
        
        // Check for configuration templates/examples
        $response->assertSee('Local Development');
        $response->assertSee('Shared Hosting');
        $response->assertSee('Common Configurations');
    }

    public function test_database_connection_test_shows_progress_indicators(): void
    {
        // This test would require JavaScript testing framework like Dusk
        // For now, we'll test that the HTML elements are present
        $response = $this->get('/setup/database');

        $response->assertStatus(200);
        $response->assertSee('connection-progress', false);
        $response->assertSee('progress-bar', false);
        $response->assertSee('progress-text', false);
        $response->assertSee('mysql-loading', false);
    }

    public function test_sqlite_configuration_includes_enhanced_options(): void
    {
        $response = $this->get('/setup/database');

        $response->assertStatus(200);
        
        // Check for SQLite enhancements
        $response->assertSee('test-sqlite-connection', false);
        $response->assertSee('sqlite-custom-path', false);
        $response->assertSee('sqlite-path-input', false);
        $response->assertSee('Check SQLite Setup');
    }

    public function test_database_form_validation_preserves_enhanced_input(): void
    {
        $inputData = [
            'database_type' => 'mysql',
            'mysql_host' => 'test-host',
            'mysql_port' => 3306,
            'mysql_database' => 'test_db',
            'mysql_username' => 'test_user',
            'mysql_password' => 'test_pass'
        ];

        // This should fail due to connection issues
        $response = $this->post('/setup/database', $inputData);

        $response->assertRedirect();
        
        // Verify enhanced validation messages are present
        $response->assertSessionHasErrors();
        
        // Check that input is preserved for enhanced form
        $this->assertEquals('mysql', old('database_type'));
        $this->assertEquals('test-host', old('mysql_host'));
        $this->assertEquals(3306, old('mysql_port'));
    }
}