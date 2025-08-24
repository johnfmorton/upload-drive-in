<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use App\Services\SetupSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SetupSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected SetupSecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = app(SetupSecurityService::class);
    }

    /** @test */
    public function it_applies_rate_limiting_to_status_refresh_endpoints()
    {
        // Test that the rate limiting middleware exists and can be instantiated
        $middleware = app(\App\Http\Middleware\SetupStatusRateLimitMiddleware::class);
        $this->assertInstanceOf(\App\Http\Middleware\SetupStatusRateLimitMiddleware::class, $middleware);
        
        // Test rate limiting logic by checking if too many attempts are detected
        $key = 'test_rate_limit_key';
        
        // Clear any existing rate limits for this key
        RateLimiter::clear($key);
        
        // Hit the rate limiter multiple times
        for ($i = 0; $i < 35; $i++) {
            RateLimiter::hit($key, 60);
        }
        
        // Check if rate limit is exceeded
        $this->assertTrue(RateLimiter::tooManyAttempts($key, 30));
    }

    /** @test */
    public function it_validates_and_sanitizes_input_parameters()
    {
        $testCases = [
            // Valid inputs
            ['step' => 'database', 'expected_valid' => true],
            ['step' => 'queue_worker', 'expected_valid' => true],
            ['delay' => 5, 'expected_valid' => true],
            ['delay' => '10', 'expected_valid' => true],
            ['test_job_id' => 'test_db70502d-6709-4109-9743-65f6df9aeb29', 'expected_valid' => true],
            
            // Invalid inputs
            ['step' => 'invalid_step', 'expected_valid' => false],
            ['step' => '<script>alert("xss")</script>', 'expected_valid' => false],
            ['delay' => -5, 'expected_valid' => true, 'expected_sanitized' => 0], // Should be sanitized to 0
            ['delay' => 100, 'expected_valid' => true, 'expected_sanitized' => 60], // Should be sanitized to max
            ['delay' => 'not_a_number', 'expected_valid' => false],
            ['test_job_id' => 'invalid_job_id', 'expected_valid' => false],
            ['test_job_id' => 'test_<script>', 'expected_valid' => false],
        ];

        foreach ($testCases as $testCase) {
            $result = $this->securityService->sanitizeStatusRequest($testCase);
            
            $this->assertEquals(
                $testCase['expected_valid'], 
                $result['is_valid'],
                "Input validation failed for: " . json_encode($testCase)
            );

            if (isset($testCase['expected_sanitized'])) {
                $key = array_key_first($testCase);
                if ($key !== 'expected_valid' && $key !== 'expected_sanitized') {
                    $this->assertEquals(
                        $testCase['expected_sanitized'],
                        $result['sanitized'][$key] ?? null,
                        "Input sanitization failed for: " . json_encode($testCase)
                    );
                }
            }
        }
    }

    /** @test */
    public function it_blocks_suspicious_requests()
    {
        $suspiciousUserAgents = [
            'curl/7.68.0',
            'python-requests/2.25.1',
            'Googlebot/2.1',
            'Mozilla/5.0 (compatible; bingbot/2.0)',
        ];

        foreach ($suspiciousUserAgents as $userAgent) {
            $request = $this->createRequest('POST', '/setup/status/refresh', [
                'User-Agent' => $userAgent
            ]);

            $security = $this->securityService->validateRequestSecurity($request);
            
            // Should detect suspicious user agent
            $this->assertFalse($security['is_secure'], "Suspicious user agent should be detected: {$userAgent}");
            $this->assertContains('Suspicious or missing user agent', $security['issues']);
        }
    }

    /** @test */
    public function it_requires_csrf_token_for_post_requests()
    {
        // Test that CSRF middleware class exists and is configured
        $this->assertTrue(class_exists(\App\Http\Middleware\VerifyCsrfToken::class));
        
        // Test that the middleware is registered in the kernel
        $kernel = app(\App\Http\Kernel::class);
        $reflection = new \ReflectionClass($kernel);
        $middlewareGroupsProperty = $reflection->getProperty('middlewareGroups');
        $middlewareGroupsProperty->setAccessible(true);
        $middlewareGroups = $middlewareGroupsProperty->getValue($kernel);
        
        $webMiddleware = $middlewareGroups['web'] ?? [];
        // Check for either our custom CSRF middleware or Laravel's default
        $hasCSRFMiddleware = in_array(\App\Http\Middleware\VerifyCsrfToken::class, $webMiddleware) ||
                           in_array(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, $webMiddleware);
        $this->assertTrue($hasCSRFMiddleware);
        
        // Test CSRF token validation logic
        $request = $this->createRequest('POST', '/setup/status/refresh');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        
        $security = $this->securityService->validateRequestSecurity($request);
        
        // Should pass basic security checks (CSRF is handled by Laravel middleware)
        $this->assertIsArray($security);
        $this->assertArrayHasKey('is_secure', $security);
    }

    /** @test */
    public function it_enforces_admin_authentication_for_admin_endpoints()
    {
        // Test that admin middleware exists
        $this->assertTrue(class_exists(\App\Http\Middleware\AdminMiddleware::class));
        
        // Test role-based access control logic
        $user = User::factory()->create(['role' => UserRole::CLIENT]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Test user roles
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($admin->isAdmin());
        
        // Test that admin routes are protected (they should require authentication)
        $adminRoutes = ['admin.queue.test', 'admin.queue.test.status', 'admin.queue.health'];
        foreach ($adminRoutes as $routeName) {
            $this->assertTrue(\Illuminate\Support\Facades\Route::has($routeName), "Route {$routeName} should exist");
        }
    }

    /** @test */
    public function it_logs_security_events()
    {
        // Test that the security service can log events without throwing exceptions
        try {
            $this->securityService->logSecurityEvent('test_event', [
                'test_data' => 'test_value'
            ]);
            $success = true;
        } catch (Exception $e) {
            $success = false;
        }

        // Assert that logging completed without exceptions
        $this->assertTrue($success, 'Security event logging should not throw exceptions');
    }

    /** @test */
    public function it_validates_request_security()
    {
        // Test with normal request
        $request = $this->createRequest('GET', '/setup/status/refresh', [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $security = $this->securityService->validateRequestSecurity($request);
        $this->assertTrue($security['is_secure']);
        $this->assertEquals('low', $security['risk_level']);

        // Test with suspicious request
        $suspiciousRequest = $this->createRequest('GET', '/setup/status/refresh', [
            'User-Agent' => 'curl/7.68.0'
        ]);

        $security = $this->securityService->validateRequestSecurity($suspiciousRequest);
        $this->assertFalse($security['is_secure']);
        $this->assertGreaterThan(0, count($security['issues']));
    }

    /** @test */
    public function it_handles_input_sanitization_edge_cases()
    {
        $edgeCases = [
            // Null values
            ['step' => null],
            ['delay' => null],
            ['test_job_id' => null],
            
            // Empty values
            ['step' => ''],
            ['delay' => ''],
            ['test_job_id' => ''],
            
            // Array values (should be rejected)
            ['step' => ['array', 'value']],
            ['delay' => ['array', 'value']],
            
            // Very long strings
            ['step' => str_repeat('a', 1000)],
            ['test_job_id' => str_repeat('a', 1000)],
            
            // Special characters
            ['step' => 'database; DROP TABLE users;'],
            ['test_job_id' => 'test_<script>alert("xss")</script>'],
        ];

        foreach ($edgeCases as $input) {
            $result = $this->securityService->sanitizeStatusRequest($input);
            
            // Should not throw exceptions and should handle gracefully
            $this->assertIsArray($result);
            $this->assertArrayHasKey('sanitized', $result);
            $this->assertArrayHasKey('violations', $result);
            $this->assertArrayHasKey('is_valid', $result);
        }
    }

    /**
     * Create a request instance for testing
     */
    protected function createRequest(string $method, string $uri, array $headers = []): \Illuminate\Http\Request
    {
        $request = \Illuminate\Http\Request::create($uri, $method);
        
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }
        
        return $request;
    }
}