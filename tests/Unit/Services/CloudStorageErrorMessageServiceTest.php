<?php

namespace Tests\Unit\Services;

use App\Enums\CloudStorageErrorType;
use App\Services\CloudStorageErrorMessageService;
use App\Services\CloudStorageStatusMessages;
use Tests\TestCase;

class CloudStorageErrorMessageServiceTest extends TestCase
{
    private CloudStorageErrorMessageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CloudStorageErrorMessageService();
    }

    public function test_token_refresh_rate_limited_message_with_retry_time()
    {
        $context = [
            'retry_after' => 300, // 5 minutes
            'provider' => 'google-drive'
        ];

        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
            $context
        );

        $this->assertStringContainsString('5 minute', $message);
    }

    public function test_token_refresh_rate_limited_message_without_retry_time()
    {
        $context = ['provider' => 'google-drive'];

        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
            $context
        );

        $this->assertStringContainsString('try again later', $message);
    }

    public function test_token_refresh_rate_limited_message_with_consecutive_failures()
    {
        $context = [
            'consecutive_failures' => 6,
            'provider' => 'google-drive'
        ];

        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
            $context
        );

        $this->assertStringContainsString('Google Drive connection attempts', $message);
        $this->assertStringContainsString('extended delays', $message);
    }

    public function test_status_display_message_with_error_context()
    {
        $context = [
            'error_type' => CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
            'retry_after' => 180,
            'provider' => 'google-drive'
        ];

        $message = $this->service->getStatusDisplayMessage('connection_issues', $context);

        $this->assertStringContainsString('3 minute', $message);
    }

    public function test_connection_issue_message_with_consecutive_failures()
    {
        $context = [
            'consecutive_failures' => 5,
            'provider' => 'google-drive'
        ];

        $message = $this->service->getStatusDisplayMessage('connection_issues', $context);

        $this->assertStringContainsString('Multiple connection failures', $message);
    }

    public function test_connection_issue_message_with_specific_error_types()
    {
        // Test token expired
        $context = [
            'error_type' => CloudStorageErrorType::TOKEN_EXPIRED,
            'provider' => 'google-drive'
        ];

        $message = $this->service->getStatusDisplayMessage('connection_issues', $context);
        $this->assertStringContainsString('connection has expired', $message);

        // Test invalid credentials
        $context['error_type'] = CloudStorageErrorType::INVALID_CREDENTIALS;
        $message = $this->service->getStatusDisplayMessage('connection_issues', $context);
        $this->assertStringContainsString('Invalid Google Drive credentials', $message);

        // Test network error
        $context['error_type'] = CloudStorageErrorType::NETWORK_ERROR;
        $message = $this->service->getStatusDisplayMessage('connection_issues', $context);
        $this->assertStringContainsString('Network connection issue', $message);
    }

    public function test_message_priority_resolution()
    {
        $errorContexts = [
            [
                'consolidated_status' => 'connection_issues',
                'consecutive_failures' => 2
            ],
            [
                'error_type' => CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
                'retry_after' => 120
            ],
            [
                'consolidated_status' => 'authentication_required'
            ]
        ];

        $message = $this->service->resolveMessagePriority($errorContexts);

        // Rate limiting should have highest priority
        $this->assertStringContainsString('2 minute', $message);
    }

    public function test_enhanced_priority_resolution_with_multiple_error_types()
    {
        $errorContexts = [
            [
                'error_type' => CloudStorageErrorType::NETWORK_ERROR,
                'provider' => 'google-drive'
            ],
            [
                'error_type' => CloudStorageErrorType::TOKEN_EXPIRED,
                'provider' => 'google-drive'
            ],
            [
                'error_type' => CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
                'retry_after' => 300,
                'provider' => 'google-drive'
            ]
        ];

        $message = $this->service->resolveMessagePriority($errorContexts);

        // Rate limiting should have highest priority over token expired and network error
        $this->assertStringContainsString('5 minute', $message);
    }

    public function test_context_aware_message_generation()
    {
        $context = [
            'error_type' => CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
            'consecutive_failures' => 3,
            'retry_after' => 240,
            'provider' => 'google-drive',
            'user' => null
        ];

        $result = $this->service->generateContextAwareMessage($context);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('urgency', $result);
        $this->assertArrayHasKey('action_buttons', $result);
        $this->assertArrayHasKey('is_retryable', $result);
        $this->assertArrayHasKey('requires_user_action', $result);
        $this->assertArrayHasKey('message_type', $result);

        $this->assertEquals('critical', $result['urgency']);
        $this->assertEquals('rate_limit', $result['message_type']);
        $this->assertTrue($result['is_retryable']);
        $this->assertTrue($result['requires_user_action']);
        $this->assertStringContainsString('4 minute', $result['message']);
    }

    public function test_contextual_action_buttons()
    {
        // Test rate limited context
        $context = [
            'error_type' => CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
            'retry_after' => 180
        ];

        $result = $this->service->generateContextAwareMessage($context);
        $buttons = $result['action_buttons'];

        $this->assertNotEmpty($buttons);
        $this->assertEquals('wait', $buttons[0]['type']);
        $this->assertTrue($buttons[0]['disabled']);
        $this->assertEquals(180, $buttons[0]['countdown']);

        // Test authentication required context
        $context = [
            'error_type' => CloudStorageErrorType::TOKEN_EXPIRED,
            'provider' => 'google-drive'
        ];

        $result = $this->service->generateContextAwareMessage($context);
        $buttons = $result['action_buttons'];

        $this->assertNotEmpty($buttons);
        $reconnectButton = array_filter($buttons, fn($b) => $b['type'] === 'reconnect');
        $this->assertNotEmpty($reconnectButton);
    }

    public function test_recovery_instructions_for_rate_limited()
    {
        $instructions = $this->service->getRecoveryInstructions(
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
            ['provider' => 'google-drive']
        );

        $this->assertIsArray($instructions);
        $this->assertNotEmpty($instructions);
        $this->assertStringContainsString('rate limit', strtolower(implode(' ', $instructions)));
    }

    public function test_error_is_retryable()
    {
        $response = $this->service->generateErrorResponse(
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
            ['provider' => 'google-drive']
        );

        $this->assertTrue($response['is_retryable']);
        $this->assertTrue($response['requires_user_action']);
    }

    public function test_technical_error_message_detection()
    {
        $context = [
            'last_error_message' => 'HTTP 429 Too Many Requests',
            'consecutive_failures' => 2,
            'provider' => 'google-drive'
        ];

        $message = $this->service->getStatusDisplayMessage('connection_issues', $context);

        // Should not show technical HTTP error message to users
        $this->assertStringNotContainsString('HTTP 429', $message);
        $this->assertStringContainsString('Connection issue detected', $message);
    }

    public function test_message_consistency_validation()
    {
        // Test deprecated message patterns
        $this->assertFalse(
            CloudStorageStatusMessages::validateMessageConsistency(
                'Connection issues detected - please check your network and try again'
            )
        );

        $this->assertTrue(
            CloudStorageStatusMessages::validateMessageConsistency(
                'Too many token refresh attempts. Please try again in 5 minutes.'
            )
        );
    }

    public function test_rate_limited_message_with_last_attempt_time()
    {
        $lastAttempt = new \DateTime('-2 minutes');
        $context = [
            'last_attempt_time' => $lastAttempt->format('Y-m-d H:i:s'),
            'consecutive_failures' => 3,
            'provider' => 'google-drive'
        ];

        $message = $this->service->getActionableErrorMessage(
            CloudStorageErrorType::TOKEN_REFRESH_RATE_LIMITED,
            $context
        );

        $this->assertStringContainsString('3 more minute', $message);
    }

    public function test_priority_resolution_handles_string_error_types()
    {
        $errorContexts = [
            [
                'error_type' => 'network_error',
                'provider' => 'google-drive'
            ],
            [
                'error_type' => 'token_refresh_rate_limited',
                'retry_after' => 120,
                'provider' => 'google-drive'
            ]
        ];

        $message = $this->service->resolveMessagePriority($errorContexts);

        // Rate limiting should have highest priority
        $this->assertStringContainsString('2 minute', $message);
    }
}