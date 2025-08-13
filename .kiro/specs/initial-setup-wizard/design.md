# Design Document

## Overview

The initial setup wizard for Upload Drive-in will be implemented as a multi-step web interface that guides administrators through the essential configuration steps when the application is first deployed. The wizard will be built using Laravel's existing authentication and middleware infrastructure, with custom middleware to detect and enforce the setup state.

The design leverages Laravel's existing patterns including Blade templates, form validation, and the service layer architecture already established in the application. The wizard will integrate seamlessly with the existing Google Drive service infrastructure and user management system.

## Architecture

### Setup State Detection

The application will use a multi-layered approach to determine setup requirements, handling both database initialization and application configuration:

**Database Layer Checks:**
- **Database Connectivity**: Test if database connection can be established
- **Migration Status**: Check if database tables exist and migrations are current
- **Data Integrity**: Verify essential tables are present and accessible

**Application Layer Checks:**
- **Admin User Existence**: Check if any admin users exist in the database
- **Cloud Storage Configuration**: Verify required cloud storage credentials are present
- **Setup Completion Flag**: Check dedicated setup completion marker

**Fallback Mechanisms:**
- **File-based State**: If database is unavailable, use file-based setup state tracking
- **Environment Detection**: Detect fresh installation vs. existing deployment
- **Recovery Mode**: Handle partial setup scenarios and allow resumption

### Middleware Integration

A new `RequireSetupMiddleware` will be created to:
- Check if the application requires initial setup
- Redirect all requests to the setup wizard when setup is incomplete
- Allow access to setup routes and essential assets
- Bypass setup requirements once configuration is complete

### Route Structure

Setup routes will be grouped under a dedicated prefix with appropriate middleware:
```
/setup/welcome - Initial welcome screen and system checks
/setup/database - Database configuration and migration
/setup/admin - Admin user creation
/setup/storage - Cloud storage configuration
/setup/complete - Final completion screen and first login
```

**Route Protection:**
- Setup routes accessible only when setup is required
- Database routes handle both SQLite and MySQL scenarios
- Graceful fallback for database connectivity issues

## Components and Interfaces

### Controllers

#### SetupController
Primary controller handling the setup wizard flow:

**Methods:**
- `welcome()` - Display welcome screen and perform initial system checks
- `showDatabaseForm()` - Display database configuration options
- `configureDatabase(DatabaseConfigRequest $request)` - Handle database setup and migrations
- `showAdminForm()` - Display admin user creation form
- `createAdmin(AdminUserRequest $request)` - Process admin user creation
- `showStorageForm()` - Display cloud storage configuration form
- `configureStorage(StorageConfigRequest $request)` - Process storage configuration
- `complete()` - Mark setup as complete and redirect to dashboard

#### SetupValidationController
Helper controller for AJAX validation and system checks:

**Methods:**
- `checkDatabase()` - Verify database connectivity
- `testStorageConnection(Request $request)` - Test cloud storage credentials
- `validateAdminEmail(Request $request)` - AJAX email validation

### Services

#### SetupService
Core business logic for setup operations:

**Methods:**
- `isSetupRequired(): bool` - Check if setup is needed
- `getSetupStep(): string` - Determine current setup step required
- `markSetupComplete(): void` - Mark setup as finished
- `createInitialAdmin(array $data): User` - Create the first admin user
- `configureCloudStorage(string $provider, array $config): void` - Store cloud configuration
- `validateDatabaseConnection(): bool` - Test database connectivity
- `getSystemRequirements(): array` - Check system prerequisites

#### DatabaseSetupService
Specialized service for database initialization:

**Methods:**
- `detectDatabaseType(): string` - Determine if using MySQL or SQLite
- `validateDatabaseConfig(): array` - Check database configuration validity
- `initializeSQLiteDatabase(): bool` - Create and set up SQLite database file
- `testMySQLConnection(array $config): bool` - Test MySQL connection parameters
- `runMigrations(): bool` - Execute database migrations
- `seedInitialData(): void` - Insert any required initial data
- `getDatabaseStatus(): array` - Get comprehensive database status information

#### CloudStorageSetupService
Specialized service for cloud storage configuration:

**Methods:**
- `testGoogleDriveConnection(string $clientId, string $clientSecret): bool`
- `storeGoogleDriveConfig(array $config): void`
- `generateRedirectUri(): string`
- `validateRequiredFields(string $provider, array $config): array`

### Models

#### SetupConfiguration
Model to track setup state and store configuration:

**Attributes:**
- `key: string` - Configuration key (e.g., 'setup_complete', 'initial_admin_created')
- `value: string` - Configuration value (JSON encoded if complex)
- `created_at: timestamp`
- `updated_at: timestamp`

### Middleware

#### RequireSetupMiddleware
Middleware to enforce setup completion:

**Logic:**
1. Check if current route is setup-related (allow through)
2. Check if setup is complete (allow through)
3. Check if request is for assets/health checks (allow through)
4. Redirect to setup wizard

#### SetupCompleteMiddleware
Middleware to prevent access to setup routes after completion:

**Logic:**
1. Check if setup is complete
2. If complete and accessing setup routes, return 404
3. Otherwise allow through

## Data Models

### User Model Extensions
The existing User model already supports the required functionality:
- Admin role via UserRole enum
- Email verification capabilities
- Password hashing and validation

### Configuration Storage
Setup configuration will use a hybrid approach to handle different deployment scenarios:

**Primary: File-based State Tracking**
```
storage/app/setup/setup-state.json
```
This approach works regardless of database availability and persists across deployments.

**Secondary: Database Table (Post-Migration)**
```sql
CREATE TABLE setup_configurations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(255) NOT NULL UNIQUE,
    value TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Database Configuration Handling:**
- **SQLite**: Automatically create database file if it doesn't exist
- **MySQL**: Validate connection parameters and test connectivity
- **Environment Updates**: Update .env file with validated database settings
- **Migration Execution**: Run migrations as part of database setup step

### Cloud Storage Configuration
Leverage existing `config/cloud-storage.php` structure:
- Store credentials securely in environment variables
- Update configuration arrays dynamically
- Validate against existing provider schemas

## Error Handling

### Validation Errors
- Use Laravel's built-in validation with custom request classes
- Provide specific error messages for each field
- Support AJAX validation for real-time feedback

### System Errors
- **Database connection failures**: Clear error messages with troubleshooting steps for both MySQL and SQLite
- **Migration failures**: Detailed error reporting with rollback options
- **File permission issues**: Specific instructions for SQLite file creation and .env file updates
- **Cloud storage API errors**: Specific error codes with resolution guidance
- **Environment file issues**: Guidance for file permissions and syntax validation

### Recovery Mechanisms
- Allow partial setup completion and resumption
- Provide manual override options for advanced users
- Include diagnostic tools for troubleshooting common issues

## Testing Strategy

### Unit Tests
- SetupService methods for business logic validation
- CloudStorageSetupService for API integration testing
- Middleware behavior for different setup states
- Model validation and data integrity

### Feature Tests
- Complete setup wizard flow from start to finish
- Error handling for invalid inputs
- Middleware redirection behavior
- Database and storage connectivity testing

### Integration Tests
- **Database Setup Testing**: Both MySQL and SQLite initialization scenarios
- **Migration Execution**: Test migration running during setup process
- **Environment File Updates**: Validate .env file modifications and persistence
- **Google Drive API**: Connection testing with mock credentials
- **Multi-step Flow**: Complete wizard flow with different database types
- **Error Recovery**: Test partial setup scenarios and resumption capabilities

### Browser Tests
- JavaScript functionality for dynamic form elements
- Progress indicator updates
- AJAX validation feedback
- Responsive design across different screen sizes

## Security Considerations

### Access Control
- Setup wizard only accessible when setup is required
- No authentication required for initial setup (by design)
- Setup routes disabled after completion
- Rate limiting on setup form submissions

### Data Protection
- Secure storage of cloud storage credentials
- Password hashing for admin user creation
- CSRF protection on all forms
- Input sanitization and validation

### Environment Security
- Validate file permissions for .env updates
- Secure temporary storage of configuration data
- Audit logging for setup completion
- Protection against setup state manipulation

## User Experience Design

### Progress Indication
- Multi-step progress bar showing current position
- Clear step titles and descriptions
- Estimated time for completion
- Ability to navigate between completed steps

### Form Design
- Single-column layout for simplicity
- Clear field labels and help text
- Real-time validation feedback
- Responsive design for mobile devices

### Error Messaging
- Contextual error messages near relevant fields
- Success confirmations for completed steps
- Clear instructions for resolving issues
- Links to documentation and support resources

### Accessibility
- WCAG 2.1 AA compliance
- Keyboard navigation support
- Screen reader compatibility
- High contrast mode support