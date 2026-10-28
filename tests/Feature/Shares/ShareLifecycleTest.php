<?php

namespace Tests\Feature\Shares;

use App\Events\ShareFirstDownloaded;
use App\Events\ShareReady;
use App\Jobs\PurgeExpiredShares;
use App\Jobs\PurgeShareFiles;
use App\Jobs\UploadShareFileToCloud;
use App\Models\Share;
use App\Models\ShareFile;
use App\Models\User;
use App\Services\ShareService;
use App\Services\ShareStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ShareLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $sender;

    private $storage;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->sender = User::factory()->create();

        $this->storage = Mockery::mock(ShareStorageService::class);
        $this->storage->shouldReceive('stagingDirectory')
            ->andReturnUsing(fn (Share $share) => 'shares/'.$share->ulid);
        $this->app->instance(ShareStorageService::class, $this->storage);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function service(): ShareService
    {
        return $this->app->make(ShareService::class);
    }

    public function test_create_stores_only_a_hash_of_the_token()
    {
        $this->storage->shouldReceive('resolveStorageFor')
            ->once()
            ->andReturn(['provider' => 'amazon-s3', 'cloud_user_id' => $this->sender->id]);

        [$share, $token] = $this->service()->create($this->sender, [
            'title' => 'Project photos',
            'password' => 'secret-pass',
            'expiry_days' => 7,
        ]);

        $this->assertSame(43, strlen($token));
        $this->assertNotSame($token, $share->token_hash);
        $this->assertSame(Share::hashToken($token), $share->token_hash);
        $this->assertTrue($share->is($this->service()->findByToken($token)));
        $this->assertNull($this->service()->findByToken('wrong-token'));
        $this->assertTrue(Hash::check('secret-pass', $share->password));
        $this->assertSame(Share::STATUS_DRAFT, $share->status);
        $this->assertTrue($share->expires_at->between(now()->addDays(7)->subMinute(), now()->addDays(7)->addMinute()));
    }

    public function test_upload_error_enforces_limits()
    {
        config([
            'shares.max_file_size' => 1000,
            'shares.max_share_size' => 1500,
            'shares.min_free_disk' => 0,
        ]);

        $share = Share::factory()->create(['user_id' => $this->sender->id]);
        ShareFile::factory()->create(['share_id' => $share->id, 'file_size' => 800]);

        $service = $this->service();

        $this->assertNull($service->uploadError($share, 600));
        $this->assertNotNull($service->uploadError($share, 1001), 'file over the per-file cap');
        $this->assertNotNull($service->uploadError($share, 701), 'share total over the cap');

        $share->update(['status' => Share::STATUS_UPLOADING]);
        $this->assertNotNull($service->uploadError($share, 10), 'share no longer accepting files');
    }

    public function test_upload_error_keeps_minimum_free_disk()
    {
        config(['shares.min_free_disk' => 5000]);

        $service = Mockery::mock(ShareService::class, [$this->storage])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('freeDiskSpace')->andReturn(6000);

        $share = Share::factory()->create(['user_id' => $this->sender->id]);

        $this->assertNull($service->uploadError($share, 1000));
        $this->assertNotNull($service->uploadError($share, 1001));
    }

    public function test_add_file_stages_it_and_queues_the_cloud_upload()
    {
        Bus::fake();

        $share = Share::factory()->create(['user_id' => $this->sender->id]);

        $file = $this->service()->addFile($share, UploadedFile::fake()->create('report.pdf', 10), 'report.pdf');

        $this->assertSame('report.pdf', $file->original_filename);
        $this->assertSame(10 * 1024, $file->file_size);
        Storage::disk('local')->assertExists($file->local_path);
        Bus::assertDispatched(UploadShareFileToCloud::class, fn ($job) => $job->shareFile->is($file));
    }

    public function test_finalize_marks_ready_only_when_every_file_is_uploaded()
    {
        Event::fake([ShareReady::class]);

        $share = Share::factory()->create(['user_id' => $this->sender->id]);
        ShareFile::factory()->uploaded()->create(['share_id' => $share->id]);
        $pending = ShareFile::factory()->create(['share_id' => $share->id]);

        $this->service()->finalize($share);
        $this->assertSame(Share::STATUS_UPLOADING, $share->fresh()->status);
        Event::assertNotDispatched(ShareReady::class);

        $pending->update(['status' => ShareFile::STATUS_UPLOADED]);
        $this->assertTrue($this->service()->markReadyIfComplete($share->fresh()));
        $this->assertFalse($this->service()->markReadyIfComplete($share->fresh()), 'second call must not win');

        $this->assertSame(Share::STATUS_READY, $share->fresh()->status);
        Event::assertDispatchedTimes(ShareReady::class, 1);
    }

    public function test_finalize_with_no_files_does_not_become_ready()
    {
        $share = Share::factory()->create(['user_id' => $this->sender->id]);

        $this->service()->finalize($share);

        $this->assertSame(Share::STATUS_UPLOADING, $share->fresh()->status);
    }

    public function test_upload_job_moves_file_to_cloud_and_marks_share_ready()
    {
        Event::fake([ShareReady::class]);

        $share = Share::factory()->uploading()->create(['user_id' => $this->sender->id]);
        Storage::disk('local')->put('shares/x/abc', 'data');
        $file = ShareFile::factory()->create(['share_id' => $share->id, 'local_path' => 'shares/x/abc']);

        $this->storage->shouldReceive('upload')->once()->andReturn('cloud-id-1');

        $this->app->call([new UploadShareFileToCloud($file), 'handle']);

        $file->refresh();
        $this->assertSame(ShareFile::STATUS_UPLOADED, $file->status);
        $this->assertSame('cloud-id-1', $file->provider_file_id);
        $this->assertNull($file->local_path);
        Storage::disk('local')->assertMissing('shares/x/abc');
        $this->assertSame(Share::STATUS_READY, $share->fresh()->status);
        Event::assertDispatched(ShareReady::class);
    }

    public function test_upload_job_skips_files_of_ended_shares()
    {
        $share = Share::factory()->create(['user_id' => $this->sender->id, 'status' => Share::STATUS_REVOKED]);
        Storage::disk('local')->put('shares/x/abc', 'data');
        $file = ShareFile::factory()->create(['share_id' => $share->id, 'local_path' => 'shares/x/abc']);

        $this->storage->shouldNotReceive('upload');

        $this->app->call([new UploadShareFileToCloud($file), 'handle']);

        Storage::disk('local')->assertMissing('shares/x/abc');
        $this->assertSame(ShareFile::STATUS_PENDING, $file->fresh()->status);
    }

    public function test_upload_job_purges_again_if_share_was_revoked_mid_upload()
    {
        Bus::fake([PurgeShareFiles::class]);

        $share = Share::factory()->uploading()->create(['user_id' => $this->sender->id]);
        $file = ShareFile::factory()->create(['share_id' => $share->id]);

        $this->storage->shouldReceive('upload')->once()->andReturnUsing(function () use ($share) {
            $share->update(['status' => Share::STATUS_REVOKED]);

            return 'cloud-id-1';
        });

        $this->app->call([new UploadShareFileToCloud($file), 'handle']);

        Bus::assertDispatched(PurgeShareFiles::class);
    }

    public function test_failed_upload_marks_file_failed_unless_it_already_succeeded()
    {
        $file = ShareFile::factory()->create();
        (new UploadShareFileToCloud($file))->failed(new \Exception('Drive quota exceeded'));

        $this->assertSame(ShareFile::STATUS_FAILED, $file->fresh()->status);
        $this->assertSame('Drive quota exceeded', $file->fresh()->last_error);

        $uploaded = ShareFile::factory()->uploaded()->create();
        (new UploadShareFileToCloud($uploaded))->failed(new \Exception('duplicate run'));

        $this->assertSame(ShareFile::STATUS_UPLOADED, $uploaded->fresh()->status);
    }

    public function test_revoke_ends_the_share_and_queues_the_purge()
    {
        Bus::fake([PurgeShareFiles::class]);

        $share = Share::factory()->ready()->create(['user_id' => $this->sender->id]);

        $this->service()->revoke($share);

        $this->assertSame(Share::STATUS_REVOKED, $share->fresh()->status);
        $this->assertNotNull($share->fresh()->revoked_at);
        $this->assertFalse($share->fresh()->isAvailable());
        Bus::assertDispatched(PurgeShareFiles::class);
    }

    public function test_purge_job_deletes_files_and_records_it()
    {
        $share = Share::factory()->create(['user_id' => $this->sender->id, 'status' => Share::STATUS_EXPIRED]);

        $this->storage->shouldReceive('deleteShareFiles')->once()->with(Mockery::on(fn ($s) => $s->is($share)));

        $this->app->call([new PurgeShareFiles($share), 'handle']);

        $this->assertNotNull($share->fresh()->purged_at);
    }

    public function test_purge_job_leaves_live_shares_alone()
    {
        $share = Share::factory()->ready()->create(['user_id' => $this->sender->id]);

        $this->storage->shouldNotReceive('deleteShareFiles');

        $this->app->call([new PurgeShareFiles($share), 'handle']);

        $this->assertNull($share->fresh()->purged_at);
    }

    public function test_hourly_cleanup_expires_abandons_and_purges()
    {
        Bus::fake([PurgeShareFiles::class]);
        config(['shares.stale_upload_hours' => 24]);

        $overdue = Share::factory()->ready('a')->create(['expires_at' => now()->subMinute()]);
        $live = Share::factory()->ready('b')->create();
        $staleDraft = Share::factory()->create(['created_at' => now()->subHours(25)]);
        $freshDraft = Share::factory()->create();
        $alreadyPurged = Share::factory()->create(['status' => Share::STATUS_REVOKED, 'purged_at' => now()]);

        (new PurgeExpiredShares)->handle();

        $this->assertSame(Share::STATUS_EXPIRED, $overdue->fresh()->status);
        $this->assertSame(Share::STATUS_READY, $live->fresh()->status);
        $this->assertSame(Share::STATUS_FAILED, $staleDraft->fresh()->status);
        $this->assertSame(Share::STATUS_DRAFT, $freshDraft->fresh()->status);

        Bus::assertDispatchedTimes(PurgeShareFiles::class, 2);
        Bus::assertDispatched(PurgeShareFiles::class, fn ($job) => $job->share->is($overdue));
        Bus::assertDispatched(PurgeShareFiles::class, fn ($job) => $job->share->is($staleDraft));
        Bus::assertNotDispatched(PurgeShareFiles::class, fn ($job) => $job->share->is($alreadyPurged));
    }

    public function test_first_download_event_fires_once()
    {
        Event::fake([ShareFirstDownloaded::class]);

        $share = Share::factory()->ready()->create(['user_id' => $this->sender->id]);
        $file = ShareFile::factory()->uploaded()->create(['share_id' => $share->id]);
        $request = Request::create('/s/test-token', 'GET', server: ['REMOTE_ADDR' => '203.0.113.5']);

        $this->service()->recordDownload($share, $file, $request);
        $this->service()->recordDownload($share, $file, $request);

        Event::assertDispatchedTimes(ShareFirstDownloaded::class, 1);
        $this->assertSame(2, $share->fresh()->download_count);
        $this->assertSame(2, $file->fresh()->download_count);
        $this->assertNotNull($share->fresh()->first_downloaded_at);
        $this->assertSame(2, $share->accessLogs()->where('event', 'download')->count());
    }
}
