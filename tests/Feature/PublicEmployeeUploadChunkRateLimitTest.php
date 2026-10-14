<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chunked uploads on /upload/{name} send one request per 5MB chunk. They used to
 * share the throttle:10,1 group, so any file over ~50MB was cut off with a 429
 * after the tenth chunk.
 */
class PublicEmployeeUploadChunkRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_chunk_endpoint_accepts_more_than_ten_requests_per_minute()
    {
        User::factory()->create([
            'email' => 'admin@company.com',
            'role' => UserRole::ADMIN,
        ]);

        // 25 chunks = a ~125MB file
        for ($i = 0; $i < 25; $i++) {
            $response = $this->postJson('/upload/admin/chunk');

            $this->assertNotEquals(429, $response->getStatusCode(), 'Chunk request #'.($i + 1).' was rate limited');
        }
    }

    public function test_chunk_requests_do_not_consume_the_shared_upload_limit()
    {
        User::factory()->create([
            'email' => 'admin@company.com',
            'role' => UserRole::ADMIN,
        ]);

        for ($i = 0; $i < 15; $i++) {
            $this->postJson('/upload/admin/chunk');
        }

        $response = $this->postJson('/upload/admin/batch-complete');

        $this->assertNotEquals(429, $response->getStatusCode());
    }
}
