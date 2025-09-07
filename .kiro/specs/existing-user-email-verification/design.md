# Design Document

## Overview

This design modifies the existing email verification flow in `PublicUploadController` to prioritize existing user detection over security restrictions. The solution ensures that existing users can always access their accounts through the public email form, regardless of domain restrictions or public registration settings, while maintaining security for new user registration attempts.

## Architecture

### Current State Analysis

The current `PublicUploadController::validateEmail()` method applies security checks in this order:

1. Basic email validation
2. Check if public registration is allowed
3. Check if email domain is allowed
4. Check if user already exists (for template selection)
5. Send verification email

This order causes existing users to be blocked by security restrictions before the system can determine they are existing users.

### Proposed Architecture

The new flow will reorder the checks to prioritize existing user detection:

1. Basic email validation
2. **Check if user already exists** (moved up)
3. **If existing user**: Bypass all registration restrictions
4. **If new user**: Apply public registration and domain restrictions
5. Send appropriate verification email

```
Email Verification Flow (Enhanced)
├── Basic Validation (email format, required fields)
├── Existing User Detection
│   ├── User Found → Bypass all restrictions → Send role-based email
│   └── User Not Found → Apply registration restrictions
│       ├── Public registration disabled → Reject
│       ├── Domain not allowed → Reject
│       └── All checks pass → Send client email (new user)
└── Verification Email Sent
```

## Components and Interfaces

### 1. Enhanced PublicUploadController

#### Modified validateEmail() Method

```php
public function validateEmail(Request $request)
{
    // Step 1: Basic validation
    $validated = $request->validate([
        'email' => ['required', 'string', 'email', 'max:255'],
        'intended_url' => ['nullable', 'string', 'url'],
    ]);

    $email = $validated['email'];
    
    // Step 2: Check for existing user FIRST
    $existingUser = \App\Models\User::where('email', $email)->first();
    
    if ($existingUser) {
        // Existing user - bypass all registration restrictions
        return $this->sendVerificationEmailToExistingUser($existingUser, $email, $validated);
    } else {
        // New user - apply all registration restrictions
        return $this->handleNewUserRegistration($email, $validated);
    }
}
```

#### New Helper Methods

```php
private function sendVerificationEmailToExistingUser(User $user, string $email, array $validated): JsonResponse
{
    Log::info('Existing user email verification', [
        'email' => $email,
        'user_id' => $user->id,
        'role' => $user->role->value,
        'restrictions_bypassed' => true
    ]);

    // Store intended URL if provided
    if (!empty($validated['intended_url'])) {
        session(['intended_url' => $validated['intended_url']]);
    }

    // Create verification record and send email
    return $this->createVerificationAndSendEmail($email, $user);
}

private function handleNewUserRegistration(string $email, array $validated): JsonResponse
{
    // Apply existing security checks for new users
    $domainRules = DomainAccessRule::first();
    
    // Check public registration setting
    if ($domainRules && !$domainRules->allow_public_registration) {
        Log::warning('Public registration attempt when disabled', [
            'email' => $email,
            'user_exists' => false
        ]);
        throw ValidationException::withMessages([
            'email' => [__('messages.public_registration_disabled')],
        ]);
    }

    // Check domain restrictions
    if ($domainRules && !$domainRules->isEmailAllowed($email)) {
        Log::warning('Email domain not allowed for new user', [
            'email' => $email,
            'mode' => $domainRules->mode,
            'user_exists' => false
        ]);
        throw ValidationException::withMessages([
            'email' => [__('messages.email_domain_not_allowed')],
        ]);
    }

    Log::info('New user registration allowed', [
        'email' => $email,
        'user_exists' => false
    ]);

    // Store intended URL if provided
    if (!empty($validated['intended_url'])) {
        session(['intended_url' => $validated['intended_url']]);
    }

    // Create verification record and send email (no existing user)
    return $this->createVerificationAndSendEmail($email, null);
}
```

### 2. Enhanced Logging Strategy

#### Existing User Detection Logging

```php
// When existing user is found
Log::info('Existing user bypassing registration restrictions', [
    'email' => $email,
    'user_id' => $existingUser->id,
    'user_role' => $existingUser->role->value,
    'public_registration_enabled' => $domainRules?->allow_public_registration ?? true,
    'domain_restrictions_mode' => $domainRules?->mode ?? 'none',
    'restrictions_bypassed' => true,
    'context' => 'existing_user_login'
]);

// When new user faces restrictions
Log::warning('New user blocked by registration restrictions', [
    'email' => $email,
    'user_exists' => false,
    'restriction_type' => 'public_registration_disabled', // or 'domain_not_allowed'
    'domain_rules_mode' => $domainRules?->mode,
    'context' => 'new_user_registration'
]);
```

### 3. Error Message Enhancement

#### New Language Keys

```php
// resources/lang/en/messages.php

// Enhanced error messages that distinguish between new and existing users
'public_registration_disabled' => 'New user registration is currently disabled. If you already have an account, please try again or contact support.',
'email_domain_not_allowed' => 'This email domain is not allowed for new registrations. If you already have an account, please try again or contact support.',

// Success messages for existing users
'existing_user_verification_sent' => 'Verification email sent to your existing account. Please check your inbox.',
'new_user_verification_sent' => 'Verification email sent. Please check your inbox to complete registration.',
```

## Data Models

### Enhanced EmailValidation Model

No changes needed to the model structure, but the creation logic will include additional context:

```php
$validation = EmailValidation::updateOrCreate(
    ['email' => $email],
    [
        'verification_code' => $verificationCode,
        'expires_at' => now()->addHours(24),
        // Additional context for debugging
        'created_for_existing_user' => $existingUser !== null,
        'user_role_at_creation' => $existingUser?->role?->value,
    ]
);
```

Note: This would require a migration to add the new fields, or we can track this information in logs only.

## Error Handling

### Fallback Strategy

1. **Primary**: Check for existing user first, bypass restrictions if found
2. **Secondary**: If user lookup fails, log error but continue with restriction checks
3. **Tertiary**: If all else fails, apply most restrictive security settings

### Error Scenarios

#### Database Connection Issues
```php
try {
    $existingUser = \App\Models\User::where('email', $email)->first();
} catch (\Exception $e) {
    Log::error('Failed to check for existing user', [
        'email' => $email,
        'error' => $e->getMessage()
    ]);
    // Fall back to treating as new user (apply all restrictions)
    $existingUser = null;
}
```

#### Domain Rules Configuration Issues
```php
try {
    $domainRules = DomainAccessRule::first();
} catch (\Exception $e) {
    Log::error('Failed to load domain rules', [
        'error' => $e->getMessage()
    ]);
    // Fall back to allowing the request (fail open for existing users)
    if ($existingUser) {
        // Allow existing users through
        return $this->sendVerificationEmailToExistingUser($existingUser, $email, $validated);
    } else {
        // For new users, fail closed (reject)
        throw ValidationException::withMessages([
            'email' => ['Unable to process registration at this time. Please try again later.'],
        ]);
    }
}
```

## Testing Strategy

### Unit Tests

#### Existing User Detection Tests
```php
public function test_existing_admin_bypasses_public_registration_disabled()
{
    // Create admin user
    $admin = User::factory()->admin()->create(['email' => 'admin@test.com']);
    
    // Disable public registration
    DomainAccessRule::create([
        'mode' => 'whitelist',
        'rules' => ['allowed.com'],
        'allow_public_registration' => false
    ]);
    
    // Test that admin can still get verification email
    $response = $this->postJson('/validate-email', ['email' => 'admin@test.com']);
    
    $response->assertOk();
    $response->assertJson(['success' => true]);
    Mail::assertSent(AdminVerificationMail::class);
}

public function test_existing_user_bypasses_domain_restrictions()
{
    // Create user with non-whitelisted domain
    $user = User::factory()->client()->create(['email' => 'user@blocked.com']);
    
    // Set up whitelist that doesn't include user's domain
    DomainAccessRule::create([
        'mode' => 'whitelist',
        'rules' => ['allowed.com'],
        'allow_public_registration' => true
    ]);
    
    // Test that existing user can still get verification email
    $response = $this->postJson('/validate-email', ['email' => 'user@blocked.com']);
    
    $response->assertOk();
    Mail::assertSent(ClientVerificationMail::class);
}

public function test_new_user_blocked_by_restrictions()
{
    // Disable public registration
    DomainAccessRule::create([
        'mode' => 'whitelist',
        'rules' => ['allowed.com'],
        'allow_public_registration' => false
    ]);
    
    // Test that new user is blocked
    $response = $this->postJson('/validate-email', ['email' => 'newuser@test.com']);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
    Mail::assertNothingSent();
}
```

### Integration Tests

#### Complete Flow Tests
```php
public function test_existing_user_complete_verification_flow()
{
    $employee = User::factory()->employee()->create(['email' => 'emp@test.com']);
    
    // Disable public registration to test bypass
    DomainAccessRule::create(['allow_public_registration' => false]);
    
    // Step 1: Request verification
    $response = $this->postJson('/validate-email', ['email' => 'emp@test.com']);
    $response->assertOk();
    
    // Step 2: Get verification link from email
    Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use (&$verificationUrl) {
        $verificationUrl = $mail->verificationUrl;
        return true;
    });
    
    // Step 3: Click verification link
    $response = $this->get($verificationUrl);
    $response->assertRedirect('/employee/' . $employee->username . '/dashboard');
    
    // Step 4: Verify user is logged in
    $this->assertAuthenticatedAs($employee);
}
```

## Migration Strategy

### Phase 1: Code Changes
1. Modify `PublicUploadController::validateEmail()` method
2. Add new helper methods for existing vs new user handling
3. Enhance logging throughout the flow

### Phase 2: Testing
1. Add comprehensive unit tests for all scenarios
2. Add integration tests for complete flows
3. Test edge cases (database errors, missing domain rules)

### Phase 3: Deployment
1. Deploy changes with feature flag if needed
2. Monitor logs for existing user bypass events
3. Verify no legitimate users are being blocked

### Backward Compatibility

- No breaking changes to existing API endpoints
- No changes to verification URL structure
- No changes to email template selection logic
- Enhanced logging provides better debugging without changing behavior

## Security Considerations

### Security Benefits
- Existing users can always access their accounts
- No reduction in security for new user registration
- Better user experience without compromising security posture

### Security Risks and Mitigations
- **Risk**: Attackers could probe for existing email addresses
- **Mitigation**: Log all attempts and implement rate limiting
- **Risk**: Bypassing domain restrictions could be seen as a security hole
- **Mitigation**: This only applies to users who already have accounts, so no new access is granted

### Audit Trail
All existing user bypass events will be logged with:
- User ID and role
- Restrictions that were bypassed
- Timestamp and IP address
- Success/failure of email sending

This provides a complete audit trail for security review.