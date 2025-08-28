<?php

namespace Tests\Unit\Components;

use Tests\TestCase;
use Illuminate\View\Component;

class FileManagerConfirmationModalTest extends TestCase
{
    public function test_confirmation_modal_component_renders()
    {
        $view = $this->view('components.file-manager.modals.confirmation-modal', [
            'userType' => 'admin',
            'username' => null
        ]);

        $view->assertSee('file-manager-confirmation');
        $view->assertSee('z-[10004]');
        $view->assertSee('confirmation-modal-title');
    }

    public function test_confirmation_modal_component_renders_with_employee_user_type()
    {
        $view = $this->view('components.file-manager.modals.confirmation-modal', [
            'userType' => 'employee',
            'username' => 'testuser'
        ]);

        $view->assertSee('file-manager-confirmation');
        $view->assertSee('employee');
        $view->assertSee('testuser');
    }

    public function test_confirmation_modal_has_proper_z_index_stacking()
    {
        $view = $this->view('components.file-manager.modals.confirmation-modal', [
            'userType' => 'admin'
        ]);

        // Check z-index hierarchy
        $view->assertSee('z-[10004]'); // Container
        $view->assertSee('z-[10005]'); // Content
        $view->assertSee('z-[10003]'); // Backdrop
    }

    public function test_confirmation_modal_includes_debug_mode_in_local_environment()
    {
        $this->app['env'] = 'local';

        $view = $this->view('components.file-manager.modals.confirmation-modal', [
            'userType' => 'admin'
        ]);

        $view->assertSee('modal-debug-info');
        $view->assertSee('Confirmation Modal Debug Info');
    }

    public function test_confirmation_modal_excludes_debug_mode_in_production()
    {
        $this->app['env'] = 'production';

        $view = $this->view('components.file-manager.modals.confirmation-modal', [
            'userType' => 'admin'
        ]);

        $view->assertDontSee('modal-debug-info');
        $view->assertDontSee('Confirmation Modal Debug Info');
    }

    public function test_confirmation_modal_has_proper_accessibility_attributes()
    {
        $view = $this->view('components.file-manager.modals.confirmation-modal', [
            'userType' => 'admin'
        ]);

        $view->assertSee('role="dialog"', false);
        $view->assertSee('aria-modal="true"', false);
        $view->assertSee('aria-labelledby="confirmation-modal-title"', false);
        $view->assertSee('aria-hidden="true"', false);
    }

    public function test_confirmation_modal_includes_alpine_js_data_function()
    {
        $view = $this->view('components.file-manager.modals.confirmation-modal', [
            'userType' => 'admin'
        ]);

        $view->assertSee('fileConfirmationModal');
        $view->assertSee('x-data=', false);
        $view->assertSee('x-show="open"', false);
    }
}