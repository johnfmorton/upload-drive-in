<?php

namespace App\Console\Commands;

use App\Models\FileUpload;
use App\Services\UploadRecoveryService;
use App\Services\UploadDiagnosticService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RecoverPendingUploadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:recover-pending 
                            {--ids= : Comma-separated list of specific upload IDs to process}
                            {--all : Process all pending uploads (default behavior)}
                            {--limit= : Maximum number of uploads to process (default from config)}
                            {--batch-size= : Number of uploads to process per batch (default from config)}
                            {--dry-run : Show what would be processed without actually processing}
                            {--detailed : Show detailed progress and results}
                            {--json : Output results in JSON format}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recover stuck pending uploads with batch processing and progress reporting';

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
        $this->dryRun = $this->option('dry-run');
        $this->detailed = $this->option('detailed');
        $this->jsonOutput = $this->option('json');
        $this->force = $this->option('force');

        if (!$this->jsonOutput) {
            $this->info('🔄 Upload Recovery System');
            $this->info('========================');
            $this->newLine();
        }

        // Get uploads to process
        $uploadsToProcess = $this->getUploadsToProcess();
        
        if ($uploadsToProcess->isEmpty()) {
            if (!$this->jsonOutput) {
                $this->info('✅ No uploads found that need recovery.');
            }
            
            $this->results = [
                'status' => 'no_uploads_found',
                'message' => 'No uploads found that need recovery',
                'total_processed' => 0,
                'timestamp' => now()->toISOString()
            ];
            
            if ($this->jsonOutput) {
                $this->line(json_encode($this->results, JSON_PRETTY_PRINT));
            }
            
            return self::SUCCESS;
        }

        // Show summary and get confirmation
        if (!$this->showSummaryAndConfirm($uploadsToProcess)) {
            if (!$this->jsonOutput) {
                $this->info('❌ Operation cancelled by user.');
            }
            return self::SUCCESS;
        }

        // Perform system health check before processing (skip for dry runs)
        if (!$this->dryRun && !$this->performPreProcessingHealthCheck()) {
            return self::FAILURE;
        }

        // Process the uploads
        $this->results = $this->processUploads($uploadsToProcess);

        // Display results
        $this->displayResults();

        // Return appropriate exit code
        return $this->results['failed_recoveries'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Get the uploads to process based on command options
     */
    private function getUploadsToProcess(): Collection
    {
        $specificIds = $this->option('ids');
        $limit = $this->option('limit') ?: config('upload-recovery.max_processing_limit', 100);

        if ($specificIds) {
            // Process specific upload IDs
            $ids = array_map('trim', explode(',', $specificIds));
            $ids = array_filter($ids, 'is_numeric');
            
            if (empty($ids)) {
                if (!$this->jsonOutput) {
                    $this->error('❌ No valid upload IDs provided.');
                }
                return collect();
            }

            $uploads = FileUpload::whereIn('id', $ids)->get();
            
            // Check for missing IDs
            $foundIds = $uploads->pluck('id')->toArray();
            $missingIds = array_diff($ids, $foundIds);
            
            if (!empty($missingIds) && !$this->jsonOutput) {
                $this->warn('⚠️  Upload IDs not found: ' . implode(', ', $missingIds));
            }
            
            return $uploads;
        } else {
            // Process all stuck uploads
            return $this->recoveryService->detectStuckUploads()->take($limit);
        }
    }

    /**
     * Show summary of uploads to process and get user confirmation
     */
    private function showSummaryAndConfirm(Collection $uploads): bool
    {
        if ($this->jsonOutput) {
            return true; // Skip confirmation for JSON output
        }

        $this->info('📊 Recovery Summary');
        $this->info('------------------');
        
        // Group uploads by status for summary
        $statusCounts = $uploads->groupBy(function ($upload) {
            try {
                if (!empty($upload->google_drive_file_id)) {
                    return 'already_completed';
                }
                if ($upload->hasExceededRecoveryAttempts()) {
                    return 'max_attempts_exceeded';
                }
                if (!$upload->localFileExists()) {
                    return 'file_missing';
                }
                return 'recoverable';
            } catch (\Exception $e) {
                return 'unknown';
            }
        })->map->count();

        $this->line('Total uploads to process: ' . $uploads->count());
        
        foreach ($statusCounts as $status => $count) {
            $icon = match ($status) {
                'recoverable' => '🔄',
                'already_completed' => '✅',
                'max_attempts_exceeded' => '❌',
                'file_missing' => '📁',
                default => '•'
            };
            
            $label = match ($status) {
                'recoverable' => 'Recoverable uploads',
                'already_completed' => 'Already completed',
                'max_attempts_exceeded' => 'Max attempts exceeded',
                'file_missing' => 'Missing local files',
                default => ucfirst(str_replace('_', ' ', $status))
            };
            
            $this->line("  {$icon} {$label}: {$count}");
        }

        if ($this->detailed) {
            $this->newLine();
            $this->info('📋 Upload Details');
            $this->info('----------------');
            
            foreach ($uploads->take(10) as $upload) {
                $status = $this->getUploadStatusDescription($upload);
                $age = $upload->created_at->diffForHumans();
                $this->line("  • ID {$upload->id}: {$upload->filename} ({$status}, {$age})");
            }
            
            if ($uploads->count() > 10) {
                $this->line("  ... and " . ($uploads->count() - 10) . " more uploads");
            }
        }

        $this->newLine();

        if ($this->dryRun) {
            $this->info('🔍 DRY RUN MODE - No actual processing will occur');
            return true;
        }

        if ($this->force) {
            return true;
        }

        return $this->confirm('Do you want to proceed with recovery?', false);
    }

    /**
     * Get a human-readable status description for an upload
     */
    private function getUploadStatusDescription(FileUpload $upload): string
    {
        try {
            if (!empty($upload->google_drive_file_id)) {
                return 'Already completed';
            }
            if ($upload->hasExceededRecoveryAttempts()) {
                return 'Max attempts exceeded';
            }
            if (!$upload->localFileExists()) {
                return 'File missing';
            }
            if ($upload->isStuck()) {
                return 'Stuck (pending ' . $upload->created_at->diffForHumans() . ')';
            }
            return 'Pending';
        } catch (\Exception $e) {
            return 'Unknown status';
        }
    }

    /**
     * Perform system health check before processing
     */
    private function performPreProcessingHealthCheck(): bool
    {
        if (!$this->jsonOutput) {
            $this->info('🔍 Pre-processing Health Check');
            $this->info('------------------------------');
        }

        try {
            // Check critical system components
            $healthCheck = $this->diagnosticService->performHealthCheck();
            
            $criticalIssues = [];
            
            // Check overall status
            if ($healthCheck['overall_status'] === 'critical') {
                $criticalIssues[] = 'System health check failed with critical status';
            }
            
            // Check specific critical components
            if (isset($healthCheck['checks']['queue_worker']) && 
                $healthCheck['checks']['queue_worker']['status'] === 'error') {
                $criticalIssues[] = 'Queue worker is not responsive';
            }
            
            if (isset($healthCheck['checks']['disk_space']) && 
                $healthCheck['checks']['disk_space']['status'] === 'error') {
                $criticalIssues[] = 'Disk space is critically low';
            }
            
            if (isset($healthCheck['checks']['google_drive_api']) && 
                $healthCheck['checks']['google_drive_api']['status'] === 'error') {
                $criticalIssues[] = 'Google Drive API is not accessible';
            }

            if (!empty($criticalIssues)) {
                if (!$this->jsonOutput) {
                    $this->error('❌ Critical system issues detected:');
                    foreach ($criticalIssues as $issue) {
                        $this->line("  • {$issue}");
                    }
                    $this->newLine();
                    $this->error('Please resolve these issues before running recovery.');
                }
                
                $this->results['pre_check_failed'] = true;
                $this->results['critical_issues'] = $criticalIssues;
                
                return false;
            }

            if (!$this->jsonOutput) {
                $this->info('✅ System health check passed');
                $this->newLine();
            }

            return true;

        } catch (\Exception $e) {
            if (!$this->jsonOutput) {
                $this->error('❌ Health check failed: ' . $e->getMessage());
            }
            
            $this->results['pre_check_error'] = $e->getMessage();
            return false;
        }
    }

    /**
     * Process the uploads with progress reporting
     */
    private function processUploads(Collection $uploads): array
    {
        if ($this->dryRun) {
            return $this->simulateDryRun($uploads);
        }

        $batchSize = $this->option('batch-size') ?: config('upload-recovery.batch_size', 10);
        
        if (!$this->jsonOutput) {
            $this->info('🔄 Processing Uploads');
            $this->info('--------------------');
            $this->line("Processing {$uploads->count()} uploads in batches of {$batchSize}");
            $this->newLine();
        }

        $startTime = microtime(true);
        
        // Use the recovery service for bulk processing
        $results = $this->recoveryService->bulkRecovery($uploads);
        
        $endTime = microtime(true);
        $processingTime = round($endTime - $startTime, 2);
        
        // Add additional metadata
        $results['processing_time_seconds'] = $processingTime;
        $results['batch_size'] = $batchSize;
        $results['command_options'] = [
            'detailed' => $this->detailed,
            'dry_run' => $this->dryRun,
            'force' => $this->force,
            'specific_ids' => $this->option('ids') ? explode(',', $this->option('ids')) : null
        ];

        // Show progress during processing if detailed mode
        if ($this->detailed && !$this->jsonOutput) {
            $this->showDetailedProgress($results);
        }

        return $results;
    }

    /**
     * Simulate processing for dry run mode
     */
    private function simulateDryRun(Collection $uploads): array
    {
        if (!$this->jsonOutput) {
            $this->info('🔍 DRY RUN - Simulating Recovery Process');
            $this->info('---------------------------------------');
        }

        $results = [
            'dry_run' => true,
            'total_processed' => $uploads->count(),
            'successful_recoveries' => 0,
            'failed_recoveries' => 0,
            'already_completed' => 0,
            'file_missing' => 0,
            'max_attempts_exceeded' => 0,
            'details' => [],
            'started_at' => now()->toISOString(),
            'completed_at' => now()->toISOString()
        ];

        foreach ($uploads as $upload) {
            $simulatedResult = [
                'upload_id' => $upload->id,
                'filename' => $upload->filename,
                'would_process' => true,
                'reason' => 'Dry run simulation'
            ];

            if (!empty($upload->google_drive_file_id)) {
                $results['already_completed']++;
                $simulatedResult['would_process'] = false;
                $simulatedResult['reason'] = 'Already completed';
            } elseif ($upload->hasExceededRecoveryAttempts()) {
                $results['max_attempts_exceeded']++;
                $simulatedResult['would_process'] = false;
                $simulatedResult['reason'] = 'Max attempts exceeded';
            } elseif (!$upload->localFileExists()) {
                $results['file_missing']++;
                $simulatedResult['would_process'] = false;
                $simulatedResult['reason'] = 'Local file missing';
            } else {
                $results['successful_recoveries']++;
                $simulatedResult['reason'] = 'Would be re-queued for processing';
            }

            $results['details'][] = $simulatedResult;

            if ($this->detailed && !$this->jsonOutput) {
                $icon = $simulatedResult['would_process'] ? '🔄' : '⏭️';
                $this->line("  {$icon} ID {$upload->id}: {$simulatedResult['reason']}");
            }
        }

        return $results;
    }

    /**
     * Show detailed progress during processing
     */
    private function showDetailedProgress(array $results): void
    {
        if (empty($results['details'])) {
            return;
        }

        $this->newLine();
        $this->info('📋 Detailed Results');
        $this->info('------------------');

        foreach ($results['details'] as $detail) {
            $uploadId = $detail['upload_id'];
            $success = $detail['success'];
            $message = $detail['message'] ?? $detail['error'] ?? 'Unknown result';
            
            $icon = $success ? '✅' : '❌';
            $this->line("  {$icon} Upload {$uploadId}: {$message}");
            
            // Show additional details for failures
            if (!$success && isset($detail['error']) && $this->detailed) {
                $this->line("      Error: {$detail['error']}");
            }
        }
    }

    /**
     * Display the final results
     */
    private function displayResults(): void
    {
        if ($this->jsonOutput) {
            $this->line(json_encode($this->results, JSON_PRETTY_PRINT));
            return;
        }

        $this->newLine();
        $this->info('📊 Recovery Results');
        $this->info('------------------');

        if ($this->dryRun) {
            $this->line('Mode: DRY RUN (no actual processing performed)');
        }

        $this->line('Total processed: ' . $this->results['total_processed']);
        $this->line('✅ Successful recoveries: ' . $this->results['successful_recoveries']);
        $this->line('❌ Failed recoveries: ' . $this->results['failed_recoveries']);
        $this->line('✅ Already completed: ' . $this->results['already_completed']);
        $this->line('📁 Missing files: ' . $this->results['file_missing']);
        $this->line('🚫 Max attempts exceeded: ' . $this->results['max_attempts_exceeded']);

        if (isset($this->results['processing_time_seconds'])) {
            $this->line('⏱️  Processing time: ' . $this->results['processing_time_seconds'] . ' seconds');
        }

        // Show recommendations
        $this->showRecommendations();

        // Log the results
        $this->logResults();
    }

    /**
     * Show recommendations based on results
     */
    private function showRecommendations(): void
    {
        $recommendations = [];

        if ($this->results['failed_recoveries'] > 0) {
            $recommendations[] = 'Run uploads:diagnose to investigate failure causes';
        }

        if ($this->results['file_missing'] > 0) {
            $recommendations[] = 'Run uploads:cleanup to handle uploads with missing files';
        }

        if ($this->results['max_attempts_exceeded'] > 0) {
            $recommendations[] = 'Review uploads that exceeded max attempts - they may need manual intervention';
        }

        if ($this->results['successful_recoveries'] > 0) {
            $recommendations[] = 'Monitor queue worker to ensure recovered uploads are processed';
        }

        if (!empty($recommendations)) {
            $this->newLine();
            $this->info('💡 Recommendations');
            $this->info('------------------');
            
            foreach ($recommendations as $index => $recommendation) {
                $this->line(($index + 1) . ". {$recommendation}");
            }
        }
    }

    /**
     * Log the recovery results for audit purposes
     */
    private function logResults(): void
    {
        $logData = [
            'command' => 'uploads:recover-pending',
            'results' => $this->results,
            'options' => [
                'ids' => $this->option('ids'),
                'all' => $this->option('all'),
                'limit' => $this->option('limit'),
                'batch_size' => $this->option('batch-size'),
                'dry_run' => $this->dryRun,
                'detailed' => $this->detailed,
                'force' => $this->force
            ],
            'executed_at' => now()->toISOString(),
            'executed_by' => 'CLI'
        ];

        Log::info('🔄 Upload recovery command completed', $logData);
    }
}