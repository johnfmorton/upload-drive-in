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

**Purpose**: Process pending file uploads that failed to upload to Google Drive due to connection issues or other temporary problems.

**Signature**: `uploads:process-pending {--user-id=} {--dry-run} {--limit=50}`

**Description**: Identifies files without a `google_drive_file_id` and re-queues them for Google Drive upload. Includes validation for local file existence and Google Drive connectivity.

**Options**:

- `--user-id=` (optional): Process pending uploads for a specific user ID only
- `--dry-run` (optional): Show what would be processed without actually processing
- `--limit=50` (optional): Maximum number of uploads to process (default: 50)

**Usage Examples**:

```bash
# Process all pending uploads (default limit: 50)
ddev artisan uploads:process-pending

# See what would be processed without actually processing
ddev artisan uploads:process-pending --dry-run

# Process uploads for a specific user
ddev artisan uploads:process-pending --user-id=123

# Process with custom limit
ddev artisan uploads:process-pending --limit=10

# Combine options
ddev artisan uploads:process-pending --user-id=123 --limit=5 --dry-run
```

**Behavior**:

- Queries `file_uploads` table for records missing `google_drive_file_id`
- Validates local files still exist in storage
- Determines appropriate user with Google Drive connection
- Dispatches `UploadToGoogleDrive` job for valid files
- Provides detailed console output with status indicators
- Logs all operations for monitoring

**Output Indicators**:

- ✅ Successfully queued for processing
- ⚠️ Skipped (missing connection, file issues)
- ❌ Error occurred
- 🔍 Dry run indicator

**User Priority for Google Drive**:

1. **Uploaded By User**: Employee who uploaded the file
2. **Company User**: Assigned company user for the upload
3. **Admin Fallback**: Any admin user with Google Drive connected

**Use Cases**:

- Recovery after Google Drive connection issues
- Processing uploads after service outages
- Manual intervention during development
- Batch processing of accumulated uploads

**Automatic Processing**: This command also runs automatically every 30 minutes via Laravel scheduler (see [Pending Uploads Documentation](docs/pending-uploads.md) for details).

## User Administration Commands

### `user:create`

**Purpose**: Create new users with specified roles and details.

**Signature**: `user:create {name} {email} {--role=admin} {--password=} {--owner=}`

**Arguments**:

- `name` (required): Full name of the user
- `email` (required): Email address (must be unique)

**Options**:

- `--role=client` (optional): User role (admin, employee, client) - defaults to client
- `--password=` (optional): Password (generated if not provided)
- `--owner=` (optional): Owner email for employee users (required for employees)

**Usage Examples**:

```bash
# Create client user (default role, uses token-based login)
ddev artisan user:create "Client User" client@example.com

# Create admin user with generated password
ddev artisan user:create "John Doe" john@example.com --role=admin

# Create employee with specific owner (owner required for employees)
ddev artisan user:create "Jane Smith" jane@example.com --role=employee --owner=admin@example.com

# Create user with specific password
ddev artisan user:create "User Name" user@example.com --role=admin --password=mypassword123
```

**Important**: Name and email are positional arguments (not options), so they must be provided in order without `--name` or `--email` flags.

**Behavior**:

- Validates email uniqueness and format
- Generates secure passwords if not provided
- Shows login URL for client users
- Sets appropriate default settings per role

---

### `user:list` / `users:list`

**Purpose**: List users with filtering options.

**Signatures**: 
- `user:list {--role=} {--owner=}`
- `users:list {--role=} {--owner=}`

**Aliases**: Both `user:list` and `users:list` work identically.

**Options**:

- `--role=` (optional): Filter by role (admin, employee, client)
- `--owner=` (optional): Filter by owner email (for employees)

**Usage Examples**:

```bash
# List all users (both commands work the same)
ddev artisan user:list
ddev artisan users:list

# List only admin users
ddev artisan user:list --role=admin
ddev artisan users:list --role=admin

# List employees of specific owner
ddev artisan user:list --role=employee --owner=admin@example.com
ddev artisan users:list --role=employee --owner=admin@example.com
```

**Displayed Information**:

- User ID, name, email
- Role and owner (if applicable)
- Creation date
- Total user count

---

### `user:show`

**Purpose**: Display detailed information about a specific user.

**Signature**: `user:show {email}`

**Arguments**:

- `email` (required): Email address of the user

**Usage Examples**:

```bash
# Show detailed user information
ddev artisan user:show user@example.com
```

**Displayed Information**:

- Basic details (ID, name, email, role, owner)
- Settings (notifications, 2FA status, Google Drive connection)
- Login URLs (for client users)
- Employee list (for admin users)

---

### `user:set-role`

**Purpose**: Assign roles to existing users for access control and permissions management.

**Signature**: `user:set-role {email} {--role=admin} {--owner=}`

**Arguments**:

- `email` (required): Email address of the target user

**Options**:

- `--role=admin` (optional): Role to assign (default: admin)
- `--owner=` (optional): Owner email for employee users (auto-assigns to first admin if not specified)

**Available Roles**:

- `admin`: Full system access
- `employee`: Limited administrative access
- `client`: Basic user access

**Usage Examples**:

```bash
# Set user as admin (default role)
ddev artisan user:set-role user@example.com

# Set user as employee with specific owner
ddev artisan user:set-role user@example.com --role=employee --owner=admin@example.com

# Set user as employee (auto-assigns to first admin)
ddev artisan user:set-role user@example.com --role=employee

# Set user as client
ddev artisan user:set-role user@example.com --role=client
```

**Behavior**:

- Automatically handles owner relationships for employees
- Clears owner relationship when changing from employee to other roles
- Updates notification settings based on role
- Auto-assigns to first admin if no owner specified for employees

**Error Handling**:

- Returns error if user email not found
- Validates that owner exists and is an admin
- Provides confirmation of role assignment and owner

---

### `user:delete`

**Purpose**: Delete users from the system.

**Signature**: `user:delete {email} {--force}`

**Arguments**:

- `email` (required): Email address of the user to delete

**Options**:

- `--force` (optional): Skip confirmation and handle employee relationships

**Usage Examples**:

```bash
# Delete user with confirmation
ddev artisan user:delete user@example.com

# Force delete user (removes employee relationships)
ddev artisan user:delete admin@example.com --force
```

**Behavior**:

- Shows user details before deletion
- Warns about employee relationships
- Requires confirmation unless forced
- Handles employee reassignment

---

### `user:reset-password`

**Purpose**: Reset user passwords for admin and employee users.

**Signature**: `user:reset-password {email} {--password=}`

**Arguments**:

- `email` (required): Email address of the user

**Options**:

- `--password=` (optional): New password (generated if not provided)

**Usage Examples**:

```bash
# Reset password with interactive prompt
ddev artisan user:reset-password user@example.com

# Reset with specific password
ddev artisan user:reset-password user@example.com --password=newpassword123
```

**Behavior**:

- Only works for users who can login with passwords
- Validates password length (minimum 8 characters)
- Generates secure passwords if not provided
- Warns about command-line password security

---

### `user:toggle-notifications`

**Purpose**: Enable or disable upload notifications for users.

**Signature**: `user:toggle-notifications {email} {--enable} {--disable}`

**Arguments**:

- `email` (required): Email address of the user

**Options**:

- `--enable` (optional): Enable notifications
- `--disable` (optional): Disable notifications

**Usage Examples**:

```bash
# Toggle current notification state
ddev artisan user:toggle-notifications user@example.com

# Explicitly enable notifications
ddev artisan user:toggle-notifications user@example.com --enable

# Explicitly disable notifications
ddev artisan user:toggle-notifications user@example.com --disable
```

---

### `user:login-url`

**Purpose**: Generate login URLs for users (primarily for clients).

**Signature**: `user:login-url {email}`

**Arguments**:

- `email` (required): Email address of the user

**Usage Examples**:

```bash
# Generate login URL for client
ddev artisan user:login-url client@example.com
```

**Behavior**:

- Generates temporary signed URL (valid for 7 days)
- Shows additional login options for password-enabled users
- Provides security information about URL validity

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

## Google Drive Integration Commands

### `google-drive:refresh-tokens`

**Purpose**: Proactively refresh Google Drive OAuth tokens to prevent expiration and maintain continuous access to Google Drive API.

**Signature**: `google-drive:refresh-tokens {--force} {--dry-run}`

**Description**: Automatically refreshes Google Drive access tokens that are expiring within 24 hours or have already expired. This prevents authentication failures during file uploads.

**Options**:

- `--force` (optional): Force refresh all tokens regardless of expiration status
- `--dry-run` (optional): Show what would be refreshed without actually performing the refresh

**Usage Examples**:

```bash
# Refresh tokens expiring within 24 hours (normal operation)
ddev artisan google-drive:refresh-tokens

# Force refresh all tokens regardless of expiration
ddev artisan google-drive:refresh-tokens --force

# Preview what would be refreshed without making changes
ddev artisan google-drive:refresh-tokens --dry-run

# Combine options for testing
ddev artisan google-drive:refresh-tokens --force --dry-run
```

**Behavior**:

- Checks all Google Drive tokens in the database
- Identifies tokens expiring within 24 hours or already expired
- Uses refresh tokens to obtain new access tokens from Google
- Updates token records with new expiration times
- Logs all operations for monitoring and debugging
- Sends email notifications to admin users if refresh fails

**Automatic Scheduling**:

- Runs every 6 hours via Laravel scheduler
- Additional daily run at 9:00 AM as backup
- Prevents manual intervention for token maintenance

**Error Handling**:

- Skips users without refresh tokens (requires re-authentication)
- Logs detailed error information for troubleshooting
- Sends notifications to admin users when failures occur
- Returns appropriate exit codes for monitoring systems

**Monitoring**:

- All operations logged to `storage/logs/laravel.log`
- Scheduled runs also logged to `storage/logs/token-refresh.log`
- Email notifications sent to admin users on failures

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

The following commands are scheduled to run automatically via Laravel's task scheduler:

### Google Drive Token Refresh

```php
// Defined in app/Console/Kernel.php
$schedule->command('google-drive:refresh-tokens')->everySixHours();
$schedule->command('google-drive:refresh-tokens')->dailyAt('09:00');
```

### Manual Token Refresh

You can also run the token refresh command manually:

```bash
# Refresh tokens that are expiring within 24 hours
ddev artisan google-drive:refresh-tokens

# Force refresh all tokens regardless of expiration
ddev artisan google-drive:refresh-tokens --force

# Dry run to see what would be refreshed without actually doing it
ddev artisan google-drive:refresh-tokens --dry-run
```

## Laravel Scheduler Setup

**Important**: The `schedule()` method in `app/Console/Kernel.php` only **defines** when commands should run. To actually execute scheduled commands, you need to set up a cron job or daemon.

### Development Environment (DDEV)

#### Option 1: DDEV Cron Configuration (Recommended)

Add to your `.ddev/config.yaml`:

```yaml
web_extra_daemons:
  - name: "laravel-scheduler"
    command: "/var/www/html/artisan schedule:work"
    directory: /var/www/html
```

Then restart DDEV:

```bash
ddev restart
```

#### Option 2: Manual Scheduler Commands

```bash
# Run scheduler once (checks what should run now)
ddev artisan schedule:run

# Run scheduler continuously (like a daemon)
ddev artisan schedule:work

# List all scheduled commands
ddev artisan schedule:list
```

### Production Environment

#### Standard Server Setup

Add this single cron job to your server (runs every minute):

```bash
# Edit crontab
crontab -e

# Add this line (adjust path to your project)
* * * * * cd /path/to/your/laravel/project && php artisan schedule:run >> /dev/null 2>&1
```

#### Laravel Forge Setup

Laravel Forge automatically handles scheduler setup:

1. **Automatic Configuration**: Forge automatically adds the required cron job when you deploy a Laravel application
2. **Scheduler Tab**: View and manage scheduled tasks in the Forge dashboard under your site's "Scheduler" tab
3. **Manual Override**: You can disable automatic scheduling and add custom cron jobs if needed

**Forge Cron Job** (automatically added):

```bash
* * * * * /usr/bin/php8.3 /home/forge/your-site.com/artisan schedule:run >> /dev/null 2>&1
```

**Forge Dashboard Features**:

- View scheduled command history
- Enable/disable the scheduler
- Monitor command execution logs
- Set up custom cron jobs if needed

#### Other Hosting Platforms

**Vapor (Serverless)**:

```bash
# Vapor automatically handles scheduling via CloudWatch Events
# No additional configuration needed
```

**Shared Hosting**:

```bash
# Add via cPanel or hosting control panel
* * * * * /usr/local/bin/php /path/to/your/project/artisan schedule:run
```

### Monitoring Scheduled Commands

#### Check Scheduler Status

```bash
# List all scheduled commands and their next run times
ddev artisan schedule:list

# Test scheduler without waiting
ddev artisan schedule:run

# Run scheduler in foreground (useful for debugging)
ddev artisan schedule:work --verbose
```

#### Logging

Scheduled commands log to multiple locations:

- **Laravel Log**: `storage/logs/laravel.log`
- **Custom Log**: `storage/logs/token-refresh.log` (for token refresh)
- **Cron Log**: System cron logs (varies by server)

#### Laravel Forge Monitoring

- **Dashboard**: View command execution history
- **Notifications**: Set up alerts for failed scheduled commands
- **Logs**: Access scheduler logs directly from Forge interface

### Troubleshooting Scheduler Issues

#### Common Problems

1. **Commands not running**: Check if cron job is properly configured
2. **Permission errors**: Ensure web server user can execute artisan
3. **Path issues**: Use absolute paths in cron jobs
4. **Timezone problems**: Set correct timezone in `config/app.php`

#### Debug Commands

```bash
# Verify scheduler configuration
ddev artisan schedule:list

# Run scheduler manually to test
ddev artisan schedule:run --verbose

# Check if specific command works
ddev artisan google-drive:refresh-tokens --dry-run
```

#### Laravel Forge Debugging

- Check "Scheduler" tab for execution history
- Review site logs for scheduler errors
- Verify PHP version matches your application requirements
- Ensure queue workers are running if commands use queues

### Example Scheduling (Available but not currently active)

```php
// Additional commands that could be scheduled
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
