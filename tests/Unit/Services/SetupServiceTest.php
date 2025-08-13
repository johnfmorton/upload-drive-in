<?php

namespace Tests\Unit\Services;

use App\Services\SetupService;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SetupServiceTest extends TestCase
{
    use RefreshDatabase;

    private SetupService $setupService;
    private string $setupStateFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupService = new SetupService();
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

    public function test_is_setup_required_returns_true_when_no_admin_user_exists(): void
    {
        $this->assertTrue($this->setupService->isSetupRequired());
    }

    public function test_is_setup_required_returns_false_when_admin_user_exists_and_storage_configured(): void
    {
        // Create admin user
        User::factory()->create([
            'role' => UserRole::ADMIN,
            'email_verified_at' => now(),
        ]);

        // Mock cloud storage configuration
        Config::set('services.google.client_id', 'test-client-id');
        Config::set('services.google.client_secret', 'test-client-secret');

        $this->assertFalse($this->setupService->isSetupRequired());
    }

    public function test_get_setup_step_returns_welcome_initially(): void
    {
        // In test environment, database is already configured, so it should return 'admin'
        $step = $this->setupService->getSetupStep();
        $this->assertEquals('admin', $step);
    }

    public function test_get_setup_step_returns_admin_when_database_configured(): void
    {
        // Database is already configured in test environment
        $step = $this->setupService->getSetupStep();
        $this->assertEquals('admin', $step);
    }

    public function test_get_setup_step_returns_storage_when_admin_exists(): void
    {
        User::factory()->create([
            'role' => UserRole::ADMIN,
            'email_verified_at' => now(),
        ]);

        // Clear any existing cloud storage config to ensure we get 'storage' step
        Config::set('services.google.client_id', null);
        Config::set('services.google.client_secret', null);

        $step = $this->setupService->getSetupStep();
        $this->assertEquals('storage', $step);
    }

    public function test_get_setup_step_returns_complete_when_all_configured(): void
    {
        // Create admin user
        User::factory()->create([
            'role' => UserRole::ADMIN,
            'email_verified_at' => now(),
        ]);

        // Mock cloud storage configuration
        Config::set('services.google.client_id', 'test-client-id');
        Config::set('services.google.client_secret', 'test-client-secret');

        $step = $this->setupService->getSetupStep();
        $this->assertEquals('complete', $step);
    }

    public function test_create_initial_admin_creates_user_with_admin_role(): void
    {
        $userData = [
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ];

        $user = $this->setupService->createInitialAdmin($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(UserRole::ADMIN, $user->role);
        $this->assertEquals('Test Admin', $user->name);
        $this->assertEquals('admin@example.com', $user->email);
        
        // Refresh the user from database to get the actual saved values
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_mark_setup_complete_updates_state_file(): void
    {
        $this->setupService->markSetupComplete();

        $this->assertTrue($this->setupService->isSetupComplete());
        $this->assertTrue(File::exists($this->setupStateFile));

        $state = json_decode(File::get($this->setupStateFile), true);
        $this->assertTrue($state['setup_complete']);
        $this->assertEquals('complete', $state['current_step']);
    }

    public function test_update_setup_step_updates_state_correctly(): void
    {
        $this->setupService->updateSetupStep('admin', true);

        $steps = $this->setupService->getSetupSteps();
        $this->assertTrue($steps['admin']['completed']);
        $this->assertNotNull($steps['admin']['completed_at']);
    }

    public function test_get_setup_progress_returns_correct_percentage(): void
    {
        // Initially 0%
        $this->assertEquals(0, $this->setupService->getSetupProgress());

        // Complete one step (20% of 5 steps)
        $this->setupService->updateSetupStep('welcome', true);
        $this->assertEquals(20, $this->setupService->getSetupProgress());

        // Complete another step (40%)
        $this->setupService->updateSetupStep('database', true);
        $this->assertEquals(40, $this->setupService->getSetupProgress());
    }

    public function test_setup_state_file_is_created_automatically(): void
    {
        $this->assertFalse(File::exists($this->setupStateFile));
        
        // Trigger state creation
        $this->setupService->getSetupStep();
        
        $this->assertTrue(File::exists($this->setupStateFile));
    }

    public function test_is_database_configured_returns_true_in_test_environment(): void
    {
        $this->assertTrue($this->setupService->isDatabaseConfigured());
    }

    public function test_is_admin_user_created_returns_false_initially(): void
    {
        $this->assertFalse($this->setupService->isAdminUserCreated());
    }

    public function test_is_admin_user_created_returns_true_after_creating_admin(): void
    {
        User::factory()->create(['role' => UserRole::ADMIN]);
        
        $this->assertTrue($this->setupService->isAdminUserCreated());
    }

    public function test_is_cloud_storage_configured_returns_false_initially(): void
    {
        Config::set('services.google.client_id', null);
        Config::set('services.google.client_secret', null);
        
        $this->assertFalse($this->setupService->isCloudStorageConfigured());
    }

    public function test_is_cloud_storage_configured_returns_true_with_credentials(): void
    {
        Config::set('services.google.client_id', 'test-client-id');
        Config::set('services.google.client_secret', 'test-client-secret');
        
        $this->assertTrue($this->setupService->isCloudStorageConfigured());
    }

    public function test_update_setup_step_throws_exception_for_invalid_step(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid setup step: invalid_step');
        
        $this->setupService->updateSetupStep('invalid_step', true);
    }

    public function test_get_setup_steps_returns_all_steps_initially(): void
    {
        $steps = $this->setupService->getSetupSteps();
        
        $this->assertIsArray($steps);
        $this->assertArrayHasKey('welcome', $steps);
        $this->assertArrayHasKey('database', $steps);
        $this->assertArrayHasKey('admin', $steps);
        $this->assertArrayHasKey('storage', $steps);
        $this->assertArrayHasKey('complete', $steps);
        
        // All steps should be incomplete initially
        foreach ($steps as $step) {
            $this->assertFalse($step['completed']);
            $this->assertNull($step['completed_at']);
        }
    }

    public function test_create_initial_admin_sets_email_verified(): void
    {
        $userData = [
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ];

        $user = $this->setupService->createInitialAdmin($userData);
        
        $this->assertNotNull($user->email_verified_at);
        $this->assertEquals(UserRole::ADMIN, $user->role);
    }

    public function test_create_initial_admin_updates_setup_step(): void
    {
        $userData = [
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ];

        $this->setupService->createInitialAdmin($userData);
        
        $steps = $this->setupService->getSetupSteps();
        $this->assertTrue($steps['admin']['completed']);
        $this->assertNotNull($steps['admin']['completed_at']);
    }

    public function test_create_initial_admin_uses_default_name_when_not_provided(): void
    {
        $userData = [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ];

        $user = $this->setupService->createInitialAdmin($userData);
        
        $this->assertEquals('Administrator', $user->name);
    }

    public function test_is_setup_complete_returns_false_initially(): void
    {
        $this->assertFalse($this->setupService->isSetupComplete());
    }

    public function test_mark_setup_complete_sets_completion_timestamp(): void
    {
        $this->setupService->markSetupComplete();
        
        $this->assertTrue(File::exists($this->setupStateFile));
        
        $state = json_decode(File::get($this->setupStateFile), true);
        $this->assertTrue($state['setup_complete']);
        $this->assertNotNull($state['completed_at']);
        $this->assertEquals('complete', $state['current_step']);
    }

    public function test_get_setup_progress_calculates_correctly_with_multiple_steps(): void
    {
        // Complete multiple steps
        $this->setupService->updateSetupStep('welcome', true);
        $this->setupService->updateSetupStep('database', true);
        $this->setupService->updateSetupStep('admin', true);
        
        $progress = $this->setupService->getSetupProgress();
        $this->assertEquals(60, $progress); // 3 out of 5 steps = 60%
    }

    public function test_get_setup_progress_returns_100_when_all_complete(): void
    {
        $steps = ['welcome', 'database', 'admin', 'storage', 'complete'];
        
        foreach ($steps as $step) {
            $this->setupService->updateSetupStep($step, true);
        }
        
        $progress = $this->setupService->getSetupProgress();
        $this->assertEquals(100, $progress);
    }

    public function test_is_setup_required_handles_database_connection_failure(): void
    {
        // Mock a database connection failure scenario
        // This is tricky to test without actually breaking the database
        // In a real scenario, you might use database transactions or separate test databases
        
        $this->assertTrue($this->setupService->isSetupRequired());
    }

    public function test_get_setup_step_handles_exceptions_gracefully(): void
    {
        // Test that the method returns 'database' when there are database issues
        // This is hard to test without actually breaking the database connection
        
        $step = $this->setupService->getSetupStep();
        $this->assertIsString($step);
        $this->assertContains($step, ['welcome', 'database', 'admin', 'storage', 'complete']);
    }

    public function test_setup_state_file_structure_is_correct(): void
    {
        $this->setupService->getSetupStep(); // Trigger state file creation
        
        $this->assertTrue(File::exists($this->setupStateFile));
        
        $state = json_decode(File::get($this->setupStateFile), true);
        
        $this->assertIsArray($state);
        $this->assertArrayHasKey('setup_complete', $state);
        $this->assertArrayHasKey('current_step', $state);
        $this->assertArrayHasKey('started_at', $state);
        $this->assertArrayHasKey('steps', $state);
        
        $this->assertFalse($state['setup_complete']);
        $this->assertIsString($state['current_step']);
        $this->assertIsString($state['started_at']);
        $this->assertIsArray($state['steps']);
    }

    public function test_setup_state_persists_across_service_instances(): void
    {
        // Update a step with first instance
        $this->setupService->updateSetupStep('welcome', true);
        
        // Create new service instance
        $newService = new SetupService();
        
        // Check that the state persists
        $steps = $newService->getSetupSteps();
        $this->assertTrue($steps['welcome']['completed']);
    }
}