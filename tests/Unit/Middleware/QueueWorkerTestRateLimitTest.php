<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Http\Middleware\QueueWorkerTestRateLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class QueueWorkerTestRateLimitTest extends TestCase
{
    private QueueWorkerTestRateLimit $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new QueueWorkerTestRateLimit();
        
        // Clear cache and rate limiter
        Cache::flush();
        RateLimiter::clear('queue_worker_test:test_identifier');
        
        // Set test configuration
        config([
            'setup.queue_worker_test.rate_limit.max_attempts' => 3,
            'setup.queue_worker_test.rate_limit.decay_minutes' => 5,
            'setup.queue_worker_test.cooldown.seconds' => 30,
            'setup.queue_worker_test.cache_keys.cooldown' => 'setup:queue_test:cooldown:',
        ]);
    }

    public function test_allows_request_when_no_rate_limit_exceeded()
    {
        $request = Request::create('/test', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('User-Agent', 'Test Browser');

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function test_blocks_request_when_in_cooldown()
    {
        $request = Request::create('/test', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('User-Agent', 'Test Browser');

        // Set cooldown
        $identifier = hash('sha256', '127.0.0.1|Test Browser');
        $cooldownKey = 'setup:queue_test:cooldown:' . $identifier;
        Cache::put($cooldownKey, time() + 30, 30);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(429, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('cooldown period', $data['error']);
        $this->assertArrayHasKey('cooldown_remaining', $data);
        $this->assertGreaterThan(0, $data['cooldown_remaining']);
    }

    public function test_blocks_request_when_rate_limit_exceeded()
    {
        $request = Request::create('/test', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('User-Agent', 'Test Browser');

        $identifier = hash('sha256', '127.0.0.1|Test Browser');
        $rateLimitKey = 'queue_worker_test:' . $identifier;

        // Exceed rate limit
        for ($i = 0; $i < 4; $i++) {
            RateLimiter::hit($rateLimitKey, 5 * 60);
        }

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(429, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Too many queue worker test attempts', $data['error']);
        $this->assertArrayHasKey('retry_after', $data);
        $this->assertArrayHasKey('max_attempts', $data);
        $this->assertEquals(3, $data['max_attempts']);
    }

    public function test_increments_rate_limit_counter_on_allowed_request()
    {
        $request = Request::create('/test', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('User-Agent', 'Test Browser');

        $identifier = hash('sha256', '127.0.0.1|Test Browser');
        $rateLimitKey = 'queue_worker_test:' . $identifier;

        // Verify no attempts initially
        $this->assertEquals(0, RateLimiter::attempts($rateLimitKey));

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        // Should increment counter
        $this->assertEquals(1, RateLimiter::attempts($rateLimitKey));
    }

    public function test_sets_cooldown_after_successful_request()
    {
        $request = Request::create('/test', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('User-Agent', 'Test Browser');

        $identifier = hash('sha256', '127.0.0.1|Test Browser');
        $cooldownKey = 'setup:queue_test:cooldown:' . $identifier;

        // Verify no cooldown initially
        $this->assertNull(Cache::get($cooldownKey));

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        // Should set cooldown after successful response
        $this->assertNotNull(Cache::get($cooldownKey));
        $this->assertGreaterThan(time(), Cache::get($cooldownKey));
    }

    public function test_does_not_set_cooldown_on_failed_request()
    {
        $request = Request::create('/test', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('User-Agent', 'Test Browser');

        $identifier = hash('sha256', '127.0.0.1|Test Browser');
        $cooldownKey = 'setup:queue_test:cooldown:' . $identifier;

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['error' => 'Test failed'], 500);
        });

        // Should not set cooldown for failed response
        $this->assertNull(Cache::get($cooldownKey));
    }

    public function test_generates_consistent_identifier_for_same_request()
    {
        $request1 = Request::create('/test', 'POST');
        $request1->server->set('REMOTE_ADDR', '127.0.0.1');
        $request1->headers->set('User-Agent', 'Test Browser');

        $request2 = Request::create('/test', 'POST');
        $request2->server->set('REMOTE_ADDR', '127.0.0.1');
        $request2->headers->set('User-Agent', 'Test Browser');

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('getIdentifier');
        $method->setAccessible(true);

        $identifier1 = $method->invoke($this->middleware, $request1);
        $identifier2 = $method->invoke($this->middleware, $request2);

        $this->assertEquals($identifier1, $identifier2);
    }

    public function test_generates_different_identifiers_for_different_requests()
    {
        $request1 = Request::create('/test', 'POST');
        $request1->server->set('REMOTE_ADDR', '127.0.0.1');
        $request1->headers->set('User-Agent', 'Test Browser');

        $request2 = Request::create('/test', 'POST');
        $request2->server->set('REMOTE_ADDR', '192.168.1.1');
        $request2->headers->set('User-Agent', 'Different Browser');

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('getIdentifier');
        $method->setAccessible(true);

        $identifier1 = $method->invoke($this->middleware, $request1);
        $identifier2 = $method->invoke($this->middleware, $request2);

        $this->assertNotEquals($identifier1, $identifier2);
    }

    public function test_cooldown_remaining_calculation()
    {
        $request = Request::create('/test', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('User-Agent', 'Test Browser');

        $identifier = hash('sha256', '127.0.0.1|Test Browser');
        $cooldownKey = 'setup:queue_test:cooldown:' . $identifier;
        
        // Set cooldown to expire in 15 seconds
        Cache::put($cooldownKey, time() + 15, 30);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('getCooldownRemaining');
        $method->setAccessible(true);

        $remaining = $method->invoke($this->middleware, $identifier, config('setup.queue_worker_test'));

        $this->assertGreaterThan(10, $remaining);
        $this->assertLessThanOrEqual(15, $remaining);
    }

    public function test_expired_cooldown_returns_zero()
    {
        $request = Request::create('/test', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('User-Agent', 'Test Browser');

        $identifier = hash('sha256', '127.0.0.1|Test Browser');
        $cooldownKey = 'setup:queue_test:cooldown:' . $identifier;
        
        // Set expired cooldown
        Cache::put($cooldownKey, time() - 10, 30);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('getCooldownRemaining');
        $method->setAccessible(true);

        $remaining = $method->invoke($this->middleware, $identifier, config('setup.queue_worker_test'));

        $this->assertEquals(0, $remaining);
    }

    public function test_handles_missing_user_agent_gracefully()
    {
        $request = Request::create('/test', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        // No User-Agent header

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_handles_missing_ip_address_gracefully()
    {
        $request = Request::create('/test', 'POST');
        $request->headers->set('User-Agent', 'Test Browser');
        // No IP address

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}