<?php

namespace Tests\Unit\Services;

use App\Services\EnvironmentFileService;
use App\Services\SetupSecurityService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EnvironmentFileServiceTest extends TestCase
{
    private EnvironmentFileService $environmentFileService;
    private SetupSecurityService $securityService;
    private string $testEnvPath;
    private string $testBackupDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->securityService = new SetupSecurityService();
        $this->environmentFileService = new EnvironmentFileService($this->securityService);
        
        // Use test paths
        $this->testEnvPath = storage_path('app/test/.env');
        $this->testBackupDir = storage_path('app/test/env-backups');
        
        // Ensure test directories exist
        File::ensureDirectoryExists(dirname($this->testEnvPath));
        File::ensureDirectoryExists($this->testBackupDir);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists($this->testEnvPath)) {
            File::delete($this->testEnvPath);
        }
        
        if (File::exists($this->testBackupDir)) {
            File::deleteDirectory($this->testBackupDir);
        }
        
        parent::tearDown();
    }

    /** @test */
    public function creates_backup_of_existing_environment_file()
    {
        // Create a test environment file
        $envContent = "APP_NAME=TestApp\nAPP_ENV=testing\nDB_HOST=localhost";
        File::put($this->testEnvPath, $envContent);

        // Mock the environment file service to use test paths
        $service = $this->createEnvironmentFileServiceWithTestPaths();
        
        $result = $service->createBackup();
        
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['backup_path']);
        $this->assertFileExists($result['backup_path']);
        
        // Verify backup content matches original
        $backupContent = File::get($result['backup_path']);
        $this->assertEquals($envContent, $backupContent);
    }

    /** @test */
    public function backup_creation_fails_when_env_file_missing()
    {
        $service = $this->createEnvironmentFileServiceWithTestPaths();
        
        $result = $service->createBackup();
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('does not exist', $result['message']);
    }

    /** @test */
    public function updates_environment_file_with_validation()
    {
        // Create initial environment file
        $initialContent = "APP_NAME=OldApp\nAPP_ENV=testing";
        File::put($this->testEnvPath, $initialContent);

        $service = $this->createEnvironmentFileServiceWithTestPaths();
        
        $updates = [
            'APP_NAME' => 'NewApp',
            'DB_HOST' => 'localhost',
            'DB_PORT' => '3306'
        ];

        $result = $service->updateEnvironmentFile($updates);
        
        $this->assertTrue($result['success']);
        $this->assertTrue($result['backup_created']);
        $this->assertNotEmpty($result['backup_path']);
        
        // Verify file was updated
        $updatedContent = File::get($this->testEnvPath);
        $this->assertStringContainsString('APP_NAME=NewApp', $updatedContent);
        $this->assertStringContainsString('DB_HOST=localhost', $updatedContent);
        $this->assertStringContainsString('DB_PORT=3306', $updatedContent);
    }

    /** @test */
    public function rejects_invalid_environment_variables()
    {
        File::put($this->testEnvPath, "APP_NAME=TestApp");

        $service = $this->createEnvironmentFileServiceWithTestPaths();
        
        $invalidUpdates = [
            'INVALID_VAR' => '$(rm -rf /)', // Dangerous command
            'ANOTHER_VAR' => 'valid_value'
        ];

        $result = $service->updateEnvironmentFile($invalidUpdates);
        
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['violations']);
        $this->assertStringContainsString('validation failed', $result['message']);
    }

    /** @test */
    public function restores_from_backup_on_validation_failure()
    {
        $originalContent = "APP_NAME=OriginalApp\nAPP_ENV=testing";
        File::put($this->testEnvPath, $originalContent);

        $service = $this->createEnvironmentFileServiceWithTestPaths();
        
        // This should create a backup but fail validation
        $invalidUpdates = [
            'VALID_VAR' => 'valid_value',
            'INVALID_VAR' => '$(malicious_command)'
        ];

        $result = $service->updateEnvironmentFile($invalidUpdates);
        
        $this->assertFalse($result['success']);
        
        // Verify original content is preserved
        $currentContent = File::get($this->testEnvPath);
        $this->assertEquals($originalContent, $currentContent);
    }

    /** @test */
    public function validates_environment_file_format()
    {
        $validContent = "APP_NAME=TestApp\nAPP_ENV=testing\nDB_HOST=localhost";
        File::put($this->testEnvPath, $validContent);

        $service = $this->createEnvironmentFileServiceWithTestPaths();
        
        $result = $service->validateEnvironmentFile();
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['violations']);
    }

    /** @test */
    public function detects_invalid_environment_file_format()
    {
        $invalidContent = "APP_NAME=TestApp\nINVALID_LINE_WITHOUT_EQUALS\nDB_HOST=localhost";
        File::put($this->testEnvPath, $invalidContent);

        $service = $this->createEnvironmentFileServiceWithTestPaths();
        
        $result = $service->validateEnvironmentFile();
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['violations']);
        $this->assertStringContainsString("missing '='", implode(' ', $result['violations']));
    }

    /** @test */
    public function detects_missing_required_variables()
    {
        $incompleteContent = "APP_ENV=testing\nDB_HOST=localhost"; // Missing APP_NAME and APP_KEY
        File::put($this->testEnvPath, $incompleteContent);

        $service = $this->createEnvironmentFileServiceWithTestPaths();
        
        $result = $service->validateEnvironmentFile();
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['violations']);
        
        $violationsText = implode(' ', $result['violations']);
        $this->assertStringContainsString('Missing required variable: APP_NAME', $violationsText);
        $this->assertStringContainsString('Missing required variable: APP_KEY', $violationsText);
    }

    /** @test */
    public function restores_from_backup_successfully()
    {
        $originalContent = "APP_NAME=OriginalApp\nAPP_ENV=testing";
        File::put($this->testEnvPath, $originalContent);

        $service = $this->createEnvironmentFileServiceWithTestPaths();
        
        // Create backup
        $backupResult = $service->createBackup();
        $this->assertTrue($backupResult['success']);
        
        // Modify the file
        File::put($this->testEnvPath, "APP_NAME=ModifiedApp\nAPP_ENV=production");
        
        // Restore from backup
        $restoreResult = $service->restoreFromBackup($backupResult['backup_path']);
        
        $this->assertTrue($restoreResult['success']);
        
        // Verify content was restored
        $restoredContent = File::get($this->testEnvPath);
        $this->assertEquals($originalContent, $restoredContent);
    }

    /** @test */
    public function lists_available_backups()
    {
        File::put($this->testEnvPath, "APP_NAME=TestApp");

        $service = $this->createEnvironmentFileServiceWithTestPaths();
        
        // Create multiple backups
        $service->createBackup();
        sleep(1); // Ensure different timestamps
        $service->createBackup();
        
        $backups = $service->getAvailableBackups();
        
        $this->assertCount(2, $backups);
        
        foreach ($backups as $backup) {
            $this->assertArrayHasKey('filename', $backup);
            $this->assertArrayHasKey('path', $backup);
            $this->assertArrayHasKey('created_at', $backup);
            $this->assertArrayHasKey('size', $backup);
            $this->assertStringStartsWith('.env.backup.', $backup['filename']);
        }
        
        // Verify backups are sorted by creation time (newest first)
        $this->assertGreaterThanOrEqual(
            $backups[1]['created_at']->timestamp,
            $backups[0]['created_at']->timestamp
        );
    }

    /** @test */
    public function parses_environment_file_content_correctly()
    {
        $envContent = "# Comment line\nAPP_NAME=TestApp\nAPP_ENV=\"testing\"\nDB_PASSWORD='secret123'\n\n# Another comment\nDB_HOST=localhost";
        
        $service = $this->createEnvironmentFileServiceWithTestPaths();
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('parseEnvironmentFile');
        $method->setAccessible(true);
        
        $result = $method->invoke($service, $envContent);
        
        $expected = [
            'APP_NAME' => 'TestApp',
            'APP_ENV' => 'testing',
            'DB_PASSWORD' => 'secret123',
            'DB_HOST' => 'localhost'
        ];
        
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function generates_well_formatted_environment_content()
    {
        $variables = [
            'APP_NAME' => 'Test App',
            'APP_ENV' => 'testing',
            'DB_HOST' => 'localhost',
            'DB_PORT' => '3306',
            'GOOGLE_DRIVE_CLIENT_ID' => 'client123',
            'MAIL_HOST' => 'smtp.example.com'
        ];
        
        $service = $this->createEnvironmentFileServiceWithTestPaths();
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('generateEnvironmentFileContent');
        $method->setAccessible(true);
        
        $result = $method->invoke($service, $variables);
        
        // Verify structure
        $this->assertStringContainsString('# Environment Configuration', $result);
        $this->assertStringContainsString('# Application', $result);
        $this->assertStringContainsString('# Database', $result);
        $this->assertStringContainsString('# Google Drive', $result);
        $this->assertStringContainsString('# Mail', $result);
        
        // Verify quoted values
        $this->assertStringContainsString('APP_NAME="Test App"', $result);
        $this->assertStringContainsString('APP_ENV=testing', $result);
        $this->assertStringContainsString('DB_HOST=localhost', $result);
    }

    /** @test */
    public function cleans_up_old_backups()
    {
        File::put($this->testEnvPath, "APP_NAME=TestApp");

        $service = $this->createEnvironmentFileServiceWithTestPaths();
        
        // Create more backups than the limit (10)
        for ($i = 0; $i < 12; $i++) {
            $service->createBackup();
            usleep(100000); // 0.1 second delay to ensure different timestamps
        }
        
        $backups = $service->getAvailableBackups();
        
        // Should only keep the maximum number of backups
        $this->assertLessThanOrEqual(10, count($backups));
    }

    /**
     * Create environment file service with test paths using reflection
     */
    private function createEnvironmentFileServiceWithTestPaths(): EnvironmentFileService
    {
        $service = new EnvironmentFileService($this->securityService);
        
        // Use reflection to set test paths
        $reflection = new \ReflectionClass($service);
        
        $envPathProperty = $reflection->getProperty('envPath');
        $envPathProperty->setAccessible(true);
        $envPathProperty->setValue($service, $this->testEnvPath);
        
        $backupDirProperty = $reflection->getProperty('backupDirectory');
        $backupDirProperty->setAccessible(true);
        $backupDirProperty->setValue($service, $this->testBackupDir);
        
        return $service;
    }
}