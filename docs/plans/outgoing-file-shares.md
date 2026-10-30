# Plan: Outgoing File Shares ("Send files")

Status: phases 0 and 1 shipped (2026-10-29); phases 2 and 3 not started.
Planned 2026-10-27. This doc describes what was built; where the build
changed the original plan, the change is noted.

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
| Revoke | Deletes the files immediately (after a confirmation prompt) |
| Size limits | 5 GB per file, 10 GB per share, 5 GB free-disk floor; all set in `.env` (server may change) |
| Download email | Phase 1: email the sender on a share's first download, reusing the upload notification pattern |

## 1. Data model

**`shares`**
- `id`, `ulid` (public id), `user_id` (creator)
- `token_hash`: SHA-256 of the link token, used to look shares up
- `token_encrypted`: the token encrypted with `APP_KEY`, so the sender can copy
  the link again later (changed during the build; a database leak alone still
  doesn't expose links)
- `title`, `message`, `password` (hashed, nullable), `recipient_emails` (JSON)
- `provider`, `cloud_user_id` (the account whose storage holds the files),
  `cloud_folder_id`; all pinned at creation
- `status`: `draft` → `uploading` → `ready` → `expired` / `revoked`, or
  `failed`. There is no `purged` status: a purged share keeps its status and
  gets `purged_at`.
- `expires_at`, `ready_at`, `revoked_at`, `purged_at`, `first_downloaded_at`,
  `download_count`, `last_accessed_at`, timestamps

**`share_files`**
- `share_id`, `original_filename`, `mime_type`, `file_size`
- `provider_file_id`, `local_path` (until the cloud upload finishes)
- `status` (`pending` / `uploaded` / `failed`), `last_error`, `download_count`

**`share_access_logs`**
- `share_id`, `share_file_id` (nullable, for a future zip download), `event`
  (`view` / `download` / `bad_password`; `zip` comes with phase 2), `ip`,
  `user_agent`, `created_at`

**`users.receive_share_download_notifications`** (boolean, default true)

## 2. Sender side (admin + employee)

- `routes/shares.php` is included from both `routes/admin.php`
  (`admin.shares.*`) and `routes/employee-portal.php` (`employee.shares.*`),
  and every route goes to one `ShareController`: index / create / store /
  show / status / finalize / revoke, plus per-file upload, remove and retry.
  `SharePolicy` limits employees to their own shares. Extending expiry is
  phase 2. There is no route that deletes a share record: revoke deletes the
  files, and the record stays in the list.
- The create page works in three steps:
  1. "Upload and create" saves the details as a `draft` share.
  2. The files upload into it in 5MB chunks (`pion/laravel-chunk-upload`,
     which works around the 20MB nginx limit). Every chunk is checked against
     the size limits.
  3. `finalize` moves the share to `uploading`.
- The upload code is `resources/js/share-upload.js`, its own Vite entry, so
  only the create page loads it. It uses Dropzone, which the app already uses
  elsewhere; the plan had said Uppy.
- The `UploadShareFileToCloud` job copies each assembled file to the
  provider. A per-file lock stops two copies running at once. The job has a
  1 hour timeout and retries for up to 12 hours.
  - **Drive:** uploads use `GoogleDriveChunkedUploadService` (resumable). The
    files go in the sender's own Drive, or the first admin's Drive if the
    sender hasn't connected one. Each share gets its own folder,
    `Outgoing shares/<title> (<ulid>)/`, kept separate from the folders
    organized by client email.
  - **S3:** files are stored under the key prefix `outgoing-share-<ulid>/`.
- A share becomes `ready` once every file is uploaded, and only then is the
  link shown or emailed. A single database update makes that change, so it
  can only happen once. The local copy is deleted after a successful upload.
  The detail page checks the status regularly while the upload is running.
- Size limits live in a new `config/shares.php`, read from `.env` so they can
  change with the server without a code change:

  | Variable | Default | Meaning |
  |---|---|---|
  | `SHARES_MAX_FILE_SIZE` | 5 GB | Largest single file (Google Drive's limit in `config/cloud-storage.php`) |
  | `SHARES_MAX_SHARE_SIZE` | 10 GB | Largest total size of one share |
  | `SHARES_MIN_FREE_DISK` | 5 GB | Refuse an upload if accepting it would leave less free disk than this |

  The caps are checked on the client and again on the server; the free-disk
  floor is checked on the server with `DiskSpaceMonitorService`. Files sit on
  the server until they've been copied to the cloud, so until phase 3, server
  disk is the practical limit. (Production on 2026-10-28: 77 GB disk, 36 GB
  free.)
- Form fields: files, title, optional message, optional password, expiry
  (7 / 14 / 30 days, default 14), optional recipient emails (the app emails
  them the link).

## 3. Recipient side (public)

- `GET /s/{token}`: landing page that lists files and sizes, 100 per page.
  If the share has a password, the page asks for it first and keeps it
  unlocked for the session (`POST /s/{token}/unlock`). The page uses its own
  `share-public` layout, which loads only CSS, no app JavaScript.
- `GET /s/{token}/files/{fileId}`: single-file download.
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

- `PurgeExpiredShares` (scheduled hourly in `routes/console.php`) queues a
  `PurgeShareFiles` job per share. It marks overdue shares `expired`,
  deletes their cloud files and folder, and sets `purged_at` only after the
  deletes succeed. Failures are retried on the next run.
- Revoke stops the link immediately and queues deletion of the cloud files
  right away; no grace period. The UI asks for confirmation first, since it
  can't be undone. If a deletion fails, the hourly purge job retries it.
- Shares stuck in `draft` or `uploading` for more than 24h
  (`stale_upload_hours` in `config/shares.php`) are marked `failed` and
  cleaned up, including their local files.
- The same job also picks up any `expired`, `revoked` or `failed` share whose
  `purged_at` is still empty, so failed deletions are retried.

## 5. Download notification (phase 1)

Reuses the existing upload notification pattern (`BatchUploadComplete` →
`SendBatchUploadNotifications` → `AdminBatchUploadNotification`):

- `ShareFirstDownloaded` event → queued `SendShareDownloadedNotification`
  listener → `ShareDownloadedMail` (`resources/views/emails/shares/downloaded`).
- The link email to recipients works the same way: `ShareReady` →
  `SendShareLinkToRecipients` → `ShareLinkMail`.
- Sent once per share: the event fires only when a single database update
  that sets `first_downloaded_at` where it is still empty succeeds, so
  downloads that start at the same moment can't send two emails.
- The email says which share, when it was downloaded, and which file (or
  "all files"), and links to the share's detail page.
- Opt-out: a new `users.receive_share_download_notifications` setting
  (default true) with an unsubscribe link built the same way as
  `NotificationSettingsController::unsubscribeUploads`. It's kept separate from
  `receive_upload_notifications` so turning off one doesn't turn off the
  other.

## 6. Security

- Tokens are 32 random bytes, encoded base64url, which gives a 43-character
  token; the route only matches that shape. The database stores the token's
  hash plus a copy encrypted with `APP_KEY` (see section 1).
- Public pages send `X-Robots-Tag: noindex, nofollow`. They set the referrer
  policy to no-referrer with a `<meta name="referrer">` tag in the
  `share-public` layout instead of a header.
- Rate limiters, defined in `AppServiceProvider` like the existing ones:
  - `share-pages` (page views and unlock attempts): 60 a minute per IP
  - password attempts: 5 per share and IP per 15 minutes, plus 20 per share
    from all IPs per hour; failures are logged as `bad_password`
  - `share-downloads`: 60 a minute per IP
- Download URLs are generated fresh on each click and never embedded in the
  page ahead of time.

## 7. Performance

- The recipient page is server-rendered Blade with minimal JS. A share with
  many files gets a paginated list.
- Large downloads never pass through PHP memory (S3 presigned redirect / Drive
  streaming).

## 8. Phases

0. **Prerequisite (done):** stream S3 downloads instead of loading the whole object
   into memory (`FileManagerService::downloadS3File`). This also fixes large S3
   downloads in the admin and employee file managers.
1. **MVP (done):**
   - migrations and models
   - create share with chunked upload to the cloud
   - share list and detail pages for the sender
   - public page with per-file download, including Drive Range support
   - password and expiry
   - purge job, recipient email, revoke
   - first-download email to the sender
2. **Phase 2 (not started):**
   - "Download all" streamed zip
   - access log on the share detail page (the data is already logged)
   - extend expiry
3. **Phase 3 (optional):** the browser uploads straight to S3 (Uppy S3
   multipart with presigned parts), bypassing the app server and its disk.

## 9. Testing

- PHPUnit feature tests:
  - token lookup and the 404/410 behavior
  - password gate and its rate limit
  - employee scoping (an employee can't see another employee's shares)
  - purge job state changes, with mocked providers
  - the first-download email is sent exactly once, and respects the opt-out
- Jest: the create-share upload code (`tests/js/share-upload.test.js`).
- Update `CHANGELOG.md`.

The PHPUnit tests are in `tests/Feature/Shares/` and
`tests/Unit/Services/ShareStorageServiceTest.php`.

## Deployment notes

- Raise the queue's `retry_after` (`REDIS_QUEUE_RETRY_AFTER` or
  `DB_QUEUE_RETRY_AFTER`) to about 3700 seconds, and run the Forge queue
  worker with `--timeout=3600`. Otherwise a large Drive upload gets retried
  while it is still running.
- Files are staged in `storage/app/private/shares` until they are copied to
  the cloud.

## Still to verify by hand

- A full share through Google Drive, including resuming an interrupted
  download (S3 was tested end to end against the dev bucket).
- The recipient email and the first-download email arriving in a real inbox.

## Open questions

None at the moment.
