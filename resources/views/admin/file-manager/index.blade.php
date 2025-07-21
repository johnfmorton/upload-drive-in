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
                                        x-on:click="bulkDelete()"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        {{ __('messages.delete_selected') }}
                                    </button>
                                    
                                    <button 
                                        x-on:click="bulkDownload()"
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
                                            x-on:click="searchQuery = ''"
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
                                        x-on:click="showAdvancedFilters = !showAdvancedFilters"
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
                                            x-on:click="open = !open"
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
                                            x-on:click.away="open = false"
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
                                                            x-on:change="toggleColumn(column.key)"
                                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 mr-3"
                                                        >
                                                        <span x-text="column.label"></span>
                                                    </label>
                                                </template>
                                                <div class="border-t border-gray-100 pt-1">
                                                    <button 
                                                        x-on:click="resetColumns()"
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
                                        x-on:click="toggleViewMode()"
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
                                    x-on:click="clearAllFilters()"
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

                    <!-- Success Notification -->
                    <div 
                        x-show="showSuccessNotification" 
                        x-cloak
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-2"
                        class="fixed top-4 right-4 z-50 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5"
                    >
                        <div class="p-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3 w-0 flex-1 pt-0.5">
                                    <p class="text-sm font-medium text-gray-900">Success!</p>
                                    <p class="mt-1 text-sm text-gray-500" x-text="successMessage"></p>
                                </div>
                                <div class="ml-4 flex-shrink-0 flex">
                                    <button 
                                        x-on:click="showSuccessNotification = false"
                                        class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        <span class="sr-only">Close</span>
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Error Notification -->
                    <div 
                        x-show="showErrorModal" 
                        x-cloak
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-2"
                        class="fixed top-4 right-4 z-50 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-red-500 ring-opacity-5"
                    >
                        <div class="p-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3 w-0 flex-1 pt-0.5">
                                    <p class="text-sm font-medium text-gray-900">Error</p>
                                    <p class="mt-1 text-sm text-gray-500" x-text="errorMessage"></p>
                                    <div x-show="isErrorRetryable" class="mt-3">
                                        <button 
                                            x-on:click="retryLastOperation()"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                        >
                                            Retry
                                        </button>
                                    </div>
                                </div>
                                <div class="ml-4 flex-shrink-0 flex">
                                    <button 
                                        x-on:click="showErrorModal = false"
                                        class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    >
                                        <span class="sr-only">Close</span>
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Confirmation Dialog -->
                    <div 
                        x-show="showConfirmDialog" 
                        x-cloak
                        class="fixed inset-0 z-50 overflow-y-auto"
                        aria-labelledby="confirm-modal-title"
                        role="dialog"
                        aria-modal="true"
                    >
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <!-- Background overlay -->
                            <div 
                                x-show="showConfirmDialog"
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                                x-on:click="showConfirmDialog = false"
                                aria-hidden="true"
                            ></div>

                            <!-- Modal panel -->
                            <div 
                                x-show="showConfirmDialog"
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                            >
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <div class="sm:flex sm:items-start">
                                        <div 
                                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10"
                                            :class="{
                                                'bg-red-100': confirmDialogType === 'danger',
                                                'bg-yellow-100': confirmDialogType === 'warning',
                                                'bg-blue-100': confirmDialogType === 'info'
                                            }"
                                        >
                                            <svg 
                                                x-show="confirmDialogType === 'danger'"
                                                class="h-6 w-6 text-red-600" 
                                                xmlns="http://www.w3.org/2000/svg" 
                                                fill="none" 
                                                viewBox="0 0 24 24" 
                                                stroke="currentColor" 
                                                aria-hidden="true"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <svg 
                                                x-show="confirmDialogType === 'warning'"
                                                class="h-6 w-6 text-yellow-600" 
                                                xmlns="http://www.w3.org/2000/svg" 
                                                fill="none" 
                                                viewBox="0 0 24 24" 
                                                stroke="currentColor" 
                                                aria-hidden="true"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <svg 
                                                x-show="confirmDialogType === 'info'"
                                                class="h-6 w-6 text-blue-600" 
                                                xmlns="http://www.w3.org/2000/svg" 
                                                fill="none" 
                                                viewBox="0 0 24 24" 
                                                stroke="currentColor" 
                                                aria-hidden="true"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="confirm-modal-title" x-text="confirmDialogTitle">
                                            </h3>
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-500" x-text="confirmDialogMessage"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button 
                                        type="button" 
                                        x-on:click="confirmAction()"
                                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                                        :class="confirmDialogDestructive ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' : 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'"
                                    >
                                        <span x-show="!isLoading">Confirm</span>
                                        <span x-show="isLoading" class="flex items-center">
                                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Processing...
                                        </span>
                                    </button>
                                    <button 
                                        type="button" 
                                        x-on:click="cancelConfirmation()"
                                        :disabled="isLoading"
                                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Operation Progress Modal -->
                    <div 
                        x-show="bulkOperationProgress.show" 
                        x-cloak
                        class="fixed inset-0 z-50 overflow-y-auto"
                        aria-labelledby="progress-modal-title"
                        role="dialog"
                        aria-modal="true"
                    >
                        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <!-- Background overlay -->
                            <div 
                                x-show="bulkOperationProgress.show"
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                                aria-hidden="true"
                            ></div>

                            <!-- Modal panel -->
                            <div 
                                x-show="bulkOperationProgress.show"
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                            >
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <div class="sm:flex sm:items-start">
                                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                            <svg class="animate-spin h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="progress-modal-title">
                                                <span x-text="bulkOperationProgress.operation"></span> Files
                                            </h3>
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-500 mb-4" x-text="bulkOperationProgress.message"></p>
                                                
                                                <!-- Progress Bar -->
                                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                    <div 
                                                        class="bg-blue-600 h-2.5 rounded-full transition-all duration-300 ease-out"
                                                        :style="`width: ${bulkOperationProgress.total > 0 ? (bulkOperationProgress.current / bulkOperationProgress.total) * 100 : 0}%`"
                                                    ></div>
                                                </div>
                                                
                                                <div class="flex justify-between text-xs text-gray-500 mt-2">
                                                    <span x-text="`${bulkOperationProgress.current} of ${bulkOperationProgress.total}`"></span>
                                                    <span x-text="`${bulkOperationProgress.total > 0 ? Math.round((bulkOperationProgress.current / bulkOperationProgress.total) * 100) : 0}%`"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Download Progress Modal -->
                    <div 
                        x-show="downloadProgress.show" 
                        x-cloak
                        class="fixed inset-0 z-50 overflow-y-auto"
                        aria-labelledby="download-progress-modal-title"
                        role="dialog"
                        aria-modal="true"
                    >
                        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <!-- Background overlay -->
                            <div 
                                x-show="downloadProgress.show"
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                                aria-hidden="true"
                            ></div>

                            <!-- Modal panel -->
                            <div 
                                x-show="downloadProgress.show"
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                            >
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <div class="sm:flex sm:items-start">
                                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                            <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="download-progress-modal-title">
                                                Downloading File
                                            </h3>
                                            <div class="mt-2">
                                                <p class="text-sm text-gray-500 mb-4" x-text="downloadProgress.filename"></p>
                                                
                                                <!-- Progress Bar -->
                                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                    <div 
                                                        class="bg-green-600 h-2.5 rounded-full transition-all duration-300 ease-out"
                                                        :style="`width: ${downloadProgress.percentage}%`"
                                                    ></div>
                                                </div>
                                                
                                                <div class="flex justify-center text-xs text-gray-500 mt-2">
                                                    <span x-text="`${downloadProgress.percentage}%`"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>




                    <!-- Loading Overlay -->
                    <div 
                        x-show="isLoading" 
                        x-cloak
                        class="fixed inset-0 z-40 bg-gray-500 bg-opacity-75 flex items-center justify-center"
                    >
                        <div class="bg-white rounded-lg p-6 shadow-xl max-w-md w-full">
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-center space-x-4">
                                    <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-lg font-medium text-gray-900" x-text="operationInProgress || 'Loading...'"></span>
                                </div>
                                
                                <!-- Progress bar for operations that support it -->
                                <div x-show="(downloadProgressPercent || 0) > 0" class="w-full">
                                    <div class="text-xs font-medium text-gray-500 mb-1">
                                        <span x-text="Math.round(downloadProgressPercent || 0) + '%'"></span>
                                        <span x-show="(downloadTotal || 0) > 0" x-text="' - ' + formatBytes((downloadProgressPercent || 0) / 100 * (downloadTotal || 0)) + ' of ' + formatBytes(downloadTotal || 0)"></span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-blue-600 h-2.5 rounded-full" :style="'width: ' + (downloadProgressPercent || 0) + '%'"></div>
                                    </div>
                                </div>
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
                                                    x-on:click="previewFile(file)"
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
                                                    x-on:click="previewFile(file)"
                                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                                                >
                                                    {{ __('messages.preview') }}
                                                </button>
                                                <button 
                                                    x-on:click="downloadFile(file)"
                                                    class="text-xs text-green-600 hover:text-green-800 font-medium"
                                                >
                                                    {{ __('messages.download') }}
                                                </button>
                                            </div>
                                            <button 
                                                x-on:click="deleteFile(file)"
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
                                                    x-on:click="column.sortable ? sortBy(column.key) : null"
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
                                                                x-on:mousedown="startColumnResize($event, column.key)"
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
                                                            x-on:click="previewFile(file)"
                                                            class="text-blue-600 hover:text-blue-900"
                                                        >
                                                            {{ __('messages.preview') }}
                                                        </button>
                                                        <button 
                                                            x-on:click="downloadFile(file)"
                                                            class="text-green-600 hover:text-green-900"
                                                        >
                                                            {{ __('messages.download') }}
                                                        </button>
                                                        <button 
                                                            x-on:click="deleteFile(file)"
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
        // Define the component before Alpine.js processes it
        document.addEventListener('alpine:init', () => {
            Alpine.data('fileManager', (initialFiles, initialStatistics) => ({
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
                visibleColumns: {},
                columnWidths: {},
                
                // Column resizing state
                isResizing: false,
                resizingColumn: null,
                startX: 0,
                startWidth: 0,
                
                // User feedback states
                isLoading: false,
                showErrorModal: false,
                errorMessage: '',
                isErrorRetryable: false,
                showSuccessNotification: false,
                successMessage: '',
                showConfirmDialog: false,
                confirmDialogTitle: '',
                confirmDialogMessage: '',
                confirmDialogAction: null,
                confirmDialogType: 'info', // 'danger', 'warning', 'info'
                confirmDialogDestructive: false,
                
                // Operation states
                isDeleting: false,
                isDownloading: false,
                operationInProgress: '',
                lastOperation: null,
                currentFile: null,
                
                // Download progress tracking (for progress bars in UI)
                downloadProgressPercent: 0,
                downloadTotal: 0,
                downloadStartTime: null,
                downloadEstimatedTime: null,
                
                bulkOperationProgress: {
                    show: false,
                    current: 0,
                    total: 0,
                    operation: '',
                    message: ''
                },
                downloadProgress: {
                    show: false,
                    percentage: 0,
                    filename: ''
                },
                
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
                    console.log('File Manager initializing...', {
                        files: this.files,
                        statistics: this.statistics
                    });
                    
                    try {
                        this.visibleColumns = this.getStoredColumnVisibility();
                        this.columnWidths = this.getStoredColumnWidths();
                        this.setupColumnResizing();
                        console.log('File Manager initialized successfully');
                    } catch (error) {
                        console.error('Error initializing file manager:', error);
                        // Set default values if initialization fails
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
                    }
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
                    this.isLoading = true;
                    this.isDownloading = true;
                    this.operationInProgress = `Preparing download for ${file.original_filename}...`;
                    this.downloadProgressPercent = 0;
                    this.downloadTotal = file.file_size || 0;
                    this.downloadStartTime = new Date();
                    
                    try {
                        // For small files, show a simple success notification
                        if (file.file_size < 5 * 1024 * 1024) { // Less than 5MB
                            this.showSuccess(`Downloading ${file.original_filename}...`, 2000);
                            
                            // Create an invisible iframe for download
                            const iframe = document.createElement('iframe');
                            iframe.style.display = 'none';
                            iframe.name = 'download-frame';
                            iframe.src = `/admin/file-manager/${file.id}/download`;
                            document.body.appendChild(iframe);
                            
                            // Remove iframe after download starts (after a short delay)
                            setTimeout(() => {
                                document.body.removeChild(iframe);
                                this.isLoading = false;
                                this.isDownloading = false;
                                this.operationInProgress = '';
                            }, 2000);
                        } else {
                            // For larger files, use XHR to track progress
                            const xhr = new XMLHttpRequest();
                            xhr.open('GET', `/admin/file-manager/${file.id}/download`, true);
                            xhr.responseType = 'blob';
                            
                            // Track download progress
                            xhr.onprogress = (event) => {
                                if (event.lengthComputable) {
                                    this.downloadProgressPercent = (event.loaded / event.total) * 100;
                                    this.downloadTotal = event.total;
                                    
                                    // Update operation message with progress
                                    this.operationInProgress = `Downloading ${file.original_filename}... ${Math.round(this.downloadProgressPercent)}%`;
                                    
                                    // Calculate estimated time remaining
                                    if (this.downloadProgressPercent > 0) {
                                        const elapsedTime = (new Date() - this.downloadStartTime) / 1000;
                                        const estimatedTotalTime = elapsedTime / (this.downloadProgressPercent / 100);
                                        this.downloadEstimatedTime = Math.round(estimatedTotalTime - elapsedTime);
                                    }
                                }
                            };
                            
                            // Handle download completion
                            xhr.onload = () => {
                                if (xhr.status === 200) {
                                    // Create download link
                                    const blob = new Blob([xhr.response], { type: xhr.getResponseHeader('Content-Type') });
                                    const url = window.URL.createObjectURL(blob);
                                    const a = document.createElement('a');
                                    a.href = url;
                                    a.download = file.original_filename;
                                    document.body.appendChild(a);
                                    a.click();
                                    window.URL.revokeObjectURL(url);
                                    document.body.removeChild(a);
                                    
                                    // Show success notification
                                    this.showSuccess(`Successfully downloaded ${file.original_filename}`);
                                } else {
                                    this.showError(`Failed to download ${file.original_filename}`);
                                }
                                
                                // Reset download state
                                this.isLoading = false;
                                this.isDownloading = false;
                                this.operationInProgress = '';
                                this.downloadProgressPercent = 0;
                            };
                            
                            // Handle download errors
                            xhr.onerror = () => {
                                this.showError(`Failed to download ${file.original_filename}. Please try again.`);
                                this.isLoading = false;
                                this.isDownloading = false;
                                this.operationInProgress = '';
                                this.downloadProgressPercent = 0;
                            };
                            
                            // Start download
                            xhr.send();
                        }
                    } catch (error) {
                        this.isLoading = false;
                        this.isDownloading = false;
                        this.operationInProgress = '';
                        this.showError('Failed to initiate download. Please try again.');
                    }
                },
                
                async deleteFile(file) {
                    this.showConfirmation(
                        'Delete File',
                        `Are you sure you want to delete "${file.original_filename}"? This action cannot be undone.`,
                        () => this.performDeleteFile(file),
                        'danger'
                    );
                },
                
                async performDeleteFile(file) {
                    this.isLoading = true;
                    this.isDeleting = true;
                    this.operationInProgress = 'Deleting file...';
                    
                    try {
                        const response = await fetch(`/admin/file-manager/${file.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Remove the file from the list
                            this.files = this.files.filter(f => f.id !== file.id);
                            this.showSuccess(`File "${file.original_filename}" has been deleted successfully.`);
                            
                            // Update statistics if available
                            if (data.statistics) {
                                this.statistics = data.statistics;
                            }
                        } else {
                            this.showError(data.message || 'Failed to delete file.');
                        }
                    } catch (error) {
                        this.handleApiError(error, 'file deletion');
                    } finally {
                        this.isLoading = false;
                        this.isDeleting = false;
                        this.operationInProgress = '';
                    }
                },
                
                async bulkDelete() {
                    if (this.selectedFiles.length === 0) {
                        this.showError('Please select files to delete.');
                        return;
                    }
                    
                    const fileCount = this.selectedFiles.length;
                    this.showConfirmation(
                        'Delete Files',
                        `Are you sure you want to delete ${fileCount} file${fileCount > 1 ? 's' : ''}? This action cannot be undone.`,
                        () => this.performBulkDelete(),
                        'danger'
                    );
                },
                
                async bulkDownload() {
                    if (this.selectedFiles.length === 0) {
                        this.showError('Please select files to download.');
                        return;
                    }
                    
                    const fileCount = this.selectedFiles.length;
                    this.showConfirmation(
                        'Download Files',
                        `Download ${fileCount} file${fileCount > 1 ? 's' : ''} as a ZIP archive?`,
                        () => this.performBulkDownload(),
                        'info'
                    );
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
                
                // Retry functionality
                retryLastOperation() {
                    if (!this.lastOperation) {
                        this.showError('No operation to retry.');
                        return;
                    }
                    
                    this.showErrorModal = false;
                    
                    // Delay slightly to allow modal to close
                    setTimeout(() => {
                        switch (this.lastOperation.type) {
                            case 'file deletion':
                                if (this.lastOperation.params && this.lastOperation.params.file) {
                                    this.performDeleteFile(this.lastOperation.params.file);
                                }
                                break;
                            case 'bulk delete':
                                this.performBulkDelete();
                                break;
                            case 'download':
                                if (this.lastOperation.params && this.lastOperation.params.file) {
                                    this.downloadFile(this.lastOperation.params.file);
                                }
                                break;
                            case 'bulk download':
                                this.performBulkDownload();
                                break;
                            default:
                                this.refreshData();
                                break;
                        }
                    }, 300);
                },
                
                getOperationParams(operation) {
                    // Store relevant parameters for retry functionality
                    switch (operation) {
                        case 'file deletion':
                            return { file: this.currentFile };
                        case 'download':
                            return { file: this.currentFile };
                        case 'bulk delete':
                            return { fileIds: [...this.selectedFiles] };
                        case 'bulk download':
                            return { fileIds: [...this.selectedFiles] };
                        default:
                            return {};
                    }
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
                },

                // User feedback methods
                showSuccess(message, duration = 3000) {
                    this.successMessage = message;
                    this.showSuccessNotification = true;
                    
                    setTimeout(() => {
                        this.showSuccessNotification = false;
                    }, duration);
                },

                showConfirmation(title, message, action, type = 'info') {
                    this.confirmDialogTitle = title;
                    this.confirmDialogMessage = message;
                    this.confirmDialogAction = action;
                    this.confirmDialogType = type;
                    this.confirmDialogDestructive = type === 'danger';
                    this.showConfirmDialog = true;
                },

                confirmAction() {
                    if (this.confirmDialogAction && typeof this.confirmDialogAction === 'function') {
                        this.confirmDialogAction();
                    }
                    this.showConfirmDialog = false;
                },

                cancelConfirmation() {
                    this.showConfirmDialog = false;
                    this.confirmDialogAction = null;
                    this.confirmDialogDestructive = false;
                },

                showBulkProgress(operation, total, message = '') {
                    this.bulkOperationProgress = {
                        show: true,
                        current: 0,
                        total: total,
                        operation: operation,
                        message: message
                    };
                },

                updateBulkProgress(current, message = '') {
                    this.bulkOperationProgress.current = current;
                    if (message) {
                        this.bulkOperationProgress.message = message;
                    }
                },

                hideBulkProgress() {
                    this.bulkOperationProgress.show = false;
                },

                showDownloadProgress(filename) {
                    this.downloadProgress = {
                        show: true,
                        percentage: 0,
                        filename: filename
                    };
                },

                updateDownloadProgress(percentage) {
                    this.downloadProgress.percentage = Math.min(100, Math.max(0, percentage));
                },

                hideDownloadProgress() {
                    this.downloadProgress.show = false;
                },
                
                showError(message, isRetryable = false) {
                    this.errorMessage = message;
                    this.isErrorRetryable = isRetryable;
                    this.showErrorModal = true;
                    
                    // Auto-hide non-critical errors after 5 seconds
                    if (!isRetryable) {
                        setTimeout(() => {
                            this.showErrorModal = false;
                        }, 5000);
                    }
                },
                
                handleApiError(error, operation = 'operation') {
                    console.error(`Error during ${operation}:`, error);
                    
                    let message = 'An unexpected error occurred. Please try again.';
                    let isRetryable = false;
                    
                    if (error.response) {
                        // Server responded with an error
                        if (error.response.data && error.response.data.message) {
                            message = error.response.data.message;
                        }
                        isRetryable = error.response.data && error.response.data.is_retryable;
                    } else if (error.request) {
                        // Request was made but no response
                        message = 'No response from server. Please check your connection and try again.';
                        isRetryable = true;
                    }
                    
                    // Store the last operation for retry functionality
                    this.lastOperation = {
                        type: operation,
                        params: this.getOperationParams(operation)
                    };
                    
                    this.showError(message, isRetryable);
                },

                // Modal methods
                async confirmDelete() {
                    if (!this.currentFile || this.isDeleting) return;
                    
                    this.isDeleting = true;
                    
                    try {
                        const response = await fetch(`/admin/file-manager/${this.currentFile.id}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        if (response.ok) {
                            const data = await response.json();
                            this.showSuccess(data.message || 'File deleted successfully.');
                            
                            // Remove file from the list
                            this.files = this.files.filter(f => f.id !== this.currentFile.id);
                            
                            // Close modal
                            this.currentFile = null;
                        } else {
                            const errorData = await response.json();
                            this.showError(errorData.message || 'Failed to delete file.');
                        }
                    } catch (error) {
                        console.error('Delete error:', error);
                        this.showError('An error occurred while deleting the file.');
                    } finally {
                        this.isDeleting = false;
                    }
                },

                // Enhanced bulk delete with confirmation and progress
                confirmBulkDelete() {
                    if (this.selectedFiles.length === 0) {
                        this.showError('Please select files to delete.');
                        return;
                    }

                    const fileCount = this.selectedFiles.length;
                    const message = `Are you sure you want to delete ${fileCount} selected file${fileCount > 1 ? 's' : ''}? This action cannot be undone.`;
                    
                    this.showConfirmation(
                        'Confirm Bulk Delete',
                        message,
                        () => this.performBulkDelete(),
                        'danger'
                    );
                },

                async performBulkDelete() {
                    const selectedIds = [...this.selectedFiles];
                    const totalFiles = selectedIds.length;
                    
                    this.showBulkProgress('Deleting', totalFiles, 'Preparing to delete files...');
                    
                    try {
                        const response = await fetch('/admin/file-manager/bulk-delete', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                file_ids: selectedIds
                            })
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            this.hideBulkProgress();
                            this.showSuccess(`Successfully deleted ${data.deleted_count} file${data.deleted_count > 1 ? 's' : ''}.`);
                            
                            // Remove deleted files from the UI
                            this.files = this.files.filter(file => !selectedIds.includes(file.id));
                            this.selectedFiles = [];
                            
                            // Update statistics if available
                            if (data.statistics) {
                                this.statistics = data.statistics;
                            }
                        } else {
                            this.hideBulkProgress();
                            this.showError(data.message || 'Failed to delete files.');
                        }
                    } catch (error) {
                        this.hideBulkProgress();
                        this.handleApiError(error, 'bulk delete');
                    }
                },

                // Enhanced bulk download with progress
                async performBulkDownload() {
                    if (this.selectedFiles.length === 0) {
                        this.showError('Please select files to download.');
                        return;
                    }

                    const selectedIds = [...this.selectedFiles];
                    const totalFiles = selectedIds.length;
                    
                    // Calculate total size of selected files
                    let totalSize = 0;
                    selectedIds.forEach(id => {
                        const file = this.files.find(f => f.id === id);
                        if (file && file.file_size) {
                            totalSize += file.file_size;
                        }
                    });
                    
                    // Set up download tracking
                    this.isLoading = true;
                    this.isDownloading = true;
                    this.operationInProgress = `Preparing download for ${totalFiles} file${totalFiles > 1 ? 's' : ''}...`;
                    this.downloadProgressPercent = 0;
                    this.downloadTotal = totalSize;
                    this.downloadStartTime = new Date();
                    
                    try {
                        // Use XHR for progress tracking
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', '/admin/file-manager/bulk-download', true);
                        xhr.responseType = 'blob';
                        xhr.setRequestHeader('Content-Type', 'application/json');
                        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                        xhr.setRequestHeader('Accept', 'application/octet-stream');
                        
                        // Track download progress
                        xhr.onprogress = (event) => {
                            if (event.lengthComputable) {
                                this.downloadProgressPercent = (event.loaded / event.total) * 100;
                                this.downloadTotal = event.total;
                                
                                // Update operation message with progress
                                this.operationInProgress = `Downloading ${totalFiles} file${totalFiles > 1 ? 's' : ''}... ${Math.round(this.downloadProgressPercent)}%`;
                                
                                // Calculate estimated time remaining
                                if (this.downloadProgressPercent > 0) {
                                    const elapsedTime = (new Date() - this.downloadStartTime) / 1000;
                                    const estimatedTotalTime = elapsedTime / (this.downloadProgressPercent / 100);
                                    this.downloadEstimatedTime = Math.round(estimatedTotalTime - elapsedTime);
                                }
                            }
                        };
                        
                        // Handle download completion
                        xhr.onload = () => {
                            if (xhr.status === 200) {
                                // Create download link
                                const blob = new Blob([xhr.response], { type: xhr.getResponseHeader('Content-Type') || 'application/zip' });
                                const url = window.URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = `files-${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.zip`;
                                document.body.appendChild(a);
                                a.click();
                                window.URL.revokeObjectURL(url);
                                document.body.removeChild(a);
                                
                                // Show success notification
                                this.showSuccess(`Successfully downloaded ${totalFiles} file${totalFiles > 1 ? 's' : ''}.`);
                            } else {
                                // Try to parse error response
                                try {
                                    const reader = new FileReader();
                                    reader.onload = () => {
                                        try {
                                            const errorData = JSON.parse(reader.result);
                                            this.showError(errorData.message || 'Failed to download files.');
                                        } catch (e) {
                                            this.showError('Failed to download files. Please try again.');
                                        }
                                    };
                                    reader.readAsText(xhr.response);
                                } catch (e) {
                                    this.showError('Failed to download files. Please try again.');
                                }
                            }
                            
                            // Reset download state
                            this.isLoading = false;
                            this.isDownloading = false;
                            this.operationInProgress = '';
                            this.downloadProgressPercent = 0;
                        };
                        
                        // Handle download errors
                        xhr.onerror = () => {
                            this.showError('Failed to download files. Please try again.');
                            this.isLoading = false;
                            this.isDownloading = false;
                            this.operationInProgress = '';
                            this.downloadProgressPercent = 0;
                        };
                        
                        // Start download
                        xhr.send(JSON.stringify({
                            file_ids: selectedIds
                        }));
                    } catch (error) {
                        this.hideBulkProgress();
                        this.handleApiError(error, 'bulk download');
                    }
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>