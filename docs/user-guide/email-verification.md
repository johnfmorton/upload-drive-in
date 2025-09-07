# Email Verification User Guide

This guide explains how the email verification system works for different types of users and administrators managing the system.

## Overview

The email verification system allows users to access the file upload system by verifying their email address. The system intelligently handles both existing users and new registrations based on configured security settings.

## For End Users

### How Email Verification Works

1. **Enter Your Email**: Visit the upload page and enter your email address
2. **Receive Verification Email**: Check your inbox for a verification email
3. **Click Verification Link**: Click the link in the email to verify your identity
4. **Access the System**: You'll be logged in and redirected to the appropriate interface

### User Types and Experience

#### Existing Admin Users
- **Access**: Always allowed, even when registration is restricted
- **Email Template**: Administrative verification email with admin-specific messaging
- **Redirect**: Admin dashboard after verification
- **Features**: Full administrative access to system management

#### Existing Employee Users  
- **Access**: Always allowed, even when domain restrictions apply
- **Email Template**: Employee verification email with file management messaging
- **Redirect**: Employee dashboard with file management tools
- **Features**: File management, client relationship management

#### Existing Client Users
- **Access**: Always allowed, regardless of security restrictions
- **Email Template**: Client verification email with upload instructions
- **Redirect**: File upload interface or intended destination
- **Features**: File upload, upload history, download links

#### New Users
- **Access**: Subject to configured security restrictions
- **Email Template**: Client verification email (if allowed)
- **Redirect**: File upload interface
- **Features**: Basic file upload functionality

### What to Expect

#### If You're an Existing User
✅ **You can always access the system** - even if:
- Public registration is disabled
- Your email domain is not on the allowed list
- Other security restrictions are in place

#### If You're a New User
❓ **Your access depends on security settings**:
- ✅ **Allowed**: If public registration is enabled and your domain is permitted
- ❌ **Blocked**: If public registration is disabled or your domain is restricted

### Common Scenarios

#### Scenario 1: Existing User During Maintenance
**Situation**: Public registration is temporarily disabled for maintenance

**What Happens**:
- Existing users can still request verification emails
- New users see a message about registration being disabled
- All existing users maintain normal access

#### Scenario 2: Domain Restrictions Active
**Situation**: Only certain email domains are allowed for new registrations

**What Happens**:
- Existing users with any domain can still access the system
- New users must have emails from approved domains
- Existing users see no difference in experience

#### Scenario 3: High Security Mode
**Situation**: Both public registration disabled and strict domain restrictions

**What Happens**:
- All existing users maintain full access
- No new users can register
- System remains fully functional for existing users

### Troubleshooting for Users

#### "Registration is disabled" Error
**If you're an existing user**:
- Try again - the system should recognize you
- Contact support if the issue persists
- Your account may need to be verified in the database

**If you're a new user**:
- Registration is currently disabled
- Contact the administrator for access
- Wait for registration to be re-enabled

#### "Domain not allowed" Error
**If you're an existing user**:
- This shouldn't happen - contact support immediately
- Your existing account should bypass domain restrictions
- There may be a system issue

**If you're a new user**:
- Your email domain is not currently allowed
- Contact the administrator to request domain approval
- Use an email from an approved domain if available

#### Not Receiving Verification Emails
**Check These Items**:
- Spam/junk folder in your email
- Email address spelling
- Email server delays (wait 5-10 minutes)
- Contact support if email doesn't arrive

## For Administrators

### Managing Security Settings

#### Domain Access Rules
Configure which domains are allowed for new user registration:

```bash
# Access admin dashboard
# Navigate to Settings > Domain Access Rules

# Whitelist Mode: Only specified domains allowed
# Blacklist Mode: Specified domains blocked
# Disabled: All domains allowed
```

#### Public Registration Control
Enable or disable new user registration:

```bash
# In Domain Access Rules settings
# Toggle "Allow Public Registration"
# Existing users always maintain access
```

### Understanding User Behavior

#### Existing User Bypass
- **Automatic**: System automatically detects existing users
- **Transparent**: Users don't see any difference in experience
- **Logged**: All bypass events are recorded for audit

#### New User Restrictions
- **Enforced**: All configured restrictions apply to new users
- **Consistent**: Same rules apply regardless of user type
- **Audited**: All restriction enforcement is logged

### Monitoring and Auditing

#### Log Monitoring
Monitor these log entries for system health:

```bash
# Successful existing user bypass
[INFO] Existing user bypassing registration restrictions

# New user blocked by restrictions  
[WARNING] New user blocked by registration restrictions

# System errors
[ERROR] Failed to check for existing user
```

#### User Access Patterns
Watch for unusual patterns:
- High volume of bypass events
- Repeated failed attempts from same IP
- Unusual geographic access patterns

### Best Practices

#### Security Configuration
1. **Use whitelist mode** for maximum security
2. **Regularly review** approved domains
3. **Monitor bypass logs** for unusual activity
4. **Test restrictions** with new user accounts

#### User Communication
1. **Inform users** about security changes
2. **Provide clear error messages** for blocked attempts
3. **Offer alternative contact methods** for access issues
4. **Document approved domains** for user reference

#### Incident Response
1. **Monitor logs** for security events
2. **Respond quickly** to user access issues
3. **Document incidents** for future reference
4. **Update security settings** based on lessons learned

### Common Administrative Tasks

#### Adding New Approved Domains
1. Access admin dashboard
2. Navigate to Domain Access Rules
3. Add domain to whitelist
4. Test with new user registration

#### Temporarily Disabling Registration
1. Access Domain Access Rules
2. Disable "Allow Public Registration"
3. Existing users maintain access
4. New users see appropriate message

#### Investigating Access Issues
1. Check user exists in database
2. Review recent log entries
3. Verify domain rules configuration
4. Test email verification flow

## For Developers

### Integration Points

#### API Usage
```javascript
// Request verification email
fetch('/validate-email', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({
        email: 'user@example.com',
        intended_url: 'https://example.com/dashboard'
    })
});
```

#### Error Handling
```javascript
// Handle different response types
response.json().then(data => {
    if (data.success) {
        // Show success message
    } else if (data.errors) {
        // Show validation errors
    }
});
```

### Customization Options

#### Email Templates
- Modify templates in `resources/views/emails/`
- Role-specific templates automatically selected
- Maintain consistent branding across templates

#### Security Settings
- Configure in admin dashboard
- Database-driven configuration
- Real-time updates without code changes

#### Logging and Monitoring
- Structured logging for easy parsing
- Integration with monitoring systems
- Audit trail for compliance requirements

## Frequently Asked Questions

### For Users

**Q: Why am I getting a "registration disabled" message if I already have an account?**
A: The system should automatically recognize existing users. If you're seeing this message, contact support as there may be a technical issue.

**Q: Can I use the system if my company's domain isn't on the approved list?**
A: If you already have an account, yes - existing users bypass domain restrictions. New users need approved domains.

**Q: How long is the verification link valid?**
A: Verification links expire after 24 hours for security reasons.

### For Administrators

**Q: Will existing users be locked out if I disable public registration?**
A: No - existing users always maintain access regardless of security restrictions.

**Q: How can I tell if the bypass logic is working correctly?**
A: Monitor the application logs for "Existing user bypassing registration restrictions" messages.

**Q: What happens if the user database is unavailable?**
A: The system falls back to treating all users as new users and applies all restrictions.

### For Developers

**Q: How do I test the bypass logic?**
A: Create test users in different roles, then enable restrictions and verify they can still access the system.

**Q: Can I customize which restrictions are bypassed?**
A: The current implementation bypasses all registration restrictions for existing users. Customization would require code changes.

**Q: How do I add new user roles?**
A: Update the UserRole enum and add corresponding email templates and redirect logic.

## Related Documentation

- [Email Verification API Documentation](../api/email-verification-endpoints.md)
- [Troubleshooting Guide](../troubleshooting/email-verification-issues.md)
- [Security Documentation](../security/email-verification-security.md)
- [Development Guidelines](../development/email-verification-development.md)