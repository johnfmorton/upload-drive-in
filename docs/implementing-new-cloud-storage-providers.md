# Implementing New Cloud Storage Providers

## Overview

This guide walks you through implementing a new cloud storage provider for the Cloud Storage Provider System. The system is designed to be extensible, allowing you to add support for any cloud storage service by implementing the required interfaces and following established patterns.

## Prerequisites

Before implementing a new provider, ensure you have:

- Understanding of the target cloud storage API
- API credentials for testing
- Knowledge of the authentication method (OAuth 2.0, API keys, etc.)
- Understanding of the storage model (hierarchical vs. flat)

## Implementation Steps

### Step 1: Create Provider Class

Create a new provider class that implements `CloudStorageProviderInterface`:

```php
<?php

namespace App\Services;

use App\Contracts\CloudStorageProviderInterface;
use App\Models\CloudStorageHealthStatus;
use App\Models\User;
use App\Enums\CloudStorageErrorType;
use Exception;

class DropboxProvider implements CloudStorageProviderInterface
{
    private array $config;
    private ?object $client = null;

    public function __construct(
        private DropboxErrorHandler $errorHandler,
        private CloudStorageLogService $logService
    ) {}

    public function getProviderName(): string
    {
        return 'dropbox';
    }

    public function getAuthenticationType(): string
    {
        return 'oauth'; // or 'api_key', 'service_account'
    }

    public function getStorageModel(): string
    {
        return 'hierarchical'; // or 'flat', 'hybrid'
    }

    public function getCapabilities(): array
    {
        return [
            'folder_creation' => true,
            'file_upload' => true,
            'file_delete' => true,
            'folder_delete' => true,
            'file_sharing' => true,
            'max_file_size' => 350 * 1024 * 1024 * 1024, // 350GB
            'supported_file_types' => ['*'], // All types
            'presigned_urls' => false,
            'storage_classes' => false,
        ];
    }

    public function supportsFeature(string $feature): bool
    {
        return $this->getCapabilities()[$feature] ?? false;
    }

    public function validateConfiguration(array $config): array
    {
        $errors = [];

        if (empty($config['app_key'])) {
            $errors[] = 'Dropbox App Key is required';
        }

        if (empty($config['app_secret'])) {
            $errors[] = 'Dropbox App Secret is required';
        }

        if (empty($config['redirect_uri'])) {
            $errors[] = 'Redirect URI is required';
        }

        return $errors;
    }

    public function initialize(array $config): void
    {
        $this->config = $config;
        
        // Initialize Dropbox client
        $this->client = new DropboxClient([
            'app_key' => $config['app_key'],
            'app_secret' => $config['app_secret'],
            'redirect_uri' => $config['redirect_uri'],
        ]);
    }

    public function getAuthUrl(User $user): string
    {
        if (!$this->client) {
            throw new CloudStorageException('Provider not initialized');
        }

        return $this->client->getAuthUrl([
            'state' => $user->id,
            'force_reapprove' => true,
        ]);
    }

    public function handleAuthCallback(User $user, string $code): void
    {
        try {
            $token = $this->client->getAccessToken($code);
            
            // Store token for user
            DropboxToken::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'access_token' => $token['access_token'],
                    'refresh_token' => $token['refresh_token'] ?? null,
                    'expires_at' => $token['expires_in'] ? 
                        now()->addSeconds($token['expires_in']) : null,
                ]
            );

            $this->logService->logOperation($this, 'auth_callback', $user, [
                'success' => true,
            ]);

        } catch (Exception $e) {
            $this->logService->logOperation($this, 'auth_callback', $user, [
                'success' => false,
                'error' => $e->getMessage(),
            ]);
            
            throw new CloudStorageException(
                'Failed to handle Dropbox authentication callback: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function hasValidConnection(User $user): bool
    {
        try {
            $token = DropboxToken::where('user_id', $user->id)->first();
            
            if (!$token) {
                return false;
            }

            // Check if token is expired and refresh if needed
            if ($token->expires_at && $token->expires_at->isPast()) {
                return $this->refreshToken($user);
            }

            // Test connection with a simple API call
            $this->client->setAccessToken($token->access_token);
            $this->client->getCurrentAccount();
            
            return true;

        } catch (Exception $e) {
            $this->logService->logOperation($this, 'connection_check', $user, [
                'success' => false,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    public function uploadFile(User $user, string $localPath, string $targetPath, array $metadata = []): string
    {
        $startTime = microtime(true);
        
        try {
            $token = $this->getValidToken($user);
            $this->client->setAccessToken($token);

            // Ensure target path starts with /
            $targetPath = '/' . ltrim($targetPath, '/');

            // Upload file
            $result = $this->client->uploadFile($localPath, $targetPath, [
                'mode' => 'overwrite',
                'autorename' => false,
            ]);

            $duration = microtime(true) - $startTime;
            
            $this->logService->logOperation($this, 'upload', $user, [
                'file_path' => $targetPath,
                'file_size' => filesize($localPath),
                'duration' => $duration,
                'success' => true,
            ]);

            return $result['id'];

        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;
            
            $this->logService->logOperation($this, 'upload', $user, [
                'file_path' => $targetPath,
                'duration' => $duration,
                'success' => false,
                'error' => $e->getMessage(),
            ]);

            throw new CloudStorageException(
                'Failed to upload file to Dropbox: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function deleteFile(User $user, string $fileId): bool
    {
        try {
            $token = $this->getValidToken($user);
            $this->client->setAccessToken($token);

            $this->client->deleteFile($fileId);

            $this->logService->logOperation($this, 'delete', $user, [
                'file_id' => $fileId,
                'success' => true,
            ]);

            return true;

        } catch (Exception $e) {
            $this->logService->logOperation($this, 'delete', $user, [
                'file_id' => $fileId,
                'success' => false,
                'error' => $e->getMessage(),
            ]);

            throw new CloudStorageException(
                'Failed to delete file from Dropbox: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function getConnectionHealth(User $user): CloudStorageHealthStatus
    {
        try {
            if (!$this->hasValidConnection($user)) {
                return new CloudStorageHealthStatus([
                    'user_id' => $user->id,
                    'provider' => $this->getProviderName(),
                    'status' => 'authentication_required',
                    'consolidated_status' => 'authentication_required',
                    'last_checked_at' => now(),
                    'error_message' => 'Authentication required',
                ]);
            }

            // Test API connectivity
            $token = $this->getValidToken($user);
            $this->client->setAccessToken($token);
            $account = $this->client->getCurrentAccount();

            return new CloudStorageHealthStatus([
                'user_id' => $user->id,
                'provider' => $this->getProviderName(),
                'status' => 'healthy',
                'consolidated_status' => 'healthy',
                'last_checked_at' => now(),
                'error_message' => null,
            ]);

        } catch (Exception $e) {
            $errorType = $this->errorHandler->classifyError($e);
            
            return new CloudStorageHealthStatus([
                'user_id' => $user->id,
                'provider' => $this->getProviderName(),
                'status' => $this->mapErrorTypeToStatus($errorType),
                'consolidated_status' => $this->mapErrorTypeToStatus($errorType),
                'last_checked_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function disconnect(User $user): void
    {
        try {
            // Revoke token if possible
            $token = DropboxToken::where('user_id', $user->id)->first();
            if ($token) {
                $this->client->setAccessToken($token->access_token);
                $this->client->revokeToken();
            }
        } catch (Exception $e) {
            // Log but don't fail - we'll delete the local token anyway
            $this->logService->logOperation($this, 'disconnect', $user, [
                'token_revoke_success' => false,
                'error' => $e->getMessage(),
            ]);
        }

        // Delete local token
        DropboxToken::where('user_id', $user->id)->delete();

        $this->logService->logOperation($this, 'disconnect', $user, [
            'success' => true,
        ]);
    }

    public function getMaxFileSize(): int
    {
        return $this->getCapabilities()['max_file_size'];
    }

    public function getSupportedFileTypes(): array
    {
        return $this->getCapabilities()['supported_file_types'];
    }

    public function cleanup(): void
    {
        // Clean up any resources, connections, etc.
        $this->client = null;
    }

    private function getValidToken(User $user): string
    {
        $token = DropboxToken::where('user_id', $user->id)->first();
        
        if (!$token) {
            throw new CloudStorageException('No Dropbox token found for user');
        }

        // Refresh token if expired
        if ($token->expires_at && $token->expires_at->isPast()) {
            if (!$this->refreshToken($user)) {
                throw new CloudStorageException('Failed to refresh Dropbox token');
            }
            $token->refresh();
        }

        return $token->access_token;
    }

    private function refreshToken(User $user): bool
    {
        try {
            $token = DropboxToken::where('user_id', $user->id)->first();
            
            if (!$token || !$token->refresh_token) {
                return false;
            }

            $newToken = $this->client->refreshAccessToken($token->refresh_token);
            
            $token->update([
                'access_token' => $newToken['access_token'],
                'refresh_token' => $newToken['refresh_token'] ?? $token->refresh_token,
                'expires_at' => $newToken['expires_in'] ? 
                    now()->addSeconds($newToken['expires_in']) : null,
            ]);

            return true;

        } catch (Exception $e) {
            $this->logService->logOperation($this, 'token_refresh', $user, [
                'success' => false,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    private function mapErrorTypeToStatus(CloudStorageErrorType $errorType): string
    {
        return match ($errorType) {
            CloudStorageErrorType::AUTHENTICATION_FAILED,
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::INVALID_CREDENTIALS => 'authentication_required',
            
            CloudStorageErrorType::NETWORK_ERROR,
            CloudStorageErrorType::RATE_LIMIT_EXCEEDED => 'connection_issues',
            
            default => 'not_connected',
        };
    }
}
```

### Step 2: Create Error Handler

Create a provider-specific error handler:

```php
<?php

namespace App\Services;

use App\Contracts\CloudStorageErrorHandlerInterface;
use App\Enums\CloudStorageErrorType;
use Exception;

class DropboxErrorHandler extends BaseCloudStorageErrorHandler implements CloudStorageErrorHandlerInterface
{
    public function classifyError(Exception $exception): CloudStorageErrorType
    {
        // Handle Dropbox-specific exceptions
        if ($exception instanceof DropboxException) {
            return match ($exception->getCode()) {
                401 => CloudStorageErrorType::AUTHENTICATION_FAILED,
                403 => CloudStorageErrorType::ACCESS_DENIED,
                404 => CloudStorageErrorType::FILE_NOT_FOUND,
                409 => CloudStorageErrorType::FILE_ALREADY_EXISTS,
                429 => CloudStorageErrorType::RATE_LIMIT_EXCEEDED,
                507 => CloudStorageErrorType::QUOTA_EXCEEDED,
                default => CloudStorageErrorType::UNKNOWN_ERROR,
            };
        }

        // Handle network and generic errors
        if ($exception instanceof NetworkException) {
            return CloudStorageErrorType::NETWORK_ERROR;
        }

        // Fall back to parent classification
        return parent::classifyError($exception);
    }

    public function getRetryDelay(CloudStorageErrorType $errorType, int $attemptNumber): int
    {
        return match ($errorType) {
            CloudStorageErrorType::RATE_LIMIT_EXCEEDED => min(300, 30 * pow(2, $attemptNumber)), // Max 5 minutes
            CloudStorageErrorType::NETWORK_ERROR => min(120, 5 * pow(2, $attemptNumber)), // Max 2 minutes
            default => parent::getRetryDelay($errorType, $attemptNumber),
        };
    }

    public function shouldRetry(CloudStorageErrorType $errorType, int $attemptNumber): bool
    {
        $maxRetries = match ($errorType) {
            CloudStorageErrorType::RATE_LIMIT_EXCEEDED => 5,
            CloudStorageErrorType::NETWORK_ERROR => 3,
            CloudStorageErrorType::TEMPORARY_ERROR => 3,
            default => 0,
        };

        return $attemptNumber < $maxRetries;
    }

    public function getUserMessage(CloudStorageErrorType $errorType): string
    {
        return match ($errorType) {
            CloudStorageErrorType::AUTHENTICATION_FAILED => 'Please reconnect your Dropbox account.',
            CloudStorageErrorType::QUOTA_EXCEEDED => 'Your Dropbox storage is full. Please free up space.',
            CloudStorageErrorType::FILE_TOO_LARGE => 'File is too large for Dropbox (max 350GB).',
            CloudStorageErrorType::RATE_LIMIT_EXCEEDED => 'Too many requests to Dropbox. Please try again later.',
            default => parent::getUserMessage($errorType),
        };
    }
}
```

### Step 3: Create Token Model

Create a model to store authentication tokens:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DropboxToken extends Model
{
    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
```

### Step 4: Create Migration

Create a database migration for the token table:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dropbox_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dropbox_tokens');
    }
};
```

### Step 5: Update Configuration

Add your provider to the configuration file:

```php
// config/cloud-storage.php

'providers' => [
    // ... existing providers

    'dropbox' => [
        'driver' => 'dropbox',
        'class' => DropboxProvider::class,
        'error_handler' => DropboxErrorHandler::class,
        'auth_type' => 'oauth',
        'storage_model' => 'hierarchical',
        'config' => [
            'app_key' => env('DROPBOX_APP_KEY'),
            'app_secret' => env('DROPBOX_APP_SECRET'),
            'redirect_uri' => config('app.url') . '/admin/cloud-storage/dropbox/callback',
        ],
        'features' => [
            'folder_creation' => true,
            'file_upload' => true,
            'file_delete' => true,
            'folder_delete' => true,
            'file_sharing' => true,
            'max_file_size' => 350 * 1024 * 1024 * 1024, // 350GB
            'supported_file_types' => ['*'],
            'presigned_urls' => false,
            'storage_classes' => false,
        ],
    ],
],
```

### Step 6: Register Provider

The provider will be automatically registered by the `CloudStorageServiceProvider`. If you need custom registration logic, you can override it:

```php
// app/Providers/CloudStorageServiceProvider.php

protected function registerProviders(): void
{
    parent::registerProviders();

    // Custom registration if needed
    $factory = $this->app->make(CloudStorageFactory::class);
    $factory->register('dropbox', DropboxProvider::class);
}
```

### Step 7: Create Tests

Create comprehensive tests for your provider:

```php
<?php

namespace Tests\Unit\Services;

use App\Services\DropboxProvider;
use Tests\Unit\Contracts\CloudStorageProviderTestCase;

class DropboxProviderTest extends CloudStorageProviderTestCase
{
    protected function getProviderName(): string
    {
        return 'dropbox';
    }

    protected function createProvider(): CloudStorageProviderInterface
    {
        return new DropboxProvider(
            $this->mock(DropboxErrorHandler::class),
            $this->mock(CloudStorageLogService::class)
        );
    }

    public function test_supports_oauth_authentication(): void
    {
        $provider = $this->createProvider();
        $this->assertEquals('oauth', $provider->getAuthenticationType());
    }

    public function test_supports_hierarchical_storage(): void
    {
        $provider = $this->createProvider();
        $this->assertEquals('hierarchical', $provider->getStorageModel());
    }

    public function test_reports_correct_capabilities(): void
    {
        $provider = $this->createProvider();
        $capabilities = $provider->getCapabilities();

        $this->assertTrue($capabilities['folder_creation']);
        $this->assertTrue($capabilities['file_upload']);
        $this->assertTrue($capabilities['file_delete']);
        $this->assertFalse($capabilities['presigned_urls']);
    }

    // Add more provider-specific tests
}
```

### Step 8: Create Integration Tests

Create integration tests that test against the real API:

```php
<?php

namespace Tests\Integration;

use App\Services\DropboxProvider;
use Tests\Integration\CloudStorageProviderIntegrationTestCase;

class DropboxProviderIntegrationTest extends CloudStorageProviderIntegrationTestCase
{
    protected function getProviderName(): string
    {
        return 'dropbox';
    }

    protected function getTestConfig(): array
    {
        return [
            'app_key' => env('DROPBOX_TEST_APP_KEY'),
            'app_secret' => env('DROPBOX_TEST_APP_SECRET'),
            'redirect_uri' => 'http://localhost/callback',
        ];
    }

    protected function skipIfNotConfigured(): void
    {
        if (!env('DROPBOX_TEST_APP_KEY')) {
            $this->markTestSkipped('Dropbox test credentials not configured');
        }
    }
}
```

## Provider Implementation Patterns

### Authentication Types

#### OAuth 2.0 Providers
For providers using OAuth 2.0 (Google Drive, Dropbox, OneDrive):

```php
public function getAuthenticationType(): string
{
    return 'oauth';
}

public function getAuthUrl(User $user): string
{
    return $this->client->getAuthUrl([
        'state' => $user->id,
        'scope' => 'files.read files.write',
    ]);
}
```

#### API Key Providers
For providers using API keys (Amazon S3, some others):

```php
public function getAuthenticationType(): string
{
    return 'api_key';
}

public function initialize(array $config): void
{
    $this->client = new S3Client([
        'credentials' => [
            'key' => $config['access_key_id'],
            'secret' => $config['secret_access_key'],
        ],
        'region' => $config['region'],
    ]);
}
```

#### Service Account Providers
For providers using service accounts (Google Cloud Storage):

```php
public function getAuthenticationType(): string
{
    return 'service_account';
}

public function initialize(array $config): void
{
    $this->client = new StorageClient([
        'keyFilePath' => $config['service_account_path'],
        'projectId' => $config['project_id'],
    ]);
}
```

### Storage Models

#### Hierarchical Storage
For providers with folder support (Google Drive, Dropbox):

```php
public function getStorageModel(): string
{
    return 'hierarchical';
}

public function createFolder(User $user, string $folderName, string $parentId = null): string
{
    // Create actual folder
    return $this->client->createFolder($folderName, $parentId);
}
```

#### Flat Storage
For providers using key-based storage (Amazon S3, Azure Blob):

```php
public function getStorageModel(): string
{
    return 'flat';
}

public function uploadFile(User $user, string $localPath, string $targetPath, array $metadata = []): string
{
    // Use full path as key
    $key = ltrim($targetPath, '/');
    return $this->client->putObject($key, file_get_contents($localPath));
}
```

### Error Handling Patterns

#### Transient Errors
Handle temporary errors with retry logic:

```php
public function classifyError(Exception $exception): CloudStorageErrorType
{
    if ($exception instanceof NetworkException) {
        return CloudStorageErrorType::NETWORK_ERROR;
    }

    if ($exception->getCode() === 503) {
        return CloudStorageErrorType::TEMPORARY_ERROR;
    }

    return CloudStorageErrorType::UNKNOWN_ERROR;
}
```

#### Rate Limiting
Handle rate limits with exponential backoff:

```php
public function getRetryDelay(CloudStorageErrorType $errorType, int $attemptNumber): int
{
    if ($errorType === CloudStorageErrorType::RATE_LIMIT_EXCEEDED) {
        return min(300, 30 * pow(2, $attemptNumber)); // Max 5 minutes
    }

    return parent::getRetryDelay($errorType, $attemptNumber);
}
```

## Testing Your Provider

### Unit Tests
Run unit tests to verify interface compliance:

```bash
php artisan test --filter=DropboxProviderTest
```

### Integration Tests
Test against real API (requires test credentials):

```bash
php artisan test --filter=DropboxProviderIntegrationTest
```

### Provider Validation
Use the built-in validation command:

```bash
php artisan cloud-storage:test-providers dropbox
```

### Configuration Validation
Validate your provider configuration:

```bash
php artisan cloud-storage:validate-config dropbox
```

## Best Practices

### Security
1. **Never log sensitive data** (tokens, API keys)
2. **Encrypt tokens** in database storage
3. **Validate all inputs** from the cloud provider
4. **Use secure HTTP** for all API calls
5. **Implement proper token refresh** logic

### Performance
1. **Cache provider instances** when possible
2. **Use connection pooling** if supported
3. **Implement proper timeouts** for API calls
4. **Handle large files** efficiently (chunked uploads)
5. **Monitor API rate limits** and implement backoff

### Reliability
1. **Implement comprehensive error handling**
2. **Use structured logging** for debugging
3. **Test edge cases** thoroughly
4. **Handle network failures** gracefully
5. **Implement health checks** properly

### User Experience
1. **Provide clear error messages** to users
2. **Implement progress tracking** for uploads
3. **Handle authentication flows** smoothly
4. **Support provider-specific features** when possible
5. **Maintain backward compatibility** when updating

## Common Pitfalls

### Authentication Issues
- Not handling token refresh properly
- Storing tokens in plain text
- Not validating authentication state

### Error Handling
- Not classifying errors correctly
- Missing retry logic for transient errors
- Poor error messages for users

### Configuration
- Not validating configuration properly
- Hardcoding values instead of using config
- Missing required configuration parameters

### Testing
- Not testing against real APIs
- Missing edge case testing
- Not testing error conditions

## Deployment Checklist

Before deploying your new provider:

- [ ] All tests pass
- [ ] Configuration is properly documented
- [ ] Error handling is comprehensive
- [ ] Logging is implemented
- [ ] Health checks work correctly
- [ ] Performance is acceptable
- [ ] Security review is complete
- [ ] Documentation is updated

## Support and Resources

- Review existing provider implementations for patterns
- Use the base test classes for consistent testing
- Leverage the error handling framework
- Follow the logging and monitoring patterns
- Consult the API documentation for interface details

Implementing a new provider should be straightforward if you follow these patterns and best practices. The system is designed to handle the complexity of multiple providers while providing a consistent interface for your application.