<x-app-layout>
    <div class="py-8 sm:py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="flex justify-between items-end mb-8 px-4 sm:px-0">
                <div>
                    <div class="w-8 h-px bg-accent-500 mb-4"></div>
                    <h1 class="font-display text-3xl sm:text-4xl text-warm-900">File Upload</h1>
                    <p class="text-warm-500 mt-1 text-sm">Upload your files securely to {{ config('app.company_name') }}</p>
                </div>
                <div class="text-right hidden sm:block">
                    <p class="text-xs text-warm-400">{{ auth()->user()->email }}</p>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-xs text-accent-500 hover:text-accent-600 transition-colors">
                            Sign out
                        </button>
                    </form>
                </div>
            </div>

            @if (session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Recipient Selection --}}
            @if(auth()->user()->companyUsers->count() > 1)
                <div class="mb-6 p-5 bg-cream-50 rounded-2xl border border-cream-200">
                    <label for="company_user_id" class="block text-xs font-medium text-warm-500 mb-2 tracking-wide uppercase">
                        {{ __('messages.select_recipient') }}
                    </label>
                    <select id="company_user_id" name="company_user_id"
                            class="w-full px-4 py-3 bg-white border border-cream-300 rounded-xl text-warm-900 focus:outline-none focus:ring-2 focus:ring-accent-500/20 focus:border-accent-500 text-sm">
                        @foreach(auth()->user()->companyUsers as $companyUser)
                            <option value="{{ $companyUser->id }}"
                                    @if($companyUser->pivot->is_primary) selected @endif>
                                {{ $companyUser->name }} ({{ $companyUser->email }})
                                @if($companyUser->pivot->is_primary) - Primary @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-warm-400">
                        {{ __('messages.select_recipient_help') }}
                    </p>
                </div>
            @endif

            {{-- Upload Card --}}
            <div class="bg-white border border-cream-200 rounded-2xl overflow-hidden">
                <form id="messageForm" class="p-6 sm:p-10 space-y-8">
                    @csrf

                    {{-- Dropzone Container --}}
                    <div id="file-upload-dropzone"
                         class="dropzone group relative border-2 border-dashed border-cream-300 rounded-2xl p-12 text-center cursor-pointer hover:border-accent-500 hover:bg-accent-500/[0.02] transition-all duration-300"
                         data-upload-url="{{ route('client.chunk.upload') }}">
                        <div class="dz-message" data-dz-message>
                            <div class="mx-auto w-14 h-14 rounded-2xl bg-cream-100 group-hover:bg-accent-500/10 flex items-center justify-center mb-5 transition-colors duration-300">
                                <svg class="w-7 h-7 text-warm-400 group-hover:text-accent-500 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            </div>
                            <span class="block text-base font-medium text-warm-700">Drop files here or click to upload</span>
                            <span class="block text-sm text-warm-400 mt-2">Any file type, any size</span>
                        </div>
                    </div>

                    {{-- Hidden input to store successful upload IDs --}}
                    <input type="hidden" name="file_upload_ids" id="file_upload_ids" value="[]">

                    {{-- Area to display upload errors --}}
                    <div id="upload-errors" class="hidden mt-4"></div>

                    {{-- Upload Progress Overlay --}}
                    <div id="upload-progress-overlay" class="hidden fixed inset-0 bg-warm-900/40 backdrop-blur-sm z-50 items-center justify-center">
                        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl border border-cream-200">
                            <div class="text-center mb-6">
                                <div class="inline-flex items-center justify-center w-12 h-12 bg-accent-500/10 rounded-full mb-4">
                                    <svg class="w-6 h-6 text-accent-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <h3 class="font-display text-xl text-warm-900">Uploading Files</h3>
                                <p id="progress-status" class="text-sm text-warm-500 mt-1">Preparing upload...</p>
                            </div>

                            <div class="mb-6">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium text-warm-500 uppercase tracking-wide">Progress</span>
                                    <span id="overall-progress-text" class="text-xs text-warm-500">0%</span>
                                </div>
                                <div class="w-full bg-cream-200 rounded-full h-2">
                                    <div id="overall-progress-bar" class="bg-accent-500 h-2 rounded-full transition-all duration-500 ease-out" style="width: 0%"></div>
                                </div>
                            </div>

                            <div class="max-h-48 overflow-y-auto">
                                <div id="file-progress-container" class="space-y-3"></div>
                            </div>

                            <div class="mt-6 text-center">
                                <button type="button"
                                        onclick="if(confirm('Are you sure you want to cancel the upload?')) { location.reload(); }"
                                        class="text-xs text-warm-400 hover:text-warm-600 transition-colors">
                                    Cancel Upload
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Message Textarea --}}
                    <div>
                        <label for="message" class="block text-xs font-medium text-warm-500 mb-2 tracking-wide uppercase">Message (Optional)</label>
                        <textarea id="message" name="message" rows="3"
                                  class="w-full px-4 py-3.5 bg-cream-50 border border-cream-300 rounded-xl text-warm-900 text-sm placeholder-warm-400 focus:outline-none focus:ring-2 focus:ring-accent-500/20 focus:border-accent-500 transition-all duration-200"
                                  placeholder="Add a note about these files..."></textarea>
                    </div>

                    {{-- Submit Button --}}
                    <div class="pt-2">
                        <button type="submit"
                                class="w-full sm:w-auto bg-warm-900 text-cream-50 px-10 py-3.5 rounded-xl font-medium text-sm tracking-wide hover:bg-warm-800 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-warm-900 transition-all duration-200">
                            Upload and Send Message
                        </button>
                    </div>
                </form>
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
                <p class="mt-2 text-sm text-gray-500">Your files have been uploaded successfully! Large files are being processed in the background.</p>
                <p class="mt-1 text-xs text-gray-400">Redirecting to My Uploads...</p>
            </div>
            <div class="mt-5 sm:mt-6">
                <a href="{{ route('client.my-uploads') }}" class="inline-flex w-full justify-center rounded-md bg-[var(--brand-color)] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:brightness-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--brand-color)]">View My Uploads</a>
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

    @push('scripts')

    @endpush
</x-app-layout>
