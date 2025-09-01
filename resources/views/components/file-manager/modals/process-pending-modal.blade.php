<!-- Process Pending Uploads Modal -->
<div x-show="showProcessPendingModal" 
     x-cloak
     class="fixed inset-0 z-[9999] overflow-y-auto"
     aria-labelledby="process-pending-modal-title" 
     role="dialog" 
     aria-modal="true"
     data-modal-name="process-pending-modal"
     data-z-index="9999"
     data-modal-type="container"
     x-init="console.log('🔍 Process pending modal initialized')"
     x-effect="console.log('🔍 showProcessPendingModal changed to:', showProcessPendingModal)">
    
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
         data-modal-name="process-pending-modal"
         data-z-index="9998"
         data-modal-type="backdrop"></div>

    <!-- Modal panel -->
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
             data-modal-name="process-pending-modal"
             data-z-index="10000"
             data-modal-type="content">
            
            <!-- Content -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <!-- Icon -->
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    
                    <!-- Content -->
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 id="process-pending-modal-title" class="text-lg leading-6 font-medium text-gray-900">
                            Process Pending Uploads
                        </h3>
                        <div class="mt-2">
                            <!-- Processing state content -->
                            <div x-show="!isProcessingPending">
                                <p class="text-sm text-gray-500">
                                    <span x-show="pendingCount > 0">
                                        You have <span x-text="pendingCount" class="font-semibold text-blue-600"></span> 
                                        <span x-text="pendingCount === 1 ? 'upload' : 'uploads'"></span> 
                                        waiting to be processed.
                                    </span>
                                    <span x-show="pendingCount === 0">
                                        No pending uploads found.
                                    </span>
                                </p>
                                <p x-show="pendingCount > 0" class="text-sm text-gray-500 mt-2">
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
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Processing uploads...</p>
                                        <p class="text-sm text-gray-500" x-text="processingMessage"></p>
                                    </div>
                                </div>
                                
                                <!-- Progress bar -->
                                <div x-show="processingProgress > 0" class="mt-3">
                                    <div class="bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                             :style="`width: ${processingProgress}%`"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <span x-text="processedCount"></span> of <span x-text="totalCount"></span> processed
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Results summary -->
                            <div x-show="processingResults" class="mt-3">
                                <div x-show="processingResults && processingResults.success" class="flex items-center space-x-2 text-green-700">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-sm font-medium" x-text="processingResults.message"></p>
                                </div>
                                <div x-show="processingResults && !processingResults.success" class="flex items-center space-x-2 text-red-700">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-sm font-medium" x-text="processingResults.message"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <!-- Process Button -->
                <button x-show="!isProcessingPending && !processingResults"
                        x-on:click="console.log('🔍 Process button clicked'); confirmProcessPending()"
                        :disabled="pendingCount === 0"
                        type="button"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Process Now
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