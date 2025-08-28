<?php

namespace Tests\Unit\Components;

use Tests\TestCase;
use Illuminate\View\Component;

class FileManagerSuccessNotificationTest extends TestCase
{
    public function test_success_notification_renders_with_default_props()
    {
        $view = $this->blade('<x-file-manager.notifications.success-notification />');
        
        $view->assertSee('role="alert"', false);
        $view->assertSee('aria-live="polite"', false);
        $view->assertSee('style="display: none;"', false);
        $view->assertSee('top-4 right-4'); // default position
    }
    
    public function test_success_notification_renders_with_message()
    {
        $message = 'File uploaded successfully!';
        
        $view = $this->blade('<x-file-manager.notifications.success-notification :message="$message" />', [
            'message' => $message
        ]);
        
        $view->assertSee("message: '{$message}'", false);
    }
    
    public function test_success_notification_renders_with_show_true()
    {
        $view = $this->blade('<x-file-manager.notifications.success-notification :show="true" />');
        
        $view->assertSee('show: true', false);
    }
    
    public function test_success_notification_renders_with_custom_position()
    {
        $view = $this->blade('<x-file-manager.notifications.success-notification position="bottom-left" />');
        
        $view->assertSee('bottom-4 left-4');
        $view->assertDontSee('top-4 right-4');
    }
    
    public function test_success_notification_renders_with_auto_dismiss_disabled()
    {
        $view = $this->blade('<x-file-manager.notifications.success-notification :auto-dismiss="false" />');
        
        $view->assertSee('autoDismiss: false', false);
    }
    
    public function test_success_notification_renders_with_custom_dismiss_delay()
    {
        $dismissDelay = 3000;
        
        $view = $this->blade('<x-file-manager.notifications.success-notification :dismiss-delay="$dismissDelay" />', [
            'dismissDelay' => $dismissDelay
        ]);
        
        $view->assertSee("dismissDelay: {$dismissDelay}", false);
    }
    
    public function test_success_notification_includes_close_button()
    {
        $view = $this->blade('<x-file-manager.notifications.success-notification />');
        
        $view->assertSee('@click="dismiss()"', false);
        $view->assertSee('aria-label="Close notification"', false);
        $view->assertSee('<span class="sr-only">Close</span>', false);
    }
    
    public function test_success_notification_includes_success_icon()
    {
        $view = $this->blade('<x-file-manager.notifications.success-notification />');
        
        $view->assertSee('text-green-400');
        $view->assertSee('M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'); // checkmark circle path
    }
    
    public function test_success_notification_includes_progress_bar()
    {
        $view = $this->blade('<x-file-manager.notifications.success-notification />');
        
        $view->assertSee('x-show="autoDismiss && show"', false);
        $view->assertSee('bg-green-500');
        $view->assertSee('animation: shrink', false);
    }
    
    public function test_success_notification_includes_alpine_transitions()
    {
        $view = $this->blade('<x-file-manager.notifications.success-notification />');
        
        $view->assertSee('x-transition:enter="transform ease-out duration-300 transition"', false);
        $view->assertSee('x-transition:leave="transition ease-in duration-100"', false);
    }
    
    public function test_success_notification_listens_for_global_events()
    {
        $view = $this->blade('<x-file-manager.notifications.success-notification />');
        
        $view->assertSee("window.addEventListener('file-manager-success'", false);
        $view->assertSee('this.showSuccess(event.detail.message)', false);
    }
}