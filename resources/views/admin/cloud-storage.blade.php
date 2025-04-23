<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.cloud_storage_configuration') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div x-data="{ selectedProvider: '{{ old('default_provider', config('cloud-storage.default')) }}' }" x-cloak class="p-6 divide-y divide-gray-200">

                 <!-- Default Provider Selection -->
                    <div class="py-6 first:pt-0 last:pb-0">
                        <form action="{{ route('admin.cloud-storage.default') }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div>
                                <x-label for="default_provider" :value="__('messages.default_storage_provider')" class="text-lg" />
                                <p class="mt-1 text-sm text-gray-500">{{ __('messages.select_default_provider_description') }}</p>
                                <select x-model="selectedProvider" name="default_provider" id="default_provider"
                                    class="mt-2 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                    <option value="google-drive" @if(config('cloud-storage.default') === 'google-drive') selected @endif>Google Drive</option>
                                    <option value="microsoft-teams" @if(config('cloud-storage.default') === 'microsoft-teams') selected @endif>Microsoft Teams</option>
                                    <option value="dropbox" @if(config('cloud-storage.default') === 'dropbox') selected @endif>Dropbox</option>
                                </select>
                                <x-input-error for="default_provider" class="mt-2" />
                            </div>
                            <div class="flex justify-end">
                                <x-button>
                                    {{ __('messages.save_changes') }}
                                </x-button>
                            </div>
                        </form>
                    </div>
                    <!-- Microsoft Teams Configuration -->
                    <div x-show="selectedProvider === 'microsoft-teams'" x-cloak class="py-6 first:pt-0 last:pb-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Microsoft Teams</h3>
                                <p class="mt-1 text-sm text-gray-500"><a href="https://portal.azure.com" target="_blank"
                                title="{{ __('messages.configure_microsoft_teams_storage_link') }}" class="text-blue-500 hover:text-blue-700">
                                    {{ __('messages.configure_microsoft_teams_storage_link_description') }}
                                </a></p>
                            </div>
                            <div class="flex items-center space-x-4">
                                @if(Storage::exists('microsoft-teams-credentials.json'))
                                    <span class="px-3 py-1 text-sm text-green-800 bg-green-100 rounded-full">{{ __('messages.connected') }}</span>
                                    <form action="{{ route('admin.cloud-storage.microsoft-teams.disconnect') }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            {{ __('messages.disconnect') }}
                                        </button>
                                    </form>
                                @else
                                    <span class="px-3 py-1 text-sm text-gray-800 bg-gray-100 rounded-full">{{ __('messages.not_connected') }}</span>
                                    <a href="{{ route('admin.cloud-storage.microsoft-teams.connect') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        {{ __('messages.connect') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="mt-6">
                            <form action="{{ route('admin.cloud-storage.microsoft-teams.update') }}" method="POST" class="space-y-4">
                                @csrf
                                @method('PUT')
                                <div>
                                    <x-label for="microsoft_teams_client_id" :value="__('messages.client_id')" />
                                    <x-input id="microsoft_teams_client_id" name="microsoft_teams_client_id" type="text" class="mt-1 block w-full"
                                        :value="old('microsoft_teams_client_id', env('MICROSOFT_TEAMS_CLIENT_ID'))" />
                                    <x-input-error for="microsoft_teams_client_id" class="mt-2" />
                                </div>
                                <div>
                                    <x-label for="microsoft_teams_client_secret" :value="__('messages.client_secret')" />
                                    <x-input id="microsoft_teams_client_secret" name="microsoft_teams_client_secret" type="password" class="mt-1 block w-full" />
                                    <x-input-error for="microsoft_teams_client_secret" class="mt-2" />
                                </div>
                                <div>
                                    <x-label for="microsoft_teams_root_folder_id" :value="__('messages.root_folder_id')" />
                                    <x-input id="microsoft_teams_root_folder_id" name="microsoft_teams_root_folder_id" type="text" class="mt-1 block w-full"
                                        :value="old('microsoft_teams_root_folder_id', env('MICROSOFT_TEAMS_ROOT_FOLDER_ID'))" />
                                    <x-input-error for="microsoft_teams_root_folder_id" class="mt-2" />
                                </div>
                                <div class="flex justify-end">
                                    <x-button>
                                        {{ __('messages.save_changes') }}
                                    </x-button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Dropbox Configuration -->
                    <div x-show="selectedProvider === 'dropbox'" x-cloak class="py-6 first:pt-0 last:pb-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Dropbox</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                <a href="https://www.dropbox.com/developers/apps" target="_blank"
                                title="{{ __('messages.configure_dropbox_storage_link') }}" class="text-blue-500 hover:text-blue-700">
                                    {{ __('messages.configure_dropbox_storage_link_description') }}
                                </a>


                                </p>
                            </div>
                            <div class="flex items-center space-x-4">
                                @if(Storage::exists('dropbox-credentials.json'))
                                    <span class="px-3 py-1 text-sm text-green-800 bg-green-100 rounded-full">{{ __('messages.connected') }}</span>
                                    <form action="{{ route('admin.cloud-storage.dropbox.disconnect') }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            {{ __('messages.disconnect') }}
                                        </button>
                                    </form>
                                @else
                                    <span class="px-3 py-1 text-sm text-gray-800 bg-gray-100 rounded-full">{{ __('messages.not_connected') }}</span>
                                    <a href="{{ route('admin.cloud-storage.dropbox.connect') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        {{ __('messages.connect') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="mt-6">
                            <form action="{{ route('admin.cloud-storage.dropbox.update') }}" method="POST" class="space-y-4">
                                @csrf
                                @method('PUT')
                                <div>
                                    <x-label for="dropbox_client_id" :value="__('messages.client_id')" />
                                    <x-input id="dropbox_client_id" name="dropbox_client_id" type="text" class="mt-1 block w-full"
                                        :value="old('dropbox_client_id', env('DROPBOX_CLIENT_ID'))" />
                                    <x-input-error for="dropbox_client_id" class="mt-2" />
                                </div>
                                <div>
                                    <x-label for="dropbox_client_secret" :value="__('messages.client_secret')" />
                                    <x-input id="dropbox_client_secret" name="dropbox_client_secret" type="password" class="mt-1 block w-full" />
                                    <x-input-error for="dropbox_client_secret" class="mt-2" />
                                </div>
                                <div>
                                    <x-label for="dropbox_root_folder" :value="__('messages.root_folder')" />
                                    <x-input id="dropbox_root_folder" name="dropbox_root_folder" type="text" class="mt-1 block w-full"
                                        :value="old('dropbox_root_folder', env('DROPBOX_ROOT_FOLDER'))" />
                                    <x-input-error for="dropbox_root_folder" class="mt-2" />
                                </div>
                                <div class="flex justify-end">
                                    <x-button>
                                        {{ __('messages.save_changes') }}
                                    </x-button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Google Drive Configuration -->
                    <div x-show="selectedProvider === 'google-drive'" x-cloak class="py-6 first:pt-0 last:pb-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Google Drive</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank"
                                       title="{{ __('messages.configure_google_drive_storage_link') }}"
                                       class="text-blue-500 hover:text-blue-700">
                                        {{ __('messages.configure_google_drive_storage_link_description') }}
                                    </a>
                                </p>
                            </div>
                            <div class="flex items-center space-x-4">
                                @if(Storage::exists('google-credentials.json'))
                                    <span class="px-3 py-1 text-sm text-green-800 bg-green-100 rounded-full">{{ __('messages.connected') }}</span>
                                    <form action="{{ route('admin.cloud-storage.google-drive.disconnect') }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            {{ __('messages.disconnect') }}
                                        </button>
                                    </form>
                                @else
                                    <span class="px-3 py-1 text-sm text-gray-800 bg-gray-100 rounded-full">{{ __('messages.not_connected') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-6 space-y-6">
                            {{-- Credentials Form --}}
                            @include('admin.cloud-storage.google-drive.google-drive-credentials')

                            @unless(Storage::exists('google-credentials.json'))
                                {{-- Connect Button --}}
                                @include('admin.cloud-storage.google-drive.google-drive-connect')
                            @else
                                {{-- Root Folder Form --}}
                                @include('admin.cloud-storage.google-drive.google-drive-root-folder')
                            @endunless
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    function googleDriveFolderPicker() {
        return {
            showModal: false,
            rootFolderId: 'root',
            currentFolderId: @json(old('google_drive_root_folder_id', $currentFolderId ?? '')),
            initialFolderId: @json(old('google_drive_root_folder_id', $currentFolderId ?? '')),
            folderChanged: false,
            currentFolderName: @json($currentFolderName ?: __('messages.select_folder_prompt')),
            rootFolderName: '{{ __('messages.root_folder') }}',
            baseFolderShowUrl: '{{ url('/admin/cloud-storage/google-drive/folders') }}',
            folderStack: [],
            folders: [],
            newFolderName: '',
            init() {
                this.folderStack = [{ id: this.rootFolderId, name: this.rootFolderName }];
                // Remember initial folder to detect changes
                this.initialFolderId = this.currentFolderId;
                this.folderChanged = false;
                if (this.currentFolderId) {
                    fetch(`${this.baseFolderShowUrl}/${this.currentFolderId}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.folder && data.folder.name) {
                                this.currentFolderName = data.folder.name;
                            }
                        })
                        .catch(() => {});
                }
            },
            openModal() {
                this.showModal = true;
                this.folderStack = [{ id: this.rootFolderId, name: this.rootFolderName }];
                this.loadFolders(this.rootFolderId);
            },
            closeModal() {
                this.showModal = false;
            },
            loadFolders(parentId) {
                fetch(`{{ route('admin.cloud-storage.google-drive.folders') }}?parent_id=${parentId}`)
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
                fetch(`{{ route('admin.cloud-storage.google-drive.folders.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
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
                this.currentFolderId = selected.id;
                this.currentFolderName = selected.name;
                this.folderChanged = (this.currentFolderId !== this.initialFolderId);
                this.showModal = false;
            },
        };
    }
</script>
