# Plan: Outgoing File Shares ("Send files")

Status: planned (2026-10-27)

## Goal

Let admins and employees send someone a link to one large file or a large
collection of files. The sender uploads files into a **share**, which is stored
in the configured cloud provider. The app generates an unguessable link, with an
optional password. Anyone holding the link can download the files until the
share expires. After that, the files are deleted from cloud storage
automatically.

### Decisions

| Question | Decision |
|---|---|
| File source | Newly uploaded files only (separate outbound flow, not existing `FileUpload` records) |
| Who can create shares | Admins and employees (employees see/manage only their own) |
| Recipient access | Anyone with the link; optional per-share password |
| Expiry | Link expires; scheduled job deletes the files from cloud storage |

## 1. Data model

**`shares`**
- `id`, `ulid` (public id), `user_id` (creator)
- `token_hash`: SHA-256 of the link token; the raw token is never stored
- `title`, `message`, `password` (hashed, nullable)
- `provider`, `cloud_folder_id` (pinned at creation)
- `status`: `uploading` → `ready` → `expired` / `revoked` → `purged`
- `expires_at`, `purged_at`, `download_count`, `last_accessed_at`, timestamps

**`share_files`**
- `share_id`, `original_filename`, `mime_type`, `file_size`
- `provider_file_id`, `local_path` (until the cloud upload finishes)
- `status`, `download_count`

**`share_access_logs`**
- `share_id`, `share_file_id` (nullable for zip), `event`
  (view / download / zip / bad_password), `ip`, `user_agent`, `created_at`

## 2. Sender side (admin + employee)

- Routes in `routes/admin.php` and `routes/employee-portal.php` point to a
  shared controller: index / create / store / show / revoke / extend / destroy.
  A policy scopes employees to their own shares.
- Uploads reuse the existing 5MB chunked upload (`pion/laravel-chunk-upload`,
  same pattern as `upload.employee.chunk`), which works around the 20MB nginx
  limit. Uppy is loaded only on the create page.
- The `UploadShareFileToCloud` job copies each assembled file to the provider.
  Drive uploads use `GoogleDriveChunkedUploadService` (resumable). Drive folder
  layout: `Outgoing/<share-ulid>/`, kept separate from the folders organized by
  client email.
- A share becomes `ready` only once every file is uploaded; only then is the
  link shown or emailed. The local copy is deleted after a successful upload.
  Large uploads are checked against `DiskSpaceMonitorService` before they are
  accepted.
- Form fields: files, title, optional message, optional password, expiry
  (7 / 14 / 30 days, default 14), optional recipient emails (the app emails
  them the link).

## 3. Recipient side (public)

- `GET /s/{token}`: landing page that lists files and sizes. If the share has
  a password, the page asks for it first and keeps it unlocked for the session.
- `GET /s/{token}/files/{file}`: single-file download.
  - **S3:** redirect to a short-lived (~5 min) presigned URL with
    `ResponseContentDisposition` set to the original filename. The file never
    passes through the app server.
  - **Google Drive:** stream through the app in pieces, with **Range** support
    so interrupted downloads can resume.
- `GET /s/{token}/zip` (phase 2): "Download all" as a zip built while it
  downloads (`maennchen/zipstream-php`, no compression). It reads from the
  cloud file by file, with no temp zip on disk. Needs `set_time_limit(0)` and
  a longer nginx `fastcgi_read_timeout` on Forge.
- An expired, revoked or unknown token returns the same generic "no longer
  available" page (410/404), so the page doesn't reveal which shares exist.

## 4. Expiry and cleanup

- `PurgeExpiredSharesJob` runs hourly. It marks overdue shares `expired`,
  deletes their cloud files and folder, and sets `purged_at` only after the
  deletes succeed. Failures are retried on the next run.
- Revoke stops the link immediately. Files are purged after a grace period
  (default 24h).
- Shares stuck in `uploading` for more than 24h, and their leftover local
  chunks, are cleaned up.

## 5. Security

- Tokens are 32 random bytes, encoded base64url; only the hash is stored.
- Public routes send `X-Robots-Tag: noindex` and `Referrer-Policy: no-referrer`.
- Rate limiters, defined in `AppServiceProvider` like the existing ones:
  - share page views, per IP
  - password attempts, per share and IP; failures are logged
  - downloads
- Download URLs are generated fresh on each click and never embedded in the
  page ahead of time.

## 6. Performance

- The recipient page is server-rendered Blade with minimal JS. A share with
  many files gets a paginated list.
- Large downloads never pass through PHP memory (S3 presigned redirect / Drive
  streaming).

## 7. Phases

0. **Prerequisite:** stream S3 downloads instead of loading the whole object
   into memory (`FileManagerService::downloadS3File`). This also fixes large S3
   downloads in the admin and employee file managers.
1. **MVP:**
   - migrations and models
   - create share with chunked upload to the cloud
   - share list and detail pages for the sender
   - public page with per-file download, including Drive Range support
   - password and expiry
   - purge job, recipient email, revoke
2. **Phase 2:**
   - "Download all" streamed zip
   - email to the sender on first download
   - access log on the share detail page
   - extend expiry
3. **Phase 3 (optional):** the browser uploads straight to S3 (Uppy S3
   multipart with presigned parts), bypassing the app server and its disk.

## 8. Testing

- PHPUnit feature tests:
  - token lookup and the 404/410 behavior
  - password gate and its rate limit
  - employee scoping (an employee can't see another employee's shares)
  - purge job state changes, with mocked providers
- Jest: the create-share upload code.
- Update `CHANGELOG.md`.

## Open questions (defaults assumed)

1. Size limits per share? Default: no cap, just the disk-space check.
2. Revoke: delete files immediately or after the grace period? Default: 24h
   grace period.
3. Email to the sender on first download: MVP or phase 2? Default: phase 2.
