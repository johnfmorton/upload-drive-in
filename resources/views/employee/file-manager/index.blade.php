<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('File Manager') }}
        </h2>
    </x-slot>

    <div class="py-6" style="min-height: auto;">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- File Management Dashboard -->
            <div class="bg-white shadow sm:rounded-lg">
                <div x-data="employeeFileManager({{ json_encode($files->items()) }})" class="file-manager">
                    <!-- Header Section -->
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    Uploaded Files
                                </h3>
                                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                    Manage uploaded files with bulk operations, preview, and download capabilities.
                                </p>
                            </div>

                            <!-- Statistics -->
                            <div class="flex flex-wrap gap-4 text-sm">
                                <div class="flex items-center space-x-2">
                                    <span class="text-gray-500">Total Files:</span>
                                    <span class="font-medium" x-text="files.length"></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-gray-500">Pending:</span>
                                    <span class="font-medium text-yellow-600" x-text="files.filter(f => !f.google_drive_file_id).length"></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-gray-500">Total Size:</span>
                                    <span class="font-medium" x-text="formatBytes(files.reduce((sum, f) => sum + (f.file_size || 0), 0))"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Toolbar -->
                    <div class="px-4 py-4 sm:px-6 border-b border-gray-200 bg-gray-50">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <!-- Bulk Actions -->
                            <div class="flex items-center space-x-4">
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Select All</span>
                                </label>

                                <div x-show="selectedFiles.length > 0" class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-600" x-text="`${selectedFiles.length} selected`"></span>

                                    <button x-on:click="bulkDelete()"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                        Delete Selected
                                    </button>

                                    <button x-on:click="bulkDownload()"
                                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        Download Selected
                                    </button>
                                </div>
                            </div>

                            <!-- Search and Filters -->
                            <div class="flex flex-col lg:flex-row gap-3">
                                <!-- Search Input -->
                                <div class="relative">
                                    <input type="text" x-model.debounce.500ms="searchQuery"
                                        placeholder="Search files, users, or messages..."
                                        class="block w-full sm:w-64 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm pl-10 pr-10">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <div x-show="searchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <button x-on:click="searchQuery = ''" class="text-gray-400 hover:text-gray-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Quick Filters -->
                                <div class="flex flex-wrap gap-2">
                                    <!-- Status Filter -->
                                    <select x-model="statusFilter"
                                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">All Statuses</option>
                                        <option value="uploaded">Uploaded</option>
                                        <option value="pending">Processing</option>
                                    </select>

                                    <!-- File Type Filter -->
                                    <select x-model="fileTypeFilter"
                                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">All File Types</option>
                                        <option value="image">Images</option>
                                        <option value="document">Documents</option>
                                        <option value="video">Videos</option>
                                        <option value="audio">Audio</option>
                                        <option value="archive">Archives</option>
                                        <option value="other">Other</option>
                                    </select>

                                    <!-- View Mode Toggle -->
                                    <button x-on:click="toggleViewMode()"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg x-show="viewMode === 'grid'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                        </svg>
                                        <svg x-show="viewMode === 'table'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Success/Error Messages -->
                    @if(session('success'))
                        <div class="mx-4 mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mx-4 mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- File Grid View -->
                    <div x-show="viewMode === 'grid' && filteredFiles.length > 0"
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 p-4 sm:p-6">
                        <template x-for="(file, index) in filteredFiles" :key="file.id">
                            <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200 hover:shadow-md transition-shadow duration-200">
                                <!-- File Selection Checkbox -->
                                <div class="p-3 border-b border-gray-100">
                                    <label class="flex items-center space-x-2">
                                        <input type="checkbox" :value="file.id" x-model="selectedFiles"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                        <span class="text-xs text-gray-500">Select</span>
                                    </label>
                                </div>

                                <!-- File Preview -->
                                <div class="aspect-w-16 aspect-h-9 bg-gray-50">
                                    <div class="flex items-center justify-center p-4">
                                        <template x-if="file.mime_type && file.mime_type.startsWith('image/')">
                                            <img :src="getThumbnailUrl(file)" :alt="file.original_filename"
                                                class="max-w-full max-h-full object-contain rounded"
                                                x-on:click="previewFile(file)" style="cursor: pointer;">
                                        </template>
                                        <template x-if="!file.mime_type || !file.mime_type.startsWith('image/')">
                                            <div class="text-center">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                    </path>
                                                </svg>
                                                <p class="mt-2 text-xs text-gray-500" x-text="getFileExtension(file.original_filename)"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- File Info -->
                                <div class="p-4">
                                    <h3 class="text-sm font-medium text-gray-900 truncate mb-2"
                                        :title="file.original_filename" x-text="file.original_filename"></h3>

                                    <div class="space-y-1 text-xs text-gray-500">
                                        <div class="flex justify-between">
                                            <span>Size:</span>
                                            <span x-text="formatBytes(file.file_size)"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>From:</span>
                                            <span class="truncate ml-1" x-text="file.email"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Date:</span>
                                            <span x-text="formatDate(file.created_at)"></span>
                                        </div>
                                    </div>

                                    <!-- Status Badge -->
                                    <div class="mt-3">
                                        <span x-show="file.google_drive_file_id"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Uploaded
                                        </span>
                                        <span x-show="!file.google_drive_file_id"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Processing
                                        </span>
                                    </div>
                                </div>

                                <!-- File Actions -->
                                <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 rounded-b-lg">
                                    <div class="flex justify-between items-center">
                                        <div class="flex space-x-2">
                                            <button x-on:click="previewFile(file)"
                                                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                                Preview
                                            </button>
                                            <button x-on:click="downloadFile(file)"
                                                class="text-xs text-green-600 hover:text-green-800 font-medium">
                                                Download
                                            </button>
                                        </div>
                                        <button x-on:click="deleteFile(file)"
                                            class="text-xs text-red-600 hover:text-red-800 font-medium">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Table View -->
                    <div x-show="viewMode === 'table' && filteredFiles.length > 0" class="overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="w-12 px-6 py-3">
                                            <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            File
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            From
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Size
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="file in filteredFiles" :key="file.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox" :value="file.id" x-model="selectedFiles"
                                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <img class="h-10 w-10 rounded" :src="getThumbnailUrl(file)" alt="File thumbnail">
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <a :href="getShowUrl(file)" class="text-blue-600 hover:text-blue-900" x-text="file.original_filename"></a>
                                                        </div>
                                                        <div x-show="file.message" class="text-sm text-gray-500" x-text="truncateText(file.message, 50)"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="file.email"></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatBytes(file.file_size)"></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span x-show="file.google_drive_file_id"
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Uploaded to Drive
                                                </span>
                                                <span x-show="!file.google_drive_file_id"
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Processing
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(file.created_at)"></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <button x-on:click="previewFile(file)" class="text-blue-600 hover:text-blue-900">Preview</button>
                                                    <button x-on:click="downloadFile(file)" class="text-green-600 hover:text-green-900">Download</button>
                                                    <button x-on:click="deleteFile(file)" class="text-red-600 hover:text-red-900">Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div x-show="filteredFiles.length === 0" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No files found</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            <span x-show="searchQuery || statusFilter || fileTypeFilter">
                                Try adjusting your search criteria.
                            </span>
                            <span x-show="!searchQuery && !statusFilter && !fileTypeFilter">
                                Files uploaded to you will appear here.
                            </span>
                        </p>
                    </div>

                    <!-- Pagination -->
                    <div class="px-4 py-3 border-t border-gray-200 sm:px-6">
                        {{ $files->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('employeeFileManager', (initialFiles) => ({
                // Data
                files: initialFiles || [],
                selectedFiles: [],
                selectAll: false,
                searchQuery: '',
                statusFilter: '',
                fileTypeFilter: '',
                viewMode: localStorage.getItem('employeeFileManagerViewMode') || 'grid',

                // Computed
                get filteredFiles() {
                    let filtered = this.files;

                    // Search filter
                    if (this.searchQuery) {
                        const query = this.searchQuery.toLowerCase();
                        filtered = filtered.filter(file => 
                            file.original_filename.toLowerCase().includes(query) ||
                            file.email.toLowerCase().includes(query) ||
                            (file.message && file.message.toLowerCase().includes(query))
                        );
                    }

                    // Status filter
                    if (this.statusFilter) {
                        if (this.statusFilter === 'uploaded') {
                            filtered = filtered.filter(file => file.google_drive_file_id);
                        } else if (this.statusFilter === 'pending') {
                            filtered = filtered.filter(file => !file.google_drive_file_id);
                        }
                    }

                    // File type filter
                    if (this.fileTypeFilter) {
                        filtered = filtered.filter(file => {
                            const mimeType = file.mime_type || '';
                            switch (this.fileTypeFilter) {
                                case 'image': return mimeType.startsWith('image/');
                                case 'document': return mimeType.includes('pdf') || mimeType.includes('document') || mimeType.includes('word') || mimeType.includes('text');
                                case 'video': return mimeType.startsWith('video/');
                                case 'audio': return mimeType.startsWith('audio/');
                                case 'archive': return mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('tar');
                                default: return true;
                            }
                        });
                    }

                    return filtered;
                },

                // Methods
                init() {
                    this.$watch('selectedFiles', () => {
                        this.selectAll = this.selectedFiles.length === this.filteredFiles.length && this.filteredFiles.length > 0;
                    });
                },

                toggleSelectAll() {
                    if (this.selectAll) {
                        this.selectedFiles = this.filteredFiles.map(file => file.id);
                    } else {
                        this.selectedFiles = [];
                    }
                },

                toggleViewMode() {
                    this.viewMode = this.viewMode === 'grid' ? 'table' : 'grid';
                    localStorage.setItem('employeeFileManagerViewMode', this.viewMode);
                },

                previewFile(file) {
                    // Use the global preview route that works for all authenticated users
                    const url = `{{ route('files.preview', ':id') }}`.replace(':id', file.id);
                    window.open(url, '_blank');
                },

                async downloadFile(file) {
                    try {
                        // Use the employee-specific download route
                        const url = `{{ route('employee.file-manager.download', ['username' => auth()->user()->username, 'file' => ':id']) }}`.replace(':id', file.id);
                        
                        // For small files, use fetch + blob approach (most reliable)
                        if (file.file_size < 5 * 1024 * 1024) { // Less than 5MB
                            fetch(url)
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! Status: ${response.status}`);
                                    }
                                    return response.blob();
                                })
                                .then(blob => {
                                    // Create a download link with the blob
                                    const downloadUrl = window.URL.createObjectURL(blob);
                                    const a = document.createElement('a');
                                    a.href = downloadUrl;
                                    a.download = file.original_filename;
                                    a.style.display = 'none';
                                    document.body.appendChild(a);

                                    // Trigger the download
                                    a.click();

                                    // Clean up
                                    window.URL.revokeObjectURL(downloadUrl);
                                    document.body.removeChild(a);
                                })
                                .catch(error => {
                                    console.error('Download failed:', error);
                                    // Fallback to direct navigation
                                    window.location.href = url;
                                });
                        } else {
                            // For larger files, use direct navigation
                            window.location.href = url;
                        }
                    } catch (error) {
                        console.error('Download error:', error);
                        // Fallback to direct navigation
                        const url = `{{ route('employee.file-manager.download', ['username' => auth()->user()->username, 'file' => ':id']) }}`.replace(':id', file.id);
                        window.location.href = url;
                    }
                },

                deleteFile(file) {
                    if (confirm('Are you sure you want to delete this file? This action cannot be undone.')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `{{ route('employee.file-manager.destroy', ['username' => auth()->user()->username, 'file' => ':id']) }}`.replace(':id', file.id);
                        
                        const csrfToken = document.createElement('input');
                        csrfToken.type = 'hidden';
                        csrfToken.name = '_token';
                        csrfToken.value = '{{ csrf_token() }}';
                        form.appendChild(csrfToken);
                        
                        const methodField = document.createElement('input');
                        methodField.type = 'hidden';
                        methodField.name = '_method';
                        methodField.value = 'DELETE';
                        form.appendChild(methodField);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                },

                bulkDelete() {
                    if (this.selectedFiles.length === 0) return;
                    
                    if (confirm(`Are you sure you want to delete ${this.selectedFiles.length} selected files? This action cannot be undone.`)) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `{{ route('employee.file-manager.bulk-destroy', ['username' => auth()->user()->username]) }}`;
                        
                        const csrfToken = document.createElement('input');
                        csrfToken.type = 'hidden';
                        csrfToken.name = '_token';
                        csrfToken.value = '{{ csrf_token() }}';
                        form.appendChild(csrfToken);
                        
                        const methodField = document.createElement('input');
                        methodField.type = 'hidden';
                        methodField.name = '_method';
                        methodField.value = 'DELETE';
                        form.appendChild(methodField);
                        
                        const fileIdsField = document.createElement('input');
                        fileIdsField.type = 'hidden';
                        fileIdsField.name = 'file_ids';
                        fileIdsField.value = JSON.stringify(this.selectedFiles);
                        form.appendChild(fileIdsField);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                },

                bulkDownload() {
                    if (this.selectedFiles.length === 0) return;
                    
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `{{ route('employee.file-manager.bulk-download', ['username' => auth()->user()->username]) }}`;
                    
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);
                    
                    const fileIdsField = document.createElement('input');
                    fileIdsField.type = 'hidden';
                    fileIdsField.name = 'file_ids';
                    fileIdsField.value = JSON.stringify(this.selectedFiles);
                    form.appendChild(fileIdsField);
                    
                    document.body.appendChild(form);
                    form.submit();
                },

                // Utility functions
                formatBytes(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                },

                formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                },

                getFileExtension(filename) {
                    return filename.split('.').pop().toUpperCase();
                },

                getThumbnailUrl(file) {
                    // Use the global thumbnail route that works for all authenticated users
                    return `{{ route('files.thumbnail', ':id') }}`.replace(':id', file.id);
                },

                getShowUrl(file) {
                    return `{{ route('employee.file-manager.show', ['username' => auth()->user()->username, 'file' => ':id']) }}`.replace(':id', file.id);
                },

                truncateText(text, length) {
                    if (!text) return '';
                    return text.length > length ? text.substring(0, length) + '...' : text;
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>