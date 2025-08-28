@props([
    'message' => '',
    'show' => false,
    'autoDismiss' => false,
    'dismissDelay' => 10000,
    'position' => 'top-right',
    'retryable' => false,
    'retryAction' => null,
    'retryText' => 'Retry'
])

<div x-data="{
    show: @js($show),
    message: @js($message),
    autoDismiss: @js($autoDismiss),
    dismissDelay: @js($dismissDelay),
    retryable: @js($retryable),
    retryAction: @js($retryAction),
    retryText: @js($retryText),
    timeoutId: null,
    isRetrying: false,
    
    init() {
        this.$watch('show', (value) => {
            if (value && this.autoDismiss) {
                this.startAutoDismiss();
            } else if (!value) {
                this.clearAutoDismiss();
            }
        });
        
        // Listen for global error events
        this.$nextTick(() => {
            window.addEventListener('file-manager-error', (event) => {
                this.showError(event.detail.message, event.detail.retryable, event.detail.retryAction);
            });
        });
    },
    
    showError(msg, isRetryable = false, action = null) {
        this.message = msg;
        this.retryable = isRetryable;
        this.retryAction = action;
        this.show = true;
        this.isRetrying = false;
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
        this.isRetrying = false;
    },
    
    async retry() {
        if (!this.retryable || !this.retryAction || this.isRetrying) {
            return;
        }
        
        this.isRetrying = true;
        this.clearAutoDismiss();
        
        try {
            // If retryAction is a function name, call it on the parent component
            if (typeof this.retryAction === 'string') {
                // Dispatch retry event to parent component
                this.$dispatch('retry-action', { action: this.retryAction });
            } else if (typeof this.retryAction === 'function') {
                await this.retryAction();
            }
            
            // Auto-dismiss on successful retry
            setTimeout(() => {
                this.dismiss();
            }, 1000);
        } catch (error) {
            console.error('Retry failed:', error);
            this.isRetrying = false;
            // Restart auto-dismiss if enabled
            if (this.autoDismiss) {
                this.startAutoDismiss();
            }
        }
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
aria-live="assertive"
aria-atomic="true">
    <div class="p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <!-- Error Icon -->
                <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>
            <div class="ml-3 w-0 flex-1 pt-0.5">
                <p class="text-sm font-medium text-gray-900" x-text="message"></p>
                
                <!-- Retry button -->
                <div x-show="retryable" class="mt-3">
                    <button @click="retry()" 
                            :disabled="isRetrying"
                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isRetrying" x-text="retryText"></span>
                        <span x-show="isRetrying" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-3 w-3 text-red-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Retrying...
                        </span>
                    </button>
                </div>
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
        <div x-show="autoDismiss && show && !isRetrying" 
             class="mt-3 bg-gray-200 rounded-full h-1 overflow-hidden">
            <div class="bg-red-500 h-1 rounded-full transition-all ease-linear"
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