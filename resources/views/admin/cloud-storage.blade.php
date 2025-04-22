<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.cloud_storage_configuration') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 divide-y divide-gray-200">
                    <!-- Microsoft Teams Configuration -->
                    <div class="py-6 first:pt-0 last:pb-0">
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
                    <div class="py-6 first:pt-0 last:pb-0">
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
                    <div class="py-6 first:pt-0 last:pb-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Google Drive</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                <a href="https://console.cloud.google.com/apis/credentials" target="_blank"
                                title="{{ __('messages.configure_google_drive_storage_link') }}" class="text-blue-500 hover:text-blue-700">
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
                                    <a href="{{ route('admin.cloud-storage.google-drive.connect') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        {{ __('messages.connect') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="mt-6">
                            <form action="{{ route('admin.cloud-storage.google-drive.update') }}" method="POST" class="space-y-4">
                                @csrf
                                @method('PUT')
                                <div>
                                    <x-label for="google_drive_client_id" :value="__('messages.client_id')" />
                                    <x-input id="google_drive_client_id" name="google_drive_client_id" type="text" class="mt-1 block w-full"
                                        :value="old('google_drive_client_id', env('GOOGLE_DRIVE_CLIENT_ID'))" />
                                    <x-input-error for="google_drive_client_id" class="mt-2" />
                                </div>
                                <div>
                                    <x-label for="google_drive_client_secret" :value="__('messages.client_secret')" />
                                    <x-input id="google_drive_client_secret" name="google_drive_client_secret" type="password" class="mt-1 block w-full" />
                                    <x-input-error for="google_drive_client_secret" class="mt-2" />
                                </div>
                                <div>
                                    <x-label for="google_drive_root_folder_id" :value="__('messages.root_folder_id')" />
                                    <x-input id="google_drive_root_folder_id" name="google_drive_root_folder_id" type="text" class="mt-1 block w-full"
                                        :value="old('google_drive_root_folder_id', env('GOOGLE_DRIVE_ROOT_FOLDER_ID'))" />
                                    <x-input-error for="google_drive_root_folder_id" class="mt-2" />
                                </div>
                                <div class="flex justify-end">
                                    <x-button>
                                        {{ __('messages.save_changes') }}
                                    </x-button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Default Provider Selection -->
                    <div class="py-6 first:pt-0 last:pb-0">
                        <form action="{{ route('admin.cloud-storage.default') }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div>
                                <x-label for="default_provider" :value="__('messages.default_storage_provider')" class="text-lg" />
                                <p class="mt-1 text-sm text-gray-500">{{ __('messages.select_default_provider_description') }}</p>
                                <select name="default_provider" id="default_provider"
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
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
