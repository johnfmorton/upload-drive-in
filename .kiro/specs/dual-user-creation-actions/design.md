# Design Document

## Overview

This design enhances the existing user management system by providing both admin and employee users with dual client creation options. The solution maintains backward compatibility while extending functionality to offer both "Create User" (without invitation) and "Create & Send Invitation" (with automatic email) actions to both user types.

## Architecture

### Current State Analysis

**Admin Users:**
- Route: `/admin/users`
- Controller: `AdminUserController@store`
- View: `resources/views/admin/users/index.blade.php`
- Current behavior: Creates user without sending invitation

**Employee Users:**
- Route: `/employee/{username}/clients`
- Controller: `ClientManagementController@store`
- View: `resources/views/employee/client-management/index.blade.php`
- Current behavior: Creates user and sends invitation email

### Proposed Architecture

The design will extend both controllers to support a new `action` parameter that determines whether to send an invitation email. This approach maintains existing functionality while adding the new capability.

## Components and Interfaces

### 1. Controller Enhancements

#### AdminUserController Modifications
```php
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|lowercase|email|max:255',
        'action' => 'required|in:create,create_and_invite'
    ]);

    $clientUser = $this->clientUserService->findOrCreateClientUser($validated, Auth::user());
    
    if ($validated['action'] === 'create_and_invite') {
        $this->sendInvitationEmail($clientUser);
        $message = 'Client user created and invitation sent successfully.';
    } else {
        $message = 'Client user created successfully. You can provide them with their login link manually.';
    }
    
    return redirect()->route('admin.users.index')->with('success', $message);
}

private function sendInvitationEmail(User $clientUser)
{
    $loginUrl = URL::temporarySignedRoute(
        'login.via.token',
        now()->addDays(7),
        ['user' => $clientUser->id]
    );
    
    Mail::to($clientUser->email)->send(new LoginVerificationMail($loginUrl));
}
```

#### ClientManagementController Modifications
```php
public function store(Request $request, ClientUserService $clientUserService)
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255'],
        'action' => ['required', 'in:create,create_and_invite']
    ]);

    $clientUser = $clientUserService->findOrCreateClientUser($validated, Auth::user());

    if ($validated['action'] === 'create_and_invite') {
        $loginUrl = URL::temporarySignedRoute(
            'login.via.token',
            now()->addDays(7),
            ['user' => $clientUser->id]
        );
        
        Mail::to($clientUser->email)->send(new LoginVerificationMail($loginUrl));
        $status = 'client-created-and-invited';
    } else {
        $status = 'client-created';
    }

    return back()->with('status', $status);
}
```

### 2. Frontend Interface Design

#### Dual Button Approach
Both admin and employee interfaces will feature two distinct buttons:

1. **Create User Button**
   - Primary styling with neutral color
   - Icon: User plus icon
   - Tooltip: "Create user account without sending invitation email"

2. **Create & Send Invitation Button**
   - Secondary styling with accent color
   - Icon: Mail send icon
   - Tooltip: "Create user account and automatically send invitation email"

#### Form Enhancement
The existing form will be enhanced with:
- Hidden input field for `action` parameter
- JavaScript to set the action value based on button clicked
- Improved visual hierarchy and spacing
- Consistent styling across both user types

### 3. User Experience Flow

#### Admin User Flow
1. Admin navigates to `/admin/users`
2. Admin fills out name and email fields
3. Admin chooses between:
   - "Create User" → User created, no email sent
   - "Create & Send Invitation" → User created, invitation email sent
4. Success message indicates which action was performed

#### Employee User Flow
1. Employee navigates to `/employee/{username}/clients`
2. Employee fills out name and email fields
3. Employee chooses between:
   - "Create User" → User created, no email sent
   - "Create & Send Invitation" → User created, invitation email sent
4. Success message indicates which action was performed

## Data Models

No changes to existing data models are required. The enhancement uses existing:
- `User` model for client creation
- `ClientUserRelationship` model for associations
- Existing email templates and services

## Error Handling

### Validation Errors
- Form validation remains unchanged for name and email fields
- New validation for `action` parameter ensures only valid values are accepted
- Client-side validation prevents form submission without action selection

### Email Sending Errors
- If invitation email fails to send, user creation should still succeed
- Error message should indicate partial success: "User created successfully, but invitation email failed to send"
- Email failures should be logged for administrative review

### Duplicate User Handling
- Existing duplicate handling logic in `ClientUserService` remains unchanged
- Success messages should reflect whether user was created or already existed

## Testing Strategy

### Unit Tests
- Test both controller methods with different action parameters
- Test email sending functionality in isolation
- Test validation rules for new action parameter

### Integration Tests
- Test complete user creation flow for both actions
- Test email queue integration for invitation sending
- Test error handling scenarios

### Frontend Tests
- Test button click behavior and form submission
- Test JavaScript action parameter setting
- Test responsive design on mobile devices

### User Acceptance Tests
- Test admin user workflow with both creation methods
- Test employee user workflow with both creation methods
- Test success/error message display and clarity

## Security Considerations

### Input Validation
- Strict validation of action parameter to prevent injection
- Existing email and name validation remains in place
- CSRF protection maintained for all form submissions

### Authorization
- Existing role-based access controls remain unchanged
- Both user types maintain their current permission levels
- No elevation of privileges introduced

### Email Security
- Existing signed URL generation for login tokens
- Temporary URLs with appropriate expiration times
- No sensitive data exposed in email content

## Performance Considerations

### Email Queue
- Invitation emails should be queued for background processing
- No blocking of user creation process due to email sending
- Appropriate retry logic for failed email deliveries

### Database Impact
- No additional database queries required
- Existing indexing and relationships sufficient
- No performance degradation expected

## Backward Compatibility

### API Compatibility
- Existing endpoints remain functional without action parameter
- Default behavior maintains current functionality if action not specified
- No breaking changes to existing integrations

### URL Structure
- No changes to existing route definitions
- Form submission targets remain the same
- Existing bookmarks and links continue to work