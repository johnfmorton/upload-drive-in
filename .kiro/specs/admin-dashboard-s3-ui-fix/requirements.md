# Requirements Document

## Introduction

The admin dashboard currently displays "Google Drive Connection" as the widget title even when Amazon S3 or other cloud storage providers are configured. This creates confusion and inconsistency with the employee dashboard, which correctly displays "Your Upload Page" for all storage providers. This feature will fix the admin dashboard to match the employee dashboard behavior.

## Glossary

- **Admin Dashboard**: The dashboard view accessible to administrator users at `/admin/dashboard`
- **Employee Dashboard**: The dashboard view accessible to employee users at `/employee/dashboard`
- **Upload Page Widget**: The dashboard component that displays the user's upload URL and storage provider information
- **Storage Provider**: The cloud storage service configured for the application (Google Drive, Amazon S3, etc.)
- **System-Level Storage**: Storage providers that don't require individual user authentication (e.g., Amazon S3)
- **OAuth Storage**: Storage providers that require individual user authentication (e.g., Google Drive)

## Requirements

### Requirement 1

**User Story:** As an admin user, I want to see "Your Upload Page" as the widget title when using any storage provider, so that the interface is consistent and not misleading

#### Acceptance Criteria

1. WHEN the admin views the dashboard with Amazon S3 configured, THE Admin Dashboard SHALL display "Your Upload Page" as the widget title
2. WHEN the admin views the dashboard with any system-level storage provider configured, THE Admin Dashboard SHALL display "Your Upload Page" as the widget title
3. WHEN the admin views the dashboard with Google Drive configured, THE Admin Dashboard SHALL display "Your Upload Page" as the widget title
4. THE Admin Dashboard SHALL display the same upload page widget structure as the Employee Dashboard for consistency

### Requirement 2

**User Story:** As an admin user, I want to see appropriate storage provider information in the upload page widget, so that I understand which storage service is being used

#### Acceptance Criteria

1. WHEN the admin views the dashboard with Amazon S3 configured, THE Admin Dashboard SHALL display a cloud icon with "Cloud Storage" label
2. WHEN the admin views the dashboard with Google Drive configured, THE Admin Dashboard SHALL display the Google Drive icon with "Google Drive" label
3. WHEN the admin views the dashboard with any OAuth provider configured, THE Admin Dashboard SHALL display the appropriate provider icon and name
4. THE Admin Dashboard SHALL display storage provider information in the same format as the Employee Dashboard

### Requirement 3

**User Story:** As an admin user, I want to see appropriate informational messages based on the storage provider type, so that I understand how files are being stored

#### Acceptance Criteria

1. WHEN the admin views the dashboard with Amazon S3 configured, THE Admin Dashboard SHALL display an info message stating "Files are stored in your organization's Amazon S3"
2. WHEN the admin views the dashboard with any system-level storage configured, THE Admin Dashboard SHALL display an info message with the provider name
3. WHEN the admin views the dashboard with Google Drive configured, THE Admin Dashboard SHALL display Google Drive connection status messages
4. THE Admin Dashboard SHALL include a note to "Contact your administrator for storage-related questions" for system-level storage providers

### Requirement 4

**User Story:** As an admin user, I want the upload URL display and copy functionality to work consistently across all storage providers, so that I can easily share my upload link

#### Acceptance Criteria

1. THE Admin Dashboard SHALL display the upload URL in a code block with copy button for all storage providers
2. WHEN the admin clicks the copy button, THE Admin Dashboard SHALL copy the URL to clipboard and show "Copied" feedback
3. THE Admin Dashboard SHALL display "Share this URL with your clients to receive file uploads" helper text for all providers
4. THE Admin Dashboard SHALL maintain keyboard accessibility for the copy button across all providers

### Requirement 5

**User Story:** As an admin user, I want to see Google Drive-specific connection management options only when Google Drive is configured, so that I don't see irrelevant connection widgets for other storage providers

#### Acceptance Criteria

1. WHEN Amazon S3 is configured as the storage provider, THE Admin Dashboard SHALL NOT display the Google Drive connection widget
2. WHEN any system-level storage provider is configured, THE Admin Dashboard SHALL NOT display the Google Drive connection widget
3. WHEN Google Drive is configured AND the admin is not connected, THE Admin Dashboard SHALL display a "Connect Google Drive" button
4. WHEN Google Drive is configured AND the admin is connected, THE Admin Dashboard SHALL display a "Disconnect" button
5. WHEN Google Drive is configured AND the admin is connected, THE Admin Dashboard SHALL display "Google Drive is connected" status message
6. WHEN Google Drive is not configured, THE Admin Dashboard SHALL display appropriate configuration prompts for admins
7. THE Admin Dashboard SHALL maintain all existing Google Drive connection functionality when Google Drive is the configured provider
