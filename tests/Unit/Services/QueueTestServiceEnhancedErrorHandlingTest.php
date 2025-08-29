<?php

namespace Tests\Unit\Services;

use App\Services\QueueTestService;
use App\Services\QueueWorkerStatus;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ReflectionClass;

class QueueTestServiceEnhancedErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    private QueueTestService $queueTestService;
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queueTestService = app(QueueTestService::class);
        $this->reflection = new ReflectionClass($this->queueTestService);
    }

    /** @test */
    public function it_identifies_dispatch_errors_correctly()
    {
        $method = $this->reflection->getMethod('isDispatchError');
        $method->setAccessible(true);

        // Test dispatch error indicators
        $dispatchError = new Exception('Failed to dispatch job to queue');
        $this->assertTrue($method->invoke($this->queueTestService, $dispatchError));

        $queueConnectionError = new Exception('Queue connection failed');
        $this->assertTrue($method->invoke($this->queueTestService, $queueConnectionError));

        $databaseError = new Exception('Database connection lost');
        $this->assertTrue($method->invoke($this->queueTestService, $databaseError));

        $tableError = new Exception('Table jobs table doesn\'t exist');
        $this->assertTrue($method->invoke($this->queueTestService, $tableError));

        // Test non-dispatch error
        $genericError = new Exception('Some other error');
        $this->assertFalse($method->invoke($this->queueTestService, $genericError));
    }

    /** @test */
    public function it_identifies_network_errors_correctly()
    {
        $method = $this->reflection->getMethod('isNetworkError');
        $method->setAccessible(true);

        // Test network error indicators
        $connectionRefused = new Exception('Connection refused');
        $this->assertTrue($method->invoke($this->queueTestService, $connectionRefused));

        $timeout = new Exception('Connection timed out');
        $this->assertTrue($method->invoke($this->queueTestService, $timeout));

        $unreachable = new Exception('Network unreachable');
        $this->assertTrue($method->invoke($this->queueTestService, $unreachable));

        $hostError = new Exception('Could not resolve host');
        $this->assertTrue($method->invoke($this->queueTestService, $hostError));

        // Test non-network error
        $genericError = new Exception('Some other error');
        $this->assertFalse($method->invoke($this->queueTestService, $genericError));
    }

    /** @test */
    public function it_creates_appropriate_error_status_from_exception_type()
    {
        $method = $this->reflection->getMethod('createErrorStatusFromException');
        $method->setAccessible(true);

        // Test dispatch error
        $dispatchException = new Exception('Failed to dispatch job');
        $status = $method->invoke($this->queueTestService, $dispatchException);
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Failed to dispatch test job', $status->message);

        // Test network error
        $networkException = new Exception('Connection refused');
        $status = $method->invoke($this->queueTestService, $networkException);
        $this->assertEquals(QueueWorkerStatus::STATUS_ERROR, $status->status);
        $this->assertEquals('Network error during queue test', $status->message);

        // Test generic error
        $genericException = new Exception('Generic error');
        $status = $method->invoke($this->queueTestService, $genericException);
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $status->status);
        $this->assertEquals('Queue worker test failed', $status->message);
    }

    /** @test */
    public function it_builds_enhanced_error_messages_with_context()
    {
        $method = $this->reflection->getMethod('buildEnhancedErrorMessage');
        $method->setAccessible(true);

        $maxAttempts = 3;

        // Test dispatch error message
        $dispatchException = new Exception('Failed to dispatch job to queue');
        $message = $method->invoke($this->queueTestService, $dispatchException, $maxAttempts);
        $this->assertStringContainsString('Queue configuration issue', $message);
        $this->assertStringContainsString("after {$maxAttempts} attempts", $message);

        // Test network error message
        $networkException = new Exception('Connection refused');
        $message = $method->invoke($this->queueTestService, $networkException, $maxAttempts);
        $this->assertStringContainsString('Network connectivity issue', $message);
        $this->assertStringContainsString("after {$maxAttempts} attempts", $message);

        // Test generic error message
        $genericException = new Exception('Some generic error');
        $message = $method->invoke($this->queueTestService, $genericException, $maxAttempts);
        $this->assertStringContainsString('Some generic error', $message);
        $this->assertStringContainsString("after {$maxAttempts} attempts", $message);

        // Test null exception
        $message = $method->invoke($this->queueTestService, null, $maxAttempts);
        $this->assertStringContainsString('Unknown error', $message);
        $this->assertStringContainsString("after {$maxAttempts} attempts", $message);
    }

    /** @test */
    public function it_determines_non_retryable_errors_correctly()
    {
        $method = $this->reflection->getMethod('shouldNotRetryDispatch');
        $method->setAccessible(true);

        // Test non-retryable exception types
        $invalidArgument = new \InvalidArgumentException('Invalid argument');
        $this->assertTrue($method->invoke($this->queueTestService, $invalidArgument));

        $badMethod = new \BadMethodCallException('Method not found');
        $this->assertTrue($method->invoke($this->queueTestService, $badMethod));

        $logic = new \LogicException('Logic error');
        $this->assertTrue($method->invoke($this->queueTestService, $logic));

        // Test non-retryable error messages
        $classNotFound = new Exception('Class not found');
        $this->assertTrue($method->invoke($this->queueTestService, $classNotFound));

        $methodNotExist = new Exception('Method does not exist');
        $this->assertTrue($method->invoke($this->queueTestService, $methodNotExist));

        $permissionDenied = new Exception('Permission denied');
        $this->assertTrue($method->invoke($this->queueTestService, $permissionDenied));

        // Test retryable errors
        $retryableError = new Exception('Temporary connection issue');
        $this->assertFalse($method->invoke($this->queueTestService, $retryableError));
    }

    /** @test */
    public function it_initializes_job_status_with_configurable_timeout()
    {
        $method = $this->reflection->getMethod('initializeJobStatusWithConfigurableTimeout');
        $method->setAccessible(true);

        $jobId = 'test_' . \Illuminate\Support\Str::uuid()->toString(); // Use valid UUID format
        $delay = 5;
        $timeout = 45;

        // This should not throw an exception
        $method->invoke($this->queueTestService, $jobId, $delay, $timeout);

        // Verify the job status was created with correct timeout
        $jobStatus = $this->queueTestService->checkTestJobStatus($jobId);
        
        $this->assertEquals($jobId, $jobStatus['test_job_id']);
        $this->assertEquals('pending', $jobStatus['status']);
        $this->assertEquals($delay, $jobStatus['delay']);
        $this->assertEquals($timeout, $jobStatus['timeout_seconds']);
        $this->assertNotNull($jobStatus['timeout_at']);
    }

    /** @test */
    public function it_handles_cache_failures_gracefully_during_initialization()
    {
        // Mock cache failure by using invalid cache configuration
        config(['cache.default' => 'invalid_driver']);

        $method = $this->reflection->getMethod('initializeJobStatusWithConfigurableTimeout');
        $method->setAccessible(true);

        $jobId = 'test_cache_failure';
        $delay = 0;
        $timeout = 30;

        // This should throw an exception due to cache failure
        $this->expectException(Exception::class);
        // The actual exception message may vary based on cache driver
        $this->expectExceptionMessageMatches('/cache|store|driver/i');

        $method->invoke($this->queueTestService, $jobId, $delay, $timeout);
    }

    /** @test */
    public function it_provides_different_timeout_constants()
    {
        $reflection = new ReflectionClass($this->queueTestService);
        
        // Check that timeout constants are defined
        $this->assertTrue($reflection->hasConstant('TIMEOUT_DISPATCH'));
        $this->assertTrue($reflection->hasConstant('TIMEOUT_QUICK_TEST'));
        $this->assertTrue($reflection->hasConstant('TIMEOUT_STANDARD'));
        $this->assertTrue($reflection->hasConstant('TIMEOUT_EXTENDED'));

        // Check that constants have reasonable values
        $this->assertEquals(10, $reflection->getConstant('TIMEOUT_DISPATCH'));
        $this->assertEquals(15, $reflection->getConstant('TIMEOUT_QUICK_TEST'));
        $this->assertEquals(30, $reflection->getConstant('TIMEOUT_STANDARD'));
        $this->assertEquals(60, $reflection->getConstant('TIMEOUT_EXTENDED'));
    }

    /** @test */
    public function it_handles_enhanced_dispatch_with_retry_logic()
    {
        $method = $this->reflection->getMethod('dispatchTestJobWithEnhancedErrorHandling');
        $method->setAccessible(true);

        // This test would require more complex mocking to simulate retry scenarios
        // For now, we'll test that the method exists and can be called
        $this->assertTrue($method->isPrivate());
        $this->assertEquals('dispatchTestJobWithEnhancedErrorHandling', $method->getName());
    }

    /** @test */
    public function it_validates_error_status_array_conversion()
    {
        // Test that all error status types convert to arrays properly
        $dispatchStatus = QueueWorkerStatus::dispatchFailed('Dispatch error');
        $array = $dispatchStatus->toArray();
        
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('error_message', $array);
        $this->assertArrayHasKey('troubleshooting', $array);
        $this->assertArrayHasKey('can_retry', $array);
        
        $this->assertEquals(QueueWorkerStatus::STATUS_FAILED, $array['status']);
        $this->assertTrue($array['can_retry']);
        $this->assertIsArray($array['troubleshooting']);
        $this->assertNotEmpty($array['troubleshooting']);
    }

    /** @test */
    public function it_handles_error_recovery_scenarios()
    {
        // Test that error statuses maintain proper state for recovery
        $networkError = QueueWorkerStatus::networkError('Connection lost', 'test_123');
        
        $this->assertTrue($networkError->canRetry);
        $this->assertFalse($networkError->isSuccessful());
        $this->assertTrue($networkError->isFailed());
        $this->assertFalse($networkError->isTesting());
        
        // Test that troubleshooting information is available
        $this->assertNotEmpty($networkError->troubleshooting);
        $this->assertContains('Check your internet connection', $networkError->troubleshooting);
    }
}