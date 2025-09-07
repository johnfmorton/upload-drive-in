<x-mail::message>
# {{ __('messages.admin_verify_email_title') }}

{{ __('messages.admin_verify_email_intro', ['company_name' => config('app.company_name', config('app.name'))]) }}

<x-mail::button :url="$verificationUrl">
{{ __('messages.admin_verify_email_button') }}
</x-mail::button>

{{ __('messages.verify_email_ignore') }}

{{ __('messages.thanks_signature') }},<br>
{{ config('app.name') }}
</x-mail::message>