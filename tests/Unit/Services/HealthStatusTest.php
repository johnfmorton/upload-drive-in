<?php

namespace Tests\Unit\Services;

use App\Services\HealthStatus;
use Carbon\Carbon;
use Tests\TestCase;

class HealthStatusTest extends TestCase
{
    public function test_healthy_status_creation(): void
    {
        // Arrange & Act
        $healthStatus = HealthStatus::healthy(['test' => 'data']);

        // Assert
        $this->assertTrue($healthStatus->isHealthy());
        $this->assertEquals('healthy', $healthStatus->getStatus());
        $this->assertNull($healthStatus->getErrorMessage());
        $this->assertNull($healthStatus->getErrorType());
        $this->assertEquals(['test' => 'data'], $healthStatus->getValidationDetails());
        $this->assertInstanceOf(Carbon::class, $healthStatus->getValidatedAt());
        $this->assertEquals(30, $healthStatus->getCacheTtlSeconds());
    }

    public function test_authentication_required_status_creation(): void
    {
        // Arrange & Act
        $healthStatus = HealthStatus::authenticationRequired('Token expired', ['token' => 'invalid']);

        // Assert
        $this->assertFalse($healthStatus->isHealthy());
        $this->assertEquals('authentication_required', $healthStatus->getStatus());
        $this->assertEquals('Token expired', $healthStatus->getErrorMessage());
        $this->assertEquals('authentication_error', $healthStatus->getErrorType());
        $this->assertEquals(['token' => 'invalid'], $healthStatus->getValidationDetails());
        $this->assertInstanceOf(Carbon::class, $healthStatus->getValidatedAt());
        $this->assertEquals(10, $healthStatus->getCacheTtlSeconds());
    }

    public function test_connection_issues_status_creation(): void
    {
        // Arrange & Act
        $healthStatus = HealthStatus::connectionIssues('Network timeout', 'network_error', ['timeout' => true]);

        // Assert
        $this->assertFalse($healthStatus->isHealthy());
        $this->assertEquals('connection_issues', $healthStatus->getStatus());
        $this->assertEquals('Network timeout', $healthStatus->getErrorMessage());
        $this->assertEquals('network_error', $healthStatus->getErrorType());
        $this->assertEquals(['timeout' => true], $healthStatus->getValidationDetails());
        $this->assertInstanceOf(Carbon::class, $healthStatus->getValidatedAt());
        $this->assertEquals(10, $healthStatus->getCacheTtlSeconds());
    }

    public function test_connection_issues_status_with_default_error_type(): void
    {
        // Arrange & Act
        $healthStatus = HealthStatus::connectionIssues('API failed');

        // Assert
        $this->assertFalse($healthStatus->isHealthy());
        $this->assertEquals('connection_issues', $healthStatus->getStatus());
        $this->assertEquals('API failed', $healthStatus->getErrorMessage());
        $this->assertEquals('connection_error', $healthStatus->getErrorType());
        $this->assertEquals(10, $healthStatus->getCacheTtlSeconds());
    }

    public function test_not_connected_status_creation(): void
    {
        // Arrange & Act
        $healthStatus = HealthStatus::notConnected('No provider configured', ['provider' => null]);

        // Assert
        $this->assertFalse($healthStatus->isHealthy());
        $this->assertEquals('not_connected', $healthStatus->getStatus());
        $this->assertEquals('No provider configured', $healthStatus->getErrorMessage());
        $this->assertEquals('not_connected', $healthStatus->getErrorType());
        $this->assertEquals(['provider' => null], $healthStatus->getValidationDetails());
        $this->assertInstanceOf(Carbon::class, $healthStatus->getValidatedAt());
        $this->assertEquals(10, $healthStatus->getCacheTtlSeconds());
    }

    public function test_from_token_error_with_user_intervention_required(): void
    {
        // Arrange
        $tokenResult = [
            'error' => 'Invalid refresh token',
            'error_type' => (object) ['value' => 'invalid_refresh_token'],
            'requires_user_intervention' => true,
            'additional_data' => 'test'
        ];

        // Act
        $healthStatus = HealthStatus::fromTokenError($tokenResult);

        // Assert
        $this->assertFalse($healthStatus->isHealthy());
        $this->assertEquals('authentication_required', $healthStatus->getStatus());
        $this->assertEquals('Invalid refresh token', $healthStatus->getErrorMessage());
        $this->assertEquals('invalid_refresh_token', $healthStatus->getErrorType());
        $this->assertEquals($tokenResult, $healthStatus->getValidationDetails());
        $this->assertEquals(10, $healthStatus->getCacheTtlSeconds());
    }

    public function test_from_token_error_without_user_intervention_required(): void
    {
        // Arrange
        $tokenResult = [
            'error' => 'Network timeout',
            'error_type' => (object) ['value' => 'network_timeout'],
            'requires_user_intervention' => false,
        ];

        // Act
        $healthStatus = HealthStatus::fromTokenError($tokenResult);

        // Assert
        $this->assertFalse($healthStatus->isHealthy());
        $this->assertEquals('connection_issues', $healthStatus->getStatus());
        $this->assertEquals('Network timeout', $healthStatus->getErrorMessage());
        $this->assertEquals('network_timeout', $healthStatus->getErrorType());
    }

    public function test_from_token_error_with_defaults(): void
    {
        // Arrange
        $tokenResult = []; // Empty result

        // Act
        $healthStatus = HealthStatus::fromTokenError($tokenResult);

        // Assert
        $this->assertFalse($healthStatus->isHealthy());
        $this->assertEquals('authentication_required', $healthStatus->getStatus());
        $this->assertEquals('Token validation failed', $healthStatus->getErrorMessage());
        $this->assertEquals('token_error', $healthStatus->getErrorType());
    }

    public function test_from_api_error(): void
    {
        // Arrange
        $apiResult = [
            'error' => 'API quota exceeded',
            'error_type' => 'quota_exceeded',
            'http_code' => 429
        ];

        // Act
        $healthStatus = HealthStatus::fromApiError($apiResult);

        // Assert
        $this->assertFalse($healthStatus->isHealthy());
        $this->assertEquals('connection_issues', $healthStatus->getStatus());
        $this->assertEquals('API quota exceeded', $healthStatus->getErrorMessage());
        $this->assertEquals('quota_exceeded', $healthStatus->getErrorType());
        $this->assertEquals($apiResult, $healthStatus->getValidationDetails());
        $this->assertEquals(10, $healthStatus->getCacheTtlSeconds());
    }

    public function test_from_api_error_with_defaults(): void
    {
        // Arrange
        $apiResult = []; // Empty result

        // Act
        $healthStatus = HealthStatus::fromApiError($apiResult);

        // Assert
        $this->assertFalse($healthStatus->isHealthy());
        $this->assertEquals('connection_issues', $healthStatus->getStatus());
        $this->assertEquals('API connectivity test failed', $healthStatus->getErrorMessage());
        $this->assertEquals('api_error', $healthStatus->getErrorType());
    }

    public function test_to_array_conversion(): void
    {
        // Arrange
        $validationDetails = ['test' => 'data', 'duration' => 123];
        $healthStatus = HealthStatus::healthy($validationDetails);

        // Act
        $array = $healthStatus->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertEquals(true, $array['is_healthy']);
        $this->assertEquals('healthy', $array['status']);
        $this->assertNull($array['error_message']);
        $this->assertNull($array['error_type']);
        $this->assertEquals($validationDetails, $array['validation_details']);
        $this->assertIsString($array['validated_at']);
        $this->assertEquals(30, $array['cache_ttl_seconds']);
    }

    public function test_to_array_conversion_with_error(): void
    {
        // Arrange
        $healthStatus = HealthStatus::connectionIssues('Test error', 'test_error', ['context' => 'test']);

        // Act
        $array = $healthStatus->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertEquals(false, $array['is_healthy']);
        $this->assertEquals('connection_issues', $array['status']);
        $this->assertEquals('Test error', $array['error_message']);
        $this->assertEquals('test_error', $array['error_type']);
        $this->assertEquals(['context' => 'test'], $array['validation_details']);
        $this->assertIsString($array['validated_at']);
        $this->assertEquals(10, $array['cache_ttl_seconds']);
    }

    public function test_constructor_with_all_parameters(): void
    {
        // Arrange
        $validatedAt = now()->subMinutes(5);
        
        // Act
        $healthStatus = new HealthStatus(
            isHealthy: false,
            status: 'custom_status',
            errorMessage: 'Custom error',
            errorType: 'custom_error',
            validationDetails: ['custom' => 'data'],
            validatedAt: $validatedAt,
            cacheTtlSeconds: 60
        );

        // Assert
        $this->assertFalse($healthStatus->isHealthy());
        $this->assertEquals('custom_status', $healthStatus->getStatus());
        $this->assertEquals('Custom error', $healthStatus->getErrorMessage());
        $this->assertEquals('custom_error', $healthStatus->getErrorType());
        $this->assertEquals(['custom' => 'data'], $healthStatus->getValidationDetails());
        $this->assertEquals($validatedAt, $healthStatus->getValidatedAt());
        $this->assertEquals(60, $healthStatus->getCacheTtlSeconds());
    }

    public function test_constructor_with_minimal_parameters(): void
    {
        // Act
        $healthStatus = new HealthStatus(
            isHealthy: true,
            status: 'minimal'
        );

        // Assert
        $this->assertTrue($healthStatus->isHealthy());
        $this->assertEquals('minimal', $healthStatus->getStatus());
        $this->assertNull($healthStatus->getErrorMessage());
        $this->assertNull($healthStatus->getErrorType());
        $this->assertNull($healthStatus->getValidationDetails());
        $this->assertNull($healthStatus->getValidatedAt());
        $this->assertNull($healthStatus->getCacheTtlSeconds());
    }
}