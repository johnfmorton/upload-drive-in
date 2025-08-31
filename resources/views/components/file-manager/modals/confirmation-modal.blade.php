@props([
    'userType' => 'admin',
    'username' => null
])

<!-- Enhanced Confirmation Modal with Z-Index Management and Debug Mode -->
<div
    x-data="fileConfirmationModal('{{ $userType }}', '{{ $username }}')"
    x-on:open-confirmation-modal.window="openModal($event.detail)"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[10004] overflow-y-auto modal-container"
    aria-labelledby="confirmation-modal-title"
    role="dialog"
    aria-modal="true"
    data-modal-name="file-manager-confirmation"
    data-z-index="10004"
    data-modal-type="container"
    x-on:close.stop="closeModal()"
    x-on:keydown.escape.window="closeModal()"
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
            class="fixed inset-0 bg-black/75 transition-opacity z-[10003] modal-backdrop"
            x-on:click.stop="handleBackgroundClick($event)"
            data-modal-name="file-manager-confirmation"
            data-z-index="10003"
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
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-[10005] relative modal-content"
            data-modal-name="file-manager-confirmation"
            data-z-index="10005"
            data-modal-type="content"
        >
            <!-- Content -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <!-- Icon -->
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10"
                         :class="iconClasses">
                        <!-- Warning Icon (default) -->
                        <template x-if="!iconType || iconType === 'warning'">
                            <svg class="h-6 w-6" :class="iconTextClasses" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.008v.008H12v-.008Z" />
                            </svg>
                        </template>

                        <!-- Danger Icon -->
                        <template x-if="iconType === 'danger'">
                            <svg class="h-6 w-6" :class="iconTextClasses" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </template>

                        <!-- Info Icon -->
                        <template x-if="iconType === 'info'">
                            <svg class="h-6 w-6" :class="iconTextClasses" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </template>

                        <!-- Success Icon -->
                        <template x-if="iconType === 'success'">
                            <svg class="h-6 w-6" :class="iconTextClasses" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </template>
                    </div>

                    <!-- Content -->
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 id="confirmation-modal-title" class="text-lg leading-6 font-medium text-gray-900" x-text="title">
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" x-text="message">
                            </p>
                            
                            <!-- Additional details -->
                            <template x-if="details">
                                <div class="mt-2">
                                    <p class="text-sm font-medium text-gray-900" x-text="details"></p>
                                </div>
                            </template>

                            <!-- Warning text -->
                            <template x-if="warning">
                                <p class="mt-1 text-xs text-gray-500" x-text="warning"></p>
                            </template>

                            <!-- File count for bulk operations -->
                            <template x-if="fileCount && fileCount > 1">
                                <p class="mt-2 text-sm font-medium text-gray-900">
                                    <span x-text="fileCount"></span> {{ __('messages.files_selected') }}
                                </p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <!-- Confirm Button -->
                <button
                    x-on:click="confirmAction()"
                    :disabled="processing"
                    type="button"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="confirmButtonClasses"
                >
                    <span x-show="!processing" x-text="confirmText"></span>
                    <span x-show="processing" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="processingText"></span>
                    </span>
                </button>

                <!-- Cancel Button -->
                <button
                    x-on:click="closeModal()"
                    :disabled="processing"
                    type="button"
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ __('messages.cancel') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Debug Info Panel (only in development) -->
    @if(app()->environment('local'))
    <div x-show="debugMode" x-cloak class="modal-debug-info">
        <h4>Confirmation Modal Debug Info</h4>
        <ul>
            <li>Container Z-Index: 10004</li>
            <li>Content Z-Index: 10005</li>
            <li>Backdrop Z-Index: 10003</li>
            <li>User Type: <span x-text="userType"></span></li>
            <li>Username: <span x-text="username || 'N/A'"></span></li>
            <li>Action Type: <span x-text="actionType || 'N/A'"></span></li>
            <li>File Count: <span x-text="fileCount || 'N/A'"></span></li>
            <li>Processing: <span x-text="processing"></span></li>
        </ul>
        <button x-on:click="logModalState()">Log Modal State</button>
        <button x-on:click="toggleDebugMode()">Hide Debug</button>
    </div>
    @endif
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('fileConfirmationModal', (userType = 'admin', username = null) => ({
        open: false,
        userType: userType,
        username: username,
        debugMode: false,
        processing: false,

        // Modal content
        title: '',
        message: '',
        details: '',
        warning: '',
        confirmText: '{{ __("messages.confirm") }}',
        processingText: '{{ __("messages.processing") }}',

        // Action configuration
        actionType: '',
        actionData: null,
        onConfirm: null,
        fileCount: 0,

        // Icon configuration
        iconType: 'warning', // warning, danger, info, success
        
        init() {
            // Initialize debug mode from localStorage or URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const debugParam = urlParams.get('modal-debug');
            const debugStorage = localStorage.getItem('modal-debug');
            this.debugMode = debugParam === 'true' || debugStorage === 'true';
        },

        get iconClasses() {
            const baseClasses = 'mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10';
            switch (this.iconType) {
                case 'danger':
                    return baseClasses + ' bg-red-100';
                case 'warning':
                    return baseClasses + ' bg-yellow-100';
                case 'info':
                    return baseClasses + ' bg-blue-100';
                case 'success':
                    return baseClasses + ' bg-green-100';
                default:
                    return baseClasses + ' bg-yellow-100';
            }
        },

        get iconTextClasses() {
            switch (this.iconType) {
                case 'danger':
                    return 'text-red-600';
                case 'warning':
                    return 'text-yellow-600';
                case 'info':
                    return 'text-blue-600';
                case 'success':
                    return 'text-green-600';
                default:
                    return 'text-yellow-600';
            }
        },

        get confirmButtonClasses() {
            const baseClasses = 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed';
            switch (this.actionType) {
                case 'delete':
                case 'bulk-delete':
                    return baseClasses + ' bg-red-600 text-white hover:bg-red-700 focus:ring-red-500';
                case 'warning':
                    return baseClasses + ' bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500';
                default:
                    return baseClasses + ' bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500';
            }
        },

        openModal(config) {
            console.log('🔍 Modal: openModal called with config:', config);
            
            // Reset state
            this.processing = false;
            
            // Set configuration
            this.title = config.title || '{{ __("messages.confirm_action") }}';
            this.message = config.message || '{{ __("messages.are_you_sure") }}';
            this.details = config.details || '';
            this.warning = config.warning || '';
            this.confirmText = config.confirmText || '{{ __("messages.confirm") }}';
            this.processingText = config.processingText || '{{ __("messages.processing") }}';
            this.actionType = config.actionType || '';
            this.actionData = config.actionData || null;
            this.onConfirm = config.onConfirm || null;
            this.fileCount = config.fileCount || 0;
            this.iconType = config.iconType || 'warning';

            console.log('🔍 Modal: Setting open to true');
            this.open = true;
            console.log('🔍 Modal: Modal opened, open is now:', this.open);
        },

        closeModal() {
            console.log('🔍 Modal: closeModal called, processing:', this.processing);
            
            if (this.processing) {
                console.log('🔍 Modal: Cannot close modal while processing');
                return;
            }

            console.log('🔍 Modal: Setting open to false');
            this.open = false;
            this.resetState();
            console.log('🔍 Modal: Modal state reset, open is now:', this.open);
        },

        resetState() {
            this.title = '';
            this.message = '';
            this.details = '';
            this.warning = '';
            this.confirmText = '{{ __("messages.confirm") }}';
            this.processingText = '{{ __("messages.processing") }}';
            this.actionType = '';
            this.actionData = null;
            this.onConfirm = null;
            this.fileCount = 0;
            this.iconType = 'warning';
            this.processing = false;
        },

        async confirmAction() {
            if (this.processing) {
                console.warn('Confirmation already in progress, ignoring duplicate call');
                return;
            }

            this.processing = true;
            console.log('🔍 Modal: Starting confirmation action');

            try {
                if (this.onConfirm && typeof this.onConfirm === 'function') {
                    console.log('🔍 Modal: Calling onConfirm callback');
                    await this.onConfirm(this.actionData);
                    console.log('🔍 Modal: onConfirm callback completed successfully');
                } else {
                    console.log('🔍 Modal: No onConfirm callback, dispatching event');
                    // Dispatch event for parent component to handle
                    this.$dispatch('confirmation-confirmed', {
                        actionType: this.actionType,
                        actionData: this.actionData
                    });
                }

                // Close modal after successful action
                console.log('🔍 Modal: Closing modal after successful action');
                this.closeModal();
                console.log('🔍 Modal: Modal closed successfully');

            } catch (error) {
                console.error('🔍 Modal: Confirmation action failed:', error);
                
                // Show error notification
                this.$dispatch('show-error-notification', {
                    message: error.message || '{{ __("messages.action_failed") }}'
                });

            } finally {
                this.processing = false;
                console.log('🔍 Modal: Processing flag reset to false');
            }
        },

        // Debug mode methods
        toggleDebugMode() {
            this.debugMode = !this.debugMode;
            localStorage.setItem('modal-debug', this.debugMode.toString());
            
            if (this.debugMode) {
                console.log('🔍 Confirmation modal debug mode enabled');
                this.logModalState();
            } else {
                console.log('🔍 Confirmation modal debug mode disabled');
            }
        },

        logModalState() {
            console.group('🔍 Confirmation Modal State');
            console.log('Open:', this.open);
            console.log('User Type:', this.userType);
            console.log('Username:', this.username);
            console.log('Title:', this.title);
            console.log('Action Type:', this.actionType);
            console.log('File Count:', this.fileCount);
            console.log('Processing:', this.processing);
            console.log('Debug Mode:', this.debugMode);
            console.groupEnd();
        },

        // Handle background click with debug logging
        handleBackgroundClick(event) {
            if (this.processing) {
                if (this.debugMode) {
                    console.log('🔍 Background clicked but modal is processing, ignoring');
                }
                return;
            }

            if (this.debugMode) {
                console.log('🔍 Background clicked, closing modal');
            }
            this.closeModal();
        }
    }));
});
</script>