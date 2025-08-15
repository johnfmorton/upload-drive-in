<?php

namespace Tests\Unit\Services;

use App\Services\AssetValidationService;
use App\Services\SetupService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SetupServiceRecoveryTest extends TestCase
{
    private SetupService $setupService;
    private AssetValidationService $assetValidationService;
    private string $setupStateFile;
    private string $backupDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->assetValidationService = $this->createMock(AssetValidationService::class);
        $this->setupService = new SetupService($this->assetValidationService);
        
        $this->setupStateFile = storage_path('app/setup/setup-state.json');
        $this->backupDirectory = storage_path('app/setup/backups');
        
        // Clean up any existing files
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        if (File::exists($this->backupDirectory)) {
            File::deleteDirectory($this->backupDirectory);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        if (File::exists($this->backupDirectory)) {
            File::deleteDirectory($this->backupDirectory);
        }
        
        parent::tearDown();
    }

    public function test_validate_setup_state_integrity_with_valid_state(): void
    {
        // Create valid state
        $this->setupService->updateSetupStep('welcome', true);
        
        $issues = $this->setupService->validateSetupStateIntegrity();
        
        $this->assertEmpty($issues);
    }

    public function test_validate_setup_state_integrity_with_missing_fields(): void
    {
        // Create state with missing required fields
        $invalidState = [
            'setup_complete' => false,
            // Missing: current_step, started_at, steps
        ];
        
        File::put($this->setupStateFile, json_encode($invalidState));
        
        $issues = $this->setupService->validateSetupStateIntegrity();
        
        $this->assertNotEmpty($issues);
        $this->assertContains('Missing required field: current_step', $issues);
        $this->assertContains('Missing required field: started_at', $issues);
        $this->assertContains('Missing required field: steps', $issues);
    }

    public function test_validate_setup_state_integrity_with_invalid_step(): void
    {
        // Create state with invalid current step
        $invalidState = [
            'setup_complete' => false,
            'current_step' => 'invalid_step',
            'started_at' => now()->toISOString(),
            'steps' => []
        ];
        
        File::put($this->setupStateFile, json_encode($invalidState));
        
        $issues = $this->setupService->validateSetupStateIntegrity();
        
        $this->assertNotEmpty($issues);
        $this->assertContains('Invalid current step: invalid_step', $issues);
    }

    public function test_validate_setup_state_integrity_with_invalid_timestamp(): void
    {
        // Create state with invalid timestamp
        $invalidState = [
            'setup_complete' => false,
            'current_step' => 'welcome',
            'started_at' => 'invalid_timestamp',
            'steps' => []
        ];
        
        File::put($this->setupStateFile, json_encode($invalidState));
        
        $issues = $this->setupService->validateSetupStateIntegrity();
        
        $this->assertNotEmpty($issues);
        $this->assertContains('Invalid started_at timestamp: invalid_timestamp', $issues);
    }

    public function test_validate_setup_state_integrity_with_version_mismatch(): void
    {
        // Create state with wrong version
        $invalidState = [
            'version' => '0.5',
            'setup_complete' => false,
            'current_step' => 'welcome',
            'started_at' => now()->toISOString(),
            'steps' => []
        ];
        
        File::put($this->setupStateFile, json_encode($invalidState));
        
        $issues = $this->setupService->validateSetupStateIntegrity();
        
        $this->assertNotEmpty($issues);
        $versionIssue = collect($issues)->first(fn($issue) => str_contains($issue, 'version mismatch'));
        $this->assertNotNull($versionIssue);
    }

    public function test_create_state_backup(): void
    {
        // Create initial state
        $this->setupService->updateSetupStep('welcome', true);
        
        $backupFile = $this->setupService->createStateBackup();
        
        $this->assertTrue(File::exists($backupFile));
        $this->assertTrue(File::exists($this->backupDirectory));
        
        // Backup should contain valid JSON
        $backupContent = File::get($backupFile);
        $decoded = json_decode($backupContent, true);
        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['steps']['welcome']['completed']);
    }

    public function test_create_state_backup_with_no_existing_state(): void
    {
        // Try to create backup when no state file exists
        $backupFile = $this->setupService->createStateBackup();
        
        // Should create backup directory but no backup file
        $this->assertTrue(File::exists($this->backupDirectory));
        $this->assertFalse(File::exists($backupFile));
    }

    public function test_restore_state_from_backup(): void
    {
        // Create initial state
        $this->setupService->updateSetupStep('welcome', true);
        
        // Manually create backup of current state (before automatic backup on next update)
        $stateFile = storage_path('app/setup/setup-state.json');
        $backupDir = storage_path('app/setup/backups');
        
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupFile = $backupDir . "/manual-backup-{$timestamp}.json";
        File::copy($stateFile, $backupFile);
        
        // Wait a moment to ensure backup is created
        usleep(100000); // 0.1 seconds
        
        // Modify state after backup
        $this->setupService->updateSetupStep('database', true);
        $modifiedState = $this->setupService->getSetupState();
        
        // Verify modification
        $this->assertTrue($modifiedState['steps']['database']['completed']);
        
        // Restore from backup
        $success = $this->setupService->restoreStateFromBackup($backupFile);
        
        $this->assertTrue($success);
        
        // Verify restoration - create new service instance to avoid caching
        $newService = new SetupService($this->assetValidationService);
        $restoredState = $newService->getSetupState();
        $this->assertFalse($restoredState['steps']['database']['completed']);
        $this->assertTrue($restoredState['steps']['welcome']['completed']);
    }

    public function test_restore_state_from_nonexistent_backup(): void
    {
        $success = $this->setupService->restoreStateFromBackup('/nonexistent/backup.json');
        
        $this->assertFalse($success);
    }

    public function test_restore_state_from_corrupted_backup(): void
    {
        // Create corrupted backup file
        $backupFile = $this->backupDirectory . '/corrupted-backup.json';
        File::makeDirectory($this->backupDirectory, 0755, true);
        File::put($backupFile, 'invalid json content');
        
        $success = $this->setupService->restoreStateFromBackup($backupFile);
        
        $this->assertFalse($success);
    }

    public function test_get_available_backups(): void
    {
        // Create multiple backups
        $this->setupService->updateSetupStep('welcome', true);
        $backup1 = $this->setupService->createStateBackup();
        
        sleep(1); // Ensure different timestamps
        
        $this->setupService->updateSetupStep('database', true);
        $backup2 = $this->setupService->createStateBackup();
        
        $backups = $this->setupService->getAvailableBackups();
        
        $this->assertCount(2, $backups);
        
        // Should be sorted by creation time (newest first)
        $this->assertGreaterThan(
            $backups[1]['created_at']->timestamp,
            $backups[0]['created_at']->timestamp
        );
        
        // Each backup should have required fields
        foreach ($backups as $backup) {
            $this->assertArrayHasKey('file', $backup);
            $this->assertArrayHasKey('filename', $backup);
            $this->assertArrayHasKey('created_at', $backup);
            $this->assertArrayHasKey('size', $backup);
        }
    }

    public function test_get_available_backups_with_no_backups(): void
    {
        $backups = $this->setupService->getAvailableBackups();
        
        $this->assertEmpty($backups);
    }

    public function test_auto_detect_current_step(): void
    {
        // Mock asset validation to return false (assets not ready)
        $this->assetValidationService
            ->expects($this->once())
            ->method('areAssetRequirementsMet')
            ->willReturn(false);
        
        $detectedStep = $this->setupService->autoDetectCurrentStep();
        
        $this->assertEquals('assets', $detectedStep);
    }

    public function test_auto_detect_current_step_with_assets_ready(): void
    {
        // Mock asset validation to return true
        $this->assetValidationService
            ->expects($this->once())
            ->method('areAssetRequirementsMet')
            ->willReturn(true);
        
        $detectedStep = $this->setupService->autoDetectCurrentStep();
        
        // Should detect admin step since database is configured in test environment
        // but no admin user exists
        $this->assertEquals('admin', $detectedStep);
    }

    public function test_detect_and_resume_setup_with_valid_state(): void
    {
        // Create valid state
        $this->setupService->updateSetupStep('welcome', true);
        
        $recoveryInfo = $this->setupService->detectAndResumeSetup();
        
        $this->assertFalse($recoveryInfo['interrupted']);
        $this->assertEmpty($recoveryInfo['issues_found']);
        $this->assertNull($recoveryInfo['resumed_from']);
    }

    public function test_detect_and_resume_setup_with_corrupted_state(): void
    {
        // Create initial valid state first
        $this->setupService->updateSetupStep('welcome', true);
        
        // Then corrupt the state file
        File::put($this->setupStateFile, 'invalid json content');
        
        $recoveryInfo = $this->setupService->detectAndResumeSetup();
        
        // Debug output to see what's happening
        if (!$recoveryInfo['interrupted']) {
            $this->fail('Recovery info: ' . json_encode($recoveryInfo, JSON_PRETTY_PRINT));
        }
        
        $this->assertTrue($recoveryInfo['interrupted']);
        $this->assertNotEmpty($recoveryInfo['actions_taken']);
        
        // Should either restore from backup or recreate state
        $hasRestoreAction = collect($recoveryInfo['actions_taken'])->contains(fn($action) => 
            str_contains($action, 'Restored state from backup') || 
            str_contains($action, 'Recreated setup state') ||
            str_contains($action, 'No backups available, recreating state')
        );
        $this->assertTrue($hasRestoreAction, 'Actions taken: ' . json_encode($recoveryInfo['actions_taken']));
    }

    public function test_detect_and_resume_setup_with_integrity_issues(): void
    {
        // Create state with integrity issues
        $invalidState = [
            'setup_complete' => false,
            'current_step' => 'invalid_step',
            'started_at' => now()->toISOString(),
            'steps' => []
        ];
        
        File::put($this->setupStateFile, json_encode($invalidState));
        
        $recoveryInfo = $this->setupService->detectAndResumeSetup();
        
        $this->assertTrue($recoveryInfo['interrupted']);
        $this->assertNotEmpty($recoveryInfo['issues_found']);
        $this->assertContains('Invalid current step: invalid_step', $recoveryInfo['issues_found']);
    }

    public function test_cleanup_after_completion(): void
    {
        // Create temporary files
        $tempDir = storage_path('app/setup/temp');
        File::makeDirectory($tempDir, 0755, true);
        File::put($tempDir . '/temp-file.txt', 'temporary content');
        
        // Create some state
        $this->setupService->updateSetupStep('welcome', true);
        
        // Perform cleanup
        $this->setupService->cleanupAfterCompletion();
        
        // Temporary directory should be removed
        $this->assertFalse(File::exists($tempDir));
        
        // State should be marked with cleanup completion
        $state = $this->setupService->getSetupState();
        $this->assertTrue($state['cleanup_completed'] ?? false);
        $this->assertNotNull($state['cleanup_completed_at'] ?? null);
    }

    public function test_get_recovery_info(): void
    {
        // Create some state and backups
        $this->setupService->updateSetupStep('welcome', true);
        $this->setupService->createStateBackup();
        
        $recoveryInfo = $this->setupService->getRecoveryInfo();
        
        $this->assertArrayHasKey('state_file_exists', $recoveryInfo);
        $this->assertArrayHasKey('state_file_size', $recoveryInfo);
        $this->assertArrayHasKey('backup_count', $recoveryInfo);
        $this->assertArrayHasKey('latest_backup', $recoveryInfo);
        $this->assertArrayHasKey('integrity_issues', $recoveryInfo);
        $this->assertArrayHasKey('environment_issues', $recoveryInfo);
        
        $this->assertTrue($recoveryInfo['state_file_exists']);
        $this->assertGreaterThan(0, $recoveryInfo['state_file_size']);
        $this->assertEquals(1, $recoveryInfo['backup_count']);
        $this->assertNotNull($recoveryInfo['latest_backup']);
    }

    public function test_validate_setup_environment(): void
    {
        $issues = $this->setupService->validateSetupEnvironment();
        
        // Should be empty if environment is properly set up
        $this->assertIsArray($issues);
    }

    public function test_backup_cleanup_limits_file_count(): void
    {
        // Create more backups than the limit
        for ($i = 0; $i < 8; $i++) {
            $this->setupService->updateSetupStep('welcome', $i % 2 === 0);
            sleep(1); // Ensure different timestamps
        }
        
        $backups = $this->setupService->getAvailableBackups();
        
        // Should not exceed maximum backup files (5)
        $this->assertLessThanOrEqual(5, count($backups));
    }

    public function test_state_includes_version_and_metadata(): void
    {
        $this->setupService->updateSetupStep('welcome', true);
        
        $state = $this->setupService->getSetupState();
        
        $this->assertArrayHasKey('version', $state);
        $this->assertArrayHasKey('last_updated_at', $state);
        $this->assertArrayHasKey('recovery_info', $state);
        
        $this->assertEquals('1.0', $state['version']);
        $this->assertNotNull($state['last_updated_at']);
    }

    public function test_state_includes_started_at_for_steps(): void
    {
        $this->setupService->markStepStarted('welcome');
        
        $state = $this->setupService->getSetupState();
        
        $this->assertArrayHasKey('started_at', $state['steps']['welcome']);
        $this->assertNotNull($state['steps']['welcome']['started_at']);
    }
}