<?php

namespace Tests\Unit\Enums;

use App\Enums\TokenRefreshErrorType;
use Tests\TestCase;

class TokenRefreshErrorTypeTest extends TestCase
{
    /**
     * Test that all enum cases are properly defined
     */
    public function test_enum_cases_are_defined(): void
    {
        $expectedCases = [
            'NETWORK_TIMEOUT',
            'INVALID_REFRESH_TOKEN',
            'EXPIRED_REFRESH_TOKEN',
            'API_QUOTA_EXCEEDED',
            'SERVICE_UNAVAILABLE',
            'UNKNOWN_ERROR'
        ];

        $actualCases = array_map(fn($case) => $case->name, TokenRefreshErrorType::cases());

        $this->assertEquals($expectedCases, $actualCases);
    }

    /**
     * Test that enum values are properly set
     */
    public function test_enum_values_are_correct(): void
    {
        $this->assertEquals('network_timeout', TokenRefreshErrorType::NETWORK_TIMEOUT->value);
        $this->assertEquals('invalid_refresh_token', TokenRefreshErrorType::INVALID_REFRESH_TOKEN->value);
        $this->assertEquals('expired_refresh_token', TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN->value);
        $this->assertEquals('api_quota_exceeded', TokenRefreshErrorType::API_QUOTA_EXCEEDED->value);
        $this->assertEquals('service_unavailable', TokenRefreshErrorType::SERVICE_UNAVAILABLE->value);
        $this->assertEquals('unknown_error', TokenRefreshErrorType::UNKNOWN_ERROR->value);
    }

    /**
     * Test isRecoverable method for recoverable errors
     */
    public function test_is_recoverable_returns_true_for_recoverable_errors(): void
    {
        $recoverableErrors = [
            TokenRefreshErrorType::NETWORK_TIMEOUT,
            TokenRefreshErrorType::API_QUOTA_EXCEEDED,
            TokenRefreshErrorType::SERVICE_UNAVAILABLE,
        ];

        foreach ($recoverableErrors as $error) {
            $this->assertTrue($error->isRecoverable(), "Expected {$error->value} to be recoverable");
        }
    }

    /**
     * Test isRecoverable method for non-recoverable errors
     */
    public function test_is_recoverable_returns_false_for_non_recoverable_errors(): void
    {
        $nonRecoverableErrors = [
            TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
            TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN,
            TokenRefreshErrorType::UNKNOWN_ERROR,
        ];

        foreach ($nonRecoverableErrors as $error) {
            $this->assertFalse($error->isRecoverable(), "Expected {$error->value} to not be recoverable");
        }
    }

    /**
     * Test requiresUserIntervention method for errors requiring intervention
     */
    public function test_requires_user_intervention_returns_true_for_intervention_errors(): void
    {
        $interventionErrors = [
            TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
            TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN,
        ];

        foreach ($interventionErrors as $error) {
            $this->assertTrue($error->requiresUserIntervention(), "Expected {$error->value} to require user intervention");
        }
    }

    /**
     * Test requiresUserIntervention method for errors not requiring intervention
     */
    public function test_requires_user_intervention_returns_false_for_automatic_errors(): void
    {
        $automaticErrors = [
            TokenRefreshErrorType::NETWORK_TIMEOUT,
            TokenRefreshErrorType::API_QUOTA_EXCEEDED,
            TokenRefreshErrorType::SERVICE_UNAVAILABLE,
            TokenRefreshErrorType::UNKNOWN_ERROR,
        ];

        foreach ($automaticErrors as $error) {
            $this->assertFalse($error->requiresUserIntervention(), "Expected {$error->value} to not require user intervention");
        }
    }

    /**
     * Test getRetryDelay method for network timeout with exponential backoff
     */
    public function test_get_retry_delay_for_network_timeout(): void
    {
        $error = TokenRefreshErrorType::NETWORK_TIMEOUT;

        $this->assertEquals(1, $error->getRetryDelay(1)); // 2^0 = 1
        $this->assertEquals(2, $error->getRetryDelay(2)); // 2^1 = 2
        $this->assertEquals(4, $error->getRetryDelay(3)); // 2^2 = 4
        $this->assertEquals(8, $error->getRetryDelay(4)); // 2^3 = 8
        $this->assertEquals(16, $error->getRetryDelay(5)); // 2^4 = 16
        $this->assertEquals(16, $error->getRetryDelay(6)); // Max 16 seconds
    }

    /**
     * Test getRetryDelay method for API quota exceeded
     */
    public function test_get_retry_delay_for_api_quota_exceeded(): void
    {
        $error = TokenRefreshErrorType::API_QUOTA_EXCEEDED;

        $this->assertEquals(3600, $error->getRetryDelay(1)); // 1 hour
        $this->assertEquals(3600, $error->getRetryDelay(3)); // Always 1 hour
    }

    /**
     * Test getRetryDelay method for service unavailable with linear backoff
     */
    public function test_get_retry_delay_for_service_unavailable(): void
    {
        $error = TokenRefreshErrorType::SERVICE_UNAVAILABLE;

        $this->assertEquals(60, $error->getRetryDelay(1)); // 1 minute
        $this->assertEquals(120, $error->getRetryDelay(2)); // 2 minutes
        $this->assertEquals(180, $error->getRetryDelay(3)); // 3 minutes
        $this->assertEquals(240, $error->getRetryDelay(4)); // 4 minutes
        $this->assertEquals(300, $error->getRetryDelay(5)); // 5 minutes (max)
        $this->assertEquals(300, $error->getRetryDelay(10)); // Still max 5 minutes
    }

    /**
     * Test getRetryDelay method for non-retryable errors
     */
    public function test_get_retry_delay_for_non_retryable_errors(): void
    {
        $nonRetryableErrors = [
            TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
            TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN,
            TokenRefreshErrorType::UNKNOWN_ERROR,
        ];

        foreach ($nonRetryableErrors as $error) {
            $this->assertEquals(0, $error->getRetryDelay(1), "Expected {$error->value} to have 0 retry delay");
        }
    }

    /**
     * Test getMaxRetryAttempts method
     */
    public function test_get_max_retry_attempts(): void
    {
        $this->assertEquals(5, TokenRefreshErrorType::NETWORK_TIMEOUT->getMaxRetryAttempts());
        $this->assertEquals(3, TokenRefreshErrorType::API_QUOTA_EXCEEDED->getMaxRetryAttempts());
        $this->assertEquals(3, TokenRefreshErrorType::SERVICE_UNAVAILABLE->getMaxRetryAttempts());
        
        // Non-retryable errors should have 0 max attempts
        $this->assertEquals(0, TokenRefreshErrorType::INVALID_REFRESH_TOKEN->getMaxRetryAttempts());
        $this->assertEquals(0, TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN->getMaxRetryAttempts());
        $this->assertEquals(0, TokenRefreshErrorType::UNKNOWN_ERROR->getMaxRetryAttempts());
    }

    /**
     * Test getDescription method returns meaningful descriptions
     */
    public function test_get_description_returns_meaningful_text(): void
    {
        $this->assertEquals(__('messages.token_refresh_error_network_timeout'), TokenRefreshErrorType::NETWORK_TIMEOUT->getDescription());
        $this->assertEquals(__('messages.token_refresh_error_invalid_refresh_token'), TokenRefreshErrorType::INVALID_REFRESH_TOKEN->getDescription());
        $this->assertEquals(__('messages.token_refresh_error_expired_refresh_token'), TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN->getDescription());
        $this->assertEquals(__('messages.token_refresh_error_api_quota_exceeded'), TokenRefreshErrorType::API_QUOTA_EXCEEDED->getDescription());
        $this->assertEquals(__('messages.token_refresh_error_service_unavailable'), TokenRefreshErrorType::SERVICE_UNAVAILABLE->getDescription());
        $this->assertEquals(__('messages.token_refresh_error_unknown_error'), TokenRefreshErrorType::UNKNOWN_ERROR->getDescription());
    }

    /**
     * Test getSeverity method returns appropriate severity levels
     */
    public function test_get_severity_returns_appropriate_levels(): void
    {
        $this->assertEquals('low', TokenRefreshErrorType::NETWORK_TIMEOUT->getSeverity());
        $this->assertEquals('critical', TokenRefreshErrorType::INVALID_REFRESH_TOKEN->getSeverity());
        $this->assertEquals('critical', TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN->getSeverity());
        $this->assertEquals('medium', TokenRefreshErrorType::API_QUOTA_EXCEEDED->getSeverity());
        $this->assertEquals('low', TokenRefreshErrorType::SERVICE_UNAVAILABLE->getSeverity());
        $this->assertEquals('high', TokenRefreshErrorType::UNKNOWN_ERROR->getSeverity());
    }

    /**
     * Test shouldNotifyImmediately method
     */
    public function test_should_notify_immediately(): void
    {
        // Errors requiring immediate notification
        $this->assertTrue(TokenRefreshErrorType::INVALID_REFRESH_TOKEN->shouldNotifyImmediately());
        $this->assertTrue(TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN->shouldNotifyImmediately());

        // Errors that should wait for retry attempts
        $this->assertFalse(TokenRefreshErrorType::NETWORK_TIMEOUT->shouldNotifyImmediately());
        $this->assertFalse(TokenRefreshErrorType::API_QUOTA_EXCEEDED->shouldNotifyImmediately());
        $this->assertFalse(TokenRefreshErrorType::SERVICE_UNAVAILABLE->shouldNotifyImmediately());
        $this->assertFalse(TokenRefreshErrorType::UNKNOWN_ERROR->shouldNotifyImmediately());
    }

    /**
     * Test getNotificationMessage method returns user-friendly messages
     */
    public function test_get_notification_message_returns_user_friendly_text(): void
    {
        $this->assertEquals(__('messages.token_refresh_notification_network_timeout'), TokenRefreshErrorType::NETWORK_TIMEOUT->getNotificationMessage());
        $this->assertEquals(__('messages.token_refresh_notification_invalid_refresh_token'), TokenRefreshErrorType::INVALID_REFRESH_TOKEN->getNotificationMessage());
        $this->assertEquals(__('messages.token_refresh_notification_expired_refresh_token'), TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN->getNotificationMessage());
        $this->assertEquals(__('messages.token_refresh_notification_api_quota_exceeded'), TokenRefreshErrorType::API_QUOTA_EXCEEDED->getNotificationMessage());
        $this->assertEquals(__('messages.token_refresh_notification_service_unavailable'), TokenRefreshErrorType::SERVICE_UNAVAILABLE->getNotificationMessage());
        $this->assertEquals(__('messages.token_refresh_notification_unknown_error'), TokenRefreshErrorType::UNKNOWN_ERROR->getNotificationMessage());
    }

    /**
     * Test that recoverable errors have retry attempts and delays
     */
    public function test_recoverable_errors_have_retry_configuration(): void
    {
        $recoverableErrors = [
            TokenRefreshErrorType::NETWORK_TIMEOUT,
            TokenRefreshErrorType::API_QUOTA_EXCEEDED,
            TokenRefreshErrorType::SERVICE_UNAVAILABLE,
        ];

        foreach ($recoverableErrors as $error) {
            $this->assertGreaterThan(0, $error->getMaxRetryAttempts(), "Recoverable error {$error->value} should have retry attempts");
            $this->assertGreaterThan(0, $error->getRetryDelay(1), "Recoverable error {$error->value} should have retry delay");
        }
    }

    /**
     * Test that non-recoverable errors have no retry configuration
     */
    public function test_non_recoverable_errors_have_no_retry_configuration(): void
    {
        $nonRecoverableErrors = [
            TokenRefreshErrorType::INVALID_REFRESH_TOKEN,
            TokenRefreshErrorType::EXPIRED_REFRESH_TOKEN,
            TokenRefreshErrorType::UNKNOWN_ERROR,
        ];

        foreach ($nonRecoverableErrors as $error) {
            $this->assertEquals(0, $error->getMaxRetryAttempts(), "Non-recoverable error {$error->value} should have no retry attempts");
            $this->assertEquals(0, $error->getRetryDelay(1), "Non-recoverable error {$error->value} should have no retry delay");
        }
    }
}