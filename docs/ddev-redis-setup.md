# DDEV Redis Service Setup Guide

This guide explains how to enable and configure Redis in your DDEV environment for caching and queue operations.

## Overview

Redis is an in-memory data structure store that can be used as a cache, message broker, and queue backend. This setup provides Redis as an optional service in DDEV that can be enabled when needed.

## Enabling Redis Service

### 1. Verify Redis Configuration Files

Ensure these files exist in your project:
- `.ddev/docker-compose.redis.yaml` - DDEV service configuration
- `.ddev/redis/redis.conf` - Redis server configuration
- `.ddev/redis/README.txt` - Setup instructions

### 2. Restart DDEV Environment

```bash
ddev restart
```

This will:
- Start the Redis container
- Make Redis available at `redis:6379` within DDEV
- Create persistent storage for Redis data

### 3. Verify Redis is Running

Check that Redis is running:

```bash
# Check DDEV services
ddev describe

# Test Redis connectivity from within DDEV
ddev ssh
redis-cli -h redis ping
# Should return: PONG
exit
```

## Laravel Configuration

### Environment Variables

Add these variables to your `.env` file:

```env
# Redis Configuration
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=predis

# Cache Configuration (to use Redis)
CACHE_STORE=redis
CACHE_DRIVER=redis

# Queue Configuration (to use Redis)
QUEUE_CONNECTION=redis
```

### Configuration Files

The Laravel configuration files should automatically pick up these environment variables. Verify your `config/database.php` includes:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

## Testing Redis Connectivity

### Using Artisan Commands

Test Redis caching:

```bash
# Test cache operations
ddev artisan tinker
>>> Cache::store('redis')->put('test_key', 'test_value', 60);
>>> Cache::store('redis')->get('test_key');
# Should return: "test_value"
>>> exit
```

### Using Validation Commands

Use the built-in validation commands:

```bash
# Test cache configuration (includes Redis)
ddev artisan validate:cache-configuration

# Test queue configuration (includes Redis)
ddev artisan validate:queue-configuration
```

### Manual Redis CLI Testing

```bash
# Connect to Redis directly
ddev ssh
redis-cli -h redis

# Test basic operations
127.0.0.1:6379> ping
PONG
127.0.0.1:6379> set test_key "hello world"
OK
127.0.0.1:6379> get test_key
"hello world"
127.0.0.1:6379> exit
exit
```

## Queue Operations with Redis

### Configure Queue for Redis

Update your `.env`:

```env
QUEUE_CONNECTION=redis
```

### Test Queue Operations

```bash
# Start queue worker
ddev artisan queue:work redis --once

# In another terminal, dispatch a test job
ddev artisan tinker
>>> dispatch(new App\Jobs\UploadToGoogleDrive($fileUpload));
>>> exit
```

## Troubleshooting

### Redis Container Not Starting

1. Check DDEV logs:
   ```bash
   ddev logs redis
   ```

2. Verify the docker-compose file syntax:
   ```bash
   ddev debug compose-config
   ```

3. Restart DDEV completely:
   ```bash
   ddev stop
   ddev start
   ```

### Connection Refused Errors

1. Verify Redis is running:
   ```bash
   ddev describe
   ```

2. Check Redis service health:
   ```bash
   ddev ssh
   redis-cli -h redis ping
   ```

3. Verify environment variables:
   ```bash
   ddev artisan config:show database.redis
   ```

### Cache/Queue Not Using Redis

1. Clear Laravel configuration cache:
   ```bash
   ddev artisan config:clear
   ddev artisan cache:clear
   ```

2. Verify configuration:
   ```bash
   ddev artisan config:show cache.stores.redis
   ddev artisan config:show queue.connections.redis
   ```

### Performance Issues

1. Monitor Redis memory usage:
   ```bash
   ddev ssh
   redis-cli -h redis info memory
   ```

2. Check slow queries:
   ```bash
   redis-cli -h redis slowlog get 10
   ```

## Redis Configuration Customization

### Memory Limits

The default configuration sets a 256MB memory limit. To adjust:

1. Edit `.ddev/redis/redis.conf`:
   ```
   maxmemory 512mb
   ```

2. Restart DDEV:
   ```bash
   ddev restart
   ```

### Persistence Settings

Redis is configured with both RDB snapshots and AOF logging for development. For production, you may want to adjust these settings in `redis.conf`.

### Security Considerations

The development configuration has no password. For production deployments:

1. Set a Redis password in `redis.conf`:
   ```
   requirepass your_secure_password
   ```

2. Update your `.env`:
   ```env
   REDIS_PASSWORD=your_secure_password
   ```

## Performance Comparison

### Cache Performance
- **File Cache**: Good for single server, simple setup
- **Database Cache**: Good for multi-server without Redis
- **Redis Cache**: Excellent performance, best for production

### Queue Performance
- **Sync Queue**: Immediate execution, blocks requests
- **Database Queue**: Persistent, good for moderate loads
- **Redis Queue**: High performance, best for heavy loads

## Next Steps

After enabling Redis:

1. Update your application to use Redis for caching
2. Configure queues to use Redis for better performance
3. Monitor Redis usage and adjust configuration as needed
4. Consider Redis for session storage in production

For more information, see the main environment configuration guide.