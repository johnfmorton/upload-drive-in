# Design Document

## Overview

The Pending Upload Recovery System will implement a multi-layered approach to handle stuck uploads through automated recovery, manual intervention tools, and comprehensive monitoring. The system will replace the current alert-based interface with proper modal components following established standards, while providing robust error handling and diagnostic capabilities.

## Architecture

### Core Components

1. **Upload Recovery Service** - Handles automatic detection and recovery of stuck uploads
2. **Upload Diagnostic Service** - Provides detailed analysis of upload failures and system health
3. **Modal-Based Dashboard Interface** - Replaces alert-based UI with proper modal components
4. **Enhanced CLI Commands** - Comprehensive command-line tools for production debugging
5. **Monitoring and Alerting System** - Proactive detection of upload processing issues

### Data Flow

```
Pending Upload Detection → Recovery Attempt → Status Update → Notification
                       ↓
                   Error Analysis → Retry Logic → Final Status → Alert System
```

## Components and Interfaces

### 1. Upload Recovery Service

**Class:** `App\Services\UploadRecoveryService`

**Key Methods:**
- `detectStuckUploads()` - Identifies uploads pending beyond threshold
- `attemptRecovery($uploadId)` - Processes individual stuck upload
- `bulkRecovery($uploadIds)` - Handles multiple uploads efficiently
- `analyzeFailurePattern($uploads)` - Identifies systemic issues

**Recovery Logic:**
1. Check if original file still exists in storage
2. Verify Google Drive token validity and refresh if needed
3. Attempt to re-queue the upload job with fresh context
4. Update status based on success/failure with detailed logging

### 2. Upload Diagnostic Service

**Class:** `App\Services\UploadDiagnosticService`

**Key Methods:**
- `performHealthCheck()` - Comprehensive system status check
- `analyzeUploadFailure($uploadId)` - Detailed failure analysis
- `checkSystemResources()` - Disk space, memory, queue worker status
- `validateGoogleDriveConnectivity()` - API connectivity and token status

**Diagnostic Checks:**
- Queue worker responsiveness
- Google Drive API rate limits and quotas
- Local storage availability and permissions
- Database connectivity and consistency
- Token expiration and refresh status

### 3. Enhanced File Upload Model

**Updates to:** `App\Models\FileUpload`

**New Status Constants:**
```php
const STATUS_PENDING = 'pending';
const STATUS_PROCESSING = 'processing';
const STATUS_UPLOADED = 'uploaded';
const STATUS_FAILED = 'failed';
const STATUS_MISSING_FILE = 'missing_file';
const STATUS_RETRY = 'retry';
```

**New Fields:**
- `retry_count` - Number of processing attempts
- `last_error` - Most recent error message
- `error_details` - JSON field for detailed error information
- `last_processed_at` - Timestamp of last processing attempt
- `recovery_attempts` - Count of automatic recovery attempts

### 4. Modal-Based Dashboard Interface

**Component:** `resources/views/components/file-manager/modals/process-pending-modal.blade.php`

**Modal Structure:**
- Follows established z-index hierarchy (z-[10000] for content)
- Implements proper backdrop and transition effects
- Shows pending upload count and processing options
- Displays real-time progress during processing
- Provides detailed results summary

**Alpine.js Data Structure:**
```javascript
{
    showProcessPendingModal: false,
    pendingCount: 0,
    isProcessing: false,
    processingProgress: 0,
    processedCount: 0,
    failedCount: 0,
    results: null
}
```

### 5. Enhanced CLI Commands

**Command:** `app/Console/Commands/Uploads/DiagnoseUploadsCommand.php`
- Comprehensive system health check
- Upload queue analysis
- Google Drive connectivity test
- Resource availability check

**Command:** `app/Console/Commands/Uploads/RecoverPendingUploadsCommand.php`
- Automated recovery of stuck uploads
- Batch processing with progress reporting
- Detailed logging and error reporting

**Command:** `app/Console/Commands/Uploads/CleanupUploadsCommand.php`
- Remove orphaned files
- Fix inconsistent database states
- Clean up failed upload artifacts

## Data Models

### Database Schema Updates

**Migration:** `add_recovery_fields_to_file_uploads_table`

```php
Schema::table('file_uploads', function (Blueprint $table) {
    $table->integer('retry_count')->default(0);
    $table->text('last_error')->nullable();
    $table->json('error_details')->nullable();
    $table->timestamp('last_processed_at')->nullable();
    $table->integer('recovery_attempts')->default(0);
    $table->index(['status', 'created_at']);
    $table->index(['status', 'last_processed_at']);
});
```

### Configuration Updates

**Config:** `config/upload-recovery.php`

```php
return [
    'stuck_threshold_minutes' => env('UPLOAD_STUCK_THRESHOLD', 30),
    'max_retry_attempts' => env('UPLOAD_MAX_RETRIES', 3),
    'max_recovery_attempts' => env('UPLOAD_MAX_RECOVERY', 5),
    'batch_size' => env('UPLOAD_RECOVERY_BATCH_SIZE', 10),
    'alert_threshold_hours' => env('UPLOAD_ALERT_THRESHOLD', 1),
    'failure_rate_threshold' => env('UPLOAD_FAILURE_RATE_THRESHOLD', 0.1),
];
```

## Error Handling

### Error Classification

1. **Transient Errors** - Network issues, temporary API limits
   - Automatic retry with exponential backoff
   - Up to 3 retry attempts

2. **Permanent Errors** - Missing files, invalid tokens
   - Mark as failed immediately
   - Require manual intervention

3. **System Errors** - Disk space, queue worker issues
   - Alert administrators
   - Pause processing until resolved

### Error Recovery Strategies

**Google Drive API Errors:**
- Rate limit exceeded: Exponential backoff (1min, 5min, 15min)
- Token expired: Automatic refresh and retry
- Quota exceeded: Alert admin and pause uploads

**File System Errors:**
- Missing file: Mark as missing_file status
- Permission denied: Alert admin and retry once
- Disk full: Alert admin and pause uploads

**Queue System Errors:**
- Worker not responding: Alert admin immediately
- Job timeout: Increase timeout and retry
- Memory limit: Process in smaller batches

## Testing Strategy

### Unit Tests
- Upload recovery service methods
- Diagnostic service health checks
- Error classification logic
- Modal component behavior

### Integration Tests
- End-to-end recovery workflow
- Google Drive API error handling
- Queue job processing with failures
- CLI command functionality

### Manual Testing
- Modal interface following design standards
- Progress indicators during processing
- Error message display and handling
- CLI command output and behavior

### Performance Tests
- Bulk recovery processing
- Large file upload handling
- System resource usage during recovery
- Database query performance with new indexes

## Security Considerations

### Access Control
- Only admin users can access recovery functions
- CLI commands require proper authentication
- Sensitive error details logged securely

### Data Protection
- Error logs exclude sensitive file content
- Google Drive tokens handled securely
- File cleanup respects retention policies

### Audit Trail
- All recovery actions logged with timestamps
- User actions tracked for accountability
- System alerts include relevant context

## Monitoring and Alerting

### Metrics to Track
- Upload success/failure rates
- Average processing time
- Queue depth and processing speed
- Google Drive API usage and limits

### Alert Conditions
- Uploads pending > 1 hour
- Failure rate > 10% in 24 hours
- Queue worker unresponsive > 15 minutes
- Google Drive token expiration imminent

### Dashboard Indicators
- Real-time upload status counts
- Recent failure trends
- System health status
- Queue processing metrics