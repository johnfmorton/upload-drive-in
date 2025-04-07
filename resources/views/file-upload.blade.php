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
                                    <div class="flex text-sm text-gray-600">
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
        let fileStore = new DataTransfer();

        // --- Aggressive Global Drag/Drop Event Prevention ---
        function preventDefaultsAndStopPropagation(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        // Attach to window AND documentElement for maximum coverage
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            window.addEventListener(eventName, preventDefaultsAndStopPropagation, false);
            document.documentElement.addEventListener(eventName, preventDefaultsAndStopPropagation, false);
        });


        // --- Drop Zone Specific Highlighting & Handling ---
        dropZone.addEventListener('dragenter', highlight, false);
        dropZone.addEventListener('dragover', highlight, false); // Need this to allow drop
        dropZone.addEventListener('dragleave', unhighlight, false);
        dropZone.addEventListener('drop', handleDrop, false);

        function highlight(e) {
             // Check if the related target is outside the drop zone when entering/over
            if (!dropZone.contains(e.relatedTarget)) {
                dropZone.classList.add('border-indigo-500', 'bg-indigo-50');
            }
        }

        function unhighlight(e) {
            // Check if the related target is outside the drop zone when leaving
            if (!dropZone.contains(e.relatedTarget)) {
                 dropZone.classList.remove('border-indigo-500', 'bg-indigo-50');
            }
        }

        function handleDrop(e) {
            // Stop propagation specifically for the drop zone drop event
            e.stopPropagation();
            console.log('Drop event fired on dropZone. Files found:', e.dataTransfer.files.length); // *** Explicit Debug Log ***
            addFiles(e.dataTransfer.files);
            unhighlight(e); // Ensure highlight is removed
        }

        // --- File Input Handling ---
        fileInput.addEventListener('change', handleInputChange, false);

        function handleInputChange(e) {
            console.log('Input change event fired. Files selected:', e.target.files.length); // *** Explicit Debug Log ***
            addFiles(e.target.files);
            e.target.value = null; // Reset input
        }

        function addFiles(newFiles) {
            console.log('addFiles called with', newFiles.length, 'files.'); // *** Explicit Debug Log ***
            if (!newFiles || newFiles.length === 0) return;

            let addedCount = 0;
            for (const file of newFiles) {
                 let isDuplicate = false;
                 for(let i=0; i < fileStore.items.length; i++){
                     if(fileStore.items[i].kind === 'file') {
                         const existingFile = fileStore.items[i].getAsFile();
                         if(existingFile && existingFile.name === file.name && existingFile.size === file.size){
                            isDuplicate = true;
                            break;
                         }
                     }
                 }
                 if (!isDuplicate) {
                    fileStore.items.add(file);
                    addedCount++;
                 }
            }
            console.log(`Added ${addedCount} new files. Total in store: ${fileStore.files.length}`); // *** Explicit Debug Log ***

            if(addedCount > 0) {
                fileInput.files = fileStore.files;
                displayFiles();
            }
        }

         function removeFile(index) {
             console.log(`removeFile called for index: ${index}`); // *** Explicit Debug Log ***
             const dt = new DataTransfer();
             const currentFiles = Array.from(fileStore.files);

             if (index < 0 || index >= currentFiles.length) {
                console.error('Invalid index for removeFile:', index);
                return;
             }

             for (let i = 0; i < currentFiles.length; i++) {
                 if (i !== index) {
                     dt.items.add(currentFiles[i]);
                 }
             }

             fileStore = dt;
             fileInput.files = fileStore.files;
             displayFiles();
         }


        function displayFiles() {
            console.log(`displayFiles called. Rendering ${fileStore.files.length} files.`); // *** Explicit Debug Log ***
            fileList.innerHTML = '';

            if (fileStore.files.length === 0) {
                fileList.innerHTML = '<p class="text-sm text-gray-500 p-4 text-center">No files selected.</p>'; // Show a message when empty
                return;
            }

            Array.from(fileStore.files).forEach((file, index) => {
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


        // --- Form Submission ---
        uploadForm.addEventListener('submit', function(e) {
            fileInput.files = fileStore.files;
            console.log(`Form submit initiated. Files in input: ${fileInput.files.length}`); // *** Explicit Debug Log ***

            if (fileStore.files.length === 0) {
                e.preventDefault();
                console.log('Form submission prevented: No files.'); // *** Explicit Debug Log ***
                alert('Please select at least one file to upload.');
                 dropZone.classList.add('border-red-500');
                 setTimeout(() => dropZone.classList.remove('border-red-500'), 2000);
            }
        });

        // Initial display state (optional, can be useful)
        displayFiles();

      }); // End DOMContentLoaded
    </script>
    @endpush
</x-app-layout>
