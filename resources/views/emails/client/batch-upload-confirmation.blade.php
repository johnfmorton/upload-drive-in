<x-mail::message>
# {{ __('messages.client_batch_upload_heading') }}*

{{ trans_choice('messages.client_batch_upload_body', $fileCount, ['count' => $fileCount]) }}*

**{{ __('messages.uploaded_files_list') }}*:**
<ul>
@foreach($fileNames as $fileName)
<li>{{ $fileName }}</li>
@endforeach
</ul>

{{ __('messages.upload_thank_you') }}*

Thanks,<br>
{{ config('app.name') }}

<x-slot:subcopy>
@lang(
    "messages.unsubscribe_link_text",
    [
        'actionText' => __('messages.unsubscribe_action_text') . '*'
    ]
)
<span class="break-all">[{{ $unsubscribeUrl }}]({{ $unsubscribeUrl }})</span>
</x-slot>
</x-mail::message>
