---
inclusion: always
---

# Modal Development Standards

This document establishes the standard approach for implementing modals in the Laravel application, based on the successful resolution of modal functionality issues in the file manager system.

## Core Principles

### 1. Simple State Management
- Use direct Alpine.js properties instead of complex event dispatching
- Avoid Promise-based callbacks in favor of standard `.then()/.catch()` patterns
- Keep modal state isolated and predictable

### 2. Reliable Z-Index Hierarchy
All modals must follow the established z-index hierarchy to prevent overlay conflicts:

```
z-[10003] (10003) - Admin preview modal content (highest)
z-[10002] (10002) - Admin preview modal container
z-[10000] (10000) - Standard modal content / Bulk delete modal
z-[9999]  (9999)  - Standard modal container
z-[9998]  (9998)  - Standard modal backdrop
z-50      (50)    - Employee modals, delete modals, folder picker
```

### 3. Consistent Visual Design
- Use the `modal-backdrop` class for consistent backdrop styling
- Apply `backdrop-filter: blur(2px)` and `background-color: rgba(0, 0, 0, 0.5)`
- Include proper transitions and animations

## Implementation Pattern

### Alpine.js Data Structure
```javascript
// Modal state properties
showModalName: false,
modalData: null,
modalTitle: '',
modalMessage: '',
isProcessing: false,
```

### Modal Opening Method
```javascript
openModal(data) {
    console.log('🔍 Opening modal with:', data);
    this.showModalName = true;
    this.modalData = data;
    this.modalTitle = 'Modal Title';
    this.modalMessage = 'Modal message content';
}
```

### Modal Confirmation Method
```javascript
confirmAction() {
    console.log('🔍 Confirm action called');
    if (!this.modalData || this.isProcessing) {
        console.log('🔍 Returning early - no data or already processing');
        return;
    }
    
    console.log('🔍 Starting operation');
    this.performOperation(this.modalData)
        .then(() => {
            console.log('🔍 Operation successful, closing modal');
            this.closeModal();
        })
        .catch(error => {
            console.error('🔍 Operation failed:', error);
            this.showError(error.message || 'Operation failed');
        });
}
```

### Modal Closing Method
```javascript
closeModal() {
    this.showModalName = false;
    this.modalData = null;
    this.modalTitle = '';
    this.modalMessage = '';
    this.isProcessing = false;
}
```

## Modal Component Template

### Required Structure
```blade
<!-- Modal Container -->
<div x-show="showModalName" 
     x-cloak
     class="fixed inset-0 z-[9999] overflow-y-auto"
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true"
     data-modal-name="modal-name"
     data-z-index="9999"
     data-modal-type="container">
    
    <!-- Background Overlay -->
    <div x-show="showModalName"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 modal-backdrop transition-opacity z-[9998]"
         x-on:click="closeModal()"
         data-modal-name="modal-name"
         data-z-index="9998"
         data-modal-type="backdrop"></div>

    <!-- Modal Panel -->
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div x-show="showModalName"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-[10000] relative"
             data-modal-name="modal-name"
             data-z-index="10000"
             data-modal-type="content">
            
            <!-- Modal Content -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <!-- Content goes here -->
            </div>
            
            <!-- Modal Actions -->
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <!-- Action buttons -->
                <button x-on:click="confirmAction()"
                        :disabled="isProcessing"
                        type="button"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!isProcessing">Confirm</span>
                    <span x-show="isProcessing" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    </span>
                </button>
                
                <button x-on:click="closeModal()"
                        :disabled="isProcessing"
                        type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
```

## Required CSS Classes

Ensure the following CSS is available for modal styling:

```css
.modal-backdrop {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(2px);
    transition: opacity 300ms ease-out;
}
```

## Debug Standards

### Console Logging
All modal operations should include debug logging with the 🔍 prefix:

```javascript
console.log('🔍 Modal: Opening modal');
console.log('🔍 Modal: Confirm action called');
console.log('🔍 Modal: Operation successful');
console.error('🔍 Modal: Operation failed:', error);
```

### Debug Attributes
Include debug attributes on modal elements:

```html
data-modal-name="descriptive-name"
data-z-index="9999"
data-modal-type="container|backdrop|content"
```

## Error Handling

### Standard Error Pattern
```javascript
.catch(error => {
    console.error('🔍 Operation failed:', error);
    this.showError(error.message || 'Operation failed');
    // Keep modal open for retry
});
```

### Success Pattern
```javascript
.then(() => {
    console.log('🔍 Operation successful');
    this.closeModal();
    this.showSuccess('Operation completed successfully');
});
```

## Anti-Patterns to Avoid

### ❌ Complex Event Dispatching
```javascript
// Don't do this
this.$dispatch('open-modal', { complex: 'config' });
```

### ❌ Promise-Based Callbacks
```javascript
// Don't do this
onConfirm: async () => { await someOperation(); }
```

### ❌ Multiple State Management
```javascript
// Don't do this - conflicting flag management
this.isProcessing = true;
if (this.isProcessing) return; // This prevents execution
```

### ❌ Inconsistent Z-Index Values
```javascript
// Don't use arbitrary z-index values
class="z-50 z-999 z-[5000]"
```

## Testing Requirements

### Manual Testing Checklist
- [ ] Modal opens correctly when triggered
- [ ] Modal displays with proper backdrop blur and transparency
- [ ] Confirm button shows loading state during processing
- [ ] Modal closes automatically on successful operation
- [ ] Modal stays open on error with appropriate error message
- [ ] Cancel button works correctly
- [ ] ESC key closes modal (if implemented)
- [ ] Modal is accessible via keyboard navigation
- [ ] Multiple rapid clicks don't cause issues
- [ ] Z-index layering works correctly with other modals

### Debug Testing
- [ ] Console shows proper debug messages
- [ ] No JavaScript errors in console
- [ ] Modal state changes are logged correctly
- [ ] Error handling works as expected

## Migration Guide

When updating existing modals to follow these standards:

1. **Replace complex event systems** with direct state management
2. **Update z-index values** according to the hierarchy
3. **Add debug logging** for troubleshooting
4. **Implement proper error handling** patterns
5. **Test thoroughly** with the manual checklist

## Examples

Reference the successful implementation in:
- `resources/views/components/file-manager/modals/simple-delete-modal.blade.php`
- `resources/views/components/file-manager/shared-javascript.blade.php` (deleteFile, confirmDelete, closeDeleteModal methods)

These files demonstrate the complete implementation of these standards and serve as the template for all future modal development.