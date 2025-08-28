# Design Document

## Overview

This design addresses the critical security and functionality issue in the employee file manager where preview and download operations redirect directly to Google Drive URLs instead of using the application's secure file serving infrastructure.

**The solution follows the DRY (Don't Repeat Yourself) principle by directly copying the working admin controller's preview and download functionality to the employee controller.** We are not creating new functionality - we are replicating the proven, secure, and fully-functional implementation that already exists in the admin controller.

The admin controller's file serving works perfectly with proper security, audit logging, error handling, and user experience. Our goal is to make the employee controller behave identically.

## Architecture

### Current State Analysis

**Admin Controller (Working Correctly):**

- Uses `FileManagerService->downloadFile()` for downloads
- Uses `FilePreviewService->generatePreview()` for previews
- Implements security validation via `FileSecurityService`
- Logs all access via `AuditLogService`
- Handles errors gracefully with user-friendly messages

**Employee Controller (Problematic):**

- Directly redirects to `https://drive.google.com/uc?export=download&id={file_id}` for downloads
- Directly redirects to `https://drive.google.com/file/d/{file_id}/preview` for previews
- Bypasses all security controls and audit logging
- Exposes Google Drive file IDs to client browsers
- Creates dependency on external Google Drive availability

### Target Architecture

**The employee controller will be refactored to exactly mirror the admin controller's working implementation.** This is a direct copy-paste approach with minimal modifications for employee-specific access control.

Benefits of this DRY approach:

1. **Proven Functionality**: Admin preview/download already works perfectly
2. **Zero New Bugs**: No new code means no new potential issues
3. **Consistent Behavior**: Identical user experience across user types
4. **Maintenance Efficiency**: Single codebase pattern to maintain
5. **Security Parity**: Same battle-tested security controls

## Components and Interfaces

### Modified Components

#### EmployeeFileManagerController

**Current Methods (to be updated):**

```php
// Current problematic implementation
public function download(FileUpload $file)
{
    // Direct Google Drive redirect - REMOVE
    if ($file->google_drive_file_id) {
        $downloadUrl = "https://drive.google.com/uc?export=download&id={$file->google_drive_file_id}";
        return redirect($downloadUrl);
    }
}

public function preview(FileUpload $file): Response
{
    // Direct Google Drive redirect - REMOVE
    if ($file->google_drive_file_id) {
        $previewUrl = "https://drive.google.com/file/d/{$file->google_drive_file_id}/preview";
        return redirect($previewUrl);
    }
}
```

**New Implementation (direct copy from admin controller with employee access control):**

```php
public function download(FileUpload $file)
{
    $this->checkEmployeeAccess($file);
    
    try {
        // Security validation
        $securityViolations = $this->fileSecurityService->validateExistingFile($file);
        $highSeverityViolations = array_filter($securityViolations, fn($v) => $v['severity'] === 'high');
        
        if (!empty($highSeverityViolations)) {
            $this->auditLogService->logSecurityViolation('download_blocked_security', auth()->user(), request(), [
                'file_id' => $file->id,
                'violations' => $highSeverityViolations
            ]);
            
            return $this->handleSecurityViolation('File download blocked due to security concerns.');
        }

        // Audit log file download
        $this->auditLogService->logFileAccess('download', $file, auth()->user(), request());
        
        // Use FileManagerService for consistent file serving
        return $this->fileManagerService->downloadFile($file, auth()->user());
        
    } catch (\App\Exceptions\FileAccessException $e) {
        return $this->handleFileAccessException($e, 'download');
    } catch (\Exception $e) {
        return $this->handleGeneralException($e, 'download', $file);
    }
}

public function preview(FileUpload $file): Response
{
    // Ensure user is authenticated
    if (!auth()->check()) {
        abort(401, 'Authentication required');
    }

    // Check if the authenticated user can access this file
    if (!$file->canBeAccessedBy(auth()->user())) {
        abort(403, 'Access denied to this file');
    }

    try {
        // Security validation for preview
        if (!$this->fileSecurityService->isPreviewSafe($file->mime_type)) {
            $this->auditLogService->logSecurityViolation('preview_blocked_unsafe_type', auth()->user(), request(), [
                'file_id' => $file->id,
                'mime_type' => $file->mime_type
            ]);
            
            return response('Preview not available for this file type due to security restrictions.', 403, [
                'Content-Type' => 'text/plain'
            ]);
        }

        // Generate ETag for conditional requests
        $etag = md5($file->id . '_' . $file->file_size . '_' . $file->updated_at->timestamp);
        
        // Check if client has cached version
        if (request()->header('If-None-Match') === '"' . $etag . '"') {
            return response('', 304);
        }

        // Audit log file preview
        $this->auditLogService->logFileAccess('preview', $file, auth()->user(), request());

        return $this->filePreviewService->generatePreview($file, auth()->user());
        
    } catch (\Exception $e) {
        return response('Preview not available: ' . $e->getMessage(), 404, [
            'Content-Type' => 'text/plain'
        ]);
    }
}
```

#### Service Dependencies

The employee controller will inject the exact same services as the admin controller (direct copy):

```php
public function __construct(
    private FileManagerService $fileManagerService,
    private FilePreviewService $filePreviewService,
    private FileSecurityService $fileSecurityService,
    private AuditLogService $auditLogService
) {
}
```

### Helper Methods

#### Access Control

```php
private function checkEmployeeAccess(FileUpload $file): void
{
    $user = auth()->user();
    
    if (!$user || $user->role !== 'employee') {
        abort(403, 'Employee access required');
    }
    
    if (!$file->canBeAccessedBy($user)) {
        abort(403, 'Access denied to this file');
    }
}
```

#### Error Handling

```php
private function handleSecurityViolation(string $message)
{
    if (request()->expectsJson()) {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_type' => 'security_violation'
        ], 403);
    }
    
    return redirect()->back()->with('error', $message);
}

private function handleFileAccessException(\App\Exceptions\FileAccessException $e, string $operation)
{
    \Log::warning("File {$operation} access denied", [
        'user_id' => auth()->id(),
        'error' => $e->getMessage()
    ]);
    
    if (request()->expectsJson()) {
        return response()->json([
            'success' => false,
            'message' => $e->getUserMessage(),
            'error_type' => 'access_denied'
        ], 403);
    }

    return redirect()->back()->with('error', $e->getUserMessage());
}

private function handleGeneralException(\Exception $e, string $operation, FileUpload $file)
{
    \Log::error("File {$operation} error", [
        'user_id' => auth()->id(),
        'file_id' => $file->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    if (request()->expectsJson()) {
        return response()->json([
            'success' => false,
            'message' => "Error {$operation}ing file: " . $e->getMessage(),
            'error_type' => "{$operation}_failed",
            'is_retryable' => true
        ], 500);
    }

    return redirect()->back()->with('error', "Error {$operation}ing file. Please try again.");
}
```

## Data Models

No changes to existing data models are required. The solution leverages existing:

- `FileUpload` model with `canBeAccessedBy()` method
- User authentication and role system
- Existing file metadata and Google Drive integration

## Error Handling

### Security Violations

- High-severity security violations block access and log the attempt
- Unsafe file types for preview are blocked with appropriate messaging
- All security events are logged via `AuditLogService`

### File Access Errors

- `FileAccessException` provides user-friendly error messages
- Access denied scenarios return 403 with appropriate context
- All access attempts are logged for audit purposes

### General Errors

- File serving errors are logged with full context
- Users receive helpful error messages without exposing system details
- Retryable errors are marked appropriately for client handling

### Response Formats

- JSON responses for AJAX requests
- Redirect responses with flash messages for regular requests
- Consistent error structure across both admin and employee interfaces

## Testing Strategy

### Unit Tests

- Test employee controller methods in isolation
- Mock all service dependencies
- Verify security validation calls
- Test error handling scenarios

### Integration Tests

- Test complete file download flow for employees
- Test complete file preview flow for employees
- Verify audit logging integration
- Test access control enforcement

### Security Tests

- Verify employees cannot access files outside their scope
- Test security violation logging
- Verify unsafe file type blocking
- Test authentication requirements

### Regression Tests

- Ensure admin functionality remains unchanged
- Verify existing employee file listing still works
- Test backward compatibility with existing URLs

### Performance Tests

- Compare response times before and after changes
- Verify no performance degradation in file serving
- Test concurrent access scenarios

## Migration Strategy

### Phase 1: Service Integration

1. Add service dependencies to employee controller constructor
2. Add helper methods for access control and error handling
3. Update dependency injection configuration

### Phase 2: Method Replacement (Direct Copy from Admin)

1. Copy `download()` method from admin controller, replace `checkAdminAccess()` with `checkEmployeeAccess()`
2. Copy `preview()` method from admin controller with identical logic
3. Remove direct Google Drive URL generation code

### Phase 3: Testing and Validation

1. Run comprehensive test suite
2. Verify audit logs are generated correctly
3. Test error scenarios and user experience
4. Performance validation

### Rollback Plan

- Keep original methods commented out during initial deployment
- Monitor error logs and user feedback
- Quick rollback capability if issues arise
- Gradual rollout to subset of users if needed
