<?php

namespace Tests\Feature\Shares;

use App\Events\ShareFirstDownloaded;
use App\Models\Share;
use App\Models\ShareFile;
use App\Services\ShareStorageService;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;
use ZipArchive;

/**
 * "Download all": the zip built from a share's files while it downloads.
 */
class ShareZipDownloadTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQ';

    private $storage;

    /** @var array<int, string> File contents by share file ID */
    private array $contents = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = Mockery::mock(ShareStorageService::class);
        $this->storage->shouldReceive('openFileStream')
            ->andReturnUsing(fn (ShareFile $file) => Utils::streamFor($this->contents[$file->id]));
        $this->app->instance(ShareStorageService::class, $this->storage);
    }

    private function shareWithFiles(array $files, array $attributes = []): Share
    {
        $share = Share::factory()->ready(self::TOKEN)->create($attributes + ['title' => 'Project photos']);

        foreach ($files as $name => $content) {
            $file = ShareFile::factory()->uploaded()->create([
                'share_id' => $share->id,
                'original_filename' => $name,
                'file_size' => strlen($content),
            ]);
            $this->contents[$file->id] = $content;
        }

        return $share;
    }

    /**
     * @return array<string, string> Entry name => contents
     */
    private function unzip(string $bytes): array
    {
        $path = tempnam(sys_get_temp_dir(), 'share-zip-');
        file_put_contents($path, $bytes);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'The download is not a valid zip');

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $entries[$name] = $zip->getFromName($name);
        }

        $zip->close();
        unlink($path);

        return $entries;
    }

    public function test_download_all_streams_a_valid_zip_with_an_exact_length()
    {
        Event::fake([ShareFirstDownloaded::class]);
        $this->shareWithFiles(['b.txt' => 'second file', 'a.txt' => str_repeat('first ', 1000)]);

        $response = $this->get('/s/'.self::TOKEN.'/zip');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/zip')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('X-Accel-Buffering', 'no');
        $this->assertStringContainsString('filename="Project photos.zip"', $response->headers->get('Content-Disposition'));

        $bytes = $response->streamedContent();
        $this->assertSame(strlen($bytes), (int) $response->headers->get('Content-Length'));
        $this->assertSame([
            'a.txt' => str_repeat('first ', 1000),
            'b.txt' => 'second file',
        ], $this->unzip($bytes));
    }

    public function test_files_with_the_same_name_get_unique_names_in_the_zip()
    {
        Event::fake([ShareFirstDownloaded::class]);
        $this->shareWithFiles(['report.pdf' => 'one', 'Report.pdf' => 'two', 'folder/notes' => 'three']);

        $entries = $this->unzip($this->get('/s/'.self::TOKEN.'/zip')->streamedContent());

        $this->assertEqualsCanonicalizing(['folder_notes', 'Report.pdf', 'report (2).pdf'], array_keys($entries));
    }

    public function test_download_all_counts_as_a_download_and_sends_the_first_download_event()
    {
        Event::fake([ShareFirstDownloaded::class]);
        $share = $this->shareWithFiles(['a.txt' => 'a', 'b.txt' => 'b']);

        $this->get('/s/'.self::TOKEN.'/zip')->streamedContent();

        $share->refresh();
        $this->assertSame(1, $share->download_count);
        $this->assertNotNull($share->first_downloaded_at);
        $this->assertSame(1, $share->accessLogs()->where('event', 'zip')->whereNull('share_file_id')->count());
        // Per-file counts are for single-file downloads only
        $this->assertSame(0, (int) $share->files()->sum('download_count'));
        Event::assertDispatched(ShareFirstDownloaded::class, fn ($event) => $event->file === null);
    }

    public function test_head_request_gets_headers_but_is_not_counted()
    {
        Event::fake([ShareFirstDownloaded::class]);
        $share = $this->shareWithFiles(['a.txt' => 'a', 'b.txt' => 'b']);

        $this->head('/s/'.self::TOKEN.'/zip')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/zip');

        $this->assertSame(0, $share->fresh()->download_count);
        Event::assertNotDispatched(ShareFirstDownloaded::class);
    }

    public function test_only_uploaded_files_go_in_the_zip()
    {
        Event::fake([ShareFirstDownloaded::class]);
        $share = $this->shareWithFiles(['a.txt' => 'a', 'b.txt' => 'b']);
        ShareFile::factory()->create(['share_id' => $share->id, 'original_filename' => 'pending.txt']);

        $entries = $this->unzip($this->get('/s/'.self::TOKEN.'/zip')->streamedContent());

        $this->assertSame(['a.txt', 'b.txt'], array_keys($entries));
    }

    public function test_password_protected_share_redirects_until_unlocked()
    {
        $share = $this->shareWithFiles(['a.txt' => 'a', 'b.txt' => 'b'], ['password' => 'open-sesame']);

        $this->get('/s/'.self::TOKEN.'/zip')->assertRedirect('/s/'.self::TOKEN);
        $this->assertSame(0, $share->fresh()->download_count);
    }

    public function test_ended_share_has_no_zip()
    {
        $this->shareWithFiles(['a.txt' => 'a', 'b.txt' => 'b'], ['status' => Share::STATUS_REVOKED]);

        $this->get('/s/'.self::TOKEN.'/zip')->assertStatus(410);
    }

    public function test_zip_downloads_are_limited_per_ip()
    {
        Event::fake([ShareFirstDownloaded::class]);
        $this->shareWithFiles(['a.txt' => 'a', 'b.txt' => 'b']);

        for ($i = 0; $i < 10; $i++) {
            $this->head('/s/'.self::TOKEN.'/zip')->assertOk();
        }

        $this->head('/s/'.self::TOKEN.'/zip')->assertStatus(429);
    }

    public function test_zip_downloads_are_limited_per_share_across_ips()
    {
        Event::fake([ShareFirstDownloaded::class]);
        $this->shareWithFiles(['a.txt' => 'a', 'b.txt' => 'b']);

        for ($i = 1; $i <= 30; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "198.51.100.{$i}"])
                ->head('/s/'.self::TOKEN.'/zip')->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.31'])
            ->head('/s/'.self::TOKEN.'/zip')->assertStatus(429);
    }

    public function test_share_page_offers_download_all_only_for_more_than_one_file()
    {
        $share = $this->shareWithFiles(['a.txt' => 'a']);

        $this->get('/s/'.self::TOKEN)->assertDontSee('/s/'.self::TOKEN.'/zip');

        ShareFile::factory()->uploaded()->create(['share_id' => $share->id, 'original_filename' => 'b.txt']);

        $this->get('/s/'.self::TOKEN)->assertSee('/s/'.self::TOKEN.'/zip');
    }
}
