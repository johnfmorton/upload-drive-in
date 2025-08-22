# Design Document

## Overview

This design replaces the complex setup wizard with a simple, static instructional page that guides users through manual server configuration. The approach eliminates all wizard complexity while providing clear guidance for system administrators.

## Architecture

### Detection Logic
- **Setup Status Detection**: Simple boolean checks for required configuration
- **Automatic Routing**: Middleware redirects to instructions or main app based on setup status
- **No Database Dependencies**: Setup detection works even without database connection

### Page Structure
- **Single Static View**: One comprehensive instructions page
- **Progressive Disclosure**: Instructions organized in clear, numbered steps
- **Copy-Paste Ready**: All configuration examples formatted for easy copying

## Components and Interfaces

### Setup Detection Service
```php
class SetupDetectionService
{
    public function isSetupComplete(): bool
    public function getDatabaseStatus(): bool
    public function getGoogleDriveStatus(): bool
    public function getAdminUserStatus(): bool
    public function getMissingRequirements(): array
}
```

### Setup Instructions Controller
```php
class SetupInstructionsController
{
    public function show(): View
    // Simple controller that displays instructions page
}
```

### Setup Detection Middleware
```php
class SetupDetectionMiddleware
{
    // Replaces all existing setup middleware
    // Redirects to instructions if setup incomplete
    // Allows normal app access if setup complete
}
```

## Data Models

### No New Models Required
- Removes all existing setup-related models
- Uses existing User model for admin user detection
- Configuration stored in .env file only

### Environment Variables
```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Google Drive Configuration
GOOGLE_DRIVE_CLIENT_ID=your_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret
```

## Error Handling

### Graceful Degradation
- **Database Unavailable**: Show instructions without database-dependent checks
- **Partial Configuration**: Clearly indicate which steps are complete/incomplete
- **Invalid Configuration**: Provide helpful error messages with correction guidance

### Error States
- Missing database connection
- Invalid Google Drive credentials
- No admin users found
- File permission issues

## Testing Strategy

### Unit Tests
- SetupDetectionService methods
- Configuration validation logic
- Environment variable parsing

### Integration Tests
- Complete setup flow simulation
- Partial setup scenarios
- Error condition handling

### Manual Testing
- Fresh installation workflow
- Configuration error scenarios
- Setup completion detection

## Implementation Approach

### Phase 1: Create New Simple System
1. Build SetupDetectionService
2. Create simple instructions view
3. Implement detection middleware
4. Add routing for instructions page

### Phase 2: Remove Existing Setup System
1. Remove setup wizard controllers
2. Remove setup wizard views
3. Remove setup wizard routes
4. Remove setup wizard middleware
5. Remove setup wizard services
6. Remove setup database tables

### Phase 3: Integration and Testing
1. Update application bootstrap
2. Test setup detection logic
3. Verify instruction clarity
4. Test complete setup flow

## User Experience Flow

### First-Time Installation
1. User accesses application URL
2. System detects incomplete setup
3. User sees clear instructions page
4. User follows manual configuration steps
5. System automatically detects completion
6. User redirected to login page

### Instructions Page Content
```
Setup Instructions

To complete the installation of this application, please follow these steps:

1. Database Configuration
   Add these variables to your .env file:
   [Copy-paste ready configuration block]

2. Google Drive Setup
   Add these variables to your .env file:
   [Copy-paste ready configuration block]

3. Create Admin User
   Run this command on your server:
   [Copy-paste ready command]

Once completed, refresh this page to access the application.
```

## Security Considerations

### Minimal Attack Surface
- No complex setup state management
- No setup-specific database tables
- No wizard session handling
- Simple environment variable validation

### Access Control
- Instructions page accessible without authentication
- No sensitive information displayed
- Clear guidance without exposing system details

## Performance Benefits

### Reduced Complexity
- Eliminates setup wizard JavaScript
- Removes setup-specific database queries
- Simplifies middleware stack
- Reduces memory footprint

### Faster Installation
- No multi-step wizard navigation
- No setup state persistence
- Immediate feedback on configuration status
- Single-page instruction reference