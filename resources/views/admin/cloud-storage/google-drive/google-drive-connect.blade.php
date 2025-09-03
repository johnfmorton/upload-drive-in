{{-- Google Drive Connect Button with Enhanced Validation --}}
<div x-data="googleDriveConnectHandler()" x-cloak>
    <form @submit="handleConnect" action="{{ route('admin.cloud-storage.google-drive.connect') }}" method="POST" class="space-y-4">
        @csrf
        
        {{-- Connection Status Display --}}
        <div x-show="statusMessage" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             class="p-4 rounded-md"
             :class="{
                 'bg-blue-50 border border-blue-200 text-blue-800': statusType === 'info',
                 'bg-green-50 border border-green-200 text-green-800': statusType === 'success',
                 'bg-yellow-50 border border-yellow-200 text-yellow-800': statusType === 'warning',
                 'bg-red-50 border border-red-200 text-red-800': statusType === 'error'
             }">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <template x-if="statusType === 'info'">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template x-if="statusType === 'success'">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template x-if="statusType === 'warning'">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template x-if="statusType === 'error'">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </template>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium" x-text="statusMessage"></p>
                    <template x-if="statusInstructions && statusInstructions.length > 0">
                        <div class="mt-2">
                            <p class="text-sm font-medium mb-1">What to do:</p>
                            <ul class="text-sm list-disc list-inside space-y-1">
                                <template x-for="instruction in statusInstructions" :key="instruction">
                                    <li x-text="instruction"></li>
                                </template>
                            </ul>
                        </div>
                    </template>
                    <template x-if="technicalDetails && showTechnicalDetails">
                        <details class="mt-2">
                            <summary class="text-sm font-medium cursor-pointer hover:text-opacity-80">Technical Details</summary>
                            <p class="text-xs mt-1 font-mono bg-gray-100 p-2 rounded" x-text="technicalDetails"></p>
                        </details>
                    </template>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button @click="clearStatus" type="button" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Progress Indicator --}}
        <div x-show="isConnecting" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="bg-blue-50 border border-blue-200 rounded-md p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-800" x-text="progressMessage"></p>
                    <div class="mt-2 w-full bg-blue-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-500 ease-out" 
                             :style="`width: ${progressPercent}%`"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Connect Button --}}
        <div class="flex justify-end">
            <button type="submit" 
                    :disabled="isConnecting || isValidating"
                    :class="{
                        'opacity-50 cursor-not-allowed': isConnecting || isValidating,
                        'hover:bg-blue-700 focus:ring-blue-500': !isConnecting && !isValidating
                    }"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150">
                <template x-if="!isConnecting && !isValidating">
                    <span>{{ __('messages.connect') }}</span>
                </template>
                <template x-if="isValidating">
                    <span class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Validating...
                    </span>
                </template>
                <template x-if="isConnecting">
                    <span class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Connecting...
                    </span>
                </template>
            </button>
        </div>

        {{-- Retry Button (shown when retryable error occurs) --}}
        <div x-show="showRetryButton" class="flex justify-end mt-2">
            <button @click="retryConnection" type="button"
                    :disabled="isConnecting || isValidating"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Retry Connection
            </button>
        </div>
    </form>
</div>

<script>
function googleDriveConnectHandler() {
    return {
        isConnecting: false,
        isValidating: false,
        progressPercent: 0,
        progressMessage: '',
        statusMessage: '',
        statusType: '', // 'info', 'success', 'warning', 'error'
        statusInstructions: [],
        technicalDetails: '',
        showTechnicalDetails: {{ Auth::user()->role === 'admin' ? 'true' : 'false' }},
        showRetryButton: false,
        retryCount: 0,
        maxRetries: 3,

        async handleConnect(event) {
            event.preventDefault();
            
            if (this.isConnecting || this.isValidating) {
                return;
            }

            this.clearStatus();
            this.isConnecting = true;
            this.progressPercent = 25;
            this.progressMessage = 'Connecting to Google Drive...';

            // Show progress for a moment, then submit the form normally
            setTimeout(() => {
                this.progressPercent = 75;
                this.progressMessage = 'Redirecting to Google Drive...';
                
                // Submit the form normally (not via AJAX) to handle the redirect
                const form = event.target;
                form.action = '{{ route("admin.cloud-storage.google-drive.connect") }}';
                form.method = 'POST';
                form.submit();
            }, 500);
        },

        handleConnectionError(errorData) {
            this.isConnecting = false;
            this.isValidating = false;
            this.progressPercent = 0;
            this.progressMessage = '';

            this.statusType = 'error';
            this.statusMessage = errorData.error || 'Connection failed. Please try again.';
            this.statusInstructions = errorData.instructions || [];
            this.technicalDetails = errorData.technical_details || '';
            
            // Show retry button for retryable errors
            this.showRetryButton = errorData.is_retryable && this.retryCount < this.maxRetries;

            // Auto-retry for certain transient errors
            if (errorData.is_retryable && !errorData.requires_user_action && this.retryCount < this.maxRetries) {
                const retryDelay = errorData.retry_after ? errorData.retry_after * 1000 : 5000;
                
                this.statusType = 'warning';
                this.statusMessage = `Connection failed. Retrying in ${Math.ceil(retryDelay / 1000)} seconds... (Attempt ${this.retryCount + 1}/${this.maxRetries})`;
                
                setTimeout(() => {
                    this.retryConnection();
                }, retryDelay);
            }
        },

        async retryConnection() {
            this.retryCount++;
            this.showRetryButton = false;
            
            // Simulate form submission for retry
            const form = this.$el.querySelector('form');
            const event = new Event('submit', { cancelable: true });
            await this.handleConnect(event);
        },

        clearStatus() {
            this.statusMessage = '';
            this.statusType = '';
            this.statusInstructions = [];
            this.technicalDetails = '';
            this.showRetryButton = false;
        }
    };
}
</script>
