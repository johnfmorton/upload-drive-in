# Design Document

## Overview

This design addresses the inconsistency between the admin and employee dashboards regarding the upload page widget display. Currently, the admin dashboard uses a Google Drive-specific component (`google-drive-connection`) that displays "Google Drive Connection" as the title regardless of the configured storage provider. The employee dashboard correctly uses a provider-agnostic component (`upload-page-section`) that adapts to any storage provider.

The solution is to update the admin dashboard to use the same `upload-page-section` component that the employee dashboard uses, while maintaining the Google Drive-specific connection management functionality through a separate component.

## Architecture

### Current Architecture

**Admin Dashboard:**
```
admin/dashboard.blade.php
└── x-dashboard.google-drive-connection (always shown)
    ├── Hardcoded "Google Drive Connection" title
    ├── Upload URL display (if connected)
    └── Google Drive connection management
```

**Employee Dashboard:**
```
employee/dashboard.blade.php
├── x-dashboard.upload-page-section (always shown)
│   ├── Dynamic "Your Upload Page" title
│   ├── Provider-specific icon and label
│   ├── Upload URL display
│   └── System-level storage info messages
└── x-dashboard.google-drive-connection (only for Google Drive)
    └── Google Drive connection management
```

### Proposed Architecture

**Admin Dashboard (Updated):**
```
admin/dashboard.blade.php
├── x-dashboard.upload-page-section (always shown)
│   ├── Dynamic "Your Upload Page" title
│   ├── Provider-specific icon and label
│   ├── Upload URL display
│   └── System-level storage info messages
└── x-dashboard.google-drive-connection (only for Google Drive)
    └── Google Drive connection management
```

This creates consistency between admin and employee dashboards while maintaining all existing functionality.

## Components and Interfaces

### Component: upload-page-section.blade.php

**Purpose:** Display the upload page URL and storage provider information for any storage provider

**Props:**
- `user` (User): The authenticated user object
- `storageProvider` (array): Storage provider configuration data

**Storage Provider Array Structure:**
```php
[
    'provider' => 'amazon-s3',           // Provider identifier
    'display_name' => 'Amazon S3',       // Human-readable name
    'requires_user_auth' => false,       // Whether user needs to authenticate
    'is_configured' => true,             // Whether provider is configured
    'error' => null                      // Error message if any
]
```

**Behavior:**
- Displays "Your Upload Page" as the title for all providers
- Shows provider-specific icon (Google Drive icon for Google Drive, generic cloud icon for others)
- Displays provider name or "Cloud Storage" label
- Shows upload URL with copy functionality
- For system-level storage (S3, etc.), displays info message about organizational storage
- Handles error states gracefully

**No Changes Required:** This component already exists and works correctly

### Component: google-drive-connection.blade.php

**Purpose:** Manage Google Drive-specific connection functionality

**Props:**
- `user` (User): The authenticated user object
- `isAdmin` (bool): Whether the user is an admin
- `storageProvider` (array|null): Storage provider configuration data

**Current Behavior:**
- Shows for all providers (incorrect)
- Displays "Google Drive Connection" title
- Shows upload URL section
- Manages Google Drive OAuth connection

**Required Changes:**
- Add early return when `storageProvider` is provided and is not Google Drive
- Remove upload URL display section (now handled by upload-page-section)
- Keep only Google Drive connection management functionality
- Update title to be more specific about connection management

**Updated Behavior:**
- Only renders when Google Drive is the configured provider
- Focuses solely on connection management (connect/disconnect buttons, status messages)
- Does not display upload URL (delegated to upload-page-section)

### Controller: Admin/DashboardController.php

**Current Implementation:**
```php
public function index()
{
    $user = auth()->user();
    $files = FileUpload::where(...)->paginate(...);
    $isFirstTimeLogin = $this->checkFirstTimeLogin();
    
    return view('admin.dashboard', compact('files', 'isFirstTimeLogin'));
}
```

**Required Changes:**
Add storage provider data retrieval to match employee dashboard:

```php
public function index()
{
    $user = auth()->user();
    $files = FileUpload::where(...)->paginate(...);
    $isFirstTimeLogin = $this->checkFirstTimeLogin();
    
    // Get storage provider information
    $storageProvider = $this->getStorageProviderInfo($user);
    
    return view('admin.dashboard', compact('files', 'isFirstTimeLogin', 'storageProvider'));
}

private function getStorageProviderInfo(User $user): array
{
    $defaultProvider = config('cloud-storage.default');
    $providerConfig = config("cloud-storage.providers.{$defaultProvider}");
    
    return [
        'provider' => $defaultProvider,
        'display_name' => $providerConfig['display_name'] ?? ucwords(str_replace('-', ' ', $defaultProvider)),
        'requires_user_auth' => $providerConfig['requires_user_auth'] ?? false,
        'is_configured' => $this->isProviderConfigured($defaultProvider),
        'error' => null
    ];
}

private function isProviderConfigured(string $provider): bool
{
    switch ($provider) {
        case 'google-drive':
            $clientId = CloudStorageSetting::getEffectiveValue('google-drive', 'client_id');
            $clientSecret = CloudStorageSetting::getEffectiveValue('google-drive', 'client_secret');
            return !empty($clientId) && !empty($clientSecret);
            
        case 'amazon-s3':
            return !empty(config('filesystems.disks.s3.key')) 
                && !empty(config('filesystems.disks.s3.secret'))
                && !empty(config('filesystems.disks.s3.bucket'));
                
        default:
            return false;
    }
}
```

### View: admin/dashboard.blade.php

**Current Structure:**
```blade
<x-dashboard.google-drive-connection :user="Auth::user()" :is-admin="true" />
```

**Updated Structure:**
```blade
<!-- Upload Page Section - Always shown -->
<x-dashboard.upload-page-section 
    :user="Auth::user()" 
    :storage-provider="$storageProvider" 
/>

<!-- Google Drive Connection Management - Only for Google Drive -->
<x-dashboard.google-drive-connection 
    :user="Auth::user()" 
    :is-admin="true"
    :storage-provider="$storageProvider"
/>
```

## Data Models

### User Model

**Existing Attributes Used:**
- `upload_url`: The user's unique upload URL
- `googleDriveToken`: Google Drive OAuth token (relationship)

**No Changes Required**

### CloudStorageSetting Model

**Existing Methods Used:**
- `getEffectiveValue($provider, $key)`: Get configuration value for a provider

**No Changes Required**

## Error Handling

### Missing Storage Provider Configuration

**Scenario:** Storage provider is not properly configured

**Handling:**
- `upload-page-section` component displays error state
- Shows "Storage configuration error" message
- Suggests contacting administrator

### Missing Upload URL

**Scenario:** User doesn't have an upload URL generated

**Handling:**
- `upload-page-section` component displays warning state
- Shows "Upload page not available" message
- This is a rare edge case as URLs are generated during user creation

### Google Drive Connection Errors

**Scenario:** Google Drive OAuth connection fails

**Handling:**
- Existing error handling in `google-drive-connection` component
- Shows appropriate error messages
- Provides retry options

## Testing Strategy

### Unit Tests

**Test: Admin Dashboard Controller**
- Test `getStorageProviderInfo()` returns correct data for Google Drive
- Test `getStorageProviderInfo()` returns correct data for Amazon S3
- Test `isProviderConfigured()` correctly detects Google Drive configuration
- Test `isProviderConfigured()` correctly detects Amazon S3 configuration
- Test dashboard index passes storage provider data to view

### Feature Tests

**Test: Admin Dashboard View with Amazon S3**
- Test dashboard displays "Your Upload Page" title (not "Google Drive Connection")
- Test dashboard displays cloud icon with "Cloud Storage" label
- Test dashboard displays S3 info message
- Test dashboard does NOT display Google Drive connection widget
- Test upload URL is displayed with copy button

**Test: Admin Dashboard View with Google Drive**
- Test dashboard displays "Your Upload Page" title
- Test dashboard displays Google Drive icon and label
- Test dashboard displays Google Drive connection widget
- Test Google Drive connection status is shown
- Test connect/disconnect buttons are present

**Test: Admin Dashboard View Consistency**
- Test admin dashboard matches employee dashboard structure for S3
- Test admin dashboard matches employee dashboard structure for Google Drive
- Test both dashboards use same upload-page-section component

### Component Tests

**Test: upload-page-section Component**
- Test component displays correct title for all providers
- Test component displays correct icon for Google Drive
- Test component displays generic cloud icon for S3
- Test component displays system-level storage info for S3
- Test component does not display system-level storage info for Google Drive

**Test: google-drive-connection Component**
- Test component does not render when provider is Amazon S3
- Test component does not render when provider is not Google Drive
- Test component renders when provider is Google Drive
- Test component displays connection management UI for Google Drive

### Manual Testing

**Test Scenarios:**
1. Admin with Amazon S3 configured
   - Verify "Your Upload Page" title is shown
   - Verify cloud icon with "Cloud Storage" label
   - Verify S3 info message is displayed
   - Verify Google Drive connection widget is NOT shown
   - Verify upload URL copy functionality works

2. Admin with Google Drive configured and connected
   - Verify "Your Upload Page" title is shown
   - Verify Google Drive icon and label
   - Verify Google Drive connection status is shown
   - Verify disconnect button is present
   - Verify upload URL copy functionality works

3. Admin with Google Drive configured but not connected
   - Verify "Your Upload Page" title is shown
   - Verify connect button is present
   - Verify appropriate messaging about connection requirement

4. Compare admin and employee dashboards side-by-side
   - Verify both show same upload page widget structure
   - Verify both show same provider information
   - Verify both handle S3 the same way

## Implementation Notes

### Reuse Existing Code

The `upload-page-section` component already exists and works correctly. No modifications are needed to this component. The implementation focuses on:

1. Updating the admin dashboard view to use the correct component
2. Updating the admin dashboard controller to provide storage provider data
3. Updating the `google-drive-connection` component to only render for Google Drive

### Backward Compatibility

All existing Google Drive functionality is preserved. The changes only affect:
- Which components are displayed
- The order and structure of the dashboard widgets
- The title and labeling of the upload page section

No breaking changes to:
- Google Drive OAuth flow
- Upload URL generation
- File upload functionality
- Database schema

### Configuration

No new configuration is required. The implementation uses existing configuration:
- `config('cloud-storage.default')` - Current storage provider
- `config('cloud-storage.providers.*')` - Provider configurations
- `config('filesystems.disks.s3.*')` - S3 configuration

## Migration Path

### Phase 1: Update Controller
- Add `getStorageProviderInfo()` method to Admin/DashboardController
- Add `isProviderConfigured()` method to Admin/DashboardController
- Update `index()` method to pass storage provider data to view

### Phase 2: Update View
- Replace single `google-drive-connection` component with two components:
  - `upload-page-section` (always shown)
  - `google-drive-connection` (only for Google Drive)
- Ensure proper ordering and spacing

### Phase 3: Update google-drive-connection Component
- Add early return when provider is not Google Drive
- Remove upload URL display section
- Update title and messaging to focus on connection management

### Phase 4: Testing
- Run unit tests for controller methods
- Run feature tests for dashboard views
- Perform manual testing with both S3 and Google Drive
- Verify consistency between admin and employee dashboards

## Rollback Plan

If issues are discovered after deployment:

1. **Quick Rollback:** Revert admin dashboard view to use only `google-drive-connection` component
2. **Controller Rollback:** Remove storage provider data retrieval if causing issues
3. **Component Rollback:** Revert changes to `google-drive-connection` component

The changes are isolated to the admin dashboard and don't affect:
- Employee dashboard (already working correctly)
- File upload functionality
- Storage provider integrations
- Database operations
