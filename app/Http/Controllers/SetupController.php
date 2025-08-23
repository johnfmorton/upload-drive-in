<?php

namespace App\Http\Controllers;

use App\Services\QueueTestService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SetupController extends Controller
{
    public function __construct(
        private QueueTestService $queueTestService
    ) {}

    /**
     * Test queue worker functionality (public route for setup instructions)
     */
    public function testQueue(Request $request): JsonResponse
    {
        try {
            $delay = $request->input('delay', 0);
            
            $testJobId = $this->queueTestService->dispatchTestJob($delay);
            
            return response()->json([
                'success' => true,
                'test_job_id' => $testJobId,
                'message' => 'Test job dispatched successfully'
            ]);
            
        } catch (\Exception $e) {
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
            $testJobId = $request->query('test_job_id');
            
            if (!$testJobId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test job ID is required'
                ], 400);
            }
            
            $status = $this->queueTestService->checkTestJobStatus($testJobId);
            
            return response()->json([
                'success' => true,
                'status' => $status
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check test job status: ' . $e->getMessage()
            ], 500);
        }
    }
}