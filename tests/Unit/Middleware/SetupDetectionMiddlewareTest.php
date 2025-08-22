<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SetupDetectionMiddleware;
use App\Services\SetupDetectionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Mockery;
use Tests\TestCase;

class SetupDetectionMiddlewareTest extends TestCase
{
    private SetupDetectionMiddleware $middleware;
    private SetupDetectionService $setupDetectionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupDetectionService = Mockery::mock(SetupDetectionService::class);
        $this->middleware = new SetupDetectionMiddleware($this->setupDetectionService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_allows_setup_instructions_route_to_pass_through()
    {
        // Arrange
        $request = Request::create('/setup/instructions', 'GET');
        $route = new Route(['GET'], '/setup/instructions', []);
        $route->name('setup.instructions');
        $request->setRouteResolver(fn() => $route);
        
        $next = function ($req) {
            return new Response('Setup instructions page');
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals('Setup instructions page', $response->getContent());
    }

    /** @test */
    public function it_allows_exempt_routes_to_pass_through()
    {
        // Test asset routes
        $assetPaths = [
            '/build/app.js',
            '/storage/uploads/file.txt',
            '/images/logo.png',
            '/css/app.css',
            '/js/app.js',
            '/fonts/font.woff',
            '/assets/icon.svg',
            '/favicon.ico',
            '/robots.txt',
        ];

        foreach ($assetPaths as $path) {
            $request = Request::create($path, 'GET');
            $next = function ($req) {
                return new Response('Asset content');
            };

            $response = $this->middleware->handle($request, $next);
            
            $this->assertEquals('Asset content', $response->getContent(), "Failed for path: {$path}");
        }
    }

    /** @test */
    public function it_allows_health_check_routes_to_pass_through()
    {
        // Arrange
        $request = Request::create('/health', 'GET');
        $route = new Route(['GET'], '/health', []);
        $route->name('health.check');
        $request->setRouteResolver(fn() => $route);
        
        $next = function ($req) {
            return new Response('OK');
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_redirects_to_setup_instructions_when_setup_is_incomplete()
    {
        // Arrange
        $this->setupDetectionService
            ->shouldReceive('isSetupComplete')
            ->once()
            ->andReturn(false);

        $request = Request::create('/dashboard', 'GET');
        $next = function ($req) {
            return new Response('Dashboard');
        };

        // Mock the route function to return a URL
        $this->app->bind('url', function () {
            $urlGenerator = Mockery::mock(\Illuminate\Routing\UrlGenerator::class);
            $urlGenerator->shouldReceive('route')
                ->with('setup.instructions')
                ->andReturn('/setup/instructions');
            return $urlGenerator;
        });

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContains('/setup/instructions', $response->headers->get('Location'));
    }

    /** @test */
    public function it_returns_json_response_for_ajax_requests_when_setup_is_incomplete()
    {
        // Arrange
        $this->setupDetectionService
            ->shouldReceive('isSetupComplete')
            ->once()
            ->andReturn(false);
            
        $this->setupDetectionService
            ->shouldReceive('getMissingRequirements')
            ->once()
            ->andReturn(['Database connection not configured']);

        $request = Request::create('/api/data', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        
        $next = function ($req) {
            return new Response('API Data');
        };

        // Mock the route function to return a URL
        $this->app->bind('url', function () {
            $urlGenerator = Mockery::mock(\Illuminate\Routing\UrlGenerator::class);
            $urlGenerator->shouldReceive('route')
                ->with('setup.instructions')
                ->andReturn('/setup/instructions');
            return $urlGenerator;
        });

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(503, $response->getStatusCode());
        $this->assertJson($response->getContent());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Setup required', $data['error']);
        $this->assertArrayHasKey('missing_requirements', $data);
        $this->assertEquals(['Database connection not configured'], $data['missing_requirements']);
    }

    /** @test */
    public function it_allows_normal_application_access_when_setup_is_complete()
    {
        // Arrange
        $this->setupDetectionService
            ->shouldReceive('isSetupComplete')
            ->once()
            ->andReturn(true);

        $request = Request::create('/dashboard', 'GET');
        $next = function ($req) {
            return new Response('Dashboard content');
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals('Dashboard content', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_requests_expecting_json_when_setup_is_incomplete()
    {
        // Arrange
        $this->setupDetectionService
            ->shouldReceive('isSetupComplete')
            ->once()
            ->andReturn(false);
            
        $this->setupDetectionService
            ->shouldReceive('getMissingRequirements')
            ->once()
            ->andReturn(['Google Drive credentials not configured', 'No admin user found']);

        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $next = function ($req) {
            return new Response('Users data');
        };

        // Mock the route function to return a URL
        $this->app->bind('url', function () {
            $urlGenerator = Mockery::mock(\Illuminate\Routing\UrlGenerator::class);
            $urlGenerator->shouldReceive('route')
                ->with('setup.instructions')
                ->andReturn('/setup/instructions');
            return $urlGenerator;
        });

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(503, $response->getStatusCode());
        $this->assertJson($response->getContent());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Setup required', $data['error']);
        $this->assertStringContainsString('Application setup is required', $data['message']);
        $this->assertCount(2, $data['missing_requirements']);
    }

    /** @test */
    public function it_correctly_identifies_setup_instructions_route_by_name()
    {
        // Arrange
        $request = Request::create('/some/path', 'GET');
        $route = new Route(['GET'], '/some/path', []);
        $route->name('setup.instructions');
        $request->setRouteResolver(fn() => $route);
        
        $next = function ($req) {
            return new Response('Instructions');
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals('Instructions', $response->getContent());
    }

    /** @test */
    public function it_handles_routes_without_names_gracefully()
    {
        // Arrange
        $this->setupDetectionService
            ->shouldReceive('isSetupComplete')
            ->once()
            ->andReturn(true);

        $request = Request::create('/some/path', 'GET');
        $request->setRouteResolver(fn() => null);
        
        $next = function ($req) {
            return new Response('Content');
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals('Content', $response->getContent());
    }
}