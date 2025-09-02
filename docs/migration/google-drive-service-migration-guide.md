# GoogleDriveService Migration Guide

This guide helps developers migrate from the deprecated `GoogleDriveService` to the new `CloudStorageManager` system.

## Overview

The `GoogleDriveService` class has been deprecated in favor of the new generic cloud storage provider system. This new system provides better abstraction, supports multiple providers, and offers improved error handling and configuration management.

## Migration Timeline

- **Current**: `GoogleDriveService` is deprecated but still functional with warnings
- **Next Major Version**: `GoogleDriveService` will be removed
- **Recommended Action**: Migrate to `CloudStorageManager` as soon as possible

## Key Changes

### Before (Deprecated)
```php
use App\Services\GoogleDriveService;

class MyController extends Controller
{
    public function __construct(private GoogleDriveService $driveService)
    {
    }
    
    public function uploadFile(User $user)
    {
        $fileId = $this->driveService->uploadFile(
            $user,
            'uploads/file.pdf',
            'folder-id',
            'file.pdf',
            'application/pdf'
        );
    }
}
```

### After (Recommended)
```php
use App\Services\CloudStorageManager;

class MyController extends Controller
{
    public function __construct(private CloudStorageManager $storageManager)
    {
    }
    
    public function uploadFile(User $user)
    {
        $provider = $this->storageManager->getProvider('google-drive');
        $fileId = $provider->uploadFile(
            $user,
            'uploads/file.pdf',
            'folder-id',
            ['filename' => 'file.pdf', 'mime_type' => 'application/pdf']
        );
    }
}
```

## Method Migration Map

| Deprecated Method | New Method | Notes |
|------------------|------------|-------|
| `GoogleDriveService::uploadFile()` | `CloudStorageProvider::uploadFile()` | Parameters changed to use metadata array |
| `GoogleDriveService::deleteFile()` | `CloudStorageProvider::deleteFile()` | Same signature |
| `GoogleDriveService::getAuthUrl()` | `CloudStorageProvider::getAuthUrl()` | Same signature |
| `GoogleDriveService::handleCallback()` | `CloudStorageProvider::handleAuthCallback()` | Same signature |
| `GoogleDriveService::disconnect()` | `CloudStorageProvider::disconnect()` | Same signature |
| `GoogleDriveService::validateAndRefreshToken()` | `CloudStorageProvider::hasValidConnection()` | Returns boolean instead of detailed status |
| `GoogleDriveService::testApiConnectivity()` | `CloudStorageProvider::getConnectionHealth()` | Returns health status object |

## Step-by-Step Migration

### 1. Update Constructor Dependencies

**Before:**
```php
public function __construct(GoogleDriveService $driveService)
{
    $this->driveService = $driveService;
}
```

**After:**
```php
public function __construct(CloudStorageManager $storageManager)
{
    $this->storageManager = $storageManager;
}
```

### 2. Update Method Calls

**Before:**
```php
$this->driveService->uploadFile($user, $path, $folderId, $filename, $mimeType);
```

**After:**
```php
$provider = $this->storageManager->getProvider('google-drive');
$provider->uploadFile($user, $path, $folderId, [
    'filename' => $filename,
    'mime_type' => $mimeType
]);
```

### 3. Update Error Handling

**Before:**
```php
try {
    $this->driveService->uploadFile(...);
} catch (Exception $e) {
    // Handle generic exception
}
```

**After:**
```php
try {
    $provider = $this->storageManager->getProvider('google-drive');
    $provider->uploadFile(...);
} catch (CloudStorageException $e) {
    // Handle cloud storage specific exception with error type
    $errorType = $e->getErrorType();
    // Handle based on error type
}
```

### 4. Update Health Checks

**Before:**
```php
$isConnected = $this->driveService->testApiConnectivity($user);
```

**After:**
```php
$provider = $this->storageManager->getProvider('google-drive');
$health = $provider->getConnectionHealth($user);
$isConnected = $health->isHealthy();
```

## Advanced Migration Scenarios

### Multiple Provider Support

The new system supports multiple providers. If you want to make your code provider-agnostic:

```php
// Get user's preferred provider or default
$provider = $this->storageManager->getUserProvider($user);

// Or get a specific provider
$provider = $this->storageManager->getProvider('google-drive');

// Check provider capabilities
if ($provider->supportsFeature('folder_creation')) {
    // Use folder creation feature
}
```

### Configuration Migration

**Before:**
```php
// Configuration was hardcoded or in environment variables
$rootFolderId = env('GOOGLE_DRIVE_ROOT_FOLDER_ID', 'root');
```

**After:**
```php
// Configuration is managed through the provider system
$config = $this->storageManager->getProviderConfig('google-drive');
$rootFolderId = $config['root_folder_id'] ?? 'root';
```

## Testing Migration

### Update Test Dependencies

**Before:**
```php
class MyTest extends TestCase
{
    public function test_upload()
    {
        $driveService = $this->mock(GoogleDriveService::class);
        $driveService->shouldReceive('uploadFile')->once();
        
        // Test code
    }
}
```

**After:**
```php
class MyTest extends TestCase
{
    public function test_upload()
    {
        $storageManager = $this->mock(CloudStorageManager::class);
        $provider = $this->mock(CloudStorageProviderInterface::class);
        
        $storageManager->shouldReceive('getProvider')
            ->with('google-drive')
            ->andReturn($provider);
            
        $provider->shouldReceive('uploadFile')->once();
        
        // Test code
    }
}
```

### Use Test Helpers

The new system provides test helpers for easier testing:

```php
use Tests\Traits\CloudStorageTestHelpers;

class MyTest extends TestCase
{
    use CloudStorageTestHelpers;
    
    public function test_upload()
    {
        $provider = $this->createMockProvider('google-drive');
        $provider->shouldReceive('uploadFile')->once();
        
        // Test code
    }
}
```

## Common Migration Issues

### 1. Circular Dependencies

**Problem:** Getting circular dependency errors when injecting services.

**Solution:** Use the CloudStorageManager as the main entry point instead of individual services.

### 2. Method Signature Changes

**Problem:** Some methods have different signatures.

**Solution:** Check the method migration map above and update parameters accordingly.

### 3. Error Handling Changes

**Problem:** Different exception types are thrown.

**Solution:** Update catch blocks to handle `CloudStorageException` and use error types for specific handling.

## Deprecation Warnings

When using the deprecated `GoogleDriveService`, you'll see warnings like:

```
[WARNING] GoogleDriveService is deprecated. Use CloudStorageManager instead.
[WARNING] Deprecated GoogleDriveService method called: uploadFile
```

These warnings include:
- The deprecated method name
- The recommended replacement
- Migration guidance
- Example code

## Benefits of Migration

1. **Multi-Provider Support**: Easily switch between Google Drive, S3, Azure, etc.
2. **Better Error Handling**: Structured error types and recovery strategies
3. **Improved Configuration**: Centralized and validated configuration management
4. **Enhanced Testing**: Better mocking and test helpers
5. **Future-Proof**: New features will only be added to the new system

## Getting Help

- Check the [Cloud Storage Configuration Guide](../cloud-storage-configuration-guide.md)
- Review the [Cloud Storage Provider Testing Guide](../testing/cloud-storage-provider-testing-guide.md)
- Look at existing migrated code in the codebase for examples
- Check deprecation warning logs for specific guidance

## Migration Checklist

- [ ] Update constructor dependencies to use `CloudStorageManager`
- [ ] Replace direct `GoogleDriveService` method calls
- [ ] Update error handling to use `CloudStorageException`
- [ ] Update tests to mock the new interfaces
- [ ] Test the migration thoroughly
- [ ] Remove any direct imports of `GoogleDriveService`
- [ ] Update documentation and comments
- [ ] Verify no deprecation warnings in logs