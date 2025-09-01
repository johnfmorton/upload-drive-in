<?php

namespace App\Services;

use App\Models\FileUpload;
use App\Models\User;
use App\Models\GoogleDriveToken;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Exception;

/**
 * Service for comprehensive system health monitoring and upload failure analysis.
 * 
 * This service provides methods to:
 * - Perform comprehensive system health checks
 * - Analyze detailed upload failure patterns
 * - Validate Google Drive connectivity and token status
 * - Monitor system resources and queue performance
 */
class UploadDiagnosticService
{
    private GoogleDriveService $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Perform comprehensive system health check including queue worker, disk space, and API connectivity.
     * 
     * @return array Comprehensive health check results with status and recommendations
     */
    public function performHealthCheck(): array
    {
        Log::info('🔍 Upload Diagnostic: Starting comprehensive system health check');

        $healthCheck = [
            'overall_status' => 'healthy',
            'checks' => [],
            'warnings' => [],
            'errors' => [],
            'recommendations' => [],
            'checked_at' => now()->toISOString(),
            'summary' => [
                'total_checks' => 0,
                'passed_checks' => 0,
                'warning_checks' => 0,
                'failed_checks' => 0
            ]
        ];

        // 1. Queue Worker Health Check
        $queueCheck = $this->checkQueueWorkerHealth();
        $healthCheck['checks']['queue_worker'] = $queueCheck;
        $this->updateHealthSummary($healthCheck, $queueCheck);

        // 2. Disk Space Check
        $diskCheck = $this->checkDiskSpace();
        $healthCheck['checks']['disk_space'] = $diskCheck;
        $this->updateHealthSummary($healthCheck, $diskCheck);

        // 3. Database Connectivity Check
        $databaseCheck = $this->checkDatabaseConnectivity();
        $healthCheck['checks']['database'] = $databaseCheck;
        $this->updateHealthSummary($healthCheck, $databaseCheck);

        // 4. Google Drive API Connectivity Check
        $apiCheck = $this->validateGoogleDriveConnectivity();
        $healthCheck['checks']['google_drive_api'] = $apiCheck;
        $this->updateHealthSummary($healthCheck, $apiCheck);

        // 5. Storage Permissions Check
        $storageCheck = $this->checkStoragePermissions();
        $healthCheck['checks']['storage_permissions'] = $storageCheck;
        $this->updateHealthSummary($healthCheck, $storageCheck);

        // 6. Upload Queue Depth Check
        $queueDepthCheck = $this->checkUploadQueueDepth();
        $healthCheck['checks']['queue_depth'] = $queueDepthCheck;
        $this->updateHealthSummary($healthCheck, $queueDepthCheck);

        // 7. Recent Upload Performance Check
        $performanceCheck = $this->checkRecentUploadPerformance();
        $healthCheck['checks']['upload_performance'] = $performanceCheck;
        $this->updateHealthSummary($healthCheck, $performanceCheck);

        // Determine overall status
        if ($healthCheck['summary']['failed_checks'] > 0) {
            $healthCheck['overall_status'] = 'critical';
        } elseif ($healthCheck['summary']['warning_checks'] > 0) {
            $healthCheck['overall_status'] = 'warning';
        }

        // Collect warnings and errors
        foreach ($healthCheck['checks'] as $checkName => $check) {
            if ($check['status'] === 'warning' && !empty($check['message'])) {
                $healthCheck['warnings'][] = "{$checkName}: {$check['message']}";
            }
            if ($check['status'] === 'error' && !empty($check['message'])) {
                $healthCheck['errors'][] = "{$checkName}: {$check['message']}";
            }
            if (!empty($check['recommendations'])) {
                $healthCheck['recommendations'] = array_merge(
                    $healthCheck['recommendations'], 
                    $check['recommendations']
                );
            }
        }

        // Remove duplicate recommendations
        $healthCheck['recommendations'] = array_unique($healthCheck['recommendations']);

        Log::info('🔍 Upload Diagnostic: Health check completed', [
            'overall_status' => $healthCheck['overall_status'],
            'total_checks' => $healthCheck['summary']['total_checks'],
            'passed' => $healthCheck['summary']['passed_checks'],
            'warnings' => $healthCheck['summary']['warning_checks'],
            'errors' => $healthCheck['summary']['failed_checks']
        ]);

        return $healthCheck;
    }

    /**
     * Analyze detailed upload failure patterns and provide specific recommendations.
     * 
     * @param int $uploadId The specific upload ID to analyze
     * @return array Detailed failure analysis with root cause and recommendations
     */
    public function analyzeUploadFailure(int $uploadId): array
    {
        Log::info('🔍 Upload Diagnostic: Starting detailed failure analysis', ['upload_id' => $uploadId]);

        $analysis = [
            'upload_id' => $uploadId,
            'analysis_status' => 'completed',
            'upload_details' => null,
            'failure_classification' => null,
            'root_cause_analysis' => [],
            'system_context' => [],
            'recommendations' => [],
            'related_issues' => [],
            'analyzed_at' => now()->toISOString()
        ];

        try {
            // Get upload details
            $upload = FileUpload::find($uploadId);
            if (!$upload) {
                $analysis['analysis_status'] = 'failed';
                $analysis['error'] = 'Upload record not found';
                Log::warning('🔍 Upload Diagnostic: Upload not found for analysis', ['upload_id' => $uploadId]);
                return $analysis;
            }

            $analysis['upload_details'] = [
                'id' => $upload->id,
                'filename' => $upload->filename,
                'status' => $upload->getUploadStatus(),
                'created_at' => $upload->created_at->toISOString(),
                'updated_at' => $upload->updated_at->toISOString(),
                'retry_count' => $upload->retry_count,
                'recovery_attempts' => $upload->recovery_attempts,
                'last_error' => $upload->last_error,
                'last_processed_at' => $upload->last_processed_at?->toISOString(),
                'google_drive_file_id' => $upload->google_drive_file_id,
                'file_size' => $upload->file_size,
                'mime_type' => $upload->mime_type,
                'client_email' => $upload->email
            ];

            // Classify the failure type
            $analysis['failure_classification'] = $this->classifyUploadFailure($upload);

            // Perform root cause analysis
            $analysis['root_cause_analysis'] = $this->performRootCauseAnalysis($upload);

            // Get system context at time of failure
            $analysis['system_context'] = $this->getSystemContextForUpload($upload);

            // Generate specific recommendations
            $analysis['recommendations'] = $this->generateFailureRecommendations($upload, $analysis);

            // Find related issues
            $analysis['related_issues'] = $this->findRelatedIssues($upload);

            Log::info('🔍 Upload Diagnostic: Failure analysis completed', [
                'upload_id' => $uploadId,
                'classification' => $analysis['failure_classification']['type'],
                'root_causes' => count($analysis['root_cause_analysis']),
                'recommendations' => count($analysis['recommendations'])
            ]);

        } catch (Exception $e) {
            $analysis['analysis_status'] = 'failed';
            $analysis['error'] = $e->getMessage();
            Log::error('🔍 Upload Diagnostic: Failure analysis failed', [
                'upload_id' => $uploadId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $analysis;
    }

    /**
     * Validate Google Drive API connectivity and token status for all users.
     * 
     * @return array Google Drive connectivity status and token validation results
     */
    public function validateGoogleDriveConnectivity(): array
    {
        Log::info('🔍 Upload Diagnostic: Starting Google Drive connectivity validation');

        $validation = [
            'status' => 'healthy',
            'message' => 'Google Drive connectivity is healthy',
            'api_status' => null,
            'token_validation' => [],
            'connectivity_test' => null,
            'recommendations' => [],
            'checked_at' => now()->toISOString(),
            'summary' => [
                'total_tokens' => 0,
                'valid_tokens' => 0,
                'expired_tokens' => 0,
                'invalid_tokens' => 0,
                'refresh_needed' => 0
            ]
        ];

        try {
            // Test basic API connectivity
            $validation['api_status'] = $this->testGoogleDriveApiConnectivity();

            // Validate all user tokens
            $tokens = GoogleDriveToken::with('user')->get();
            $validation['summary']['total_tokens'] = $tokens->count();

            if ($tokens->isEmpty()) {
                $validation['status'] = 'warning';
                $validation['message'] = 'No Google Drive tokens found - no users have connected their accounts';
                $validation['recommendations'][] = 'At least one admin user should connect their Google Drive account';
                Log::warning('🔍 Upload Diagnostic: No Google Drive tokens found');
                return $validation;
            }

            foreach ($tokens as $token) {
                $tokenValidation = $this->validateUserToken($token);
                $validation['token_validation'][] = $tokenValidation;

                // Update summary counters
                switch ($tokenValidation['status']) {
                    case 'valid':
                        $validation['summary']['valid_tokens']++;
                        break;
                    case 'expired':
                        $validation['summary']['expired_tokens']++;
                        if ($tokenValidation['can_refresh']) {
                            $validation['summary']['refresh_needed']++;
                        }
                        break;
                    case 'invalid':
                        $validation['summary']['invalid_tokens']++;
                        break;
                }
            }

            // Test actual connectivity with a valid token
            $validation['connectivity_test'] = $this->testActualGoogleDriveConnectivity();

            // Determine overall status and recommendations
            if ($validation['summary']['invalid_tokens'] > 0) {
                $validation['status'] = 'error';
                $validation['message'] = 'Some Google Drive tokens are invalid and need re-authorization';
                $validation['recommendations'][] = 'Users with invalid tokens need to reconnect their Google Drive accounts';
            } elseif ($validation['summary']['expired_tokens'] > 0) {
                $validation['status'] = 'warning';
                $validation['message'] = 'Some Google Drive tokens are expired but can be refreshed';
                $validation['recommendations'][] = 'Expired tokens will be automatically refreshed on next use';
            }

            // Check if we have at least one working admin token
            $adminTokens = $tokens->filter(function ($token) {
                return $token->user && $token->user->role === \App\Enums\UserRole::ADMIN;
            });

            if ($adminTokens->isEmpty()) {
                $validation['status'] = 'warning';
                $validation['recommendations'][] = 'At least one admin user should have a connected Google Drive account for fallback uploads';
            }

            Log::info('🔍 Upload Diagnostic: Google Drive connectivity validation completed', [
                'status' => $validation['status'],
                'total_tokens' => $validation['summary']['total_tokens'],
                'valid_tokens' => $validation['summary']['valid_tokens'],
                'expired_tokens' => $validation['summary']['expired_tokens'],
                'invalid_tokens' => $validation['summary']['invalid_tokens']
            ]);

        } catch (Exception $e) {
            $validation['status'] = 'error';
            $validation['message'] = 'Google Drive connectivity validation failed: ' . $e->getMessage();
            $validation['error'] = $e->getMessage();
            $validation['recommendations'][] = 'Check Google Drive API configuration and network connectivity';
            
            Log::error('🔍 Upload Diagnostic: Google Drive connectivity validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $validation;
    }

    /**
     * Check queue worker health and responsiveness.
     */
    private function checkQueueWorkerHealth(): array
    {
        $check = [
            'status' => 'healthy',
            'message' => 'Queue worker is responsive',
            'details' => [],
            'recommendations' => []
        ];

        try {
            // Check if there are jobs in the queue
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            $check['details'] = [
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'queue_connection' => config('queue.default')
            ];

            // Check for excessive failed jobs
            if ($failedJobs > 10) {
                $check['status'] = 'warning';
                $check['message'] = "High number of failed jobs detected ({$failedJobs})";
                $check['recommendations'][] = 'Review failed jobs and restart queue worker if necessary';
            }

            // Check for excessive pending jobs (might indicate worker issues)
            if ($pendingJobs > 50) {
                $check['status'] = 'warning';
                $check['message'] = "High number of pending jobs ({$pendingJobs}) - queue worker may be slow or stopped";
                $check['recommendations'][] = 'Check queue worker status and consider scaling up workers';
            }

        } catch (Exception $e) {
            $check['status'] = 'error';
            $check['message'] = 'Failed to check queue worker health: ' . $e->getMessage();
            $check['recommendations'][] = 'Verify database connectivity and queue configuration';
        }

        return $check;
    }

    /**
     * Check available disk space for upload storage.
     */
    private function checkDiskSpace(): array
    {
        $check = [
            'status' => 'healthy',
            'message' => 'Sufficient disk space available',
            'details' => [],
            'recommendations' => []
        ];

        try {
            $storagePath = storage_path();
            $freeBytes = disk_free_space($storagePath);
            $totalBytes = disk_total_space($storagePath);
            $usedBytes = $totalBytes - $freeBytes;
            $usagePercentage = ($usedBytes / $totalBytes) * 100;

            $check['details'] = [
                'storage_path' => $storagePath,
                'free_space' => format_bytes($freeBytes),
                'total_space' => format_bytes($totalBytes),
                'used_space' => format_bytes($usedBytes),
                'usage_percentage' => round($usagePercentage, 2)
            ];

            // Check disk usage thresholds
            if ($usagePercentage > 90) {
                $check['status'] = 'error';
                $check['message'] = 'Critical: Disk space usage is above 90%';
                $check['recommendations'][] = 'Immediately free up disk space or uploads will fail';
            } elseif ($usagePercentage > 80) {
                $check['status'] = 'warning';
                $check['message'] = 'Warning: Disk space usage is above 80%';
                $check['recommendations'][] = 'Monitor disk usage and plan for cleanup or expansion';
            }

            // Check if we have at least 1GB free for uploads
            if ($freeBytes < 1024 * 1024 * 1024) { // 1GB
                $check['status'] = 'warning';
                $check['message'] = 'Low free disk space (less than 1GB available)';
                $check['recommendations'][] = 'Ensure adequate free space for file uploads';
            }

        } catch (Exception $e) {
            $check['status'] = 'error';
            $check['message'] = 'Failed to check disk space: ' . $e->getMessage();
            $check['recommendations'][] = 'Verify storage path permissions and system access';
        }

        return $check;
    }

    /**
     * Check database connectivity and performance.
     */
    private function checkDatabaseConnectivity(): array
    {
        $check = [
            'status' => 'healthy',
            'message' => 'Database connectivity is healthy',
            'details' => [],
            'recommendations' => []
        ];

        try {
            $startTime = microtime(true);
            
            // Test basic connectivity
            $result = DB::select('SELECT 1 as test');
            
            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

            // Test upload-related tables
            $uploadCount = FileUpload::count();
            $tokenCount = GoogleDriveToken::count();

            $check['details'] = [
                'connection' => config('database.default'),
                'response_time_ms' => round($responseTime, 2),
                'upload_records' => $uploadCount,
                'token_records' => $tokenCount
            ];

            // Check response time
            if ($responseTime > 1000) { // > 1 second
                $check['status'] = 'warning';
                $check['message'] = 'Database response time is slow (> 1 second)';
                $check['recommendations'][] = 'Check database performance and optimize queries';
            } elseif ($responseTime > 500) { // > 500ms
                $check['status'] = 'warning';
                $check['message'] = 'Database response time is elevated (> 500ms)';
                $check['recommendations'][] = 'Monitor database performance';
            }

        } catch (Exception $e) {
            $check['status'] = 'error';
            $check['message'] = 'Database connectivity failed: ' . $e->getMessage();
            $check['recommendations'][] = 'Check database configuration and server status';
        }

        return $check;
    }

    /**
     * Check storage permissions for upload directories.
     */
    private function checkStoragePermissions(): array
    {
        $check = [
            'status' => 'healthy',
            'message' => 'Storage permissions are correct',
            'details' => [],
            'recommendations' => []
        ];

        try {
            $storagePaths = [
                'public' => storage_path('app/public'),
                'uploads' => storage_path('app/public/uploads'),
                'temp' => storage_path('app/temp'),
                'logs' => storage_path('logs')
            ];

            $permissionIssues = [];

            foreach ($storagePaths as $name => $path) {
                $readable = is_readable($path);
                $writable = is_writable($path);
                $exists = file_exists($path);

                $check['details'][$name] = [
                    'path' => $path,
                    'exists' => $exists,
                    'readable' => $readable,
                    'writable' => $writable
                ];

                if (!$exists) {
                    $permissionIssues[] = "{$name} directory does not exist: {$path}";
                } elseif (!$readable || !$writable) {
                    $permissionIssues[] = "{$name} directory has permission issues: {$path}";
                }
            }

            if (!empty($permissionIssues)) {
                $check['status'] = 'error';
                $check['message'] = 'Storage permission issues detected';
                $check['details']['issues'] = $permissionIssues;
                $check['recommendations'][] = 'Fix directory permissions and ensure all storage paths exist';
            }

        } catch (Exception $e) {
            $check['status'] = 'error';
            $check['message'] = 'Failed to check storage permissions: ' . $e->getMessage();
            $check['recommendations'][] = 'Verify storage configuration and file system access';
        }

        return $check;
    }

    /**
     * Check upload queue depth and processing rate.
     */
    private function checkUploadQueueDepth(): array
    {
        $check = [
            'status' => 'healthy',
            'message' => 'Upload queue depth is normal',
            'details' => [],
            'recommendations' => []
        ];

        try {
            $pendingUploads = FileUpload::pending()->count();
            $stuckUploads = FileUpload::stuck()->count();
            $processingUploads = FileUpload::where('status', 'processing')->count();

            // Calculate recent processing rate (last hour)
            $recentUploads = FileUpload::where('created_at', '>=', now()->subHour())->count();
            $recentCompleted = FileUpload::where('updated_at', '>=', now()->subHour())
                ->whereNotNull('google_drive_file_id')
                ->count();

            $check['details'] = [
                'pending_uploads' => $pendingUploads,
                'stuck_uploads' => $stuckUploads,
                'processing_uploads' => $processingUploads,
                'recent_uploads_1h' => $recentUploads,
                'recent_completed_1h' => $recentCompleted,
                'processing_rate' => $recentUploads > 0 ? round(($recentCompleted / $recentUploads) * 100, 1) : 0
            ];

            // Check for concerning queue depths
            if ($stuckUploads > 5) {
                $check['status'] = 'error';
                $check['message'] = "High number of stuck uploads detected ({$stuckUploads})";
                $check['recommendations'][] = 'Run upload recovery process to clear stuck uploads';
            } elseif ($pendingUploads > 20) {
                $check['status'] = 'warning';
                $check['message'] = "High number of pending uploads ({$pendingUploads})";
                $check['recommendations'][] = 'Monitor queue processing and consider scaling workers';
            }

            // Check processing rate
            if ($recentUploads > 0 && ($recentCompleted / $recentUploads) < 0.8) {
                $check['status'] = 'warning';
                $check['message'] = 'Upload success rate is below 80% in the last hour';
                $check['recommendations'][] = 'Investigate upload failures and system performance';
            }

        } catch (Exception $e) {
            $check['status'] = 'error';
            $check['message'] = 'Failed to check upload queue depth: ' . $e->getMessage();
            $check['recommendations'][] = 'Verify database connectivity and upload system status';
        }

        return $check;
    }

    /**
     * Check recent upload performance metrics.
     */
    private function checkRecentUploadPerformance(): array
    {
        $check = [
            'status' => 'healthy',
            'message' => 'Upload performance is normal',
            'details' => [],
            'recommendations' => []
        ];

        try {
            // Analyze last 24 hours of uploads
            $recentUploads = FileUpload::where('created_at', '>=', now()->subDay())->get();
            
            if ($recentUploads->isEmpty()) {
                $check['message'] = 'No recent uploads to analyze';
                return $check;
            }

            $totalUploads = $recentUploads->count();
            $completedUploads = $recentUploads->whereNotNull('google_drive_file_id')->count();
            $failedUploads = $recentUploads->whereNotNull('last_error')->count();
            $stuckUploads = $recentUploads->filter(function ($upload) {
                return $upload->isStuck();
            })->count();

            $successRate = $totalUploads > 0 ? ($completedUploads / $totalUploads) * 100 : 0;
            $failureRate = $totalUploads > 0 ? ($failedUploads / $totalUploads) * 100 : 0;

            // Calculate average processing time for completed uploads
            $completedWithTimes = $recentUploads->filter(function ($upload) {
                return $upload->google_drive_file_id && $upload->last_processed_at;
            });

            $avgProcessingTime = null;
            if ($completedWithTimes->count() > 0) {
                $totalProcessingTime = $completedWithTimes->sum(function ($upload) {
                    return $upload->created_at->diffInMinutes($upload->last_processed_at);
                });
                $avgProcessingTime = $totalProcessingTime / $completedWithTimes->count();
            }

            $check['details'] = [
                'analysis_period' => '24 hours',
                'total_uploads' => $totalUploads,
                'completed_uploads' => $completedUploads,
                'failed_uploads' => $failedUploads,
                'stuck_uploads' => $stuckUploads,
                'success_rate' => round($successRate, 1),
                'failure_rate' => round($failureRate, 1),
                'avg_processing_time_minutes' => $avgProcessingTime ? round($avgProcessingTime, 1) : null
            ];

            // Evaluate performance
            if ($successRate < 70) {
                $check['status'] = 'error';
                $check['message'] = 'Upload success rate is critically low (< 70%)';
                $check['recommendations'][] = 'Immediate investigation required - check system health and Google Drive connectivity';
            } elseif ($successRate < 85) {
                $check['status'] = 'warning';
                $check['message'] = 'Upload success rate is below normal (< 85%)';
                $check['recommendations'][] = 'Monitor upload failures and check for systemic issues';
            }

            if ($avgProcessingTime && $avgProcessingTime > 10) {
                $check['status'] = 'warning';
                $check['message'] = 'Average upload processing time is high (> 10 minutes)';
                $check['recommendations'][] = 'Check queue worker performance and Google Drive API response times';
            }

        } catch (Exception $e) {
            $check['status'] = 'error';
            $check['message'] = 'Failed to check upload performance: ' . $e->getMessage();
            $check['recommendations'][] = 'Verify upload system status and database connectivity';
        }

        return $check;
    }

    /**
     * Test basic Google Drive API connectivity.
     */
    private function testGoogleDriveApiConnectivity(): array
    {
        $test = [
            'status' => 'healthy',
            'message' => 'Google Drive API is accessible',
            'response_time_ms' => null,
            'api_version' => null
        ];

        try {
            $startTime = microtime(true);
            
            // Create a basic client to test API accessibility
            $client = new \Google\Client();
            $client->setClientId(config('cloud-storage.providers.google-drive.client_id'));
            $client->setClientSecret(config('cloud-storage.providers.google-drive.client_secret'));
            
            // Test if we can create a Drive service (this validates API configuration)
            $service = new \Google\Service\Drive($client);
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            $test['response_time_ms'] = round($responseTime, 2);
            $test['api_version'] = 'v3'; // Google Drive API v3

            if ($responseTime > 5000) { // > 5 seconds
                $test['status'] = 'warning';
                $test['message'] = 'Google Drive API response time is slow';
            }

        } catch (Exception $e) {
            $test['status'] = 'error';
            $test['message'] = 'Google Drive API connectivity failed: ' . $e->getMessage();
        }

        return $test;
    }

    /**
     * Validate a specific user's Google Drive token.
     */
    private function validateUserToken(GoogleDriveToken $token): array
    {
        $validation = [
            'user_id' => $token->user_id,
            'user_email' => $token->user->email ?? 'Unknown',
            'user_role' => $token->user->role->value ?? 'Unknown',
            'status' => 'valid',
            'expires_at' => $token->expires_at?->toISOString(),
            'can_refresh' => !empty($token->refresh_token),
            'issues' => []
        ];

        try {
            // Check if token is expired
            if ($token->expires_at && $token->expires_at->isPast()) {
                $validation['status'] = 'expired';
                $validation['issues'][] = 'Access token has expired';
                
                if (!$token->refresh_token) {
                    $validation['status'] = 'invalid';
                    $validation['issues'][] = 'No refresh token available for renewal';
                }
            }

            // Check if required fields are present
            if (empty($token->access_token)) {
                $validation['status'] = 'invalid';
                $validation['issues'][] = 'Missing access token';
            }

        } catch (Exception $e) {
            $validation['status'] = 'invalid';
            $validation['issues'][] = 'Token validation failed: ' . $e->getMessage();
        }

        return $validation;
    }

    /**
     * Test actual Google Drive connectivity with a valid token.
     */
    private function testActualGoogleDriveConnectivity(): array
    {
        $test = [
            'status' => 'healthy',
            'message' => 'Google Drive connectivity test successful',
            'tested_with' => null,
            'test_details' => []
        ];

        try {
            // Find a user with a valid token to test with
            $validToken = GoogleDriveToken::with('user')
                ->where('expires_at', '>', now())
                ->orWhereNull('expires_at')
                ->first();

            if (!$validToken) {
                $test['status'] = 'warning';
                $test['message'] = 'No valid tokens available for connectivity test';
                return $test;
            }

            $test['tested_with'] = [
                'user_id' => $validToken->user_id,
                'user_email' => $validToken->user->email,
                'user_role' => $validToken->user->role->value
            ];

            // Test actual API call
            $startTime = microtime(true);
            $service = $this->googleDriveService->getDriveService($validToken->user);
            
            // Make a simple API call to test connectivity
            $about = $service->about->get(['fields' => 'user,storageQuota']);
            
            $responseTime = (microtime(true) - $startTime) * 1000;

            $test['test_details'] = [
                'response_time_ms' => round($responseTime, 2),
                'user_email' => $about->getUser()->getEmailAddress(),
                'storage_quota' => [
                    'limit' => $about->getStorageQuota()->getLimit(),
                    'usage' => $about->getStorageQuota()->getUsage()
                ]
            ];

            if ($responseTime > 10000) { // > 10 seconds
                $test['status'] = 'warning';
                $test['message'] = 'Google Drive API response time is very slow';
            }

        } catch (Exception $e) {
            $test['status'] = 'error';
            $test['message'] = 'Google Drive connectivity test failed: ' . $e->getMessage();
        }

        return $test;
    }

    /**
     * Update health check summary counters.
     */
    private function updateHealthSummary(array &$healthCheck, array $check): void
    {
        $healthCheck['summary']['total_checks']++;
        
        switch ($check['status']) {
            case 'healthy':
                $healthCheck['summary']['passed_checks']++;
                break;
            case 'warning':
                $healthCheck['summary']['warning_checks']++;
                break;
            case 'error':
                $healthCheck['summary']['failed_checks']++;
                break;
        }
    }

    /**
     * Classify the type of upload failure.
     */
    private function classifyUploadFailure(FileUpload $upload): array
    {
        $classification = [
            'type' => 'unknown',
            'category' => 'system',
            'severity' => 'medium',
            'description' => 'Unable to classify failure type'
        ];

        $error = strtolower($upload->last_error ?? '');

        // Token-related failures
        if (str_contains($error, 'token') || str_contains($error, 'unauthorized') || str_contains($error, 'authentication')) {
            $classification = [
                'type' => 'authentication_failure',
                'category' => 'google_drive',
                'severity' => 'high',
                'description' => 'Google Drive authentication or token issue'
            ];
        }
        // API quota/rate limit failures
        elseif (str_contains($error, 'quota') || str_contains($error, 'rate limit') || str_contains($error, 'too many requests')) {
            $classification = [
                'type' => 'api_limit_exceeded',
                'category' => 'google_drive',
                'severity' => 'medium',
                'description' => 'Google Drive API quota or rate limit exceeded'
            ];
        }
        // File system failures
        elseif (str_contains($error, 'file not found') || str_contains($error, 'no such file')) {
            $classification = [
                'type' => 'file_missing',
                'category' => 'file_system',
                'severity' => 'high',
                'description' => 'Local file missing or inaccessible'
            ];
        }
        // Network failures
        elseif (str_contains($error, 'network') || str_contains($error, 'timeout') || str_contains($error, 'connection')) {
            $classification = [
                'type' => 'network_failure',
                'category' => 'connectivity',
                'severity' => 'medium',
                'description' => 'Network connectivity or timeout issue'
            ];
        }
        // Permission failures
        elseif (str_contains($error, 'permission') || str_contains($error, 'forbidden') || str_contains($error, 'access denied')) {
            $classification = [
                'type' => 'permission_denied',
                'category' => 'google_drive',
                'severity' => 'high',
                'description' => 'Google Drive permission or access issue'
            ];
        }
        // Stuck uploads (no error but old)
        elseif (empty($upload->last_error) && $upload->isStuck()) {
            $classification = [
                'type' => 'stuck_processing',
                'category' => 'queue',
                'severity' => 'medium',
                'description' => 'Upload stuck in processing queue'
            ];
        }

        return $classification;
    }

    /**
     * Perform root cause analysis for an upload failure.
     */
    private function performRootCauseAnalysis(FileUpload $upload): array
    {
        $rootCauses = [];

        // Check if local file still exists
        if (!$upload->localFileExists()) {
            $rootCauses[] = [
                'cause' => 'Local file missing',
                'description' => 'The original uploaded file is no longer available in local storage',
                'impact' => 'high',
                'actionable' => true
            ];
        }

        // Check user's Google Drive token status
        if ($upload->uploaded_by_user_id) {
            $user = User::find($upload->uploaded_by_user_id);
            if ($user && !$user->hasGoogleDriveConnected()) {
                $rootCauses[] = [
                    'cause' => 'User Google Drive not connected',
                    'description' => 'The user who uploaded the file does not have Google Drive connected',
                    'impact' => 'high',
                    'actionable' => true
                ];
            }
        }

        // Check for pattern of similar failures
        $similarFailures = FileUpload::where('last_error', $upload->last_error)
            ->where('id', '!=', $upload->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($similarFailures > 2) {
            $rootCauses[] = [
                'cause' => 'Systemic issue pattern',
                'description' => "Similar error occurred {$similarFailures} times in the last 24 hours",
                'impact' => 'medium',
                'actionable' => true
            ];
        }

        // Check retry pattern
        if ($upload->retry_count > 2) {
            $rootCauses[] = [
                'cause' => 'Persistent failure',
                'description' => "Upload has failed {$upload->retry_count} times, indicating a persistent issue",
                'impact' => 'medium',
                'actionable' => true
            ];
        }

        return $rootCauses;
    }

    /**
     * Get system context at the time of upload failure.
     */
    private function getSystemContextForUpload(FileUpload $upload): array
    {
        $context = [
            'upload_timing' => [
                'created_at' => $upload->created_at->toISOString(),
                'last_processed_at' => $upload->last_processed_at?->toISOString(),
                'time_since_creation' => $upload->created_at->diffForHumans(),
                'processing_duration' => $upload->last_processed_at 
                    ? $upload->created_at->diffInMinutes($upload->last_processed_at) . ' minutes'
                    : null
            ],
            'concurrent_uploads' => [],
            'system_load' => []
        ];

        // Check for concurrent uploads around the same time
        $concurrentUploads = FileUpload::where('created_at', '>=', $upload->created_at->subMinutes(5))
            ->where('created_at', '<=', $upload->created_at->addMinutes(5))
            ->where('id', '!=', $upload->id)
            ->count();

        $context['concurrent_uploads'] = [
            'count' => $concurrentUploads,
            'time_window' => '±5 minutes'
        ];

        // Get system load indicators from around that time
        $systemLoadUploads = FileUpload::where('created_at', '>=', $upload->created_at->subHour())
            ->where('created_at', '<=', $upload->created_at->addHour())
            ->count();

        $context['system_load'] = [
            'uploads_in_hour' => $systemLoadUploads,
            'time_window' => '±1 hour'
        ];

        return $context;
    }

    /**
     * Generate specific recommendations based on failure analysis.
     */
    private function generateFailureRecommendations(FileUpload $upload, array $analysis): array
    {
        $recommendations = [];

        $classification = $analysis['failure_classification'];

        switch ($classification['type']) {
            case 'authentication_failure':
                $recommendations[] = 'Check and refresh Google Drive token for the user';
                $recommendations[] = 'Verify Google Drive API credentials are correct';
                $recommendations[] = 'User may need to reconnect their Google Drive account';
                break;

            case 'api_limit_exceeded':
                $recommendations[] = 'Implement exponential backoff retry logic';
                $recommendations[] = 'Monitor Google Drive API quota usage';
                $recommendations[] = 'Consider spreading uploads across multiple time periods';
                break;

            case 'file_missing':
                $recommendations[] = 'Check file cleanup timing - files may be deleted too early';
                $recommendations[] = 'Verify storage permissions and disk space';
                $recommendations[] = 'Review file upload and processing workflow';
                break;

            case 'network_failure':
                $recommendations[] = 'Check network connectivity to Google Drive API';
                $recommendations[] = 'Implement retry logic with exponential backoff';
                $recommendations[] = 'Monitor network stability and latency';
                break;

            case 'permission_denied':
                $recommendations[] = 'Verify Google Drive folder permissions';
                $recommendations[] = 'Check if user has access to the target folder';
                $recommendations[] = 'Review Google Drive API scopes and permissions';
                break;

            case 'stuck_processing':
                $recommendations[] = 'Check queue worker status and restart if necessary';
                $recommendations[] = 'Review job timeout settings';
                $recommendations[] = 'Monitor queue depth and processing capacity';
                break;

            default:
                $recommendations[] = 'Review error logs for more specific information';
                $recommendations[] = 'Check system health and Google Drive connectivity';
                $recommendations[] = 'Consider manual retry after investigating the issue';
                break;
        }

        // Add general recommendations based on retry count
        if ($upload->retry_count > 2) {
            $recommendations[] = 'Consider marking as failed to prevent infinite retry loops';
        }

        if ($upload->recovery_attempts > 3) {
            $recommendations[] = 'Manual intervention required - automatic recovery has failed multiple times';
        }

        return array_unique($recommendations);
    }

    /**
     * Find related issues that might be connected to this upload failure.
     */
    private function findRelatedIssues(FileUpload $upload): array
    {
        $relatedIssues = [];

        // Find uploads with the same error
        if ($upload->last_error) {
            $sameErrorCount = FileUpload::where('last_error', $upload->last_error)
                ->where('id', '!=', $upload->id)
                ->where('created_at', '>=', now()->subDay())
                ->count();

            if ($sameErrorCount > 0) {
                $relatedIssues[] = [
                    'type' => 'same_error_pattern',
                    'description' => "Found {$sameErrorCount} other uploads with the same error in the last 24 hours",
                    'count' => $sameErrorCount
                ];
            }
        }

        // Find uploads from the same client email
        $sameClientIssues = FileUpload::where('email', $upload->email)
            ->where('id', '!=', $upload->id)
            ->whereNotNull('last_error')
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        if ($sameClientIssues > 0) {
            $relatedIssues[] = [
                'type' => 'client_pattern',
                'description' => "Found {$sameClientIssues} other failed uploads from the same client in the last week",
                'count' => $sameClientIssues
            ];
        }

        // Find uploads from the same user
        if ($upload->uploaded_by_user_id) {
            $sameUserIssues = FileUpload::where('uploaded_by_user_id', $upload->uploaded_by_user_id)
                ->where('id', '!=', $upload->id)
                ->whereNotNull('last_error')
                ->where('created_at', '>=', now()->subWeek())
                ->count();

            if ($sameUserIssues > 0) {
                $relatedIssues[] = [
                    'type' => 'user_pattern',
                    'description' => "Found {$sameUserIssues} other failed uploads from the same user in the last week",
                    'count' => $sameUserIssues
                ];
            }
        }

        return $relatedIssues;
    }
}