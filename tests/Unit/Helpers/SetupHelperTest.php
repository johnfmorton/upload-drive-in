<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SetupHelper;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SetupHelperTest extends TestCase
{
    use RefreshDatabase;

    private string $setupStateFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupStateFile = storage_path('app/setup/setup-state.json');
        
        // Clean up any existing setup state file
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
    }

    protected function tearDown(): void
    {
        // Clean up setup state file after each test
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        parent::tearDown();
    }

    public function test_is_setup_required_returns_true_when_no_admin_exists(): void
    {
        $this->assertTrue(SetupHelper::isSetupRequired());
    }

    public function test_is_setup_complete_returns_false_initially(): void
    {
        $this->assertFalse(SetupHelper::isSetupComplete());
    }

    public function test_get_current_setup_step_returns_admin_in_test_environment(): void
    {
        // In test environment, database is configured, so should return 'admin'
        $step = SetupHelper::getCurrentSetupStep();
        $this->assertEquals('admin', $step);
    }

    public function test_get_setup_progress_returns_zero_initially(): void
    {
        $progress = SetupHelper::getSetupProgress();
        $this->assertEquals(0, $progress);
    }

    public function test_is_step_completed_returns_false_for_uncompleted_steps(): void
    {
        $this->assertFalse(SetupHelper::isStepCompleted('admin'));
        $this->assertFalse(SetupHelper::isStepCompleted('storage'));
    }

    public function test_should_bypass_setup_allows_setup_routes(): void
    {
        $this->assertTrue(SetupHelper::shouldBypassSetup('setup.welcome'));
        $this->assertTrue(SetupHelper::shouldBypassSetup(null, '/setup/admin'));
    }

    public function test_should_bypass_setup_allows_asset_requests(): void
    {
        $this->assertTrue(SetupHelper::shouldBypassSetup(null, '/build/app.css'));
        $this->assertTrue(SetupHelper::shouldBypassSetup(null, '/css/app.css'));
        $this->assertTrue(SetupHelper::shouldBypassSetup(null, '/js/app.js'));
        $this->assertTrue(SetupHelper::shouldBypassSetup(null, '/images/logo.png'));
        $this->assertTrue(SetupHelper::shouldBypassSetup(null, '/favicon.ico'));
    }

    public function test_should_bypass_setup_allows_health_checks(): void
    {
        $this->assertTrue(SetupHelper::shouldBypassSetup(null, '/health'));
        $this->assertTrue(SetupHelper::shouldBypassSetup(null, '/up'));
        $this->assertTrue(SetupHelper::shouldBypassSetup(null, '/health/check'));
    }

    public function test_should_bypass_setup_blocks_regular_routes(): void
    {
        $this->assertFalse(SetupHelper::shouldBypassSetup('dashboard'));
        $this->assertFalse(SetupHelper::shouldBypassSetup(null, '/admin'));
        $this->assertFalse(SetupHelper::shouldBypassSetup(null, '/login'));
    }

    public function test_get_step_display_name_returns_correct_names(): void
    {
        $this->assertEquals('Welcome', SetupHelper::getStepDisplayName('welcome'));
        $this->assertEquals('Database Configuration', SetupHelper::getStepDisplayName('database'));
        $this->assertEquals('Admin User Creation', SetupHelper::getStepDisplayName('admin'));
        $this->assertEquals('Cloud Storage Setup', SetupHelper::getStepDisplayName('storage'));
        $this->assertEquals('Setup Complete', SetupHelper::getStepDisplayName('complete'));
    }

    public function test_get_step_description_returns_correct_descriptions(): void
    {
        $this->assertStringContainsString('Welcome', SetupHelper::getStepDescription('welcome'));
        $this->assertStringContainsString('database', SetupHelper::getStepDescription('database'));
        $this->assertStringContainsString('administrator', SetupHelper::getStepDescription('admin'));
        $this->assertStringContainsString('cloud storage', SetupHelper::getStepDescription('storage'));
        $this->assertStringContainsString('completed', SetupHelper::getStepDescription('complete'));
    }
}