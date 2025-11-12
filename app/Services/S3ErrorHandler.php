<?php

namespace App\Services;

use App\Enums\CloudStorageErrorType;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Amazon S3 specific error handler
 * 
 * Extends BaseCloudStorageErrorHandler to provide S3
 * specific error classification, user-friendly messages, and retry logic
 */
class S3ErrorHandler extends BaseCloudStorageErrorHandler
{

    /**
     * Get the provider name for logging and error messages
     *
     * @return string Provider name
     */
    protected function getProviderName(): string
    {
        return 'Amazon S3';
    }

    /**
     * Classify provider-specific exceptions
     *
     * @param Throwable $exception The exception to classify
     * @return CloudStorageErrorType|null The classified error type, or null if not handled
     */
    protected function classifyProviderException(Throwable $exception): ?CloudStorageErrorType
    {
        // Handle S3-specific exceptions
        if ($exception instanceof S3Exception) {
            return $this->classifyS3Exception($exception);
        }

        // Handle general AWS exceptions
        if ($exception instanceof AwsException) {
            return $this->classifyAwsException($exception);
        }

        return null; // Let base class handle common errors
    }

    /**
     * Classify S3-specific exceptions
     *
     * @param S3Exception $exception
     * @return CloudStorageErrorType
     */
    private function classifyS3Exception(S3Exception $exception): CloudStorageErrorType
    {
        $errorCode = $exception->getAwsErrorCode();
        $statusCode = $exception->getStatusCode();
        $message = strtolower($exception->getMessage());

        Log::debug('Classifying S3 exception', [
            'error_code' => $errorCode,
            'status_code' => $statusCode,
            'message' => $message
        ]);

        // If we don't have an error code, try to classify by status code first
        if (empty($errorCode)) {
            $statusResult = $this->classifyByStatusCode($statusCode);
            if ($statusResult !== null) {
                return $statusResult;
            }
        }

        return match ($errorCode) {
            'NoSuchBucket' => CloudStorageErrorType::BUCKET_NOT_FOUND,
            'InvalidBucketName' => CloudStorageErrorType::INVALID_BUCKET_NAME,
            'BucketNotEmpty' => CloudStorageErrorType::BUCKET_ACCESS_DENIED,
            'AccessDenied' => $this->classifyAccessDeniedError($exception, $message),
            'InvalidAccessKeyId', 'SignatureDoesNotMatch' => CloudStorageErrorType::INVALID_CREDENTIALS,
            'NoSuchKey' => CloudStorageErrorType::FILE_NOT_FOUND,
            'EntityTooLarge' => CloudStorageErrorType::FILE_TOO_LARGE,
            'InvalidRequest' => $this->classifyInvalidRequestError($exception, $message),
            'SlowDown', 'RequestTimeTooSkewed' => CloudStorageErrorType::API_QUOTA_EXCEEDED,
            'ServiceUnavailable', 'InternalError' => CloudStorageErrorType::SERVICE_UNAVAILABLE,
            'InvalidRegion' => CloudStorageErrorType::INVALID_REGION,
            'NotImplemented' => CloudStorageErrorType::FEATURE_NOT_SUPPORTED,
            'RequestTimeout' => CloudStorageErrorType::NETWORK_ERROR,
            default => $this->classifyByStatusCode($statusCode) ?? CloudStorageErrorType::UNKNOWN_ERROR
        };
    }

    /**
     * Classify general AWS exceptions
     *
     * @param AwsException $exception
     * @return CloudStorageErrorType
     */
    private function classifyAwsException(AwsException $exception): CloudStorageErrorType
    {
        $errorCode = $exception->getAwsErrorCode();
        $statusCode = $exception->getStatusCode();
        $message = strtolower($exception->getMessage());

        Log::debug('Classifying AWS exception', [
            'error_code' => $errorCode,
            'status_code' => $statusCode,
            'message' => $message
        ]);

        return match ($errorCode) {
            'InvalidAccessKeyId', 'SignatureDoesNotMatch', 'TokenRefreshRequired' => CloudStorageErrorType::INVALID_CREDENTIALS,
            'UnauthorizedOperation' => CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            'RequestLimitExceeded', 'Throttling' => CloudStorageErrorType::API_QUOTA_EXCEEDED,
            'ServiceUnavailable', 'InternalFailure' => CloudStorageErrorType::SERVICE_UNAVAILABLE,
            'InvalidParameterValue' => CloudStorageErrorType::INVALID_REGION,
            default => $this->classifyByStatusCode($statusCode) ?? CloudStorageErrorType::UNKNOWN_ERROR
        };
    }

    /**
     * Classify AccessDenied errors more specifically
     *
     * @param S3Exception $exception
     * @param string $message
     * @return CloudStorageErrorType
     */
    private function classifyAccessDeniedError(S3Exception $exception, string $message): CloudStorageErrorType
    {
        if (str_contains($message, 'bucket')) {
            return CloudStorageErrorType::BUCKET_ACCESS_DENIED;
        }

        if (str_contains($message, 'key') || str_contains($message, 'object')) {
            return CloudStorageErrorType::INSUFFICIENT_PERMISSIONS;
        }

        return CloudStorageErrorType::INSUFFICIENT_PERMISSIONS;
    }

    /**
     * Classify InvalidRequest errors more specifically
     *
     * @param S3Exception $exception
     * @param string $message
     * @return CloudStorageErrorType
     */
    private function classifyInvalidRequestError(S3Exception $exception, string $message): CloudStorageErrorType
    {
        if (str_contains($message, 'too large') || str_contains($message, 'size') || str_contains($message, 'exceeds')) {
            return CloudStorageErrorType::FILE_TOO_LARGE;
        }

        if (str_contains($message, 'content-type') || str_contains($message, 'invalid content')) {
            return CloudStorageErrorType::INVALID_FILE_CONTENT;
        }

        return CloudStorageErrorType::UNKNOWN_ERROR;
    }

    /**
     * Classify error by HTTP status code
     *
     * @param int|null $statusCode
     * @return CloudStorageErrorType|null
     */
    private function classifyByStatusCode(?int $statusCode): ?CloudStorageErrorType
    {
        if ($statusCode === null) {
            return null;
        }

        return match ($statusCode) {
            401 => CloudStorageErrorType::INVALID_CREDENTIALS,
            403 => CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            404 => CloudStorageErrorType::FILE_NOT_FOUND,
            413 => CloudStorageErrorType::FILE_TOO_LARGE,
            429 => CloudStorageErrorType::API_QUOTA_EXCEEDED,
            500, 502, 503, 504 => CloudStorageErrorType::SERVICE_UNAVAILABLE,
            default => null
        };
    }



    /**
     * Get provider-specific user-friendly messages
     *
     * @param CloudStorageErrorType $type The error type
     * @param array $context Additional context for message generation
     * @return string|null Provider-specific message, or null to use default
     */
    protected function getProviderSpecificMessage(CloudStorageErrorType $type, array $context = []): ?string
    {
        $fileName = $context['file_name'] ?? 'file';
        $operation = $context['operation'] ?? 'operation';
        $bucketName = $context['bucket'] ?? 'S3 bucket';

        return match ($type) {
            CloudStorageErrorType::INVALID_CREDENTIALS => 
                'Invalid AWS credentials. Please check your Access Key ID and Secret Access Key in the cloud storage settings.',
            
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => 
                'Insufficient S3 permissions. Please ensure your AWS credentials have the necessary permissions for the S3 bucket.',
            
            CloudStorageErrorType::BUCKET_NOT_FOUND => 
                "The S3 bucket '{$bucketName}' was not found. Please verify the bucket name and region in your settings.",
            
            CloudStorageErrorType::BUCKET_ACCESS_DENIED => 
                "Access denied to S3 bucket '{$bucketName}'. Please check your bucket permissions and AWS credentials.",
            
            CloudStorageErrorType::INVALID_BUCKET_NAME => 
                "Invalid S3 bucket name '{$bucketName}'. Bucket names must follow AWS naming conventions.",
            
            CloudStorageErrorType::INVALID_REGION => 
                'Invalid AWS region specified. Please check your region setting in the cloud storage configuration.',
            
            CloudStorageErrorType::API_QUOTA_EXCEEDED => 
                'S3 API rate limit exceeded. Your uploads will resume automatically in ' . 
                $this->getQuotaResetTimeMessage($context) . '. No action is required.',
            
            CloudStorageErrorType::FILE_NOT_FOUND => 
                "The file '{$fileName}' could not be found in S3. It may have been deleted or moved.",
            
            CloudStorageErrorType::INVALID_FILE_TYPE => 
                "The file type of '{$fileName}' is not supported. Please try a different file format.",
            
            CloudStorageErrorType::FILE_TOO_LARGE => 
                "The file '{$fileName}' is too large for S3. Maximum file size is 5TB.",
            
            CloudStorageErrorType::NETWORK_ERROR => 
                'Network connection issue prevented the S3 upload. The upload will be retried automatically.',
            
            CloudStorageErrorType::SERVICE_UNAVAILABLE => 
                'Amazon S3 is temporarily unavailable. Your uploads will be retried automatically when the service is restored.',
            
            CloudStorageErrorType::TIMEOUT => 
                "The S3 {$operation} timed out. This is usually temporary and will be retried automatically.",
            
            CloudStorageErrorType::INVALID_FILE_CONTENT => 
                "The file '{$fileName}' appears to be corrupted or has invalid content. Please try uploading the file again.",
            
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED => 
                'S3 provider is not configured. Please configure your AWS credentials in the cloud storage settings.',
            
            CloudStorageErrorType::PROVIDER_INITIALIZATION_FAILED => 
                'Failed to initialize S3 connection. Please check your AWS credentials and configuration.',
            
            CloudStorageErrorType::FEATURE_NOT_SUPPORTED => 
                'The requested feature is not supported by Amazon S3.',
            
            CloudStorageErrorType::STORAGE_CLASS_NOT_SUPPORTED => 
                'The specified S3 storage class is not supported or available in your region.',
            
            CloudStorageErrorType::UNKNOWN_ERROR => 
                'An unexpected error occurred with Amazon S3. ' . 
                ($context['original_message'] ?? 'Please try again or contact support if the problem persists.'),
            
            // Return null for error types that should use common messages
            default => null
        };
    }

    /**
     * Get provider-specific quota retry delay
     *
     * @param array $context Additional context
     * @return int Delay in seconds
     */
    protected function getQuotaRetryDelay(array $context = []): int
    {
        if (isset($context['retry_after'])) {
            return (int) $context['retry_after'];
        }

        return 900; // 15 minutes for S3 rate limits
    }



    /**
     * Get provider-specific recommended actions
     *
     * @param CloudStorageErrorType $type The error type
     * @param array $context Additional context for recommendations
     * @return array|null Provider-specific actions, or null to use default
     */
    protected function getProviderSpecificActions(CloudStorageErrorType $type, array $context = []): ?array
    {
        $bucketName = $context['bucket'] ?? 'your S3 bucket';

        return match ($type) {
            CloudStorageErrorType::INVALID_CREDENTIALS => [
                'Go to Settings → Cloud Storage',
                'Check your AWS Access Key ID and Secret Access Key',
                'Ensure the credentials are active and not expired',
                'Test the connection after updating credentials'
            ],
            
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS => [
                'Check your AWS IAM permissions',
                'Ensure your user/role has S3 read/write permissions',
                'Verify bucket-specific permissions if using bucket policies',
                'Contact your AWS administrator if needed'
            ],
            
            CloudStorageErrorType::BUCKET_NOT_FOUND => [
                'Verify the S3 bucket name in your settings',
                'Check that the bucket exists in the specified region',
                'Ensure you have access to the bucket',
                'Create the bucket if it doesn\'t exist'
            ],
            
            CloudStorageErrorType::BUCKET_ACCESS_DENIED => [
                'Check your bucket permissions in AWS console',
                'Verify your AWS credentials have access to this bucket',
                'Review bucket policies and ACLs',
                'Contact your AWS administrator'
            ],
            
            CloudStorageErrorType::INVALID_BUCKET_NAME => [
                'Use a valid S3 bucket name (3-63 characters)',
                'Use only lowercase letters, numbers, and hyphens',
                'Don\'t use consecutive hyphens or start/end with hyphens',
                'Avoid IP address format names'
            ],
            
            CloudStorageErrorType::INVALID_REGION => [
                'Check the AWS region in your settings',
                'Ensure the region matches where your bucket is located',
                'Use standard AWS region codes (e.g., us-east-1, eu-west-1)',
                'Verify the region is available for your account'
            ],
            
            CloudStorageErrorType::API_QUOTA_EXCEEDED => [
                'Wait for the rate limit to reset (usually 15 minutes)',
                'Uploads will resume automatically',
                'Consider spreading uploads across time for large batches',
                'Contact AWS support if you need higher limits'
            ],
            
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED => [
                'Go to Settings → Cloud Storage',
                'Configure your AWS credentials',
                'Set the S3 bucket name and region',
                'Test the connection after configuration'
            ],
            
            CloudStorageErrorType::INVALID_FILE_TYPE => [
                'Check if the file type is supported',
                'Try uploading a different file format',
                'Verify the file is not corrupted'
            ],
            
            CloudStorageErrorType::FILE_TOO_LARGE => [
                'Compress the file to reduce its size',
                'Split large files into smaller parts',
                'Use multipart upload for very large files',
                'Check S3 limits for your account type'
            ],
            
            // Return null for error types that should use common actions
            default => null
        };
    }
}