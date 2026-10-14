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

    private User $uploader;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create([
            'email' => 'admin@company.com',
            'role' => UserRole::ADMIN,
        ]);

        $this->uploader = User::factory()->create([
            'email' => 'client@example.com',
            'role' => UserRole::CLIENT,
        ]);
    }

    public function test_chunk_endpoint_accepts_more_than_ten_requests_per_minute()
    {
        // 25 chunks = a ~125MB file
        for ($i = 0; $i < 25; $i++) {
            $response = $this->actingAs($this->uploader)->postJson('/upload/admin/chunk');

            $this->assertNotEquals(429, $response->getStatusCode(), 'Chunk request #'.($i + 1).' was rate limited');
        }
    }

    public function test_chunk_requests_do_not_consume_the_shared_upload_limit()
    {
        for ($i = 0; $i < 15; $i++) {
            $this->actingAs($this->uploader)->postJson('/upload/admin/chunk');
        }

        $response = $this->actingAs($this->uploader)->postJson('/upload/admin/batch-complete');

        $this->assertNotEquals(429, $response->getStatusCode());
    }

    public function test_guests_cannot_upload_chunks()
    {
        $this->postJson('/upload/admin/chunk', ['dzuuid' => 'abc', 'dztotalchunkcount' => 1])
            ->assertStatus(401);
    }

    public function test_oversized_files_are_rejected_on_the_first_chunk()
    {
        config(['upload.limits.max_file_size' => 100 * 1024 * 1024]);

        $this->actingAs($this->uploader)
            ->postJson('/upload/admin/chunk', ['dztotalfilesize' => 200 * 1024 * 1024])
            ->assertStatus(422);
    }
}
