<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\TokenRefreshRateLimit;
use App\Models\User;
use App\Services\TokenSecurityService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;
use Mockery;

class TokenRefreshRateLimitTest extends TestCase
{
    private TokenSecurityService $securityService;
    private TokenRefreshRateLimit $middleware;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->securityService = Mockery::mock(TokenSecurityService::class);
        $this->middleware = new TokenRefreshRateLimit($this->securityService);
        $this->user = User::factory()->make(['id' => 1]);
    }

    public function test_allows_request_when_under_rate_limit(): void
    {
        $request = Request::create('/test');
        $request->setUserResolver(fn() => $this->user);
        
        $this->securityService->shouldReceive('checkUserRateLimit')
            ->with($this->user)
            ->once()
            ->andReturn(true);
            
        $this->securityService->shouldReceive('checkIpRateLimit')
            ->once()
            ->andReturn(true);
            
        $this->securityService->shouldReceive('recordRefreshAttempt')
            ->with($this->user)
            ->once();
            
        $this->securityService->shouldReceive('getRemainingUserAttempts')
            ->with($this->user)
            ->once()
            ->andReturn(4);
            
        $this->securityService->shouldReceive('getRateLimitResetTime')
            ->with($this->user)
            ->once()
            ->andReturn(now()->addHour());

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('5', $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals('4', $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_blocks_request_when_user_rate_limit_exceeded(): void
    {
        $request = Request::create('/test');
        $request->setUserResolver(fn() => $this->user);
        
        $this->securityService->shouldReceive('checkUserRateLimit')
            ->with($this->user)
            ->once()
            ->andReturn(false);
            
        $this->securityService->shouldReceive('getRateLimitResetTime')
            ->with($this->user)
            ->once()
            ->andReturn(now()->addMinutes(30));

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(429, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Rate limit exceeded', $responseData['error']);
        $this->assertEquals(0, $responseData['remaining_attempts']);
    }

    public function test_blocks_request_when_ip_rate_limit_exceeded(): void
    {
        $request = Request::create('/test');
        $request->setUserResolver(fn() => $this->user);
        
        $this->securityService->shouldReceive('checkUserRateLimit')
            ->with($this->user)
            ->once()
            ->andReturn(true);
            
        $this->securityService->shouldReceive('checkIpRateLimit')
            ->once()
            ->andReturn(false);
            
        $this->securityService->shouldReceive('getRemainingIpAttempts')
            ->once()
            ->andReturn(0);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(429, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('IP rate limit exceeded', $responseData['error']);
        $this->assertEquals(0, $responseData['remaining_attempts']);
    }

    public function test_returns_401_when_user_not_authenticated(): void
    {
        $request = Request::create('/test');
        $request->setUserResolver(fn() => null);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Authentication required', $responseData['error']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}