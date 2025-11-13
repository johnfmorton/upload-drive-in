# Implementation Plan

- [ ] 1. Enhance Employee Dashboard Controller with Provider Detection
  - Add `getStorageProviderContext()` method to detect active storage provider from config
  - Add `getProviderDisplayName()` helper method for human-readable provider names
  - Add `getDefaultProviderContext()` method for error fallback
  - Update `index()` method to pass storage provider context to view
  - Add error handling for missing or invalid provider configuration
  - _Requirements: 1.4, 5.1, 5.2, 10.1, 10.3_

- [ ] 2. Create New Upload Page Section Component
  - Create `resources/views/components/dashboard/upload-page-section.blade.php`
  - Implement provider-agnostic upload URL display with copy functionality
  - Add system-level storage information message for API key providers
  - Add generic cloud icon for non-OAuth providers
  - Include error state handling for missing upload URL
  - _Requirements: 1.1, 1.2, 3.1, 3.2, 4.1, 4.4, 7.1, 7.2, 7.3_

- [ ] 3. Update Google Drive Connection Component
  - Modify `resources/views/components/dashboard/google-drive-connection.blade.php`
  - Add `storageProvider` prop to component
  - Add conditional rendering to only show for Google Drive provider
  - Return early if provider is not Google Drive
  - Maintain existing Google Drive functionality unchanged
  - _Requirements: 1.1, 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 4. Update Cloud Storage Status Widget Component
  - Modify `resources/views/components/dashboard/cloud-storage-status-widget.blade.php`
  - Add `storageProvider` prop to component
  - Add conditional rendering to hide for non-admin users with system-level storage
  - Return early if employee user and provider doesn't require user auth
  - Maintain existing widget functionality for OAuth providers
  - _Requirements: 2.1, 2.2, 2.4, 5.4_

- [ ] 5. Update Employee Dashboard View
  - Modify `resources/views/employee/dashboard.blade.php`
  - Add conditional rendering based on `$storageProvider['requires_user_auth']`
  - Show Google Drive connection component for OAuth providers
  - Show new upload page section component for system-level providers
  - Add simplified storage info section for system-level providers
  - Conditionally render cloud storage status widget based on auth type
  - Update layout to maintain consistent spacing regardless of provider
  - _Requirements: 1.1, 1.5, 2.1, 2.2, 2.3, 4.2, 4.3, 5.3, 9.1, 9.2, 9.3, 9.4, 9.5_

- [ ] 6. Add Translation Keys
  - Add English translations to `resources/lang/en/messages.php`
  - Add French translations to `resources/lang/fr/messages.php`
  - Include upload page section messages
  - Include cloud storage info messages
  - Include error messages for configuration issues
  - _Requirements: 3.1, 3.4, 7.1, 7.2, 7.3, 10.2, 10.4_

- [ ] 7. Write Unit Tests for Dashboard Controller
  - Create `tests/Unit/Controllers/Employee/DashboardControllerTest.php`
  - Test `getStorageProviderContext()` returns correct config for Google Drive
  - Test `getStorageProviderContext()` returns correct config for Amazon S3
  - Test `getStorageProviderContext()` handles missing configuration
  - Test `getProviderDisplayName()` returns correct display names
  - Test `index()` passes storage provider context to view
  - _Requirements: All requirements validation_

- [ ] 8. Write Feature Tests for Dashboard Rendering
  - Create `tests/Feature/Employee/DashboardTest.php`
  - Test dashboard shows Google Drive connection when Google Drive is configured
  - Test dashboard shows upload page section when S3 is configured
  - Test dashboard hides cloud storage widget for S3
  - Test dashboard shows cloud storage widget for Google Drive
  - Test dashboard handles missing provider configuration gracefully
  - _Requirements: 1.1, 1.2, 2.1, 2.2, 10.1, 10.2_

- [ ] 9. Write Component Tests for Upload Page Section
  - Create `tests/Feature/Components/UploadPageSectionTest.php`
  - Test component displays correctly for S3 provider
  - Test component shows system-level storage message
  - Test component displays upload URL with copy button
  - Test component handles missing upload URL
  - _Requirements: 1.2, 3.1, 3.2, 7.1, 7.2_

- [ ] 10. Perform Manual Testing
  - Test with Google Drive configuration (verify connection box, upload URL placement, status widget)
  - Test with Amazon S3 configuration (verify standalone upload section, no connection box, no status widget)
  - Test provider switching (change config and verify dashboard updates)
  - Test error states (missing config, invalid config)
  - Verify responsive layout on mobile and desktop
  - Test in both English and French languages
  - _Requirements: All requirements validation_
