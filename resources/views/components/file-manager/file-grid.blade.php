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

<!-- File Grid View -->
<div x-show="viewMode === 'grid' && filteredFiles.length > 0"
    class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 p-4 sm:p-6">
    <template x-for="(file, index) in filteredFiles" :key="file.id">
        <div
            class="bg-white rounded-lg shadow overflow-hidden border border-gray-200 hover:shadow-md transition-shadow duration-200 flex flex-col h-full">
            <!-- File Selection Checkbox -->
            <div class="p-3 border-b border-gray-100">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" :value="file.id" x-model="selectedFiles"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    <span class="text-xs text-gray-500">{{ __('messages.select_file') }}</span>
                </label>
            </div>

            <!-- File Preview -->
            <div class="aspect-w-16 aspect-h-9">
                <div class="flex items-center justify-center p-4">
                    <template x-if="file.can_preview && file.thumbnail_url">
                        <img :src="file.thumbnail_url + (file.thumbnail_url.includes('?') ? '&' : '?') + 'v=' + (file.updated_at ? new Date(file.updated_at).getTime() : Date.now())" 
                            :alt="file.original_filename"
                            class="max-w-full max-h-full object-contain rounded checkerboard-bg"
                            x-on:click="previewFile(file)" 
                            style="cursor: pointer;">
                    </template>
                    <template x-if="!file.can_preview || !file.thumbnail_url">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <p class="mt-2 text-xs text-gray-500"
                                x-text="getFileExtension(file.original_filename)"></p>
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
                    @if($userType === 'admin')
                    <div class="flex justify-between">
                        <span>{{ __('messages.message_label') }}:</span>
                        <span class="truncate ml-1" x-text="file.message || '{{ __('messages.no_message_provided') }}'"></span>
                    </div>
                    @endif
                </div>

                <!-- Status Badge -->
                <div class="mt-3">
                    <span x-show="file.google_drive_file_id"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ __('messages.status_uploaded') }}
                    </span>
                    <span x-show="!file.google_drive_file_id && !file.cloud_storage_error_type"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        {{ __('messages.status_pending') }}
                    </span>
                    <span x-show="file.cloud_storage_error_type"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="getErrorBadgeColor(file.cloud_storage_error_severity)">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ __('messages.status_error') }}
                    </span>
                </div>

                <!-- Cloud Storage Error Details -->
                <div x-show="file.cloud_storage_error_type" class="mt-2 p-2 bg-red-50 rounded-md border border-red-200">
                    <p class="text-xs text-red-800 font-medium" x-text="file.cloud_storage_error_message"></p>
                    <p class="text-xs text-red-600 mt-1" x-text="file.cloud_storage_error_description"></p>
                </div>
            </div>

            <!-- File Actions (footer sticks to bottom) -->
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 rounded-b-lg mt-auto">
                <div class="flex justify-between items-center">
                    <div class="flex space-x-2">
                        <button x-show="file.can_preview" x-on:click="previewFile(file)"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            {{ __('messages.preview') }}
                        </button>
                        <button x-on:click="downloadFile(file)"
                            class="text-xs text-green-600 hover:text-green-800 font-medium">
                            {{ __('messages.download') }}
                        </button>
                        <!-- Retry button for recoverable errors -->
                        <button x-show="file.cloud_storage_error_type && isErrorRecoverable(file)"
                                x-on:click="retryFileUpload(file)"
                                :disabled="isRetryingFile(file.id)"
                                class="text-xs text-orange-600 hover:text-orange-800 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!isRetryingFile(file.id)">{{ __('messages.retry') }}</span>
                            <span x-show="isRetryingFile(file.id)" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('messages.retrying') }}
                            </span>
                        </button>
                    </div>
                    <button x-on:click="deleteFile(file)"
                        class="text-xs text-red-600 hover:text-red-800 font-medium">
                        {{ __('messages.delete') }}
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>