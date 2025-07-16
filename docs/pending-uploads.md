# Pending Uploads Management

This document describes the pending uploads feature that handles files that failed to upload to Google Drive due to connection issues or other temporary problems.

## Overview

When files are uploaded but cannot be immediately sent to Google Drive (due to connection issues, authentication problems, or service outages), they remain in "Pending" status. The pending uploads system provides both automatic and manual ways to process these files once the issues are resolved.

## How It Works

### Upload Status Determination

File upload status is determined by the presence of a `google_drive_file_id`:
- **Uploaded**: File has a valid `google_drive_file_id`
- **Pending**: File has no `google_drive_file_id` or an empty value

### Automatic Processing

The system automatically processes pending uploads:
- **Schedule**: Every 30 minutes
- **Limit**: Up to 25 files per run
- **Logging**: Results logged to `storage/logs/pending-uploads.log`
- **Overlap Protection**: Prevents multiple instances running simultaneously

### Manual Processing

Administrators can manually trigger pending upload processing through:
1. **Admin Dashboard**: One-click "Process Pending" button
2. **Command Line**: Artisan command with various options

## Admin Dashboard Interface

### Pending Upload Indicator

When pending uploads exist, the admin dashboard shows:
- Yellow badge with pending count next to "Uploaded Files" title
- "Process Pending" button for immediate processing
- Confirmation dialog before processing

### Processing Flow

1. Admin clicks "Process Pending" button
2. System confirms the action with upload count
3. Artisan command executes in background
4. Success/error message displayed
5. Queue processes uploads asynchronously

## Command Line Interface

### Basic Usage

```bash
# Process all pending uploads (default limit: 50)
ddev artisan uploads:process-pending

# See what would be processed without actually processing
ddev artisan uploads:process-pending --dry-run

# Process uploads for a specific user
ddev artisan uploads:process-pending --user-id=123

# Limit the number of uploads processed
ddev artisan uploads:process-pending --limit=10
```

### Command Options

| Option | Description | Default |
|--------|-------------|---------|
| `--user-id` | Process uploads for specific user ID | All users |
| `--dry-run` | Show what would be processed without processing | false |
| `--limit` | Maximum number of uploads to process | 50 |

### Command Output

The command provides detailed output:
- Files being processed with their names
- Target user for each upload
- Success/error status for each file
- Summary with counts of processed, skipped, and error files

Example output:
```
Processing pending uploads...
Found 4 pending uploads.
Processing upload ID 123: document.pdf
  ✅ Queued for processing with user: admin@example.com
Processing upload ID 124: image.jpg
  ⚠️  Target user john@example.com has no Google Drive connection
Processing upload ID 125: spreadsheet.xlsx
  ❌ Local file missing: uploads/abc123.xlsx

Summary:
  Processed: 1
  Skipped: 1
  Errors: 1
```

## Technical Implementation

### FileUpload Model Methods

```php
// Check if upload is pending
$upload->isPending(); // returns boolean

// Query scopes
FileUpload::pending()->get(); // Get all pending uploads
FileUpload::completed()->get(); // Get all completed uploads
```

### Processing Logic

1. **File Validation**: Checks if local file still exists
2. **User Resolution**: Finds appropriate user with Google Drive connection
3. **Job Dispatch**: Queues upload using existing `UploadToGoogleDrive` job
4. **Error Handling**: Logs and reports various failure scenarios

### User Priority for Google Drive Upload

The system determines which user's Google Drive to use in this order:
1. **Uploaded By User**: If file was uploaded by specific employee
2. **Company User**: If file has assigned company user
3. **Admin Fallback**: Any admin user with Google Drive connected

## Monitoring and Troubleshooting

### Log Files

- **Pending Uploads**: `storage/logs/pending-uploads.log`
- **Laravel Application**: `storage/logs/laravel.log`
- **Queue Jobs**: Monitor queue status with `ddev artisan queue:work`

### Common Issues and Solutions

#### No Pending Uploads Found
- **Cause**: All uploads have been processed
- **Action**: No action needed

#### Local File Missing
- **Cause**: File was deleted from local storage before processing
- **Action**: File cannot be recovered, remove database record

#### No Google Drive Connection
- **Cause**: Target user doesn't have Google Drive connected
- **Action**: Connect Google Drive for the user or assign to different user

#### Upload Job Failures
- **Cause**: Google Drive API errors, network issues, quota limits
- **Action**: Check Laravel logs, verify Google Drive connection, check API quotas

### Queue Management

Monitor queue status:
```bash
# Check queue status
ddev artisan queue:work

# View failed jobs
ddev artisan queue:failed

# Retry failed jobs
ddev artisan queue:retry all

# Clear failed jobs
ddev artisan queue:flush
```

## Scheduled Tasks

The pending uploads processing is scheduled in `app/Console/Kernel.php`:

```php
$schedule->command('uploads:process-pending --limit=25')
         ->everyThirtyMinutes()
         ->withoutOverlapping()
         ->runInBackground()
         ->appendOutputTo(storage_path('logs/pending-uploads.log'));
```

### Customizing the Schedule

To modify the automatic processing:

1. Edit `app/Console/Kernel.php`
2. Adjust the frequency (e.g., `->hourly()`, `->daily()`)
3. Change the limit (e.g., `--limit=50`)
4. Modify logging destination if needed

## Best Practices

### For Administrators

1. **Regular Monitoring**: Check admin dashboard for pending uploads
2. **Proactive Processing**: Process pending uploads after resolving connection issues
3. **Log Review**: Periodically review pending upload logs
4. **Queue Monitoring**: Ensure queue workers are running

### For Developers

1. **Error Handling**: Always handle Google Drive API failures gracefully
2. **Local File Cleanup**: Only delete local files after successful cloud upload
3. **Status Updates**: Ensure `google_drive_file_id` is set on successful upload
4. **Testing**: Test upload failure scenarios during development

### For System Administrators

1. **Cron Jobs**: Ensure Laravel scheduler is running (`* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1`)
2. **Queue Workers**: Keep queue workers running for background processing
3. **Storage Monitoring**: Monitor local storage usage for pending files
4. **Backup Strategy**: Include pending uploads in backup procedures

## API Integration

For programmatic access to pending uploads:

```php
// Get pending upload count
$pendingCount = FileUpload::pending()->count();

// Get pending uploads with details
$pendingUploads = FileUpload::pending()
    ->with(['clientUser', 'companyUser'])
    ->orderBy('created_at', 'asc')
    ->get();

// Process specific upload
$upload = FileUpload::find($id);
if ($upload->isPending()) {
    UploadToGoogleDrive::dispatch($upload);
}
```

## Security Considerations

- Only administrators can trigger manual processing
- Command requires appropriate permissions
- File access is validated before processing
- User permissions are checked for Google Drive access

## Performance Considerations

- Processing is limited to prevent system overload
- Background processing prevents UI blocking
- Overlap protection prevents duplicate processing
- Logging is optimized for performance monitoring