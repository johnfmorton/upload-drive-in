<x-mail::message>
# {{ __('messages.verify_email_title') }}

{{ __('messages.verify_email_intro', ['company_name' => config('app.company_name')]) }}

<x-mail::button :url="$verificationUrl">
{{ __('messages.verify_email_button') }}
</x-mail::button>

{{ __('messages.verify_email_ignore') }}

{{ __('messages.thanks_signature') }},<br>
{{ config('app.name') }}
</x-mail::message>