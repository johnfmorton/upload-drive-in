<?php

namespace Tests\Feature;

use App\Services\QueueTestService;
use App\Services\QueueWorkerStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Test enhanced error messaging and troubleshooting guidance in queue worker functionality.
 */
class QueueWorkerEnhancedErrorMessagingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we have a clean cache state
        Cache::flush();
    }

    public function test_queue_worker_status_endpoint_returns_enhanced_error_details()
    {
        // Cache a failed status with troubleshooting steps
        $failedStatus = QueueWorkerStatus::configurationError('Invalid queue driver configuration');
        Cache::put(QueueWorkerStatus::CACHE_KEY, $failedStatus->toArray(), 3600);

        $response = $this->getJson('/setup/queue-worker/status');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'queue_worker' => [
                        'status' => 'failed',
                        'message' => 'Queue configuration error',
                        'error_message' => 'Invalid queue driver configuration',
                        'troubleshooting' => [
                            'Verify queue configuration in .env file (QUEUE_CONNECTION)',
                            'Check if database tables exist: php artisan migrate',
                            'Ensure queue driver is properly configured (database, redis, etc.)',
                            'Check application logs for configuration errors',
                            'Verify file permissions for storage and cache directories',
                            'Test database connection: php artisan tinker, then DB::connection()->getPdo()',
                            'For Redis queue: ensure Redis server is running and accessible'
                        ],
                        'can_retry' => true
                    ]
                ]
            ]);
    }

    public function test_queue_worker_test_endpoint_returns_enhanced_error_on_failure()
    {
        // Mock a configuration that will cause dispatch to fail
        config(['queue.default' => 'invalid_driver']);

        $response = $this->postJson('/setup/queue/test', [
            'delay' => 0
        ]);

        // Should return error response with troubleshooting guidance
        $response->assertStatus(500)
            ->assertJsonStructure([
                'success',
                'message',
                'error' => [
                    'type',
                    'message',
                    'troubleshooting'
                ]
            ]);

        $responseData = $response->json();
        $this->assertFalse($responseData['success']);
        $this->assertIsArray($responseData['error']['troubleshooting']);
        $this->assertNotEmpty($responseData['error']['troubleshooting']);
    }

    public function test_queue_worker_status_includes_specific_error_types()
    {
        $errorTypes = [
            'configuration' => QueueWorkerStatus::configurationError('Config error'),
            'database' => QueueWorkerStatus::databaseError('DB error'),
            'permission' => QueueWorkerStatus::permissionError('Permission error'),
            'worker_not_running' => QueueWorkerStatus::workerNotRunning('test_123'),
            'worker_stuck' => QueueWorkerStatus::workerStuck('test_456'),
        ];

        foreach ($errorTypes as $type => $status) {
            Cache::put(QueueWorkerStatus::CACHE_KEY, $status->toArray(), 3600);

            $response = $this->getJson('/setup/queue-worker/status');

            $response->assertOk();
            $data = $response->json('data.queue_worker');

            $this->assertArrayHasKey('troubleshooting', $data);
            $this->assertIsArray($data['troubleshooting']);
            $this->assertNotEmpty($data['troubleshooting']);
            $this->assertTrue($data['can_retry']);

            // Verify troubleshooting steps are relevant to error type
            $troubleshooting = $data['troubleshooting'];
            switch ($type) {
                case 'configuration':
                    $this->assertContains('Verify queue configuration in .env file (QUEUE_CONNECTION)', $troubleshooting);
                    break;
                case 'database':
                    $this->assertContains('Verify database connection settings in .env file', $troubleshooting);
                    break;
                case 'permission':
                    $this->assertContains('Check file permissions on storage directory: chmod -R 755 storage', $troubleshooting);
                    break;
                case 'worker_not_running':
                    $this->assertContains('Start the queue worker: php artisan queue:work', $troubleshooting);
                    break;
                case 'worker_stuck':
                    $this->assertContains('Restart the queue worker: php artisan queue:restart', $troubleshooting);
                    break;
            }
        }
    }

    public function test_setup_instructions_page_includes_enhanced_error_information()
    {
        $response = $this->get('/setup/instructions');

        $response->assertOk();
        
        // Check for enhanced error handling information
        $response->assertSee('Test Failure Scenarios:');
        $response->assertSee('Configuration Error:');
        $response->assertSee('Database Error:');
        $response->assertSee('Permission Error:');
        $response->assertSee('Worker Not Running:');
        $response->assertSee('Worker Stuck:');
        $response->assertSee('Network Error:');
        
        // Check for manual verification guide
        $response->assertSee('Manual Queue Worker Verification');
        $response->assertSee('Check if Queue Worker is Running');
        $response->assertSee('Check Failed Jobs');
        $response->assertSee('Test Database Connection');
        $response->assertSee('Check Application Logs');
        
        // Check for troubleshooting commands
        $response->assertSee('ps aux | grep "queue:work"');
        $response->assertSee('php artisan queue:failed');
        $response->assertSee('php artisan tinker');
        $response->assertSee('tail -f storage/logs/laravel.log');
    }

    public function test_queue_worker_status_refresh_endpoint_excludes_queue_worker()
    {
        $response = $this->getJson('/setup/status/refresh');

        $response->assertOk();
        $data = $response->json('data.statuses');

        // Should not include queue_worker in general status refresh
        $this->assertArrayNotHasKey('queue_worker', $data);
        
        // Should include other status steps
        $this->assertArrayHasKey('database', $data);
        $this->assertArrayHasKey('mail', $data);
        $this->assertArrayHasKey('google_drive', $data);
        $this->assertArrayHasKey('migrations', $data);
        $this->assertArrayHasKey('admin_user', $data);
    }

    public function test_error_details_are_properly_escaped_in_response()
    {
        // Create status with potentially dangerous content
        $maliciousError = '<script>alert("xss")</script>';
        $failedStatus = QueueWorkerStatus::failed($maliciousError);
        Cache::put(QueueWorkerStatus::CACHE_KEY, $failedStatus->toArray(), 3600);

        $response = $this->getJson('/setup/queue-worker/status');

        $response->assertOk();
        $data = $response->json('data.queue_worker');

        // Error message should be returned as-is (escaping happens on frontend)
        $this->assertEquals($maliciousError, $data['error_message']);
        
        // But response should be valid JSON
        $this->assertIsArray($data);
        $this->assertArrayHasKey('troubleshooting', $data);
    }

    public function test_troubleshooting_steps_are_comprehensive_and_actionable()
    {
        $errorTypes = [
            QueueWorkerStatus::configurationError('test'),
            QueueWorkerStatus::databaseError('test'),
            QueueWorkerStatus::permissionError('test'),
            QueueWorkerStatus::workerNotRunning(),
            QueueWorkerStatus::workerStuck(),
        ];

        foreach ($errorTypes as $status) {
            Cache::put(QueueWorkerStatus::CACHE_KEY, $status->toArray(), 3600);

            $response = $this->getJson('/setup/queue-worker/status');
            $troubleshooting = $response->json('data.queue_worker.troubleshooting');

            // Each troubleshooting step should be actionable
            foreach ($troubleshooting as $step) {
                $this->assertIsString($step);
                $this->assertNotEmpty($step);
                
                // Should contain actionable commands or clear instructions
                $this->assertTrue(
                    str_contains($step, 'php artisan') ||
                    str_contains($step, 'Check') ||
                    str_contains($step, 'Verify') ||
                    str_contains($step, 'Ensure') ||
                    str_contains($step, 'Start') ||
                    str_contains($step, 'Restart') ||
                    str_contains($step, 'chmod') ||
                    str_contains($step, 'chown') ||
                    str_contains($step, 'ps aux'),
                    "Troubleshooting step should be actionable: {$step}"
                );
            }

            // Should have multiple steps for comprehensive guidance
            $this->assertGreaterThanOrEqual(3, count($troubleshooting));
        }
    }

    public function test_retry_functionality_is_available_for_all_error_types()
    {
        $errorStatuses = [
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

        foreach ($errorStatuses as $status) {
            Cache::put(QueueWorkerStatus::CACHE_KEY, $status->toArray(), 3600);

            $response = $this->getJson('/setup/queue-worker/status');
            $data = $response->json('data.queue_worker');

            $this->assertTrue($data['can_retry'], "Status {$status->status} should allow retry");
        }
    }

    public function test_successful_status_hides_troubleshooting_information()
    {
        $successStatus = QueueWorkerStatus::completed(1.23, 'test_job_123');
        Cache::put(QueueWorkerStatus::CACHE_KEY, $successStatus->toArray(), 3600);

        $response = $this->getJson('/setup/queue-worker/status');

        $response->assertOk();
        $data = $response->json('data.queue_worker');

        $this->assertEquals('completed', $data['status']);
        $this->assertNull($data['error_message']);
        $this->assertNull($data['troubleshooting']);
        $this->assertTrue($data['can_retry']); // Can still retry to test again
    }
}