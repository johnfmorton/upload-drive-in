<x-mail::message>
# Email Configuration Test

This is a test email from **{{ $appName }}** to verify your mail configuration is working correctly.

**Test Details:**
- Test ID: `{{ $testId }}`
- Sent at: {{ $sentAt }}
- Server: {{ config('mail.mailers.smtp.host') }}

If you received this email, your SMTP configuration is working properly. You can now complete the setup process.

Thanks,<br>
{{ $appName }}
</x-mail::message>
