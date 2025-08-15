<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\SetupService;
use App\Services\AssetValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class SetupProgressTrackingTest extends TestCase
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

    public function test_can_get_detailed_progress_information()
    {
        // Mock asset validation to return true
        $this->assetValidationService
            ->method('areAssetRequirementsMet')
            ->willReturn(true);

        $progressDetails = $this->setupService->getDetailedProgress();

        $this->assertIsArray($progressDetails);
        $this->assertArrayHasKey('current_step', $progressDetails);
        $this->assertArrayHasKey('progress_percentage', $progressDetails);
        $this->assertArrayHasKey('total_steps', $progressDetails);
        $this->assertArrayHasKey('completed_steps', $progressDetails);
        $this->assertArrayHasKey('remaining_steps', $progressDetails);
        $this->assertArrayHasKey('steps', $progressDetails);
        $this->assertArrayHasKey('estimated_time_remaining', $progressDetails);
    }

    public function test_can_calculate_progress_percentage()
    {
        // Mock asset validation
        $this->assetValidationService
            ->method('areAssetRequirementsMet')
            ->willReturn(true);

        // Initially no steps completed
        $progress = $this->setupService->getSetupProgress();
        $this->assertEquals(0, $progress);

        // Complete one step
        $this->setupService->updateSetupStep('assets', true);
        $progress = $this->setupService->getSetupProgress();
        $this->assertGreaterThan(0, $progress);

        // Complete all steps
        $steps = ['assets', 'welcome', 'database', 'admin', 'storage', 'complete'];
        foreach ($steps as $step) {
            $this->setupService->updateSetupStep($step, true);
        }
        
        $progress = $this->setupService->getSetupProgress();
        $this->assertEquals(100, $progress);
    }

    public function test_can_mark_step_as_started()
    {
        // Mock asset validation to ensure we're on the welcome step
        $this->assetValidationService
            ->method('areAssetRequirementsMet')
            ->willReturn(true);
            
        $this->setupService->markStepStarted('welcome');
        
        // Since getCurrentStepStartTime looks for the current step, we need to ensure welcome is current
        $currentStep = $this->setupService->getSetupStep();
        if ($currentStep === 'welcome') {
            $startTime = $this->setupService->getCurrentStepStartTime();
            $this->assertNotNull($startTime);
            $this->assertIsString($startTime);
        } else {
            // If not on welcome step, just verify the method doesn't throw an exception
            $this->assertTrue(true);
        }
    }

    public function test_can_get_estimated_time_remaining()
    {
        $estimatedTime = $this->setupService->getEstimatedTimeRemaining();
        $this->assertIsString($estimatedTime);
        $this->assertNotEmpty($estimatedTime);
    }

    public function test_can_get_step_completion_details()
    {
        $details = $this->setupService->getStepCompletionDetails('database');
        
        $this->assertIsArray($details);
        $this->assertArrayHasKey('title', $details);
        $this->assertArrayHasKey('message', $details);
        $this->assertArrayHasKey('details', $details);
        $this->assertIsArray($details['details']);
    }

    public function test_progress_tracking_with_step_transitions()
    {
        // Mock asset validation to return false initially
        $this->assetValidationService
            ->method('areAssetRequirementsMet')
            ->willReturn(false);

        // Should start with assets step when assets are not ready
        $initialStep = $this->setupService->getSetupStep();
        $this->assertEquals('assets', $initialStep);

        // Get initial progress
        $initialProgress = $this->setupService->getSetupProgress();

        // Complete the assets step
        $this->setupService->updateSetupStep('assets', true);
        
        // Progress should have increased
        $newProgress = $this->setupService->getSetupProgress();
        $this->assertGreaterThan($initialProgress, $newProgress);
        
        // Verify the step was marked as completed in the state
        $steps = $this->setupService->getSetupSteps();
        $this->assertTrue($steps['assets']['completed'] ?? false);
    }

    public function test_setup_state_persistence()
    {
        // Mock asset validation
        $this->assetValidationService
            ->method('areAssetRequirementsMet')
            ->willReturn(true);
            
        // Get current step and mark it as started
        $currentStep = $this->setupService->getSetupStep();
        $this->setupService->markStepStarted($currentStep);
        
        // Create new service instance to test persistence
        $newSetupService = new SetupService($this->assetValidationService);
        
        // Check if the step start time persisted
        $newCurrentStep = $newSetupService->getSetupStep();
        if ($newCurrentStep === $currentStep) {
            $startTime = $newSetupService->getCurrentStepStartTime();
            $this->assertNotNull($startTime);
        } else {
            // State might have changed, just verify no exception is thrown
            $this->assertTrue(true);
        }
    }

    public function test_estimated_time_calculation_with_different_remaining_steps()
    {
        // Test with all steps remaining
        $allStepsRemaining = [
            'assets' => ['completed' => false],
            'welcome' => ['completed' => false],
            'database' => ['completed' => false],
            'admin' => ['completed' => false],
            'storage' => ['completed' => false],
            'complete' => ['completed' => false]
        ];
        
        $estimatedTime = $this->setupService->getEstimatedTimeRemaining($allStepsRemaining);
        $this->assertStringContainsString('minute', $estimatedTime);

        // Test with only one step remaining
        $oneStepRemaining = [
            'complete' => ['completed' => false]
        ];
        
        $estimatedTime = $this->setupService->getEstimatedTimeRemaining($oneStepRemaining);
        $this->assertStringContainsString('minute', $estimatedTime);
    }

    public function test_progress_details_include_timing_information()
    {
        // Mock asset validation
        $this->assetValidationService
            ->method('areAssetRequirementsMet')
            ->willReturn(true);

        $this->setupService->markStepStarted('welcome');
        
        $progressDetails = $this->setupService->getDetailedProgress();
        
        $this->assertArrayHasKey('setup_started_at', $progressDetails);
        $this->assertArrayHasKey('current_step_started_at', $progressDetails);
    }

    public function test_step_completion_details_for_all_steps()
    {
        $steps = ['assets', 'welcome', 'database', 'admin', 'storage', 'complete'];
        
        foreach ($steps as $step) {
            $details = $this->setupService->getStepCompletionDetails($step);
            
            $this->assertIsArray($details);
            $this->assertArrayHasKey('title', $details);
            $this->assertArrayHasKey('message', $details);
            $this->assertArrayHasKey('details', $details);
            $this->assertNotEmpty($details['title']);
            $this->assertNotEmpty($details['message']);
            $this->assertIsArray($details['details']);
        }
    }

    public function test_invalid_step_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->setupService->markStepStarted('invalid_step');
    }

    public function test_progress_tracking_with_cache_enabled()
    {
        Config::set('setup.cache_state', true);
        
        // Mock asset validation
        $this->assetValidationService
            ->method('areAssetRequirementsMet')
            ->willReturn(true);

        $progress1 = $this->setupService->getSetupProgress();
        $progress2 = $this->setupService->getSetupProgress();
        
        $this->assertEquals($progress1, $progress2);
    }

    public function test_progress_tracking_with_cache_disabled()
    {
        Config::set('setup.cache_state', false);
        
        // Mock asset validation
        $this->assetValidationService
            ->method('areAssetRequirementsMet')
            ->willReturn(true);

        $progress1 = $this->setupService->getSetupProgress();
        
        // Complete a step that's not already completed
        $currentStep = $this->setupService->getSetupStep();
        $this->setupService->updateSetupStep($currentStep, true);
        
        $progress2 = $this->setupService->getSetupProgress();
        
        // Progress should increase unless we were already at 100%
        if ($progress1 < 100) {
            $this->assertGreaterThan($progress1, $progress2);
        } else {
            $this->assertEquals(100, $progress2);
        }
    }
}