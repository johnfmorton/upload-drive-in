<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">File Upload</h1>
                            <p class="text-gray-600">Upload your files securely</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Logged in as: {{ auth()->user()->email }}</p>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-[var(--brand-color)] hover:brightness-75">
                                    Not you? Sign out
                                </button>
                            </form>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- Dropzone form --}}
                    <form id="messageForm" class="space-y-6">
                         @csrf {{-- Important for CSRF protection --}}

                         {{-- Dropzone Container --}}
                         <div id="file-upload-dropzone"
                              class="dropzone border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-[var(--brand-color)] transition-colors duration-200"
                              data-upload-url="{{ route('chunk.upload') }}">
                             <div class="dz-message" data-dz-message>
                                 <span class="block text-lg font-medium text-gray-700">Drop files here or click to upload.</span>
                                 <span class="block text-sm text-gray-500">(Large files will be uploaded in chunks)</span>
                             </div>
                             {{-- Dropzone will automatically add file previews here --}}
                         </div>

                         {{-- Hidden input to store successful upload IDs --}}
                         <input type="hidden" name="file_upload_ids" id="file_upload_ids" value="[]">

                         {{-- Area to display upload errors --}}
                         <div id="upload-errors" class="hidden mt-4"></div>

                         {{-- Upload Progress Overlay --}}
                         <div id="upload-progress-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center">
                             <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                                 <div class="text-center mb-4">
                                     <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full mb-3">
                                         <svg class="w-6 h-6 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                             <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                             <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                         </svg>
                                     </div>
                                     <h3 class="text-lg font-semibold text-gray-900">{{ __('messages.upload_progress_title') }}</h3>
                                     <p id="progress-status" class="text-sm text-gray-600 mt-1">{{ __('messages.upload_progress_preparing') }}</p>
                                 </div>

                                 {{-- Overall Progress --}}
                                 <div class="mb-4">
                                     <div class="flex items-center justify-between mb-2">
                                         <span class="text-sm font-medium text-gray-700">{{ __('messages.upload_progress_overall') }}</span>
                                         <span id="overall-progress-text" class="text-sm text-gray-600">0%</span>
                                     </div>
                                     <div class="w-full bg-gray-200 rounded-full h-3">
                                         <div id="overall-progress-bar" class="bg-blue-600 h-3 rounded-full transition-all duration-500 ease-out" style="width: 0%"></div>
                                     </div>
                                 </div>

                                 {{-- Individual File Progress --}}
                                 <div class="max-h-48 overflow-y-auto">
                                     <div id="file-progress-container" class="space-y-3">
                                         {{-- Individual file progress bars will be inserted here --}}
                                     </div>
                                 </div>

                                 {{-- Cancel Button (Optional) --}}
                                 <div class="mt-6 text-center">
                                     <button type="button" 
                                             onclick="if(confirm('{{ __('messages.upload_progress_cancel_confirm') }}')) { location.reload(); }"
                                             class="text-sm text-gray-500 hover:text-gray-700 underline">
                                         {{ __('messages.upload_progress_cancel_button') }}
                                     </button>
                                 </div>
                             </div>
                         </div>

                         {{-- Message Textarea --}}
                         <div>
                             <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message (Optional)</label>
                             <textarea id="message" name="message" rows="4"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[var(--brand-color)] focus:border-[var(--brand-color)]"
                                       placeholder="Enter an optional message to associate with the uploaded files..."></textarea>
                         </div>

                         {{-- Submit Button --}}
                         <div class="text-center">
                             <button type="submit"
                                     class="bg-[var(--brand-color)] text-white px-6 py-2 rounded-md hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-color)] transition-colors duration-200">
                                 Upload and Send Message
                             </button>
                         </div>
                    </form>
                    {{-- Uppy handles the upload button, so we remove the original form submit button --}}

                </div>
            </div>
        </div>
    </div>

    {{-- Status Modals --}}
    <x-modal name="upload-success" :show="false" focusable>
        <div class="p-6">
            <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-green-100">
                <svg class="size-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
            </div>
            <div class="mt-3 text-center sm:mt-5">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Upload Complete</h3>
                <p class="mt-2 text-sm text-gray-500">Files uploaded successfully! (No message was entered to associate).</p>
            </div>
            <div class="mt-5 sm:mt-6">
                <button @click="show = false" type="button" class="inline-flex w-full justify-center rounded-md bg-[var(--brand-color)] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:brightness-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--brand-color)]">Close</button>
            </div>
        </div>
    </x-modal>

    <x-modal name="association-success" :show="false" focusable>
        <div class="p-6">
            <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-green-100">
                <svg class="size-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
            </div>
            <div class="mt-3 text-center sm:mt-5">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Success</h3>
                <p class="mt-2 text-sm text-gray-500">Files uploaded and message associated successfully!</p>
            </div>
            <div class="mt-5 sm:mt-6">
                <button @click="show = false" type="button" class="inline-flex w-full justify-center rounded-md bg-[var(--brand-color)] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:brightness-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--brand-color)]">Close</button>
            </div>
        </div>
    </x-modal>

     <x-modal name="association-error" :show="false" focusable>
         <div class="p-6">
            <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-red-100">
                 <svg class="size-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.008v.008H12v-.008Z" /></svg>
            </div>
             <div class="mt-3 text-center sm:mt-5">
                 <h3 class="text-base font-semibold leading-6 text-gray-900">Association Error</h3>
                 <p class="mt-2 text-sm text-gray-500">Files uploaded, but the message could not be associated. Please check the console.</p>
             </div>
             <div class="mt-5 sm:mt-6">
                <button @click="show = false" type="button" class="inline-flex w-full justify-center rounded-md bg-[var(--brand-color)] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:brightness-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--brand-color)]">Close</button>
            </div>
         </div>
     </x-modal>

      <x-modal name="upload-error" :show="false" focusable>
         <div class="p-6">
            <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-red-100">
                 <svg class="size-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.008v.008H12v-.008Z" /></svg>
            </div>
             <div class="mt-3 text-center sm:mt-5">
                 <h3 class="text-base font-semibold leading-6 text-gray-900">Upload Failed</h3>
                 <p class="mt-2 text-sm text-gray-500">Some files failed to upload. Please remove them or check the errors and try again.</p>
             </div>
             <div class="mt-5 sm:mt-6">
                <button @click="show = false" type="button" class="inline-flex w-full justify-center rounded-md bg-[var(--brand-color)] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:brightness-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--brand-color)]">Close</button>
            </div>
         </div>
     </x-modal>

     <x-modal name="no-files-error" :show="false" focusable>
         <div class="p-6">
             <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-yellow-100">
                 <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
             </div>
             <div class="mt-3 text-center sm:mt-5">
                 <h3 class="text-base font-semibold leading-6 text-gray-900">No Files Added</h3>
                 <p class="mt-2 text-sm text-gray-500">Please add one or more files to upload before submitting.</p>
             </div>
             <div class="mt-5 sm:mt-6">
                 <button @click="show = false" type="button" class="inline-flex w-full justify-center rounded-md bg-[var(--brand-color)] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:brightness-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--brand-color)]">Close</button>
             </div>
         </div>
     </x-modal>

      <x-modal name="no-message-error" :show="false" focusable>
         <div class="p-6">
              <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-yellow-100">
                 <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
             </div>
             <div class="mt-3 text-center sm:mt-5">
                 <h3 class="text-base font-semibold leading-6 text-gray-900">{{ __('messages.file_upload_message_required_title') }}</h3>
                 <p class="mt-2 text-sm text-gray-500">{{ __('messages.file_upload_message_required_text') }}</p>
             </div>
             <div class="mt-5 sm:mt-6">
                <button @click="show = false" type="button" class="inline-flex w-full justify-center rounded-md bg-[var(--brand-color)] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:brightness-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--brand-color)]">{{ __('messages.file_upload_close_button') }}</button>
            </div>
         </div>
     </x-modal>


    @push('scripts')
    {{-- Initialization is handled in resources/js/app.js --}}
    @endpush
</x-app-layout>
