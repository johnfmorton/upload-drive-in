<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Google Drive Connection Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="text-lg font-medium text-gray-900">
                        Google Drive Connection
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        Connect to Google Drive to enable automatic file uploads.
                    </p>

                    <div class="mt-6">
                        @if (Storage::exists('google-credentials.json'))
                            <div class="flex items-center gap-4">
                                <p class="text-sm text-green-600">Google Drive is connected</p>
                                <form action="{{ route('google-drive.disconnect') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Disconnect Google Drive
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="flex items-center gap-4">
                                <p class="text-sm text-red-600">Google Drive is not connected</p>
                                <a href="{{ route('google-drive.connect') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Connect Google Drive
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
                        }

                    }" class="max-w-full">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        Uploaded Files
                    </h2>

                    <!-- Column Visibility Controls -->
                    <div class="mb-4 p-4 border rounded bg-gray-50">
                        <h3 class="text-md font-medium text-gray-700 mb-2">Show/Hide Columns:</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.fileName" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>File Name</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.user" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>User</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.size" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>Size</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.status" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>Status</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.message" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>Message</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.uploadedAt" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>Uploaded At</span>
                            </label>
                             <label class="flex items-center space-x-2">
                                <input type="checkbox" x-model="columns.actions" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span>Actions</span>
                            </label>
                        </div>
                    </div>

                    <!-- Filter Input -->
                    <div class="mb-4">
                        <label for="fileFilter" class="sr-only">Filter files</label>
                        <input type="text" id="fileFilter" x-model.debounce.300ms="filterQuery" placeholder="Filter by filename, user, or message..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm">
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <template x-if="columns.fileName">
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('original_filename')">
                                            File Name <span x-show="sortColumn === 'original_filename'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                        </th>
                                    </template>
                                    <template x-if="columns.user">
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('email')">
                                            User <span x-show="sortColumn === 'email'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                        </th>
                                    </template>
                                    <template x-if="columns.size">
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('file_size')">
                                            Size <span x-show="sortColumn === 'file_size'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                        </th>
                                    </template>
                                    <template x-if="columns.status">
                                        <!-- Status might not be directly sortable easily without more data -->
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                    </template>
                                    <template x-if="columns.message">
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('message')">
                                            Message <span x-show="sortColumn === 'message'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                        </th>
                                    </template>
                                    <template x-if="columns.uploadedAt">
                                         <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" @click="sortBy('created_at')">
                                            Uploaded At <span x-show="sortColumn === 'created_at'" x-text="sortDirection === 'asc' ? '▲' : '▼'"></span>
                                        </th>
                                    </template>
                                    <template x-if="columns.actions"><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th></template>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="file in filteredAndSortedFiles" :key="file.id">
                                    <tr>
                                        <template x-if="columns.fileName"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="file.original_filename"></td></template>
                                        <template x-if="columns.user"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="file.email"></td></template>
                                        <template x-if="columns.size"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatSize(file.file_size)"></td></template>
                                        <template x-if="columns.status">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span x-show="file.google_drive_file_id" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Uploaded to Drive
                                                </span>
                                                <span x-show="!file.google_drive_file_id" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Pending
                                                </span>
                                            </td>
                                        </template>
                                        <template x-if="columns.message"><td class="px-6 py-4 whitespace-normal text-sm text-gray-500" x-text="file.message"></td></template>
                                         <template x-if="columns.uploadedAt"><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(file.created_at)"></td></template>
                                        <template x-if="columns.actions">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                <template x-if="file.google_drive_file_id">
                                                     <a :href="`https://drive.google.com/file/d/${file.google_drive_file_id}/view`" target="_blank" class="text-indigo-600 hover:text-indigo-900">View in Drive</a>
                                                </template>
                                                <!-- Note: The delete form needs careful handling in Alpine x-for -->
                                                <!-- Option 1: Keep Blade form (might be simplest if CSRF is main concern) -->
                                                {{-- <form :action="`{{ route('admin.files.destroy', '') }}/${file.id}`" method="POST" class="inline" @submit.prevent="if(confirm('Are you sure?')) $el.submit()">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                                </form> --}}
                                                <!-- Option 2: AJAX delete (requires controller changes) -->
                                                 <button @click="
                                                    if (confirm('Are you sure you want to delete this file?')) {
                                                        // You would typically make an AJAX DELETE request here
                                                        // For now, let's just log it or try submitting a dynamically created form
                                                        console.log('Attempting to delete file ID:', file.id);

                                                        // Example: Submitting a hidden form (requires a form element elsewhere or created dynamically)
                                                        let form = document.createElement('form');
                                                        form.method = 'POST';
                                                        form.action = '{{ url('admin/files') }}/' + file.id; // Construct URL manually or pass base URL
                                                        form.style.display = 'none';

                                                        let csrfInput = document.createElement('input');
                                                        csrfInput.type = 'hidden';
                                                        csrfInput.name = '_token';
                                                        csrfInput.value = '{{ csrf_token() }}'; // Get CSRF token
                                                        form.appendChild(csrfInput);

                                                        let methodInput = document.createElement('input');
                                                        methodInput.type = 'hidden';
                                                        methodInput.name = '_method';
                                                        methodInput.value = 'DELETE';
                                                        form.appendChild(methodInput);

                                                        document.body.appendChild(form);
                                                        form.submit();
                                                    }
                                                " class="text-red-600 hover:text-red-900">Delete</button>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                                <!-- Message if no files match filter -->
                                <template x-if="filteredAndSortedFiles.length === 0">
                                    <tr>
                                        <td :colspan="Object.values(columns).filter(v => v).length" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            No files match your filter criteria.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $files->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
