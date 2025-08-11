@props(['user'])

<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Personal Upload Page</h2>
            <p class="mt-1 text-sm text-gray-500">
                Share this URL with clients to allow them to upload files directly to you.
            </p>
        </div>
        <div class="flex items-center space-x-4">
            @if($user->upload_url)
                <div class="flex items-center space-x-2">
                    <input type="text" 
                           value="{{ $user->upload_url }}" 
                           readonly 
                           class="px-3 py-2 text-sm border border-gray-300 rounded-md bg-gray-50 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           style="width: 300px;"
                           id="upload-url-input">
                    <button type="button" 
                            onclick="copyToClipboard('upload-url-input')"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Copy URL
                    </button>
                    <a href="{{ $user->upload_url }}" 
                       target="_blank"
                       class="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Visit Page
                    </a>
                </div>
            @else
                <span class="px-3 py-1 text-sm text-gray-800 bg-gray-100 rounded-full">Not available</span>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    function copyToClipboard(inputId) {
        const input = document.getElementById(inputId);
        input.select();
        input.setSelectionRange(0, 99999); // For mobile devices
        
        try {
            document.execCommand('copy');
            
            // Show feedback
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Copied!';
            button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            button.classList.add('bg-green-600', 'hover:bg-green-700');
            
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove('bg-green-600', 'hover:bg-green-700');
                button.classList.add('bg-blue-600', 'hover:bg-blue-700');
            }, 2000);
        } catch (err) {
            console.error('Failed to copy text: ', err);
        }
    }
</script>
@endpush