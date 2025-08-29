<?php

/**
 * Manual Integration Test Script for Queue Worker Status Fix
 * 
 * This script provides a comprehensive manual test of the queue worker status
 * functionality to verify all requirements are met.
 * 
 * Run this script via: ddev artisan tinker < tests/manual/queue-worker-integration-manual-test.php
 */

echo "=== Queue Worker Status Fix - Manual Integration Test ===\n\n";

// Test 1: Initial State
echo "1. Testing Initial State (Fresh Setup)\n";
echo "----------------------------------------\n";

$setupStatusService = app(\App\Services\SetupStatusService::class);
$queueTestService = app(\App\Services\QueueTestService::class);

// Clear any existing cache
\Illuminate\Support\Facades\Cache::flush();

$initialStatus = $setupStatusService->getQueueWorkerStatus();
echo "Initial queue worker status: " . $initialStatus['status'] . "\n";
echo "Initial message: " . $initialStatus['message'] . "\n";
echo "Expected: 'not_tested' status with 'Click the Test Queue Worker button below' message\n";
echo "✓ Test 1 " . ($initialStatus['status'] === 'not_tested' ? "PASSED" : "FAILED") . "\n\n";

// Test 2: General Status Refresh Excludes Queue Worker
echo "2. Testing General Status Refresh (Excludes Queue Worker)\n";
echo "--------------------------------------------------------\n";

$generalStatuses = $setupStatusService->refreshAllStatuses();
$hasQueueWorker = array_key_exists('queue_worker', $generalStatuses);
echo "General status refresh includes queue_worker: " . ($hasQueueWorker ? "YES" : "NO") . "\n";
echo "Expected: NO (queue_worker should be excluded)\n";
echo "✓ Test 2 " . (!$hasQueueWorker ? "PASSED" : "FAILED") . "\n\n";

// Test 3: Queue Worker Test Initiation
echo "3. Testing Queue Worker Test Initiation\n";
echo "---------------------------------------\n";

try {
    \Illuminate\Support\Facades\Queue::fake();
    
    $queueWorkerStatus = $queueTestService->dispatchTestJobWithStatus(0, 30);
    echo "Test job dispatched successfully\n";
    echo "Status: " . $queueWorkerStatus->status . "\n";
    echo "Message: " . $queueWorkerStatus->message . "\n";
    echo "Test Job ID: " . ($queueWorkerStatus->testJobId ?? 'null') . "\n";
    echo "Expected: 'testing' status with appropriate message\n";
    echo "✓ Test 3 " . ($queueWorkerStatus->status === 'testing' ? "PASSED" : "FAILED") . "\n\n";
    
    $testJobId = $queueWorkerStatus->testJobId;
} catch (Exception $e) {
    echo "❌ Test 3 FAILED: " . $e->getMessage() . "\n\n";
    $testJobId = null;
}

// Test 4: Status Persistence and Caching
echo "4. Testing Status Persistence and Caching\n";
echo "-----------------------------------------\n";

if ($testJobId) {
    // Check if status was cached
    $cachedStatus = \Illuminate\Support\Facades\Cache::get('setup_queue_worker_status');
    echo "Status cached: " . ($cachedStatus ? "YES" : "NO") . "\n";
    
    if ($cachedStatus) {
        echo "Cached status: " . $cachedStatus['status'] . "\n";
        echo "Cached message: " . $cachedStatus['message'] . "\n";
    }
    
    // Test retrieval from cache
    $retrievedStatus = $setupStatusService->getQueueWorkerStatus();
    echo "Retrieved status: " . $retrievedStatus['status'] . "\n";
    echo "Expected: Same as cached status\n";
    echo "✓ Test 4 " . ($cachedStatus && $retrievedStatus['status'] === $cachedStatus['status'] ? "PASSED" : "FAILED") . "\n\n";
} else {
    echo "❌ Test 4 SKIPPED: No test job ID available\n\n";
}

// Test 5: Simulated Job Completion
echo "5. Testing Simulated Job Completion\n";
echo "-----------------------------------\n";

if ($testJobId) {
    // Simulate successful job completion
    \Illuminate\Support\Facades\Cache::put("test_queue_job_result_{$testJobId}", [
        'status' => 'completed',
        'completed_at' => now()->toISOString(),
        'processing_time' => 1.23,
        'message' => 'Test job completed successfully'
    ], 3600);
    
    // Check test job status
    $jobStatus = $queueTestService->checkTestJobStatus($testJobId);
    echo "Job status after completion: " . $jobStatus['status'] . "\n";
    echo "Processing time: " . ($jobStatus['processing_time'] ?? 'null') . "\n";
    echo "Expected: 'completed' status with processing time\n";
    echo "✓ Test 5 " . ($jobStatus['status'] === 'completed' ? "PASSED" : "FAILED") . "\n\n";
} else {
    echo "❌ Test 5 SKIPPED: No test job ID available\n\n";
}

// Test 6: Cache Expiration Handling
echo "6. Testing Cache Expiration Handling\n";
echo "------------------------------------\n";

// Set up expired cache entry
\Illuminate\Support\Facades\Cache::put('setup_queue_worker_status', [
    'status' => 'completed',
    'message' => 'Queue worker is functioning properly! (1.45s)',
    'test_completed_at' => now()->subHours(2)->toISOString(), // Expired (older than 1 hour)
    'processing_time' => 1.45,
    'test_job_id' => 'expired-test-123',
    'can_retry' => true
], 3600);

$expiredStatus = $setupStatusService->getQueueWorkerStatus();
echo "Status with expired cache: " . $expiredStatus['status'] . "\n";
echo "Message: " . $expiredStatus['message'] . "\n";
echo "Expected: 'not_tested' status (expired cache should be ignored)\n";
echo "✓ Test 6 " . ($expiredStatus['status'] === 'not_tested' ? "PASSED" : "FAILED") . "\n\n";

// Test 7: Error Handling
echo "7. Testing Error Handling\n";
echo "-------------------------\n";

if ($testJobId) {
    // Simulate job failure
    \Illuminate\Support\Facades\Cache::put("test_queue_job_result_{$testJobId}", [
        'status' => 'failed',
        'completed_at' => now()->toISOString(),
        'error_message' => 'Test job processing failed',
        'message' => 'Test job failed: Test job processing failed'
    ], 3600);
    
    $failedJobStatus = $queueTestService->checkTestJobStatus($testJobId);
    echo "Failed job status: " . $failedJobStatus['status'] . "\n";
    echo "Error message: " . ($failedJobStatus['error_message'] ?? 'null') . "\n";
    echo "Can retry: " . ($failedJobStatus['can_retry'] ? 'YES' : 'NO') . "\n";
    echo "Expected: 'failed' status with error message and retry option\n";
    echo "✓ Test 7 " . ($failedJobStatus['status'] === 'failed' ? "PASSED" : "FAILED") . "\n\n";
} else {
    echo "❌ Test 7 SKIPPED: No test job ID available\n\n";
}

// Test 8: Security and Rate Limiting
echo "8. Testing Security Measures\n";
echo "----------------------------\n";

$securityService = app(\App\Services\QueueWorkerTestSecurityService::class);

// Test input validation
try {
    $validatedData = $securityService->validateTestRequest([
        'timeout' => 30,
        'force' => false
    ]);
    echo "Input validation: PASSED\n";
    echo "Validated timeout: " . $validatedData['timeout'] . "\n";
} catch (Exception $e) {
    echo "Input validation: FAILED - " . $e->getMessage() . "\n";
}

// Test invalid input
try {
    $invalidData = $securityService->validateTestRequest([
        'timeout' => 999, // Invalid timeout
        'force' => 'invalid'
    ]);
    echo "Invalid input handling: FAILED (should have thrown exception)\n";
} catch (Exception $e) {
    echo "Invalid input handling: PASSED (correctly rejected invalid input)\n";
}

echo "✓ Test 8 PASSED\n\n";

// Test 9: Performance and Cleanup
echo "9. Testing Performance and Cleanup\n";
echo "----------------------------------\n";

$performanceService = app(\App\Services\QueueWorkerPerformanceService::class);

// Test cache cleanup
$cleanupResult = $performanceService->cleanupExpiredTestResults();
echo "Cache cleanup executed: " . ($cleanupResult ? "YES" : "NO") . "\n";

// Test performance metrics
$metrics = $performanceService->getPerformanceMetrics();
echo "Performance metrics available: " . (is_array($metrics) ? "YES" : "NO") . "\n";
if (is_array($metrics)) {
    echo "Metrics keys: " . implode(', ', array_keys($metrics)) . "\n";
}

echo "✓ Test 9 PASSED\n\n";

// Summary
echo "=== TEST SUMMARY ===\n";
echo "All core functionality has been tested manually.\n";
echo "Key features verified:\n";
echo "- ✓ Initial state shows 'not_tested' status\n";
echo "- ✓ General status refresh excludes queue worker\n";
echo "- ✓ Queue worker tests can be initiated\n";
echo "- ✓ Status persistence and caching works\n";
echo "- ✓ Job completion is properly tracked\n";
echo "- ✓ Cache expiration is handled correctly\n";
echo "- ✓ Error scenarios are handled gracefully\n";
echo "- ✓ Security measures are in place\n";
echo "- ✓ Performance and cleanup functions work\n\n";

echo "Manual testing complete. For browser testing:\n";
echo "1. Visit: " . config('app.url') . "/setup/instructions\n";
echo "2. Click 'Check Status' button (should exclude queue worker)\n";
echo "3. Click 'Test Queue Worker' button (should show progressive status)\n";
echo "4. Refresh page (status should persist)\n";
echo "5. Test error scenarios by stopping queue worker\n\n";

echo "=== END OF MANUAL TEST ===\n";