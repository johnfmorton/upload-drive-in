<?php

namespace App\Console\Commands;

use App\Models\FileUpload;
use App\Jobs\UploadToGoogleDrive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FixPendingUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:fix-pending 
                            {--dry-run : Show what would be done without making changes}
                            {--retry-failed : Retry failed uploads}
                            {--cleanup-missing : Clean up records for missing local files}
                            {--force : Force retry even for recently failed uploads}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix pending uploads that failed due to memory issues or other problems';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Analyzing pending uploads...');
        $this->newLine();

        $isDryRun = $this->option('dry-run');
        $retryFailed = $this->option('retry-failed');
        $cleanupMissing = $this->option('cleanup-missing');
        $force = $this->option('force');

        // Find pending uploads (files that haven't been successfully uploaded to cloud storage)
        $pendingUploads = FileUpload::where(function($query) {
                $query->whereNull('google_drive_file_id')
                      ->whereNull('provider_file_id');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        if ($pendingUploads->isEmpty()) {
            $this->info('✅ No pending uploads found.');
            return;
        }

        $this->info("Found {$pendingUploads->count()} pending uploads:");
        $this->newLine();

        $stats = [
            'total' => $pendingUploads->count(),
            'with_local_files' => 0,
            'missing_local_files' => 0,
            'large_files' => 0,
            'retried' => 0,
            'cleaned_up' => 0,
        ];

        foreach ($pendingUploads as $upload) {
            $localPath = "uploads/{$upload->filename}";
            $hasLocalFile = Storage::disk('public')->exists($localPath);
            $fileSize = $hasLocalFile ? Storage::disk('public')->size($localPath) : 0;
            $isLargeFile = $fileSize > 50 * 1024 * 1024; // 50MB

            if ($hasLocalFile) {
                $stats['with_local_files']++;
                if ($isLargeFile) {
                    $stats['large_files']++;
                }
            } else {
                $stats['missing_local_files']++;
            }

            $this->displayUploadInfo($upload, $hasLocalFile, $fileSize, $isLargeFile);

            // Handle the upload based on options
            if (!$hasLocalFile && $cleanupMissing) {
                $this->handleMissingFile($upload, $isDryRun);
                $stats['cleaned_up']++;
            } elseif ($hasLocalFile && ($retryFailed || $force)) {
                $this->handleRetryUpload($upload, $isDryRun, $force);
                $stats['retried']++;
            }
        }

        $this->newLine();
        $this->displaySummary($stats, $isDryRun);
        
        if ($isDryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Use --retry-failed and/or --cleanup-missing to make actual changes.');
        }
    }

    /**
     * Display information about an upload
     */
    private function displayUploadInfo(FileUpload $upload, bool $hasLocalFile, int $fileSize, bool $isLargeFile): void
    {
        $status = $hasLocalFile ? '📁' : '❌';
        $sizeInfo = $hasLocalFile ? $this->formatBytes($fileSize) : 'N/A';
        $largeFileIndicator = $isLargeFile ? ' 🔥' : '';
        
        $this->line("{$status} ID: {$upload->id} | {$upload->original_filename} | {$sizeInfo}{$largeFileIndicator}");
        $this->line("   Email: {$upload->email} | Created: {$upload->created_at->format('Y-m-d H:i:s')}");
        
        if (!$hasLocalFile) {
            $this->line("   ⚠️  Local file missing: uploads/{$upload->filename}");
        } elseif ($isLargeFile) {
            $this->line("   💡 Large file - will use chunked upload");
        }
        
        $this->newLine();
    }

    /**
     * Handle missing local file
     */
    private function handleMissingFile(FileUpload $upload, bool $isDryRun): void
    {
        if ($isDryRun) {
            $this->warn("   [DRY RUN] Would delete database record for missing file: {$upload->original_filename}");
            return;
        }

        Log::info('Cleaning up database record for missing local file', [
            'upload_id' => $upload->id,
            'filename' => $upload->original_filename,
            'email' => $upload->email
        ]);

        $upload->delete();
        $this->info("   ✅ Cleaned up database record for missing file: {$upload->original_filename}");
    }

    /**
     * Handle retry upload
     */
    private function handleRetryUpload(FileUpload $upload, bool $isDryRun, bool $force): void
    {
        // Check if upload was recently attempted (within last hour) unless forced
        if (!$force && $upload->updated_at->gt(now()->subHour())) {
            $this->warn("   ⏰ Upload was recently attempted, skipping. Use --force to retry anyway.");
            return;
        }

        if ($isDryRun) {
            $this->warn("   [DRY RUN] Would retry upload: {$upload->original_filename}");
            return;
        }

        Log::info('Retrying failed upload with chunked upload support', [
            'upload_id' => $upload->id,
            'filename' => $upload->original_filename,
            'email' => $upload->email,
            'file_size' => Storage::disk('public')->size("uploads/{$upload->filename}")
        ]);

        // Reset upload status and dispatch job
        $upload->update([
            'upload_status' => 'pending',
            'error_message' => null,
        ]);

        // Dispatch the upload job
        UploadToGoogleDrive::dispatch($upload->id)
            ->onQueue('default')
            ->delay(now()->addSeconds(5)); // Small delay to avoid overwhelming the system

        $this->info("   🔄 Queued retry for: {$upload->original_filename}");
    }

    /**
     * Display summary statistics
     */
    private function displaySummary(array $stats, bool $isDryRun): void
    {
        $this->info('📊 Summary:');
        $this->info("   Total pending uploads: {$stats['total']}");
        $this->info("   With local files: {$stats['with_local_files']}");
        $this->info("   Missing local files: {$stats['missing_local_files']}");
        $this->info("   Large files (>50MB): {$stats['large_files']}");
        
        if (!$isDryRun) {
            $this->info("   Retried: {$stats['retried']}");
            $this->info("   Cleaned up: {$stats['cleaned_up']}");
        }

        if ($stats['large_files'] > 0) {
            $this->newLine();
            $this->info('💡 Large files will now use chunked upload to avoid memory issues.');
        }

        if ($stats['missing_local_files'] > 0) {
            $this->newLine();
            $this->warn("⚠️  {$stats['missing_local_files']} uploads have missing local files.");
            $this->warn('   Use --cleanup-missing to remove these database records.');
        }

        if ($stats['with_local_files'] > 0) {
            $this->newLine();
            $this->info("🔄 {$stats['with_local_files']} uploads can be retried.");
            $this->info('   Use --retry-failed to queue them for retry.');
        }
    }

    /**
     * Format bytes into human readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}