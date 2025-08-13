<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\RequireSetupMiddleware;
use App\Services\SetupService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Mockery;
use Tests\TestCase;

class RequireSetupMiddlewareTest extends TestCase
{
    private SetupService $setupService;
    private RequireSetupMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupService = Mockery::mock(SetupService::class);
        $this->middleware = new RequireSetupMiddleware($this->setupService);
    }

    public function test_allows_setup_routes_when_setup_required(): void
    {
        $request = Request::create('/setup/welcome', 'GET');
        $this->setupService->shouldNotReceive('isSetupRequired');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_allows_setup_routes_by_name(): void
    {
        $request = Request::create('/some-path', 'GET');
        $route = new Route(['GET'], '/some-path', []);
        $route->name('setup.welcome');
        $request->setRouteResolver(fn() => $route);

        $this->setupService->shouldNotReceive('isSetupRequired');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_allows_asset_routes(): void
    {
        $assetPaths = [
            '/build/app.js',
            '/storage/file.pdf',
            '/images/logo.png',
            '/css/app.css',
            '/js/app.js',
            '/favicon.ico',
            '/robots.txt',
            '/health'
        ];

        foreach ($assetPaths as $path) {
            $request = Request::create($path, 'GET');
            $this->setupService->shouldNotReceive('isSetupRequired');

            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK');
            });

            $this->assertEquals('OK', $response->getContent(), "Failed for path: {$path}");
        }
    }

    public function test_allows_file_extensions(): void
    {
        $setupService = Mockery::mock(SetupService::class);
        $middleware = new RequireSetupMiddleware($setupService);
        
        $request = Request::create('/some/path/file.css', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_redirects_to_setup_when_setup_required(): void
    {
        $request = Request::create('/dashboard', 'GET');
        $this->setupService->shouldReceive('isSetupRequired')->once()->andReturn(true);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Should not reach here');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue(str_contains($response->headers->get('Location'), '/setup/welcome'));
    }

    public function test_allows_request_when_setup_not_required(): void
    {
        $request = Request::create('/dashboard', 'GET');
        $this->setupService->shouldReceive('isSetupRequired')->once()->andReturn(false);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_handles_ajax_requests_correctly(): void
    {
        $request = Request::create('/api/data', 'GET');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->setupService->shouldReceive('isSetupRequired')->once()->andReturn(true);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Should not reach here');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue(str_contains($response->headers->get('Location'), '/setup/welcome'));
    }

    public function test_handles_json_requests_correctly(): void
    {
        $request = Request::create('/api/data', 'GET');
        $request->headers->set('Accept', 'application/json');
        $this->setupService->shouldReceive('isSetupRequired')->once()->andReturn(true);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Should not reach here');
        });

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_allows_health_check_routes(): void
    {
        $healthPaths = [
            '/health',
            '/health/check',
            '/status',
            '/ping'
        ];

        foreach ($healthPaths as $path) {
            $request = Request::create($path, 'GET');
            $this->setupService->shouldNotReceive('isSetupRequired');

            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK');
            });

            $this->assertEquals('OK', $response->getContent(), "Failed for health path: {$path}");
        }
    }

    public function test_allows_well_known_routes(): void
    {
        $wellKnownPaths = [
            '/.well-known/microsoft-identity-association.json',
            '/.well-known/security.txt',
            '/.well-known/robots.txt'
        ];

        foreach ($wellKnownPaths as $path) {
            $request = Request::create($path, 'GET');
            $this->setupService->shouldNotReceive('isSetupRequired');

            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK');
            });

            $this->assertEquals('OK', $response->getContent(), "Failed for well-known path: {$path}");
        }
    }

    public function test_handles_post_requests_to_non_setup_routes(): void
    {
        $request = Request::create('/login', 'POST');
        $this->setupService->shouldReceive('isSetupRequired')->once()->andReturn(true);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Should not reach here');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue(str_contains($response->headers->get('Location'), '/setup/welcome'));
    }

    public function test_preserves_query_parameters_in_redirect(): void
    {
        $request = Request::create('/dashboard?tab=files&sort=date', 'GET');
        $this->setupService->shouldReceive('isSetupRequired')->once()->andReturn(true);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Should not reach here');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $location = $response->headers->get('Location');
        $this->assertTrue(str_contains($location, '/setup/welcome'));
        // The redirect should go to setup, not preserve the original query params
    }

    public function test_handles_setup_service_exceptions(): void
    {
        $request = Request::create('/dashboard', 'GET');
        $this->setupService->shouldReceive('isSetupRequired')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));

        // Should treat exceptions as setup required
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Should not reach here');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue(str_contains($response->headers->get('Location'), '/setup/welcome'));
    }

    public function test_allows_setup_step_routes(): void
    {
        $setupStepPaths = [
            '/setup/step/welcome',
            '/setup/step/database',
            '/setup/step/admin',
            '/setup/step/storage',
            '/setup/step/complete'
        ];

        foreach ($setupStepPaths as $path) {
            $request = Request::create($path, 'GET');
            $this->setupService->shouldNotReceive('isSetupRequired');

            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK');
            });

            $this->assertEquals('OK', $response->getContent(), "Failed for setup step path: {$path}");
        }
    }

    public function test_middleware_performance_with_multiple_requests(): void
    {
        $startTime = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $request = Request::create('/dashboard', 'GET');
            $this->setupService->shouldReceive('isSetupRequired')->once()->andReturn(false);

            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK');
            });

            $this->assertEquals('OK', $response->getContent());
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Should complete 100 requests in under 1 second
        $this->assertLessThan(1.0, $executionTime, 'Middleware performance is too slow');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}