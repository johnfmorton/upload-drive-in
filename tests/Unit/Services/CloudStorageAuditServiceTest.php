<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CloudStorageAuditService;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CloudStorageAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    private CloudStorageAuditService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CloudStorageAuditService();
        $this->user = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);
    }

    public function test_logs_provider_configuration_change()
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->once();
        Log::shouldReceive('channel')->with('cloud-storage')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $oldConfig = ['client_id' => 'old_id', 'client_secret' => 'old_secret'];
        $newConfig = ['client_id' => 'new_id', 'client_secret' => 'new_secret'];

        $this->service->logProviderConfigurationChange(
            $this->user,
            'google-drive',
            'update',
            $oldConfig,
            $newConfig,
            'Updating credentials'
        );

        // Verify the log was called with proper structure
        $this->assertTrue(true); // Log expectations verified above
    }

    public function test_sanitizes_sensitive_configuration_data()
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->withArgs(function ($message, $data) {
            // Verify sensitive data is redacted
            return isset($data['new_configuration']['client_secret']) && 
                   $data['new_configuration']['client_secret'] === '[REDACTED]';
        })->once();
        Log::shouldReceive('channel')->with('cloud-storage')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $newConfig = [
            'client_id' => 'public_id',
            'client_secret' => 'very_secret_value',
            'redirect_uri' => 'https://example.com/callback'
        ];

        $this->service->logProviderConfigurationChange(
            $this->user,
            'google-drive',
            'create',
            [],
            $newConfig
        );
    }

    public function test_logs_provider_authentication_event()
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $this->service->logProviderAuthenticationEvent(
            $this->user,
            'google-drive',
            'oauth_callback',
            true,
            null,
            ['redirect_from' => 'admin_dashboard']
        );

        // Verify the log was called
        $this->assertTrue(true);
    }

    public function test_logs_failed_authentication_event()
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('warning')->once();

        $this->service->logProviderAuthenticationEvent(
            $this->user,
            'google-drive',
            'oauth_callback',
            false,
            'Invalid authorization code',
            ['attempt_number' => 2]
        );

        // Verify the log was called with warning level
        $this->assertTrue(true);
    }

    public function test_logs_administrative_action()
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $this->service->logAdministrativeAction(
            $this->user,
            'update',
            'provider',
            'google-drive',
            ['field' => 'enabled', 'old_value' => false, 'new_value' => true],
            'Enabling provider for testing'
        );

        // Verify the log was called
        $this->assertTrue(true);
    }

    public function test_logs_security_event()
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('warning')->once();
        Log::shouldReceive('channel')->with('security')->andReturnSelf();
        Log::shouldReceive('warning')->once();

        $this->service->logSecurityEvent(
            $this->user,
            'unauthorized_access_attempt',
            'medium',
            'User attempted to access restricted provider configuration',
            ['attempted_provider' => 'amazon-s3', 'user_role' => 'client']
        );

        // Verify both audit and security channels were used
        $this->assertTrue(true);
    }

    public function test_logs_security_event_without_user()
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('warning')->once();
        Log::shouldReceive('channel')->with('security')->andReturnSelf();
        Log::shouldReceive('warning')->once();

        $this->service->logSecurityEvent(
            null,
            'suspicious_activity',
            'high',
            'Multiple failed authentication attempts from same IP',
            ['ip_address' => '192.168.1.100', 'attempt_count' => 5]
        );

        // Verify logs work without user
        $this->assertTrue(true);
    }

    public function test_logs_provider_health_status_change()
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $this->service->logProviderHealthStatusChange(
            $this->user,
            'google-drive',
            'healthy',
            'authentication_required',
            'Token expired',
            ['token_expiry' => '2024-01-01T00:00:00Z']
        );

        // Verify the log was called
        $this->assertTrue(true);
    }

    public function test_logs_bulk_operation()
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $this->service->logBulkOperation(
            $this->user,
            'delete',
            'files',
            25,
            ['older_than' => '30 days', 'provider' => 'google-drive'],
            'Cleanup old files'
        );

        // Verify the log was called
        $this->assertTrue(true);
    }

    public function test_logs_system_configuration_change()
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $this->service->logSystemConfigurationChange(
            $this->user,
            'cloud_storage.default_provider',
            'google-drive',
            'amazon-s3',
            'Switching to S3 for better performance'
        );

        // Verify the log was called
        $this->assertTrue(true);
    }

    public function test_sanitizes_sensitive_system_configuration_values()
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->withArgs(function ($message, $data) {
            // Verify long random strings are redacted
            return $data['old_value'] === '[REDACTED]' && $data['new_value'] === '[REDACTED]';
        })->once();

        $this->service->logSystemConfigurationChange(
            $this->user,
            'some.secret.key',
            'abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'ZYXWVUTSRQPONMLKJIHGFEDCBA0987654321zyxwvutsrqponmlkjihgfedcba',
            'Rotating secret key'
        );
    }

    public function test_logs_data_access_event()
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $this->service->logDataAccess(
            $this->user,
            'user_files',
            'view',
            'file_123',
            ['file_name' => 'document.pdf', 'file_size' => 1024000]
        );

        // Verify the log was called
        $this->assertTrue(true);
    }

    public function test_logs_provider_migration()
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $this->service->logProviderMigration(
            $this->user,
            'google-drive',
            'amazon-s3',
            150,
            true,
            null,
            ['migration_id' => 'mig_123', 'duration_seconds' => 3600]
        );

        // Verify successful migration was logged
        $this->assertTrue(true);
    }

    public function test_logs_failed_provider_migration()
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('error')->once();

        $this->service->logProviderMigration(
            $this->user,
            'google-drive',
            'amazon-s3',
            75,
            false,
            'S3 bucket access denied',
            ['migration_id' => 'mig_124', 'failed_at_file' => 'document.pdf']
        );

        // Verify failed migration was logged with error level
        $this->assertTrue(true);
    }

    public function test_calculates_configuration_diff_correctly()
    {
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();
        Log::shouldReceive('info')->withArgs(function ($message, $data) {
            $changes = $data['configuration_changes'];
            
            // Verify added keys
            $this->assertArrayHasKey('added', $changes);
            $this->assertArrayHasKey('new_key', $changes['added']);
            
            // Verify modified keys
            $this->assertArrayHasKey('modified', $changes);
            $this->assertArrayHasKey('existing_key', $changes['modified']);
            $this->assertEquals('old_value', $changes['modified']['existing_key']['old']);
            $this->assertEquals('new_value', $changes['modified']['existing_key']['new']);
            
            // Verify removed keys
            $this->assertArrayHasKey('removed', $changes);
            $this->assertArrayHasKey('removed_key', $changes['removed']);
            
            return true;
        })->once();
        Log::shouldReceive('channel')->with('cloud-storage')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $oldConfig = [
            'existing_key' => 'old_value',
            'unchanged_key' => 'same_value',
            'removed_key' => 'will_be_removed'
        ];

        $newConfig = [
            'existing_key' => 'new_value',
            'unchanged_key' => 'same_value',
            'new_key' => 'added_value'
        ];

        $this->service->logProviderConfigurationChange(
            $this->user,
            'google-drive',
            'update',
            $oldConfig,
            $newConfig
        );
    }

    public function test_returns_audit_trail_structure()
    {
        $auditTrail = $this->service->getAuditTrail(
            'provider',
            'google-drive',
            $this->user,
            30
        );

        $this->assertEquals('provider', $auditTrail['resource_type']);
        $this->assertEquals('google-drive', $auditTrail['resource_id']);
        $this->assertEquals($this->user->id, $auditTrail['user_id']);
        $this->assertEquals(30, $auditTrail['days']);
        $this->assertArrayHasKey('events', $auditTrail);
        $this->assertArrayHasKey('generated_at', $auditTrail);
    }

    public function test_generates_compliance_report_structure()
    {
        $startDate = now()->subDays(7);
        $endDate = now();

        $report = $this->service->generateComplianceReport(
            $startDate,
            $endDate,
            'google-drive',
            $this->user
        );

        $this->assertEquals('compliance_audit', $report['report_type']);
        $this->assertEquals($startDate->toISOString(), $report['period']['start']);
        $this->assertEquals($endDate->toISOString(), $report['period']['end']);
        $this->assertEquals('google-drive', $report['provider']);
        $this->assertEquals($this->user->id, $report['user_id']);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('events', $report);
        $this->assertArrayHasKey('generated_at', $report);
    }
}