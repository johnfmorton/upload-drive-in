# Cloud Storage Provider Troubleshooting Guide

## Overview

This guide helps you diagnose and resolve common issues with the Cloud Storage Provider System. It covers authentication problems, configuration issues, performance problems, and provider-specific errors.

## Diagnostic Commands

Before troubleshooting specific issues, use these commands to gather information:

### Health Check Commands
```bash
# Check all providers
php artisan cloud-storage:health-check

# Check specific provider
php artisan cloud-storage:health-check google-drive

# Comprehensive health check with detailed output
php artisan cloud-storage:comprehensive-health-check

# Monitor providers continuously
php artisan cloud-storage:monitor-providers
```

### Configuration Commands
```bash
# Validate all provider configurations
php artisan cloud-storage:validate-config

# Validate specific provider
php artisan cloud-storage:validate-config amazon-s3

# Test provider connections
php artisan cloud-storage:test-providers

# Migrate configuration from environment
php artisan cloud-storage:migrate-config
```

### Maintenance Commands
```bash
# Fix health status inconsistencies
php artisan cloud-storage:fix-health-status

# Clean up old metrics
php artisan cloud-storage:cleanup-metrics

# Refresh expired tokens
php artisan cloud-storage:refresh-tokens
```

## Common Issues and Solutions

### Authentication Issues

#### Issue: "Authentication Required" Status

**Symptoms:**
- Provider shows "authentication_required" status
- Users can't upload files
- Error messages about invalid credentials

**Diagnosis:**
```bash
# Check provider health
php artisan cloud-storage:health-check google-drive

# Check token status
php artisan tinker
>>> $user = User::find(1);
>>> $token = GoogleDriveToken::where('user_id', $user->id)->first();
>>> $token->expires_at; // Check expiration
>>> $token->access_token; // Verify token exists
```

**Solutions:**

1. **Token Expired:**
   ```bash
   # Force token refresh
   php artisan cloud-storage:refresh-tokens
   ```

2. **Missing Token:**
   - User needs to re-authenticate through the admin panel
   - Check OAuth callback URL configuration

3. **Invalid Credentials:**
   ```bash
   # Validate configuration
   php artisan cloud-storage:validate-config google-drive
   ```

#### Issue: OAuth Callback Failures

**Symptoms:**
- Authentication redirects fail
- "Invalid redirect URI" errors
- Users stuck in authentication loop

**Diagnosis:**
```bash
# Check configuration
php artisan config:show cloud-storage.providers.google-drive.config.redirect_uri

# Verify environment variables
echo $GOOGLE_DRIVE_CLIENT_ID
echo $GOOGLE_DRIVE_CLIENT_SECRET
```

**Solutions:**

1. **Incorrect Redirect URI:**
   ```php
   // config/cloud-storage.php
   'redirect_uri' => config('app.url') . '/admin/cloud-storage/google-drive/callback',
   ```

2. **Environment Variables:**
   ```env
   GOOGLE_DRIVE_CLIENT_ID=your_client_id
   GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret
   ```

3. **Clear Configuration Cache:**
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

### Configuration Issues

#### Issue: Provider Not Found

**Symptoms:**
- "Provider 'xyz' not found" errors
- Provider not listed in available providers
- Service container binding errors

**Diagnosis:**
```bash
# Check registered providers
php artisan tinker
>>> app(CloudStorageFactory::class)->getRegisteredProviders();

# Check configuration
php artisan config:show cloud-storage.providers
```

**Solutions:**

1. **Provider Not Registered:**
   ```php
   // app/Providers/CloudStorageServiceProvider.php
   $factory->register('my-provider', MyProvider::class);
   ```

2. **Configuration Missing:**
   ```php
   // config/cloud-storage.php
   'providers' => [
       'my-provider' => [
           'driver' => 'my-provider',
           'class' => MyProvider::class,
           // ... other config
       ],
   ],
   ```

3. **Clear Caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan optimize:clear
   ```

#### Issue: Invalid Configuration

**Symptoms:**
- Configuration validation errors
- Provider initialization failures
- Missing required parameters

**Diagnosis:**
```bash
# Validate configuration
php artisan cloud-storage:validate-config

# Check specific provider
php artisan cloud-storage:validate-config amazon-s3
```

**Solutions:**

1. **Missing Environment Variables:**
   ```env
   # Amazon S3
   AWS_ACCESS_KEY_ID=your_access_key
   AWS_SECRET_ACCESS_KEY=your_secret_key
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=your_bucket_name
   ```

2. **Invalid Configuration Values:**
   ```bash
   # Test configuration
   php artisan tinker
   >>> $config = config('cloud-storage.providers.amazon-s3.config');
   >>> $provider = new S3Provider();
   >>> $errors = $provider->validateConfiguration($config);
   >>> dd($errors);
   ```

### Connection Issues

#### Issue: Network Timeouts

**Symptoms:**
- Upload operations timeout
- "Connection timed out" errors
- Intermittent failures

**Diagnosis:**
```bash
# Check network connectivity
curl -I https://www.googleapis.com/drive/v3/files
curl -I https://s3.amazonaws.com

# Check application logs
tail -f storage/logs/laravel.log | grep "cloud-storage"
```

**Solutions:**

1. **Increase Timeout Values:**
   ```php
   // In provider initialization
   $this->client->setTimeout(300); // 5 minutes
   ```

2. **Configure HTTP Client:**
   ```php
   // For Guzzle-based clients
   $this->client = new Client([
       'timeout' => 300,
       'connect_timeout' => 30,
       'read_timeout' => 300,
   ]);
   ```

3. **Check Firewall/Proxy:**
   - Ensure outbound HTTPS connections are allowed
   - Configure proxy settings if needed

#### Issue: Rate Limiting

**Symptoms:**
- "Rate limit exceeded" errors
- 429 HTTP status codes
- Temporary upload failures

**Diagnosis:**
```bash
# Check error logs
grep "rate_limit_exceeded" storage/logs/laravel.log

# Monitor API usage
php artisan cloud-storage:monitor-providers
```

**Solutions:**

1. **Implement Exponential Backoff:**
   ```php
   public function getRetryDelay(CloudStorageErrorType $errorType, int $attemptNumber): int
   {
       if ($errorType === CloudStorageErrorType::RATE_LIMIT_EXCEEDED) {
           return min(300, 30 * pow(2, $attemptNumber));
       }
       return parent::getRetryDelay($errorType, $attemptNumber);
   }
   ```

2. **Reduce Concurrent Operations:**
   ```php
   // In queue configuration
   'connections' => [
       'database' => [
           'driver' => 'database',
           'table' => 'jobs',
           'queue' => 'default',
           'retry_after' => 90,
           'processes' => 1, // Reduce from default
       ],
   ],
   ```

### File Operation Issues

#### Issue: Upload Failures

**Symptoms:**
- Files not appearing in cloud storage
- Upload jobs failing repeatedly
- "Upload failed" error messages

**Diagnosis:**
```bash
# Check failed jobs
php artisan queue:failed

# Check job logs
php artisan queue:work --verbose

# Test upload manually
php artisan tinker
>>> $user = User::find(1);
>>> $manager = app(CloudStorageManager::class);
>>> $provider = $manager->getUserProvider($user);
>>> $result = $provider->uploadFile($user, '/path/to/test/file.txt', 'test.txt');
```

**Solutions:**

1. **File Permissions:**
   ```bash
   # Check file permissions
   ls -la storage/app/uploads/
   
   # Fix permissions
   chmod -R 755 storage/app/uploads/
   chown -R www-data:www-data storage/app/uploads/
   ```

2. **File Size Limits:**
   ```php
   // Check provider limits
   $maxSize = $provider->getMaxFileSize();
   $fileSize = filesize($filePath);
   
   if ($fileSize > $maxSize) {
       throw new CloudStorageException('File too large');
   }
   ```

3. **Storage Space:**
   ```bash
   # Check disk space
   df -h
   
   # Check cloud storage quota
   php artisan cloud-storage:health-check
   ```

#### Issue: File Not Found Errors

**Symptoms:**
- "File not found" when trying to delete
- Inconsistent file listings
- Metadata sync issues

**Diagnosis:**
```bash
# Check database vs cloud storage
php artisan tinker
>>> $upload = FileUpload::find(1);
>>> $provider = app(CloudStorageManager::class)->getProvider($upload->storage_provider);
>>> // Try to access file using stored ID
```

**Solutions:**

1. **Sync Database with Cloud Storage:**
   ```php
   // Create sync command
   php artisan make:command SyncCloudStorageFiles
   ```

2. **Handle Missing Files Gracefully:**
   ```php
   public function deleteFile(User $user, string $fileId): bool
   {
       try {
           $this->client->deleteFile($fileId);
           return true;
       } catch (FileNotFoundException $e) {
           // File already deleted, consider it successful
           return true;
       }
   }
   ```

### Performance Issues

#### Issue: Slow Upload Performance

**Symptoms:**
- Uploads taking longer than expected
- Timeout errors on large files
- Poor user experience

**Diagnosis:**
```bash
# Monitor upload performance
php artisan cloud-storage:monitor-providers

# Check metrics
php artisan tinker
>>> $metrics = app(CloudStoragePerformanceMetricsService::class);
>>> $stats = $metrics->getProviderStatistics('google-drive', '1h');
>>> dd($stats);
```

**Solutions:**

1. **Enable Chunked Uploads:**
   ```php
   // For large files
   if (filesize($localPath) > 50 * 1024 * 1024) { // 50MB
       return $this->uploadFileChunked($localPath, $targetPath);
   }
   ```

2. **Optimize File Processing:**
   ```php
   // Use streaming for large files
   $stream = fopen($localPath, 'r');
   $result = $this->client->uploadStream($stream, $targetPath);
   fclose($stream);
   ```

3. **Queue Optimization:**
   ```bash
   # Use multiple queue workers
   php artisan queue:work --queue=uploads --processes=3
   ```

#### Issue: Memory Issues

**Symptoms:**
- "Allowed memory size exhausted" errors
- PHP memory limit errors during uploads
- Server crashes during large operations

**Solutions:**

1. **Increase Memory Limit:**
   ```php
   // In upload methods
   ini_set('memory_limit', '512M');
   ```

2. **Use Streaming:**
   ```php
   // Don't load entire file into memory
   $stream = fopen($localPath, 'r');
   $this->client->uploadStream($stream, $targetPath);
   fclose($stream);
   ```

3. **Process Files in Batches:**
   ```php
   // For bulk operations
   $files = collect($allFiles)->chunk(10);
   foreach ($files as $batch) {
       $this->processBatch($batch);
   }
   ```

## Provider-Specific Issues

### Google Drive Issues

#### Issue: Quota Exceeded

**Symptoms:**
- "Quota exceeded" errors
- 403 status codes from Google API
- Upload failures

**Solutions:**
```bash
# Check quota usage
php artisan tinker
>>> $provider = app(CloudStorageManager::class)->getProvider('google-drive');
>>> $health = $provider->getConnectionHealth($user);
>>> dd($health->error_message);
```

#### Issue: Folder Creation Failures

**Symptoms:**
- Files uploaded to root instead of intended folder
- "Folder not found" errors

**Solutions:**
```php
// Ensure folder exists before upload
$folderId = $this->ensureFolderExists($user, $folderName);
$result = $provider->uploadFile($user, $localPath, $targetPath, [
    'parent_folder_id' => $folderId,
]);
```

### Amazon S3 Issues

#### Issue: Bucket Access Denied

**Symptoms:**
- "Access Denied" errors
- 403 status codes from S3
- Authentication appears successful but operations fail

**Solutions:**
```bash
# Check bucket policy and IAM permissions
aws s3api get-bucket-policy --bucket your-bucket-name
aws iam get-user-policy --user-name your-user --policy-name your-policy
```

#### Issue: Region Mismatch

**Symptoms:**
- "The bucket is in this region" errors
- Inconsistent behavior across operations

**Solutions:**
```env
# Ensure correct region
AWS_DEFAULT_REGION=us-west-2
```

## Monitoring and Alerting

### Set Up Monitoring

1. **Enable Performance Metrics:**
   ```php
   // config/cloud-storage.php
   'monitoring' => [
       'enabled' => true,
       'metrics_retention' => '30 days',
       'alert_thresholds' => [
           'error_rate' => 0.05, // 5%
           'response_time' => 30, // seconds
       ],
   ],
   ```

2. **Configure Alerts:**
   ```php
   // In your monitoring service
   if ($errorRate > 0.05) {
       Notification::route('slack', '#alerts')
           ->notify(new CloudStorageAlert($provider, $errorRate));
   }
   ```

### Log Analysis

1. **Structured Logging:**
   ```bash
   # Filter cloud storage logs
   tail -f storage/logs/laravel.log | grep "cloud-storage"
   
   # Search for specific errors
   grep "authentication_failed" storage/logs/laravel.log
   ```

2. **Performance Analysis:**
   ```bash
   # Find slow operations
   grep "duration" storage/logs/laravel.log | awk '$NF > 30'
   ```

## Emergency Procedures

### Provider Outage

1. **Switch to Backup Provider:**
   ```php
   // Temporarily change default provider
   config(['cloud-storage.default' => 'amazon-s3']);
   ```

2. **Queue Pause:**
   ```bash
   # Pause upload queue
   php artisan queue:pause
   
   # Resume when provider is back
   php artisan queue:resume
   ```

### Data Recovery

1. **Backup Verification:**
   ```bash
   # Verify database backups
   php artisan backup:list
   
   # Test restore procedure
   php artisan backup:restore --dry-run
   ```

2. **File Recovery:**
   ```php
   // Recover from provider's trash/recycle bin
   $provider->restoreFile($user, $fileId);
   ```

## Prevention Strategies

### Health Monitoring

1. **Automated Health Checks:**
   ```bash
   # Add to cron
   * * * * * php artisan cloud-storage:health-check --quiet
   ```

2. **Proactive Monitoring:**
   ```php
   // Monitor key metrics
   $metrics = app(CloudStoragePerformanceMetricsService::class);
   $stats = $metrics->getProviderStatistics('google-drive', '1h');
   
   if ($stats['error_rate'] > 0.05) {
       // Alert administrators
   }
   ```

### Configuration Management

1. **Environment Validation:**
   ```bash
   # Add to deployment script
   php artisan cloud-storage:validate-config
   ```

2. **Configuration Backup:**
   ```bash
   # Backup configuration before changes
   cp config/cloud-storage.php config/cloud-storage.php.backup
   ```

### Testing

1. **Regular Integration Tests:**
   ```bash
   # Run provider tests weekly
   php artisan test --filter=CloudStorageProvider
   ```

2. **Load Testing:**
   ```bash
   # Test with realistic load
   php artisan cloud-storage:load-test --files=100 --concurrent=5
   ```

## Getting Help

### Debug Information

When reporting issues, include:

1. **System Information:**
   ```bash
   php artisan about
   php artisan cloud-storage:health-check --verbose
   ```

2. **Configuration:**
   ```bash
   php artisan config:show cloud-storage
   ```

3. **Recent Logs:**
   ```bash
   tail -n 100 storage/logs/laravel.log | grep "cloud-storage"
   ```

4. **Error Details:**
   - Full error messages
   - Stack traces
   - Steps to reproduce

### Support Channels

- Check the documentation first
- Search existing issues in the repository
- Run diagnostic commands
- Provide complete debug information when asking for help

This troubleshooting guide should help you resolve most common issues with the Cloud Storage Provider System. Remember to always test changes in a development environment before applying them to production.