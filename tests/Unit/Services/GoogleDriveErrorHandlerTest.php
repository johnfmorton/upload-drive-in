<?php

namespace Tests\Unit\Services;

use App\Enums\CloudStorageErrorType;
use App\Services\GoogleDriveErrorHandler;
use Exception;
use Google\Service\Exception as GoogleServiceException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class GoogleDriveErrorHandlerTest extends TestCase
{
    private GoogleDriveErrorHandler $errorHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->errorHandler = new GoogleDriveErrorHandler();
    }

    public function test_it_classifies_google_service_401_errors_as_token_expired()
    {
        $exception = new GoogleServiceException('Invalid credentials', 401, null, [
            ['reason' => 'authError']
        ]);

        $result = $this->errorHandler->classifyError($exception);

        $this->assertEquals(CloudStorageErrorType::TOKEN_EXPIRED, $result);
    }

    public function test_it_classifies_google_service_403_insufficient_permissions()
    {
        $exception = new GoogleServiceException('Insufficient permissions', 403, null, [
            ['reason' => 'insufficientPermissions']
        ]);

        $result = $this->errorHandler->classifyError($exception);

        $this->assertEquals(CloudStorageErrorType::INSUFFICIENT_PERMISSIONS, $result);
    }

    public function test_it_classifies_google_service_403_quota_exceeded()
    {
        $exception = new GoogleServiceException('Quota exceeded', 403, null, [
            ['reason' => 'quotaExceeded']
        ]);

        $result = $this->errorHandler->classifyError($exception);

        $this->assertEquals(CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED, $result);
    }

    public function test_it_classifies_google_service_403_rate_limit_as_api_quota()
    {
        $exception = new GoogleServiceException('Rate limit exceeded', 403, null, [
            ['reason' => 'rateLimitExceeded']
        ]);

        $result = $this->errorHandler->classifyError($exception);

        $this->assertEquals(CloudStorageErrorType::API_QUOTA_EXCEEDED, $result);
    }

    public function test_it_classifies_google_service_404_as_file_not_found()
    {
        $exception = new GoogleServiceException('File not found', 404);

        $result = $this->errorHandler->classifyError($exception);

        $this->assertEquals(CloudStorageErrorType::FILE_NOT_FOUND, $result);
    }

    public function test_it_classifies_google_service_413_as_file_too_large()
    {
        $exception = new GoogleServiceException('File too large', 413);

        $result = $this->errorHandler->classifyError($exception);

        $this->assertEquals(CloudStorageErrorType::FILE_TOO_LARGE, $result);
    }

    public function test_it_classifies_google_service_429_as_api_quota_exceeded()
    {
        $exception = new GoogleServiceException('Too many requests', 429);

        $result = $this->errorHandler->classifyError($exception);

        $this->assertEquals(CloudStorageErrorType::API_QUOTA_EXCEEDED, $result);
    }

    public function test_it_classifies_google_service_5xx_as_service_unavailable()
    {
        $exception = new GoogleServiceException('Internal server error', 500);

        $result = $this->errorHandler->classifyError($exception);

        $this->assertEquals(CloudStorageErrorType::SERVICE_UNAVAILABLE, $result);
    }

    public function test_it_classifies_connect_exception_as_network_error()
    {
        $request = new Request('GET', 'https://example.com');
        $exception = new ConnectException('Connection refused', $request);

        $result = $this->errorHandler->classifyError($exception);

        $this->assertEquals(CloudStorageErrorType::NETWORK_ERROR, $result);
    }

    public function test_it_classifies_request_exception_with_network_message_as_network_error()
    {
        $request = new Request('GET', 'https://example.com');
        $response = new Response(200);
        $exception = new RequestException('Connection timeout', $request, $response);

        $result = $this->errorHandler->classifyError($exception);

        $this->assertEquals(CloudStorageErrorType::NETWORK_ERROR, $result);
    }

    public function test_it_classifies_timeout_exception_as_timeout()
    {
        $exception = new Exception('Operation timed out');

        $result = $this->errorHandler->classifyError($exception);

        $this->assertEquals(CloudStorageErrorType::TIMEOUT, $result);
    }

    public function test_it_classifies_unknown_exception_as_unknown_error()
    {
        $exception = new Exception('Some random error');

        $result = $this->errorHandler->classifyError($exception);

        $this->assertEquals(CloudStorageErrorType::UNKNOWN_ERROR, $result);
    }

    public function test_it_generates_user_friendly_message_for_token_expired()
    {
        $message = $this->errorHandler->getUserFriendlyMessage(
            CloudStorageErrorType::TOKEN_EXPIRED
        );

        $this->assertStringContainsString('Google Drive connection has expired', $message);
        $this->assertStringContainsString('reconnect', $message);
    }

    public function test_it_generates_user_friendly_message_for_insufficient_permissions()
    {
        $message = $this->errorHandler->getUserFriendlyMessage(
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS
        );

        $this->assertStringContainsString('Insufficient Google Drive permissions', $message);
        $this->assertStringContainsString('full access', $message);
    }

    public function test_it_generates_user_friendly_message_for_api_quota_exceeded()
    {
        $message = $this->errorHandler->getUserFriendlyMessage(
            CloudStorageErrorType::API_QUOTA_EXCEEDED
        );

        $this->assertStringContainsString('Google Drive API limit reached', $message);
        $this->assertStringContainsString('resume automatically', $message);
    }

    public function test_it_generates_user_friendly_message_for_storage_quota_exceeded()
    {
        $message = $this->errorHandler->getUserFriendlyMessage(
            CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED
        );

        $this->assertStringContainsString('Google Drive storage is full', $message);
        $this->assertStringContainsString('free up space', $message);
    }

    public function test_it_generates_user_friendly_message_with_file_name_context()
    {
        $message = $this->errorHandler->getUserFriendlyMessage(
            CloudStorageErrorType::FILE_NOT_FOUND,
            ['file_name' => 'test.pdf']
        );

        $this->assertStringContainsString('test.pdf', $message);
        $this->assertStringContainsString('could not be found', $message);
    }

    public function test_it_determines_retry_logic_for_token_expired()
    {
        $shouldRetry = $this->errorHandler->shouldRetry(CloudStorageErrorType::TOKEN_EXPIRED, 1);
        $this->assertFalse($shouldRetry);
    }

    public function test_it_determines_retry_logic_for_network_error()
    {
        $shouldRetry1 = $this->errorHandler->shouldRetry(CloudStorageErrorType::NETWORK_ERROR, 1);
        $shouldRetry2 = $this->errorHandler->shouldRetry(CloudStorageErrorType::NETWORK_ERROR, 2);
        $shouldRetry3 = $this->errorHandler->shouldRetry(CloudStorageErrorType::NETWORK_ERROR, 3);
        $shouldRetry4 = $this->errorHandler->shouldRetry(CloudStorageErrorType::NETWORK_ERROR, 4);

        $this->assertTrue($shouldRetry1);
        $this->assertTrue($shouldRetry2);
        $this->assertFalse($shouldRetry3);
        $this->assertFalse($shouldRetry4);
    }

    public function test_it_determines_retry_logic_for_api_quota_exceeded()
    {
        $shouldRetry1 = $this->errorHandler->shouldRetry(CloudStorageErrorType::API_QUOTA_EXCEEDED, 1);
        $shouldRetry2 = $this->errorHandler->shouldRetry(CloudStorageErrorType::API_QUOTA_EXCEEDED, 2);

        $this->assertFalse($shouldRetry1); // Fixed: API quota exceeded should not retry immediately
        $this->assertFalse($shouldRetry2);
    }

    public function test_it_calculates_retry_delay_for_api_quota_exceeded()
    {
        $delay = $this->errorHandler->getRetryDelay(CloudStorageErrorType::API_QUOTA_EXCEEDED, 1);
        $this->assertEquals(3600, $delay); // 1 hour
    }

    public function test_it_calculates_retry_delay_for_network_error_with_exponential_backoff()
    {
        $delay1 = $this->errorHandler->getRetryDelay(CloudStorageErrorType::NETWORK_ERROR, 1);
        $delay2 = $this->errorHandler->getRetryDelay(CloudStorageErrorType::NETWORK_ERROR, 2);
        $delay3 = $this->errorHandler->getRetryDelay(CloudStorageErrorType::NETWORK_ERROR, 3);

        $this->assertEquals(30, $delay1);   // 30 seconds
        $this->assertEquals(60, $delay2);   // 60 seconds
        $this->assertEquals(120, $delay3);  // 120 seconds
    }

    public function test_it_caps_retry_delay_for_network_error()
    {
        $delay = $this->errorHandler->getRetryDelay(CloudStorageErrorType::NETWORK_ERROR, 10);
        $this->assertEquals(300, $delay); // Capped at 5 minutes
    }

    public function test_it_returns_correct_max_retry_attempts()
    {
        $this->assertEquals(0, $this->errorHandler->getMaxRetryAttempts(CloudStorageErrorType::TOKEN_EXPIRED));
        $this->assertEquals(0, $this->errorHandler->getMaxRetryAttempts(CloudStorageErrorType::API_QUOTA_EXCEEDED));
        $this->assertEquals(3, $this->errorHandler->getMaxRetryAttempts(CloudStorageErrorType::NETWORK_ERROR));
        $this->assertEquals(1, $this->errorHandler->getMaxRetryAttempts(CloudStorageErrorType::UNKNOWN_ERROR));
    }

    public function test_it_identifies_errors_requiring_user_intervention()
    {
        $this->assertTrue($this->errorHandler->requiresUserIntervention(CloudStorageErrorType::TOKEN_EXPIRED));
        $this->assertTrue($this->errorHandler->requiresUserIntervention(CloudStorageErrorType::INSUFFICIENT_PERMISSIONS));
        $this->assertTrue($this->errorHandler->requiresUserIntervention(CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED));
        $this->assertFalse($this->errorHandler->requiresUserIntervention(CloudStorageErrorType::NETWORK_ERROR));
        $this->assertFalse($this->errorHandler->requiresUserIntervention(CloudStorageErrorType::SERVICE_UNAVAILABLE));
    }

    public function test_it_provides_recommended_actions_for_token_expired()
    {
        $actions = $this->errorHandler->getRecommendedActions(CloudStorageErrorType::TOKEN_EXPIRED);

        $this->assertIsArray($actions);
        $this->assertNotEmpty($actions);
        $this->assertStringContainsString('Settings', $actions[0]);
        $this->assertStringContainsString('Reconnect Google Drive', $actions[1]);
    }

    public function test_it_provides_recommended_actions_for_storage_quota_exceeded()
    {
        $actions = $this->errorHandler->getRecommendedActions(CloudStorageErrorType::STORAGE_QUOTA_EXCEEDED);

        $this->assertIsArray($actions);
        $this->assertNotEmpty($actions);
        $this->assertStringContainsString('Free up space', $actions[0]);
        $this->assertStringContainsString('trash', $actions[1]);
    }

    public function test_it_provides_default_recommended_actions_for_unknown_errors()
    {
        $actions = $this->errorHandler->getRecommendedActions(CloudStorageErrorType::UNKNOWN_ERROR);

        $this->assertIsArray($actions);
        $this->assertNotEmpty($actions);
        $this->assertStringContainsString('Try uploading', $actions[0]);
    }
}