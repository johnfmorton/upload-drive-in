<?php

namespace Tests\Feature\Shares;

use App\Jobs\PurgeShareFiles;
use App\Jobs\UploadShareFileToCloud;
use App\Models\Share;
use App\Models\ShareFile;
use App\Models\User;
use App\Services\ShareStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ShareControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Bus::fake([UploadShareFileToCloud::class, PurgeShareFiles::class]);

        $this->employee = User::factory()->employee()->create();
        $this->admin = User::factory()->admin()->create();
        // Admin routes require 2FA; the test session marks it as verified
        $this->admin->forceFill(['two_factor_enabled' => true])->save();

        $storage = Mockery::mock(ShareStorageService::class)->makePartial();
        $storage->shouldReceive('resolveStorageFor')
            ->andReturnUsing(fn (User $sender) => ['provider' => 'amazon-s3', 'cloud_user_id' => $sender->id]);
        $this->app->instance(ShareStorageService::class, $storage);
    }

    private function employeeRoute(string $name, array $params = []): string
    {
        return route("employee.shares.{$name}", ['username' => $this->employee->username] + $params);
    }

    public function test_employee_sees_only_their_own_shares()
    {
        Share::factory()->create(['user_id' => $this->employee->id, 'title' => 'Mine']);
        Share::factory()->create(['title' => 'Someone else']);

        $this->actingAs($this->employee)
            ->get($this->employeeRoute('index'))
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('Someone else');
    }

    public function test_admin_sees_every_share()
    {
        Share::factory()->create(['user_id' => $this->employee->id, 'title' => 'Employee share']);

        $this->actingAs($this->admin)
            ->withSession(['two_factor_verified' => now()->timestamp])
            ->get(route('admin.shares.index'))
            ->assertOk()
            ->assertSee('Employee share')
            ->assertSee($this->employee->name);
    }

    public function test_create_page_loads_the_upload_script()
    {
        $this->actingAs($this->employee)
            ->get($this->employeeRoute('create'))
            ->assertOk()
            ->assertSee('share-create', false);
    }

    public function test_store_creates_a_draft_and_returns_upload_urls()
    {
        $response = $this->actingAs($this->employee)
            ->postJson($this->employeeRoute('store'), [
                'title' => 'Plans',
                'expiry_days' => 7,
                'recipient_emails' => ['client@example.com'],
            ])
            ->assertCreated()
            ->assertJsonStructure(['upload_url', 'finalize_url', 'show_url']);

        $share = Share::sole();
        $this->assertSame(Share::STATUS_DRAFT, $share->status);
        $this->assertSame(['client@example.com'], $share->recipient_emails);
        $this->assertStringContainsString("/employee/{$this->employee->username}/shares/{$share->ulid}/files", $response->json('upload_url'));
    }

    public function test_store_validates_expiry_and_emails()
    {
        $this->actingAs($this->employee)
            ->postJson($this->employeeRoute('store'), [
                'title' => 'Plans',
                'expiry_days' => 365,
                'recipient_emails' => ['not-an-email'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['expiry_days', 'recipient_emails.0']);
    }

    public function test_employee_cannot_open_another_users_share()
    {
        $share = Share::factory()->create(['user_id' => $this->admin->id]);

        $this->actingAs($this->employee)
            ->get($this->employeeRoute('show', ['share' => $share]))
            ->assertForbidden();

        $this->actingAs($this->employee)
            ->post($this->employeeRoute('revoke', ['share' => $share]))
            ->assertForbidden();
    }

    public function test_single_chunk_upload_adds_the_file_and_queues_it()
    {
        $share = Share::factory()->create(['user_id' => $this->employee->id]);

        $this->actingAs($this->employee)
            ->post($this->employeeRoute('files.store', ['share' => $share]), [
                'file' => UploadedFile::fake()->createWithContent('photo.jpg', str_repeat('x', 20 * 1024)),
                'dzuuid' => 'abc-123',
                'dzchunkindex' => 0,
                'dztotalfilesize' => 20 * 1024,
                'dzchunksize' => 5 * 1024 * 1024,
                'dztotalchunkcount' => 1,
                'dzchunkbyteoffset' => 0,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonStructure(['id', 'delete_url']);

        $file = ShareFile::sole();
        $this->assertSame('photo.jpg', $file->original_filename);
        Storage::disk('local')->assertExists($file->local_path);
        Bus::assertDispatched(UploadShareFileToCloud::class);
    }

    public function test_oversized_file_is_refused_on_the_first_chunk()
    {
        config(['shares.max_file_size' => 1000]);
        $share = Share::factory()->create(['user_id' => $this->employee->id]);

        $this->actingAs($this->employee)
            ->post($this->employeeRoute('files.store', ['share' => $share]), [
                'file' => UploadedFile::fake()->create('big.bin', 1),
                'dzuuid' => 'abc-123',
                'dzchunkindex' => 0,
                'dztotalfilesize' => 5000,
                'dztotalchunkcount' => 1,
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonStructure(['error']);

        $this->assertSame(0, ShareFile::count());
    }

    public function test_limits_are_checked_on_later_chunks_too()
    {
        config(['shares.max_file_size' => 8 * 1024 * 1024]);
        $share = Share::factory()->create(['user_id' => $this->employee->id]);

        // Declares a small file but sends a chunk that runs past it
        $this->actingAs($this->employee)
            ->post($this->employeeRoute('files.store', ['share' => $share]), [
                'file' => UploadedFile::fake()->create('lie.bin', 5 * 1024),
                'dzuuid' => 'abc-123',
                'dzchunkindex' => 1,
                'dztotalfilesize' => 1024,
                'dzchunksize' => 5 * 1024 * 1024,
                'dztotalchunkcount' => 2,
                'dzchunkbyteoffset' => 5 * 1024 * 1024,
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable();

        // Declares a size over the cap on a later chunk
        $this->actingAs($this->employee)
            ->post($this->employeeRoute('files.store', ['share' => $share]), [
                'file' => UploadedFile::fake()->create('big.bin', 5 * 1024),
                'dzuuid' => 'abc-456',
                'dzchunkindex' => 3,
                'dztotalfilesize' => 20 * 1024 * 1024,
                'dzchunksize' => 5 * 1024 * 1024,
                'dztotalchunkcount' => 4,
                'dzchunkbyteoffset' => 15 * 1024 * 1024,
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable();
    }

    public function test_uploads_without_chunk_parameters_are_refused()
    {
        $share = Share::factory()->create(['user_id' => $this->employee->id]);

        $this->actingAs($this->employee)
            ->post($this->employeeRoute('files.store', ['share' => $share]), [
                'file' => UploadedFile::fake()->create('plain.pdf', 10),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable();

        $this->assertSame(0, ShareFile::count());
    }

    public function test_finalize_requires_files()
    {
        $share = Share::factory()->create(['user_id' => $this->employee->id]);

        $this->actingAs($this->employee)
            ->postJson($this->employeeRoute('finalize', ['share' => $share]))
            ->assertUnprocessable();

        ShareFile::factory()->create(['share_id' => $share->id]);

        $this->actingAs($this->employee)
            ->postJson($this->employeeRoute('finalize', ['share' => $share]))
            ->assertOk()
            ->assertJsonStructure(['show_url']);

        $this->assertSame(Share::STATUS_UPLOADING, $share->fresh()->status);
    }

    public function test_show_page_shows_the_link_once_ready()
    {
        $share = Share::factory()->ready('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQ')->create(['user_id' => $this->employee->id]);

        $this->actingAs($this->employee)
            ->get($this->employeeRoute('show', ['share' => $share]))
            ->assertOk()
            ->assertSee(url('/s/abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQ'));
    }

    public function test_revoke_ends_the_share_and_queues_deletion()
    {
        $share = Share::factory()->ready()->create(['user_id' => $this->employee->id]);

        $this->actingAs($this->employee)
            ->post($this->employeeRoute('revoke', ['share' => $share]))
            ->assertRedirect($this->employeeRoute('show', ['share' => $share]));

        $this->assertSame(Share::STATUS_REVOKED, $share->fresh()->status);
        Bus::assertDispatched(PurgeShareFiles::class);
    }

    public function test_retry_requeues_a_failed_file()
    {
        $share = Share::factory()->uploading()->create(['user_id' => $this->employee->id]);
        $file = ShareFile::factory()->create(['share_id' => $share->id, 'status' => ShareFile::STATUS_FAILED, 'last_error' => 'boom']);

        $this->actingAs($this->employee)
            ->post($this->employeeRoute('files.retry', ['share' => $share, 'shareFile' => $file]))
            ->assertRedirect();

        $this->assertSame(ShareFile::STATUS_PENDING, $file->fresh()->status);
        Bus::assertDispatched(UploadShareFileToCloud::class);
    }

    public function test_clients_cannot_reach_share_pages()
    {
        $client = User::factory()->create();

        $this->actingAs($client)->get(route('admin.shares.index'))->assertForbidden();
    }
}
