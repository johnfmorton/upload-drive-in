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
        let fileStore = new DataTransfer(); // Our persistent store for cumulative files

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
            console.log('[handleDrop] Drop detected. Processing files...');
            addFilesToStore(e.dataTransfer.files); // Call the central add/merge function
        }

        // --- File Input Event Listener ---
        fileInput.addEventListener('change', handleInputChange, false);

        function handleInputChange(e) {
            console.log('[handleInputChange] Input changed. Processing files from dialog...');
            const filesFromInput = e.target.files;
            addFilesToStore(filesFromInput); // Call the central add/merge function
            // Reset input value *after* processing files
            e.target.value = null;
        }

        // --- File Management Logic ---

        // Central function to add new files TO THE STORE, merge, deduplicate, and update display
        function addFilesToStore(newFiles) {
            console.log('[addFilesToStore] Called with newFiles:', newFiles);
            if (!newFiles || newFiles.length === 0) {
                console.log('[addFilesToStore] No new files provided.');
                return;
            }

            const dt = new DataTransfer();
            const existingFilesInStore = Array.from(fileStore.files);
            console.log('[addFilesToStore] Existing files in fileStore before merge:', existingFilesInStore);

            existingFilesInStore.forEach(file => dt.items.add(file));

            let addedCount = 0;
            const existingKeys = existingFilesInStore.map(f => `${f.name}-${f.size}`);
            console.log('[addFilesToStore] Existing file keys from store:', existingKeys);

            for (const fileToAdd of newFiles) {
                 const fileKey = `${fileToAdd.name}-${fileToAdd.size}`;
                 console.log(`[addFilesToStore] Checking fileToAdd: ${fileToAdd.name}, Key: ${fileKey}`);
                 if (!existingKeys.includes(fileKey)) {
                    console.log(`[addFilesToStore] Adding unique file: ${fileToAdd.name}`);
                    dt.items.add(fileToAdd);
                    addedCount++;
                 } else {
                    console.log(`[addFilesToStore] File is duplicate, skipping: ${fileToAdd.name}`);
                 }
            }

            console.log(`[addFilesToStore] New unique files added: ${addedCount}`);

            // Update store ONLY if new unique files were added
            if(addedCount > 0) {
                fileStore = dt; // Update our persistent store
                console.log('[addFilesToStore] Updated fileStore:', fileStore.files);
                // *** DO NOT update fileInput.files here ***
                displayFiles(); // Update the visual list based on the updated fileStore
            } else {
                 console.log('[addFilesToStore] No new unique files added, store/display remain unchanged.');
            }
        }

        function removeFile(index) {
            console.log(`[removeFile] Removing file at index: ${index} from fileStore.`);
            const dt = new DataTransfer();
            const currentFiles = Array.from(fileStore.files);
            if (index < 0 || index >= currentFiles.length) {
                console.error('[removeFile] Invalid index.');
                return;
            }
            for (let i = 0; i < currentFiles.length; i++) {
                if (i !== index) {
                    dt.items.add(currentFiles[i]);
                }
            }
            fileStore = dt; // Update the persistent store
            // *** DO NOT update fileInput.files here ***
            console.log(`[removeFile] Updated fileStore: ${fileStore.files.length} files`);
            displayFiles(); // Update display based on the updated fileStore
        }

        // --- Display Logic --- Reads directly from fileStore
        function displayFiles() {
             // *** READ FROM fileStore ***
             console.log(`[displayFiles] Rendering list based on fileStore. Count: ${fileStore.files.length}`);
             fileList.innerHTML = '';
             // *** READ FROM fileStore ***
             const filesToDisplay = Array.from(fileStore.files);
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
                removeButton.addEventListener('click', () => removeFile(index)); // removeFile operates on fileStore
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

        // --- Form Submission --- Checks fileStore, syncs fileInput before submit
        uploadForm.addEventListener('submit', function(e) {
            // *** CHECK fileStore ***
            const currentFileStoreLength = fileStore.files.length;
            console.log(`[onSubmit] Checking fileStore length: ${currentFileStoreLength}`);

            if (currentFileStoreLength === 0) {
                e.preventDefault();
                console.warn('[onSubmit] Preventing submission: No files in fileStore.');
                alert('Please select at least one file to upload.');
                 dropZone.classList.add('border-red-500');
                 setTimeout(() => dropZone.classList.remove('border-red-500'), 2000);
            } else {
                 // *** SYNC fileInput right before submission ***
                 console.log('[onSubmit] Syncing fileInput.files with fileStore before allowing submission.');
                 fileInput.files = fileStore.files;
                 console.log(`[onSubmit] Allowing submission. Synced fileInput length: ${fileInput.files.length}`);
            }
        });

        // Initial display state based on fileStore (which is initially empty)
        displayFiles();

      }); // End DOMContentLoaded
    </script>
    @endpush
</x-app-layout>
