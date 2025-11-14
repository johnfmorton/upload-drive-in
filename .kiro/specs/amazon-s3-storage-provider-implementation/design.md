# Design Document: Amazon S3 Storage Provider Implementation

## Overview

This design document outlines the implementation of Amazon S3 as a cloud storage provider for the file intake system. The implementation leverages the existing `CloudStorageProviderInterface` and follows the established provider pattern used by Google Drive, while introducing a simplified authentication model using system-level credentials instead of per-user OAuth.

### Key Design Principles

1. **Shared Bucket Architecture**: Single S3 bucket configured by admin, used by all users
2. **System-Level Authentication**: AWS credentials stored once, no per-user tokens
3. **Interface Compliance**: Full implementation of `CloudStorageProviderInterface`
4. **S3-Compatible Services**: Support for Cloudflare R2, Backblaze B2, and other S3 API-compatible services
5. **Minimal Code Changes**: Leverage existing infrastructure (jobs, error handling, logging)
6. **Flat Storage Model**: Use S3 key prefixes for organization instead of hierarchical folders

## Architecture

### High-Level Component Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                     Admin Configuration UI                   │
│  (resources/views/admin/cloud-storage/index.blade.php)      │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              CloudStorageController                          │
│         (handles provider selection & config)                │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              CloudStorageFactory                             │
│         (creates and initializes providers)                  │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                    S3Provider                                │
│         (implements CloudStorageProviderInterface)           │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  - uploadFile()                                       │  │
│  │  - deleteFile()                                       │  │
│  │  - getConnectionHealth()                             │  │
│  │  - getCapabilities()                                 │  │
│  │  - validateConfiguration()                           │  │
│  │  - generatePresignedUrl()                            │  │
│  │  - setStorageClass()                                 │  │
│  └──────────────────────────────────────────────────────┘  │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                  AWS S3 SDK                                  │
│              (Aws\S3\S3Client)                              │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│            Amazon S3 / S3-Compatible Service                 │
│         (AWS S3, Cloudflare R2, Backblaze B2)               │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow

#### File Upload Flow
```
Client Upload → UploadToGoogleDrive Job → CloudStorageFactory
                                              ↓
                                         S3Provider
                                              ↓
                                    Get System Credentials
                                              ↓
                                    Generate S3 Key (client-email/filename)
                                              ↓
                                    AWS S3 SDK putObject()
                                              ↓
                                    Store S3 Key in file_uploads table
```

#### Configuration Flow
```
Admin UI → CloudStorageController → Validate Config
                                         ↓
                                    Store in cloud_storage_settings
                                         ↓
                                    Initialize S3Provider
                                         ↓
                                    Perform Health Check
                                         ↓
                                    Display Status to Admin
```

## Components and Interfaces

### 1. S3Provider Service Class

**Location**: `app/Services/S3Provider.php`

**Purpose**: Implements `CloudStorageProviderInterface` for Amazon S3 operations

**Key Methods**:

```php
class S3Provider implements CloudStorageProviderInterface
{
    private ?S3Client $s3Client = null;
    private array $config = [];
    
    public function __construct(
        private readonly S3ErrorHandler $errorHandler,
        private readonly CloudStorageLogService $logService
    ) {}
    
    // Core Operations
    public function uploadFile(User $user, string $localPath, string $targetPath, array $metadata = []): string
    public function deleteFile(User $user, string $fileId): bool
    public function getConnectionHealth(User $user): CloudStorageHealthStatus
    
    // Configuration & Initialization
    public function initialize(array $config): void
    public function validateConfiguration(array $config): array
    
    // Capabilities
    public function getCapabilities(): array
    public function supportsFeature(string $feature): bool
    
    // Advanced Features
    public function generatePresignedUrl(User $user, string $fileId, int $expirationMinutes = 60, string $operation = 'download'): ?string
    public function setStorageClass(User $user, string $fileId, string $storageClass): bool
    public function getAvailableStorageClasses(): array
    
    // Helper Methods
    private function ensureInitialized(User $user): void
    private function getSystemConfig(): array
    private function getBucket(User $user): string
    private function getRegion(User $user): string
    private function generateS3Key(string $clientEmail, string $filename): string
    private function isValidBucketName(string $bucket): bool
}
```

### 2. S3ErrorHandler Service Class

**Location**: `app/Services/S3ErrorHandler.php`

**Purpose**: Classify and handle S3-specific errors

**Key Methods**:

```php
class S3ErrorHandler extends BaseCloudStorageErrorHandler implements CloudStorageErrorHandlerInterface
{
    public function classifyError(\Exception $exception): CloudStorageErrorType
    {
        // Map AWS exceptions to CloudStorageErrorType enum
        if ($exception instanceof S3Exception) {
            return match ($exception->getAwsErrorCode()) {
                'InvalidAccessKeyId', 'SignatureDoesNotMatch' => CloudStorageErrorType::INVALID_CREDENTIALS,
                'NoSuchBucket' => CloudStorageErrorType::BUCKET_NOT_FOUND,
                'AccessDenied' => CloudStorageErrorType::BUCKET_ACCESS_DENIED,
                'InvalidBucketName' => CloudStorageErrorType::INVALID_BUCKET_NAME,
                'NoSuchKey' => CloudStorageErrorType::FILE_NOT_FOUND,
                'RequestTimeout' => CloudStorageErrorType::NETWORK_ERROR,
                default => CloudStorageErrorType::UNKNOWN_ERROR,
            };
        }
        
        return parent::classifyError($exception);
    }
}
```

### 3. Configuration Storage

**Table**: `cloud_storage_settings`

**Schema**:
```sql
CREATE TABLE cloud_storage_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,  -- NULL for system-level settings
    provider VARCHAR(255) NOT NULL,
    setting_key VARCHAR(255) NOT NULL,
    setting_value TEXT NULL,
    is_encrypted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_user_provider (user_id, provider),
    INDEX idx_provider_key (provider, setting_key)
);
```

**S3 Settings Stored**:
- `access_key_id` (encrypted)
- `secret_access_key` (encrypted)
- `region`
- `bucket`
- `endpoint` (optional, for S3-compatible services)
- `use_path_style_endpoint` (boolean)

### 4. UI Components

#### S3 Configuration Section

**Location**: `resources/views/admin/cloud-storage/amazon-s3/configuration.blade.php`

**Structure**:
```blade
<div x-show="selectedProvider === 'amazon-s3'" x-cloak class="py-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-medium text-gray-900">Amazon S3</h3>
            <p class="mt-1 text-sm text-gray-500">
                Configure AWS credentials for system-wide S3 storage
            </p>
        </div>
        <div class="flex items-center space-x-4">
            @if($s3Connected)
                <span class="px-3 py-1 text-sm text-green-800 bg-green-100 rounded-full">
                    Connected
                </span>
            @else
                <span class="px-3 py-1 text-sm text-gray-800 bg-gray-100 rounded-full">
                    Not Connected
                </span>
            @endif
        </div>
    </div>
    
    <form action="{{ route('admin.cloud-storage.amazon-s3.update') }}" method="POST" class="mt-6 space-y-4">
        @csrf
        @method('PUT')
        
        <!-- AWS Access Key ID -->
        <div>
            <x-label for="aws_access_key_id" value="AWS Access Key ID" />
            <x-input id="aws_access_key_id" name="aws_access_key_id" type="text" 
                     class="mt-1 block w-full" required />
            <x-input-error for="aws_access_key_id" class="mt-2" />
        </div>
        
        <!-- AWS Secret Access Key -->
        <div>
            <x-label for="aws_secret_access_key" value="AWS Secret Access Key" />
            <x-input id="aws_secret_access_key" name="aws_secret_access_key" 
                     type="password" class="mt-1 block w-full" required />
            <x-input-error for="aws_secret_access_key" class="mt-2" />
        </div>
        
        <!-- Region -->
        <div>
            <x-label for="aws_region" value="AWS Region" />
            <select id="aws_region" name="aws_region" class="mt-1 block w-full" required>
                <option value="">Select a region</option>
                <option value="us-east-1">US East (N. Virginia)</option>
                <option value="us-west-2">US West (Oregon)</option>
                <option value="eu-west-1">EU (Ireland)</option>
                <!-- More regions -->
            </select>
            <x-input-error for="aws_region" class="mt-2" />
        </div>
        
        <!-- Bucket Name -->
        <div>
            <x-label for="aws_bucket" value="S3 Bucket Name" />
            <x-input id="aws_bucket" name="aws_bucket" type="text" 
                     class="mt-1 block w-full" required />
            <x-input-error for="aws_bucket" class="mt-2" />
        </div>
        
        <!-- Custom Endpoint (Optional) -->
        <div>
            <x-label for="aws_endpoint" value="Custom Endpoint (Optional)" />
            <x-input id="aws_endpoint" name="aws_endpoint" type="url" 
                     class="mt-1 block w-full" 
                     placeholder="https://s3.example.com" />
            <p class="mt-1 text-sm text-gray-500">
                For S3-compatible services like Cloudflare R2 or Backblaze B2
            </p>
            <x-input-error for="aws_endpoint" class="mt-2" />
        </div>
        
        <div class="flex justify-end">
            <x-button>Save Configuration</x-button>
        </div>
    </form>
</div>
```

### 5. Controller Methods

**Location**: `app/Http/Controllers/Admin/CloudStorageController.php`

**New Methods**:

```php
public function updateAmazonS3(Request $request)
{
    $validated = $request->validate([
        'aws_access_key_id' => ['required', 'string', 'regex:/^[A-Z0-9]{20}$/'],
        'aws_secret_access_key' => ['required', 'string', 'size:40'],
        'aws_region' => ['required', 'string', 'regex:/^[a-z0-9-]+$/'],
        'aws_bucket' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]$/'],
        'aws_endpoint' => ['nullable', 'url'],
    ]);
    
    // Store configuration
    $this->storeS3Configuration($validated);
    
    // Perform health check
    $factory = app(CloudStorageFactory::class);
    $provider = $factory->create('amazon-s3');
    $healthStatus = $provider->getConnectionHealth(Auth::user());
    
    if ($healthStatus->isHealthy()) {
        return redirect()->back()->with('success', 'S3 configuration saved and connection verified');
    } else {
        return redirect()->back()->with('error', 'S3 configuration saved but connection failed: ' . $healthStatus->lastErrorMessage);
    }
}

private function storeS3Configuration(array $config): void
{
    $settings = [
        'access_key_id' => ['value' => $config['aws_access_key_id'], 'encrypted' => true],
        'secret_access_key' => ['value' => $config['aws_secret_access_key'], 'encrypted' => true],
        'region' => ['value' => $config['aws_region'], 'encrypted' => false],
        'bucket' => ['value' => $config['aws_bucket'], 'encrypted' => false],
    ];
    
    if (!empty($config['aws_endpoint'])) {
        $settings['endpoint'] = ['value' => $config['aws_endpoint'], 'encrypted' => false];
        $settings['use_path_style_endpoint'] = ['value' => 'true', 'encrypted' => false];
    }
    
    foreach ($settings as $key => $data) {
        CloudStorageSetting::updateOrCreate(
            [
                'user_id' => null, // System-level
                'provider' => 'amazon-s3',
                'setting_key' => $key,
            ],
            [
                'setting_value' => $data['encrypted'] ? encrypt($data['value']) : $data['value'],
                'is_encrypted' => $data['encrypted'],
            ]
        );
    }
}
```

## Data Models

### CloudStorageSetting Model

**Location**: `app/Models/CloudStorageSetting.php`

**Enhancements**:

```php
class CloudStorageSetting extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'setting_key',
        'setting_value',
        'is_encrypted',
    ];
    
    protected $casts = [
        'is_encrypted' => 'boolean',
    ];
    
    // Accessor for decrypted value
    public function getDecryptedValueAttribute(): ?string
    {
        if ($this->is_encrypted && $this->setting_value) {
            return decrypt($this->setting_value);
        }
        return $this->setting_value;
    }
    
    // Scope for system-level settings
    public function scopeSystemLevel($query)
    {
        return $query->whereNull('user_id');
    }
    
    // Scope for specific provider
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
    
    // Helper to get all settings for a provider
    public static function getProviderSettings(string $provider, ?int $userId = null): array
    {
        $settings = static::where('provider', $provider)
            ->where('user_id', $userId)
            ->get();
            
        return $settings->mapWithKeys(function ($setting) {
            return [$setting->setting_key => $setting->decrypted_value];
        })->toArray();
    }
}
```

## Error Handling

### Error Type Mapping

```php
// New CloudStorageErrorType enum cases for S3
enum CloudStorageErrorType: string
{
    // Existing cases...
    case INVALID_CREDENTIALS = 'invalid_credentials';
    case NETWORK_ERROR = 'network_error';
    
    // New S3-specific cases
    case BUCKET_NOT_FOUND = 'bucket_not_found';
    case BUCKET_ACCESS_DENIED = 'bucket_access_denied';
    case INVALID_BUCKET_NAME = 'invalid_bucket_name';
    case INVALID_REGION = 'invalid_region';
    case FILE_NOT_FOUND = 'file_not_found';
    case STORAGE_CLASS_NOT_SUPPORTED = 'storage_class_not_supported';
}
```

### Error Messages

```php
// In CloudStorageErrorMessageService
private function getS3ErrorMessages(): array
{
    return [
        CloudStorageErrorType::BUCKET_NOT_FOUND->value => [
            'user' => 'The specified S3 bucket does not exist. Please check your configuration.',
            'admin' => 'S3 bucket not found. Verify the bucket name and ensure it exists in the specified region.',
            'technical' => 'NoSuchBucket: The specified bucket does not exist.',
        ],
        CloudStorageErrorType::BUCKET_ACCESS_DENIED->value => [
            'user' => 'Access to the S3 bucket was denied. Please contact your administrator.',
            'admin' => 'S3 bucket access denied. Verify that your AWS credentials have the necessary permissions.',
            'technical' => 'AccessDenied: The AWS credentials do not have permission to access this bucket.',
        ],
        // More error messages...
    ];
}
```

## Testing Strategy

### Unit Tests

**Location**: `tests/Unit/Services/S3ProviderTest.php`

**Test Cases**:
1. `test_validates_configuration_correctly()`
2. `test_initializes_with_valid_configuration()`
3. `test_generates_correct_s3_keys()`
4. `test_uploads_file_successfully()`
5. `test_deletes_file_successfully()`
6. `test_generates_presigned_urls()`
7. `test_handles_invalid_credentials()`
8. `test_handles_bucket_not_found()`
9. `test_supports_custom_endpoints()`
10. `test_returns_correct_capabilities()`

### Integration Tests

**Location**: `tests/Integration/S3ProviderIntegrationTest.php`

**Test Cases**:
1. `test_full_upload_workflow_with_real_s3()`
2. `test_health_check_with_real_s3()`
3. `test_file_deletion_with_real_s3()`
4. `test_presigned_url_generation_with_real_s3()`
5. `test_s3_compatible_service_integration()`

### Feature Tests

**Location**: `tests/Feature/Admin/CloudStorageS3ConfigurationTest.php`

**Test Cases**:
1. `test_admin_can_configure_s3()`
2. `test_s3_configuration_validation()`
3. `test_s3_health_check_after_configuration()`
4. `test_s3_configuration_is_encrypted()`
5. `test_admin_can_switch_to_s3_provider()`

## Security Considerations

### 1. Credential Encryption

- AWS credentials stored encrypted in database using Laravel's `encrypt()` helper
- Credentials never logged or displayed in plain text
- Credentials transmitted over HTTPS only

### 2. Access Control

- Only admin users can configure S3 settings
- S3 bucket permissions configured to allow only necessary operations
- IAM policy recommendations provided in documentation

### 3. Validation

- Strict validation of AWS credential formats
- Bucket name validation against S3 naming rules
- Region validation against known AWS regions
- Custom endpoint URL validation

### 4. Audit Logging

- All S3 configuration changes logged
- All file operations logged with user context
- Failed authentication attempts logged

## Performance Considerations

### 1. Connection Pooling

- Reuse S3Client instances where possible
- Cache provider instances in CloudStorageFactory

### 2. Multipart Upload

- Use multipart upload for files > 50MB
- Configurable chunk size
- Parallel chunk uploads where supported

### 3. Presigned URLs

- Generate presigned URLs for large file downloads
- Reduce server bandwidth usage
- Configurable expiration times

### 4. Health Check Optimization

- Lightweight health checks (list 1 object)
- Cache health status for short periods
- Async health checks where possible

## Migration Path

### Phase 1: Core Implementation
1. Complete S3Provider implementation
2. Add S3ErrorHandler
3. Update configuration UI
4. Add controller methods

### Phase 2: Testing & Validation
1. Unit tests
2. Integration tests
3. Manual testing with real S3
4. S3-compatible service testing

### Phase 3: Documentation & Deployment
1. User documentation
2. Admin setup guide
3. IAM policy templates
4. Deployment checklist

## Configuration Reference

### Environment Variables

```env
# Amazon S3 Configuration
AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=my-file-intake-bucket

# Optional: For S3-compatible services
AWS_ENDPOINT=https://s3.example.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

### IAM Policy Template

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject",
        "s3:ListBucket"
      ],
      "Resource": [
        "arn:aws:s3:::my-file-intake-bucket",
        "arn:aws:s3:::my-file-intake-bucket/*"
      ]
    }
  ]
}
```

## Diagrams

### S3 Key Structure

```
bucket-name/
├── client1@example.com/
│   ├── document1.pdf
│   ├── document2.docx
│   └── image1.jpg
├── client2@example.com/
│   ├── file1.pdf
│   └── file2.xlsx
└── client3@example.com/
    └── report.pdf
```

### Configuration Flow Diagram

```
┌─────────────┐
│ Admin User  │
└──────┬──────┘
       │
       │ 1. Navigate to Cloud Storage Config
       ▼
┌─────────────────────────────┐
│  Cloud Storage Config Page  │
└──────┬──────────────────────┘
       │
       │ 2. Select "Amazon S3"
       ▼
┌─────────────────────────────┐
│  S3 Configuration Form      │
│  - Access Key ID            │
│  - Secret Access Key        │
│  - Region                   │
│  - Bucket Name              │
│  - Custom Endpoint (opt)    │
└──────┬──────────────────────┘
       │
       │ 3. Submit Form
       ▼
┌─────────────────────────────┐
│  CloudStorageController     │
│  - Validate Input           │
│  - Encrypt Credentials      │
│  - Store in DB              │
└──────┬──────────────────────┘
       │
       │ 4. Initialize Provider
       ▼
┌─────────────────────────────┐
│  S3Provider                 │
│  - Create S3Client          │
│  - Perform Health Check     │
└──────┬──────────────────────┘
       │
       │ 5. Return Status
       ▼
┌─────────────────────────────┐
│  Display Success/Error      │
└─────────────────────────────┘
```

## Dependencies

### Required Packages

- `aws/aws-sdk-php`: ^3.0 (AWS SDK for PHP)
- Existing Laravel packages (no additional required)

### Installation

```bash
composer require aws/aws-sdk-php
```

## Rollback Strategy

If issues arise after deployment:

1. **Immediate**: Switch default provider back to Google Drive in config
2. **Short-term**: Disable S3 provider in configuration
3. **Data Recovery**: S3 files remain intact, can be accessed directly
4. **Database**: Rollback migration if schema changes were made

## Future Enhancements

1. **Lifecycle Policies**: Automatic transition to cheaper storage classes
2. **Versioning**: Enable S3 versioning for file history
3. **Cross-Region Replication**: Disaster recovery setup
4. **CloudFront Integration**: CDN for faster file delivery
5. **Server-Side Encryption**: KMS-managed encryption keys
6. **Batch Operations**: Bulk file operations for efficiency
