<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\FileDownloadRateLimitMiddleware;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class FileDownloadRateLimitMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private FileDownloadRateLimitMiddleware $middleware;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new FileDownloadRateLimitMiddleware();
        $this->user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Clear rate limiter state
        RateLimiter::clear('file_download:' . $this->user->id . ':127.0.0.1:test');
    }

    /** @test */
    public function it_allows_requests_within_rate_limit()
    {
        $request = $this->createRequest();
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, '5', '1');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_blocks_requests_exceeding_rate_limit()
    {
        $request = $this->createRequest();
        
        // Make requests up to the limit
        for ($i = 0; $i < 5; $i++) {
            $this->middleware->handle($request, function ($req) {
                return new Response('Success', 200);
            }, '5', '1');
        }

        // The next request should be blocked
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, '5', '1');

        $this->assertEquals(429, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('rate_limit_exceeded', $responseData['error_type']);
        $this->assertArrayHasKey('retry_after', $responseData);
    }

    /** @test */
    public function it_adds_rate_limit_headers_to_responses()
    {
        $request = $this->createRequest();
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, '10', '1');

        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
        $this->assertEquals('10', $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals('9', $response->headers->get('X-RateLimit-Remaining'));
    }

    /** @test */
    public function it_adds_retry_after_header_when_rate_limited()
    {
        $request = $this->createRequest();
        
        // Exceed rate limit
        for ($i = 0; $i < 6; $i++) {
            $response = $this->middleware->handle($request, function ($req) {
                return new Response('Success', 200);
            }, '5', '1');
        }

        $this->assertTrue($response->headers->has('Retry-After'));
        $this->assertTrue($response->headers->has('X-RateLimit-Reset'));
    }

    /** @test */
    public function it_logs_rate_limit_violations()
    {
        Log::fake();
        
        $request = $this->createRequest();
        
        // Exceed rate limit
        for ($i = 0; $i < 6; $i++) {
            $this->middleware->handle($request, function ($req) {
                return new Response('Success', 200);
            }, '5', '1');
        }

        Log::assertLogged('warning', function ($message, $context) {
            return str_contains($message, 'File download rate limit exceeded') &&
                   $context['user_id'] === $this->user->id &&
                   $context['ip_address'] === '127.0.0.1';
        });
    }

    /** @test */
    public function it_logs_to_security_channel()
    {
        Log::fake();
        
        $request = $this->createRequest();
        
        // Exceed rate limit
        for ($i = 0; $i < 6; $i++) {
            $this->middleware->handle($request, function ($req) {
                return new Response('Success', 200);
            }, '5', '1');
        }

        Log::channel('security')->assertLogged('warning', function ($message, $context) {
            return str_contains($message, 'Rate limit exceeded for file downloads') &&
                   $context['user'] === $this->user->email &&
                   $context['ip'] === '127.0.0.1';
        });
    }

    /** @test */
    public function it_creates_unique_keys_for_different_users()
    {
        $user1 = $this->user;
        $user2 = User::factory()->create(['role' => UserRole::ADMIN]);

        $request1 = $this->createRequestForUser($user1);
        $request2 = $this->createRequestForUser($user2);

        // User 1 exceeds limit
        for ($i = 0; $i < 5; $i++) {
            $this->middleware->handle($request1, function ($req) {
                return new Response('Success', 200);
            }, '5', '1');
        }

        $response1 = $this->middleware->handle($request1, function ($req) {
            return new Response('Success', 200);
        }, '5', '1');

        // User 2 should still be allowed
        $response2 = $this->middleware->handle($request2, function ($req) {
            return new Response('Success', 200);
        }, '5', '1');

        $this->assertEquals(429, $response1->getStatusCode());
        $this->assertEquals(200, $response2->getStatusCode());
    }

    /** @test */
    public function it_creates_unique_keys_for_different_ips()
    {
        $request1 = $this->createRequest('192.168.1.1');
        $request2 = $this->createRequest('192.168.1.2');

        // IP 1 exceeds limit
        for ($i = 0; $i < 5; $i++) {
            $this->middleware->handle($request1, function ($req) {
                return new Response('Success', 200);
            }, '5', '1');
        }

        $response1 = $this->middleware->handle($request1, function ($req) {
            return new Response('Success', 200);
        }, '5', '1');

        // IP 2 should still be allowed
        $response2 = $this->middleware->handle($request2, function ($req) {
            return new Response('Success', 200);
        }, '5', '1');

        $this->assertEquals(429, $response1->getStatusCode());
        $this->assertEquals(200, $response2->getStatusCode());
    }

    /** @test */
    public function it_handles_guest_users()
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('User-Agent', 'Test Browser');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, '5', '1');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_uses_default_parameters_when_not_specified()
    {
        $request = $this->createRequest();
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
    }

    private function createRequest(string $ip = '127.0.0.1'): Request
    {
        return $this->createRequestForUser($this->user, $ip);
    }

    private function createRequestForUser(User $user, string $ip = '127.0.0.1'): Request
    {
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);
        $request->server->set('REMOTE_ADDR', $ip);
        $request->headers->set('User-Agent', 'Test Browser');
        
        return $request;
    }
}