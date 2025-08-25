<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.admin_dashboard_title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($isFirstTimeLogin)
                <!-- Welcome Message for First-Time Login -->
                    <div
                        class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6 shadow-sm">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-semibold text-blue-900">
                                    🎉 Welcome to Upload Drive-in!
                                </h3>
                                <div class="mt-2 text-blue-800">
                                    <p class="mb-3">
                                        Congratulations! Your Upload Drive-in application has been successfully
                                        configured and is ready to use.
                                    </p>
                                    <div class="space-y-2">
                                        <h4 class="font-medium">Next steps to get started:</h4>
                                        <ul class="list-disc list-inside space-y-1 text-sm">
                                            <li>Share your upload link with clients to start receiving files</li>
                                            <li>Configure additional cloud storage providers if needed</li>
                                            <li>Create employee accounts for team members</li>
                                            <li>Customize your application settings and branding</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-3">
                                    <a href="{{ route('admin.cloud-storage.index') }}"
                                        class="inline-flex items-center px-3 py-2 border border-blue-300 rounded-md text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10">
                                            </path>
                                        </svg>
                                        Cloud Storage Settings
                                    </a>
                                    <a href="{{ route('admin.employees.index') }}"
                                        class="inline-flex items-center px-3 py-2 border border-blue-300 rounded-md text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z">
                                            </path>
                                        </svg>
                                        Manage Employees
                                    </a>
                                    <a href="{{ route('admin.settings.edit') }}"
                                        class="inline-flex items-center px-3 py-2 border border-blue-300 rounded-md text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                            </path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        Application Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <!-- Client-Company User Relationships -->
                <x-dashboard.client-relationships :user="Auth::user()" :is-admin="true" />

                <!-- Google Drive Connection -->
                <x-dashboard.google-drive-connection :user="Auth::user()" :is-admin="true" />

                <!-- Queue Worker Testing Section -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div>
                            <h2 class="text-lg font-medium text-gray-900">
                                Queue Worker Status
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Test and monitor your background job processing system to ensure files are uploaded
                                properly.
                            </p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button id="test-queue-btn" type="button"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span id="test-queue-btn-text">Test Queue Worker</span>
                            </button>
                            <button id="refresh-queue-health-btn" type="button"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                    </path>
                                </svg>
                                Refresh Status
                            </button>
                        </div>
                    </div>

                    <!-- Queue Health Overview -->
                    <div id="queue-health-overview" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-500">Queue Status</div>
                                    <div id="queue-status" class="text-2xl font-bold text-gray-900">Loading...</div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-500">Recent Activity</div>
                                    <div id="recent-jobs-count" class="text-2xl font-bold text-gray-900">-</div>
                                    <div class="text-xs text-gray-400 mt-1" id="recent-jobs-description">Test jobs
                                        (1h)</div>

                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-500">Failed Jobs</div>
                                        <div id="failed-jobs-count" class="text-2xl font-bold text-gray-900">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Failed Jobs Details Section -->
                        <div id="failed-jobs-details-section" class="hidden">
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-600 mt-0.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <h3 class="text-sm font-medium text-red-900 mb-2">Recent Failed Jobs</h3>
                                        <div id="failed-jobs-list" class="space-y-2">
                                            <!-- Failed job details will be inserted here -->
                                        </div>
                                        <div class="mt-3 text-xs text-red-700">
                                            <strong>Tip:</strong> Use <code class="bg-red-100 px-1 rounded">ddev
                                                artisan queue:failed</code> to see all failed jobs, or <code
                                                class="bg-red-100 px-1 rounded">ddev artisan queue:retry all</code> to
                                            retry them.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Test Results Section -->
                        <div id="test-results-section" class="hidden">
                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-md font-medium text-gray-900 mb-4">Test Results</h3>

                                <!-- Current Test Progress -->
                                <div id="current-test-progress" class="hidden mb-4">
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <svg class="animate-spin h-5 w-5 text-blue-600" fill="none"
                                                    viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                    </path>
                                                </svg>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <div class="text-sm font-medium text-blue-900">
                                                    Testing queue worker...
                                                </div>
                                                <div class="text-sm text-blue-700">
                                                    <span id="test-progress-message">Dispatching test job</span>
                                                    <span id="test-elapsed-time" class="ml-2 font-mono"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Test Results Display -->
                                <div id="test-results-display" class="space-y-3">
                                    <!-- Test results will be dynamically inserted here -->
                                </div>
                            </div>
                        </div>

                        <!-- Historical Test Results -->
                        <div id="historical-results-section" class="hidden">
                            <div class="border-t border-gray-200 pt-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-md font-medium text-gray-900">Recent Test History</h3>
                                    <button id="clear-test-history-btn" type="button"
                                        class="text-sm text-gray-500 hover:text-gray-700">
                                        Clear History
                                    </button>
                                </div>
                                <div id="historical-results-list" class="space-y-2">
                                    <!-- Historical results will be dynamically inserted here -->
                                </div>
                            </div>
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

                                @if ($pendingCount > 0)
                                    <span
                                        class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                        {{ $pendingCount }} pending
                                    </span>
                                @endif

                                <a href="{{ route('admin.file-manager.index') }}"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
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
                                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
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
                                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-500">Uploaded</div>
                                        <div class="text-2xl font-bold text-gray-900">{{ $totalFiles - $pendingCount }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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
                                @if ($pendingCount > 0)
                                    <form action="{{ route('admin.files.process-pending') }}" method="POST"
                                        class="inline">
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
                            @if ($files->count() > 0)
                                <div class="bg-gray-50 rounded-lg overflow-hidden">
                                    <div class="px-4 py-3 border-b border-gray-200">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-medium text-gray-900">Latest 5 Files</span>
                                            <a href="{{ route('admin.file-manager.index') }}"
                                                class="text-sm text-blue-600 hover:text-blue-800">
                                                View All →
                                            </a>
                                        </div>
                                    </div>
                                    <div class="divide-y divide-gray-200">
                                        @foreach ($files->take(5) as $file)
                                            <div class="px-4 py-3 flex items-center justify-between">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center space-x-3">
                                                        <div class="flex-shrink-0">
                                                            @if ($file->google_drive_file_id)
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
                                                                {{ $file->email }} •
                                                                {{ format_bytes($file->file_size) }} •
                                                                {{ $file->created_at->diffForHumans() }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    @if ($file->google_drive_file_id)
                                                        <span
                                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            Uploaded
                                                        </span>
                                                    @else
                                                        <span
                                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
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
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No files uploaded yet</h3>
                                    <p class="mt-1 text-sm text-gray-500">Files uploaded by clients will appear here.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
        </div>



</x-app-layout>
