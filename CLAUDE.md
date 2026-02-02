# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Upload Drive-In is a Laravel 12 application that enables businesses to receive files from clients directly into cloud storage (Google Drive and Amazon S3). Files are uploaded via public forms with email validation and automatically organized by submitter email.

## Development Commands

### Using DDEV (recommended)

```bash
make init          # Full setup: composer, npm, migrations, build
make dev           # Start dev environment (launches DDEV, queue worker, Vite)
make queue         # Run queue worker separately
make mailhog       # Launch MailHog for email testing
```

### Without DDEV

```bash
composer dev       # Runs server, queue, logs (pail), and Vite concurrently
```

### Individual Commands

```bash
# Build
npm run build              # Production build
npm run dev                # Vite dev server

# Tests
npm test                   # Jest (JavaScript)
npm run test:watch         # Jest watch mode
./vendor/bin/phpunit       # PHPUnit (PHP)
./vendor/bin/phpunit tests/Unit/ExampleTest.php  # Single test file
./vendor/bin/phpunit --filter testMethodName     # Single test method

# Code quality
./vendor/bin/pint          # Laravel code style fixer
```

## Architecture

### Tech Stack
- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Vite, TailwindCSS 4, Alpine.js, Shoelace Web Components
- **File uploads:** Uppy, Dropzone
- **Database:** SQLite (dev), MySQL/PostgreSQL (production)
- **Queue:** Redis or database-backed
- **Cloud Storage:** Google Drive API, AWS S3

### Key Directories

- `app/Services/` - Business logic (88 service classes including cloud providers, token refresh, validation)
- `app/Jobs/` - Queue jobs (UploadToGoogleDrive, RefreshTokenJob, etc.)
- `app/Http/Controllers/Admin/` - Admin dashboard controllers
- `app/Http/Controllers/Employee/` - Employee portal controllers
- `app/Http/Controllers/Client/` - Client upload controllers
- `packages/upload-drive-in/laravel-admin-2fa/` - Custom 2FA package (local)

### Routes
- `routes/web.php` - Public routes
- `routes/admin.php` - Admin panel
- `routes/employee-portal.php` - Employee dashboard
- `routes/client.php` - Client routes

### Key Configuration
- `config/cloud-storage.php` - Multi-cloud provider settings
- `config/token-refresh.php` - Google Drive token refresh schedule

### Cloud Storage Architecture

The app uses a provider abstraction for cloud storage:
- `CloudStorageManager` - Main service for storage operations
- `GoogleDriveProvider` - Google Drive implementation
- `S3Provider` - Amazon S3 implementation
- Automatic token refresh via scheduled jobs (every 6 hours, daily at 9 AM)

### User Roles
- **Admin** - Full system access, settings, user management
- **Employee** - View uploads for their email
- **Client** - Public upload access

## Testing

PHPUnit uses in-memory SQLite with array drivers for cache/queue/session. Jest handles JavaScript tests with jsdom environment.

## Documentation

Extensive documentation in `/docs/` including:
- Cloud storage configuration
- Token refresh system
- Deployment guides
- Troubleshooting guides
