<?php

namespace Tests\Unit\Services;

use App\Services\QueueTestService;
use App\Services\QueueWorkerStatus;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;

/**
 * Test enhanced error detection and classification in QueueTestService.
 */
class QueueTestServiceEnhancedErrorDetectionTest extends TestCase
{
    use RefreshDatabase;

    private QueueTestService $queueTestService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queueTestService = new QueueTestService();
    }

    public function test_configuration_error_detection()
    {
        $configException = new Exception('Invalid queue driver configuration');
        $status = $this->invokePrivateMethod('createErrorStatusFromException', [$configException]);

        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Queue configuration error', $status->message);
        $this->assertContains('Verify queue configuration in .env file (QUEUE_CONNECTION)', $status->troubleshooting);
    }

    public function test_database_error_detection_with_pdo_exception()
    {
        $dbException = new PDOException('SQLSTATE[HY000] [2002] Connection refused');
        $status = $this->invokePrivateMethod('createErrorStatusFromException', [$dbException]);

        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Database connection error', $status->message);
        $this->assertContains('Verify database connection settings in .env file', $status->troubleshooting);
    }

    public function test_database_error_detection_with_query_exception()
    {
        $queryException = new QueryException(
            'mysql',
            'SELECT * FROM jobs',
            [],
            new Exception('Table \'test.jobs\' doesn\'t exist')
        );
        $status = $this->invokePrivateMethod('createErrorStatusFromException', [$queryException]);

        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Database connection error', $status->message);
    }

    public function test_permission_error_detection()
    {
        $permissionException = new Exception('Permission denied writing to storage directory');
        $status = $this->invokePrivateMethod('createErrorStatusFromException', [$permissionException]);

        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Permission error', $status->message);
        $this->assertContains('Check file permissions on storage directory: chmod -R 755 storage', $status->troubleshooting);
    }

    public function test_dispatch_error_detection()
    {
        $dispatchException = new Exception('Failed to dispatch job to queue connection');
        $status = $this->invokePrivateMethod('createErrorStatusFromException', [$dispatchException]);

        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Failed to dispatch test job', $status->message);
    }

    public function test_network_error_detection()
    {
        $networkException = new Exception('Connection timed out while connecting to server');
        $status = $this->invokePrivateMethod('createErrorStatusFromException', [$networkException]);

        $this->assertEquals(QueueWorkerStatus::STATUS_ERROR, $status->status);
        $this->assertEquals('Network error during queue test', $status->message);
    }

    public function test_timeout_type_determination_with_pending_jobs()
    {
        // Mock queue health metrics with pending jobs
        $this->mockQueueHealthMetrics([
            'job_statistics' => [
                'pending_jobs' => 5,
                'failed_jobs_1h' => 0
            ]
        ]);

        $status = $this->invokePrivateMethod('determineTimeoutType', ['test_job_123']);

        $this->assertEquals(QueueWorkerStatus::STATUS_TIMEOUT, $status->status);
        $this->assertEquals('Queue worker appears stuck', $status->message);
        $this->assertContains('Restart the queue worker: php artisan queue:restart', $status->troubleshooting);
    }

    public function test_timeout_type_determination_with_recent_failures()
    {
        // Mock queue health metrics with recent failed jobs
        $this->mockQueueHealthMetrics([
            'job_statistics' => [
                'pending_jobs' => 0,
                'failed_jobs_1h' => 3
            ]
        ]);

        $status = $this->invokePrivateMethod('determineTimeoutType', ['test_job_123']);

        $this->assertEquals(QueueWorkerStatus::STATUS_TIMEOUT, $status->status);
        $this->assertEquals('Queue worker appears stuck', $status->message);
    }

    public function test_timeout_type_determination_worker_not_running()
    {
        // Mock queue health metrics with no pending jobs or failures
        $this->mockQueueHealthMetrics([
            'job_statistics' => [
                'pending_jobs' => 0,
                'failed_jobs_1h' => 0
            ]
        ]);

        $status = $this->invokePrivateMethod('determineTimeoutType', ['test_job_123']);

        $this->assertEquals(QueueWorkerStatus::STATUS_TIMEOUT, $status->status);
        $this->assertEquals('Queue worker is not running', $status->message);
        $this->assertContains('Start the queue worker: php artisan queue:work', $status->troubleshooting);
    }

    public function test_enhanced_error_message_building()
    {
        $configException = new Exception('Invalid queue driver configuration');
        $message = $this->invokePrivateMethod('buildEnhancedErrorMessage', [$configException, 3]);

        $this->assertStringContains('Configuration issue', $message);
        $this->assertStringContains('after 3 attempts', $message);
    }

    public function test_error_type_detection_methods()
    {
        // Test configuration error detection
        $configException = new Exception('queue connection error');
        $this->assertTrue($this->invokePrivateMethod('isConfigurationError', [$configException]));

        // Test database error detection
        $dbException = new Exception('database connection refused');
        $this->assertTrue($this->invokePrivateMethod('isDatabaseError', [$dbException]));

        // Test permission error detection
        $permException = new Exception('permission denied');
        $this->assertTrue($this->invokePrivateMethod('isPermissionError', [$permException]));

        // Test dispatch error detection
        $dispatchException = new Exception('failed to dispatch job');
        $this->assertTrue($this->invokePrivateMethod('isDispatchError', [$dispatchException]));

        // Test network error detection
        $networkException = new Exception('connection timed out');
        $this->assertTrue($this->invokePrivateMethod('isNetworkError', [$networkException]));
    }

    public function test_fallback_to_generic_error_for_unknown_types()
    {
        $unknownException = new Exception('Some unknown error occurred');
        $status = $this->invokePrivateMethod('createErrorStatusFromException', [$unknownException]);

        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Queue worker test failed', $status->message);
        $this->assertEquals('Some unknown error occurred', $status->errorMessage);
    }

    public function test_queue_worker_timeout_check_with_enhanced_detection()
    {
        // Cache a testing status
        $testingStatus = QueueWorkerStatus::testing('test_job_123', 'Testing...');
        Cache::put(QueueWorkerStatus::CACHE_KEY, $testingStatus->toArray(), 3600);

        // Mock a timeout job status
        $this->mockTestJobStatus('test_job_123', ['status' => 'timeout']);

        // Mock queue health for worker not running scenario
        $this->mockQueueHealthMetrics([
            'job_statistics' => [
                'pending_jobs' => 0,
                'failed_jobs_1h' => 0
            ]
        ]);

        $result = $this->queueTestService->checkQueueWorkerTimeout('test_job_123');

        $this->assertEquals(QueueWorkerStatus::STATUS_TIMEOUT, $result->status);
        $this->assertEquals('Queue worker is not running', $result->message);
    }

    /**
     * Helper method to invoke private methods for testing.
     */
    private function invokePrivateMethod(string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($this->queueTestService);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->queueTestService, $parameters);
    }

    /**
     * Mock queue health metrics for testing.
     */
    private function mockQueueHealthMetrics(array $metrics)
    {
        $reflection = new \ReflectionClass($this->queueTestService);
        $method = $reflection->getMethod('getQueueHealthMetrics');
        $method->setAccessible(true);

        // Create a partial mock that returns our test metrics
        $mock = $this->createPartialMock(QueueTestService::class, ['getQueueHealthMetrics']);
        $mock->method('getQueueHealthMetrics')->willReturn($metrics);

        // Replace the service instance
        $this->queueTestService = $mock;
    }

    /**
     * Mock test job status for testing.
     */
    private function mockTestJobStatus(string $jobId, array $status)
    {
        $mock = $this->createPartialMock(QueueTestService::class, ['checkTestJobStatus']);
        $mock->method('checkTestJobStatus')->with($jobId)->willReturn($status);

        $this->queueTestService = $mock;
    }
}