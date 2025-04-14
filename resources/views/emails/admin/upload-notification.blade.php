<x-mail::message>
# {{ __('messages.admin_upload_heading') }}*

{{ __('messages.admin_upload_body_intro', ['userName' => $userName, 'userEmail' => $userEmail]) }}*

**{{ __('messages.file_details') }}*:**
- **{{ __('messages.file_name') }}*: {{ $fileName }}
- **{{ __('messages.file_size') }}*: {{ format_bytes($fileSize) }} {{-- Assuming you have a helper function 'format_bytes' --}}
@if($fileMessage)
- **{{ __('messages.file_message') }}*: {{ $fileMessage }}
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
