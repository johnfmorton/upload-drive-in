<?php

namespace Tests\Unit\Services;

use App\Enums\CloudStorageErrorType;
use App\Services\CloudStorageHealthStatus;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class CloudStorageHealthStatusTest extends TestCase
{
    public function test_healthy_status_creation()
    {
        $lastOperation = Carbon::now()->subHour();
        $tokenExpires = Carbon::now()->addDays(30);
        $providerData = ['quota_used' => '50%'];

        $status = CloudStorageHealthStatus::healthy(
            'google-drive',
            $lastOperation,
            $tokenExpires,
            $providerData
        );

        $this->assertEquals('google-drive', $status->provider);
        $this->assertEquals(CloudStorageHealthStatus::STATUS_HEALTHY, $status->status);
        $this->assertEquals($lastOperation, $status->lastSuccessfulOperation);
        $this->assertEquals(0, $status->consecutiveFailures);
        $this->assertNull($status->lastErrorType);
        $this->assertNull($status->lastErrorMessage);
        $this->assertEquals($tokenExpires, $status->tokenExpiresAt);
        $this->assertFalse($status->requiresReconnection);
        $this->assertEquals($providerData, $status->providerSpecificData);
    }

    public function test_degraded_status_creation()
    {
        $lastOperation = Carbon::now()->subHours(2);
        $errorType = CloudStorageErrorType::NETWORK_ERROR;
        $errorMessage = 'Connection timeout';
        $providerData = ['last_attempt' => 'failed'];

        $status = CloudStorageHealthStatus::degraded(
            'dropbox',
            3,
            $errorType,
            $errorMessage,
            $lastOperation,
            $providerData
        );

        $this->assertEquals('dropbox', $status->provider);
        $this->assertEquals(CloudStorageHealthStatus::STATUS_DEGRADED, $status->status);
        $this->assertEquals($lastOperation, $status->lastSuccessfulOperation);
        $this->assertEquals(3, $status->consecutiveFailures);
        $this->assertEquals($errorType, $status->lastErrorType);
        $this->assertEquals($errorMessage, $status->lastErrorMessage);
        $this->assertNull($status->tokenExpiresAt);
        $this->assertFalse($status->requiresReconnection);
        $this->assertEquals($providerData, $status->providerSpecificData);
    }

    public function test_unhealthy_status_creation()
    {
        $lastOperation = Carbon::now()->subDays(1);
        $errorType = CloudStorageErrorType::TOKEN_EXPIRED;
        $errorMessage = 'Token has expired';
        $providerData = ['needs_reauth' => true];

        $status = CloudStorageHealthStatus::unhealthy(
            'onedrive',
            5,
            $errorType,
            $errorMessage,
            true,
            $lastOperation,
            $providerData
        );

        $this->assertEquals('onedrive', $status->provider);
        $this->assertEquals(CloudStorageHealthStatus::STATUS_UNHEALTHY, $status->status);
        $this->assertEquals($lastOperation, $status->lastSuccessfulOperation);
        $this->assertEquals(5, $status->consecutiveFailures);
        $this->assertEquals($errorType, $status->lastErrorType);
        $this->assertEquals($errorMessage, $status->lastErrorMessage);
        $this->assertNull($status->tokenExpiresAt);
        $this->assertTrue($status->requiresReconnection);
        $this->assertEquals($providerData, $status->providerSpecificData);
    }

    public function test_disconnected_status_creation()
    {
        $providerData = ['disconnected_at' => '2024-01-01'];

        $status = CloudStorageHealthStatus::disconnected(
            'google-drive',
            $providerData
        );

        $this->assertEquals('google-drive', $status->provider);
        $this->assertEquals(CloudStorageHealthStatus::STATUS_DISCONNECTED, $status->status);
        $this->assertNull($status->lastSuccessfulOperation);
        $this->assertEquals(0, $status->consecutiveFailures);
        $this->assertNull($status->lastErrorType);
        $this->assertNull($status->lastErrorMessage);
        $this->assertNull($status->tokenExpiresAt);
        $this->assertTrue($status->requiresReconnection);
        $this->assertEquals($providerData, $status->providerSpecificData);
    }

    public function test_status_check_methods()
    {
        $healthy = CloudStorageHealthStatus::healthy('test');
        $degraded = CloudStorageHealthStatus::degraded('test');
        $unhealthy = CloudStorageHealthStatus::unhealthy('test', 5);
        $disconnected = CloudStorageHealthStatus::disconnected('test');

        // Healthy status
        $this->assertTrue($healthy->isHealthy());
        $this->assertFalse($healthy->isDegraded());
        $this->assertFalse($healthy->isUnhealthy());
        $this->assertFalse($healthy->isDisconnected());

        // Degraded status
        $this->assertFalse($degraded->isHealthy());
        $this->assertTrue($degraded->isDegraded());
        $this->assertFalse($degraded->isUnhealthy());
        $this->assertFalse($degraded->isDisconnected());

        // Unhealthy status
        $this->assertFalse($unhealthy->isHealthy());
        $this->assertFalse($unhealthy->isDegraded());
        $this->assertTrue($unhealthy->isUnhealthy());
        $this->assertFalse($unhealthy->isDisconnected());

        // Disconnected status
        $this->assertFalse($disconnected->isHealthy());
        $this->assertFalse($disconnected->isDegraded());
        $this->assertFalse($disconnected->isUnhealthy());
        $this->assertTrue($disconnected->isDisconnected());
    }

    public function test_is_token_expiring_soon()
    {
        // Token expiring in 12 hours (soon)
        $expiringSoon = CloudStorageHealthStatus::healthy(
            'test',
            tokenExpiresAt: Carbon::now()->addHours(12)
        );
        $this->assertTrue($expiringSoon->isTokenExpiringSoon());

        // Token expiring in 48 hours (not soon)
        $notExpiringSoon = CloudStorageHealthStatus::healthy(
            'test',
            tokenExpiresAt: Carbon::now()->addHours(48)
        );
        $this->assertFalse($notExpiringSoon->isTokenExpiringSoon());

        // No token expiration set
        $noExpiration = CloudStorageHealthStatus::healthy('test');
        $this->assertFalse($noExpiration->isTokenExpiringSoon());
    }

    public function test_get_status_description()
    {
        $healthy = CloudStorageHealthStatus::healthy('test');
        $degraded = CloudStorageHealthStatus::degraded('test', 2);
        $unhealthy = CloudStorageHealthStatus::unhealthy('test', 5);
        $disconnected = CloudStorageHealthStatus::disconnected('test');

        $this->assertEquals('Connection is healthy and operational', $healthy->getStatusDescription());
        $this->assertEquals('Connection is experiencing issues (2 consecutive failures)', $degraded->getStatusDescription());
        $this->assertEquals('Connection is unhealthy (5 consecutive failures)', $unhealthy->getStatusDescription());
        $this->assertEquals('Connection is disconnected and requires authentication', $disconnected->getStatusDescription());
    }

    public function test_to_array()
    {
        $lastOperation = Carbon::now()->subHour();
        $tokenExpires = Carbon::now()->addDays(30);
        $errorType = CloudStorageErrorType::NETWORK_ERROR;
        $providerData = ['test' => 'data'];

        $status = CloudStorageHealthStatus::degraded(
            'google-drive',
            2,
            $errorType,
            'Network error',
            $lastOperation,
            $providerData
        );

        $array = $status->toArray();

        $this->assertEquals('google-drive', $array['provider']);
        $this->assertEquals(CloudStorageHealthStatus::STATUS_DEGRADED, $array['status']);
        $this->assertEquals($lastOperation->toISOString(), $array['last_successful_operation']);
        $this->assertEquals(2, $array['consecutive_failures']);
        $this->assertEquals($errorType->value, $array['last_error_type']);
        $this->assertEquals('Network error', $array['last_error_message']);
        $this->assertNull($array['token_expires_at']);
        $this->assertFalse($array['requires_reconnection']);
        $this->assertEquals($providerData, $array['provider_specific_data']);
        $this->assertFalse($array['is_healthy']);
        $this->assertTrue($array['is_degraded']);
        $this->assertFalse($array['is_unhealthy']);
        $this->assertFalse($array['is_disconnected']);
        $this->assertFalse($array['is_token_expiring_soon']);
        $this->assertEquals('Connection is experiencing issues (2 consecutive failures)', $array['status_description']);
    }
}