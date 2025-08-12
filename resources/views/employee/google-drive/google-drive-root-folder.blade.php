{{-- Employee Google Drive Root Folder Selection Form --}}
<div x-data="employeeGoogleDriveFolderPicker()" x-init="init()">
    <form action="{{ route('employee.google-drive.folder.update', ['username' => $user->username]) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')
        <x-label for="google_drive_root_folder_id" :value="__('Root Folder')" />
        <p class="-mt-3 text-sm text-gray-500">Select the folder where uploaded files will be stored in your Google Drive.</p>
        
        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">User-Specific Configuration</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Your root folder setting is stored in your user profile. If no folder is selected, uploads will go to your Google Drive root directory by default.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-2 border-gray-300 rounded-md border p-4">
            <input type="hidden" id="google_drive_root_folder_id" name="google_drive_root_folder_id" x-model="currentFolderId" />
            <span class="text-gray-700 relative top-[1px]">📁</span>
            <span
                x-text="currentFolderName || 'Google Drive Root (default)'"
                class="inline-block w-full text-gray-700">
            </span>
            <x-secondary-button
                type="button"
                @click="openModal"
                class="whitespace-nowrap bg-gray-200 hover:bg-gray-700 hover:text-white px-2 py-1 rounded"
                x-text="currentFolderId ? 'Change Folder' : 'Select Folder'"
            />
        </div>
        @error('google_drive_root_folder_id')
            <div class="text-red-600 text-sm mt-2">{{ $message }}</div>
        @enderror

        <!-- Folder Selection Modal -->
        <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div @click.away="closeModal" class="bg-white rounded-lg shadow-lg w-3/4 max-w-2xl p-6">
                <h2 class="text-xl font-bold mb-4">Select Folder</h2>
                <div class="flex items-center justify-between mb-4">
                    <button type="button" @click="goUp" class="px-2 py-1 bg-gray-200 rounded">Up</button>
                    <span class="text-lg font-medium" x-text="folderStack.length ? folderStack[folderStack.length - 1].name : rootFolderName"></span>
                    <button type="button" @click="closeModal" class="px-2 py-1 bg-gray-200 rounded">Cancel</button>
                </div>
                <ul class="divide-y divide-gray-200 mb-4 overflow-y-auto h-64">
                    <template x-if="folders.length === 0">
                        <li class="p-2 text-gray-500">No folders found</li>
                    </template>
                    <template x-for="folder in folders" :key="folder.id">
                        <li class="p-2 hover:bg-gray-100 cursor-pointer" @click="enterFolder(folder)">
                            <span x-text="folder.name"></span>
                        </li>
                    </template>
                </ul>
                <div class="flex items-center space-x-2 mb-4">
                    <input x-model="newFolderName" type="text" placeholder="Create new folder" class="mt-0 block w-full border-gray-300 rounded-md" />
                    <button type="button" @click="createFolder" class="bg-green-600 hover:bg-green-700 text-white whitespace-nowrap px-2 py-1 rounded">
                        Create Folder
                    </button>
                </div>
                <div class="flex justify-between">
                    <x-secondary-button type="button" @click="useGoogleDriveRoot" class="bg-gray-600 hover:bg-gray-700 text-white">
                        Use Google Drive Root (default)
                    </x-secondary-button>
                    <div class="flex space-x-2">
                        <x-button type="button" @click="confirmSelection">Confirm</x-button>
                        <x-secondary-button type="button" @click="closeModal">Cancel</x-secondary-button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <x-button type="submit" x-bind:class="folderChanged ? 'animate-wiggle' : ''">Save Root Folder</x-button>
        </div>
    </form>
</div>

<script>
    function employeeGoogleDriveFolderPicker() {
        return {
            showModal: false,
            rootFolderId: 'root',
            currentFolderId: @json($user->google_drive_root_folder_id ?? ''),
            initialFolderId: @json($user->google_drive_root_folder_id ?? ''),
            folderChanged: false,
            currentFolderName: @json($currentFolderName ?? 'Google Drive Root'),
            rootFolderName: 'Google Drive Root',
            baseFolderShowUrl: '{{ route('employee.google-drive.folders', ['username' => $user->username]) }}',
            folderStack: [],
            folders: [],
            newFolderName: '',

            init() {
                // Remember initial folder to detect changes
                this.initialFolderId = this.currentFolderId;
                this.folderChanged = false;
                
                // If user has a specific folder configured, fetch its name
                if (this.currentFolderId && this.currentFolderId !== 'root') {
                    fetch(`{{ route('employee.google-drive.folders', ['username' => $user->username]) }}/${this.currentFolderId}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.folder && data.folder.name) {
                                this.currentFolderName = data.folder.name;
                            }
                        })
                        .catch(() => {
                            // If folder fetch fails, reset to default
                            this.currentFolderName = '';
                        });
                }
            },

            openModal() {
                this.showModal = true;
                this.folderStack = [{ id: this.rootFolderId, name: this.rootFolderName }];
                this.loadFolders(this.rootFolderId);
            },

            closeModal() {
                this.showModal = false;
                this.newFolderName = '';
            },

            loadFolders(parentId) {
                fetch(`{{ route('employee.google-drive.folders', ['username' => $user->username]) }}?parent_id=${parentId}`)
                    .then(response => response.json())
                    .then(data => { this.folders = data.folders; })
                    .catch(() => { this.folders = []; });
            },

            enterFolder(folder) {
                this.folderStack.push(folder);
                this.loadFolders(folder.id);
            },

            goUp() {
                if (this.folderStack.length > 1) {
                    this.folderStack.pop();
                    const prev = this.folderStack[this.folderStack.length - 1];
                    this.loadFolders(prev.id);
                }
            },

            createFolder() {
                if (!this.newFolderName) return;
                fetch(`{{ route('employee.google-drive.folders.store', ['username' => $user->username]) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ parent_id: this.folderStack[this.folderStack.length - 1].id, name: this.newFolderName })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.folder) {
                        this.folders.push(data.folder);
                        this.newFolderName = '';
                    }
                });
            },

            confirmSelection() {
                const selected = this.folderStack[this.folderStack.length - 1];
                // Don't set root as the folder ID - leave it empty for default behavior
                if (selected.id === 'root') {
                    this.currentFolderId = '';
                    this.currentFolderName = '';
                } else {
                    this.currentFolderId = selected.id;
                    this.currentFolderName = selected.name;
                }
                this.folderChanged = (this.currentFolderId !== this.initialFolderId);
                this.showModal = false;
            },

            useGoogleDriveRoot() {
                this.currentFolderId = '';
                this.currentFolderName = '';
                this.folderChanged = (this.currentFolderId !== this.initialFolderId);
                this.showModal = false;
            }
        };
    }
</script>