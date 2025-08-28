@props([
    'userType' => 'admin', // 'admin' or 'employee'
    'username' => null, // Required for employee routes
    'files' => collect(), // Collection of files
    'statistics' => [] // Array of statistics
])

@php
    // Ensure files is a collection and get items for JavaScript
    $filesCollection = is_array($files) ? collect($files) : $files;
    $filesArray = $filesCollection instanceof \Illuminate\Pagination\LengthAwarePaginator 
        ? $filesCollection->items() 
        : $filesCollection->toArray();
    
    // Ensure statistics has default values
    $stats = array_merge([
        'total' => 0,
        'pending' => 0,
        'total_size' => 0
    ], $statistics);
    
    // Use a consistent Alpine.js data function name
    $alpineDataFunction = 'fileManager';
@endphp

<!-- File Management Dashboard -->
<div class="bg-white shadow sm:rounded-lg">
    <div x-data="{{ $alpineDataFunction }}({{ json_encode($filesArray) }}, {{ json_encode($stats) }})" 
         class="file-manager" 
         data-lazy-container>
        
        <!-- Header Section -->
        <x-file-manager.header 
            :userType="$userType" 
            :statistics="$stats" />

        <!-- Toolbar -->
        <x-file-manager.toolbar 
            :userType="$userType" 
            :username="$username" />

        <!-- Advanced Filters Panel -->
        <x-file-manager.advanced-filters />

        <!-- Success Notification -->
        <x-file-manager.notifications.success-notification />

        <!-- Error Notification -->
        <x-file-manager.notifications.error-notification />

        <!-- File Content Area -->
        <div class="px-4 py-6 sm:px-6">
            <!-- Empty State -->
            <div x-show="filteredFiles.length === 0 && !searchQuery && !statusFilter && !fileTypeFilter" 
                 class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                    </path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No files uploaded yet</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Files uploaded through your public upload form will appear here.
                </p>
            </div>

            <!-- No Results State -->
            <div x-show="filteredFiles.length === 0 && (searchQuery || statusFilter || fileTypeFilter)" 
                 class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z">
                    </path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No files found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Try adjusting your search or filter criteria.
                </p>
                <div class="mt-6">
                    <button x-on:click="clearAllFilters()" 
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Clear all filters
                    </button>
                </div>
            </div>

            <!-- Grid View -->
            <div x-show="viewMode === 'grid' && filteredFiles.length > 0">
                <x-file-manager.file-grid 
                    :userType="$userType" 
                    :username="$username" />
            </div>

            <!-- Table View -->
            <div x-show="viewMode === 'table' && filteredFiles.length > 0">
                <x-file-manager.file-table 
                    :userType="$userType" 
                    :username="$username" />
            </div>
        </div>

        <!-- Pagination -->
        @if($filesCollection instanceof \Illuminate\Pagination\LengthAwarePaginator && $filesCollection->hasPages())
        <div class="px-4 py-3 sm:px-6 border-t border-gray-200">
            {{ $filesCollection->links() }}
        </div>
        @endif

        <!-- Modals -->
        <x-file-manager.modals.preview-modal 
            :userType="$userType" 
            :username="$username" />

        <x-file-manager.modals.confirmation-modal />

        <x-file-manager.modals.progress-modal />

        <!-- Shared JavaScript -->
        <x-file-manager.shared-javascript 
            :userType="$userType" 
            :username="$username" />
    </div>
</div>