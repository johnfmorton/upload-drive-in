<?php

namespace Tests\Feature\Shares;

use App\Models\Share;
use App\Models\ShareFile;
use App\Services\ShareStorageService;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PublicShareControllerTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQ';

    private $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = Mockery::mock(ShareStorageService::class);
        $this->app->instance(ShareStorageService::class, $this->storage);
    }

    private function readyShare(array $attributes = []): Share
    {
        return Share::factory()->ready(self::TOKEN)->create($attributes);
    }

    public function test_unknown_link_is_a_404()
    {
        $this->get('/s/'.str_repeat('x', 43))
            ->assertNotFound()
            ->assertSee(__('messages.shares_public_unavailable_title'));
    }

    public function test_ended_link_is_a_410_and_looks_the_same()
    {
        $this->readyShare(['status' => Share::STATUS_REVOKED]);

        $this->get('/s/'.self::TOKEN)
            ->assertStatus(410)
            ->assertSee(__('messages.shares_public_unavailable_title'));
    }

    public function test_expired_link_is_unavailable_before_the_cleanup_job_runs()
    {
        $this->readyShare(['expires_at' => now()->subMinute()]);

        $this->get('/s/'.self::TOKEN)->assertStatus(410);
    }

    public function test_share_page_lists_uploaded_files_and_is_not_indexed()
    {
        $share = $this->readyShare(['title' => 'Project photos']);
        ShareFile::factory()->uploaded()->create(['share_id' => $share->id, 'original_filename' => 'cover.jpg']);
        ShareFile::factory()->create(['share_id' => $share->id, 'original_filename' => 'not-yet.jpg']);

        $this->get('/s/'.self::TOKEN)
            ->assertOk()
            ->assertSee('Project photos')
            ->assertSee('cover.jpg')
            ->assertDontSee('not-yet.jpg')
            ->assertDontSee('app.js')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->assertSame(1, $share->accessLogs()->where('event', 'view')->count());
    }

    public function test_password_protected_share_needs_the_password()
    {
        $share = $this->readyShare(['password' => 'open-sesame']);
        ShareFile::factory()->uploaded()->create(['share_id' => $share->id, 'original_filename' => 'secret.pdf']);

        $this->get('/s/'.self::TOKEN)
            ->assertOk()
            ->assertSee(__('messages.shares_public_password_title'))
            ->assertDontSee('secret.pdf');

        $this->from('/s/'.self::TOKEN)
            ->post('/s/'.self::TOKEN.'/unlock', ['password' => 'wrong'])
            ->assertSessionHasErrors('password');
        $this->assertSame(1, $share->accessLogs()->where('event', 'bad_password')->count());

        $this->post('/s/'.self::TOKEN.'/unlock', ['password' => 'open-sesame'])
            ->assertRedirect('/s/'.self::TOKEN);

        $this->get('/s/'.self::TOKEN)->assertSee('secret.pdf');
    }

    public function test_password_attempts_are_limited()
    {
        $this->readyShare(['password' => 'open-sesame']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/s/'.self::TOKEN.'/unlock', ['password' => 'wrong']);
        }

        // Even the right password is refused during the lockout
        $this->from('/s/'.self::TOKEN)
            ->post('/s/'.self::TOKEN.'/unlock', ['password' => 'open-sesame'])
            ->assertSessionHasErrors('password');
    }

    public function test_download_without_unlocking_goes_back_to_the_password_page()
    {
        $share = $this->readyShare(['password' => 'open-sesame']);
        $file = ShareFile::factory()->uploaded()->create(['share_id' => $share->id]);

        $this->get('/s/'.self::TOKEN."/files/{$file->id}")
            ->assertRedirect('/s/'.self::TOKEN);
    }

    public function test_s3_download_redirects_to_a_presigned_url_and_counts()
    {
        $share = $this->readyShare();
        $file = ShareFile::factory()->uploaded()->create(['share_id' => $share->id]);

        $this->storage->shouldReceive('temporaryS3Url')->once()->andReturn('https://bucket.s3.amazonaws.com/x?sig=1');

        $this->get('/s/'.self::TOKEN."/files/{$file->id}")
            ->assertRedirect('https://bucket.s3.amazonaws.com/x?sig=1');

        $this->assertSame(1, $share->fresh()->download_count);
        $this->assertNotNull($share->fresh()->first_downloaded_at);
    }

    public function test_files_from_other_shares_cannot_be_downloaded()
    {
        $this->readyShare();
        $otherFile = ShareFile::factory()->uploaded()->create();

        $this->get('/s/'.self::TOKEN."/files/{$otherFile->id}")->assertNotFound();
    }

    public function test_drive_download_streams_with_range_support()
    {
        $share = $this->readyShare(['provider' => 'google-drive']);
        $file = ShareFile::factory()->uploaded()->create([
            'share_id' => $share->id,
            'original_filename' => 'Résumé final.pdf',
            'file_size' => 11,
        ]);

        $this->storage->shouldReceive('openDriveStream')
            ->once()
            ->with(Mockery::on(fn ($f) => $f->is($file)), 'bytes=6-')
            ->andReturn(new PsrResponse(206, [
                'Content-Length' => '5',
                'Content-Range' => 'bytes 6-10/11',
            ], Utils::streamFor('world')));

        $response = $this->get('/s/'.self::TOKEN."/files/{$file->id}", ['Range' => 'bytes=6-']);

        $response->assertStatus(206)
            ->assertHeader('Content-Range', 'bytes 6-10/11')
            ->assertHeader('Accept-Ranges', 'bytes');
        $this->assertStringContainsString("filename*=UTF-8''R%C3%A9sum%C3%A9%20final.pdf", $response->headers->get('Content-Disposition'));
        $this->assertSame('world', $response->streamedContent());

        // A resumed download isn't counted as a new one
        $this->assertSame(0, $share->fresh()->download_count);
    }

    public function test_drive_error_returns_bad_gateway()
    {
        $share = $this->readyShare(['provider' => 'google-drive']);
        $file = ShareFile::factory()->uploaded()->create(['share_id' => $share->id]);

        $this->storage->shouldReceive('openDriveStream')->andReturn(new PsrResponse(403));

        $this->get('/s/'.self::TOKEN."/files/{$file->id}")->assertStatus(502);
    }

    public function test_malformed_tokens_do_not_match_the_route()
    {
        $this->get('/s/short')->assertNotFound();
    }
}
