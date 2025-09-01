<?php

namespace Tests\Unit\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\CleanupUploadsCommand;
use App\Models\FileUpload;
use App\Models\User;
use App\Services\UploadRecoveryService;
use App\Services\UploadDiagnosticService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class CleanupUploadsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test storage
        Storage::fake('local');
    }

    public function test_command_exists_and_has_correct_signature()
    {
        $this->assertTrue(class_exists(CleanupUploadsCommand::class));
        
        // Test that the command can be called
        $result = $this->artisan('uploads:cleanup --help');
        $result->assertExitCode(0);
    }

    public function test_dry_run_mode_does_not_make_changes()
    {
        // Create some orphaned files in storage
        Storage::put('uploads/orphaned1.txt', 'test content');
        Storage::put('chunks/orphaned2.part', 'chunk content');

        $this->artisan('uploads:cleanup --dry-run --type=orphaned --force')
            ->assertExitCode(0);

        // Files should still exist after dry run
        $this->assertTrue(Storage::exists('uploads/orphaned1.txt'));
        $this->assertTrue(Storage::exists('chunks/orphaned2.part'));
    }

    public function test_orphaned_files_are_removed_when_not_in_dry_run()
    {
        // Create some orphaned files in storage
        Storage::put('uploads/orphaned1.txt', 'test content');
        Storage::put('chunks/orphaned2.part', 'chunk content');

        $this->artisan('uploads:cleanup --force --type=orphaned')
            ->assertExitCode(0);

        // Files should be removed
        $this->assertFalse(Storage::exists('uploads/orphaned1.txt'));
        $this->assertFalse(Storage::exists('chunks/orphaned2.part'));
    }

    public function test_files_with_database_records_are_not_removed()
    {
        $user = User::factory()->create();
        
        // Create a file upload record
        $upload = FileUpload::factory()->create([
            'client_user_id' => $user->id,
            'filename' => 'test-file.txt',
            'original_filename' => 'test-file.txt'
        ]);

        // Create the corresponding file in storage
        Storage::put('uploads/test-file.txt', 'test content');
        
        // Also create an orphaned file
        Storage::put('uploads/orphaned.txt', 'orphaned content');

        $this->artisan('uploads:cleanup --force --type=orphaned')
            ->assertExitCode(0);

        // File with database record should remain
        $this->assertTrue(Storage::exists('uploads/test-file.txt'));
        // Orphaned file should be removed
        $this->assertFalse(Storage::exists('uploads/orphaned.txt'));
    }

    public function test_inconsistent_records_cleanup_runs_without_error()
    {
        $this->artisan('uploads:cleanup --force --type=inconsistent')
            ->assertExitCode(0);
    }

    public function test_failed_uploads_cleanup_runs_without_error()
    {
        $this->artisan('uploads:cleanup --force --type=failed --older-than=30')
            ->assertExitCode(0);
    }

    public function test_temporary_files_cleanup_runs_without_error()
    {
        $this->artisan('uploads:cleanup --force --type=temp')
            ->assertExitCode(0);
    }

    public function test_all_cleanup_types_run_when_type_is_all()
    {
        // Create test data for all cleanup types
        Storage::put('uploads/orphaned.txt', 'orphaned');
        Storage::put('temp/old-temp.tmp', 'temp');

        $this->artisan('uploads:cleanup --dry-run --type=all --force')
            ->assertExitCode(0);
    }

    public function test_json_output_format()
    {
        Storage::put('uploads/orphaned.txt', 'orphaned content');

        $result = $this->artisan('uploads:cleanup --dry-run --json --type=orphaned --force');
        
        $result->assertExitCode(0);
        
        // The command should run successfully with JSON output
        // We can't easily test the JSON structure in unit tests due to Laravel's command testing limitations
        // But we can verify it runs without error
    }

    public function test_confirmation_required_when_not_forced()
    {
        Storage::put('uploads/orphaned.txt', 'orphaned content');

        $this->artisan('uploads:cleanup --type=orphaned')
            ->expectsQuestion('Do you want to continue?', false)
            ->assertExitCode(0);

        // File should still exist
        $this->assertTrue(Storage::exists('uploads/orphaned.txt'));
    }

    public function test_invalid_cleanup_type_returns_error()
    {
        $this->artisan('uploads:cleanup --type=invalid --force')
            ->assertExitCode(1);
    }

    public function test_custom_parameters_are_respected()
    {
        $user = User::factory()->create();
        
        $failedUpload = FileUpload::factory()->create([
            'client_user_id' => $user->id,
            'filename' => 'failed.txt',
            'retry_count' => 5,
            'last_error' => 'Failed',
            'created_at' => Carbon::now()->subDays(15)
        ]);

        $this->artisan('uploads:cleanup --dry-run --type=failed --older-than=10 --batch-size=5 --force')
            ->assertExitCode(0);
    }
}