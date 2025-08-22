# Requirements Document

## Introduction

The current setup wizard has become overly complex and is taking too long to complete, risking project cancellation. This feature will replace the entire setup wizard system with a simple instructional screen that guides users through manual configuration steps they need to perform on their server.

## Requirements

### Requirement 1

**User Story:** As a system administrator installing the application, I want to see clear, simple instructions for manual setup so that I can quickly configure the application without going through a complex wizard.

#### Acceptance Criteria

1. WHEN a user accesses the application for the first time THEN the system SHALL display a simple setup instructions page
2. WHEN the setup instructions are displayed THEN the system SHALL show database configuration steps with specific .env variables
3. WHEN the setup instructions are displayed THEN the system SHALL show Google Drive configuration steps with required credentials
4. WHEN the setup instructions are displayed THEN the system SHALL show command line instructions for creating the initial admin user
5. WHEN the user has completed manual setup THEN the system SHALL automatically detect the configuration and proceed to the normal application

### Requirement 2

**User Story:** As a system administrator, I want the setup instructions to be comprehensive and clear so that I don't need to reference external documentation.

#### Acceptance Criteria

1. WHEN the setup instructions are displayed THEN the system SHALL show the exact .env variables that need to be configured
2. WHEN database configuration is shown THEN the system SHALL list all required database variables (DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
3. WHEN Google Drive configuration is shown THEN the system SHALL list the required Google Drive variables (GOOGLE_DRIVE_CLIENT_ID, GOOGLE_DRIVE_CLIENT_SECRET)
4. WHEN command line instructions are shown THEN the system SHALL provide the exact artisan command for creating the initial admin user
5. WHEN instructions are displayed THEN the system SHALL use clear, non-technical language where possible

### Requirement 3

**User Story:** As a developer, I want all existing setup wizard code removed so that the application is simpler and easier to maintain.

#### Acceptance Criteria

1. WHEN the simplification is complete THEN the system SHALL have removed all setup wizard controllers
2. WHEN the simplification is complete THEN the system SHALL have removed all setup wizard views
3. WHEN the simplification is complete THEN the system SHALL have removed all setup wizard routes
4. WHEN the simplification is complete THEN the system SHALL have removed all setup wizard middleware
5. WHEN the simplification is complete THEN the system SHALL have removed all setup wizard services
6. WHEN the simplification is complete THEN the system SHALL have removed all setup wizard database tables and migrations

### Requirement 4

**User Story:** As a user, I want the application to automatically detect when setup is complete so that I don't need to manually indicate completion.

#### Acceptance Criteria

1. WHEN the application starts THEN the system SHALL check if database connection is configured
2. WHEN the application starts THEN the system SHALL check if Google Drive credentials are configured
3. WHEN the application starts THEN the system SHALL check if at least one admin user exists
4. WHEN all setup requirements are met THEN the system SHALL redirect to the normal application login
5. WHEN any setup requirement is missing THEN the system SHALL display the setup instructions page