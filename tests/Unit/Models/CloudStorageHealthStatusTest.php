<?php

namespace Tests\Unit\Models;

use App\Models\CloudStorageHealthStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloudStorageHealthStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_it_can_create_health_status(): void
    {
        $healthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
        ]);

        $this->assertDatabaseHas('cloud_storage_health_statuses', [
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
        ]);

        $this->assertEquals($this->user->id, $healthStatus->user_id);
        $this->assertEquals('google-drive', $healthStatus->provider);
        $this->assertEquals('healthy', $healthStatus->status);
    }

    public function test_it_belongs_to_user(): void
    {
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $healthStatus->user);
        $this->assertEquals($this->user->id, $healthStatus->user->id);
    }

    public function test_it_casts_attributes_correctly(): void
    {
        $providerData = ['folder_id' => 'test123', 'quota_used' => 1024];
        $lastOperation = now()->subHour();
        $tokenExpires = now()->addDay();

        $healthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'provider_specific_data' => $providerData,
            'last_successful_operation_at' => $lastOperation,
            'token_expires_at' => $tokenExpires,
            'requires_reconnection' => true,
            'consecutive_failures' => 3,
        ]);

        $this->assertIsArray($healthStatus->provider_specific_data);
        $this->assertEquals($providerData, $healthStatus->provider_specific_data);
        $this->assertInstanceOf(Carbon::class, $healthStatus->last_successful_operation_at);
        $this->assertInstanceOf(Carbon::class, $healthStatus->token_expires_at);
        $this->assertTrue($healthStatus->requires_reconnection);
        $this->assertEquals(3, $healthStatus->consecutive_failures);
    }

    public function test_is_healthy_method(): void
    {
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'healthy',
        ]);

        $this->assertTrue($healthStatus->isHealthy());
        $this->assertFalse($healthStatus->isDegraded());
        $this->assertFalse($healthStatus->isUnhealthy());
        $this->assertFalse($healthStatus->isDisconnected());
    }

    public function test_is_degraded_method(): void
    {
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'degraded',
        ]);

        $this->assertFalse($healthStatus->isHealthy());
        $this->assertTrue($healthStatus->isDegraded());
        $this->assertFalse($healthStatus->isUnhealthy());
        $this->assertFalse($healthStatus->isDisconnected());
    }

    public function test_is_unhealthy_method(): void
    {
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unhealthy',
        ]);

        $this->assertFalse($healthStatus->isHealthy());
        $this->assertFalse($healthStatus->isDegraded());
        $this->assertTrue($healthStatus->isUnhealthy());
        $this->assertFalse($healthStatus->isDisconnected());
    }

    public function test_is_disconnected_method(): void
    {
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'disconnected',
        ]);

        $this->assertFalse($healthStatus->isHealthy());
        $this->assertFalse($healthStatus->isDegraded());
        $this->assertFalse($healthStatus->isUnhealthy());
        $this->assertTrue($healthStatus->isDisconnected());
    }

    public function test_get_status_message(): void
    {
        $testCases = [
            'healthy' => 'Connection is working properly',
            'degraded' => 'Connection has some issues but is functional',
            'unhealthy' => 'Connection has significant problems',
            'disconnected' => 'Connection is not established',
        ];

        foreach ($testCases as $status => $expectedMessage) {
            $user = User::factory()->create();
            $healthStatus = CloudStorageHealthStatus::factory()->create([
                'user_id' => $user->id,
                'status' => $status,
            ]);

            $this->assertEquals($expectedMessage, $healthStatus->getStatusMessage());
        }
    }

    public function test_get_time_since_last_success(): void
    {
        // Test with no last successful operation
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'last_successful_operation_at' => null,
        ]);

        $this->assertNull($healthStatus->getTimeSinceLastSuccess());

        // Test with last successful operation
        $lastOperation = now()->subHours(2);
        $healthStatus->update(['last_successful_operation_at' => $lastOperation]);

        $timeSince = $healthStatus->getTimeSinceLastSuccess();
        $this->assertIsString($timeSince);
        $this->assertStringContainsString('ago', $timeSince);
    }

    public function test_is_token_expiring_soon(): void
    {
        // Test token expiring in 12 hours (should return true)
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'token_expires_at' => now()->addHours(12),
        ]);

        $this->assertTrue($healthStatus->isTokenExpiringSoon());

        // Test token expiring in 48 hours (should return false)
        $healthStatus->update(['token_expires_at' => now()->addHours(48)]);
        $this->assertFalse($healthStatus->isTokenExpiringSoon());

        // Test no token expiration (should return false)
        $healthStatus->update(['token_expires_at' => null]);
        $this->assertFalse($healthStatus->isTokenExpiringSoon());
    }

    public function test_is_token_expired(): void
    {
        // Test expired token
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'token_expires_at' => now()->subHour(),
        ]);

        $this->assertTrue($healthStatus->isTokenExpired());

        // Test valid token
        $healthStatus->update(['token_expires_at' => now()->addHour()]);
        $this->assertFalse($healthStatus->isTokenExpired());

        // Test no token expiration
        $healthStatus->update(['token_expires_at' => null]);
        $this->assertFalse($healthStatus->isTokenExpired());
    }

    public function test_unique_constraint_on_user_and_provider(): void
    {
        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'unhealthy',
        ]);
    }

    public function test_it_can_create_health_status_with_consolidated_fields(): void
    {
        $operationalTestResult = ['test' => 'success', 'response_time' => 150];
        $lastTokenRefresh = now()->subMinutes(30);

        $healthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'last_token_refresh_attempt_at' => $lastTokenRefresh,
            'token_refresh_failures' => 1,
            'operational_test_result' => $operationalTestResult,
        ]);

        $this->assertDatabaseHas('cloud_storage_health_statuses', [
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'consolidated_status' => 'healthy',
            'token_refresh_failures' => 1,
        ]);

        $this->assertEquals('healthy', $healthStatus->consolidated_status);
        $this->assertEquals(1, $healthStatus->token_refresh_failures);
        $this->assertInstanceOf(Carbon::class, $healthStatus->last_token_refresh_attempt_at);
        $this->assertIsArray($healthStatus->operational_test_result);
        $this->assertEquals($operationalTestResult, $healthStatus->operational_test_result);
    }

    public function test_it_casts_new_attributes_correctly(): void
    {
        $operationalTestResult = ['api_call' => 'success', 'latency' => 200];
        $lastTokenRefresh = now()->subHour();

        $healthStatus = CloudStorageHealthStatus::create([
            'user_id' => $this->user->id,
            'provider' => 'google-drive',
            'last_token_refresh_attempt_at' => $lastTokenRefresh,
            'token_refresh_failures' => 2,
            'operational_test_result' => $operationalTestResult,
        ]);

        $this->assertInstanceOf(Carbon::class, $healthStatus->last_token_refresh_attempt_at);
        $this->assertIsInt($healthStatus->token_refresh_failures);
        $this->assertIsArray($healthStatus->operational_test_result);
        $this->assertEquals($operationalTestResult, $healthStatus->operational_test_result);
    }

    public function test_get_consolidated_status_message(): void
    {
        $testCases = [
            'healthy' => 'Connection is working properly',
            'authentication_required' => 'Please reconnect your account',
            'connection_issues' => 'Experiencing connectivity problems',
            'not_connected' => 'Account not connected',
        ];

        foreach ($testCases as $status => $expectedMessage) {
            $user = User::factory()->create();
            $healthStatus = CloudStorageHealthStatus::factory()->create([
                'user_id' => $user->id,
                'consolidated_status' => $status,
            ]);

            $this->assertEquals($expectedMessage, $healthStatus->getConsolidatedStatusMessage());
        }
    }

    public function test_get_consolidated_status_message_with_unknown_status(): void
    {
        // Create a health status with a valid enum value first
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'consolidated_status' => 'healthy',
        ]);

        // Manually set an invalid status to test the default case
        // This simulates a scenario where the enum might be expanded in the future
        $healthStatus->setAttribute('consolidated_status', 'unknown_status');

        $this->assertEquals('Status unknown', $healthStatus->getConsolidatedStatusMessage());
    }

    public function test_is_token_refresh_working_returns_true_when_failures_below_threshold(): void
    {
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'token_refresh_failures' => 2,
        ]);

        $this->assertTrue($healthStatus->isTokenRefreshWorking());
    }

    public function test_is_token_refresh_working_returns_false_when_failures_at_threshold(): void
    {
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'token_refresh_failures' => 3,
        ]);

        $this->assertFalse($healthStatus->isTokenRefreshWorking());
    }

    public function test_is_token_refresh_working_returns_false_when_failures_above_threshold(): void
    {
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'token_refresh_failures' => 5,
        ]);

        $this->assertFalse($healthStatus->isTokenRefreshWorking());
    }

    public function test_is_token_refresh_working_returns_true_when_no_failures(): void
    {
        $healthStatus = CloudStorageHealthStatus::factory()->create([
            'user_id' => $this->user->id,
            'token_refresh_failures' => 0,
        ]);

        $this->assertTrue($healthStatus->isTokenRefreshWorking());
    }
}