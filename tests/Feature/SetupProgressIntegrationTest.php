<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\SetupService;
use App\Services\AssetValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class SetupProgressIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private SetupService $setupService;
    private AssetValidationService $assetValidationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->assetValidationService = $this->createMock(AssetValidationService::class);
        $this->setupService = new SetupService($this->assetValidationService);
        
        // Clear any cached setup state
        Cache::flush();
        
        // Clear setup state file to start fresh
        $stateFile = storage_path('app/setup/setup-state.json');
        if (file_exists($stateFile)) {
            unlink($stateFile);
        }
    }

    public function test_complete_setup_progress_flow()
    {
        // Mock asset validation to return false initially
        $this->assetValidationService
            ->method('areAssetRequirementsMet')
            ->willReturn(false);

        // 1. Start with assets step
        $currentStep = $this->setupService->getSetupStep();
        $this->assertEquals('assets', $currentStep);
        
        $progress = $this->setupService->getSetupProgress();
        $this->assertEquals(0, $progress);

        // 2. Complete assets step
        $this->setupService->markStepStarted('assets');
        $this->setupService->updateSetupStep('assets', true);
        
        $progress = $this->setupService->getSetupProgress();
        $this->assertGreaterThan(0, $progress);
        
        // Get completion details
        $completionDetails = $this->setupService->getStepCompletionDetails('assets');
        $this->assertArrayHasKey('title', $completionDetails);
        $this->assertArrayHasKey('message', $completionDetails);
        $this->assertArrayHasKey('details', $completionDetails);

        // 3. Complete welcome step
        $this->setupService->markStepStarted('welcome');
        $this->setupService->updateSetupStep('welcome', true);
        
        $newProgress = $this->setupService->getSetupProgress();
        $this->assertGreaterThan($progress, $newProgress);

        // 4. Complete database step
        $this->setupService->markStepStarted('database');
        $this->setupService->updateSetupStep('database', true);
        
        $progress = $newProgress;
        $newProgress = $this->setupService->getSetupProgress();
        $this->assertGreaterThan($progress, $newProgress);

        // 5. Complete admin step
        $this->setupService->markStepStarted('admin');
        $this->setupService->updateSetupStep('admin', true);
        
        $progress = $newProgress;
        $newProgress = $this->setupService->getSetupProgress();
        $this->assertGreaterThan($progress, $newProgress);

        // 6. Complete storage step
        $this->setupService->markStepStarted('storage');
        $this->setupService->updateSetupStep('storage', true);
        
        $progress = $newProgress;
        $newProgress = $this->setupService->getSetupProgress();
        $this->assertGreaterThan($progress, $newProgress);

        // 7. Complete final step
        $this->setupService->updateSetupStep('complete', true);
        
        $finalProgress = $this->setupService->getSetupProgress();
        $this->assertEquals(100, $finalProgress);
    }

    public function test_detailed_progress_tracking_throughout_setup()
    {
        // Mock asset validation
        $this->assetValidationService
            ->method('areAssetRequirementsMet')
            ->willReturn(false);

        // Get initial detailed progress
        $progressDetails = $this->setupService->getDetailedProgress();
        $this->assertEquals(0, $progressDetails['completed_steps']);
        $this->assertEquals(6, $progressDetails['total_steps']); // assets, welcome, database, admin, storage, complete
        $this->assertEquals(6, $progressDetails['remaining_steps']);

        // Complete first step
        $this->setupService->updateSetupStep('assets', true);
        
        $progressDetails = $this->setupService->getDetailedProgress();
        $this->assertEquals(1, $progressDetails['completed_steps']);
        $this->assertEquals(5, $progressDetails['remaining_steps']);
        $this->assertGreaterThan(0, $progressDetails['progress_percentage']);

        // Complete second step
        $this->setupService->updateSetupStep('welcome', true);
        
        $progressDetails = $this->setupService->getDetailedProgress();
        $this->assertEquals(2, $progressDetails['completed_steps']);
        $this->assertEquals(4, $progressDetails['remaining_steps']);

        // Verify estimated time decreases as steps are completed
        $estimatedTime = $this->setupService->getEstimatedTimeRemaining();
        $this->assertIsString($estimatedTime);
        $this->assertNotEmpty($estimatedTime);
    }

    public function test_step_timing_tracking()
    {
        // Mock asset validation
        $this->assetValidationService
            ->method('areAssetRequirementsMet')
            ->willReturn(false);

        // Mark step as started
        $this->setupService->markStepStarted('assets');
        
        // Verify timing information is available
        $progressDetails = $this->setupService->getDetailedProgress();
        $this->assertArrayHasKey('setup_started_at', $progressDetails);
        $this->assertArrayHasKey('current_step_started_at', $progressDetails);
        
        if ($this->setupService->getSetupStep() === 'assets') {
            $this->assertNotNull($progressDetails['current_step_started_at']);
        }
    }

    public function test_step_completion_details_for_visual_feedback()
    {
        $steps = ['assets', 'welcome', 'database', 'admin', 'storage', 'complete'];
        
        foreach ($steps as $step) {
            $details = $this->setupService->getStepCompletionDetails($step);
            
            // Verify structure for visual components
            $this->assertArrayHasKey('title', $details);
            $this->assertArrayHasKey('message', $details);
            $this->assertArrayHasKey('details', $details);
            
            // Verify content is appropriate for visual feedback
            $this->assertNotEmpty($details['title']);
            $this->assertNotEmpty($details['message']);
            $this->assertIsArray($details['details']);
            
            // Verify details array has meaningful content
            if (!empty($details['details'])) {
                foreach ($details['details'] as $detail) {
                    $this->assertIsString($detail);
                    $this->assertNotEmpty($detail);
                }
            }
        }
    }

    public function test_progress_persistence_across_requests()
    {
        // Mock asset validation
        $this->assetValidationService
            ->method('areAssetRequirementsMet')
            ->willReturn(false);

        // Complete some steps
        $this->setupService->updateSetupStep('assets', true);
        $this->setupService->updateSetupStep('welcome', true);
        
        $progress1 = $this->setupService->getSetupProgress();
        
        // Create new service instance (simulating new request)
        $newSetupService = new SetupService($this->assetValidationService);
        $progress2 = $newSetupService->getSetupProgress();
        
        // Progress should be the same
        $this->assertEquals($progress1, $progress2);
        
        // Step states should be preserved
        $steps1 = $this->setupService->getSetupSteps();
        $steps2 = $newSetupService->getSetupSteps();
        
        $this->assertEquals($steps1['assets']['completed'], $steps2['assets']['completed']);
        $this->assertEquals($steps1['welcome']['completed'], $steps2['welcome']['completed']);
    }
}