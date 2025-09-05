# Google Drive Token Auto-Renewal System - Configuration Reference

## Overview

This document provides comprehensive reference for all configuration options available in the Google Drive Token Auto-Renewal System, including recommended settings for different environments.

## Environment Variables

### Core Token Refresh Settings

#### TOKEN_REFRESH_ENABLED
- **Type**: Boolean
- **Default**: `true`
- **Description**: Master switch to enable/disable the entire token refresh system
- **Values**: `true`, `false`
- **Impact**: When disabled, tokens will not be automatically refreshed

```env
# Enable automatic token refresh
TOKEN_REFRESH_ENABLED=true

# Disable for emergency rollback
TOKEN_REFRESH_ENABLED=false
```

#### TOKEN_REFRESH_PROACTIVE_MINUTES
- **Type**: Integer
- **Default**: `15`
- **Description**: Minutes before token expiration to trigger proactive refresh
- **Range**: `5-60` minutes
- **Recommendation**: 
  - Development: `5` (faster testing)
  - Production: `15` (optimal balance)
  - High-traffic: `30` (more buffer time)

```env
# Refresh tokens 15 minutes before expiration
TOKEN_REFRESH_PROACTIVE_MINUTES=15

# More aggressive refresh for testing
TOKEN_REFRESH_PROACTIVE_MINUTES=5

# Conservative refresh for high-traffic systems
TOKEN_REFRESH_PROACTIVE_MINUTES=30
```

#### TOKEN_REFRESH_MAX_RETRY_ATTEMPTS
- **Type**: Integer
- **Default**: `5`
- **Description**: Maximum number of retry attempts for failed token refreshes
- **Range**: `1-10`
- **Impact**: Higher values increase resilience but may delay failure detection

```env
# Standard retry attempts
TOKEN_REFRESH_MAX_RETRY_ATTEMPTS=5

# Conservative for production
TOKEN_REFRESH_MAX_RETRY_ATTEMPTS=3

# Aggressive for development
TOKEN_REFRESH_MAX_RETRY_ATTEMPTS=10
```

#### TOKEN_REFRESH_RETRY_DELAYS
- **Type**: Array (comma-separated)
- **Default**: `1,2,4,8,16`
- **Description**: Exponential backoff delays in seconds for retry attempts
- **Format**: Comma-separated integers

```env
# Standard exponential backoff
TOKEN_REFRESH_RETRY_DELAYS=1,2,4,8,16

# Faster retries for development
TOKEN_REFRESH_RETRY_DELAYS=1,1,2,3,5

# Slower retries for production
TOKEN_REFRESH_RETRY_DELAYS=2,5,10,20,40
```

### Health Validation Settings

#### HEALTH_VALIDATION_LIVE_ENABLED
- **Type**: Boolean
- **Default**: `true`
- **Description**: Enable real-time API validation for health status
- **Impact**: When disabled, relies on cached status only

```env
# Enable live validation
HEALTH_VALIDATION_LIVE_ENABLED=true

# Use cached status only
HEALTH_VALIDATION_LIVE_ENABLED=false
```

#### HEALTH_VALIDATION_CACHE_TTL_HEALTHY
- **Type**: Integer
- **Default**: `30`
- **Description**: Cache TTL in seconds for healthy status results
- **Range**: `10-300` seconds
- **Recommendation**: Balance between accuracy and performance

```env
# Standard caching
HEALTH_VALIDATION_CACHE_TTL_HEALTHY=30

# More frequent validation
HEALTH_VALIDATION_CACHE_TTL_HEALTHY=15

# Less frequent validation (better performance)
HEALTH_VALIDATION_CACHE_TTL_HEALTHY=60
```

#### HEALTH_VALIDATION_CACHE_TTL_ERROR
- **Type**: Integer
- **Default**: `10`
- **Description**: Cache TTL in seconds for error status results
- **Range**: `5-60` seconds
- **Recommendation**: Shorter than healthy TTL for faster error recovery

```env
# Standard error caching
HEALTH_VALIDATION_CACHE_TTL_ERROR=10

# Faster error recovery
HEALTH_VALIDATION_CACHE_TTL_ERROR=5

# Longer error caching (reduce API calls)
HEALTH_VALIDATION_CACHE_TTL_ERROR=30
```

#### HEALTH_VALIDATION_RATE_LIMIT
- **Type**: Integer
- **Default**: `60`
- **Description**: Maximum health validations per minute per user
- **Range**: `10-300`
- **Impact**: Prevents API abuse while allowing responsive status updates

```env
# Standard rate limiting
HEALTH_VALIDATION_RATE_LIMIT=60

# Stricter rate limiting
HEALTH_VALIDATION_RATE_LIMIT=30

# More permissive for development
HEALTH_VALIDATION_RATE_LIMIT=120
```

### Notification Settings

#### TOKEN_NOTIFICATION_ENABLED
- **Type**: Boolean
- **Default**: `true`
- **Description**: Enable email notifications for token issues
- **Impact**: When disabled, users won't receive failure notifications

```env
# Enable notifications
TOKEN_NOTIFICATION_ENABLED=true

# Disable for testing
TOKEN_NOTIFICATION_ENABLED=false
```

#### TOKEN_NOTIFICATION_THROTTLE_HOURS
- **Type**: Integer
- **Default**: `24`
- **Description**: Hours between duplicate notifications of the same type
- **Range**: `1-168` (1 hour to 1 week)
- **Recommendation**: Balance between user awareness and spam prevention

```env
# Standard throttling (24 hours)
TOKEN_NOTIFICATION_THROTTLE_HOURS=24

# More frequent notifications
TOKEN_NOTIFICATION_THROTTLE_HOURS=12

# Less frequent notifications
TOKEN_NOTIFICATION_THROTTLE_HOURS=48
```

#### TOKEN_NOTIFICATION_ADMIN_EMAIL
- **Type**: Email address
- **Default**: `null`
- **Description**: Admin email for escalation when user notifications fail
- **Format**: Valid email address

```env
# Set admin email for escalations
TOKEN_NOTIFICATION_ADMIN_EMAIL=admin@example.com

# Multiple admins (comma-separated)
TOKEN_NOTIFICATION_ADMIN_EMAIL=admin1@example.com,admin2@example.com
```

### Rate Limiting Settings

#### TOKEN_REFRESH_RATE_LIMIT_ATTEMPTS
- **Type**: Integer
- **Default**: `5`
- **Description**: Maximum token refresh attempts per user per time window
- **Range**: `1-20`
- **Impact**: Prevents abuse while allowing legitimate retries

```env
# Standard rate limiting
TOKEN_REFRESH_RATE_LIMIT_ATTEMPTS=5

# Stricter limiting
TOKEN_REFRESH_RATE_LIMIT_ATTEMPTS=3

# More permissive for development
TOKEN_REFRESH_RATE_LIMIT_ATTEMPTS=10
```

#### TOKEN_REFRESH_RATE_LIMIT_WINDOW
- **Type**: Integer
- **Default**: `3600`
- **Description**: Rate limit time window in seconds
- **Range**: `300-86400` (5 minutes to 24 hours)
- **Recommendation**: Align with Google API quotas

```env
# Standard window (1 hour)
TOKEN_REFRESH_RATE_LIMIT_WINDOW=3600

# Shorter window (30 minutes)
TOKEN_REFRESH_RATE_LIMIT_WINDOW=1800

# Longer window (4 hours)
TOKEN_REFRESH_RATE_LIMIT_WINDOW=14400
```

### Background Job Settings

#### TOKEN_REFRESH_BACKGROUND_JOBS_ENABLED
- **Type**: Boolean
- **Default**: `true`
- **Description**: Enable background maintenance jobs
- **Impact**: When disabled, no proactive maintenance occurs

```env
# Enable background jobs
TOKEN_REFRESH_BACKGROUND_JOBS_ENABLED=true

# Disable for maintenance
TOKEN_REFRESH_BACKGROUND_JOBS_ENABLED=false
```

#### TOKEN_MAINTENANCE_JOB_FREQUENCY
- **Type**: String
- **Default**: `everyFifteenMinutes`
- **Description**: Frequency for token maintenance job
- **Values**: Laravel scheduler frequency methods

```env
# Every 15 minutes (default)
TOKEN_MAINTENANCE_JOB_FREQUENCY=everyFifteenMinutes

# Every 30 minutes
TOKEN_MAINTENANCE_JOB_FREQUENCY=everyThirtyMinutes

# Hourly
TOKEN_MAINTENANCE_JOB_FREQUENCY=hourly
```

#### HEALTH_STATUS_VALIDATION_JOB_FREQUENCY
- **Type**: String
- **Default**: `hourly`
- **Description**: Frequency for health status validation job
- **Values**: Laravel scheduler frequency methods

```env
# Hourly validation (default)
HEALTH_STATUS_VALIDATION_JOB_FREQUENCY=hourly

# Every 30 minutes
HEALTH_STATUS_VALIDATION_JOB_FREQUENCY=everyThirtyMinutes

# Every 2 hours
HEALTH_STATUS_VALIDATION_JOB_FREQUENCY=everyTwoHours
```

### Performance Settings

#### TOKEN_REFRESH_CONNECTION_POOL_SIZE
- **Type**: Integer
- **Default**: `5`
- **Description**: Size of Google API connection pool
- **Range**: `1-20`
- **Impact**: Higher values improve concurrency but use more memory

```env
# Standard pool size
TOKEN_REFRESH_CONNECTION_POOL_SIZE=5

# Larger pool for high-traffic
TOKEN_REFRESH_CONNECTION_POOL_SIZE=10

# Smaller pool for resource-constrained environments
TOKEN_REFRESH_CONNECTION_POOL_SIZE=2
```

#### TOKEN_REFRESH_BATCH_SIZE
- **Type**: Integer
- **Default**: `10`
- **Description**: Number of tokens to process in batch operations
- **Range**: `1-50`
- **Impact**: Larger batches improve efficiency but may cause timeouts

```env
# Standard batch size
TOKEN_REFRESH_BATCH_SIZE=10

# Larger batches for better performance
TOKEN_REFRESH_BATCH_SIZE=25

# Smaller batches for stability
TOKEN_REFRESH_BATCH_SIZE=5
```

## Configuration File Settings

### config/token-refresh.php

#### Core Configuration Structure
```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Token Refresh Configuration
    |--------------------------------------------------------------------------
    */
    
    'enabled' => env('TOKEN_REFRESH_ENABLED', true),
    
    'proactive' => [
        'enabled' => env('TOKEN_REFRESH_PROACTIVE_ENABLED', true),
        'minutes_before_expiry' => env('TOKEN_REFRESH_PROACTIVE_MINUTES', 15),
        'batch_size' => env('TOKEN_REFRESH_BATCH_SIZE', 10),
    ],
    
    'retry' => [
        'max_attempts' => env('TOKEN_REFRESH_MAX_RETRY_ATTEMPTS', 5),
        'delays' => array_map('intval', explode(',', env('TOKEN_REFRESH_RETRY_DELAYS', '1,2,4,8,16'))),
        'exponential_backoff' => env('TOKEN_REFRESH_EXPONENTIAL_BACKOFF', true),
    ],
    
    'rate_limiting' => [
        'enabled' => env('TOKEN_REFRESH_RATE_LIMITING_ENABLED', true),
        'max_attempts' => env('TOKEN_REFRESH_RATE_LIMIT_ATTEMPTS', 5),
        'window_seconds' => env('TOKEN_REFRESH_RATE_LIMIT_WINDOW', 3600),
        'cache_prefix' => 'token_refresh_rate_limit',
    ],
    
    'health_validation' => [
        'live_enabled' => env('HEALTH_VALIDATION_LIVE_ENABLED', true),
        'cache_ttl' => [
            'healthy' => env('HEALTH_VALIDATION_CACHE_TTL_HEALTHY', 30),
            'error' => env('HEALTH_VALIDATION_CACHE_TTL_ERROR', 10),
        ],
        'rate_limit' => env('HEALTH_VALIDATION_RATE_LIMIT', 60),
        'timeout_seconds' => env('HEALTH_VALIDATION_TIMEOUT', 10),
    ],
    
    'notifications' => [
        'enabled' => env('TOKEN_NOTIFICATION_ENABLED', true),
        'throttle' => [
            'enabled' => env('TOKEN_NOTIFICATION_THROTTLE_ENABLED', true),
            'hours' => env('TOKEN_NOTIFICATION_THROTTLE_HOURS', 24),
        ],
        'admin_email' => env('TOKEN_NOTIFICATION_ADMIN_EMAIL'),
        'escalation_enabled' => env('TOKEN_NOTIFICATION_ESCALATION_ENABLED', true),
    ],
    
    'background_jobs' => [
        'enabled' => env('TOKEN_REFRESH_BACKGROUND_JOBS_ENABLED', true),
        'queues' => [
            'high_priority' => env('TOKEN_REFRESH_HIGH_PRIORITY_QUEUE', 'high'),
            'maintenance' => env('TOKEN_REFRESH_MAINTENANCE_QUEUE', 'maintenance'),
        ],
        'frequencies' => [
            'token_maintenance' => env('TOKEN_MAINTENANCE_JOB_FREQUENCY', 'everyFifteenMinutes'),
            'health_validation' => env('HEALTH_STATUS_VALIDATION_JOB_FREQUENCY', 'hourly'),
            'cleanup' => env('TOKEN_CLEANUP_JOB_FREQUENCY', 'daily'),
        ],
    ],
    
    'performance' => [
        'connection_pool_size' => env('TOKEN_REFRESH_CONNECTION_POOL_SIZE', 5),
        'query_optimization' => env('TOKEN_REFRESH_QUERY_OPTIMIZATION', true),
        'cache_warming' => env('TOKEN_REFRESH_CACHE_WARMING', true),
    ],
    
    'logging' => [
        'enabled' => env('TOKEN_REFRESH_LOGGING_ENABLED', true),
        'level' => env('TOKEN_REFRESH_LOG_LEVEL', 'info'),
        'channels' => [
            'token_refresh' => env('TOKEN_REFRESH_LOG_CHANNEL', 'token_refresh'),
            'health_validation' => env('HEALTH_VALIDATION_LOG_CHANNEL', 'health_validation'),
        ],
        'structured' => env('TOKEN_REFRESH_STRUCTURED_LOGGING', true),
    ],
    
    'security' => [
        'token_encryption' => env('TOKEN_REFRESH_ENCRYPTION_ENABLED', true),
        'audit_logging' => env('TOKEN_REFRESH_AUDIT_LOGGING', true),
        'ip_whitelist' => env('TOKEN_REFRESH_IP_WHITELIST'),
    ],
];
```

## Environment-Specific Configurations

### Development Environment

#### .env.development
```env
# Development-optimized settings
TOKEN_REFRESH_ENABLED=true
TOKEN_REFRESH_PROACTIVE_MINUTES=5
TOKEN_REFRESH_MAX_RETRY_ATTEMPTS=3
TOKEN_REFRESH_RETRY_DELAYS=1,1,2

# Faster validation for testing
HEALTH_VALIDATION_CACHE_TTL_HEALTHY=10
HEALTH_VALIDATION_CACHE_TTL_ERROR=5
HEALTH_VALIDATION_RATE_LIMIT=120

# More frequent notifications for testing
TOKEN_NOTIFICATION_THROTTLE_HOURS=1

# Verbose logging
TOKEN_REFRESH_LOG_LEVEL=debug
LOG_LEVEL=debug

# Smaller batches for testing
TOKEN_REFRESH_BATCH_SIZE=3
TOKEN_REFRESH_CONNECTION_POOL_SIZE=2

# More frequent background jobs
TOKEN_MAINTENANCE_JOB_FREQUENCY=everyFiveMinutes
HEALTH_STATUS_VALIDATION_JOB_FREQUENCY=everyTenMinutes
```

### Staging Environment

#### .env.staging
```env
# Staging settings (production-like but more permissive)
TOKEN_REFRESH_ENABLED=true
TOKEN_REFRESH_PROACTIVE_MINUTES=10
TOKEN_REFRESH_MAX_RETRY_ATTEMPTS=5
TOKEN_REFRESH_RETRY_DELAYS=1,2,4,8,16

# Moderate caching
HEALTH_VALIDATION_CACHE_TTL_HEALTHY=20
HEALTH_VALIDATION_CACHE_TTL_ERROR=8
HEALTH_VALIDATION_RATE_LIMIT=90

# Moderate notification throttling
TOKEN_NOTIFICATION_THROTTLE_HOURS=12

# Info level logging
TOKEN_REFRESH_LOG_LEVEL=info
LOG_LEVEL=info

# Standard batch sizes
TOKEN_REFRESH_BATCH_SIZE=8
TOKEN_REFRESH_CONNECTION_POOL_SIZE=4

# Standard background job frequency
TOKEN_MAINTENANCE_JOB_FREQUENCY=everyTenMinutes
HEALTH_STATUS_VALIDATION_JOB_FREQUENCY=everyThirtyMinutes
```

### Production Environment

#### .env.production
```env
# Production-optimized settings
TOKEN_REFRESH_ENABLED=true
TOKEN_REFRESH_PROACTIVE_MINUTES=15
TOKEN_REFRESH_MAX_RETRY_ATTEMPTS=5
TOKEN_REFRESH_RETRY_DELAYS=2,5,10,20,40

# Optimized caching
HEALTH_VALIDATION_CACHE_TTL_HEALTHY=30
HEALTH_VALIDATION_CACHE_TTL_ERROR=10
HEALTH_VALIDATION_RATE_LIMIT=60

# Standard notification throttling
TOKEN_NOTIFICATION_THROTTLE_HOURS=24
TOKEN_NOTIFICATION_ADMIN_EMAIL=admin@yourcompany.com

# Warning level logging (reduce noise)
TOKEN_REFRESH_LOG_LEVEL=warning
LOG_LEVEL=warning

# Optimized batch sizes
TOKEN_REFRESH_BATCH_SIZE=15
TOKEN_REFRESH_CONNECTION_POOL_SIZE=8

# Standard background job frequency
TOKEN_MAINTENANCE_JOB_FREQUENCY=everyFifteenMinutes
HEALTH_STATUS_VALIDATION_JOB_FREQUENCY=hourly

# Enhanced security
TOKEN_REFRESH_ENCRYPTION_ENABLED=true
TOKEN_REFRESH_AUDIT_LOGGING=true
```

### High-Traffic Environment

#### .env.high-traffic
```env
# High-traffic optimized settings
TOKEN_REFRESH_ENABLED=true
TOKEN_REFRESH_PROACTIVE_MINUTES=30
TOKEN_REFRESH_MAX_RETRY_ATTEMPTS=3
TOKEN_REFRESH_RETRY_DELAYS=5,10,20

# Aggressive caching
HEALTH_VALIDATION_CACHE_TTL_HEALTHY=60
HEALTH_VALIDATION_CACHE_TTL_ERROR=15
HEALTH_VALIDATION_RATE_LIMIT=30

# Reduced notification frequency
TOKEN_NOTIFICATION_THROTTLE_HOURS=48

# Error level logging only
TOKEN_REFRESH_LOG_LEVEL=error
LOG_LEVEL=error

# Large batch sizes for efficiency
TOKEN_REFRESH_BATCH_SIZE=25
TOKEN_REFRESH_CONNECTION_POOL_SIZE=15

# Less frequent background jobs
TOKEN_MAINTENANCE_JOB_FREQUENCY=everyThirtyMinutes
HEALTH_STATUS_VALIDATION_JOB_FREQUENCY=everyTwoHours

# Performance optimizations
TOKEN_REFRESH_QUERY_OPTIMIZATION=true
TOKEN_REFRESH_CACHE_WARMING=true
```

## Advanced Configuration Options

### Custom Error Handling

#### Error Type Configuration
```php
// config/token-refresh.php
'error_handling' => [
    'recoverable_errors' => [
        'network_timeout',
        'service_unavailable',
        'api_quota_exceeded',
    ],
    'permanent_errors' => [
        'invalid_refresh_token',
        'expired_refresh_token',
        'revoked_access',
    ],
    'retry_strategies' => [
        'network_timeout' => 'exponential_backoff',
        'service_unavailable' => 'linear_backoff',
        'api_quota_exceeded' => 'quota_reset_wait',
    ],
],
```

### Queue Configuration

#### Custom Queue Settings
```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => false,
    ],
    
    // High priority queue for token operations
    'redis-high' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'high',
        'retry_after' => 60,
        'block_for' => null,
        'after_commit' => false,
    ],
    
    // Maintenance queue for background jobs
    'redis-maintenance' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'maintenance',
        'retry_after' => 300,
        'block_for' => null,
        'after_commit' => false,
    ],
],
```

### Monitoring Configuration

#### Metrics Collection Settings
```php
// config/token-refresh.php
'monitoring' => [
    'metrics_enabled' => env('TOKEN_REFRESH_METRICS_ENABLED', true),
    'metrics_retention_days' => env('TOKEN_REFRESH_METRICS_RETENTION', 30),
    'prometheus_enabled' => env('TOKEN_REFRESH_PROMETHEUS_ENABLED', false),
    'dashboard_enabled' => env('TOKEN_REFRESH_DASHBOARD_ENABLED', true),
    'alerts' => [
        'success_rate_threshold' => env('TOKEN_REFRESH_SUCCESS_RATE_THRESHOLD', 90),
        'latency_threshold_ms' => env('TOKEN_REFRESH_LATENCY_THRESHOLD', 5000),
        'queue_depth_threshold' => env('TOKEN_REFRESH_QUEUE_DEPTH_THRESHOLD', 50),
    ],
],
```

## Configuration Validation

### Validation Rules

The system includes built-in validation for configuration values:

```php
// app/Services/TokenRefreshConfigService.php
public function validateConfiguration(): array
{
    $errors = [];
    
    // Validate proactive minutes
    $proactiveMinutes = config('token-refresh.proactive.minutes_before_expiry');
    if ($proactiveMinutes < 5 || $proactiveMinutes > 60) {
        $errors[] = 'Proactive minutes must be between 5 and 60';
    }
    
    // Validate retry attempts
    $maxAttempts = config('token-refresh.retry.max_attempts');
    if ($maxAttempts < 1 || $maxAttempts > 10) {
        $errors[] = 'Max retry attempts must be between 1 and 10';
    }
    
    // Validate cache TTL
    $healthyTtl = config('token-refresh.health_validation.cache_ttl.healthy');
    $errorTtl = config('token-refresh.health_validation.cache_ttl.error');
    if ($errorTtl >= $healthyTtl) {
        $errors[] = 'Error cache TTL should be less than healthy cache TTL';
    }
    
    return $errors;
}
```

### Configuration Testing

#### Test Configuration Command
```bash
# Test current configuration
php artisan token-refresh:test-config

# Validate specific environment
php artisan token-refresh:test-config --env=production

# Test with custom values
php artisan token-refresh:test-config --proactive-minutes=20 --max-attempts=3
```

## Migration Between Configurations

### Configuration Migration Script

```bash
#!/bin/bash
# migrate-token-config.sh

# Backup current configuration
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Apply new configuration based on environment
case "$1" in
    "development")
        cp .env.development .env
        ;;
    "staging")
        cp .env.staging .env
        ;;
    "production")
        cp .env.production .env
        ;;
    *)
        echo "Usage: $0 {development|staging|production}"
        exit 1
        ;;
esac

# Clear configuration cache
php artisan config:clear

# Validate new configuration
php artisan token-refresh:test-config

echo "Configuration migrated to $1 environment"
```

This comprehensive configuration reference ensures that the Google Drive Token Auto-Renewal System can be properly configured for any environment and use case.