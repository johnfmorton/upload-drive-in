<?php

namespace App\Http\Controllers;

use App\Services\MailTestService;
use App\Services\QueueTestService;
use App\Services\QueueWorkerTestSecurityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SetupController extends Controller
{
    public function __construct(
        private QueueTestService $queueTestService,
        private QueueWorkerTestSecurityService $securityService,
        private MailTestService $mailTestService
    ) {}

    /**
     * Test email configuration by sending a test email
     */
    public function testEmail(Request $request): JsonResponse
    {
        try {
            // Rate limit: 3 test emails per 5 minutes per IP
            $key = 'setup-email-test:' . $request->ip();

            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableIn($key);

                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => "Too many test email attempts. Please try again in {$seconds} seconds.",
                        'code' => 'RATE_LIMIT_EXCEEDED',
                        'retry_after' => $seconds,
                    ]
                ], 429);
            }

            // Validate email address
            $validatedData = $request->validate([
                'email' => 'required|email:rfc,dns|max:255',
            ], [
                'email.required' => 'Please enter an email address.',
                'email.email' => 'Please enter a valid email address.',
                'email.max' => 'Email address is too long.',
            ]);

            // Increment rate limiter
            RateLimiter::hit($key, 300); // 5 minutes decay

            // Log the test attempt
            \Log::info('Email test requested from setup', [
                'email' => $validatedData['email'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Send test email synchronously
            $result = $this->mailTestService->sendTestEmail($validatedData['email']);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'test_id' => $result['test_id'],
                        'duration_ms' => $result['duration_ms'],
                        'recipient' => $validatedData['email'],
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'message' => $result['message'],
                    'code' => strtoupper($result['error_type']),
                    'troubleshooting' => $result['troubleshooting'],
                    'details' => $result['details'],
                ]
            ], 422);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Invalid email address.',
                    'code' => 'VALIDATION_ERROR',
                    'details' => $e->errors()
                ]
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to send test email from setup', [
                'error' => $e->getMessage(),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'An unexpected error occurred while sending the test email.',
                    'code' => 'INTERNAL_ERROR',
                ]
            ], 500);
        }
    }

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