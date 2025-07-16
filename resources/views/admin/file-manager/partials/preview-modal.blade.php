<!-- Preview Modal -->
<div 
    x-data="{ 
        open: false, 
        file: null,
        previewContent: '',
        loading: false
    }"
    x-on:open-preview-modal.window="
        file = $event.detail;
        open = true;
        loadPreview();
    "
    x-show="open"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
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
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            @click="open = false"
        ></div>

        <!-- Modal panel -->
        <div 
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"
        >
            <!-- Header -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        {{ __('messages.file_preview') }}
                    </h3>
                    <button 
                        @click="open = false"
                        class="bg-white rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <span class="sr-only">{{ __('messages.close') }}</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div x-show="file" class="mt-2">
                    <p class="text-sm text-gray-500" x-text="file?.original_filename"></p>
                    <div class="flex items-center space-x-4 mt-1 text-xs text-gray-400">
                        <span x-text="formatBytes(file?.file_size || 0)"></span>
                        <span x-text="formatDate(file?.created_at)"></span>
                        <span x-text="file?.email"></span>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <!-- Loading state -->
                <div x-show="loading" class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-2 text-sm text-gray-600">{{ __('messages.loading_preview') }}</span>
                </div>

                <!-- Preview content -->
                <div x-show="!loading && previewContent" class="max-h-96 overflow-auto">
                    <div x-html="previewContent"></div>
                </div>

                <!-- Error state -->
                <div x-show="!loading && !previewContent" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('messages.preview_not_available') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('messages.preview_not_available_description') }}</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button 
                    @click="downloadFile()"
                    type="button" 
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                >
                    {{ __('messages.download') }}
                </button>
                <button 
                    @click="open = false"
                    type="button" 
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                >
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>

    <script>
        function loadPreview() {
            if (!this.file) return;
            
            this.loading = true;
            this.previewContent = '';
            
            fetch(`/admin/file-manager/${this.file.id}/preview`)
                .then(response => {
                    if (response.ok) {
                        return response.text();
                    }
                    throw new Error('Preview not available');
                })
                .then(content => {
                    this.previewContent = content;
                })
                .catch(error => {
                    console.error('Preview error:', error);
                    this.previewContent = '';
                })
                .finally(() => {
                    this.loading = false;
                });
        }
        
        function downloadFile() {
            if (this.file) {
                window.location.href = `/admin/file-manager/${this.file.id}/download`;
            }
        }
    </script>
</div>