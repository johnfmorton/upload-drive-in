<?php

namespace Database\Factories;

use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FileUpload>
 */
class FileUploadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FileUpload::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_user_id' => User::factory(),
            'company_user_id' => User::factory(),
            'filename' => $this->faker->uuid() . '.jpg',
            'original_filename' => $this->faker->word() . '.jpg',
            'provider_file_id' => $this->faker->uuid(),
            'storage_provider' => 'google-drive',
            'cloud_storage_provider' => 'google-drive',
            'message' => $this->faker->sentence(),
            'validation_method' => 'email',
            'mime_type' => 'image/jpeg',
            'file_size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'chunk_size' => 1048576, // 1MB
            'total_chunks' => 1,
            'google_drive_file_id' => $this->faker->uuid(),
            'uploaded_by_user_id' => null,
            'email' => $this->faker->email(),
        ];
    }

    /**
     * Indicate that the file is an image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => $this->faker->randomElement([
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
            ]),
            'original_filename' => $this->faker->word() . '.' . $this->faker->randomElement(['jpg', 'png', 'gif', 'webp']),
        ]);
    }

    /**
     * Indicate that the file is a PDF.
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'application/pdf',
            'original_filename' => $this->faker->word() . '.pdf',
        ]);
    }

    /**
     * Indicate that the file is a text file.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'text/plain',
            'original_filename' => $this->faker->word() . '.txt',
        ]);
    }

    /**
     * Indicate that the file is not previewable.
     */
    public function notPreviewable(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => $this->faker->randomElement([
                'application/zip',
                'video/mp4',
                'audio/mpeg',
                'application/vnd.ms-excel',
            ]),
            'original_filename' => $this->faker->word() . '.' . $this->faker->randomElement(['zip', 'mp4', 'mp3', 'xls']),
        ]);
    }

    /**
     * Indicate that the file is pending upload.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'google_drive_file_id' => null,
        ]);
    }

    /**
     * Indicate that the file upload is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'google_drive_file_id' => $this->faker->uuid(),
        ]);
    }

    /**
     * Indicate that the file has a cloud storage error.
     */
    public function withCloudStorageError(string $errorType = null, array $errorContext = null): static
    {
        return $this->state(fn (array $attributes) => [
            'cloud_storage_error_type' => $errorType ?? $this->faker->randomElement([
                'token_expired',
                'insufficient_permissions',
                'api_quota_exceeded',
                'network_error',
                'storage_quota_exceeded',
            ]),
            'cloud_storage_error_context' => $errorContext ?? [
                'message' => $this->faker->sentence(),
                'code' => $this->faker->numberBetween(400, 500),
            ],
            'connection_health_at_failure' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'retry_recommended_at' => $this->faker->dateTimeBetween('now', '+1 hour'),
        ]);
    }

    /**
     * Indicate that the file has a recoverable cloud storage error.
     */
    public function withRecoverableError(): static
    {
        return $this->withCloudStorageError(
            $this->faker->randomElement(['network_error', 'service_unavailable', 'timeout', 'api_quota_exceeded'])
        );
    }

    /**
     * Indicate that the file has a cloud storage error requiring user intervention.
     */
    public function withErrorRequiringIntervention(): static
    {
        return $this->withCloudStorageError(
            $this->faker->randomElement(['token_expired', 'insufficient_permissions', 'storage_quota_exceeded', 'invalid_credentials'])
        );
    }
}