{{-- Employee Google Drive Root Folder Selection Form --}}
<div x-data="employeeGoogleDriveFolderPicker()" x-init="init()">
    <form action="{{ route('employee.google-drive.folder.update', ['username' => $user->username]) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')
        <x-label for="google_drive_root_folder_id" :value="__('Root Folder')" />
        <p class="-mt-3 text-sm text-gray-500">Select the folder where uploaded files will be stored in your Google Drive.</p>
        <div class="flex items-center space-x-2 border-gray-300 rounded-md border p-4">
            <input type="hidden" id="google_drive_root_folder_id" name="google_drive_root_folder_id" x-model="currentFolderId" />
            <span class="text-gray-700 relative top-[1px]" x-text="currentFolderName ? '📁' : ''"></span>
            <span
                x-text="currentFolderName ? currentFolderName : 'Select a folder'"
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
                <div class="flex justify-end space-x-2">
                    <button type="button" @click="confirmSelection" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Confirm</button>
                    <button type="button" @click="closeModal" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded">Cancel</button>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" x-bind:class="folderChanged ? 'animate-pulse' : ''" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                Save Root Folder
            </button>
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
            currentFolderName: @json($currentFolderName ?? ''),
            rootFolderName: 'Root Folder',
            baseFolderShowUrl: '{{ route('employee.google-drive.folders', ['username' => $user->username]) }}',
            folderStack: [],
            folders: [],
            newFolderName: '',

            init() {
                // Folder name is now loaded server-side
            },

            openModal() {
                this.showModal = true;
                this.loadFolders(this.rootFolderId);
                this.folderStack = [];
            },

            closeModal() {
                this.showModal = false;
                this.newFolderName = '';
            },

            loadFolders(parentId) {
                fetch(`{{ route('employee.google-drive.folders', ['username' => $user->username]) }}?parent_id=${parentId}`)
                    .then(response => response.json())
                    .then(data => { this.folders = data.folders; })
                    .catch(error => {
                        console.error('Failed to load folders:', error);
                        this.folders = [];
                    });
            },

            enterFolder(folder) {
                this.folderStack.push(folder);
                this.loadFolders(folder.id);
            },

            goUp() {
                if (this.folderStack.length > 0) {
                    this.folderStack.pop();
                    const parentId = this.folderStack.length > 0 
                        ? this.folderStack[this.folderStack.length - 1].id 
                        : this.rootFolderId;
                    this.loadFolders(parentId);
                }
            },

            createFolder() {
                if (!this.newFolderName) return;
                const parentId = this.folderStack.length > 0 
                    ? this.folderStack[this.folderStack.length - 1].id 
                    : this.rootFolderId;

                fetch(`{{ route('employee.google-drive.folders.store', ['username' => $user->username]) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        parent_id: parentId,
                        name: this.newFolderName
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.folder) {
                        this.folders.push(data.folder);
                        this.newFolderName = '';
                    }
                })
                .catch(error => {
                    console.error('Failed to create folder:', error);
                });
            },

            confirmSelection() {
                const selectedId = this.folderStack.length > 0 
                    ? this.folderStack[this.folderStack.length - 1].id 
                    : this.rootFolderId;
                const selectedName = this.folderStack.length > 0 
                    ? this.folderStack[this.folderStack.length - 1].name 
                    : this.rootFolderName;

                this.currentFolderId = selectedId;
                this.currentFolderName = selectedName;
                this.folderChanged = this.currentFolderId !== this.initialFolderId;
                this.closeModal();
            }
        };
    }
</script>