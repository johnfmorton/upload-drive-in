# CLI Commands Documentation

This document provides comprehensive documentation for all custom CLI commands available in the Upload Drive-In Laravel application.

## Overview

The application includes several custom Artisan commands for managing file uploads, user administration, database operations, and system maintenance. All commands should be run using the DDEV environment.

### Command Execution Pattern

```bash
ddev artisan [command] [arguments] [options]
```

## File Upload Management Commands

### `uploads:clear-old`

**Purpose**: Clean up old uploaded files from local storage to prevent disk space issues.

**Signature**: `uploads:clear-old {--hours=24}`

**Description**: Removes uploaded files older than the specified number of hours from the `storage/app/public/uploads/` directory.

**Options**:

- `--hours=24` (optional): Number of hours to keep files (default: 24)

**Usage Examples**:

```bash
# Clear files older than 24 hours (default)
ddev artisan uploads:clear-old

# Clear files older than 48 hours
ddev artisan uploads:clear-old --hours=48

# Clear files older than 1 hour
ddev artisan uploads:clear-old --hours=1
```

**Behavior**:

- Logs deleted files to the `uploads_cleanup` log channel
- Provides console output showing which files were deleted
- Returns success/failure status codes
- Validates that hours parameter is positive

---

### `uploads:process-pending`

**Purpose**: Manually trigger processing of files that haven't been uploaded to Google Drive yet.

**Signature**: `uploads:process-pending`

**Description**: Finds all file uploads without a Google Drive file ID and dispatches background jobs to upload them to Google Drive.

**Usage**:

```bash
ddev artisan uploads:process-pending
```

**Behavior**:

- Queries `file_uploads` table for records missing `google_drive_file_id`
- Dispatches `UploadToGoogleDrive` job for each pending file
- Provides detailed logging and console output
- Handles exceptions gracefully for individual files

**Use Cases**:

- Recovery after queue worker failures
- Manual processing during development
- Batch processing of accumulated uploads

## User Administration Commands

### `user:set-role`

**Purpose**: Assign roles to users for access control and permissions management.

**Signature**: `user:set-role {email} {--role=admin}`

**Arguments**:

- `email` (required): Email address of the target user

**Options**:

- `--role=admin` (optional): Role to assign (default: admin)

**Available Roles**:

- `admin`: Full system access
- `employee`: Limited administrative access
- `client`: Basic user access

**Usage Examples**:

```bash
# Set user as admin (default role)
ddev artisan user:set-role user@example.com

# Set user as employee
ddev artisan user:set-role user@example.com --role=employee

# Set user as client
ddev artisan user:set-role user@example.com --role=client
```

**Error Handling**:

- Returns error if user email not found
- Provides confirmation of role assignment

---

### `users` / `users:list`

**Purpose**: List all user accounts in the database with their details.

**Signature**: `users {--format=table}`

**Aliases**: `users:list` (both commands work identically)

**Options**:

- `--format=table` (optional): Output format - table, json, or csv (default: table)

**Usage Examples**:

```bash
# List users in table format (default)
ddev artisan users
ddev artisan users:list

# List users in JSON format
ddev artisan users --format=json

# List users in CSV format
ddev artisan users --format=csv
```

**Displayed Information**:

- User ID
- Name
- Username (if available)
- Email address
- Role (Administrator, Client, Employee)
- Account creation date
- Total user count

**Output Formats**:

- **Table**: Human-readable formatted table (default)
- **JSON**: Machine-readable JSON with additional metadata
- **CSV**: Comma-separated values for spreadsheet import

---

### `admin:2fa-remove`

**Purpose**: Manage two-factor authentication settings for users who are locked out or need 2FA reset.

**Signature**: `admin:2fa-remove {email} {--show-only}`

**Arguments**:

- `email` (required): Email address of the user

**Options**:

- `--show-only` (optional): Display 2FA details without removing them

**Usage Examples**:

```bash
# Show 2FA details without removing
ddev artisan admin:2fa-remove user@example.com --show-only

# Remove 2FA after confirmation
ddev artisan admin:2fa-remove user@example.com
```

**Displayed Information**:

- 2FA secret key
- Recovery codes (if available)
- Confirmation prompt before removal

**Security Features**:

- Requires confirmation before removal
- Only works if 2FA is currently enabled
- Clears all 2FA-related data (secret, recovery codes, enabled flag)

## Database Management Commands

### `db:export-sqlite`

**Purpose**: Export data from SQLite database to JSON files for backup or migration purposes.

**Signature**: `db:export-sqlite`

**Description**: Exports all tables (except migrations and system tables) from SQLite database to individual JSON files.

**Usage**:

```bash
ddev artisan db:export-sqlite
```

**Behavior**:

- Temporarily switches database connection to SQLite
- Exports each table to `storage/app/private/db_export/[table_name].json`
- Creates export directory if it doesn't exist
- Restores original database connection after export
- Excludes system tables (`migrations`, `sqlite_sequence`)

**Output Location**: `storage/app/private/db_export/`

---

### `db:import-mariadb`

**Purpose**: Import JSON-exported data into MariaDB database.

**Signature**: `db:import-mariadb`

**Description**: Reads JSON files from the export directory and imports them into the current database connection.

**Usage**:

```bash
ddev artisan db:import-mariadb
```

**Behavior**:

- Reads JSON files from `storage/app/private/db_export/`
- Temporarily disables foreign key checks
- Truncates existing table data before import
- Imports data in chunks of 100 records
- Re-enables foreign key checks after import
- Supports both MySQL and SQLite target databases

**Prerequisites**:

- Must have previously run `db:export-sqlite`
- Export directory must exist with JSON files

## Built-in Laravel Commands

### `inspire`

**Purpose**: Display an inspiring quote for developer motivation.

**Signature**: `inspire`

**Usage**:

```bash
ddev artisan inspire
```

## Development Workflow Integration

### Queue Management

Several commands work with the Laravel queue system:

```bash
# Start queue worker for background jobs
ddev artisan queue:work

# View failed jobs
ddev artisan queue:failed

# Retry all failed jobs
ddev artisan queue:retry all

# Clear all jobs from queue
ddev artisan queue:clear
```

### Logging and Monitoring

Monitor command execution with Laravel Pail:

```bash
# Real-time log monitoring
ddev artisan pail

# Filter for specific channels
ddev artisan pail --filter="uploads_cleanup"
```

## Scheduled Commands

Currently, no commands are scheduled to run automatically. The `schedule` method in `app/Console/Kernel.php` is empty, but can be configured for automated execution:

```php
// Example scheduling (not currently active)
$schedule->command('uploads:clear-old')->daily();
$schedule->command('uploads:process-pending')->everyFiveMinutes();
```

## Error Handling and Return Codes

All custom commands follow Laravel conventions for return codes:

- `0` or `Command::SUCCESS`: Successful execution
- `1` or `Command::FAILURE`: Error occurred
- Commands provide descriptive error messages and logging

## Security Considerations

- **2FA Management**: `admin:2fa-remove` requires careful use as it affects user security
- **Database Operations**: Export/import commands handle sensitive data
- **File Cleanup**: `uploads:clear-old` permanently deletes files
- **User Roles**: Role assignment affects system permissions

## Best Practices

1. **Always use DDEV**: Run commands with `ddev artisan` prefix
2. **Monitor Logs**: Use `ddev artisan pail` to watch command execution
3. **Test First**: Use `--show-only` or dry-run options when available
4. **Backup Data**: Export data before running destructive operations
5. **Queue Workers**: Ensure queue workers are running for upload processing

## Troubleshooting

### Common Issues

**Queue Jobs Not Processing**:

```bash
# Check if queue worker is running
ddev artisan queue:work

# Check for failed jobs
ddev artisan queue:failed
```

**File Upload Issues**:

```bash
# Process pending uploads manually
ddev artisan uploads:process-pending

# Check storage permissions
ddev ssh
ls -la storage/app/public/uploads/
```

**Database Connection Issues**:

```bash
# Test database connection
ddev artisan tinker
>>> DB::connection()->getPdo();
```

**2FA Lockout**:

```bash
# Show 2FA details first
ddev artisan admin:2fa-remove user@example.com --show-only

# Remove if necessary
ddev artisan admin:2fa-remove user@example.com
```
