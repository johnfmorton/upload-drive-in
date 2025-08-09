<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Employee Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Client Relationships -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="text-lg font-medium text-gray-900">Client Relationships</h2>
                    <p class="mt-1 text-sm text-gray-600">View and manage your client relationships.</p>

                    <div class="mt-6">
                        @foreach($user->clientUsers as $clientUser)
                            <div class="border-b border-gray-200 py-4 last:border-b-0">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900">{{ $clientUser->name }}</h3>
                                        <p class="text-sm text-gray-500">{{ $clientUser->email }}</p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if($clientUser->pivot->is_primary)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Primary
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if($user->clientUsers->isEmpty())
                            <p class="text-sm text-gray-500">No client relationships found.</p>
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
                            <a href="{{ route('employee.cloud-storage.index', ['username' => $user->username]) }}" 
                               class="text-blue-500 hover:text-blue-700">
                                Configure Google Drive storage settings and connection details.
                            </a>
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        @if($user->hasGoogleDriveConnected())
                            <span class="px-3 py-1 text-sm text-green-800 bg-green-100 rounded-full">Connected</span>
                            <form action="{{ route('employee.google-drive.disconnect', ['username' => $user->username]) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    Disconnect
                                </button>
                            </form>
                        @else
                            <span class="px-3 py-1 text-sm text-gray-800 bg-gray-100 rounded-full">Not Connected</span>
                            <a href="{{ route('employee.google-drive.connect', ['username' => $user->username]) }}" 
                               class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Connect
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- File Management Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900">
                            Uploaded Files
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Manage uploaded files with advanced filtering, bulk operations, and more.
                        </p>
                    </div>
                    <div class="flex items-center space-x-3">
                        @php
                            $totalFiles = \App\Models\FileUpload::where(function($query) use ($user) {
                                $query->where('company_user_id', $user->id)
                                      ->orWhere('uploaded_by_user_id', $user->id);
                            })->count();
                            
                            $pendingCount = \App\Models\FileUpload::where(function($query) use ($user) {
                                $query->where('company_user_id', $user->id)
                                      ->orWhere('uploaded_by_user_id', $user->id);
                            })->pending()->count();
                        @endphp
                        
                        <div class="text-sm text-gray-500">
                            <span class="font-medium">{{ $totalFiles }}</span> total files
                        </div>
                        
                        @if($pendingCount > 0)
                            <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                {{ $pendingCount }} pending
                            </span>
                        @endif
                        
                        <a href="{{ route('employee.file-manager.index', ['username' => $user->username]) }}" 
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
                    </div>

                    <!-- Simple Recent Files List -->
                    @if($files->count() > 0)
                        <div class="bg-gray-50 rounded-lg overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-900">Latest 5 Files</span>
                                    <a href="{{ route('employee.file-manager.index', ['username' => $user->username]) }}" class="text-sm text-blue-600 hover:text-blue-800">
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
                            <p class="mt-1 text-sm text-gray-500">Files uploaded to you will appear here.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
