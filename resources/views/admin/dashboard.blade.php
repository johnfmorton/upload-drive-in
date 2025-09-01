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
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6 shadow-sm">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                        <li>Connect your Google account to the Google Drive application. See the
                                            Google Drive Connection.</li>
                                        <li>Customize your company name, logo, etc., using the branding settings page.
                                        </li>
                                        <li>Share your upload link with clients to start receiving files</li>
                                        <li>Create employee accounts for team members</li>

                                    </ul>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <a href="{{ route('admin.cloud-storage.index') }}"
                                    class="inline-flex items-center px-3 py-2 border border-blue-300 rounded-md text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10">
                                        </path>
                                    </svg>
                                    Cloud Storage Settings
                                </a>
                                <a href="{{ route('admin.employees.index') }}"
                                    class="inline-flex items-center px-3 py-2 border border-blue-300 rounded-md text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z">
                                        </path>
                                    </svg>
                                    Manage Employees
                                </a>
                                <a href="{{ route('admin.settings.edit') }}"
                                    class="inline-flex items-center px-3 py-2 border border-blue-300 rounded-md text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            
            <!-- Google Drive Connection -->
            <x-dashboard.google-drive-connection :user="Auth::user()" :is-admin="true" />
            
            <!-- Dashboard Statistics Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Primary Contact Statistics -->
                <x-dashboard.primary-contact-stats :user="Auth::user()" :is-admin="true" />
                
                <!-- Client Relationships -->
                <div class="lg:col-span-1">
                    <x-dashboard.client-relationships :user="Auth::user()" :is-admin="true" />
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
                            $user = Auth::user();
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

                        @if ($pendingCount > 0)
                            <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                {{ $pendingCount }} pending
                            </span>
                        @endif

                        <a href="{{ route('admin.file-manager.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <div x-data="{ 
                                showProcessPendingModal: false,
                                pendingCount: {{ $pendingCount }},
                                isProcessingPending: false,
                                processingResults: null,
                                
                                openProcessPendingModal() {
                                    console.log('🔍 Opening process pending modal');
                                    this.showProcessPendingModal = true;
                                    this.processingResults = null;
                                },
                                
                                closeProcessPendingModal() {
                                    console.log('🔍 Closing process pending modal');
                                    this.showProcessPendingModal = false;
                                    this.isProcessingPending = false;
                                    this.processingResults = null;
                                },
                                
                                async confirmProcessPending() {
                                    console.log('🔍 Confirm process pending called');
                                    if (this.isProcessingPending) {
                                        console.log('🔍 Returning early - already processing');
                                        return;
                                    }
                                    
                                    console.log('🔍 Starting process pending operation');
                                    this.isProcessingPending = true;
                                    
                                    try {
                                        const response = await fetch('{{ route('admin.files.process-pending') }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                                                'Accept': 'application/json'
                                            }
                                        });
                                        
                                        const result = await response.json();
                                        
                                        if (response.ok) {
                                            console.log('🔍 Process pending successful:', result);
                                            this.processingResults = {
                                                success: true,
                                                message: result.message || 'Pending uploads have been queued for processing.'
                                            };
                                            
                                            // Auto-close modal after 3 seconds on success
                                            setTimeout(() => {
                                                this.closeProcessPendingModal();
                                                // Refresh the page to show updated counts
                                                window.location.reload();
                                            }, 3000);
                                        } else {
                                            throw new Error(result.message || 'Failed to process pending uploads');
                                        }
                                    } catch (error) {
                                        console.error('🔍 Process pending failed:', error);
                                        this.processingResults = {
                                            success: false,
                                            message: error.message || 'Failed to process pending uploads'
                                        };
                                    } finally {
                                        this.isProcessingPending = false;
                                    }
                                }
                            }">
                                <button x-on:click="openProcessPendingModal()"
                                    class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Process {{ $pendingCount }} Pending
                                </button>
                                
                                <!-- Process Pending Modal -->
                                <div x-show="showProcessPendingModal" 
                                     x-cloak
                                     class="fixed inset-0 z-[9999] overflow-y-auto"
                                     aria-labelledby="process-pending-modal-title" 
                                     role="dialog" 
                                     aria-modal="true"
                                     data-modal-name="admin-process-pending-modal"
                                     data-z-index="9999"
                                     data-modal-type="container">
                                    
                                    <!-- Background overlay -->
                                    <div x-show="showProcessPendingModal"
                                         x-transition:enter="ease-out duration-300"
                                         x-transition:enter-start="opacity-0"
                                         x-transition:enter-end="opacity-100"
                                         x-transition:leave="ease-in duration-200"
                                         x-transition:leave-start="opacity-100"
                                         x-transition:leave-end="opacity-0"
                                         class="fixed inset-0 modal-backdrop transition-opacity z-[9998]"
                                         x-on:click="closeProcessPendingModal()"
                                         data-modal-name="admin-process-pending-modal"
                                         data-z-index="9998"
                                         data-modal-type="backdrop"></div>

                                    <!-- Modal Panel -->
                                    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                        
                                        <div x-show="showProcessPendingModal"
                                             x-transition:enter="ease-out duration-300"
                                             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                             x-transition:leave="ease-in duration-200"
                                             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                             class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-[10000] relative"
                                             data-modal-name="admin-process-pending-modal"
                                             data-z-index="10000"
                                             data-modal-type="content">
                                            
                                            <!-- Modal Content -->
                                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                <div class="sm:flex sm:items-start">
                                                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                        </svg>
                                                    </div>
                                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                                        <h3 id="process-pending-modal-title" class="text-lg leading-6 font-medium text-gray-900">
                                                            Process Pending Uploads
                                                        </h3>
                                                        <div class="mt-2">
                                                            <!-- Processing state content -->
                                                            <div x-show="!isProcessingPending && !processingResults">
                                                                <p class="text-sm text-gray-500">
                                                                    <span x-text="`You have ${pendingCount} pending upload${pendingCount === 1 ? '' : 's'} that need to be processed.`"></span>
                                                                </p>
                                                                <p class="text-sm text-gray-500 mt-2">
                                                                    This will attempt to upload all pending files to Google Drive. The process may take a few moments depending on file sizes.
                                                                </p>
                                                            </div>
                                                            
                                                            <!-- Processing progress -->
                                                            <div x-show="isProcessingPending">
                                                                <div class="flex items-center space-x-3">
                                                                    <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                                    </svg>
                                                                    <span class="text-sm text-gray-700">Processing pending uploads...</span>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Results display -->
                                                            <div x-show="processingResults">
                                                                <div x-show="processingResults && processingResults.success" class="rounded-md bg-green-50 p-4">
                                                                    <div class="flex">
                                                                        <div class="flex-shrink-0">
                                                                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                                            </svg>
                                                                        </div>
                                                                        <div class="ml-3">
                                                                            <p class="text-sm font-medium text-green-800" x-text="processingResults.message"></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div x-show="processingResults && !processingResults.success" class="rounded-md bg-red-50 p-4">
                                                                    <div class="flex">
                                                                        <div class="flex-shrink-0">
                                                                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                                            </svg>
                                                                        </div>
                                                                        <div class="ml-3">
                                                                            <p class="text-sm font-medium text-red-800" x-text="processingResults.message"></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Modal Actions -->
                                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                                <!-- Process Button -->
                                                <button x-show="!isProcessingPending && !processingResults"
                                                        x-on:click="confirmProcessPending()"
                                                        type="button"
                                                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                                    Process Uploads
                                                </button>
                                                
                                                <!-- Processing indicator (replaces process button) -->
                                                <div x-show="isProcessingPending" 
                                                     class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white sm:ml-3 sm:w-auto sm:text-sm opacity-75 cursor-not-allowed">
                                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Processing...
                                                </div>
                                                
                                                <!-- Close Button (changes based on state) -->
                                                <button x-on:click="closeProcessPendingModal()"
                                                        :disabled="isProcessingPending"
                                                        type="button"
                                                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <span x-show="!processingResults">Cancel</span>
                                                    <span x-show="processingResults">Close</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                    <div class="flex items-center">
                        <button id="test-queue-btn" type="button"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span id="test-queue-btn-text">Test Queue Worker</span>
                        </button>
                    </div>
                </div>

                <!-- Test Results Section -->
                <div id="test-results-section" class="hidden">
                    <div data-class="border-t border-gray-200 pt-6">
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
            </div>

        </div>
    </div>
</x-app-layout>
