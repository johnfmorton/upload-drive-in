# File Manager Notification Components

This directory contains reusable notification components for the file manager system.

## Components

### Success Notification (`success-notification.blade.php`)

Toast-style success notifications with auto-dismiss functionality.

**Props:**
- `message` (string): The success message to display
- `show` (boolean): Whether to show the notification
- `autoDismiss` (boolean, default: true): Auto-dismiss after delay
- `dismissDelay` (integer, default: 5000): Auto-dismiss delay in milliseconds
- `position` (string, default: 'top-right'): Position on screen

**Usage:**
```blade
<x-file-manager.notifications.success-notification 
    :show="$showSuccess"
    :message="$successMessage"
    position="top-right"
    :auto-dismiss="true"
    :dismiss-delay="5000" />
```

**JavaScript Events:**
The component listens for global `file-manager-success` events:
```javascript
window.dispatchEvent(new CustomEvent('file-manager-success', {
    detail: { message: 'File uploaded successfully!' }
}));
```

### Error Notification (`error-notification.blade.php`)

Toast-style error notifications with optional retry functionality.

**Props:**
- `message` (string): The error message to display
- `show` (boolean): Whether to show the notification
- `autoDismiss` (boolean, default: false): Auto-dismiss after delay
- `dismissDelay` (integer, default: 10000): Auto-dismiss delay in milliseconds
- `position` (string, default: 'top-right'): Position on screen
- `retryable` (boolean, default: false): Show retry button
- `retryAction` (string|null): Action to retry (function name or event)
- `retryText` (string, default: 'Retry'): Text for retry button

**Usage:**
```blade
<x-file-manager.notifications.error-notification 
    :show="$showError"
    :message="$errorMessage"
    position="top-right"
    :retryable="true"
    retry-action="retryUpload"
    retry-text="Try Again" />
```

**JavaScript Events:**
The component listens for global `file-manager-error` events:
```javascript
window.dispatchEvent(new CustomEvent('file-manager-error', {
    detail: { 
        message: 'Failed to upload file',
        retryable: true,
        retryAction: 'retryUpload'
    }
}));
```

The component dispatches `retry-action` events when retry is clicked:
```javascript
// Listen for retry events in parent component
this.$nextTick(() => {
    this.$el.addEventListener('retry-action', (event) => {
        const action = event.detail.action;
        // Handle retry action
        this[action]();
    });
});
```

## Position Options

Both components support these position values:
- `top-right` (default)
- `top-left`
- `bottom-right`
- `bottom-left`

## Accessibility Features

- Proper ARIA attributes (`role="alert"`, `aria-live`, `aria-atomic`)
- Screen reader support with semantic HTML
- Keyboard accessible close buttons
- High contrast colors for visibility
- Focus management

## Styling

Components use Tailwind CSS classes and include:
- Smooth Alpine.js transitions
- Progress bars for auto-dismiss timing
- Loading spinners for retry actions
- Responsive design for mobile/desktop
- High z-index (z-50) for proper stacking

## Integration Example

```blade
<!-- In your file manager template -->
<div x-data="fileManager()">
    <!-- Your file manager content -->
    
    <!-- Notification components -->
    <x-file-manager.notifications.success-notification />
    <x-file-manager.notifications.error-notification />
</div>

<script>
function fileManager() {
    return {
        // Your file manager data
        
        showSuccess(message) {
            window.dispatchEvent(new CustomEvent('file-manager-success', {
                detail: { message }
            }));
        },
        
        showError(message, retryable = false, retryAction = null) {
            window.dispatchEvent(new CustomEvent('file-manager-error', {
                detail: { message, retryable, retryAction }
            }));
        },
        
        // Example usage
        async uploadFile() {
            try {
                // Upload logic
                this.showSuccess('File uploaded successfully!');
            } catch (error) {
                this.showError('Failed to upload file', true, 'retryUpload');
            }
        },
        
        async retryUpload() {
            // Retry logic
            await this.uploadFile();
        }
    }
}
</script>
```