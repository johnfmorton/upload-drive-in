<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Upload Files') }}
        </h2>
    </x-slot>

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

                    <form id="messageForm" class="space-y-6">
                        @csrf {{-- Important for CSRF protection --}}

                        {{-- Dropzone Container --}}
                        <div id="file-upload-dropzone"
                             class="dropzone border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-[var(--brand-color)] transition-colors duration-200"
                             data-upload-url="{{ route('client.chunk.upload') }}">
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
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Success Modal --}}
    <div id="success-modal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden" aria-hidden="true">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Upload Complete!</h3>
                    <p class="text-sm text-gray-600">Your files have been uploaded successfully.</p>
                    <div class="mt-6">
                        <button type="button" onclick="window.location.reload()"
                                class="w-full bg-[var(--brand-color)] text-white px-4 py-2 rounded hover:brightness-90 transition">
                            Upload More Files
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')

    @endpush
</x-app-layout>
