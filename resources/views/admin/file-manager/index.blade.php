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
                            <div class="flex flex-col lg:flex-row gap-3">
                                <!-- Enhanced Search Input -->
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        x-model.debounce.500ms="searchQuery"
                                        placeholder="{{ __('messages.search_files_placeholder') }}"
                                        class="block w-full sm:w-64 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm pl-10 pr-10"
                                    >
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <div x-show="searchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <button 
                                            @click="searchQuery = ''"
                                            class="text-gray-400 hover:text-gray-600"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Quick Filters -->
                                <div class="flex flex-wrap gap-2">
                                    <!-- Status Filter -->
                                    <select 
                                        x-model="statusFilter"
                                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    >
                                        <option value="">{{ __('messages.all_statuses') }}</option>
                                        <option value="uploaded">{{ __('messages.status_uploaded') }}</option>
                                        <option value="pending">{{ __('messages.status_pending') }}</option>
                                    </select>

                                    <!-- File Type Filter -->
                                    <select 
                                        x-model="fileTypeFilter"
                                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    >
                                        <option value="">All File Types</option>
                                        <option value="image">Images</option>
                                        <option value="document">Documents</option>
                                        <option value="video">Videos</option>
                                        <option value="audio">Audio</option>
                                        <option value="archive">Archives</option>
                                        <option value="other">Other</option>
                                    </select>

                                    <!-- Advanced Filters Toggle -->
                                    <button 
                                        @click="showAdvancedFilters = !showAdvancedFilters"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                        :class="{ 'bg-blue-50 border-blue-300 text-blue-700': showAdvancedFilters }"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                                        </svg>
                                        Filters
                                        <span x-show="activeFiltersCount > 0" class="ml-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-blue-600 rounded-full" x-text="activeFiltersCount"></span>
                                    </button>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <!-- Column Visibility Toggle (only show in table mode) -->
                                    <div x-show="viewMode === 'table'" class="relative" x-data="{ open: false }">
                                        <button 
                                            @click="open = !open"
                                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                            </svg>
                                            {{ __('messages.columns') }}
                                        </button>
                                        
                                        <!-- Column visibility dropdown -->
                                        <div 
                                            x-show="open"
                                            @click.away="open = false"
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="transform opacity-0 scale-95"
                                            x-transition:enter-end="transform opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="transform opacity-100 scale-100"
                                            x-transition:leave-end="transform opacity-0 scale-95"
                                            class="absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10"
                                        >
                                            <div class="py-1">
                                                <div class="px-4 py-2 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100">
                                                    {{ __('messages.show_columns') }}
                                                </div>
                                                <template x-for="column in availableColumns" :key="column.key">
                                                    <label class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer">
                                                        <input 
                                                            type="checkbox" 
                                                            :checked="visibleColumns[column.key]"
                                                            @change="toggleColumn(column.key)"
                                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 mr-3"
                                                        >
                                                        <span x-text="column.label"></span>
                                                    </label>
                                                </template>
                                                <div class="border-t border-gray-100 pt-1">
                                                    <button 
                                                        @click="resetColumns()"
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                                    >
                                                        {{ __('messages.reset_columns') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- View Mode Toggle -->
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
                    </div>

                    <!-- Advanced Filters Panel -->
                    <div 
                        x-show="showAdvancedFilters" 
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        class="px-4 py-4 sm:px-6 border-b border-gray-200 bg-gray-50"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Date Range Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                                <div class="space-y-2">
                                    <input 
                                        type="date" 
                                        x-model="dateFromFilter"
                                        placeholder="From date"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    >
                                    <input 
                                        type="date" 
                                        x-model="dateToFilter"
                                        placeholder="To date"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    >
                                </div>
                            </div>

                            <!-- User Email Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Uploaded By</label>
                                <input 
                                    type="email" 
                                    x-model.debounce.300ms="userEmailFilter"
                                    placeholder="Enter email address"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                >
                            </div>

                            <!-- File Size Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">File Size</label>
                                <div class="space-y-2">
                                    <input 
                                        type="text" 
                                        x-model.debounce.300ms="fileSizeMinFilter"
                                        placeholder="Min size (e.g., 1MB)"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    >
                                    <input 
                                        type="text" 
                                        x-model.debounce.300ms="fileSizeMaxFilter"
                                        placeholder="Max size (e.g., 10MB)"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    >
                                </div>
                            </div>

                            <!-- Filter Actions -->
                            <div class="flex flex-col justify-end space-y-2">
                                <button 
                                    @click="clearAllFilters()"
                                    class="inline-flex items-center justify-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Clear All
                                </button>
                                <div class="text-xs text-gray-500 text-center">
                                    <span x-text="filteredFiles.length"></span> of <span x-text="files.length"></span> files
                                </div>
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
                                <table class="min-w-full divide-y divide-gray-200" :style="tableStyles">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <!-- Selection Column (always visible) -->
                                            <th scope="col" class="w-12 px-6 py-3 sticky left-0 bg-gray-50 z-10">
                                                <input 
                                                    type="checkbox" 
                                                    x-model="selectAll"
                                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                >
                                            </th>
                                            
                                            <!-- Dynamic Columns -->
                                            <template x-for="column in visibleColumnsList" :key="column.key">
                                                <th 
                                                    scope="col" 
                                                    :class="getColumnHeaderClass(column)"
                                                    @click="column.sortable ? sortBy(column.key) : null"
                                                    :style="getColumnStyle(column.key)"
                                                >
                                                    <div class="flex items-center justify-between group">
                                                        <span x-text="column.label"></span>
                                                        <div class="flex items-center space-x-1">
                                                            <!-- Sort indicator -->
                                                            <span 
                                                                x-show="column.sortable && sortColumn === column.key" 
                                                                x-text="sortDirection === 'asc' ? '▲' : '▼'"
                                                                class="text-blue-600"
                                                            ></span>
                                                            <!-- Resize handle -->
                                                            <div 
                                                                x-show="column.resizable"
                                                                class="w-1 h-4 bg-gray-300 cursor-col-resize opacity-0 group-hover:opacity-100 transition-opacity"
                                                                @mousedown="startColumnResize($event, column.key)"
                                                            ></div>
                                                        </div>
                                                    </div>
                                                </th>
                                            </template>
                                            
                                            <!-- Actions Column (always visible) -->
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky right-0 bg-gray-50 z-10">
                                                {{ __('messages.actions') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <template x-for="file in filteredFiles" :key="file.id">
                                            <tr class="hover:bg-gray-50">
                                                <!-- Selection Column (always visible) -->
                                                <td class="px-6 py-4 whitespace-nowrap sticky left-0 bg-white z-10">
                                                    <input 
                                                        type="checkbox" 
                                                        :value="file.id"
                                                        x-model="selectedFiles"
                                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                    >
                                                </td>
                                                
                                                <!-- Dynamic Columns -->
                                                <template x-for="column in visibleColumnsList" :key="column.key">
                                                    <td 
                                                        :class="getColumnCellClass(column)"
                                                        :style="getColumnStyle(column.key)"
                                                        x-html="getCellContent(file, column)"
                                                    ></td>
                                                </template>
                                                
                                                <!-- Actions Column (always visible) -->
                                                <td class="px-6 py-4 text-sm font-medium whitespace-nowrap sticky right-0 bg-white z-10">
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
                fileTypeFilter: '',
                dateFromFilter: '',
                dateToFilter: '',
                userEmailFilter: '',
                fileSizeMinFilter: '',
                fileSizeMaxFilter: '',
                showAdvancedFilters: false,
                sortColumn: 'created_at',
                sortDirection: 'desc',
                viewMode: localStorage.getItem('fileManagerViewMode') || 'grid',
                
                // Column management
                availableColumns: [
                    { key: 'original_filename', label: 'Filename', sortable: true, resizable: true, defaultWidth: 300, minWidth: 200 },
                    { key: 'email', label: 'Uploaded By', sortable: true, resizable: true, defaultWidth: 200, minWidth: 150 },
                    { key: 'file_size', label: 'Size', sortable: true, resizable: true, defaultWidth: 120, minWidth: 80 },
                    { key: 'status', label: 'Status', sortable: false, resizable: true, defaultWidth: 120, minWidth: 100 },
                    { key: 'created_at', label: 'Uploaded At', sortable: true, resizable: true, defaultWidth: 180, minWidth: 150 }
                ],
                visibleColumns: this.getStoredColumnVisibility(),
                columnWidths: this.getStoredColumnWidths(),
                
                // Column resizing state
                isResizing: false,
                resizingColumn: null,
                startX: 0,
                startWidth: 0,
                
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
                    
                    // Apply search filter with enhanced multi-term search
                    if (this.searchQuery.trim()) {
                        const searchTerms = this.searchQuery.toLowerCase().split(' ').filter(term => term.length >= 2);
                        filtered = filtered.filter(file => {
                            return searchTerms.every(term => 
                                file.original_filename.toLowerCase().includes(term) ||
                                file.email.toLowerCase().includes(term) ||
                                (file.message && file.message.toLowerCase().includes(term)) ||
                                (file.mime_type && file.mime_type.toLowerCase().includes(term))
                            );
                        });
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

                    // Apply file type filter
                    if (this.fileTypeFilter) {
                        filtered = filtered.filter(file => {
                            const mimeType = file.mime_type || '';
                            switch (this.fileTypeFilter) {
                                case 'image':
                                    return mimeType.startsWith('image/');
                                case 'document':
                                    return mimeType.includes('pdf') || 
                                           mimeType.includes('msword') || 
                                           mimeType.includes('officedocument') ||
                                           mimeType.startsWith('text/');
                                case 'video':
                                    return mimeType.startsWith('video/');
                                case 'audio':
                                    return mimeType.startsWith('audio/');
                                case 'archive':
                                    return mimeType.includes('zip') || 
                                           mimeType.includes('rar') || 
                                           mimeType.includes('7z');
                                case 'other':
                                    return !mimeType.startsWith('image/') &&
                                           !mimeType.startsWith('video/') &&
                                           !mimeType.startsWith('audio/') &&
                                           !mimeType.includes('pdf') &&
                                           !mimeType.includes('msword') &&
                                           !mimeType.includes('officedocument') &&
                                           !mimeType.startsWith('text/') &&
                                           !mimeType.includes('zip') &&
                                           !mimeType.includes('rar') &&
                                           !mimeType.includes('7z');
                                default:
                                    return true;
                            }
                        });
                    }

                    // Apply date range filters
                    if (this.dateFromFilter) {
                        const fromDate = new Date(this.dateFromFilter);
                        filtered = filtered.filter(file => new Date(file.created_at) >= fromDate);
                    }
                    if (this.dateToFilter) {
                        const toDate = new Date(this.dateToFilter + 'T23:59:59');
                        filtered = filtered.filter(file => new Date(file.created_at) <= toDate);
                    }

                    // Apply user email filter
                    if (this.userEmailFilter.trim()) {
                        const emailQuery = this.userEmailFilter.toLowerCase();
                        filtered = filtered.filter(file => 
                            file.email && file.email.toLowerCase().includes(emailQuery)
                        );
                    }

                    // Apply file size filters
                    if (this.fileSizeMinFilter) {
                        const minSize = this.parseFileSize(this.fileSizeMinFilter);
                        if (minSize > 0) {
                            filtered = filtered.filter(file => (file.file_size || 0) >= minSize);
                        }
                    }
                    if (this.fileSizeMaxFilter) {
                        const maxSize = this.parseFileSize(this.fileSizeMaxFilter);
                        if (maxSize > 0) {
                            filtered = filtered.filter(file => (file.file_size || 0) <= maxSize);
                        }
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

                get activeFiltersCount() {
                    let count = 0;
                    if (this.searchQuery.trim()) count++;
                    if (this.statusFilter) count++;
                    if (this.fileTypeFilter) count++;
                    if (this.dateFromFilter) count++;
                    if (this.dateToFilter) count++;
                    if (this.userEmailFilter.trim()) count++;
                    if (this.fileSizeMinFilter.trim()) count++;
                    if (this.fileSizeMaxFilter.trim()) count++;
                    return count;
                },
                
                // Computed properties
                get visibleColumnsList() {
                    return this.availableColumns.filter(column => this.visibleColumns[column.key]);
                },
                
                get tableStyles() {
                    return `table-layout: fixed; width: ${this.getTotalTableWidth()}px;`;
                },
                
                // Methods
                init() {
                    // Initialize component
                    this.setupColumnResizing();
                },
                
                // Column management methods
                getStoredColumnVisibility() {
                    const stored = localStorage.getItem('fileManagerColumnVisibility');
                    const defaults = {
                        original_filename: true,
                        email: true,
                        file_size: true,
                        status: true,
                        created_at: true
                    };
                    return stored ? { ...defaults, ...JSON.parse(stored) } : defaults;
                },
                
                getStoredColumnWidths() {
                    const stored = localStorage.getItem('fileManagerColumnWidths');
                    const defaults = {
                        original_filename: 300,
                        email: 200,
                        file_size: 120,
                        status: 120,
                        created_at: 180
                    };
                    return stored ? { ...defaults, ...JSON.parse(stored) } : defaults;
                },
                
                toggleColumn(columnKey) {
                    this.visibleColumns[columnKey] = !this.visibleColumns[columnKey];
                    this.saveColumnPreferences();
                },
                
                resetColumns() {
                    this.visibleColumns = {
                        original_filename: true,
                        email: true,
                        file_size: true,
                        status: true,
                        created_at: true
                    };
                    this.columnWidths = {
                        original_filename: 300,
                        email: 200,
                        file_size: 120,
                        status: 120,
                        created_at: 180
                    };
                    this.saveColumnPreferences();
                },
                
                saveColumnPreferences() {
                    localStorage.setItem('fileManagerColumnVisibility', JSON.stringify(this.visibleColumns));
                    localStorage.setItem('fileManagerColumnWidths', JSON.stringify(this.columnWidths));
                },
                
                // Column styling methods
                getColumnHeaderClass(column) {
                    let classes = 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
                    if (column.sortable) {
                        classes += ' cursor-pointer hover:bg-gray-100';
                    }
                    return classes;
                },
                
                getColumnCellClass(column) {
                    let classes = 'px-6 py-4 text-sm';
                    if (column.key === 'original_filename') {
                        classes += ' text-gray-900';
                    } else if (column.key === 'status') {
                        classes += ' whitespace-nowrap';
                    } else {
                        classes += ' text-gray-500';
                    }
                    if (column.key === 'file_size' || column.key === 'created_at') {
                        classes += ' whitespace-nowrap';
                    }
                    return classes;
                },
                
                getColumnStyle(columnKey) {
                    const width = this.columnWidths[columnKey] || 150;
                    return `width: ${width}px; min-width: ${width}px; max-width: ${width}px;`;
                },
                
                getTotalTableWidth() {
                    const selectionWidth = 60; // Selection column
                    const actionsWidth = 200; // Actions column
                    const visibleColumnsWidth = this.visibleColumnsList.reduce((total, column) => {
                        return total + (this.columnWidths[column.key] || 150);
                    }, 0);
                    return selectionWidth + visibleColumnsWidth + actionsWidth;
                },
                
                // Cell content generation
                getCellContent(file, column) {
                    switch (column.key) {
                        case 'original_filename':
                            return this.getFilenameCell(file);
                        case 'email':
                            return `<div class="truncate" title="${file.email}">${file.email}</div>`;
                        case 'file_size':
                            return this.formatBytes(file.file_size);
                        case 'status':
                            return this.getStatusCell(file);
                        case 'created_at':
                            return this.formatDate(file.created_at);
                        default:
                            return '';
                    }
                },
                
                getFilenameCell(file) {
                    const thumbnailHtml = file.can_preview && file.thumbnail_url 
                        ? `<img src="${file.thumbnail_url}" alt="${file.original_filename}" class="w-8 h-8 object-cover rounded">`
                        : `<div class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center">
                             <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                             </svg>
                           </div>`;
                    
                    return `<div class="flex items-center space-x-3">
                              <div class="flex-shrink-0 w-8 h-8">${thumbnailHtml}</div>
                              <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 break-words" title="${file.original_filename}">${file.original_filename}</p>
                              </div>
                            </div>`;
                },
                
                getStatusCell(file) {
                    if (file.google_drive_file_id) {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Uploaded</span>';
                    } else {
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>';
                    }
                },
                
                // Column resizing methods
                setupColumnResizing() {
                    document.addEventListener('mousemove', (e) => this.handleColumnResize(e));
                    document.addEventListener('mouseup', () => this.endColumnResize());
                },
                
                startColumnResize(event, columnKey) {
                    event.preventDefault();
                    this.isResizing = true;
                    this.resizingColumn = columnKey;
                    this.startX = event.clientX;
                    this.startWidth = this.columnWidths[columnKey] || 150;
                    document.body.style.cursor = 'col-resize';
                },
                
                handleColumnResize(event) {
                    if (!this.isResizing || !this.resizingColumn) return;
                    
                    const diff = event.clientX - this.startX;
                    const column = this.availableColumns.find(col => col.key === this.resizingColumn);
                    const minWidth = column?.minWidth || 100;
                    const newWidth = Math.max(minWidth, this.startWidth + diff);
                    
                    this.columnWidths[this.resizingColumn] = newWidth;
                },
                
                endColumnResize() {
                    if (this.isResizing) {
                        this.isResizing = false;
                        this.resizingColumn = null;
                        document.body.style.cursor = '';
                        this.saveColumnPreferences();
                    }
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
                },

                // Advanced filter methods
                clearAllFilters() {
                    this.searchQuery = '';
                    this.statusFilter = '';
                    this.fileTypeFilter = '';
                    this.dateFromFilter = '';
                    this.dateToFilter = '';
                    this.userEmailFilter = '';
                    this.fileSizeMinFilter = '';
                    this.fileSizeMaxFilter = '';
                    this.selectedFiles = [];
                },

                parseFileSize(sizeString) {
                    if (!sizeString || typeof sizeString !== 'string') return 0;
                    
                    const sizeStr = sizeString.trim().toUpperCase();
                    const match = sizeStr.match(/^(\d+(?:\.\d+)?)\s*([KMGT]?B?)$/);
                    
                    if (!match) {
                        // If it's just a number, treat as bytes
                        const num = parseFloat(sizeString);
                        return isNaN(num) ? 0 : num;
                    }
                    
                    const number = parseFloat(match[1]);
                    const unit = match[2] || 'B';
                    
                    const multipliers = {
                        'B': 1,
                        'KB': 1024,
                        'MB': 1024 * 1024,
                        'GB': 1024 * 1024 * 1024,
                        'TB': 1024 * 1024 * 1024 * 1024,
                        'K': 1024,
                        'M': 1024 * 1024,
                        'G': 1024 * 1024 * 1024,
                        'T': 1024 * 1024 * 1024 * 1024
                    };
                    
                    return Math.floor(number * (multipliers[unit] || 1));
                }
            }
        }
    </script>
    @endpush
</x-app-layout>