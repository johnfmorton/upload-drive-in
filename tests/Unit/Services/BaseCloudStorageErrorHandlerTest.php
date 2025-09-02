<?php

namespace Tests\Unit\Services;

use App\Enums\CloudStorageErrorType;
use App\Services\BaseCloudStorageErrorHandler;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class BaseCloudStorageErrorHandlerTest extends TestCase
{
    private TestableBaseCloudStorageErrorHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new TestableBaseCloudStorageErrorHandler();
    }

    public function test_classifies_network_errors()
    {
        $connectException = new ConnectException('Connection failed', $this->createMock(RequestInterface::class));
        $result = $this->handler->classifyError($connectException);
        
        $this->assertEquals(CloudStorageErrorType::NETWORK_ERROR, $result);
    }

    public function test_classifies_timeout_errors()
    {
        $timeoutException = new Exception('Operation timed out');
        $result = $this->handler->classifyError($timeoutException);
        
        $this->assertEquals(CloudStorageErrorType::TIMEOUT, $result);
    }

    public function test_classifies_unknown_errors()
    {
        $unknownException = new Exception('Some unknown error');
        $result = $this->handler->classifyError($unknownException);
        
        $this->assertEquals(CloudStorageErrorType::UNKNOWN_ERROR, $result);
    }

    public function test_provider_specific_classification_takes_precedence()
    {
        $this->handler->setProviderResult(CloudStorageErrorType::INVALID_CREDENTIALS);
        
        $exception = new Exception('Some error');
        $result = $this->handler->classifyError($exception);
        
        $this->assertEquals(CloudStorageErrorType::INVALID_CREDENTIALS, $result);
    }

    public function test_falls_back_to_common_classification()
    {
        $this->handler->setProviderResult(null);
        
        $timeoutException = new Exception('timeout occurred');
        $result = $this->handler->classifyError($timeoutException);
        
        $this->assertEquals(CloudStorageErrorType::TIMEOUT, $result);
    }

    public function test_detects_network_errors()
    {
        $networkMessages = [
            'connection refused',
            'network unreachable',
            'name resolution failed',
            'could not resolve host'
        ];

        foreach ($networkMessages as $message) {
            $exception = new Exception($message);
            $result = $this->handler->classifyError($exception);
            
            $this->assertEquals(CloudStorageErrorType::NETWORK_ERROR, $result, "Failed for message: {$message}");
        }
    }

    public function test_detects_timeout_errors()
    {
        $timeoutMessages = [
            'timeout',
            'timed out',
            'operation timeout',
            'request timeout'
        ];

        foreach ($timeoutMessages as $message) {
            $exception = new Exception($message);
            $result = $this->handler->classifyError($exception);
            
            $this->assertEquals(CloudStorageErrorType::TIMEOUT, $result, "Failed for message: {$message}");
        }
    }

    public function test_gets_user_friendly_message_from_provider()
    {
        $this->handler->setProviderMessage('Provider specific message');
        
        $message = $this->handler->getUserFriendlyMessage(CloudStorageErrorType::INVALID_CREDENTIALS);
        
        $this->assertEquals('Provider specific message', $message);
    }

    public function test_falls_back_to_common_message()
    {
        $this->handler->setProviderMessage(null);
        
        $message = $this->handler->getUserFriendlyMessage(CloudStorageErrorType::NETWORK_ERROR);
        
        $this->assertStringContainsString('Network connection issue', $message);
        $this->assertStringContainsString('Test Provider', $message);
    }

    public function test_gets_common_user_friendly_messages()
    {
        $this->handler->setProviderMessage(null);
        
        $testCases = [
            CloudStorageErrorType::NETWORK_ERROR => 'Network connection issue',
            CloudStorageErrorType::SERVICE_UNAVAILABLE => 'temporarily unavailable',
            CloudStorageErrorType::TIMEOUT => 'timed out',
            CloudStorageErrorType::INVALID_FILE_CONTENT => 'corrupted or has invalid content',
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED => 'not configured',
            CloudStorageErrorType::PROVIDER_INITIALIZATION_FAILED => 'Failed to initialize',
            CloudStorageErrorType::FEATURE_NOT_SUPPORTED => 'not supported',
            CloudStorageErrorType::UNKNOWN_ERROR => 'unexpected error'
        ];

        foreach ($testCases as $errorType => $expectedText) {
            $message = $this->handler->getUserFriendlyMessage($errorType);
            $this->assertStringContainsString($expectedText, $message, "Failed for error type: {$errorType->value}");
        }
    }

    public function test_should_retry_logic()
    {
        $retryableErrors = [
            CloudStorageErrorType::NETWORK_ERROR,
            CloudStorageErrorType::SERVICE_UNAVAILABLE,
            CloudStorageErrorType::TIMEOUT
        ];

        $nonRetryableErrors = [
            CloudStorageErrorType::TOKEN_EXPIRED,
            CloudStorageErrorType::INSUFFICIENT_PERMISSIONS,
            CloudStorageErrorType::INVALID_CREDENTIALS,
            CloudStorageErrorType::PROVIDER_NOT_CONFIGURED,
            CloudStorageErrorType::FEATURE_NOT_SUPPORTED
        ];

        foreach ($retryableErrors as $errorType) {
            $this->assertTrue($this->handler->shouldRetry($errorType, 1), "Should retry {$errorType->value}");
            $this->assertTrue($this->handler->shouldRetry($errorType, 2), "Should retry {$errorType->value} on attempt 2");
            $this->assertFalse($this->handler->shouldRetry($errorType, 3), "Should not retry {$errorType->value} on attempt 3");
        }

        foreach ($nonRetryableErrors as $errorType) {
            $this->assertFalse($this->handler->shouldRetry($errorType, 1), "Should not retry {$errorType->value}");
        }
    }

    public function test_retry_delay_calculation()
    {
        // Test exponential backoff for network errors
        $this->assertEquals(30, $this->handler->getRetryDelay(CloudStorageErrorType::NETWORK_ERROR, 1));
        $this->assertEquals(60, $this->handler->getRetryDelay(CloudStorageErrorType::NETWORK_ERROR, 2));
        $this->assertEquals(120, $this->handler->getRetryDelay(CloudStorageErrorType::NETWORK_ERROR, 3));

        // Test linear backoff for timeouts
        $this->assertEquals(60, $this->handler->getRetryDelay(CloudStorageErrorType::TIMEOUT, 1));
        $this->assertEquals(120, $this->handler->getRetryDelay(CloudStorageErrorType::TIMEOUT, 2));
        $this->assertEquals(180, $this->handler->getRetryDelay(CloudStorageErrorType::TIMEOUT, 3));

        // Test quota delay
        $this->assertEquals(3600, $this->handler->getRetryDelay(CloudStorageErrorType::API_QUOTA_EXCEEDED, 1));
    }

    public function test_max_retry_attempts()
    {
        $this->assertEquals(3, $this->handler->getMaxRetryAttempts(CloudStorageErrorType::NETWORK_ERROR));
        $this->assertEquals(3, $this->handler->getMaxRetryAttempts(CloudStorageErrorType::SERVICE_UNAVAILABLE));
        $this->assertEquals(3, $this->handler->getMaxRetryAttempts(CloudStorageErrorType::TIMEOUT));
        $this->assertEquals(1, $this->handler->getMaxRetryAttempts(CloudStorageErrorType::UNKNOWN_ERROR));
        $this->assertEquals(0, $this->handler->getMaxRetryAttempts(CloudStorageErrorType::INVALID_CREDENTIALS));
    }

    public function test_requires_user_intervention()
    {
        $this->assertTrue($this->handler->requiresUserIntervention(CloudStorageErrorType::TOKEN_EXPIRED));
        $this->assertTrue($this->handler->requiresUserIntervention(CloudStorageErrorType::INVALID_CREDENTIALS));
        $this->assertFalse($this->handler->requiresUserIntervention(CloudStorageErrorType::NETWORK_ERROR));
    }

    public function test_gets_recommended_actions_from_provider()
    {
        $this->handler->setProviderActions(['Provider action 1', 'Provider action 2']);
        
        $actions = $this->handler->getRecommendedActions(CloudStorageErrorType::INVALID_CREDENTIALS);
        
        $this->assertEquals(['Provider action 1', 'Provider action 2'], $actions);
    }

    public function test_falls_back_to_common_actions()
    {
        $this->handler->setProviderActions(null);
        
        $actions = $this->handler->getRecommendedActions(CloudStorageErrorType::PROVIDER_NOT_CONFIGURED);
        
        $this->assertContains('Go to Settings → Cloud Storage', $actions);
        $this->assertContains('Configure your Test Provider credentials', $actions);
    }

    public function test_quota_reset_time_message()
    {
        $context = ['retry_after' => 3600];
        $message = $this->handler->getUserFriendlyMessage(CloudStorageErrorType::NETWORK_ERROR, $context);
        
        // Should use the base message since provider returns null
        $this->assertStringContainsString('Network connection issue', $message);
    }
}

/**
 * Testable implementation of BaseCloudStorageErrorHandler
 */
class TestableBaseCloudStorageErrorHandler extends BaseCloudStorageErrorHandler
{
    private ?CloudStorageErrorType $providerResult = null;
    private ?string $providerMessage = null;
    private ?array $providerActions = null;

    protected function getProviderName(): string
    {
        return 'Test Provider';
    }

    protected function classifyProviderException(Exception $exception): ?CloudStorageErrorType
    {
        return $this->providerResult;
    }

    protected function getProviderSpecificMessage(CloudStorageErrorType $type, array $context = []): ?string
    {
        return $this->providerMessage;
    }

    protected function getProviderSpecificActions(CloudStorageErrorType $type, array $context = []): ?array
    {
        return $this->providerActions;
    }

    public function setProviderResult(?CloudStorageErrorType $result): void
    {
        $this->providerResult = $result;
    }

    public function setProviderMessage(?string $message): void
    {
        $this->providerMessage = $message;
    }

    public function setProviderActions(?array $actions): void
    {
        $this->providerActions = $actions;
    }
}