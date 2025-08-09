<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('File Manager') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Search and Filter Form -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <form method="GET" action="{{ route('employee.file-manager.index', ['username' => auth()->user()->username]) }}" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                                    <input type="text" id="search" name="search" value="{{ request('search') }}" 
                                           placeholder="Filename, email, or message..." 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500/50">
                                </div>
                                <div>
                                    <label for="date_from" class="block text-sm font-medium text-gray-700">From Date</label>
                                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500/50">
                                </div>
                                <div>
                                    <label for="date_to" class="block text-sm font-medium text-gray-700">To Date</label>
                                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500/50">
                                </div>
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                    <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500/50">
                                        <option value="">All Files</option>
                                        <option value="uploaded" {{ request('status') === 'uploaded' ? 'selected' : '' }}>Uploaded to Drive</option>
                                        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex justify-end space-x-2">
                                <a href="{{ route('employee.file-manager.index', ['username' => auth()->user()->username]) }}" 
                                   class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                                    Clear
                                </a>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Filter
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Files Table -->
                    @if($files->count() > 0)
                        <div x-data="{ 
                            selectedFiles: [], 
                            selectAll: false,
                            allFileIds: {{ $files->pluck('id')->toJson() }},
                            toggleSelectAll() {
                                if (this.selectAll) {
                                    this.selectedFiles = [...this.allFileIds];
                                } else {
                                    this.selectedFiles = [];
                                }
                            }
                        }" x-init="
                            $watch('selectedFiles', () => {
                                selectAll = selectedFiles.length === allFileIds.length && allFileIds.length > 0;
                            })
                        ">
                            <!-- Bulk Actions -->
                            <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <label class="flex items-center space-x-2">
                                            <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                            <span class="text-sm text-gray-700">Select All</span>
                                        </label>
                                        <span class="text-sm text-gray-500" x-text="selectedFiles.length + ' files selected'"></span>
                                    </div>
                                    <div class="flex space-x-2" x-show="selectedFiles.length > 0">
                                        <form method="POST" action="{{ route('employee.file-manager.bulk-download', ['username' => auth()->user()->username]) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="file_ids" :value="JSON.stringify(selectedFiles)">
                                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                                                Download Selected
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('employee.file-manager.bulk-destroy', ['username' => auth()->user()->username]) }}" class="inline" 
                                              onsubmit="return confirm('Are you sure you want to delete the selected files?')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="file_ids" :value="JSON.stringify(selectedFiles)">
                                            <button type="submit" class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                                                Delete Selected
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($files as $file)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox" 
                                                       value="{{ $file->id }}" 
                                                       x-model="selectedFiles" 
                                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <img class="h-10 w-10 rounded" src="{{ route('employee.file-manager.thumbnail', ['username' => auth()->user()->username, 'file' => $file]) }}" alt="File thumbnail">
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <a href="{{ route('employee.file-manager.show', ['username' => auth()->user()->username, 'file' => $file]) }}" 
                                                               class="text-blue-600 hover:text-blue-900">
                                                                {{ $file->original_filename }}
                                                            </a>
                                                        </div>
                                                        @if($file->message)
                                                            <div class="text-sm text-gray-500">{{ Str::limit($file->message, 50) }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $file->email }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ format_bytes($file->file_size) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($file->google_drive_file_id)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Uploaded to Drive
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Processing
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $file->created_at->format('M j, Y g:i A') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <a href="{{ route('employee.file-manager.preview', ['username' => auth()->user()->username, 'file' => $file]) }}" 
                                                       target="_blank" 
                                                       class="text-blue-600 hover:text-blue-900">
                                                        Preview
                                                    </a>
                                                    <a href="{{ route('employee.file-manager.download', ['username' => auth()->user()->username, 'file' => $file]) }}" 
                                                       class="text-green-600 hover:text-green-900">
                                                        Download
                                                    </a>
                                                    <form method="POST" 
                                                          action="{{ route('employee.file-manager.destroy', ['username' => auth()->user()->username, 'file' => $file]) }}" 
                                                          class="inline"
                                                          onsubmit="return confirm('Are you sure you want to delete this file?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            </div>

                            <!-- Pagination -->
                            <div class="mt-6">
                                {{ $files->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No files found</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                @if(request()->hasAny(['search', 'date_from', 'date_to', 'status']))
                                    Try adjusting your search criteria.
                                @else
                                    Files uploaded to you will appear here.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>