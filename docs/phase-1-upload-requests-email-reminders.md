# Phase 1: UploadRequest Primitive + Email Reminders

## Context

The April 2026 competitive analysis identified automated reminders as one of two features most needed to compete in the accounting and legal verticals. Today the app supports "client uploads files to their firm," but there is no concept of an **outstanding request** — no way for a firm to say "I need X from client Y by date Z" and have the system follow up automatically.

This phase introduces the `UploadRequest` model as the foundational domain primitive and layers email-based automated reminders on top. It delivers immediate value with **zero external API dependencies** — no DocuSign, no Twilio, just the existing Laravel Mail + queue infrastructure.

**Scope decisions:**
- New `UploadRequest` model (not piggybacking on `ClientUserRelationship`)
- Email-only reminders (SMS deferred to Phase 3)
- Feature-flagged via `config('reminders.enabled')`

## Non-Goals

- E-signature workflow (Phase 2)
- SMS reminders via Twilio (Phase 3)
- Replacing the existing public upload flow
- Multi-party workflows

---

## New Model: `UploadRequest`

Represents "firm asked client for something."

### Schema (`upload_requests` table)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigIncrements | PK |
| `company_user_id` | foreignId | Admin/employee who created the request |
| `client_user_id` | foreignId | Client the request is addressed to |
| `title` | string | Short description ("Q1 tax documents") |
| `message` | text, nullable | Free-text instructions for the client |
| `status` | string | `pending` / `fulfilled` / `canceled` / `expired` |
| `due_at` | timestamp, nullable | Optional deadline |
| `requires_signature` | boolean | false in Phase 1; true triggers e-sig in Phase 2 |
| `reminder_cadence` | json | Array of hour offsets, e.g. `[24, 72, 168]` |
| `reminder_count` | unsignedInteger | How many reminders sent so far |
| `max_reminders` | unsignedInteger | Ceiling (default from config) |
| `next_reminder_at` | timestamp, nullable | Computed by `ReminderCadenceResolver` |
| `last_reminded_at` | timestamp, nullable | |
| `fulfilled_at` | timestamp, nullable | Set when all artifacts received |
| `unsubscribe_token` | string, unique | For signed unsubscribe URL |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Additional migration

`*_add_upload_request_id_to_file_uploads` — adds nullable `upload_request_id` FK on the existing `file_uploads` table so uploaded files can be associated with a specific request.

---

## Reminder Engine

### Services

- **`app/Services/Reminders/ReminderCadenceResolver.php`** — Given current `reminder_count` + `reminder_cadence` JSON array, returns the next `Carbon` send time. Returns `null` when `max_reminders` reached.
- **`app/Services/Reminders/ReminderScheduler.php`** — Sets `next_reminder_at` on the `UploadRequest` after each send. Handles quiet-hours by pushing the next send to the start of the next active window.

### Artisan command + scheduler

- **`app/Console/Commands/DispatchDueRemindersCommand.php`** — `php artisan reminders:dispatch`. Queries `upload_requests` where `status = pending AND next_reminder_at <= now()`, dispatches `DispatchDueRemindersJob` for each onto the queue.
- **`app/Console/Kernel.php`** — Schedule `reminders:dispatch` every 15 minutes (add after existing token maintenance schedule, ~line 188).

### Job

- **`app/Jobs/DispatchDueRemindersJob.php`** (`ShouldQueue`) — Sends `UploadRequestReminderMail`, increments `reminder_count`, calls `ReminderScheduler` to compute and set the next `next_reminder_at`.

### Mailables

All follow the existing `ClientBatchUploadConfirmation` pattern: `Queueable`, `SerializesModels`, markdown content.

- **`app/Mail/UploadRequestCreatedMail.php`** — Initial "you have a new request" email sent to the client when the request is created.
- **`app/Mail/UploadRequestReminderMail.php`** — Follow-up reminder email with request details, due date, upload link, and signed unsubscribe URL.
- **`app/Mail/UploadRequestFulfilledMail.php`** — Notification to the admin/employee that the client has completed the request.

### Stop conditions

- Request status changed to `fulfilled`, `canceled`, or `expired`
- `max_reminders` reached (`ReminderCadenceResolver` returns null)
- Client hits unsubscribe signed URL → sets `next_reminder_at = null`

---

## Events & Listeners

| Event | Listeners |
|-------|-----------|
| `UploadRequestCreated` | `SendUploadRequestCreatedMail`, `ScheduleInitialReminder` |
| `UploadRequestFulfilled` | `NotifyFirmOfFulfillment`, `CancelPendingReminders` |

**Extend existing listener:** `SendBatchUploadNotifications` — after sending current upload notifications, check if the uploaded `FileUpload` rows have an `upload_request_id`. If so, check whether the request's requirements are now met and fire `UploadRequestFulfilled` if complete.

---

## Admin/Employee UI

### Routes (in `routes/admin.php` under `upload-requests` prefix)

| Method | URI | Action | Description |
|--------|-----|--------|-------------|
| GET | `/upload-requests` | index | List requests, filterable by status/client/date |
| GET | `/upload-requests/create` | create | Form: select client, set title/message/due date/cadence |
| POST | `/upload-requests` | store | Create request, fire `UploadRequestCreated` |
| GET | `/upload-requests/{uploadRequest}` | show | Status timeline, linked uploads, reminder log |
| POST | `/upload-requests/{uploadRequest}/cancel` | cancel | Set status to `canceled`, clear reminders |

### Controller

`app/Http/Controllers/UploadRequestController.php` — gated to admin/employee via existing `admin` middleware.

---

## Client UI

### Routes (in `routes/client.php`)

| Method | URI | Action | Description |
|--------|-----|--------|-------------|
| GET | `/requests` | index | List pending/fulfilled requests |
| GET | `/requests/{uploadRequest}` | show | View details + upload files against this request |

### Controller

`app/Http/Controllers/ClientUploadRequestController.php`

### Dashboard integration

`app/Http/Controllers/Client/DashboardController.php` — add pending-request count badge to the client dashboard.

---

## Configuration

New `config/reminders.php`:

```php
return [
    'enabled' => env('REMINDERS_ENABLED', true),
    'default_cadence' => [24, 72, 168], // hours after creation
    'max_reminders' => env('REMINDERS_MAX', 5),
    'quiet_hours' => ['start' => '21:00', 'end' => '08:00'],
    'timezone' => env('REMINDERS_TIMEZONE', 'America/New_York'),
];
```

`.env.example` additions: `REMINDERS_ENABLED`, `REMINDERS_MAX`, `REMINDERS_TIMEZONE`

---

## Files Summary

### New files

```
app/Models/UploadRequest.php
app/Services/Reminders/ReminderScheduler.php
app/Services/Reminders/ReminderCadenceResolver.php
app/Console/Commands/DispatchDueRemindersCommand.php
app/Jobs/DispatchDueRemindersJob.php
app/Mail/UploadRequestReminderMail.php
app/Mail/UploadRequestCreatedMail.php
app/Mail/UploadRequestFulfilledMail.php
app/Events/UploadRequestCreated.php
app/Events/UploadRequestFulfilled.php
app/Listeners/SendUploadRequestCreatedMail.php
app/Listeners/ScheduleInitialReminder.php
app/Listeners/NotifyFirmOfFulfillment.php
app/Listeners/CancelPendingReminders.php
app/Http/Controllers/UploadRequestController.php
app/Http/Controllers/ClientUploadRequestController.php
database/migrations/*_create_upload_requests_table.php
database/migrations/*_add_upload_request_id_to_file_uploads.php
resources/views/emails/upload-request-reminder.blade.php
resources/views/emails/upload-request-created.blade.php
resources/views/emails/upload-request-fulfilled.blade.php
resources/views/admin/upload-requests/index.blade.php
resources/views/admin/upload-requests/create.blade.php
resources/views/admin/upload-requests/show.blade.php
resources/views/client/upload-requests/index.blade.php
resources/views/client/upload-requests/show.blade.php
config/reminders.php
tests/Unit/ReminderCadenceResolverTest.php
tests/Feature/UploadRequestCreationTest.php
tests/Feature/ReminderDispatchTest.php
```

### Modified files

- `app/Console/Kernel.php` — add `reminders:dispatch` every 15 min
- `app/Models/User.php` — add `uploadRequestsAsClient()` and `uploadRequestsAsCompany()` hasMany relationships
- `app/Models/FileUpload.php` — add nullable `upload_request_id` to `$fillable`, add `belongsTo(UploadRequest::class)`
- `app/Listeners/SendBatchUploadNotifications.php` — extend to check UploadRequest fulfillment
- `routes/admin.php` — upload-request CRUD routes
- `routes/client.php` — client request views
- `.env.example` — reminder config vars

### Reuse (do NOT reinvent)

- `app/Mail/ClientBatchUploadConfirmation.php` — Mailable pattern (Queueable, markdown content, signed unsubscribe URL)
- `app/Events/BatchUploadComplete.php` — Event class structure
- `app/Http/Controllers/NotificationSettingsController.php` — signed-URL unsubscribe pattern
- `app/Enums/UserRole.php` — admin/employee role gating
- `resources/views/emails/` — existing Blade email layout

---

## Verification

### Automated tests (`php artisan test`)

- **`ReminderCadenceResolverTest`** — correct next-send times for various cadences; returns null at max reminders
- **`UploadRequestCreationTest`** — admin creates request → fires `UploadRequestCreated` → client receives `UploadRequestCreatedMail` (assert via `Mail::fake()`)
- **`ReminderDispatchTest`** — `reminders:dispatch` with faked clock sends queued mail for due requests; skips fulfilled/canceled/expired; respects `max_reminders`; respects unsubscribe
- **Fulfillment test** — upload files against a request → `UploadRequestFulfilled` fires → firm gets notification, `next_reminder_at` cleared

### Manual end-to-end

1. `composer install && npm install && php artisan migrate && composer run dev`
2. Log in as admin → create UploadRequest for a test client with a 1-minute cadence (for testing)
3. Tail `php artisan queue:listen` output, confirm client receives initial email + first reminder
4. Upload files as the client against the request → verify fulfilled notification sent to firm and reminders stop
5. Test unsubscribe URL → confirm reminders stop for that request only

### Rollback

Feature flag `config('reminders.enabled')` disables the feature without removing code. Migration reversible via `php artisan migrate:rollback --step=2`.
