@props([
    'userType' => 'admin',
    'username' => null
])

<!-- Enhanced Progress Modal with Z-Index Management and Debug Mode -->
<div
    x-data="fileProgressModal('{{ $userType }}', '{{ $username }}')"
    x-on:open-progress-modal.window="openModal($event.detail)"
    x-on:update-progress.window="updateProgress($event.detail)"
    x-on:close-progress-modal.window="closeModal()"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[10006] overflow-y-auto modal-container"
    aria-labelledby="progress-modal-title"
    role="dialog"
    aria-modal="true"
    :class="{ 'z-debug-highest': debugMode }"
    data-modal-name="file-manager-progress"
    data-z-index="10006"
    data-modal-type="container"
    x-on:keydown.escape.window="handleEscapeKey($event)"
    style="pointer-events: auto; display: none;"
>
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/75 transition-opacity z-[10005] modal-backdrop"
            :class="{ 'z-debug-medium': debugMode }"
            data-modal-name="file-manager-progress"
            data-z-index="10005"
            data-modal-type="backdrop"
            aria-hidden="true"
        ></div>

        <!-- This element is to trick the browser into centering the modal contents. -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-[10007] relative modal-content"
            :class="{ 'z-debug-high': debugMode }"
            data-modal-name="file-manager-progress"
            data-z-index="10007"
            data-modal-type="content"
        >
            <!-- Header -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <!-- Icon -->
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10"
                         :class="iconClasses">
                        <!-- Processing Icon (default) -->
                        <template x-if="!status || status === 'processing'">
                            <svg class="h-6 w-6 animate-spin" :class="iconTextClasses" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>

                        <!-- Success Icon -->
                        <template x-if="status === 'completed'">
                            <svg class="h-6 w-6" :class="iconTextClasses" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </template>

                        <!-- Error Icon -->
                        <template x-if="status === 'error'">
                            <svg class="h-6 w-6" :class="iconTextClasses" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.008v.008H12v-.008Z" />
                            </svg>
                        </template>

                        <!-- Cancelled Icon -->
                        <template x-if="status === 'cancelled'">
                            <svg class="h-6 w-6" :class="iconTextClasses" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </template>
                    </div>

                    <!-- Content -->
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                        <h3 id="progress-modal-title" class="text-lg leading-6 font-medium text-gray-900">
                            <span x-text="operation"></span> {{ __('messages.files') }}
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 mb-4" x-text="message"></p>

                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                                <div 
                                    class="h-2.5 rounded-full transition-all duration-300 ease-out"
                                    :class="progressBarClasses"
                                    :style="`width: ${progressPercentage}%`"
                                ></div>
                            </div>

                            <!-- Progress Stats -->
                            <div class="flex justify-between text-xs text-gray-500 mb-2">
                                <span x-text="`${current} of ${total}`"></span>
                                <span x-text="`${progressPercentage}%`"></span>
                            </div>

                            <!-- Current Item -->
                            <template x-if="currentItem">
                                <div class="text-xs text-gray-600 truncate" x-text="currentItem"></div>
                            </template>

                            <!-- Error Details -->
                            <template x-if="status === 'error' && errorMessage">
                                <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700" x-text="errorMessage"></div>
                            </template>

                            <!-- Success Summary -->
                            <template x-if="status === 'completed' && successCount > 0">
                                <div class="mt-2 p-2 bg-green-50 border border-green-200 rounded text-sm text-green-700">
                                    <span x-text="successCount"></span> {{ __('messages.files_processed_successfully') }}
                                    <template x-if="failedCount > 0">
                                        <span>, <span x-text="failedCount"></span> {{ __('messages.failed') }}</span>
                                    </template>
                                </div>
                            </template>

                            <!-- Estimated Time -->
                            <template x-if="estimatedTimeRemaining && status === 'processing'">
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ __('messages.estimated_time_remaining') }}: <span x-text="formatTime(estimatedTimeRemaining)"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <!-- Close Button (shown when completed, error, or cancelled) -->
                <template x-if="status === 'completed' || status === 'error' || status === 'cancelled'">
                    <button
                        x-on:click="closeModal()"
                        type="button"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        {{ __('messages.close') }}
                    </button>
                </template>

                <!-- Cancel Button (shown when processing) -->
                <template x-if="status === 'processing' && cancellable">
                    <button
                        x-on:click="cancelOperation()"
                        :disabled="cancelling"
                        type="button"
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span x-show="!cancelling">{{ __('messages.cancel') }}</span>
                        <span x-show="cancelling" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('messages.cancelling') }}
                        </span>
                    </button>
                </template>

                <!-- Retry Button (shown when error) -->
                <template x-if="status === 'error' && retryable">
                    <button
                        x-on:click="retryOperation()"
                        type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:mr-3 sm:w-auto sm:text-sm"
                    >
                        {{ __('messages.retry') }}
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Debug Info Panel (only in development) -->
    @if(app()->environment('local'))
    <div x-show="debugMode" x-cloak class="modal-debug-info">
        <h4>Progress Modal Debug Info</h4>
        <ul>
            <li>Container Z-Index: 10006</li>
            <li>Content Z-Index: 10007</li>
            <li>Backdrop Z-Index: 10005</li>
            <li>User Type: <span x-text="userType"></span></li>
            <li>Username: <span x-text="username || 'N/A'"></span></li>
            <li>Operation: <span x-text="operation || 'N/A'"></span></li>
            <li>Status: <span x-text="status"></span></li>
            <li>Progress: <span x-text="current + '/' + total"></span></li>
            <li>Cancellable: <span x-text="cancellable"></span></li>
        </ul>
        <button x-on:click="logModalState()">Log Modal State</button>
        <button x-on:click="toggleDebugMode()">Hide Debug</button>
    </div>
    @endif
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('fileProgressModal', (userType = 'admin', username = null) => ({
        open: false,
        userType: userType,
        username: username,
        debugMode: false,

        // Progress state
        operation: '',
        message: '',
        current: 0,
        total: 0,
        status: 'processing', // processing, completed, error, cancelled
        currentItem: '',
        
        // Error handling
        errorMessage: '',
        successCount: 0,
        failedCount: 0,
        
        // Control options
        cancellable: true,
        retryable: false,
        cancelling: false,
        
        // Timing
        startTime: null,
        estimatedTimeRemaining: null,
        
        // Callbacks
        onCancel: null,
        onRetry: null,
        onComplete: null,

        init() {
            // Initialize debug mode from localStorage or URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const debugParam = urlParams.get('modal-debug');
            const debugStorage = localStorage.getItem('modal-debug');
            this.debugMode = debugParam === 'true' || debugStorage === 'true';
        },

        get progressPercentage() {
            if (this.total === 0) return 0;
            return Math.round((this.current / this.total) * 100);
        },

        get iconClasses() {
            const baseClasses = 'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10';
            switch (this.status) {
                case 'completed':
                    return baseClasses + ' bg-green-100';
                case 'error':
                    return baseClasses + ' bg-red-100';
                case 'cancelled':
                    return baseClasses + ' bg-gray-100';
                default:
                    return baseClasses + ' bg-blue-100';
            }
        },

        get iconTextClasses() {
            switch (this.status) {
                case 'completed':
                    return 'text-green-600';
                case 'error':
                    return 'text-red-600';
                case 'cancelled':
                    return 'text-gray-600';
                default:
                    return 'text-blue-600';
            }
        },

        get progressBarClasses() {
            switch (this.status) {
                case 'completed':
                    return 'bg-green-600';
                case 'error':
                    return 'bg-red-600';
                case 'cancelled':
                    return 'bg-gray-600';
                default:
                    return 'bg-blue-600';
            }
        },

        openModal(config) {
            // Set configuration
            this.operation = config.operation || '{{ __("messages.processing") }}';
            this.message = config.message || '{{ __("messages.please_wait") }}';
            this.current = config.current || 0;
            this.total = config.total || 0;
            this.status = config.status || 'processing';
            this.currentItem = config.currentItem || '';
            this.cancellable = config.cancellable !== false;
            this.retryable = config.retryable === true;
            this.onCancel = config.onCancel || null;
            this.onRetry = config.onRetry || null;
            this.onComplete = config.onComplete || null;

            // Reset state
            this.errorMessage = '';
            this.successCount = 0;
            this.failedCount = 0;
            this.cancelling = false;
            this.startTime = Date.now();
            this.estimatedTimeRemaining = null;

            this.open = true;

            // Log modal opening in debug mode
            if (this.debugMode) {
                console.log('🔍 Progress modal opened:', {
                    operation: this.operation,
                    total: this.total,
                    userType: this.userType,
                    username: this.username
                });
            }
        },

        closeModal() {
            this.open = false;
            this.resetState();

            // Log modal closing in debug mode
            if (this.debugMode) {
                console.log('🔍 Progress modal closed');
            }

            // Call completion callback if provided
            if (this.onComplete && typeof this.onComplete === 'function') {
                this.onComplete({
                    status: this.status,
                    successCount: this.successCount,
                    failedCount: this.failedCount
                });
            }
        },

        resetState() {
            this.operation = '';
            this.message = '';
            this.current = 0;
            this.total = 0;
            this.status = 'processing';
            this.currentItem = '';
            this.errorMessage = '';
            this.successCount = 0;
            this.failedCount = 0;
            this.cancellable = true;
            this.retryable = false;
            this.cancelling = false;
            this.startTime = null;
            this.estimatedTimeRemaining = null;
            this.onCancel = null;
            this.onRetry = null;
            this.onComplete = null;
        },

        updateProgress(data) {
            if (!this.open) return;

            // Update progress data
            if (data.current !== undefined) this.current = data.current;
            if (data.total !== undefined) this.total = data.total;
            if (data.message !== undefined) this.message = data.message;
            if (data.currentItem !== undefined) this.currentItem = data.currentItem;
            if (data.status !== undefined) this.status = data.status;
            if (data.errorMessage !== undefined) this.errorMessage = data.errorMessage;
            if (data.successCount !== undefined) this.successCount = data.successCount;
            if (data.failedCount !== undefined) this.failedCount = data.failedCount;

            // Calculate estimated time remaining
            if (this.status === 'processing' && this.current > 0 && this.total > 0) {
                const elapsed = Date.now() - this.startTime;
                const rate = this.current / elapsed;
                const remaining = this.total - this.current;
                this.estimatedTimeRemaining = remaining / rate;
            }

            if (this.debugMode) {
                console.log('🔍 Progress updated:', {
                    current: this.current,
                    total: this.total,
                    status: this.status,
                    percentage: this.progressPercentage
                });
            }
        },

        async cancelOperation() {
            if (this.cancelling) return;

            this.cancelling = true;

            if (this.debugMode) {
                console.log('🔍 Cancelling operation');
            }

            try {
                if (this.onCancel && typeof this.onCancel === 'function') {
                    await this.onCancel();
                } else {
                    // Dispatch event for parent component to handle
                    this.$dispatch('progress-cancelled', {
                        operation: this.operation
                    });
                }

                this.status = 'cancelled';
                this.message = '{{ __("messages.operation_cancelled") }}';

            } catch (error) {
                console.error('Cancel operation failed:', error);
                
                if (this.debugMode) {
                    console.log('🔍 Cancel operation failed:', error);
                }

                this.status = 'error';
                this.errorMessage = error.message || '{{ __("messages.cancel_failed") }}';

            } finally {
                this.cancelling = false;
            }
        },

        async retryOperation() {
            if (this.debugMode) {
                console.log('🔍 Retrying operation');
            }

            try {
                if (this.onRetry && typeof this.onRetry === 'function') {
                    await this.onRetry();
                } else {
                    // Dispatch event for parent component to handle
                    this.$dispatch('progress-retry', {
                        operation: this.operation
                    });
                }

                // Reset to processing state
                this.status = 'processing';
                this.current = 0;
                this.errorMessage = '';
                this.startTime = Date.now();

            } catch (error) {
                console.error('Retry operation failed:', error);
                
                if (this.debugMode) {
                    console.log('🔍 Retry operation failed:', error);
                }

                this.status = 'error';
                this.errorMessage = error.message || '{{ __("messages.retry_failed") }}';
            }
        },

        handleEscapeKey(event) {
            // Only allow escape to close if not processing or if cancellable
            if (this.status !== 'processing' || this.cancellable) {
                if (this.status === 'processing' && this.cancellable) {
                    this.cancelOperation();
                } else {
                    this.closeModal();
                }
            }
        },

        // Utility methods
        formatTime(milliseconds) {
            const seconds = Math.ceil(milliseconds / 1000);
            if (seconds < 60) {
                return `${seconds}s`;
            } else if (seconds < 3600) {
                const minutes = Math.ceil(seconds / 60);
                return `${minutes}m`;
            } else {
                const hours = Math.ceil(seconds / 3600);
                return `${hours}h`;
            }
        },

        // Debug mode methods
        toggleDebugMode() {
            this.debugMode = !this.debugMode;
            localStorage.setItem('modal-debug', this.debugMode.toString());
            
            if (this.debugMode) {
                console.log('🔍 Progress modal debug mode enabled');
                this.logModalState();
            } else {
                console.log('🔍 Progress modal debug mode disabled');
            }
        },

        logModalState() {
            console.group('🔍 Progress Modal State');
            console.log('Open:', this.open);
            console.log('User Type:', this.userType);
            console.log('Username:', this.username);
            console.log('Operation:', this.operation);
            console.log('Status:', this.status);
            console.log('Progress:', `${this.current}/${this.total} (${this.progressPercentage}%)`);
            console.log('Cancellable:', this.cancellable);
            console.log('Retryable:', this.retryable);
            console.log('Debug Mode:', this.debugMode);
            console.groupEnd();
        }
    }));
});
</script>