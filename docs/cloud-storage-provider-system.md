# Cloud Storage Provider System Documentation

## Overview

The Cloud Storage Provider System is a comprehensive abstraction layer that enables the application to work with multiple cloud storage providers (Google Drive, Amazon S3, Azure Blob Storage, etc.) through a unified interface. This system provides provider factory patterns, dynamic provider resolution, configuration management, and enhanced error handling.

## Architecture

### Core Components

#### CloudStorageManager
The central service that coordinates all cloud storage operations and provider management.

```php
$manager = app(CloudStorageManager::class);

// Get default provider
$provider = $manager->getDefaultProvider();

// Get specific provider
$provider = $manager->getProvider('amazon-s3');

// Get user's preferred provider
$provider = $manager->getUserProvider($user);

// Switch user's provider
$manager->switchUserProvider($user, 'google-drive');
```

#### CloudStorageFactory
Factory service responsible for instantiating and configuring cloud storage providers.

```php
$factory = app(CloudStorageFactory::class);

// Create provider instance
$provider = $factory->create('google-drive');

// Create provider for specific user
$provider = $factory->createForUser($user, 'amazon-s3');

// Register new provider
$factory->register('dropbox', DropboxProvider::class);
```

#### CloudConfigurationService
Service for managing provider configurations from multiple sources.

```php
$configService = app(CloudConfigurationService::class);

// Get provider configuration
$config = $configService->getProviderConfig('amazon-s3');

// Validate configuration
$errors = $configService->validateProviderConfig('google-drive', $config);

// Check if provider is configured
$isConfigured = $configService->isProviderConfigured('amazon-s3');
```

## Supported Providers

### Google Drive
- **Authentication**: OAuth 2.0
- **Storage Model**: Hierarchical (folders)
- **Features**: Full CRUD operations, folder management
- **Configuration**: Client ID, Client Secret, Redirect URI

### Amazon S3
- **Authentication**: API Key (Access Key + Secret)
- **Storage Model**: Flat (key-based)
- **Features**: File upload/delete, presigned URLs, storage classes
- **Configuration**: Access Key ID, Secret Access Key, Region, Bucket

### Future Providers
- Azure Blob Storage
- Dropbox
- Microsoft OneDrive

## Configuration

### Environment Variables

```env
# Default provider
CLOUD_STORAGE_DEFAULT=google-drive

# Google Drive
GOOGLE_DRIVE_CLIENT_ID=your_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret

# Amazon S3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket_name
```

### Configuration File

The `config/cloud-storage.php` file defines provider configurations:

```php
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
        ],
        'features' => [
            'folder_creation' => true,
            'file_upload' => true,
            'file_delete' => true,
            'max_file_size' => 5368709120, // 5GB
        ],
    ],
    // ... other providers
],
```

### Database Configuration

Provider-specific settings can be stored in the `cloud_storage_settings` table:

```php
CloudStorageSetting::set('google-drive', 'root_folder_id', 'folder_id');
CloudStorageSetting::set('amazon-s3', 'storage_class', 'STANDARD_IA');
```

## Provider Interface

All providers must implement the `CloudStorageProviderInterface`:

```php
interface CloudStorageProviderInterface
{
    // Core operations
    public function uploadFile(User $user, string $localPath, string $targetPath, array $metadata = []): string;
    public function deleteFile(User $user, string $fileId): bool;
    public function getConnectionHealth(User $user): CloudStorageHealthStatus;
    
    // Authentication
    public function handleAuthCallback(User $user, string $code): void;
    public function getAuthUrl(User $user): string;
    public function disconnect(User $user): void;
    public function hasValidConnection(User $user): bool;
    
    // Provider information
    public function getProviderName(): string;
    public function getCapabilities(): array;
    public function getAuthenticationType(): string;
    public function getStorageModel(): string;
    
    // Configuration and lifecycle
    public function validateConfiguration(array $config): array;
    public function initialize(array $config): void;
    public function cleanup(): void;
    
    // Feature detection
    public function supportsFeature(string $feature): bool;
    public function getMaxFileSize(): int;
    public function getSupportedFileTypes(): array;
}
```

## Error Handling

### Error Classification

The system uses a comprehensive error classification system:

```php
enum CloudStorageErrorType: string
{
    // Authentication errors
    case AUTHENTICATION_FAILED = 'authentication_failed';
    case TOKEN_EXPIRED = 'token_expired';
    case INVALID_CREDENTIALS = 'invalid_credentials';
    
    // File operation errors
    case FILE_NOT_FOUND = 'file_not_found';
    case UPLOAD_FAILED = 'upload_failed';
    case DELETE_FAILED = 'delete_failed';
    
    // Provider-specific errors
    case BUCKET_NOT_FOUND = 'bucket_not_found';
    case QUOTA_EXCEEDED = 'quota_exceeded';
    
    // Network and system errors
    case NETWORK_ERROR = 'network_error';
    case RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';
}
```

### Error Handlers

Each provider has a dedicated error handler:

```php
class GoogleDriveErrorHandler implements CloudStorageErrorHandlerInterface
{
    public function classifyError(Exception $exception): CloudStorageErrorType
    {
        if ($exception instanceof Google_Service_Exception) {
            return match ($exception->getCode()) {
                401 => CloudStorageErrorType::AUTHENTICATION_FAILED,
                404 => CloudStorageErrorType::FILE_NOT_FOUND,
                403 => CloudStorageErrorType::QUOTA_EXCEEDED,
                default => CloudStorageErrorType::UNKNOWN_ERROR,
            };
        }
        
        return CloudStorageErrorType::UNKNOWN_ERROR;
    }
}
```

## Feature Detection

### Capability System

Providers report their capabilities through the `getCapabilities()` method:

```php
public function getCapabilities(): array
{
    return [
        'folder_creation' => true,
        'file_upload' => true,
        'file_delete' => true,
        'folder_delete' => true,
        'presigned_urls' => false,
        'storage_classes' => false,
    ];
}
```

### Feature Detection Service

Use the `CloudStorageFeatureDetectionService` to check capabilities:

```php
$featureService = app(CloudStorageFeatureDetectionService::class);

// Check if provider supports a feature
$supportsFeature = $featureService->supportsFeature($provider, 'folder_creation');

// Get all capabilities
$capabilities = $featureService->getProviderCapabilities($provider);

// Check feature across all providers
$providers = $featureService->getProvidersWithFeature('presigned_urls');
```

### Graceful Degradation

The system automatically handles unsupported features:

```php
$degradationService = app(CloudStorageGracefulDegradationService::class);

// Attempt operation with fallback
$result = $degradationService->attemptWithFallback(
    $provider,
    'createFolder',
    [$folderName],
    function() {
        // Fallback: create folder structure in file path
        return $this->createVirtualFolder($folderName);
    }
);
```

## Health Monitoring

### Health Checks

Monitor provider health using the health check system:

```php
// Check single provider
php artisan cloud-storage:health-check google-drive

// Check all providers
php artisan cloud-storage:health-check

// Comprehensive health check
php artisan cloud-storage:comprehensive-health-check
```

### Health Status

Provider health is tracked in the `cloud_storage_health_statuses` table:

```php
$healthService = app(CloudStorageHealthService::class);

// Get provider health
$health = $healthService->getProviderHealth($user, 'google-drive');

// Update health status
$healthService->updateProviderHealth($user, 'google-drive', $status);
```

## Performance Monitoring

### Metrics Collection

The system collects performance metrics for all operations:

```php
$metricsService = app(CloudStoragePerformanceMetricsService::class);

// Record operation metrics
$metricsService->recordOperation('upload', $provider, $duration, $success);

// Get performance statistics
$stats = $metricsService->getProviderStatistics('google-drive', '24h');
```

### Monitoring Dashboard

Access the monitoring dashboard at `/admin/cloud-storage/monitoring`:

- Provider performance metrics
- Error rates and trends
- Health status overview
- Operation statistics

## Logging and Auditing

### Structured Logging

All provider operations are logged with structured data:

```php
$logService = app(CloudStorageLogService::class);

// Log operation
$logService->logOperation($provider, 'upload', $user, [
    'file_size' => $fileSize,
    'duration' => $duration,
    'success' => true,
]);
```

### Audit Trail

Provider configuration changes and operations are audited:

```php
$auditService = app(CloudStorageAuditService::class);

// Log configuration change
$auditService->logConfigurationChange($user, 'google-drive', $oldConfig, $newConfig);

// Log provider switch
$auditService->logProviderSwitch($user, $oldProvider, $newProvider);
```

## Testing

### Provider Testing

Use the base test classes for consistent provider testing:

```php
class MyProviderTest extends CloudStorageProviderTestCase
{
    protected function getProviderName(): string
    {
        return 'my-provider';
    }
    
    protected function createProvider(): CloudStorageProviderInterface
    {
        return new MyProvider(/* dependencies */);
    }
}
```

### Integration Testing

Test provider integration using the integration test base:

```php
class MyProviderIntegrationTest extends CloudStorageProviderIntegrationTestCase
{
    protected function getProviderName(): string
    {
        return 'my-provider';
    }
    
    protected function getTestConfig(): array
    {
        return [
            'api_key' => 'test_key',
            'secret' => 'test_secret',
        ];
    }
}
```

## Commands

### Configuration Management

```bash
# Validate all provider configurations
php artisan cloud-storage:validate-config

# Migrate configuration from environment to database
php artisan cloud-storage:migrate-config

# Test provider connections
php artisan cloud-storage:test-providers
```

### Health Monitoring

```bash
# Check provider health
php artisan cloud-storage:health-check [provider]

# Comprehensive health check
php artisan cloud-storage:comprehensive-health-check

# Monitor providers (continuous)
php artisan cloud-storage:monitor-providers
```

### Maintenance

```bash
# Fix health status inconsistencies
php artisan cloud-storage:fix-health-status

# Clean up old metrics
php artisan cloud-storage:cleanup-metrics

# Refresh provider tokens
php artisan cloud-storage:refresh-tokens
```

## Security Considerations

### Configuration Security
- Sensitive configuration values are encrypted in the database
- Environment variables are validated and sanitized
- Provider credentials are stored securely with proper access controls

### Provider Security
- All provider implementations are validated for security compliance
- Authentication flows use secure protocols (OAuth 2.0, API keys with proper scoping)
- Provider access is logged and audited

### Access Control
- Provider-level access controls ensure users can only access authorized providers
- User permissions are validated for all provider operations
- Configuration changes require appropriate administrative privileges

## Best Practices

### Provider Implementation
1. Always implement the full `CloudStorageProviderInterface`
2. Use proper error handling and classification
3. Implement capability detection accurately
4. Follow the authentication patterns for your provider type
5. Include comprehensive logging for all operations

### Configuration Management
1. Use environment variables for sensitive data
2. Store provider-specific settings in the database
3. Validate all configuration before use
4. Implement proper fallback mechanisms

### Error Handling
1. Use the standardized error classification system
2. Provide meaningful error messages to users
3. Implement proper retry logic for transient errors
4. Log all errors with sufficient context for debugging

### Performance
1. Cache provider instances when possible
2. Use connection pooling for providers that support it
3. Implement proper timeout handling
4. Monitor and optimize slow operations

This documentation provides a comprehensive guide to using and extending the Cloud Storage Provider System. For specific implementation details, refer to the migration guides and API documentation.