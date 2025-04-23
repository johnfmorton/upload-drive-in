{{-- Google Drive Connect Button --}}
<form action="{{ route('admin.cloud-storage.google-drive.connect') }}" method="POST">
    @csrf
    <div class="flex justify-end">
        <x-button type="submit">{{ __('messages.connect') }}</x-button>
    </div>
</form>
