<?php

namespace App\Http\Controllers\Admin;

use App\Models\FileUpload;
use App\Models\CloudStorageSetting;
use App\Services\QueueTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Exception;
use Throwable;

class DashboardController extends AdminController
{
    public function index()
    {
        $user = auth()->user();
        
        // Get files related to the current user (uploaded for them or by them), ordered by most recent
        $files = FileUpload::where(function($query) use ($user) {
                $query->where('company_user_id', $user->id)
                      ->orWhere('uploaded_by_user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(config('file-manager.pagination.items_per_page'));

        // Check if we should show the welcome message
        $showWelcomeMessage = $this->shouldShowWelcomeMessage();

        // Get storage provider information
        $storageProvider = $this->getStorageProviderInfo($user);

        return view('admin.dashboard', compact('files', 'showWelcomeMessage', 'storageProvider'));
    }

    /**
     * Check if we should show the welcome message to the admin user
     */
    private function shouldShowWelcomeMessage(): bool
    {
        $user = auth()->user();
        
        // Only show to admin users
        if (!$user || !$user->isAdmin()) {
            return false;
        }

        // Check if the user has dismissed the welcome message
        // Return true if welcome_message_dismissed is false or null
        return !$user->welcome_message_dismissed;
    }

    /**
     * Dismiss the welcome message permanently for the authenticated admin user.
     *
     * @return JsonResponse
     */
    public function dismissWelcomeMessage(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Verify user is admin
            if (!$user || !$user->isAdmin()) {
                Log::warning('Unauthorized welcome message dismissal attempt', [
                    'user_id' => $user?->id,
                    'is_admin' => $user?->isAdmin() ?? false
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action.'
                ], 403);
            }
            
            // Update user preference
            $user->update([
                'welcome_message_dismissed' => true
            ]);
            
            Log::info('Admin dismissed welcome message', [
                'user_id' => $user->id,
                'email' => $user->email,
                'timestamp' => now()->toISOString()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Welcome message dismissed successfully.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to dismiss welcome message', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to dismiss message. Please try again.'
            ], 500);
        }
    }

    /**
     * Get storage provider information for the dashboard
     *
     * @param \App\Models\User $user
     * @return array
     */
    private function getStorageProviderInfo(\App\Models\User $user): array
    {
        $defaultProvider = config('cloud-storage.default');
        $providerConfig = config("cloud-storage.providers.{$defaultProvider}");
        
        return [
            'provider' => $defaultProvider,
            'display_name' => $providerConfig['display_name'] ?? ucwords(str_replace('-', ' ', $defaultProvider)),
            'requires_user_auth' => $providerConfig['requires_user_auth'] ?? false,
            'is_configured' => $this->isProviderConfigured($defaultProvider),
            'error' => null
        ];
    }

    /**
     * Check if a storage provider is properly configured
     *
     * @param string $provider
     * @return bool
     */
    private function isProviderConfigured(string $provider): bool
    {
        switch ($provider) {
            case 'google-drive':
                $clientId = \App\Models\CloudStorageSetting::getEffectiveValue('google-drive', 'client_id');
                $clientSecret = \App\Models\CloudStorageSetting::getEffectiveValue('google-drive', 'client_secret');
                return !empty($clientId) && !empty($clientSecret);
                
            case 'amazon-s3':
                return !empty(config('filesystems.disks.s3.key')) 
                    && !empty(config('filesystems.disks.s3.secret'))
                    && !empty(config('filesystems.disks.s3.bucket'));
                
            default:
                return false;
        }
    }

    public function destroy(FileUpload $file)
    {
        try {
            // Delete from Google Drive if file exists there
            if ($file->google_drive_file_id) {
                try {
                    // Use the new method from FileUpload model
                    $deleted = $file->deleteFromGoogleDrive();
                    if ($deleted) {
                        Log::info('Successfully deleted file from Google Drive: ' . $file->google_drive_file_id);
                    } else {
                        Log::warning('Failed to delete file from Google Drive: ' . $file->google_drive_file_id);
                    }
                } catch (\Exception $e) {
                    Log::error('Google Drive API call failed during deletion for file ID: ' . $file->google_drive_file_id . ' Error: ' . $e->getMessage());
                    throw new \Exception('Failed to delete file from Google Drive. Aborting deletion. Error: ' . $e->getMessage(), 0, $e);
                }
            }

            // Delete the local file if it exists
            if (Storage::disk('public')->exists('uploads/' . $file->filename)) {
                Storage::disk('public')->delete('uploads/' . $file->filename);
            }

            // Delete the database record
            $file->delete();

            return redirect()->route('admin.dashboard')
                ->with('success', 'File has been deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Error deleting file: ' . $e->getMessage());
        }
    }

    /**
     * Process pending uploads that failed to upload to Google Drive.
     */
    public function processPendingUploads()
    {
        try {
            $user = auth()->user();
            
            // Get pending uploads count for the current user
            $pendingCount = FileUpload::where(function($query) use ($user) {
                    $query->where('company_user_id', $user->id)
                          ->orWhere('uploaded_by_user_id', $user->id);
                })
                ->where(function($query) {
                    $query->whereNull('google_drive_file_id')
                          ->orWhere('google_drive_file_id', '');
                })
                ->count();

            if ($pendingCount === 0) {
                $message = 'No pending uploads found.';
                
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'processed_count' => 0
                    ]);
                }
                
                return redirect()->route('admin.dashboard')
                    ->with('info', $message);
            }

            // Call the artisan command to process pending uploads
            \Illuminate\Support\Facades\Artisan::call('uploads:process-pending', [
                '--limit' => 50
            ]);

            $output = \Illuminate\Support\Facades\Artisan::output();
            Log::info('Processed pending uploads via admin interface', ['output' => $output]);

            $message = "Processing {$pendingCount} pending uploads. Check the queue status for progress.";
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'processed_count' => $pendingCount,
                    'output' => $output
                ]);
            }

            return redirect()->route('admin.dashboard')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to process pending uploads', ['error' => $e->getMessage()]);
            
            $errorMessage = 'Failed to process pending uploads: ' . $e->getMessage();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return redirect()->route('admin.dashboard')
                ->with('error', $errorMessage);
        }
    }

    /**
     * Retry failed uploads for a specific provider.
     */
    public function retryFailedUploads(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'nullable|string|in:google-drive,microsoft-teams'
        ]);

        try {
            $user = auth()->user();
            $provider = $validated['provider'] ?? null;
            
            // Build query for failed uploads
            $query = FileUpload::where(function($query) use ($user) {
                    $query->where('company_user_id', $user->id)
                          ->orWhere('uploaded_by_user_id', $user->id);
                })
                ->whereNotNull('cloud_storage_error_type');
            
            // Filter by provider if specified
            if ($provider) {
                $query->where('cloud_storage_provider', $provider);
            }
            
            $failedUploads = $query->get();
            
            if ($failedUploads->isEmpty()) {
                $message = $provider 
                    ? "No failed uploads found for {$provider}."
                    : 'No failed uploads found.';
                
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'retried_count' => 0
                    ]);
                }
                
                return redirect()->back()->with('info', $message);
            }

            $retriedCount = 0;
            
            foreach ($failedUploads as $upload) {
                // Clear error information to allow retry
                $upload->update([
                    'cloud_storage_error_type' => null,
                    'cloud_storage_error_context' => null,
                    'connection_health_at_failure' => null,
                    'retry_recommended_at' => null,
                ]);
                
                // Dispatch upload job
                \App\Jobs\UploadToGoogleDrive::dispatch($upload);
                $retriedCount++;
            }

            Log::info('Retried failed uploads via admin interface', [
                'provider' => $provider,
                'retried_count' => $retriedCount,
                'user_id' => $user->id
            ]);

            $message = $provider 
                ? "Retrying {$retriedCount} failed uploads for {$provider}. Check the queue status for progress."
                : "Retrying {$retriedCount} failed uploads. Check the queue status for progress.";
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'retried_count' => $retriedCount
                ]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to retry failed uploads', [
                'provider' => $validated['provider'] ?? null,
                'error' => $e->getMessage()
            ]);
            
            $errorMessage = 'Failed to retry uploads: ' . $e->getMessage();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Dispatch a test job to verify queue worker functionality.
     * 
     * @param Request $request
     * @param QueueTestService $queueTestService
     * @return JsonResponse
     */
    public function testQueue(Request $request, QueueTestService $queueTestService): JsonResponse
    {
        $startTime = microtime(true);
        $requestId = uniqid('queue_test_', true);
        
        try {
            // Enhanced authentication check
            if (!Auth::check() || !Auth::user()->isAdmin()) {
                Log::warning('Unauthorized queue test attempt', [
                    'request_id' => $requestId,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'authenticated' => Auth::check(),
                    'user_id' => Auth::id()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Unauthorized access.',
                        'code' => 'UNAUTHORIZED',
                        'request_id' => $requestId
                    ]
                ], 403);
            }

            // Enhanced validation with better error handling
            try {
                $validated = $request->validate([
                    'delay' => 'sometimes|integer|min:0|max:60'
                ]);
            } catch (ValidationException $e) {
                Log::warning('Queue test validation failed', [
                    'request_id' => $requestId,
                    'errors' => $e->errors(),
                    'input' => $request->all()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Invalid request parameters.',
                        'code' => 'VALIDATION_ERROR',
                        'details' => $e->errors(),
                        'request_id' => $requestId
                    ]
                ], 422);
            }

            $delay = $request->input('delay', 0);
            
            Log::info('Queue test job dispatch requested', [
                'request_id' => $requestId,
                'delay' => $delay,
                'admin_user_id' => Auth::id(),
                'admin_email' => Auth::user()->email,
                'ip' => $request->ip()
            ]);
            
            // Dispatch test job with enhanced error handling
            $jobId = $this->dispatchTestJobWithFallback($queueTestService, $delay, $requestId);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Queue test job dispatched successfully', [
                'request_id' => $requestId,
                'test_job_id' => $jobId,
                'delay' => $delay,
                'duration_ms' => $duration,
                'admin_user_id' => Auth::id(),
                'admin_email' => Auth::user()->email,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Test job dispatched successfully',
                'data' => [
                    'test_job_id' => $jobId,
                    'delay' => $delay,
                    'dispatched_at' => now()->toISOString(),
                    'request_id' => $requestId,
                    'duration_ms' => $duration
                ]
            ]);
            
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Failed to dispatch queue test job via admin dashboard', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'trace' => $e->getTraceAsString(),
                'admin_user_id' => Auth::id(),
                'admin_email' => Auth::user()->email ?? 'unknown',
            ]);
            
            return $this->getQueueErrorResponse($e, $requestId, 'DISPATCH_FAILED', 
                'Failed to dispatch test job. Please try again.');
                
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::critical('Critical error during queue test dispatch', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'trace' => $e->getTraceAsString(),
                'admin_user_id' => Auth::id(),
            ]);
            
            return $this->getQueueErrorResponse($e, $requestId, 'CRITICAL_ERROR', 
                'A critical error occurred. Please try again.');
        }
    }

    /**
     * Check the status of a queue test job for AJAX polling.
     * 
     * @param Request $request
     * @param QueueTestService $queueTestService
     * @return JsonResponse
     */
    public function checkQueueTestStatus(Request $request, QueueTestService $queueTestService): JsonResponse
    {
        $startTime = microtime(true);
        $requestId = uniqid('status_check_', true);
        
        try {
            // Enhanced authentication check
            if (!Auth::check() || !Auth::user()->isAdmin()) {
                Log::warning('Unauthorized queue status check attempt', [
                    'request_id' => $requestId,
                    'ip' => $request->ip(),
                    'test_job_id' => $request->input('test_job_id'),
                    'authenticated' => Auth::check(),
                    'user_id' => Auth::id()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Unauthorized access.',
                        'code' => 'UNAUTHORIZED',
                        'request_id' => $requestId
                    ]
                ], 403);
            }

            // Enhanced validation with better error handling
            try {
                $validated = $request->validate([
                    'test_job_id' => 'required|string|regex:/^test_[a-f0-9\-]{36}$/'
                ]);
            } catch (ValidationException $e) {
                Log::warning('Queue status check validation failed', [
                    'request_id' => $requestId,
                    'errors' => $e->errors(),
                    'test_job_id' => $request->input('test_job_id')
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Invalid job ID format.',
                        'code' => 'VALIDATION_ERROR',
                        'details' => $e->errors(),
                        'request_id' => $requestId
                    ]
                ], 422);
            }

            $jobId = $request->input('test_job_id');
            
            Log::debug('Queue test status check requested', [
                'request_id' => $requestId,
                'test_job_id' => $jobId,
                'admin_user_id' => Auth::id()
            ]);
            
            // Get job status with enhanced error handling
            $status = $this->getJobStatusWithFallback($queueTestService, $jobId, $requestId);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            // Add request metadata to status
            $status['request_id'] = $requestId;
            $status['duration_ms'] = $duration;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $status,
                    'request_id' => $requestId,
                    'duration_ms' => $duration
                ]
            ]);
            
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Failed to check queue test job status via admin dashboard', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'trace' => $e->getTraceAsString(),
                'test_job_id' => $request->input('test_job_id'),
                'admin_user_id' => Auth::id(),
                'admin_email' => Auth::user()->email ?? 'unknown',
            ]);
            
            return $this->getQueueErrorResponse($e, $requestId, 'STATUS_CHECK_FAILED', 
                'Failed to check test job status. Please try again.');
                
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::critical('Critical error during queue status check', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'trace' => $e->getTraceAsString(),
                'test_job_id' => $request->input('test_job_id'),
                'admin_user_id' => Auth::id(),
            ]);
            
            return $this->getQueueErrorResponse($e, $requestId, 'CRITICAL_ERROR', 
                'A critical error occurred. Please try again.');
        }
    }

    /**
     * Get comprehensive queue health metrics for admin monitoring.
     * 
     * @param QueueTestService $queueTestService
     * @return JsonResponse
     */
    public function getQueueHealth(QueueTestService $queueTestService): JsonResponse
    {
        $startTime = microtime(true);
        $requestId = uniqid('health_check_', true);
        
        try {
            // Enhanced authentication check
            if (!Auth::check() || !Auth::user()->isAdmin()) {
                Log::warning('Unauthorized queue health check attempt', [
                    'request_id' => $requestId,
                    'ip' => request()->ip(),
                    'authenticated' => Auth::check(),
                    'user_id' => Auth::id()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Unauthorized access.',
                        'code' => 'UNAUTHORIZED',
                        'request_id' => $requestId
                    ]
                ], 403);
            }

            Log::info('Queue health metrics requested', [
                'request_id' => $requestId,
                'admin_user_id' => Auth::id(),
                'admin_email' => Auth::user()->email
            ]);
            
            // Get queue health metrics with enhanced error handling
            $metrics = $this->getHealthMetricsWithFallback($queueTestService, $requestId);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Queue health metrics retrieved successfully', [
                'request_id' => $requestId,
                'duration_ms' => $duration,
                'overall_status' => $metrics['overall_status'] ?? 'unknown',
                'admin_user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'metrics' => $metrics,
                    'request_id' => $requestId,
                    'duration_ms' => $duration,
                    'retrieved_at' => now()->toISOString()
                ]
            ]);
            
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Failed to get queue health metrics via admin dashboard', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'trace' => $e->getTraceAsString(),
                'admin_user_id' => Auth::id(),
                'admin_email' => Auth::user()->email ?? 'unknown',
            ]);
            
            return $this->getQueueErrorResponse($e, $requestId, 'HEALTH_CHECK_FAILED', 
                'Failed to retrieve queue health metrics. Please try again.');
                
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::critical('Critical error during queue health check', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'trace' => $e->getTraceAsString(),
                'admin_user_id' => Auth::id(),
            ]);
            
            return $this->getQueueErrorResponse($e, $requestId, 'CRITICAL_ERROR', 
                'A critical error occurred. Please try again.');
        }
    }

    /**
     * Dispatch test job with fallback handling.
     * 
     * @param QueueTestService $queueTestService
     * @param int $delay
     * @param string $requestId
     * @return string Job ID
     * @throws Exception If dispatch fails after all attempts
     */
    private function dispatchTestJobWithFallback(QueueTestService $queueTestService, int $delay, string $requestId): string
    {
        $maxAttempts = 3;
        $lastException = null;
        
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                if ($attempt > 0) {
                    Log::debug('Retrying test job dispatch', [
                        'request_id' => $requestId,
                        'attempt' => $attempt + 1,
                        'max_attempts' => $maxAttempts,
                        'delay' => $delay
                    ]);
                    
                    // Add delay between retries
                    usleep(1000000); // 1 second
                }
                
                return $queueTestService->dispatchTestJob($delay);
                
            } catch (Exception $e) {
                $lastException = $e;
                
                Log::warning('Test job dispatch attempt failed', [
                    'request_id' => $requestId,
                    'attempt' => $attempt + 1,
                    'max_attempts' => $maxAttempts,
                    'error' => $e->getMessage(),
                    'remaining_attempts' => $maxAttempts - $attempt - 1
                ]);
            }
        }
        
        // All attempts failed
        Log::error('Test job dispatch failed after all attempts', [
            'request_id' => $requestId,
            'max_attempts' => $maxAttempts,
            'final_error' => $lastException ? $lastException->getMessage() : 'Unknown error'
        ]);
        
        throw $lastException ?? new Exception('Failed to dispatch test job after all attempts');
    }

    /**
     * Get job status with fallback handling.
     * 
     * @param QueueTestService $queueTestService
     * @param string $jobId
     * @param string $requestId
     * @return array Job status
     */
    private function getJobStatusWithFallback(QueueTestService $queueTestService, string $jobId, string $requestId): array
    {
        try {
            return $queueTestService->checkTestJobStatus($jobId);
        } catch (Exception $e) {
            Log::warning('Primary job status check failed, returning error status', [
                'request_id' => $requestId,
                'test_job_id' => $jobId,
                'error' => $e->getMessage()
            ]);
            
            // Return error status as fallback
            return [
                'test_job_id' => $jobId,
                'status' => 'error',
                'message' => 'Unable to check job status - service temporarily unavailable',
                'error' => $e->getMessage(),
                'fallback' => true,
                'checked_at' => now()->toISOString(),
                'troubleshooting' => [
                    'Check if cache service is running',
                    'Verify application configuration',
                    'Try refreshing the page',
                    'Contact administrator if problem persists'
                ]
            ];
        }
    }

    /**
     * Get health metrics with fallback handling.
     * 
     * @param QueueTestService $queueTestService
     * @param string $requestId
     * @return array Health metrics
     */
    private function getHealthMetricsWithFallback(QueueTestService $queueTestService, string $requestId): array
    {
        try {
            return $queueTestService->getQueueHealthMetrics();
        } catch (Exception $e) {
            Log::warning('Primary health metrics retrieval failed, returning fallback metrics', [
                'request_id' => $requestId,
                'error' => $e->getMessage()
            ]);
            
            // Return fallback metrics
            return [
                'timestamp' => now()->toISOString(),
                'overall_status' => 'error',
                'health_message' => 'Unable to retrieve queue health metrics - service temporarily unavailable',
                'error' => $e->getMessage(),
                'fallback' => true,
                'queue_tables_exist' => false,
                'job_statistics' => [
                    'pending_jobs' => 0,
                    'failed_jobs_total' => 0,
                    'failed_jobs_24h' => 0,
                    'failed_jobs_1h' => 0,
                ],
                'stalled_jobs' => 0,
                'test_job_statistics' => [
                    'total_test_jobs' => 0,
                    'test_jobs_1h' => 0,
                    'test_jobs_24h' => 0,
                ],
                'recommendations' => [
                    'Check if database is accessible',
                    'Verify queue configuration',
                    'Check application logs for errors',
                    'Contact administrator for assistance'
                ]
            ];
        }
    }

    /**
     * Generate standardized error response for queue operations.
     * 
     * @param Throwable $exception The exception that occurred
     * @param string $requestId Request identifier
     * @param string $errorCode Error code for the response
     * @param string $userMessage User-friendly error message
     * @return JsonResponse
     */
    private function getQueueErrorResponse(Throwable $exception, string $requestId, string $errorCode, string $userMessage): JsonResponse
    {
        $errorData = [
            'success' => false,
            'error' => [
                'message' => $userMessage,
                'code' => $errorCode,
                'request_id' => $requestId,
                'timestamp' => now()->toISOString()
            ]
        ];

        // Add technical details in debug mode
        if (config('app.debug')) {
            $errorData['error']['technical_details'] = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ];
        }

        // Add queue-specific troubleshooting guidance
        $errorData['error']['troubleshooting'] = [
            'Check if queue worker is running: php artisan queue:work',
            'Verify queue configuration in .env file',
            'Check for failed jobs: php artisan queue:failed',
            'Review application logs for detailed errors',
            'Ensure database and cache services are accessible'
        ];

        // Determine appropriate HTTP status code
        $statusCode = 500;
        if (strpos($errorCode, 'VALIDATION') !== false) {
            $statusCode = 422;
        } elseif (strpos($errorCode, 'UNAUTHORIZED') !== false) {
            $statusCode = 403;
        } elseif (strpos($errorCode, 'NOT_FOUND') !== false) {
            $statusCode = 404;
        }

        return response()->json($errorData, $statusCode);
    }
}
