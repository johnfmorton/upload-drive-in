# Google Drive Token Auto-Renewal System - Rollback and Emergency Recovery Guide

## Overview

This guide provides comprehensive procedures for rolling back the Google Drive Token Auto-Renewal System and recovering from critical failures. Use these procedures when the system is causing more problems than it solves.

## Emergency Response Levels

### Level 1: Minor Issues (Service Degradation)
- Token refresh success rate 80-90%
- Occasional upload failures
- Dashboard showing some incorrect statuses
- **Response Time**: Within 30 minutes

### Level 2: Major Issues (Service Impairment)
- Token refresh success rate 60-80%
- Significant upload failures
- Multiple user complaints
- **Response Time**: Within 15 minutes

### Level 3: Critical Issues (Service Outage)
- Token refresh success rate <60%
- Widespread upload failures
- System causing more harm than benefit
- **Response Time**: Immediate (within 5 minutes)

## Quick Emergency Rollback (Level 3)

### Immediate Actions (Execute in Order)

#### 1. Disable Auto-Renewal System (30 seconds)
```bash
# Add to .env file
echo "TOKEN_REFRESH_ENABLED=false" >> .env
echo "HEALTH_VALIDATION_LIVE_ENABLED=false" >> .env

# Clear configuration cache
php artisan config:clear
```

#### 2. Stop Background Jobs (60 seconds)
```bash
# Kill all queue workers
pkill -f "queue:work"

# Clear all queues
php artisan queue:clear redis:default
php artisan queue:clear redis:high
php artisan queue:clear redis:maintenance

# Stop scheduler temporarily
# Comment out token-related jobs in app/Console/Kernel.php
```

#### 3. Clear Problematic Cache (30 seconds)
```bash
# Clear all health status cache
php artisan cache:clear

# Clear Redis cache specifically
redis-cli FLUSHDB

# Clear specific token-related cache keys
redis-cli DEL "health_status_*"
redis-cli DEL "token_refresh_*"
```

#### 4. Revert to Basic Health Status (2 minutes)
```bash
# Force all health statuses to use cached values only
php artisan tinker
>>> App\Models\CloudStorageHealthStatus::query()->update([
>>>     'live_validation_result' => null,
>>>     'last_live_validation_at' => null,
>>>     'requires_manual_intervention' => false
>>> ]);
```

#### 5. Verify System Recovery (2 minutes)
```bash
# Test basic functionality
php artisan tinker
>>> $user = App\Models\User::first()
>>> $service = app(App\Services\GoogleDriveService::class)
>>> $service->testConnection($user) // Should work with basic token validation
```

**Total Time**: ~5 minutes

## Selective Rollback Procedures

### Disable Specific Features

#### Disable Live Health Validation Only
```bash
# Keep token refresh but disable live validation
echo "HEALTH_VALIDATION_LIVE_ENABLED=false" >> .env
php artisan config:clear

# Revert health status to cached values
php artisan tinker
>>> App\Models\CloudStorageHealthStatus::whereNotNull('live_validation_result')
>>>     ->update(['live_validation_result' => null])
```

#### Disable Proactive Token Refresh Only
```bash
# Keep health validation but disable proactive refresh
echo "TOKEN_REFRESH_PROACTIVE_ENABLED=false" >> .env
php artisan config:clear

# Stop proactive refresh jobs
php artisan queue:clear redis:maintenance
```

#### Disable Notifications Only
```bash
# Keep system running but stop notifications
echo "TOKEN_NOTIFICATION_ENABLED=false" >> .env
php artisan config:clear

# Clear notification throttling
redis-cli DEL "notification_throttle_*"
```

### Partial Service Restoration

#### Restore Basic Token Refresh
```bash
# Enable basic refresh without proactive features
echo "TOKEN_REFRESH_ENABLED=true" >> .env
echo "TOKEN_REFRESH_PROACTIVE_ENABLED=false" >> .env
echo "TOKEN_REFRESH_BACKGROUND_JOBS_ENABLED=false" >> .env
php artisan config:clear

# Start minimal queue worker
php artisan queue:work --queue=high --tries=1 --timeout=30 &
```

#### Restore Health Validation with Longer Cache
```bash
# Enable health validation with extended cache
echo "HEALTH_VALIDATION_LIVE_ENABLED=true" >> .env
echo "HEALTH_VALIDATION_CACHE_TTL_HEALTHY=300" >> .env  # 5 minutes
echo "HEALTH_VALIDATION_CACHE_TTL_ERROR=60" >> .env     # 1 minute
php artisan config:clear
```

## Database Rollback Procedures

### Rollback Database Migrations

#### Check Migration Status
```bash
# See which migrations are applied
php artisan migrate:status | grep "google_drive_tokens\|cloud_storage_health"
```

#### Rollback Token Tracking Fields (If Causing Issues)
```bash
# Rollback token tracking migration
php artisan migrate:rollback --path=database/migrations/2025_09_05_110150_add_tracking_fields_to_google_drive_tokens_table.php

# Verify rollback
php artisan tinker
>>> Schema::hasColumn('google_drive_tokens', 'last_refresh_attempt_at')  // Should return false
```

#### Rollback Health Status Fields (If Causing Issues)
```bash
# Rollback health status migration
php artisan migrate:rollback --path=database/migrations/2025_09_05_120000_add_live_validation_fields_to_cloud_storage_health_statuses_table.php

# Verify rollback
php artisan tinker
>>> Schema::hasColumn('cloud_storage_health_statuses', 'live_validation_result')  // Should return false
```

#### Rollback Performance Indexes (If Causing Performance Issues)
```bash
# Rollback performance optimization migration
php artisan migrate:rollback --path=database/migrations/2025_09_05_173336_add_performance_optimization_indexes.php

# Check index removal
php artisan tinker
>>> DB::select("SHOW INDEX FROM google_drive_tokens WHERE Key_name LIKE 'idx_%'")  // Should be empty
```

### Data Recovery Procedures

#### Restore from Database Backup
```bash
# Stop all services first
pkill -f "queue:work"
php artisan down

# Restore database from backup
mysql -u username -p database_name < backup_before_token_system.sql

# Verify data integrity
php artisan tinker
>>> App\Models\GoogleDriveToken::count()
>>> App\Models\User::has('googleDriveTokens')->count()

# Bring system back up
php artisan up
```

#### Selective Data Restoration
```bash
# Restore only specific tables if needed
mysql -u username -p database_name -e "
DROP TABLE IF EXISTS google_drive_tokens_backup;
CREATE TABLE google_drive_tokens_backup AS SELECT * FROM google_drive_tokens;

-- Restore from backup
TRUNCATE google_drive_tokens;
INSERT INTO google_drive_tokens SELECT * FROM backup_google_drive_tokens;
"
```

## Service Recovery Procedures

### Restart All Services

#### Complete Service Restart
```bash
# 1. Stop all services
pkill -f "queue:work"
php artisan down

# 2. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
redis-cli FLUSHALL

# 3. Restart services
php artisan up
php artisan queue:work --queue=high --tries=3 --timeout=60 &
php artisan queue:work --queue=default --tries=3 --timeout=60 &

# 4. Verify services
php artisan queue:monitor redis:default,redis:high
```

#### Gradual Service Restoration
```bash
# 1. Start with basic functionality
echo "TOKEN_REFRESH_ENABLED=false" >> .env
echo "HEALTH_VALIDATION_LIVE_ENABLED=false" >> .env
php artisan config:clear

# 2. Test basic upload functionality
php artisan tinker
>>> $user = App\Models\User::first()
>>> dispatch(new App\Jobs\UploadToGoogleDrive($user, 'test-file.txt'))

# 3. Gradually enable features
echo "TOKEN_REFRESH_ENABLED=true" >> .env
php artisan config:clear
# Test token refresh...

echo "HEALTH_VALIDATION_LIVE_ENABLED=true" >> .env  
php artisan config:clear
# Test health validation...
```

### Queue System Recovery

#### Reset Queue System
```bash
# Clear all queues
php artisan queue:clear redis:default
php artisan queue:clear redis:high  
php artisan queue:clear redis:maintenance

# Reset failed jobs
php artisan queue:flush

# Restart workers with conservative settings
php artisan queue:work --queue=high --tries=1 --timeout=30 --memory=128 &
php artisan queue:work --queue=default --tries=1 --timeout=60 --memory=128 &
```

#### Recover Stuck Jobs
```bash
# Identify stuck jobs
php artisan queue:failed

# Retry specific jobs if safe
php artisan queue:retry 1,2,3  # Specific job IDs

# Or retry all if confident
php artisan queue:retry all
```

## Configuration Recovery

### Restore Original Configuration

#### Backup Current Configuration
```bash
# Backup current config before changes
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
cp config/token-refresh.php config/token-refresh.php.backup
```

#### Restore Minimal Configuration
```bash
# Create minimal .env settings
cat > .env.token-minimal << 'EOF'
TOKEN_REFRESH_ENABLED=false
HEALTH_VALIDATION_LIVE_ENABLED=false
TOKEN_NOTIFICATION_ENABLED=false
TOKEN_REFRESH_BACKGROUND_JOBS_ENABLED=false
EOF

# Apply minimal config
cat .env.token-minimal >> .env
php artisan config:clear
```

#### Restore Default Laravel Configuration
```bash
# Remove token-refresh config entirely
rm -f config/token-refresh.php

# Remove service provider registration
# Edit config/app.php and remove:
# App\Providers\TokenRefreshConfigServiceProvider::class,

# Clear all config
php artisan config:clear
```

### Environment-Specific Recovery

#### Production Recovery
```bash
# Production-safe rollback
echo "TOKEN_REFRESH_ENABLED=false" >> .env
echo "HEALTH_VALIDATION_LIVE_ENABLED=false" >> .env
echo "LOG_LEVEL=error" >> .env  # Reduce log noise
php artisan config:clear

# Monitor for 30 minutes before enabling features
```

#### Staging Recovery
```bash
# More aggressive rollback for staging
php artisan migrate:rollback --step=3  # Rollback all token migrations
php artisan cache:clear
redis-cli FLUSHALL

# Test with clean state
```

## Monitoring During Recovery

### Key Metrics to Watch

#### Immediate Metrics (First 15 minutes)
```bash
# Upload success rate
tail -f storage/logs/laravel.log | grep "UploadToGoogleDrive" | grep -c "success"

# Error rate
tail -f storage/logs/laravel.log | grep "ERROR" | wc -l

# Queue depth
redis-cli llen "queues:default"
redis-cli llen "queues:high"
```

#### Short-term Metrics (First hour)
```bash
# Token refresh attempts (should be minimal after rollback)
grep "Token refresh" storage/logs/laravel.log | tail -20

# Health validation calls (should be reduced)
grep "Health validation" storage/logs/laravel.log | tail -20

# User complaints (monitor support channels)
```

#### Long-term Metrics (First 24 hours)
- Overall upload success rate compared to pre-deployment baseline
- User satisfaction and complaint volume
- System resource usage (CPU, memory, database)
- Google API quota usage

### Recovery Success Criteria

#### Immediate Success (Within 15 minutes)
- [ ] Upload success rate returns to >95%
- [ ] No new error spikes in logs
- [ ] Queue depth returns to normal (<10 jobs)
- [ ] Dashboard loads without errors

#### Short-term Success (Within 1 hour)
- [ ] No user complaints about upload failures
- [ ] System resource usage normalized
- [ ] All critical functionality working
- [ ] No recurring error patterns

#### Long-term Success (Within 24 hours)
- [ ] Upload success rate matches pre-deployment baseline
- [ ] User satisfaction restored
- [ ] System stability maintained
- [ ] No hidden issues discovered

## Post-Recovery Analysis

### Root Cause Analysis

#### Data Collection
```bash
# Collect logs from failure period
cp storage/logs/laravel.log logs/failure-analysis-$(date +%Y%m%d).log

# Export relevant database data
mysqldump -u username -p database_name google_drive_tokens cloud_storage_health_statuses > failure-data-$(date +%Y%m%d).sql

# Collect system metrics
df -h > system-metrics-$(date +%Y%m%d).txt
free -h >> system-metrics-$(date +%Y%m%d).txt
ps aux >> system-metrics-$(date +%Y%m%d).txt
```

#### Analysis Questions
1. What was the trigger event that caused the failure?
2. Which component failed first (token refresh, health validation, notifications)?
3. How did the failure cascade through the system?
4. What monitoring alerts fired (or should have fired)?
5. How long did it take to detect and respond to the issue?
6. What was the user impact (number of affected users, duration)?

### Prevention Measures

#### Immediate Improvements
- Add missing monitoring alerts identified during incident
- Improve rollback procedures based on lessons learned
- Update documentation with new failure scenarios
- Enhance testing to cover the failure case

#### Long-term Improvements
- Implement circuit breakers for external API calls
- Add more granular feature flags for safer rollouts
- Improve automated testing coverage
- Enhance monitoring and alerting systems

## Recovery Testing

### Test Rollback Procedures

#### Staging Environment Testing
```bash
# Test complete rollback in staging
./test-rollback-staging.sh

# Verify functionality after rollback
./verify-basic-functionality.sh

# Test gradual re-enablement
./test-gradual-restore.sh
```

#### Production Readiness Checklist
- [ ] Rollback procedures tested in staging
- [ ] Recovery time objectives verified
- [ ] Monitoring alerts configured and tested
- [ ] Team trained on emergency procedures
- [ ] Communication plan prepared for users
- [ ] Backup and restore procedures verified

### Rollback Automation

#### Emergency Rollback Script
```bash
#!/bin/bash
# emergency-rollback.sh

echo "EMERGENCY ROLLBACK INITIATED at $(date)"

# 1. Disable features
echo "TOKEN_REFRESH_ENABLED=false" >> .env
echo "HEALTH_VALIDATION_LIVE_ENABLED=false" >> .env
php artisan config:clear

# 2. Stop background jobs
pkill -f "queue:work"
php artisan queue:clear redis:default
php artisan queue:clear redis:high
php artisan queue:clear redis:maintenance

# 3. Clear cache
php artisan cache:clear
redis-cli FLUSHDB

# 4. Verify basic functionality
php artisan tinker --execute="
\$user = App\Models\User::first();
\$service = app(App\Services\GoogleDriveService::class);
echo \$service->testConnection(\$user) ? 'SUCCESS' : 'FAILED';
"

echo "EMERGENCY ROLLBACK COMPLETED at $(date)"
```

## Communication During Recovery

### Internal Communication

#### Incident Response Team
- **Incident Commander**: Coordinates response
- **Technical Lead**: Executes rollback procedures  
- **Communications Lead**: Updates stakeholders
- **Monitoring Lead**: Tracks metrics and recovery

#### Status Updates
- **Immediate**: Incident declared, rollback initiated
- **15 minutes**: Rollback status, initial impact assessment
- **1 hour**: Recovery status, user impact, next steps
- **24 hours**: Final status, lessons learned, prevention measures

### User Communication

#### During Incident
- **Status Page Update**: "Investigating upload issues"
- **Email to Admins**: Brief explanation and expected resolution time
- **Dashboard Notice**: "Service temporarily degraded - uploads may be delayed"

#### Post-Recovery
- **Status Page Update**: "Service restored - all systems operational"
- **Email Summary**: What happened, what was fixed, prevention measures
- **Documentation Update**: Any changes to user procedures

## Lessons Learned Template

### Incident Summary
- **Date/Time**: When incident occurred
- **Duration**: How long system was impacted
- **Root Cause**: What caused the failure
- **Impact**: Number of users affected, failed uploads, etc.
- **Resolution**: How the issue was resolved

### What Went Well
- Effective monitoring and alerting
- Quick response time
- Successful rollback procedures
- Good team coordination

### What Could Be Improved
- Earlier detection of issues
- Faster rollback execution
- Better user communication
- More comprehensive testing

### Action Items
- [ ] Implement additional monitoring
- [ ] Update rollback procedures
- [ ] Enhance testing coverage
- [ ] Improve documentation
- [ ] Train team on new procedures

This comprehensive rollback and recovery guide ensures that any issues with the Google Drive Token Auto-Renewal System can be quickly and effectively resolved with minimal user impact.