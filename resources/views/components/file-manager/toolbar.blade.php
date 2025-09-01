@props([
    'userType' => 'admin', // 'admin' or 'employee'
    'username' => null, // Required for employee routes
])

<!-- Toolbar -->
<div class="px-4 py-4 sm:px-6 border-b border-gray-200 bg-gray-50">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <!-- Bulk Actions -->
        <div class="flex items-center space-x-4">
            <label class="flex items-center space-x-2">
                <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                <span class="text-sm text-gray-700">{{ __('messages.select_all') ?? 'Select All' }}</span>
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
                    {{ __('messages.delete_selected') ?? 'Delete Selected' }}
                </button>

                <button x-on:click="bulkDownload()"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    {{ __('messages.download_selected') ?? 'Download Selected' }}
                </button>

                <!-- Bulk Retry Button (only show if recoverable files are selected) -->
                <button x-show="selectedFiles.some(id => {
                            const file = files.find(f => f.id === id);
                            return file && isErrorRecoverable(file);
                        })"
                        x-on:click="bulkRetryFiles()"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                    {{ __('messages.retry_selected') ?? 'Retry Selected' }}
                </button>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="flex flex-col xl:flex-row gap-3">
            <!-- Enhanced Search Input -->
            <div class="relative">
                <input type="text" x-model.debounce.500ms="searchQuery"
                    placeholder="{{ __('messages.search_files_placeholder') ?? 'Search files, users, or messages...' }}"
                    class="block w-full sm:w-80 lg:w-96 xl:w-80 min-w-0 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm pl-10 pr-10">
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
                    <option value="">{{ __('messages.all_statuses') ?? 'All Statuses' }}</option>
                    <option value="uploaded">{{ __('messages.status_uploaded') ?? 'Uploaded' }}</option>
                    <option value="pending">{{ __('messages.status_pending') ?? 'Processing' }}</option>
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

                <!-- Advanced Filters Toggle -->
                <button x-on:click="showAdvancedFilters = !showAdvancedFilters"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    :class="{ 'bg-blue-50 border-blue-300 text-blue-700': showAdvancedFilters }">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z">
                        </path>
                    </svg>
                    Filters
                    <span x-show="activeFiltersCount > 0"
                        class="ml-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-blue-600 rounded-full"
                        x-text="activeFiltersCount"></span>
                </button>
            </div>

            <div class="flex items-center space-x-2">
                <!-- Column Visibility Toggle (only show in table mode) -->
                <div x-show="viewMode === 'table'" class="relative" x-data="{ open: false }">
                    <button x-on:click="open = !open"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4">
                            </path>
                        </svg>
                        {{ __('messages.columns') ?? 'Columns' }}
                    </button>

                    <!-- Column visibility dropdown -->
                    <div x-show="open" x-on:click.away="open = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute left-0 xl:right-0 xl:left-auto mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-20">
                        <div class="py-1">
                            <div class="px-4 py-2 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100">
                                {{ __('messages.show_columns') ?? 'Show Columns' }}
                            </div>
                            <template x-for="column in availableColumns" :key="column.key">
                                <label class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" :checked="visibleColumns[column.key]"
                                        x-on:change="toggleColumn(column.key)"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 mr-3">
                                    <span x-text="column.label"></span>
                                </label>
                            </template>
                            <div class="border-t border-gray-100 pt-1">
                                <button x-on:click="resetColumns()"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    {{ __('messages.reset_columns') ?? 'Reset Columns' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

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