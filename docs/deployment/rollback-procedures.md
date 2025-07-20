# File Management Dashboard Rollback Procedures

## Overview

This document provides detailed rollback procedures for the file management dashboard deployment. Use these procedures if critical issues are discovered after deployment that cannot be quickly resolved.

## When to Rollback

### Critical Issues Requiring Immediate Rollback
- **Data Loss**: Files are being deleted or corrupted
- **Security Breach**: Unauthorized access to files
- **System Instability**: Application crashes or becomes unresponsive
- **Performance Degradation**: Severe slowdown affecting all users
- **Database Corruption**: Data integrity issues

### Issues That May Not Require Rollback
- **Minor UI Issues**: Layout problems that don't affect functionality
- **Non-Critical Features**: Preview or thumbnail generation issues
- **Performance Issues**: Slow responses that don't block functionality
- **Cosmetic Problems**: Styling or responsive design issues

## Pre-Rollback Assessment

### 1. Issue Severity Assessment
```bash
# Check system status
php artisan about

# Check database integrity
php artisan db:show --counts

# Check queue status
php artisan queue:monitor

# Check error logs
tail -100 storage/logs/laravel.log | grep -i error
```

### 2. Impact Analysis
- **Affected Users**: How many users are impacted?
- **Data Risk**: Is there risk of data loss or corruption?
- **Business Impact**: What business processes are affected?
- **Time Sensitivity**: How quickly must this be resolved?

### 3. Alternative Solutions
Before rolling back, consider:
- **Quick Fixes**: Can the issue be resolved with a hotfix?
- **Feature Flags**: Can problematic features be disabled?
- **Configuration Changes**: Can settings be adjusted to resolve the issue?
- **Partial Rollback**: Can only specific components be rolled back?

## Rollback Decision Matrix

| Issue Severity | Data Risk | User Impact | Action |
|---------------|-----------|-------------|---------|
| Critical | High | All Users | Immediate Full Rollback |
| High | Medium | Most Users | Full Rollback within 1 hour |
| Medium | Low | Some Users | Partial Rollback or Hotfix |
| Low | None | Few Users | Monitor and Plan Fix |

## Rollback Procedures

### Phase 1: Immediate Response (0-15 minutes)

#### 1.1 Emergency Communication
```bash
# Send immediate notification to stakeholders
echo "URGENT: File Management System Rollback in Progress" | mail -s "System Alert" stakeholders@company.com

# Update status page (if available)
curl -X POST https://status.company.com/api/incidents \
  -H "Authorization: Bearer $STATUS_API_KEY" \
  -d '{"status": "investigating", "message": "File management system issues detected"}'
```

#### 1.2 Stop Incoming Traffic (if necessary)
```bash
# Enable maintenance mode
php artisan down --message="System maintenance in progress" --retry=60

# Or redirect traffic to maintenance page via web server
# nginx: return 503;
# apache: Redirect 503 /maintenance.html
```

#### 1.3 Stop Background Processes
```bash
# Stop queue workers
php artisan queue:restart
sudo supervisorctl stop laravel-worker:*

# Stop any running file operations
pkill -f "php artisan file-manager"
```

### Phase 2: Database Rollback (15-30 minutes)

#### 2.1 Database Backup Verification
```bash
# Verify backup exists and is recent
ls -la backups/ | grep $(date +%Y%m%d)

# Test backup integrity
mysqldump --single-transaction --routines --triggers your_database > test_restore.sql
mysql -e "CREATE DATABASE test_restore_db;"
mysql test_restore_db < your_backup.sql
mysql -e "DROP DATABASE test_restore_db;"
```

#### 2.2 Database Rollback
```bash
# Create current state backup before rollback
mysqldump --single-transaction --routines --triggers your_database > rollback_backup_$(date +%Y%m%d_%H%M%S).sql

# Restore from pre-deployment backup
mysql your_database < pre_deployment_backup.sql

# Verify database restoration
php artisan migrate:status
```

#### 2.3 Migration Rollback (if needed)
```bash
# Check which migrations need to be rolled back
php artisan migrate:status

# Rollback specific migrations (if database restore wasn't used)
php artisan migrate:rollback --step=5

# Verify rollback
php artisan migrate:status
```

### Phase 3: Code Rollback (30-45 minutes)

#### 3.1 Git Rollback
```bash
# Identify the commit to rollback to
git log --oneline -10

# Create a rollback branch
git checkout -b rollback-$(date +%Y%m%d-%H%M%S)

# Rollback to previous stable version
git reset --hard PREVIOUS_STABLE_COMMIT_HASH

# Or revert specific commits
git revert COMMIT_HASH_1 COMMIT_HASH_2
```

#### 3.2 Dependency Rollback
```bash
# Restore previous composer.lock
git checkout HEAD~1 composer.lock
composer install --no-dev --optimize-autoloader

# Restore previous package-lock.json
git checkout HEAD~1 package-lock.json
npm ci --production
npm run build
```

#### 3.3 Configuration Rollback
```bash
# Restore previous configuration files
git checkout HEAD~1 config/

# Restore previous environment configuration (if needed)
cp .env.backup .env

# Clear and rebuild caches
php artisan optimize:clear
php artisan optimize
```

### Phase 4: File System Rollback (45-60 minutes)

#### 4.1 Storage Rollback
```bash
# Restore storage directory from backup
rm -rf storage
cp -r storage-backup-$(date +%Y%m%d) storage

# Restore proper permissions
chown -R www-data:www-data storage
chmod -R 755 storage
```

#### 4.2 Public Assets Rollback
```bash
# Restore public assets
rm -rf public/build
git checkout HEAD~1 public/build/

# Or rebuild from previous version
npm run build
```

#### 4.3 Uploaded Files Verification
```bash
# Verify uploaded files are intact
php artisan file-manager:verify-integrity

# Check Google Drive synchronization
php artisan google-drive:verify-sync
```

### Phase 5: Service Restoration (60-75 minutes)

#### 5.1 Cache Restoration
```bash
# Clear all caches
php artisan optimize:clear
redis-cli FLUSHALL

# Rebuild caches
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 5.2 Queue System Restoration
```bash
# Clear failed jobs from rollback period
php artisan queue:flush

# Restart queue workers
sudo supervisorctl start laravel-worker:*
php artisan queue:restart
```

#### 5.3 Web Server Restart
```bash
# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Restart web server
sudo systemctl restart nginx
# or
sudo systemctl restart apache2
```

### Phase 6: Verification and Testing (75-90 minutes)

#### 6.1 System Health Check
```bash
# Basic application test
php artisan about

# Database connectivity
php artisan tinker
>>> DB::connection()->getPdo();

# Cache connectivity
>>> Cache::get('test');

# Queue functionality
>>> dispatch(new \App\Jobs\TestJob());
```

#### 6.2 Functional Testing
```bash
# Test file manager access
curl -I http://your-domain.com/admin/file-manager

# Test file operations
php artisan file-manager:test-operations

# Test user authentication
curl -X POST http://your-domain.com/login \
  -d "email=test@example.com&password=password"
```

#### 6.3 User Acceptance Testing
1. **Admin Login**: Verify admin can access dashboard
2. **File List**: Confirm file list displays correctly
3. **File Operations**: Test download, preview (basic functionality)
4. **User Permissions**: Verify role-based access works
5. **Mobile Access**: Test mobile interface functionality

### Phase 7: Communication and Monitoring (90+ minutes)

#### 7.1 Stakeholder Communication
```bash
# Notify completion
echo "File Management System Rollback Complete - System Restored" | \
  mail -s "System Restored" stakeholders@company.com

# Update status page
curl -X PATCH https://status.company.com/api/incidents/$INCIDENT_ID \
  -H "Authorization: Bearer $STATUS_API_KEY" \
  -d '{"status": "resolved", "message": "System restored to previous stable version"}'
```

#### 7.2 Enable Normal Operations
```bash
# Disable maintenance mode
php artisan up

# Resume normal traffic routing
# (reverse any traffic redirection done in Phase 1)
```

#### 7.3 Enhanced Monitoring
```bash
# Monitor error logs closely
tail -f storage/logs/laravel.log

# Monitor system resources
watch -n 5 'free -h && df -h'

# Monitor queue processing
watch -n 10 'php artisan queue:monitor'
```

## Post-Rollback Actions

### Immediate Actions (Day 1)

#### 1. Incident Documentation
Create detailed incident report including:
- **Timeline**: Exact times of issue detection and rollback
- **Root Cause**: What caused the need for rollback
- **Impact Assessment**: Users affected, data impact, downtime
- **Lessons Learned**: What could be improved

#### 2. Data Integrity Verification
```bash
# Run comprehensive data integrity checks
php artisan file-manager:integrity-check --comprehensive

# Verify Google Drive synchronization
php artisan google-drive:sync-verify --full

# Check for any data inconsistencies
php artisan db:check-integrity
```

#### 3. User Communication
- Send notification to all users about the rollback
- Explain any temporary limitations
- Provide timeline for when new features will be re-deployed
- Offer support contact information

### Short-term Actions (Week 1)

#### 4. Issue Analysis and Resolution
- Conduct thorough root cause analysis
- Develop fixes for identified issues
- Create comprehensive test plan for re-deployment
- Update deployment procedures based on lessons learned

#### 5. Enhanced Testing
- Set up staging environment that mirrors production exactly
- Implement automated testing for critical paths
- Perform load testing with realistic data volumes
- Test rollback procedures in staging environment

#### 6. Process Improvements
- Update deployment checklist
- Enhance monitoring and alerting
- Improve backup and recovery procedures
- Document additional rollback scenarios

### Long-term Actions (Month 1)

#### 7. System Hardening
- Implement feature flags for safer deployments
- Set up blue-green deployment capability
- Enhance monitoring and observability
- Create automated rollback triggers

#### 8. Team Training
- Train team on rollback procedures
- Conduct rollback drills
- Update incident response procedures
- Create decision trees for rollback scenarios

## Rollback Testing

### Regular Rollback Drills

#### Monthly Drill Schedule
```bash
# Schedule monthly rollback drills
# Test different scenarios:
# - Database rollback only
# - Code rollback only
# - Full system rollback
# - Partial feature rollback

# Document drill results
echo "Rollback Drill $(date): Success/Failure - Notes" >> rollback_drill_log.txt
```

#### Drill Scenarios
1. **Database Corruption**: Practice database-only rollback
2. **Code Issues**: Practice code-only rollback
3. **Performance Problems**: Practice selective feature rollback
4. **Security Issues**: Practice emergency full rollback

### Rollback Automation

#### Automated Rollback Scripts
```bash
#!/bin/bash
# rollback.sh - Automated rollback script

ROLLBACK_TYPE=$1
COMMIT_HASH=$2

case $ROLLBACK_TYPE in
  "full")
    ./scripts/full-rollback.sh $COMMIT_HASH
    ;;
  "database")
    ./scripts/database-rollback.sh
    ;;
  "code")
    ./scripts/code-rollback.sh $COMMIT_HASH
    ;;
  *)
    echo "Usage: $0 {full|database|code} [commit_hash]"
    exit 1
    ;;
esac
```

## Emergency Contacts

### Escalation Chain
1. **On-Call Developer**: +1-XXX-XXX-XXXX
2. **Lead Developer**: +1-XXX-XXX-XXXX
3. **System Administrator**: +1-XXX-XXX-XXXX
4. **CTO/Technical Director**: +1-XXX-XXX-XXXX

### External Contacts
- **Hosting Provider Support**: +1-XXX-XXX-XXXX
- **Database Administrator**: +1-XXX-XXX-XXXX
- **Security Team**: security@company.com

### Communication Channels
- **Slack**: #emergency-response
- **Email**: emergency@company.com
- **Status Page**: https://status.company.com

## Rollback Checklist

### Pre-Rollback Checklist
- [ ] Issue severity assessed
- [ ] Stakeholders notified
- [ ] Backup integrity verified
- [ ] Rollback plan reviewed
- [ ] Emergency contacts alerted

### During Rollback Checklist
- [ ] Maintenance mode enabled
- [ ] Background processes stopped
- [ ] Database backed up
- [ ] Database rolled back
- [ ] Code rolled back
- [ ] Dependencies restored
- [ ] Caches cleared
- [ ] Services restarted

### Post-Rollback Checklist
- [ ] System health verified
- [ ] Functional testing completed
- [ ] Users notified
- [ ] Monitoring enhanced
- [ ] Incident documented
- [ ] Lessons learned captured

Remember: The goal of rollback procedures is to restore system stability quickly and safely. When in doubt, prioritize data integrity and user safety over feature availability.