# Session Cookie Configuration Guide

This guide explains the session cookie security settings available in the application and when to use each option.

## Overview

Session cookies are used to maintain user authentication state across requests. Proper configuration of these cookies is critical for both security and functionality, especially when using HTTPS.

## Configuration Options

### SESSION_SECURE_COOKIE

**Purpose**: Controls whether session cookies are marked with the `Secure` attribute, which ensures cookies are only transmitted over HTTPS connections.

**Available Options**:
- `true` - Cookies will only be sent over HTTPS connections
- `false` - Cookies will be sent over both HTTP and HTTPS connections
- Not set - Auto-detected from `APP_URL` (recommended)

**When to Use Each Option**:

#### Use `SESSION_SECURE_COOKIE=true` when:
- Your application uses HTTPS (production environments)
- Your local development environment uses HTTPS (like DDEV)
- You want maximum security for session cookies
- Your `APP_URL` starts with `https://`

**Example**:
```env
APP_URL=https://example.com
SESSION_SECURE_COOKIE=true
```

#### Use `SESSION_SECURE_COOKIE=false` when:
- Your application uses HTTP (local development without SSL)
- You're testing in an environment without HTTPS
- Your `APP_URL` starts with `http://`

**Example**:
```env
APP_URL=http://localhost:8000
SESSION_SECURE_COOKIE=false
```

#### Leave unset (recommended) when:
- You want automatic detection based on `APP_URL`
- You're using the same codebase across multiple environments
- You want the application to automatically configure itself

**Example**:
```env
APP_URL=https://example.com
# SESSION_SECURE_COOKIE not set - will auto-detect as true
```

**Security Implications**:
- ✅ **With HTTPS + Secure Cookie**: Maximum security - cookies cannot be intercepted over unencrypted connections
- ⚠️ **With HTTPS + No Secure Cookie**: Reduced security - browsers may reject cookies or behave inconsistently
- ⚠️ **With HTTP + Secure Cookie**: Application will not work - cookies will not be sent
- ❌ **With HTTP + No Secure Cookie**: Insecure - only acceptable for local development

---

### SESSION_SAME_SITE

**Purpose**: Controls how cookies behave with cross-site requests, providing CSRF (Cross-Site Request Forgery) protection.

**Available Options**:
- `lax` (recommended) - Cookies sent with top-level navigation and same-site requests
- `strict` - Cookies only sent with same-site requests
- `none` - Cookies sent with all requests (requires `SESSION_SECURE_COOKIE=true`)

**When to Use Each Option**:

#### Use `SESSION_SAME_SITE=lax` when:
- You want good CSRF protection with minimal user friction (recommended)
- Users navigate to your site from external links
- You have standard web application behavior
- You're unsure which option to choose

**Example**:
```env
SESSION_SAME_SITE=lax
```

**Behavior**:
- ✅ Cookies sent when user clicks a link to your site
- ✅ Cookies sent for same-site requests (within your domain)
- ❌ Cookies NOT sent for cross-site POST requests
- ❌ Cookies NOT sent for embedded iframes from other sites

**Use Cases**:
- Standard web applications
- E-commerce sites
- Content management systems
- Most Laravel applications

#### Use `SESSION_SAME_SITE=strict` when:
- You need maximum CSRF protection
- Your application doesn't need to maintain sessions from external links
- Security is more important than user convenience
- You have a banking or financial application

**Example**:
```env
SESSION_SAME_SITE=strict
```

**Behavior**:
- ❌ Cookies NOT sent when user clicks external link to your site
- ✅ Cookies sent for same-site requests only
- ❌ Cookies NOT sent for any cross-site requests

**Use Cases**:
- Banking applications
- Financial services
- High-security admin panels
- Applications where users always navigate directly

**Trade-offs**:
- ⚠️ Users clicking links from emails will appear logged out
- ⚠️ Users coming from external sites will need to log in again
- ⚠️ May cause confusion for users expecting to stay logged in

#### Use `SESSION_SAME_SITE=none` when:
- Your application is embedded in iframes on other domains
- You need cross-site cookie access
- You're building a widget or embeddable application
- You have specific cross-origin requirements

**Example**:
```env
SESSION_SAME_SITE=none
SESSION_SECURE_COOKIE=true  # Required when using none
```

**Behavior**:
- ✅ Cookies sent with all requests, including cross-site
- ⚠️ Requires HTTPS and `SESSION_SECURE_COOKIE=true`
- ⚠️ Reduced CSRF protection

**Use Cases**:
- Embeddable widgets
- Third-party integrations
- Cross-domain applications
- OAuth providers

**Requirements**:
- MUST use HTTPS
- MUST set `SESSION_SECURE_COOKIE=true`
- MUST implement additional CSRF protection

---

### SESSION_HTTP_ONLY

**Purpose**: Controls whether JavaScript can access session cookies, providing XSS (Cross-Site Scripting) protection.

**Available Options**:
- `true` (recommended) - JavaScript cannot access cookies
- `false` - JavaScript can access cookies via `document.cookie`

**When to Use Each Option**:

#### Use `SESSION_HTTP_ONLY=true` when:
- You want to protect against XSS attacks (recommended)
- You don't need JavaScript to access session cookies
- You're following security best practices
- You're unsure which option to choose

**Example**:
```env
SESSION_HTTP_ONLY=true
```

**Security Benefits**:
- ✅ Prevents XSS attacks from stealing session cookies
- ✅ Cookies cannot be accessed via `document.cookie`
- ✅ Reduces attack surface for session hijacking
- ✅ Industry standard security practice

**Use Cases**:
- All production applications (recommended)
- Any application handling sensitive data
- Standard Laravel applications
- E-commerce and financial applications

#### Use `SESSION_HTTP_ONLY=false` when:
- You have a specific requirement for JavaScript to access cookies
- You're implementing custom client-side session management
- You understand the security implications

**Example**:
```env
SESSION_HTTP_ONLY=false
```

**Security Risks**:
- ❌ XSS attacks can steal session cookies
- ❌ Malicious scripts can access authentication tokens
- ❌ Increased risk of session hijacking
- ❌ Not recommended for production

**Use Cases**:
- Custom authentication systems requiring client-side access
- Specific legacy application requirements
- Development/debugging (temporary only)

**Important**: If you set this to `false`, you MUST implement additional XSS protection measures.

---

## Recommended Configurations

### Production (HTTPS)
```env
APP_URL=https://example.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_HTTP_ONLY=true
```

### Local Development (HTTPS with DDEV)
```env
APP_URL=https://upload-drive-in.ddev.site
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_HTTP_ONLY=true
```

### Local Development (HTTP)
```env
APP_URL=http://localhost:8000
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
SESSION_HTTP_ONLY=true
```

### High-Security Application
```env
APP_URL=https://secure-app.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
SESSION_HTTP_ONLY=true
```

### Embeddable Widget
```env
APP_URL=https://widget.example.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none
SESSION_HTTP_ONLY=true
```

---

## Troubleshooting

### Issue: "419 Page Expired" errors on login

**Possible Causes**:
1. Using HTTPS without `SESSION_SECURE_COOKIE=true`
2. Mismatched `APP_URL` and actual URL
3. Browser rejecting cookies due to security settings

**Solutions**:
1. Set `SESSION_SECURE_COOKIE=true` if using HTTPS
2. Verify `APP_URL` matches your actual URL (including protocol)
3. Clear browser cookies and cache
4. Check browser console for cookie warnings

### Issue: Users logged out when clicking external links

**Cause**: `SESSION_SAME_SITE=strict` is too restrictive

**Solution**: Change to `SESSION_SAME_SITE=lax`

### Issue: Cookies not working in embedded iframe

**Cause**: `SESSION_SAME_SITE` is blocking cross-site cookies

**Solution**: 
1. Set `SESSION_SAME_SITE=none`
2. Set `SESSION_SECURE_COOKIE=true`
3. Ensure you're using HTTPS

### Issue: Session cookies visible in JavaScript

**Cause**: `SESSION_HTTP_ONLY=false`

**Solution**: Set `SESSION_HTTP_ONLY=true` for security

---

## Security Best Practices

### Always Do:
- ✅ Use `SESSION_SECURE_COOKIE=true` in production with HTTPS
- ✅ Use `SESSION_HTTP_ONLY=true` to prevent XSS attacks
- ✅ Use `SESSION_SAME_SITE=lax` for good CSRF protection
- ✅ Use HTTPS in production environments
- ✅ Keep `SESSION_LIFETIME` reasonable (120 minutes is good)
- ✅ Regenerate session ID on login (Laravel does this automatically)

### Never Do:
- ❌ Use `SESSION_SECURE_COOKIE=true` with HTTP
- ❌ Use `SESSION_HTTP_ONLY=false` without good reason
- ❌ Use `SESSION_SAME_SITE=none` without HTTPS
- ❌ Disable CSRF protection
- ❌ Store sensitive data in cookies accessible to JavaScript

### Consider:
- 💡 Auto-detection by leaving `SESSION_SECURE_COOKIE` unset
- 💡 Using `SESSION_SAME_SITE=strict` for high-security applications
- 💡 Implementing additional security layers (2FA, rate limiting)
- 💡 Monitoring for session-related errors in production

---

## Testing Your Configuration

### Verify Cookie Attributes in Browser

1. Open your application in a browser
2. Open Developer Tools (F12)
3. Go to Application/Storage → Cookies
4. Find your session cookie (usually named after `SESSION_COOKIE` config)
5. Verify the attributes:
   - **Secure**: Should be ✓ if using HTTPS
   - **HttpOnly**: Should be ✓ (recommended)
   - **SameSite**: Should show your configured value (Lax, Strict, or None)

### Test Login Flow

1. Clear all cookies and cache
2. Visit the login page
3. Check that CSRF token is present in the form
4. Submit login credentials
5. Verify no "419 Page Expired" errors
6. Confirm session persists across page navigation
7. Test logout functionality

### Test Cross-Site Behavior (if using SameSite=strict)

1. Send yourself an email with a link to your application
2. Click the link from your email client
3. Verify you need to log in (expected with strict)
4. Navigate within the site
5. Verify session persists during same-site navigation

---

## Additional Resources

- [Laravel Session Documentation](https://laravel.com/docs/session)
- [MDN: Set-Cookie](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie)
- [OWASP Session Management](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
- [SameSite Cookie Explained](https://web.dev/samesite-cookies-explained/)

---

## Configuration Reference

### Quick Reference Table

| Setting | Production | Dev (HTTPS) | Dev (HTTP) | High Security | Embedded |
|---------|-----------|-------------|------------|---------------|----------|
| `SESSION_SECURE_COOKIE` | `true` | `true` | `false` | `true` | `true` |
| `SESSION_SAME_SITE` | `lax` | `lax` | `lax` | `strict` | `none` |
| `SESSION_HTTP_ONLY` | `true` | `true` | `true` | `true` | `true` |

### Environment Detection

The application automatically detects HTTPS from `APP_URL` if `SESSION_SECURE_COOKIE` is not explicitly set:

```php
// In config/session.php
'secure' => env('SESSION_SECURE_COOKIE', function() {
    $appUrl = env('APP_URL', '');
    return str_starts_with(strtolower($appUrl), 'https://');
}),
```

This means you can omit `SESSION_SECURE_COOKIE` and let the application configure itself based on your `APP_URL`.
