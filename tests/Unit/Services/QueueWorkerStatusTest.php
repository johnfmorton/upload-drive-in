<?php

namespace Tests\Unit\Services;

use App\Services\QueueWorkerStatus;
use Carbon\Carbon;
use Tests\TestCase;

class QueueWorkerStatusTest extends TestCase
{
    public function test_not_tested_status_creation()
    {
        $status = QueueWorkerStatus::notTested();
        
        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $status->status);
        $this->assertEquals('Click the Test Queue Worker button below', $status->message);
        $this->assertTrue($status->canRetry);
        $this->assertTrue($status->isExpired());
        $this->assertFalse($status->isSuccessful());
        $this->assertFalse($status->isFailed());
        $this->assertFalse($status->isTesting());
    }

    public function test_testing_status_creation()
    {
        $jobId = 'test_12345';
        $status = QueueWorkerStatus::testing($jobId);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_TESTING, $status->status);
        $this->assertEquals('Testing queue worker...', $status->message);
        $this->assertEquals($jobId, $status->testJobId);
        $this->assertFalse($status->canRetry);
        $this->assertTrue($status->isTesting());
        $this->assertFalse($status->isSuccessful());
        $this->assertFalse($status->isFailed());
    }

    public function test_completed_status_creation()
    {
        $processingTime = 1.23;
        $jobId = 'test_12345';
        $status = QueueWorkerStatus::completed($processingTime, $jobId);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $status->status);
        $this->assertEquals('Queue worker is functioning properly (1.23s)', $status->message);
        $this->assertEquals($processingTime, $status->processingTime);
        $this->assertEquals($jobId, $status->testJobId);
        $this->assertInstanceOf(Carbon::class, $status->testCompletedAt);
        $this->assertTrue($status->canRetry);
        $this->assertTrue($status->isSuccessful());
        $this->assertFalse($status->isFailed());
        $this->assertFalse($status->isTesting());
        $this->assertFalse($status->isExpired()); // Should not be expired immediately
    }

    public function test_failed_status_creation()
    {
        $errorMessage = 'Job execution failed';
        $jobId = 'test_12345';
        $status = QueueWorkerStatus::failed($errorMessage, $jobId);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Queue worker test failed', $status->message);
        $this->assertEquals($errorMessage, $status->errorMessage);
        $this->assertEquals($jobId, $status->testJobId);
        $this->assertIsArray($status->troubleshooting);
        $this->assertTrue($status->canRetry);
        $this->assertFalse($status->isSuccessful());
        $this->assertTrue($status->isFailed());
        $this->assertFalse($status->isTesting());
    }

    public function test_timeout_status_creation()
    {
        $jobId = 'test_12345';
        $status = QueueWorkerStatus::timeout($jobId);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_TIMEOUT, $status->status);
        $this->assertEquals('Queue worker test timed out - worker may not be running', $status->message);
        $this->assertEquals('Test job did not complete within the expected time', $status->errorMessage);
        $this->assertEquals($jobId, $status->testJobId);
        $this->assertIsArray($status->troubleshooting);
        $this->assertTrue($status->canRetry);
        $this->assertFalse($status->isSuccessful());
        $this->assertTrue($status->isFailed());
        $this->assertFalse($status->isTesting());
    }

    public function test_error_status_creation()
    {
        $errorMessage = 'Cache service unavailable';
        $jobId = 'test_12345';
        $status = QueueWorkerStatus::error($errorMessage, $jobId);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_ERROR, $status->status);
        $this->assertEquals('Error checking queue worker status', $status->message);
        $this->assertEquals($errorMessage, $status->errorMessage);
        $this->assertEquals($jobId, $status->testJobId);
        $this->assertIsArray($status->troubleshooting);
        $this->assertTrue($status->canRetry);
        $this->assertFalse($status->isSuccessful());
        $this->assertTrue($status->isFailed());
        $this->assertFalse($status->isTesting());
    }

    public function test_status_expiration()
    {
        // Create a completed status with a past completion time
        $pastTime = Carbon::now()->subHours(2);
        $status = new QueueWorkerStatus(
            status: QueueWorkerStatus::STATUS_COMPLETED,
            message: 'Test message',
            testCompletedAt: $pastTime
        );
        
        $this->assertTrue($status->isExpired());
        
        // Create a completed status with a recent completion time
        $recentTime = Carbon::now()->subMinutes(30);
        $status = new QueueWorkerStatus(
            status: QueueWorkerStatus::STATUS_COMPLETED,
            message: 'Test message',
            testCompletedAt: $recentTime
        );
        
        $this->assertFalse($status->isExpired());
        
        // Testing statuses should not be considered expired
        $status = new QueueWorkerStatus(
            status: QueueWorkerStatus::STATUS_TESTING,
            message: 'Test message'
        );
        
        $this->assertFalse($status->isExpired());
        
        // Not tested statuses should be considered expired
        $status = new QueueWorkerStatus(
            status: QueueWorkerStatus::STATUS_NOT_TESTED,
            message: 'Test message'
        );
        
        $this->assertTrue($status->isExpired());
    }

    public function test_to_array_conversion()
    {
        $processingTime = 1.23;
        $jobId = 'test_12345';
        $status = QueueWorkerStatus::completed($processingTime, $jobId);
        
        $array = $status->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(QueueWorkerStatus::STATUS_COMPLETED, $array['status']);
        $this->assertEquals('Queue worker is functioning properly (1.23s)', $array['message']);
        $this->assertEquals($processingTime, $array['processing_time']);
        $this->assertEquals($jobId, $array['test_job_id']);
        $this->assertIsString($array['test_completed_at']);
        $this->assertTrue($array['can_retry']);
        $this->assertIsBool($array['is_expired']);
    }

    public function test_from_array_conversion()
    {
        $data = [
            'status' => QueueWorkerStatus::STATUS_COMPLETED,
            'message' => 'Queue worker is functioning properly (1.23s)',
            'test_completed_at' => Carbon::now()->toISOString(),
            'processing_time' => 1.23,
            'error_message' => null,
            'test_job_id' => 'test_12345',
            'troubleshooting' => null,
            'can_retry' => true
        ];
        
        $status = QueueWorkerStatus::fromArray($data);
        
        $this->assertEquals($data['status'], $status->status);
        $this->assertEquals($data['message'], $status->message);
        $this->assertEquals($data['processing_time'], $status->processingTime);
        $this->assertEquals($data['test_job_id'], $status->testJobId);
        $this->assertInstanceOf(Carbon::class, $status->testCompletedAt);
        $this->assertTrue($status->canRetry);
    }

    public function test_from_array_with_missing_data()
    {
        $data = [
            'status' => QueueWorkerStatus::STATUS_FAILED,
            'message' => 'Test failed'
        ];
        
        $status = QueueWorkerStatus::fromArray($data);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Test failed', $status->message);
        $this->assertNull($status->testCompletedAt);
        $this->assertNull($status->processingTime);
        $this->assertNull($status->errorMessage);
        $this->assertNull($status->testJobId);
        $this->assertTrue($status->canRetry); // Default value
    }

    public function test_from_array_with_empty_data()
    {
        $status = QueueWorkerStatus::fromArray([]);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_NOT_TESTED, $status->status);
        $this->assertEquals('', $status->message);
        $this->assertTrue($status->canRetry);
    }
}