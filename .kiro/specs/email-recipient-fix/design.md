# Design Document

## Overview

The email recipient fix addresses a critical bug in the `SendBatchUploadNotifications` listener where the recipient selection logic incorrectly falls back to admin users instead of using the intended recipient specified during file upload. The fix involves correcting the recipient determination logic and improving error handling and logging throughout the notification system.

## Architecture

### Current System Flow
1. Client uploads files via `Client/UploadController`
2. `company_user_id` is correctly set in `FileUpload` records
3. `BatchUploadComplete` event is dispatched
4. `SendBatchUploadNotifications` listener processes the event
5. **BUG**: Recipient selection logic incorrectly determines recipients
6. Emails are sent to wrong recipients

### Fixed System Flow
1. Client uploads files via `Client/UploadController` (no changes needed)
2. `company_user_id` is correctly set in `FileUpload` records (no changes needed)
3. `BatchUploadComplete` event is dispatched (no changes needed)
4. `SendBatchUploadNotifications` listener processes the event with **corrected logic**
5. Emails are sent to correct recipients based on upload context

## Components and Interfaces

### SendBatchUploadNotifications Listener

**Current Problematic Logic:**
```php
$candidateId = $upload->company_user_id ?? $upload->uploaded_by_user_id;
```

**Fixed Logic:**
```php
// For client uploads: prioritize company_user_id (the selected recipient)
// For employee/admin uploads: use uploaded_by_user_id (the uploader)
if ($upload->client_user_id) {
    // This is a client upload - use the selected company user
    $candidateId = $upload->company_user_id;
} else {
    // This is an employee/admin upload - notify the uploader
    $candidateId = $upload->uploaded_by_user_id;
}
```

### Enhanced Logging

**Recipient Selection Logging:**
- Log the upload context (client vs employee/admin upload)
- Log the selected recipient for each upload
- Log fallback scenarios when they occur

**Error Handling Improvements:**
- More specific error messages for different failure scenarios
- Graceful handling of missing or invalid recipient data
- Continue processing other uploads even if one fails

## Data Models

### FileUpload Model Fields (No Changes Required)
- `client_user_id`: ID of the client who uploaded the file
- `company_user_id`: ID of the employee/admin selected as recipient
- `uploaded_by_user_id`: ID of the employee/admin who uploaded on behalf of client

### Upload Context Determination
```php
$isClientUpload = !empty($upload->client_user_id);
$isEmployeeUpload = !empty($upload->uploaded_by_user_id) && empty($upload->client_user_id);
```

## Error Handling

### Recipient Determination Errors
1. **Missing company_user_id for client upload**: Fall back to primary company user
2. **Invalid company_user_id**: Fall back to admin user
3. **No valid recipient found**: Fall back to admin user
4. **Recipient has no email**: Skip notification and log warning

### Email Sending Errors
1. **Individual recipient email failure**: Log error, continue with other recipients
2. **Client confirmation email failure**: Log error, continue processing
3. **Complete notification failure**: Log error with full context

### Fallback Strategy
```php
// Priority order for recipient determination:
// 1. Specified company_user_id (for client uploads)
// 2. uploaded_by_user_id (for employee uploads)  
// 3. Primary company user (for client with relationship)
// 4. Admin user (final fallback)
```

## Testing Strategy

### Unit Tests
- Test recipient selection logic with various upload scenarios
- Test fallback logic when recipients are invalid
- Test error handling for missing data
- Test logging output for different scenarios

### Integration Tests
- Test complete email flow for client uploads to specific recipients
- Test employee upload notifications
- Test batch uploads with mixed recipients
- Test email delivery with invalid recipients

### Test Scenarios
1. **Client upload to specific employee**: Should notify only that employee
2. **Client upload with no recipient selected**: Should notify primary company user
3. **Employee upload on behalf of client**: Should notify the employee
4. **Batch upload to multiple recipients**: Each should get only their files
5. **Invalid recipient scenarios**: Should fall back gracefully to admin

## Implementation Notes

### Backward Compatibility
- Existing `FileUpload` records will work with the new logic
- No database migrations required
- Existing email templates remain unchanged

### Performance Considerations
- Recipient determination logic is O(n) where n is number of uploads
- Database queries are already optimized with proper relationships
- No additional database calls required

### Monitoring and Observability
- Enhanced logging will help identify recipient selection patterns
- Error logs will help troubleshoot delivery issues
- Success logs will confirm correct recipient targeting