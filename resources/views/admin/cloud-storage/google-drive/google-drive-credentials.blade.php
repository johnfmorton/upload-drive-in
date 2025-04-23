{{-- Google Drive App Credentials Form --}}
<form action="{{ route('admin.cloud-storage.google-drive.credentials.update') }}" method="POST" class="space-y-4">
    @csrf
    @method('PUT')
    <div>
        <x-label for="google_drive_client_id" :value="__('messages.client_id')" />
        <x-input id="google_drive_client_id" name="google_drive_client_id" type="text" class="mt-1 block w-full"
            :value="old('google_drive_client_id', env('GOOGLE_DRIVE_CLIENT_ID'))" required />
        <x-input-error for="google_drive_client_id" class="mt-2" />
    </div>
    <div>
        <x-label for="google_drive_client_secret" :value="__('messages.client_secret')" />
        <x-input
            id="google_drive_client_secret"
            name="google_drive_client_secret"
            type="password"
            class="mt-1 block w-full"
            placeholder="{{ env('GOOGLE_DRIVE_CLIENT_SECRET') ? '********' : '' }}"
        />
        <x-input-error for="google_drive_client_secret" class="mt-2" />
    </div>
    <div class="flex justify-end">
        <x-button type="submit">{{ __('messages.save_google_app_credentials') }}</x-button>
    </div>
</form>
