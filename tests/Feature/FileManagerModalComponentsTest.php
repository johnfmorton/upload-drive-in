<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FileManagerModalComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_modal_component_can_be_included_in_template()
    {
        $view = $this->view('components.file-manager.modals.confirmation-modal', [
            'userType' => 'admin',
            'username' => null
        ]);

        // Test that the component renders without errors
        $view->assertSee('file-manager-confirmation');
        $view->assertSee('fileConfirmationModal');
    }

    public function test_progress_modal_component_can_be_included_in_template()
    {
        $view = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'admin',
            'username' => null
        ]);

        // Test that the component renders without errors
        $view->assertSee('file-manager-progress');
        $view->assertSee('fileProgressModal');
    }

    public function test_modal_components_have_proper_z_index_hierarchy()
    {
        $confirmationView = $this->view('components.file-manager.modals.confirmation-modal', [
            'userType' => 'admin'
        ]);

        $progressView = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'admin'
        ]);

        // Confirmation modal z-indexes
        $confirmationView->assertSee('z-[10004]'); // Container
        $confirmationView->assertSee('z-[10005]'); // Content
        $confirmationView->assertSee('z-[10003]'); // Backdrop

        // Progress modal z-indexes (should be higher)
        $progressView->assertSee('z-[10006]'); // Container
        $progressView->assertSee('z-[10007]'); // Content
        $progressView->assertSee('z-[10005]'); // Backdrop
    }

    public function test_modal_components_support_both_user_types()
    {
        $adminConfirmation = $this->view('components.file-manager.modals.confirmation-modal', [
            'userType' => 'admin',
            'username' => null
        ]);

        $employeeConfirmation = $this->view('components.file-manager.modals.confirmation-modal', [
            'userType' => 'employee',
            'username' => 'testuser'
        ]);

        $adminProgress = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'admin',
            'username' => null
        ]);

        $employeeProgress = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'employee',
            'username' => 'testuser'
        ]);

        // All should render without errors
        $adminConfirmation->assertSee('fileConfirmationModal');
        $employeeConfirmation->assertSee('fileConfirmationModal');
        $adminProgress->assertSee('fileProgressModal');
        $employeeProgress->assertSee('fileProgressModal');
    }

    public function test_modal_components_include_required_alpine_js_events()
    {
        $confirmationView = $this->view('components.file-manager.modals.confirmation-modal', [
            'userType' => 'admin'
        ]);

        $progressView = $this->view('components.file-manager.modals.progress-modal', [
            'userType' => 'admin'
        ]);

        // Confirmation modal events
        $confirmationView->assertSee('x-on:open-confirmation-modal.window', false);
        $confirmationView->assertSee('x-on:close.stop', false);
        $confirmationView->assertSee('x-on:keydown.escape.window', false);

        // Progress modal events
        $progressView->assertSee('x-on:open-progress-modal.window', false);
        $progressView->assertSee('x-on:update-progress.window', false);
        $progressView->assertSee('x-on:close-progress-modal.window', false);
    }
}