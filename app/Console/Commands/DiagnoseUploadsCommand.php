<?php

namespace App\Console\Commands;

use App\Models\FileUpload;
use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Services\UploadDiagnosticService;
use App\Services\UploadRecoveryService;
use App\Enums\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DiagnoseUploadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:diagnose 
                            {--detailed : Show detailed information for each check}
                            {--json : Output results in JSON format}
                            {--check= : Run specific check (queue|storage|api|tokens|uploads|all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform comprehensive system health checks for upload processing';

    private UploadDiagnosticService $diagnosticService;
    private UploadRecoveryService $recoveryService;
    private array $results = [];
    private bool $detailed = false;
    private bool $jsonOutput = false;

    public function __construct(
        UploadDiagnosticService $diagnosticService,
        UploadRecoveryService $recoveryService
    ) {
        parent::__construct();
        $this->diagnosticService = $diagnosticService;
        $this->recoveryService = $recoveryService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->detailed = $this->option('detailed');
        $this->jsonOutput = $this->option('json');
        $checkType = $this->option('check') ?? 'all';

        if (!$this->jsonOutput) {
            $this->info('🔍 Upload System Diagnostics');
            $this->info('==========================');
            $this->newLine();
        }

        // Run specified checks
        switch ($checkType) {
            case 'queue':
                $this->checkQueueSystem();
                break;
            case 'storage':
                $this->checkStorageSystem();
                break;
            case 'api':
                $this->checkGoogleDriveAPI();
                break;
            case 'tokens':
                $this->checkGoogleDriveTokens();
                break;
            case 'uploads':
                $this->checkUploadStatus();
                break;
            case 'all':
            default:
                $this->checkQueueSystem();
                $this->checkStorageSystem();
                $this->checkGoogleDriveAPI();
                $this->checkGoogleDriveTokens();
                $this->checkUploadStatus();
                break;
        }

        // Output results
        if ($this->jsonOutput) {
            $this->line(json_encode($this->results, JSON_PRETTY_PRINT));
        } else {
            $this->displaySummary();
            $this->displayRecommendations();
        }

        // Return appropriate exit code
        $hasErrors = collect($this->results)->contains(function ($result) {
            return $result['status'] === 'error';
        });

        return $hasErrors ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Check queue system health
     */
    private function checkQueueSystem(): void
    {
        if (!$this->jsonOutput) {
            $this->info('📋 Queue System Check');
            $this->info('--------------------');
        }

        $result = [
            'check' => 'queue_system',
            'status' => 'ok',
            'details' => [],
            'recommendations' => []
        ];

        try {
            // Check queue connection
            $connection = Queue::connection();
            $result['details']['connection'] = 'Connected';

            // Check failed jobs
            $failedJobs = DB::table('failed_jobs')->count();
            $result['details']['failed_jobs'] = $failedJobs;

            if ($failedJobs > 0) {
                $result['status'] = 'warning';
                $result['recommendations'][] = "Clear failed jobs with: php artisan queue:retry all";
            }

            // Check recent job activity
            $recentJobs = DB::table('jobs')->where('created_at', '>', Carbon::now()->subHour())->count();
            $result['details']['recent_jobs'] = $recentJobs;

            // Check for stuck jobs
            $stuckJobs = DB::table('jobs')
                ->where('reserved_at', '<', Carbon::now()->subMinutes(30))
                ->whereNotNull('reserved_at')
                ->count();
            
            $result['details']['stuck_jobs'] = $stuckJobs;

            if ($stuckJobs > 0) {
                $result['status'] = 'error';
                $result['recommendations'][] = "Restart queue workers to clear stuck jobs";
            }

            if (!$this->jsonOutput) {
                $this->displayCheckResult('Queue Connection', $result['details']['connection'], 'ok');
                $this->displayCheckResult('Failed Jobs', $failedJobs, $failedJobs > 0 ? 'warning' : 'ok');
                $this->displayCheckResult('Recent Jobs (1h)', $recentJobs, 'info');
                $this->displayCheckResult('Stuck Jobs', $stuckJobs, $stuckJobs > 0 ? 'error' : 'ok');
            }

        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['details']['error'] = $e->getMessage();
            $result['recommendations'][] = "Check queue configuration and ensure queue worker is running";

            if (!$this->jsonOutput) {
                $this->displayCheckResult('Queue System', 'Error: ' . $e->getMessage(), 'error');
            }
        }

        $this->results['queue_system'] = $result;
        if (!$this->jsonOutput) {
            $this->newLine();
        }
    }

    /**
     * Check storage system health
     */
    private function checkStorageSystem(): void
    {
        if (!$this->jsonOutput) {
            $this->info('💾 Storage System Check');
            $this->info('----------------------');
        }

        $result = [
            'check' => 'storage_system',
            'status' => 'ok',
            'details' => [],
            'recommendations' => []
        ];

        try {
            // Check disk space
            $uploadPath = storage_path('app/public/uploads');
            $freeBytes = disk_free_space($uploadPath);
            $totalBytes = disk_total_space($uploadPath);
            $usedPercent = (($totalBytes - $freeBytes) / $totalBytes) * 100;

            $result['details']['free_space'] = format_bytes($freeBytes);
            $result['details']['total_space'] = format_bytes($totalBytes);
            $result['details']['used_percent'] = round($usedPercent, 2);

            if ($usedPercent > 90) {
                $result['status'] = 'error';
                $result['recommendations'][] = "Disk usage is critical (>90%). Clean up old files immediately.";
            } elseif ($usedPercent > 80) {
                $result['status'] = 'warning';
                $result['recommendations'][] = "Disk usage is high (>80%). Consider cleaning up old files.";
            }

            // Check upload directory permissions
            $uploadDir = storage_path('app/public/uploads');
            $writable = is_writable($uploadDir);
            $result['details']['upload_dir_writable'] = $writable;

            if (!$writable) {
                $result['status'] = 'error';
                $result['recommendations'][] = "Upload directory is not writable. Check permissions.";
            }

            // Count orphaned files (files without database records)
            $orphanedFiles = $this->countOrphanedFiles();
            $result['details']['orphaned_files'] = $orphanedFiles;

            if ($orphanedFiles > 10) {
                $result['status'] = 'warning';
                $result['recommendations'][] = "Found {$orphanedFiles} orphaned files. Run cleanup command.";
            }

            if (!$this->jsonOutput) {
                $this->displayCheckResult('Free Space', $result['details']['free_space'], 'info');
                $this->displayCheckResult('Disk Usage', $result['details']['used_percent'] . '%', 
                    $usedPercent > 90 ? 'error' : ($usedPercent > 80 ? 'warning' : 'ok'));
                $this->displayCheckResult('Upload Dir Writable', $writable ? 'Yes' : 'No', 
                    $writable ? 'ok' : 'error');
                $this->displayCheckResult('Orphaned Files', $orphanedFiles, 
                    $orphanedFiles > 10 ? 'warning' : 'ok');
            }

        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['details']['error'] = $e->getMessage();
            $result['recommendations'][] = "Check storage configuration and permissions";

            if (!$this->jsonOutput) {
                $this->displayCheckResult('Storage System', 'Error: ' . $e->getMessage(), 'error');
            }
        }

        $this->results['storage_system'] = $result;
        if (!$this->jsonOutput) {
            $this->newLine();
        }
    }

    /**
     * Check Google Drive API connectivity
     */
    private function checkGoogleDriveAPI(): void
    {
        if (!$this->jsonOutput) {
            $this->info('🔗 Google Drive API Check');
            $this->info('-------------------------');
        }

        $result = [
            'check' => 'google_drive_api',
            'status' => 'ok',
            'details' => [],
            'recommendations' => []
        ];

        try {
            // Check API configuration
            $clientId = config('cloud-storage.google_drive.client_id');
            $clientSecret = config('cloud-storage.google_drive.client_secret');

            $result['details']['client_id_configured'] = !empty($clientId);
            $result['details']['client_secret_configured'] = !empty($clientSecret);

            if (empty($clientId) || empty($clientSecret)) {
                $result['status'] = 'error';
                $result['recommendations'][] = "Configure Google Drive API credentials in environment file";
            }

            // Test API connectivity with a user token
            $testUser = User::whereHas('googleDriveToken')->first();
            if ($testUser) {
                $connectivity = $this->diagnosticService->validateGoogleDriveConnectivity($testUser);
                $result['details']['api_connectivity'] = $connectivity['status'];
                $result['details']['test_user'] = $testUser->email;

                if ($connectivity['status'] !== 'connected') {
                    $result['status'] = 'warning';
                    $result['recommendations'][] = "Google Drive API connectivity issues detected";
                }
            } else {
                $result['details']['api_connectivity'] = 'no_tokens_to_test';
                $result['status'] = 'warning';
                $result['recommendations'][] = "No Google Drive tokens available for testing connectivity";
            }

            if (!$this->jsonOutput) {
                $this->displayCheckResult('Client ID', $result['details']['client_id_configured'] ? 'Configured' : 'Missing', 
                    $result['details']['client_id_configured'] ? 'ok' : 'error');
                $this->displayCheckResult('Client Secret', $result['details']['client_secret_configured'] ? 'Configured' : 'Missing', 
                    $result['details']['client_secret_configured'] ? 'ok' : 'error');
                
                if (isset($result['details']['api_connectivity'])) {
                    $this->displayCheckResult('API Connectivity', $result['details']['api_connectivity'], 
                        $result['details']['api_connectivity'] === 'connected' ? 'ok' : 'warning');
                }
            }

        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['details']['error'] = $e->getMessage();
            $result['recommendations'][] = "Check Google Drive API configuration and credentials";

            if (!$this->jsonOutput) {
                $this->displayCheckResult('Google Drive API', 'Error: ' . $e->getMessage(), 'error');
            }
        }

        $this->results['google_drive_api'] = $result;
        if (!$this->jsonOutput) {
            $this->newLine();
        }
    }

    /**
     * Check Google Drive tokens
     */
    private function checkGoogleDriveTokens(): void
    {
        if (!$this->jsonOutput) {
            $this->info('🔑 Google Drive Tokens Check');
            $this->info('---------------------------');
        }

        $result = [
            'check' => 'google_drive_tokens',
            'status' => 'ok',
            'details' => [],
            'recommendations' => []
        ];

        try {
            $totalTokens = GoogleDriveToken::count();
            $result['details']['total_tokens'] = $totalTokens;

            if ($totalTokens === 0) {
                $result['status'] = 'warning';
                $result['recommendations'][] = "No Google Drive tokens configured. Set up Google Drive integration.";
            }

            // Check for expired tokens
            $expiredTokens = GoogleDriveToken::where('expires_at', '<', Carbon::now())->count();
            $result['details']['expired_tokens'] = $expiredTokens;

            if ($expiredTokens > 0) {
                $result['status'] = 'warning';
                $result['recommendations'][] = "Found {$expiredTokens} expired tokens. Run token refresh command.";
            }

            // Check for tokens expiring soon (within 24 hours)
            $expiringSoon = GoogleDriveToken::where('expires_at', '<', Carbon::now()->addDay())
                ->where('expires_at', '>', Carbon::now())
                ->count();
            $result['details']['expiring_soon'] = $expiringSoon;

            if ($expiringSoon > 0) {
                $result['status'] = 'warning';
                $result['recommendations'][] = "Found {$expiringSoon} tokens expiring within 24 hours.";
            }

            // Check users without tokens
            $usersWithoutTokens = User::whereDoesntHave('googleDriveToken')
                ->whereIn('role', [UserRole::ADMIN, UserRole::EMPLOYEE])
                ->count();
            $result['details']['users_without_tokens'] = $usersWithoutTokens;

            if ($usersWithoutTokens > 0) {
                $result['status'] = 'warning';
                $result['recommendations'][] = "Found {$usersWithoutTokens} users without Google Drive tokens.";
            }

            if (!$this->jsonOutput) {
                $this->displayCheckResult('Total Tokens', $totalTokens, $totalTokens > 0 ? 'ok' : 'warning');
                $this->displayCheckResult('Expired Tokens', $expiredTokens, $expiredTokens > 0 ? 'warning' : 'ok');
                $this->displayCheckResult('Expiring Soon', $expiringSoon, $expiringSoon > 0 ? 'warning' : 'ok');
                $this->displayCheckResult('Users Without Tokens', $usersWithoutTokens, 
                    $usersWithoutTokens > 0 ? 'warning' : 'ok');
            }

        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['details']['error'] = $e->getMessage();
            $result['recommendations'][] = "Check Google Drive token configuration";

            if (!$this->jsonOutput) {
                $this->displayCheckResult('Google Drive Tokens', 'Error: ' . $e->getMessage(), 'error');
            }
        }

        $this->results['google_drive_tokens'] = $result;
        if (!$this->jsonOutput) {
            $this->newLine();
        }
    }

    /**
     * Check upload status and identify issues
     */
    private function checkUploadStatus(): void
    {
        if (!$this->jsonOutput) {
            $this->info('📤 Upload Status Check');
            $this->info('---------------------');
        }

        $result = [
            'check' => 'upload_status',
            'status' => 'ok',
            'details' => [],
            'recommendations' => []
        ];

        try {
            // Count uploads by status
            $totalUploads = FileUpload::count();
            
            // Check if status column exists (it's added in earlier tasks)
            $hasStatusColumn = \Schema::hasColumn('file_uploads', 'status');
            
            if ($hasStatusColumn) {
                $pendingUploads = FileUpload::where('status', 'pending')->count();
                $failedUploads = FileUpload::where('status', 'failed')->count();
                $uploadedUploads = FileUpload::where('status', 'uploaded')->count();
            } else {
                // Fallback to checking google_drive_file_id for pending status
                $pendingUploads = FileUpload::whereNull('google_drive_file_id')
                    ->orWhere('google_drive_file_id', '')
                    ->count();
                $failedUploads = 0; // Can't determine without status column
                $uploadedUploads = FileUpload::whereNotNull('google_drive_file_id')
                    ->where('google_drive_file_id', '!=', '')
                    ->count();
            }

            $result['details']['total_uploads'] = $totalUploads;
            $result['details']['pending_uploads'] = $pendingUploads;
            $result['details']['failed_uploads'] = $failedUploads;
            $result['details']['uploaded_uploads'] = $uploadedUploads;

            // Check for stuck uploads
            $stuckUploads = $this->recoveryService->detectStuckUploads();
            $result['details']['stuck_uploads'] = $stuckUploads->count();

            if ($stuckUploads->count() > 0) {
                $result['status'] = 'warning';
                $result['recommendations'][] = "Found {$stuckUploads->count()} stuck uploads. Run recovery command.";
            }

            // Check failure rate
            if ($totalUploads > 0) {
                $failureRate = ($failedUploads / $totalUploads) * 100;
                $result['details']['failure_rate'] = round($failureRate, 2);

                if ($failureRate > 10) {
                    $result['status'] = 'error';
                    $result['recommendations'][] = "High failure rate ({$failureRate}%). Investigate upload issues.";
                } elseif ($failureRate > 5) {
                    $result['status'] = 'warning';
                    $result['recommendations'][] = "Elevated failure rate ({$failureRate}%). Monitor upload issues.";
                }
            }

            // Check for uploads with missing files (only if status column exists)
            if ($hasStatusColumn) {
                $missingFileUploads = FileUpload::where('status', 'missing_file')->count();
                $result['details']['missing_file_uploads'] = $missingFileUploads;

                if ($missingFileUploads > 0) {
                    $result['status'] = 'warning';
                    $result['recommendations'][] = "Found {$missingFileUploads} uploads with missing files. Run cleanup.";
                }
            } else {
                $missingFileUploads = 0;
                $result['details']['missing_file_uploads'] = 'N/A (status column not available)';
            }

            // Check recent upload activity
            $recentUploads = FileUpload::where('created_at', '>', Carbon::now()->subDay())->count();
            $result['details']['recent_uploads_24h'] = $recentUploads;

            if (!$this->jsonOutput) {
                $this->displayCheckResult('Total Uploads', $totalUploads, 'info');
                $this->displayCheckResult('Pending', $pendingUploads, $pendingUploads > 0 ? 'warning' : 'ok');
                
                if ($hasStatusColumn) {
                    $this->displayCheckResult('Failed', $failedUploads, $failedUploads > 0 ? 'warning' : 'ok');
                } else {
                    $this->displayCheckResult('Failed', 'N/A (status column not available)', 'info');
                }
                
                $this->displayCheckResult('Uploaded', $uploadedUploads, 'ok');
                $this->displayCheckResult('Stuck Uploads', $stuckUploads->count(), 
                    $stuckUploads->count() > 0 ? 'warning' : 'ok');
                
                if (isset($result['details']['failure_rate'])) {
                    $this->displayCheckResult('Failure Rate', $result['details']['failure_rate'] . '%', 
                        $result['details']['failure_rate'] > 10 ? 'error' : 
                        ($result['details']['failure_rate'] > 5 ? 'warning' : 'ok'));
                }
                
                if ($hasStatusColumn) {
                    $this->displayCheckResult('Missing Files', $missingFileUploads, 
                        $missingFileUploads > 0 ? 'warning' : 'ok');
                } else {
                    $this->displayCheckResult('Missing Files', 'N/A (status column not available)', 'info');
                }
                
                $this->displayCheckResult('Recent (24h)', $recentUploads, 'info');
            }

        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['details']['error'] = $e->getMessage();
            $result['recommendations'][] = "Check upload system configuration";

            if (!$this->jsonOutput) {
                $this->displayCheckResult('Upload Status', 'Error: ' . $e->getMessage(), 'error');
            }
        }

        $this->results['upload_status'] = $result;
        if (!$this->jsonOutput) {
            $this->newLine();
        }
    }

    /**
     * Count orphaned files in storage
     */
    private function countOrphanedFiles(): int
    {
        try {
            $files = Storage::disk('public')->files('uploads');
            $dbFilenames = FileUpload::pluck('filename')->toArray();
            
            $orphanedCount = 0;
            foreach ($files as $file) {
                $filename = basename($file);
                if (!in_array($filename, $dbFilenames)) {
                    $orphanedCount++;
                }
            }
            
            return $orphanedCount;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Display a check result with appropriate formatting
     */
    private function displayCheckResult(string $check, string $value, string $status): void
    {
        $icon = match ($status) {
            'ok' => '✅',
            'warning' => '⚠️',
            'error' => '❌',
            'info' => 'ℹ️',
            default => '•'
        };

        $this->line("  {$icon} {$check}: {$value}");
    }

    /**
     * Display summary of all checks
     */
    private function displaySummary(): void
    {
        $this->info('📊 Summary');
        $this->info('----------');

        $totalChecks = count($this->results);
        $okChecks = collect($this->results)->where('status', 'ok')->count();
        $warningChecks = collect($this->results)->where('status', 'warning')->count();
        $errorChecks = collect($this->results)->where('status', 'error')->count();

        $this->line("Total Checks: {$totalChecks}");
        $this->line("✅ OK: {$okChecks}");
        $this->line("⚠️  Warnings: {$warningChecks}");
        $this->line("❌ Errors: {$errorChecks}");
        $this->newLine();
    }

    /**
     * Display recommendations for resolving issues
     */
    private function displayRecommendations(): void
    {
        $allRecommendations = collect($this->results)
            ->flatMap(fn($result) => $result['recommendations'])
            ->unique()
            ->values();

        if ($allRecommendations->isEmpty()) {
            $this->info('🎉 No issues found! System is healthy.');
            return;
        }

        $this->info('💡 Recommendations');
        $this->info('------------------');

        foreach ($allRecommendations as $index => $recommendation) {
            $this->line(($index + 1) . ". {$recommendation}");
        }

        $this->newLine();
        $this->info('For more help, run specific commands:');
        $this->line('• uploads:recover-pending - Recover stuck uploads');
        $this->line('• uploads:cleanup - Clean up orphaned files');
        $this->line('• queue:retry all - Retry failed jobs');
        $this->line('• google-drive:refresh-tokens - Refresh expired tokens');
    }
}