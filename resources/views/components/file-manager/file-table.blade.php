@props([
    'userType' => 'admin',
    'username' => null
])

@php
    // Validate required props
    if ($userType === 'employee' && !$username) {
        throw new InvalidArgumentException('Username is required for employee user type');
    }
    
    // Define route patterns based on user type
    $routePatterns = [
        'admin' => [
            'preview' => 'admin.file-manager.show',
            'download' => 'admin.file-manager.download',
            'delete' => 'admin.file-manager.destroy'
        ],
        'employee' => [
            'preview' => 'employee.file-manager.show',
            'download' => 'employee.file-manager.download', 
            'delete' => 'employee.file-manager.destroy'
        ]
    ];
    
    $routes = $routePatterns[$userType] ?? $routePatterns['admin'];
@endphp

<!-- File Table View -->
<div x-show="viewMode === 'table'" class="overflow-hidden">
    <div class="overflow-x-auto max-h-none">
        <table class="min-w-full divide-y divide-gray-200" :style="tableStyles">
            <thead class="bg-gray-50">
                <tr>
                    <!-- Selection Column (always visible, sticky) -->
                    <th scope="col" class="w-12 px-6 py-3 sticky left-0 bg-gray-50 z-10">
                        <input type="checkbox" x-model="selectAll"
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    </th>

                    <!-- Dynamic Columns -->
                    <template x-for="column in visibleColumnsList" :key="column.key">
                        <th scope="col" :class="getColumnHeaderClass(column)"
                            x-on:click="column.sortable ? sortBy(column.key) : null"
                            :style="getColumnStyle(column.key)">
                            <div class="flex items-center justify-between group">
                                <span x-text="column.label"></span>
                                <div class="flex items-center space-x-1">
                                    <!-- Sort indicator -->
                                    <span x-show="column.sortable && sortColumn === column.key"
                                        x-text="sortDirection === 'asc' ? '▲' : '▼'"
                                        class="text-blue-600"></span>
                                    <!-- Resize handle -->
                                    <div x-show="column.resizable"
                                        class="w-1 h-4 bg-gray-300 cursor-col-resize opacity-0 group-hover:opacity-100 transition-opacity"
                                        x-on:mousedown="startColumnResize($event, column.key)">
                                    </div>
                                </div>
                            </div>
                        </th>
                    </template>

                    <!-- Actions Column (always visible, sticky) -->
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky right-0 bg-gray-50 z-10">
                        {{ __('messages.actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="file in filteredFiles" :key="file.id">
                    <tr class="hover:bg-gray-50">
                        <!-- Selection Column (always visible, sticky) -->
                        <td class="px-6 py-4 whitespace-nowrap sticky left-0 bg-white z-10">
                            <input type="checkbox" :value="file.id" x-model="selectedFiles"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                        </td>

                        <!-- Dynamic Columns -->
                        <template x-for="column in visibleColumnsList" :key="column.key">
                            <td :class="getColumnCellClass(column)"
                                :style="getColumnStyle(column.key)"
                                x-html="getCellContent(file, column)"></td>
                        </template>

                        <!-- Actions Column (always visible, sticky) -->
                        <td
                            class="px-6 py-4 text-sm font-medium whitespace-nowrap sticky right-0 bg-white z-10">
                            <div class="flex items-center space-x-2">
                                <button x-show="file.can_preview" x-on:click="previewFile(file)"
                                    class="text-blue-600 hover:text-blue-900">
                                    {{ __('messages.preview') }}
                                </button>
                                <button x-on:click="downloadFile(file)"
                                    class="text-green-600 hover:text-green-900">
                                    {{ __('messages.download') }}
                                </button>
                                <!-- Retry button for recoverable errors -->
                                <button x-show="file.cloud_storage_error_type && isErrorRecoverable(file)"
                                        x-on:click="retryFileUpload(file)"
                                        :disabled="isRetryingFile(file.id)"
                                        class="text-orange-600 hover:text-orange-900 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span x-show="!isRetryingFile(file.id)">{{ __('messages.retry') }}</span>
                                    <span x-show="isRetryingFile(file.id)" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ __('messages.retrying') }}
                                    </span>
                                </button>
                                <button x-on:click="deleteFile(file)"
                                    class="text-red-600 hover:text-red-900">
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

<!-- Empty State for Table View -->
<div x-show="viewMode === 'table' && filteredFiles.length === 0" class="text-center py-12">
    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
        viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
        </path>
    </svg>
    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('messages.no_files_found') }}</h3>
    <p class="mt-1 text-sm text-gray-500">{{ __('messages.no_files_description') }}</p>
</div>