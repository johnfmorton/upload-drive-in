# Email Validation Redirect Behavior

This document explains how email validation redirects work for different upload page types.

## Overview

The system supports three types of upload pages:
1. **Generic Upload Page** (`/`) - General file upload page
2. **Employee Upload Pages** (`/upload/{employee_name}`) - Employee-specific upload pages
3. **Admin Upload Pages** (`/upload/{admin_name}`) - Admin-specific upload pages

When users visit these pages without being authenticated, they see an email validation form. The redirect behavior after email verification depends on which page they originally visited.

## Redirect Behavior

### Employee/Admin Specific Pages

When users visit an employee or admin specific upload page (e.g., `/upload/john` or `/upload/admin`):

1. **Email Validation Form**: Shows a form asking for email address with a hidden `intended_url` field containing the original page URL
2. **Email Submission**: The `intended_url` is stored in the session
3. **Email Verification**: After clicking the verification link, users are redirected back to the original employee/admin specific page
4. **Result**: Users can upload files directly to that specific employee/admin

**Example Flow:**
```
User visits: /upload/john
↓
Email validation form (with intended_url = /upload/john)
↓
User enters email and submits
↓
Verification email sent
↓
User clicks verification link
↓
User redirected back to: /upload/john
↓
User can now upload files for John
```

### Generic Upload Page

When users visit the generic upload page (`/`):

1. **Email Validation Form**: Shows a form asking for email address (no `intended_url` field)
2. **Email Submission**: No intended URL is stored
3. **Email Verification**: After clicking the verification link, users are redirected based on their user role:
   - **New users (clients)**: Redirected to client upload page
   - **Existing employees**: Redirected to their employee dashboard
   - **Existing admins**: Redirected to admin dashboard
4. **Result**: Users are directed to their appropriate dashboard/upload area

**Example Flow:**
```
User visits: /
↓
Email validation form (no intended_url)
↓
User enters email and submits
↓
Verification email sent
↓
User clicks verification link
↓
User redirected based on role:
  - New client → /client/upload-files
  - Existing employee → /employee/{username}/dashboard
  - Existing admin → /admin/dashboard
```

## Technical Implementation

### Form Fields

**Employee/Admin Pages** (`resources/views/public-employee/email-validation.blade.php`):
```html
<form id="emailValidationForm">
    @csrf
    <input type="hidden" name="intended_url" value="{{ request()->url() }}">
    <input type="email" name="email" required>
    <!-- ... -->
</form>
```

**Generic Page** (`resources/views/email-validation-form.blade.php`):
```html
<form id="emailValidationForm">
    @csrf
    <input type="email" name="email" required>
    <!-- No intended_url field -->
</form>
```

### Controller Logic

**Email Validation** (`PublicUploadController::validateEmail()`):
```php
$validated = $request->validate([
    'email' => ['required', 'string', 'email', 'max:255'],
    'intended_url' => ['nullable', 'string', 'url'],
]);

// Store intended URL if provided
if (!empty($validated['intended_url'])) {
    session(['intended_url' => $validated['intended_url']]);
}
```

**Email Verification** (`PublicUploadController::verifyEmail()`):
```php
// Check if there's an intended URL in the session
$intendedUrl = session('intended_url');
if ($intendedUrl) {
    session()->forget('intended_url');
    return redirect($intendedUrl);
}

// Default redirect based on user role
if ($user->isAdmin()) {
    return redirect()->route('admin.dashboard');
} elseif ($user->isEmployee()) {
    return redirect()->route('employee.dashboard', ['username' => $user->username]);
} else {
    return redirect()->route('client.upload-files');
}
```

## Security Considerations

- The `intended_url` field is validated as a proper URL
- Only valid URLs are stored in the session
- Session data is automatically cleaned up after redirect
- Users can only be redirected to URLs within the application domain

## Testing

Comprehensive tests verify:
- Employee pages redirect back to employee pages
- Admin pages redirect back to admin pages  
- Generic page redirects to appropriate dashboards
- Invalid users return 404 errors
- Client users cannot have upload pages
- Authenticated users bypass email validation

## User Experience

This behavior ensures that:
1. **Clients uploading to specific employees/admins** are returned to the correct upload page
2. **General users** are directed to their appropriate workspace
3. **No confusion** about which upload page they were trying to access
4. **Seamless experience** for both specific and general upload flows