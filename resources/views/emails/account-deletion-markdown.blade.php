<x-mail::message>
# {{ __('messages.delete_account_email_title') }}

{{ __('messages.delete_account_email_request', ['app_name' => config('app.name')]) }}

**{{ __('messages.delete_account_email_warning') }}**

{{ __('messages.delete_account_email_proceed') }}

<x-mail::button :url="$verificationUrl" color="error">
{{ __('messages.delete_account_email_confirm_button') }}
</x-mail::button>

{{ __('messages.delete_account_email_ignore') }}

{{ __('messages.thanks_signature') }},<br>
{{ config('app.name') }}
</x-mail::message>