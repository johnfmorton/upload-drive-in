# Design Document

## Overview

This design enhances the Google Drive integration error handling system to provide clear, actionable feedback when uploads fail. The current system has basic error logging but lacks user-friendly error messages and proactive connection health monitoring. The enhancement will implement comprehensive error classification, user notifications, dashboard status indicators, and automated recovery mechanisms.

## Architecture

### Cloud Storage Provider Interface

The system will use a provider-agnostic interface to handle all cloud storage operations and errors:

```php
interface CloudStorageProviderInterface
{
    public function uploadFile(User $user, string $localPath, string $targetPath, array $metadata = []): string;
    public function deleteFile(User $user, string $fileId): bool;
    public function getConnectionHealth(User $user): CloudStorageHealthStatus;
    public function handleAuthCallback(User $user, string $code): void;
    public function getAuthUrl(User $user): string;
    public function disconnect(User $user): void;
}

interface CloudStorageErrorHandlerInterface
{
    public function classifyError(Exception $exception): CloudStorageErrorType;
    public function getUserFriendlyMessage(CloudStorageErrorType $type, array $context = []): string;
    public function shouldRetry(CloudStorageErrorType $type, int $attemptCount): bool;
    public function getRetryDelay(CloudStorageErrorType $type, int $attemptCount): int;
}
```

### Universal Error Classification System

The system will categorize cloud storage errors into universal types that apply across providers:

```php
enum CloudStorageErrorType: string
{
    case TOKEN_EXPIRED = 'token_expired';
    case INSUFFICIENT_PERMISSIONS = 'insufficient_permissions';
    case API_QUOTA_EXCEEDED = 'api_quota_exceeded';
    case NETWORK_ERROR = 'network_error';
    case FILE_NOT_FOUND = 'file_not_found';
    case FOLDER_ACCESS_DENIED = 'folder_access_denied';
    case STORAGE_QUOTA_EXCEEDED = 'storage_quota_exceeded';
    case INVALID_FILE_TYPE = 'invalid_file_type';
    case UNKNOWN_ERROR = 'unknown_error';
}
```

### Connection Health Monitoring

A universal service will monitor cloud storage connection health across all providers:

```php
class CloudStorageHealthService
{
    public function checkConnectionHealth(User $user, string $provider): CloudStorageHealthStatus;
    public function markConnectionAsUnhealthy(User $user, string $provider, string $reason): void;
    public function recordSuccessfulOperation(User $user, string $provider): void;
    public function getHealthSummary(User $user): array;
    public function getAllProvidersHealth(User $user): array;
}
```

## Components and Interfaces

### Cloud Storage Provider Implementations

**GoogleDriveProvider Service**

- Implements CloudStorageProviderInterface
- Handles Google Drive specific operations
- Integrates with GoogleDriveErrorHandler

**GoogleDriveErrorHandler Service**

- Implements CloudStorageErrorHandlerInterface
- Classifies Google API exceptions into universal error types
- Generates provider-specific user-friendly messages
- Determines retry strategies based on Google Drive behavior

```php
class GoogleDriveProvider implements CloudStorageProviderInterface
{
    public function __construct(
        private GoogleDriveService $driveService,
        private GoogleDriveErrorHandler $errorHandler
    ) {}
    
    public function uploadFile(User $user, string $localPath, string $targetPath, array $metadata = []): string;
    // ... other interface methods
}

class GoogleDriveErrorHandler implements CloudStorageErrorHandlerInterface
{
    public function classifyError(Exception $exception): CloudStorageErrorType;
    public function getUserFriendlyMessage(CloudStorageErrorType $type, array $context = []): string;
    public function shouldRetry(CloudStorageErrorType $type, int $attemptCount): bool;
    public function getRetryDelay(CloudStorageErrorType $type, int $attemptCount): int;
}
```

### Universal Dashboard Status Component

**Cloud Storage Status Widget**

- Multi-provider connection status display
- Provider-specific error counts and last successful uploads
- One-click reconnection buttons for each provider
- Pending uploads requiring attention across all providers

### Enhanced Job Error Handling

**Modified UploadToCloudStorage Job** (renamed from UploadToGoogleDrive)

- Provider-agnostic upload handling
- Integration with CloudStorageErrorHandlerInterface
- Detailed error context collection
- User notification triggers
- Connection health status updates

### Notification System

**CloudStorageConnectionAlert Notification**

- Email notifications for connection issues across all providers
- Dashboard alerts for multiple failures
- Proactive warnings before token expiration
- Provider-specific messaging and actions

## Data Models

### Enhanced FileUpload Model

Add fields for detailed error tracking across all cloud providers:

```php
// New fields in file_uploads table
'cloud_storage_provider' => 'string nullable', // google-drive, dropbox, etc.
'cloud_storage_error_type' => 'string nullable',
'cloud_storage_error_context' => 'json nullable',
'connection_health_at_failure' => 'timestamp nullable',
'retry_recommended_at' => 'timestamp nullable'
```

### CloudStorageHealthStatus Model

New model to track connection health across all providers:

```php
class CloudStorageHealthStatus extends Model
{
    protected $fillable = [
        'user_id',
        'provider', // google-drive, dropbox, onedrive, etc.
        'status', // healthy, degraded, unhealthy, disconnected
        'last_successful_operation_at',
        'consecutive_failures',
        'last_error_type',
        'last_error_message',
        'token_expires_at',
        'requires_reconnection',
        'provider_specific_data' // JSON field for provider-specific health info
    ];
    
    protected $casts = [
        'provider_specific_data' => 'array',
        'last_successful_operation_at' => 'datetime',
        'token_expires_at' => 'datetime'
    ];
}
```

## Error Handling

### Error Classification Logic (Google Drive Implementation)

```php
class GoogleDriveErrorHandler implements CloudStorageErrorHandlerInterface
{
    public function classifyError(Exception $exception): CloudStorageErrorType
    {
        if ($exception instanceof GoogleServiceException) {
            return match ($exception->getCode()) {
                401 => CloudStorageErrorType::TOKEN_EXPIRED,
                403 => $this->classifyForbiddenError($exception),
                429 => CloudStorageErrorType::API_QUOTA_EXCEEDED,
                404 => CloudStorageErrorType::FILE_NOT_FOUND,
                default => CloudStorageErrorType::UNKNOWN_ERROR
            };
        }
        
        if ($this->isNetworkError($exception)) {
            return CloudStorageErrorType::NETWORK_ERROR;
        }
        
        return CloudStorageErrorType::UNKNOWN_ERROR;
    }
    
    private function classifyForbiddenError(GoogleServiceException $exception): CloudStorageErrorType
    {
        $message = strtolower($exception->getMessage());
        if (str_contains($message, 'insufficient') || str_contains($message, 'scope')) {
            return CloudStorageErrorType::INSUFFICIENT_PERMISSIONS;
        }
        if (str_contains($message, 'quota') || str_contains($message, 'storage')) {
            return CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED;
        }
        return CloudStorageErrorType::FOLDER_ACCESS_DENIED;
    }
}
```

### User-Friendly Messages (Provider-Agnostic)

```php
public function getUserFriendlyMessage(CloudStorageErrorType $type, array $context = []): string
{
    $provider = $context['provider'] ?? 'cloud storage';
    
    return match ($type) {
        CloudStorageErrorType::TOKEN_EXPIRED => 
            "Your {$provider} connection has expired. Please reconnect to continue uploading files.",
        CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => 
            "Insufficient {$provider} permissions. Please reconnect and grant full access.",
        CloudStorageErrorType::API_QUOTA_EXCEEDED => 
            "{$provider} API limit reached. Uploads will resume automatically in " . 
            $this->getQuotaResetTime($context),
        CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED => 
            "{$provider} storage quota exceeded. Please free up space or upgrade your plan.",
        CloudStorageErrorType::NETWORK_ERROR => 
            'Network connection issue. The upload will be retried automatically.',
        default => "{$provider} upload failed: " . ($context['original_message'] ?? 'Unknown error')
    };
}
```

### Universal Retry Strategy

```php
public function shouldRetry(CloudStorageErrorType $type, int $attemptCount): bool
{
    return match ($type) {
        CloudStorageErrorType::TOKEN_EXPIRED => false, // Requires user intervention
        CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => false, // Requires user intervention
        CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED => false, // Requires user intervention
        CloudStorageErrorType::API_QUOTA_EXCEEDED => $attemptCount < 2, // Retry once after delay
        CloudStorageErrorType::NETWORK_ERROR => $attemptCount < 3, // Standard retry
        CloudStorageErrorType::FILE_NOT_FOUND => false, // Permanent failure
        CloudStorageErrorType::INVALID_FILE_TYPE => false, // Permanent failure
        default => $attemptCount < 2 // Conservative retry for unknown errors
    };
}
```

## Testing Strategy

### Unit Tests

- **GoogleDriveErrorHandler**: Error classification accuracy
- **GoogleDriveHealthService**: Health status tracking
- **Enhanced Job**: Error handling and notification triggers
- **User-friendly messages**: Message generation for all error types

### Integration Tests

- **End-to-end error scenarios**: Token expiration, API limits, network issues
- **Dashboard status updates**: Real-time status reflection
- **Notification delivery**: Email and dashboard alerts
- **Reconnection flow**: OAuth flow with error recovery

### Manual Testing Scenarios

1. **Token Expiration**: Simulate expired token and verify user experience
2. **API Quota**: Test quota exceeded scenario and retry behavior
3. **Network Issues**: Simulate network failures and recovery
4. **Permission Issues**: Test insufficient scope scenarios
5. **Dashboard Integration**: Verify status indicators and reconnection flow

## Implementation Phases

### Phase 1: Provider Interface and Error Classification

- Create CloudStorageProviderInterface and CloudStorageErrorHandlerInterface
- Implement GoogleDriveProvider and GoogleDriveErrorHandler
- Create CloudStorageErrorType enum
- Add CloudStorageHealthStatus model and migration

### Phase 2: Enhanced Job Error Handling

- Refactor UploadToGoogleDrive job to use provider interface
- Implement detailed error context collection
- Add error classification to existing failed uploads
- Update FileUpload model with cloud storage error fields

### Phase 3: User Interface Enhancements

- Create universal cloud storage status dashboard widget
- Implement one-click reconnection functionality for all providers
- Add user-friendly error messages to admin interface
- Display provider-specific health status

### Phase 4: Proactive Monitoring and Notifications

- Implement CloudStorageHealthService
- Add connection health tracking across all providers
- Create automated health checks and alerts
- Implement email notifications and dashboard alerts

## Security Considerations

- **Token Security**: Ensure error messages don't expose sensitive token information
- **Rate Limiting**: Implement proper backoff for API quota scenarios
- **Audit Logging**: Enhanced logging for security monitoring
- **Permission Validation**: Verify user permissions before displaying sensitive error details

## Performance Considerations

- **Error Classification**: Efficient exception analysis without performance impact
- **Health Monitoring**: Lightweight health checks with minimal API calls
- **Dashboard Updates**: Real-time status updates without excessive database queries
- **Notification Throttling**: Prevent notification spam during widespread issues
