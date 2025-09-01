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
            'last_successful_operation_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'consecutive_failures' => $this->faker->numberBetween(0, 10),
            'last_error_type' => $this->faker->optional()->randomElement([
                'token_expired',
                'insufficient_permissions',
                'api_quota_exceeded',
                'network_error',
                'file_not_found',
                'folder_access_denied',
                'storage_quota_exceeded',
                'invalid_file_type',
                'unknown_error',
            ]),
            'last_error_message' => $this->faker->optional()->sentence(),
            'token_expires_at' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'requires_reconnection' => $this->faker->boolean(20), // 20% chance of requiring reconnection
            'provider_specific_data' => $this->faker->optional()->randomElement([
                ['folder_id' => $this->faker->uuid(), 'quota_used' => $this->faker->numberBetween(1000, 1000000)],
                ['sync_enabled' => $this->faker->boolean(), 'last_sync' => $this->faker->dateTime()->format('c')],
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
            'consecutive_failures' => 0,
            'last_error_type' => null,
            'last_error_message' => null,
            'requires_reconnection' => false,
            'last_successful_operation_at' => now()->subMinutes($this->faker->numberBetween(1, 60)),
        ]);
    }

    /**
     * Indicate that the health status is degraded.
     */
    public function degraded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'degraded',
            'consecutive_failures' => $this->faker->numberBetween(2, 4),
            'last_error_type' => $this->faker->randomElement(['network_error', 'api_quota_exceeded']),
            'last_error_message' => 'Connection issues detected',
            'requires_reconnection' => false,
        ]);
    }

    /**
     * Indicate that the health status is unhealthy.
     */
    public function unhealthy(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unhealthy',
            'consecutive_failures' => $this->faker->numberBetween(5, 10),
            'last_error_type' => $this->faker->randomElement(['token_expired', 'insufficient_permissions']),
            'last_error_message' => 'Multiple failures detected',
            'requires_reconnection' => true,
        ]);
    }

    /**
     * Indicate that the health status is disconnected.
     */
    public function disconnected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disconnected',
            'consecutive_failures' => 0,
            'last_error_type' => null,
            'last_error_message' => null,
            'requires_reconnection' => false,
            'last_successful_operation_at' => null,
            'token_expires_at' => null,
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
            'last_error_type' => 'token_expired',
            'last_error_message' => 'Access token has expired',
            'requires_reconnection' => true,
        ]);
    }
}
