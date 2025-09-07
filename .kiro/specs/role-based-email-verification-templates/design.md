# Design Document

## Overview

This design implements a role-based email verification system that provides customized messaging for admin, employee, and client users. The solution extends the existing email verification infrastructure while maintaining backward compatibility and following Laravel best practices.

## Architecture

### Current State Analysis

The current system uses two Mail classes but only one is actively used:
- `LoginVerificationMail` (actively used) → `emails.login-verification-markdown`
- `EmailVerificationMail` (unused) → `emails.email-verification`

Both templates are nearly identical and use the same language keys, creating redundancy without differentiation.

### Proposed Architecture

```
Mail Classes (Enhanced)
├── LoginVerificationMail (enhanced with role detection)
├── AdminVerificationMail (new)
├── EmployeeVerificationMail (new)
└── ClientVerificationMail (new)

Templates
├── emails/verification/admin-verification.blade.php
├── emails/verification/employee-verification.blade.php
└── emails/verification/client-verification.blade.php

Language Keys (Enhanced)
├── admin_verify_* (new keys)
├── employee_verify_* (new keys)
└── client_verify_* (enhanced existing keys)
```

## Components and Interfaces

### 1. Mail Class Hierarchy

#### Base Verification Mail (Abstract)
```php
abstract class BaseVerificationMail extends Mailable
{
    public $verificationUrl;
    public $userRole;
    public $companyName;
    
    abstract protected function getTemplate(): string;
    abstract protected function getSubject(): string;
}
```

#### Role-Specific Mail Classes
```php
class AdminVerificationMail extends BaseVerificationMail
{
    protected function getTemplate(): string 
    {
        return 'emails.verification.admin-verification';
    }
    
    protected function getSubject(): string 
    {
        return __('messages.admin_verify_email_subject');
    }
}

class EmployeeVerificationMail extends BaseVerificationMail
{
    protected function getTemplate(): string 
    {
        return 'emails.verification.employee-verification';
    }
    
    protected function getSubject(): string 
    {
        return __('messages.employee_verify_email_subject');
    }
}

class ClientVerificationMail extends BaseVerificationMail
{
    protected function getTemplate(): string 
    {
        return 'emails.verification.client-verification';
    }
    
    protected function getSubject(): string 
    {
        return __('messages.client_verify_email_subject');
    }
}
```

### 2. Mail Factory Service

```php
class VerificationMailFactory
{
    public function createForUser(?User $user, string $verificationUrl): Mailable
    {
        if ($user && $user->isAdmin()) {
            return new AdminVerificationMail($verificationUrl);
        }
        
        if ($user && $user->isEmployee()) {
            return new EmployeeVerificationMail($verificationUrl);
        }
        
        // Default to client verification for public users or clients
        return new ClientVerificationMail($verificationUrl);
    }
    
    public function createForContext(string $context, string $verificationUrl): Mailable
    {
        return match($context) {
            'admin' => new AdminVerificationMail($verificationUrl),
            'employee' => new EmployeeVerificationMail($verificationUrl),
            'client' => new ClientVerificationMail($verificationUrl),
            default => new ClientVerificationMail($verificationUrl)
        };
    }
}
```

### 3. Template Structure

Each template will follow this consistent structure:
```blade
<x-mail::message>
# {{ __('messages.{role}_verify_email_title') }}

{{ __('messages.{role}_verify_email_intro', ['company_name' => config('app.company_name')]) }}

<x-mail::button :url="$verificationUrl">
{{ __('messages.{role}_verify_email_button') }}
</x-mail::button>

{{ __('messages.verify_email_ignore') }}

{{ __('messages.thanks_signature') }},<br>
{{ config('app.name') }}
</x-mail::message>
```

## Data Models

### Language File Structure

```php
// resources/lang/en/messages.php

// Admin Verification
'admin_verify_email_subject' => 'Verify Your Administrator Email Address',
'admin_verify_email_title' => 'Verify Your Administrator Email Address',
'admin_verify_email_intro' => 'Welcome to the :company_name file management system. As an administrator, you have full access to manage users, configure cloud storage, and oversee all file uploads. Please verify your email address to complete your admin account setup.',
'admin_verify_email_button' => 'Verify Administrator Access',

// Employee Verification  
'employee_verify_email_subject' => 'Verify Your Employee Email Address',
'employee_verify_email_title' => 'Verify Your Employee Email Address',
'employee_verify_email_intro' => 'Welcome to :company_name! As an employee, you can receive client file uploads directly to your Google Drive and manage your own client relationships. Please verify your email address to start receiving client files.',
'employee_verify_email_button' => 'Verify Employee Access',

// Client Verification
'client_verify_email_subject' => 'Verify Your Email Address',
'client_verify_email_title' => 'Verify Your Email Address', 
'client_verify_email_intro' => 'To upload files to :company_name, please verify your email address by clicking on the link below. Once verified, you\'ll be able to securely upload files that will be delivered directly to the appropriate team member.',
'client_verify_email_button' => 'Verify Email Address',

// Shared elements (existing)
'verify_email_ignore' => 'If you did not request this verification, you can safely ignore this email.',
'thanks_signature' => 'Thanks',
```

## Error Handling

### Fallback Strategy
1. **Primary**: Use role-specific template based on user role
2. **Secondary**: If role detection fails, use client template as safe default
3. **Tertiary**: If all else fails, use existing LoginVerificationMail as ultimate fallback

### Logging Strategy
```php
Log::info('Email verification sent', [
    'email' => $email,
    'template_used' => $mailClass,
    'user_role' => $userRole ?? 'unknown',
    'context' => $context ?? 'default'
]);
```

## Testing Strategy

### Unit Tests
- Test each Mail class renders correct template
- Test VerificationMailFactory role detection logic
- Test language key interpolation

### Integration Tests  
- Test email sending with different user roles
- Test fallback behavior when role is unknown
- Test template rendering with real data

### Feature Tests
- Test complete verification flow for each user type
- Test email content matches expected role-specific messaging
- Test backward compatibility with existing flows

## Migration Strategy

### Phase 1: Create New Components
1. Create new Mail classes
2. Create new email templates
3. Add new language keys
4. Create VerificationMailFactory

### Phase 2: Update Controllers
1. Update PublicUploadController to use factory
2. Update any admin/employee user creation flows
3. Add role detection logic

### Phase 3: Cleanup
1. Remove unused EmailVerificationMail class
2. Remove duplicate email-verification.blade.php template
3. Update tests to use new system

### Backward Compatibility
- Existing LoginVerificationMail remains functional during transition
- New system gracefully falls back to client template for unknown contexts
- No breaking changes to existing verification URLs or flows

## Implementation Considerations

### Template Inheritance
All templates share the same base structure using Laravel's mail components, ensuring consistent styling and functionality while allowing role-specific content.

### Configuration Flexibility
The factory pattern allows for easy extension to additional roles or contexts without modifying existing code.

### Performance Impact
Minimal performance impact as role detection is simple boolean checks, and template selection happens at mail creation time.

### Maintenance
Centralized language files make content updates easy, and the factory pattern isolates role logic for maintainability.