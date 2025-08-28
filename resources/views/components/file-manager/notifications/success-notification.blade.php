@props([
    'message' => '',
    'show' => false,
    'autoDismiss' => true,
    'dismissDelay' => 5000,
    'position' => 'top-right'
])

<div x-data="{
    show: @js($show),
    message: @js($message),
    autoDismiss: @js($autoDismiss),
    dismissDelay: @js($dismissDelay),
    timeoutId: null,
    
    init() {
        this.$watch('show', (value) => {
            if (value && this.autoDismiss) {
                this.startAutoDismiss();
            } else if (!value) {
                this.clearAutoDismiss();
            }
        });
        
        // Listen for global success events
        this.$nextTick(() => {
            window.addEventListener('file-manager-success', (event) => {
                this.showSuccess(event.detail.message);
            });
        });
    },
    
    showSuccess(msg) {
        this.message = msg;
        this.show = true;
        if (this.autoDismiss) {
            this.startAutoDismiss();
        }
    },
    
    startAutoDismiss() {
        this.clearAutoDismiss();
        this.timeoutId = setTimeout(() => {
            this.dismiss();
        }, this.dismissDelay);
    },
    
    clearAutoDismiss() {
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
            this.timeoutId = null;
        }
    },
    
    dismiss() {
        this.show = false;
        this.clearAutoDismiss();
    }
}"
x-show="show"
x-transition:enter="transform ease-out duration-300 transition"
x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
x-transition:leave="transition ease-in duration-100"
x-transition:leave-start="opacity-100"
x-transition:leave-end="opacity-0"
class="fixed z-50 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden
    @if($position === 'top-right') top-4 right-4 @endif
    @if($position === 'top-left') top-4 left-4 @endif
    @if($position === 'bottom-right') bottom-4 right-4 @endif
    @if($position === 'bottom-left') bottom-4 left-4 @endif"
style="display: none;"
role="alert"
aria-live="polite"
aria-atomic="true">
    <div class="p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <!-- Success Icon -->
                <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-3 w-0 flex-1 pt-0.5">
                <p class="text-sm font-medium text-gray-900" x-text="message"></p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
                <button @click="dismiss()" 
                        class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        aria-label="Close notification">
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Progress bar for auto-dismiss -->
        <div x-show="autoDismiss && show" 
             class="mt-3 bg-gray-200 rounded-full h-1 overflow-hidden">
            <div class="bg-green-500 h-1 rounded-full transition-all ease-linear"
                 x-bind:style="`width: 100%; animation: shrink ${dismissDelay}ms linear;`"></div>
        </div>
    </div>
</div>

<style>
@keyframes shrink {
    from { width: 100%; }
    to { width: 0%; }
}
</style>