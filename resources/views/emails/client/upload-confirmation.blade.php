<x-mail::message>
# {{ __('messages.client_upload_heading') }}*

{{ __('messages.client_upload_body_intro', ['fileName' => $fileName]) }}*

{{ __('messages.client_upload_body_thanks') }}*

<x-mail::button :url="$unsubscribeUrl">
{{ __('messages.unsubscribe_button') }}*
</x-mail::button>

{{ __('messages.unsubscribe_text_prefix') }}* <a href="{{ $unsubscribeUrl }}">{{ $unsubscribeUrl }}</a>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
