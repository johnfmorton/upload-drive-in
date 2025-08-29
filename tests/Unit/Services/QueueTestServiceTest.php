<?php

namespace Tests\Unit\Services;

use App\Jobs\TestQueueJob;
use App\Services\QueueTestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Tests\TestCase;
use Exception;

class QueueTestServiceTest extends TestCase
{
    use RefreshDatabase;

    private QueueTestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create security service dependency
        $securityService = app(\App\Services\QueueWorkerTestSecurityService::class);
        $this->service = new QueueTestService($securityService);
        
        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_can_dispatch_test_job_and_return_job_id(): void
    {
        Queue::fake();
        
        $jobId = $this->service->dispatchTestJob();
        
        // Verify job ID format
        $this->assertStringStartsWith('test_', $jobId);
        $this->assertEquals(41, strlen($jobId)); // 'test_' + UUID length
        
        // Verify job was dispatched
        Queue::assertPushed(TestQueueJob::class, function ($job) use ($jobId) {
            return $job->testJobId === $jobId && $job->testDelay === 0;
        });
        
        // Verify job status was initialized in cache
        $status = Cache::get('test_queue_job_' . $jobId);
        $this->assertNotNull($status);
        $this->assertEquals('pending', $status['status']);
        $this->assertEquals($jobId, $status['test_job_id']);
    }

    /** @test */
    public function it_can_dispatch_test_job_with_delay(): void
    {
        Queue::fake();
        
        $delay = 5;
        $jobId = $this->service->dispatchTestJob($delay);
        
        // Verify job was dispatched with correct delay
        Queue::assertPushed(TestQueueJob::class, function ($job) use ($jobId, $delay) {
            return $job->testJobId === $jobId && $job->testDelay === $delay;
        });
        
        // Verify delay is stored in cache
        $status = Cache::get('test_queue_job_' . $jobId);
        $this->assertEquals($delay, $status['delay']);
    }

    /** @test */
    public function it_handles_job_dispatch_failure(): void
    {
        // Mock Queue facade to simulate failure
        Queue::shouldReceive('connection')->andThrow(new Exception('Queue connection failed'));
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to dispatch test job: Queue connection failed');
        
        $this->service->dispatchTestJob();
    }

    /** @test */
    public function it_can_check_test_job_status(): void
    {
        // Create a test job status in cache
        $jobId = 'test_12345';
        $expectedStatus = [
            'test_job_id' => $jobId,
            'status' => 'completed',
            'message' => 'Test job completed successfully',
            'processing_time_ms' => 150.5,
            'completed_at' => Carbon::now()->toISOString(),
        ];
        
        Cache::put('test_queue_job_' . $jobId, $expectedStatus, 3600);
        
        $status = $this->service->checkTestJobStatus($jobId);
        
        $this->assertEquals($expectedStatus, $status);
    }

    /** @test */
    public function it_returns_not_found_for_missing_job(): void
    {
        $jobId = 'test_nonexistent';
        
        $status = $this->service->checkTestJobStatus($jobId);
        
        $this->assertEquals('not_found', $status['status']);
        $this->assertEquals($jobId, $status['test_job_id']);
        $this->assertStringContainsString('not found or expired', $status['message']);
    }

    /** @test */
    public function it_detects_job_timeout(): void
    {
        Carbon::setTestNow(Carbon::now());
        
        $jobId = 'test_timeout';
        $pastTimeout = Carbon::now()->subMinutes(5)->toISOString();
        
        $jobStatus = [
            'test_job_id' => $jobId,
            'status' => 'processing',
            'message' => 'Test job is processing',
            'timeout_at' => $pastTimeout,
        ];
        
        Cache::put('test_queue_job_' . $jobId, $jobStatus, 3600);
        
        $status = $this->service->checkTestJobStatus($jobId);
        
        $this->assertEquals('timeout', $status['status']);
        $this->assertStringContainsString('timed out', $status['message']);
        $this->assertArrayHasKey('timed_out_at', $status);
    }

    /** @test */
    public function it_handles_status_check_errors(): void
    {
        // Mock Cache to throw exception
        Cache::shouldReceive('get')->andThrow(new Exception('Cache connection failed'));
        
        $jobId = 'test_error';
        $status = $this->service->checkTestJobStatus($jobId);
        
        $this->assertEquals('error', $status['status']);
        $this->assertEquals($jobId, $status['test_job_id']);
        $this->assertStringContainsString('Error checking job status', $status['message']);
        $this->assertEquals('Cache connection failed', $status['error']);
    }

    /** @test */
    public function it_can_get_queue_health_metrics_when_tables_exist(): void
    {
        // Mock DB facade to return schema builder
        DB::shouldReceive('getSchemaBuilder')->andReturnSelf();
        DB::shouldReceive('hasTable')->with('jobs')->andReturn(true);
        DB::shouldReceive('hasTable')->with('failed_jobs')->andReturn(true);
        
        // Mock database queries
        DB::shouldReceive('table')->with('jobs')->andReturnSelf();
        DB::shouldReceive('table')->with('failed_jobs')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(5, 2, 1, 0, 0); // pending, total failed, 24h failed, 1h failed, stalled
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('whereNotNull')->andReturnSelf();
        
        $metrics = $this->service->getQueueHealthMetrics();
        
        $this->assertTrue($metrics['queue_tables_exist']);
        $this->assertArrayHasKey('job_statistics', $metrics);
        $this->assertArrayHasKey('overall_status', $metrics);
        $this->assertArrayHasKey('health_message', $metrics);
        $this->assertArrayHasKey('recommendations', $metrics);
    }

    /** @test */
    public function it_handles_missing_queue_tables(): void
    {
        // Mock DB facade to return schema builder
        DB::shouldReceive('getSchemaBuilder')->andReturnSelf();
        DB::shouldReceive('hasTable')->andReturn(false);
        
        $metrics = $this->service->getQueueHealthMetrics();
        
        $this->assertFalse($metrics['queue_tables_exist']);
        $this->assertEquals('error', $metrics['status']);
        $this->assertStringContainsString('Queue tables not found', $metrics['message']);
    }

    /** @test */
    public function it_handles_queue_health_metrics_errors(): void
    {
        // Mock DB facade to return true for table existence but throw exception on table queries
        DB::shouldReceive('getSchemaBuilder')->andReturnSelf();
        DB::shouldReceive('hasTable')->andReturn(true);
        DB::shouldReceive('table')->andThrow(new Exception('Database connection failed'));
        
        $metrics = $this->service->getQueueHealthMetrics();
        
        $this->assertEquals('error', $metrics['status']);
        $this->assertStringContainsString('Error retrieving queue health metrics', $metrics['message']);
        $this->assertEquals('Database connection failed', $metrics['error']);
    }

    /** @test */
    public function it_can_cleanup_old_test_jobs(): void
    {
        Carbon::setTestNow(Carbon::now());
        
        // Create test job index with old and new jobs
        $oldJobId = 'test_old_job';
        $newJobId = 'test_new_job';
        
        $jobIndex = [
            [
                'job_id' => $oldJobId,
                'created_at' => Carbon::now()->subHours(25)->toISOString(), // Older than 24 hours
            ],
            [
                'job_id' => $newJobId,
                'created_at' => Carbon::now()->subHours(1)->toISOString(), // Recent
            ],
        ];
        
        Cache::put('test_queue_job_index', $jobIndex, 3600);
        
        // Put old job data in cache
        Cache::put('test_queue_job_' . $oldJobId, ['status' => 'completed'], 3600);
        Cache::put('test_queue_job_' . $newJobId, ['status' => 'pending'], 3600);
        
        $cleaned = $this->service->cleanupOldTestJobs(24);
        
        $this->assertEquals(1, $cleaned);
        
        // Verify old job was removed
        $this->assertNull(Cache::get('test_queue_job_' . $oldJobId));
        
        // Verify new job still exists
        $this->assertNotNull(Cache::get('test_queue_job_' . $newJobId));
        
        // Verify index was updated
        $updatedIndex = Cache::get('test_queue_job_index');
        $this->assertCount(1, $updatedIndex);
        $this->assertEquals($newJobId, $updatedIndex[0]['job_id']);
    }

    /** @test */
    public function it_handles_cleanup_with_empty_index(): void
    {
        // No job index in cache
        $cleaned = $this->service->cleanupOldTestJobs();
        
        $this->assertEquals(0, $cleaned);
    }

    /** @test */
    public function it_handles_cleanup_errors(): void
    {
        // Mock Cache to throw exception on get
        Cache::shouldReceive('get')->andThrow(new Exception('Cache error'));
        
        $cleaned = $this->service->cleanupOldTestJobs();
        
        $this->assertEquals(0, $cleaned);
    }

    /** @test */
    public function it_limits_job_index_size(): void
    {
        Queue::fake();
        
        // Create more than 100 jobs to test index limiting
        $initialIndex = [];
        for ($i = 0; $i < 105; $i++) {
            $initialIndex[] = [
                'job_id' => "test_job_$i",
                'created_at' => Carbon::now()->subMinutes($i)->toISOString(),
            ];
        }
        
        Cache::put('test_queue_job_index', $initialIndex, 3600);
        
        // Dispatch a new job
        $this->service->dispatchTestJob();
        
        // Verify index is limited to 100 entries
        $updatedIndex = Cache::get('test_queue_job_index');
        $this->assertLessThanOrEqual(100, count($updatedIndex));
    }

    /** @test */
    public function it_assesses_critical_queue_health(): void
    {
        DB::shouldReceive('getSchemaBuilder')->andReturnSelf();
        DB::shouldReceive('hasTable')->andReturn(true);
        
        // Mock high failure rates
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0, 10, 8, 6, 4); // pending, total failed, 24h failed, 1h failed, stalled
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('whereNotNull')->andReturnSelf();
        
        $metrics = $this->service->getQueueHealthMetrics();
        
        $this->assertEquals('critical', $metrics['overall_status']);
        $this->assertStringContainsString('critical issues', $metrics['health_message']);
        $this->assertContains('Check if queue worker is running: php artisan queue:work', $metrics['recommendations']);
    }

    /** @test */
    public function it_assesses_healthy_queue_status(): void
    {
        DB::shouldReceive('getSchemaBuilder')->andReturnSelf();
        DB::shouldReceive('hasTable')->andReturn(true);
        
        // Mock healthy metrics
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(5, 0, 0, 0, 0); // pending, total failed, 24h failed, 1h failed, stalled
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('whereNotNull')->andReturnSelf();
        
        $metrics = $this->service->getQueueHealthMetrics();
        
        $this->assertEquals('healthy', $metrics['overall_status']);
        $this->assertStringContainsString('functioning normally', $metrics['health_message']);
    }

    /** @test */
    public function it_logs_job_dispatch_events(): void
    {
        Queue::fake();
        Log::shouldReceive('info')->once()->with(
            'Test queue job dispatched',
            \Mockery::type('array')
        );
        
        $this->service->dispatchTestJob();
    }

    /** @test */
    public function it_logs_cleanup_events(): void
    {
        Log::shouldReceive('info')->once()->with(
            'Test job cleanup completed',
            \Mockery::type('array')
        );
        
        $this->service->cleanupOldTestJobs();
    }

    /** @test */
    public function it_includes_test_job_statistics_in_health_metrics(): void
    {
        DB::shouldReceive('getSchemaBuilder')->andReturnSelf();
        DB::shouldReceive('hasTable')->andReturn(true);
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('whereNotNull')->andReturnSelf();
        
        // Create test job index
        $jobIndex = [
            [
                'job_id' => 'test_recent',
                'created_at' => Carbon::now()->subMinutes(30)->toISOString(),
            ],
            [
                'job_id' => 'test_old',
                'created_at' => Carbon::now()->subHours(2)->toISOString(),
            ],
        ];
        
        Cache::put('test_queue_job_index', $jobIndex, 3600);
        
        $metrics = $this->service->getQueueHealthMetrics();
        
        $this->assertArrayHasKey('test_job_statistics', $metrics);
        $this->assertEquals(2, $metrics['test_job_statistics']['total_test_jobs']);
        $this->assertEquals(1, $metrics['test_job_statistics']['test_jobs_1h']);
        $this->assertEquals(2, $metrics['test_job_statistics']['test_jobs_24h']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}