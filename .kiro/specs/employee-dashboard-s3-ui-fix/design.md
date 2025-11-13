# Design Document

## Overview

This design document outlines the solution for adapting the Employee dashboard UI based on the configured cloud storage provider. The current implementation assumes Google Drive with OAuth authentication, but when Amazon S3 is configured (which uses system-level API key authentication), the UI displays inappropriate elements like "Google Drive Connection" boxes and irrelevant cloud storage status widgets.

The solution will introduce provider-aware conditional rendering in the Employee dashboard, ensuring that UI components adapt to the authentication model and capabilities of the active storage provider.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Employee Dashboard                        │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Storage Provider Detection Layer                      │ │
│  │  - Query active provider from config                   │ │
│  │  - Determine authentication model                      │ │
│  │  - Pass provider context to view                       │ │
│  └────────────────────────────────────────────────────────┘ │
│                           │                                  │
│                           ▼                                  │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Conditional UI Rendering                              │ │
│  │                                                         │ │
│  │  ┌──────────────────┐    ┌──────────────────┐         │ │
│  │  │ User-Level Auth  │    │ System-Level Auth│         │ │
│  │  │ (Google Drive)   │    │ (Amazon S3)      │         │ │
│  │  │                  │    │                  │         │ │
│  │  │ - Connection Box │    │ - Upload Page    │         │ │
│  │  │ - Upload Page    │    │ - Simple Info    │         │ │
│  │  │ - Status Widget  │    │ - No Status      │         │ │
│  │  └──────────────────┘    └──────────────────┘         │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Component Interaction Flow

```
Employee Dashboard Controller
        │
        ├─> Get Active Provider (config)
        │
        ├─> Determine Auth Model
        │   ├─> OAuth (user-level)
        │   └─> API Key (system-level)
        │
        ├─> Pass Provider Context to View
        │
        └─> View Renders Conditionally
            ├─> Upload Page Section
            │   ├─> Google Drive: Inside connection box
            │   └─> S3: Standalone section
            │
            └─> Cloud Storage Status
                ├─> Google Drive: Show widget
                └─> S3: Hide widget
```

## Components and Interfaces

### 1. Dashboard Controller Enhancement

**File**: `app/Http/Controllers/Employee/DashboardController.php`

**Purpose**: Detect active storage provider and pass context to view

**New Method**:
```php
/**
 * Get the active cloud storage provider configuration
 *
 * @return array Provider context including name, auth type, and capabilities
 */
private function getStorageProviderContext(): array
{
    $defaultProvider = config('cloud-storage.default');
    $providerConfig = config("cloud-storage.providers.{$defaultProvider}");
    
    return [
        'provider' => $defaultProvider,
        'auth_type' => $providerConfig['auth_type'] ?? 'oauth',
        'storage_model' => $providerConfig['storage_model'] ?? 'hierarchical',
        'requires_user_auth' => ($providerConfig['auth_type'] ?? 'oauth') === 'oauth',
        'display_name' => $this->getProviderDisplayName($defaultProvider),
    ];
}

/**
 * Get human-readable provider name
 *
 * @param string $provider Provider identifier
 * @return string Display name
 */
private function getProviderDisplayName(string $provider): string
{
    return match($provider) {
        'google-drive' => 'Google Drive',
        'amazon-s3' => 'Amazon S3',
        'microsoft-teams' => 'Microsoft Teams',
        default => ucwords(str_replace('-', ' ', $provider)),
    };
}
```

**Updated index() Method**:
```php
public function index(Request $request): View
{
    $user = Auth::user();
    
    // Get files uploaded to this employee
    $files = FileUpload::where(function($query) use ($user) {
        $query->where('company_user_id', $user->id)
              ->orWhere('uploaded_by_user_id', $user->id);
    })
    ->orderBy('created_at', 'desc')
    ->paginate(config('file-manager.pagination.items_per_page'));
    
    // Get storage provider context
    $storageProvider = $this->getStorageProviderContext();

    return view('employee.dashboard', compact('user', 'files', 'storageProvider'));
}
```

### 2. New Upload Page Component

**File**: `resources/views/components/dashboard/upload-page-section.blade.php`

**Purpose**: Provider-agnostic upload page URL display

**Props**:
- `user` - The employee user
- `storageProvider` - Storage provider context array

**Structure**:
```blade
@props(['user', 'storageProvider'])

<div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('messages.your_upload_page') }}
        </h2>
        
        @if($storageProvider['requires_user_auth'])
            <!-- Show provider icon for OAuth providers -->
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-5 h-5 mr-2"><!-- Provider icon --></svg>
                <span>{{ $storageProvider['display_name'] }}</span>
            </div>
        @else
            <!-- Show generic cloud icon for system-level providers -->
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-5 h-5 mr-2"><!-- Generic cloud icon --></svg>
                <span>{{ __('messages.cloud_storage') }}</span>
            </div>
        @endif
    </div>
    
    @if($user->upload_url)
        <!-- Upload URL display with copy button -->
        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <!-- URL and copy button implementation -->
        </div>
        
        @if(!$storageProvider['requires_user_auth'])
            <!-- System-level storage info message -->
            <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    {{ __('messages.files_stored_in_organization_storage', ['provider' => $storageProvider['display_name']]) }}
                </p>
                <p class="text-xs text-blue-600 mt-1">
                    {{ __('messages.contact_admin_for_storage_questions') }}
                </p>
            </div>
        @endif
    @else
        <!-- No upload URL available -->
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm text-yellow-800">
                {{ __('messages.upload_page_not_available') }}
            </p>
        </div>
    @endif
</div>
```

### 3. Modified Google Drive Connection Component

**File**: `resources/views/components/dashboard/google-drive-connection.blade.php`

**Changes**: Add conditional rendering based on storage provider

**New Logic**:
```blade
@props(['user', 'isAdmin' => false, 'storageProvider' => null])

@php
    // Only show this component for Google Drive
    if ($storageProvider && $storageProvider['provider'] !== 'google-drive') {
        return;
    }
    
    // Existing Google Drive logic...
@endphp
```

### 4. Modified Cloud Storage Status Widget

**File**: `resources/views/components/dashboard/cloud-storage-status-widget.blade.php`

**Changes**: Add conditional rendering for system-level storage

**New Logic**:
```blade
@props(['user', 'isAdmin' => false, 'storageProvider' => null])

@php
    // Hide widget for system-level storage providers (employees don't manage these)
    if (!$isAdmin && $storageProvider && !$storageProvider['requires_user_auth']) {
        return;
    }
    
    // Existing widget logic...
@endphp
```

### 5. Updated Employee Dashboard View

**File**: `resources/views/employee/dashboard.blade.php`

**Changes**: Use new components with provider context

**Structure**:
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.employee_dashboard_title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if($storageProvider['requires_user_auth'])
                <!-- Google Drive Connection (OAuth providers) -->
                <x-dashboard.google-drive-connection 
                    :user="$user" 
                    :is-admin="false" 
                    :storage-provider="$storageProvider" />
            @else
                <!-- Upload Page Section (System-level providers) -->
                <x-dashboard.upload-page-section 
                    :user="$user" 
                    :storage-provider="$storageProvider" />
            @endif

            <!-- File Management Section -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <!-- Existing file management UI -->
            </div>

            <!-- Dashboard Statistics Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Client Relationships -->
                <div class="lg:col-span-1">
                    <x-dashboard.client-relationships :user="$user" :is-admin="false" />
                </div>
            
                <!-- Cloud Storage Status Widget (only for OAuth providers) -->
                @if($storageProvider['requires_user_auth'])
                    <x-dashboard.cloud-storage-status-widget 
                        :user="$user" 
                        :is-admin="false" 
                        :storage-provider="$storageProvider" />
                @else
                    <!-- Simplified Storage Info for System-Level Providers -->
                    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">
                            {{ __('messages.cloud_storage_info') }}
                        </h2>
                        <div class="flex items-center p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <svg class="w-8 h-8 text-blue-600 mr-3"><!-- Cloud icon --></svg>
                            <div>
                                <p class="text-sm font-medium text-blue-900">
                                    {{ $storageProvider['display_name'] }}
                                </p>
                                <p class="text-sm text-blue-700">
                                    {{ __('messages.managed_by_administrator') }}
                                </p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 mt-3">
                            {{ __('messages.contact_admin_for_storage_configuration') }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
```

## Data Models

### Storage Provider Context Structure

```php
[
    'provider' => 'amazon-s3',           // Provider identifier
    'auth_type' => 'api_key',            // 'oauth' or 'api_key'
    'storage_model' => 'flat',           // 'hierarchical' or 'flat'
    'requires_user_auth' => false,       // Boolean: does user need to authenticate?
    'display_name' => 'Amazon S3',       // Human-readable name
]
```

### Provider Authentication Models

**OAuth (User-Level)**:
- Each user must connect their own account
- Examples: Google Drive, Microsoft Teams
- UI shows: Connection status, connect/disconnect buttons, health status

**API Key (System-Level)**:
- Admin configures once for entire system
- Examples: Amazon S3, Cloudflare R2
- UI shows: Simple info message, no connection controls

## Error Handling

### Missing Provider Configuration

**Scenario**: Storage provider not configured or invalid

**Handling**:
```php
private function getStorageProviderContext(): array
{
    try {
        $defaultProvider = config('cloud-storage.default');
        
        if (!$defaultProvider) {
            Log::warning('No default cloud storage provider configured');
            return $this->getDefaultProviderContext();
        }
        
        $providerConfig = config("cloud-storage.providers.{$defaultProvider}");
        
        if (!$providerConfig) {
            Log::error("Provider configuration not found: {$defaultProvider}");
            return $this->getDefaultProviderContext();
        }
        
        return [
            'provider' => $defaultProvider,
            'auth_type' => $providerConfig['auth_type'] ?? 'oauth',
            'storage_model' => $providerConfig['storage_model'] ?? 'hierarchical',
            'requires_user_auth' => ($providerConfig['auth_type'] ?? 'oauth') === 'oauth',
            'display_name' => $this->getProviderDisplayName($defaultProvider),
        ];
    } catch (\Exception $e) {
        Log::error('Error getting storage provider context', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        return $this->getDefaultProviderContext();
    }
}

private function getDefaultProviderContext(): array
{
    return [
        'provider' => 'unknown',
        'auth_type' => 'oauth',
        'storage_model' => 'hierarchical',
        'requires_user_auth' => true,
        'display_name' => 'Cloud Storage',
        'error' => true,
    ];
}
```

### View Error States

**Component**: All dashboard components should handle missing provider context gracefully

```blade
@if(isset($storageProvider['error']) && $storageProvider['error'])
    <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm text-red-800">
            {{ __('messages.storage_configuration_error') }}
        </p>
        <p class="text-xs text-red-600 mt-1">
            {{ __('messages.contact_admin_to_resolve') }}
        </p>
    </div>
@endif
```

## Testing Strategy

### Unit Tests

**Test File**: `tests/Unit/Controllers/Employee/DashboardControllerTest.php`

**Test Cases**:
1. `test_get_storage_provider_context_returns_google_drive_config()`
2. `test_get_storage_provider_context_returns_s3_config()`
3. `test_get_storage_provider_context_handles_missing_config()`
4. `test_get_provider_display_name_returns_correct_names()`
5. `test_index_passes_storage_provider_to_view()`

### Feature Tests

**Test File**: `tests/Feature/Employee/DashboardTest.php`

**Test Cases**:
1. `test_employee_dashboard_shows_google_drive_connection_when_configured()`
2. `test_employee_dashboard_shows_upload_page_section_when_s3_configured()`
3. `test_employee_dashboard_hides_cloud_storage_widget_for_s3()`
4. `test_employee_dashboard_shows_cloud_storage_widget_for_google_drive()`
5. `test_employee_dashboard_handles_missing_provider_configuration()`

### Component Tests

**Test File**: `tests/Feature/Components/UploadPageSectionTest.php`

**Test Cases**:
1. `test_upload_page_section_displays_for_s3()`
2. `test_upload_page_section_shows_system_level_message()`
3. `test_upload_page_section_displays_upload_url()`
4. `test_upload_page_section_handles_missing_upload_url()`

### Manual Testing Checklist

1. **Google Drive Configuration**:
   - [ ] Dashboard shows Google Drive connection box
   - [ ] Upload page URL is inside connection box
   - [ ] Cloud Storage Status widget is visible
   - [ ] Connection status displays correctly

2. **Amazon S3 Configuration**:
   - [ ] Dashboard shows standalone upload page section
   - [ ] No Google Drive connection box visible
   - [ ] Cloud Storage Status widget is hidden
   - [ ] System-level storage message displays

3. **Provider Switching**:
   - [ ] Change from Google Drive to S3 in config
   - [ ] Dashboard updates on next page load
   - [ ] No cached UI elements from previous provider

4. **Error States**:
   - [ ] Missing provider config shows error message
   - [ ] Invalid provider config shows error message
   - [ ] Error messages direct user to contact admin

## Translation Keys

### New Translation Keys Required

**File**: `resources/lang/en/messages.php`

```php
// Upload Page Section
'your_upload_page' => 'Your Upload Page',
'cloud_storage' => 'Cloud Storage',
'files_stored_in_organization_storage' => 'Files are stored in your organization\'s :provider',
'contact_admin_for_storage_questions' => 'Contact your administrator for storage-related questions',
'upload_page_not_available' => 'Upload page is not available. Please contact your administrator.',

// Cloud Storage Info
'cloud_storage_info' => 'Cloud Storage Information',
'managed_by_administrator' => 'Managed by your administrator',
'contact_admin_for_storage_configuration' => 'For storage configuration or questions, please contact your administrator.',

// Error Messages
'storage_configuration_error' => 'Cloud storage is not properly configured',
'contact_admin_to_resolve' => 'Please contact your administrator to resolve this issue',
```

**File**: `resources/lang/fr/messages.php`

```php
// Upload Page Section
'your_upload_page' => 'Votre page de téléchargement',
'cloud_storage' => 'Stockage cloud',
'files_stored_in_organization_storage' => 'Les fichiers sont stockés dans :provider de votre organisation',
'contact_admin_for_storage_questions' => 'Contactez votre administrateur pour les questions relatives au stockage',
'upload_page_not_available' => 'La page de téléchargement n\'est pas disponible. Veuillez contacter votre administrateur.',

// Cloud Storage Info
'cloud_storage_info' => 'Informations sur le stockage cloud',
'managed_by_administrator' => 'Géré par votre administrateur',
'contact_admin_for_storage_configuration' => 'Pour la configuration du stockage ou des questions, veuillez contacter votre administrateur.',

// Error Messages
'storage_configuration_error' => 'Le stockage cloud n\'est pas correctement configuré',
'contact_admin_to_resolve' => 'Veuillez contacter votre administrateur pour résoudre ce problème',
```

## Performance Considerations

### Caching Provider Context

The storage provider configuration is read from config files, which are already cached in production. No additional caching is needed.

### View Compilation

Blade templates with conditional logic will be compiled once and cached. The conditional checks are minimal and won't impact performance.

### Database Queries

No additional database queries are introduced. The existing queries for files and user data remain unchanged.

## Security Considerations

### Provider Configuration Access

- Provider configuration is read from config files (read-only)
- No user input is used to determine the provider
- Configuration cannot be manipulated by employees

### Upload URL Display

- Upload URLs are already generated securely
- No changes to URL generation logic
- Display logic doesn't expose sensitive information

### Error Messages

- Error messages don't expose system internals
- Generic messages direct users to contact admin
- Detailed errors are logged server-side only

## Deployment Considerations

### Configuration Changes

No environment variable changes required. The system uses existing `CLOUD_STORAGE_DEFAULT` configuration.

### Database Migrations

No database migrations required. This is purely a UI enhancement.

### Backward Compatibility

- Existing Google Drive functionality remains unchanged
- Admin dashboard is not affected
- Client upload pages are not affected
- Only Employee dashboard UI is modified

### Rollback Strategy

If issues arise, the changes can be rolled back by:
1. Reverting the dashboard controller changes
2. Reverting the view file changes
3. Removing the new upload-page-section component

The system will fall back to the previous Google Drive-centric UI.

## Future Enhancements

### Multi-Provider Support

If the system needs to support multiple providers simultaneously (e.g., some employees use Google Drive, others use S3), the design can be extended to:

1. Check user-specific provider preference
2. Display multiple provider sections if needed
3. Allow employees to switch between providers

### Provider-Specific Features

The provider context can be extended to include:
- Available features (folder creation, sharing, etc.)
- Storage limits and quotas
- Provider-specific actions and controls

### Dynamic Provider Discovery

Instead of hardcoding provider checks, implement a provider registry that automatically adapts the UI based on provider capabilities.
