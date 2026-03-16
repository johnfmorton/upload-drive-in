# Upload Drive-In Documentation

Upload Drive-In is a Laravel 12 application that enables businesses to receive files from clients directly into cloud storage (Google Drive and Amazon S3). Files are uploaded via public forms with email validation and automatically organized by submitter email.

---

## Table of Contents

- [Getting Started](#getting-started)
  - [Requirements](#requirements)
  - [Installation with DDEV](#installation-with-ddev)
  - [Installation without DDEV](#installation-without-ddev)
- [Configuration](#configuration)
  - [Environment Variables](#environment-variables)
  - [Cloud Storage Providers](#cloud-storage-providers)
  - [ClamAV Virus Scanning](#clamav-virus-scanning)
  - [File Upload Limits](#file-upload-limits)
  - [Session & Security](#session--security)
  - [Mail](#mail)
- [Architecture](#architecture)
  - [Tech Stack](#tech-stack)
  - [Directory Structure](#directory-structure)
  - [User Roles](#user-roles)
  - [Route Groups](#route-groups)
  - [Database Schema](#database-schema)
- [Features](#features)
  - [Cloud Storage](#cloud-storage)
  - [File Uploads](#file-uploads)
  - [Google Drive Token Management](#google-drive-token-management)
  - [File Security](#file-security)
  - [Admin Dashboard](#admin-dashboard)
  - [Employee Portal](#employee-portal)
  - [Client Interface](#client-interface)
- [Development](#development)
  - [Commands](#commands)
  - [Testing](#testing)
  - [Code Style](#code-style)
- [Deployment](#deployment)
  - [Docker](#docker)
  - [Production Checklist](#production-checklist)
- [Additional Documentation](#additional-documentation)

---

## Getting Started

### Requirements

- PHP 8.2+
- Node.js 18+
- Composer
- SQLite (development) or MySQL/PostgreSQL (production)
- Redis (optional, for cache/queue/session)
- [DDEV](https://ddev.readthedocs.io/) (recommended for local development)

### Installation with DDEV

```bash
git clone <repository-url>
cd upload-drive-in
make init
```

This runs the full setup: copies `.env.example` to `.env`, installs Composer and npm dependencies, generates the app key, runs migrations with seeders, and builds frontend assets.

After setup:

1. Configure your `.env` file (database, mail, cloud storage credentials).
2. Create an admin user: `ddev exec php artisan user:set-role <your-email> admin`
3. Start the dev environment: `make dev`

### Installation without DDEV

```bash
git clone <repository-url>
cd upload-drive-in
cp .env.example .env
composer install
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
composer dev
```

`composer dev` starts the PHP dev server, queue worker, log viewer (Pail), and Vite dev server concurrently.

---

## Configuration

### Environment Variables

All configuration is managed through the `.env` file. Copy `.env.example` to get started. Key sections:

#### Core Application

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_NAME` | Upload Drive-In | Application display name |
| `APP_ENV` | local | Environment (`local`, `production`) |
| `APP_DEBUG` | true | Enable debug mode (disable in production) |
| `APP_URL` | http://localhost | Base application URL |
| `COMPANY_NAME` | Your Company Name | Company branding name |
| `APP_BRANDING_COLOR` | #1b4cc3 | Primary brand color |
| `APP_LOCALE` | en | Application locale (`en`, `de`, `fr`, `es`) |

#### Database

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_CONNECTION` | sqlite | Database driver (`sqlite`, `mysql`, `pgsql`) |
| `DB_HOST` | 127.0.0.1 | Database host |
| `DB_PORT` | 3306 | Database port |
| `DB_DATABASE` | laravel | Database name |
| `DB_USERNAME` | root | Database user |
| `DB_PASSWORD` | | Database password |

### Cloud Storage Providers

Upload Drive-In supports multiple cloud storage backends. Set the default with:

```env
CLOUD_STORAGE_DEFAULT=google-drive   # or: amazon-s3
```

#### Google Drive

Requires OAuth credentials from the [Google Cloud Console](https://console.cloud.google.com/).

```env
GOOGLE_DRIVE_CLIENT_ID=your-client-id
GOOGLE_DRIVE_CLIENT_SECRET=your-client-secret
```

After configuring credentials, connect via the admin dashboard. The OAuth flow handles token acquisition. Tokens are stored encrypted and refreshed automatically.

See `docs/google-drive-*.md` for detailed setup and troubleshooting guides.

#### Amazon S3

```env
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_FOLDER_PATH=                      # Optional subfolder (no leading/trailing slashes)
AWS_USE_PATH_STYLE_ENDPOINT=false
```

See `docs/s3-setup-guide.md` and `docs/s3-deployment-checklist.md` for details.

### ClamAV Virus Scanning

Upload Drive-In can optionally scan uploaded files for viruses using a ClamAV daemon. This is disabled by default and requires a running ClamAV instance.

```env
CLAMAV_ENABLED=false
CLAMAV_CONNECTION=socket              # 'socket' or 'tcp'
CLAMAV_SOCKET=/var/run/clamav/clamd.ctl
CLAMAV_HOST=127.0.0.1
CLAMAV_PORT=3310
CLAMAV_TIMEOUT=30
CLAMAV_MAX_FILE_SIZE=26214400         # 25MB - files larger than this skip scanning
CLAMAV_FAIL_CLOSED=false              # false = uploads proceed if ClamAV is down
```

#### How It Works

The `ClamAvService` communicates with the ClamAV daemon via Unix socket or TCP using the INSTREAM protocol. It is called as the final step in `FileSecurityService::validateFileUpload()`, after all cheaper checks (extension, MIME type, magic bytes, content patterns) have passed. This avoids unnecessary daemon calls for files that would already be rejected.

**Key behaviors:**
- When `CLAMAV_ENABLED=false` (default), scanning is skipped entirely — no performance impact.
- When the daemon is unreachable and `CLAMAV_FAIL_CLOSED=false` (default), uploads proceed normally and the error is logged. This prevents ClamAV outages from blocking the application.
- When `CLAMAV_FAIL_CLOSED=true`, uploads are rejected if ClamAV cannot be reached (maximum security mode).
- User-facing error messages are generic ("The uploaded file failed security scanning"). Virus names are only logged server-side.
- No controller changes are needed — scanning plugs into the existing `validateFileUpload()` flow used by all five upload controllers.

#### Installing ClamAV

**Option A: Local daemon (VPS/dedicated server)**

```bash
# Ubuntu/Debian
apt-get install clamav clamav-daemon
systemctl enable clamav-daemon
systemctl start clamav-daemon
freshclam   # Update virus definitions
```

Uses Unix socket at `/var/run/clamav/clamd.ctl` by default.

**Option B: Docker sidecar**

Add to your `docker-compose.yml`:

```yaml
services:
  clamav:
    image: clamav/clamav:stable
    ports:
      - "3310:3310"
    volumes:
      - clamav-data:/var/lib/clamav
    restart: unless-stopped

volumes:
  clamav-data:
```

Configure with TCP connection:

```env
CLAMAV_ENABLED=true
CLAMAV_CONNECTION=tcp
CLAMAV_HOST=clamav       # Docker service name
CLAMAV_PORT=3310
```

**Option C: DDEV (development)**

Create `.ddev/docker-compose.clamav.yaml`:

```yaml
services:
  clamav:
    container_name: ddev-${DDEV_SITENAME}-clamav
    image: clamav/clamav:stable
    expose:
      - "3310"
    volumes:
      - clamav-data:/var/lib/clamav
    labels:
      com.ddev.site-name: ${DDEV_SITENAME}

volumes:
  clamav-data:
```

#### Virus Definition Updates

ClamAV requires regular definition updates. The official Docker image handles this automatically via `freshclam`. For bare-metal installs, ensure the `clamav-freshclam` service is running or add a cron job:

```bash
0 */6 * * * /usr/bin/freshclam --quiet
```

#### Rollout Strategy

1. Deploy with `CLAMAV_ENABLED=false` (no behavior change)
2. Install and start the ClamAV daemon
3. Set `CLAMAV_ENABLED=true` and monitor logs for errors
4. Once stable, optionally set `CLAMAV_FAIL_CLOSED=true` for maximum security

See `docs/CLAMAV_INTEGRATION.md` for the full implementation plan.

### File Upload Limits

These settings are configured in `config/upload.php` and can be overridden via `.env`:

```env
# Disk space management
UPLOAD_MIN_FREE_SPACE=2147483648        # 2GB minimum free space
UPLOAD_WARNING_THRESHOLD=5368709120      # 5GB warning threshold

# File limits
UPLOAD_MAX_FILE_SIZE=5368709120          # 5GB max file size
UPLOAD_CHUNK_SIZE=5242880                # 5MB chunk size
UPLOAD_MAX_CONCURRENT=20                 # Max concurrent uploads

# Cleanup
UPLOAD_CLEANUP_DEFAULT_HOURS=24
UPLOAD_AUTO_CLEANUP=true
UPLOAD_FAILED_CLEANUP_HOURS=72
```

Upload recovery settings are in `config/upload-recovery.php` and control automatic retry behavior for failed uploads.

### Session & Security

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=                   # true for HTTPS, false for HTTP, empty for auto
SESSION_SAME_SITE=lax
SESSION_HTTP_ONLY=true
```

### Mail

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

For local development with DDEV, use `make mailhog` to launch MailHog for email testing.

---

## Architecture

### Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | Vite, TailwindCSS 4, Alpine.js, Shoelace Web Components |
| File uploads | Uppy (dashboard, TUS, XHR), Dropzone |
| Database | SQLite (dev), MySQL/PostgreSQL (production) |
| Queue | Redis or database-backed |
| Cloud storage | Google Drive API, AWS S3 |
| Auth | Laravel Breeze, Sanctum, custom 2FA package |
| Images | Intervention Image |

### Directory Structure

```
app/
├── Contracts/              # Interfaces (CloudStorageProviderInterface, etc.)
├── Helpers/                # Utility helpers (pagination, session config)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Admin dashboard controllers (12 files)
│   │   ├── Employee/       # Employee portal controllers (7 files)
│   │   ├── Client/         # Client upload controllers (3 files)
│   │   └── ...             # Public, auth, health, and setup controllers
│   └── Middleware/          # CSP headers, rate limiting, etc.
├── Jobs/                   # Queue jobs (7 jobs)
├── Models/                 # Eloquent models
├── Observers/              # Model observers (FileUpload, DomainAccessRule)
├── Providers/              # Service providers
└── Services/               # Business logic (88 service classes)
    ├── CloudStorageManager.php
    ├── GoogleDriveProvider.php
    ├── S3Provider.php
    ├── FileSecurityService.php
    ├── ClamAvService.php
    └── ...

config/
├── cloud-storage.php       # Multi-cloud provider settings
├── token-refresh.php       # Google Drive token refresh schedule
├── upload.php              # Upload limits and disk space management
├── upload-recovery.php     # Upload recovery/retry configuration
└── filesecurity.php        # ClamAV virus scanning configuration

routes/
├── web.php                 # Public routes, OAuth callbacks, health checks
├── admin.php               # Admin panel routes
├── employee-portal.php     # Employee dashboard routes
└── client.php              # Client upload routes

packages/
└── upload-drive-in/
    └── laravel-admin-2fa/  # Custom 2FA package (local)

docs/                       # 86 documentation files
```

### User Roles

| Role | Access |
|------|--------|
| **Admin** | Full system access: settings, user management, cloud storage configuration, file management, token monitoring |
| **Employee** | View and manage uploads associated with their email, cloud storage status, Google Drive folder selection, client management |
| **Client** | Public upload access with email validation, profile management |

### Route Groups

| File | Prefix | Description |
|------|--------|-------------|
| `routes/web.php` | `/` | Public pages, employee uploads, health checks, OAuth callbacks, token monitoring, cloud storage dashboard |
| `routes/admin.php` | `/admin` | Admin dashboard, file manager, user/employee management, settings, cloud storage config |
| `routes/employee-portal.php` | `/employee` | Employee dashboard, uploads, file manager, cloud storage, client management, profile |
| `routes/client.php` | `/client` | Client dashboard, file uploads (including chunked), profile |

### Database Schema

Key tables (43 migrations total):

| Table | Purpose |
|-------|---------|
| `users` | User accounts with roles, 2FA fields, token fields |
| `file_uploads` | File upload metadata (name, size, MIME, status, storage path) |
| `google_drive_tokens` | Encrypted OAuth tokens per user |
| `cloud_storage_settings` | Per-user cloud storage provider configuration |
| `cloud_storage_health_statuses` | Provider health tracking |
| `domain_access_rules` | Email domain restrictions for uploads |
| `email_validations` | Email verification records |
| `client_user_relationships` | Client-to-user mappings |
| `setup_configurations` | Setup wizard state |
| `jobs`, `cache`, `sessions` | Laravel infrastructure tables |

---

## Features

### Cloud Storage

The application uses a provider abstraction for cloud storage:

- **`CloudStorageManager`** — Main orchestrator for all storage operations
- **`GoogleDriveProvider`** — Google Drive implementation with folder hierarchy, chunked uploads, and OAuth
- **`S3Provider`** — Amazon S3 implementation with bucket/folder configuration
- **`CloudStorageFactory`** — Creates provider instances based on configuration

Provider health is continuously monitored with automatic fallback support. The admin dashboard shows real-time connection status.

### File Uploads

- **Public forms** with email validation — clients don't need accounts
- **Chunked uploads** for large files (up to 5GB) via `pion/laravel-chunk-upload`
- **Drag-and-drop** interface using Uppy and Dropzone
- **Automatic organization** by submitter email in cloud storage
- **Upload recovery** with automatic retry for failed uploads (configurable strategies for token refresh, network errors, quota limits, and service outages)
- **Disk space monitoring** with configurable thresholds and emergency cleanup

### Google Drive Token Management

Google Drive OAuth tokens expire and require periodic refresh:

- Automatic refresh every 6 hours and daily at 9 AM via scheduled jobs
- Proactive refresh 15 minutes before expiration
- Token rotation on successful refresh
- Encrypted storage of access and refresh tokens
- Admin monitoring dashboard with health status
- Batch processing for multi-user environments

Managed by `ProactiveTokenRenewalService`, `TokenRefreshCoordinator`, and the `RefreshTokenJob` queue job.

### File Security

All uploads pass through `FileSecurityService` which performs:

1. **Extension blocking** — Rejects dangerous extensions (exe, bat, php, js, etc.)
2. **MIME type validation** — Detects mismatches between declared and actual MIME types
3. **Magic byte checking** — Identifies suspicious file signatures (executables, macros)
4. **Content pattern scanning** — Detects code injection patterns (eval, exec, script tags)
5. **Filename sanitization** — Removes path traversal attempts and dangerous characters
6. **ClamAV virus scanning** (optional) — Scans files against virus definitions via the ClamAV daemon

All five upload controllers use the same validation flow. See [ClamAV Virus Scanning](#clamav-virus-scanning) for setup instructions.

### Admin Dashboard

- Cloud storage status monitoring and configuration
- Token refresh monitoring and configuration
- File manager with search, sort, preview, download, bulk operations, and retry
- User and employee management
- Application settings (branding, locale)
- Security settings
- Setup wizard for initial configuration
- Queue health monitoring

### Employee Portal

- Dashboard showing upload activity
- File manager for viewing and managing uploads
- Cloud storage status and reconnection
- Google Drive folder selection
- Client management
- Profile and email verification

### Client Interface

- Simple upload form with drag-and-drop
- Chunked upload support for large files
- Email validation before uploading
- Profile management

---

## Development

### Commands

#### DDEV (recommended)

```bash
make init          # Full project setup
make dev           # Start dev environment (DDEV + queue worker + Vite)
make queue         # Run queue worker separately
make mailhog       # Launch MailHog for email testing
```

#### Without DDEV

```bash
composer dev       # Runs server, queue, Pail log viewer, and Vite concurrently
```

#### Individual Commands

```bash
# Frontend
npm run build              # Production build
npm run dev                # Vite dev server

# Backend
php artisan serve          # PHP dev server
php artisan queue:work     # Queue worker
php artisan migrate        # Run migrations
php artisan migrate --seed # Run migrations with seeders

# User management
php artisan user:set-role <email> admin    # Set user role
```

### Testing

**PHP (PHPUnit):**

```bash
./vendor/bin/phpunit                              # Run all tests
./vendor/bin/phpunit tests/Unit/                   # Unit tests only
./vendor/bin/phpunit tests/Feature/                # Feature tests only
./vendor/bin/phpunit tests/Unit/Services/ClamAvServiceTest.php  # Single file
./vendor/bin/phpunit --filter testMethodName       # Single method
```

PHPUnit uses in-memory SQLite with array drivers for cache, queue, and session.

**JavaScript (Jest):**

```bash
npm test                   # Run all JS tests
npm run test:watch         # Watch mode
```

### Code Style

```bash
./vendor/bin/pint          # Laravel Pint code style fixer
```

---

## Deployment

### Docker

The project includes a multi-stage Dockerfile:

1. **Frontend builder** (Node 18 Alpine) — installs npm dependencies and runs `npm run build`
2. **Production image** (PHP 8.3 FPM Alpine) — installs Nginx, Supervisor, Redis, PHP extensions (GD, zip, PDO), Composer dependencies, and copies built frontend assets

The health check endpoint is `/health`.

```bash
docker build -t upload-drive-in .
docker compose up -d
```

### Production Checklist

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Set a strong `APP_KEY` (generated via `php artisan key:generate`)
3. Configure a production database (MySQL or PostgreSQL)
4. Set `SESSION_SECURE_COOKIE=true` and `SESSION_ENCRYPT=true` for HTTPS
5. Configure cloud storage credentials (Google Drive and/or S3)
6. Set up a queue worker (Redis recommended for production)
7. Configure mail (SMTP or a transactional email service)
8. Run `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`
9. Set up scheduled tasks: `* * * * * php artisan schedule:run >> /dev/null 2>&1`
10. (Optional) Install and enable ClamAV for virus scanning
11. (Optional) Configure upload recovery settings for automatic retry

See `docs/deployment-guide.md` and `docs/production-deployment-checklist.md` for detailed instructions.

---

## Additional Documentation

The `docs/` directory contains 86 files covering specific topics in depth:

| Topic | Key Files |
|-------|-----------|
| **Cloud Storage** | `cloud-storage-configuration.md`, `cloud-storage-provider-system.md`, `implementing-new-providers.md` |
| **Google Drive** | `google-drive-token-auto-renewal.md`, `google-drive-token-configuration-reference.md`, `google-drive-troubleshooting.md` |
| **Amazon S3** | `s3-setup-guide.md`, `s3-deployment-checklist.md`, `s3-code-review-summary.md` |
| **Virus Scanning** | `CLAMAV_INTEGRATION.md` |
| **Security** | `SECURITY_REVIEW.md`, `email-verification-security.md`, `security-implementation-summary.md` |
| **Deployment** | `deployment-guide.md`, `production-deployment-checklist.md`, `rollback-procedures.md`, `suggested-forge-deployment-script.md` |
| **File Management** | `file-management-dashboard-overview.md`, `pending-uploads-management.md` |
| **Monitoring** | `cloud-storage-logging-monitoring.md`, `token-monitoring-alerting-guide.md` |
| **Troubleshooting** | `google-drive-connection-issues.md`, `email-verification-issues.md`, `production-debug-commands.md` |
| **API** | `cloud-storage-provider-api.md`, `cloud-storage-status-endpoints.md`, `file-management-endpoints.md` |
| **Setup** | `environment-configuration.md`, `ddev-redis-setup-guide.md`, `session-cookie-configuration.md` |
