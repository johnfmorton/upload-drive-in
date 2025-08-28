<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\FileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FileManagerNotificationComponentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    public function test_success_notification_component_renders_in_file_manager()
    {
        $this->actingAs($this->user);
        
        $response = $this->get('/admin/file-manager');
        
        $response->assertStatus(200);
        
        // Test that we can render the success notification component
        $view = $this->blade(
            '<x-file-manager.notifications.success-notification 
                :show="true" 
                message="File uploaded successfully!" 
                position="top-right" />'
        );
        
        $view->assertSee('File uploaded successfully!', false);
        $view->assertSee('text-green-400'); // success icon color
        $view->assertSee('top-4 right-4'); // position classes
        $view->assertSee('role="alert"', false);
        $view->assertSee('aria-live="polite"', false);
    }

    public function test_error_notification_component_renders_in_file_manager()
    {
        $this->actingAs($this->user);
        
        $response = $this->get('/admin/file-manager');
        
        $response->assertStatus(200);
        
        // Test that we can render the error notification component
        $view = $this->blade(
            '<x-file-manager.notifications.error-notification 
                :show="true" 
                message="Failed to delete file. Please try again." 
                :retryable="true"
                retry-action="retryDelete"
                position="top-right" />'
        );
        
        $view->assertSee('Failed to delete file. Please try again.', false);
        $view->assertSee('text-red-400'); // error icon color
        $view->assertSee('top-4 right-4'); // position classes
        $view->assertSee('role="alert"', false);
        $view->assertSee('aria-live="assertive"', false);
        $view->assertSee('retryable: true', false);
        $view->assertSee('retryAction: \'retryDelete\'', false);
    }

    public function test_success_notification_with_different_positions()
    {
        $positions = [
            'top-right' => 'top-4 right-4',
            'top-left' => 'top-4 left-4',
            'bottom-right' => 'bottom-4 right-4',
            'bottom-left' => 'bottom-4 left-4'
        ];

        foreach ($positions as $position => $expectedClasses) {
            $view = $this->blade(
                '<x-file-manager.notifications.success-notification position="' . $position . '" />'
            );
            
            $view->assertSee($expectedClasses);
        }
    }

    public function test_error_notification_with_different_positions()
    {
        $positions = [
            'top-right' => 'top-4 right-4',
            'top-left' => 'top-4 left-4',
            'bottom-right' => 'bottom-4 right-4',
            'bottom-left' => 'bottom-4 left-4'
        ];

        foreach ($positions as $position => $expectedClasses) {
            $view = $this->blade(
                '<x-file-manager.notifications.error-notification position="' . $position . '" />'
            );
            
            $view->assertSee($expectedClasses);
        }
    }

    public function test_success_notification_auto_dismiss_functionality()
    {
        $view = $this->blade(
            '<x-file-manager.notifications.success-notification 
                :auto-dismiss="true" 
                :dismiss-delay="3000" />'
        );
        
        $view->assertSee('autoDismiss: true', false);
        $view->assertSee('dismissDelay: 3000', false);
        $view->assertSee('x-show="autoDismiss && show"', false);
        $view->assertSee('bg-green-500'); // progress bar color
    }

    public function test_error_notification_retry_functionality()
    {
        $view = $this->blade(
            '<x-file-manager.notifications.error-notification 
                :retryable="true"
                retry-action="retryUpload"
                retry-text="Try Again" />'
        );
        
        $view->assertSee('retryable: true', false);
        $view->assertSee('retryAction: \'retryUpload\'', false);
        $view->assertSee('retryText: \'Try Again\'', false);
        $view->assertSee('@click="retry()"', false);
        $view->assertSee(':disabled="isRetrying"', false);
    }

    public function test_notifications_include_accessibility_attributes()
    {
        $successView = $this->blade('<x-file-manager.notifications.success-notification />');
        $errorView = $this->blade('<x-file-manager.notifications.error-notification />');
        
        // Success notification accessibility
        $successView->assertSee('role="alert"', false);
        $successView->assertSee('aria-live="polite"', false);
        $successView->assertSee('aria-atomic="true"', false);
        $successView->assertSee('aria-label="Close notification"', false);
        $successView->assertSee('<span class="sr-only">Close</span>', false);
        
        // Error notification accessibility
        $errorView->assertSee('role="alert"', false);
        $errorView->assertSee('aria-live="assertive"', false);
        $errorView->assertSee('aria-atomic="true"', false);
        $errorView->assertSee('aria-label="Close notification"', false);
        $errorView->assertSee('<span class="sr-only">Close</span>', false);
    }

    public function test_notifications_include_proper_z_index()
    {
        $successView = $this->blade('<x-file-manager.notifications.success-notification />');
        $errorView = $this->blade('<x-file-manager.notifications.error-notification />');
        
        // Both should have high z-index for proper stacking
        $successView->assertSee('z-50');
        $errorView->assertSee('z-50');
    }

    public function test_notifications_include_alpine_transitions()
    {
        $successView = $this->blade('<x-file-manager.notifications.success-notification />');
        $errorView = $this->blade('<x-file-manager.notifications.error-notification />');
        
        $expectedTransitions = [
            'x-transition:enter="transform ease-out duration-300 transition"',
            'x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"',
            'x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"',
            'x-transition:leave="transition ease-in duration-100"',
            'x-transition:leave-start="opacity-100"',
            'x-transition:leave-end="opacity-0"'
        ];
        
        foreach ($expectedTransitions as $transition) {
            $successView->assertSee($transition, false);
            $errorView->assertSee($transition, false);
        }
    }

    public function test_notifications_listen_for_global_events()
    {
        $successView = $this->blade('<x-file-manager.notifications.success-notification />');
        $errorView = $this->blade('<x-file-manager.notifications.error-notification />');
        
        // Success notification should listen for success events
        $successView->assertSee("window.addEventListener('file-manager-success'", false);
        $successView->assertSee('this.showSuccess(event.detail.message)', false);
        
        // Error notification should listen for error events
        $errorView->assertSee("window.addEventListener('file-manager-error'", false);
        $errorView->assertSee('this.showError(event.detail.message, event.detail.retryable, event.detail.retryAction)', false);
    }
}