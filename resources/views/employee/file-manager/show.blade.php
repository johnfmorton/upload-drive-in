<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('File Details') }}
            </h2>
            <a href="{{ route('employee.file-manager.index', ['username' => auth()->user()->username]) }}" 
               class="text-blue-600 hover:text-blue-900">
                ← Back to File Manager
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
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

                    <!-- File Information -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- File Preview -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">File Preview</h3>
                            <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                                @if(str_starts_with($file->mime_type, 'image/'))
                                    <img src="{{ route('files.preview', $file) }}" 
                                         alt="{{ $file->original_filename }}" 
                                         class="max-w-full h-auto rounded">
                                @elseif($file->mime_type === 'application/pdf')
                                    <iframe src="{{ route('files.preview', $file) }}" 
                                            class="w-full h-96 rounded"></iframe>
                                @else
                                    <div class="text-center py-8">
                                        <div class="text-4xl text-gray-400 mb-2">📄</div>
                                        <p class="text-gray-600">Preview not available for this file type</p>
                                        <a href="{{ route('files.preview', $file) }}" 
                                           target="_blank" 
                                           class="text-blue-600 hover:text-blue-900 mt-2 inline-block">
                                            Open in new tab
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- File Details -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">File Information</h3>
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Filename</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $file->original_filename }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">File Size</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ format_bytes($file->file_size) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">File Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $file->mime_type }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Uploaded By</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $file->email }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Upload Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $file->created_at->format('M j, Y g:i A') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1">
                                        @if($file->google_drive_file_id)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Uploaded to Drive
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Processing
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                                @if($file->message)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Message</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $file->message }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="flex flex-wrap gap-4">
                            <a href="{{ route('employee.file-manager.download', ['username' => auth()->user()->username, 'file' => $file]) }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Download File
                            </a>
                            <a href="{{ route('files.preview', $file) }}" 
                               target="_blank"
                               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Open Preview
                            </a>
                            <form method="POST" 
                                  action="{{ route('employee.file-manager.destroy', ['username' => auth()->user()->username, 'file' => $file]) }}" 
                                  class="inline"
                                  onsubmit="return confirm('Are you sure you want to delete this file? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Delete File
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>