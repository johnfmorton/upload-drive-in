# Security Review — Upload Drive-In

**Date:** 2026-03-10
**Scope:** Full codebase security review covering authentication, file uploads, input validation, secrets management, and access control.

---

## Executive Summary

This review identified **6 critical**, **10 high**, **9 medium**, and **6 low** severity issues. The most dangerous findings are:

1. **OAuth callback authentication bypass** — any user can be impersonated via crafted state parameter
2. **SQL injection** via unsanitized `sort_direction` in `orderByRaw`
3. **File upload security is effectively disabled** — `FileSecurityService` exists but is never called during uploads
4. **Files stored on public disk** with no type restriction, enabling potential remote code execution
5. **Google Drive OAuth tokens stored unencrypted** in the database
6. **CSRF protection disabled** for the admin creation setup route

---

## CRITICAL Findings

### C1. OAuth Callback Allows Authentication as Any User
**File:** `app/Http/Controllers/GoogleDriveUnifiedCallbackController.php:61-75`

The Google Drive OAuth callback decodes a `state` parameter containing a `user_id` and authenticates that user without cryptographic verification:

```php
$stateData = json_decode(base64_decode($state), true);
$user = User::find($stateData['user_id']);
Auth::login($user);
```

An attacker can craft `state=base64_encode('{"user_id":1}')` to authenticate as any user, including admins. This is a full authentication bypass.

**Fix:** Use a session-bound, cryptographically signed state parameter (e.g., encrypt user_id + session token, verify on callback).

---

### C2. SQL Injection via `orderByRaw`
**File:** `app/Services/FileManagerService.php:204`

The `$sortDirection` value from user input is interpolated directly into a raw SQL query:

```php
$query->orderByRaw("LOWER(original_filename) {$sortDirection}");
```

No validation restricts `$sortDirection` to `'asc'` or `'desc'`.

**Fix:** Add whitelist validation:
```php
$sortDirection = in_array($sortDirection, ['asc', 'desc']) ? $sortDirection : 'desc';
```

---

### C3. FileSecurityService Never Called During Uploads
**File:** `app/Services/FileSecurityService.php` (dead code in upload path)

The `FileSecurityService` contains excellent security logic — dangerous extension blocking, MIME consistency checking, magic byte inspection, and content scanning — but **none of the upload controllers call it**. It is only used in `FileManagerController` for viewing/downloading files. Every upload controller processes files without security validation.

**Affected controllers:**
- `Client\UploadController`
- `UploadController`
- `FileUploadController`
- `Employee\UploadController`
- `PublicEmployeeUploadController`

**Fix:** Call `FileSecurityService::validateFileUpload()` in all upload controllers before storing files.

---

### C4. Uploaded Files on Public Disk With No Type Restriction
**Files:** All upload controllers store via `Storage::disk('public')->putFileAs('uploads', ...)`

All controllers validate only `'required|file|max:10240'` with no `mimes` or `mimetypes` rule. Combined with the `public` disk (web-accessible via `/storage/` symlink), an attacker can upload PHP files, HTML with XSS payloads, or web shells that are directly accessible.

**Fix:**
1. Add `mimes` validation rules to all upload endpoints
2. Move uploaded files to `Storage::disk('local')` (private) and serve through an authenticated controller
3. Use detected MIME type instead of `getClientOriginalExtension()` for stored filenames

---

### C5. Google Drive OAuth Tokens Stored Unencrypted
**Files:** `app/Models/GoogleDriveToken.php`, `app/Services/GoogleDriveManager.php:64`

`access_token` and `refresh_token` are stored as plaintext in the database. By contrast, S3 credentials use `Crypt::encryptString`. The `GoogleDriveToken` model also lacks `$hidden`, meaning tokens are exposed in any JSON serialization.

**Fix:**
```php
// In GoogleDriveToken model:
protected $casts = [
    'access_token' => 'encrypted',
    'refresh_token' => 'encrypted',
];
protected $hidden = ['access_token', 'refresh_token'];
```

---

### C6. CSRF Protection Disabled for Admin Creation Route
**File:** `app/Http/Middleware/VerifyCsrfToken.php:16`

```php
'setup/admin', // Temporary - for debugging CSRF issues
```

The admin user creation endpoint has CSRF verification bypassed. An attacker can forge cross-site requests to create admin accounts if the setup wizard is accessible.

**Fix:** Remove `'setup/admin'` from the `$except` array. Fix the underlying CSRF issue properly.

---

## HIGH Findings

### H1. No Rate Limiting on 2FA Verification
**File:** `packages/upload-drive-in/laravel-admin-2fa/routes/web.php:19`

`POST /admin/2fa/verify` has no `throttle` middleware. An attacker with a stolen password can brute-force all 1,000,000 possible 6-digit TOTP codes.

**Fix:** Add `throttle:5,1` middleware to the 2FA verify route.

---

### H2. No Rate Limiting on Token Login Route
**File:** `routes/web.php:21-23`

The `loginViaToken` route uses `signed` middleware but no `throttle` middleware.

**Fix:** Add `throttle:6,1` middleware.

---

### H3. Chunked Upload Endpoints Have No Validation
**Files:** `Client\UploadController::store()`, `UploadController::store()`

Chunked upload endpoints pass requests directly to `FileReceiver` with no `$request->validate()` call. No file type check, no server-side size limit. The frontend allows 5GB but this is trivially bypassed.

**Fix:** Add server-side validation for file type and size before processing chunks.

---

### H4. Public Employee Upload Routes Lack Authentication
**File:** `routes/web.php:14-18`

`POST /upload/{name}` and `/upload/{name}/chunk` have no `auth` middleware. The controller accepts any email the caller provides.

**Fix:** Add authentication or implement CAPTCHA/rate limiting for public upload routes.

---

### H5. Debug Middleware Logs CSRF Tokens
**File:** `app/Http/Middleware/DebugSetupRequests.php`

This middleware logs CSRF tokens, input keys, and request headers to log files. This appears to be leftover debug code.

**Fix:** Remove this middleware or gate behind `config('app.debug')` and never log CSRF token values.

---

### H6. Token Response Logged With Full Credentials
**File:** `app/Services/GoogleDriveService.php:856,862`

When token refresh fails, the entire `$newToken` response (which may contain `access_token` and `refresh_token`) is logged in plaintext.

**Fix:** Use `CloudStorageAuditService::sanitizeConfigurationForLogging()` before logging.

---

### H7. 2FA Secret Stored in Plaintext
**File:** `packages/upload-drive-in/laravel-admin-2fa/src/Traits/HasTwoFactorAuth.php:12`

The `two_factor_secret` is stored as a plain string. If the database is compromised, an attacker can clone every admin's TOTP authenticator.

**Fix:** Use Laravel's `encrypted` cast for `two_factor_secret`.

---

### H8. Admin Routes Protected Only by `auth`, Not `admin`
**File:** `routes/web.php` (cloud storage dashboard routes)

Some `/admin/` URL paths only require `auth` middleware, not `admin` middleware. Any authenticated user (client or employee) can access admin cloud storage status and trigger operations.

**Fix:** Ensure all `/admin/` routes use the `admin` middleware.

---

### H9. `role` in User Model `$fillable` Enables Privilege Escalation
**File:** `app/Models/User.php`

The `role`, `two_factor_enabled`, `two_factor_secret`, and `two_factor_recovery_codes` fields are mass-assignable. If any controller passes unfiltered input to create/update, an attacker could escalate to admin.

**Fix:** Remove sensitive fields from `$fillable` and set them explicitly in controllers.

---

### H10. Login URL Exposed in User Model Serialization
**File:** `app/Models/User.php`

A `login_url` accessor generates signed URLs valid for 7 days and is included in `$appends`. This appears in any JSON response containing user data.

**Fix:** Remove `login_url` from `$appends`.

---

## MEDIUM Findings

### M1. Password::defaults() Never Configured
**File:** `app/Providers/AppServiceProvider.php` (missing)

Without configuration, `Password::defaults()` resolves to `Password::min(8)` only — no mixed case, numbers, symbols, or uncompromised check.

**Fix:** Add to `AppServiceProvider::boot()`:
```php
Password::defaults(fn () => Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised());
```

---

### M2. 2FA Enforcement Is Broken
**File:** `app/Providers/RouteServiceProvider.php:120`

The wrong 2FA middleware is applied to admin routes. The package's enforcement middleware is registered but never used. `enforce_admin_2fa` defaults to `false`. 2FA enforcement is effectively dead code.

**Fix:** Apply the package's `RequireTwoFactorAuth` middleware to admin routes and set `enforce_admin_2fa` to `true`.

---

### M3. 2FA Session Flag Never Expires
**File:** `packages/upload-drive-in/laravel-admin-2fa/src/Http/Controllers/TwoFactorAuthController.php:97,103`

`session(['two_factor_verified' => true])` persists for the entire session (120 minutes) despite a `code_timeout` config of 300 seconds that is never checked.

**Fix:** Store a timestamp and check elapsed time in the middleware.

---

### M4. Unauthenticated Health Endpoints Expose System Information
**File:** `routes/web.php:103-114`

Public endpoints like `/health/detailed`, `/health/cloud-storage/configuration`, and `/health/cloud-storage/liveness` reveal disk usage, provider configuration, database status, and error messages.

**Fix:** Add authentication middleware or strip sensitive details from public responses.

---

### M5. No Content-Security-Policy Headers
No CSP headers or middleware exist anywhere in the codebase.

**Fix:** Add CSP middleware using a package like `spatie/laravel-csp` or custom middleware.

---

### M6. Performance Headers Triggered by Attacker-Controllable Header
**File:** `app/Http/Middleware/QueueWorkerPerformanceHeaders.php:96`

Any request with an `X-Debug-Performance` header triggers internal architecture information in response headers.

**Fix:** Remove the `$request->hasHeader('X-Debug-Performance')` check; only use `config('app.debug')`.

---

### M7. TrustProxies Trusts All Proxies
**File:** `app/Http/Middleware/TrustProxies.php:15`

`$proxies` is unset, potentially trusting all proxies and allowing IP spoofing via `X-Forwarded-For`.

**Fix:** Explicitly configure proxy IPs.

---

### M8. Session Encryption Disabled by Default
**File:** `config/session.php:50`

`SESSION_ENCRYPT` defaults to `false`. Session data including auth state is stored in plaintext in the database.

**Fix:** Set `SESSION_ENCRYPT=true` in production.

---

### M9. PreventClientPasswordLogin Middleware Never Applied
**File:** `bootstrap/app.php:24`

The middleware is registered but never applied to any route. It is dead code.

**Fix:** Apply to the `POST /login` route or remove if not needed.

---

## LOW Findings

### L1. 2FA Disable Requires No Password Re-confirmation
**File:** `packages/upload-drive-in/laravel-admin-2fa/routes/web.php:13`

`POST /admin/2fa/disable` has no `password.confirm` middleware.

### L2. .env Newline Injection
**File:** `app/Http/Controllers/Admin/AdminSettingsController.php:100`

`updateEnvironmentValue` does not strip `\n`/`\r` from values. Admin-only risk.

### L3. Queue Test Endpoints Missing Rate Limiting
**File:** `routes/admin.php:23`

TODO comment acknowledges this: "Re-add rate limiting middleware once container resolution is fixed."

### L4. Original Filename Stored Without Sanitization
**Files:** All upload controllers use `$file->getClientOriginalName()` directly. Potential stored XSS if rendered without escaping.

### L5. No Virus/Malware Scanning
No ClamAV or antivirus integration exists for files from untrusted public users.

### L6. Setup Routes Accessible If Flag Left Enabled
**File:** `routes/setup.php`

If `APP_SETUP_ENABLED` is left `true` in production, setup routes including email testing are publicly accessible.

---

## Recommendations Priority Matrix

| Priority | Action | Effort |
|----------|--------|--------|
| **Immediate** | Fix OAuth callback state validation (C1) | Medium |
| **Immediate** | Fix SQL injection in FileManagerService (C2) | Low |
| **Immediate** | Integrate FileSecurityService into upload path (C3) | Medium |
| **Immediate** | Move uploads to private disk + add type validation (C4) | Medium |
| **Immediate** | Encrypt Google Drive tokens in DB (C5) | Low |
| **Immediate** | Remove CSRF exclusion for setup/admin (C6) | Low |
| **This week** | Add rate limiting to 2FA verify and token login (H1, H2) | Low |
| **This week** | Add validation to chunked uploads (H3) | Medium |
| **This week** | Fix admin route middleware (H8) | Low |
| **This week** | Remove role from User $fillable (H9) | Low |
| **This week** | Remove debug middleware and token logging (H5, H6) | Low |
| **This sprint** | Configure Password::defaults (M1) | Low |
| **This sprint** | Fix 2FA enforcement (M2, M3) | Medium |
| **This sprint** | Add CSP headers (M5) | Medium |
| **This sprint** | Protect health endpoints (M4) | Low |
| **Backlog** | Add virus scanning (L5) | High |
| **Backlog** | Implement authorization policies (M5-related) | Medium |
