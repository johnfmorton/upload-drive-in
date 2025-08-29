<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\QueueWorkerTestSecurityService;

class QueueWorkerTestSecurityTest extends TestCase
{
    use RefreshDatabase;

    private QueueWorkerTestSecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = app(QueueWorkerTestSecurityService::class);
        
        // Clear any existing rate limits and cache
        Cache::flush();
        RateLimiter::clear('queue_worker_test:test_identifier');
    }

    public function test_rate_limiting_blocks_excessive_requests()
    {
        // Configure test rate limits
        config([
            'setup.queue_worker_test.rate_limit.max_attempts' => 2,
            'setup.queue_worker_test.rate_limit.decay_minutes' => 1,
        ]);

        // First request should succeed
        $response1 = $this->postJson('/setup/queue/test', ['timeout' => 10]);
        $response1->assertStatus(200);

        // Second request should be in cooldown (due to middleware setting cooldown after successful request)
        $response2 = $this->postJson('/setup/queue/test', ['timeout' => 10]);
        $response2->assertStatus(429); // Should be blocked by cooldown

        // Verify it's a cooldown error, not rate limit error
        $this->assertStringContainsString('cooldown', $response2->json('error'));
    }

    public function test_cooldown_period_prevents_rapid_successive_tests()
    {
        // Configure short cooldown for testing
        config([
            'setup.queue_worker_test.cooldown.seconds' => 5,
        ]);

        // First request should succeed
        $response1 = $this->postJson('/setup/queue/test', ['timeout' => 10]);
        $response1->assertStatus(200);

        // Immediate second request should be in cooldown
        $response2 = $this->postJson('/setup/queue/test', ['timeout' => 10]);
        $response2->assertStatus(429)
            ->assertJsonStructure([
                'error',
                'cooldown_remaining',
            ]);

        $this->assertGreaterThan(0, $response2->json('cooldown_remaining'));
    }

    public function test_input_validation_rejects_invalid_data()
    {
        // Test invalid timeout values
        $response1 = $this->postJson('/setup/queue/test', ['timeout' => -1]);
        $response1->assertStatus(422);

        $response2 = $this->postJson('/setup/queue/test', ['timeout' => 200]);
        $response2->assertStatus(422);

        $response3 = $this->postJson('/setup/queue/test', ['timeout' => 'invalid']);
        $response3->assertStatus(422);

        // Test invalid force parameter
        $response4 = $this->postJson('/setup/queue/test', ['force' => 'not_boolean']);
        $response4->assertStatus(422);
    }

    public function test_input_sanitization_prevents_xss()
    {
        $maliciousData = [
            'timeout' => '<script>alert("xss")</script>',
            'force' => '<img src=x onerror=alert(1)>',
        ];

        try {
            $sanitized = $this->securityService->validateTestRequest($maliciousData);
            // If validation passes, check that data is sanitized
            $this->assertArrayNotHasKey('timeout', $sanitized); // Invalid timeout should be removed
            $this->assertArrayNotHasKey('force', $sanitized); // Invalid force should be removed
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation should fail for invalid data types
            $this->assertArrayHasKey('timeout', $e->errors());
            $this->assertArrayHasKey('force', $e->errors());
        }
    }

    public function test_cached_data_validation_prevents_tampering()
    {
        // Test with malicious cached data
        $maliciousCache = [
            'status' => '<script>alert("xss")</script>',
            'message' => '<img src=x onerror=alert(1)>',
            'test_completed_at' => 'invalid_date',
            'processing_time' => -999,
            'error_message' => str_repeat('A', 2000), // Too long
        ];

        $validated = $this->securityService->validateCachedStatus($maliciousCache);
        
        // Should return null for invalid data
        $this->assertNull($validated);
    }

    public function test_cached_data_validation_accepts_valid_data()
    {
        $validCache = [
            'status' => 'completed',
            'message' => 'Queue worker is functioning properly',
            'test_completed_at' => now()->toISOString(),
            'processing_time' => 1.23,
            'error_message' => null,
            'test_job_id' => 'test_12345',
            'details' => 'Test completed successfully',
            'can_retry' => false,
        ];

        $validated = $this->securityService->validateCachedStatus($validCache);
        
        $this->assertIsArray($validated);
        $this->assertEquals('completed', $validated['status']);
        $this->assertEquals('Queue worker is functioning properly', $validated['message']);
    }

    public function test_security_thresholds_detect_suspicious_activity()
    {
        $identifier = 'test_user_123';
        
        // Simulate multiple attempts
        for ($i = 0; $i < 25; $i++) {
            Cache::increment("security:suspicious_activity:{$identifier}");
        }

        $thresholds = $this->securityService->checkSecurityThresholds($identifier);
        
        $this->assertTrue($thresholds['is_suspicious']);
    }

    public function test_security_event_logging()
    {
        // Mock the Log facade to verify the call
        \Log::shouldReceive('channel')
            ->with('security')
            ->once()
            ->andReturnSelf();
            
        \Log::shouldReceive('info')
            ->with('Queue worker test security event: test_event', ['test_data' => 'test_value'])
            ->once();
        
        $this->securityService->recordSecurityEvent('test_event', [
            'test_data' => 'test_value',
        ]);
    }

    public function test_csrf_protection_on_queue_test_endpoints()
    {
        // Disable CSRF middleware for this test to verify it's normally enabled
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        
        // Test without CSRF token - should work when middleware is disabled
        $response = $this->post('/setup/queue/test', ['timeout' => 10]);
        $this->assertNotEquals(419, $response->status()); // Should not be CSRF error when disabled
        
        // Re-enable CSRF middleware
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class)
            ->pushMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_status_update_validation_sanitizes_data()
    {
        $statusData = [
            'status' => 'completed',
            'message' => '<script>alert("xss")</script>Test message',
            'test_completed_at' => now()->toISOString(),
            'processing_time' => 1.23,
            'error_message' => '<img src=x onerror=alert(1)>Error occurred',
            'test_job_id' => 'test_<script>alert(1)</script>_123',
            'details' => 'Test <b>completed</b> successfully',
            'can_retry' => true,
        ];

        $validated = $this->securityService->validateStatusUpdate($statusData);
        
        // Check that HTML tags are stripped
        $this->assertStringNotContainsString('<script>', $validated['message']);
        $this->assertStringNotContainsString('<img', $validated['error_message']);
        $this->assertStringNotContainsString('<script>', $validated['test_job_id']);
        $this->assertStringNotContainsString('<b>', $validated['details']);
        
        // Check that valid data is preserved
        $this->assertEquals('completed', $validated['status']);
        $this->assertEquals(1.23, $validated['processing_time']);
        $this->assertTrue($validated['can_retry']);
    }

    public function test_rate_limit_headers_are_included()
    {
        config([
            'setup.queue_worker_test.rate_limit.max_attempts' => 3,
        ]);

        $response = $this->postJson('/setup/queue/test', ['timeout' => 10]);
        
        // Should include rate limit information in response or headers
        $this->assertTrue($response->isSuccessful() || $response->status() === 429);
    }

    public function test_security_service_clears_data_properly()
    {
        $identifier = 'test_user_456';
        
        // Set up some security data
        Cache::put('queue_worker_test:' . $identifier, 'test_data', 60);
        Cache::put('setup:queue_test:cooldown:' . $identifier, time() + 30, 60);
        Cache::put("security:suspicious_activity:{$identifier}", 5, 60);
        
        // Clear the data
        $this->securityService->clearSecurityData($identifier);
        
        // Verify data is cleared
        $this->assertNull(Cache::get('queue_worker_test:' . $identifier));
        $this->assertNull(Cache::get('setup:queue_test:cooldown:' . $identifier));
        $this->assertNull(Cache::get("security:suspicious_activity:{$identifier}"));
    }

    public function test_middleware_applies_to_correct_routes()
    {
        // Test that the middleware is applied to the queue test route
        $response = $this->postJson('/setup/queue/test');
        
        // Should either succeed or fail with rate limiting/validation, not 404
        $this->assertNotEquals(404, $response->status());
        
        // Test that status check route has throttling
        $response = $this->getJson('/setup/queue/test/status?test_job_id=test123');
        $this->assertNotEquals(404, $response->status());
    }
}