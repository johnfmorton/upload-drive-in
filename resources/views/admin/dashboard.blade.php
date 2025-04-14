<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.admin_dashboard_title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Google Drive Connection Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ __('messages.google_drive_connection_title') }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('messages.google_drive_connection_description') }}
                    </p>

                    <div class="mt-6">
                        @if (Storage::exists('google-credentials.json'))
                            <div class="flex items-center gap-4">
                                <p class="text-sm text-green-600">{{ __('messages.google_drive_connected') }}</p>
                                <form action="{{ route('google-drive.disconnect') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        {{ __('messages.google_drive_disconnect_button') }}
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="flex items-center gap-4">
                                <p class="text-sm text-red-600">{{ __('messages.google_drive_not_connected') }}</p>
                                <a href="{{ route('google-drive.connect') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ __('messages.google_drive_connect_button') }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Files Table Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div x-data="{
                        filesData: {{ json_encode($files->items()) }},
                        filterQuery: '',
                        sortColumn: 'created_at', // Default sort column
                        sortDirection: 'desc', // Default sort direction ('asc' or 'desc')
                        fileIdToDelete: null,
                        columns: $persist({
                            fileName: true,
                            user: true,
                            size: true,
                            status: true,
                            message: true,
                            uploadedAt: true,
                            actions: true
                        }).as('adminFileColumns'),

                        get filteredAndSortedFiles() {
                            let filtered = this.filesData;

                            // Apply filter
                            if (this.filterQuery.trim() !== '') {
                                const query = this.filterQuery.trim().toLowerCase();
                                filtered = filtered.filter(file => {
                                    return (file.original_filename && file.original_filename.toLowerCase().includes(query)) ||
                                           (file.email && file.email.toLowerCase().includes(query)) ||
                                           (file.message && file.message.toLowerCase().includes(query));
                                });
                            }

                            // Apply sort
                            if (this.sortColumn) {
                                filtered.sort((a, b) => {
                                    let valA = a[this.sortColumn];
                                    let valB = b[this.sortColumn];

                                    // Handle specific types if needed (e.g., dates, numbers)
                                    if (this.sortColumn === 'file_size') {
                                        valA = parseInt(valA);
                                        valB = parseInt(valB);
                                    } else if (this.sortColumn === 'created_at') {
                                        // Dates are usually comparable as strings (ISO format)
                                        // If not, parse them: valA = new Date(valA); valB = new Date(valB);
                                    } else if (valA && typeof valA === 'string') {
                                        valA = valA.toLowerCase();
                                        valB = valB.toLowerCase();
                                    }

                                    let comparison = 0;
                                    if (valA > valB) {
                                        comparison = 1;
                                    } else if (valA < valB) {
                                        comparison = -1;
                                    }
                                    return this.sortDirection === 'asc' ? comparison : -comparison;
                                });
                            }

                            return filtered;
                        },

                        sortBy(column) {
                            if (this.sortColumn === column) {
                                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                            } else {
                                this.sortColumn = column;
                                this.sortDirection = 'asc'; // Default to ascending when changing column
                            }
                        },

                        // Helper to format file size (moved here from blade for consistency)
                        formatSize(bytes) {
                            if (bytes === 0) return '0 Bytes';
                            const k = 1024;
                            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                            const i = Math.floor(Math.log(bytes) / Math.log(k));
                            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                        },

                        // Helper to format date (moved here for consistency)
                         formatDate(dateString) {
                            const options = { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' };
                            return new Date(dateString).toLocaleString(undefined, options);
                        },

                        performDelete(id) {
                            if (!id) return;
                            console.log('Confirmed deletion for file ID:', id);

                            let form = document.createElement('form');
                            form.method = 'POST';
                            form.action = '{{ url('admin/files') }}/' + id;
                            form.style.display = 'none';

                            let csrfInput = document.createElement('input');
                            csrfInput.type = 'hidden';
                            csrfInput.name = '_token';
                            csrfInput.value = '{{ csrf_token() }}';
                            form.appendChild(csrfInput);

                            let methodInput = document.createElement('input');
                            methodInput.type = 'hidden';
                            methodInput.name = '_method';
                            methodInput.value = 'DELETE';
                            form.appendChild(methodInput);

                            document.body.appendChild(form);
                            form.submit();
                        },

                        openDeleteModal(id) {
                            this.fileIdToDelete = id;
                            $dispatch('open-modal', 'confirm-delete');
                        },

                        confirmDelete() {
                            this.performDelete(this.fileIdToDelete);
                            this.fileIdToDelete = null;
                            $dispatch('close');
                        }
                    }"
                    class="max-w-full">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        {{ __('messages.uploaded_files_title') }}
                    </h2>

                    <!-- Column Visibility Controls -->
                    <div class="mb-4 p-4 border rounded bg-gray-50 hidden lg:block">
                        <h3 class="text-md font-medium text-gray-700 mb-2">{{ __('messages.toggle_columns_title') }}</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.fileName" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>{{ __('messages.column_file_name') }}</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.user" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>{{ __('messages.column_user') }}</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.size" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>{{ __('messages.column_size') }}</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.status" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>{{ __('messages.column_status') }}</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.message" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>{{ __('messages.column_message') }}</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.uploadedAt" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>{{ __('messages.column_uploaded_at') }}</span>
                            </label>
                             <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.actions" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>{{ __('messages.column_actions') }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Filter Input -->
                    <div class="mb-4">
                        <label for="fileFilter" class="sr-only">{{ __('messages.filter_files_label') }}</label>
                        <input type="text" id="fileFilter" x-model.debounce.300ms="filterQuery" placeholder="{{ __('messages.filter_placeholder') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm">
                    </div>

                    <!-- Mobile Card View -->
                    <div class="lg:hidden space-y-4">
                        <template x-for="file in filteredAndSortedFiles" :key="file.id">
                            <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                                <!-- Header Section -->
                                <div class="border-b border-gray-200 pb-3 mb-3">
                                    <div class="flex justify-between items-start gap-2">
                                        <div class="font-medium text-gray-900 break-words" x-text="file.original_filename"></div>
                                        <div class="flex shrink-0">
                                            <div class="flex flex-wrap gap-2">
                                                <template x-if="file.google_drive_file_id">
                                                    <a :href="`https://drive.google.com/file/d/${file.google_drive_file_id}/view`"
                                                       target="_blank"
                                                       class="shrink-0 inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 text-sm font-medium rounded-md hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                       <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                       </svg>
                                                       {{ __('messages.view_button') }}
                                                    </a>
                                                </template>
                                                <button @click="openDeleteModal(file.id)"
                                                    class="shrink-0 inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 text-sm font-medium rounded-md hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                    {{ __('messages.delete_button') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <span x-show="file.google_drive_file_id" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                             {{ __('messages.status_uploaded') }}
                                        </span>
                                        <span x-show="!file.google_drive_file_id" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            {{ __('messages.status_pending') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Main Information -->
                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                        <div class="text-gray-500">{{ __('messages.column_user') }}</div>
                                        <div class="font-medium truncate" x-text="file.email"></div>

                                        <div class="text-gray-500">{{ __('messages.column_size') }}</div>
                                        <div class="font-medium" x-text="formatSize(file.file_size)"></div>

                                        <div class="text-gray-500">{{ __('messages.mobile_label_uploaded_at') }}</div>
                                        <div class="font-medium" x-text="formatDate(file.created_at)"></div>
                                    </div>

                                    <template x-if="file.message">
                                        <div class="pt-3 border-t border-gray-200">
                                            <div class="text-sm">
                                                <div class="text-gray-500 mb-1">{{ __('messages.mobile_label_message') }}</div>
                                                <div class="text-gray-900 break-words" x-text="file.message"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- No results message for mobile -->
                        <template x-if="filteredAndSortedFiles.length === 0">
                            <div class="text-center text-gray-500 py-4">
                                {{ __('messages.no_files_found') }}
                            </div>
                        </template>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden lg:block">
                        <div class="relative border border-gray-200 rounded-lg overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <template x-if="columns.fileName">
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('original_filename')">
                                                    {{ __('messages.column_file_name') }} <span x-show="sortColumn === 'original_filename'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                                </th>
                                            </template>
                                            <template x-if="columns.user">
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('email')">
                                                    {{ __('messages.column_user') }} <span x-show="sortColumn === 'email'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                                </th>
                                            </template>
                                            <template x-if="columns.size">
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('file_size')">
                                                    {{ __('messages.column_size') }} <span x-show="sortColumn === 'file_size'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                                </th>
                                            </template>
                                            <template x-if="columns.message">
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {{ __('messages.column_message') }}
                                                </th>
                                            </template>
                                            <template x-if="columns.status">
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {{ __('messages.column_status') }}
                                                </th>
                                            </template>
                                            <template x-if="columns.uploadedAt">
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('created_at')">
                                                    {{ __('messages.column_uploaded_at') }} <span x-show="sortColumn === 'created_at'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                                </th>
                                            </template>
                                            <template x-if="columns.actions">
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.column_actions') }}</th>
                                            </template>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <template x-for="file in filteredAndSortedFiles" :key="file.id">
                                            <tr>
                                                <template x-if="columns.fileName">
                                                    <td class="px-6 py-4 text-sm text-gray-900">
                                                        {{-- Allow wrapping, remove truncation/max-width --}}
                                                        <div x-text="file.original_filename" :title="file.original_filename"></div>
                                                    </td>
                                                </template>
                                                <template x-if="columns.user">
                                                    <td class="px-6 py-4 text-sm text-gray-900">
                                                        {{-- Keep truncation --}}
                                                        <div class="overflow-hidden text-ellipsis whitespace-nowrap max-w-[200px] lg:max-w-[250px]" x-text="file.email" :title="file.email"></div>
                                                    </td>
                                                </template>
                                                <template x-if="columns.size">
                                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap" x-text="formatSize(file.file_size)"></td>
                                                </template>
                                                <template x-if="columns.message">
                                                    <td class="px-6 py-4 text-sm text-gray-900">
                                                        {{-- Allow wrapping --}}
                                                        <div x-text="file.message" :title="file.message"></div>
                                                    </td>
                                                </template>
                                                <template x-if="columns.status">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span x-show="file.google_drive_file_id" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                             {{ __('messages.status_uploaded') }}
                                                        </span>
                                                        <span x-show="!file.google_drive_file_id" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                             {{ __('messages.status_pending') }}
                                                        </span>
                                                    </td>
                                                </template>
                                                <template x-if="columns.uploadedAt">
                                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap" x-text="formatDate(file.created_at)"></td>
                                                </template>
                                                <template x-if="columns.actions">
                                                    <td class="px-6 py-4 text-sm font-medium">
                                                        <div class="flex flex-wrap gap-2">
                                                            <template x-if="file.google_drive_file_id">
                                                                <a :href="`https://drive.google.com/file/d/${file.google_drive_file_id}/view`"
                                                                   target="_blank"
                                                                   class="shrink-0 inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 text-sm font-medium rounded-md hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                                   <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                                   </svg>
                                                                   {{ __('messages.view_button') }}
                                                                </a>
                                                            </template>
                                                            <button @click="openDeleteModal(file.id)"
                                                                class="shrink-0 inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 text-sm font-medium rounded-md hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                                {{ __('messages.delete_button') }}
                                                            </button>
                                                        </div>
                                                    </td>
                                                </template>
                                            </tr>
                                        </template>
                                        <!-- Message if no files match filter -->
                                        <template x-if="filteredAndSortedFiles.length === 0">
                                            <tr>
                                                <td :colspan="Object.values(columns).filter(v => v).length" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                    {{ __('messages.no_files_found') }}
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        {{ $files->links() }}
                    </div>

                    {{-- Confirmation Modal for Deletion --}}
                    <x-modal name="confirm-delete" :show="false" focusable>
                        <div class="p-6">
                            <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-red-100">
                                <svg class="size-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.008v.008H12v-.008Z" /></svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-base font-semibold leading-6 text-gray-900">{{ __('messages.delete_modal_title') }}</h3>
                                <p class="mt-2 text-sm text-gray-500">{{ __('messages.delete_modal_text') }}</p>
                            </div>
                            <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                <button @click="confirmDelete()" type="button" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:col-start-2">{{ __('messages.delete_modal_confirm_button') }}</button>
                                <button @click="$dispatch('close')" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0">{{ __('messages.delete_modal_cancel_button') }}</button>
                            </div>
                        </div>
                    </x-modal>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
