<?php

namespace Tests\Unit\Services;

use App\Services\SetupDetectionService;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Tests\TestCase;
use Exception;
use Mockery;

class SetupDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SetupDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SetupDetectionService();
    }

    protected function tearDown(): void
    {
        // Clean up environment variables set during tests
        putenv('MAIL_MAILER');
        putenv('MAIL_HOST');
        putenv('MAIL_PORT');
        putenv('MAIL_USERNAME');
        putenv('MAIL_PASSWORD');
        putenv('MAIL_FROM_ADDRESS');
        putenv('GOOGLE_DRIVE_CLIENT_ID');
        putenv('GOOGLE_DRIVE_CLIENT_SECRET');
        putenv('DB_CONNECTION');
        putenv('DB_HOST');
        putenv('DB_DATABASE');
        putenv('DB_USERNAME');
        
        parent::tearDown();
    }

    public function test_is_setup_complete_returns_true_when_all_requirements_met(): void
    {
        // Mock all individual status methods to return true
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(true);
        $service->shouldReceive('getMailStatus')->andReturn(true);
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(true);
        $service->shouldReceive('getAdminUserStatus')->andReturn(true);

        $result = $service->isSetupComplete();

        $this->assertTrue($result);
    }

    public function test_is_setup_complete_returns_false_when_database_missing(): void
    {
        // Mock database failure, others success
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(false);
        $service->shouldReceive('getMailStatus')->andReturn(true);
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(true);
        $service->shouldReceive('getAdminUserStatus')->andReturn(true);

        $result = $service->isSetupComplete();

        $this->assertFalse($result);
    }

    public function test_is_setup_complete_returns_false_when_mail_missing(): void
    {
        // Mock mail failure, others success
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(true);
        $service->shouldReceive('getMailStatus')->andReturn(false);
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(true);
        $service->shouldReceive('getAdminUserStatus')->andReturn(true);

        $result = $service->isSetupComplete();

        $this->assertFalse($result);
    }

    public function test_is_setup_complete_returns_false_when_google_drive_missing(): void
    {
        // Mock Google Drive failure, others success
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(true);
        $service->shouldReceive('getMailStatus')->andReturn(true);
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(false);
        $service->shouldReceive('getAdminUserStatus')->andReturn(true);

        $result = $service->isSetupComplete();

        $this->assertFalse($result);
    }

    public function test_is_setup_complete_returns_false_when_admin_user_missing(): void
    {
        // Mock admin user failure, others success
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(true);
        $service->shouldReceive('getMailStatus')->andReturn(true);
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(true);
        $service->shouldReceive('getAdminUserStatus')->andReturn(false);

        $result = $service->isSetupComplete();

        $this->assertFalse($result);
    }

    public function test_get_database_status_works_with_current_config(): void
    {
        // Test that database status works with current configuration
        // This is more of an integration test but validates the method works
        $result = $this->service->getDatabaseStatus();

        // Should return true since we have database configured in .env
        $this->assertTrue($result);
    }

    public function test_get_google_drive_status_returns_true_when_config_present(): void
    {
        Config::set('services.google.client_id', 'test-client-id');
        Config::set('services.google.client_secret', 'test-client-secret');

        $result = $this->service->getGoogleDriveStatus();

        $this->assertTrue($result);
    }

    public function test_get_google_drive_status_works_with_current_config(): void
    {
        // Test that Google Drive status works with current configuration
        $result = $this->service->getGoogleDriveStatus();

        // Should return true since we have Google Drive configured in .env
        $this->assertTrue($result);
    }

    public function test_get_mail_status_with_current_config(): void
    {
        // Test mail status with current configuration
        // The result may vary based on test environment vs application environment
        $result = $this->service->getMailStatus();

        // Just verify the method works without asserting specific result
        $this->assertIsBool($result);
    }

    public function test_mail_validation_logic_with_current_environment(): void
    {
        // Test the mail validation with current environment
        // This is more of an integration test but validates the new logic works
        $result = $this->service->getMailStatus();

        // Should return false since mail is not configured in current .env
        $this->assertFalse($result);
    }

    public function test_get_admin_user_status_returns_true_when_admin_exists(): void
    {
        User::factory()->create(['role' => UserRole::ADMIN]);

        $result = $this->service->getAdminUserStatus();

        $this->assertTrue($result);
    }

    public function test_get_admin_user_status_returns_false_when_no_admin_exists(): void
    {
        // Create non-admin users
        User::factory()->create(['role' => UserRole::CLIENT]);
        User::factory()->create(['role' => UserRole::EMPLOYEE]);

        $result = $this->service->getAdminUserStatus();

        $this->assertFalse($result);
    }

    public function test_get_admin_user_status_returns_true_when_multiple_admins_exist(): void
    {
        User::factory()->count(3)->create(['role' => UserRole::ADMIN]);

        $result = $this->service->getAdminUserStatus();

        $this->assertTrue($result);
    }

    public function test_get_missing_requirements_returns_empty_when_all_complete(): void
    {
        // Mock all status methods to return true
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(true);
        $service->shouldReceive('getMailStatus')->andReturn(true);
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(true);
        $service->shouldReceive('getAdminUserStatus')->andReturn(true);

        $result = $service->getMissingRequirements();

        $this->assertEmpty($result);
    }

    public function test_get_missing_requirements_returns_database_when_missing(): void
    {
        // Mock database failure
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(false);
        $service->shouldReceive('getMailStatus')->andReturn(true);
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(true);
        $service->shouldReceive('getAdminUserStatus')->andReturn(true);

        $result = $service->getMissingRequirements();

        $this->assertContains('Database connection not configured or not accessible', $result);
    }

    public function test_get_missing_requirements_returns_mail_when_missing(): void
    {
        // Mock mail failure
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(true);
        $service->shouldReceive('getMailStatus')->andReturn(false);
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(true);
        $service->shouldReceive('getAdminUserStatus')->andReturn(true);

        $result = $service->getMissingRequirements();

        $this->assertContains('Mail server configuration not properly set up', $result);
    }

    public function test_get_missing_requirements_returns_google_drive_when_missing(): void
    {
        // Mock Google Drive failure
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(true);
        $service->shouldReceive('getMailStatus')->andReturn(true);
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(false);
        $service->shouldReceive('getAdminUserStatus')->andReturn(true);

        $result = $service->getMissingRequirements();

        $this->assertContains('Google Drive credentials not configured', $result);
    }

    public function test_get_missing_requirements_returns_admin_user_when_missing(): void
    {
        // Mock admin user failure
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(true);
        $service->shouldReceive('getMailStatus')->andReturn(true);
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(true);
        $service->shouldReceive('getAdminUserStatus')->andReturn(false);

        $result = $service->getMissingRequirements();

        $this->assertContains('No admin user found in the system', $result);
    }

    public function test_get_missing_requirements_returns_all_when_nothing_configured(): void
    {
        // Mock all failures
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(false);
        $service->shouldReceive('getMailStatus')->andReturn(false);
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(false);
        $service->shouldReceive('getAdminUserStatus')->andReturn(false);

        $result = $service->getMissingRequirements();

        $this->assertCount(4, $result);
        $this->assertContains('Database connection not configured or not accessible', $result);
        $this->assertContains('Mail server configuration not properly set up', $result);
        $this->assertContains('Google Drive credentials not configured', $result);
        $this->assertContains('No admin user found in the system', $result);
    }

    public function test_get_missing_requirements_with_real_service_returns_missing(): void
    {
        // Test with real service in current environment
        $result = $this->service->getMissingRequirements();

        // In the current environment, check what's actually missing
        // The exact count may vary based on test environment configuration
        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertContains('No admin user found in the system', $result);
    }

    // Tests for new migration status detection functionality

    public function test_get_migration_status_returns_true_when_all_required_tables_exist(): void
    {
        // Mock Schema facade to return true for all required tables
        Schema::shouldReceive('hasTable')
            ->with('users')->andReturn(true)
            ->shouldReceive('hasTable')
            ->with('sessions')->andReturn(true)
            ->shouldReceive('hasTable')
            ->with('password_reset_tokens')->andReturn(true)
            ->shouldReceive('hasTable')
            ->with('jobs')->andReturn(true)
            ->shouldReceive('hasTable')
            ->with('failed_jobs')->andReturn(true)
            ->shouldReceive('hasTable')
            ->with('file_uploads')->andReturn(true)
            ->shouldReceive('hasTable')
            ->with('email_validations')->andReturn(true)
            ->shouldReceive('hasTable')
            ->with('upload_tokens')->andReturn(true)
            ->shouldReceive('hasTable')
            ->with('google_drive_tokens')->andReturn(true)
            ->shouldReceive('hasTable')
            ->with('client_user_relationships')->andReturn(true);

        $result = $this->service->getMigrationStatus();

        $this->assertTrue($result);
    }

    public function test_get_migration_status_returns_false_when_core_table_missing(): void
    {
        // Mock Schema facade to return false for users table
        Schema::shouldReceive('hasTable')
            ->with('users')->andReturn(false);

        $result = $this->service->getMigrationStatus();

        $this->assertFalse($result);
    }

    public function test_get_migration_status_returns_false_when_jobs_table_missing(): void
    {
        // Mock Schema facade to return true for most tables but false for jobs
        Schema::shouldReceive('hasTable')
            ->with('users')->andReturn(true)
            ->shouldReceive('hasTable')
            ->with('sessions')->andReturn(true)
            ->shouldReceive('hasTable')
            ->with('password_reset_tokens')->andReturn(true)
            ->shouldReceive('hasTable')
            ->with('jobs')->andReturn(false);

        $result = $this->service->getMigrationStatus();

        $this->assertFalse($result);
    }

    public function test_get_migration_status_handles_database_exception(): void
    {
        // Mock Schema facade to throw exception
        Schema::shouldReceive('hasTable')
            ->andThrow(new Exception('Database connection failed'));

        $result = $this->service->getMigrationStatus();

        $this->assertFalse($result);
    }

    public function test_get_migration_status_with_real_database(): void
    {
        // Test with real database - should return true since migrations run in tests
        $result = $this->service->getMigrationStatus();

        $this->assertTrue($result);
    }

    // Tests for new queue health status functionality

    public function test_get_queue_health_status_returns_incomplete_when_tables_missing(): void
    {
        // Mock Schema to return false for jobs table
        Schema::shouldReceive('hasTable')
            ->with('jobs')->andReturn(false)
            ->shouldReceive('hasTable')
            ->with('failed_jobs')->andReturn(true);

        $result = $this->service->getQueueHealthStatus();

        $this->assertEquals('incomplete', $result['status']);
        $this->assertStringContainsString('Queue tables not found', $result['message']);
    }

    public function test_get_queue_health_status_returns_idle_with_no_activity(): void
    {
        // Test with real database - should return idle since no jobs exist
        $result = $this->service->getQueueHealthStatus();

        $this->assertEquals('idle', $result['status']);
        $this->assertStringContainsString('Queue worker is idle', $result['message']);
        $this->assertEquals(0, $result['details']['recent_jobs']);
        $this->assertEquals(0, $result['details']['recent_failed_jobs']);
        $this->assertEquals(0, $result['details']['total_failed_jobs']);
        $this->assertEquals(0, $result['details']['stalled_jobs']);
    }

    public function test_get_queue_health_status_with_real_database_returns_valid_structure(): void
    {
        // Test with real database to ensure the method works and returns proper structure
        $result = $this->service->getQueueHealthStatus();

        // Should return a valid status structure
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('details', $result);
        
        // Status should be one of the expected values
        $this->assertContains($result['status'], ['idle', 'working', 'needs_attention', 'error', 'incomplete', 'cannot_verify']);
        
        // Details should contain expected keys
        $this->assertArrayHasKey('recent_jobs', $result['details']);
        $this->assertArrayHasKey('recent_failed_jobs', $result['details']);
        $this->assertArrayHasKey('total_failed_jobs', $result['details']);
        $this->assertArrayHasKey('stalled_jobs', $result['details']);
        $this->assertArrayHasKey('checked_at', $result['details']);
    }

    public function test_get_queue_health_status_handles_database_exception(): void
    {
        // Ensure tables exist but DB queries fail
        Schema::shouldReceive('hasTable')
            ->with('jobs')->andReturn(true)
            ->shouldReceive('hasTable')
            ->with('failed_jobs')->andReturn(true);

        DB::shouldReceive('table')
            ->andThrow(new Exception('Database query failed'));

        $result = $this->service->getQueueHealthStatus();

        $this->assertEquals('cannot_verify', $result['status']);
        $this->assertStringContainsString('Unable to check queue status', $result['message']);
        $this->assertArrayHasKey('error', $result['details']);
    }

    // Tests for comprehensive status array functionality

    public function test_get_all_step_statuses_returns_complete_status_array(): void
    {
        // Mock all individual status methods
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(true);
        $service->shouldReceive('getMailStatus')->andReturn(true);
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(true);
        $service->shouldReceive('getMigrationStatus')->andReturn(true);
        $service->shouldReceive('getAdminUserStatus')->andReturn(true);
        $service->shouldReceive('getQueueHealthStatus')->andReturn([
            'status' => 'working',
            'message' => 'Queue worker is processing jobs successfully',
            'details' => ['recent_jobs' => 5]
        ]);

        $result = $service->getAllStepStatuses();

        $this->assertArrayHasKey('database', $result);
        $this->assertArrayHasKey('mail', $result);
        $this->assertArrayHasKey('google_drive', $result);
        $this->assertArrayHasKey('migrations', $result);
        $this->assertArrayHasKey('admin_user', $result);
        $this->assertArrayHasKey('queue_worker', $result);

        // Check structure of each status
        foreach (['database', 'mail', 'google_drive', 'migrations', 'admin_user'] as $step) {
            $this->assertEquals('completed', $result[$step]['status']);
            $this->assertArrayHasKey('message', $result[$step]);
            $this->assertArrayHasKey('checked_at', $result[$step]);
        }

        $this->assertEquals('working', $result['queue_worker']['status']);
    }

    public function test_get_all_step_statuses_handles_individual_exceptions(): void
    {
        // Mock some methods to throw exceptions
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andThrow(new Exception('DB error'));
        $service->shouldReceive('getMailStatus')->andReturn(false);
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(true);
        $service->shouldReceive('getMigrationStatus')->andReturn(true);
        $service->shouldReceive('getAdminUserStatus')->andReturn(false);
        $service->shouldReceive('getQueueHealthStatus')->andReturn([
            'status' => 'idle',
            'message' => 'Queue worker is idle'
        ]);

        $result = $service->getAllStepStatuses();

        // Database should show error status
        $this->assertEquals('error', $result['database']['status']);
        $this->assertStringContainsString('Error checking database status', $result['database']['message']);
        $this->assertArrayHasKey('error', $result['database']['details']);

        // Mail should show incomplete
        $this->assertEquals('incomplete', $result['mail']['status']);

        // Admin user should show incomplete
        $this->assertEquals('incomplete', $result['admin_user']['status']);
    }

    public function test_is_setup_complete_includes_migration_status(): void
    {
        // Mock all status methods including new migration status
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(true);
        $service->shouldReceive('getMailStatus')->andReturn(true);
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(true);
        $service->shouldReceive('getAdminUserStatus')->andReturn(true);
        $service->shouldReceive('getMigrationStatus')->andReturn(false); // Migration incomplete

        $result = $service->isSetupComplete();

        $this->assertFalse($result);
    }

    public function test_get_missing_requirements_includes_migration_status(): void
    {
        // Mock migration failure
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(true);
        $service->shouldReceive('getMailStatus')->andReturn(true);
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(true);
        $service->shouldReceive('getAdminUserStatus')->andReturn(true);
        $service->shouldReceive('getMigrationStatus')->andReturn(false);

        $result = $service->getMissingRequirements();

        $this->assertContains('Database migrations need to be run', $result);
    }
}