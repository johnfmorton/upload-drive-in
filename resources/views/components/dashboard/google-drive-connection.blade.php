@props(['user', 'isAdmin' => false])

<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <h2 class="text-lg font-medium text-gray-900 mb-4">
        {{ __('messages.google_drive_connection') }}
    </h2>
    
    @if($user->googleDriveToken)
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

    @if($user->googleDriveToken && $user->upload_url)
        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h3 class="text-sm font-medium text-blue-800 mb-2">{{ __('messages.your_upload_page') }}</h3>
            <div class="flex items-center justify-between">
                <code class="text-sm bg-white px-2 py-1 rounded border flex-1 mr-2 truncate">{{ $user->upload_url }}</code>
                <button onclick="copyUploadUrl('{{ $user->upload_url }}')" class="inline-flex items-center px-3 py-1 border border-blue-300 shadow-sm text-xs font-medium rounded text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 whitespace-nowrap">
                    <span class="copy-text">{{ __('messages.copy_url') }}</span>
                </button>
            </div>
            <p class="text-xs text-blue-600 mt-2">{{ __('messages.share_this_url_with_clients') }}</p>
        </div>
    @endif
</div>

@push('scripts')
<script>
    function copyUploadUrl(url) {
        navigator.clipboard.writeText(url).then(function() {
            // Find the button that was clicked
            const button = event.target.closest('button');
            const copyText = button.querySelector('.copy-text');
            const originalText = copyText.textContent;
            
            copyText.textContent = '{{ __('messages.copied') }}';
            copyText.classList.add('text-green-600');
            
            setTimeout(function() {
                copyText.textContent = originalText;
                copyText.classList.remove('text-green-600');
            }, 2000);
        }).catch(function(err) {
            console.error('Failed to copy text: ', err);
        });
    }
</script>
@endpush