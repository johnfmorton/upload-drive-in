<?php

namespace App\Http\Controllers;

use App\Services\QueueTestService;
use App\Services\QueueWorkerTestSecurityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SetupController extends Controller
{
    public function __construct(
        private QueueTestService $queueTestService,
        private QueueWorkerTestSecurityService $securityService
    ) {}

    /**
     * Test queue worker functionality (public route for setup instructions)
     */
    public function testQueue(Request $request): JsonResponse
    {
        try {
            // Validate and sanitize input
            $validatedData = $this->securityService->validateTestRequest($request->all());
            
            $timeout = $validatedData['timeout'] ?? 30;
            $force = $validatedData['force'] ?? false;

            // Record security event
            $this->securityService->recordSecurityEvent('queue_test_requested', [
                'route' => 'setup.queue.test',
                'timeout' => $timeout,
                'force' => $force,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            // Dispatch test job and cache testing status with configurable timeout
            $queueWorkerStatus = $this->queueTestService->dispatchTestJobWithStatus(0, $timeout);
            
            return response()->json([
                'success' => true,
                'test_job_id' => $queueWorkerStatus->testJobId,
                'queue_worker_status' => $queueWorkerStatus->toArray(),
                'message' => 'Test job dispatched successfully'
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Invalid request parameters.',
                    'code' => 'VALIDATION_ERROR',
                    'details' => $e->errors()
                ]
            ], 422);
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
            // Validate test job ID parameter
            $request->validate([
                'test_job_id' => 'required|string|max:100',
            ]);

            $testJobId = $request->input('test_job_id');

            // Record security event
            $this->securityService->recordSecurityEvent('queue_test_status_checked', [
                'route' => 'setup.queue.test.status',
                'test_job_id' => $testJobId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            // Check test job status
            $jobStatus = $this->queueTestService->checkTestJobStatus($testJobId);
            
            // Check for timeout and update queue worker status if needed
            $queueWorkerStatus = $this->queueTestService->checkQueueWorkerTimeout($testJobId);
            
            return response()->json([
                'success' => true,
                'status' => $jobStatus,
                'queue_worker_status' => $queueWorkerStatus->toArray()
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Invalid request parameters.',
                    'code' => 'VALIDATION_ERROR',
                    'details' => $e->errors()
                ]
            ], 422);
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