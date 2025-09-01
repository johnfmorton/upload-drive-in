@props(['show' => 'showPrimaryContactConfirmation'])

<!-- Primary Contact Change Confirmation Modal -->
<div x-show="{{ $show }}" 
     x-cloak
     class="fixed inset-0 z-[9999] overflow-y-auto"
     aria-labelledby="primary-contact-modal-title" 
     role="dialog" 
     aria-modal="true"
     data-modal-name="primary-contact-confirmation"
     data-z-index="9999"
     data-modal-type="container">
    
    <!-- Background Overlay -->
    <div x-show="{{ $show }}"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 modal-backdrop transition-opacity z-[9998]"
         x-on:click="closePrimaryContactConfirmation()"
         data-modal-name="primary-contact-confirmation"
         data-z-index="9998"
         data-modal-type="backdrop"></div>

    <!-- Modal Panel -->
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div x-show="{{ $show }}"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-[10000] relative"
             data-modal-name="primary-contact-confirmation"
             data-z-index="10000"
             data-modal-type="content">
            
            <!-- Modal Content -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <!-- Warning Icon -->
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    
                    <!-- Modal Content -->
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="primary-contact-modal-title">
                            {{ __('messages.change_primary_contact_modal_title') }}
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                {!! __('messages.change_primary_contact_modal_text', ['name' => '<strong x-text="newPrimaryContact || \'this person\'"></strong>']) !!}
                            </p>
                            <div class="mt-3 p-3 bg-blue-50 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-blue-800">
                                            {{ __('messages.what_this_means') }}
                                        </h4>
                                        <div class="mt-1 text-sm text-blue-700">
                                            <ul class="list-disc list-inside space-y-1">
                                                <li>{{ __('messages.primary_contact_responsibility_uploads') }}</li>
                                                <li>{{ __('messages.primary_contact_responsibility_notifications') }}</li>
                                                <li>{{ __('messages.primary_contact_responsibility_unique') }}</li>
                                            </ul>
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
                <button x-on:click="confirmPrimaryContactChange()"
                        type="button"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm">
                    {{ __('messages.yes_change_primary_contact') }}
                </button>
                
                <button x-on:click="closePrimaryContactConfirmation()"
                        type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    {{ __('messages.action_cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>