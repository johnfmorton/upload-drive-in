<?php

namespace App\Console\Commands;

use App\Models\FileUpload;
use App\Services\UploadRecoveryService;
use App\Services\UploadDiagnosticService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupUploadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:cleanup 
                            {--type= : Type of cleanup (orphaned|inconsistent|failed|temp|all)}
                            {--older-than= : Clean up items older than specified days (default from config)}
                            {--batch-size= : Number of items to process per batch (default from config)}
                            {--dry-run : Show what would be cleaned up without actually cleaning}
                            {--detailed : Show detailed progress and results}
                            {--json : Output results in JSON format}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned files, inconsistent database states, and failed upload artifacts';

    private UploadRecoveryService $recoveryService;
    private UploadDiagnosticService $diagnosticService;
    private bool $dryRun = false;
    private bool $detailed = false;
    private bool $jsonOutput = false;
    private bool $force = false;
    private array $results = [];

    public function __construct(
        UploadRecoveryService $recoveryService,
        UploadDiagnosticService $diagnosticService
    ) {
        parent::__construct();
        $this->recoveryService = $recoveryService;
        $this->diagnosticService = $diagnosticService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->initializeOptions();
        
        if ($this->jsonOutput) {
            $this->results['started_at'] = now()->toISOString();
            $this->results['command'] = 'uploads:cleanup';
            $this->results['options'] = $this->options();
        }

        try {
            $this->displayHeader();
            
            $cleanupType = $this->option('type') ?? 'all';
            $olderThanDays = $this->option('older-than') ?? config('upload-recovery.cleanup_failed_uploads_days', 30);
            $batchSize = $this->option('batch-size') ?? config('upload-recovery.batch_size', 10);

            if (!$this->force && !$this->dryRun && !$this->confirmCleanup($cleanupType, $olderThanDays)) {
                $this->outputResult('Operation cancelled by user', 'info');
                return self::SUCCESS;
            }

            $this->results['cleanup_type'] = $cleanupType;
            $this->results['older_than_days'] = $olderThanDays;
            $this->results['batch_size'] = $batchSize;
            $this->results['dry_run'] = $this->dryRun;

            switch ($cleanupType) {
                case 'orphaned':
                    $this->cleanupOrphanedFiles($batchSize);
                    break;
                case 'inconsistent':
                    $this->cleanupInconsistentRecords($batchSize);
                    break;
                case 'failed':
                    $this->cleanupFailedUploads($olderThanDays, $batchSize);
                    break;
                case 'temp':
                    $this->cleanupTemporaryFiles();
                    break;
                case 'all':
                    $this->cleanupOrphanedFiles($batchSize);
                    $this->cleanupInconsistentRecords($batchSize);
                    $this->cleanupFailedUploads($olderThanDays, $batchSize);
                    $this->cleanupTemporaryFiles();
                    break;
                default:
                    $this->outputResult("Invalid cleanup type: {$cleanupType}", 'error');
                    return self::FAILURE;
            }

            $this->displaySummary();
            
            if ($this->jsonOutput) {
                $this->results['completed_at'] = now()->toISOString();
                $this->line(json_encode($this->results, JSON_PRETTY_PRINT));
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->outputResult("Cleanup failed: {$e->getMessage()}", 'error');
            Log::error('Upload cleanup command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'options' => $this->options()
            ]);
            
            if ($this->jsonOutput) {
                $this->results['error'] = $e->getMessage();
                $this->results['completed_at'] = now()->toISOString();
                $this->line(json_encode($this->results, JSON_PRETTY_PRINT));
            }
            
            return self::FAILURE;
        }
    }

    /**
     * Initialize command options and flags.
     */
    private function initializeOptions(): void
    {
        $this->dryRun = $this->option('dry-run');
        $this->detailed = $this->option('detailed');
        $this->jsonOutput = $this->option('json');
        $this->force = $this->option('force');
        
        $this->results = [
            'orphaned_files' => [],
            'inconsistent_records' => [],
            'failed_uploads' => [],
            'temporary_files' => [],
            'summary' => [
                'orphaned_files_removed' => 0,
                'inconsistent_records_fixed' => 0,
                'failed_uploads_cleaned' => 0,
                'temporary_files_removed' => 0,
                'total_space_freed_mb' => 0,
                'errors' => []
            ]
        ];
    }

    /**
     * Display command header information.
     */
    private function displayHeader(): void
    {
        if ($this->jsonOutput) {
            return;
        }

        $this->info('Upload Cleanup Command');
        $this->info('====================');
        
        if ($this->dryRun) {
            $this->warn('DRY RUN MODE - No actual cleanup will be performed');
        }
        
        $this->newLine();
    }

    /**
     * Confirm cleanup operation with user.
     */
    private function confirmCleanup(string $cleanupType, int $olderThanDays): bool
    {
        $this->warn("This will perform cleanup of type: {$cleanupType}");
        $this->warn("Items older than {$olderThanDays} days will be affected");
        $this->newLine();
        
        return $this->confirm('Do you want to continue?');
    }

    /**
     * Clean up orphaned files (files in storage without database records).
     */
    private function cleanupOrphanedFiles(int $batchSize): void
    {
        $this->outputResult('Scanning for orphaned files...', 'info');
        
        $uploadPaths = [
            'uploads',
            'chunks',
            'temp'
        ];
        
        $orphanedFiles = [];
        $totalSize = 0;
        
        foreach ($uploadPaths as $path) {
            if (!Storage::exists($path)) {
                continue;
            }
            
            $files = Storage::allFiles($path);
            
            foreach ($files as $file) {
                $filename = basename($file);
                
                // Check if file has corresponding database record
                $hasRecord = FileUpload::where('original_filename', $filename)
                    ->orWhere('filename', $filename)
                    ->exists();
                
                if (!$hasRecord) {
                    $fileSize = Storage::size($file);
                    $orphanedFiles[] = [
                        'path' => $file,
                        'size' => $fileSize,
                        'last_modified' => Storage::lastModified($file)
                    ];
                    $totalSize += $fileSize;
                }
            }
        }
        
        $this->outputResult("Found " . count($orphanedFiles) . " orphaned files ({$this->formatBytes($totalSize)})", 'info');
        
        if (empty($orphanedFiles)) {
            return;
        }
        
        $this->results['orphaned_files'] = $orphanedFiles;
        
        if ($this->dryRun) {
            $this->outputResult('DRY RUN: Would remove orphaned files', 'comment');
            if ($this->detailed) {
                foreach ($orphanedFiles as $file) {
                    $this->line("  - {$file['path']} ({$this->formatBytes($file['size'])})");
                }
            }
            return;
        }
        
        $removed = 0;
        $errors = 0;
        
        foreach (array_chunk($orphanedFiles, $batchSize) as $batch) {
            foreach ($batch as $file) {
                try {
                    if (Storage::delete($file['path'])) {
                        $removed++;
                        $this->results['summary']['total_space_freed_mb'] += $file['size'] / 1024 / 1024;
                        
                        if ($this->detailed) {
                            $this->line("  Removed: {$file['path']}");
                        }
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->results['summary']['errors'][] = "Failed to remove {$file['path']}: {$e->getMessage()}";
                    
                    if ($this->detailed) {
                        $this->error("  Failed to remove: {$file['path']} - {$e->getMessage()}");
                    }
                }
            }
        }
        
        $this->results['summary']['orphaned_files_removed'] = $removed;
        $this->outputResult("Removed {$removed} orphaned files" . ($errors > 0 ? " ({$errors} errors)" : ''), 'info');
    }

    /**
     * Clean up inconsistent database records (records without files).
     */
    private function cleanupInconsistentRecords(int $batchSize): void
    {
        $this->outputResult('Scanning for inconsistent database records...', 'info');
        
        // Find uploads that should have files but don't
        $inconsistentRecords = FileUpload::whereNotNull('google_drive_file_id')
            ->orWhere('retry_count', '>', 0)
            ->get()
            ->filter(function ($upload) {
                // Skip if file doesn't exist locally (this is expected for uploaded files)
                if (!$upload->localFileExists()) {
                    // Only consider it inconsistent if it's not uploaded to Google Drive
                    return empty($upload->google_drive_file_id);
                }
                
                return false;
            });
        
        $this->outputResult("Found {$inconsistentRecords->count()} inconsistent records", 'info');
        
        if ($inconsistentRecords->isEmpty()) {
            return;
        }
        
        $this->results['inconsistent_records'] = $inconsistentRecords->map(function ($upload) {
            return [
                'id' => $upload->id,
                'filename' => $upload->original_filename,
                'status' => $upload->getStatus(),
                'created_at' => $upload->created_at->toISOString()
            ];
        })->toArray();
        
        if ($this->dryRun) {
            $this->outputResult('DRY RUN: Would mark inconsistent records as missing_file', 'comment');
            if ($this->detailed) {
                foreach ($inconsistentRecords as $upload) {
                    $this->line("  - ID {$upload->id}: {$upload->original_filename} (status: {$upload->getStatus()})");
                }
            }
            return;
        }
        
        $fixed = 0;
        $errors = 0;
        
        foreach ($inconsistentRecords->chunk($batchSize) as $batch) {
            foreach ($batch as $upload) {
                try {
                    $upload->update([
                        'last_error' => 'File not found in storage during cleanup',
                        'error_details' => json_encode([
                            'error_type' => 'missing_file',
                            'detected_at' => now()->toISOString(),
                            'cleanup_command' => true
                        ]),
                        'last_processed_at' => now()
                    ]);
                    
                    $fixed++;
                    
                    if ($this->detailed) {
                        $this->line("  Fixed: ID {$upload->id} - {$upload->original_filename}");
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->results['summary']['errors'][] = "Failed to fix record ID {$upload->id}: {$e->getMessage()}";
                    
                    if ($this->detailed) {
                        $this->error("  Failed to fix: ID {$upload->id} - {$e->getMessage()}");
                    }
                }
            }
        }
        
        $this->results['summary']['inconsistent_records_fixed'] = $fixed;
        $this->outputResult("Fixed {$fixed} inconsistent records" . ($errors > 0 ? " ({$errors} errors)" : ''), 'info');
    }

    /**
     * Clean up failed upload artifacts.
     */
    private function cleanupFailedUploads(int $olderThanDays, int $batchSize): void
    {
        $this->outputResult("Scanning for failed uploads older than {$olderThanDays} days...", 'info');
        
        $cutoffDate = Carbon::now()->subDays($olderThanDays);
        
        // Find uploads that are failed based on the model's logic
        $failedUploads = FileUpload::failed()
            ->where('created_at', '<', $cutoffDate)
            ->get();
        
        $this->outputResult("Found {$failedUploads->count()} failed uploads to clean up", 'info');
        
        if ($failedUploads->isEmpty()) {
            return;
        }
        
        $this->results['failed_uploads'] = $failedUploads->map(function ($upload) {
            return [
                'id' => $upload->id,
                'filename' => $upload->original_filename,
                'status' => $upload->getStatus(),
                'created_at' => $upload->created_at->toISOString(),
                'last_error' => $upload->last_error
            ];
        })->toArray();
        
        if ($this->dryRun) {
            $this->outputResult('DRY RUN: Would remove failed upload records and associated files', 'comment');
            if ($this->detailed) {
                foreach ($failedUploads as $upload) {
                    $this->line("  - ID {$upload->id}: {$upload->original_filename} (status: {$upload->getStatus()})");
                }
            }
            return;
        }
        
        $cleaned = 0;
        $errors = 0;
        $totalSpaceFreed = 0;
        
        foreach ($failedUploads->chunk($batchSize) as $batch) {
            foreach ($batch as $upload) {
                try {
                    DB::beginTransaction();
                    
                    // Remove associated files if they exist
                    $filePaths = [
                        "uploads/{$upload->filename}",
                        "chunks/{$upload->filename}",
                        "temp/{$upload->filename}"
                    ];
                    
                    foreach ($filePaths as $path) {
                        if (Storage::exists($path)) {
                            $fileSize = Storage::size($path);
                            if (Storage::delete($path)) {
                                $totalSpaceFreed += $fileSize;
                            }
                        }
                    }
                    
                    // Remove database record
                    $upload->delete();
                    
                    DB::commit();
                    $cleaned++;
                    
                    if ($this->detailed) {
                        $this->line("  Cleaned: ID {$upload->id} - {$upload->original_filename}");
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    $errors++;
                    $this->results['summary']['errors'][] = "Failed to clean upload ID {$upload->id}: {$e->getMessage()}";
                    
                    if ($this->detailed) {
                        $this->error("  Failed to clean: ID {$upload->id} - {$e->getMessage()}");
                    }
                }
            }
        }
        
        $this->results['summary']['failed_uploads_cleaned'] = $cleaned;
        $this->results['summary']['total_space_freed_mb'] += $totalSpaceFreed / 1024 / 1024;
        $this->outputResult("Cleaned {$cleaned} failed uploads" . ($errors > 0 ? " ({$errors} errors)" : ''), 'info');
    }

    /**
     * Clean up temporary files.
     */
    private function cleanupTemporaryFiles(): void
    {
        $this->outputResult('Scanning for old temporary files...', 'info');
        
        $maxAgeHours = config('upload-recovery.temp_file_max_age_hours', 24);
        $cutoffTime = Carbon::now()->subHours($maxAgeHours)->timestamp;
        
        $tempPaths = ['temp', 'chunks'];
        $tempFiles = [];
        $totalSize = 0;
        
        foreach ($tempPaths as $path) {
            if (!Storage::exists($path)) {
                continue;
            }
            
            $files = Storage::allFiles($path);
            
            foreach ($files as $file) {
                $lastModified = Storage::lastModified($file);
                
                if ($lastModified < $cutoffTime) {
                    $fileSize = Storage::size($file);
                    $tempFiles[] = [
                        'path' => $file,
                        'size' => $fileSize,
                        'last_modified' => $lastModified
                    ];
                    $totalSize += $fileSize;
                }
            }
        }
        
        $this->outputResult("Found " . count($tempFiles) . " old temporary files ({$this->formatBytes($totalSize)})", 'info');
        
        if (empty($tempFiles)) {
            return;
        }
        
        $this->results['temporary_files'] = $tempFiles;
        
        if ($this->dryRun) {
            $this->outputResult('DRY RUN: Would remove old temporary files', 'comment');
            if ($this->detailed) {
                foreach ($tempFiles as $file) {
                    $this->line("  - {$file['path']} ({$this->formatBytes($file['size'])})");
                }
            }
            return;
        }
        
        $removed = 0;
        $errors = 0;
        
        foreach ($tempFiles as $file) {
            try {
                if (Storage::delete($file['path'])) {
                    $removed++;
                    $this->results['summary']['total_space_freed_mb'] += $file['size'] / 1024 / 1024;
                    
                    if ($this->detailed) {
                        $this->line("  Removed: {$file['path']}");
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                $this->results['summary']['errors'][] = "Failed to remove {$file['path']}: {$e->getMessage()}";
                
                if ($this->detailed) {
                    $this->error("  Failed to remove: {$file['path']} - {$e->getMessage()}");
                }
            }
        }
        
        $this->results['summary']['temporary_files_removed'] = $removed;
        $this->outputResult("Removed {$removed} temporary files" . ($errors > 0 ? " ({$errors} errors)" : ''), 'info');
    }

    /**
     * Display cleanup summary.
     */
    private function displaySummary(): void
    {
        if ($this->jsonOutput) {
            return;
        }

        $this->newLine();
        $this->info('Cleanup Summary');
        $this->info('===============');
        
        $summary = $this->results['summary'];
        
        $this->line("Orphaned files removed: {$summary['orphaned_files_removed']}");
        $this->line("Inconsistent records fixed: {$summary['inconsistent_records_fixed']}");
        $this->line("Failed uploads cleaned: {$summary['failed_uploads_cleaned']}");
        $this->line("Temporary files removed: {$summary['temporary_files_removed']}");
        $this->line("Total space freed: {$this->formatBytes($summary['total_space_freed_mb'] * 1024 * 1024)}");
        
        if (!empty($summary['errors'])) {
            $this->newLine();
            $this->error("Errors encountered: " . count($summary['errors']));
            if ($this->detailed) {
                foreach ($summary['errors'] as $error) {
                    $this->error("  - {$error}");
                }
            }
        }
        
        $this->newLine();
        
        if ($this->dryRun) {
            $this->warn('DRY RUN completed - no actual changes were made');
        } else {
            $this->info('Cleanup completed successfully');
        }
    }

    /**
     * Output result message based on format preference.
     */
    private function outputResult(string $message, string $type = 'info'): void
    {
        if ($this->jsonOutput) {
            return;
        }

        match ($type) {
            'error' => $this->error($message),
            'warn', 'warning' => $this->warn($message),
            'comment' => $this->comment($message),
            default => $this->info($message)
        };
    }

    /**
     * Format bytes into human-readable format.
     */
    private function formatBytes(int $bytes): string
    {
        if (function_exists('format_bytes')) {
            return format_bytes($bytes);
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}