# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- ClamAV virus scanning integration — optional antivirus scanning for uploaded files via ClamAV daemon (socket or TCP), with fail-open/fail-closed modes
- `ClamAvService` for communicating with the ClamAV daemon using the INSTREAM protocol
- `config/filesecurity.php` for ClamAV configuration (enabled, connection type, timeout, max file size, fail mode)
- ClamAV environment variables in `.env.example`
- Unit tests for ClamAV service (disabled state, missing files, oversized files, fail-open, fail-closed, unreachable daemon)

### Changed
- Rewrote `README.md` as a project showcase with features, tech stack, architecture overview, and quick start
- Moved Google Drive API setup instructions from README to `docs/setup/google-drive-api-setup.md`

### Removed
- 129 Kiro AI tool spec files (`.kiro/` directory) — completed feature specs not needed in shipped repo
- 4 unused preview-modal blade variants (backup, fixed, new, previous-version)
- 9 test documentation artifacts (summaries, reports, and a demo script)
- `DOCUMENTATION.md`, `LOGO-REDESIGN.md`, `SECURITY_REVIEW.md` — internal development notes
- `public/favicon.zip` — archive file not needed in public directory
- `CLAUDE.md`, `design-criteria.md`, `design-log.md` untracked from repo (kept locally)
- Updated `.gitignore` to exclude build output, screenshots, Playwright logs, and AI tool directories
- 21 implementation summary docs (fix reports, code reviews, optimization logs) — replaced by git history

### Changed
- Email templates redesigned with Warm Studio aesthetic to match the app UI — cream backgrounds (#FAF8F5), warm brown text (#625646), orange accent links (#E8772E)
- Email buttons restyled as pill-shaped with warm dark (#2D2A26) primary and orange accent success/error variants
- Email card containers now use 16px border radius and warm-tint shadows
- Typography in emails upgraded to DM Sans (body) and Outfit (headings) via Bunny Fonts
- Panel accents changed from dark blue-gray to orange (#E8772E)
- Footer, subcopy borders, and table headers use warm cream tones

### Fixed
- Removed duplicate "thank you" message in client batch upload confirmation email
- Standardized sign-off format across batch upload emails to use consistent comma-separated signature

## [1.1.0] - 2026-03-10

### Changed
- Redesigned UI with "Warm Studio" aesthetic — cream/sand backgrounds, warm shadows, and refined color palette replacing generic gray gradients
- Typography upgraded to DM Sans (body) and Outfit (display headings) for a distinctive, modern feel
- Buttons restyled as pill-shaped (`rounded-full`) with warm dark primary and cream secondary variants
- Navigation bar now uses sticky positioning with frosted glass blur effect
- Cards, dropdowns, and modals use warmer borders, larger border-radius, and softer shadows
- Inputs use `rounded-xl` with warm cream borders and focus states
- Global CSS overrides warm up existing Tailwind gray patterns (cards, text, dividers, action buttons) across all views
- Page and card entrance animations for smoother transitions
- Subtle noise texture overlay for visual depth

[1.1.0]: https://github.com/johnfmorton/upload-drive-in/releases/tag/v1.1.0

## [1.0.2] - 2026-03-10

### Fixed
- S3 environment variable detection now works with cached config (`php artisan config:cache`) by replacing all `env()` calls with `config()` equivalents in the S3 configuration page, controller, and CloudStorageSetting model
- Complete test suite repair: 125/125 test files now passing (was 5/167)
- PHP 8.5 deprecation in database config (`PDO::MYSQL_ATTR_SSL_CA`)
- `CloudStorageErrorHandlerFactory` constructor argument type mismatch
- `ProactiveTokenRenewalService` undefined variable (`$refreshTime` used before assignment)
- `CloudStorageMonitoringDashboardService` returning arrays instead of User objects
- Missing `RecoveryResult` enum import
- Duplicate language key `token_refresh_failed_description` causing placeholder leak
- `EnvironmentFileService` not finding hidden backup files
- User model missing `role` in `$fillable` array
- Missing `admin.files.retry-failed` route

### Removed
- 42 obsolete and broken test files targeting deleted source code, changed constructors, or architectural mismatches
- Stale Vite build artifacts from git tracking (`public/build/` is gitignored)

### Added
- Test suite repair report (`docs/test-suite-errors.md`)

[1.0.2]: https://github.com/johnfmorton/upload-drive-in/releases/tag/v1.0.2

## [1.0.1] - 2026-03-10

### Added
- Smart Vite dev server startup script (`scripts/start-vite.sh`) with interactive port conflict handling — offers to kill blocking processes, use an alternate port, or quit
- `serve:smart` npm script for invoking the smart Vite startup

### Changed
- `make dev` now runs the smart Vite startup script and depends on the `build` target
- Content Security Policy allows Bunny Fonts (`fonts.bunny.net`) for styles and fonts
- Content Security Policy allows Vite dev server origin in local environment for HMR support

### Fixed
- File manager crash when no sort direction filter is provided (undefined array key in `FileManagerService`)
- File manager controller return type missing `RedirectResponse`, causing a secondary TypeError on exceptions
- Google Drive token decryption error (`DecryptException`) when APP_KEY changes — now gracefully returns "not connected"
- Docker Compose `version` attribute warning in `.ddev/docker-compose.redis.yaml`

### Security
- Fixed critical OAuth authentication bypass by adding HMAC-signed state parameters to Google Drive callbacks
- Fixed SQL injection vulnerability in file manager sort direction parameter
- Integrated FileSecurityService (extension blocking, MIME validation, magic byte checking, filename sanitization) into all five upload controllers
- Added encrypted casts for Google Drive access and refresh tokens stored at rest
- Removed sensitive fields (`role`, `two_factor_secret`, `two_factor_recovery_codes`) from User model mass assignment
- Fixed 2FA enforcement with timestamp-based verification and configurable timeout
- Added Content Security Policy (CSP) headers middleware
- Enforced session encryption by default
- Added rate limiting to setup routes, 2FA verification, and queue test endpoints
- Removed CSRF exclusion for setup admin route
- Stripped newlines from .env value updates to prevent .env injection
- Sanitized token refresh logging to avoid leaking credentials
- Removed debug header bypass in queue worker performance middleware
- Added password complexity requirements (mixed case, numbers, symbols, uncompromised check)
- Required password re-confirmation for 2FA disable action
- Restricted debug middleware logging to debug mode only
- Added SECURITY_REVIEW.md with comprehensive findings and remediation details
- Added ClamAV integration planning document (docs/CLAMAV_INTEGRATION.md)

[1.0.1]: https://github.com/johnfmorton/upload-drive-in/releases/tag/v1.0.1

## [1.0.0] - 2026-02-03

### Added
- Multi-cloud storage support (Google Drive and Amazon S3)
- Public upload forms with email validation
- Automatic file organization by submitter email
- Admin dashboard for system configuration and user management
- Employee portal for viewing uploads
- Client upload interface with drag-and-drop support
- Two-factor authentication (2FA) for admin accounts
- Google Drive folder selection and auto-save
- Amazon S3 bucket and folder configuration
- Automatic Google Drive token refresh (scheduled every 6 hours)
- Email notifications for uploads
- Test email feature in setup process
- Privacy policy and terms of service pages
- Setup detection and guided configuration flow

### Security
- Role-based access control (Admin, Employee, Client)
- CSRF protection on all forms
- Email verification for uploads
- Secure token storage for cloud provider credentials
