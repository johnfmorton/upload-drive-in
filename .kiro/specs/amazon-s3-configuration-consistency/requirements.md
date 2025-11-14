# Requirements Document

## Introduction

The Cloud Storage Configuration page currently displays Google Drive credentials with proper environment variable detection, showing read-only fields with masked secrets when credentials are configured via .env file. The Amazon S3 configuration section does not follow these same conventions, creating an inconsistent user experience. This feature will align the Amazon S3 configuration UI with the established Google Drive patterns.

## Glossary

- **System**: The Cloud Storage Configuration page in the admin dashboard
- **Environment Configuration**: Credentials and settings stored in the .env file
- **Database Configuration**: Credentials and settings stored in the cloud_storage_settings database table
- **Configuration Source**: Either environment variables (.env) or database storage
- **Masked Display**: Showing credentials as dots (••••••) to hide sensitive information
- **Read-Only Field**: An input field that displays information but cannot be edited

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to see a clear notification when S3 credentials are configured via environment variables, so that I understand why I cannot edit them in the control panel

#### Acceptance Criteria

1. WHEN the System detects AWS credentials in environment variables, THE System SHALL display an information banner above the S3 configuration form
2. THE information banner SHALL list which specific S3 settings are configured via environment variables
3. THE information banner SHALL use the same visual styling as the Google Drive environment configuration banner
4. THE information banner SHALL include an icon indicating informational content
5. THE information banner SHALL explain that environment-configured values cannot be edited in the control panel

### Requirement 2

**User Story:** As an administrator, I want S3 credential fields to be read-only when configured via environment variables, so that I cannot accidentally attempt to override environment settings

#### Acceptance Criteria

1. WHEN AWS_ACCESS_KEY_ID exists in environment variables, THE System SHALL display the access key field as read-only
2. WHEN AWS_SECRET_ACCESS_KEY exists in environment variables, THE System SHALL display the secret key field as read-only with masked characters
3. WHEN AWS_DEFAULT_REGION exists in environment variables, THE System SHALL display the region field as read-only
4. WHEN AWS_BUCKET exists in environment variables, THE System SHALL display the bucket field as read-only
5. WHEN AWS_ENDPOINT exists in environment variables, THE System SHALL display the endpoint field as read-only
6. THE read-only fields SHALL have a gray background to visually indicate they are not editable
7. THE read-only fields SHALL display the actual environment variable values

### Requirement 3

**User Story:** As an administrator, I want the AWS Secret Access Key to display as dots when configured via environment variables, so that sensitive credentials are not exposed in the UI

#### Acceptance Criteria

1. WHEN AWS_SECRET_ACCESS_KEY exists in environment variables, THE System SHALL display the secret key value as a series of dots (••••••••••••••••••••••••••••••••••••••••)
2. THE masked secret key display SHALL use exactly 40 dot characters to match the actual secret key length
3. THE secret key field SHALL remain a password input type when read-only
4. THE secret key field SHALL include helper text stating "This value is configured via environment variables"

### Requirement 4

**User Story:** As an administrator, I want the save button to be hidden when all S3 credentials are from environment variables, so that I understand there are no editable fields to save

#### Acceptance Criteria

1. WHEN all S3 credential fields are configured via environment variables, THE System SHALL hide the save configuration button
2. WHEN at least one S3 credential field is not configured via environment variables, THE System SHALL display the save configuration button
3. THE save button visibility logic SHALL match the pattern used in the Google Drive credentials form

### Requirement 5

**User Story:** As an administrator, I want to see helper text under read-only fields, so that I understand why the field cannot be edited

#### Acceptance Criteria

1. WHEN a field is read-only due to environment configuration, THE System SHALL display helper text below the field
2. THE helper text SHALL state "This value is configured via environment variables"
3. THE helper text SHALL use gray text color to indicate secondary information
4. THE helper text SHALL use the same styling as the Google Drive credential helper text

### Requirement 6

**User Story:** As an administrator, I want the test connection and disconnect buttons to work regardless of configuration source, so that I can verify and manage my S3 connection

#### Acceptance Criteria

1. WHEN S3 credentials are configured via environment variables, THE System SHALL still allow testing the connection
2. WHEN S3 credentials are configured via environment variables, THE System SHALL still allow disconnecting
3. THE test connection button SHALL use environment variable values when present
4. THE disconnect button SHALL clear database-stored credentials but SHALL NOT modify environment variables

### Requirement 7

**User Story:** As an administrator, I want to be able to save S3 credentials to the database when no environment variables are configured, so that I can configure S3 without modifying the .env file

#### Acceptance Criteria

1. WHEN no AWS environment variables are configured, THE System SHALL allow saving S3 credentials via the configuration form
2. WHEN the save button is clicked with valid credentials, THE System SHALL store the credentials in the cloud_storage_settings table
3. WHEN credentials are successfully saved, THE System SHALL display a success message
4. WHEN credentials fail to save, THE System SHALL display an error message with details
5. THE form validation SHALL work correctly for database-stored credentials
6. THE System SHALL allow updating previously saved database credentials
