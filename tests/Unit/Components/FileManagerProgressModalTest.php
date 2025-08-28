<?php

namespace Tests\Unit\Components;

use Tests\TestCase;
use Illuminate\View\Component;

class FileManagerProgressModalTest extends TestCase
{
    public function test_progress_modal_component_renders()
    {
        $view = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'admin',
            'username' => null
        ]);

        $view->assertSee('file-manager-progress');
        $view->assertSee('z-[10006]');
        $view->assertSee('progress-modal-title');
    }

    public function test_progress_modal_component_renders_with_employee_user_type()
    {
        $view = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'employee',
            'username' => 'testuser'
        ]);

        $view->assertSee('file-manager-progress');
        $view->assertSee('employee');
        $view->assertSee('testuser');
    }

    public function test_progress_modal_has_proper_z_index_stacking()
    {
        $view = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'admin'
        ]);

        // Check z-index hierarchy (higher than confirmation modal)
        $view->assertSee('z-[10006]'); // Container
        $view->assertSee('z-[10007]'); // Content
        $view->assertSee('z-[10005]'); // Backdrop
    }

    public function test_progress_modal_includes_progress_bar()
    {
        $view = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'admin'
        ]);

        $view->assertSee('bg-gray-200 rounded-full h-2.5'); // Progress bar container
        $view->assertSee('progressPercentage'); // Progress calculation
        $view->assertSee('current'); // Current progress
        $view->assertSee('total'); // Total items
    }

    public function test_progress_modal_includes_different_status_icons()
    {
        $view = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'admin'
        ]);

        // Check for different status templates by looking for the template conditions
        $view->assertSee('x-if="!status || status === \'processing\'"', false);
        $view->assertSee('x-if="status === \'completed\'"', false);
        $view->assertSee('x-if="status === \'error\'"', false);
        $view->assertSee('x-if="status === \'cancelled\'"', false);
    }

    public function test_progress_modal_includes_cancel_and_retry_functionality()
    {
        $view = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'admin'
        ]);

        $view->assertSee('cancelOperation()');
        $view->assertSee('retryOperation()');
        $view->assertSee('cancellable');
        $view->assertSee('retryable');
    }

    public function test_progress_modal_includes_debug_mode_in_local_environment()
    {
        $this->app['env'] = 'local';

        $view = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'admin'
        ]);

        $view->assertSee('modal-debug-info');
        $view->assertSee('Progress Modal Debug Info');
    }

    public function test_progress_modal_excludes_debug_mode_in_production()
    {
        $this->app['env'] = 'production';

        $view = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'admin'
        ]);

        $view->assertDontSee('modal-debug-info');
        $view->assertDontSee('Progress Modal Debug Info');
    }

    public function test_progress_modal_has_proper_accessibility_attributes()
    {
        $view = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'admin'
        ]);

        $view->assertSee('role="dialog"', false);
        $view->assertSee('aria-modal="true"', false);
        $view->assertSee('aria-labelledby="progress-modal-title"', false);
        $view->assertSee('aria-hidden="true"', false);
    }

    public function test_progress_modal_includes_alpine_js_data_function()
    {
        $view = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'admin'
        ]);

        $view->assertSee('fileProgressModal');
        $view->assertSee('x-data=', false);
        $view->assertSee('x-show="open"', false);
    }

    public function test_progress_modal_includes_time_estimation()
    {
        $view = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'admin'
        ]);

        $view->assertSee('estimatedTimeRemaining');
        $view->assertSee('formatTime');
        $view->assertSee('messages.estimated_time_remaining');
    }
}