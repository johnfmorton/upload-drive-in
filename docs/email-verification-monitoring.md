# Email Verification Monitoring System

This document describes the monitoring and metrics system for email verification bypass patterns and restriction enforcement.

## Overview

The email verification monitoring system tracks and analyzes patterns in how existing users bypass registration restrictions and how restrictions are enforced for new users. This helps identify unusual activity, potential security issues, and system health.

## Components

### 1. EmailVerificationMetricsService

The core service that records and analyzes metrics:

- **Records bypass events** when existing users bypass restrictions
- **Records restriction events** when new users are blocked
- **Analyzes patterns** to detect unusual activity
- **Provides dashboard metrics** for monitoring

### 2. Artisan Commands

#### View Metrics
```bash
# Display metrics in table format (default)
php artisan email-verification:metrics

# Display metrics for specific time range
php artisan email-verification:metrics --hours=48

# Display in JSON format
php artisan email-verification:metrics --format=json

# Log metrics to Laravel log
php artisan email-verification:metrics --format=log

# Show only alerts
php artisan email-verification:metrics --alerts
```

#### Check for Alerts
```bash
# Check for unusual patterns with default thresholds
php artisan email-verification:check-alerts

# Custom thresholds
php artisan email-verification:check-alerts --threshold-bypasses=30 --threshold-restrictions=100

# Send email notifications
php artisan email-verification:check-alerts --notify-email=admin@example.com
```

### 3. Dashboard Component

Include the metrics dashboard in your admin views:

```blade
<x-email-verification-metrics-dashboard :hours="24" />
```

### 4. Scheduled Tasks

The system automatically runs scheduled tasks:

- **Alert monitoring**: Every 15 minutes
- **Daily metrics report**: Every day at 8 AM

## Metrics Tracked

### Bypass Events
- **User role** (admin, employee, client)
- **Email domain**
- **Restrictions bypassed** (public registration disabled, domain not allowed)
- **Timestamp and frequency**

### Restriction Events
- **Restriction type** (public registration disabled, domain not allowed)
- **Email domain**
- **Context information**
- **Timestamp and frequency**

### Calculated Metrics
- **Bypass to restriction ratio**
- **Hourly activity distribution**
- **Top domains by activity**
- **Most bypassed restrictions**

## Alert Types

### Bypass Spike
- **Trigger**: More than 10 bypasses in one hour
- **Severity**: Warning
- **Indicates**: Possible system issue or unusual user activity

### Repeated Bypasses
- **Trigger**: Same user bypasses restrictions more than 5 times
- **Severity**: Info
- **Indicates**: User may be having login issues

### Unusual Domain Activity
- **Trigger**: More than 3 bypasses from non-common domains
- **Severity**: Info
- **Indicates**: Possible suspicious activity

### High Volume Alerts
- **Bypass Volume**: Configurable threshold (default: 20/hour)
- **Restriction Volume**: Configurable threshold (default: 50/hour)
- **Severity**: Warning

### No Activity Alert
- **Trigger**: Zero activity during business hours
- **Severity**: Info
- **Indicates**: Possible system issue

## Dashboard Features

### Summary Cards
- Total existing user bypasses
- Total restriction enforcements
- Bypass to restriction ratio

### Pattern Analysis
- Bypasses by user role
- Bypasses by restriction type
- Top bypass domains
- Top blocked domains

### Activity Timeline
- Hourly distribution chart
- Visual representation of bypass vs restriction activity

### Alert Display
- Real-time unusual activity alerts
- Severity-based color coding
- Detailed alert information

## Configuration

### Cache Settings
- Metrics are cached for 1 hour
- Events are stored with hourly keys
- Maximum 1000 events per hour to prevent memory issues

### Alert Thresholds
- Bypass spike: 10 events/hour
- Repeated user: 5 events/user
- Unusual domain: 3 events/domain
- High volume: Configurable via command options

### Email Notifications
- 1-hour cooldown between alert emails
- Only warning/error severity alerts trigger emails
- Configurable recipient via command option

## Usage Examples

### Daily Monitoring Routine
```bash
# Check current status
php artisan email-verification:metrics --alerts

# Generate daily report
php artisan email-verification:metrics --hours=24 --format=log

# Check for any issues
php artisan email-verification:check-alerts --notify-email=admin@company.com
```

### Investigating Issues
```bash
# Look at recent activity
php artisan email-verification:metrics --hours=2

# Check for specific patterns
php artisan email-verification:metrics --format=json | jq '.bypass_patterns.bypasses_by_domain'

# Monitor in real-time
watch -n 300 'php artisan email-verification:check-alerts'
```

### Integration with Admin Dashboard
```blade
@if(auth()->user()->isAdmin())
    <div class="mb-6">
        <x-email-verification-metrics-dashboard :hours="24" />
    </div>
@endif
```

## Troubleshooting

### High Bypass Volume
1. Check if restrictions are configured correctly
2. Verify existing users are legitimate
3. Look for patterns in bypass domains
4. Consider adjusting restriction settings

### High Restriction Volume
1. Check if domain rules are too restrictive
2. Verify public registration settings
3. Look for spam or bot activity
4. Consider adjusting domain whitelist

### No Activity
1. Check if email verification system is working
2. Verify database connectivity
3. Check application logs for errors
4. Test email verification flow manually

### Unusual Domain Activity
1. Investigate domains showing high activity
2. Check if domains are legitimate business domains
3. Consider adding suspicious domains to monitoring
4. Review user creation patterns

## Security Considerations

### Data Privacy
- Email addresses are logged but can be configured to hash/anonymize
- Metrics focus on patterns rather than individual user tracking
- Cache data expires automatically

### Performance Impact
- Metrics recording is lightweight (cache-based)
- Scheduled tasks run in background
- Dashboard queries are optimized with caching

### Alert Fatigue
- Cooldown periods prevent spam
- Severity levels help prioritize alerts
- Thresholds are configurable to reduce false positives

## Integration Points

### Logging
All metrics events are logged to Laravel log with structured data for analysis.

### Monitoring Systems
JSON output format allows integration with external monitoring tools.

### Notifications
Email alerts can be extended to integrate with Slack, PagerDuty, etc.

### Analytics
Metrics data can be exported for business intelligence analysis.