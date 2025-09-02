# Cloud Storage Provider Migration Guide

## Overview

This guide helps you migrate from the legacy Google Drive-specific implementation to the new generic Cloud Storage Provider System. The migration maintains backward compatibility while providing access to enhanced features and multi-provider support.

## Migration Timeline

### Phase 1: Immediate (Backward Compatible)
- Legacy `GoogleDriveService` continues to work with deprecation warnings
- Existing database schema and data remain unchanged
- All current functionality preserved

### Phase 2: Gradual Migration (Recommended)
- Update service dependencies to use `CloudStorageManager`
- Migrate custom code to use provider interfaces
- Update configuration to use new format

### Phase 3: Complete Migration (Future)
- Remove deprecated service bindings
- Clean up legacy configuration
- Fully utilize new provider features

## Pre-Migration Checklist

Before starting the migration, ensure:

- [ ] All tests are passing
- [ ] Database backup is created
- [ ] Environment variables are documented
- [ ] Custom Google Drive integrations are identified
- [ ] User data and tokens are backed up

## Step-by-Step Migration

### Step 1: Update Service Dependencies

#### Before (Legacy)
```php
class MyService
{
    public function __construct(
        private GoogleDriveService $googleDriveService
    ) {}
    
    public function uploadFile($user, $file)
    {
        return $this->googleDriveService->uploadFile($user, $file);
    }
}
```

#### After (New System)
```php
class MyService
{
    public function __construct(
        private CloudStorageManager $storageManager
    ) {}
    
    public function uploadFile($user, $file)
    {
        $provider = $this->storageManager->getUserProvider($user);
        return $provider->uploadFile($user, $file['path'], $file['name']);
    }
}
```

### Step 2: Update Controller Dependencies

#### Before (Legacy)
```php
class FileController extends Controller
{
    public function __construct(
        private GoogleDriveService $googleDriveService
    ) {}
    
    public function upload(Request $request)
    {
        $result = $this->googleDriveService->uploadFile(
            auth()->user(),
            $request->file('upload')
        );
        
        return response()->json(['success' => true, 'file_id' => $result]);
    }
}
```

#### After (New System)
```php
class FileController extends Controller
{
    public function __construct(
        private CloudStorageManager $storageManager
    ) {}
    
    public function upload(Request $request)
    {
        $provider = $this->storageManager->getUserProvider(auth()->user());
        
        $result = $provider->uploadFile(
            auth()->user(),
            $request->file('upload')->getPathname(),
            $request->file('upload')->getClientOriginalName()
        );
        
        return response()->json(['success' => true, 'file_id' => $result]);
    }
}
```

### Step 3: Update Job Classes

#### Before (Legacy)
```php
class UploadToGoogleDrive implements ShouldQueue
{
    public function handle(GoogleDriveService $googleDriveService)
    {
        $result = $googleDriveService->uploadFile(
            $this->user,
            $this->filePath
        );
        
        // Update database with result
    }
}
```

#### After (New System)
```php
class UploadToGoogleDrive implements ShouldQueue
{
    public function handle(CloudStorageManager $storageManager)
    {
        $provider = $storageManager->getUserProvider($this->user);
        
        $result = $provider->uploadFile(
            $this->user,
            $this->filePath,
            $this->fileName
        );
        
        // Update database with result
    }
}
```

### Step 4: Update Configuration

#### Environment Variables
The new system uses the same environment variables but with enhanced configuration options:

```env
# Existing variables (unchanged)
GOOGLE_DRIVE_CLIENT_ID=your_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret

# New variables (optional)
CLOUD_STORAGE_DEFAULT=google-drive
```

#### Configuration File Migration
Run the configuration migration command:

```bash
php artisan cloud-storage:migrate-config
```

This command:
- Migrates existing Google Drive settings to the new format
- Preserves all current configuration values
- Creates backup of original configuration
- Validates migrated configuration

### Step 5: Update Database Settings

If you have Google Drive settings stored in the database, migrate them:

```php
// Migration script (run once)
$oldSettings = DB::table('settings')->where('key', 'like', 'google_drive_%')->get();

foreach ($oldSettings as $setting) {
    $newKey = str_replace('google_drive_', '', $setting->key);
    CloudStorageSetting::set('google-drive', $newKey, $setting->value);
}
```

### Step 6: Update Tests

#### Before (Legacy)
```php
class FileUploadTest extends TestCase
{
    public function test_file_upload()
    {
        $this->mock(GoogleDriveService::class, function ($mock) {
            $mock->shouldReceive('uploadFile')->once()->andReturn('file_id_123');
        });
        
        // Test logic
    }
}
```

#### After (New System)
```php
class FileUploadTest extends TestCase
{
    public function test_file_upload()
    {
        $this->mock(CloudStorageManager::class, function ($mock) {
            $provider = $this->mock(CloudStorageProviderInterface::class);
            $provider->shouldReceive('uploadFile')->once()->andReturn('file_id_123');
            $mock->shouldReceive('getUserProvider')->andReturn($provider);
        });
        
        // Test logic
    }
}
```

## Configuration Migration

### Legacy Configuration Format
```php
// config/services.php
'google_drive' => [
    'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_DRIVE_REDIRECT_URI'),
],
```

### New Configuration Format
```php
// config/cloud-storage.php
'providers' => [
    'google-drive' => [
        'driver' => 'google-drive',
        'class' => GoogleDriveProvider::class,
        'error_handler' => GoogleDriveErrorHandler::class,
        'auth_type' => 'oauth',
        'storage_model' => 'hierarchical',
        'config' => [
            'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
            'redirect_uri' => config('app.url') . '/admin/cloud-storage/google-drive/callback',
        ],
        'features' => [
            'folder_creation' => true,
            'file_upload' => true,
            'file_delete' => true,
            'folder_delete' => true,
            'max_file_size' => 5368709120, // 5GB
            'supported_file_types' => ['*'],
        ],
    ],
],
```

## Database Migration

### Schema Changes
The new system introduces additional tables but maintains compatibility with existing data:

```sql
-- New tables (automatically created by migrations)
CREATE TABLE cloud_storage_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(255) NOT NULL,
    key VARCHAR(255) NOT NULL,
    value TEXT,
    encrypted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_provider_key (provider, key)
);

CREATE TABLE cloud_storage_health_statuses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(255) NOT NULL,
    status ENUM('healthy', 'authentication_required', 'connection_issues', 'not_connected') NOT NULL,
    consolidated_status ENUM('healthy', 'authentication_required', 'connection_issues', 'not_connected') NOT NULL,
    last_checked_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Data Migration
Existing Google Drive tokens and settings are automatically migrated:

```bash
# Run migrations
php artisan migrate

# Migrate existing configuration
php artisan cloud-storage:migrate-config

# Fix any health status inconsistencies
php artisan cloud-storage:fix-health-status
```

## Backward Compatibility

### Deprecated Services
The legacy `GoogleDriveService` is wrapped to maintain compatibility:

```php
// This still works but shows deprecation warnings
$googleDriveService = app(GoogleDriveService::class);
$result = $googleDriveService->uploadFile($user, $file);
```

### Deprecation Warnings
Enable deprecation warnings in development:

```env
APP_DEBUG=true
LOG_DEPRECATIONS=true
```

Warnings will appear in logs:
```
[WARNING] GoogleDriveService is deprecated. Use CloudStorageManager with provider 'google-drive' instead.
Migration guide: https://docs.example.com/migration/cloud-storage-provider-migration-guide
```

### Service Container Bindings
Legacy bindings are maintained:

```php
// Legacy binding (deprecated but functional)
$this->app->bind(GoogleDriveService::class, function ($app) {
    return new DeprecatedGoogleDriveServiceWrapper(
        $app->make(CloudStorageManager::class)
    );
});
```

## Testing Migration

### Validation Steps

1. **Run Existing Tests**
   ```bash
   php artisan test
   ```

2. **Test Legacy Functionality**
   ```bash
   php artisan test --filter=GoogleDrive
   ```

3. **Test New Provider System**
   ```bash
   php artisan test --filter=CloudStorage
   ```

4. **Integration Testing**
   ```bash
   php artisan cloud-storage:test-providers
   ```

### Rollback Plan

If issues occur during migration:

1. **Revert Configuration**
   ```bash
   git checkout HEAD~1 -- config/cloud-storage.php
   ```

2. **Restore Database**
   ```bash
   php artisan migrate:rollback --step=5
   ```

3. **Clear Caches**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

## Common Migration Issues

### Issue 1: Method Signature Changes

**Problem**: Method signatures have changed between legacy and new system.

**Solution**: Update method calls to match new interface:

```php
// Old
$googleDriveService->uploadFile($user, $uploadedFile);

// New
$provider->uploadFile($user, $uploadedFile->getPathname(), $uploadedFile->getClientOriginalName());
```

### Issue 2: Configuration Not Found

**Problem**: Provider configuration is not found.

**Solution**: Run configuration migration:

```bash
php artisan cloud-storage:migrate-config
php artisan config:cache
```

### Issue 3: Health Status Issues

**Problem**: Provider shows as "not connected" despite valid tokens.

**Solution**: Fix health status:

```bash
php artisan cloud-storage:fix-health-status
```

### Issue 4: Deprecation Warnings in Production

**Problem**: Deprecation warnings appearing in production logs.

**Solution**: Disable deprecation logging in production:

```env
LOG_DEPRECATIONS=false
```

## Performance Considerations

### Caching
The new system includes improved caching:

```php
// Provider instances are cached
$provider = $storageManager->getProvider('google-drive'); // Cached

// Configuration is cached
$config = $configService->getProviderConfig('google-drive'); // Cached
```

### Connection Pooling
For providers that support it, connection pooling is enabled:

```php
// S3 provider uses connection pooling
$s3Provider = $storageManager->getProvider('amazon-s3');
```

### Lazy Loading
Providers are loaded only when needed:

```php
// Provider is not instantiated until first use
$provider = $storageManager->getProvider('google-drive');
```

## Post-Migration Checklist

After completing the migration:

- [ ] All tests pass
- [ ] Legacy functionality works with deprecation warnings
- [ ] New provider features are accessible
- [ ] Configuration is properly migrated
- [ ] Health checks pass for all providers
- [ ] Performance metrics are being collected
- [ ] Audit logs are working
- [ ] Documentation is updated

## Next Steps

After successful migration:

1. **Add Additional Providers**: Consider adding Amazon S3 or other providers
2. **Utilize New Features**: Implement capability detection and graceful degradation
3. **Monitor Performance**: Use the monitoring dashboard to track provider performance
4. **Plan Legacy Removal**: Schedule removal of deprecated services in future releases

## Support

If you encounter issues during migration:

1. Check the troubleshooting guide
2. Review the API documentation
3. Run diagnostic commands
4. Check application logs
5. Contact the development team

The migration process is designed to be safe and reversible. Take your time and test thoroughly at each step.