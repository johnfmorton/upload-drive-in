# Upload Drive-In

A Laravel application that lets businesses receive files from clients directly into cloud storage. Clients upload files through public forms with email validation, and files are automatically organized by submitter email into Google Drive or Amazon S3.

## Features

- **Client upload portal** — Public drag-and-drop forms with email verification; no client accounts needed
- **Dual cloud storage** — Google Drive and Amazon S3 with automatic token refresh and a provider abstraction layer
- **Admin dashboard** — File management, user administration, cloud storage configuration, and system setup wizard
- **Employee portal** — Employees view and manage uploads associated with their email
- **Batch uploads** — Multiple file upload with real-time progress tracking
- **Email notifications** — Automated notifications for upload events with branded templates
- **Virus scanning** — Optional ClamAV integration for uploaded file security
- **Queue-based processing** — Background file transfers to cloud storage via Redis or database queues
- **Two-factor authentication** — TOTP-based 2FA for admin accounts

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Vite, TailwindCSS 4, Alpine.js, Shoelace Web Components |
| File Upload | Uppy, Dropzone |
| Database | SQLite (dev), MySQL/PostgreSQL (production) |
| Queue | Redis or database-backed |
| Cloud Storage | Google Drive API, AWS S3 |
| Security | Custom 2FA package, ClamAV, CSP headers |

## Quick Start

```bash
# Clone and install
git clone https://github.com/johnfmorton/upload-drive-in.git
cd upload-drive-in
composer install
npm install

# Configure
cp .env.example .env
php artisan key:generate
# Edit .env with your database and cloud storage settings

# Set up database and build assets
php artisan migrate
npm run build

# Start the development server
composer dev
```

Visit `http://127.0.0.1:8000` to access the setup wizard.

### Using DDEV

```bash
make init    # Full setup: composer, npm, migrations, build
make dev     # Start dev environment (DDEV, queue worker, Vite)
```

## Architecture

Upload Drive-In uses a **provider abstraction** for cloud storage, making it straightforward to add new storage backends:

- `CloudStorageManager` orchestrates storage operations across providers
- `GoogleDriveProvider` and `S3Provider` implement the storage interface
- Automatic Google Drive token refresh runs via scheduled jobs (every 6 hours)
- File uploads are queued as background jobs for reliable delivery to cloud storage

### User Roles

| Role | Access |
|------|--------|
| **Admin** | Full system access — settings, users, file management, cloud storage config |
| **Employee** | View and manage uploads associated with their email |
| **Client** | Public upload access via email-verified forms |

### Key Directories

```
app/Services/          — Business logic (cloud providers, validation, token refresh)
app/Jobs/              — Queue jobs (cloud upload, token refresh)
app/Http/Controllers/  — Admin, Employee, and Client controllers
config/                — Cloud storage, token refresh, and security config
packages/              — Custom 2FA package
```

## Documentation

Detailed guides are in the [`docs/`](docs/) directory:

- **Setup:** [Initial Setup](docs/setup/Instructions.md) · [Google Drive API](docs/setup/google-drive-api-setup.md) · [S3 Setup](docs/cloud-storage/amazon-s3-setup-guide.md)
- **Configuration:** [Environment Guide](docs/environment-configuration-guide.md) · [Cloud Storage](docs/cloud-storage-configuration-guide.md) · [Redis/DDEV](docs/ddev-redis-setup.md)
- **Deployment:** [Guide](docs/deployment/DEPLOYMENT.md) · [Production Checklist](docs/deployment/production-deployment-checklist.md) · [Rollback](docs/deployment/rollback-procedures.md)
- **Troubleshooting:** [Cloud Storage](docs/troubleshooting/cloud-storage-provider-troubleshooting.md) · [Google Drive](docs/troubleshooting/google-drive-connection-issues.md) · [Email](docs/troubleshooting/email-verification-issues.md)

## Testing

```bash
./vendor/bin/phpunit       # PHP tests
npm test                   # JavaScript tests
./vendor/bin/pint          # Code style
```

## License

See [LICENSE.md](LICENSE.md).
