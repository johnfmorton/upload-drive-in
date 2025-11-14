# Design Document

## Overview

This design addresses the intermittent "419 Page Expired" errors that occur during login by implementing proper HTTPS session cookie configuration. The root cause is that the application uses HTTPS but lacks explicit session cookie security settings, causing browsers to inconsistently handle session cookies and invalidate CSRF tokens. The solution involves automatic HTTPS detection, proper cookie configuration, and comprehensive environment documentation.

## Architecture

### Problem Analysis

The current implementation has several configuration gaps:

1. **Missing SESSION_SECURE_COOKIE**: The `.env` file doesn't specify whether cookies should be marked as secure
2. **HTTPS Detection**: The `config/session.php` doesn't auto-detect HTTPS from `APP_URL`
3. **Incomplete Documentation**: The `.env.example` lacks session cookie security settings
4. **Browser Behavior**: Modern browsers may reject or inconsistently handle cookies without proper security attributes over HTTPS

### Solution Architecture

The solution implements a three-layer approach:

1. **Configuration Layer**: Auto-detect HTTPS and set appropriate defaults in `config/session.php`
2. **Environment Layer**: Add comprehensive session cookie settings to `.env.example` with documentation
3. **Setup Layer**: Update the setup wizard to generate proper session configuration based on `APP_URL`

### Design Principles

- **Convention over Configuration**: Auto-detect HTTPS and apply sensible defaults
- **Explicit over Implicit**: Allow manual override of auto-detected settings
- **Backward Compatibility**: Maintain existing behavior for explicitly configured environments
- **Security First**: Default to secure settings when HTTPS is detected

## Components and Interfaces

### 1. Session Configuration Enhancement

#### File: `config/session.php`

Add HTTPS auto-detection logic for the `secure` cookie setting:

```php
'secure' => env('SESSION_SECURE_COOKIE', function() {
    $appUrl = env('APP_URL', '');
    return str_starts_with(strtolower($appUrl), 'https://');
}),
```

**Logic Flow:**
1. Check if `SESSION_SECURE_COOKIE` is explicitly set in `.env`
2. If not set, parse `APP_URL` to detect HTTPS
3. Return `true` if URL starts with `https://`, otherwise `false`
4. Cache the result for performance

**Edge Cases:**
- Empty `APP_URL`: Default to `false` (insecure)
- Malformed `APP_URL`: Default to `false`
- Mixed case protocol: Normalize to lowercase before checking

#### Enhanced Cookie Configuration

Ensure all cookie-related settings have proper defaults:

```php
'same_site' => env('SESSION_SAME_SITE', 'lax'),
'http_only' => env('SESSION_HTTP_ONLY', true),
'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),
```

### 2. Environment Configuration

#### File: `.env.example`

Add comprehensive session cookie documentation:

```env
# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# Session Cookie Security
# Set to true when using HTTPS to ensure cookies are only sent over secure connections
# Auto-detected from APP_URL if not specified
SESSION_SECURE_COOKIE=

# Controls how cookies behave with cross-site requests (CSRF protection)
# Options: lax (recommended), strict, none
# Default: lax
SESSION_SAME_SITE=lax

# Prevents JavaScript access to session cookies (security best practice)
# Should always be true unless you have a specific reason to change it
# Default: true
SESSION_HTTP_ONLY=true

# Ties cookies to top-level site in cross-site contexts (advanced)
# Only needed for specific cross-site scenarios with secure cookies
# Default: false
SESSION_PARTITIONED_COOKIE=false
```

### 3. Setup Wizard Enhancement

#### File: `app/Services/SetupService.php` or equivalent

Update the environment file generation to include session cookie settings:

```php
protected function generateSessionConfiguration(string $appUrl): array
{
    $isHttps = str_starts_with(strtolower($appUrl), 'https://');
    
    return [
        'SESSION_DRIVER' => 'database',
        'SESSION_LIFETIME' => 120,
        'SESSION_ENCRYPT' => 'false',
        'SESSION_PATH' => '/',
        'SESSION_DOMAIN' => 'null',
        'SESSION_SECURE_COOKIE' => $isHttps ? 'true' : 'false',
        'SESSION_SAME_SITE' => 'lax',
        'SESSION_HTTP_ONLY' => 'true',
        'SESSION_PARTITIONED_COOKIE' => 'false',
    ];
}
```

### 4. Configuration Helper

#### Optional: Create a helper for session configuration

```php
// app/Helpers/SessionConfigHelper.php

namespace App\Helpers;

class SessionConfigHelper
{
    public static function shouldUseSecureCookies(): bool
    {
        // Check explicit configuration first
        $explicit = env('SESSION_SECURE_COOKIE');
        if ($explicit !== null) {
            return filter_var($explicit, FILTER_VALIDATE_BOOLEAN);
        }
        
        // Auto-detect from APP_URL
        $appUrl = env('APP_URL', '');
        return str_starts_with(strtolower($appUrl), 'https://');
    }
    
    public static function getRecommendedSameSite(): string
    {
        return env('SESSION_SAME_SITE', 'lax');
    }
}
```

## Data Models

No database schema changes required. This is purely a configuration enhancement.

## Error Handling

### Configuration Validation

Add validation to detect misconfiguration:

```php
// In a service provider or configuration validator
public function validateSessionConfiguration(): array
{
    $warnings = [];
    
    $appUrl = env('APP_URL', '');
    $isHttps = str_starts_with(strtolower($appUrl), 'https://');
    $secureCookie = config('session.secure');
    
    // Warn if using HTTPS without secure cookies
    if ($isHttps && !$secureCookie) {
        $warnings[] = 'APP_URL uses HTTPS but SESSION_SECURE_COOKIE is not enabled. This may cause session issues.';
    }
    
    // Warn if secure cookies are enabled without HTTPS
    if (!$isHttps && $secureCookie) {
        $warnings[] = 'SESSION_SECURE_COOKIE is enabled but APP_URL does not use HTTPS. Cookies will not be sent.';
    }
    
    return $warnings;
}
```

### Logging Strategy

Log session configuration on application boot (in development mode):

```php
if (config('app.debug')) {
    Log::debug('Session Configuration', [
        'driver' => config('session.driver'),
        'secure' => config('session.secure'),
        'same_site' => config('session.same_site'),
        'http_only' => config('session.http_only'),
        'app_url' => config('app.url'),
    ]);
}
```

## Testing Strategy

### Unit Tests

1. **HTTPS Detection Logic**
   - Test with `https://` URL returns `true`
   - Test with `http://` URL returns `false`
   - Test with empty URL returns `false`
   - Test with malformed URL returns `false`
   - Test explicit `SESSION_SECURE_COOKIE=true` overrides detection
   - Test explicit `SESSION_SECURE_COOKIE=false` overrides detection

2. **Configuration Helper**
   - Test `shouldUseSecureCookies()` with various scenarios
   - Test `getRecommendedSameSite()` returns correct values

### Integration Tests

1. **Session Cookie Behavior**
   - Test that cookies are marked secure with HTTPS
   - Test that cookies work correctly with HTTP
   - Test SameSite attribute is set correctly
   - Test HttpOnly attribute is set correctly

2. **CSRF Token Validation**
   - Test login form submission with proper CSRF token
   - Test CSRF token persists across page loads
   - Test CSRF token validation with secure cookies

### Feature Tests

1. **Login Flow**
   - Test successful login with HTTPS configuration
   - Test login form displays without errors
   - Test CSRF token is present in login form
   - Test session persists after login
   - Test no 419 errors occur during normal login flow

2. **Setup Wizard**
   - Test setup wizard generates correct session configuration for HTTPS
   - Test setup wizard generates correct session configuration for HTTP
   - Test generated `.env` includes all session cookie settings

### Manual Testing Checklist

- [ ] Clear browser cookies and cache
- [ ] Visit login page and verify session cookie is set
- [ ] Check cookie attributes in browser DevTools (Secure, SameSite, HttpOnly)
- [ ] Submit login form and verify no 419 error
- [ ] Repeat login 10 times to check for intermittent issues
- [ ] Test with different browsers (Chrome, Firefox, Safari)
- [ ] Test with browser privacy modes
- [ ] Verify session persists across page navigation

## Implementation Approach

### Phase 1: Configuration Updates

1. Update `config/session.php` with HTTPS auto-detection
2. Update `.env.example` with comprehensive session cookie documentation
3. Verify configuration loads correctly

### Phase 2: Setup Wizard Enhancement

1. Update setup wizard to generate session cookie configuration
2. Add validation to warn about misconfiguration
3. Test setup wizard with both HTTP and HTTPS URLs

### Phase 3: Testing and Validation

1. Add unit tests for HTTPS detection logic
2. Add integration tests for session cookie behavior
3. Add feature tests for login flow
4. Perform manual testing across browsers

### Phase 4: Documentation

1. Update deployment documentation with session configuration guidance
2. Add troubleshooting guide for 419 errors
3. Document the auto-detection behavior

## Security Considerations

### Cookie Security Attributes

- **Secure Flag**: Prevents cookie transmission over unencrypted HTTP
- **HttpOnly Flag**: Prevents JavaScript access to cookies (XSS protection)
- **SameSite Attribute**: Prevents CSRF attacks by controlling cross-site cookie behavior

### HTTPS Enforcement

While this design fixes cookie configuration, consider adding HTTPS enforcement:

```php
// Optional: Force HTTPS in production
if (app()->environment('production') && !request()->secure()) {
    return redirect()->secure(request()->getRequestUri());
}
```

### Session Security Best Practices

1. **Session Lifetime**: Keep `SESSION_LIFETIME` reasonable (120 minutes is good)
2. **Session Regeneration**: Regenerate session ID on login (Laravel does this by default)
3. **CSRF Protection**: Keep CSRF middleware enabled for all state-changing routes
4. **Session Encryption**: Consider enabling `SESSION_ENCRYPT=true` for sensitive data

## Backward Compatibility

### Existing Installations

For existing installations with explicit `SESSION_SECURE_COOKIE` settings:
- **No Change**: Explicit settings take precedence over auto-detection
- **Behavior**: Existing behavior is maintained

For existing installations without `SESSION_SECURE_COOKIE`:
- **HTTP Sites**: No change (secure cookies remain disabled)
- **HTTPS Sites**: Secure cookies are automatically enabled (fixes the issue)

### Migration Path

No migration required. Configuration changes take effect immediately on next application restart.

## Performance Considerations

### Configuration Caching

The HTTPS detection runs once during configuration loading. To optimize:

```bash
# Cache configuration in production
php artisan config:cache
```

### Session Driver Performance

Current configuration uses `SESSION_DRIVER=file`. Consider:
- **Database**: Better for multi-server deployments
- **Redis**: Best performance for high-traffic sites
- **File**: Adequate for single-server deployments

## Monitoring and Debugging

### Debug Mode Logging

In development, log session configuration:

```php
if (config('app.debug')) {
    Log::debug('Session cookie configuration', [
        'secure' => config('session.secure'),
        'same_site' => config('session.same_site'),
        'detected_https' => str_starts_with(config('app.url'), 'https://'),
    ]);
}
```

### Production Monitoring

Monitor for 419 errors:

```php
// In exception handler
if ($exception instanceof TokenMismatchException) {
    Log::warning('CSRF token mismatch', [
        'url' => request()->url(),
        'user_agent' => request()->userAgent(),
        'session_driver' => config('session.driver'),
        'secure_cookie' => config('session.secure'),
    ]);
}
```

## Alternative Approaches Considered

### 1. Middleware-Based Detection

**Approach**: Use middleware to set cookie attributes dynamically
**Rejected**: Configuration-level solution is cleaner and more performant

### 2. Environment-Specific Config Files

**Approach**: Separate `config/session.local.php` and `config/session.production.php`
**Rejected**: Laravel's environment variable approach is more flexible

### 3. Always Use Secure Cookies

**Approach**: Force secure cookies regardless of protocol
**Rejected**: Breaks local HTTP development environments

## Deployment Considerations

### DDEV Environment

DDEV provides HTTPS by default. Ensure:
- `APP_URL` is set to `https://upload-drive-in.ddev.site`
- Auto-detection will enable secure cookies
- No manual configuration needed

### Production Environment

For production deployment:
1. Ensure `APP_URL` uses `https://`
2. Run `php artisan config:cache` to cache configuration
3. Verify session cookies have Secure attribute in browser DevTools
4. Monitor for 419 errors in logs

### Staging Environment

For staging environments:
- Use HTTPS if possible to match production
- If using HTTP, ensure `SESSION_SECURE_COOKIE=false` is explicit
