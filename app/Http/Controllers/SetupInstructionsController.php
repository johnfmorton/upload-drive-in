<?php

namespace App\Http\Controllers;

use App\Services\SetupDetectionService;
use App\Services\SetupStatusService;
use App\Services\SetupSecurityService;
use App\Services\EnvironmentFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;
use Throwable;

class SetupInstructionsController extends Controller
{
    public function __construct(
        private SetupDetectionService $setupDetectionService,
        private SetupStatusService $setupStatusService,
        private SetupSecurityService $setupSecurityService,
        private EnvironmentFileService $environmentFileService
    ) {}

    /**
     * Display the setup instructions page or redirect if setup is complete.
     */
    public function show(): View|RedirectResponse
    {
        // For the setup instructions page, we assume setup is not complete
        // and skip the database-dependent setup completion check to avoid
        // infinite loops when database configuration is incomplete.
        // 
        // The middleware will handle redirecting users away from this page
        // if setup is actually complete.
        
        Log::info('Showing setup instructions page');
        return view('setup.instructions');
    }

    /**
     * Refresh and return JSON status data for all setup steps.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshStatus(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $requestId = uniqid('refresh_', true);
        
        try {
            Log::info('Setup status refresh requested', [
                'request_id' => $requestId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Security validation with timeout
            if ($this->setupSecurityService->shouldBlockRequest($request)) {
                Log::warning('Setup status refresh blocked by security service', [
                    'request_id' => $requestId,
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Request blocked for security reasons.',
                        'code' => 'SECURITY_BLOCK',
                        'request_id' => $requestId
                    ]
                ], 403);
            }

            // Validate and sanitize input with error handling
            $sanitization = $this->setupSecurityService->sanitizeStatusRequest($request->all());
            if (!$sanitization['is_valid']) {
                Log::warning('Setup status refresh validation failed', [
                    'request_id' => $requestId,
                    'violations' => $sanitization['violations']
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Invalid request parameters.',
                        'code' => 'VALIDATION_ERROR',
                        'details' => $sanitization['violations'],
                        'request_id' => $requestId
                    ]
                ], 422);
            }

            // Log security event
            $this->setupSecurityService->logSecurityEvent('status_refresh_requested', [
                'route' => 'setup.status.refresh',
                'request_id' => $requestId,
                'sanitized_input' => $sanitization['sanitized']
            ]);
            
            // Get fresh status data with fallback handling (excluding queue_worker)
            $statuses = $this->refreshStatusesWithFallback($requestId);
            $summary = $this->getSummaryWithFallback($requestId);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Setup status refresh completed successfully', [
                'request_id' => $requestId,
                'duration_ms' => $duration,
                'status_count' => count($statuses),
                'has_fallback_data' => $this->hasFallbackData($statuses)
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'statuses' => $statuses,
                    'summary' => $summary,
                    'refreshed_at' => now()->toISOString(),
                    'request_id' => $requestId,
                    'duration_ms' => $duration
                ],
                'message' => 'Status refreshed successfully'
            ]);
            
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Failed to refresh setup status', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'trace' => $e->getTraceAsString(),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return $this->getErrorResponse($e, $requestId, 'REFRESH_FAILED', 
                'Failed to refresh status. Please try again.');
                
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::critical('Critical error during setup status refresh', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'trace' => $e->getTraceAsString(),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return $this->getErrorResponse($e, $requestId, 'CRITICAL_ERROR', 
                'A critical error occurred. Please try again.');
        }
    }

    /**
     * Refresh and return JSON status data for a single setup step.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshSingleStep(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $requestId = uniqid('step_refresh_', true);
        
        try {
            Log::info('Single step status refresh requested', [
                'request_id' => $requestId,
                'step' => $request->input('step'),
                'ip' => $request->ip()
            ]);

            // Security validation with timeout
            if ($this->setupSecurityService->shouldBlockRequest($request)) {
                Log::warning('Single step refresh blocked by security service', [
                    'request_id' => $requestId,
                    'step' => $request->input('step'),
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Request blocked for security reasons.',
                        'code' => 'SECURITY_BLOCK',
                        'request_id' => $requestId
                    ]
                ], 403);
            }

            // Validate and sanitize input with enhanced error handling
            $sanitization = $this->setupSecurityService->sanitizeStatusRequest($request->all());
            if (!$sanitization['is_valid']) {
                Log::warning('Single step refresh validation failed', [
                    'request_id' => $requestId,
                    'violations' => $sanitization['violations']
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Invalid request parameters.',
                        'code' => 'VALIDATION_ERROR',
                        'details' => $sanitization['violations'],
                        'request_id' => $requestId
                    ]
                ], 422);
            }

            // Additional validation for required step parameter
            if (!isset($sanitization['sanitized']['step'])) {
                Log::warning('Step parameter missing in single step refresh', [
                    'request_id' => $requestId,
                    'sanitized_input' => $sanitization['sanitized']
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Step parameter is required.',
                        'code' => 'MISSING_STEP',
                        'request_id' => $requestId
                    ]
                ], 422);
            }
            
            $stepName = $sanitization['sanitized']['step'];

            // Log security event
            $this->setupSecurityService->logSecurityEvent('single_step_refresh_requested', [
                'route' => 'setup.status.refresh-step',
                'request_id' => $requestId,
                'step' => $stepName
            ]);
            
            // Get fresh status with fallback handling
            $allStatuses = $this->getStepStatusesWithFallback($requestId, $stepName);
            
            // Check if the requested step exists in the results
            if (!isset($allStatuses[$stepName])) {
                Log::warning('Invalid step name provided', [
                    'request_id' => $requestId,
                    'step_name' => $stepName,
                    'available_steps' => array_keys($allStatuses)
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Invalid step name provided.',
                        'code' => 'INVALID_STEP',
                        'valid_steps' => array_keys($allStatuses),
                        'request_id' => $requestId
                    ]
                ], 400);
            }
            
            $stepStatus = $allStatuses[$stepName];
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Single step status refresh completed', [
                'request_id' => $requestId,
                'step' => $stepName,
                'status' => $stepStatus['status'],
                'duration_ms' => $duration
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'step' => $stepName,
                    'status' => $stepStatus,
                    'refreshed_at' => now()->toISOString(),
                    'request_id' => $requestId,
                    'duration_ms' => $duration
                ],
                'message' => "Status for '{$stepStatus['step_name']}' refreshed successfully"
            ]);
            
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Failed to refresh single step status', [
                'request_id' => $requestId,
                'step' => $request->input('step'),
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'trace' => $e->getTraceAsString(),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return $this->getErrorResponse($e, $requestId, 'STEP_REFRESH_FAILED', 
                'Failed to refresh step status. Please try again.');
                
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::critical('Critical error during single step refresh', [
                'request_id' => $requestId,
                'step' => $request->input('step'),
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getErrorResponse($e, $requestId, 'CRITICAL_ERROR', 
                'A critical error occurred. Please try again.');
        }
    }

    /**
     * Refresh statuses with fallback handling.
     * 
     * @param string $requestId Request identifier for logging
     * @return array Status data
     */
    private function refreshStatusesWithFallback(string $requestId): array
    {
        try {
            return $this->setupStatusService->refreshAllStatuses();
        } catch (Exception $e) {
            Log::warning('Primary status refresh failed, attempting fallback', [
                'request_id' => $requestId,
                'error' => $e->getMessage()
            ]);
            
            // Try to get cached data as fallback
            try {
                return $this->setupStatusService->getDetailedStepStatuses(true);
            } catch (Exception $fallbackError) {
                Log::error('Fallback status retrieval also failed', [
                    'request_id' => $requestId,
                    'primary_error' => $e->getMessage(),
                    'fallback_error' => $fallbackError->getMessage()
                ]);
                
                throw $e; // Re-throw original exception
            }
        }
    }

    /**
     * Get summary with fallback handling.
     * 
     * @param string $requestId Request identifier for logging
     * @return array Summary data
     */
    private function getSummaryWithFallback(string $requestId): array
    {
        try {
            return $this->setupStatusService->getStatusSummary(false);
        } catch (Exception $e) {
            Log::warning('Primary summary retrieval failed, attempting fallback', [
                'request_id' => $requestId,
                'error' => $e->getMessage()
            ]);
            
            // Try to get cached summary as fallback
            try {
                return $this->setupStatusService->getStatusSummary(true);
            } catch (Exception $fallbackError) {
                Log::warning('Fallback summary retrieval also failed, returning minimal summary', [
                    'request_id' => $requestId,
                    'primary_error' => $e->getMessage(),
                    'fallback_error' => $fallbackError->getMessage()
                ]);
                
                // Return minimal summary as last resort
                return [
                    'overall_status' => 'error',
                    'completion_percentage' => 0,
                    'completed_steps' => 0,
                    'total_steps' => 6,
                    'incomplete_steps' => [],
                    'error_steps' => [],
                    'last_updated' => now()->toISOString(),
                    'error_message' => 'Unable to determine setup status',
                    'fallback' => true
                ];
            }
        }
    }

    /**
     * Get step statuses with fallback handling for single step refresh.
     * 
     * @param string $requestId Request identifier for logging
     * @param string $stepName The step being refreshed
     * @return array Status data
     */
    private function getStepStatusesWithFallback(string $requestId, string $stepName): array
    {
        try {
            // Clear cache and get fresh status for all steps (since steps may be interdependent)
            $this->setupStatusService->clearStatusCache();
            return $this->setupStatusService->getDetailedStepStatuses(false);
        } catch (Exception $e) {
            Log::warning('Primary step status refresh failed, attempting fallback', [
                'request_id' => $requestId,
                'step' => $stepName,
                'error' => $e->getMessage()
            ]);
            
            // Try to get cached data as fallback
            try {
                return $this->setupStatusService->getDetailedStepStatuses(true);
            } catch (Exception $fallbackError) {
                Log::error('Fallback step status retrieval also failed', [
                    'request_id' => $requestId,
                    'step' => $stepName,
                    'primary_error' => $e->getMessage(),
                    'fallback_error' => $fallbackError->getMessage()
                ]);
                
                throw $e; // Re-throw original exception
            }
        }
    }

    /**
     * Check if status data contains fallback information.
     * 
     * @param array $statuses Status data to check
     * @return bool True if any status is marked as fallback
     */
    private function hasFallbackData(array $statuses): bool
    {
        foreach ($statuses as $status) {
            if (isset($status['fallback']) && $status['fallback'] === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate standardized error response.
     * 
     * @param Throwable $exception The exception that occurred
     * @param string $requestId Request identifier
     * @param string $errorCode Error code for the response
     * @param string $userMessage User-friendly error message
     * @return JsonResponse
     */
    private function getErrorResponse(Throwable $exception, string $requestId, string $errorCode, string $userMessage): JsonResponse
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

        // Add troubleshooting guidance
        $errorData['error']['troubleshooting'] = [
            'Check your internet connection and try again',
            'Refresh the page and retry the operation',
            'If the problem persists, check application logs',
            'Contact administrator if issue continues'
        ];

        // Determine appropriate HTTP status code
        $statusCode = 500;
        if (strpos($errorCode, 'VALIDATION') !== false) {
            $statusCode = 422;
        } elseif (strpos($errorCode, 'SECURITY') !== false) {
            $statusCode = 403;
        } elseif (strpos($errorCode, 'NOT_FOUND') !== false) {
            $statusCode = 404;
        }

        return response()->json($errorData, $statusCode);
    }

    /**
     * Get queue worker status from cached test results.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getQueueWorkerStatus(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $requestId = uniqid('queue_status_', true);
        
        try {
            Log::info('Queue worker status requested', [
                'request_id' => $requestId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Security validation with timeout
            if ($this->setupSecurityService->shouldBlockRequest($request)) {
                Log::warning('Queue worker status blocked by security service', [
                    'request_id' => $requestId,
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Request blocked for security reasons.',
                        'code' => 'SECURITY_BLOCK',
                        'request_id' => $requestId
                    ]
                ], 403);
            }

            // Validate and sanitize input with error handling
            $sanitization = $this->setupSecurityService->sanitizeStatusRequest($request->all());
            if (!$sanitization['is_valid']) {
                Log::warning('Queue worker status validation failed', [
                    'request_id' => $requestId,
                    'violations' => $sanitization['violations']
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Invalid request parameters.',
                        'code' => 'VALIDATION_ERROR',
                        'details' => $sanitization['violations'],
                        'request_id' => $requestId
                    ]
                ], 422);
            }

            // Log security event
            $this->setupSecurityService->logSecurityEvent('queue_worker_status_requested', [
                'route' => 'setup.queue-worker.status',
                'request_id' => $requestId,
                'sanitized_input' => $sanitization['sanitized']
            ]);
            
            // Get queue worker status from cache or default state
            $queueWorkerStatus = $this->setupStatusService->getQueueWorkerStatus();
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Queue worker status retrieved successfully', [
                'request_id' => $requestId,
                'status' => $queueWorkerStatus['status'] ?? 'unknown',
                'duration_ms' => $duration
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'queue_worker' => $queueWorkerStatus,
                    'retrieved_at' => now()->toISOString(),
                    'request_id' => $requestId,
                    'duration_ms' => $duration
                ],
                'message' => 'Queue worker status retrieved successfully'
            ]);
            
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Failed to get queue worker status', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'trace' => $e->getTraceAsString(),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return $this->getErrorResponse($e, $requestId, 'QUEUE_STATUS_FAILED', 
                'Failed to get queue worker status. Please try again.');
                
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::critical('Critical error during queue worker status retrieval', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'trace' => $e->getTraceAsString(),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return $this->getErrorResponse($e, $requestId, 'CRITICAL_ERROR', 
                'A critical error occurred. Please try again.');
        }
    }

    /**
     * Disable the setup process by setting APP_SETUP_ENABLED to false.
     */
    public function disableSetup(Request $request): JsonResponse
    {
        $requestId = uniqid('disable_setup_', true);
        
        try {
            Log::info('Setup disable requested', [
                'request_id' => $requestId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Security validation
            if ($this->setupSecurityService->shouldBlockRequest($request)) {
                Log::warning('Setup disable blocked by security service', [
                    'request_id' => $requestId,
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Request blocked for security reasons.',
                        'code' => 'SECURITY_BLOCK',
                        'request_id' => $requestId
                    ]
                ], 403);
            }

            // Log security event
            $this->setupSecurityService->logSecurityEvent('setup_disable_requested', [
                'route' => 'setup.disable',
                'request_id' => $requestId
            ]);

            // Update environment file to disable setup
            $result = $this->environmentFileService->updateEnvironmentFile([
                'APP_SETUP_ENABLED' => 'false'
            ]);

            if (!$result['success']) {
                Log::error('Failed to disable setup', [
                    'request_id' => $requestId,
                    'error' => $result['message'],
                    'violations' => $result['violations'] ?? []
                ]);

                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Failed to disable setup: ' . $result['message'],
                        'code' => 'UPDATE_FAILED',
                        'request_id' => $requestId,
                        'violations' => $result['violations'] ?? []
                    ]
                ], 500);
            }

            // Clear config cache to ensure the new .env value is picked up on subsequent requests
            try {
                Artisan::call('config:clear');
                Log::info('Config cache cleared after disabling setup', [
                    'request_id' => $requestId
                ]);
            } catch (Exception $e) {
                // Log but don't fail - the .env was updated successfully
                Log::warning('Failed to clear config cache after disabling setup', [
                    'request_id' => $requestId,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('Setup disabled successfully', [
                'request_id' => $requestId,
                'backup_path' => $result['backup_path'] ?? null
            ]);

            // Log security event for successful disable
            $this->setupSecurityService->logSecurityEvent('setup_disabled', [
                'request_id' => $requestId,
                'backup_created' => $result['backup_created'] ?? false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Setup process has been disabled successfully.',
                'data' => [
                    'backup_created' => $result['backup_created'] ?? false,
                    'backup_path' => $result['backup_path'] ?? null,
                    'request_id' => $requestId,
                    'disabled_at' => now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Failed to disable setup', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return $this->getErrorResponse($e, $requestId, 'DISABLE_FAILED', 
                'Failed to disable setup. Please try again.');
                
        } catch (Throwable $e) {
            Log::critical('Critical error during setup disable', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return $this->getErrorResponse($e, $requestId, 'CRITICAL_ERROR', 
                'A critical error occurred. Please try again.');
        }
    }
}