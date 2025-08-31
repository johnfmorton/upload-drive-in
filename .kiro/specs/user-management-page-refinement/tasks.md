# Implementation Plan

- [x] 1. Create new SecuritySettingsController
  - Create `app/Http/Controllers/Admin/SecuritySettingsController.php` with methods from UserManagementController
  - Remove the `createClient` method and related functionality
  - Rename `settings()` method to `index()` for consistency
  - Update method documentation and class name
  - _Requirements: 1.1, 2.1_

- [x] 2. Update route definitions
  - Modify `routes/admin.php` to replace user-management routes with security-settings routes
  - Update route names from `user-management.*` to `security.*`
  - Remove the client creation route entirely
  - _Requirements: 2.1, 2.2, 3.1_

- [x] 3. Create new security settings view
  - Create `resources/views/admin/security/settings.blade.php` based on existing user-management view
  - Remove the "Create Client User" section completely
  - Update page title to "Security and Access Settings"
  - Update form action URLs to use new route names
  - _Requirements: 1.1, 3.1, 4.1, 4.2_

- [x] 4. Update navigation and menu references
  - Find and update any navigation menu items that reference user-management
  - Update breadcrumbs or navigation components to show "Security Settings"
  - Update any dashboard links or buttons that point to the old route
  - _Requirements: 2.1, 2.2_

- [x] 5. Update language files and translations
  - Add new translation keys for "Security and Access Settings"
  - Update any existing translation keys that reference user management
  - Ensure all form labels and messages use appropriate security-focused language
  - _Requirements: 2.1, 4.2_

- [x] 6. Create comprehensive tests for SecuritySettingsController
  - Write unit tests for the new controller methods
  - Test form submissions to new routes
  - Test validation for registration and domain rule settings
  - Verify that client creation functionality is completely removed
  - _Requirements: 1.1, 3.1_

- [x] 7. Update existing tests that reference user-management routes
  - Find and update any feature tests that use old route names
  - Update any test assertions that check for user-management page content
  - Remove any tests related to client creation from the security settings context
  - _Requirements: 3.1_

- [x] 8. Clean up old files and references
  - Remove `app/Http/Controllers/Admin/UserManagementController.php`
  - Remove `resources/views/admin/user-management/settings.blade.php`
  - Remove the `user-management` directory if it's now empty
  - Search codebase for any remaining references to old routes or controller
  - _Requirements: 3.1_
