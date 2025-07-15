# Google Drive Integration Context

This Laravel application is a client file intake system that integrates with Google Drive for cloud storage.

## Project Architecture

### Core Purpose
- **Primary Goal**: Enable businesses to receive files from clients directly into their Google Drive accounts
- **Current Phase**: MVP for single user (sole proprietor use)
- **Future Vision**: Multi-tenant SaaS platform

### Key Components
- **Public Upload System**: Email-validated file uploads with token-based authentication
- **Google Drive Integration**: OAuth 2.0 authentication with automatic folder organization
- **Admin Dashboard**: File management and upload monitoring
- **Queue System**: Background processing for cloud uploads

## Google Drive Integration Patterns

### Service Architecture
- **GoogleDriveService**: Main service class for API interactions
- **GoogleDriveManager**: Higher-level management operations
- **GoogleDriveToken Model**: OAuth token storage and management
- **UploadToGoogleDrive Job**: Queued background uploads

### Authentication Flow
- OAuth 2.0 with offline access and consent prompt
- Tokens stored per user with refresh capability
- Scopes: `drive.file` and `drive` for full access
- Redirect URI: `/admin/cloud-storage/google-drive/callback`

### File Organization
- Root folder ID configured via `GOOGLE_DRIVE_ROOT_FOLDER_ID`
- Subfolders created per client email address
- Metadata stored locally with Google Drive file IDs

## Environment Configuration

### Required Google Drive Variables
```env
GOOGLE_DRIVE_CLIENT_ID=
GOOGLE_DRIVE_CLIENT_SECRET=
GOOGLE_DRIVE_REDIRECT_URI=
GOOGLE_DRIVE_ROOT_FOLDER_ID=
CLOUD_STORAGE_DEFAULT=google-drive
```

### Multi-Provider Support
- Google Drive (primary)
- Microsoft Teams (planned)
- Dropbox (planned)

## Development Guidelines

### When Working with Google Drive Features
- Always use the service classes rather than direct API calls
- Handle token refresh automatically in service methods
- Queue file uploads using `UploadToGoogleDrive` job
- Store metadata locally before cloud upload
- Implement proper error handling and retry logic

### File Upload Workflow
1. Client uploads via public form with email validation
2. Files stored temporarily in local storage
3. Metadata saved to `file_uploads` table
4. Background job uploads to Google Drive
5. Local cleanup after successful cloud upload

### Testing Considerations
- Mock Google API client for unit tests
- Use test Google Drive folder for integration tests
- Verify token refresh mechanisms
- Test queue job failure scenarios