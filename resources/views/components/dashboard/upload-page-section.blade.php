@props(['user', 'storageProvider'])

<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg" x-data="{
    copiedUploadUrl: false,
    copyUploadUrl(url) {
        navigator.clipboard.writeText(url)
            .then(() => {
                this.copiedUploadUrl = true;
                // Announce to screen readers
                this.$refs.copyStatus.textContent = '{{ __('messages.copied') }}';
                setTimeout(() => {
                    this.copiedUploadUrl = false;
                    this.$refs.copyStatus.textContent = '';
                }, 2000);
            })
            .catch((error) => {
                console.error('Failed to copy URL to clipboard:', error);
                // Announce error to screen readers
                this.$refs.copyStatus.textContent = '{{ __('messages.copy_failed') }}';
                setTimeout(() => {
                    this.$refs.copyStatus.textContent = '';
                }, 3000);
            });
    },
    handleKeydown(event, url) {
        // Handle Enter and Space key presses for accessibility
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            this.copyUploadUrl(url);
        }
    }
}">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('messages.your_upload_page') }}
        </h2>
        
        @if($storageProvider['requires_user_auth'])
            <!-- Show provider icon for OAuth providers -->
            <div class="flex items-center text-sm text-gray-600">
                @if($storageProvider['provider'] === 'google-drive')
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/>
                    </svg>
                @else
                    <!-- Generic cloud icon for other OAuth providers -->
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path>
                    </svg>
                @endif
                <span>{{ $storageProvider['display_name'] }}</span>
            </div>
        @else
            <!-- Show generic cloud icon for system-level providers -->
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path>
                </svg>
                <span>{{ __('messages.cloud_storage') }}</span>
            </div>
        @endif
    </div>
    
    @if($user->upload_url)
        <!-- Upload URL display with copy button -->
        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <div class="flex items-center justify-between">
                <code class="text-sm bg-white px-2 py-1 rounded border flex-1 mr-2 truncate" 
                      role="textbox" 
                      aria-readonly="true" 
                      aria-label="{{ __('messages.upload_url_label') }}">{{ $user->upload_url }}</code>
                <button @click="copyUploadUrl('{{ $user->upload_url }}')" 
                        @keydown="handleKeydown($event, '{{ $user->upload_url }}')"
                        class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 whitespace-nowrap"
                        :aria-label="copiedUploadUrl ? '{{ __('messages.url_copied_to_clipboard') }}' : '{{ __('messages.copy_url_to_clipboard') }}'"
                        :aria-pressed="copiedUploadUrl"
                        role="button"
                        tabindex="0">
                    <span x-show="!copiedUploadUrl" aria-hidden="true">{{ __('messages.copy_url') }}</span>
                    <span x-show="copiedUploadUrl" class="text-green-600" aria-hidden="true">{{ __('messages.copied') }}</span>
                </button>
            </div>
            <!-- Screen reader announcement area -->
            <div x-ref="copyStatus" 
                 aria-live="polite" 
                 aria-atomic="true" 
                 class="sr-only"></div>
            <p class="text-xs text-gray-600 mt-2">{{ __('messages.share_this_url_with_clients') }}</p>
        </div>
        
        @if(!$storageProvider['requires_user_auth'])
            <!-- System-level storage info message -->
            <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm text-blue-800">
                            {{ __('messages.files_stored_in_organization_storage', ['provider' => $storageProvider['display_name']]) }}
                        </p>
                        <p class="text-xs text-blue-600 mt-1">
                            {{ __('messages.contact_admin_for_storage_questions') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @else
        <!-- No upload URL available -->
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-yellow-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-yellow-800">
                        {{ __('messages.upload_page_not_available') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if(isset($storageProvider['error']) && $storageProvider['error'])
        <!-- Configuration error state -->
        <div class="mt-3 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-red-800">
                        {{ __('messages.storage_configuration_error') }}
                    </p>
                    <p class="text-xs text-red-600 mt-1">
                        {{ __('messages.contact_admin_to_resolve') }}
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
