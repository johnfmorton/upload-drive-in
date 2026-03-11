<?php

namespace Tests\Unit\Console\Commands;

use Tests\TestCase;
use App\Models\FileUpload;
use App\Services\UploadRecoveryService;
use App\Services\UploadDiagnosticService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Mockery;

class RecoverPendingUploadsCommandTest extends TestCase
{
    use RefreshDatabase;

    private $mockRecoveryService;
    private $mockDiagnosticService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->mockRecoveryService = Mockery::mock(UploadRecoveryService::class);
        $this->mockDiagnosticService = Mockery::mock(UploadDiagnosticService::class);

        $this->app->instance(UploadRecoveryService::class, $this->mockRecoveryService);
        $this->app->instance(UploadDiagnosticService::class, $this->mockDiagnosticService);
    }

    public function test_command_handles_no_uploads_found()
    {
        $this->mockRecoveryService
            ->shouldReceive('detectStuckUploads')
            ->once()
            ->andReturn(collect());

        $result = Artisan::call('uploads:recover-pending', ['--json' => true]);

        $this->assertEquals(0, $result);

        $output = Artisan::output();
        $jsonOutput = json_decode($output, true);

        $this->assertEquals('no_uploads_found', $jsonOutput['status']);
        $this->assertEquals(0, $jsonOutput['total_processed']);
    }

    public function test_command_handles_dry_run_mode()
    {
        // Create a stuck upload
        $upload = FileUpload::factory()->create([
            'provider_file_id' => null,
            'google_drive_file_id' => null,
            'created_at' => Carbon::now()->subHours(2),
            'filename' => 'test-file.pdf'
        ]);

        // Create the file in storage
        Storage::disk('public')->put('uploads/' . $upload->filename, 'test content');

        // Mock detectStuckUploads to return our upload
        $this->mockRecoveryService
            ->shouldReceive('detectStuckUploads')
            ->once()
            ->andReturn(collect([$upload]));

        $result = Artisan::call('uploads:recover-pending', [
            '--dry-run' => true,
            '--json' => true,
            '--force' => true
        ]);

        $this->assertEquals(0, $result);

        $output = Artisan::output();
        $jsonOutput = json_decode($output, true);

        $this->assertTrue($jsonOutput['dry_run']);
        $this->assertEquals(1, $jsonOutput['total_processed']);
    }

    public function test_command_processes_specific_upload_ids()
    {
        $upload1 = FileUpload::factory()->create([
            'provider_file_id' => null,
            'google_drive_file_id' => null,
            'filename' => 'test-file-1.pdf'
        ]);
        $upload2 = FileUpload::factory()->create([
            'provider_file_id' => null,
            'google_drive_file_id' => null,
            'filename' => 'test-file-2.pdf'
        ]);

        // Create the files in storage
        Storage::disk('public')->put('uploads/' . $upload1->filename, 'test content 1');
        Storage::disk('public')->put('uploads/' . $upload2->filename, 'test content 2');

        $result = Artisan::call('uploads:recover-pending', [
            '--ids' => "{$upload1->id},{$upload2->id}",
            '--dry-run' => true,
            '--json' => true,
            '--force' => true
        ]);

        $this->assertEquals(0, $result);

        $output = Artisan::output();
        $jsonOutput = json_decode($output, true);

        $this->assertEquals(2, $jsonOutput['total_processed']);
    }

    public function test_command_handles_invalid_upload_ids()
    {
        $result = Artisan::call('uploads:recover-pending', [
            '--ids' => 'invalid,999999',
            '--json' => true
        ]);

        $this->assertEquals(0, $result);

        $output = Artisan::output();
        $jsonOutput = json_decode($output, true);

        $this->assertEquals('no_uploads_found', $jsonOutput['status']);
    }

    public function test_command_respects_limit_option()
    {
        // Create uploads
        $uploads = FileUpload::factory()->count(15)->create([
            'provider_file_id' => null,
            'google_drive_file_id' => null,
            'created_at' => Carbon::now()->subHours(2),
            'filename' => 'test-file.pdf'
        ]);

        // Create the files in storage
        foreach ($uploads as $upload) {
            Storage::disk('public')->put('uploads/' . $upload->filename, 'test content');
        }

        // Mock detectStuckUploads to return all uploads
        $this->mockRecoveryService
            ->shouldReceive('detectStuckUploads')
            ->once()
            ->andReturn($uploads);

        $result = Artisan::call('uploads:recover-pending', [
            '--limit' => '10',
            '--dry-run' => true,
            '--json' => true,
            '--force' => true
        ]);

        $this->assertEquals(0, $result);

        $output = Artisan::output();
        $jsonOutput = json_decode($output, true);

        // Should only process 10 uploads due to limit
        $this->assertEquals(10, $jsonOutput['total_processed']);
    }

    public function test_command_shows_detailed_output_when_requested()
    {
        $upload = FileUpload::factory()->create([
            'provider_file_id' => null,
            'google_drive_file_id' => null,
            'filename' => 'test-file.pdf',
            'created_at' => Carbon::now()->subHours(2)
        ]);

        // Create the file in storage
        Storage::disk('public')->put('uploads/' . $upload->filename, 'test content');

        // Mock detectStuckUploads to return our upload
        $this->mockRecoveryService
            ->shouldReceive('detectStuckUploads')
            ->once()
            ->andReturn(collect([$upload]));

        $result = Artisan::call('uploads:recover-pending', [
            '--detailed' => true,
            '--dry-run' => true,
            '--force' => true
        ]);

        $this->assertEquals(0, $result);

        $output = Artisan::output();

        // Should contain detailed information
        $this->assertStringContainsString('Upload Details', $output);
        $this->assertStringContainsString('test-file.pdf', $output);
        $this->assertStringContainsString('DRY RUN', $output);
    }

    public function test_command_handles_missing_files()
    {
        $upload = FileUpload::factory()->create([
            'provider_file_id' => null,
            'google_drive_file_id' => null,
            'filename' => 'missing-file.pdf',
            'created_at' => Carbon::now()->subHours(2)
        ]);

        // Don't create the file in storage - it should be missing

        $result = Artisan::call('uploads:recover-pending', [
            '--ids' => (string) $upload->id,
            '--dry-run' => true,
            '--json' => true,
            '--force' => true
        ]);

        $this->assertEquals(0, $result);

        $output = Artisan::output();
        $jsonOutput = json_decode($output, true);

        $this->assertEquals(1, $jsonOutput['total_processed']);
        $this->assertEquals(1, $jsonOutput['file_missing']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
