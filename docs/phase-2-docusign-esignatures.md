# Phase 2: DocuSign E-Signatures (Pluggable Provider)

## Context

Phase 1 introduced the `UploadRequest` model and email reminders. This phase activates the `requires_signature` flag on `UploadRequest` and integrates DocuSign as the first e-signature provider behind a pluggable abstraction layer.

The pluggable provider pattern mirrors the existing `CloudStorageProviderInterface` → `CloudStorageManager` → `GoogleDriveProvider` / `S3Provider` architecture already in the codebase (`app/Contracts/CloudStorageProviderInterface.php`). This makes it straightforward to add Dropbox Sign, BoldSign, or other providers later without refactoring domain code.

**Depends on:** Phase 1 (UploadRequest model must exist)

**Feature-flagged via:** `config('services.docusign.enabled')`

## Non-Goals

- Document editor or template library (rely on DocuSign tabs/anchor tags)
- Multi-party signing (v1 = single client signer)
- Signature block placement UI (admin uploads pre-prepared PDFs)
- SMS notification of signing links (Phase 3)

---

## Provider Abstraction

### Interface

**`app/Contracts/SignatureProviderInterface.php`**

Mirrors `CloudStorageProviderInterface` structure. Methods:

```php
interface SignatureProviderInterface
{
    public function createEnvelope(UploadRequest $request, Collection $documents, User $signer): string;
    public function getEnvelopeStatus(string $envelopeId): string;
    public function getSigningUrl(string $envelopeId, User $signer): string;
    public function downloadSignedDocument(string $envelopeId): string; // returns local temp path
    public function voidEnvelope(string $envelopeId, string $reason): bool;
    public function verifyWebhook(Request $request): bool;
}
```

### Manager

**`app/Services/Signature/SignatureManager.php`** — resolves the active provider from config (default: `docusign`). Analogous to `CloudStorageManager`.

### DocuSign implementation

**`app/Services/Signature/DocuSignProvider.php`** — implements `SignatureProviderInterface`, wraps `docusign/esign-client`. Uses JWT grant authentication (integration key + RSA private key configured by admin).

**`app/Services/Signature/DocuSignWebhookVerifier.php`** — HMAC-SHA256 verification of DocuSign Connect webhook payloads.

---

## New Models

### `SignatureRequest`

One row per DocuSign envelope. Schema:

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigIncrements | PK |
| `upload_request_id` | foreignId | FK to `upload_requests` |
| `provider` | string | `docusign` (extensible) |
| `envelope_id` | string, nullable | Provider's envelope/transaction ID |
| `status` | string | `draft` / `sent` / `delivered` / `completed` / `voided` |
| `status_updated_at` | timestamp, nullable | Last status change from webhook |
| `signing_url` | text, nullable | URL for the client to sign |
| `signed_document_path` | string, nullable | Local/cloud path of signed PDF |
| `voided_reason` | string, nullable | If voided, why |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### `SignatureRequestDocument`

Links source PDFs to the envelope. Schema:

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigIncrements | PK |
| `signature_request_id` | foreignId | FK to `signature_requests` |
| `file_upload_id` | foreignId | FK to `file_uploads` (admin-uploaded source PDF) |
| `document_order` | unsignedInteger | Order within the envelope |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

## Workflow

1. **Admin creates UploadRequest** with `requires_signature = true` and uploads 1-N PDFs. PDFs stored via existing `FileUpload` flow (land in firm's connected Google Drive / S3).

2. **`UploadRequestCreated` event fires** (from Phase 1). A new listener `CreateDocuSignEnvelopeListener` checks `requires_signature` and dispatches `CreateDocuSignEnvelopeJob`.

3. **`CreateDocuSignEnvelopeJob`** (queued):
   - Downloads source PDFs from cloud storage via existing `CloudStorageManager`
   - Calls `SignatureManager->createEnvelope()` with the documents and client signer info
   - Stores returned `envelope_id` and `signing_url` on `SignatureRequest`
   - Fires `SignatureEnvelopeStatusChanged` with status `sent`

4. **Signing URL distribution**: The `UploadRequestReminderMail` (from Phase 1) is extended to include the signing URL when `requires_signature = true`. Client clicks the link, signs in DocuSign's UI.

5. **DocuSign Connect webhook** POSTs to `/webhooks/signature/docusign`:
   - `DocuSignWebhookController` verifies HMAC via `DocuSignWebhookVerifier`
   - Fires `SignatureEnvelopeStatusChanged` event with the new status
   - `RecordSignatureStatus` listener updates `SignatureRequest.status`
   - On `completed` status: dispatches `StoreSignedDocumentJob`

6. **`StoreSignedDocumentJob`** (queued):
   - Calls `SignatureManager->downloadSignedDocument()` to get signed PDF
   - Stores it via existing `CloudStorageManager` pipeline (creates a `FileUpload` row linked to the `UploadRequest`)
   - Checks if all requirements (uploads + signatures) are met
   - If complete, fires `UploadRequestFulfilled` (Phase 1 event) which triggers firm notification and cancels reminders

---

## Events & Listeners

| Event | Listener | Action |
|-------|----------|--------|
| `UploadRequestCreated` (Phase 1) | `CreateDocuSignEnvelopeListener` (new) | If `requires_signature`, dispatch `CreateDocuSignEnvelopeJob` |
| `SignatureEnvelopeStatusChanged` (new) | `RecordSignatureStatus` (new) | Update `SignatureRequest` status |
| `SignatureEnvelopeStatusChanged` | `StoreSignedDocumentJob` (new, on `completed` only) | Download + store signed PDF |

---

## Configuration

Add to `config/services.php`:

```php
'docusign' => [
    'enabled'         => env('DOCUSIGN_ENABLED', false),
    'base_uri'        => env('DOCUSIGN_BASE_URI', 'https://demo.docusign.net/restapi'),
    'integration_key' => env('DOCUSIGN_INTEGRATION_KEY'),
    'user_id'         => env('DOCUSIGN_USER_ID'),
    'account_id'      => env('DOCUSIGN_ACCOUNT_ID'),
    'private_key'     => env('DOCUSIGN_PRIVATE_KEY'),
    'webhook_secret'  => env('DOCUSIGN_WEBHOOK_SECRET'),
],
```

### Webhook route

In `routes/web.php`, add under a `/webhooks` group:

```php
Route::post('/webhooks/signature/docusign', [DocuSignWebhookController::class, 'handle'])
    ->name('webhooks.docusign');
```

Public route (no auth middleware), HMAC-verified in the controller.

---

## Files Summary

### New files

```
app/Contracts/SignatureProviderInterface.php
app/Models/SignatureRequest.php
app/Models/SignatureRequestDocument.php
app/Services/Signature/SignatureManager.php
app/Services/Signature/DocuSignProvider.php
app/Services/Signature/DocuSignWebhookVerifier.php
app/Jobs/CreateDocuSignEnvelopeJob.php
app/Jobs/StoreSignedDocumentJob.php
app/Events/SignatureEnvelopeStatusChanged.php
app/Listeners/RecordSignatureStatus.php
app/Listeners/CreateDocuSignEnvelopeListener.php
app/Http/Controllers/Webhooks/DocuSignWebhookController.php
database/migrations/*_create_signature_requests_table.php
database/migrations/*_create_signature_request_documents_table.php
tests/Unit/DocuSignWebhookVerifierTest.php
tests/Feature/DocuSignWebhookTest.php
tests/Feature/SignatureRequestCreationTest.php
```

### Modified files

- `composer.json` — add `docusign/esign-client`
- `config/services.php` — add `docusign` block
- `routes/web.php` — add `/webhooks/signature/docusign` route
- `app/Listeners/SendUploadRequestCreatedMail.php` — include signing URL when `requires_signature`
- `app/Mail/UploadRequestReminderMail.php` — include signing URL in reminder emails
- `resources/views/emails/upload-request-reminder.blade.php` — signing link section
- `resources/views/emails/upload-request-created.blade.php` — signing link section
- `resources/views/admin/upload-requests/create.blade.php` — signature toggle + PDF upload fields
- `resources/views/admin/upload-requests/show.blade.php` — signature status display
- `.env.example` — DocuSign env vars

### Reuse (do NOT reinvent)

- `app/Contracts/CloudStorageProviderInterface.php` — pattern for `SignatureProviderInterface`
- `app/Services/CloudStorageManager.php` — store signed PDFs via existing pipeline
- `app/Jobs/UploadToGoogleDrive.php` — job pattern for `StoreSignedDocumentJob`
- Phase 1 events/listeners — `UploadRequestCreated`, `UploadRequestFulfilled` are extended, not replaced

---

## Verification

### Automated tests (`php artisan test`)

- **`DocuSignWebhookVerifierTest`** — valid HMAC accepted, invalid/missing HMAC rejected
- **`DocuSignWebhookTest`** — POST with `envelope-completed` payload triggers `StoreSignedDocumentJob`; job creates `FileUpload` via faked `CloudStorageManager`; request flips to `fulfilled`
- **`SignatureRequestCreationTest`** — `UploadRequest` with `requires_signature=true` dispatches `CreateDocuSignEnvelopeJob` with correct envelope body (mock HTTP client)

### Manual end-to-end

1. Configure DocuSign sandbox credentials in `.env` (`DOCUSIGN_ENABLED=true`)
2. `php artisan migrate && composer run dev`
3. Admin creates UploadRequest with `requires_signature = true`, uploads a test PDF
4. Confirm `CreateDocuSignEnvelopeJob` runs in queue, `SignatureRequest` row created with `envelope_id`
5. Open signing URL as client, complete signing in DocuSign sandbox
6. Expose local webhook via `ngrok`, confirm DocuSign Connect POST arrives and is verified
7. Verify signed PDF appears in firm's cloud storage (Google Drive / S3)
8. Verify `UploadRequest` status flips to `fulfilled` and firm receives notification

### Rollback

Feature flag `config('services.docusign.enabled')` disables the feature. Migrations reversible via `php artisan migrate:rollback --step=2`. Phase 1 continues to function independently.
