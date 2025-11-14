# Design Document

## Overview

This design document outlines the implementation approach for aligning the Amazon S3 configuration UI with the established Google Drive configuration patterns. The solution will detect environment variable configuration, display appropriate UI states, mask sensitive credentials, and ensure proper form submission handling for both environment-based and database-based configurations.

## Architecture

### Component Structure

The implementation follows the existing Laravel Blade component architecture:

```
resources/views/admin/cloud-storage/
├── index.blade.php (main container)
└── amazon-s3/
    └── configuration.blade.php (S3 configuration partial)
```

### Data Flow

1. **Configuration Detection**: Controller passes environment variable detection flags to the view
2. **UI Rendering**: Blade template conditionally renders read-only or editable fields
3. **Form Submission**: Controller validates and stores credentials based on configuration source
4. **Feedback**: Success/error messages displayed to user

## Components and Interfaces

### Controller Layer

**File**: `app/Http/Controllers/Admin/CloudStorageController.php`

#### New Method: `getS3EnvironmentSettings()`

```php
/**
 * Check which S3 settings are defined in environment variables
 *
 * @return array
 */
private function getS3EnvironmentSettings(): array
{
    return [
        'access_key_id' => !empty(env('AWS_ACCESS_KEY_ID')),
        'secret_access_key' => !empty(env('AWS_SECRET_ACCESS_KEY')),
        'region' => !empty(env('AWS_DEFAULT_REGION')),
        'bucket' => !empty(env('AWS_BUCKET')),
        'endpoint' => !empty(env('AWS_ENDPOINT')),
    ];
}
```

#### Modified Method: `index()`

Update the existing `index()` method to pass S3 environment settings to the view:

```php
public function index()
{
    // ... existing code ...
    
    $s3EnvSettings = $this->getS3EnvironmentSettings();
    
    return view('admin.cloud-storage.index', compact(
        'currentFolderId', 
        'currentFolderName',
        'googleDriveEnvSettings',
        's3EnvSettings' // Add this
    ));
}
```

### View Layer

**File**: `resources/views/admin/cloud-storage/amazon-s3/configuration.blade.php`

#### Environment Configuration Banner

Add an information banner similar to Google Drive's implementation:

```blade
@if($s3EnvSettings['access_key_id'] || $s3EnvSettings['secret_access_key'] || 
    $s3EnvSettings['region'] || $s3EnvSettings['bucket'] || $s3EnvSettings['endpoint'])
    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Environment Configuration</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Some Amazon S3 settings are configured via environment variables and cannot be edited here:</p>
                    <ul class="list-disc list-inside mt-1">
                        @if($s3EnvSettings['access_key_id'])
                            <li>AWS Access Key ID</li>
                        @endif
                        @if($s3EnvSettings['secret_access_key'])
                            <li>AWS Secret Access Key</li>
                        @endif
                        @if($s3EnvSettings['region'])
                            <li>AWS Region</li>
                        @endif
                        @if($s3EnvSettings['bucket'])
                            <li>S3 Bucket Name</li>
                        @endif
                        @if($s3EnvSettings['endpoint'])
                            <li>Custom Endpoint</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endif
```

#### Conditional Field Rendering

Each form field will be conditionally rendered as read-only or editable:

**Access Key ID Field**:
```blade
<div>
    <x-label for="aws_access_key_id" value="AWS Access Key ID" />
    @if($s3EnvSettings['access_key_id'])
        <x-input id="aws_access_key_id" 
                 type="text" 
                 class="mt-1 block w-full bg-gray-100" 
                 :value="env('AWS_ACCESS_KEY_ID')" 
                 readonly />
        <p class="mt-1 text-sm text-gray-500">This value is configured via environment variables.</p>
    @else
        <x-input id="aws_access_key_id" 
                 name="aws_access_key_id" 
                 type="text" 
                 class="mt-1 block w-full"
                 :value="old('aws_access_key_id', $s3Config['access_key_id'] ?? '')"
                 placeholder="AKIAIOSFODNN7EXAMPLE"
                 maxlength="20"
                 pattern="[A-Z0-9]{20}"
                 x-model="formData.access_key_id"
                 @input="validateAccessKeyId"
                 required />
        <p class="mt-1 text-xs text-gray-500">
            Must be exactly 20 uppercase alphanumeric characters
        </p>
    @endif
    <x-input-error for="aws_access_key_id" class="mt-2" />
</div>
```

**Secret Access Key Field** (with masking):
```blade
<div>
    <x-label for="aws_secret_access_key" value="AWS Secret Access Key" />
    @if($s3EnvSettings['secret_access_key'])
        <x-input id="aws_secret_access_key" 
                 type="password" 
                 class="mt-1 block w-full bg-gray-100" 
                 value="••••••••••••••••••••••••••••••••••••••••" 
                 readonly />
        <p class="mt-1 text-sm text-gray-500">This value is configured via environment variables.</p>
    @else
        <x-input id="aws_secret_access_key" 
                 name="aws_secret_access_key" 
                 type="password" 
                 class="mt-1 block w-full"
                 placeholder="{{ !empty($s3Config['secret_access_key']) ? '••••••••••••••••••••••••••••••••••••••••' : 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY' }}"
                 minlength="40"
                 maxlength="40"
                 x-model="formData.secret_access_key"
                 @input="validateSecretAccessKey"
                 {{ empty($s3Config['secret_access_key']) ? 'required' : '' }} />
        <p class="mt-1 text-xs text-gray-500">
            Must be exactly 40 characters. Leave blank to keep existing secret key.
        </p>
    @endif
    <x-input-error for="aws_secret_access_key" class="mt-2" />
</div>
```

**Region Field**:
```blade
<div>
    <x-label for="aws_region" value="AWS Region" />
    @if($s3EnvSettings['region'])
        <select id="aws_region" 
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100"
                disabled>
            <option value="{{ env('AWS_DEFAULT_REGION') }}" selected>
                {{ $awsRegions[env('AWS_DEFAULT_REGION')] ?? env('AWS_DEFAULT_REGION') }}
            </option>
        </select>
        <p class="mt-1 text-sm text-gray-500">This value is configured via environment variables.</p>
    @else
        <select id="aws_region" 
                name="aws_region" 
                class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                x-model="formData.region"
                @change="validateRegion"
                required>
            <option value="">Select a region</option>
            @foreach($awsRegions as $regionCode => $regionName)
                <option value="{{ $regionCode }}" 
                        {{ old('aws_region', $s3Config['region'] ?? '') === $regionCode ? 'selected' : '' }}>
                    {{ $regionName }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">
            Select the AWS region where your S3 bucket is located
        </p>
    @endif
    <x-input-error for="aws_region" class="mt-2" />
</div>
```

**Bucket Name Field**:
```blade
<div>
    <x-label for="aws_bucket" value="S3 Bucket Name" />
    @if($s3EnvSettings['bucket'])
        <x-input id="aws_bucket" 
                 type="text" 
                 class="mt-1 block w-full bg-gray-100" 
                 :value="env('AWS_BUCKET')" 
                 readonly />
        <p class="mt-1 text-sm text-gray-500">This value is configured via environment variables.</p>
    @else
        <x-input id="aws_bucket" 
                 name="aws_bucket" 
                 type="text" 
                 class="mt-1 block w-full"
                 :value="old('aws_bucket', $s3Config['bucket'] ?? '')"
                 placeholder="my-file-intake-bucket"
                 pattern="[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]"
                 x-model="formData.bucket"
                 @input="validateBucketName"
                 required />
        <p class="mt-1 text-xs text-gray-500">
            Bucket name must be 3-63 characters, lowercase letters, numbers, hyphens, and periods only
        </p>
    @endif
    <x-input-error for="aws_bucket" class="mt-2" />
</div>
```

**Custom Endpoint Field**:
```blade
<div>
    <x-label for="aws_endpoint" value="Custom Endpoint (Optional)" />
    @if($s3EnvSettings['endpoint'])
        <x-input id="aws_endpoint" 
                 type="text" 
                 class="mt-1 block w-full bg-gray-100" 
                 :value="env('AWS_ENDPOINT')" 
                 readonly />
        <p class="mt-1 text-sm text-gray-500">This value is configured via environment variables.</p>
    @else
        <x-input id="aws_endpoint" 
                 name="aws_endpoint" 
                 type="url" 
                 class="mt-1 block w-full"
                 :value="old('aws_endpoint', $s3Config['endpoint'] ?? '')"
                 placeholder="https://s3.example.com"
                 x-model="formData.endpoint"
                 @input="validateEndpoint" />
        <p class="mt-1 text-xs text-gray-500">
            For S3-compatible services like Cloudflare R2, Backblaze B2, or MinIO. Leave blank for standard AWS S3.
        </p>
    @endif
    <x-input-error for="aws_endpoint" class="mt-2" />
</div>
```

#### Conditional Save Button

The save button should only be displayed when at least one field is editable:

```blade
@php
    $allFieldsFromEnv = $s3EnvSettings['access_key_id'] && 
                        $s3EnvSettings['secret_access_key'] && 
                        $s3EnvSettings['region'] && 
                        $s3EnvSettings['bucket'];
@endphp

@unless($allFieldsFromEnv)
    <div class="flex justify-end pt-4">
        <button type="submit" 
                x-bind:disabled="isSaving || !isFormValid"
                x-bind:class="{ 'opacity-50 cursor-not-allowed': isSaving || !isFormValid }"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150">
            <span x-show="!isSaving">{{ __('messages.save_configuration') }}</span>
            <span x-show="isSaving" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ __('messages.s3_saving_configuration') }}
            </span>
        </button>
    </div>
@endunless
```

### Alpine.js Data Structure

Update the `s3ConfigurationHandler()` function to handle environment-configured fields:

```javascript
function s3ConfigurationHandler() {
    return {
        formData: {
            access_key_id: @json(old('aws_access_key_id', $s3Config['access_key_id'] ?? '')),
            secret_access_key: '',
            region: @json(old('aws_region', $s3Config['region'] ?? '')),
            bucket: @json(old('aws_bucket', $s3Config['bucket'] ?? '')),
            endpoint: @json(old('aws_endpoint', $s3Config['endpoint'] ?? ''))
        },
        envSettings: @json($s3EnvSettings),
        errors: {},
        isTesting: false,
        isSaving: false,
        testResult: null,
        isFormValid: false,

        init() {
            // Initialize form data with environment values if present
            if (this.envSettings.access_key_id) {
                this.formData.access_key_id = @json(env('AWS_ACCESS_KEY_ID'));
            }
            if (this.envSettings.region) {
                this.formData.region = @json(env('AWS_DEFAULT_REGION'));
            }
            if (this.envSettings.bucket) {
                this.formData.bucket = @json(env('AWS_BUCKET'));
            }
            if (this.envSettings.endpoint) {
                this.formData.endpoint = @json(env('AWS_ENDPOINT'));
            }
            
            this.validateForm();
        },

        // ... existing validation methods ...

        async testConnection() {
            this.isTesting = true;
            this.testResult = null;

            try {
                // Use environment values if present, otherwise use form values
                const testConfig = {
                    aws_access_key_id: this.envSettings.access_key_id ? 
                        @json(env('AWS_ACCESS_KEY_ID')) : this.formData.access_key_id,
                    aws_secret_access_key: this.envSettings.secret_access_key ? 
                        @json(env('AWS_SECRET_ACCESS_KEY')) : this.formData.secret_access_key,
                    aws_region: this.envSettings.region ? 
                        @json(env('AWS_DEFAULT_REGION')) : this.formData.region,
                    aws_bucket: this.envSettings.bucket ? 
                        @json(env('AWS_BUCKET')) : this.formData.bucket,
                    aws_endpoint: this.envSettings.endpoint ? 
                        @json(env('AWS_ENDPOINT')) : this.formData.endpoint
                };

                const response = await fetch('{{ route("admin.cloud-storage.amazon-s3.test-connection") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(testConfig)
                });

                const data = await response.json();
                
                this.testResult = {
                    success: data.success || false,
                    message: data.message || 'Connection test failed'
                };

            } catch (error) {
                this.testResult = {
                    success: false,
                    message: 'Failed to test connection. Please try again.'
                };
            } finally {
                this.isTesting = false;
            }
        },

        handleSubmit(event) {
            // Prevent submission if all fields are from environment
            if (this.envSettings.access_key_id && 
                this.envSettings.secret_access_key && 
                this.envSettings.region && 
                this.envSettings.bucket) {
                event.preventDefault();
                return false;
            }
            
            this.isSaving = true;
            // Form will submit normally
        }
    };
}
```

## Data Models

No changes to existing data models are required. The implementation uses:

- **CloudStorageSetting**: Existing model for storing database-based credentials
- **Environment Variables**: Standard Laravel `env()` helper for environment-based configuration

## Error Handling

### Validation Errors

- Field-level validation errors displayed below each input
- Form-level validation prevents submission of invalid data
- Server-side validation in controller provides additional security

### Configuration Save Errors

- Success message: "Amazon S3 configuration saved successfully"
- Warning message: "Configuration saved but connection test failed"
- Error message: "Failed to save S3 configuration"

### Environment Variable Detection

- No errors thrown if environment variables are not set
- Graceful fallback to database configuration
- Clear UI indication of configuration source

## Testing Strategy

### Manual Testing

1. **Environment Variable Configuration**:
   - Set AWS credentials in .env file
   - Verify information banner displays
   - Verify fields are read-only
   - Verify secret key is masked
   - Verify save button is hidden
   - Verify test connection works

2. **Database Configuration**:
   - Remove AWS credentials from .env file
   - Verify fields are editable
   - Verify form validation works
   - Verify credentials can be saved
   - Verify success message displays

3. **Mixed Configuration**:
   - Set some credentials in .env, others in database
   - Verify correct fields are read-only
   - Verify save button is visible
   - Verify partial updates work

4. **Connection Testing**:
   - Test connection with environment credentials
   - Test connection with database credentials
   - Test connection with invalid credentials
   - Verify appropriate messages display

### Automated Testing

Existing test suite in `tests/Feature/Admin/CloudStorageS3ConfigurationTest.php` should continue to pass. Additional tests may be added for:

- Environment variable detection
- Read-only field rendering
- Conditional save button display
- Mixed configuration scenarios

## Visual Design

### Color Scheme

- Information banner: Blue (`bg-blue-50`, `border-blue-200`, `text-blue-700`)
- Read-only fields: Gray background (`bg-gray-100`)
- Helper text: Gray (`text-gray-500`)
- Success messages: Green
- Error messages: Red
- Warning messages: Amber

### Typography

- Banner heading: `text-sm font-medium`
- Helper text: `text-sm` or `text-xs`
- Field labels: Standard Laravel Blade component styling

### Spacing

- Banner margin: `mb-4`
- Banner padding: `p-4`
- Field spacing: `space-y-4`
- Helper text margin: `mt-1`

## Implementation Notes

1. **Consistency**: All patterns match the existing Google Drive implementation
2. **Security**: Sensitive credentials are masked in read-only mode
3. **Usability**: Clear visual indicators for configuration source
4. **Flexibility**: Supports environment, database, or mixed configurations
5. **Maintainability**: Follows existing Laravel Blade component patterns
