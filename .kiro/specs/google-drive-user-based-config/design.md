# Design Document

## Overview

This design removes the global `GOOGLE_DRIVE_ROOT_FOLDER_ID` environment variable dependency and implements a purely user-based Google Drive root folder configuration system. The solution ensures each user (admin or employee) can independently configure their Google Drive root folder through their respective control panels, with the system defaulting to Google Drive root when no specific folder is configured.

## Architecture

### Current State Analysis

The current implementation has a mixed approach:
- Environment variable `GOOGLE_DRIVE_ROOT_FOLDER_ID` for global configuration
- User-specific `google_drive_root_folder_id` field in the users table
- CloudStorageSetting model that prioritizes environment over database values

### Target State

The new implementation will:
- Remove all references to `GOOGLE_DRIVE_ROOT_FOLDER_ID` environment variable
- Use only user-specific database settings
- Default to Google Drive root ('root') when no user setting exists
- Maintain backward compatibility for existing user configurations

## Components and Interfaces

### GoogleDriveService Modifications

**Current Methods to Update:**
- `getRootFolderId()` - Remove CloudStorageSetting dependency
- `getEffectiveRootFolderId(User $user)` - Simplify to only check user setting
- `uploadFileForUser()` - Update to use simplified logic

**New Method Signature:**
```php
public function getEffectiveRootFolderId(User $user): string
{
    // Return user's setting or default to 'root'
    return $user->google_drive_root_folder_id ?? 'root';
}
```

### Controller Updates

**Admin CloudStorageController:**
- Remove environment variable handling for root folder
- Remove CloudStorageSetting operations for root folder
- Simplify validation to focus on user-specific settings

**Employee Controllers:**
- Ensure consistent behavior with admin approach
- Maintain existing user-specific folder management

### Database Schema

**No changes required** - the `users.google_drive_root_folder_id` field already exists and supports the new approach.

### Frontend Components

**Admin Interface:**
- Remove environment variable configuration options
- Focus on user-specific folder selection
- Clear messaging about default behavior

**Employee Interface:**
- Maintain existing folder selection functionality
- Ensure consistent messaging with admin interface

## Data Models

### User Model
```php
// Existing field - no changes needed
protected $fillable = [
    // ... other fields
    'google_drive_root_folder_id',
];

protected $casts = [
    // ... other casts
    'google_drive_root_folder_id' => 'string',
];
```

### CloudStorageSetting Model
- Remove usage for 'google-drive' provider 'root_folder_id' key
- Keep model for other cloud storage providers if needed

## Error Handling

### Graceful Defaults
- When `$user->google_drive_root_folder_id` is null/empty, default to 'root'
- No exceptions thrown for missing configuration
- Clear logging when defaulting to Google Drive root

### Migration Considerations
- Existing users with configured folders continue to work
- Users without configured folders get default behavior
- No data migration required

## Testing Strategy

### Unit Tests
- Test `getEffectiveRootFolderId()` with various user configurations
- Test default behavior when user has no folder configured
- Test that environment variables are not referenced

### Integration Tests
- Test admin folder configuration workflow
- Test employee folder configuration workflow
- Test file upload with various folder configurations

### Backward Compatibility Tests
- Test existing user configurations continue to work
- Test system behavior with missing environment variables
- Test upgrade path from current implementation

## Implementation Phases

### Phase 1: Service Layer Updates
- Update GoogleDriveService methods
- Remove CloudStorageSetting dependencies
- Implement simplified logic

### Phase 2: Controller Updates
- Update admin controllers
- Update employee controllers
- Remove environment variable handling

### Phase 3: Frontend Updates
- Update admin interface
- Update employee interface
- Improve user messaging

### Phase 4: Cleanup
- Remove unused CloudStorageSetting operations
- Update documentation
- Remove environment variable references

## Security Considerations

- User folder configurations remain isolated per user
- No global configuration that could affect all users
- Maintain existing Google Drive API authentication patterns

## Performance Considerations

- Simplified logic reduces database queries to CloudStorageSetting
- Direct user field access is more efficient
- No impact on existing Google Drive API performance

## Documentation and Cleanup Summary

The following documentation and cleanup tasks have been completed:

### Documentation Updates
- **README.md**: Removed references to `GOOGLE_DRIVE_ROOT_FOLDER_ID` environment variable and updated Google Drive setup instructions
- **documentation.md**: Added comprehensive section explaining the new user-based configuration approach
- **.env.example**: Confirmed no `GOOGLE_DRIVE_ROOT_FOLDER_ID` reference (already clean)
- **Steering Rules**: Updated `google-drive-integration.md` to reflect user-based configuration

### Code Cleanup
- **CloudStorageSetting Model**: Removed 'root_folder_id' from google-drive provider configuration keys
- **Environment Variable References**: Confirmed all references to `GOOGLE_DRIVE_ROOT_FOLDER_ID` have been removed from active code

### Migration Notes
- No data migration required - existing user configurations preserved
- System gracefully defaults to Google Drive root when no folder configured
- Backward compatibility maintained for existing users