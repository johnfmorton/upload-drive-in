# Implementation Plan

- [x] 1. Update Admin Dashboard Controller to provide storage provider data
  - Add `getStorageProviderInfo()` method to retrieve current storage provider configuration
  - Add `isProviderConfigured()` method to check if a provider is properly configured
  - Update `index()` method to pass `$storageProvider` data to the view
  - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2, 2.3_

- [x] 2. Update Admin Dashboard view to use provider-agnostic components
  - Replace single `google-drive-connection` component with `upload-page-section` component
  - Add `google-drive-connection` component below upload-page-section, passing storage provider data
  - Ensure proper component ordering and spacing matches employee dashboard
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 2.4_

- [x] 3. Update google-drive-connection component to only render for Google Drive
  - Add early return at component start when `storageProvider` is provided and is not Google Drive
  - Remove upload URL display section (now handled by upload-page-section component)
  - Update component to focus solely on Google Drive connection management
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7_

- [x] 4. Add unit tests for Admin Dashboard Controller
- [x] 4.1 Write test for `getStorageProviderInfo()` with Google Drive configuration
  - Test returns correct provider data structure
  - Test includes Google Drive display name and requires_user_auth flag
  - _Requirements: 1.3, 2.2_

- [x] 4.2 Write test for `getStorageProviderInfo()` with Amazon S3 configuration
  - Test returns correct provider data structure
  - Test includes Amazon S3 display name and system-level flag
  - _Requirements: 1.1, 2.1_

- [x] 4.3 Write test for `isProviderConfigured()` method
  - Test correctly detects Google Drive configuration status
  - Test correctly detects Amazon S3 configuration status
  - _Requirements: 1.1, 1.3_

- [x] 4.4 Write test for dashboard index method
  - Test passes storage provider data to view
  - Test includes all required view variables
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 5. Add feature tests for Admin Dashboard with different providers
- [x] 5.1 Write test for admin dashboard with Amazon S3 configured
  - Test displays "Your Upload Page" title (not "Google Drive Connection")
  - Test displays cloud icon with "Cloud Storage" label
  - Test displays S3 info message about organizational storage
  - Test does NOT display Google Drive connection widget
  - Test displays upload URL with copy button
  - _Requirements: 1.1, 2.1, 3.1, 3.2, 4.1, 4.2, 4.3, 5.1, 5.2_

- [x] 5.2 Write test for admin dashboard with Google Drive configured and connected
  - Test displays "Your Upload Page" title
  - Test displays Google Drive icon and label
  - Test displays Google Drive connection status message
  - Test displays disconnect button
  - Test displays upload URL with copy button
  - _Requirements: 1.3, 2.2, 4.1, 4.2, 4.3, 5.3, 5.4, 5.5_

- [x] 5.3 Write test for admin dashboard with Google Drive configured but not connected
  - Test displays "Your Upload Page" title
  - Test displays connect button
  - Test displays appropriate connection requirement messaging
  - _Requirements: 1.3, 2.2, 5.3, 5.6_

- [x] 5.4 Write test for dashboard consistency between admin and employee
  - Test admin dashboard structure matches employee dashboard for S3
  - Test admin dashboard structure matches employee dashboard for Google Drive
  - Test both use same upload-page-section component
  - _Requirements: 1.4, 2.4_

- [x] 6. Add component tests for google-drive-connection behavior
- [x] 6.1 Write test for component with Amazon S3 provider
  - Test component does not render when provider is Amazon S3
  - Test component returns early without displaying any content
  - _Requirements: 5.1, 5.2_

- [x] 6.2 Write test for component with Google Drive provider
  - Test component renders when provider is Google Drive
  - Test component displays connection management UI
  - Test component shows appropriate status messages
  - _Requirements: 5.3, 5.4, 5.5, 5.7_
