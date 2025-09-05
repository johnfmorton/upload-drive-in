# Google Drive Token Auto-Renewal System - Deployment Guide

## Overview

This guide provides step-by-step instructions for deploying the Google Drive Token Auto-Renewal System, which addresses critical issues with token management and health status reporting.

## Pre-Deployment Checklist

### System Requirements
- PHP 8.3+
- Laravel 12+
- Redis (for caching and queue management)
- MySQL/MariaDB 8.0+
- Google Drive API credentials

### Environment Preparation
- [ ] Backup production database
- [ ] Verify Redis is running and accessible
- [ ] Confirm queue workers are operational
- [ ] Test Google Drive API connectivity
- [ ] Review current token status in production

## Step-by-Step Deployment

### Phase 1: Database Migrations

1. **Run the token tracking enhancement migration:**
   ```bash
   php artisan migrate --path=database/migrations/2025_09_05_110150_add_tracking_fields_to_google_drive_tokens_table.php
   ```

2. **Run the health status enhancement migration:**
   ```bash
   php artisan migrate --path=database/migrations/2025_09_05_120000_add_live_validation_fields_to_cloud_storage_health_statuses_table.php
   ```

3. **Run the performance optimization migration:**
   ```bash
   php artisan migrate --path=database/migrations/2025_09_05_173336_add_performance_optimization_indexes.php
   ```

4. **Verify migrations completed successfully:**
   ```bash
   php artisan migrate:status
   ```

### Phase 2: Configuration Updates

1. **Update environment configuration:**
   ```bash
   # Copy token refresh configuration template
   cp .env.token-refresh.example .env.token-refresh
   
   # Add to your .env file:
   TOKEN_REFRESH_ENABLED=true
   TOKEN_REFRESH_PROACTIVE_MINUTES=15
   TOKEN_REFRESH_MAX_RETRY_ATTEMPTS=5
   TOKEN_REFRESH_NOTIFICATION_THROTTLE_HOURS=24
   TOKEN_REFRESH_RATE_LIMIT_ATTEMPTS=5
   TOKEN_REFRESH_RATE_LIMIT_WINDOW=3600
   ```

2. **Publish configuration files:**
   ```bash
   php artisan vendor:publish --tag=token-refresh-config
   ```

3. **Clear configuration cache:**
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

### Phase 3: Service Registration

1. **Verify service providers are registered in `config/app.php`:**
   ```php
   'providers' => [
       // ... other providers
       App\Providers\TokenRefreshConfigServiceProvider::class,
   ],
   ```

2. **Register facades if needed:**
   ```php
   'aliases' => [
       // ... other aliases
       'TokenRefreshConfig' => App\Facades\TokenRefreshConfig::class,
   ],
   ```

### Phase 4: Queue Configuration

1. **Update queue configuration for new jobs:**
   ```bash
   # Add to config/queue.php connections
   'redis' => [
       'driver' => 'redis',
       'connection' => 'default',
       'queue' => env('REDIS_QUEUE', 'default'),
       'retry_after' => 90,
       'block_for' => null,
       'after_commit' => false,
   ],
   ```

2. **Start queue workers for token maintenance:**
   ```bash
   # High priority queue for immediate token refreshes
   php artisan queue:work --queue=high --tries=3 --timeout=60
   
   # Maintenance queue for background jobs
   php artisan queue:work --queue=maintenance --tries=1 --timeout=300
   ```

### Phase 5: Scheduler Configuration

1. **Add to `app/Console/Kernel.php`:**
   ```php
   protected function schedule(Schedule $schedule)
   {
       // Token maintenance every 15 minutes
       $schedule->job(new TokenMaintenanceJob())
                ->everyFifteenMinutes()
                ->withoutOverlapping();
       
       // Health status validation every hour
       $schedule->job(new HealthStatusValidationJob())
                ->hourly()
                ->withoutOverlapping();
       
       // Cleanup failed attempts daily
       $schedule->job(new CleanupFailedRefreshAttemptsJob())
                ->daily()
                ->withoutOverlapping();
   }
   ```

2. **Verify cron is configured:**
   ```bash
   # Add to crontab
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```

### Phase 6: Cache Configuration

1. **Configure Redis for health status caching:**
   ```bash
   # Add to .env
   CACHE_DRIVER=redis
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

2. **Clear and warm cache:**
   ```bash
   php artisan cache:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## Post-Deployment Verification

### 1. Database Verification
```bash
# Check new fields exist
php artisan tinker
>>> App\Models\GoogleDriveToken::first()->toArray()
>>> App\Models\CloudStorageHealthStatus::first()->toArray()
```

### 2. Service Verification
```bash
# Test token refresh service
php artisan tinker
>>> $service = app(App\Services\ProactiveTokenRenewalService::class)
>>> $user = App\Models\User::first()
>>> $result = $service->refreshTokenIfNeeded($user, 'google-drive')
>>> $result->isSuccessful()
```

### 3. Health Status Verification
```bash
# Test real-time health validation
php artisan tinker
>>> $validator = app(App\Services\RealTimeHealthValidator::class)
>>> $user = App\Models\User::first()
>>> $status = $validator->validateConnectionHealth($user, 'google-drive')
>>> $status->isHealthy()
```

### 4. Queue Job Verification
```bash
# Dispatch test jobs
php artisan tinker
>>> dispatch(new App\Jobs\TokenMaintenanceJob())
>>> dispatch(new App\Jobs\HealthStatusValidationJob())

# Check job execution
php artisan queue:work --once
```

### 5. Dashboard Verification
- Navigate to admin dashboard
- Verify token status widget displays correctly
- Test "Test Connection" button functionality
- Confirm status accuracy matches actual connection state

## Configuration Options

### Token Refresh Settings

| Setting | Default | Description |
|---------|---------|-------------|
| `TOKEN_REFRESH_ENABLED` | `true` | Enable/disable automatic token refresh |
| `TOKEN_REFRESH_PROACTIVE_MINUTES` | `15` | Minutes before expiration to refresh |
| `TOKEN_REFRESH_MAX_RETRY_ATTEMPTS` | `5` | Maximum retry attempts for failed refreshes |
| `TOKEN_REFRESH_NOTIFICATION_THROTTLE_HOURS` | `24` | Hours between duplicate notifications |
| `TOKEN_REFRESH_RATE_LIMIT_ATTEMPTS` | `5` | Max refresh attempts per hour per user |
| `TOKEN_REFRESH_RATE_LIMIT_WINDOW` | `3600` | Rate limit window in seconds |

### Health Validation Settings

| Setting | Default | Description |
|---------|---------|-------------|
| `HEALTH_VALIDATION_CACHE_TTL_HEALTHY` | `30` | Cache TTL for healthy status (seconds) |
| `HEALTH_VALIDATION_CACHE_TTL_ERROR` | `10` | Cache TTL for error status (seconds) |
| `HEALTH_VALIDATION_LIVE_ENABLED` | `true` | Enable live API validation |
| `HEALTH_VALIDATION_RATE_LIMIT` | `60` | Max validations per minute |

### Notification Settings

| Setting | Default | Description |
|---------|---------|-------------|
| `TOKEN_NOTIFICATION_ENABLED` | `true` | Enable email notifications |
| `TOKEN_NOTIFICATION_ADMIN_EMAIL` | `null` | Admin email for escalations |
| `TOKEN_NOTIFICATION_THROTTLE_ENABLED` | `true` | Enable notification throttling |

## Environment-Specific Configurations

### Development Environment
```env
TOKEN_REFRESH_ENABLED=true
TOKEN_REFRESH_PROACTIVE_MINUTES=5
TOKEN_REFRESH_MAX_RETRY_ATTEMPTS=3
TOKEN_REFRESH_NOTIFICATION_THROTTLE_HOURS=1
HEALTH_VALIDATION_CACHE_TTL_HEALTHY=10
HEALTH_VALIDATION_CACHE_TTL_ERROR=5
LOG_LEVEL=debug
```

### Staging Environment
```env
TOKEN_REFRESH_ENABLED=true
TOKEN_REFRESH_PROACTIVE_MINUTES=10
TOKEN_REFRESH_MAX_RETRY_ATTEMPTS=5
TOKEN_REFRESH_NOTIFICATION_THROTTLE_HOURS=12
HEALTH_VALIDATION_CACHE_TTL_HEALTHY=20
HEALTH_VALIDATION_CACHE_TTL_ERROR=8
LOG_LEVEL=info
```

### Production Environment
```env
TOKEN_REFRESH_ENABLED=true
TOKEN_REFRESH_PROACTIVE_MINUTES=15
TOKEN_REFRESH_MAX_RETRY_ATTEMPTS=5
TOKEN_REFRESH_NOTIFICATION_THROTTLE_HOURS=24
HEALTH_VALIDATION_CACHE_TTL_HEALTHY=30
HEALTH_VALIDATION_CACHE_TTL_ERROR=10
LOG_LEVEL=warning
```

## Rollback Procedures

### Emergency Rollback (Immediate)

1. **Disable feature flags:**
   ```bash
   # Add to .env
   TOKEN_REFRESH_ENABLED=false
   HEALTH_VALIDATION_LIVE_ENABLED=false
   
   # Clear config cache
   php artisan config:clear
   ```

2. **Stop background jobs:**
   ```bash
   # Kill queue workers
   php artisan queue:clear
   
   # Remove from scheduler temporarily
   # Comment out jobs in app/Console/Kernel.php
   ```

3. **Clear problematic cache:**
   ```bash
   php artisan cache:clear
   redis-cli FLUSHDB
   ```

### Partial Rollback (Selective)

1. **Disable specific features:**
   ```env
   # Keep token refresh but disable live validation
   TOKEN_REFRESH_ENABLED=true
   HEALTH_VALIDATION_LIVE_ENABLED=false
   
   # Or disable notifications only
   TOKEN_NOTIFICATION_ENABLED=false
   ```

2. **Revert to cached health status:**
   ```bash
   php artisan tinker
   >>> App\Models\CloudStorageHealthStatus::query()->update(['live_validation_result' => null])
   ```

### Full Rollback (Database)

1. **Rollback migrations (if necessary):**
   ```bash
   # Only if new fields cause issues
   php artisan migrate:rollback --step=3
   ```

2. **Restore from backup:**
   ```bash
   # Restore database backup if critical issues occur
   mysql -u username -p database_name < backup_file.sql
   ```

## Monitoring During Deployment

### Key Metrics to Watch

1. **Token Refresh Success Rate:**
   ```bash
   # Monitor logs for refresh attempts
   tail -f storage/logs/laravel.log | grep "Token refresh"
   ```

2. **Health Status Accuracy:**
   ```bash
   # Check dashboard vs actual API connectivity
   php artisan tinker
   >>> $users = App\Models\User::has('googleDriveTokens')->get()
   >>> foreach($users as $user) { /* test each user */ }
   ```

3. **Queue Performance:**
   ```bash
   # Monitor queue depth
   php artisan queue:monitor redis:default,redis:high,redis:maintenance
   ```

4. **Error Rates:**
   ```bash
   # Watch for increased error rates
   tail -f storage/logs/laravel.log | grep "ERROR"
   ```

### Rollback Triggers

Initiate rollback if:
- Token refresh success rate drops below 90%
- Upload failure rate increases by more than 20%
- Dashboard shows incorrect status for more than 10% of users
- Queue jobs fail consistently for more than 5 minutes
- Memory usage increases by more than 50%

## Success Criteria

Deployment is successful when:
- [ ] All migrations completed without errors
- [ ] Token refresh works automatically for expiring tokens
- [ ] Dashboard shows accurate real-time status
- [ ] Upload jobs succeed with expired tokens (auto-refresh)
- [ ] Notifications are sent for permanent failures
- [ ] Background maintenance jobs run successfully
- [ ] Performance metrics are within acceptable ranges
- [ ] No increase in error rates or failed uploads

## Support and Troubleshooting

For issues during deployment:
1. Check the troubleshooting guide: `docs/google-drive-token-troubleshooting-guide.md`
2. Review monitoring dashboard: `/admin/token-monitoring`
3. Analyze logs: `php artisan analyze:token-refresh-logs`
4. Contact development team with specific error messages and logs

## Next Steps

After successful deployment:
1. Monitor system for 24-48 hours
2. Review token refresh success rates
3. Validate notification delivery
4. Schedule regular health checks
5. Plan for gradual feature rollout if using feature flags