# Cloud Storage Provider System Documentation

## Overview

The Cloud Storage Provider System is a comprehensive abstraction layer that enables the application to work with multiple cloud storage providers (Google Drive, Amazon S3, Azure Blob Storage, etc.) through a unified interface. This system provides provider factory patterns, dynamic provider resolution, configuration management, and enhanced error handling.

## Architecture

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        Application Layer                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   Admin UI   │  │  Employee UI │  │   Client UI  │          │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘          │
│         │                  │                  │                   │
│         └──────────────────┴──────────────────┘                  │
│                            │                                      │
└────────────────────────────┼──────────────────────────────────────┘
                             │
┌────────────────────────────┼──────────────────────────────────────┐
│                    Controller Layer                               │
│                            │                                      │
│         ┌──────────────────┴──────────────────┐                  │
│         │   CloudStorageController            │                  │
│         │   - Provider selection              │                  │
│         │   - Configuration management        │                  │
│         │   - Health checks                   │                  │
│         └──────────────────┬──────────────────┘                  │
└────────────────────────────┼──────────────────────────────────────┘
                             │
┌────────────────────────────┼──────────────────────────────────────┐
│                     Service Layer                                 │
│         ┌──────────────────┴──────────────────┐                  │
│         │   CloudStorageManager               │                  │
│         │   - Provider coordination           │                  │
│         │   - User provider management        │                  │
│         └──────────────────┬──────────────────┘                  │
│                            │                                      │
│         ┌──────────────────┴──────────────────┐                  │
│         │   CloudStorageFactory               │                  │
│         │   - Provider instantiation          │                  │
│         │   - Configuration injection         │                  │
│         └──────────────────┬──────────────────┘                  │
│                            │                                      │
│         ┌──────────────────┴──────────────────┐                  │
│         │                                      │                  │
│    ┌────▼─────┐                         ┌─────▼────┐            │
│    │  Google  │                         │ Amazon   │            │
│    │  Drive   │                         │   S3     │            │
│    │ Provider │                         │ Provider │            │
│    └────┬─────┘                         └─────┬────┘            │
│         │                                      │                  │
└─────────┼──────────────────────────────────────┼──────────────────┘
          │                                      │
┌─────────┼──────────────────────────────────────┼──────────────────┐
│         │         External Services            │                  │
│    ┌────▼─────┐                         ┌─────▼────┐            │
│    │  Google  │                         │   AWS    │            │
│    │  Drive   │                         │    S3    │            │
│    │   API    │                         │   API    │            │
│    └──────────┘                         └──────────┘            │
│                                                                   │
│    ┌──────────────────────────────────────────────┐             │
│    │  S3-Compatible Services                      │             │
│    │  - Cloudflare R2                             │             │
│    │  - Backblaze B2                              │             │
│    │  - DigitalOcean Spaces                       │             │
│    └──────────────────────────────────────────────┘             │
└───────────────────────────────────────────────────────────────────┘
```

### File Upload Flow Comparison

#### Google Drive Upload Flow

```
Client Upload
    │
    ▼
UploadToGoogleDrive Job
    │
    ▼
CloudStorageFactory
    │
    ▼
GoogleDriveProvider
    │
    ├─► Get user's OAuth token
    │
    ├─► Get/Create root folder
    │
    ├─► Get/Create client subfolder
    │
    ▼
Google Drive API
    │
    ├─► Upload file to folder
    │
    ▼
Store file_id in database
    │
    ▼
Cleanup local file
```

#### Amazon S3 Upload Flow

```
Client Upload
    │
    ▼
UploadToGoogleDrive Job
    │
    ▼
CloudStorageFactory
    │
    ▼
S3Provider
    │
    ├─► Get system-level credentials
    │
    ├─► Generate S3 key (client-email/filename)
    │
    ├─► Check file size for multipart
    │
    ▼
AWS S3 API
    │
    ├─► Upload file (single or multipart)
    │
    ├─► Set metadata and tags
    │
    ▼
Store S3 key in database
    │
    ▼
Cleanup local file
```

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

### Provider Comparison Table

| Feature | Google Drive | Amazon S3 | Azure Blob | Dropbox | OneDrive |
|---------|-------------|-----------|------------|---------|----------|
| **Status** | ✅ Available | ✅ Available | 🔜 Planned | 🔜 Planned | 🔜 Planned |
| **Authentication** | OAuth 2.0 | API Key | API Key | OAuth 2.0 | OAuth 2.0 |
| **Storage Model** | Hierarchical | Flat (key-based) | Flat | Hierarchical | Hierarchical |
| **Folder Creation** | ✅ Yes | ❌ No (virtual) | ❌ No (virtual) | ✅ Yes | ✅ Yes |
| **File Upload** | ✅ Yes | ✅ Yes | 🔜 Planned | 🔜 Planned | 🔜 Planned |
| **File Delete** | ✅ Yes | ✅ Yes | 🔜 Planned | 🔜 Planned | 🔜 Planned |
| **Presigned URLs** | ❌ No | ✅ Yes | 🔜 Planned | ❌ No | ❌ No |
| **Storage Classes** | ❌ No | ✅ Yes | 🔜 Planned | ❌ No | ❌ No |
| **Max File Size** | 5 GB | 5 TB | TBD | 2 GB | 250 GB |
| **Per-User Auth** | ✅ Yes | ❌ No (system-level) | TBD | TBD | TBD |
| **Multipart Upload** | ✅ Yes | ✅ Yes | TBD | TBD | TBD |
| **S3-Compatible** | N/A | ✅ Yes | N/A | N/A | N/A |

### Google Drive

**Status**: Fully Available

**Authentication**: OAuth 2.0 with per-user tokens
- Each user authenticates with their own Google account
- Tokens stored per user with automatic refresh
- Requires Google Cloud Console project setup

**Storage Model**: Hierarchical (folders)
- Native folder support with parent-child relationships
- Files organized in user-defined folder structures
- Root folder configurable per user

**Key Features**:
- ✅ Full CRUD operations
- ✅ Folder management (create, delete, organize)
- ✅ Per-user authentication and storage
- ✅ Automatic token refresh
- ✅ Chunked uploads for large files
- ✅ File metadata and permissions

**Configuration Requirements**:
- Google Cloud Console project
- OAuth 2.0 Client ID and Secret
- Redirect URI configuration
- Drive API enabled

**Best For**:
- Users who want files in their personal Google Drive
- Organizations using Google Workspace
- Scenarios requiring hierarchical folder organization
- Per-user file isolation

### Amazon S3

**Status**: Fully Available

**Authentication**: API Key (system-level)
- Single set of AWS credentials configured by admin
- All users share the same S3 bucket
- No per-user authentication required

**Storage Model**: Flat (key-based with prefixes)
- Files identified by unique keys (paths)
- Folder-like organization using key prefixes
- Single shared bucket for entire system

**Key Features**:
- ✅ File upload with metadata
- ✅ File deletion
- ✅ Presigned URLs (download, upload, delete)
- ✅ Storage class management (Standard, IA, Glacier, Deep Archive)
- ✅ Multipart upload for large files (>50MB)
- ✅ Custom endpoint support (S3-compatible services)
- ✅ Server-side encryption
- ✅ Object tagging and metadata

**Configuration Requirements**:
- AWS account with S3 access
- IAM user with appropriate permissions
- Access Key ID and Secret Access Key
- S3 bucket (pre-created)
- AWS region selection

**S3-Compatible Services**:
- ✅ Cloudflare R2
- ✅ Backblaze B2
- ✅ DigitalOcean Spaces
- ✅ MinIO
- ✅ Wasabi

**Best For**:
- Organizations wanting centralized storage
- Cost-effective storage with lifecycle policies
- High-volume file storage
- S3-compatible service users
- Scenarios not requiring per-user isolation

### Google Drive vs Amazon S3: Key Differences

#### Authentication Model

**Google Drive**:
- Per-user OAuth 2.0 authentication
- Each user connects their own Google account
- Files stored in individual user's Drive
- Token refresh handled per user

**Amazon S3**:
- System-level API key authentication
- Single AWS account for entire system
- All files in shared bucket
- No per-user tokens needed

#### Storage Organization

**Google Drive**:
```
User's Google Drive
└── Configured Root Folder
    ├── client1@example.com/
    │   ├── document1.pdf
    │   └── document2.docx
    └── client2@example.com/
        └── file1.pdf
```

**Amazon S3**:
```
my-bucket/
├── client1@example.com/document1.pdf
├── client1@example.com/document2.docx
└── client2@example.com/file1.pdf
```

#### File Access Patterns

**Google Drive**:
- Files accessed through Google Drive API
- Requires valid OAuth token
- Files visible in user's Google Drive interface
- Sharing managed through Google Drive permissions

**Amazon S3**:
- Files accessed through S3 API or presigned URLs
- Requires AWS credentials or presigned URL
- Files not visible in any user interface by default
- Access controlled through IAM policies and bucket policies

#### Cost Considerations

**Google Drive**:
- Free tier: 15 GB per user
- Paid: Google Workspace subscription required
- Cost scales with number of users
- No egress fees for downloads

**Amazon S3**:
- Pay-per-use pricing
- Storage: ~$0.023/GB/month (Standard)
- Requests: $0.005 per 1,000 PUT requests
- Data transfer: $0.09/GB egress
- Cost scales with storage and usage, not users

#### Advanced Features

**Google Drive**:
- Native folder hierarchy
- Google Workspace integration
- Real-time collaboration
- Version history
- File preview in browser

**Amazon S3**:
- Storage class transitions (lifecycle policies)
- Presigned URLs for temporary access
- Server-side encryption (SSE-S3, SSE-KMS)
- Object versioning
- Cross-region replication
- CloudFront CDN integration

#### Setup Complexity

**Google Drive**:
- Moderate: Requires Google Cloud Console setup
- OAuth flow configuration
- Per-user authentication flow
- Token management

**Amazon S3**:
- Simple: Single IAM user setup
- Bucket creation
- One-time credential configuration
- No per-user setup needed

### Future Providers

The following providers are planned for future implementation:

- **Azure Blob Storage**: Microsoft's object storage solution
- **Dropbox**: Popular file hosting service
- **Microsoft OneDrive**: Microsoft's cloud storage service

## Configuration

### Environment Variables

```env
# Default provider (google-drive or amazon-s3)
CLOUD_STORAGE_DEFAULT=google-drive

# Google Drive Configuration
GOOGLE_DRIVE_CLIENT_ID=your_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret
# Note: Root folder ID is configured per-user through the dashboard

# Amazon S3 Configuration
AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=my-file-intake-bucket

# Optional: S3-Compatible Services (Cloudflare R2, Backblaze B2, etc.)
AWS_ENDPOINT=https://s3.example.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

### Provider-Specific Configuration Examples

#### Google Drive Setup

1. **Create Google Cloud Project**:
   - Go to [Google Cloud Console](https://console.cloud.google.com)
   - Create a new project or select existing
   - Enable Google Drive API

2. **Configure OAuth Consent Screen**:
   - Set application name and support email
   - Add scopes: `drive.file` and `drive`
   - Add test users if in development

3. **Create OAuth 2.0 Credentials**:
   - Create OAuth 2.0 Client ID
   - Application type: Web application
   - Authorized redirect URI: `https://yourdomain.com/admin/cloud-storage/google-drive/callback`

4. **Configure in Application**:
   ```env
   GOOGLE_DRIVE_CLIENT_ID=123456789-abc123.apps.googleusercontent.com
   GOOGLE_DRIVE_CLIENT_SECRET=GOCSPX-abc123def456
   ```

5. **User Authentication**:
   - Each user navigates to Cloud Storage settings
   - Clicks "Connect Google Drive"
   - Completes OAuth flow
   - Selects root folder (optional)

#### Amazon S3 Setup

1. **Create S3 Bucket**:
   ```bash
   aws s3 mb s3://my-file-intake-bucket --region us-east-1
   ```

2. **Create IAM User**:
   ```bash
   aws iam create-user --user-name file-intake-s3-user
   ```

3. **Create IAM Policy** (`s3-file-intake-policy.json`):
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

4. **Attach Policy and Create Access Keys**:
   ```bash
   aws iam put-user-policy --user-name file-intake-s3-user \
     --policy-name S3FileIntakePolicy \
     --policy-document file://s3-file-intake-policy.json
   
   aws iam create-access-key --user-name file-intake-s3-user
   ```

5. **Configure in Application**:
   - Admin navigates to Cloud Storage settings
   - Selects "Amazon S3" provider
   - Enters Access Key ID and Secret Access Key
   - Selects region and enters bucket name
   - Clicks "Save Configuration"
   - System performs health check automatically

#### S3-Compatible Services

**Cloudflare R2**:
```env
AWS_ACCESS_KEY_ID=your_r2_access_key
AWS_SECRET_ACCESS_KEY=your_r2_secret_key
AWS_DEFAULT_REGION=auto
AWS_BUCKET=my-r2-bucket
AWS_ENDPOINT=https://your-account-id.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

**Backblaze B2**:
```env
AWS_ACCESS_KEY_ID=your_b2_key_id
AWS_SECRET_ACCESS_KEY=your_b2_application_key
AWS_DEFAULT_REGION=us-west-002
AWS_BUCKET=my-b2-bucket
AWS_ENDPOINT=https://s3.us-west-002.backblazeb2.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

**DigitalOcean Spaces**:
```env
AWS_ACCESS_KEY_ID=your_spaces_key
AWS_SECRET_ACCESS_KEY=your_spaces_secret
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=my-space-name
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
AWS_USE_PATH_STYLE_ENDPOINT=false
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
        'availability' => 'fully_available',
        'config' => [
            'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        ],
        'features' => [
            'folder_creation' => true,
            'file_upload' => true,
            'file_delete' => true,
            'folder_delete' => true,
            'presigned_urls' => false,
            'storage_classes' => false,
            'multipart_upload' => true,
            'max_file_size' => 5368709120, // 5GB
        ],
    ],
    
    'amazon-s3' => [
        'driver' => 'amazon-s3',
        'class' => S3Provider::class,
        'error_handler' => S3ErrorHandler::class,
        'auth_type' => 'api_key',
        'storage_model' => 'flat',
        'availability' => 'fully_available',
        'config' => [
            'access_key_id' => env('AWS_ACCESS_KEY_ID'),
            'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],
        'features' => [
            'folder_creation' => false, // Virtual folders via key prefixes
            'file_upload' => true,
            'file_delete' => true,
            'folder_delete' => false,
            'presigned_urls' => true,
            'storage_classes' => true,
            'multipart_upload' => true,
            'max_file_size' => 5497558138880, // 5TB
            's3_compatible' => true,
        ],
    ],
    
    // ... other providers
],
```

### Database Configuration

Provider-specific settings can be stored in the `cloud_storage_settings` table:

#### Google Drive Settings (per-user)
```php
// Store user's root folder ID
CloudStorageSetting::updateOrCreate(
    [
        'user_id' => $user->id,
        'provider' => 'google-drive',
        'setting_key' => 'root_folder_id',
    ],
    [
        'setting_value' => 'folder_id_from_google',
        'is_encrypted' => false,
    ]
);
```

#### Amazon S3 Settings (system-level)
```php
// Store S3 credentials (system-level, user_id = null)
CloudStorageSetting::updateOrCreate(
    [
        'user_id' => null, // System-level
        'provider' => 'amazon-s3',
        'setting_key' => 'access_key_id',
    ],
    [
        'setting_value' => encrypt($accessKeyId),
        'is_encrypted' => true,
    ]
);

CloudStorageSetting::updateOrCreate(
    [
        'user_id' => null,
        'provider' => 'amazon-s3',
        'setting_key' => 'secret_access_key',
    ],
    [
        'setting_value' => encrypt($secretAccessKey),
        'is_encrypted' => true,
    ]
);

// Optional: Storage class preference
CloudStorageSetting::updateOrCreate(
    [
        'user_id' => null,
        'provider' => 'amazon-s3',
        'setting_key' => 'storage_class',
    ],
    [
        'setting_value' => 'STANDARD_IA',
        'is_encrypted' => false,
    ]
);
```

#### Retrieving Settings
```php
// Get Google Drive settings for specific user
$settings = CloudStorageSetting::where('user_id', $user->id)
    ->where('provider', 'google-drive')
    ->get()
    ->mapWithKeys(fn($s) => [$s->setting_key => $s->decrypted_value]);

// Get S3 system-level settings
$settings = CloudStorageSetting::whereNull('user_id')
    ->where('provider', 'amazon-s3')
    ->get()
    ->mapWithKeys(fn($s) => [$s->setting_key => $s->decrypted_value]);
```

## Amazon S3 Advanced Features

### Presigned URLs

Amazon S3 supports presigned URLs for temporary, secure access to objects without requiring AWS credentials:

```php
$provider = app(CloudStorageFactory::class)->create('amazon-s3');

// Generate download URL (valid for 1 hour)
$downloadUrl = $provider->generatePresignedUrl($user, $s3Key, 60, 'download');

// Generate upload URL (valid for 30 minutes)
$uploadUrl = $provider->generatePresignedUrl($user, $s3Key, 30, 'upload');

// Generate delete URL (valid for 15 minutes)
$deleteUrl = $provider->generatePresignedUrl($user, $s3Key, 15, 'delete');
```

**Use Cases**:
- Direct client-side uploads to S3
- Temporary file sharing without authentication
- Reducing server bandwidth for downloads
- Time-limited access to sensitive files

### Storage Classes

S3 offers multiple storage classes for cost optimization:

```php
$provider = app(CloudStorageFactory::class)->create('amazon-s3');

// Get available storage classes
$classes = $provider->getAvailableStorageClasses();
// Returns: ['STANDARD', 'STANDARD_IA', 'GLACIER', 'DEEP_ARCHIVE']

// Set storage class for a file
$provider->setStorageClass($user, $s3Key, 'STANDARD_IA');
```

**Storage Class Comparison**:

| Class | Use Case | Retrieval Time | Cost (per GB/month) |
|-------|----------|----------------|---------------------|
| STANDARD | Frequently accessed | Immediate | $0.023 |
| STANDARD_IA | Infrequently accessed | Immediate | $0.0125 |
| GLACIER | Archive, rarely accessed | Minutes to hours | $0.004 |
| DEEP_ARCHIVE | Long-term archive | 12-48 hours | $0.00099 |

### Multipart Upload

For files larger than 50MB, S3Provider automatically uses multipart upload:

```php
// Automatically handled by uploadFile() method
$s3Key = $provider->uploadFile($user, $localPath, $targetPath, [
    'original_filename' => 'large-file.zip',
    'client_email' => 'client@example.com',
]);

// Multipart upload is used automatically for files > 50MB
// Chunk size: 5MB (configurable)
// Parallel uploads: Supported
```

**Benefits**:
- Improved upload reliability for large files
- Resume capability for failed uploads
- Better performance through parallel chunk uploads
- Reduced memory usage

### Object Metadata and Tagging

Store custom metadata and tags with S3 objects:

```php
// Upload with metadata
$s3Key = $provider->uploadFile($user, $localPath, $targetPath, [
    'original_filename' => 'document.pdf',
    'client_email' => 'client@example.com',
    'uploaded_by' => $user->id,
    'upload_timestamp' => now()->toIso8601String(),
]);

// Get file metadata
$metadata = $provider->getFileMetadata($user, $s3Key);

// Update metadata
$provider->setFileMetadata($user, $s3Key, [
    'processed' => 'true',
    'processed_at' => now()->toIso8601String(),
]);

// Add tags
$provider->addFileTags($user, $s3Key, [
    'department' => 'sales',
    'priority' => 'high',
]);

// Get tags
$tags = $provider->getFileTags($user, $s3Key);
```

### S3-Compatible Services

The S3Provider supports S3-compatible services through custom endpoint configuration:

**Cloudflare R2**:
- Zero egress fees
- S3-compatible API
- Global edge network

**Backblaze B2**:
- Lower storage costs
- S3-compatible API
- Predictable pricing

**DigitalOcean Spaces**:
- Simple pricing
- CDN included
- S3-compatible API

**Configuration Example**:
```php
// In admin UI or via CloudStorageSettingsService
$config = [
    'access_key_id' => 'your_key',
    'secret_access_key' => 'your_secret',
    'region' => 'auto', // or specific region
    'bucket' => 'my-bucket',
    'endpoint' => 'https://account-id.r2.cloudflarestorage.com',
    'use_path_style_endpoint' => true,
];
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
    
    // Provider-specific errors (Google Drive)
    case QUOTA_EXCEEDED = 'quota_exceeded';
    case FOLDER_NOT_FOUND = 'folder_not_found';
    
    // Provider-specific errors (Amazon S3)
    case BUCKET_NOT_FOUND = 'bucket_not_found';
    case BUCKET_ACCESS_DENIED = 'bucket_access_denied';
    case INVALID_BUCKET_NAME = 'invalid_bucket_name';
    case INVALID_REGION = 'invalid_region';
    case STORAGE_CLASS_NOT_SUPPORTED = 'storage_class_not_supported';
    
    // Network and system errors
    case NETWORK_ERROR = 'network_error';
    case RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';
    case UNKNOWN_ERROR = 'unknown_error';
}
```

### Error Handlers

Each provider has a dedicated error handler:

#### Google Drive Error Handler

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
                429 => CloudStorageErrorType::RATE_LIMIT_EXCEEDED,
                default => CloudStorageErrorType::UNKNOWN_ERROR,
            };
        }
        
        return CloudStorageErrorType::UNKNOWN_ERROR;
    }
}
```

#### Amazon S3 Error Handler

```php
class S3ErrorHandler implements CloudStorageErrorHandlerInterface
{
    public function classifyError(Exception $exception): CloudStorageErrorType
    {
        if ($exception instanceof S3Exception) {
            return match ($exception->getAwsErrorCode()) {
                'InvalidAccessKeyId', 'SignatureDoesNotMatch' 
                    => CloudStorageErrorType::INVALID_CREDENTIALS,
                'NoSuchBucket' 
                    => CloudStorageErrorType::BUCKET_NOT_FOUND,
                'AccessDenied' 
                    => CloudStorageErrorType::BUCKET_ACCESS_DENIED,
                'InvalidBucketName' 
                    => CloudStorageErrorType::INVALID_BUCKET_NAME,
                'NoSuchKey' 
                    => CloudStorageErrorType::FILE_NOT_FOUND,
                'RequestTimeout', 'ServiceUnavailable' 
                    => CloudStorageErrorType::NETWORK_ERROR,
                'SlowDown', 'RequestLimitExceeded' 
                    => CloudStorageErrorType::RATE_LIMIT_EXCEEDED,
                default => CloudStorageErrorType::UNKNOWN_ERROR,
            };
        }
        
        return CloudStorageErrorType::UNKNOWN_ERROR;
    }
}
```

### Error Messages

User-friendly error messages are provided through the `CloudStorageErrorMessageService`:

```php
$messageService = app(CloudStorageErrorMessageService::class);

// Get user-friendly message
$message = $messageService->getUserMessage(
    CloudStorageErrorType::BUCKET_NOT_FOUND,
    'amazon-s3'
);
// Returns: "The specified S3 bucket does not exist. Please check your configuration."

// Get admin message with more details
$message = $messageService->getAdminMessage(
    CloudStorageErrorType::BUCKET_ACCESS_DENIED,
    'amazon-s3'
);
// Returns: "S3 bucket access denied. Verify that your AWS credentials have the necessary permissions."

// Get technical message for logging
$message = $messageService->getTechnicalMessage(
    CloudStorageErrorType::INVALID_CREDENTIALS,
    'amazon-s3'
);
// Returns: "InvalidAccessKeyId: The AWS Access Key Id you provided does not exist in our records."
```

## Feature Detection

### Capability System

Providers report their capabilities through the `getCapabilities()` method:

#### Google Drive Capabilities

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
        'multipart_upload' => true,
        'max_file_size' => 5368709120, // 5GB
    ];
}
```

#### Amazon S3 Capabilities

```php
public function getCapabilities(): array
{
    return [
        'folder_creation' => false, // Virtual folders only
        'file_upload' => true,
        'file_delete' => true,
        'folder_delete' => false,
        'presigned_urls' => true,
        'storage_classes' => true,
        'multipart_upload' => true,
        'object_tagging' => true,
        'custom_metadata' => true,
        's3_compatible' => true,
        'max_file_size' => 5497558138880, // 5TB
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

# Validate specific provider
php artisan cloud-storage:validate-config google-drive
php artisan cloud-storage:validate-config amazon-s3

# Migrate configuration from environment to database
php artisan cloud-storage:migrate-config

# Test provider connections
php artisan cloud-storage:test-providers

# Test specific provider
php artisan cloud-storage:test-connection google-drive
php artisan cloud-storage:test-connection amazon-s3
```

### Health Monitoring

```bash
# Check all providers health
php artisan cloud-storage:health-check

# Check specific provider health
php artisan cloud-storage:health-check google-drive
php artisan cloud-storage:health-check amazon-s3

# Comprehensive health check with detailed output
php artisan cloud-storage:comprehensive-health-check

# Monitor providers (continuous)
php artisan cloud-storage:monitor-providers

# Check S3 bucket permissions
php artisan cloud-storage:check-s3-permissions
```

### Maintenance

```bash
# Fix health status inconsistencies
php artisan cloud-storage:fix-health-status

# Clean up old metrics
php artisan cloud-storage:cleanup-metrics

# Refresh Google Drive tokens (OAuth)
php artisan cloud-storage:refresh-tokens

# Verify S3 credentials
php artisan cloud-storage:verify-s3-credentials

# List S3 bucket contents
php artisan cloud-storage:list-s3-objects --limit=100

# Test S3 multipart upload
php artisan cloud-storage:test-s3-multipart
```

### File Operations

```bash
# Migrate files between providers
php artisan cloud-storage:migrate-files google-drive amazon-s3 --user=all

# Verify file integrity
php artisan cloud-storage:verify-files --provider=amazon-s3

# Generate presigned URLs for S3 files
php artisan cloud-storage:generate-presigned-url {file_id} --expires=60

# Update S3 storage class
php artisan cloud-storage:update-storage-class {file_id} STANDARD_IA
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

## Choosing the Right Provider

### Decision Matrix

Use this matrix to help choose the appropriate provider for your use case:

| Consideration | Google Drive | Amazon S3 |
|--------------|--------------|-----------|
| **Per-user file isolation** | ✅ Excellent | ❌ Shared bucket |
| **Setup complexity** | Medium (OAuth) | Low (API keys) |
| **Cost for small teams** | Low (free tier) | Low (pay-per-use) |
| **Cost for large scale** | High (per-user) | Low (shared) |
| **File organization** | Hierarchical folders | Flat with prefixes |
| **Integration with Google Workspace** | ✅ Native | ❌ None |
| **Advanced storage features** | Limited | ✅ Extensive |
| **S3-compatible alternatives** | N/A | ✅ Many options |
| **Presigned URLs** | ❌ No | ✅ Yes |
| **Storage lifecycle policies** | ❌ No | ✅ Yes |
| **Max file size** | 5 GB | 5 TB |
| **Egress costs** | None | Yes ($0.09/GB) |

### Use Case Recommendations

#### Choose Google Drive When:
- Users want files in their personal Google Drive
- Organization uses Google Workspace
- Per-user file isolation is required
- Hierarchical folder organization is important
- Users need to access files through Google Drive interface
- Small to medium team size
- Budget allows per-user licensing

#### Choose Amazon S3 When:
- Centralized storage is preferred
- Cost optimization is important at scale
- Advanced storage features are needed (lifecycle, storage classes)
- S3-compatible service is desired (R2, B2, etc.)
- Presigned URLs for direct access are needed
- Large file support (>5GB) is required
- No per-user isolation is needed
- Technical team can manage AWS infrastructure

#### Hybrid Approach:
The system supports using both providers simultaneously:
- Admin configures both providers
- Users select their preferred provider
- Different user groups can use different providers
- Files can be migrated between providers if needed

### Migration Between Providers

To migrate from one provider to another:

1. **Configure new provider** in admin settings
2. **Test connection** to ensure it works
3. **Update default provider** in configuration
4. **Existing files remain** in original provider
5. **New uploads** go to new provider
6. **Optional**: Migrate existing files using migration command

```bash
# Migrate files from Google Drive to S3
php artisan cloud-storage:migrate-files google-drive amazon-s3 --user=all

# Migrate specific user's files
php artisan cloud-storage:migrate-files google-drive amazon-s3 --user=123
```

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

## Troubleshooting

### Google Drive Issues

#### Token Expired
**Symptom**: "Token expired" error when uploading files

**Solution**:
1. Navigate to Cloud Storage settings
2. Click "Reconnect Google Drive"
3. Complete OAuth flow again
4. Verify connection with health check

#### Folder Not Found
**Symptom**: "Root folder not found" error

**Solution**:
1. Check if root folder still exists in Google Drive
2. Update root folder ID in settings
3. Or remove root folder setting to use Drive root

#### Quota Exceeded
**Symptom**: "Quota exceeded" error during upload

**Solution**:
1. Check Google Drive storage quota
2. Delete unnecessary files
3. Upgrade Google Workspace plan
4. Consider switching to S3 for unlimited storage

### Amazon S3 Issues

#### Invalid Credentials
**Symptom**: "InvalidAccessKeyId" or "SignatureDoesNotMatch" error

**Solution**:
1. Verify Access Key ID format (20 uppercase alphanumeric)
2. Verify Secret Access Key (40 characters)
3. Check for extra spaces or line breaks
4. Regenerate access keys in AWS IAM if needed
5. Update credentials in admin settings

#### Bucket Not Found
**Symptom**: "NoSuchBucket" error

**Solution**:
1. Verify bucket name is correct (case-sensitive)
2. Check bucket exists in specified region
3. Verify region setting matches bucket region
4. Create bucket if it doesn't exist:
   ```bash
   aws s3 mb s3://my-bucket --region us-east-1
   ```

#### Access Denied
**Symptom**: "AccessDenied" error when uploading or deleting

**Solution**:
1. Check IAM policy includes required permissions:
   - `s3:PutObject` for uploads
   - `s3:GetObject` for downloads
   - `s3:DeleteObject` for deletions
   - `s3:ListBucket` for health checks
2. Verify bucket policy doesn't deny access
3. Check bucket is not in different AWS account
4. Update IAM policy with correct permissions

#### S3-Compatible Service Issues
**Symptom**: Connection fails with custom endpoint

**Solution**:
1. Verify endpoint URL is correct
2. Enable "Use Path Style Endpoint" setting
3. Check service-specific authentication requirements
4. Verify region setting (some services use 'auto' or 'us-east-1')
5. Test with AWS CLI to verify credentials:
   ```bash
   aws s3 ls s3://bucket-name --endpoint-url=https://endpoint.com
   ```

#### Multipart Upload Failures
**Symptom**: Large file uploads fail or timeout

**Solution**:
1. Check network stability
2. Increase PHP timeout settings
3. Verify bucket CORS configuration if uploading from browser
4. Check for incomplete multipart uploads:
   ```bash
   aws s3api list-multipart-uploads --bucket my-bucket
   ```
5. Clean up incomplete uploads:
   ```bash
   aws s3api abort-multipart-upload --bucket my-bucket --key file-key --upload-id upload-id
   ```

### General Issues

#### Health Check Fails
**Symptom**: Provider shows "Not Connected" status

**Solution**:
1. Run manual health check:
   ```bash
   php artisan cloud-storage:health-check provider-name
   ```
2. Check error logs for specific error
3. Verify credentials are correct
4. Test network connectivity to provider
5. Check firewall rules allow outbound connections

#### File Upload Stuck in Queue
**Symptom**: Files remain in "pending" status

**Solution**:
1. Check queue worker is running:
   ```bash
   php artisan queue:work
   ```
2. Check failed jobs table:
   ```bash
   php artisan queue:failed
   ```
3. Retry failed jobs:
   ```bash
   php artisan queue:retry all
   ```
4. Check provider health status
5. Review error logs for specific failures

## Additional Resources

### Documentation
- [Amazon S3 Setup Guide](./cloud-storage/amazon-s3-setup-guide.md)
- [Google Drive Configuration Guide](./cloud-storage/google-drive-configuration-guide.md)
- [Implementing New Providers](./implementing-new-cloud-storage-providers.md)
- [Cloud Storage Configuration Guide](./cloud-storage-configuration-guide.md)

### API References
- [AWS S3 API Documentation](https://docs.aws.amazon.com/s3/)
- [Google Drive API Documentation](https://developers.google.com/drive)
- [Cloudflare R2 Documentation](https://developers.cloudflare.com/r2/)
- [Backblaze B2 Documentation](https://www.backblaze.com/b2/docs/)

### Support
- Check application logs: `storage/logs/laravel.log`
- Run diagnostics: `php artisan cloud-storage:diagnose`
- Review health status: `/admin/cloud-storage/monitoring`

This documentation provides a comprehensive guide to using and extending the Cloud Storage Provider System. For specific implementation details, refer to the migration guides and API documentation.