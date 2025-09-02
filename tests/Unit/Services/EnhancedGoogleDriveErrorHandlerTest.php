<?php

namespace Tests\Unit\Services;

use App\Enums\CloudStorageErrorType;
use App\Services\GoogleDriveErrorHandler;
use Exception;
use Google\Service\Exception as GoogleServiceException;
use GuzzleHttp\Exception\ConnectException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class EnhancedGoogleDriveErrorHandlerTest extends TestCase
{
    private GoogleDriveErrorHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new GoogleDriveErrorHandler();
    }

    public function test_extends_base_error_handler()
    {
        $this->assertInstanceOf(\App\Services\BaseCloudStorageErrorHandler::class, $this->handler);
    }

    public function test_classifies_google_service_exceptions()
    {
        $googleException = new GoogleServiceException('Unauthorized', 401);
        $result = $this->handler->classifyError($googleException);
        
        $this->assertEquals(CloudStorageErrorType::TOKEN_EXPIRED, $result);
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

    public function test_provides_google_drive_specific_messages()
    {
        $message = $this->handler->getUserFriendlyMessage(CloudStorageErrorType::TOKEN_EXPIRED);
        
        $this->assertStringContainsString('Google Drive connection has expired', $message);
        $this->assertStringContainsString('reconnect your Google Drive account', $message);
    }

    public function test_falls_back_to_base_messages_for_common_errors()
    {
        $message = $this->handler->getUserFriendlyMessage(CloudStorageErrorType::NETWORK_ERROR);
        
        $this->assertStringContainsString('Network connection issue', $message);
        $this->assertStringContainsString('Google Drive', $message);
    }

    public function test_provides_google_drive_specific_actions()
    {
        $actions = $this->handler->getRecommendedActions(CloudStorageErrorType::TOKEN_EXPIRED);
        
        $this->assertContains('Go to Settings → Cloud Storage', $actions);
        $this->assertContains('Click "Reconnect Google Drive"', $actions);
        $this->assertContains('Complete the authorization process', $actions);
    }

    public function test_falls_back_to_base_actions_for_common_errors()
    {
        $actions = $this->handler->getRecommendedActions(CloudStorageErrorType::PROVIDER_NOT_CONFIGURED);
        
        $this->assertContains('Go to Settings → Cloud Storage', $actions);
        $this->assertContains('Configure your Google Drive credentials', $actions);
    }

    public function test_uses_base_class_retry_logic()
    {
        // Test that retry logic is handled by base class
        $this->assertFalse($this->handler->shouldRetry(CloudStorageErrorType::TOKEN_EXPIRED, 1));
        $this->assertTrue($this->handler->shouldRetry(CloudStorageErrorType::NETWORK_ERROR, 1));
        $this->assertFalse($this->handler->shouldRetry(CloudStorageErrorType::NETWORK_ERROR, 4));
    }

    public function test_uses_base_class_retry_delays()
    {
        // Test that retry delays are handled by base class
        $this->assertEquals(30, $this->handler->getRetryDelay(CloudStorageErrorType::NETWORK_ERROR, 1));
        $this->assertEquals(60, $this->handler->getRetryDelay(CloudStorageErrorType::TIMEOUT, 1));
    }

    public function test_uses_base_class_max_retry_attempts()
    {
        // Test that max retry attempts are handled by base class
        $this->assertEquals(3, $this->handler->getMaxRetryAttempts(CloudStorageErrorType::NETWORK_ERROR));
        $this->assertEquals(0, $this->handler->getMaxRetryAttempts(CloudStorageErrorType::TOKEN_EXPIRED));
    }

    public function test_uses_base_class_user_intervention_logic()
    {
        // Test that user intervention logic is handled by base class
        $this->assertTrue($this->handler->requiresUserIntervention(CloudStorageErrorType::TOKEN_EXPIRED));
        $this->assertFalse($this->handler->requiresUserIntervention(CloudStorageErrorType::NETWORK_ERROR));
    }

    public function test_google_drive_specific_quota_retry_delay()
    {
        // Test that Google Drive uses 1 hour for quota issues
        $this->assertEquals(3600, $this->handler->getRetryDelay(CloudStorageErrorType::API_QUOTA_EXCEEDED, 1));
    }

    public function test_handles_context_in_messages()
    {
        $context = [
            'file_name' => 'test.pdf',
            'operation' => 'upload'
        ];

        $message = $this->handler->getUserFriendlyMessage(CloudStorageErrorType::FILE_NOT_FOUND, $context);
        
        $this->assertStringContainsString('test.pdf', $message);
    }

    public function test_handles_quota_reset_time_in_context()
    {
        $context = [
            'retry_after' => 1800 // 30 minutes
        ];

        $message = $this->handler->getUserFriendlyMessage(CloudStorageErrorType::API_QUOTA_EXCEEDED, $context);
        
        $this->assertStringContainsString('30 minutes', $message);
    }

    public function test_classifies_various_google_service_error_codes()
    {
        $testCases = [
            [401, 'authError', CloudStorageErrorType::TOKEN_EXPIRED],
            [403, 'insufficientPermissions', CloudStorageErrorType::INSUFFICIENT_PERMISSIONS],
            [403, 'quotaExceeded', CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED],
            [403, 'rateLimitExceeded', CloudStorageErrorType::API_QUOTA_EXCEEDED],
            [404, null, CloudStorageErrorType::FILE_NOT_FOUND],
            [413, null, CloudStorageErrorType::FILE_TOO_LARGE],
            [429, null, CloudStorageErrorType::API_QUOTA_EXCEEDED],
            [500, null, CloudStorageErrorType::SERVICE_UNAVAILABLE],
        ];

        foreach ($testCases as [$code, $reason, $expectedType]) {
            $errors = $reason ? [['reason' => $reason]] : [];
            $exception = new GoogleServiceException('Test error', $code);
            
            // Use reflection to set the errors property since it's private
            $reflection = new \ReflectionClass($exception);
            $errorsProperty = $reflection->getProperty('errors');
            $errorsProperty->setAccessible(true);
            $errorsProperty->setValue($exception, $errors);

            $result = $this->handler->classifyError($exception);
            
            $this->assertEquals($expectedType, $result, "Failed for code {$code} with reason {$reason}");
        }
    }
}