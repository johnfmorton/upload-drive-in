# Employee Cloud Storage Access Control for S3

## Overview

This document describes the implementation of conditional access control for the Employee Cloud Storage page based on the configured cloud storage provider.

## Problem Statement

When the admin user sets the Cloud Storage Provider to Amazon S3, the Employee User Admin panel should not display the Cloud Storage navigation item, and employees should not be able to access the Cloud Storage page URL. This is because S3 uses API key authentication managed by the admin, not OAuth authentication that requires individual user connections.

However, when the cloud storage provider is set to Google Drive (or other OAuth providers), the navigation item and page should be accessible to employee users.

## Solution

The solution implements conditional access control based on the `auth_type` configuration of the active cloud storage provider:

- **OAuth providers** (like Google Drive, Microsoft Teams): Require user authentication, so the Cloud Storage page is accessible
- **API Key providers** (like Amazon S3): Managed entirely by admin, so the Cloud Storage page is hidden and inaccessible

## Implementation Details

### 1. Navigation Visibility Control

**File:** `resources/views/layouts/navigation.blade.php`

Added conditional logic to both the desktop dropdown menu and responsive mobile menu:

```blade
@php
    $defaultProvider = config('cloud-storage.default');
    $providerConfig = config("cloud-storage.providers.{$defaultProvider}");
    $requiresUserAuth = ($providerConfig['auth_type'] ?? 'oauth') === 'oauth';
@endphp
@if($requiresUserAuth)
    <div class="border-t border-gray-100"></div>
    <x-dropdown-link :href="route('employee.cloud-storage.index', ['username' => auth()->user()->username])">
        {{ __('Cloud Storage') }}
    </x-dropdown-link>
@endif
```

**Behavior:**
- When `CLOUD_STORAGE_DEFAULT=google-drive`: Navigation item is visible
- When `CLOUD_STORAGE_DEFAULT=amazon-s3`: Navigation item is hidden

### 2. Controller Access Control

**File:** `app/Http/Controllers/Employee/CloudStorageController.php`

Added access control checks to all controller methods:

#### `index()` Method
Redirects to dashboard with informational message when provider doesn't require user auth:

```php
// Check if the current provider requires user authentication
$defaultProvider = config('cloud-storage.default');
$providerConfig = config("cloud-storage.providers.{$defaultProvider}");
$requiresUserAuth = ($providerConfig['auth_type'] ?? 'oauth') === 'oauth';

// If provider doesn't require user auth (like S3), redirect to dashboard
if (!$requiresUserAuth) {
    return redirect()
        ->route('employee.dashboard', ['username' => Auth::user()->username])
        ->with('info', 'Cloud storage is managed by the administrator for the current provider.');
}
```

#### `getStatus()` Method
Returns 403 error for API requests when provider doesn't require user auth:

```php
if (!$requiresUserAuth) {
    return response()->json([
        'success' => false,
        'error' => 'Cloud storage status is not available for the current provider.',
        'message' => 'Provider does not require user authentication'
    ], 403);
}
```

#### `reconnectProvider()` Method
Returns 403 error when attempting to reconnect a non-OAuth provider:

```php
if (!$requiresUserAuth) {
    return response()->json([
        'error' => 'Reconnection is not available for the current provider.'
    ], 403);
}
```

#### `testConnection()` Method
Returns 403 error when attempting to test connection for non-OAuth provider:

```php
if (!$requiresUserAuth) {
    return response()->json([
        'success' => false,
        'error' => 'Connection testing is not available for the current provider.',
        'message' => 'Provider does not require user authentication'
    ], 403);
}
```

## Configuration Reference

The solution relies on the `auth_type` configuration in `config/cloud-storage.php`:

```php
'providers' => [
    'google-drive' => [
        'auth_type' => 'oauth',  // Requires user authentication
        // ...
    ],
    
    'amazon-s3' => [
        'auth_type' => 'api_key',  // Admin-managed, no user auth
        // ...
    ],
    
    'microsoft-teams' => [
        'auth_type' => 'oauth',  // Requires user authentication
        // ...
    ],
]
```

## Testing

### Manual Testing Steps

1. **Test with Google Drive (OAuth provider):**
   ```bash
   # Set provider to Google Drive
   sed -i '' 's/CLOUD_STORAGE_DEFAULT=.*/CLOUD_STORAGE_DEFAULT=google-drive/' .env
   ddev artisan config:clear
   ```
   - Login as employee user
   - Verify "Cloud Storage" appears in navigation dropdown
   - Verify you can access the Cloud Storage page
   - Verify the page shows Google Drive connection options

2. **Test with Amazon S3 (API Key provider):**
   ```bash
   # Set provider to Amazon S3
   sed -i '' 's/CLOUD_STORAGE_DEFAULT=.*/CLOUD_STORAGE_DEFAULT=amazon-s3/' .env
   ddev artisan config:clear
   ```
   - Login as employee user
   - Verify "Cloud Storage" does NOT appear in navigation dropdown
   - Try to access `/employee/{username}/cloud-storage` directly
   - Verify you are redirected to dashboard with info message
   - Verify API endpoints return 403 errors

3. **Test responsive navigation:**
   - Repeat above tests on mobile viewport
   - Verify hamburger menu shows/hides Cloud Storage link appropriately

### Expected Behavior Matrix

| Provider | Auth Type | Navigation Visible | Page Accessible | API Endpoints |
|----------|-----------|-------------------|-----------------|---------------|
| Google Drive | oauth | ✅ Yes | ✅ Yes | ✅ Available |
| Amazon S3 | api_key | ❌ No | ❌ No (redirects) | ❌ 403 Error |
| Microsoft Teams | oauth | ✅ Yes | ✅ Yes | ✅ Available |

## Security Considerations

1. **Defense in Depth:** Both navigation hiding and controller-level access control are implemented
2. **API Protection:** All API endpoints check provider type before processing requests
3. **Graceful Degradation:** Users receive informative messages rather than errors
4. **Configuration-Driven:** Access control is based on provider configuration, not hardcoded provider names

## Future Enhancements

1. **Middleware Approach:** Consider creating a dedicated middleware for provider-based access control
2. **Route-Level Protection:** Add route middleware to prevent route registration for non-OAuth providers
3. **Caching:** Cache provider configuration checks to reduce repeated config lookups
4. **Admin Override:** Allow admins to force-enable employee cloud storage pages for specific use cases

## Related Files

- `resources/views/layouts/navigation.blade.php` - Navigation visibility
- `app/Http/Controllers/Employee/CloudStorageController.php` - Access control
- `config/cloud-storage.php` - Provider configuration
- `routes/employee-portal.php` - Employee routes

## Rollback Instructions

If issues arise, revert the changes:

```bash
git checkout HEAD -- resources/views/layouts/navigation.blade.php
git checkout HEAD -- app/Http/Controllers/Employee/CloudStorageController.php
ddev artisan config:clear
ddev artisan view:clear
```
