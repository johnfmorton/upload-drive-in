# Email Verification API Documentation

This document describes the email verification API endpoints and their behavior, including the existing user bypass logic implemented in the system.

## Overview

The email verification system allows users to request verification emails through a public endpoint. The system implements intelligent user detection to bypass registration restrictions for existing users while maintaining security for new user registrations.

## Endpoints

### POST /validate-email

Validates an email address and sends a verification email if the user is authorized.

#### Request

```http
POST /validate-email
Content-Type: application/json

{
    "email": "user@example.com",
    "intended_url": "https://example.com/dashboard" // optional
}
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `email` | string | Yes | Valid email address (max 255 characters) |
| `intended_url` | string | No | URL to redirect to after verification |

#### Response

**Success (200 OK)**
```json
{
    "success": true,
    "message": "Verification email sent successfully"
}
```

**Validation Error (422 Unprocessable Entity)**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": [
            "Public registration is currently disabled. If you already have an account, please try again or contact support."
        ]
    }
}
```

**Server Error (500 Internal Server Error)**
```json
{
    "message": "Unable to process request at this time"
}
```

## Behavior Logic

### Existing User Detection

The system prioritizes existing user detection over security restrictions:

1. **Email Validation**: Basic format and length validation
2. **User Lookup**: Check if email exists in the database
3. **Existing User Path**: If user exists, bypass all registration restrictions
4. **New User Path**: If user doesn't exist, apply all security checks

### Existing User Bypass Logic

When an existing user is detected:

- ✅ **Public registration disabled**: Bypassed for existing users
- ✅ **Domain restrictions**: Bypassed for existing users  
- ✅ **Whitelist/blacklist rules**: Bypassed for existing users
- ✅ **Role-based email templates**: Applied based on user's role

### New User Restrictions

When no existing user is found:

- ❌ **Public registration disabled**: Request rejected
- ❌ **Domain not whitelisted**: Request rejected (whitelist mode)
- ❌ **Domain blacklisted**: Request rejected (blacklist mode)
- ✅ **All checks pass**: Client verification email sent

## Email Templates by User Role

The system sends role-specific verification emails:

| User Role | Template | Redirect After Verification |
|-----------|----------|----------------------------|
| Admin | `AdminVerificationMail` | `/admin/dashboard` |
| Employee | `EmployeeVerificationMail` | `/employee/{username}/dashboard` |
| Client | `ClientVerificationMail` | Upload interface or intended URL |

## Security Considerations

### Existing User Benefits
- Existing users can always access their accounts
- No legitimate users are locked out by security changes
- Maintains user experience during restriction periods

### Security Maintained
- New user registration still respects all security settings
- No new access is granted to unauthorized users
- All bypass events are logged for audit purposes

### Rate Limiting
- Standard Laravel rate limiting applies
- Consider implementing additional rate limiting for email endpoints
- Monitor for unusual patterns in bypass usage

## Error Handling

### Database Connection Issues
- System falls back to treating users as new (applies restrictions)
- Errors are logged but don't expose sensitive information
- Graceful degradation maintains security posture

### Domain Rules Configuration Issues
- Existing users: Allowed through (fail open)
- New users: Rejected (fail closed)
- Configuration errors are logged for investigation

## Logging and Monitoring

### Successful Bypass Events
```json
{
    "level": "info",
    "message": "Existing user bypassing registration restrictions",
    "context": {
        "email": "user@example.com",
        "user_id": 123,
        "user_role": "employee",
        "public_registration_enabled": false,
        "domain_restrictions_mode": "whitelist",
        "restrictions_bypassed": true,
        "context": "existing_user_login"
    }
}
```

### Restriction Enforcement
```json
{
    "level": "warning", 
    "message": "New user blocked by registration restrictions",
    "context": {
        "email": "newuser@example.com",
        "user_exists": false,
        "restriction_type": "public_registration_disabled",
        "domain_rules_mode": "whitelist",
        "context": "new_user_registration"
    }
}
```

## Testing the API

### Test Existing User Bypass
```bash
# Create test user first
curl -X POST https://your-app.com/validate-email \
  -H "Content-Type: application/json" \
  -d '{"email": "existing@example.com"}'

# Should succeed even with restrictions enabled
```

### Test New User Restrictions
```bash
# With public registration disabled
curl -X POST https://your-app.com/validate-email \
  -H "Content-Type: application/json" \
  -d '{"email": "newuser@example.com"}'

# Should return 422 validation error
```

## Migration Notes

### Backward Compatibility
- No breaking changes to existing API contracts
- Enhanced behavior is transparent to existing clients
- All existing verification URLs continue to work

### Deployment Considerations
- Monitor logs for bypass events after deployment
- Verify existing users can still access the system
- Confirm new user restrictions are properly enforced

## Related Documentation
- [Email Verification Troubleshooting Guide](../troubleshooting/email-verification-issues.md)
- [Security Documentation](../security/email-verification-security.md)
- [User Management Guide](../user-guide/email-verification.md)