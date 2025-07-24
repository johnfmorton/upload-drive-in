# Client Recipient Selection Feature

## Overview

When a client has relationships with multiple company users (employees or admins), they can now select which specific user should receive their uploaded files. This ensures files are delivered to the correct person's Google Drive folder.

## How It Works

### Backend Implementation

1. **Database Structure**: The `client_user_relationships` table manages the many-to-many relationship between clients and company users
2. **Primary Relationship**: Each client has one "primary" company user (marked with `is_primary = true`)
3. **File Association**: Each `FileUpload` record stores both `client_user_id` and `company_user_id` to track who uploaded and who should receive the file

### Frontend Implementation

1. **Recipient Selection UI**: When a client has multiple company relationships, a dropdown appears on the upload page
2. **Default Selection**: The primary company user is selected by default
3. **Upload Parameter**: The selected `company_user_id` is sent with each file upload chunk
4. **Google Drive Storage**: Files are uploaded to the selected company user's Google Drive folder

## User Experience

### For Clients with Single Company User
- No change in experience
- Files automatically go to their single company contact
- No additional UI elements shown

### For Clients with Multiple Company Users
- Dropdown selector appears at the top of the upload page
- Shows all associated company users with names and emails
- Primary contact is pre-selected and marked as "Primary"
- Selection persists throughout the upload session

## Technical Details

### Controller Logic
```php
// Get the company user who should receive this upload
$companyUser = null;
if ($request->has('company_user_id')) {
    // If a specific company user was selected
    $companyUser = $user->companyUsers()
        ->where('users.id', $request->input('company_user_id'))
        ->first();
}

if (!$companyUser) {
    // Fall back to primary company user if none selected or selection invalid
    $companyUser = $user->primaryCompanyUser();
}
```

### JavaScript Integration
The Dropzone configuration includes the selected company user ID in upload parameters:
```javascript
params: function (files, xhr, chunk) {
    // ... chunk parameters ...
    
    // Add selected company user ID if available
    const companyUserSelect = document.getElementById('company_user_id');
    if (companyUserSelect && companyUserSelect.value) {
        params.company_user_id = companyUserSelect.value;
    }
    
    return params;
}
```

### Database Schema
```sql
-- FileUpload table includes both client and company user references
CREATE TABLE file_uploads (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    client_user_id BIGINT UNSIGNED NULL,
    company_user_id BIGINT UNSIGNED NULL,
    -- ... other fields
);

-- ClientUserRelationship manages the many-to-many relationship
CREATE TABLE client_user_relationships (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    client_user_id BIGINT UNSIGNED NOT NULL,
    company_user_id BIGINT UNSIGNED NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    -- ... timestamps
);
```

## Security Considerations

1. **Validation**: The controller validates that the selected company user actually has a relationship with the client
2. **Fallback**: If an invalid company_user_id is provided, the system falls back to the primary company user
3. **Google Drive Access**: Only company users with connected Google Drive accounts can receive files

## Error Handling

- If no valid company user is found: Returns error message "No valid upload destination found"
- If selected user has no Google Drive connection: Falls back to primary user or returns error
- If primary user also has no Google Drive: Upload fails with appropriate error message

## Future Enhancements

1. **Client Preferences**: Allow clients to set a default recipient preference
2. **Per-Upload Messages**: Different messages for different recipients
3. **Notification Routing**: Send notifications to the selected recipient instead of all company users
4. **Upload History**: Show which company user received each historical upload