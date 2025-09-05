<?php

namespace Database\Factories;

use App\Models\GoogleDriveToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GoogleDriveToken>
 */
class GoogleDriveTokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GoogleDriveToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'access_token' => 'ya29.' . $this->faker->regexify('[A-Za-z0-9_-]{100}'),
            'refresh_token' => '1//0' . $this->faker->regexify('[A-Za-z0-9_-]{80}'),
            'token_type' => 'Bearer',
            'expires_at' => now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/drive.file', 'https://www.googleapis.com/auth/drive'],
            'last_refresh_attempt_at' => null,
            'refresh_failure_count' => 0,
            'last_successful_refresh_at' => null,
            'proactive_refresh_scheduled_at' => null,
            'health_check_failures' => 0,
            'requires_user_intervention' => false,
            'last_notification_sent_at' => null,
            'notification_failure_count' => 0,
        ];
    }

    /**
     * Indicate that the token is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that the token has no refresh token.
     */
    public function withoutRefreshToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'refresh_token' => null,
        ]);
    }

    /**
     * Indicate that the token is valid for a long time.
     */
    public function longLived(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * Indicate that the token is expiring soon.
     */
    public function expiringSoon(int $minutes = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addMinutes($minutes),
        ]);
    }

    /**
     * Indicate that the token has failed refresh attempts.
     */
    public function withRefreshFailures(int $count = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'refresh_failure_count' => $count,
            'last_refresh_attempt_at' => now()->subMinutes(30),
            'requires_user_intervention' => $count >= 5,
        ]);
    }

    /**
     * Indicate that the token requires user intervention.
     */
    public function requiresIntervention(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_user_intervention' => true,
            'refresh_failure_count' => 5,
            'last_refresh_attempt_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that proactive refresh is scheduled.
     */
    public function withScheduledRefresh(): static
    {
        return $this->state(fn (array $attributes) => [
            'proactive_refresh_scheduled_at' => now()->addMinutes(15),
        ]);
    }
}