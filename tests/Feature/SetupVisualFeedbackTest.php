<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\SetupService;
use App\Services\AssetValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;

class SetupVisualFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private SetupService $setupService;
    private AssetValidationService $assetValidationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->assetValidationService = $this->createMock(AssetValidationService::class);
        $this->setupService = new SetupService($this->assetValidationService);
    }

    public function test_setup_progress_indicator_component_renders()
    {
        $view = View::make('components.setup-progress-indicator', [
            'currentStep' => 'welcome',
            'progress' => 25,
            'animated' => true,
            'showEstimatedTime' => true
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Setup Progress', $html);
        $this->assertStringContainsString('25%', $html);
        $this->assertStringContainsString('Welcome', $html);
        $this->assertStringContainsString('setupProgress', $html); // Alpine.js function
    }

    public function test_setup_step_transition_component_renders()
    {
        $view = View::make('components.setup-step-transition', [
            'fromStep' => 'welcome',
            'toStep' => 'database',
            'message' => 'Moving to database configuration',
            'duration' => 2000
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Step Complete!', $html);
        $this->assertStringContainsString('Welcome', $html);
        $this->assertStringContainsString('Database', $html);
        $this->assertStringContainsString('Moving to database configuration', $html);
    }

    public function test_setup_completion_celebration_component_renders()
    {
        $nextSteps = [
            'Access the admin dashboard',
            'Configure cloud storage',
            'Test file uploads'
        ];

        $view = View::make('components.setup-completion-celebration', [
            'title' => 'Setup Complete!',
            'message' => 'Your installation is ready',
            'nextSteps' => $nextSteps,
            'autoRedirect' => true,
            'redirectUrl' => '/admin',
            'redirectDelay' => 10000
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Setup Complete!', $html);
        $this->assertStringContainsString('Your installation is ready', $html);
        $this->assertStringContainsString('Access the admin dashboard', $html);
        $this->assertStringContainsString('confetti', $html);
        $this->assertStringContainsString('setupCelebration', $html); // Alpine.js function
    }

    public function test_setup_step_completion_component_renders()
    {
        $details = [
            'Database connection established',
            'Tables created successfully',
            'Initial data seeded'
        ];

        $view = View::make('components.setup-step-completion', [
            'step' => 'database',
            'title' => 'Database Setup Complete',
            'message' => 'Database has been configured successfully',
            'details' => $details,
            'nextStep' => 'admin',
            'nextStepUrl' => '/setup/admin',
            'progress' => 60,
            'autoAdvance' => true
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Database Setup Complete', $html);
        $this->assertStringContainsString('Database has been configured successfully', $html);
        $this->assertStringContainsString('Database connection established', $html);
        $this->assertStringContainsString('Next: Admin', $html);
        $this->assertStringContainsString('stepCompletion', $html); // Alpine.js function
    }

    public function test_enhanced_setup_progress_indicator_with_animations()
    {
        $steps = [
            'assets' => ['title' => 'Assets', 'description' => 'Build assets', 'icon' => 'build'],
            'welcome' => ['title' => 'Welcome', 'description' => 'System checks', 'icon' => 'home'],
            'database' => ['title' => 'Database', 'description' => 'Configure DB', 'icon' => 'database']
        ];

        $view = View::make('components.setup-progress-indicator', [
            'currentStep' => 'database',
            'progress' => 66,
            'steps' => $steps,
            'animated' => true,
            'showEstimatedTime' => true
        ]);

        $html = $view->render();

        $this->assertStringContainsString('66%', $html);
        $this->assertStringContainsString('Database', $html);
        $this->assertStringContainsString('Configure DB', $html);
        $this->assertStringContainsString('animate-pulse', $html);
        $this->assertStringContainsString('transition-all', $html);
    }

    public function test_progress_indicator_shows_estimated_time()
    {
        $view = View::make('components.setup-progress-indicator', [
            'currentStep' => 'database',
            'progress' => 40,
            'showEstimatedTime' => true
        ]);

        $html = $view->render();

        $this->assertStringContainsString('2-4 min', $html); // Database step estimated time
    }

    public function test_progress_indicator_without_estimated_time()
    {
        $view = View::make('components.setup-progress-indicator', [
            'currentStep' => 'database',
            'progress' => 40,
            'showEstimatedTime' => false
        ]);

        $html = $view->render();

        $this->assertStringNotContainsString('2-4 min', $html);
    }

    public function test_step_completion_with_auto_advance()
    {
        $view = View::make('components.setup-step-completion', [
            'step' => 'welcome',
            'nextStep' => 'database',
            'nextStepUrl' => '/setup/database',
            'autoAdvance' => true,
            'autoAdvanceDelay' => 5000
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Automatically continuing', $html);
        $this->assertStringContainsString('Cancel auto-advance', $html);
        $this->assertStringContainsString('countdown', $html);
    }

    public function test_step_completion_without_auto_advance()
    {
        $view = View::make('components.setup-step-completion', [
            'step' => 'welcome',
            'nextStep' => 'database',
            'nextStepUrl' => '/setup/database',
            'autoAdvance' => false
        ]);

        $html = $view->render();

        $this->assertStringNotContainsString('Automatically continuing', $html);
        $this->assertStringNotContainsString('Cancel auto-advance', $html);
    }

    public function test_celebration_component_with_confetti_animation()
    {
        $view = View::make('components.setup-completion-celebration', [
            'title' => 'All Done!',
            'message' => 'Setup completed successfully'
        ]);

        $html = $view->render();

        $this->assertStringContainsString('confetti-container', $html);
        $this->assertStringContainsString('confetti-fall', $html);
        $this->assertStringContainsString('@keyframes confetti-fall', $html);
    }

    public function test_celebration_component_with_auto_redirect()
    {
        $view = View::make('components.setup-completion-celebration', [
            'autoRedirect' => true,
            'redirectUrl' => '/admin/dashboard',
            'redirectDelay' => 15000
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Redirecting to admin dashboard', $html);
        $this->assertStringContainsString('15', $html); // 15 seconds
        $this->assertStringContainsString('/admin/dashboard', $html);
    }

    public function test_celebration_component_without_auto_redirect()
    {
        $view = View::make('components.setup-completion-celebration', [
            'autoRedirect' => false
        ]);

        $html = $view->render();

        $this->assertStringNotContainsString('Redirecting to', $html);
        $this->assertStringNotContainsString('countdown', $html);
    }

    public function test_progress_indicator_milestone_markers()
    {
        $view = View::make('components.setup-progress-indicator', [
            'currentStep' => 'storage',
            'progress' => 80
        ]);

        $html = $view->render();

        // Should show milestone markers at 20, 40, 60, 80%
        $this->assertStringContainsString('milestone', $html);
        $this->assertStringContainsString('80', $html);
    }

    public function test_step_transition_with_custom_message()
    {
        $customMessage = 'Preparing database configuration...';
        
        $view = View::make('components.setup-step-transition', [
            'fromStep' => 'welcome',
            'toStep' => 'database',
            'message' => $customMessage
        ]);

        $html = $view->render();

        $this->assertStringContainsString($customMessage, $html);
    }

    public function test_step_transition_with_default_message()
    {
        $view = View::make('components.setup-step-transition', [
            'fromStep' => 'welcome',
            'toStep' => 'database'
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Moving to the next step', $html);
    }
}