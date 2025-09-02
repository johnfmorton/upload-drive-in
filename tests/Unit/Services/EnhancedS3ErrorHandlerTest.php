<?php

namespace Tests\Unit\Services;

use App\Enums\CloudStorageErrorType;
use App\Services\S3ErrorHandler;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class EnhancedS3ErrorHandlerTest extends TestCase
{
    private S3ErrorHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new S3ErrorHandler();
    }

    public function test_extends_base_error_handler()
    {
        $this->assertInstanceOf(\App\Services\BaseCloudStorageErrorHandler::class, $this->handler);
    }

    public function test_classifies_s3_exceptions()
    {
        $s3Exception = $this->createS3Exception('NoSuchBucket', 404);
        $result = $this->handler->classifyError($s3Exception);
        
        $this->assertEquals(CloudStorageErrorType::BUCKET_NOT_FOUND, $result);
    }

    public function test_classifies_aws_exceptions()
    {
        $awsException = $this->createAwsException('InvalidAccessKeyId', 403);
        $result = $this->handler->classifyError($awsException);
        
        $this->assertEquals(CloudStorageErrorType::INVALID_CREDENTIALS, $result);
    }

    public function test_falls_back_to_base_class_for_network_errors()
    {
        $connectException = new ConnectException('Connection failed', $this->createMock(RequestInterface::class));
        $result = $this->handler->classifyError($connectException);
        
        $this->assertEquals(CloudStorageErrorType::NETWORK_ERROR, $result);
    }

    public function test_falls_back_to_base_class_for_timeout_errors()
    {
        $timeoutException = new Exception('Operation timed out');
        $result = $this->handler->classifyError($timeoutException);
        
        $this->assertEquals(CloudStorageErrorType::TIMEOUT, $result);
    }

    public function test_provides_s3_specific_messages()
    {
        $message = $this->handler->getUserFriendlyMessage(CloudStorageErrorType::BUCKET_NOT_FOUND, ['bucket' => 'my-bucket']);
        
        $this->assertStringContainsString('S3 bucket \'my-bucket\' was not found', $message);
        $this->assertStringContainsString('verify the bucket name and region', $message);
    }

    public function test_falls_back_to_base_messages_for_common_errors()
    {
        $message = $this->handler->getUserFriendlyMessage(CloudStorageErrorType::NETWORK_ERROR);
        
        $this->assertStringContainsString('Network connection issue', $message);
        $this->assertStringContainsString('Amazon S3', $message);
    }

    public function test_provides_s3_specific_actions()
    {
        $actions = $this->handler->getRecommendedActions(CloudStorageErrorType::BUCKET_NOT_FOUND);
        
        $this->assertContains('Verify the S3 bucket name in your settings', $actions);
        $this->assertContains('Check that the bucket exists in the specified region', $actions);
    }

    public function test_falls_back_to_base_actions_for_common_errors()
    {
        $actions = $this->handler->getRecommendedActions(CloudStorageErrorType::PROVIDER_NOT_CONFIGURED);
        
        $this->assertContains('Go to Settings → Cloud Storage', $actions);
        $this->assertContains('Configure your Amazon S3 credentials', $actions);
    }

    public function test_uses_base_class_retry_logic()
    {
        // Test that retry logic is handled by base class
        $this->assertFalse($this->handler->shouldRetry(CloudStorageErrorType::INVALID_CREDENTIALS, 1));
        $this->assertTrue($this->handler->shouldRetry(CloudStorageErrorType::NETWORK_ERROR, 1));
        $this->assertFalse($this->handler->shouldRetry(CloudStorageErrorType::NETWORK_ERROR, 4));
    }

    public function test_uses_base_class_retry_delays()
    {
        // Test that retry delays are handled by base class
        $this->assertEquals(30, $this->handler->getRetryDelay(CloudStorageErrorType::NETWORK_ERROR, 1));
        $this->assertEquals(60, $this->handler->getRetryDelay(CloudStorageErrorType::TIMEOUT, 1));
    }

    public function test_s3_specific_quota_retry_delay()
    {
        // Test that S3 uses 15 minutes for quota issues
        $this->assertEquals(900, $this->handler->getRetryDelay(CloudStorageErrorType::API_QUOTA_EXCEEDED, 1));
    }

    public function test_classifies_various_s3_error_codes()
    {
        $testCases = [
            ['NoSuchBucket', CloudStorageErrorType::BUCKET_NOT_FOUND],
            ['InvalidBucketName', CloudStorageErrorType::INVALID_BUCKET_NAME],
            ['AccessDenied', CloudStorageErrorType::INSUFFICIENT_PERMISSIONS],
            ['InvalidAccessKeyId', CloudStorageErrorType::INVALID_CREDENTIALS],
            ['NoSuchKey', CloudStorageErrorType::FILE_NOT_FOUND],
            ['EntityTooLarge', CloudStorageErrorType::FILE_TOO_LARGE],
            ['SlowDown', CloudStorageErrorType::API_QUOTA_EXCEEDED],
            ['ServiceUnavailable', CloudStorageErrorType::SERVICE_UNAVAILABLE],
            ['InvalidRegion', CloudStorageErrorType::INVALID_REGION],
            ['NotImplemented', CloudStorageErrorType::FEATURE_NOT_SUPPORTED],
        ];

        foreach ($testCases as [$errorCode, $expectedType]) {
            $exception = $this->createS3Exception($errorCode, 400);
            $result = $this->handler->classifyError($exception);
            
            $this->assertEquals($expectedType, $result, "Failed for error code: {$errorCode}");
        }
    }

    public function test_classifies_by_status_code_when_no_error_code()
    {
        $testCases = [
            [401, CloudStorageErrorType::INVALID_CREDENTIALS],
            [403, CloudStorageErrorType::INSUFFICIENT_PERMISSIONS],
            [404, CloudStorageErrorType::FILE_NOT_FOUND],
            [413, CloudStorageErrorType::FILE_TOO_LARGE],
            [429, CloudStorageErrorType::API_QUOTA_EXCEEDED],
            [500, CloudStorageErrorType::SERVICE_UNAVAILABLE],
        ];

        foreach ($testCases as [$statusCode, $expectedType]) {
            $exception = $this->createS3Exception('', $statusCode);
            $result = $this->handler->classifyError($exception);
            
            $this->assertEquals($expectedType, $result, "Failed for status code: {$statusCode}");
        }
    }

    public function test_handles_context_in_messages()
    {
        $context = [
            'file_name' => 'test.pdf',
            'bucket' => 'my-test-bucket',
            'operation' => 'upload'
        ];

        $message = $this->handler->getUserFriendlyMessage(CloudStorageErrorType::FILE_NOT_FOUND, $context);
        
        $this->assertStringContainsString('test.pdf', $message);
    }

    public function test_handles_quota_reset_time_in_context()
    {
        $context = [
            'retry_after' => 900 // 15 minutes
        ];

        $message = $this->handler->getUserFriendlyMessage(CloudStorageErrorType::API_QUOTA_EXCEEDED, $context);
        
        $this->assertStringContainsString('15 minutes', $message);
    }

    public function test_classifies_access_denied_errors_specifically()
    {
        // Test bucket access denied
        $bucketException = $this->createS3Exception('AccessDenied', 403, 'bucket access denied');
        $result = $this->handler->classifyError($bucketException);
        $this->assertEquals(CloudStorageErrorType::BUCKET_ACCESS_DENIED, $result);

        // Test general access denied
        $generalException = $this->createS3Exception('AccessDenied', 403, 'access denied to key');
        $result = $this->handler->classifyError($generalException);
        $this->assertEquals(CloudStorageErrorType::INSUFFICIENT_PERMISSIONS, $result);
    }

    public function test_classifies_invalid_request_errors_specifically()
    {
        // Test file too large
        $sizeException = $this->createS3Exception('InvalidRequest', 400, 'file too large');
        $result = $this->handler->classifyError($sizeException);
        $this->assertEquals(CloudStorageErrorType::FILE_TOO_LARGE, $result);

        // Test invalid content
        $contentException = $this->createS3Exception('InvalidRequest', 400, 'invalid content-type');
        $result = $this->handler->classifyError($contentException);
        $this->assertEquals(CloudStorageErrorType::INVALID_FILE_CONTENT, $result);
    }

    private function createS3Exception(string $errorCode, int $statusCode, string $message = 'Test error'): S3Exception
    {
        $exception = new S3Exception($message, $this->createMock(\Aws\CommandInterface::class));
        
        // Use reflection to set private properties
        $reflection = new \ReflectionClass($exception);
        
        $errorCodeProperty = $reflection->getProperty('errorCode');
        $errorCodeProperty->setAccessible(true);
        $errorCodeProperty->setValue($exception, $errorCode);
        
        $statusCodeProperty = $reflection->getProperty('statusCode');
        $statusCodeProperty->setAccessible(true);
        $statusCodeProperty->setValue($exception, $statusCode);
        
        return $exception;
    }

    private function createAwsException(string $errorCode, int $statusCode, string $message = 'Test error'): AwsException
    {
        $exception = new AwsException($message, $this->createMock(\Aws\CommandInterface::class));
        
        // Use reflection to set private properties
        $reflection = new \ReflectionClass($exception);
        
        $errorCodeProperty = $reflection->getProperty('errorCode');
        $errorCodeProperty->setAccessible(true);
        $errorCodeProperty->setValue($exception, $errorCode);
        
        $statusCodeProperty = $reflection->getProperty('statusCode');
        $statusCodeProperty->setAccessible(true);
        $statusCodeProperty->setValue($exception, $statusCode);
        
        return $exception;
    }
}