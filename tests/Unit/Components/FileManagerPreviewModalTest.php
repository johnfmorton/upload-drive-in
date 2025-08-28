<?php

namespace Tests\Unit\Components;

use Tests\TestCase;
use Illuminate\View\Component;
use Illuminate\Support\Facades\View;

class FileManagerPreviewModalTest extends TestCase
{
    /** @test */
    public function preview_modal_component_renders_with_admin_user_type()
    {
        $view = View::make('components.file-manager.modals.preview-modal', [
            'userType' => 'admin',
            'username' => null
        ]);

        $content = $view->render();

        // Check that the modal has the correct z-index values
        $this->assertStringContainsString('z-[10002]', $content);
        $this->assertStringContainsString('z-[10001]', $content);
        $this->assertStringContainsString('z-[10003]', $content);

        // Check that debug mode is available in local environment
        if (app()->environment('local')) {
            $this->assertStringContainsString('toggleDebugMode()', $content);
            $this->assertStringContainsString('modal-debug-info', $content);
        }

        // Check that the modal has proper ARIA attributes
        $this->assertStringContainsString('aria-labelledby="preview-modal-title"', $content);
        $this->assertStringContainsString('role="dialog"', $content);
        $this->assertStringContainsString('aria-modal="true"', $content);

        // Check that the modal has proper data attributes for debugging
        $this->assertStringContainsString('data-modal-name="file-manager-preview"', $content);
        $this->assertStringContainsString('data-modal-type="container"', $content);
        $this->assertStringContainsString('data-modal-type="backdrop"', $content);
        $this->assertStringContainsString('data-modal-type="content"', $content);
    }

    /** @test */
    public function preview_modal_component_renders_with_employee_user_type()
    {
        $view = View::make('components.file-manager.modals.preview-modal', [
            'userType' => 'employee',
            'username' => 'testuser'
        ]);

        $content = $view->render();

        // Check that the modal has the correct z-index values
        $this->assertStringContainsString('z-[10002]', $content);
        $this->assertStringContainsString('z-[10001]', $content);
        $this->assertStringContainsString('z-[10003]', $content);

        // Check that the Alpine.js component receives the correct parameters
        $this->assertStringContainsString("filePreviewModal('employee', 'testuser')", $content);
    }

    /** @test */
    public function preview_modal_has_enhanced_features()
    {
        $view = View::make('components.file-manager.modals.preview-modal', [
            'userType' => 'admin',
            'username' => null
        ]);

        $content = $view->render();

        // Check for image zoom controls
        $this->assertStringContainsString('zoomIn()', $content);
        $this->assertStringContainsString('zoomOut()', $content);
        $this->assertStringContainsString('resetImageView()', $content);

        // Check for different preview types
        $this->assertStringContainsString("previewType === 'image'", $content);
        $this->assertStringContainsString("previewType === 'pdf'", $content);
        $this->assertStringContainsString("previewType === 'text'", $content);
        $this->assertStringContainsString("previewType === 'code'", $content);
        $this->assertStringContainsString("previewType === 'unsupported'", $content);

        // Check for proper error handling
        $this->assertStringContainsString('Preview Error', $content);
        $this->assertStringContainsString('loading', $content);

        // Check for download functionality
        $this->assertStringContainsString('downloadFile()', $content);
    }

    /** @test */
    public function preview_modal_has_proper_backdrop_handling()
    {
        $view = View::make('components.file-manager.modals.preview-modal', [
            'userType' => 'admin',
            'username' => null
        ]);

        $content = $view->render();

        // Check for proper backdrop click handling
        $this->assertStringContainsString('handleBackgroundClick($event)', $content);
        $this->assertStringContainsString('x-on:click.stop', $content);

        // Check for escape key handling
        $this->assertStringContainsString('x-on:keydown.escape.window="closeModal()"', $content);

        // Check for proper backdrop styling
        $this->assertStringContainsString('bg-black/75', $content);
        $this->assertStringContainsString('modal-backdrop', $content);
    }

    /** @test */
    public function preview_modal_supports_localization()
    {
        // Check the raw template file for localization strings
        $templatePath = resource_path('views/components/file-manager/modals/preview-modal.blade.php');
        $templateContent = file_get_contents($templatePath);

        // Check for localized strings in the raw template
        $this->assertStringContainsString("{{ __('messages.file_preview') }}", $templateContent);
        $this->assertStringContainsString("{{ __('messages.close') }}", $templateContent);
        $this->assertStringContainsString("{{ __('messages.download') }}", $templateContent);
        $this->assertStringContainsString("{{ __('messages.loading_preview') }}", $templateContent);
        $this->assertStringContainsString("{{ __('messages.preview_not_available') }}", $templateContent);
        $this->assertStringContainsString("{{ __('messages.filename') }}", $templateContent);
        $this->assertStringContainsString("{{ __('messages.size') }}", $templateContent);
        $this->assertStringContainsString("{{ __('messages.uploaded_by') }}", $templateContent);
        $this->assertStringContainsString("{{ __('messages.uploaded_at') }}", $templateContent);
    }

    /** @test */
    public function preview_modal_javascript_handles_user_type_routing()
    {
        $view = View::make('components.file-manager.modals.preview-modal', [
            'userType' => 'employee',
            'username' => 'testuser'
        ]);

        $content = $view->render();

        // Check that the JavaScript component receives the correct parameters
        $this->assertStringContainsString("filePreviewModal('employee', 'testuser')", $content);

        // Check that the JavaScript handles different user types
        $this->assertStringContainsString('this.userType === \'admin\'', $content);
        $this->assertStringContainsString('this.userType === \'employee\'', $content);
        $this->assertStringContainsString('this.username', $content);

        // Check for route generation logic
        $this->assertStringContainsString('/admin/file-manager/', $content);
        $this->assertStringContainsString('/employee/', $content);
        $this->assertStringContainsString('/preview', $content);
        $this->assertStringContainsString('/download', $content);
    }
}