# Requirements Document

## Introduction

The current User Management Settings page at `/admin/user-management` contains mixed functionality that combines security/access control settings with user creation features. This creates confusion about the page's purpose and makes navigation less intuitive. This feature will refine the page by removing the user creation functionality and renaming it to better reflect its security-focused purpose.

## Requirements

### Requirement 1

**User Story:** As an admin, I want a dedicated page for security and access control settings, so that I can easily manage registration and domain access policies without being distracted by user creation functionality.

#### Acceptance Criteria

1. WHEN I navigate to the security settings page THEN I SHALL see only security and access control related settings
2. WHEN I view the page title and navigation THEN the page SHALL be named "Security and Access Settings" or similar
3. WHEN I look for user creation functionality THEN it SHALL NOT be present on this page
4. WHEN I need to manage public registration settings THEN I SHALL be able to toggle and save the setting
5. WHEN I need to manage domain access control THEN I SHALL be able to configure blacklist/whitelist modes and domain rules

### Requirement 2

**User Story:** As an admin, I want the page URL and navigation to reflect the security-focused purpose, so that I can easily find and access these settings in the future.

#### Acceptance Criteria

1. WHEN I access the page THEN the URL SHALL reflect the security/access control purpose
2. WHEN I view the navigation breadcrumbs or menu THEN the page name SHALL clearly indicate its security focus
3. WHEN I bookmark or share the page THEN the URL SHALL be intuitive and descriptive
4. WHEN I navigate to the old URL THEN I SHALL be redirected to the new URL to maintain backward compatibility

### Requirement 3

**User Story:** As an admin, I want the "Create Client User" functionality to be moved to a more appropriate location, so that user creation and security settings are properly separated.

#### Acceptance Criteria

1. WHEN I look for user creation functionality THEN it SHALL be removed from the security settings page
2. WHEN I need to create client users THEN the functionality SHALL be available in a more appropriate location (such as a dedicated user management page or the main dashboard)
3. WHEN I access user creation features THEN they SHALL maintain the same functionality as before, just in a different location
4. WHEN I complete the page refinement THEN no user creation functionality SHALL remain on the security settings page

### Requirement 4

**User Story:** As an admin, I want the page layout and design to be clean and focused, so that I can efficiently manage security settings without visual clutter.

#### Acceptance Criteria

1. WHEN I view the page THEN the layout SHALL be clean and focused on security settings only
2. WHEN I interact with the settings THEN the form sections SHALL be well-organized and clearly labeled
3. WHEN I save settings THEN the feedback SHALL be clear and immediate
4. WHEN I view the page on different screen sizes THEN it SHALL remain responsive and usable
5. WHEN I compare to the original page THEN the visual hierarchy SHALL be improved by removing unrelated content