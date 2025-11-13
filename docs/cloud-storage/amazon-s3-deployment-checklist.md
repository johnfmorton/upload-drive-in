# Amazon S3 Storage Provider - Deployment Checklist

## Overview

This checklist ensures a smooth deployment of the Amazon S3 storage provider feature to production. Follow each section in order and verify completion before proceeding to the next step.

**Deployment Date**: _________________  
**Deployed By**: _________________  
**Environment**: _________________

---

## Pre-Deployment Checklist

### 1. Code Review & Quality Assurance

- [ ] All code changes reviewed and approved
- [ ] No debug code or console.log statements remain
- [ ] All TODO comments addressed or documented
- [ ] Code follows Laravel conventions and project standards
- [ ] PHPDoc blocks complete and accurate
- [ ] No security vulnerabilities identified

### 2. Testing Verification

- [ ] All unit tests passing (`php artisan test --testsuite=Unit`)
- [ ] All feature tests passing (`php artisan test --testsuite=Feature`)
- [ ] Integration tests passing (if S3 credentials available)
- [ ] Manual testing completed on staging environment
- [ ] S3-compatible services tested (if applicable)
- [ ] Error handling scenarios verified
- [ ] Health check functionality verified

### 3. Documentation Review

- [ ] Setup guide reviewed and accurate (`docs/cloud-storage/amazon-s3-setup-guide.md`)
- [ ] Provider system documentation updated (`docs/cloud-storage-provider-system.md`)
- [ ] Deployment checklist reviewed (this document)
- [ ] IAM policy templates verified
- [ ] Troubleshooting guide complete

### 4. Environment Preparation

- [ ] AWS account created and configured
- [ ] S3 bucket created in desired region
- [ ] IAM user created with appropriate permissions
- [ ] Access key ID and secret access key generated
- [ ] Bucket CORS configuration set (if needed)
- [ ] Bucket lifecycle policies configured (optional)
- [ ] Test upload performed to verify bucket access

### 5. Configuration Files

- [ ] `config/cloud-storage.php` updated with S3 configuration
- [ ] S3 provider status set to 'fully_available'
- [ ] Error handler configured correctly
- [ ] Feature flags accurate
- [ ] Storage class options verified

---

## Database Migration Steps

### 1. Review Migration Files

- [ ] Review `database/migrations/2025_11_12_152854_add_user_id_to_cloud_storage_settings_table.php`
- [ ] Verify migration adds `user_id` column to `cloud_storage_settings` table
- [ ] Confirm migration is idempotent (safe to run multiple times)
- [ ] Check for any additional S3-related migrations

### 2. Backup Database

```bash
# Production backup
php artisan backup:run --only-db

# Or manual backup
mysqldump -u [user] -p [database] > backup_$(date +%Y%m%d_%H%M%S).sql
```

- [ ] Database backup completed
- [ ] Backup file verified and stored securely
- [ ] Backup restoration tested on staging

### 3. Run Migrations

```bash
# Check migration status
php artisan migrate:status

# Run migrations (dry-run first if available)
php artisan migrate --pretend

# Execute migrations
php artisan migrate --force
```

- [ ] Migration status checked
- [ ] Migrations executed successfully
- [ ] No errors in migration output
- [ ] Database schema verified

### 4. Verify Database Changes

```sql
-- Verify cloud_storage_settings table structure
DESCRIBE cloud_storage_settings;

-- Check for existing S3 settings
SELECT * FROM cloud_storage_settings WHERE provider = 'amazon-s3';

-- Verify indexes
SHOW INDEXES FROM cloud_storage_settings;
```

- [ ] Table structure correct
- [ ] Indexes created properly
- [ ] No data corruption detected

---

## Deployment Steps

### 1. Code Deployment

```bash
# Pull latest code
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Clear and rebuild caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild optimized caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

- [ ] Code deployed to production server
- [ ] Dependencies installed
- [ ] Caches cleared and rebuilt
- [ ] No errors during deployment

### 2. Asset Compilation

```bash
# Build production assets
npm ci
npm run build

# Verify assets compiled
ls -la public/build/
```

- [ ] Node modules installed
- [ ] Assets compiled successfully
- [ ] Build manifest generated
- [ ] Assets accessible via web

### 3. Service Provider Registration

- [ ] Verify `CloudStorageServiceProvider` registered in `config/app.php` or `bootstrap/providers.php`
- [ ] S3Provider registered in service provider boot method
- [ ] S3ErrorHandler registered with factory
- [ ] No service provider conflicts

### 4. Queue Worker Restart

```bash
# Restart queue workers to load new code
php artisan queue:restart

# Verify workers restarted
php artisan queue:work --once
```

- [ ] Queue workers restarted
- [ ] Workers processing jobs correctly
- [ ] No job failures after restart

---

## Configuration Verification

### 1. Environment Variables

Verify the following environment variables are set (if using env-based config):

```env
# AWS Configuration (optional - can be configured via UI)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_ENDPOINT=  # Optional for S3-compatible services
```

- [ ] Environment variables reviewed
- [ ] Sensitive values encrypted/secured
- [ ] Configuration matches documentation

### 2. Provider Configuration

```bash
# Check provider configuration
php artisan tinker
>>> config('cloud-storage.providers.amazon-s3')
>>> exit
```

- [ ] Provider configuration loaded correctly
- [ ] Availability status is 'fully_available'
- [ ] Error handler class correct
- [ ] Features array accurate

### 3. Service Registration

```bash
# Verify S3Provider can be instantiated
php artisan tinker
>>> $factory = app(\App\Services\CloudStorageFactory::class);
>>> $provider = $factory->create('amazon-s3');
>>> get_class($provider)
>>> exit
```

- [ ] CloudStorageFactory resolves correctly
- [ ] S3Provider instantiates without errors
- [ ] Provider implements correct interface

---

## Health Check Verification

### 1. Admin UI Access

- [ ] Navigate to `/admin/cloud-storage`
- [ ] Amazon S3 appears in provider dropdown
- [ ] S3 configuration form displays correctly
- [ ] All form fields present and functional

### 2. Configuration Test

- [ ] Enter test AWS credentials
- [ ] Save configuration
- [ ] Verify credentials stored encrypted in database
- [ ] Check for validation errors

### 3. Connection Health Check

```bash
# Test S3 connection via artisan
php artisan tinker
>>> $factory = app(\App\Services\CloudStorageFactory::class);
>>> $provider = $factory->create('amazon-s3');
>>> $user = \App\Models\User::first();
>>> $health = $provider->getConnectionHealth($user);
>>> $health->isHealthy()
>>> $health->lastErrorMessage
>>> exit
```

- [ ] Health check executes without errors
- [ ] Connection status accurate
- [ ] Error messages clear and helpful
- [ ] Dashboard widget displays status correctly

### 4. File Upload Test

- [ ] Upload test file via public interface
- [ ] Verify file queued for S3 upload
- [ ] Monitor queue job execution
- [ ] Confirm file uploaded to S3 bucket
- [ ] Verify S3 key stored in database
- [ ] Check file accessible in S3 console
- [ ] Verify local file cleaned up

### 5. File Operations Test

- [ ] Test file deletion via admin interface
- [ ] Verify file removed from S3
- [ ] Test presigned URL generation (if implemented)
- [ ] Test storage class changes (if implemented)
- [ ] Verify all operations logged correctly

---

## Error Handling Verification

### 1. Invalid Credentials

- [ ] Test with invalid access key ID
- [ ] Verify appropriate error message displayed
- [ ] Check error logged correctly
- [ ] Confirm health status reflects error

### 2. Bucket Access Issues

- [ ] Test with bucket that doesn't exist
- [ ] Test with bucket without permissions
- [ ] Verify error classification correct
- [ ] Check user-friendly error messages

### 3. Network Issues

- [ ] Simulate network timeout (if possible)
- [ ] Verify retry logic works
- [ ] Check failed job handling
- [ ] Confirm error recovery mechanisms

---

## Monitoring & Logging

### 1. Log Configuration

```bash
# Check recent logs for S3 operations
tail -f storage/logs/laravel.log | grep -i "s3\|amazon"
```

- [ ] S3 operations logged appropriately
- [ ] Log level appropriate (not too verbose)
- [ ] Sensitive data not logged
- [ ] Error context sufficient for debugging

### 2. Queue Monitoring

```bash
# Check queue status
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed
```

- [ ] Queue processing normally
- [ ] No unusual failure rate
- [ ] Failed jobs have clear error messages

### 3. Health Status Monitoring

- [ ] Dashboard displays S3 health status
- [ ] Health status updates correctly
- [ ] Error states display properly
- [ ] Status cache working correctly

---

## Security Verification

### 1. Credential Storage

```sql
-- Verify credentials are encrypted
SELECT 
    setting_key, 
    is_encrypted,
    LENGTH(setting_value) as value_length
FROM cloud_storage_settings 
WHERE provider = 'amazon-s3';
```

- [ ] Access key ID encrypted
- [ ] Secret access key encrypted
- [ ] Encryption working correctly
- [ ] No plaintext credentials in logs

### 2. Access Control

- [ ] Only admin users can configure S3
- [ ] Configuration routes protected by middleware
- [ ] CSRF protection enabled
- [ ] Input validation working

### 3. IAM Permissions

- [ ] IAM user has minimum required permissions
- [ ] No overly permissive policies
- [ ] Bucket policy restricts access appropriately
- [ ] MFA enabled on AWS account (recommended)

---

## Performance Verification

### 1. Upload Performance

- [ ] Test small file upload (< 5MB)
- [ ] Test medium file upload (5-50MB)
- [ ] Test large file upload (> 50MB)
- [ ] Verify multipart upload for large files
- [ ] Check upload duration acceptable

### 2. Health Check Performance

```bash
# Time health check execution
php artisan tinker
>>> $start = microtime(true);
>>> $factory = app(\App\Services\CloudStorageFactory::class);
>>> $provider = $factory->create('amazon-s3');
>>> $user = \App\Models\User::first();
>>> $health = $provider->getConnectionHealth($user);
>>> $duration = microtime(true) - $start;
>>> echo "Health check took: " . round($duration, 2) . " seconds";
>>> exit
```

- [ ] Health check completes in < 5 seconds
- [ ] No timeout issues
- [ ] Caching working correctly

### 3. Queue Performance

- [ ] Queue jobs processing at expected rate
- [ ] No memory issues with large files
- [ ] Worker processes stable
- [ ] No job timeouts

---

## User Acceptance Testing

### 1. Admin User Testing

- [ ] Admin can access S3 configuration
- [ ] Admin can save S3 credentials
- [ ] Admin can test connection
- [ ] Admin can view health status
- [ ] Admin can switch between providers
- [ ] Admin can disconnect S3

### 2. Employee User Testing

- [ ] Employees can upload files (if S3 is default)
- [ ] Upload process transparent to employees
- [ ] No errors during upload flow
- [ ] Files accessible after upload

### 3. Client User Testing

- [ ] Clients can upload files via public form
- [ ] Upload process works with S3 backend
- [ ] Email validation still works
- [ ] Upload confirmation received

---

## Rollback Procedures

### 1. Immediate Rollback (Code Level)

If critical issues are discovered immediately after deployment:

```bash
# Revert to previous code version
git revert [commit-hash]
git push origin main

# Or rollback to previous release
git checkout [previous-tag]

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Restart queue workers
php artisan queue:restart
```

- [ ] Previous code version identified
- [ ] Rollback procedure tested on staging
- [ ] Team notified of rollback

### 2. Configuration Rollback

If issues are configuration-related:

```bash
# Switch default provider back to Google Drive
php artisan tinker
>>> $setting = \App\Models\CloudStorageSetting::systemLevel()
    ->forProvider('system')
    ->where('setting_key', 'default_provider')
    ->first();
>>> $setting->update(['setting_value' => 'google-drive']);
>>> exit

# Or via config file
# Edit config/cloud-storage.php
# Change 'default' => 'google-drive'
php artisan config:cache
```

- [ ] Default provider switched back
- [ ] Existing uploads continue working
- [ ] No data loss

### 3. Database Rollback

If database migration needs to be rolled back:

```bash
# Rollback last migration batch
php artisan migrate:rollback --step=1

# Or rollback specific migration
php artisan migrate:rollback --path=/database/migrations/2025_11_12_152854_add_user_id_to_cloud_storage_settings_table.php

# Restore from backup if needed
mysql -u [user] -p [database] < backup_[timestamp].sql
```

- [ ] Migration rollback tested on staging
- [ ] Data backup available
- [ ] Rollback procedure documented

### 4. Disable S3 Provider

If S3 needs to be disabled without full rollback:

```php
// In config/cloud-storage.php
'providers' => [
    'amazon-s3' => [
        'availability' => 'coming_soon', // Change from 'fully_available'
        // ... rest of config
    ],
],
```

```bash
php artisan config:cache
```

- [ ] Provider disabled in configuration
- [ ] UI no longer shows S3 option
- [ ] Existing S3 files remain accessible
- [ ] No impact on Google Drive uploads

### 5. Data Recovery

If S3 files need to be recovered:

- [ ] S3 bucket versioning enabled (if configured)
- [ ] Backup of `file_uploads` table available
- [ ] S3 keys documented for recovery
- [ ] Recovery procedure tested

---

## Post-Deployment Monitoring

### 1. First 24 Hours

- [ ] Monitor error logs every 2 hours
- [ ] Check queue job success rate
- [ ] Verify no spike in failed jobs
- [ ] Monitor S3 API usage and costs
- [ ] Check user feedback/support tickets

### 2. First Week

- [ ] Daily log review
- [ ] Weekly performance metrics
- [ ] User adoption tracking
- [ ] Cost analysis
- [ ] Error pattern analysis

### 3. Ongoing Monitoring

- [ ] Set up alerts for S3 errors
- [ ] Monitor AWS billing
- [ ] Track upload success rate
- [ ] Review health check failures
- [ ] Analyze performance trends

---

## Communication Plan

### 1. Pre-Deployment

- [ ] Notify team of deployment schedule
- [ ] Inform users of new feature
- [ ] Prepare support team with documentation
- [ ] Schedule maintenance window (if needed)

### 2. During Deployment

- [ ] Update status page (if applicable)
- [ ] Monitor team communication channel
- [ ] Document any issues encountered
- [ ] Keep stakeholders informed

### 3. Post-Deployment

- [ ] Announce successful deployment
- [ ] Share documentation links
- [ ] Provide training materials
- [ ] Collect user feedback

---

## Success Criteria

The deployment is considered successful when:

- [ ] All tests passing
- [ ] S3 provider available in admin UI
- [ ] File uploads working to S3
- [ ] Health checks reporting correctly
- [ ] No critical errors in logs
- [ ] Performance within acceptable limits
- [ ] Security verification complete
- [ ] Documentation complete and accurate
- [ ] Team trained on new feature
- [ ] Rollback procedure tested and documented

---

## Sign-Off

### Technical Lead

**Name**: _________________  
**Signature**: _________________  
**Date**: _________________

### Product Owner

**Name**: _________________  
**Signature**: _________________  
**Date**: _________________

### DevOps/Infrastructure

**Name**: _________________  
**Signature**: _________________  
**Date**: _________________

---

## Notes & Issues

Document any issues encountered during deployment:

```
Issue #1:
Description:
Resolution:
Time to resolve:

Issue #2:
Description:
Resolution:
Time to resolve:
```

---

## Appendix

### A. Useful Commands

```bash
# Check S3 provider status
php artisan tinker
>>> config('cloud-storage.providers.amazon-s3.availability')

# Test S3 connection
php artisan tinker
>>> $factory = app(\App\Services\CloudStorageFactory::class);
>>> $provider = $factory->create('amazon-s3');
>>> $user = \App\Models\User::first();
>>> $health = $provider->getConnectionHealth($user);
>>> dd($health);

# View S3 settings
php artisan tinker
>>> \App\Models\CloudStorageSetting::where('provider', 'amazon-s3')->get();

# Clear all caches
php artisan optimize:clear

# Rebuild all caches
php artisan optimize
```

### B. Emergency Contacts

- **AWS Support**: [Contact information]
- **Technical Lead**: [Contact information]
- **DevOps Team**: [Contact information]
- **On-Call Engineer**: [Contact information]

### C. Related Documentation

- [Amazon S3 Setup Guide](./amazon-s3-setup-guide.md)
- [Cloud Storage Provider System](../cloud-storage-provider-system.md)
- [Implementing New Cloud Storage Providers](../implementing-new-cloud-storage-providers.md)
- [Cloud Storage Configuration Guide](../cloud-storage-configuration-guide.md)

---

**Document Version**: 1.0  
**Last Updated**: 2025-11-12  
**Next Review Date**: [After first production deployment]
