<!-- Enhanced Preview Modal -->
<div 
    x-data="filePreviewModal"
    x-on:open-preview-modal.window="openModal($event.detail)"
    x-show="open"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
    x-on:keydown.escape="closeModal()"
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
            class="fixed inset-0 bg-black/75 transition-opacity"
            x-on:click="closeModal()"
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
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full max-h-screen"
        >
            <!-- Header -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            {{ __('messages.file_preview') }}
                        </h3>
                        <div x-show="file" class="mt-2">
                            <p class="text-sm text-gray-500 truncate" x-text="file?.original_filename" :title="file?.original_filename"></p>
                            <div class="flex items-center space-x-4 mt-1 text-xs text-gray-400">
                                <span x-text="formatBytes(file?.file_size || 0)"></span>
                                <span x-text="formatDate(file?.created_at)"></span>
                                <span x-text="file?.email"></span>
                                <span x-show="previewType" x-text="previewType.toUpperCase()"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview Controls -->
                    <div class="flex items-center space-x-2 ml-4">
                        <!-- Image Controls -->
                        <template x-if="previewType === 'image'">
                            <div class="flex items-center space-x-2">
                                <button 
                                    x-on:click="zoomOut()"
                                    class="p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
                                    title="Zoom Out"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"></path>
                                    </svg>
                                </button>
                                <span class="text-xs text-gray-500" x-text="Math.round(imageZoom * 100) + '%'"></span>
                                <button 
                                    x-on:click="zoomIn()"
                                    class="p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
                                    title="Zoom In"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                    </svg>
                                </button>
                                <button 
                                    x-on:click="resetImageView()"
                                    class="p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
                                    title="Reset View"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>
                        
                        <!-- PDF Preview Info -->
                        <template x-if="previewType === 'pdf'">
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-gray-500">PDF Preview</span>
                                <div class="text-xs text-gray-400">
                                    Use browser controls for navigation
                                </div>
                            </div>
                        </template>
                        
                        <!-- Close Button -->
                        <button 
                            x-on:click="closeModal()"
                            class="p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
                            title="Close"
                        >
                            <span class="sr-only">{{ __('messages.close') }}</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="bg-white flex-1 overflow-hidden" style="max-height: calc(100vh - 200px);">
                <!-- Loading state -->
                <div x-show="loading" class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-2 text-sm text-gray-600">{{ __('messages.loading_preview') }}</span>
                </div>

                <!-- Image Preview -->
                <div x-show="!loading && previewType === 'image'" class="h-full overflow-hidden relative bg-gray-100">
                    <div 
                        class="h-full overflow-auto cursor-move"
                        x-on:mousedown="startImageDrag($event)"
                        x-on:mousemove="dragImage($event)"
                        x-on:mouseup="endImageDrag()"
                        x-on:mouseleave="endImageDrag()"
                        x-on:wheel.prevent="wheelZoom($event)"
                    >
                        <img 
                            x-show="previewContent"
                            :src="previewContent"
                            :alt="file?.original_filename"
                            class="block mx-auto transition-transform duration-200"
                            :style="`transform: scale(${imageZoom}) translate(${imagePanX}px, ${imagePanY}px); transform-origin: center center;`"
                            x-on:load="imageLoaded()"
                            x-on:error="imageError()"
                            draggable="false"
                        >
                    </div>
                </div>

                <!-- PDF Preview -->
                <div x-show="!loading && previewType === 'pdf'" class="h-full">
                    <div class="h-full bg-gray-100">
                        <embed 
                            x-show="previewContent"
                            :src="previewContent" 
                            type="application/pdf" 
                            class="w-full h-full border-0"
                            style="min-height: 600px;"
                        >
                    </div>
                </div>

                <!-- Text Preview -->
                <div x-show="!loading && previewType === 'text'" class="h-full overflow-auto p-6">
                    <pre x-show="previewContent" class="text-sm font-mono whitespace-pre-wrap break-words bg-gray-50 p-4 rounded border" x-html="previewContent"></pre>
                </div>

                <!-- Code Preview -->
                <div x-show="!loading && previewType === 'code'" class="h-full overflow-auto">
                    <div x-show="previewContent" class="h-full" x-html="previewContent"></div>
                </div>

                <!-- Unsupported Preview -->
                <div x-show="!loading && previewType === 'unsupported'" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">{{ __('messages.preview_not_available') }}</h3>
                        <p class="mt-2 text-sm text-gray-500">{{ __('messages.preview_not_available_description') }}</p>
                        <div x-show="file" class="mt-4 p-4 bg-gray-50 rounded-lg">
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('messages.filename') }}</dt>
                                    <dd class="text-sm text-gray-900" x-text="file?.original_filename"></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('messages.size') }}</dt>
                                    <dd class="text-sm text-gray-900" x-text="formatBytes(file?.file_size || 0)"></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('messages.uploaded_by') }}</dt>
                                    <dd class="text-sm text-gray-900" x-text="file?.email"></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('messages.uploaded_at') }}</dt>
                                    <dd class="text-sm text-gray-900" x-text="formatDate(file?.created_at)"></dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Error state -->
                <div x-show="!loading && error" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('messages.preview_error') }}</h3>
                        <p class="mt-1 text-sm text-gray-500" x-text="error"></p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200">
                <button 
                    x-on:click="downloadFile()"
                    type="button" 
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{ __('messages.download') }}
                </button>
                <button 
                    x-on:click="closeModal()"
                    type="button" 
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                >
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('filePreviewModal', () => ({
        open: false,
        file: null,
        previewContent: '',
        previewType: '',
        loading: false,
        error: null,
        
        // Image viewer state
        imageZoom: 1,
        imagePanX: 0,
        imagePanY: 0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,
        
        openModal(file) {
            this.file = file;
            this.open = true;
            this.resetState();
            this.loadPreview();
        },
        
        closeModal() {
            this.open = false;
            this.resetState();
        },
        
        resetState() {
            // Clean up blob URLs to prevent memory leaks
            if (this.previewContent && this.previewContent.startsWith('blob:')) {
                URL.revokeObjectURL(this.previewContent);
            }
            
            this.previewContent = '';
            this.previewType = '';
            this.loading = false;
            this.error = null;
            this.resetImageView();
        },
        
        async loadPreview() {
            if (!this.file) return;
            
            this.loading = true;
            this.error = null;
            
            try {
                const response = await fetch(`/admin/file-manager/${this.file.id}/preview`);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const contentType = response.headers.get('content-type') || '';
                this.previewType = this.determinePreviewType(this.file.original_filename, contentType);
                
                if (this.previewType === 'image') {
                    const blob = await response.blob();
                    this.previewContent = URL.createObjectURL(blob);
                } else if (this.previewType === 'pdf') {
                    // Use native browser PDF preview for better compatibility
                    const blob = await response.blob();
                    this.previewContent = URL.createObjectURL(blob);
                } else if (this.previewType === 'text' || this.previewType === 'code') {
                    const text = await response.text();
                    this.previewContent = this.previewType === 'code' ? this.highlightCode(text, this.file.original_filename) : this.escapeHtml(text);
                } else {
                    this.previewType = 'unsupported';
                }
                
            } catch (error) {
                console.error('Preview error:', error);
                this.error = error.message;
                this.previewType = 'unsupported';
            } finally {
                this.loading = false;
            }
        },
        
        determinePreviewType(filename, contentType) {
            const ext = filename.toLowerCase().split('.').pop();
            
            // Image types
            if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(ext) || contentType.startsWith('image/')) {
                return 'image';
            }
            
            // PDF
            if (ext === 'pdf' || contentType === 'application/pdf') {
                return 'pdf';
            }
            
            // Code files
            if (['js', 'ts', 'jsx', 'tsx', 'php', 'py', 'rb', 'java', 'cpp', 'c', 'h', 'css', 'scss', 'sass', 'less', 'html', 'xml', 'json', 'yaml', 'yml', 'sql', 'sh', 'bash'].includes(ext)) {
                return 'code';
            }
            
            // Text files
            if (['txt', 'md', 'csv', 'log', 'ini', 'conf', 'config'].includes(ext) || contentType.startsWith('text/')) {
                return 'text';
            }
            
            return 'unsupported';
        },
        
        // Image viewer methods
        zoomIn() {
            this.imageZoom = Math.min(this.imageZoom * 1.2, 5);
        },
        
        zoomOut() {
            this.imageZoom = Math.max(this.imageZoom / 1.2, 0.1);
        },
        
        resetImageView() {
            this.imageZoom = 1;
            this.imagePanX = 0;
            this.imagePanY = 0;
        },
        
        wheelZoom(event) {
            event.preventDefault();
            const delta = event.deltaY > 0 ? 0.9 : 1.1;
            const newZoom = Math.max(0.1, Math.min(5, this.imageZoom * delta));
            
            // Calculate zoom center point relative to the image
            const rect = event.currentTarget.getBoundingClientRect();
            const centerX = (event.clientX - rect.left - rect.width / 2) / this.imageZoom;
            const centerY = (event.clientY - rect.top - rect.height / 2) / this.imageZoom;
            
            // Adjust pan to keep the zoom centered on cursor
            this.imagePanX -= centerX * (newZoom - this.imageZoom);
            this.imagePanY -= centerY * (newZoom - this.imageZoom);
            
            this.imageZoom = newZoom;
        },
        
        startImageDrag(event) {
            if (this.imageZoom <= 1) return;
            event.preventDefault();
            this.isDragging = true;
            this.dragStartX = event.clientX - this.imagePanX;
            this.dragStartY = event.clientY - this.imagePanY;
            event.currentTarget.style.cursor = 'grabbing';
        },
        
        dragImage(event) {
            if (!this.isDragging) return;
            event.preventDefault();
            this.imagePanX = event.clientX - this.dragStartX;
            this.imagePanY = event.clientY - this.dragStartY;
        },
        
        endImageDrag() {
            if (this.isDragging) {
                this.isDragging = false;
                // Reset cursor
                const container = document.querySelector('.cursor-move');
                if (container) {
                    container.style.cursor = this.imageZoom > 1 ? 'grab' : 'default';
                }
            }
        },
        
        imageLoaded() {
            // Image loaded successfully
        },
        
        imageError() {
            this.error = 'Failed to load image';
        },
        
        // Download file
        downloadFile() {
            if (this.file) {
                window.location.href = `/admin/file-manager/${this.file.id}/download`;
            }
        },
        
        // Utility methods
        formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString();
        },
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        highlightCode(code, filename) {
            // Simple syntax highlighting - you can enhance this
            return this.escapeHtml(code);
        }
    }));
});
</script>