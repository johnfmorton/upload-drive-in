# Cloud Storage Logging and Monitoring

This document describes the comprehensive logging and monitoring system for cloud storage operations, specifically designed to track token refresh operations, status determinations, and system performance metrics.

## Overview

The cloud storage logging system provides detailed insights into:
- Token refresh attempts and outcomes
- Status determination decisions and reasoning
- API connectivity test results
- Cache performance metrics
- System health trends

## Components

### CloudStorageLogService

The `CloudStorageLogService` is the central component that handles all logging and metrics tracking for cloud storage operations.

#### Key Features

- **Structured Logging**: All logs use consistent JSON structure with event types and context
- **Metrics Tracking**: Real-time metrics stored in cache for performance monitoring
- **Status Change Detection**: Tracks when system status changes between states
- **Cache Performance Monitoring**: Tracks cache hit/miss rates for optimization

#### Log Channels

All cloud storage logs are written to the `cloud-storage` channel, which stores logs in:
- File: `storage/logs/cloud-storage-YYYY-MM-DD.log`
- Retention: 30 days
- Format: JSON with structured data

## Logging Categories

### Token Refresh Operations

#### Token Refresh Attempt
```json
{
  "event": "token_refresh_attempt",
  "user_id": 123,
  "provider": "google-drive",
  "timestamp": "2025-01-01T12:00:00Z",
  "context": {
    "trigger": "proactive_validation",
    "token_expired_at": "2025-01-01T11:00:00Z"
  }
}
```

#### Token Refresh Success
```json
{
  "event": "token_refresh_success",
  "user_id": 123,
  "provider": "google-drive",
  "timestamp": "2025-01-01T12:00:00Z",
  "context": {
    "new_expires_at": "2025-01-01T13:00:00Z",
    "expires_in_seconds": 3600
  }
}
```

#### Token Refresh Failure
```json
{
  "event": "token_refresh_failure",
  "user_id": 123,
  "provider": "google-drive",
  "error": "Invalid refresh token",
  "timestamp": "2025-01-01T12:00:00Z",
  "context": {
    "error_type": "invalid_credentials",
    "http_code": 400
  }
}
```

### Status Determination

#### Status Determination Decision
```json
{
  "event": "status_determination",
  "user_id": 123,
  "provider": "google-drive",
  "determined_status": "healthy",
  "reason": "Token is valid and API connectivity confirmed",
  "timestamp": "2025-01-01T12:00:00Z",
  "context": {
    "token_validation_result": true,
    "api_connectivity_result": true,
    "determination_time_ms": 150.5
  }
}
```

### API Connectivity Tests

#### API Connectivity Test
```json
{
  "event": "api_connectivity_test",
  "user_id": 123,
  "provider": "google-drive",
  "success": true,
  "timestamp": "2025-01-01T12:00:00Z",
  "context": {
    "test_method": "about_get",
    "drive_user_email": "user@example.com"
  }
}
```

### Cache Operations

#### Cache Operation
```json
{
  "event": "cache_operation",
  "operation": "get",
  "cache_key": "token_valid_123_google-drive",
  "cache_hit": true,
  "timestamp": "2025-01-01T12:00:00Z",
  "context": {
    "operation": "token_validation_cache_hit",
    "user_id": 123,
    "provider": "google-drive"
  }
}
```

## Metrics Tracking

### Available Metrics

#### Token Refresh Metrics
- `token_refresh_attempts.{provider}` - Total refresh attempts
- `token_refresh_success.{provider}` - Successful refreshes
- `token_refresh_failures.{provider}` - Failed refreshes
- `token_refresh_attempts.{provider}.user.{user_id}` - Per-user attempts

#### API Connectivity Metrics
- `api_connectivity_success.{provider}` - Successful API tests
- `api_connectivity_failures.{provider}` - Failed API tests

#### Status Frequency Metrics
- `status_frequency.{provider}.{status}` - How often each status occurs
- `status_changes.{provider}.{from_status}_to_{status}` - Status transitions

#### Cache Performance Metrics
- `cache_hits` - Total cache hits
- `cache_misses` - Total cache misses

#### Proactive Validation Metrics
- `proactive_validation.expired_tokens.{provider}` - Expired tokens found
- `proactive_validation.refresh_needed.{provider}` - Refreshes needed

### Metrics Storage

- **Storage**: Redis/Cache with 1-hour TTL
- **Prefix**: `cloud_storage_metrics:`
- **Reset**: Automatic after TTL expires
- **Aggregation**: Real-time counters

## Monitoring Commands

### Cloud Storage Metrics Command

Display comprehensive metrics and health analysis:

```bash
# Basic metrics for last 24 hours
ddev artisan cloud-storage:metrics

# Metrics for specific provider and time range
ddev artisan cloud-storage:metrics google-drive --hours=48

# JSON output for integration
ddev artisan cloud-storage:metrics --format=json

# Export to file
ddev artisan cloud-storage:metrics --export=metrics.csv
```

#### Sample Output

```
🔄 Token Refresh Metrics
+--------------+---------+
| Metric       | Value   |
+--------------+---------+
| Attempts     | 25      |
| Successes    | 24      |
| Failures     | 1       |
| Success Rate | 96.00%  |
+--------------+---------+

🏥 Health Summary & Recommendations
✅ Token refresh is working well (96.0% success rate).
⚠️  System is frequently unhealthy (75.0% healthy status). Review error logs.
```

## Health Analysis

### Automatic Health Assessment

The system provides automatic health assessment with recommendations:

#### Token Refresh Health
- **Healthy**: Success rate ≥ 95%
- **Warning**: Success rate < 90%
- **Critical**: Success rate < 70%

#### API Connectivity Health
- **Healthy**: Success rate ≥ 95%
- **Warning**: Success rate < 90%
- **Critical**: Success rate < 70%

#### Status Distribution Health
- **Healthy**: ≥ 90% healthy status checks
- **Warning**: < 80% healthy status checks
- **Critical**: < 60% healthy status checks

#### Cache Performance Health
- **Optimal**: Hit rate ≥ 80%
- **Good**: Hit rate ≥ 70%
- **Poor**: Hit rate < 70%

## Integration with Existing Systems

### Log Monitoring

The structured logs can be integrated with log monitoring systems:

```bash
# Monitor token refresh failures
tail -f storage/logs/cloud-storage-*.log | grep "token_refresh_failure"

# Monitor status changes
tail -f storage/logs/cloud-storage-*.log | grep "status_change"

# Monitor API connectivity issues
tail -f storage/logs/cloud-storage-*.log | grep "api_connectivity_test.*false"
```

### Alerting

Set up alerts based on metrics:

```php
// Example: Alert on high failure rate
$successRate = $logService->getTokenRefreshSuccessRate('google-drive', 1);
if ($successRate < 0.8) {
    // Send alert
}

// Example: Alert on status distribution issues
$distribution = $logService->getStatusDistribution('google-drive', 1);
$totalChecks = array_sum($distribution);
$healthyPercentage = $totalChecks > 0 ? $distribution['healthy'] / $totalChecks : 1;
if ($healthyPercentage < 0.8) {
    // Send alert
}
```

## Performance Considerations

### Cache Usage

- Metrics are cached for 1 hour to balance real-time data with performance
- Cache keys are prefixed to avoid conflicts
- Automatic cleanup prevents memory bloat

### Log Volume

- Logs are structured and compressed
- Daily rotation prevents disk space issues
- 30-day retention balances storage with analysis needs

### Resource Impact

- Minimal performance impact on normal operations
- Asynchronous logging where possible
- Efficient cache operations

## Troubleshooting

### Common Issues

#### High Token Refresh Failure Rate
1. Check Google Drive API credentials
2. Verify refresh tokens are not expired
3. Review error logs for specific failure reasons

#### Low Cache Hit Rate
1. Check cache configuration
2. Verify TTL settings are appropriate
3. Monitor cache memory usage

#### Missing Metrics
1. Verify cache is working
2. Check log service is properly injected
3. Ensure metrics are being tracked in code

### Debug Commands

```bash
# Check current metrics
ddev artisan cloud-storage:metrics --hours=1

# View recent logs
tail -100 storage/logs/cloud-storage-$(date +%Y-%m-%d).log

# Clear metrics cache
ddev artisan cache:clear
```

## Future Enhancements

### Planned Features

1. **Dashboard Integration**: Web-based metrics dashboard
2. **Real-time Alerts**: Automated alerting system
3. **Historical Analysis**: Long-term trend analysis
4. **Performance Profiling**: Detailed performance metrics
5. **Multi-provider Support**: Extend to other cloud providers

### Configuration Options

Future versions will include configurable:
- Metrics retention periods
- Alert thresholds
- Log levels and verbosity
- Cache TTL settings