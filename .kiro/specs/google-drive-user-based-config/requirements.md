# Requirements Document

## Introduction

This feature removes the global `GOOGLE_DRIVE_ROOT_FOLDER_ID` environment variable dependency and ensures that Google Drive root folder configuration is managed entirely on a per-user basis through the database. Each admin and employee user should be able to configure their own Google Drive root folder through their respective control panels, with sensible defaults when no folder is specified.

## Requirements

### Requirement 1

**User Story:** As an admin user, I want to configure my Google Drive root folder through the admin control panel without relying on environment variables, so that my configuration is stored in the database and can be managed independently of other users.

#### Acceptance Criteria

1. WHEN an admin accesses the Google Drive configuration section THEN they SHALL see a root folder selection interface that works independently of environment variables
2. WHEN an admin selects a root folder THEN the system SHALL store this preference in the database associated with their user account
3. WHEN an admin has not selected a root folder THEN the system SHALL default to using the Google Drive root ('root') as the upload destination
4. WHEN an admin changes their root folder selection THEN the system SHALL update their database record without affecting other users

### Requirement 2

**User Story:** As an employee user, I want to configure my Google Drive root folder through the employee control panel without relying on environment variables, so that my uploads go to my preferred location in my connected Google Drive.

#### Acceptance Criteria

1. WHEN an employee accesses their cloud storage settings THEN they SHALL see a root folder selection interface that works independently of environment variables
2. WHEN an employee selects a root folder THEN the system SHALL store this preference in the database associated with their user account
3. WHEN an employee has not selected a root folder THEN the system SHALL default to using the Google Drive root ('root') as the upload destination
4. WHEN an employee changes their root folder selection THEN the system SHALL update their database record without affecting other users

### Requirement 3

**User Story:** As a system administrator, I want the application to not depend on the `GOOGLE_DRIVE_ROOT_FOLDER_ID` environment variable, so that the configuration is cleaner and each user can manage their own settings independently.

#### Acceptance Criteria

1. WHEN the system determines a root folder for uploads THEN it SHALL only consider the user's database setting and default to 'root' if none is set
2. WHEN the system processes Google Drive operations THEN it SHALL NOT reference the `GOOGLE_DRIVE_ROOT_FOLDER_ID` environment variable
3. WHEN the application starts THEN it SHALL function correctly even if `GOOGLE_DRIVE_ROOT_FOLDER_ID` is not defined in the environment
4. WHEN existing users have no root folder configured THEN the system SHALL gracefully default to Google Drive root without errors

### Requirement 4

**User Story:** As a developer, I want the codebase to be simplified by removing environment variable dependencies for root folder configuration, so that the code is easier to maintain and understand.

#### Acceptance Criteria

1. WHEN reviewing the GoogleDriveService class THEN it SHALL not reference CloudStorageSetting for root folder configuration
2. WHEN reviewing controller validation THEN root folder fields SHALL be nullable to allow for default behavior
3. WHEN reviewing the frontend components THEN they SHALL clearly indicate when Google Drive root is being used as default
4. WHEN reviewing configuration files THEN they SHALL not include references to GOOGLE_DRIVE_ROOT_FOLDER_ID

### Requirement 5

**User Story:** As an existing user with current Google Drive configuration, I want my settings to continue working after the update, so that my workflow is not disrupted.

#### Acceptance Criteria

1. WHEN the system is updated THEN existing user root folder preferences SHALL be preserved
2. WHEN a user had no specific root folder configured THEN their uploads SHALL continue to work using Google Drive root
3. WHEN the system processes existing file upload jobs THEN they SHALL complete successfully with the new logic
4. WHEN users access their configuration panels THEN they SHALL see their current settings reflected accurately