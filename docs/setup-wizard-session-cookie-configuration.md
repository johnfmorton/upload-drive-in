# Setup Wizard Session Cookie Configuration

## Overview

The setup wizard automatically generates session cookie configuration based on the `APP_URL` setting. This ensures that session cookies are properly configured for HTTPS environments, preventing "419 Page Expired" errors and ensuring secure session handling.

## Automatic Configuration

When the setup wizard generates or updates the `.env` file, it automatically:

1. **Detects HTTPS from APP_URL**: Analyzes the protocol in the `APP_URL` setting
2. **Generates appropriate settings**: Creates session cookie configuration based on the detected protocol
3. **Adds documentation**: Includes comments explaining each setting

## Generated Settings

### SESSION_SECURE_COOKIE

- **HTTPS URLs** (`https://...`): Set to `true`
- **HTTP URLs** (`http://...`): Set to `false`
- **Purpose**: Ensures cookies are only transmitted over secure connections when using HTTPS

### SESSION_SAME_SITE

- **Default**: `lax`
- **Purpose**: Provides CSRF protection by controlling cross-site cookie behavior
- **Options**:
  - `lax`: Cookies sent with top-level navigation and same-site requests (recommended)
  - `strict`: Cookies only sent with same-site requests (most secure)
  - `none`: Cookies sent with all requests (requires `SESSION_SECURE_COOKIE=true`)

### SESSION_HTTP_ONLY

- **Default**: `true`
- **Purpose**: Prevents JavaScript access to session cookies (XSS protection)
- **Recommendation**: Should always remain `true` unless you have a specific need

### SESSION_PARTITIONED_COOKIE

- **Default**: `false`
- **Purpose**: Ties cookies to top-level site in cross-site contexts
- **Use Case**: Only needed for specific cross-site scenarios with embedded content

## Usage in Setup Service

### Generating Session Cookie Configuration

```php
use App\Services\EnvironmentFileService;

$envService = app(EnvironmentFileService::class);

// Generate configuration based on APP_URL
$appUrl = 'https://example.com';
$sessionConfig = $envService->generateSessionCookieConfiguration($appUrl);

// Result:
// [
//     'SESSION_SECURE_COOKIE' => 'true',
//     'SESSION_SAME_SITE' => 'lax',
//     'SESSION_HTTP_ONLY' => 'true',
//     'SESSION_PARTITIONED_COOKIE' => 'false',
// ]
```

### Updating Environment with Session Configuration

```php
use App\Services\SetupService;

$setupService = app(SetupService::class);

// Update session cookie configuration based on current APP_URL
$result = $setupService->updateSessionCookieEnvironment();

if ($result['success']) {
    echo "Session cookie configuration updated successfully";
} else {
    echo "Failed: " . $result['message'];
}
```

### Generating Complete Environment Configuration

```php
use App\Services\SetupService;

$setupService = app(SetupService::class);

$config = [
    'app_url' => 'https://example.com',
    'database' => [
        'connection' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'myapp',
        'username' => 'user',
        'password' => 'password',
    ],
    'storage' => [
        'client_id' => 'google-client-id',
        'client_secret' => 'google-client-secret',
    ],
];

// Generate complete environment configuration including session cookies
$envVars = $setupService->generateCompleteEnvironmentConfiguration($config);

// Session cookie settings are automatically included based on app_url
```

## Environment File Format

When the setup wizard generates the `.env` file, session cookie settings are included with comprehensive documentation:

```env
# Session Cookie Security Configuration
# These settings control how session cookies behave, particularly important for HTTPS environments.
# Proper configuration prevents "419 Page Expired" errors and ensures secure session handling.

# SESSION_SECURE_COOKIE: Controls whether cookies are only sent over HTTPS connections
# - Auto-detected from APP_URL (https:// = true, http:// = false)
# - Set to "true" when using HTTPS (recommended for production)
# - Set to "false" for local HTTP development
SESSION_SECURE_COOKIE=true

# SESSION_SAME_SITE: Controls CSRF protection (Options: lax, strict, none)
SESSION_SAME_SITE=lax

# SESSION_HTTP_ONLY: Prevents JavaScript access to cookies (XSS protection)
SESSION_HTTP_ONLY=true

# SESSION_PARTITIONED_COOKIE: For cross-site contexts (advanced)
SESSION_PARTITIONED_COOKIE=false
```

## HTTPS Detection Logic

The HTTPS detection is case-insensitive and handles various URL formats:

```php
// HTTPS URLs - SESSION_SECURE_COOKIE=true
https://example.com
https://subdomain.example.com
HTTPS://EXAMPLE.COM (mixed case)
https://upload-drive-in.ddev.site

// HTTP URLs - SESSION_SECURE_COOKIE=false
http://localhost
http://example.com
HTTP://EXAMPLE.COM (mixed case)
```

## Edge Cases

### Empty APP_URL

If `APP_URL` is not set or empty:
- `SESSION_SECURE_COOKIE` defaults to `false` (insecure)
- A warning is logged
- The update operation returns an error

### Malformed APP_URL

If `APP_URL` is malformed or doesn't start with `http://` or `https://`:
- `SESSION_SECURE_COOKIE` defaults to `false` (insecure)
- The system treats it as HTTP for safety

## Integration with Setup Wizard

The setup wizard automatically calls these methods when:

1. **Initial Setup**: Generating the `.env` file for the first time
2. **Database Configuration**: When updating database settings
3. **Storage Configuration**: When configuring cloud storage
4. **Manual Update**: When explicitly updating session configuration

## Security Considerations

### HTTPS Enforcement

While this feature configures cookies properly for HTTPS, it does not enforce HTTPS at the application level. Consider adding HTTPS enforcement in production:

```php
// In a middleware or service provider
if (app()->environment('production') && !request()->secure()) {
    return redirect()->secure(request()->getRequestUri());
}
```

### Configuration Validation

The setup wizard validates that:
- `SESSION_SECURE_COOKIE` matches the `APP_URL` protocol
- Warnings are logged if there's a mismatch
- Configuration cache is cleared after updates

## Troubleshooting

### Session Issues After Setup

If you experience session issues after setup:

1. **Verify APP_URL**: Ensure it matches your actual URL (including protocol)
2. **Check SESSION_SECURE_COOKIE**: Should be `true` for HTTPS, `false` for HTTP
3. **Clear Configuration Cache**: Run `php artisan config:clear`
4. **Clear Browser Cookies**: Remove existing cookies and try again

### Mismatch Between APP_URL and SESSION_SECURE_COOKIE

If you manually change `APP_URL` from HTTP to HTTPS (or vice versa):

1. Update `SESSION_SECURE_COOKIE` to match
2. Run `php artisan config:clear`
3. Or use the setup service to regenerate configuration:

```php
$setupService = app(SetupService::class);
$setupService->updateSessionCookieEnvironment();
```

## Testing

To test the session cookie configuration generation:

```php
use App\Services\EnvironmentFileService;
use App\Services\SetupSecurityService;

$securityService = app(SetupSecurityService::class);
$envService = new EnvironmentFileService($securityService);

// Test HTTPS URL
$config = $envService->generateSessionCookieConfiguration('https://example.com');
assert($config['SESSION_SECURE_COOKIE'] === 'true');

// Test HTTP URL
$config = $envService->generateSessionCookieConfiguration('http://localhost');
assert($config['SESSION_SECURE_COOKIE'] === 'false');
```

## Related Documentation

- [Session Cookie Configuration Guide](./session-cookie-configuration.md)
- [CSRF Protection](../SECURITY_IMPLEMENTATION_SUMMARY.md)
- [Environment Configuration Guide](./environment-configuration-guide.md)
