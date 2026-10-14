# Server Migration Checklist

Moving Upload Drive-In (uploads.morton.dev) from the current Forge server
(`ssh jfm`, site id 3027426) to a new server.

Part 1 is the to-do list for the move itself. Part 2 is the checklist for
confirming everything works afterwards. Tick items as you go.

> **The one thing that must not change: `APP_KEY`.** Google Drive tokens, cloud
> storage settings and 2FA secrets are stored encrypted in the database. A new
> key makes all of them unreadable. Copy `.env` from the old server; never let
> Forge or `key:generate` create a fresh one.

---

## Part 1 — To do

### Before the move

- [ ] **Deploy the scheduler fix first.** Previously the scheduled tasks
      lived in `app/Console/Kernel.php`, which Laravel 12 never loads, so none
      of them ran. They're now in `routes/console.php`. Deploy that to the old
      server and confirm `php artisan schedule:list` shows 12 tasks, so you
      know the schedule works before you move it.
- [ ] **Record the old server's setup** so you can recreate it. Run on the old
      server and save the output:
      ```bash
      cd /home/forge/uploads.morton.dev/current
      php -v && php -m                               # PHP 8.4 + extensions
      php -i | grep -E 'upload_max_filesize|post_max_size|memory_limit|max_execution_time'
      cat /etc/nginx/conf.d/uploads.conf             # client_max_body_size 20M
      grep -n fastcgi_read_timeout /etc/nginx/sites-available/uploads.morton.dev
      grep -E '^(DB_CONNECTION|QUEUE_CONNECTION|SESSION_DRIVER|CACHE_STORE|CLAMAV_|CLOUD_STORAGE_DEFAULT|MAIL_MAILER|TRUSTED_PROXIES)' ../.env
      crontab -l; sudo ls /etc/supervisor/conf.d/   # scheduler + queue workers
      sudo cat /etc/supervisor/conf.d/*.conf | grep command
      systemctl is-active clamav-daemon 2>/dev/null
      du -sh ../storage/app/*                        # local file volume to copy
      ```
- [ ] **Check which queues the worker processes.** The app uses five:
      `default`, `high`, `maintenance`, `notifications`, `recovery`. The new
      worker needs all of them, e.g.
      `--queue=high,default,notifications,recovery,maintenance`. If the old
      worker only runs `default`, jobs on the other queues have been piling up;
      check the `jobs` table.
- [ ] **Lower the DNS TTL** for uploads.morton.dev to ~300 seconds at least a
      day before the move.
- [ ] **Size the new server:** the old one was at 59% swap usage, so give the
      new one more RAM or check what's using memory.
- [ ] **Decide the domain.** If it stays uploads.morton.dev, the Google OAuth
      redirect URI and email links keep working. If it changes, see "If the
      domain changes" below.

### Provision the new server (Forge)

- [ ] Create the server with **PHP 8.4**, Node 18+ (DDEV uses 18), and the
      same database engine and version as the old one.
- [ ] Install the PHP extensions the old server has (compare `php -m`),
      including GD or Imagick for thumbnails (intervention/image).
- [ ] Create the site with **zero-downtime deployments**, branch `main`, and
      the deploy script from `suggested-forge-deployment-script.md`.
- [ ] Add the Forge deploy key to the GitHub repo (or reuse the existing one).
- [ ] Upload limits, matching the old server:
  - [ ] nginx `client_max_body_size 20M` (`/etc/nginx/conf.d/uploads.conf`)
  - [ ] PHP-FPM `upload_max_filesize = 20M`, `post_max_size = 20M`
  - [ ] nginx `fastcgi_read_timeout 120`
- [ ] **Queue worker** (Forge → Daemons/Queue): connection `database`,
      all five queues, `--tries` and `--timeout` matching the old worker.
- [ ] **Scheduler** (Forge → Scheduler): `php artisan schedule:run` every
      minute, as the `forge` user.
- [ ] **ClamAV** if `CLAMAV_ENABLED=true`: install `clamav-daemon`, let
      freshclam download definitions, and check the socket matches
      `CLAMAV_SOCKET`. With `CLAMAV_FAIL_CLOSED=true` and no daemon, **every
      upload is rejected.**
- [ ] Server timezone **UTC** (the app logs in UTC).
- [ ] Firewall: only 22, 80, 443 open.

### Move the data (cutover window)

- [ ] Put the old site in maintenance mode: `php artisan down`.
- [ ] Wait for the queue to drain (`jobs` table empty, or only stale rows), so
      no upload is half-transferred to S3 during the copy.
- [ ] **Copy `.env`** from the old server to the new site (same `APP_KEY`).
      While there:
  - [ ] Set `TRUSTED_PROXIES=` (empty). There's no proxy in front, and the
        default `*` lets clients fake their IP to get around rate limits.
  - [ ] Confirm `APP_ENV=production`, `APP_DEBUG=false`,
        `APP_SETUP_ENABLED=false`.
- [ ] **Database:** dump on the old server, import on the new one. The jobs,
      sessions and cache tables live in the database (all `database` driver),
      so queued work moves with it.
- [ ] **Shared storage:** rsync `/home/forge/uploads.morton.dev/storage/`
      (preserve ownership `forge:forge`). The important parts:
  - [ ] `storage/app/public/uploads/`: files received but not yet sent to S3
  - [ ] `storage/app/private/`: everything except `chunks/`, which can be skipped
  - [ ] `storage/logs/` (optional, for history)
- [ ] Deploy on the new server, then `php artisan storage:link` if the deploy
      script doesn't do it.
- [ ] `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] Point DNS at the new server.
- [ ] Issue the Let's Encrypt certificate once DNS has propagated.
- [ ] `php artisan up` on the new server.

### If the domain changes

- [ ] Update `APP_URL` and `SESSION_DOMAIN`.
- [ ] Google Cloud Console: add the new OAuth redirect URI, then reconnect
      Google Drive in the admin panel.
- [ ] Old signed links in emails (login, verification) break. Users request
      new ones.

### After the move

- [ ] Leave the old server running, in maintenance mode, for a few days as a
      fallback.
- [ ] Check that no traffic or jobs still reach the old server (no new entries
      in its `laravel.log`).
- [ ] Update the `ssh jfm` entry in `~/.ssh/config` to the new host.
- [ ] Update `reference_production_server` notes (host, site id, log paths).
- [ ] Decommission the old server: remove it from Forge, delete the droplet,
      and revoke its deploy key and any credentials only it used.

---

## Part 2 — Confirm it works

Work through these in order. Each step relies on the ones before it.

### Server and HTTPS

- [ ] `https://uploads.morton.dev` loads with a valid certificate.
- [ ] `http://` redirects to `https://`.
- [ ] Security headers are present:
      `curl -sI https://uploads.morton.dev | grep -iE 'strict-transport|content-security|x-frame'`
- [ ] `/up` returns 200.
- [ ] `laravel.log` and the nginx error log show no new errors after a few
      page loads.

### Encryption key and database

- [ ] **Admin login with 2FA works.** This proves the `APP_KEY` is right, since
      the 2FA secret is encrypted.
- [ ] Admin → Cloud Storage settings show S3 configured and healthy (the
      credentials decrypt).
- [ ] Existing users, uploads and file history all appear in the admin
      dashboard and file manager.

### Uploads (the core flow)

- [ ] **Small file, public page, incognito:** visit `/upload/john`, verify
      your email, upload a small file. The **Upload complete** modal appears.
- [ ] **That file reaches S3** within a minute or two. This proves the queue
      worker is running.
- [ ] **The notification email arrives.** This proves mail works from the new
      server; check the spam folder too.
- [ ] **Upload with a message:** the message-specific success modal appears,
      and the message is attached to the files in admin.
- [ ] **Large file (over 100MB):** completes with no 429 and no "File size
      exceeds" error. In DevTools, every `chunk` request returns 200.
- [ ] Client upload flow (`/client/...`) works and redirects to My Uploads.
- [ ] File manager: thumbnails render, preview opens, download works.

### Background jobs and schedule

- [ ] `php artisan schedule:list` shows 12 tasks: the 11 in
      `routes/console.php` plus the chunk-upload package's `uploads:clear`.
- [ ] The cron is firing: within 30 minutes `storage/logs/pending-uploads.log`
      gets a new entry (`uploads:process-pending` runs every 30 minutes).
- [ ] `php artisan queue:failed` lists nothing new.
- [ ] Jobs on all five queues get processed (the `jobs` table doesn't grow).

### Security checks

- [ ] **ClamAV** (if enabled): uploading the EICAR test file is rejected with
      "failed security scanning".
- [ ] Rate limits are active: responses on the upload routes include
      `X-Ratelimit-Limit` headers.
- [ ] `TRUSTED_PROXIES` is empty in production `.env`.

### A day later

- [ ] The old server's `laravel.log` has no new requests.
- [ ] No failed jobs, and the S3 bucket has every file uploaded since the move.
- [ ] Scheduled task output is appearing in `storage/logs/pending-uploads.log`,
      `cloud-storage-health.log` and (if using Google Drive) `token-refresh.log`.
