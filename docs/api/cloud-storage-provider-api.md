# Cloud Storage Provider API Documentation

## Overview

This document provides comprehensive API documentation for all interfaces, services, and classes in the Cloud Storage Provider System. It includes method signatures, parameters, return types, exceptions, and usage examples.

## Core Interfaces

### CloudStorageProviderInterface

The main interface that all cloud storage providers must implement.

```php
interface CloudStorageProviderInterface
{
    // Core file operations
    public function uploadFile(User $user, string $localPath, string $targetPath, array $metadata = []): string;
    public function deleteFile(User $user, string $fileId): bool;
    
    // Authentication and connection management
    public function getAuthUrl(User $user): string;
    public function handleAuthCallback(User $user, string $code): void;
    public function hasValidConnection(User $user): bool;
    public function disconnect(User $user): void;
    
    // Health and status
    public function getConnectionHealth(User $user): CloudStorageHealthStatus;
    
    // Provider information
    public function getProviderName(): string;
    public function getAuthenticationType(): string;
    public function getStorageModel(): string;
    public function getCapabilities(): array;
    public function getMaxFileSize(): int;
    public function getSupportedFileTypes(): array;
    
    // Feature detection
    public function supportsFeature(string $feature): bool;
    
    // Configuration and lifecycle
    public function validateConfiguration(array $config): array;
    public function initialize(array $config): void;
    public function cleanup(): void;
}
```

#### Method Details

##### uploadFile()

Uploads a file to the cloud storage provider.

**Signature:**
```php
public function uploadFile(User $user, string $localPath, string $targetPath, array $metadata = []): string
```

**Parameters:**
- `$user` (User): The user performing the upload
- `$localPath` (string): Full path to the local file to upload
- `$targetPath` (string): Target path/name in cloud storage
- `$metadata` (array): Optional metadata to attach to the file

**Returns:**
- `string`: Unique file identifier from the cloud provider

**Throws:**
- `CloudStorageException`: If upload fails
- `FileNotFoundException`: If local file doesn't exist
- `QuotaExceededException`: If storage quota is exceeded

**Example:**
```php
$provider = app(CloudStorageManager::class)->getProvider('google-drive');
$fileId = $provider->uploadFile(
    $user,
    '/tmp/document.pdf',
    'documents/client-files/document.pdf',
    ['client_id' => 123, 'category' => 'legal']
);
```

##### deleteFile()

Deletes a file from cloud storage.

**Signature:**
```php
public function deleteFile(User $user, string $fileId): bool
```

**Parameters:**
- `$user` (User): The user performing the deletion
- `$fileId` (string): Unique file identifier from the cloud provider

**Returns:**
- `bool`: True if deletion was successful

**Throws:**
- `CloudStorageException`: If deletion fails
- `FileNotFoundException`: If file doesn't exist (may return true)

**Example:**
```php
$success = $provider->deleteFile($user, 'file_id_from_provider');
```

##### getAuthUrl()

Gets the authentication URL for OAuth providers.

**Signature:**
```php
public function getAuthUrl(User $user): string
```

**Parameters:**
- `$user` (User): The user to authenticate

**Returns:**
- `string`: OAuth authorization URL

**Throws:**
- `CloudStorageException`: If provider is not properly configured

**Example:**
```php
$authUrl = $provider->getAuthUrl($user);
return redirect($authUrl);
```

##### handleAuthCallback()

Handles the OAuth callback and stores authentication tokens.

**Signature:**
```php
public function handleAuthCallback(User $user, string $code): void
```

**Parameters:**
- `$user` (User): The user being authenticated
- `$code` (string): Authorization code from OAuth callback

**Throws:**
- `CloudStorageException`: If authentication fails

**Example:**
```php
$provider->handleAuthCallback($user, $request->get('code'));
```

##### getConnectionHealth()

Gets the current health status of the provider connection.

**Signature:**
```php
public function getConnectionHealth(User $user): CloudStorageHealthStatus
```

**Parameters:**
- `$user` (User): The user to check connection for

**Returns:**
- `CloudStorageHealthStatus`: Health status object

**Example:**
```php
$health = $provider->getConnectionHealth($user);
echo $health->status; // 'healthy', 'authentication_required', etc.
```

##### getCapabilities()

Returns the capabilities supported by this provider.

**Signature:**
```php
public function getCapabilities(): array
```

**Returns:**
- `array`: Associative array of capabilities and their support status

**Example:**
```php
$capabilities = $provider->getCapabilities();
// Returns:
[
    'folder_creation' => true,
    'file_upload' => true,
    'file_delete' => true,
    'folder_delete' => true,
    'presigned_urls' => false,
    'storage_classes' => ['STANDARD', 'IA'],
    'max_file_size' => 5368709120, // 5GB
    'supported_file_types' => ['*'],
]
```

##### supportsFeature()

Checks if the provider supports a specific feature.

**Signature:**
```php
public function supportsFeature(string $feature): bool
```

**Parameters:**
- `$feature` (string): Feature name to check

**Returns:**
- `bool`: True if feature is supported

**Example:**
```php
if ($provider->supportsFeature('folder_creation')) {
    $folderId = $provider->createFolder($user, 'New Folder');
}
```

##### validateConfiguration()

Validates provider configuration and returns any errors.

**Signature:**
```php
public function validateConfiguration(array $config): array
```

**Parameters:**
- `$config` (array): Configuration array to validate

**Returns:**
- `array`: Array of validation error messages (empty if valid)

**Example:**
```php
$errors = $provider->validateConfiguration([
    'client_id' => 'invalid_id',
    'client_secret' => '',
]);
// Returns: ['Client secret is required']
```

### CloudStorageErrorHandlerInterface

Interface for provider-specific error handling.

```php
interface CloudStorageErrorHandlerInterface
{
    public function classifyError(Exception $exception): CloudStorageErrorType;
    public function shouldRetry(CloudStorageErrorType $errorType, int $attemptNumber): bool;
    public function getRetryDelay(CloudStorageErrorType $errorType, int $attemptNumber): int;
    public function getUserMessage(CloudStorageErrorType $errorType): string;
}
```

#### Method Details

##### classifyError()

Classifies an exception into a standardized error type.

**Signature:**
```php
public function classifyError(Exception $exception): CloudStorageErrorType
```

**Parameters:**
- `$exception` (Exception): The exception to classify

**Returns:**
- `CloudStorageErrorType`: Standardized error type enum

**Example:**
```php
$errorType = $errorHandler->classifyError($exception);
// Returns: CloudStorageErrorType::AUTHENTICATION_FAILED
```

## Core Services

### CloudStorageManager

Central service for managing cloud storage providers.

```php
class CloudStorageManager
{
    public function getProvider(string $providerName = null, User $user = null): CloudStorageProviderInterface;
    public function getDefaultProvider(): CloudStorageProviderInterface;
    public function getUserProvider(User $user): CloudStorageProviderInterface;
    public function getAllProviders(): array;
    public function getAvailableProviders(): array;
    public function validateAllProviders(): array;
    public function getProviderCapabilities(string $providerName): array;
    public function switchUserProvider(User $user, string $providerName): void;
}
```

#### Method Details

##### getProvider()

Gets a provider instance by name or for a specific user.

**Signature:**
```php
public function getProvider(string $providerName = null, User $user = null): CloudStorageProviderInterface
```

**Parameters:**
- `$providerName` (string|null): Provider name (uses default if null)
- `$user` (User|null): User context for provider selection

**Returns:**
- `CloudStorageProviderInterface`: Provider instance

**Throws:**
- `ProviderNotFoundException`: If provider doesn't exist
- `ProviderNotConfiguredException`: If provider isn't configured

**Example:**
```php
$manager = app(CloudStorageManager::class);

// Get default provider
$provider = $manager->getProvider();

// Get specific provider
$provider = $manager->getProvider('amazon-s3');

// Get user's preferred provider
$provider = $manager->getProvider(null, $user);
```

##### getUserProvider()

Gets the preferred provider for a specific user.

**Signature:**
```php
public function getUserProvider(User $user): CloudStorageProviderInterface
```

**Parameters:**
- `$user` (User): User to get provider for

**Returns:**
- `CloudStorageProviderInterface`: User's preferred provider

**Example:**
```php
$provider = $manager->getUserProvider($user);
$fileId = $provider->uploadFile($user, $localPath, $targetPath);
```

##### switchUserProvider()

Changes a user's preferred provider.

**Signature:**
```php
public function switchUserProvider(User $user, string $providerName): void
```

**Parameters:**
- `$user` (User): User to update
- `$providerName` (string): New provider name

**Throws:**
- `ProviderNotFoundException`: If provider doesn't exist
- `ProviderNotConfiguredException`: If provider isn't configured

**Example:**
```php
$manager->switchUserProvider($user, 'amazon-s3');
```

### CloudStorageFactory

Factory service for creating and registering providers.

```php
class CloudStorageFactory
{
    public function create(string $providerName, array $config = []): CloudStorageProviderInterface;
    public function createForUser(User $user, string $providerName = null): CloudStorageProviderInterface;
    public function register(string $name, string $className): void;
    public function getRegisteredProviders(): array;
    public function validateProvider(string $className): bool;
    public function discoverProviders(): array;
}
```

#### Method Details

##### create()

Creates a provider instance.

**Signature:**
```php
public function create(string $providerName, array $config = []): CloudStorageProviderInterface
```

**Parameters:**
- `$providerName` (string): Provider name to create
- `$config` (array): Optional configuration override

**Returns:**
- `CloudStorageProviderInterface`: Provider instance

**Example:**
```php
$factory = app(CloudStorageFactory::class);
$provider = $factory->create('google-drive');
```

##### register()

Registers a new provider class.

**Signature:**
```php
public function register(string $name, string $className): void
```

**Parameters:**
- `$name` (string): Provider name
- `$className` (string): Provider class name

**Throws:**
- `InvalidProviderException`: If class doesn't implement interface

**Example:**
```php
$factory->register('dropbox', DropboxProvider::class);
```

### CloudConfigurationService

Service for managing provider configurations.

```php
class CloudConfigurationService
{
    public function getProviderConfig(string $providerName): array;
    public function validateProviderConfig(string $providerName, array $config): array;
    public function getEffectiveConfig(string $providerName): array;
    public function setProviderConfig(string $providerName, array $config): void;
    public function getConfigSource(string $providerName, string $key): string;
    public function getAllProviderConfigs(): array;
    public function isProviderConfigured(string $providerName): bool;
}
```

#### Method Details

##### getProviderConfig()

Gets configuration for a specific provider.

**Signature:**
```php
public function getProviderConfig(string $providerName): array
```

**Parameters:**
- `$providerName` (string): Provider name

**Returns:**
- `array`: Provider configuration

**Throws:**
- `ProviderNotFoundException`: If provider doesn't exist

**Example:**
```php
$configService = app(CloudConfigurationService::class);
$config = $configService->getProviderConfig('google-drive');
```

##### validateProviderConfig()

Validates provider configuration.

**Signature:**
```php
public function validateProviderConfig(string $providerName, array $config): array
```

**Parameters:**
- `$providerName` (string): Provider name
- `$config` (array): Configuration to validate

**Returns:**
- `array`: Array of validation errors (empty if valid)

**Example:**
```php
$errors = $configService->validateProviderConfig('amazon-s3', $config);
if (!empty($errors)) {
    throw new ConfigurationException(implode(', ', $errors));
}
```

## Feature Detection Services

### CloudStorageFeatureDetectionService

Service for detecting provider capabilities.

```php
class CloudStorageFeatureDetectionService
{
    public function supportsFeature(CloudStorageProviderInterface $provider, string $feature): bool;
    public function getProviderCapabilities(CloudStorageProviderInterface $provider): array;
    public function getProvidersWithFeature(string $feature): array;
    public function getFeatureCompatibilityMatrix(): array;
    public function validateFeatureRequirements(array $requiredFeatures): array;
}
```

#### Method Details

##### supportsFeature()

Checks if a provider supports a specific feature.

**Signature:**
```php
public function supportsFeature(CloudStorageProviderInterface $provider, string $feature): bool
```

**Parameters:**
- `$provider` (CloudStorageProviderInterface): Provider to check
- `$feature` (string): Feature name

**Returns:**
- `bool`: True if feature is supported

**Example:**
```php
$featureService = app(CloudStorageFeatureDetectionService::class);
$supportsSharing = $featureService->supportsFeature($provider, 'file_sharing');
```

##### getProvidersWithFeature()

Gets all providers that support a specific feature.

**Signature:**
```php
public function getProvidersWithFeature(string $feature): array
```

**Parameters:**
- `$feature` (string): Feature name

**Returns:**
- `array`: Array of provider names that support the feature

**Example:**
```php
$providers = $featureService->getProvidersWithFeature('presigned_urls');
// Returns: ['amazon-s3', 'azure-blob']
```

### CloudStorageGracefulDegradationService

Service for handling unsupported features gracefully.

```php
class CloudStorageGracefulDegradationService
{
    public function attemptWithFallback(CloudStorageProviderInterface $provider, string $method, array $args, callable $fallback = null): mixed;
    public function getAlternativeImplementation(string $feature, CloudStorageProviderInterface $provider): ?callable;
    public function canDegrade(string $feature): bool;
}
```

#### Method Details

##### attemptWithFallback()

Attempts an operation with fallback if unsupported.

**Signature:**
```php
public function attemptWithFallback(CloudStorageProviderInterface $provider, string $method, array $args, callable $fallback = null): mixed
```

**Parameters:**
- `$provider` (CloudStorageProviderInterface): Provider to use
- `$method` (string): Method name to call
- `$args` (array): Method arguments
- `$fallback` (callable|null): Fallback function if method fails

**Returns:**
- `mixed`: Result of operation or fallback

**Example:**
```php
$degradationService = app(CloudStorageGracefulDegradationService::class);

$result = $degradationService->attemptWithFallback(
    $provider,
    'createFolder',
    [$folderName],
    function() use ($folderName) {
        // Fallback: create virtual folder structure
        return $this->createVirtualFolder($folderName);
    }
);
```

## Health and Monitoring Services

### CloudStorageHealthService

Service for monitoring provider health.

```php
class CloudStorageHealthService
{
    public function getProviderHealth(User $user, string $providerName): CloudStorageHealthStatus;
    public function updateProviderHealth(User $user, string $providerName, string $status): void;
    public function getAllProviderHealth(User $user): array;
    public function determineConsolidatedStatus(User $user, string $providerName): string;
    public function scheduleHealthCheck(User $user, string $providerName): void;
}
```

### CloudStoragePerformanceMetricsService

Service for collecting performance metrics.

```php
class CloudStoragePerformanceMetricsService
{
    public function recordOperation(string $operation, CloudStorageProviderInterface $provider, float $duration, bool $success, array $metadata = []): void;
    public function getProviderStatistics(string $providerName, string $timeframe = '24h'): array;
    public function getOperationMetrics(string $operation, string $timeframe = '24h'): array;
    public function getErrorRates(string $providerName, string $timeframe = '24h'): array;
    public function getPerformanceTrends(string $providerName, string $timeframe = '7d'): array;
}
```

### CloudStorageAuditService

Service for auditing provider operations.

```php
class CloudStorageAuditService
{
    public function logOperation(CloudStorageProviderInterface $provider, string $operation, User $user, array $details): void;
    public function logConfigurationChange(User $user, string $providerName, array $oldConfig, array $newConfig): void;
    public function logProviderSwitch(User $user, string $oldProvider, string $newProvider): void;
    public function getAuditLog(array $filters = []): Collection;
    public function exportAuditLog(array $filters = [], string $format = 'csv'): string;
}
```

## Error Handling

### CloudStorageErrorType Enum

Standardized error types for all providers.

```php
enum CloudStorageErrorType: string
{
    // Authentication errors
    case AUTHENTICATION_FAILED = 'authentication_failed';
    case TOKEN_EXPIRED = 'token_expired';
    case INVALID_CREDENTIALS = 'invalid_credentials';
    case ACCESS_DENIED = 'access_denied';
    
    // File operation errors
    case FILE_NOT_FOUND = 'file_not_found';
    case FILE_ALREADY_EXISTS = 'file_already_exists';
    case FILE_TOO_LARGE = 'file_too_large';
    case INVALID_FILE_TYPE = 'invalid_file_type';
    case UPLOAD_FAILED = 'upload_failed';
    case DELETE_FAILED = 'delete_failed';
    
    // Storage errors
    case QUOTA_EXCEEDED = 'quota_exceeded';
    case INSUFFICIENT_STORAGE = 'insufficient_storage';
    
    // Network and system errors
    case NETWORK_ERROR = 'network_error';
    case TIMEOUT_ERROR = 'timeout_error';
    case RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';
    case TEMPORARY_ERROR = 'temporary_error';
    
    // Provider-specific errors
    case BUCKET_NOT_FOUND = 'bucket_not_found';
    case INVALID_BUCKET_NAME = 'invalid_bucket_name';
    case BUCKET_ACCESS_DENIED = 'bucket_access_denied';
    case INVALID_REGION = 'invalid_region';
    case CONTAINER_NOT_FOUND = 'container_not_found';
    case INVALID_CONNECTION_STRING = 'invalid_connection_string';
    
    // Configuration errors
    case PROVIDER_NOT_CONFIGURED = 'provider_not_configured';
    case PROVIDER_INITIALIZATION_FAILED = 'provider_initialization_failed';
    case FEATURE_NOT_SUPPORTED = 'feature_not_supported';
    
    // Generic errors
    case UNKNOWN_ERROR = 'unknown_error';
}
```

### Exception Classes

#### CloudStorageException

Base exception for all cloud storage operations.

```php
class CloudStorageException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?CloudStorageErrorType $errorType = null,
        public readonly array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }
    
    public function getErrorType(): ?CloudStorageErrorType;
    public function getContext(): array;
}
```

## Models

### CloudStorageSetting

Model for storing provider-specific configuration.

```php
class CloudStorageSetting extends Model
{
    // Static methods for configuration management
    public static function get(string $provider, string $key, mixed $default = null): mixed;
    public static function set(string $provider, string $key, mixed $value): void;
    public static function forget(string $provider, string $key): void;
    public static function getAll(string $provider): array;
    
    // Schema and validation methods
    public static function getProviderSchema(string $provider): array;
    public static function validateProviderConfig(string $provider, array $config): array;
    public static function getRequiredKeys(string $provider): array;
    public static function getOptionalKeys(string $provider): array;
    public static function getEncryptedKeys(string $provider): array;
}
```

### CloudStorageHealthStatus

Model for tracking provider health status.

```php
class CloudStorageHealthStatus extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'status',
        'consolidated_status',
        'last_checked_at',
        'error_message',
        'error_count',
        'last_error_at',
        'consecutive_failures',
        'last_success_at',
    ];
    
    protected $casts = [
        'last_checked_at' => 'datetime',
        'last_error_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];
    
    // Relationships
    public function user(): BelongsTo;
    
    // Status methods
    public function isHealthy(): bool;
    public function requiresAuthentication(): bool;
    public function hasConnectionIssues(): bool;
    public function isNotConnected(): bool;
}
```

## Console Commands

### Configuration Commands

#### ValidateCloudStorageConfiguration

Validates provider configurations.

```bash
php artisan cloud-storage:validate-config [provider]
```

**Options:**
- `provider` (optional): Specific provider to validate

#### MigrateCloudStorageConfig

Migrates configuration from environment to database.

```bash
php artisan cloud-storage:migrate-config [--dry-run] [--force]
```

**Options:**
- `--dry-run`: Show what would be migrated without making changes
- `--force`: Overwrite existing database configuration

#### TestCloudStorageProviders

Tests provider connections and functionality.

```bash
php artisan cloud-storage:test-providers [provider] [--verbose]
```

**Options:**
- `provider` (optional): Specific provider to test
- `--verbose`: Show detailed test output

### Health Monitoring Commands

#### ComprehensiveCloudStorageHealthCheck

Performs comprehensive health checks on all providers.

```bash
php artisan cloud-storage:comprehensive-health-check [--fix] [--notify]
```

**Options:**
- `--fix`: Attempt to fix issues automatically
- `--notify`: Send notifications for critical issues

#### MonitorCloudStorageProviders

Continuously monitors provider performance and health.

```bash
php artisan cloud-storage:monitor-providers [--interval=60] [--providers=all]
```

**Options:**
- `--interval`: Monitoring interval in seconds (default: 60)
- `--providers`: Comma-separated list of providers to monitor

#### FixCloudStorageHealthStatus

Fixes inconsistent health status data.

```bash
php artisan cloud-storage:fix-health-status [--user-id=] [--provider=]
```

**Options:**
- `--user-id`: Fix status for specific user
- `--provider`: Fix status for specific provider

## Usage Examples

### Basic File Upload

```php
// Get the cloud storage manager
$manager = app(CloudStorageManager::class);

// Get user's preferred provider
$provider = $manager->getUserProvider($user);

// Upload a file
try {
    $fileId = $provider->uploadFile(
        $user,
        $uploadedFile->getPathname(),
        'documents/' . $uploadedFile->getClientOriginalName(),
        ['category' => 'client-documents']
    );
    
    // Store file information in database
    FileUpload::create([
        'user_id' => $user->id,
        'filename' => $uploadedFile->getClientOriginalName(),
        'storage_provider' => $provider->getProviderName(),
        'provider_file_id' => $fileId,
        'file_size' => $uploadedFile->getSize(),
    ]);
    
} catch (CloudStorageException $e) {
    Log::error('File upload failed', [
        'user_id' => $user->id,
        'provider' => $provider->getProviderName(),
        'error' => $e->getMessage(),
        'error_type' => $e->getErrorType()?->value,
    ]);
    
    throw $e;
}
```

### Provider Health Check

```php
// Check health of all providers for a user
$healthService = app(CloudStorageHealthService::class);
$manager = app(CloudStorageManager::class);

$healthStatuses = [];
foreach ($manager->getAvailableProviders() as $providerName) {
    $health = $healthService->getProviderHealth($user, $providerName);
    $healthStatuses[$providerName] = [
        'status' => $health->status,
        'last_checked' => $health->last_checked_at,
        'error_message' => $health->error_message,
    ];
}

return response()->json($healthStatuses);
```

### Feature Detection

```php
// Check if provider supports a feature before using it
$featureService = app(CloudStorageFeatureDetectionService::class);
$provider = $manager->getUserProvider($user);

if ($featureService->supportsFeature($provider, 'folder_creation')) {
    // Create folder structure
    $folderId = $provider->createFolder($user, 'Client Documents');
    $fileId = $provider->uploadFile($user, $localPath, $targetPath, [
        'parent_folder_id' => $folderId,
    ]);
} else {
    // Use flat file structure with path prefixes
    $fileId = $provider->uploadFile($user, $localPath, 'client-documents/' . $filename);
}
```

### Error Handling with Retry Logic

```php
$errorHandler = app(CloudStorageErrorHandlerFactory::class)
    ->create($provider->getProviderName());

$maxAttempts = 3;
$attempt = 1;

while ($attempt <= $maxAttempts) {
    try {
        $fileId = $provider->uploadFile($user, $localPath, $targetPath);
        break; // Success, exit loop
        
    } catch (CloudStorageException $e) {
        $errorType = $errorHandler->classifyError($e);
        
        if ($errorHandler->shouldRetry($errorType, $attempt) && $attempt < $maxAttempts) {
            $delay = $errorHandler->getRetryDelay($errorType, $attempt);
            sleep($delay);
            $attempt++;
        } else {
            // Final attempt failed or error is not retryable
            $userMessage = $errorHandler->getUserMessage($errorType);
            throw new CloudStorageException($userMessage, 0, $e, $errorType);
        }
    }
}
```

This API documentation provides comprehensive coverage of all interfaces, services, and classes in the Cloud Storage Provider System. Use it as a reference when implementing providers, integrating with the system, or troubleshooting issues.