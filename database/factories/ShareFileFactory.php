<?php

namespace Database\Factories;

use App\Models\Share;
use App\Models\ShareFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShareFile>
 */
class ShareFileFactory extends Factory
{
    protected $model = ShareFile::class;

    public function definition(): array
    {
        return [
            'share_id' => Share::factory(),
            'original_filename' => $this->faker->word().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'local_path' => null,
            'provider_file_id' => null,
            'status' => ShareFile::STATUS_PENDING,
        ];
    }

    public function uploaded(): static
    {
        return $this->state(fn () => [
            'status' => ShareFile::STATUS_UPLOADED,
            'provider_file_id' => 'outgoing-share/'.$this->faker->uuid().'.pdf',
        ]);
    }
}
