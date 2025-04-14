<x-mail::message>
# {{ __('messages.admin_batch_upload_heading') }}*

{{ trans_choice('messages.admin_batch_upload_body_intro', $fileUploads->count(), ['count' => $fileUploads->count(), 'userName' => $userName, 'userEmail' => $userEmail]) }}*

## {{ __('messages.uploaded_files_details') }}*:

@foreach($fileUploads as $file)
**{{ __('messages.file_label') }} {{ $loop->iteration }}:**
- **{{ __('messages.file_name') }}*: {{ $file->original_filename ?? 'N/A' }}
- **{{ __('messages.file_size') }}*: {{ format_bytes($file->file_size ?? 0) }} {{-- Assuming helper function 'format_bytes' exists --}}
@if($file->message)
- **{{ __('messages.file_message') }}*: {{ $file->message }}
@endif

@if(!$loop->last)
<hr>
@endif
@endforeach

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
