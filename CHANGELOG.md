# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
