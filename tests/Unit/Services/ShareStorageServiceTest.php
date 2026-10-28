<?php

namespace Tests\Unit\Services;

use App\Contracts\CloudStorageProviderInterface;
use App\Enums\UserRole;
use App\Models\GoogleDriveToken;
use App\Models\Share;
use App\Models\ShareFile;
use App\Models\User;
use App\Services\CloudStorageManager;
use App\Services\GoogleDriveChunkedUploadService;
use App\Services\GoogleDriveService;
use App\Services\S3Provider;
use App\Services\ShareStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ShareStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    private $manager;

    private $s3;

    private ShareStorageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->manager = Mockery::mock(CloudStorageManager::class);
        $this->s3 = Mockery::mock(S3Provider::class);

        $this->service = new ShareStorageService(
            $this->manager,
            Mockery::mock(GoogleDriveService::class),
            Mockery::mock(GoogleDriveChunkedUploadService::class)
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function providerNamed(string $name): CloudStorageProviderInterface
    {
        $provider = Mockery::mock(CloudStorageProviderInterface::class);
        $provider->shouldReceive('getProviderName')->andReturn($name);

        return $provider;
    }

    public function test_s3_shares_are_stored_under_the_sender()
    {
        $sender = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->manager->shouldReceive('getUserProvider')->andReturn($this->providerNamed('amazon-s3'));

        $this->assertSame(
            ['provider' => 'amazon-s3', 'cloud_user_id' => $sender->id],
            $this->service->resolveStorageFor($sender)
        );
    }

    public function test_drive_shares_fall_back_to_an_admin_drive_account()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        GoogleDriveToken::factory()->create(['user_id' => $admin->id]);
        $sender = User::factory()->create(['role' => UserRole::EMPLOYEE]);

        $this->manager->shouldReceive('getUserProvider')->andReturn($this->providerNamed('google-drive'));

        $this->assertSame($admin->id, $this->service->resolveStorageFor($sender)['cloud_user_id']);
    }

    public function test_drive_shares_use_the_senders_own_drive_when_connected()
    {
        User::factory()->create(['role' => UserRole::ADMIN]);
        $sender = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        GoogleDriveToken::factory()->create(['user_id' => $sender->id]);

        $this->manager->shouldReceive('getUserProvider')->andReturn($this->providerNamed('google-drive'));

        $this->assertSame($sender->id, $this->service->resolveStorageFor($sender)['cloud_user_id']);
    }

    public function test_drive_share_without_any_drive_account_is_refused()
    {
        $sender = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->manager->shouldReceive('getUserProvider')->andReturn($this->providerNamed('google-drive'));

        $this->expectException(\Exception::class);

        $this->service->resolveStorageFor($sender);
    }

    public function test_s3_upload_sends_the_staged_file_under_the_share_prefix()
    {
        $share = Share::factory()->create();
        Storage::disk('local')->put('shares/'.$share->ulid.'/abc', 'data');
        $file = ShareFile::factory()->create([
            'share_id' => $share->id,
            'original_filename' => 'plans.pdf',
            'local_path' => 'shares/'.$share->ulid.'/abc',
        ]);

        $this->manager->shouldReceive('getProvider')->with('amazon-s3', Mockery::any())->andReturn($this->s3);
        $this->s3->shouldReceive('uploadFile')
            ->once()
            ->with(
                Mockery::on(fn ($u) => $u->id === $share->cloud_user_id),
                Storage::disk('local')->path('shares/'.$share->ulid.'/abc'),
                'outgoing-share-'.$share->ulid,
                ['original_filename' => 'plans.pdf', 'mime_type' => 'application/pdf']
            )
            ->andReturn('outgoing-share-x/plans_123.pdf');

        $this->assertSame('outgoing-share-x/plans_123.pdf', $this->service->upload($file));
    }

    public function test_s3_purge_deletes_each_uploaded_file_and_the_staging_directory()
    {
        $share = Share::factory()->create(['status' => Share::STATUS_REVOKED]);
        $uploaded = ShareFile::factory()->uploaded()->count(2)->create(['share_id' => $share->id]);
        ShareFile::factory()->create(['share_id' => $share->id]); // never reached the cloud
        Storage::disk('local')->put('shares/'.$share->ulid.'/leftover', 'data');

        $this->manager->shouldReceive('getProvider')->andReturn($this->s3);
        foreach ($uploaded as $file) {
            $this->s3->shouldReceive('deleteFile')->once()->with(Mockery::any(), $file->provider_file_id)->andReturn(true);
        }

        $this->service->deleteShareFiles($share);

        Storage::disk('local')->assertMissing('shares/'.$share->ulid);
    }

    public function test_falls_back_when_provider_manager_returns_a_non_s3_provider()
    {
        $share = Share::factory()->create(['status' => Share::STATUS_REVOKED]);
        ShareFile::factory()->uploaded()->create(['share_id' => $share->id]);

        $this->manager->shouldReceive('getProvider')->andReturn($this->providerNamed('google-drive'));

        $this->expectExceptionMessage('Amazon S3 provider is not available');

        $this->service->deleteShareFiles($share);
    }
}
