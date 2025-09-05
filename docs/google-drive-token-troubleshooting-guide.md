# Google Drive Token Auto-Renewal System - Troubleshooting Guide

## Common Issues and Solutions

### Token Refresh Issues

#### Issue: Tokens Not Refreshing Automatically

**Symptoms:**
- Uploads fail with authentication errors
- Dashboard shows "Authentication Required" despite recent connection
- Logs show "Token expired" errors

**Diagnosis:**
```bash
# Check token status
php artisan tinker
>>> $user = App\Models\User::find(1)
>>> $token = $user->googleDriveTokens()->first()
>>> $token->expires_at
>>> $token->refresh_token
>>> $token->last_refresh_attempt_at
```

**Solutions:**

1. **Verify Token Refresh Service is Running:**
   ```bash
   # Test manual refresh
   php artisan tinker
   >>> $service = app(App\Services\ProactiveTokenRenewalService::class)
   >>> $result = $service->refreshTokenIfNeeded($user, 'google-drive')
   >>> $result->isSuccessful()
   >>> $result->getErrorMessage()
   ```

2. **Check Queue Workers:**
   ```bash
   # Ensure queue workers are running
   php artisan queue:work --once
   
   # Check for failed jobs
   php artisan queue:failed
   ```

3. **Verify Configuration:**
   ```bash
   # Check token refresh is enabled
   php artisan config:show token-refresh.enabled
   php artisan config:show token-refresh.proactive_minutes
   ```

#### Issue: Refresh Token Invalid/Expired

**Symptoms:**
- Logs show "invalid_grant" or "refresh_token_expired" errors
- User receives "Reconnection Required" notifications
- Dashboard shows "Authentication Required" status

**Diagnosis:**
```bash
# Check refresh token validity
php artisan tinker
>>> $token = App\Models\GoogleDriveToken::where('user_id', 1)->first()
>>> $token->refresh_token
>>> $token->refresh_failure_count
>>> $token->requires_user_intervention
```

**Solutions:**

1. **Force User Re-authentication:**
   ```bash
   # Mark token as requiring intervention
   php artisan tinker
   >>> $token->update(['requires_user_intervention' => true])
   ```

2. **Clear Invalid Tokens:**
   ```bash
   # Remove invalid tokens (user will need to reconnect)
   php artisan tinker
   >>> App\Models\GoogleDriveToken::where('requires_user_intervention', true)->delete()
   ```

3. **Check Google API Console:**
   - Verify OAuth consent screen is published
   - Check if refresh tokens have been revoked
   - Ensure redirect URIs match exactly

#### Issue: Rate Limiting Errors

**Symptoms:**
- Logs show "rate_limit_exceeded" or "quota_exceeded" errors
- Multiple refresh attempts failing
- Delays in token refresh processing

**Diagnosis:**
```bash
# Check rate limiting status
php artisan tinker
>>> $service = app(App\Services\TokenSecurityService::class)
>>> $user = App\Models\User::find(1)
>>> $service->canAttemptRefresh($user, 'google-drive')
>>> $service->getRemainingAttempts($user, 'google-drive')
```

**Solutions:**

1. **Reset Rate Limits:**
   ```bash
   # Clear rate limit cache
   php artisan cache:forget("token_refresh_attempts_1_google-drive")
   ```

2. **Adjust Rate Limit Settings:**
   ```env
   # Increase limits temporarily
   TOKEN_REFRESH_RATE_LIMIT_ATTEMPTS=10
   TOKEN_REFRESH_RATE_LIMIT_WINDOW=7200
   ```

3. **Implement Exponential Backoff:**
   ```bash
   # Check backoff configuration
   php artisan config:show token-refresh.retry_delays
   ```

### Health Status Issues

#### Issue: Dashboard Shows Incorrect Status

**Symptoms:**
- Dashboard shows "Connected" but uploads fail
- Status doesn't update after reconnection
- Inconsistent status between dashboard and actual connectivity

**Diagnosis:**
```bash
# Test live validation
php artisan tinker
>>> $validator = app(App\Services\RealTimeHealthValidator::class)
>>> $user = App\Models\User::find(1)
>>> $status = $validator->validateConnectionHealth($user, 'google-drive')
>>> $status->isHealthy()
>>> $status->getErrorMessage()
```

**Solutions:**

1. **Clear Health Status Cache:**
   ```bash
   # Clear cached status
   php artisan cache:forget("health_status_1_google-drive")
   
   # Force live validation
   php artisan tinker
   >>> $validator->validateConnectionHealth($user, 'google-drive', true)
   ```

2. **Update Health Status Records:**
   ```bash
   # Recalculate consolidated status
   php artisan tinker
   >>> $service = app(App\Services\CloudStorageHealthService::class)
   >>> $users = App\Models\User::has('googleDriveTokens')->get()
   >>> foreach($users as $user) {
   >>>     $service->updateHealthStatus($user, 'google-drive')
   >>> }
   ```

3. **Check Live Validation Settings:**
   ```env
   # Ensure live validation is enabled
   HEALTH_VALIDATION_LIVE_ENABLED=true
   HEALTH_VALIDATION_CACHE_TTL_HEALTHY=30
   HEALTH_VALIDATION_CACHE_TTL_ERROR=10
   ```

#### Issue: Health Validation Taking Too Long

**Symptoms:**
- Dashboard loads slowly
- Timeouts when checking connection status
- High CPU usage during health checks

**Diagnosis:**
```bash
# Check validation performance
php artisan tinker
>>> $start = microtime(true)
>>> $validator = app(App\Services\RealTimeHealthValidator::class)
>>> $status = $validator->validateConnectionHealth($user, 'google-drive')
>>> $duration = (microtime(true) - $start) * 1000
>>> echo "Validation took: {$duration}ms"
```

**Solutions:**

1. **Optimize Cache Settings:**
   ```env
   # Increase cache TTL for healthy status
   HEALTH_VALIDATION_CACHE_TTL_HEALTHY=60
   HEALTH_VALIDATION_CACHE_TTL_ERROR=15
   ```

2. **Use Performance Optimized Validator:**
   ```bash
   # Switch to optimized validator
   php artisan tinker
   >>> $validator = app(App\Services\PerformanceOptimizedHealthValidator::class)
   ```

3. **Implement Rate Limiting:**
   ```env
   # Limit validation frequency
   HEALTH_VALIDATION_RATE_LIMIT=30
   ```

### Notification Issues

#### Issue: Users Not Receiving Notifications

**Symptoms:**
- Token failures occur but no emails sent
- Users unaware of connection issues
- Notifications appear in logs but not delivered

**Diagnosis:**
```bash
# Check notification service
php artisan tinker
>>> $service = app(App\Services\TokenRenewalNotificationService::class)
>>> $user = App\Models\User::find(1)
>>> $service->shouldSendNotification($user, 'google-drive', 'token_expired')

# Check mail configuration
php artisan tinker
>>> Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); })
```

**Solutions:**

1. **Verify Mail Configuration:**
   ```bash
   # Check mail settings
   php artisan config:show mail
   
   # Test mail delivery
   php artisan tinker
   >>> Mail::raw('Test notification', function($msg) use ($user) {
   >>>     $msg->to($user->email)->subject('Test Token Notification')
   >>> })
   ```

2. **Check Notification Throttling:**
   ```bash
   # Check throttle status
   php artisan tinker
   >>> $key = "notification_throttle_1_google-drive_token_expired"
   >>> Cache::get($key)
   
   # Clear throttle if needed
   >>> Cache::forget($key)
   ```

3. **Verify Queue Processing:**
   ```bash
   # Check if notification jobs are queued
   php artisan queue:work --once
   
   # Check failed notification jobs
   php artisan queue:failed
   ```

#### Issue: Too Many Notifications Sent

**Symptoms:**
- Users receive duplicate notifications
- Notification throttling not working
- Spam complaints from users

**Diagnosis:**
```bash
# Check notification history
php artisan tinker
>>> $token = App\Models\GoogleDriveToken::where('user_id', 1)->first()
>>> $token->last_notification_sent_at
>>> $token->notification_failure_count
```

**Solutions:**

1. **Enable/Fix Throttling:**
   ```env
   # Ensure throttling is enabled
   TOKEN_NOTIFICATION_THROTTLE_ENABLED=true
   TOKEN_NOTIFICATION_THROTTLE_HOURS=24
   ```

2. **Clear Notification History:**
   ```bash
   # Reset notification timestamps
   php artisan tinker
   >>> App\Models\GoogleDriveToken::query()->update([
   >>>     'last_notification_sent_at' => null,
   >>>     'notification_failure_count' => 0
   >>> ])
   ```

### Queue and Background Job Issues

#### Issue: Background Jobs Not Running

**Symptoms:**
- Tokens not refreshed proactively
- Health status not updated automatically
- Maintenance jobs not executing

**Diagnosis:**
```bash
# Check scheduler status
php artisan schedule:list

# Check queue workers
ps aux | grep "queue:work"

# Check failed jobs
php artisan queue:failed
```

**Solutions:**

1. **Start Queue Workers:**
   ```bash
   # Start high priority queue
   php artisan queue:work --queue=high --tries=3 --timeout=60 &
   
   # Start maintenance queue
   php artisan queue:work --queue=maintenance --tries=1 --timeout=300 &
   ```

2. **Verify Scheduler:**
   ```bash
   # Test scheduler manually
   php artisan schedule:run
   
   # Check cron configuration
   crontab -l | grep artisan
   ```

3. **Clear Failed Jobs:**
   ```bash
   # Retry all failed jobs
   php artisan queue:retry all
   
   # Or clear failed jobs
   php artisan queue:flush
   ```

#### Issue: Jobs Timing Out

**Symptoms:**
- Jobs marked as failed due to timeout
- Long-running token refresh operations
- Memory exhaustion in queue workers

**Diagnosis:**
```bash
# Check job execution time
tail -f storage/logs/laravel.log | grep "Job timeout"

# Monitor memory usage
php artisan queue:monitor redis:default,redis:high,redis:maintenance
```

**Solutions:**

1. **Increase Timeout Settings:**
   ```bash
   # Increase job timeout
   php artisan queue:work --timeout=120
   ```

2. **Optimize Job Performance:**
   ```bash
   # Use batch processing for multiple tokens
   php artisan tinker
   >>> $processor = app(App\Services\BatchTokenRefreshProcessor::class)
   >>> $processor->processBatch($users)
   ```

3. **Monitor Resource Usage:**
   ```bash
   # Check memory usage
   php artisan queue:work --memory=512
   ```

### Performance Issues

#### Issue: Slow Dashboard Loading

**Symptoms:**
- Dashboard takes >5 seconds to load
- High database query count
- Timeout errors in browser

**Diagnosis:**
```bash
# Enable query logging
php artisan tinker
>>> DB::enableQueryLog()
>>> // Load dashboard
>>> DB::getQueryLog()

# Check cache hit rates
php artisan tinker
>>> $optimizer = app(App\Services\TokenExpirationQueryOptimizer::class)
>>> $optimizer->getCacheStats()
```

**Solutions:**

1. **Optimize Database Queries:**
   ```bash
   # Run query optimization
   php artisan optimize:token-performance
   
   # Check index usage
   php artisan tinker
   >>> DB::select("SHOW INDEX FROM google_drive_tokens")
   ```

2. **Improve Caching:**
   ```bash
   # Warm cache for active users
   php artisan tinker
   >>> $service = app(App\Services\TokenStatusService::class)
   >>> $service->warmCacheForActiveUsers()
   ```

3. **Use Connection Pooling:**
   ```bash
   # Enable connection pooling
   php artisan tinker
   >>> $pool = app(App\Services\GoogleApiConnectionPool::class)
   >>> $pool->getConnection($user)
   ```

## Diagnostic Commands

### Token Status Analysis
```bash
# Comprehensive token analysis
php artisan analyze:token-refresh-logs --days=7

# Check token health for all users
php artisan tinker
>>> App\Models\User::has('googleDriveTokens')->get()->each(function($user) {
>>>     $token = $user->googleDriveTokens()->first()
>>>     echo "User {$user->id}: Expires {$token->expires_at}, Failures: {$token->refresh_failure_count}\n"
>>> })
```

### Health Status Verification
```bash
# Verify health status accuracy
php artisan tinker
>>> $users = App\Models\User::has('googleDriveTokens')->get()
>>> foreach($users as $user) {
>>>     $cached = app(App\Services\CloudStorageHealthService::class)->getHealthSummary($user)['google-drive']['status']
>>>     $live = app(App\Services\RealTimeHealthValidator::class)->validateConnectionHealth($user, 'google-drive')->isHealthy()
>>>     if($cached !== ($live ? 'healthy' : 'error')) {
>>>         echo "Mismatch for user {$user->id}: cached={$cached}, live={$live}\n"
>>>     }
>>> }
```

### Performance Analysis
```bash
# Analyze query performance
php artisan analyze:security-logs --type=performance

# Check cache effectiveness
php artisan tinker
>>> $stats = app(App\Services\TokenMonitoringDashboardService::class)->getCacheStats()
>>> print_r($stats)
```

## Log Analysis

### Key Log Patterns to Monitor

1. **Successful Token Refresh:**
   ```
   [INFO] Token refresh completed {"user_id":1,"provider":"google-drive","success":true,"duration_ms":1250}
   ```

2. **Failed Token Refresh:**
   ```
   [ERROR] Token refresh failed {"user_id":1,"provider":"google-drive","error":"invalid_grant","attempts":3}
   ```

3. **Health Status Mismatch:**
   ```
   [WARNING] Health status inconsistency detected {"user_id":1,"cached":"healthy","live":"error"}
   ```

4. **Rate Limiting:**
   ```
   [WARNING] Token refresh rate limited {"user_id":1,"attempts":5,"window":3600}
   ```

### Log Analysis Commands
```bash
# Search for token refresh issues
grep "Token refresh failed" storage/logs/laravel.log | tail -20

# Find rate limiting events
grep "rate limited" storage/logs/laravel.log | wc -l

# Check notification delivery
grep "Notification sent" storage/logs/laravel.log | tail -10
```

## Emergency Procedures

### Critical Token Failure (All Users Affected)

1. **Immediate Response:**
   ```bash
   # Disable automatic refresh to prevent further issues
   echo "TOKEN_REFRESH_ENABLED=false" >> .env
   php artisan config:clear
   ```

2. **Assess Scope:**
   ```bash
   # Count affected users
   php artisan tinker
   >>> App\Models\GoogleDriveToken::where('requires_user_intervention', true)->count()
   ```

3. **Notify Users:**
   ```bash
   # Send bulk notification
   php artisan tinker
   >>> $users = App\Models\User::has('googleDriveTokens')->get()
   >>> foreach($users as $user) {
   >>>     Mail::to($user)->send(new App\Mail\TokenExpiredMail($user, 'google-drive'))
   >>> }
   ```

### Database Corruption

1. **Stop All Processing:**
   ```bash
   # Stop queue workers
   pkill -f "queue:work"
   
   # Disable scheduler
   # Comment out jobs in app/Console/Kernel.php
   ```

2. **Restore from Backup:**
   ```bash
   # Restore database backup
   mysql -u username -p database_name < latest_backup.sql
   ```

3. **Verify Data Integrity:**
   ```bash
   # Check token data
   php artisan tinker
   >>> App\Models\GoogleDriveToken::count()
   >>> App\Models\CloudStorageHealthStatus::count()
   ```

## Prevention Strategies

### Monitoring Setup
- Set up alerts for token refresh failure rates >10%
- Monitor queue depth and processing times
- Track health status accuracy metrics
- Alert on notification delivery failures

### Regular Maintenance
- Weekly review of token refresh logs
- Monthly cleanup of old failed refresh attempts
- Quarterly review of rate limiting settings
- Annual review of Google API quotas and limits

### Testing Procedures
- Test token refresh in staging before production deployment
- Verify notification delivery to test accounts
- Load test dashboard with multiple concurrent users
- Test rollback procedures in staging environment

## Getting Help

### Information to Collect
When reporting issues, include:
- Laravel version and environment details
- Recent log entries (last 100 lines)
- Token status for affected users
- Queue worker status and configuration
- Cache configuration and Redis status

### Support Channels
1. Check this troubleshooting guide first
2. Review monitoring dashboard at `/admin/token-monitoring`
3. Analyze logs with `php artisan analyze:token-refresh-logs`
4. Contact development team with collected information

### Escalation Criteria
Escalate immediately if:
- >50% of users cannot upload files
- Token refresh success rate <80% for >1 hour
- Dashboard shows incorrect status for >25% of users
- Queue workers crash repeatedly
- Database corruption suspected