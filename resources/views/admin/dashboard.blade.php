<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.admin_dashboard_title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Client-Company User Relationships -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="text-lg font-medium text-gray-900">{{ __('messages.client_relationships_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-600">{{ __('messages.client_relationships_description') }}</p>

                    <div class="mt-6">
                        @foreach(Auth::user()->clientUsers as $clientUser)
                            <div class="border-b border-gray-200 py-4 last:border-b-0">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900">{{ $clientUser->name }}</h3>
                                        <p class="text-sm text-gray-500">{{ $clientUser->email }}</p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if($clientUser->pivot->is_primary)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ __('messages.primary_client') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if(Auth::user()->clientUsers->isEmpty())
                            <p class="text-sm text-gray-500">{{ __('messages.no_client_relationships') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Google Drive Connection Status -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900">Google Drive</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            <a href="https://console.cloud.google.com/apis/credentials" target="_blank"
                               class="text-blue-500 hover:text-blue-700">
                                {{ __('messages.configure_google_drive_storage_link_description') }}
                            </a>
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        @if(Auth::user()->hasGoogleDriveConnected())
                            <span class="px-3 py-1 text-sm text-green-800 bg-green-100 rounded-full">{{ __('messages.connected') }}</span>
                            <form action="{{ route('admin.cloud-storage.google-drive.disconnect') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    {{ __('messages.disconnect') }}
                                </button>
                            </form>
                        @else
                            <span class="px-3 py-1 text-sm text-gray-800 bg-gray-100 rounded-full">{{ __('messages.not_connected') }}</span>
                            <form action="{{ route('admin.cloud-storage.google-drive.connect') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    {{ __('messages.connect') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- File Management Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900">
                            {{ __('messages.uploaded_files_title') }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Manage uploaded files with advanced filtering, bulk operations, and more.
                        </p>
                    </div>
                    <div class="flex items-center space-x-3">
                        @php
                            $pendingCount = \App\Models\FileUpload::pending()->count();
                            $totalFiles = \App\Models\FileUpload::count();
                        @endphp
                        
                        <div class="text-sm text-gray-500">
                            <span class="font-medium">{{ $totalFiles }}</span> total files
                        </div>
                        
                        @if($pendingCount > 0)
                            <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                {{ $pendingCount }} pending
                            </span>
                        @endif
                        
                        <a href="{{ route('admin.file-manager.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Open File Manager
                        </a>
                    </div>
                </div>

                <!-- Quick File Overview -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-500">Total Files</div>
                                <div class="text-2xl font-bold text-gray-900">{{ $totalFiles }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-500">Uploaded</div>
                                <div class="text-2xl font-bold text-gray-900">{{ $totalFiles - $pendingCount }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-500">Pending</div>
                                <div class="text-2xl font-bold text-gray-900">{{ $pendingCount }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Files Preview -->
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-md font-medium text-gray-900">Recent Files</h3>
                        @if($pendingCount > 0)
                            <form action="{{ route('admin.files.process-pending') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                        onclick="return confirm('Process {{ $pendingCount }} pending uploads? This will queue them for Google Drive upload.')">
                                    Process {{ $pendingCount }} Pending
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Simple Recent Files List -->
                    @if($files->count() > 0)
                        <div class="bg-gray-50 rounded-lg overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-900">Latest 5 Files</span>
                                    <a href="{{ route('admin.file-manager.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                                        View All →
                                    </a>
                                </div>
                            </div>
                            <div class="divide-y divide-gray-200">
                                @foreach($files->take(5) as $file)
                                    <div class="px-4 py-3 flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-3">
                                                <div class="flex-shrink-0">
                                                    @if($file->google_drive_file_id)
                                                        <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                                    @else
                                                        <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                                                    @endif
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate">
                                                        {{ $file->original_filename }}
                                                    </p>
                                                    <p class="text-sm text-gray-500 truncate">
                                                        {{ $file->email }} • {{ format_bytes($file->file_size) }} • {{ $file->created_at->diffForHumans() }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            @if($file->google_drive_file_id)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Uploaded
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Pending
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No files uploaded yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Files uploaded by clients will appear here.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
