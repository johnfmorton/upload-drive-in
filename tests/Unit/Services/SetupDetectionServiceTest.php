<?php

namespace Tests\Unit\Services;

use App\Services\SetupDetectionService;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
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

    public function test_is_setup_complete_returns_true_when_all_requirements_met(): void
    {
        // Mock all individual status methods to return true
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(true);
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
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(true);
        $service->shouldReceive('getAdminUserStatus')->andReturn(false);

        $result = $service->isSetupComplete();

        $this->assertFalse($result);
    }

    public function test_get_database_status_returns_false_when_env_vars_missing(): void
    {
        // The real service should return false when env vars are missing (current state)
        $result = $this->service->getDatabaseStatus();

        $this->assertFalse($result);
    }

    public function test_get_google_drive_status_returns_true_when_config_present(): void
    {
        Config::set('services.google.client_id', 'test-client-id');
        Config::set('services.google.client_secret', 'test-client-secret');

        $result = $this->service->getGoogleDriveStatus();

        $this->assertTrue($result);
    }

    public function test_get_google_drive_status_returns_false_when_client_id_missing(): void
    {
        Config::set('services.google.client_id', null);
        Config::set('services.google.client_secret', 'test-client-secret');

        $result = $this->service->getGoogleDriveStatus();

        $this->assertFalse($result);
    }

    public function test_get_google_drive_status_returns_false_when_client_secret_missing(): void
    {
        Config::set('services.google.client_id', 'test-client-id');
        Config::set('services.google.client_secret', null);

        $result = $this->service->getGoogleDriveStatus();

        $this->assertFalse($result);
    }

    public function test_get_google_drive_status_returns_false_when_both_missing(): void
    {
        Config::set('services.google.client_id', null);
        Config::set('services.google.client_secret', null);

        $result = $this->service->getGoogleDriveStatus();

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
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(true);
        $service->shouldReceive('getAdminUserStatus')->andReturn(true);

        $result = $service->getMissingRequirements();

        $this->assertContains('Database connection not configured or not accessible', $result);
    }

    public function test_get_missing_requirements_returns_google_drive_when_missing(): void
    {
        // Mock Google Drive failure
        $service = Mockery::mock(SetupDetectionService::class)->makePartial();
        $service->shouldReceive('getDatabaseStatus')->andReturn(true);
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
        $service->shouldReceive('getGoogleDriveStatus')->andReturn(false);
        $service->shouldReceive('getAdminUserStatus')->andReturn(false);

        $result = $service->getMissingRequirements();

        $this->assertCount(3, $result);
        $this->assertContains('Database connection not configured or not accessible', $result);
        $this->assertContains('Google Drive credentials not configured', $result);
        $this->assertContains('No admin user found in the system', $result);
    }

    public function test_get_missing_requirements_with_real_service_returns_all_missing(): void
    {
        // Test with real service in current environment (no config)
        $result = $this->service->getMissingRequirements();

        // Should have all three requirements missing
        $this->assertCount(3, $result);
        $this->assertContains('Database connection not configured or not accessible', $result);
        $this->assertContains('Google Drive credentials not configured', $result);
        $this->assertContains('No admin user found in the system', $result);
    }
}