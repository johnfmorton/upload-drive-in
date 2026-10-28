<?php

namespace Database\Factories;

use App\Models\Share;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Share>
 */
class ShareFactory extends Factory
{
    protected $model = Share::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token_hash' => Share::hashToken(Str::random(43)),
            'title' => $this->faker->sentence(3),
            'message' => null,
            'password' => null,
            'provider' => 'amazon-s3',
            'cloud_user_id' => fn (array $attributes) => $attributes['user_id'],
            'status' => Share::STATUS_DRAFT,
            'expires_at' => now()->addDays(14),
        ];
    }

    /**
     * A share whose link works, with the given token.
     */
    public function ready(string $token = 'test-token'): static
    {
        return $this->state(fn () => [
            'token_hash' => Share::hashToken($token),
            'status' => Share::STATUS_READY,
            'ready_at' => now(),
        ]);
    }

    public function uploading(): static
    {
        return $this->state(fn () => ['status' => Share::STATUS_UPLOADING]);
    }
}
