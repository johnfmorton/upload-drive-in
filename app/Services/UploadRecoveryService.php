<?php

namespace App\Services;

use App\Models\FileUpload;
use App\Jobs\UploadToGoogleDrive;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Exception;

/**
 * Service for handling automatic detection and recovery of stuck file uploads.
 * 
 * This service provides methods to:
 * - Detect uploads that are stuck in pending state beyond threshold
 * - Attempt recovery of individual stuck uploads
 * - Process multiple uploads efficiently in batches
 * - Analyze failure patterns for systemic issues
 */
class UploadRecoveryService
{
    /**
     * Detect uploads that are stuck in pending state beyond the configured threshold.
     * 
     * @return Collection<FileUpload> Collection of stuck upload records
     */
    public function detectStuckUploads(): Collection
    {
        Log::info('🔍 Upload Recovery: Starting stuck upload detection');
        
        $stuckUploads = FileUpload::stuck()
            ->recoverable()
            ->get();
            
        Log::info('🔍 Upload Recovery: Detected stuck uploads', [
            'count' => $stuckUploads->count(),
            'upload_ids' => $stuckUploads->pluck('id')->toArray()
        ]);
        
        return $stuckUploads;
    }

    /**
     * Attempt to recover a single stuck upload by re-queuing it for processing.
     * 
     * @param int $uploadId The ID of the upload to recover
     * @return array Recovery result with success status and details
     */
    public function attemptRecovery(int $uploadId): array
    {
        Log::info('🔍 Upload Recovery: Attempting recovery for upload', ['upload_id' => $uploadId]);
        
        try {
            $upload = FileUpload::find($uploadId);
            
            if (!$upload) {
                $error = 'Upload record not found';
                Log::warning('🔍 Upload Recovery: Upload not found', ['upload_id' => $uploadId]);
                return [
                    'success' => false,
                    'error' => $error,
                    'upload_id' => $uploadId
                ];
            }

            // Check if upload has already been processed successfully
            if (!empty($upload->google_drive_file_id)) {
                Log::info('🔍 Upload Recovery: Upload already completed', [
                    'upload_id' => $uploadId,
                    'google_drive_file_id' => $upload->google_drive_file_id
                ]);
                return [
                    'success' => true,
                    'message' => 'Upload already completed',
                    'upload_id' => $uploadId,
                    'already_completed' => true
                ];
            }

            // Check if upload has exceeded recovery attempts
            if ($upload->hasExceededRecoveryAttempts()) {
                $error = 'Upload has exceeded maximum recovery attempts';
                Log::warning('🔍 Upload Recovery: Max recovery attempts exceeded', [
                    'upload_id' => $uploadId,
                    'recovery_attempts' => $upload->recovery_attempts,
                    'max_attempts' => config('upload-recovery.max_recovery_attempts', 5)
                ]);
                return [
                    'success' => false,
                    'error' => $error,
                    'upload_id' => $uploadId,
                    'max_attempts_exceeded' => true
                ];
            }

            // Check if local file still exists
            if (!$upload->localFileExists()) {
                $error = 'Local file no longer exists';
                Log::error('🔍 Upload Recovery: Local file missing', [
                    'upload_id' => $uploadId,
                    'filename' => $upload->filename
                ]);
                
                // Update upload status to reflect missing file
                $upload->updateRecoveryStatus($error, [
                    'recovery_attempt_at' => now()->toISOString(),
                    'file_check_result' => 'missing'
                ]);
                
                return [
                    'success' => false,
                    'error' => $error,
                    'upload_id' => $uploadId,
                    'file_missing' => true
                ];
            }

            // Increment recovery attempts counter
            $upload->incrementRecoveryAttempts();

            // Re-queue the upload job
            UploadToGoogleDrive::dispatch($upload);
            
            // Update the last processed timestamp
            $upload->last_processed_at = now();
            $upload->save();

            Log::info('🔍 Upload Recovery: Successfully re-queued upload', [
                'upload_id' => $uploadId,
                'recovery_attempts' => $upload->recovery_attempts
            ]);

            return [
                'success' => true,
                'message' => 'Upload re-queued for processing',
                'upload_id' => $uploadId,
                'recovery_attempts' => $upload->recovery_attempts
            ];

        } catch (Exception $e) {
            $error = 'Recovery attempt failed: ' . $e->getMessage();
            Log::error('🔍 Upload Recovery: Recovery attempt failed', [
                'upload_id' => $uploadId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Try to update the upload record with error information
            try {
                $upload = FileUpload::find($uploadId);
                if ($upload) {
                    $upload->updateRecoveryStatus($error, [
                        'recovery_attempt_at' => now()->toISOString(),
                        'exception_class' => get_class($e),
                        'exception_message' => $e->getMessage()
                    ]);
                }
            } catch (Exception $updateException) {
                Log::error('🔍 Upload Recovery: Failed to update upload with error info', [
                    'upload_id' => $uploadId,
                    'update_error' => $updateException->getMessage()
                ]);
            }

            return [
                'success' => false,
                'error' => $error,
                'upload_id' => $uploadId,
                'exception' => true
            ];
        }
    }

    /**
     * Process multiple stuck uploads efficiently in batches.
     * 
     * @param Collection|array|null $uploadIds Optional specific upload IDs to process. If null, processes all stuck uploads.
     * @return array Bulk recovery results with summary statistics
     */
    public function bulkRecovery($uploadIds = null): array
    {
        Log::info('🔍 Upload Recovery: Starting bulk recovery', [
            'specific_ids' => $uploadIds ? (is_array($uploadIds) ? $uploadIds : $uploadIds->toArray()) : null
        ]);

        $results = [
            'total_processed' => 0,
            'successful_recoveries' => 0,
            'failed_recoveries' => 0,
            'already_completed' => 0,
            'file_missing' => 0,
            'max_attempts_exceeded' => 0,
            'details' => [],
            'started_at' => now()->toISOString(),
            'completed_at' => null
        ];

        try {
            // Get uploads to process
            if ($uploadIds) {
                // Process specific upload IDs
                $uploads = is_array($uploadIds) 
                    ? collect($uploadIds) 
                    : $uploadIds;
                    
                if ($uploads instanceof Collection && $uploads->first() instanceof FileUpload) {
                    // Already FileUpload models
                    $uploadsToProcess = $uploads;
                } else {
                    // IDs provided, fetch the models
                    $uploadsToProcess = FileUpload::whereIn('id', $uploads)->get();
                }
            } else {
                // Process all stuck uploads
                $uploadsToProcess = $this->detectStuckUploads();
            }

            $batchSize = config('upload-recovery.batch_size', 10);
            $batches = $uploadsToProcess->chunk($batchSize);

            Log::info('🔍 Upload Recovery: Processing uploads in batches', [
                'total_uploads' => $uploadsToProcess->count(),
                'batch_size' => $batchSize,
                'total_batches' => $batches->count()
            ]);

            foreach ($batches as $batchIndex => $batch) {
                Log::info('🔍 Upload Recovery: Processing batch', [
                    'batch_index' => $batchIndex + 1,
                    'batch_size' => $batch->count()
                ]);

                foreach ($batch as $upload) {
                    $uploadId = $upload instanceof FileUpload ? $upload->id : $upload;
                    $recoveryResult = $this->attemptRecovery($uploadId);
                    
                    $results['total_processed']++;
                    $results['details'][] = $recoveryResult;

                    // Update counters based on result
                    if ($recoveryResult['success']) {
                        if (isset($recoveryResult['already_completed']) && $recoveryResult['already_completed']) {
                            $results['already_completed']++;
                        } else {
                            $results['successful_recoveries']++;
                        }
                    } else {
                        $results['failed_recoveries']++;
                        
                        if (isset($recoveryResult['file_missing']) && $recoveryResult['file_missing']) {
                            $results['file_missing']++;
                        }
                        
                        if (isset($recoveryResult['max_attempts_exceeded']) && $recoveryResult['max_attempts_exceeded']) {
                            $results['max_attempts_exceeded']++;
                        }
                    }
                }

                // Small delay between batches to prevent overwhelming the system
                if ($batchIndex < $batches->count() - 1) {
                    usleep(100000); // 100ms delay
                }
            }

            $results['completed_at'] = now()->toISOString();

            Log::info('🔍 Upload Recovery: Bulk recovery completed', [
                'total_processed' => $results['total_processed'],
                'successful_recoveries' => $results['successful_recoveries'],
                'failed_recoveries' => $results['failed_recoveries'],
                'already_completed' => $results['already_completed'],
                'file_missing' => $results['file_missing'],
                'max_attempts_exceeded' => $results['max_attempts_exceeded']
            ]);

        } catch (Exception $e) {
            $results['completed_at'] = now()->toISOString();
            $results['bulk_error'] = $e->getMessage();
            
            Log::error('🔍 Upload Recovery: Bulk recovery failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'results_so_far' => $results
            ]);
        }

        return $results;
    }

    /**
     * Analyze failure patterns in upload data to identify systemic issues.
     * 
     * @param Collection|null $uploads Optional specific uploads to analyze. If null, analyzes recent failures.
     * @return array Analysis results with identified patterns and recommendations
     */
    public function analyzeFailurePattern(?Collection $uploads = null): array
    {
        Log::info('🔍 Upload Recovery: Starting failure pattern analysis');

        if (!$uploads) {
            // Analyze recent failures (last 24 hours)
            $uploads = FileUpload::where('created_at', '>=', now()->subDay())
                ->where(function ($query) {
                    $query->whereNotNull('last_error')
                          ->orWhere('retry_count', '>', 0);
                })
                ->get();
        }

        $analysis = [
            'total_uploads_analyzed' => $uploads->count(),
            'analysis_period' => [
                'start' => $uploads->min('created_at'),
                'end' => $uploads->max('created_at')
            ],
            'error_patterns' => [],
            'failure_rate' => 0,
            'common_issues' => [],
            'recommendations' => [],
            'analyzed_at' => now()->toISOString()
        ];

        if ($uploads->isEmpty()) {
            Log::info('🔍 Upload Recovery: No uploads to analyze');
            return $analysis;
        }

        // Calculate failure rate
        $totalUploads = FileUpload::where('created_at', '>=', $uploads->min('created_at'))
            ->where('created_at', '<=', $uploads->max('created_at'))
            ->count();
            
        $failedUploads = $uploads->where('last_error', '!=', null)->count();
        $analysis['failure_rate'] = $totalUploads > 0 ? round($failedUploads / $totalUploads, 3) : 0;

        // Analyze error patterns
        $errorGroups = $uploads->whereNotNull('last_error')
            ->groupBy('last_error')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'upload_ids' => $group->pluck('id')->toArray(),
                    'first_occurrence' => $group->min('created_at'),
                    'last_occurrence' => $group->max('updated_at')
                ];
            })
            ->sortByDesc('count');

        $analysis['error_patterns'] = $errorGroups->toArray();

        // Identify common issues and provide recommendations
        $commonIssues = [];
        $recommendations = [];

        foreach ($errorGroups as $error => $data) {
            if ($data['count'] >= 3) { // Consider it common if it occurs 3+ times
                $commonIssues[] = [
                    'error' => $error,
                    'frequency' => $data['count'],
                    'percentage' => round(($data['count'] / $uploads->count()) * 100, 1)
                ];

                // Generate recommendations based on error patterns
                if (str_contains(strtolower($error), 'token')) {
                    $recommendations[] = 'Check Google Drive token validity and refresh mechanism';
                } elseif (str_contains(strtolower($error), 'quota') || str_contains(strtolower($error), 'limit')) {
                    $recommendations[] = 'Review Google Drive API quota usage and implement rate limiting';
                } elseif (str_contains(strtolower($error), 'file') && str_contains(strtolower($error), 'not found')) {
                    $recommendations[] = 'Investigate local file cleanup timing and storage issues';
                } elseif (str_contains(strtolower($error), 'network') || str_contains(strtolower($error), 'timeout')) {
                    $recommendations[] = 'Check network connectivity and implement retry with exponential backoff';
                }
            }
        }

        // Check for stuck uploads pattern
        $stuckCount = $uploads->filter(function ($upload) {
            return $upload->isStuck();
        })->count();

        if ($stuckCount > 0) {
            $commonIssues[] = [
                'error' => 'Uploads stuck in pending state',
                'frequency' => $stuckCount,
                'percentage' => round(($stuckCount / $uploads->count()) * 100, 1)
            ];
            $recommendations[] = 'Review queue worker performance and processing capacity';
        }

        // Check for high retry patterns
        $highRetryUploads = $uploads->where('retry_count', '>=', 2)->count();
        if ($highRetryUploads > 0) {
            $commonIssues[] = [
                'error' => 'High retry count uploads',
                'frequency' => $highRetryUploads,
                'percentage' => round(($highRetryUploads / $uploads->count()) * 100, 1)
            ];
            $recommendations[] = 'Investigate root causes of upload failures to reduce retry necessity';
        }

        $analysis['common_issues'] = $commonIssues;
        $analysis['recommendations'] = array_unique($recommendations);

        // Add system health recommendations based on failure rate
        if ($analysis['failure_rate'] > 0.1) { // > 10% failure rate
            $analysis['recommendations'][] = 'High failure rate detected - perform comprehensive system health check';
        }

        Log::info('🔍 Upload Recovery: Failure pattern analysis completed', [
            'total_analyzed' => $analysis['total_uploads_analyzed'],
            'failure_rate' => $analysis['failure_rate'],
            'common_issues_count' => count($analysis['common_issues']),
            'recommendations_count' => count($analysis['recommendations'])
        ]);

        return $analysis;
    }

    /**
     * Get summary statistics for upload recovery system.
     * 
     * @return array Summary statistics including counts by status and recent activity
     */
    public function getRecoveryStatistics(): array
    {
        Log::info('🔍 Upload Recovery: Generating recovery statistics');

        $stats = [
            'counts' => [
                'total_uploads' => FileUpload::count(),
                'pending_uploads' => FileUpload::pending()->count(),
                'stuck_uploads' => FileUpload::stuck()->count(),
                'failed_uploads' => FileUpload::failed()->count(),
                'completed_uploads' => FileUpload::completed()->count(),
                'recoverable_uploads' => FileUpload::recoverable()->count()
            ],
            'recent_activity' => [
                'uploads_last_24h' => FileUpload::where('created_at', '>=', now()->subDay())->count(),
                'recoveries_last_24h' => FileUpload::where('recovery_attempts', '>', 0)
                    ->where('updated_at', '>=', now()->subDay())->count(),
                'failures_last_24h' => FileUpload::whereNotNull('last_error')
                    ->where('updated_at', '>=', now()->subDay())->count()
            ],
            'system_health' => [
                'failure_rate_24h' => 0,
                'average_processing_time' => null,
                'queue_depth' => $this->getQueueDepth()
            ],
            'generated_at' => now()->toISOString()
        ];

        // Calculate failure rate for last 24 hours
        $recentTotal = $stats['recent_activity']['uploads_last_24h'];
        $recentFailures = $stats['recent_activity']['failures_last_24h'];
        $stats['system_health']['failure_rate_24h'] = $recentTotal > 0 
            ? round($recentFailures / $recentTotal, 3) 
            : 0;

        Log::info('🔍 Upload Recovery: Statistics generated', $stats['counts']);

        return $stats;
    }

    /**
     * Get the current queue depth for upload jobs.
     * 
     * @return int Number of pending upload jobs in queue
     */
    private function getQueueDepth(): int
    {
        try {
            // This is a simplified implementation - in production you might want to
            // query the actual queue system (Redis, database, etc.) for more accurate counts
            return FileUpload::pending()->count();
        } catch (Exception $e) {
            Log::warning('🔍 Upload Recovery: Failed to get queue depth', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}