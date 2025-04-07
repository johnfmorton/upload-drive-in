<x-app-layout>
    <div class="min-h-screen bg-gray-100 py-12">
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
                                <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-900">
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

                    <form id="uploadForm" action="{{ route('upload-files.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div class="space-y-4">
                            <div id="dropZone" class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center cursor-pointer hover:border-indigo-500 transition-colors">
                                <div class="space-y-2">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600 justify-center">
                                        <label for="files" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span>Upload files</span>
                                            <input id="files" name="files[]" type="file" class="sr-only" multiple>
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, PDF up to 10MB</p>
                                </div>
                            </div>

                            <div id="fileList" class="space-y-2"></div>
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700">Message (optional)</label>
                            <textarea id="message" name="message" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Add any context or instructions for your files..."></textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Upload Files
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('files');
        const fileList = document.getElementById('fileList');
        const uploadForm = document.getElementById('uploadForm');
        // No separate fileStore needed, we rely on fileInput.files as the source of truth

        // --- Drop Zone Event Listeners ---
        dropZone.addEventListener('dragenter', handleDragEnter, false);
        dropZone.addEventListener('dragover', handleDragOver, false);
        dropZone.addEventListener('dragleave', handleDragLeave, false);
        dropZone.addEventListener('drop', handleDrop, false);

        function handleDragEnter(e) {
            e.stopPropagation();
            e.preventDefault();
            dropZone.classList.add('border-indigo-500', 'bg-indigo-50');
        }
        function handleDragOver(e) {
            e.preventDefault(); // Allows dropping
            e.stopPropagation();
            dropZone.classList.add('border-indigo-500', 'bg-indigo-50'); // Keep highlighted
        }
        function handleDragLeave(e) {
            e.stopPropagation();
            e.preventDefault();
            if (!dropZone.contains(e.relatedTarget)) { // Check if leaving dropzone boundary
                dropZone.classList.remove('border-indigo-500', 'bg-indigo-50');
            }
        }
        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('border-indigo-500', 'bg-indigo-50');
            console.log('[handleDrop] Drop detected. Processing dropped files...');
            mergeDroppedFilesAndUpdateInput(e.dataTransfer.files); // Call dedicated merge function for drop
        }

        // --- File Input Event Listener ---
        fileInput.addEventListener('change', handleInputChange, false);

        function handleInputChange(e) {
            console.log('[handleInputChange] Input changed. Browser updated fileInput.files.');
            // For dialog selection, the browser replaces the list in fileInput.files.
            // We just need to update the visual display based on this new list.
            displayFiles();
            // Reset input value *after* display to allow selecting same file again
            e.target.value = null;
        }

        // --- File Management Logic ---

        // Dedicated function to MERGE DROPPED files with existing, check duplicates, and update input
        function mergeDroppedFilesAndUpdateInput(droppedFiles) {
            console.log('[mergeDroppedFiles] Called with droppedFiles:', droppedFiles);
            if (!droppedFiles || droppedFiles.length === 0) {
                console.log('[mergeDroppedFiles] No dropped files provided.');
                return;
            }

            const dt = new DataTransfer();
            const existingFiles = Array.from(fileInput.files); // Files currently in the input
            console.log('[mergeDroppedFiles] Existing files in input before merge:', existingFiles);

            // Add existing files first
            existingFiles.forEach(file => dt.items.add(file));

            let addedCount = 0;
            const existingKeys = existingFiles.map(f => `${f.name}-${f.size}`);
            console.log('[mergeDroppedFiles] Existing file keys:', existingKeys);

            // Check newly DROPPED files against existing keys and add if unique
            for (const fileToAdd of droppedFiles) {
                 const fileKey = `${fileToAdd.name}-${fileToAdd.size}`;
                 console.log(`[mergeDroppedFiles] Checking fileToAdd: ${fileToAdd.name}, Key: ${fileKey}`);
                 if (!existingKeys.includes(fileKey)) {
                    console.log(`[mergeDroppedFiles] Adding unique dropped file: ${fileToAdd.name}`);
                    dt.items.add(fileToAdd);
                    addedCount++;
                 } else {
                    console.log(`[mergeDroppedFiles] Dropped file is duplicate, skipping: ${fileToAdd.name}`);
                 }
            }

            console.log(`[mergeDroppedFiles] New unique files added from drop: ${addedCount}`);

            // Update input only if new unique files were added from the drop
            if(addedCount > 0) {
                console.log('[mergeDroppedFiles] Updating fileInput.files:', dt.files);
                fileInput.files = dt.files;
                console.log('[mergeDroppedFiles] fileInput.files after update:', fileInput.files);
                displayFiles(); // Refresh display after merging
            } else {
                 console.log('[mergeDroppedFiles] No new unique files added from drop, input/display remains unchanged.');
            }
        }

        function removeFile(index) {
            console.log(`[removeFile] Removing file at index: ${index}`);
            const dt = new DataTransfer();
            const currentFiles = Array.from(fileInput.files);
            if (index < 0 || index >= currentFiles.length) {
                console.error('[removeFile] Invalid index.');
                return;
            }
            for (let i = 0; i < currentFiles.length; i++) {
                if (i !== index) {
                    dt.items.add(currentFiles[i]);
                }
            }
            fileInput.files = dt.files;
            console.log(`[removeFile] fileInput.files after removal: ${fileInput.files.length} files`);
            displayFiles();
        }

        // --- Display Logic --- Reads directly from fileInput.files
        function displayFiles() {
            console.log(`[displayFiles] Rendering list based on fileInput.files. Count: ${fileInput.files.length}`);
            fileList.innerHTML = '';
            const filesToDisplay = Array.from(fileInput.files);
            if (filesToDisplay.length === 0) {
                fileList.innerHTML = '<p class="text-sm text-gray-500 p-4 text-center">No files selected.</p>';
                return;
            }
            filesToDisplay.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center justify-between p-2 bg-gray-50 rounded mb-2 text-sm';
                const fileInfoDiv = document.createElement('div');
                fileInfoDiv.className = 'flex items-center space-x-2 overflow-hidden mr-2';
                fileInfoDiv.innerHTML = `
                    <svg class="h-5 w-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span class="text-gray-700 truncate" title="${escapeHTML(file.name)}">${escapeHTML(file.name)}</span>
                `;
                const fileActionsDiv = document.createElement('div');
                fileActionsDiv.className = 'flex items-center space-x-2 flex-shrink-0';
                const fileSizeSpan = document.createElement('span');
                fileSizeSpan.className = 'text-xs text-gray-500';
                fileSizeSpan.textContent = formatFileSize(file.size);
                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'text-red-500 hover:text-red-700 focus:outline-none p-1 rounded-full hover:bg-red-100 transition-colors duration-150';
                removeButton.title = 'Remove file';
                removeButton.innerHTML = `
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                         <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                `;
                removeButton.addEventListener('click', () => removeFile(index));
                fileActionsDiv.appendChild(fileSizeSpan);
                fileActionsDiv.appendChild(removeButton);
                fileItem.appendChild(fileInfoDiv);
                fileItem.appendChild(fileActionsDiv);
                fileList.appendChild(fileItem);
            });
             console.log(`[displayFiles] Finished rendering list.`);
        }

        function formatFileSize(bytes) {
            if (bytes < 0) return 'Invalid size';
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
            const i = Math.max(0, Math.min(sizes.length - 1, Math.floor(Math.log(bytes) / Math.log(k))));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

         function escapeHTML(str) {
             const div = document.createElement('div');
             div.appendChild(document.createTextNode(str));
             return div.innerHTML;
         }

        // --- Form Submission --- Reads directly from fileInput.files
        uploadForm.addEventListener('submit', function(e) {
            const currentFileInputLength = fileInput.files.length;
             console.log(`[onSubmit] Checking fileInput length: ${currentFileInputLength}`);
            if (currentFileInputLength === 0) {
                e.preventDefault();
                console.warn('[onSubmit] Preventing submission: No files in input.');
                alert('Please select at least one file to upload.');
                 dropZone.classList.add('border-red-500');
                 setTimeout(() => dropZone.classList.remove('border-red-500'), 2000);
            } else {
                 console.log('[onSubmit] Allowing submission.');
            }
        });

        // Initial display state
        displayFiles();

      }); // End DOMContentLoaded
    </script>
    @endpush
</x-app-layout>
