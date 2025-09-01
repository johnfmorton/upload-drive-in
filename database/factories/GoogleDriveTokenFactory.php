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
}