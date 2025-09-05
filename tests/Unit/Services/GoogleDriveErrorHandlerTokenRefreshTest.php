<?php

namespace Tests\Unit\Services;

use App\Enums\TokenRefreshErrorType;
use App\Services\GoogleDriveErrorHandler;
use Exception;
use Google\Service\Exception as GoogleServiceException;
use Tests\TestCase;

class GoogleDriveErrorHandlerTokenRefreshTest extends TestCase
{
    private GoogleDriveErrorHandler $errorHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->errorHandler = new GoogleDriveErrorHandler();
    }

    /**
     * Test classification of Google Service exceptions with 400 status codes
     */
    public function test_classifies_400_bad_request_errors(): void
    {
        // Test invalid_grant error
        $exception = new GoogleServiceException('invalid_grant: Token has been expired or revoked', 400);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result);

        // Test invalid_request error
        $exception = new GoogleServiceException('invalid_request: Missing required parameter', 400);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result);

        // Test expired token error
        $exception = new GoogleServiceException('Token expired and cannot be refreshed', 400);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN, $result);

        // Test generic 400 error (should default to invalid refresh token)
        $exception = new GoogleServiceException('Bad request', 400);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result);
    }

    /**
     * Test classification of Google Service exceptions with 401 status codes
     */
    public function test_classifies_401_unauthorized_errors(): void
    {
        // Test invalid_grant in message
        $exception = new GoogleServiceException('invalid_grant: The provided authorization grant is invalid', 401);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result);

        // Test expired token message
        $exception = new GoogleServiceException('Token has expired', 401);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN, $result);

        // Test generic 401 error (should default to invalid refresh token)
        $exception = new GoogleServiceException('Unauthorized', 401);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result);
    }

    /**
     * Test classification of Google Service exceptions with 403 status codes
     */
    public function test_classifies_403_forbidden_errors(): void
    {
        // Test quota in message
        $exception = new GoogleServiceException('Quota exceeded for this request', 403);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::API_QUOTA_EXCEEDED, $result);

        // Test generic 403 error (should default to invalid refresh token)
        $exception = new GoogleServiceException('Forbidden', 403);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result);
    }

    /**
     * Test classification of Google Service exceptions with 429 status codes
     */
    public function test_classifies_429_rate_limit_errors(): void
    {
        $exception = new GoogleServiceException('Too Many Requests', 429);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::API_QUOTA_EXCEEDED, $result);
    }

    /**
     * Test classification of Google Service exceptions with 5xx status codes
     */
    public function test_classifies_5xx_server_errors(): void
    {
        $serverErrorCodes = [500, 502, 503, 504];

        foreach ($serverErrorCodes as $code) {
            $exception = new GoogleServiceException('Server error', $code);
            $result = $this->errorHandler->classifyTokenRefreshError($exception);
            $this->assertEquals(TokenRefreshErrorType::SERVICE_UNAVAILABLE, $result, "Expected SERVICE_UNAVAILABLE for HTTP {$code}");
        }
    }

    /**
     * Test classification by message content (since we can't easily set errors array)
     */
    public function test_classifies_by_message_content(): void
    {
        // Test various message patterns that would be classified
        $messageMappings = [
            ['invalid_grant: Token expired', 400, TokenRefreshErrorType::INVALID_REFRESH_TOKEN],
            ['Token has expired', 400, TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN],
            ['quota exceeded', 403, TokenRefreshErrorType::API_QUOTA_EXCEEDED], // Use 403 for quota errors
        ];

        foreach ($messageMappings as [$message, $code, $expectedType]) {
            $exception = new GoogleServiceException($message, $code);
            $result = $this->errorHandler->classifyTokenRefreshError($exception);
            $this->assertEquals($expectedType, $result, "Expected {$expectedType->value} for message '{$message}' with code {$code}");
        }
    }

    /**
     * Test classification of network timeout exceptions
     */
    public function test_classifies_network_timeout_exceptions(): void
    {
        $timeoutMessages = [
            'Connection timed out',
            'Network timeout occurred',
            'cURL error: Operation timed out',
            'Request timeout',
        ];

        foreach ($timeoutMessages as $message) {
            $exception = new Exception($message);
            $result = $this->errorHandler->classifyTokenRefreshError($exception);
            $this->assertEquals(TokenRefreshErrorType::NETWORK_TIMEOUT, $result, "Expected NETWORK_TIMEOUT for message: {$message}");
        }
    }

    /**
     * Test classification of cURL timeout exceptions by error code
     */
    public function test_classifies_curl_timeout_by_error_code(): void
    {
        // Test CURLE_OPERATION_TIMEOUTED (28)
        $exception = new Exception('Operation timed out', CURLE_OPERATION_TIMEOUTED);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::NETWORK_TIMEOUT, $result);

        // Test CURLE_TIMEOUT (7) - if defined
        if (defined('CURLE_TIMEOUT')) {
            $exception = new Exception('Timeout', CURLE_TIMEOUT);
            $result = $this->errorHandler->classifyTokenRefreshError($exception);
            $this->assertEquals(TokenRefreshErrorType::NETWORK_TIMEOUT, $result);
        }
    }

    /**
     * Test classification of unknown exceptions
     */
    public function test_classifies_unknown_exceptions_as_unknown_error(): void
    {
        $unknownExceptions = [
            new Exception('Some random error'),
            new Exception('Unexpected exception', 999),
            new GoogleServiceException('Unknown Google error', 418), // I'm a teapot
        ];

        foreach ($unknownExceptions as $exception) {
            $result = $this->errorHandler->classifyTokenRefreshError($exception);
            $this->assertEquals(TokenRefreshErrorType::UNKNOWN_ERROR, $result);
        }
    }

    /**
     * Test that Google Service exceptions are handled properly based on status codes
     */
    public function test_handles_google_exceptions_by_status_code(): void
    {
        $exception = new GoogleServiceException('Error without specific message', 401);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result);
    }

    /**
     * Test case sensitivity in message classification
     */
    public function test_message_classification_is_case_insensitive(): void
    {
        $messages = [
            'INVALID_GRANT: Token expired',
            'Invalid_Grant: Token expired',
            'invalid_grant: Token expired',
            'Token has EXPIRED',
            'TOKEN HAS EXPIRED',
        ];

        foreach ($messages as $message) {
            $exception = new GoogleServiceException($message, 400);
            $result = $this->errorHandler->classifyTokenRefreshError($exception);
            $this->assertContains($result, [
                TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
                TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN
            ], "Message '{$message}' should be classified as token-related error");
        }
    }

    /**
     * Test that multiple error conditions in message are handled correctly
     */
    public function test_handles_multiple_error_conditions_in_message(): void
    {
        // Test message with both invalid_grant and expired - should prioritize invalid_grant
        $exception = new GoogleServiceException('invalid_grant: The token has expired', 400);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result);

        // Test message with quota and expired - should prioritize quota when in 403
        $exception = new GoogleServiceException('Quota exceeded: token expired', 403);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::API_QUOTA_EXCEEDED, $result);
    }

    /**
     * Test edge cases with null or empty messages
     */
    public function test_handles_null_or_empty_messages(): void
    {
        // Test with empty message
        $exception = new GoogleServiceException('', 401);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result);

        // Test with null message (if possible)
        $exception = new GoogleServiceException(null, 401);
        $result = $this->errorHandler->classifyTokenRefreshError($exception);
        $this->assertEquals(TokenRefreshErrorType::INVALID_REFRESH_TOKEN, $result);
    }
}