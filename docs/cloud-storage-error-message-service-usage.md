# CloudStorageErrorMessageService Usage Guide

The `CloudStorageErrorMessageService` provides user-friendly error messages and recovery instructions for cloud storage operations. This service is designed to help users understand and resolve cloud storage issues effectively.

## Basic Usage

### Getting Actionable Error Messages

```php
use App\Services\CloudStorageErrorMessageService;
use App\Enums\CloudStorageErrorType;

$errorMessageService = app(CloudStorageErrorMessageService::class);

// Basic error message
$message = $errorMessageService->getActionableErrorMessage(
    CloudStorageErrorType::TOKEN_EXPIRED,
    ['provider' => 'google-drive']
);
// Result: "Your Google Drive connection has expired. Please reconnect your account to continue."

// Error message with context
$message = $errorMessageService->getActionableErrorMessage(
    CloudStorageErrorType::FILE_NOT_FOUND,
    [
        'provider' => 'google-drive',
        'file_name' => 'important_document.pdf'
    ]
);
// Result: "The file 'important_document.pdf' could not be found in Google Drive. It may have been deleted or moved."
```

### Getting Recovery Instructions

```php
$instructions = $errorMessageService->getRecoveryInstructions(
    CloudStorageErrorType::TOKEN_EXPIRED,
    ['provider' => 'google-drive']
);

// Result: Array of actionable steps:
// [
//     'Go to Settings → Cloud Storage',
//     'Click "Reconnect Google Drive"',
//     'Complete the authorization process',
//     'Retry your operation'
// ]
```

### Comprehensive Error Response

```php
$user = Auth::user();

$response = $errorMessageService->generateErrorResponse(
    CloudStorageErrorType::API_QUOTA_EXCEEDED,
    [
        'provider' => 'google-drive',
        'user' => $user,
        'technical_details' => 'Rate limit: 1000 requests per hour exceeded',
        'retry_after' => 3600
    ]
);

// Result: Complete error response with all necessary information
// [
//     'error_type' => 'api_quota_exceeded',
//     'message' => 'Google Drive API limit reached. Your operations will resume automatically when the limit resets.',
//     'instructions' => [...],
//     'is_retryable' => true,
//     'requires_user_action' => false,
//     'technical_details' => 'Rate limit: 1000 requests per hour exceeded', // Only for admin users
//     'retry_after' => 3600
// ]
```

## Integration Examples

### In Controllers

```php
// In CloudStorageController
public function handleGoogleDriveError(\Exception $exception, User $user)
{
    $errorMessageService = app(CloudStorageErrorMessageService::class);
    
    // Classify the error
    $errorType = $this->classifyException($exception);
    
    // Generate user-friendly response
    $errorResponse = $errorMessageService->generateErrorResponse($errorType, [
        'provider' => 'google-drive',
        'user' => $user,
        'technical_details' => $exception->getMessage(),
        'operation' => 'file upload'
    ]);
    
    return response()->json([
        'success' => false,
        'error' => $errorResponse
    ], 422);
}
```

### In Job Classes

```php
// In UploadToGoogleDrive job
public function failed(\Throwable $exception)
{
    $errorMessageService = app(CloudStorageErrorMessageService::class);
    
    $errorType = $this->classifyException($exception);
    
    $errorResponse = $errorMessageService->generateErrorResponse($errorType, [
        'provider' => 'google-drive',
        'file_name' => $this->fileUpload->original_filename,
        'operation' => 'upload',
        'technical_details' => $exception->getMessage()
    ]);
    
    // Log the user-friendly error
    Log::error('File upload failed', [
        'file_id' => $this->fileUpload->id,
        'error_response' => $errorResponse
    ]);
    
    // Update file upload record with error information
    $this->fileUpload->update([
        'upload_error' => $errorResponse['message'],
        'error_details' => json_encode($errorResponse)
    ]);
}
```

### In Frontend (Blade Templates)

```blade
{{-- Display error message with recovery instructions --}}
@if(isset($errorResponse))
    <div class="bg-red-50 border border-red-200 rounded-md p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">
                    {{ $errorResponse['message'] }}
                </h3>
                
                @if(!empty($errorResponse['instructions']))
                    <div class="mt-2 text-sm text-red-700">
                        <p class="font-medium">To resolve this issue:</p>
                        <ol class="mt-1 list-decimal list-inside space-y-1">
                            @foreach($errorResponse['instructions'] as $instruction)
                                <li>{{ $instruction }}</li>
                            @endforeach
                        </ol>
                    </div>
                @endif
                
                @if($errorResponse['is_retryable'])
                    <div class="mt-3">
                        <button type="button" class="bg-red-100 px-3 py-2 rounded-md text-sm font-medium text-red-800 hover:bg-red-200">
                            Retry Operation
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
```

### In JavaScript (AJAX Error Handling)

```javascript
// Handle AJAX errors with user-friendly messages
$.ajaxSetup({
    error: function(xhr, status, error) {
        if (xhr.responseJSON && xhr.responseJSON.error) {
            const errorResponse = xhr.responseJSON.error;
            
            // Display user-friendly error message
            showErrorModal({
                title: 'Operation Failed',
                message: errorResponse.message,
                instructions: errorResponse.instructions,
                isRetryable: errorResponse.is_retryable,
                requiresUserAction: errorResponse.requires_user_action
            });
        }
    }
});

function showErrorModal(errorData) {
    let instructionsHtml = '';
    if (errorData.instructions && errorData.instructions.length > 0) {
        instructionsHtml = '<div class="mt-3"><p class="font-medium">To resolve this issue:</p><ol class="mt-1 list-decimal list-inside space-y-1">';
        errorData.instructions.forEach(instruction => {
            instructionsHtml += `<li>${instruction}</li>`;
        });
        instructionsHtml += '</ol></div>';
    }
    
    const modalContent = `
        <div class="text-sm text-gray-700">
            <p>${errorData.message}</p>
            ${instructionsHtml}
        </div>
    `;
    
    // Show modal with appropriate buttons based on error type
    if (errorData.isRetryable) {
        showModalWithRetry(modalContent);
    } else if (errorData.requiresUserAction) {
        showModalWithAction(modalContent);
    } else {
        showInfoModal(modalContent);
    }
}
```

## Error Classification

The service supports comprehensive error classification:

### Authentication Errors
- `TOKEN_EXPIRED`: OAuth token needs refresh
- `INVALID_CREDENTIALS`: Configuration issues
- `INSUFFICIENT_PERMISSIONS`: Scope or access issues

### Network Errors
- `NETWORK_ERROR`: Connectivity issues
- `SERVICE_UNAVAILABLE`: Provider downtime
- `TIMEOUT`: Operation timeout

### Storage Errors
- `STORAGE_QUOTA_EXCEEDED`: No space left
- `API_QUOTA_EXCEEDED`: Rate limits reached
- `FILE_TOO_LARGE`: Size limits exceeded

### File Errors
- `FILE_NOT_FOUND`: Missing files
- `INVALID_FILE_TYPE`: Unsupported formats
- `INVALID_FILE_CONTENT`: Corrupted files

### Configuration Errors
- `PROVIDER_NOT_CONFIGURED`: Setup issues
- `FOLDER_ACCESS_DENIED`: Permission problems

## Best Practices

### 1. Always Provide Context
```php
// Good: Provides context for better error messages
$errorResponse = $errorMessageService->generateErrorResponse($errorType, [
    'provider' => 'google-drive',
    'file_name' => $fileName,
    'operation' => 'upload',
    'user' => $user
]);

// Avoid: Minimal context results in generic messages
$errorResponse = $errorMessageService->generateErrorResponse($errorType);
```

### 2. Use Technical Details for Admin Users
```php
$errorResponse = $errorMessageService->generateErrorResponse($errorType, [
    'provider' => 'google-drive',
    'user' => $user,
    'technical_details' => $exception->getMessage() // Only shown to admin users
]);
```

### 3. Handle Retry Logic
```php
if ($errorResponse['is_retryable']) {
    // Implement retry logic
    $this->retryOperation();
} elseif ($errorResponse['requires_user_action']) {
    // Notify user of required action
    $this->notifyUserAction($errorResponse);
}
```

### 4. Log Comprehensive Error Information
```php
Log::error('Cloud storage operation failed', [
    'error_type' => $errorResponse['error_type'],
    'message' => $errorResponse['message'],
    'user_id' => $user->id,
    'provider' => 'google-drive',
    'is_retryable' => $errorResponse['is_retryable'],
    'requires_user_action' => $errorResponse['requires_user_action']
]);
```

## Supported Providers

The service provides consistent error messages across all supported providers:

- **Google Drive**: `google-drive`
- **Amazon S3**: `amazon-s3`
- **Azure Blob Storage**: `azure-blob`
- **Microsoft Teams**: `microsoft-teams`
- **Dropbox**: `dropbox`
- **OneDrive**: `onedrive`

## Localization Support

The service is designed to support localization. Error messages and instructions are returned as strings that can be easily translated:

```php
// Future localization support
$message = __('cloud_storage.errors.token_expired', [
    'provider' => $providerName
]);
```

## Testing

The service includes comprehensive test coverage:

- **Unit Tests**: `tests/Unit/Services/CloudStorageErrorMessageServiceTest.php`
- **Integration Tests**: `tests/Feature/CloudStorageErrorMessageServiceIntegrationTest.php`

Run tests with:
```bash
ddev artisan test --filter="CloudStorageErrorMessage"
```