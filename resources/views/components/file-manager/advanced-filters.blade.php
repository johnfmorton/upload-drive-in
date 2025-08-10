<!-- Advanced Filters Panel -->
<div x-show="showAdvancedFilters" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2"
    class="px-4 py-4 sm:px-6 border-b border-gray-200 bg-gray-50">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Date Range Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
            <div class="space-y-2">
                <input type="date" x-model="dateFromFilter" placeholder="From date"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <input type="date" x-model="dateToFilter" placeholder="To date"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
        </div>

        <!-- User Email Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Uploaded By</label>
            <input type="email" x-model.debounce.300ms="userEmailFilter"
                placeholder="Enter email address"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
        </div>

        <!-- File Size Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">File Size</label>
            <div class="space-y-2">
                <input type="text" x-model.debounce.300ms="fileSizeMinFilter"
                    placeholder="Min size (e.g., 1MB)"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <input type="text" x-model.debounce.300ms="fileSizeMaxFilter"
                    placeholder="Max size (e.g., 10MB)"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
        </div>

        <!-- Filter Actions -->
        <div class="flex flex-col justify-end space-y-2">
            <button x-on:click="clearAllFilters()"
                class="inline-flex items-center justify-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Clear All
            </button>
            <div class="text-xs text-gray-500 text-center">
                <span x-text="filteredFiles.length"></span> of <span x-text="files.length"></span> files
            </div>
        </div>
    </div>
</div>