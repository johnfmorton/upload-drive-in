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
                <div x-data="employeeFileManager({{ json_encode($files->items()) }}, {{ json_encode($statistics ?? []) }})" class="file-manager">
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
                                    <span class="font-medium" x-text="statistics.total || 0"></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-gray-500">Pending:</span>
                                    <span class="font-medium text-yellow-600" x-text="statistics.pending || 0"></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-gray-500">Total Size:</span>
                                    <span class="font-medium" x-text="formatBytes(statistics.total_size || 0)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Toolbar -->
                    <x-file-manager.toolbar user-type="employee" :username="auth()->user()->username" />

                    <!-- Advanced Filters -->
                    <x-file-manager.advanced-filters />

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
                                <div class="aspect-w-16 aspect-h-9">
                                    <div class="flex items-center justify-center p-4">
                                        <template x-if="file.mime_type && file.mime_type.startsWith('image/')">
                                            <img :src="getThumbnailUrl(file) + (getThumbnailUrl(file).includes('?') ? '&' : '?') + 'v=' + (file.updated_at ? new Date(file.updated_at).getTime() : Date.now())" :alt="file.original_filename"
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
                                        <!-- Selection Column (always visible) -->
                                        <th scope="col" class="w-12 px-6 py-3 sticky left-0 bg-gray-50 z-10">
                                            <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                        </th>

                                        <!-- Dynamic Columns -->
                                        <template x-for="column in visibleColumnsList" :key="column.key">
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                x-text="column.label">
                                            </th>
                                        </template>

                                        <!-- Actions Column (always visible) -->
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky right-0 bg-gray-50 z-10">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="file in filteredFiles" :key="file.id">
                                        <tr class="hover:bg-gray-50">
                                            <!-- Selection Column (always visible) -->
                                            <td class="px-6 py-4 whitespace-nowrap sticky left-0 bg-white z-10">
                                                <input type="checkbox" :value="file.id" x-model="selectedFiles"
                                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                            </td>

                                            <!-- Dynamic Columns -->
                                            <template x-for="column in visibleColumnsList" :key="column.key">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <!-- Filename Column -->
                                                    <template x-if="column.key === 'original_filename'">
                                                        <div class="flex items-center">
                                                            <div class="flex-shrink-0 h-10 w-10">
                                                                <!-- Show thumbnail only for image files -->
                                                                <template x-if="file.mime_type && file.mime_type.startsWith('image/')">
                                                                    <img class="h-10 w-10 rounded object-cover" :src="getThumbnailUrl(file)" :alt="file.original_filename">
                                                                </template>
                                                                <!-- Show file icon for non-image files -->
                                                                <template x-if="!file.mime_type || !file.mime_type.startsWith('image/')">
                                                                    <div class="h-10 w-10 rounded bg-gray-100 flex items-center justify-center">
                                                                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                                            </path>
                                                                        </svg>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="text-sm font-medium text-gray-900">
                                                                    <a :href="getShowUrl(file)" class="text-blue-600 hover:text-blue-900" x-text="file.original_filename"></a>
                                                                </div>
                                                                <div x-show="file.message" class="text-sm text-gray-500" x-text="truncateText(file.message, 50)"></div>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <!-- Email Column -->
                                                    <template x-if="column.key === 'email'">
                                                        <span x-text="file.email"></span>
                                                    </template>

                                                    <!-- File Size Column -->
                                                    <template x-if="column.key === 'file_size'">
                                                        <span class="text-gray-500" x-text="formatBytes(file.file_size)"></span>
                                                    </template>

                                                    <!-- Date Column -->
                                                    <template x-if="column.key === 'created_at'">
                                                        <span class="text-gray-500" x-text="formatDate(file.created_at)"></span>
                                                    </template>

                                                    <!-- Status Column -->
                                                    <template x-if="column.key === 'status'">
                                                        <div>
                                                            <span x-show="file.google_drive_file_id"
                                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                Uploaded
                                                            </span>
                                                            <span x-show="!file.google_drive_file_id"
                                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                                Processing
                                                            </span>
                                                        </div>
                                                    </template>

                                                    <!-- Message Column -->
                                                    <template x-if="column.key === 'message'">
                                                        <span class="text-gray-500" x-text="truncateText(file.message || '', 100)"></span>
                                                    </template>
                                                </td>
                                            </template>

                                            <!-- Actions Column (always visible) -->
                                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap sticky right-0 bg-white z-10">
                                                <div class="flex items-center space-x-2">
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
    <x-file-manager.shared-javascript user-type="employee" :username="auth()->user()->username" />
    @endpush

    <!-- Preview Modal -->
    @include('employee.file-manager.partials.preview-modal')
</x-app-layout>
