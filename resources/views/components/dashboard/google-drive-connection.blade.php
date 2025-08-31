@props(['user', 'isAdmin' => false])

@php
    // Check if Google Drive app is configured (Client ID and Client Secret are available)
    $clientId = \App\Models\CloudStorageSetting::getEffectiveValue('google-drive', 'client_id');
    $clientSecret = \App\Models\CloudStorageSetting::getEffectiveValue('google-drive', 'client_secret');
    $isGoogleDriveAppConfigured = !empty($clientId) && !empty($clientSecret);
@endphp

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
    <h2 class="text-lg font-medium text-gray-900 mb-4">
        {{ __('messages.google_drive_connection') }}
    </h2>
    
    @if($isGoogleDriveAppConfigured && $user->googleDriveToken && $user->upload_url)
        <div class="my-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h3 class="text-sm font-medium text-blue-800 mb-2">{{ __('messages.your_upload_page') }}</h3>
            <div class="flex items-center justify-between">
                <code class="text-sm bg-white px-2 py-1 rounded border flex-1 mr-2 truncate" 
                      role="textbox" 
                      aria-readonly="true" 
                      aria-label="{{ __('messages.upload_url_label') }}">{{ $user->upload_url }}</code>
                <button @click="copyUploadUrl('{{ $user->upload_url }}')" 
                        @keydown="handleKeydown($event, '{{ $user->upload_url }}')"
                        class="inline-flex items-center px-3 py-1 border border-blue-300 shadow-sm text-xs font-medium rounded text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 whitespace-nowrap"
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
            <p class="text-xs text-blue-600 mt-2">{{ __('messages.share_this_url_with_clients') }}</p>
        </div>
    @endif

    @if(!$isGoogleDriveAppConfigured)
        {{-- Google Drive app not configured --}}
        <div class="flex items-center justify-between p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    @if($isAdmin)
                        <p class="text-sm font-medium text-red-800">{{ __('messages.google_drive_app_not_configured') }}</p>
                        <p class="text-sm text-red-600">{{ __('messages.configure_google_drive_app_first') }}</p>
                    @else
                        <p class="text-sm font-medium text-red-800">{{ __('messages.google_drive_not_configured') }}</p>
                        <p class="text-sm text-red-600">{{ __('messages.contact_admin_to_configure_google_drive') }}</p>
                    @endif
                </div>
            </div>
            @if($isAdmin)
                <a href="{{ route('admin.cloud-storage.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[var(--brand-color)] hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)]">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    {{ __('messages.configure_cloud_storage') }}
                </a>
            @endif
        </div>
    @elseif($user->googleDriveToken)
        {{-- User is connected to Google Drive --}}
        <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <p class="text-sm font-medium text-green-800">{{ __('messages.google_drive_connected') }}</p>
                    <p class="text-sm text-green-600">{{ __('messages.client_uploads_will_go_to_your_drive') }}</p>
                </div>
            </div>
            @if($isAdmin)
                <form action="{{ route('admin.cloud-storage.google-drive.disconnect') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        {{ __('messages.disconnect') }}
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('employee.google-drive.disconnect', ['username' => $user->username]) }}" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        {{ __('messages.disconnect') }}
                    </button>
                </form>
            @endif
        </div>
    @else
        {{-- Google Drive app is configured but user is not connected --}}
        <div class="flex items-center justify-between p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <p class="text-sm font-medium text-yellow-800">{{ __('messages.google_drive_not_connected') }}</p>
                    <p class="text-sm text-yellow-600">{{ __('messages.connect_drive_to_receive_uploads') }}</p>
                </div>
            </div>
            @if($isAdmin)
                <form action="{{ route('admin.cloud-storage.google-drive.connect') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[var(--brand-color)] hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)]">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/>
                        </svg>
                        {{ __('messages.connect_google_drive') }}
                    </button>
                </form>
            @else
                <a href="{{ route('employee.google-drive.connect', ['username' => $user->username]) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-[var(--brand-color)] hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)]">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/>
                    </svg>
                    {{ __('messages.connect_google_drive') }}
                </a>
            @endif
        </div>
    @endif


</div>

