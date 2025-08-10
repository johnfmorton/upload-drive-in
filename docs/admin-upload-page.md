# Admin Personal Upload Page

This document describes the personal upload page feature for admin users.

## Overview

Admin users now have access to personal upload pages, similar to employees. This allows admin users to share a direct upload URL with clients, enabling them to upload files directly to the admin's account.

## Features

### Personal Upload URL
- Admin users get a personal upload URL in the format: `https://your-domain.com/upload/{name}`
- The `{name}` is extracted from the admin's email address (everything before the @ symbol)
- Example: If admin email is `admin@company.com`, the upload URL will be `/upload/admin`

### Dashboard Integration
- The admin dashboard now displays the personal upload URL
- Includes a "Copy URL" button for easy sharing
- Includes a "Visit Page" button to preview the upload page

### File Upload Handling
- Files uploaded through the admin's personal page are tracked with `uploaded_by_user_id` set to the admin's ID
- Uses the same validation and processing as employee uploads
- Files are queued for Google Drive upload using the existing job system

## Usage

### For Admin Users
1. Log into the admin dashboard at `/admin/dashboard`
2. Find the "Personal Upload Page" section
3. Copy the provided URL and share it with clients
4. Monitor uploaded files through the file manager

### For Clients
1. Visit the admin's personal upload URL
2. Provide email address for identification
3. Upload files and optionally include a message
4. Files are processed and uploaded to the admin's Google Drive

## Technical Implementation

### Controller Changes
- Modified `PublicEmployeeUploadController` to support both employees and admin users
- Updated role checks to include `UserRole::ADMIN` alongside `UserRole::EMPLOYEE`
- All existing employee functionality remains unchanged

### Model Changes
- Added `getUploadUrl()` method to User model
- Added `upload_url` accessor for easy access in views
- Method returns `null` for client users (no upload capability)

### Route Compatibility
- Existing routes at `/upload/{name}` now work for both employees and admins
- No new routes required - leverages existing infrastructure

### Database
- No database changes required
- Uses existing `file_uploads` table structure
- `uploaded_by_user_id` field tracks which admin/employee received the upload

## Security Considerations

- Upload pages are public (no authentication required for uploaders)
- Email validation is required for all uploads
- File size limits and type restrictions apply
- Rate limiting is handled by existing middleware
- Google Drive integration requires proper OAuth setup

## Testing

Comprehensive tests have been added to verify:
- Admin users can generate upload URLs
- Admin upload pages are accessible
- File uploads work correctly for admin users
- Employee functionality remains unaffected
- Client users cannot access upload functionality

## Configuration

No additional configuration is required. The feature uses existing:
- Google Drive integration settings
- File upload validation rules
- Queue system configuration
- Email notification settings

## Backwards Compatibility

This feature is fully backwards compatible:
- All existing employee upload functionality is preserved
- No breaking changes to existing URLs or APIs
- Existing client workflows are unaffected