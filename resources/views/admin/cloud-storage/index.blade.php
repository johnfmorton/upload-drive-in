<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('messages.cloud_storage_configuration') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.cloud-storage.provider-management') }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    {{ __('Provider Management') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                @php
                    $availabilityService = app(\App\Services\CloudStorageProviderAvailabilityService::class);
                    $providersConfig = $availabilityService->getProviderConfigurationForFrontend();
                    $defaultProvider = $availabilityService->getDefaultProvider() ?? 'google-drive';
                    $selectedProvider = old('default_provider', config('cloud-storage.default', $defaultProvider));
                @endphp

                <div x-data="{ 
                    selectedProvider: '{{ $selectedProvider }}',
                    providersConfig: @js($providersConfig),
                    previousValidSelection: '{{ $selectedProvider }}',
                    
                    isProviderSelectable(provider) {
                        return this.providersConfig[provider]?.selectable ?? false;
                    },
                    
                    getProviderLabel(provider) {
                        return this.providersConfig[provider]?.label ?? provider;
                    },
                    
                    getProviderStatusLabel(provider) {
                        return this.providersConfig[provider]?.status_label ?? '';
                    },
                    
                    handleProviderChange() {
                        // If user selects a non-selectable provider, revert to previous valid selection
                        if (!this.isProviderSelectable(this.selectedProvider)) {
                            this.$nextTick(() => {
                                this.selectedProvider = this.previousValidSelection;
                            });
                            this.showProviderNotAvailableMessage();
                        } else {
                            this.previousValidSelection = this.selectedProvider;
                        }
                    },
                    
                    showProviderNotAvailableMessage() {
                        // Show a temporary message that the provider is not available
                        const helpElement = document.getElementById('provider-selection-help');
                        if (helpElement) {
                            helpElement.classList.add('fade-in');
                            setTimeout(() => {
                                helpElement.classList.remove('fade-in');
                            }, 3000);
                        }
                    },
                    
                    handleKeyDown(event) {
                        // Handle keyboard navigation
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            const select = event.target;
                            if (select.tagName === 'SELECT') {
                                select.click();
                            }
                        }
                    }
                }" x-cloak class="p-6 divide-y divide-gray-200">

                 <!-- Default Provider Selection -->
                    <div class="py-6 first:pt-0 last:pb-0">
                        <form action="{{ route('admin.cloud-storage.default') }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div>
                                <x-label for="default_provider" :value="__('messages.default_storage_provider')" class="text-lg" />
                                <p class="mt-1 text-sm text-gray-500">{{ __('messages.select_default_provider_description') }}</p>
                                
                                <div class="mt-2 relative">
                                    <select x-model="selectedProvider" 
                                            name="default_provider" 
                                            id="default_provider"
                                            class="provider-select enhanced-provider-select block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                                            aria-describedby="provider-selection-help"
                                            role="combobox"
                                            aria-expanded="false"
                                            aria-label="Select cloud storage provider"
                                            @change="handleProviderChange()"
                                            @keydown="handleKeyDown($event)">
                                        @foreach($providersConfig as $providerKey => $config)
                                            <option value="{{ $providerKey }}" 
                                                    @if($selectedProvider === $providerKey) selected @endif
                                                    @if(!$config['selectable']) disabled @endif
                                                    class="@if(!$config['selectable']) text-gray-400 bg-gray-50 @endif">
                                                {{ $config['label'] }}
                                                @if(!$config['selectable'])
                                                    ({{ $config['status_label'] }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    
                                    <!-- Visual indicator for selected provider status -->
                                    <div class="absolute inset-y-0 right-8 flex items-center pointer-events-none">
                                        <template x-if="!isProviderSelectable(selectedProvider)">
                                            <svg class="h-4 w-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                            </svg>
                                        </template>
                                        <template x-if="isProviderSelectable(selectedProvider)">
                                            <svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                            </svg>
                                        </template>
                                    </div>
                                </div>
                                
                                <!-- Provider status information -->
                                <div id="provider-selection-help" class="provider-help-text mt-2 text-sm" role="status" aria-live="polite">
                                    <template x-if="isProviderSelectable(selectedProvider)">
                                        <p class="text-green-600 flex items-center">
                                            <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                            </svg>
                                            <span x-text="getProviderLabel(selectedProvider)" class="mr-1"></span> is available and ready to use.
                                        </p>
                                    </template>
                                    <template x-if="!isProviderSelectable(selectedProvider)">
                                        <p class="text-amber-600 flex items-center">
                                            <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                            </svg>
                                            <span x-text="getProviderLabel(selectedProvider)"></span> is <span x-text="getProviderStatusLabel(selectedProvider).toLowerCase()"></span>. Please select an available provider.
                                        </p>
                                    </template>
                                </div>
                                
                                <x-input-error for="default_provider" class="mt-2" />
                            </div>
                            <div class="flex justify-end">
                                <x-button x-bind:disabled="!isProviderSelectable(selectedProvider)"
                                         x-bind:class="{ 'opacity-50 cursor-not-allowed': !isProviderSelectable(selectedProvider) }">
                                    {{ __('messages.save_changes') }}
                                </x-button>
                            </div>
                        </form>
                    </div>
                    <!-- Amazon S3 Configuration -->
                    <div x-show="selectedProvider === 'amazon-s3'" x-cloak class="py-6 first:pt-0 last:pb-0">
                        @include('admin.cloud-storage.amazon-s3.configuration')
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
                                @if(Auth::user()->hasGoogleDriveConnected())
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

                            @unless(Auth::user()->hasGoogleDriveConnected())
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
            currentFolderName: @json($currentFolderName ?: ''),
            rootFolderName: 'Google Drive Root',
            baseFolderShowUrl: '{{ url('/admin/cloud-storage/google-drive/folders') }}',
            folderStack: [],
            folders: [],
            newFolderName: '',
            init() {
                this.folderStack = [{ id: this.rootFolderId, name: this.rootFolderName }];
                // Remember initial folder to detect changes
                this.initialFolderId = this.currentFolderId;
                this.folderChanged = false;
                
                // If user has a specific folder configured, fetch its name
                if (this.currentFolderId && this.currentFolderId !== 'root') {
                    fetch(`${this.baseFolderShowUrl}/${this.currentFolderId}`)
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
                
                // Auto-save the selection to the database
                this.saveFolder();
            },
            saveFolder() {
                // Submit the form to save the folder selection
                // Use nextTick to ensure Alpine.js has updated the form data
                this.$nextTick(() => {
                    const form = document.getElementById('google-drive-folder-form');
                    if (form) {
                        // Double-check that the hidden input has the correct value
                        const hiddenInput = form.querySelector('input[name="google_drive_root_folder_id"]');
                        if (hiddenInput) {
                            hiddenInput.value = this.currentFolderId;
                            console.log('Auto-saving folder selection:', {
                                folderId: this.currentFolderId,
                                folderName: this.currentFolderName,
                                inputValue: hiddenInput.value
                            });
                        }
                        form.submit();
                    } else {
                        console.error('Google Drive folder form not found for auto-save');
                    }
                });
            },
            useGoogleDriveRoot() {
                this.currentFolderId = '';
                this.currentFolderName = '';
                this.folderChanged = (this.currentFolderId !== this.initialFolderId);
                this.showModal = false;
                
                // Auto-save the selection to the database
                this.saveFolder();
            },
        };
    }
</script>
