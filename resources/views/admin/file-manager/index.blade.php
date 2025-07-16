<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.file_management_title') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- File Management Dashboard -->
            <div class="bg-white shadow sm:rounded-lg">
                <div 
                    x-data="fileManager({{ json_encode($files->items()) }}, {{ json_encode($statistics ?? []) }})"
                    x-init="init()"
                    class="file-manager"
                >
                    <!-- Header Section -->
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    {{ __('messages.uploaded_files_title') }}
                                </h3>
                                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                    {{ __('messages.file_management_description') }}
                                </p>
                            </div>
                            
                            <!-- Statistics -->
                            <div class="flex flex-wrap gap-4 text-sm">
                                <div class="flex items-center space-x-2">
                                    <span class="text-gray-500">{{ __('messages.total_files') }}:</span>
                                    <span class="font-medium" x-text="statistics.total || 0"></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-gray-500">{{ __('messages.pending_uploads') }}:</span>
                                    <span class="font-medium text-yellow-600" x-text="statistics.pending || 0"></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-gray-500">{{ __('messages.total_size') }}:</span>
                                    <span class="font-medium" x-text="formatBytes(statistics.total_size || 0)"></span>
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
                                    <input 
                                        type="checkbox" 
                                        x-model="selectAll"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    >
                                    <span class="text-sm text-gray-700">{{ __('messages.select_all') }}</span>
                                </label>
                                
                                <div x-show="selectedFiles.length > 0" class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-600" x-text="`${selectedFiles.length} selected`"></span>
                                    
                                    <button 
                                        @click="bulkDelete()"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        {{ __('messages.delete_selected') }}
                                    </button>
                                    
                                    <button 
                                        @click="bulkDownload()"
                                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        {{ __('messages.download_selected') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Search and Filters -->
                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        x-model.debounce.300ms="searchQuery"
                                        placeholder="{{ __('messages.search_files_placeholder') }}"
                                        class="block w-full sm:w-64 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm pl-10"
                                    >
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                
                                <select 
                                    x-model="statusFilter"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                >
                                    <option value="">{{ __('messages.all_statuses') }}</option>
                                    <option value="uploaded">{{ __('messages.status_uploaded') }}</option>
                                    <option value="pending">{{ __('messages.status_pending') }}</option>
                                </select>
                                
                                <button 
                                    @click="toggleViewMode()"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    <svg x-show="viewMode === 'grid'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                    </svg>
                                    <svg x-show="viewMode === 'table'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- File List Container -->
                    <div class="file-list-container">
                        <!-- Grid View -->
                        <div 
                            x-show="viewMode === 'grid'" 
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 p-4 sm:p-6"
                        >
                            <template x-for="file in filteredFiles" :key="file.id">
                                <div class="file-card bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
                                    <!-- File Selection Checkbox -->
                                    <div class="p-3 border-b border-gray-100">
                                        <label class="flex items-center space-x-2">
                                            <input 
                                                type="checkbox" 
                                                :value="file.id"
                                                x-model="selectedFiles"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                            >
                                            <span class="text-xs text-gray-500">{{ __('messages.select_file') }}</span>
                                        </label>
                                    </div>
                                    
                                    <!-- File Preview -->
                                    <div class="aspect-w-16 aspect-h-9 bg-gray-50">
                                        <div class="flex items-center justify-center p-4">
                                            <template x-if="file.can_preview && file.thumbnail_url">
                                                <img 
                                                    :src="file.thumbnail_url" 
                                                    :alt="file.original_filename"
                                                    class="max-w-full max-h-full object-contain rounded"
                                                    @click="previewFile(file)"
                                                    style="cursor: pointer;"
                                                >
                                            </template>
                                            <template x-if="!file.can_preview || !file.thumbnail_url">
                                                <div class="text-center">
                                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    <p class="mt-2 text-xs text-gray-500" x-text="getFileExtension(file.original_filename)"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    
                                    <!-- File Info -->
                                    <div class="p-4">
                                        <h3 
                                            class="text-sm font-medium text-gray-900 truncate mb-2" 
                                            :title="file.original_filename"
                                            x-text="file.original_filename"
                                        ></h3>
                                        
                                        <div class="space-y-1 text-xs text-gray-500">
                                            <div class="flex justify-between">
                                                <span>{{ __('messages.size') }}:</span>
                                                <span x-text="formatBytes(file.file_size)"></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span>{{ __('messages.uploaded_by') }}:</span>
                                                <span class="truncate ml-1" x-text="file.email"></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span>{{ __('messages.date') }}:</span>
                                                <span x-text="formatDate(file.created_at)"></span>
                                            </div>
                                        </div>
                                        
                                        <!-- Status Badge -->
                                        <div class="mt-3">
                                            <span 
                                                x-show="file.google_drive_file_id" 
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                            >
                                                {{ __('messages.status_uploaded') }}
                                            </span>
                                            <span 
                                                x-show="!file.google_drive_file_id" 
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                                            >
                                                {{ __('messages.status_pending') }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- File Actions -->
                                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 rounded-b-lg">
                                        <div class="flex justify-between items-center">
                                            <div class="flex space-x-2">
                                                <button 
                                                    x-show="file.can_preview"
                                                    @click="previewFile(file)"
                                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                                                >
                                                    {{ __('messages.preview') }}
                                                </button>
                                                <button 
                                                    @click="downloadFile(file)"
                                                    class="text-xs text-green-600 hover:text-green-800 font-medium"
                                                >
                                                    {{ __('messages.download') }}
                                                </button>
                                            </div>
                                            <button 
                                                @click="deleteFile(file)"
                                                class="text-xs text-red-600 hover:text-red-800 font-medium"
                                            >
                                                {{ __('messages.delete') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Table View -->
                        <div x-show="viewMode === 'table'" class="overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="w-12 px-6 py-3">
                                                <input 
                                                    type="checkbox" 
                                                    x-model="selectAll"
                                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                >
                                            </th>
                                            <th 
                                                scope="col" 
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                                @click="sortBy('original_filename')"
                                            >
                                                {{ __('messages.filename') }}
                                                <span x-show="sortColumn === 'original_filename'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                            </th>
                                            <th 
                                                scope="col" 
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                                @click="sortBy('email')"
                                            >
                                                {{ __('messages.uploaded_by') }}
                                                <span x-show="sortColumn === 'email'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                            </th>
                                            <th 
                                                scope="col" 
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                                @click="sortBy('file_size')"
                                            >
                                                {{ __('messages.size') }}
                                                <span x-show="sortColumn === 'file_size'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('messages.status') }}
                                            </th>
                                            <th 
                                                scope="col" 
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                                @click="sortBy('created_at')"
                                            >
                                                {{ __('messages.uploaded_at') }}
                                                <span x-show="sortColumn === 'created_at'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('messages.actions') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <template x-for="file in filteredFiles" :key="file.id">
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <input 
                                                        type="checkbox" 
                                                        :value="file.id"
                                                        x-model="selectedFiles"
                                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                    >
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900">
                                                    <div class="flex items-center space-x-3">
                                                        <div class="flex-shrink-0 w-8 h-8">
                                                            <template x-if="file.can_preview && file.thumbnail_url">
                                                                <img 
                                                                    :src="file.thumbnail_url" 
                                                                    :alt="file.original_filename"
                                                                    class="w-8 h-8 object-cover rounded"
                                                                >
                                                            </template>
                                                            <template x-if="!file.can_preview || !file.thumbnail_url">
                                                                <div class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center">
                                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                                    </svg>
                                                                </div>
                                                            </template>
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <p 
                                                                class="text-sm font-medium text-gray-900 break-words"
                                                                :title="file.original_filename"
                                                                x-text="file.original_filename"
                                                            ></p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500">
                                                    <div class="truncate max-w-48" :title="file.email" x-text="file.email"></div>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap" x-text="formatBytes(file.file_size)"></td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span 
                                                        x-show="file.google_drive_file_id" 
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                                    >
                                                        {{ __('messages.status_uploaded') }}
                                                    </span>
                                                    <span 
                                                        x-show="!file.google_drive_file_id" 
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                                                    >
                                                        {{ __('messages.status_pending') }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap" x-text="formatDate(file.created_at)"></td>
                                                <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                                    <div class="flex items-center space-x-2">
                                                        <button 
                                                            x-show="file.can_preview"
                                                            @click="previewFile(file)"
                                                            class="text-blue-600 hover:text-blue-900"
                                                        >
                                                            {{ __('messages.preview') }}
                                                        </button>
                                                        <button 
                                                            @click="downloadFile(file)"
                                                            class="text-green-600 hover:text-green-900"
                                                        >
                                                            {{ __('messages.download') }}
                                                        </button>
                                                        <button 
                                                            @click="deleteFile(file)"
                                                            class="text-red-600 hover:text-red-900"
                                                        >
                                                            {{ __('messages.delete') }}
                                                        </button>
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
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('messages.no_files_found') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('messages.no_files_description') }}</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="px-4 py-3 sm:px-6 border-t border-gray-200">
                        {{ $files->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    @include('admin.file-manager.partials.preview-modal')
    @include('admin.file-manager.partials.delete-modal')
    @include('admin.file-manager.partials.bulk-delete-modal')

    @push('scripts')
    <script>
        function fileManager(initialFiles, initialStatistics) {
            return {
                // Data
                files: initialFiles || [],
                statistics: initialStatistics || {},
                selectedFiles: [],
                searchQuery: '',
                statusFilter: '',
                sortColumn: 'created_at',
                sortDirection: 'desc',
                viewMode: localStorage.getItem('fileManagerViewMode') || 'grid',
                
                // Computed
                get selectAll() {
                    return this.filteredFiles.length > 0 && this.selectedFiles.length === this.filteredFiles.length;
                },
                
                set selectAll(value) {
                    if (value) {
                        this.selectedFiles = this.filteredFiles.map(file => file.id);
                    } else {
                        this.selectedFiles = [];
                    }
                },
                
                get filteredFiles() {
                    let filtered = [...this.files];
                    
                    // Apply search filter
                    if (this.searchQuery.trim()) {
                        const query = this.searchQuery.toLowerCase();
                        filtered = filtered.filter(file => 
                            file.original_filename.toLowerCase().includes(query) ||
                            file.email.toLowerCase().includes(query) ||
                            (file.message && file.message.toLowerCase().includes(query))
                        );
                    }
                    
                    // Apply status filter
                    if (this.statusFilter) {
                        filtered = filtered.filter(file => {
                            if (this.statusFilter === 'uploaded') {
                                return file.google_drive_file_id;
                            } else if (this.statusFilter === 'pending') {
                                return !file.google_drive_file_id;
                            }
                            return true;
                        });
                    }
                    
                    // Apply sorting
                    filtered.sort((a, b) => {
                        let valA = a[this.sortColumn];
                        let valB = b[this.sortColumn];
                        
                        if (this.sortColumn === 'file_size') {
                            valA = parseInt(valA) || 0;
                            valB = parseInt(valB) || 0;
                        } else if (this.sortColumn === 'created_at') {
                            valA = new Date(valA);
                            valB = new Date(valB);
                        } else if (typeof valA === 'string') {
                            valA = valA.toLowerCase();
                            valB = valB.toLowerCase();
                        }
                        
                        let comparison = 0;
                        if (valA > valB) comparison = 1;
                        else if (valA < valB) comparison = -1;
                        
                        return this.sortDirection === 'asc' ? comparison : -comparison;
                    });
                    
                    return filtered;
                },
                
                // Methods
                init() {
                    // Initialize component
                },
                
                toggleViewMode() {
                    this.viewMode = this.viewMode === 'grid' ? 'table' : 'grid';
                    localStorage.setItem('fileManagerViewMode', this.viewMode);
                },
                
                sortBy(column) {
                    if (this.sortColumn === column) {
                        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sortColumn = column;
                        this.sortDirection = 'asc';
                    }
                },
                
                async previewFile(file) {
                    // Open preview modal
                    this.$dispatch('open-preview-modal', file);
                },
                
                async downloadFile(file) {
                    window.location.href = `/admin/file-manager/${file.id}/download`;
                },
                
                async deleteFile(file) {
                    this.$dispatch('open-delete-modal', file);
                },
                
                async bulkDelete() {
                    if (this.selectedFiles.length === 0) return;
                    this.$dispatch('open-bulk-delete-modal', this.selectedFiles);
                },
                
                async bulkDownload() {
                    if (this.selectedFiles.length === 0) return;
                    
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/admin/file-manager/bulk-download';
                    form.style.display = 'none';
                    
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    form.appendChild(csrfInput);
                    
                    this.selectedFiles.forEach(fileId => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'file_ids[]';
                        input.value = fileId;
                        form.appendChild(input);
                    });
                    
                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                },
                
                // Utility methods
                formatBytes(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                },
                
                formatDate(dateString) {
                    const options = { 
                        year: 'numeric', 
                        month: '2-digit', 
                        day: '2-digit', 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    };
                    return new Date(dateString).toLocaleString(undefined, options);
                },
                
                getFileExtension(filename) {
                    return filename.split('.').pop().toUpperCase();
                }
            }
        }
    </script>
    @endpush
</x-app-layout>