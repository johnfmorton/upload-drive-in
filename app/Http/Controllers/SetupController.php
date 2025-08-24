<?php

namespace App\Http\Controllers;

use App\Services\QueueTestService;
use App\Services\SetupSecurityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SetupController extends Controller
{
    public function __construct(
        private QueueTestService $queueTestService,
        private SetupSecurityService $setupSecurityService
    ) {}

    /**
     * Test queue worker functionality (public route for setup instructions)
     */
    public function testQueue(Request $request): JsonResponse
    {
        try {
            // Security validation
            if ($this->setupSecurityService->shouldBlockRequest($request)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Request blocked for security reasons.',
                        'code' => 'SECURITY_BLOCK'
                    ]
                ], 403);
            }

            // Validate and sanitize input
            $sanitization = $this->setupSecurityService->sanitizeStatusRequest($request->all());
            if (!$sanitization['is_valid']) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Invalid request parameters.',
                        'code' => 'VALIDATION_ERROR',
                        'details' => $sanitization['violations']
                    ]
                ], 422);
            }

            $delay = $sanitization['sanitized']['delay'] ?? 0;

            // Log security event
            $this->setupSecurityService->logSecurityEvent('queue_test_requested', [
                'route' => 'setup.queue.test',
                'delay' => $delay
            ]);
            
            $testJobId = $this->queueTestService->dispatchTestJob($delay);
            
            return response()->json([
                'success' => true,
                'test_job_id' => $testJobId,
                'message' => 'Test job dispatched successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to dispatch test job from setup', [
                'error' => $e->getMessage(),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to dispatch test job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check queue test job status (public route for setup instructions)
     */
    public function checkQueueTestStatus(Request $request): JsonResponse
    {
        try {
            // Security validation
            if ($this->setupSecurityService->shouldBlockRequest($request)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Request blocked for security reasons.',
                        'code' => 'SECURITY_BLOCK'
                    ]
                ], 403);
            }

            // Validate and sanitize input
            $sanitization = $this->setupSecurityService->sanitizeStatusRequest($request->all());
            if (!$sanitization['is_valid']) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Invalid request parameters.',
                        'code' => 'VALIDATION_ERROR',
                        'details' => $sanitization['violations']
                    ]
                ], 422);
            }

            if (!isset($sanitization['sanitized']['test_job_id'])) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Test job ID is required.',
                        'code' => 'MISSING_JOB_ID'
                    ]
                ], 422);
            }

            $testJobId = $sanitization['sanitized']['test_job_id'];

            // Log security event
            $this->setupSecurityService->logSecurityEvent('queue_test_status_checked', [
                'route' => 'setup.queue.test.status',
                'test_job_id' => $testJobId
            ]);
            
            $status = $this->queueTestService->checkTestJobStatus($testJobId);
            
            return response()->json([
                'success' => true,
                'status' => $status
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to check test job status from setup', [
                'error' => $e->getMessage(),
                'test_job_id' => $request->input('test_job_id'),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check test job status: ' . $e->getMessage()
            ], 500);
        }
    }
}