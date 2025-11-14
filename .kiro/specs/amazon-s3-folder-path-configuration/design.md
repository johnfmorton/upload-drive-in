# Design Document

## Overview

This design document outlines the implementation approach for adding configurable folder path (S3 key prefix) support to the Amazon S3 storage provider. The solution will allow administrators to define a base folder structure within their S3 bucket where all uploaded files will be organized. The implementation supports both environment variable and database configuration methods, with environment variables taking precedence.

## Architecture

### Component Structure

The implementation follows the existing Laravel architecture with modifications to:

```
app/
├── Services/
│   └── S3Provider.php (modify generateS3Key method, add folder path handling)
├── Http/
│   └── Controllers/
│       └── Admin/
│           └── CloudStorageController.php (add folder path to configuration)
├── Helpers/
│   └── CloudStorageConfigHelper.php (add folder path validation)
config/
└── cloud-storage.php (add folder_path configuration)
resources/
└── views/
    └── admin/
        └── cloud-storage/
            └── amazon-s3/
                └── configuration.blade.php (add folder path UI field)
```

### Data Flow

1. **Configuration Loading**: System checks for AWS_FOLDER_PATH environment variable, falls back to database
2. **Provider Initialization**: S3Provider loads folder path during initialization
3. **Key Generation**: generateS3Key method prepends folder path to all S3 object keys
4. **Upload Operation**: Files uploaded with full path: {folder_path}/{client_email}/{filename}
5. **UI Display**: Admin form shows folder path field with read-only state for environment config

## Components and Interfaces

### Configuration Layer

**File**: `config/cloud-storage.php`

Add folder_path to the amazon-s3 provider configuration:

```php
'amazon-s3' => [
    // ... existing config ...
    'config' => [
        'access_key_id' => env('AWS_ACCESS_KEY_ID'),
        'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('AWS_BUCKET'),
        'endpoint' => env('AWS_ENDPOINT'),
        'folder_path' => env('AWS_FOLDER_PATH', ''), // New configuration
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'version' => 'latest',
    ],
    // ... rest of config ...
],
```


### Service Layer

**File**: `app/Services/S3Provider.php`

#### Modified Property

Add folder path to the config property:

```php
private array $config = []; // Will include 'folder_path' key
```

#### Modified Method: `generateS3Key()`

Update to incorporate folder path:

```php
/**
 * Generate S3 key for flat storage model with optional folder path prefix
 *
 * @param string $targetPath Client email or folder path
 * @param string $filename Original filename
 * @return string S3 object key
 */
private function generateS3Key(string $targetPath, string $filename): string
{
    // Clean the target path (client email) to be S3-safe
    $cleanPath = $this->sanitizeS3Key($targetPath);
    
    // Generate unique filename to avoid conflicts
    $timestamp = now()->format('Y-m-d_H-i-s');
    $randomSuffix = Str::random(8);
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $baseName = pathinfo($filename, PATHINFO_FILENAME);
    
    $uniqueFilename = $this->sanitizeS3Key($baseName) . '_' . $timestamp . '_' . $randomSuffix;
    if ($extension) {
        $uniqueFilename .= '.' . $extension;
    }

    // Prepend folder path if configured
    $folderPath = $this->getFolderPath();
    if (!empty($folderPath)) {
        return $folderPath . '/' . $cleanPath . '/' . $uniqueFilename;
    }

    return $cleanPath . '/' . $uniqueFilename;
}
```


#### New Metho
d: `getFolderPath()`

```php
/**
 * Get the configured folder path for S3 uploads
 *
 * @return string The folder path (empty string if not configured)
 */
private function getFolderPath(): string
{
    $folderPath = $this->config['folder_path'] ?? '';
    
    // Trim slashes and whitespace
    $folderPath = trim($folderPath, "/ \t\n\r\0\x0B");
    
    return $folderPath;
}
```

#### New Method: `validateFolderPath()`

```php
/**
 * Validate folder path format for S3 compatibility
 *
 * @param string $folderPath The folder path to validate
 * @return array Array of validation errors (empty if valid)
 */
private function validateFolderPathFormat(string $folderPath): array
{
    $errors = [];
    
    // Empty is valid (means bucket root)
    if (empty($folderPath)) {
        return $errors;
    }
    
    // Check for invalid characters (only allow alphanumeric, hyphens, underscores, slashes, periods)
    if (!preg_match('/^[a-zA-Z0-9\-_\/\.]+$/', $folderPath)) {
        $errors[] = 'Folder path contains invalid characters. Only alphanumeric, hyphens, underscores, slashes, and periods are allowed.';
    }
    
    // Check for consecutive slashes
    if (strpos($folderPath, '//') !== false) {
        $errors[] = 'Folder path cannot contain consecutive slashes.';
    }
    
    // Check for leading slash
    if (str_starts_with($folderPath, '/')) {
        $errors[] = 'Folder path cannot start with a slash.';
    }
    
    // Check for trailing slash
    if (str_ends_with($folderPath, '/')) {
        $errors[] = 'Folder path cannot end with a slash.';
    }
    
    return $errors;
}
```


#### Modified Method: `validateConfiguration()`

Update to include folder path validation:

```php
public function validateConfiguration(array $config): array
{
    $errors = [];

    // ... existing validation ...

    // Validate folder path if provided
    if (isset($config['folder_path']) && !empty($config['folder_path'])) {
        $folderPathErrors = $this->validateFolderPathFormat($config['folder_path']);
        $errors = array_merge($errors, $folderPathErrors);
    }

    return $errors;
}
```

#### Modified Method: `initialize()`

Update logging to include folder path:

```php
public function initialize(array $config): void
{
    $startTime = microtime(true);
    
    Log::info('S3Provider: Configuration initialization started', [
        'provider' => self::PROVIDER_NAME,
        'region' => $config['region'] ?? 'not_set',
        'bucket' => $config['bucket'] ?? 'not_set',
        'folder_path' => $config['folder_path'] ?? 'not_set', // Add this
        'has_custom_endpoint' => !empty($config['endpoint']),
    ]);

    // ... rest of initialization ...
}
```


### Controller Layer

**File**: `app/Http/Controllers/Admin/CloudStorageController.php`

#### Modified Method: `getS3EnvironmentSettings()`

Add folder_path detection:

```php
private function getS3EnvironmentSettings(): array
{
    return [
        'access_key_id' => !empty(env('AWS_ACCESS_KEY_ID')),
        'secret_access_key' => !empty(env('AWS_SECRET_ACCESS_KEY')),
        'region' => !empty(env('AWS_DEFAULT_REGION')),
        'bucket' => !empty(env('AWS_BUCKET')),
        'endpoint' => !empty(env('AWS_ENDPOINT')),
        'folder_path' => !empty(env('AWS_FOLDER_PATH')), // Add this
    ];
}
```

#### Modified Method: `storeS3Configuration()`

Add folder_path to stored configuration:

```php
public function storeS3Configuration(Request $request)
{
    $validated = $request->validate([
        'aws_access_key_id' => 'required|string|size:20|regex:/^[A-Z0-9]{20}$/',
        'aws_secret_access_key' => 'nullable|string|size:40',
        'aws_region' => 'required|string',
        'aws_bucket' => 'required|string|regex:/^[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]$/',
        'aws_endpoint' => 'nullable|url',
        'aws_folder_path' => 'nullable|string|regex:/^[a-zA-Z0-9\-_\/\.]+$/', // Add this
    ]);

    // ... existing code to store configuration ...
    
    // Store folder_path if provided and not from environment
    if (!env('AWS_FOLDER_PATH') && isset($validated['aws_folder_path'])) {
        CloudStorageSetting::updateOrCreate(
            ['provider' => 'amazon-s3', 'key' => 'folder_path'],
            ['value' => trim($validated['aws_folder_path'], "/ \t\n\r\0\x0B")]
        );
    }

    // ... rest of method ...
}
```


### Helper Layer

**File**: `app/Helpers/CloudStorageConfigHelper.php`

#### New Method: `getS3FolderPath()`

```php
/**
 * Get the S3 folder path from environment or database
 *
 * @return string The folder path (empty string if not configured)
 */
public static function getS3FolderPath(): string
{
    // Check environment variable first
    $envFolderPath = env('AWS_FOLDER_PATH');
    if (!empty($envFolderPath)) {
        return trim($envFolderPath, "/ \t\n\r\0\x0B");
    }

    // Fall back to database
    $setting = CloudStorageSetting::where('provider', 'amazon-s3')
        ->where('key', 'folder_path')
        ->first();

    return $setting ? trim($setting->value, "/ \t\n\r\0\x0B") : '';
}
```

#### New Method: `generateExampleS3Key()`

```php
/**
 * Generate an example S3 key for display purposes
 *
 * @param string $folderPath The folder path to use
 * @return string Example S3 key
 */
public static function generateExampleS3Key(string $folderPath): string
{
    $folderPath = trim($folderPath, "/ \t\n\r\0\x0B");
    $exampleEmail = 'client@example.com';
    $exampleFilename = 'document_2024-01-15_abc123.pdf';
    
    if (!empty($folderPath)) {
        return $folderPath . '/' . $exampleEmail . '/' . $exampleFilename;
    }
    
    return $exampleEmail . '/' . $exampleFilename;
}
```


### View Layer

**File**: `resources/views/admin/cloud-storage/amazon-s3/configuration.blade.php`

#### New Form Field: Folder Path

Add after the bucket name field:

```blade
<!-- Folder Path Field -->
<div>
    <x-label for="aws_folder_path" value="Folder Path (Optional)" />
    @if($s3EnvSettings['folder_path'])
        <x-input id="aws_folder_path" 
                 type="text" 
                 class="mt-1 block w-full bg-gray-100" 
                 :value="env('AWS_FOLDER_PATH')" 
                 readonly />
        <p class="mt-1 text-sm text-gray-500">This value is configured via environment variables.</p>
    @else
        <x-input id="aws_folder_path" 
                 name="aws_folder_path" 
                 type="text" 
                 class="mt-1 block w-full"
                 :value="old('aws_folder_path', $s3Config['folder_path'] ?? '')"
                 placeholder="uploads/client-files"
                 pattern="[a-zA-Z0-9\-_\/\.]+"
                 x-model="formData.folder_path"
                 @input="validateFolderPath" />
        <p class="mt-1 text-xs text-gray-500">
            Optional base folder path within your bucket. Files will be uploaded to: 
            <code class="text-xs bg-gray-100 px-1 py-0.5 rounded" x-text="exampleKey"></code>
        </p>
        <p class="mt-1 text-xs text-gray-500">
            Leave blank to upload to bucket root. Do not include leading or trailing slashes.
        </p>
    @endif
    <x-input-error for="aws_folder_path" class="mt-2" />
</div>
```


#### Alpine.js Updates

Update the `s3ConfigurationHandler()` function:

```javascript
function s3ConfigurationHandler() {
    return {
        formData: {
            access_key_id: @json(old('aws_access_key_id', $s3Config['access_key_id'] ?? '')),
            secret_access_key: '',
            region: @json(old('aws_region', $s3Config['region'] ?? '')),
            bucket: @json(old('aws_bucket', $s3Config['bucket'] ?? '')),
            endpoint: @json(old('aws_endpoint', $s3Config['endpoint'] ?? '')),
            folder_path: @json(old('aws_folder_path', $s3Config['folder_path'] ?? '')) // Add this
        },
        envSettings: @json($s3EnvSettings),
        errors: {},
        isTesting: false,
        isSaving: false,
        testResult: null,
        isFormValid: false,
        exampleKey: '', // Add this for dynamic example

        init() {
            // ... existing initialization ...
            
            if (this.envSettings.folder_path) {
                this.formData.folder_path = @json(env('AWS_FOLDER_PATH'));
            }
            
            this.updateExampleKey(); // Add this
            this.validateForm();
        },

        validateFolderPath() {
            const folderPath = this.formData.folder_path;
            
            // Empty is valid
            if (!folderPath) {
                delete this.errors.folder_path;
                this.updateExampleKey();
                this.validateForm();
                return;
            }
            
            // Check for invalid characters
            if (!/^[a-zA-Z0-9\-_\/\.]+$/.test(folderPath)) {
                this.errors.folder_path = 'Only alphanumeric, hyphens, underscores, slashes, and periods allowed';
                this.validateForm();
                return;
            }
            
            // Check for consecutive slashes
            if (folderPath.includes('//')) {
                this.errors.folder_path = 'Cannot contain consecutive slashes';
                this.validateForm();
                return;
            }
            
            // Check for leading/trailing slashes
            if (folderPath.startsWith('/') || folderPath.endsWith('/')) {
                this.errors.folder_path = 'Cannot start or end with slashes';
                this.validateForm();
                return;
            }
            
            delete this.errors.folder_path;
            this.updateExampleKey();
            this.validateForm();
        },

        updateExampleKey() {
            const folderPath = this.formData.folder_path?.trim() || '';
            const exampleEmail = 'client@example.com';
            const exampleFilename = 'document_2024-01-15_abc123.pdf';
            
            if (folderPath) {
                this.exampleKey = `${folderPath}/${exampleEmail}/${exampleFilename}`;
            } else {
                this.exampleKey = `${exampleEmail}/${exampleFilename}`;
            }
        },

        // ... rest of existing methods ...
    };
}
```


## Data Models

### CloudStorageSetting Model

No changes required. The existing model will store the folder_path configuration:

```php
// Example database record
[
    'provider' => 'amazon-s3',
    'key' => 'folder_path',
    'value' => 'uploads/client-files',
    'user_id' => null, // System-level setting
]
```

## Error Handling

### Validation Errors

**Folder Path Format Errors**:
- Invalid characters: "Folder path contains invalid characters. Only alphanumeric, hyphens, underscores, slashes, and periods are allowed."
- Consecutive slashes: "Folder path cannot contain consecutive slashes."
- Leading slash: "Folder path cannot start with a slash."
- Trailing slash: "Folder path cannot end with a slash."

### Upload Errors

**Folder Path Related Errors**:
- If folder path causes permission issues, error message should include the full attempted path
- Log entries should include the effective folder path for debugging

### Configuration Errors

**Test Connection Errors**:
- Test connection should verify write permissions to the folder path location
- Error messages should indicate if the issue is with the folder path specifically

## Testing Strategy

### Manual Testing Checklist

1. **Environment Variable Configuration**:
   - Set AWS_FOLDER_PATH in .env file
   - Verify field displays as read-only in admin form
   - Verify uploads use the environment-configured path
   - Verify example key displays correctly

2. **Database Configuration**:
   - Remove AWS_FOLDER_PATH from .env
   - Configure folder path via admin form
   - Verify folder path is saved to database
   - Verify uploads use the database-configured path

3. **Validation Testing**:
   - Test with invalid characters (spaces, special chars)
   - Test with consecutive slashes
   - Test with leading/trailing slashes
   - Verify error messages display correctly

4. **Upload Testing**:
   - Upload file with folder path configured
   - Verify S3 key includes folder path
   - Verify file is accessible at expected location
   - Test with empty folder path (bucket root)

5. **Mixed Configuration**:
   - Test with some settings in environment, folder path in database
   - Test with all settings in environment including folder path
   - Verify precedence rules work correctly


### Automated Testing

**Unit Tests** (`tests/Unit/Services/S3ProviderTest.php`):

```php
public function test_generates_s3_key_without_folder_path()
{
    // Test that keys are generated correctly when no folder path is configured
}

public function test_generates_s3_key_with_folder_path()
{
    // Test that folder path is correctly prepended to S3 keys
}

public function test_validates_folder_path_format()
{
    // Test folder path validation rules
}

public function test_trims_folder_path_slashes()
{
    // Test that leading/trailing slashes are removed
}
```

**Feature Tests** (`tests/Feature/Admin/CloudStorageS3FolderPathTest.php`):

```php
public function test_can_configure_folder_path_via_admin_form()
{
    // Test saving folder path through admin interface
}

public function test_folder_path_from_environment_takes_precedence()
{
    // Test that environment variable overrides database setting
}

public function test_folder_path_validation_in_form()
{
    // Test form validation for folder path
}

public function test_uploads_use_configured_folder_path()
{
    // Test that actual uploads include the folder path in S3 key
}
```

## Visual Design

### Form Field Styling

- **Label**: "Folder Path (Optional)"
- **Placeholder**: "uploads/client-files"
- **Helper Text**: Dynamic example showing full S3 key format
- **Read-only State**: Gray background when configured via environment
- **Validation**: Real-time validation with error messages below field

### Example Key Display

```
Files will be uploaded to: uploads/client-files/client@example.com/document_2024-01-15_abc123.pdf
```

- Displayed in monospace font
- Gray background
- Updates dynamically as user types

## Implementation Notes

### Key Generation Logic

The folder path will be prepended to the existing key structure:

**Without folder path**:
```
{client_email}/{filename}
Example: client@example.com/document_2024-01-15_abc123.pdf
```

**With folder path**:
```
{folder_path}/{client_email}/{filename}
Example: uploads/client-files/client@example.com/document_2024-01-15_abc123.pdf
```

### Backward Compatibility

- Existing uploads without folder path will continue to work
- New uploads will use the configured folder path
- Changing folder path will not affect existing files
- Empty folder path is valid and means bucket root

### Security Considerations

- Folder path validation prevents path traversal attacks
- Only alphanumeric and safe characters allowed
- No leading/trailing slashes to prevent unexpected behavior
- Environment variable configuration allows infrastructure-level control

### Performance Considerations

- Folder path is loaded once during provider initialization
- No additional S3 API calls required
- Minimal impact on upload performance
- Folder path included in logging for debugging

## Migration Path

### For Existing Installations

1. Add AWS_FOLDER_PATH to .env.example
2. Update configuration documentation
3. No database migration required (uses existing cloud_storage_settings table)
4. Existing files remain in current locations
5. New uploads use configured folder path

### Configuration Examples

**.env Configuration**:
```env
AWS_FOLDER_PATH=uploads/client-files
```

**Database Configuration**:
```sql
INSERT INTO cloud_storage_settings (provider, key, value, user_id)
VALUES ('amazon-s3', 'folder_path', 'uploads/client-files', NULL);
```

## Documentation Updates

### User Documentation

- Add folder path configuration to S3 setup guide
- Explain folder path vs bucket organization
- Provide examples of common folder structures
- Document environment variable precedence

### Developer Documentation

- Update S3Provider class documentation
- Document generateS3Key method changes
- Add folder path to configuration reference
- Include examples in API documentation

