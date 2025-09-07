# Email Verification Logging and Monitoring

This document describes the logging and monitoring features implemented for the role-based email verification system.

## Overview

The system provides comprehensive logging and metrics tracking for email verification processes, including:

- Structured logging for email template selection
- Error logging for role detection failures
- Metrics tracking for email verification success rates by role
- Performance monitoring with processing time tracking

## Logging Features

### Template Selection Logging

Every time an email verification template is selected, the system logs:

```json
{
  "message": "Email verification template selected",
  "user_id": 123,
  "user_email": "user@example.com",
  "detected_role": "admin",
  "mail_class": "App\\Mail\\AdminVerificationMail",
  "method": "createForUser",
  "fallback_used": false,
  "processing_time_ms": 2.45,
  "service": "VerificationMailFactory",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

### Role Detection Error Logging

When role detection fails, the system logs warnings:

```json
{
  "message": "Role detection failed for user",
  "user_id": 123,
  "user_email": "user@example.com",
  "user_role_column": null,
  "method": "createForUser",
  "fallback_used": true
}
```

### Email Sending Logging

The system tracks email sending success and failures:

```json
{
  "message": "Email verification email sent successfully",
  "role": "admin",
  "user_email": "admin@example.com",
  "service": "VerificationMailFactory",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

### Verification Completion Logging

The system logs when users complete email verification:

```json
{
  "message": "Email verification completed successfully",
  "role": "client",
  "user_email": "client@example.com",
  "service": "VerificationMailFactory",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

## Metrics System

### Available Metrics

The system tracks the following metrics by role:

- `template_selected` - Number of times a template was selected
- `template_creation_error` - Number of template creation errors
- `email_sent` - Number of emails successfully sent
- `email_send_error` - Number of email sending failures
- `verification_success` - Number of successful verifications
- `verification_failure` - Number of failed verifications

### Viewing Metrics

Use the Artisan command to view metrics:

```bash
# View all metrics
php artisan email:verification-metrics

# View metrics for specific role
php artisan email:verification-metrics --role=admin

# View metrics for specific event
php artisan email:verification-metrics --event=email_sent

# View today's metrics only
php artisan email:verification-metrics --daily

# Get JSON output
php artisan email:verification-metrics --json
```

### Success Rate Calculation

The command automatically calculates success rates:

- **Email Delivery Rate**: `email_sent / (email_sent + email_send_error)`
- **Verification Completion Rate**: `verification_success / (verification_success + verification_failure)`

## Integration Points

### Controllers

The following controllers have been updated with logging:

- `PublicUploadController` - Logs email sending and verification completion
- `EmailVerificationNotificationController` - Logs email sending
- `VerifyEmailController` - Logs verification completion

### Service Methods

The `VerificationMailFactory` provides these logging methods:

```php
// Log successful email sending
$mailFactory->logEmailSent($role, $userEmail);

// Log email sending failure
$mailFactory->logEmailSendError($role, $reason, $userEmail);

// Log successful verification
$mailFactory->logVerificationSuccess($role, $userEmail);

// Log verification failure
$mailFactory->logVerificationFailure($role, $reason, $userEmail);
```

## Monitoring Best Practices

### Log Analysis

Monitor these log patterns for issues:

1. **High fallback usage**: Look for `fallback_used: true` entries
2. **Role detection failures**: Monitor warning logs about role detection
3. **Email sending errors**: Track `email_send_error` events
4. **Verification failures**: Monitor `verification_failure` events

### Performance Monitoring

The system logs processing times for template selection. Monitor for:

- Unusually high processing times (> 100ms)
- Consistent performance degradation over time

### Alerting Recommendations

Set up alerts for:

- Email delivery success rate < 95%
- Verification completion rate < 80%
- High number of role detection failures
- Template creation errors

## Cache Storage

Metrics are stored in the application cache with the following keys:

- `email_verification_metrics:{role}:{event}` - All-time metrics
- `email_verification_metrics_daily:{role}:{event}:{date}` - Daily metrics (expire after 7 days)

## Error Handling

The metrics system is designed to be resilient:

- Cache failures don't break the main functionality
- Metrics errors are logged but don't propagate
- Graceful degradation when cache is unavailable

## Testing

The logging and metrics system includes comprehensive tests:

- Unit tests for all logging methods
- Integration tests for controller logging
- Error handling tests for cache failures
- Metrics calculation tests

Run the tests with:

```bash
php artisan test tests/Unit/EmailVerificationLoggingTest.php
```