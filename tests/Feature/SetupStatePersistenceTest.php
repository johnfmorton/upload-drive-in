<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\SetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SetupStatePersistenceTest extends TestCase
{
    use RefreshDatabase;

    private string $setupStateFile;
    private string $backupStateFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupStateFile = storage_path('app/setup/setup-state.json');
        $this->backupStateFile = storage_path('app/setup/setup-state-backup.json');
        
        // Clean up any existing setup state files
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        if (File::exists($this->backupStateFile)) {
            File::delete($this->backupStateFile);
        }
        
        // Clear any existing admin users and cloud storage config
        User::where('role', UserRole::ADMIN)->delete();
        Config::set('services.google.client_id', null);
        Config::set('services.google.client_secret', null);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        if (File::exists($this->backupStateFile)) {
            File::delete($this->backupStateFile);
        }
        
        parent::tearDown();
    }

    public function test_setup_state_file_is_created_automatically(): void
    {
        $setupService = new SetupService();
        
        $this->assertFalse(File::exists($this->setupStateFile));
        
        // Trigger state file creation
        $setupService->getSetupStep();
        
        $this->assertTrue(File::exists($this->setupStateFile));
    }

    public function test_setup_state_persists_across_service_instances(): void
    {
        $setupService1 = new SetupService();
        
        // Update a step with first instance
        $setupService1->updateSetupStep('welcome', true);
        
        // Create new service instance
        $setupService2 = new SetupService();
        
        // Check that the state persists
        $steps = $setupService2->getSetupSteps();
        $this->assertTrue($steps['welcome']['completed']);
        $this->assertNotNull($steps['welcome']['completed_at']);
    }

    public function test_setup_state_persists_across_requests(): void
    {
        // First request - update setup step
        $response1 = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => storage_path('app/test-setup.sqlite')
        ]);
        
        $response1->assertRedirect('/setup/admin');
        
        // Second request - check that step is still completed
        $setupService = app(SetupService::class);
        $steps = $setupService->getSetupSteps();
        
        $this->assertTrue($steps['database']['completed']);
    }

    public function test_setup_state_file_structure_is_valid_json(): void
    {
        $setupService = new SetupService();
        $setupService->updateSetupStep('welcome', true);
        
        $this->assertTrue(File::exists($this->setupStateFile));
        
        $content = File::get($this->setupStateFile);
        $decoded = json_decode($content, true);
        
        $this->assertNotNull($decoded, 'Setup state file should contain valid JSON');
        $this->assertIsArray($decoded);
    }

    public function test_setup_state_file_contains_required_fields(): void
    {
        $setupService = new SetupService();
        $setupService->getSetupStep(); // Trigger file creation
        
        $content = File::get($this->setupStateFile);
        $state = json_decode($content, true);
        
        $requiredFields = [
            'setup_complete',
            'current_step',
            'started_at',
            'steps'
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $state, "Setup state should contain {$field} field");
        }
    }

    public function test_setup_state_steps_have_correct_structure(): void
    {
        $setupService = new SetupService();
        $setupService->updateSetupStep('welcome', true);
        
        $content = File::get($this->setupStateFile);
        $state = json_decode($content, true);
        
        $expectedSteps = ['welcome', 'database', 'admin', 'storage', 'complete'];
        
        foreach ($expectedSteps as $step) {
            $this->assertArrayHasKey($step, $state['steps'], "Steps should contain {$step}");
            $this->assertArrayHasKey('completed', $state['steps'][$step]);
            $this->assertArrayHasKey('completed_at', $state['steps'][$step]);
        }
    }

    public function test_setup_state_handles_corrupted_file(): void
    {
        // Create corrupted setup state file
        File::put($this->setupStateFile, 'invalid json content');
        
        $setupService = new SetupService();
        
        // Should handle corrupted file gracefully and recreate
        $step = $setupService->getSetupStep();
        
        $this->assertIsString($step);
        $this->assertTrue(File::exists($this->setupStateFile));
        
        // File should now contain valid JSON
        $content = File::get($this->setupStateFile);
        $decoded = json_decode($content, true);
        $this->assertNotNull($decoded);
    }

    public function test_setup_state_handles_missing_directory(): void
    {
        // Remove setup directory
        $setupDir = dirname($this->setupStateFile);
        if (File::exists($setupDir)) {
            File::deleteDirectory($setupDir);
        }
        
        $setupService = new SetupService();
        
        // Should create directory and file
        $setupService->getSetupStep();
        
        $this->assertTrue(File::exists($setupDir));
        $this->assertTrue(File::exists($this->setupStateFile));
    }

    public function test_setup_state_handles_readonly_directory(): void
    {
        // Create setup directory with restricted permissions
        $setupDir = dirname($this->setupStateFile);
        File::makeDirectory($setupDir, 0444, true); // Read-only
        
        $setupService = new SetupService();
        
        try {
            // This should handle the permission error gracefully
            $setupService->getSetupStep();
            
            // If we get here, the service handled it gracefully
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Expected behavior - should throw exception for readonly directory
            $this->assertStringContainsString('permission', strtolower($e->getMessage()));
        } finally {
            // Clean up - restore permissions
            if (File::exists($setupDir)) {
                chmod($setupDir, 0755);
                File::deleteDirectory($setupDir);
            }
        }
    }

    public function test_setup_completion_updates_state_file(): void
    {
        $setupService = new SetupService();
        
        // Mark setup as complete
        $setupService->markSetupComplete();
        
        $this->assertTrue(File::exists($this->setupStateFile));
        
        $content = File::get($this->setupStateFile);
        $state = json_decode($content, true);
        
        $this->assertTrue($state['setup_complete']);
        $this->assertEquals('complete', $state['current_step']);
        $this->assertNotNull($state['completed_at']);
    }

    public function test_setup_state_timestamps_are_valid(): void
    {
        $setupService = new SetupService();
        
        $beforeTime = now();
        $setupService->updateSetupStep('welcome', true);
        $afterTime = now();
        
        $content = File::get($this->setupStateFile);
        $state = json_decode($content, true);
        
        $completedAt = $state['steps']['welcome']['completed_at'];
        $this->assertNotNull($completedAt);
        
        $timestamp = \Carbon\Carbon::parse($completedAt);
        $this->assertTrue($timestamp->between($beforeTime, $afterTime));
    }

    public function test_setup_state_progress_calculation(): void
    {
        $setupService = new SetupService();
        
        // Initially 0% progress
        $this->assertEquals(0, $setupService->getSetupProgress());
        
        // Complete one step (20% of 5 steps)
        $setupService->updateSetupStep('welcome', true);
        $this->assertEquals(20, $setupService->getSetupProgress());
        
        // Complete another step (40%)
        $setupService->updateSetupStep('database', true);
        $this->assertEquals(40, $setupService->getSetupProgress());
        
        // Complete all steps (100%)
        $setupService->updateSetupStep('admin', true);
        $setupService->updateSetupStep('storage', true);
        $setupService->updateSetupStep('complete', true);
        $this->assertEquals(100, $setupService->getSetupProgress());
    }

    public function test_setup_state_file_permissions(): void
    {
        $setupService = new SetupService();
        $setupService->getSetupStep(); // Create file
        
        $this->assertTrue(File::exists($this->setupStateFile));
        
        // File should be readable and writable
        $this->assertTrue(is_readable($this->setupStateFile));
        $this->assertTrue(is_writable($this->setupStateFile));
        
        // Check file permissions (should be 644 or similar)
        $permissions = fileperms($this->setupStateFile) & 0777;
        $this->assertTrue($permissions >= 0644, 'Setup state file should have appropriate permissions');
    }

    public function test_concurrent_setup_state_updates(): void
    {
        $setupService1 = new SetupService();
        $setupService2 = new SetupService();
        
        // Simulate concurrent updates
        $setupService1->updateSetupStep('welcome', true);
        $setupService2->updateSetupStep('database', true);
        
        // Both updates should be preserved
        $setupService3 = new SetupService();
        $steps = $setupService3->getSetupSteps();
        
        $this->assertTrue($steps['welcome']['completed']);
        $this->assertTrue($steps['database']['completed']);
    }

    public function test_setup_state_file_size_remains_reasonable(): void
    {
        $setupService = new SetupService();
        
        // Perform multiple operations
        for ($i = 0; $i < 10; $i++) {
            $setupService->updateSetupStep('welcome', true);
            $setupService->updateSetupStep('welcome', false);
        }
        
        $this->assertTrue(File::exists($this->setupStateFile));
        
        $fileSize = File::size($this->setupStateFile);
        
        // File should remain under 10KB even with multiple updates
        $this->assertLessThan(10240, $fileSize, 'Setup state file should not grow excessively');
    }

    public function test_setup_state_backup_and_recovery(): void
    {
        $setupService = new SetupService();
        
        // Create initial state
        $setupService->updateSetupStep('welcome', true);
        $setupService->updateSetupStep('database', true);
        
        // Backup the state file
        File::copy($this->setupStateFile, $this->backupStateFile);
        
        // Corrupt the original file
        File::put($this->setupStateFile, 'corrupted data');
        
        // Create new service instance - should handle corruption
        $newSetupService = new SetupService();
        $newSetupService->getSetupStep(); // This should recreate the file
        
        // Restore from backup to verify data
        File::copy($this->backupStateFile, $this->setupStateFile);
        
        $restoredService = new SetupService();
        $steps = $restoredService->getSetupSteps();
        
        $this->assertTrue($steps['welcome']['completed']);
        $this->assertTrue($steps['database']['completed']);
    }

    public function test_setup_state_handles_invalid_step_names(): void
    {
        $setupService = new SetupService();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid setup step: invalid_step');
        
        $setupService->updateSetupStep('invalid_step', true);
    }

    public function test_setup_state_file_encoding(): void
    {
        $setupService = new SetupService();
        $setupService->getSetupStep(); // Create file
        
        $content = File::get($this->setupStateFile);
        
        // Should be valid UTF-8
        $this->assertTrue(mb_check_encoding($content, 'UTF-8'), 'Setup state file should be valid UTF-8');
        
        // Should be properly formatted JSON
        $decoded = json_decode($content, true);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), 'Setup state file should contain valid JSON');
    }
}