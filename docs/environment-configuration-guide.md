# Environment Configuration Guide

This comprehensive guide covers caching, queue, and background job processing configuration for the Laravel application. It provides tested configuration examples, validation procedures, and production recommendations.

## Table of Contents

1. [Caching Configuration](#caching-configuration)
2. [Queue Configuration](#queue-configuration)
3. [Background Job Processing](#background-job-processing)
4. [Environment Validation](#environment-validation)
5. [Environment Cleanup](#environment-cleanup)
6. [Production Recommendations](#production-recommendations)
7. [Quick Reference](#quick-reference)

---

## Caching Configuration

The application supports multiple caching backends, each with different performance characteristics and setup requirements.

### File Caching (Default - Works Out of Box)

File caching is the default configuration and requires no additional setup.

**Configuration:**
```env
CACHE_STORE=file
```

**Storage Location:** `storage/framework/cache/data`

**Performance Characteristics:**
- Best for: Single server, low to medium traffic
- Limitations: Not shared across servers
- Performance: Good for development, adequate for small production

**Validation:**
```bash
# Test file cache operations
ddev artisan validate:cache --backend=file
```

**Manual Testing:**
```bash
ddev artisan tinker
>>> Cache::put('test_key', 'test_value', 60);
>>> Cache::get('test_key'); // Should return: "test_value"
>>> Cache::forget('test_key');
>>> Cache::get('test_key'); // Should return: null
>>> exit
```

### Database Caching (Validated - Works)

Database caching uses the existing `cache` table and works out of the box.

**Configuration:**
```env
CACHE_STORE=database
```

**Requirements:**
- Uses existing `cache` table (already migrated)
- No additional setup required

**Performance Characteristics:**
- Best for: Multi-server setups without Redis
- Limitations: Database I/O overhead
- Performance: Good for medium traffic, scales with database

**Validation:**
```bash
# Test database cache operations
ddev artisan validate:cache --backend=database
```

**Manual Testing:**
```bash
ddev artisan tinker
>>> config(['cache.default' => 'database']);
>>> Cache::put('test_db_key', 'test_db_value', 60);
>>> Cache::get('test_db_key'); // Should return: "test_db_value"
>>> Cache::forget('test_db_key');
>>> exit
```

### Redis Caching (Requires Setup)

Redis provides excellent performance but requires additional DDEV service setup.

**DDEV Service Setup:**

1. Ensure Redis service files exist:
   - `.ddev/docker-compose.redis.yaml`
   - `.ddev/redis/redis.conf`

2. Restart DDEV to enable Redis:
   ```bash
   ddev restart
   ```

3. Verify Redis is running:
   ```bash
   ddev describe
   ddev ssh
   redis-cli -h redis ping  # Should return: PONG
   exit
   ```

**Configuration:**
```env
CACHE_STORE=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=predis
```

**Performance Characteristics:**
- Best for: High-traffic production environments
- Limitations: Additional service dependency
- Performance: Excellent for all scenarios

**Validation:**
```bash
# Test Redis cache operations
ddev artisan validate:cache --backend=redis
```

**Manual Testing:**
```bash
ddev artisan tinker
>>> Cache::store('redis')->put('test_redis_key', 'test_redis_value', 60);
>>> Cache::store('redis')->get('test_redis_key'); // Should return: "test_redis_value"
>>> exit
```

### Memcached Caching (PHP Extension Available, Service Setup Required)

Memcached PHP extension is available but the service is not configured in DDEV by default.

**Status:** PHP extension exists, but memcached service is not running in current DDEV setup

**To Enable Memcached:**

1. Add memcached service to DDEV configuration
2. Configure environment variables:
   ```env
   CACHE_STORE=memcached
   MEMCACHED_HOST=memcached
   MEMCACHED_PORT=11211
   ```

**Performance Characteristics:**
- Best for: Multi-server caching scenarios
- Limitations: Additional service dependency, less feature-rich than Redis
- Performance: Good for distributed caching

**Note:** Currently not recommended due to lack of DDEV service configuration. Use Redis instead for similar benefits.

---

## Queue Configuration

The application uses Laravel's queue system for background processing, particularly for the `UploadToGoogleDrive` job.

### Sync Queue (Development)

Sync queue executes jobs immediately, blocking the request.

**Configuration:**
```env
QUEUE_CONNECTION=sync
```

**Use Cases:**
- Development and testing
- Debugging job execution
- Simple applications with minimal background processing

**Characteristics:**
- Jobs execute immediately
- Blocks request processing
- No queue worker needed
- Not suitable for production file uploads

**Validation:**
```bash
ddev artisan validate:queue --connection=sync
```

### Database Queue (Production Recommended)

Database queue stores jobs in the existing `jobs` table for persistent processing.

**Configuration:**
```env
QUEUE_CONNECTION=database
```

**Requirements:**
- Uses existing `jobs` table (already migrated)
- Optional: `failed_jobs` table for failed job tracking

**Characteristics:**
- Persistent job storage
- Survives application restarts
- Good for moderate job volumes
- Requires queue worker process

**Validation:**
```bash
ddev artisan validate:queue --connection=database --dispatch-test
```

**Queue Worker Commands:**
```bash
# Process jobs once
ddev artisan queue:work --once

# Run queue worker daemon
ddev artisan queue:work

# View queue status
ddev artisan queue:monitor

# Handle failed jobs
ddev artisan queue:failed
ddev artisan queue:retry all
```

### Redis Queue (High Performance)

Redis queue provides the best performance for high-volume job processing.

**Prerequisites:**
- Redis service must be configured (see Redis Caching section)

**Configuration:**
```env
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=predis
```

**Characteristics:**
- Excellent performance
- In-memory job storage
- Best for high-volume scenarios
- Requires Redis service

**Validation:**
```bash
ddev artisan validate:queue --connection=redis --dispatch-test
```

### UploadToGoogleDrive Job Integration

The application's main background job handles file uploads to Google Drive.

**Job Characteristics:**
- Processes file uploads asynchronously
- Handles Google Drive API interactions
- Includes retry logic for failed uploads
- Cleans up local files after successful upload

**Queue Relationship:**
- **Sync**: Files upload immediately during request
- **Database**: Files queued for background processing
- **Redis**: High-performance background processing

**Monitoring:**
```bash
# Check job status
ddev artisan queue:monitor

# View failed uploads
ddev artisan queue:failed

# Retry failed uploads
ddev artisan queue:retry all
```

---

## Background Job Processing

Choose between cron jobs and daemon processes for queue processing.

### Cron Job Approach

Use cron jobs for simple, reliable background processing.

**Implementation:**
```bash
# Add to crontab (every minute)
* * * * * cd /path/to/project && php artisan queue:work --stop-when-empty --max-time=50
```

**For DDEV Development:**
```bash
# Manual cron-like execution
ddev artisan queue:work --stop-when-empty
```

**Advantages:**
- Simple setup and management
- Automatic process restart
- Built-in resource management
- No daemon management complexity
- Handles process crashes gracefully

**Disadvantages:**
- Processing delays (up to 1 minute)
- Potential job overlap if processing takes longer than cron interval
- Less real-time responsiveness
- May not be suitable for time-sensitive uploads

**Best For:**
- Small to medium applications
- Environments where simplicity is preferred
- Scenarios where slight delays are acceptable

### Daemon Approach (Queue Worker)

Use persistent queue workers for real-time processing.

**Implementation:**
```bash
# Start queue worker daemon
php artisan queue:work --daemon --tries=3 --timeout=60
```

**Production Supervisor Configuration:**
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/worker.log
stopwaitsecs=3600
```

**Systemd Service Example:**
```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /path/to/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

**Advantages:**
- Real-time job processing
- Better performance for high-volume uploads
- Immediate file processing
- Lower latency

**Disadvantages:**
- Process management complexity
- Potential memory leaks over time
- Requires process monitoring (supervisor/systemd)
- Manual restart needed for code changes
- More complex deployment

**Best For:**
- High-volume applications
- Time-sensitive file processing
- Production environments with proper monitoring

**Management Commands:**
```bash
# Start worker
php artisan queue:work

# Restart workers (after code changes)
php artisan queue:restart

# Monitor worker status
php artisan queue:monitor

# Graceful worker shutdown
php artisan down
php artisan queue:restart
php artisan up
```

---

## Environment Validation

Use built-in validation commands to verify your configuration.

### Cache Validation

**Test All Cache Backends:**
```bash
ddev artisan validate:cache --all
```

**Test Specific Backend:**
```bash
ddev artisan validate:cache --backend=file
ddev artisan validate:cache --backend=database
ddev artisan validate:cache --backend=redis
```

**Expected Output:**
```
🔍 Cache Configuration Validation

Testing file cache backend...
✓ PUT operation successful
✓ GET operation successful
✓ FORGET operation successful
🎉 file cache backend is working correctly!
```

### Queue Validation

**Test All Queue Connections:**
```bash
ddev artisan validate:queue --all
```

**Test Specific Connection:**
```bash
ddev artisan validate:queue --connection=sync
ddev artisan validate:queue --connection=database --dispatch-test
ddev artisan validate:queue --connection=redis --dispatch-test
```

**Expected Output:**
```
🔍 Queue Configuration Validation

Testing database queue connection...
✓ Queue size check successful (current size: 0)
✓ Jobs table 'jobs' exists and is accessible
✓ Failed jobs table 'failed_jobs' exists
🎉 database queue connection is working correctly!
```

### Health Check Commands

**Production Health Checks:**
```bash
# Verify cache is working
ddev artisan validate:cache

# Verify queue is working
ddev artisan validate:queue

# Check queue status
ddev artisan queue:monitor

# Check failed jobs
ddev artisan queue:failed

# Application health
ddev artisan about
```

### Troubleshooting Common Issues

**File Cache Issues:**
- Ensure `storage/framework/cache/data` directory exists and is writable
- Check file permissions: `chmod 755 storage/framework/cache/data`
- Verify web server has write access to storage directory

**Database Cache Issues:**
- Ensure cache table exists: `php artisan cache:table && php artisan migrate`
- Check database connection configuration
- Verify `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE` settings in `.env`

**Redis Issues:**
- Ensure Redis server is running
- For DDEV: Add Redis service with `docker-compose.redis.yaml`
- Check `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` in `.env`
- Test connection: `redis-cli ping`

**Queue Issues:**
- Ensure jobs table exists: `php artisan queue:table && php artisan migrate`
- Check database connection for database queues
- Verify Redis connection for Redis queues
- Check queue worker is running for non-sync queues

---

## Environment Cleanup

Remove unused variables and optimize your configuration.

### Required Variables

**Core Application:**
```env
APP_NAME="Upload Drive-In"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL="https://upload-drive-in.ddev.site"
```

**Database:**
```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=db
DB_USERNAME=db
DB_PASSWORD=db
```

**Cache (choose one):**
```env
# File cache (default)
CACHE_STORE=file

# OR Database cache
CACHE_STORE=database

# OR Redis cache (requires Redis service)
CACHE_STORE=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=predis
```

**Queue (choose one):**
```env
# Sync queue (development)
QUEUE_CONNECTION=sync

# OR Database queue (production)
QUEUE_CONNECTION=database

# OR Redis queue (high performance)
QUEUE_CONNECTION=redis
```

### Optional Variables

**Redis (only if using Redis):**
```env
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=predis
```

**Cache Driver (legacy support):**
```env
CACHE_DRIVER=file  # Defaults to CACHE_STORE value
```

**Cache Prefix (for shared environments):**
```env
CACHE_PREFIX=myapp_cache_
```

### Removable Variables

**Memcached (service not configured in DDEV):**
```env
# These can be safely removed unless you add memcached service
MEMCACHED_HOST=127.0.0.1
MEMCACHED_PORT=11211
```

**Legacy Variables:**
- Any commented-out cache or queue variables
- Duplicate or unused configuration variables
- Development-specific variables no longer needed

### Minimal Working Configurations

**File Cache + Sync Queue (Development):**
```env
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

**Database Cache + Database Queue (Production):**
```env
CACHE_STORE=database
QUEUE_CONNECTION=database
```

**Redis Cache + Redis Queue (High Performance):**
```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=predis
```

### Environment File Structure

**Recommended Organization:**
```env
# Application
APP_NAME="Upload Drive-In"
APP_ENV=local
APP_KEY=base64:...

# Database
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=db
DB_USERNAME=db
DB_PASSWORD=db

# Cache & Queue
CACHE_STORE=file
QUEUE_CONNECTION=sync

# Redis (if used)
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=predis

# Google Drive
GOOGLE_DRIVE_CLIENT_ID=...
GOOGLE_DRIVE_CLIENT_SECRET=...
CLOUD_STORAGE_DEFAULT=google-drive

# Other services...
```

---

## Production Recommendations

Optimize your configuration for production deployment.

### Recommended Production Configurations

**Small to Medium Applications:**
```env
CACHE_STORE=database
QUEUE_CONNECTION=database
```

**High-Traffic Applications:**
```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-secure-password
REDIS_PORT=6379
REDIS_CLIENT=predis
```

### Supervisor Configuration for Queue Workers

**Basic Supervisor Config (`/etc/supervisor/conf.d/laravel-worker.conf`):**
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
stopwaitsecs=3600
```

**High-Volume Configuration:**
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=1 --tries=3 --max-time=1800 --memory=512
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
stopwaitsecs=3600
```

**Supervisor Management:**
```bash
# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start laravel-worker:*

# Stop workers
sudo supervisorctl stop laravel-worker:*

# Restart workers (after code deployment)
sudo supervisorctl restart laravel-worker:*

# Check worker status
sudo supervisorctl status
```

### Monitoring and Maintenance

**Queue Monitoring:**
```bash
# Check queue status
php artisan queue:monitor

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

**Cache Monitoring:**
```bash
# Clear cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Optimize for production
php artisan optimize
```

**Redis Monitoring (if using Redis):**
```bash
# Check Redis memory usage
redis-cli info memory

# Monitor Redis operations
redis-cli monitor

# Check slow queries
redis-cli slowlog get 10
```

### Performance Impact on File Uploads

**Cache Performance Impact:**
- **File Cache**: Minimal impact, good for single server
- **Database Cache**: Slight database load, good for multi-server
- **Redis Cache**: Best performance, recommended for high traffic

**Queue Performance Impact:**
- **Sync Queue**: Blocks upload requests, not recommended for production
- **Database Queue**: Good performance, reliable for most scenarios
- **Redis Queue**: Best performance for high-volume uploads

**Upload Processing Times:**
- **Sync**: Immediate but blocks request (30-60 seconds)
- **Database Queue**: 1-5 seconds delay, processed in background
- **Redis Queue**: Near-immediate queuing, fastest background processing

### Production Health Checks

**Automated Health Checks:**
```bash
#!/bin/bash
# health-check.sh

# Check cache
php artisan validate:cache --backend=$(php artisan config:show cache.default)
if [ $? -ne 0 ]; then
    echo "Cache health check failed"
    exit 1
fi

# Check queue
php artisan validate:queue --connection=$(php artisan config:show queue.default)
if [ $? -ne 0 ]; then
    echo "Queue health check failed"
    exit 1
fi

# Check queue workers (if using database/redis queues)
QUEUE_CONNECTION=$(php artisan config:show queue.default)
if [ "$QUEUE_CONNECTION" != "sync" ]; then
    WORKER_COUNT=$(ps aux | grep "queue:work" | grep -v grep | wc -l)
    if [ $WORKER_COUNT -eq 0 ]; then
        echo "No queue workers running"
        exit 1
    fi
fi

echo "All health checks passed"
```

**Monitoring Checklist:**
- [ ] Cache operations working
- [ ] Queue workers running
- [ ] Failed jobs count acceptable
- [ ] Redis memory usage (if applicable)
- [ ] Disk space for file cache (if applicable)
- [ ] Database performance (if using database cache/queue)

### Deployment Considerations

**Code Deployment Steps:**
```bash
# 1. Put application in maintenance mode
php artisan down

# 2. Update code
git pull origin main

# 3. Update dependencies
composer install --no-dev --optimize-autoloader

# 4. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 5. Run migrations
php artisan migrate --force

# 6. Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Restart queue workers
php artisan queue:restart

# 8. Bring application back online
php artisan up
```

**Zero-Downtime Deployment:**
- Use queue worker restart instead of stopping
- Implement graceful worker shutdown
- Use load balancer for rolling deployments
- Monitor queue processing during deployment

---

## Quick Reference

### Common Configuration Scenarios

#### Development Setup (File Cache + Database Queue)
```env
# Minimal development configuration
CACHE_STORE=file
QUEUE_CONNECTION=database
```

**Validation:**
```bash
ddev artisan validate:cache --backend=file
ddev artisan validate:queue --backend=database
```

#### Production Setup (Database Cache + Database Queue)
```env
# Production with database backends
CACHE_STORE=database
QUEUE_CONNECTION=database
```

**Validation:**
```bash
ddev artisan validate:cache --backend=database
ddev artisan validate:queue --backend=database
```

#### High-Performance Setup (Redis Cache + Redis Queue)
```env
# Redis configuration (requires DDEV Redis service)
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=predis

CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

**Setup & Validation:**
```bash
# Enable Redis service in DDEV
ddev restart

# Validate Redis connectivity
ddev artisan validate:cache --backend=redis
ddev artisan validate:queue --backend=redis
```

### One-Line Validation Commands

```bash
# Test all cache backends
ddev artisan validate:cache

# Test specific cache backend
ddev artisan validate:cache --backend=file
ddev artisan validate:cache --backend=database
ddev artisan validate:cache --backend=redis

# Test all queue backends
ddev artisan validate:queue

# Test specific queue backend
ddev artisan validate:queue --backend=sync
ddev artisan validate:queue --backend=database
ddev artisan validate:queue --backend=redis
```

### Environment Variable Cleanup

**Safe to Remove:**
```env
# These can be safely removed from .env
MEMCACHED_HOST=127.0.0.1
MEMCACHED_PORT=11211
MEMCACHED_USERNAME=
MEMCACHED_PASSWORD=
```

**Required Variables:**
```env
# Always keep these
CACHE_STORE=file
QUEUE_CONNECTION=database
```

**Optional Variables (only if using Redis):**
```env
# Only needed if CACHE_STORE=redis or QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=predis
```

### Background Job Processing Quick Start

**Cron Job Approach (Simple):**
```bash
# Add to crontab
* * * * * cd /path/to/project && php artisan queue:work --stop-when-empty
```

**Daemon Approach (Performance):**
```bash
# Start queue worker
php artisan queue:work --daemon

# With supervisor (production)
sudo supervisorctl start laravel-worker:*
```

This completes the comprehensive environment configuration guide. Use the validation commands to test your setup and refer to the troubleshooting sections for common issues.