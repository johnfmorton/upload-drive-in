# File Management Dashboard Deployment Guide

## Overview

This document provides comprehensive deployment instructions for the enhanced file management dashboard feature. Follow these steps carefully to ensure a smooth deployment with minimal downtime.

## Pre-Deployment Checklist

### System Requirements
- **PHP**: 8.3 or higher
- **Laravel**: 12.x
- **Database**: MySQL 8.0+ or MariaDB 10.11+
- **Redis**: 6.0+ (for caching and queues)
- **Storage**: Sufficient disk space for file operations
- **Memory**: Minimum 512MB PHP memory limit

### Dependencies Verification
```bash
# Verify required packages are installed
composer show | grep -E "(pion/laravel-chunk-upload|google/apiclient|predis/predis)"

# Check for required services
php artisan about
```

### Environment Configuration
Ensure these environment variables are properly configured:

```env
# File Management Settings
FILE_MANAGER_CACHE_TTL=3600
FILE_MANAGER_THUMBNAIL_CACHE_TTL=86400
FILE_MANAGER_MAX_BULK_DOWNLOAD_SIZE=524288000
FILE_MANAGER_RATE_LIMIT_DOWNLOADS=60
FILE_MANAGER_RATE_LIMIT_PREVIEWS=120

# Google Drive Integration
GOOGLE_DRIVE_CLIENT_ID=your_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret
GOOGLE_DRIVE_REDIRECT_URI=your_redirect_uri
GOOGLE_DRIVE_ROOT_FOLDER_ID=your_root_folder_id

# Queue Configuration
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache Configuration
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

## Deployment Steps

### Step 1: Code Deployment

#### 1.1 Backup Current System
```bash
# Create database backup
php artisan backup:run --only-db

# Backup current codebase
tar -czf backup-$(date +%Y%m%d-%H%M%S).tar.gz .

# Backup storage directory
cp -r storage storage-backup-$(date +%Y%m%d-%H%M%S)
```

#### 1.2 Deploy New Code
```bash
# Pull latest code
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Update npm dependencies
npm ci --production
npm run build
```

### Step 2: Database Migration

#### 2.1 Run Migrations
```bash
# Check migration status
php artisan migrate:status

# Run new migrations (if any)
php artisan migrate --force

# Verify database structure
php artisan migrate:status
```

#### 2.2 Update Indexes (if needed)
```bash
# The following indexes should already exist from previous migrations
# Verify they exist:
php artisan tinker
>>> DB::select("SHOW INDEX FROM file_uploads WHERE Key_name IN ('idx_file_uploads_user_created', 'idx_file_uploads_filename_search', 'idx_file_uploads_composite_admin')");
```

### Step 3: Cache and Configuration

#### 3.1 Clear and Rebuild Caches
```bash
# Clear all caches
php artisan optimize:clear

# Rebuild optimized caches
php artisan optimize

# Clear view cache specifically
php artisan view:clear
php artisan view:cache

# Clear route cache
php artisan route:clear
php artisan route:cache
```

#### 3.2 Storage Links
```bash
# Ensure storage link exists
php artisan storage:link

# Verify storage permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### Step 4: Queue System Setup

#### 4.1 Queue Configuration
```bash
# Clear failed jobs (if safe to do so)
php artisan queue:flush

# Restart queue workers
php artisan queue:restart

# Start queue workers (production)
php artisan queue:work --daemon --tries=3 --timeout=300
```

#### 4.2 Supervisor Configuration (Production)
Create `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/app/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/worker.log
stopwaitsecs=3600
```

### Step 5: File System Permissions

#### 5.1 Set Proper Permissions
```bash
# Set ownership (adjust user/group as needed)
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache

# Set permissions
find storage -type f -exec chmod 644 {} \;
find storage -type d -exec chmod 755 {} \;
find bootstrap/cache -type f -exec chmod 644 {} \;
find bootstrap/cache -type d -exec chmod 755 {} \;
```

#### 5.2 Create Required Directories
```bash
# Ensure thumbnail cache directory exists
mkdir -p storage/app/thumbnails
chmod 755 storage/app/thumbnails

# Ensure temp directory exists for bulk operations
mkdir -p storage/app/temp
chmod 755 storage/app/temp
```

### Step 6: Service Verification

#### 6.1 Health Checks
```bash
# Test basic application functionality
php artisan about

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Test Redis connection
>>> Redis::ping();

# Test Google Drive service
>>> app(App\Services\GoogleDriveService::class)->testConnection();
```

#### 6.2 Feature Testing
```bash
# Test file upload functionality
curl -X POST http://your-domain.com/test-upload \
  -F "file=@test-file.txt" \
  -H "Authorization: Bearer your-test-token"

# Test file manager endpoints
curl -X GET http://your-domain.com/admin/file-manager \
  -H "Authorization: Bearer your-admin-token"
```

## Post-Deployment Verification

### Functional Testing

#### 6.1 Admin Dashboard Access
1. Log in as admin user
2. Navigate to File Manager
3. Verify file list loads correctly
4. Test responsive layout on different screen sizes

#### 6.2 File Operations Testing
1. **Selection**: Test individual and bulk file selection
2. **Preview**: Test file preview for different file types
3. **Download**: Test single and bulk file downloads
4. **Delete**: Test file deletion (use test files)
5. **Search**: Test search and filtering functionality

#### 6.3 Permission Testing
1. Test admin access to all files
2. Test employee access to client files
3. Test client access to own files only
4. Verify permission error handling

### Performance Verification

#### 6.4 Load Testing
```bash
# Test concurrent file operations
ab -n 100 -c 10 http://your-domain.com/admin/file-manager

# Monitor memory usage during bulk operations
top -p $(pgrep php)

# Check queue processing performance
php artisan queue:monitor
```

#### 6.5 Cache Performance
```bash
# Verify cache is working
php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');

# Check Redis memory usage
redis-cli info memory
```

## Monitoring and Maintenance

### Log Monitoring

#### 6.1 Application Logs
```bash
# Monitor Laravel logs
tail -f storage/logs/laravel.log

# Monitor queue logs
tail -f storage/logs/worker.log

# Monitor web server logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

#### 6.2 Performance Monitoring
```bash
# Monitor disk usage (important for file storage)
df -h

# Monitor memory usage
free -h

# Monitor Redis usage
redis-cli info stats
```

### Scheduled Maintenance

#### 6.3 Cleanup Tasks
Add to your cron schedule:

```bash
# Clean up temporary files daily
0 2 * * * php /path/to/app/artisan file-manager:cleanup-temp

# Clear expired cache entries
0 3 * * * php /path/to/app/artisan cache:prune-stale-tags

# Optimize database tables weekly
0 4 * * 0 php /path/to/app/artisan db:optimize
```

## Troubleshooting

### Common Issues

#### 6.4 File Upload Issues
```bash
# Check PHP upload limits
php -i | grep -E "(upload_max_filesize|post_max_size|max_execution_time)"

# Check disk space
df -h /path/to/storage

# Check permissions
ls -la storage/app/
```

#### 6.5 Queue Issues
```bash
# Check failed jobs
php artisan queue:failed

# Restart queue workers
php artisan queue:restart

# Monitor queue status
php artisan queue:monitor
```

#### 6.6 Cache Issues
```bash
# Clear all caches
php artisan optimize:clear

# Test cache connectivity
php artisan tinker
>>> Cache::store('redis')->put('test', 'value');
>>> Cache::store('redis')->get('test');
```

### Performance Issues

#### 6.7 Slow File Operations
1. Check database query performance
2. Verify Redis is functioning properly
3. Monitor Google Drive API rate limits
4. Check network connectivity to Google Drive

#### 6.8 Memory Issues
1. Increase PHP memory limit if needed
2. Optimize bulk operations batch size
3. Monitor queue worker memory usage
4. Consider using streaming for large files

## Security Considerations

### 6.9 Security Checklist
- [ ] Verify CSRF protection is enabled
- [ ] Check rate limiting is configured
- [ ] Ensure file access permissions are correct
- [ ] Verify audit logging is working
- [ ] Test file content validation
- [ ] Check Google Drive token security

### 6.10 Security Monitoring
```bash
# Monitor failed login attempts
grep "authentication failed" storage/logs/laravel.log

# Monitor file access attempts
grep "file access" storage/logs/laravel.log

# Check for suspicious activity
grep -E "(bulk|download|delete)" storage/logs/laravel.log | tail -100
```

## Rollback Procedures

See the separate rollback procedures document for detailed rollback instructions in case of deployment issues.

## Support and Escalation

### Contact Information
- **Development Team**: dev-team@company.com
- **System Administrator**: sysadmin@company.com
- **Emergency Contact**: +1-XXX-XXX-XXXX

### Escalation Path
1. **Level 1**: Check logs and common issues
2. **Level 2**: Contact development team
3. **Level 3**: System administrator involvement
4. **Level 4**: Emergency rollback procedures