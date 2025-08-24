# Design Document

## Overview

This design addresses the missing `secureFileWrite` method in the `SetupSecurityService` class that causes application failures when using database sessions. The solution involves implementing the missing method with proper security validation, error handling, and logging to match the existing `secureFileRead` method's patterns and security standards.

## Architecture

### Current State Analysis

The `SetupSecurityService` currently has:
- `secureFileRead` method with path validation and security checks
- Security validation methods (`validateFilePath`, `logSecurityEvent`)
- Input sanitization and request validation methods

The `SetupService` calls `secureFileWrite` during state persistence but the method doesn't exist, causing the undefined method error when database sessions trigger setup state saving.

### Proposed Solution

Implement the missing `secureFileWrite` method following the same security patterns as `secureFileRead`, ensuring consistent API design and security validation.

## Components and Interfaces

### SetupSecurityService Enhancement

#### New Method: `secureFileWrite`

```php
public function secureFileWrite(string $filePath, string $content, int $mode = 0644): array
```

**Parameters:**
- `$filePath`: Target file path for writing
- `$content`: Content to write to the file
- `$mode`: File permissions (default: 0644)

**Return Format:**
```php
[
    'success' => bool,
    'message' => string,
    'bytes_written' => int|null
]
```

#### Security Validation Flow

1. **Path Validation**: Use existing `validateFilePath` method
2. **Directory Creation**: Ensure parent directory exists
3. **Permission Checks**: Validate write permissions
4. **Atomic Write**: Use temporary file for atomic operations
5. **Permission Setting**: Apply specified file mode
6. **Logging**: Log security events for monitoring

### Integration Points

#### SetupService Integration

The `SetupService::saveSetupState` method already calls `secureFileWrite` with the correct signature:

```php
$result = $this->securityService->secureFileWrite($this->stateFile, $content, 0644);
```

No changes needed to the calling code.

#### Error Handling Integration

The method will integrate with existing error handling patterns:
- Return structured arrays with success/failure status
- Use existing `logSecurityEvent` method for security monitoring
- Throw exceptions only for critical system failures

## Data Models

### File Operation Result Structure

```php
[
    'success' => boolean,        // Operation success status
    'message' => string,         // Human-readable result message
    'bytes_written' => int|null  // Number of bytes written (null on failure)
]
```

### Security Validation Result (Existing)

```php
[
    'is_valid' => boolean,       // Path validation result
    'violations' => array        // List of security violations
]
```

## Error Handling

### Security Violations

- **Path Traversal**: Reject paths containing `..`
- **Null Bytes**: Reject paths containing null bytes
- **Directory Restrictions**: Only allow writes within storage paths
- **Permission Issues**: Handle directory creation and file permission failures

### File System Errors

- **Directory Creation**: Create parent directories if they don't exist
- **Write Failures**: Handle disk space, permissions, and I/O errors
- **Atomic Operations**: Use temporary files to prevent partial writes

### Logging Strategy

Security events will be logged using the existing `logSecurityEvent` method:

- `secure_file_write_success`: Successful file write operations
- `secure_file_write_failed`: Failed write operations with reasons
- `path_validation_failed`: Security violations during path validation
- `permission_denied`: File system permission issues

## Testing Strategy

### Unit Tests

1. **Method Existence**: Verify `secureFileWrite` method exists and is callable
2. **Path Validation**: Test security validation with malicious paths
3. **Success Cases**: Test successful file writing with various content types
4. **Error Cases**: Test handling of permission errors, disk space issues
5. **Mode Setting**: Verify file permissions are set correctly

### Integration Tests

1. **SetupService Integration**: Test that setup state saving works with database sessions
2. **Session Driver Compatibility**: Test application startup with both file and database sessions
3. **Security Logging**: Verify security events are logged correctly
4. **Atomic Operations**: Test that failed writes don't leave partial files

### Feature Tests

1. **Database Session Flow**: Test complete application flow with `SESSION_DRIVER=database`
2. **Setup Process**: Test setup wizard completion with database sessions
3. **State Persistence**: Test that setup state is correctly saved and retrieved
4. **Error Recovery**: Test application behavior when file writes fail

## Implementation Approach

### Phase 1: Core Method Implementation

1. Implement `secureFileWrite` method in `SetupSecurityService`
2. Add comprehensive error handling and validation
3. Integrate with existing security logging

### Phase 2: Testing and Validation

1. Add unit tests for the new method
2. Update existing integration tests to cover database sessions
3. Test with both session drivers to ensure compatibility

### Phase 3: Documentation and Monitoring

1. Update method documentation with examples
2. Add security monitoring for file write operations
3. Verify production deployment compatibility

## Security Considerations

### File System Security

- **Path Validation**: Prevent directory traversal attacks
- **Permission Management**: Set appropriate file permissions
- **Atomic Writes**: Prevent race conditions and partial writes
- **Directory Restrictions**: Limit writes to authorized storage areas

### Monitoring and Auditing

- **Security Events**: Log all file write attempts and results
- **Failed Operations**: Monitor and alert on repeated failures
- **Path Violations**: Track and investigate security violations
- **Performance Impact**: Monitor file I/O performance

### Backward Compatibility

- **Existing Functionality**: Maintain all current security service features
- **API Consistency**: Match existing method patterns and return formats
- **Session Drivers**: Support both file and database session drivers
- **Configuration**: No changes required to existing configuration