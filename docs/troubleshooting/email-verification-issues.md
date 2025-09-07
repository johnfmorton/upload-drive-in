# Email Verification Troubleshooting Guide

This guide helps diagnose and resolve issues with the email verification system, particularly focusing on restriction bypass behavior and common problems.

## Common Issues

### 1. Existing Users Cannot Access System

**Symptoms:**
- Existing users receive "registration disabled" errors
- Users with non-whitelisted domains are blocked
- Legitimate users cannot get verification emails

**Diagnosis:**
```bash
# Check if user exists in database
ddev artisan tinker
>>> User::where('email', 'user@example.com')->first()

# Check domain rules configuration
>>> DomainAccessRule::first()
```

**Solution:**
- Verify the existing user bypass logic is working
- Check application logs for bypass events
- Ensure user lookup is not failing due to database issues

**Log Patterns to Look For:**
```
[INFO] Existing user bypassing registration restrictions
[ERROR] Failed to check for existing user
```

### 2. New Users Bypassing Restrictions

**Symptoms:**
- New users receive verification emails when they shouldn't
- Domain restrictions not being enforced
- Public registration disabled but new users can register

**Diagnosis:**
```bash
# Check if user detection is working correctly
tail -f storage/logs/laravel.log | grep "user_exists"

# Verify domain rules are loaded
ddev artisan tinker
>>> DomainAccessRule::first()
```

**Solution:**
- Verify user lookup logic is working correctly
- Check that domain rules are properly configured
- Ensure new user path is applying restrictions

**Log Patterns to Look For:**
```
[WARNING] New user blocked by registration restrictions
[INFO] New user registration allowed
```

### 3. Wrong Email Templates Being Sent

**Symptoms:**
- Admin users receive client templates
- Role-based templates not matching user roles
- Generic templates instead of role-specific ones

**Diagnosis:**
```bash
# Check user role in database
ddev artisan tinker
>>> User::where('email', 'user@example.com')->first()->role

# Check mail logs
tail -f storage/logs/laravel.log | grep "Mail"
```

**Solution:**
- Verify role detection in verification email creation
- Check that VerificationMailFactory is working correctly
- Ensure user roles are properly set in database

### 4. Verification Emails Not Being Sent

**Symptoms:**
- No emails received by users
- Queue jobs failing
- Mail configuration issues

**Diagnosis:**
```bash
# Check queue status
ddev artisan queue:failed

# Check mail configuration
ddev artisan tinker
>>> config('mail')

# Test mail sending
ddev artisan tinker
>>> Mail::raw('Test', function($m) { $m->to('test@example.com'); })
```

**Solution:**
- Verify mail configuration in `.env`
- Check queue worker is running
- Ensure mail driver is properly configured

## Debugging Steps

### Step 1: Enable Debug Logging

Add to `.env`:
```env
LOG_LEVEL=debug
```

### Step 2: Monitor Real-time Logs

```bash
# Monitor all logs
ddev artisan pail

# Filter for email verification
ddev artisan pail --filter="email"
```

### Step 3: Check Database State

```bash
ddev artisan tinker

# Check user exists
>>> User::where('email', 'problematic@email.com')->first()

# Check domain rules
>>> DomainAccessRule::first()

# Check email validations
>>> EmailValidation::where('email', 'problematic@email.com')->latest()->first()
```

### Step 4: Test API Directly

```bash
# Test with existing user
curl -X POST https://your-app.ddev.site/validate-email \
  -H "Content-Type: application/json" \
  -d '{"email": "existing@example.com"}' \
  -v

# Test with new user
curl -X POST https://your-app.ddev.site/validate-email \
  -H "Content-Type: application/json" \
  -d '{"email": "newuser@example.com"}' \
  -v
```

## Configuration Issues

### Domain Rules Not Working

**Check Configuration:**
```bash
ddev artisan tinker
>>> $rules = DomainAccessRule::first()
>>> $rules->mode  // Should be 'whitelist', 'blacklist', or 'disabled'
>>> $rules->rules  // Should be array of domains
>>> $rules->allow_public_registration  // Should be boolean
```

**Common Problems:**
- Domain rules table is empty
- Rules are not properly formatted (should be array)
- Mode is not set correctly

**Fix:**
```bash
# Create domain rules if missing
ddev artisan tinker
>>> DomainAccessRule::create([
...     'mode' => 'whitelist',
...     'rules' => ['allowed-domain.com'],
...     'allow_public_registration' => false
... ])
```

### User Role Issues

**Check User Roles:**
```bash
ddev artisan tinker
>>> User::all()->pluck('role', 'email')
```

**Common Problems:**
- User roles not set (should be 'admin', 'employee', or 'client')
- Role enum values don't match expected values

**Fix:**
```bash
# Update user role
ddev artisan tinker
>>> User::where('email', 'user@example.com')->update(['role' => 'admin'])
```

## Performance Issues

### Slow User Lookups

**Symptoms:**
- Email verification requests are slow
- Database queries timing out
- High CPU usage during verification

**Diagnosis:**
```bash
# Check database indexes
ddev mysql
> SHOW INDEX FROM users WHERE Column_name = 'email';

# Monitor query performance
tail -f storage/logs/laravel.log | grep "Query"
```

**Solution:**
- Ensure email column has proper index
- Consider caching user lookups for high-traffic scenarios
- Monitor database performance

### Queue Backup

**Symptoms:**
- Verification emails delayed
- Queue jobs accumulating
- Failed job count increasing

**Diagnosis:**
```bash
# Check queue status
ddev artisan queue:work --once --verbose

# Check failed jobs
ddev artisan queue:failed
```

**Solution:**
- Ensure queue worker is running continuously
- Check for memory leaks in queue worker
- Monitor failed job patterns

## Security Concerns

### Bypass Logic Exploitation

**Monitor For:**
- Unusual patterns in bypass usage
- High volume of requests from single IPs
- Attempts to probe for existing email addresses

**Mitigation:**
- Implement rate limiting on email verification endpoint
- Monitor logs for suspicious patterns
- Consider additional authentication for sensitive operations

### Information Disclosure

**Risk:**
- Different responses for existing vs non-existing users
- Timing attacks to determine user existence

**Mitigation:**
- Ensure consistent response times
- Use generic error messages where possible
- Implement proper rate limiting

## Emergency Procedures

### Disable Bypass Logic Temporarily

If bypass logic is causing issues, you can temporarily disable it:

```php
// In PublicUploadController::validateEmail()
// Comment out existing user detection temporarily
// $existingUser = null; // Force new user path
```

**Warning:** This will prevent existing users from accessing the system.

### Force All Users Through Restrictions

```php
// In PublicUploadController::validateEmail()
// Always apply restrictions regardless of user existence
return $this->handleNewUserRegistration($email, $validated);
```

**Warning:** This may lock out legitimate existing users.

### Reset All Email Validations

```bash
# Clear all pending email validations
ddev artisan tinker
>>> EmailValidation::truncate()
```

## Getting Help

### Log Analysis

When reporting issues, include:
- Relevant log entries with timestamps
- User email (anonymized if needed)
- Domain rules configuration
- Expected vs actual behavior

### Debug Information

Collect this information:
```bash
# Application version
git rev-parse HEAD

# Configuration
ddev artisan config:show

# Database state
ddev artisan tinker
>>> DomainAccessRule::first()
>>> User::count()
>>> EmailValidation::count()
```

## Related Documentation

- [Email Verification API Documentation](../api/email-verification-endpoints.md)
- [Security Documentation](../security/email-verification-security.md)
- [Development Workflow](../development-workflow.md)