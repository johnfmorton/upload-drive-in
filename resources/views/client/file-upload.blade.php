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
                        <div>
                            <button type="submit"
                                    class="w-full bg-[var(--brand-color)] text-white px-4 py-2 rounded hover:brightness-90 transition"
                                    id="submit-button"
                                    disabled>
                                Submit
                            </button>
                        </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileIdsInput = document.getElementById('file_upload_ids');
            const submitButton = document.getElementById('submit-button');
            const messageForm = document.getElementById('messageForm');
            const successModal = document.getElementById('success-modal');
            const uploadUrl = document.getElementById('file-upload-dropzone').dataset.uploadUrl;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            // Initialize Dropzone
            const myDropzone = new Dropzone("#file-upload-dropzone", {
                url: uploadUrl,
                paramName: "file", // Field name for the file
                maxFilesize: 5000, // Max file size in MB (adjust as needed)
                chunking: true,
                forceChunking: true,
                chunkSize: 5 * 1024 * 1024, // Chunk size in bytes (5MB)
                retryChunks: true, // Retry failed chunks
                retryChunksLimit: 3,
                parallelChunkUploads: false, // Upload chunks sequentially for pion
                addRemoveLinks: true,
                autoProcessQueue: false,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                // Must match the parameters expected by Pion's Dropzone handler
                params: function(files, xhr, chunk) {
                    if (chunk) {
                        return {
                            dzuuid: chunk.file.upload.uuid,
                            dzchunkindex: chunk.index,
                            dztotalfilesize: chunk.file.size,
                            dzchunksize: this.options.chunkSize,
                            dztotalchunkcount: chunk.file.upload.totalChunkCount,
                            dzchunkbyteoffset: chunk.index * this.options.chunkSize
                        };
                    }
                },
                success: function(file, response) {
                    if (response && response.file_upload_id) {
                        if (!file.finalIdReceived) {
                            file.finalIdReceived = true;
                            file.file_upload_id = response.file_upload_id;

                            let currentIds = fileIdsInput.value ? JSON.parse(fileIdsInput.value) : [];
                            if (!currentIds.includes(response.file_upload_id)) {
                                currentIds.push(response.file_upload_id);
                                fileIdsInput.value = JSON.stringify(currentIds);
                            }
                        }
                    }
                },
                error: function(file, message, xhr) {
                    const errorDisplay = document.getElementById('upload-errors');
                    if (errorDisplay) {
                        errorDisplay.innerHTML += `<p class="text-red-500">Error uploading ${file.name}: ${message}</p>`;
                        errorDisplay.classList.remove('hidden');
                    }
                },
                complete: function(file) {
                    myDropzone.processQueue();
                }
            });

            // Enable submit button when files are added
            myDropzone.on("addedfile", function() {
                submitButton.disabled = false;
            });

            // Handle form submission
            messageForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const fileIds = JSON.parse(fileIdsInput.value || '[]');
                if (fileIds.length === 0) {
                    alert('Please upload at least one file.');
                    return;
                }

                try {
                    const response = await fetch('/api/uploads/batch-complete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            file_upload_ids: fileIds,
                            message: document.getElementById('message').value
                        })
                    });

                    if (response.ok) {
                        successModal.classList.remove('hidden');
                    } else {
                        throw new Error('Failed to complete batch upload');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while completing the upload.');
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
