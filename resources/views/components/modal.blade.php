{{--
    Modal Component - Z-Index Documentation
    
    This modal component implements a strict z-index hierarchy to prevent overlay issues:
    
    Z-Index Standards:
    - Modal Container: z-[9999] - Highest level container, ensures modal appears above all page content
    - Modal Content: z-[10000] - Content layer, positioned above backdrop within modal container
    - Modal Backdrop: z-[9998] - Background overlay, positioned below content but above page content
    
    Z-Index Guidelines for Future Development:
    1. Never use z-index values above 10000 for modal content
    2. Always maintain the hierarchy: Container (9999) > Content (10000) > Backdrop (9998)
    3. Use explicit z-index values rather than relative positioning
    4. Test modal layering with debug mode: ?modal-debug=true
    5. Ensure new stacking contexts don't interfere with modal hierarchy
    
    Debugging:
    - Add ?modal-debug=true to URL to enable visual z-index debugging
    - Check console for modal state logging
    - Use data attributes for automated testing
    
    Requirements Addressed:
    - Requirement 3.1: Z-index values properly defined and documented
    - Requirement 3.2: Developer notes about proper modal layering
--}}

@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl'
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<div
    x-data="{
        show: @js($show),
        debugMode: window.location.search.includes('modal-debug=true') || localStorage.getItem('modal-debug') === 'true',
        focusables() {
            // All focusable element types...
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                // All non-disabled elements...
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
        logModalState(action) {
            if (this.debugMode) {
                console.group(`🔍 Modal Debug: ${action} - {{ $name }}`);
                console.log('Modal Name:', '{{ $name }}');
                console.log('Show State:', this.show);
                console.log('Container Z-Index:', getComputedStyle($el).zIndex);
                console.log('Timestamp:', new Date().toISOString());
                console.groupEnd();
            }
        }
    }"
    x-init="
        if (debugMode) {
            document.body.classList.add('modal-debug-enabled');
            logModalState('Initialized');
        }
        $watch('show', value => {
            if (debugMode) {
                logModalState(value ? 'Opening' : 'Closing');
            }
            if (value) {
                document.body.classList.add('overflow-y-hidden');
                {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable().focus(), 100)' : '' }}
            } else {
                document.body.classList.remove('overflow-y-hidden');
            }
        })
    "
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? (show = true, debugMode && logModalState('Event Triggered')) : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? (show = false, debugMode && logModalState('Close Event')) : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-show="show"
    {{-- 
        Modal Container: z-[9999]
        - Highest level container for the entire modal
        - Ensures modal appears above all page content
        - Creates stacking context for child elements
    --}}
    class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-[9999] modal-container"
    :class="{ 'z-debug-highest': debugMode }"
    data-modal-name="{{ $name }}"
    data-z-index="9999"
    data-modal-type="container"
    style="display: {{ $show ? 'block' : 'none' }};"
>
    {{-- 
        Modal Backdrop: z-[9998]
        - Background overlay positioned behind modal content
        - Lower z-index than content (9998 < 10000) ensures proper layering
        - Handles click-to-close functionality
        - Positioned within modal container stacking context
    --}}
    <div
        x-show="show"
        class="fixed inset-0 bg-gray-500 opacity-75 z-[9998] modal-backdrop"
        :class="{ 'z-debug-high': debugMode }"
        x-on:click="show = false"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-75"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-75"
        x-transition:leave-end="opacity-0"
        data-modal-name="{{ $name }}"
        data-z-index="9998"
        data-modal-type="backdrop"
    ></div>

    {{-- 
        Modal Content: z-[10000]
        - Highest z-index within modal hierarchy
        - Positioned above backdrop (10000 > 9998) for proper visibility
        - Contains all interactive modal elements
        - Maintains focus trap and accessibility features
    --}}
    <div
        x-show="show"
        class="relative mb-6 bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full {{ $maxWidth }} sm:mx-auto z-[10000] modal-content"
        :class="{ 'z-debug-highest': debugMode, 'stacking-context-debug': debugMode }"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        data-modal-name="{{ $name }}"
        data-z-index="10000"
        data-modal-type="content"
    >
        {{ $slot }}
    </div>
</div>
