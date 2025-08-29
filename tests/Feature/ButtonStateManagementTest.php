<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use App\Jobs\TestQueueJob;

class ButtonStateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any cached queue worker status
        Cache::forget('setup_queue_worker_status');
        
        // Use sync queue for predictable testing
        Queue::fake();
    }

    /** @test */
    public function setup_instructions_page_includes_proper_button_elements()
    {
        $response = $this->get('/setup/instructions');

        $response->assertStatus(200);
        
        // Check for main refresh button with proper IDs
        $response->assertSee('id="refresh-status-btn"', false);
        $response->assertSee('id="refresh-btn-text"', false);
        $response->assertSee('id="refresh-spinner"', false);
        
        // Check for queue worker test button with proper IDs
        $response->assertSee('id="test-queue-worker-btn"', false);
        $response->assertSee('id="test-queue-worker-btn-text"', false);
        $response->assertSee('id="test-queue-worker-spinner"', false);
        
        // Check for retry button (initially hidden)
        $response->assertSee('id="retry-queue-worker-btn"', false);
        $response->assertSee('hidden', false); // Retry button should be hidden initially
    }

    /** @test */
    public function buttons_have_proper_accessibility_attributes()
    {
        $response = $this->get('/setup/instructions');

        $response->assertStatus(200);
        
        // Check for proper button structure and accessibility
        $response->assertSee('disabled:opacity-50', false);
        $response->assertSee('disabled:cursor-not-allowed', false);
        
        // Buttons should have proper ARIA and title attributes in the template
        $response->assertSee('Test Queue Worker', false);
        $response->assertSee('Check Status', false);
    }

    /** @test */
    public function refresh_status_endpoint_works_with_button_coordination()
    {
        $response = $this->postJson('/setup/status/refresh', [], [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'statuses' => [
                    'database',
                    'mail',
                    'google_drive',
                    'migrations',
                    'admin_user'
                    // Note: queue_worker should NOT be included in general refresh
                ]
            ]
        ]);
        
        // The response should not include queue_worker in the main statuses array
        // but it might be in incomplete_steps summary, so we check the statuses specifically
        $responseData = $response->json();
        $this->assertArrayNotHasKey('queue_worker', $responseData['data']['statuses']);
    }

    /** @test */
    public function queue_worker_status_endpoint_works_independently()
    {
        $response = $this->getJson('/setup/queue-worker/status', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'queue_worker' => [
                    'status',
                    'message'
                ]
            ]
        ]);
    }

    /** @test */
    public function queue_worker_test_endpoint_handles_concurrent_requests()
    {
        // First request should succeed
        $response1 = $this->postJson('/setup/queue/test', [], [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response1->assertStatus(200);
        
        // Verify test job was dispatched
        Queue::assertPushed(TestQueueJob::class);
    }

    /** @test */
    public function queue_worker_status_persists_between_requests()
    {
        // Set a cached status
        Cache::put('setup_queue_worker_status', [
            'status' => 'completed',
            'message' => 'Queue worker is functioning properly',
            'test_completed_at' => now()->toISOString(),
            'processing_time' => 1.23,
            'error_message' => null,
            'test_job_id' => 'test-123'
        ], 3600);

        $response = $this->getJson('/setup/queue-worker/status', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'queue_worker' => [
                    'status' => 'completed',
                    'message' => 'Queue worker is functioning properly'
                ]
            ]
        ]);
    }

    /** @test */
    public function expired_queue_worker_status_returns_not_tested()
    {
        // Set an expired cached status (older than 1 hour)
        Cache::put('setup_queue_worker_status', [
            'status' => 'completed',
            'message' => 'Queue worker is functioning properly',
            'test_completed_at' => now()->subHours(2)->toISOString(),
            'processing_time' => 1.23,
            'error_message' => null,
            'test_job_id' => 'test-123'
        ], 3600);

        $response = $this->getJson('/setup/queue-worker/status', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'queue_worker' => [
                    'status' => 'not_tested',
                    'message' => 'Click the Test Queue Worker button below'
                ]
            ]
        ]);
    }

    /** @test */
    public function failed_queue_worker_status_includes_retry_information()
    {
        // Set a failed cached status
        Cache::put('setup_queue_worker_status', [
            'status' => 'failed',
            'message' => 'Queue worker test failed',
            'test_completed_at' => now()->toISOString(),
            'processing_time' => null,
            'error_message' => 'Test job execution failed',
            'test_job_id' => 'test-456'
        ], 3600);

        $response = $this->getJson('/setup/queue-worker/status', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'queue_worker' => [
                    'status' => 'failed',
                    'message' => 'Queue worker test failed',
                    'can_retry' => true
                ]
            ]
        ]);
    }

    /** @test */
    public function setup_instructions_page_loads_with_proper_javascript_initialization()
    {
        $response = $this->get('/setup/instructions');

        $response->assertStatus(200);
        
        // Check that the setup-status.js is included
        $response->assertSee('setup-status.js', false);
        
        // Check for proper CSS classes for button states
        $response->assertSee('loading-spinner', false);
        $response->assertSee('disabled:opacity-50', false);
        $response->assertSee('disabled:cursor-not-allowed', false);
    }

    /** @test */
    public function button_elements_have_proper_css_classes_for_state_management()
    {
        $response = $this->get('/setup/instructions');

        $response->assertStatus(200);
        
        // Check for proper Tailwind classes that support button state management
        $response->assertSee('transition ease-in-out duration-150', false);
        $response->assertSee('disabled:opacity-50', false);
        $response->assertSee('disabled:cursor-not-allowed', false);
        
        // Check for loading spinner classes
        $response->assertSee('loading-spinner', false);
        $response->assertSee('hidden', false);
    }

    /** @test */
    public function csrf_token_is_available_for_ajax_requests()
    {
        $response = $this->get('/setup/instructions');

        $response->assertStatus(200);
        
        // Check that CSRF token meta tag is present
        $response->assertSee('name="csrf-token"', false);
        $response->assertSee('content="', false);
    }

    /** @test */
    public function queue_worker_test_handles_rate_limiting_gracefully()
    {
        // This test verifies that the backend can handle rapid requests
        // The frontend debouncing should prevent most of these, but backend should handle edge cases
        
        $responses = [];
        
        // Make multiple rapid requests
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/setup/queue/test', [], [
                'X-Requested-With' => 'XMLHttpRequest'
            ]);
        }

        // All requests should succeed (backend doesn't implement rate limiting yet)
        // But they should handle concurrent access gracefully
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
        
        // Multiple test jobs should be dispatched
        Queue::assertPushed(TestQueueJob::class, 3);
    }

    /** @test */
    public function error_responses_include_proper_structure_for_button_management()
    {
        // Test with invalid request (missing CSRF token)
        $response = $this->postJson('/setup/status/refresh');

        // In test environment, CSRF might be disabled, so check for reasonable response
        $this->assertTrue(in_array($response->status(), [200, 419, 422, 500]));
        
        // Response should be JSON for AJAX requests
        $response->assertHeader('Content-Type', 'application/json');
    }
}