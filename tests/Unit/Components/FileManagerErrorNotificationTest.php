<?php

namespace Tests\Unit\Components;

use Tests\TestCase;
use Illuminate\View\Component;

class FileManagerErrorNotificationTest extends TestCase
{
    public function test_error_notification_renders_with_default_props()
    {
        $view = $this->blade('<x-file-manager.notifications.error-notification />');
        
        $view->assertSee('role="alert"', false);
        $view->assertSee('aria-live="assertive"', false);
        $view->assertSee('style="display: none;"', false);
        $view->assertSee('top-4 right-4'); // default position
    }
    
    public function test_error_notification_renders_with_message()
    {
        $message = 'Failed to upload file. Please try again.';
        
        $view = $this->blade('<x-file-manager.notifications.error-notification :message="$message" />', [
            'message' => $message
        ]);
        
        $view->assertSee("message: '{$message}'", false);
    }
    
    public function test_error_notification_renders_with_show_true()
    {
        $view = $this->blade('<x-file-manager.notifications.error-notification :show="true" />');
        
        $view->assertSee('show: true', false);
    }
    
    public function test_error_notification_renders_with_custom_position()
    {
        $view = $this->blade('<x-file-manager.notifications.error-notification position="bottom-right" />');
        
        $view->assertSee('bottom-4 right-4');
        $view->assertDontSee('top-4 right-4');
    }
    
    public function test_error_notification_renders_with_auto_dismiss_enabled()
    {
        $view = $this->blade('<x-file-manager.notifications.error-notification :auto-dismiss="true" />');
        
        $view->assertSee('autoDismiss: true', false);
    }
    
    public function test_error_notification_renders_with_custom_dismiss_delay()
    {
        $dismissDelay = 15000;
        
        $view = $this->blade('<x-file-manager.notifications.error-notification :dismiss-delay="$dismissDelay" />', [
            'dismissDelay' => $dismissDelay
        ]);
        
        $view->assertSee("dismissDelay: {$dismissDelay}", false);
    }
    
    public function test_error_notification_renders_with_retryable_true()
    {
        $view = $this->blade('<x-file-manager.notifications.error-notification :retryable="true" />');
        
        $view->assertSee('retryable: true', false);
        $view->assertSee('x-show="retryable"', false);
    }
    
    public function test_error_notification_renders_with_custom_retry_text()
    {
        $retryText = 'Try Again';
        
        $view = $this->blade('<x-file-manager.notifications.error-notification :retry-text="$retryText" />', [
            'retryText' => $retryText
        ]);
        
        $view->assertSee("retryText: '{$retryText}'", false);
    }
    
    public function test_error_notification_renders_with_retry_action()
    {
        $retryAction = 'retryUpload';
        
        $view = $this->blade('<x-file-manager.notifications.error-notification :retry-action="$retryAction" />', [
            'retryAction' => $retryAction
        ]);
        
        $view->assertSee("retryAction: '{$retryAction}'", false);
    }
    
    public function test_error_notification_includes_close_button()
    {
        $view = $this->blade('<x-file-manager.notifications.error-notification />');
        
        $view->assertSee('@click="dismiss()"', false);
        $view->assertSee('aria-label="Close notification"', false);
        $view->assertSee('<span class="sr-only">Close</span>', false);
    }
    
    public function test_error_notification_includes_error_icon()
    {
        $view = $this->blade('<x-file-manager.notifications.error-notification />');
        
        $view->assertSee('text-red-400');
        $view->assertSee('M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z'); // exclamation circle path
    }
    
    public function test_error_notification_includes_retry_button()
    {
        $view = $this->blade('<x-file-manager.notifications.error-notification />');
        
        $view->assertSee('@click="retry()"', false);
        $view->assertSee(':disabled="isRetrying"', false);
        $view->assertSee('x-show="!isRetrying"', false);
        $view->assertSee('x-show="isRetrying"', false);
    }
    
    public function test_error_notification_includes_progress_bar()
    {
        $view = $this->blade('<x-file-manager.notifications.error-notification />');
        
        $view->assertSee('x-show="autoDismiss && show && !isRetrying"', false);
        $view->assertSee('bg-red-500');
        $view->assertSee('animation: shrink', false);
    }
    
    public function test_error_notification_includes_alpine_transitions()
    {
        $view = $this->blade('<x-file-manager.notifications.error-notification />');
        
        $view->assertSee('x-transition:enter="transform ease-out duration-300 transition"', false);
        $view->assertSee('x-transition:leave="transition ease-in duration-100"', false);
    }
    
    public function test_error_notification_listens_for_global_events()
    {
        $view = $this->blade('<x-file-manager.notifications.error-notification />');
        
        $view->assertSee("window.addEventListener('file-manager-error'", false);
        $view->assertSee('this.showError(event.detail.message, event.detail.retryable, event.detail.retryAction)', false);
    }
    
    public function test_error_notification_includes_retry_spinner()
    {
        $view = $this->blade('<x-file-manager.notifications.error-notification />');
        
        $view->assertSee('animate-spin');
        $view->assertSee('Retrying...');
    }
    
    public function test_error_notification_dispatches_retry_event()
    {
        $view = $this->blade('<x-file-manager.notifications.error-notification />');
        
        $view->assertSee("\$dispatch('retry-action'", false);
        $view->assertSee('action: this.retryAction', false);
    }
}