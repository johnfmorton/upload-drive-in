# Requirements Document

## Introduction

The Amazon S3 storage provider currently uploads files directly to the root of the configured bucket or uses client email as a key prefix. Users need the ability to define a custom folder structure (key prefix) within their S3 bucket to organize uploaded files according to their business needs. This feature will allow administrators to configure a base folder path that will be prepended to all file uploads, supporting both environment variable and database configuration methods.

## Glossary

- **System**: The Amazon S3 cloud storage configuration and upload system
- **S3 Key Prefix**: A string prepended to S3 object keys to simulate folder structure in flat storage
- **Base Folder Path**: The configurable folder structure within an S3 bucket where files will be uploaded
- **Environment Configuration**: Settings stored in the .env file
- **Database Configuration**: Settings stored in the cloud_storage_settings database table
- **S3 Object Key**: The full path identifier for an object in S3 (e.g., "uploads/client@example.com/file.pdf")

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to configure a base folder path for S3 uploads via environment variables, so that I can organize files within my bucket without modifying code

#### Acceptance Criteria

1. THE System SHALL support an AWS_FOLDER_PATH environment variable for configuring the base folder path
2. WHEN AWS_FOLDER_PATH is set in environment variables, THE System SHALL use this value as the base path for all S3 uploads
3. WHEN AWS_FOLDER_PATH is not set, THE System SHALL default to empty string (bucket root)
4. THE System SHALL trim leading and trailing slashes from the configured folder path
5. THE System SHALL validate that the folder path contains only valid S3 key characters

### Requirement 2

**User Story:** As an administrator, I want to configure a base folder path for S3 uploads via the admin dashboard, so that I can manage folder structure without accessing server configuration files

#### Acceptance Criteria

1. THE System SHALL provide a folder path input field in the S3 configuration form
2. THE System SHALL store the folder path value in the cloud_storage_settings table
3. WHEN the folder path is saved, THE System SHALL validate the path format
4. THE System SHALL display the current folder path value in the configuration form
5. THE System SHALL allow updating the folder path value through the form
6. THE System SHALL allow clearing the folder path to use bucket root

### Requirement 3

**User Story:** As an administrator, I want environment variable configuration to take precedence over database configuration, so that I can enforce folder structure at the infrastructure level

#### Acceptance Criteria

1. WHEN AWS_FOLDER_PATH environment variable exists, THE System SHALL use the environment value
2. WHEN AWS_FOLDER_PATH environment variable exists, THE System SHALL display the folder path field as read-only in the admin form
3. WHEN AWS_FOLDER_PATH environment variable exists, THE System SHALL show helper text indicating the value is from environment variables
4. WHEN AWS_FOLDER_PATH environment variable does not exist, THE System SHALL use the database-stored value
5. WHEN AWS_FOLDER_PATH environment variable does not exist, THE System SHALL allow editing the folder path in the admin form

### Requirement 4

**User Story:** As an administrator, I want uploaded files to be organized under the configured folder path, so that my S3 bucket maintains a clean structure

#### Acceptance Criteria

1. WHEN a file is uploaded, THE System SHALL prepend the configured folder path to the S3 object key
2. THE System SHALL ensure exactly one forward slash separates the folder path from the client email prefix
3. WHEN no folder path is configured, THE System SHALL upload files using only the client email prefix
4. THE System SHALL generate S3 keys in the format: "{folder_path}/{client_email}/{filename}"
5. WHEN folder path is empty, THE System SHALL generate S3 keys in the format: "{client_email}/{filename}"

### Requirement 5

**User Story:** As an administrator, I want to validate the folder path format before saving, so that I don't configure invalid paths that cause upload failures

#### Acceptance Criteria

1. THE System SHALL validate that folder path contains only alphanumeric characters, hyphens, underscores, forward slashes, and periods
2. THE System SHALL reject folder paths containing consecutive forward slashes
3. THE System SHALL reject folder paths starting with a forward slash
4. THE System SHALL reject folder paths ending with a forward slash
5. THE System SHALL display clear error messages for invalid folder path formats
6. THE System SHALL allow empty folder path values

### Requirement 6

**User Story:** As an administrator, I want to see the effective folder path in the configuration UI, so that I understand where files will be uploaded

#### Acceptance Criteria

1. THE System SHALL display the effective folder path being used (environment or database)
2. WHEN folder path is configured, THE System SHALL show an example S3 key format
3. THE example S3 key SHALL use a sample client email and filename
4. THE System SHALL update the example key dynamically as the folder path is edited
5. THE System SHALL clearly indicate whether the folder path is from environment or database

### Requirement 7

**User Story:** As an administrator, I want the folder path configuration to be included in connection tests, so that I can verify the complete upload path works correctly

#### Acceptance Criteria

1. WHEN testing S3 connection, THE System SHALL use the configured folder path
2. THE test connection SHALL verify write permissions to the folder path location
3. WHEN the test succeeds, THE System SHALL display the full S3 key that was tested
4. WHEN the test fails due to folder path issues, THE System SHALL provide specific error messages
5. THE System SHALL clean up test files created during connection testing

### Requirement 8

**User Story:** As a developer, I want the folder path to be properly integrated into the S3Provider service, so that all upload operations use the configured path consistently

#### Acceptance Criteria

1. THE S3Provider class SHALL retrieve folder path from configuration during initialization
2. THE generateS3Key method SHALL incorporate the folder path into generated keys
3. THE System SHALL handle folder path consistently across all file operations (upload, download, delete)
4. THE System SHALL log the effective folder path during provider initialization
5. THE System SHALL include folder path in error messages when operations fail

