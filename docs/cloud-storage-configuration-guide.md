# Cloud Storage Configuration Guide

This guide explains the enhanced cloud storage configuration system that supports multiple providers with provider-specific features, authentication types, and storage models.

## Overview

The new configuration system provides:
- **Multiple Provider Support**: Google Drive, Amazon S3, Azure Blob Storage, Microsoft Teams, and Dropbox
- **Provider-Specific Features**: Each provider can define its own capabilities and limitations
- **Flexible Authentication**: Support for OAuth, API keys, connection strings, and service accounts
- **Storage Model Abstraction**: Handle both hierarchical (folder-based) and flat (key-based) storage
- **Automatic Fallback**: Configurable provider fallback when primary providers fail
- **Health Monitoring**: Built-in health checks and status monitoring
- **Migration Support**: Tools to migrate from legacy configurations

## Configuration Structure

The configuration is located in `config/cloud-storage.php` and follows this structure:

```php
return [
    'default' => 'google-drive',
    'providers' => [
        'provider-name' => [
            'driver' => 'provider-driver',
            'class' => ProviderClass::class,
            'error_handler' => ErrorHandlerClass::class,
            'auth_type' => 'oauth|api_key|connection_string|service_account',
            'storage_model' => 'hierarchical|flat|hybrid',
            'enabled' => true|false,
            'config' => [
                // Provider-specific configuration
            ],
            'features' => [
                // Provider capabilities
            ],
            'limits' => [
                // Provider limitations
            ],
        ],
    ],
    'feature_detection' => [...],
    'fallback' => [...],
    'health_check' => [...],
    'migration' => [...],
    'logging' => [...],
    'cache' => [...],
];
```

## Supported Providers

### Google Drive

**Authentication Type**: OAuth 2.0  
**Storage Model**: Hierarchical (folder-based)  
**Status**: Fully implemented

```env
GOOGLE_DRIVE_ENABLED=true
GOOGLE_DRIVE_CLIENT_ID=your_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret
```

**Features**:
- Folder creation and deletion
- File upload, download, and deletion
- File sharing and permissions
- Batch operations
- Resumable uploads
- Version control
- Metadata support

### Amazon S3

**Authentication Type**: API Key (Access Key + Secret Key)  
**Storage Model**: Flat (key-based with prefixes)  
**Status**: Configuration ready, implementation pending

```env
AWS_S3_ENABLED=true
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket_name
```

**Features**:
- File upload, download, and deletion
- Presigned URLs for secure access
- Multiple storage classes
- Server-side encryption
- Cross-region replication
- Batch operations
- Resumable uploads

### Azure Blob Storage

**Authentication Type**: Connection String or Account Key  
**Storage Model**: Flat (container-based)  
**Status**: Configuration ready, implementation pending

```env
AZURE_STORAGE_ENABLED=true
AZURE_STORAGE_CONNECTION_STRING=your_connection_string
AZURE_STORAGE_CONTAINER=uploads
```

**Features**:
- File upload, download, and deletion
- Access tiers (Hot, Cool, Archive)
- Server-side encryption
- Immutable storage
- Batch operations
- Resumable uploads

### Microsoft Teams / SharePoint

**Authentication Type**: OAuth 2.0 with Microsoft Graph  
**Storage Model**: Hierarchical (folder-based)  
**Status**: Configuration ready, implementation pending

```env
MICROSOFT_TEAMS_ENABLED=true
MICROSOFT_TEAMS_CLIENT_ID=your_client_id
MICROSOFT_TEAMS_CLIENT_SECRET=your_client_secret
MICROSOFT_TEAMS_TENANT_ID=your_tenant_id
```

### Dropbox

**Authentication Type**: OAuth 2.0  
**Storage Model**: Hierarchical (folder-based)  
**Status**: Configuration ready, implementation pending

```env
DROPBOX_ENABLED=true
DROPBOX_CLIENT_ID=your_app_key
DROPBOX_CLIENT_SECRET=your_app_secret
```

## Environment Configuration

### Basic Setup

1. Copy the example environment file:
   ```bash
   cp config/cloud-storage.env.example .env.cloud-storage
   ```

2. Add the relevant sections to your `.env` file

3. Configure your chosen providers with the appropriate credentials

### Provider-Specific Setup

#### Google Drive Setup

1. Create a project in Google Cloud Console
2. Enable the Google Drive API
3. Create OAuth 2.0 credentials
4. Set the redirect URI to: `https://yourdomain.com/admin/cloud-storage/google-drive/callback`
5. Add the credentials to your `.env` file

#### Amazon S3 Setup

1. Create an AWS account and S3 bucket
2. Create an IAM user with S3 permissions
3. Generate access keys for the IAM user
4. Add the credentials to your `.env` file

#### Azure Blob Storage Setup

1. Create an Azure Storage Account
2. Create a container for uploads
3. Get the connection string from the Azure portal
4. Add the connection string to your `.env` file

## Migration from Legacy Configuration

If you're upgrading from the previous configuration format, use the migration command:

```bash
# Create a backup and migrate
php artisan cloud-storage:migrate-config --backup

# Validate configuration only
php artisan cloud-storage:migrate-config --validate
```

The migration tool will:
- Backup your current configuration
- Migrate environment variables to the new format
- Validate all provider configurations
- Provide detailed feedback on any issues

## Testing Configuration

Test your provider configurations:

```bash
# Test all enabled providers
php artisan cloud-storage:test --enabled-only

# Test a specific provider
php artisan cloud-storage:test google-drive

# Test all providers (including disabled)
php artisan cloud-storage:test --all
```

## Configuration Validation

The system includes comprehensive validation:

### Automatic Validation
- Configuration is validated at application startup
- Invalid configurations are logged with specific error messages
- Health checks verify provider connectivity

### Manual Validation
```bash
# Validate all configurations
php artisan cloud-storage:migrate-config --validate

# Test provider connectivity
php artisan cloud-storage:test
```

### Common Validation Errors

1. **Missing Required Fields**: Ensure all required configuration fields are present
2. **Invalid Class References**: Verify provider classes exist and implement the correct interface
3. **Authentication Issues**: Check credentials and permissions
4. **Network Connectivity**: Ensure the application can reach provider APIs

## Feature Detection

The system automatically detects provider capabilities:

```php
$provider = app(CloudStorageManager::class)->getProvider('google-drive');

// Check if provider supports a feature
if ($provider->supportsFeature('folder_creation')) {
    // Create folders
}

// Get all provider capabilities
$capabilities = $provider->getCapabilities();
```

## Fallback Configuration

Configure automatic fallback when providers fail:

```php
'fallback' => [
    'enabled' => true,
    'order' => [
        'google-drive',
        'amazon-s3',
        'azure-blob',
    ],
    'max_retries' => 3,
    'retry_delay' => 5, // seconds
],
```

## Health Monitoring

The system includes built-in health monitoring:

```bash
# Check provider health
php artisan cloud-storage:check-health

# Fix health status inconsistencies
php artisan cloud-storage:fix-health-status
```

Health checks run automatically and can be configured:

```php
'health_check' => [
    'enabled' => true,
    'interval' => 300, // 5 minutes
    'timeout' => 30,   // 30 seconds
    'failure_threshold' => 3,
    'recovery_threshold' => 2,
],
```

## Logging and Monitoring

Configure comprehensive logging:

```php
'logging' => [
    'enabled' => true,
    'level' => 'info',
    'channels' => [
        'operations' => 'cloud-storage',
        'errors' => 'cloud-storage-errors',
        'performance' => 'cloud-storage-performance',
    ],
    'log_uploads' => true,
    'log_deletions' => true,
    'log_authentication' => true,
    'log_configuration_changes' => true,
],
```

## Troubleshooting

### Common Issues

1. **Provider Not Found**: Check that the provider is enabled and properly configured
2. **Authentication Failures**: Verify credentials and permissions
3. **Feature Not Supported**: Check provider capabilities before using features
4. **Network Issues**: Verify connectivity to provider APIs

### Debug Commands

```bash
# Test specific provider
php artisan cloud-storage:test google-drive

# Check configuration
php artisan cloud-storage:migrate-config --validate

# View provider health
php artisan cloud-storage:check-health

# View logs
php artisan pail --filter=cloud-storage
```

### Getting Help

1. Check the logs for detailed error messages
2. Run the test command to identify configuration issues
3. Use the validation command to check your setup
4. Review the provider-specific documentation

## Best Practices

1. **Always Test**: Use the test commands to verify your configuration
2. **Enable Fallback**: Configure multiple providers for redundancy
3. **Monitor Health**: Enable health checks and monitoring
4. **Secure Credentials**: Use environment variables for sensitive data
5. **Regular Backups**: Backup your configuration before making changes
6. **Update Regularly**: Keep provider credentials and configurations up to date

## Next Steps

1. Configure your preferred providers
2. Test the configuration using the provided commands
3. Implement provider-specific features as needed
4. Set up monitoring and health checks
5. Configure fallback providers for redundancy

For more detailed information about specific providers or advanced configuration options, refer to the provider-specific documentation or the source code in `app/Services/`.