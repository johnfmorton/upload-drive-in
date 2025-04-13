<x-guest-layout>
    <div class="bg-gray-100 py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-2xl font-semibold mb-6 text-center">Upload Files</h1>

                    {{-- Form for submitting the message and linking files --}}
                    <form id="messageForm" class="space-y-6">
                        @csrf {{-- Important for CSRF protection --}}

                        {{-- Dropzone Container --}}
                        <div id="file-upload-dropzone"
                             class="dropzone border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-indigo-500 transition-colors duration-200"
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

                        {{-- Message Textarea --}}
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message (Optional)</label>
                            <textarea id="message" name="message" rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                      placeholder="Enter an optional message to associate with the uploaded files..."></textarea>
                        </div>

                        {{-- Submit Button --}}
                        <div class="text-center">
                            <button type="submit"
                                    class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                                Upload and Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Keep existing scripts if needed, or integrate Dropzone logic here/app.js --}}
    {{-- @push('scripts') ... @endpush --}}

</x-guest-layout>
