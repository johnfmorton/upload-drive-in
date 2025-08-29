<?php

namespace Tests\Unit\Services;

use App\Services\QueueWorkerStatus;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Test enhanced error handling and troubleshooting guidance in QueueWorkerStatus.
 */
class QueueWorkerStatusEnhancedErrorHandlingTest extends TestCase
{
    public function test_configuration_error_status_creation()
    {
        $errorMessage = 'Invalid queue driver configuration';
        $status = QueueWorkerStatus::configurationError($errorMessage);

        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Queue configuration error', $status->message);
        $this->assertEquals($errorMessage, $status->errorMessage);
        $this->assertTrue($status->canRetry);
        $this->assertIsArray($status->troubleshooting);
        $this->assertContains('Check QUEUE_CONNECTION setting in .env file', $status->troubleshooting);
        $this->assertContains('For Redis queue: verify REDIS_HOST and REDIS_PORT settings', $status->troubleshooting);
    }

    public function test_database_error_status_creation()
    {
        $errorMessage = 'Connection refused to database server';
        $status = QueueWorkerStatus::databaseError($errorMessage);

        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Database connection error', $status->message);
        $this->assertEquals($errorMessage, $status->errorMessage);
        $this->assertTrue($status->canRetry);
        $this->assertIsArray($status->troubleshooting);
        $this->assertContains('Verify database connection settings in .env file', $status->troubleshooting);
        $this->assertContains('Test database connection: php artisan tinker, then DB::connection()->getPdo()', $status->troubleshooting);
    }

    public function test_permission_error_status_creation()
    {
        $errorMessage = 'Permission denied writing to storage directory';
        $status = QueueWorkerStatus::permissionError($errorMessage);

        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Permission error', $status->message);
        $this->assertEquals($errorMessage, $status->errorMessage);
        $this->assertTrue($status->canRetry);
        $this->assertIsArray($status->troubleshooting);
        $this->assertContains('Check file permissions on storage directory: chmod -R 755 storage', $status->troubleshooting);
        $this->assertContains('Ensure web server user owns storage directory: chown -R www-data:www-data storage', $status->troubleshooting);
    }

    public function test_worker_not_running_status_creation()
    {
        $testJobId = 'test_12345';
        $status = QueueWorkerStatus::workerNotRunning($testJobId);

        $this->assertEquals(QueueWorkerStatus::STATUS_TIMEOUT, $status->status);
        $this->assertEquals('Queue worker is not running', $status->message);
        $this->assertEquals('Test job timed out - no worker process detected', $status->errorMessage);
        $this->assertEquals($testJobId, $status->testJobId);
        $this->assertTrue($status->canRetry);
        $this->assertIsArray($status->troubleshooting);
        $this->assertContains('Start the queue worker: php artisan queue:work', $status->troubleshooting);
        $this->assertContains('Check if worker process is running: ps aux | grep "queue:work"', $status->troubleshooting);
    }

    public function test_worker_stuck_status_creation()
    {
        $testJobId = 'test_67890';
        $status = QueueWorkerStatus::workerStuck($testJobId);

        $this->assertEquals(QueueWorkerStatus::STATUS_TIMEOUT, $status->status);
        $this->assertEquals('Queue worker appears stuck', $status->message);
        $this->assertEquals('Test job timed out - worker may be processing other jobs or stuck', $status->errorMessage);
        $this->assertEquals($testJobId, $status->testJobId);
        $this->assertTrue($status->canRetry);
        $this->assertIsArray($status->troubleshooting);
        $this->assertContains('Restart the queue worker: php artisan queue:restart', $status->troubleshooting);
        $this->assertContains('Check worker memory usage: ps aux | grep "queue:work"', $status->troubleshooting);
    }

    public function test_enhanced_dispatch_failure_troubleshooting()
    {
        $errorMessage = 'Failed to dispatch test job';
        $status = QueueWorkerStatus::dispatchFailed($errorMessage);

        $this->assertIsArray($status->troubleshooting);
        $this->assertContains('Test database connection: php artisan tinker, then DB::connection()->getPdo()', $status->troubleshooting);
        $this->assertContains('For Redis queue: ensure Redis server is running and accessible', $status->troubleshooting);
    }

    public function test_enhanced_job_failure_troubleshooting()
    {
        $errorMessage = 'Job execution failed with error';
        $testJobId = 'test_job_123';
        $status = QueueWorkerStatus::jobFailed($errorMessage, $testJobId);

        $this->assertIsArray($status->troubleshooting);
        $this->assertContains('Restart queue worker: php artisan queue:restart', $status->troubleshooting);
        $this->assertContains('Clear application cache: php artisan cache:clear', $status->troubleshooting);
        $this->assertContains('Check disk space and file permissions', $status->troubleshooting);
    }

    public function test_custom_troubleshooting_steps()
    {
        $customTroubleshooting = [
            'Custom step 1: Check specific configuration',
            'Custom step 2: Verify custom service is running',
            'Custom step 3: Contact system administrator'
        ];

        $status = QueueWorkerStatus::configurationError('Custom error', $customTroubleshooting);

        $this->assertEquals($customTroubleshooting, $status->troubleshooting);
    }

    public function test_status_serialization_with_troubleshooting()
    {
        $status = QueueWorkerStatus::databaseError('Database connection failed');
        $array = $status->toArray();

        $this->assertArrayHasKey('troubleshooting', $array);
        $this->assertIsArray($array['troubleshooting']);
        $this->assertNotEmpty($array['troubleshooting']);
        $this->assertEquals($status->troubleshooting, $array['troubleshooting']);
    }

    public function test_status_deserialization_with_troubleshooting()
    {
        $troubleshootingSteps = [
            'Step 1: Check configuration',
            'Step 2: Verify permissions',
            'Step 3: Restart service'
        ];

        $data = [
            'status' => QueueWorkerStatus::STATUS_FAILED,
            'message' => 'Test failed',
            'error_message' => 'Configuration error',
            'troubleshooting' => $troubleshootingSteps,
            'can_retry' => true
        ];

        $status = QueueWorkerStatus::fromArray($data);

        $this->assertEquals($troubleshootingSteps, $status->troubleshooting);
        $this->assertTrue($status->canRetry);
    }

    public function test_error_status_failure_detection()
    {
        $configError = QueueWorkerStatus::configurationError('Config error');
        $dbError = QueueWorkerStatus::databaseError('DB error');
        $permissionError = QueueWorkerStatus::permissionError('Permission error');
        $workerNotRunning = QueueWorkerStatus::workerNotRunning();
        $workerStuck = QueueWorkerStatus::workerStuck();

        $this->assertTrue($configError->isFailed());
        $this->assertTrue($dbError->isFailed());
        $this->assertTrue($permissionError->isFailed());
        $this->assertTrue($workerNotRunning->isFailed());
        $this->assertTrue($workerStuck->isFailed());

        $this->assertFalse($configError->isSuccessful());
        $this->assertFalse($dbError->isSuccessful());
        $this->assertFalse($permissionError->isSuccessful());
        $this->assertFalse($workerNotRunning->isSuccessful());
        $this->assertFalse($workerStuck->isSuccessful());
    }

    public function test_all_error_types_have_troubleshooting_steps()
    {
        $errorTypes = [
            QueueWorkerStatus::configurationError('test'),
            QueueWorkerStatus::databaseError('test'),
            QueueWorkerStatus::permissionError('test'),
            QueueWorkerStatus::workerNotRunning(),
            QueueWorkerStatus::workerStuck(),
            QueueWorkerStatus::dispatchFailed('test'),
            QueueWorkerStatus::jobFailed('test', 'job123'),
            QueueWorkerStatus::networkError('test'),
            QueueWorkerStatus::timeout(),
            QueueWorkerStatus::error('test'),
            QueueWorkerStatus::failed('test')
        ];

        foreach ($errorTypes as $status) {
            $this->assertIsArray($status->troubleshooting, "Status type {$status->status} should have troubleshooting steps");
            $this->assertNotEmpty($status->troubleshooting, "Status type {$status->status} should have non-empty troubleshooting steps");
            
            // Verify each troubleshooting step is a string
            foreach ($status->troubleshooting as $step) {
                $this->assertIsString($step, "Troubleshooting step should be a string");
                $this->assertNotEmpty($step, "Troubleshooting step should not be empty");
            }
        }
    }
}