<x-mail::message>
# {{ __('messages.admin_batch_upload_heading') }}*

{{ trans_choice('messages.admin_batch_upload_body_intro', $fileUploads->count(), ['count' => $fileUploads->count(), 'userName' => $userName, 'userEmail' => $userEmail]) }}*

## {{ __('messages.uploaded_files_details') }}*:

@foreach($fileUploads as $file)

@include('emails.admin._batch-file-details', ['file' => $file, 'loop' => $loop])

@if(!$loop->last)

---

@endif
@endforeach

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
