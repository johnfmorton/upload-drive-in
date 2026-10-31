<x-mail::message>
# {{ __('messages.shares_email_downloaded_heading') }}

@if ($fileName)
{{ __('messages.shares_email_downloaded_body', ['title' => $share->title, 'file' => $fileName, 'date' => $downloadedAt->toDayDateTimeString()]) }}
@else
{{ __('messages.shares_email_downloaded_body_all', ['title' => $share->title, 'date' => $downloadedAt->toDayDateTimeString()]) }}
@endif

<x-mail::button :url="$shareUrl">
{{ __('messages.shares_email_downloaded_button') }}
</x-mail::button>

{{ __('messages.shares_email_downloaded_once') }}

{{ __('messages.email_signature') }},<br>
{{ config('app.name') }}

<x-slot:subcopy>
[{{ __('messages.shares_email_unsubscribe') }}]({{ $unsubscribeUrl }})
</x-slot:subcopy>
</x-mail::message>
