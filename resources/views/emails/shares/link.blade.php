<x-mail::message>
# {{ $share->title }}

{{ __('messages.shares_email_link_intro', ['name' => $senderName]) }}

@if ($share->message)
<x-mail::panel>
{{ $share->message }}
</x-mail::panel>
@endif

{{ trans_choice('messages.shares_public_file_count', $fileCount, ['count' => $fileCount]) }} · {{ format_bytes($totalSize) }}

<x-mail::button :url="$url">
{{ __('messages.shares_email_link_button') }}
</x-mail::button>

{{ __('messages.shares_email_link_expires', ['date' => $share->expires_at->toFormattedDateString()]) }}
@if ($share->hasPassword())
{{ __('messages.shares_email_link_password') }}
@endif

{{ __('messages.email_signature') }},<br>
{{ config('app.name') }}
</x-mail::message>
