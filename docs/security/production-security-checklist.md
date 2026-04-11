# Production Security Checklist

Before deploying Upload Drive-In to a client server, verify every item below.

## Environment Configuration (.env)

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false` -- exposes stack traces and sensitive data if left enabled
- [ ] `APP_SETUP_ENABLED=false` -- disables the setup wizard after initial configuration
- [ ] `APP_URL` starts with `https://`
- [ ] `APP_KEY` is set to a unique, randomly generated value (`php artisan key:generate`)

## Session & Cookies

- [ ] `SESSION_ENCRYPT=true`
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `SESSION_SAME_SITE=lax` (or `strict` if cross-site navigation is not needed)

## Database

- [ ] Strong, unique database password (not the DDEV default `db`)
- [ ] Database user has only the privileges the application needs
- [ ] Database is not exposed on a public network interface

## Email

- [ ] `MAIL_ENCRYPTION=tls` (or `ssl`)
- [ ] `MAIL_FROM_ADDRESS` set to a real domain (not `example.com`)
- [ ] SPF, DKIM, and DMARC DNS records configured for the sending domain

## Logging

- [ ] `LOG_LEVEL=info` (or `warning`) -- `debug` level can log sensitive data

## Content Security Policy

- [ ] `CSP_ENFORCE=false` initially -- starts in Report-Only mode, which logs CSP violations in the browser console without blocking anything
- [ ] Open every major page type (login, dashboard, setup wizard, file manager, settings, profile) and check the browser console for CSP violation reports
- [ ] Once no violations appear, set `CSP_ENFORCE=true` to switch from Report-Only to enforcing mode
- [ ] Note: `'unsafe-eval'` remains in `script-src` because Alpine.js requires it for expression evaluation -- this is expected

## Proxy & Networking

- [ ] `TRUSTED_PROXIES` set to specific proxy IPs (not `*`) if behind a reverse proxy
- [ ] HTTPS/TLS configured at the web server or load balancer level

## Google Drive Integration

- [ ] After deploying the narrowed OAuth scope (`drive.file` only), re-authenticate with Google Drive from the admin panel to pick up the new scope
- [ ] `GOOGLE_DRIVE_CLIENT_SECRET` is kept in `.env` only, not in code or version control

## File Uploads

- [ ] `CLAMAV_ENABLED=true` if ClamAV is available on the server
- [ ] Verify the `storage/app/public/uploads/` directory has correct permissions (not world-readable)
- [ ] Confirm the scheduled `ClearOldUploads` command is running to clean up temporary files

## Two-Factor Authentication

- [ ] `ENFORCE_ADMIN_2FA=true` (default) -- all admin users must use 2FA
