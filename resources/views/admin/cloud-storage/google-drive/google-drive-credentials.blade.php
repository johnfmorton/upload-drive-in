{{-- Google Drive App Credentials Form --}}
<form action="{{ route('admin.cloud-storage.google-drive.credentials.update') }}" method="POST" class="space-y-4">
    @csrf
    @method('PUT')
    
    @if($googleDriveEnvSettings['client_id'] || $googleDriveEnvSettings['client_secret'])
        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Environment Configuration</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Some Google Drive settings are configured via environment variables and cannot be edited here:</p>
                        <ul class="list-disc list-inside mt-1">
                            @if($googleDriveEnvSettings['client_id'])
                                <li>Client ID</li>
                            @endif
                            @if($googleDriveEnvSettings['client_secret'])
                                <li>Client Secret</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div>
        <x-label for="google_drive_client_id" :value="__('messages.client_id')" />
        @if($googleDriveEnvSettings['client_id'])
            <x-input id="google_drive_client_id" type="text" class="mt-1 block w-full bg-gray-100" 
                :value="env('GOOGLE_DRIVE_CLIENT_ID')" readonly />
            <p class="mt-1 text-sm text-gray-500">This value is configured via environment variables.</p>
        @else
            <x-input id="google_drive_client_id" name="google_drive_client_id" type="text" class="mt-1 block w-full"
                :value="old('google_drive_client_id', \App\Models\CloudStorageSetting::getValue('google-drive', 'client_id'))" required />
        @endif
        <x-input-error for="google_drive_client_id" class="mt-2" />
    </div>
    
    <div>
        <x-label for="google_drive_client_secret" :value="__('messages.client_secret')" />
        @if($googleDriveEnvSettings['client_secret'])
            <x-input id="google_drive_client_secret" type="password" class="mt-1 block w-full bg-gray-100" 
                value="********" readonly />
            <p class="mt-1 text-sm text-gray-500">This value is configured via environment variables.</p>
        @else
            <x-input
                id="google_drive_client_secret"
                name="google_drive_client_secret"
                type="password"
                class="mt-1 block w-full"
                placeholder="{{ \App\Models\CloudStorageSetting::getValue('google-drive', 'client_secret') ? '********' : '' }}"
            />
        @endif
        <x-input-error for="google_drive_client_secret" class="mt-2" />
    </div>
    
    @unless($googleDriveEnvSettings['client_id'] && $googleDriveEnvSettings['client_secret'])
        <div class="flex justify-end">
            <x-button type="submit">{{ __('messages.save_google_app_credentials') }}</x-button>
        </div>
    @endunless
</form>
