# Phase 3: Twilio SMS Reminders

## Context

Phases 1 and 2 delivered the `UploadRequest` model, email reminders, and DocuSign e-signatures. This phase adds SMS as a second reminder channel via Twilio, completing the feature set identified in the April 2026 competitive analysis as critical for accounting/legal verticals.

SMS reminders pull clients back to the app more effectively than email alone — particularly for time-sensitive document requests with approaching deadlines.

**Depends on:** Phase 1 (UploadRequest + email reminders), optionally Phase 2 (e-signatures)

**Feature-flagged via:** `config('services.twilio.enabled')`

**Status:** Deferred per user decision. This document captures the full design for when the team is ready to build.

## Non-Goals

- WhatsApp or other messaging platforms (Twilio supports them but v1 is SMS only)
- Two-way SMS conversations (reminders are outbound-only)
- SMS-based file upload (clients must use the web UI)

---

## User Model Changes

### New columns on `users` table

| Column | Type | Notes |
|--------|------|-------|
| `phone` | string, nullable | E.164 format phone number |
| `phone_verified_at` | timestamp, nullable | When phone was verified via SMS code |
| `sms_opt_in` | boolean, default false | Client must explicitly opt in to SMS |

Migration: `*_add_phone_and_sms_prefs_to_users.php`

### Phone verification flow

1. Client enters phone number in profile settings
2. System sends 6-digit code via Twilio SMS
3. Client enters code to verify → sets `phone_verified_at`
4. Client toggles `sms_opt_in` (only available after phone verified)

SMS reminders only sent when BOTH `phone_verified_at IS NOT NULL` AND `sms_opt_in = true`.

---

## Twilio Integration

### Service

**`app/Services/Sms/TwilioClientFactory.php`** — creates and configures the Twilio SDK client from config. Follows the factory pattern used by `CloudStorageFactory`.

### Laravel Notification Channel

**`app/Notifications/Channels/TwilioSmsChannel.php`** — custom Laravel Notification channel.

```php
class TwilioSmsChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (!$notifiable->phone || !$notifiable->phone_verified_at || !$notifiable->sms_opt_in) {
            return; // silently skip
        }

        $message = $notification->toSms($notifiable);

        $this->twilio->messages->create(
            $notifiable->phone,
            [
                'from' => config('services.twilio.from'),
                'body' => $message->content,
                'statusCallback' => config('services.twilio.status_url'),
            ]
        );
    }
}
```

### Notification class

**`app/Notifications/UploadRequestReminderNotification.php`** — replaces or wraps the Phase 1 `DispatchDueRemindersJob` direct Mailable sends. Implements `ShouldQueue`.

```php
public function via($notifiable): array
{
    $channels = ['mail'];

    if (config('services.twilio.enabled') && $notifiable->sms_opt_in && $notifiable->phone_verified_at) {
        $channels[] = TwilioSmsChannel::class;
    }

    return $channels;
}

public function toMail($notifiable): UploadRequestReminderMail { /* existing Phase 1 mailable */ }

public function toSms($notifiable): SmsMessage { /* short text with request title + link */ }
```

### Status webhook

**`app/Http/Controllers/Webhooks/TwilioStatusWebhookController.php`** — receives Twilio delivery status callbacks. Logs delivery status (delivered, failed, undelivered) for monitoring. Validates request signature via Twilio's `RequestValidator`.

Route in `routes/web.php`:

```php
Route::post('/webhooks/twilio/status', [TwilioStatusWebhookController::class, 'handle'])
    ->name('webhooks.twilio.status');
```

---

## Quiet Hours

Enhance `ReminderCadenceResolver` (Phase 1) to enforce quiet hours for SMS:

- If the next computed send time falls within `config('reminders.quiet_hours')`, push it to the end of the quiet window.
- Email may still send during quiet hours (configurable); SMS never does.
- Timezone from `config('reminders.timezone')`.

---

## Configuration

Add to `config/services.php`:

```php
'twilio' => [
    'enabled'    => env('TWILIO_ENABLED', false),
    'sid'        => env('TWILIO_ACCOUNT_SID'),
    'token'      => env('TWILIO_AUTH_TOKEN'),
    'from'       => env('TWILIO_FROM_NUMBER'),
    'status_url' => env('APP_URL') . '/webhooks/twilio/status',
],
```

`.env.example` additions: `TWILIO_ENABLED`, `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM_NUMBER`

---

## Files Summary

### New files

```
app/Services/Sms/TwilioClientFactory.php
app/Notifications/Channels/TwilioSmsChannel.php
app/Notifications/UploadRequestReminderNotification.php
app/Http/Controllers/Webhooks/TwilioStatusWebhookController.php
app/Http/Controllers/Client/PhoneVerificationController.php
database/migrations/*_add_phone_and_sms_prefs_to_users.php
resources/views/client/profile/phone-verification.blade.php
tests/Feature/TwilioSmsChannelTest.php
tests/Feature/PhoneVerificationTest.php
tests/Feature/TwilioWebhookTest.php
```

### Modified files

- `composer.json` — add `twilio/sdk`
- `config/services.php` — add `twilio` block
- `routes/web.php` — add `/webhooks/twilio/status` route
- `routes/client.php` — add phone verification routes
- `app/Models/User.php` — add `phone`, `phone_verified_at`, `sms_opt_in` to `$fillable` and `$casts`
- `app/Jobs/DispatchDueRemindersJob.php` — switch from direct Mailable to `UploadRequestReminderNotification` (which handles both mail + SMS channels)
- `app/Services/Reminders/ReminderCadenceResolver.php` — add quiet-hours enforcement for SMS
- `resources/views/client/profile/` — add phone number + opt-in UI to client profile
- `.env.example` — Twilio env vars

### Reuse

- Phase 1 `DispatchDueRemindersJob` — refactored to use Notification instead of direct Mailable
- Phase 1 `ReminderCadenceResolver` — extended with quiet-hours logic
- Phase 1 `ReminderScheduler` — unchanged, still manages `next_reminder_at`
- Existing `NotificationSettingsController` unsubscribe pattern — extended for SMS opt-out

---

## Verification

### Automated tests (`php artisan test`)

- **`TwilioSmsChannelTest`** — channel calls mocked Twilio client with correct payload (phone, from, body); skips send when `sms_opt_in = false` or phone unverified
- **`PhoneVerificationTest`** — verification code sent via Twilio mock; correct code verifies phone; expired/wrong code rejected
- **`TwilioWebhookTest`** — valid Twilio signature accepted; invalid rejected; delivery status logged
- **Quiet-hours test** — `ReminderCadenceResolver` pushes SMS send time out of quiet window

### Manual end-to-end

1. Configure Twilio trial account credentials in `.env` (`TWILIO_ENABLED=true`)
2. Register a verified test phone number in Twilio console (trial limitation)
3. As client, add phone number in profile → receive verification SMS → enter code
4. Enable SMS opt-in
5. Admin creates UploadRequest with short cadence
6. Confirm client receives both email AND SMS reminder
7. Test during quiet hours → verify SMS is deferred, email still sends
8. Test opt-out → confirm SMS stops, email continues

### Rollback

Feature flag `config('services.twilio.enabled')` disables SMS. Phases 1 and 2 continue to function independently. Migration reversible via `php artisan migrate:rollback --step=1`.
