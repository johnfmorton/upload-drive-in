<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CloudStorageHealthStatus>
 */
class CloudStorageHealthStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'provider' => $this->faker->randomElement(['google-drive', 'dropbox', 'onedrive']),
            'status' => $this->faker->randomElement(['healthy', 'degraded', 'unhealthy', 'disconnected']),
            'consolidated_status' => $this->faker->randomElement(['healthy', 'authentication_required', 'connection_issues', 'not_connected']),
            'last_successful_operation_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'consecutive_failures' => $this->faker->numberBetween(0, 10),
            'last_error_type' => $this->faker->optional()->randomElement([
                'token_expired',
                'token_refresh_rate_limited',
                'insufficient_permissions',
                'api_quota_exceeded',
                'network_error',
                'file_not_found',
                'folder_access_denied',
                'storage_quota_exceeded',
                'invalid_file_type',
                'service_unavailable',
                'invalid_credentials',
                'timeout',
                'unknown_error',
            ]),
            'last_error_message' => $this->faker->optional()->randomElement([
                'Too many token refresh attempts. Please try again later.',
                'Authentication required. Please reconnect your account.',
                'Connected and working properly',
                'Account not connected. Please set up your cloud storage connection.',
                'Connection issue detected. Please test your connection.',
                'Network connection timeout after 30 seconds',
                'API rate limit exceeded - quota reset in 45 minutes',
                'OAuth token has expired and refresh failed',
                'Insufficient permissions - full access required',
                'Storage quota exceeded - 15GB limit reached',
                'Temporary service unavailability detected',
                'Invalid authentication configuration',
                'Operation timed out after 60 seconds',
                null,
            ]),
            'token_expires_at' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'last_token_refresh_attempt_at' => $this->faker->optional()->dateTimeBetween('-1 day', 'now'),
            'token_refresh_failures' => $this->faker->numberBetween(0, 5),
            'operational_test_result' => $this->faker->optional()->randomElement([
                ['test' => 'success', 'response_time' => $this->faker->numberBetween(50, 500)],
                ['test' => 'failed', 'error' => 'Authentication failed'],
                ['test' => 'failed', 'error' => 'Rate limit exceeded'],
                ['test' => 'failed', 'error' => 'Network timeout'],
                ['api_call' => 'success', 'latency' => $this->faker->numberBetween(100, 1000)],
                null,
            ]),
            'requires_reconnection' => $this->faker->boolean(20), // 20% chance of requiring reconnection
            'provider_specific_data' => $this->faker->optional()->randomElement([
                ['folder_id' => $this->faker->uuid(), 'quota_used' => $this->faker->numberBetween(1000, 1000000)],
                ['sync_enabled' => $this->faker->boolean(), 'last_sync' => $this->faker->dateTime()->format('c')],
                ['rate_limited' => false, 'retry_after' => null],
                null,
            ]),
        ];
    }

    /**
     * Indicate that the health status is healthy.
     */
    public function healthy(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'healthy',
            'consolidated_status' => 'healthy',
            'consecutive_failures' => 0,
            'token_refresh_failures' => 0,
            'last_error_type' => null,
            'last_error_message' => null,
            'requires_reconnection' => false,
            'last_successful_operation_at' => now()->subMinutes($this->faker->numberBetween(1, 60)),
            'operational_test_result' => ['test' => 'success', 'response_time' => $this->faker->numberBetween(50, 200)],
        ]);
    }

    /**
     * Indicate that the health status is degraded.
     */
    public function degraded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'degraded',
            'consolidated_status' => 'connection_issues',
            'consecutive_failures' => $this->faker->numberBetween(2, 4),
            'token_refresh_failures' => $this->faker->numberBetween(1, 2),
            'last_error_type' => $this->faker->randomElement(['network_error', 'api_quota_exceeded', 'timeout', 'service_unavailable']),
            'last_error_message' => $this->faker->randomElement([
                'Connection issue detected. Please test your connection.',
                'Network connection timeout after 30 seconds',
                'API rate limit exceeded - quota reset in 45 minutes',
                'Temporary service unavailability detected',
                'Operation timed out after 60 seconds',
                'Upload failed due to network instability'
            ]),
            'requires_reconnection' => false,
            'operational_test_result' => ['test' => 'failed', 'error' => 'Network timeout'],
        ]);
    }

    /**
     * Indicate that the health status is unhealthy.
     */
    public function unhealthy(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'consecutive_failures' => $this->faker->numberBetween(5, 10),
            'token_refresh_failures' => $this->faker->numberBetween(3, 5),
            'last_error_type' => $this->faker->randomElement(['token_expired', 'insufficient_permissions', 'invalid_credentials']),
            'last_error_message' => $this->faker->randomElement([
                'Authentication required. Please reconnect your account.',
                'OAuth token has expired and refresh failed',
                'Insufficient permissions - full access required',
                'Invalid authentication configuration',
                'Authentication failed after multiple attempts',
                'Account access has been revoked'
            ]),
            'requires_reconnection' => true,
            'operational_test_result' => ['test' => 'failed', 'error' => 'Authentication failed'],
        ]);
    }

    /**
     * Indicate that the health status is disconnected.
     */
    public function disconnected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disconnected',
            'consolidated_status' => 'not_connected',
            'consecutive_failures' => 0,
            'token_refresh_failures' => 0,
            'last_error_type' => null,
            'last_error_message' => 'Account not connected. Please set up your cloud storage connection.',
            'requires_reconnection' => false,
            'last_successful_operation_at' => null,
            'token_expires_at' => null,
            'last_token_refresh_attempt_at' => null,
            'operational_test_result' => null,
        ]);
    }

    /**
     * Indicate that the token is expiring soon.
     */
    public function tokenExpiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'token_expires_at' => now()->addHours($this->faker->numberBetween(1, 23)),
        ]);
    }

    /**
     * Indicate that the token has expired.
     */
    public function tokenExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'token_expires_at' => now()->subHours($this->faker->numberBetween(1, 24)),
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'token_refresh_failures' => $this->faker->numberBetween(3, 5),
            'last_token_refresh_attempt_at' => now()->subMinutes($this->faker->numberBetween(5, 60)),
            'last_error_type' => 'token_expired',
            'last_error_message' => 'Authentication required. Please reconnect your account.',
            'requires_reconnection' => true,
            'operational_test_result' => ['test' => 'failed', 'error' => 'Authentication failed'],
        ]);
    }

    /**
     * Indicate that the provider is rate limited.
     */
    public function rateLimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'degraded',
            'consolidated_status' => 'connection_issues',
            'consecutive_failures' => $this->faker->numberBetween(6, 10),
            'token_refresh_failures' => $this->faker->numberBetween(5, 8),
            'last_token_refresh_attempt_at' => now()->subMinutes($this->faker->numberBetween(1, 30)),
            'last_error_type' => 'token_refresh_rate_limited',
            'last_error_message' => 'Too many token refresh attempts. Please try again later.',
            'requires_reconnection' => false,
            'operational_test_result' => ['test' => 'failed', 'error' => 'Rate limit exceeded'],
            'provider_specific_data' => [
                'rate_limited' => true,
                'rate_limit_reset_at' => now()->addMinutes($this->faker->numberBetween(5, 60))->toISOString(),
                'retry_after' => $this->faker->numberBetween(300, 3600), // 5 minutes to 1 hour
                'remaining_attempts' => 0,
            ],
        ]);
    }

    /**
     * Indicate that the provider has specific authentication errors.
     */
    public function authenticationRequired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'consecutive_failures' => $this->faker->numberBetween(3, 7),
            'token_refresh_failures' => $this->faker->numberBetween(2, 5),
            'last_error_type' => $this->faker->randomElement(['token_expired', 'invalid_credentials', 'insufficient_permissions']),
            'last_error_message' => 'Authentication required. Please reconnect your account.',
            'requires_reconnection' => true,
            'operational_test_result' => ['test' => 'failed', 'error' => 'Authentication failed'],
        ]);
    }

    /**
     * Indicate that the provider has realistic connection issues.
     */
    public function connectionIssues(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'degraded',
            'consolidated_status' => 'connection_issues',
            'consecutive_failures' => $this->faker->numberBetween(2, 5),
            'token_refresh_failures' => $this->faker->numberBetween(0, 2),
            'last_error_type' => $this->faker->randomElement(['network_error', 'timeout', 'api_quota_exceeded', 'service_unavailable']),
            'last_error_message' => $this->faker->randomElement([
                'Connection issue detected. Please test your connection.',
                'Network connection timeout after 30 seconds',
                'API rate limit exceeded - quota reset in 45 minutes',
                'Temporary service unavailability detected',
                'Operation timed out after 60 seconds',
                'Upload failed due to network instability'
            ]),
            'requires_reconnection' => false,
            'operational_test_result' => ['test' => 'failed', 'error' => 'Network timeout'],
        ]);
    }

    /**
     * Indicate that the provider has storage quota issues.
     */
    public function quotaExceeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'degraded',
            'consolidated_status' => 'connection_issues',
            'consecutive_failures' => $this->faker->numberBetween(3, 6),
            'token_refresh_failures' => 0,
            'last_error_type' => $this->faker->randomElement(['storage_quota_exceeded', 'api_quota_exceeded']),
            'last_error_message' => $this->faker->randomElement([
                'Storage quota exceeded - 15GB limit reached',
                'API quota exceeded - daily limit of 1000 requests reached',
                'Upload quota exceeded for current billing period',
                'Connection issue detected. Please test your connection.'
            ]),
            'requires_reconnection' => false,
            'operational_test_result' => ['test' => 'failed', 'error' => 'Quota exceeded'],
        ]);
    }

    /**
     * Indicate that the provider has multiple consecutive failures.
     */
    public function multipleFailures(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'degraded',
            'consolidated_status' => 'connection_issues',
            'consecutive_failures' => $this->faker->numberBetween(4, 8),
            'token_refresh_failures' => $this->faker->numberBetween(2, 4),
            'last_error_type' => $this->faker->randomElement(['network_error', 'timeout', 'service_unavailable', 'api_quota_exceeded']),
            'last_error_message' => 'Connection issue detected. Please test your connection.',
            'requires_reconnection' => false,
            'last_successful_operation_at' => now()->subHours($this->faker->numberBetween(2, 24)),
            'operational_test_result' => ['test' => 'failed', 'error' => 'Multiple failures detected'],
        ]);
    }

    /**
     * Indicate that the provider has network connectivity issues.
     */
    public function networkError(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'degraded',
            'consolidated_status' => 'connection_issues',
            'consecutive_failures' => $this->faker->numberBetween(1, 3),
            'token_refresh_failures' => 0,
            'last_error_type' => 'network_error',
            'last_error_message' => $this->faker->randomElement([
                'Network connection timeout after 30 seconds',
                'Connection issue detected. Please test your connection.',
                'Upload failed due to network instability'
            ]),
            'requires_reconnection' => false,
            'operational_test_result' => ['test' => 'failed', 'error' => 'Network timeout'],
        ]);
    }

    /**
     * Indicate that the provider service is temporarily unavailable.
     */
    public function serviceUnavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'degraded',
            'consolidated_status' => 'connection_issues',
            'consecutive_failures' => $this->faker->numberBetween(2, 5),
            'token_refresh_failures' => 0,
            'last_error_type' => 'service_unavailable',
            'last_error_message' => $this->faker->randomElement([
                'Temporary service unavailability detected',
                'Connection issue detected. Please test your connection.'
            ]),
            'requires_reconnection' => false,
            'operational_test_result' => ['test' => 'failed', 'error' => 'Service unavailable'],
        ]);
    }

    /**
     * Indicate that the provider has insufficient permissions.
     */
    public function insufficientPermissions(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'consecutive_failures' => $this->faker->numberBetween(3, 6),
            'token_refresh_failures' => $this->faker->numberBetween(1, 3),
            'last_error_type' => 'insufficient_permissions',
            'last_error_message' => 'Authentication required. Please reconnect your account.',
            'requires_reconnection' => true,
            'operational_test_result' => ['test' => 'failed', 'error' => 'Insufficient permissions'],
        ]);
    }

    /**
     * Indicate that the provider has timeout issues.
     */
    public function timeout(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'degraded',
            'consolidated_status' => 'connection_issues',
            'consecutive_failures' => $this->faker->numberBetween(2, 4),
            'token_refresh_failures' => 0,
            'last_error_type' => 'timeout',
            'last_error_message' => $this->faker->randomElement([
                'Operation timed out after 60 seconds',
                'Connection issue detected. Please test your connection.'
            ]),
            'requires_reconnection' => false,
            'operational_test_result' => ['test' => 'failed', 'error' => 'Operation timeout'],
        ]);
    }

    /**
     * Indicate that the provider has invalid credentials.
     */
    public function invalidCredentials(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unhealthy',
            'consolidated_status' => 'authentication_required',
            'consecutive_failures' => $this->faker->numberBetween(4, 8),
            'token_refresh_failures' => $this->faker->numberBetween(3, 6),
            'last_error_type' => 'invalid_credentials',
            'last_error_message' => 'Authentication required. Please reconnect your account.',
            'requires_reconnection' => true,
            'operational_test_result' => ['test' => 'failed', 'error' => 'Invalid credentials'],
        ]);
    }
}
