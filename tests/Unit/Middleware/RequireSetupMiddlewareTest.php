<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\RequireSetupMiddleware;
use App\Services\SetupService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class RequireSetupMiddlewareTest extends TestCase
{
    private RequireSetupMiddleware $middleware;
    private SetupService $setupService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupService = Mockery::mock(SetupService::class);
        $this->middleware = new RequireSetupMiddleware($this->setupService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_middleware_passes_when_bootstrap_checks_disabled(): void
    {
        Config::set('setup.bootstrap_checks', false);
        
        $request = Request::create('/dashboard');
        $next = fn($req) => new Response('OK');
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_middleware_passes_for_setup_routes(): void
    {
        Config::set('setup.bootstrap_checks', true);
        Config::set('setup.route_prefix', 'setup');
        
        $request = Request::create('/setup/assets');
        $next = fn($req) => new Response('Setup Page');
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals('Setup Page', $response->getContent());
    }

    public function test_middleware_passes_for_exempt_routes(): void
    {
        Config::set('setup.bootstrap_checks', true);
        Config::set('setup.exempt_routes', ['health', 'ping']);
        
        $request = Request::create('/health');
        $next = fn($req) => new Response('Healthy');
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals('Healthy', $response->getContent());
    }

    public function test_middleware_passes_for_exempt_paths(): void
    {
        Config::set('setup.bootstrap_checks', true);
        Config::set('setup.exempt_paths', ['build/*', '*.css']);
        
        $request = Request::create('/build/app.js');
        $next = fn($req) => new Response('Asset');
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals('Asset', $response->getContent());
    }

    public function test_middleware_passes_for_asset_related_routes(): void
    {
        Config::set('setup.bootstrap_checks', true);
        
        $request = Request::create('/css/app.css');
        $next = fn($req) => new Response('CSS');
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals('CSS', $response->getContent());
    }

    public function test_middleware_calls_asset_validation_before_setup_checks(): void
    {
        Config::set('setup.bootstrap_checks', true);
        
        $this->setupService
            ->shouldReceive('areAssetsValid')
            ->once()
            ->andReturn(false);
        
        // Should not call isSetupRequired if assets are invalid
        $this->setupService
            ->shouldNotReceive('isSetupRequired');
        
        $request = Request::create('/dashboard');
        $next = fn($req) => new Response('Dashboard');
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_middleware_handles_asset_validation_exceptions(): void
    {
        Config::set('setup.bootstrap_checks', true);
        
        $this->setupService
            ->shouldReceive('areAssetsValid')
            ->once()
            ->andThrow(new \Exception('Vite manifest not found'));
        
        $request = Request::create('/dashboard');
        $next = fn($req) => new Response('Dashboard');
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_middleware_proceeds_to_setup_checks_when_assets_valid(): void
    {
        Config::set('setup.bootstrap_checks', true);
        
        $this->setupService
            ->shouldReceive('areAssetsValid')
            ->once()
            ->andReturn(true);
        
        $this->setupService
            ->shouldReceive('isSetupRequired')
            ->once()
            ->andReturn(false);
        
        $request = Request::create('/dashboard');
        $next = fn($req) => new Response('Dashboard');
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals('Dashboard', $response->getContent());
    }

    public function test_middleware_handles_setup_check_exceptions(): void
    {
        Config::set('setup.bootstrap_checks', true);
        Config::set('setup.redirect_route', 'setup.welcome');
        
        $this->setupService
            ->shouldReceive('areAssetsValid')
            ->once()
            ->andReturn(true);
        
        $this->setupService
            ->shouldReceive('isSetupRequired')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
        
        $request = Request::create('/dashboard');
        $next = fn($req) => new Response('Dashboard');
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_setup_route_detection_with_route_names(): void
    {
        Config::set('setup.bootstrap_checks', true);
        Config::set('setup.route_prefix', 'setup');
        
        $request = Request::create('/custom-setup-path');
        $route = Mockery::mock();
        $route->shouldReceive('getName')->andReturn('setup.assets');
        $request->setRouteResolver(fn() => $route);
        
        $next = fn($req) => new Response('Setup Route');
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals('Setup Route', $response->getContent());
    }

    public function test_pattern_matching_with_wildcards(): void
    {
        Config::set('setup.bootstrap_checks', true);
        Config::set('setup.exempt_paths', ['api/v*/public/*']);
        
        $request = Request::create('/api/v1/public/data');
        $next = fn($req) => new Response('Public API');
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals('Public API', $response->getContent());
    }

    public function test_asset_related_route_detection(): void
    {
        Config::set('setup.bootstrap_checks', true);
        
        $assetPaths = [
            '/build/app.js',
            '/storage/uploads/file.pdf',
            '/images/logo.png',
            '/css/app.css',
            '/js/app.js',
            '/fonts/font.woff2',
            '/assets/icon.svg',
        ];
        
        foreach ($assetPaths as $path) {
            $request = Request::create($path);
            $next = fn($req) => new Response('Asset');
            
            $response = $this->middleware->handle($request, $next);
            
            $this->assertEquals('Asset', $response->getContent(), "Failed for path: {$path}");
        }
    }

    public function test_setup_route_for_step_mapping(): void
    {
        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('getSetupRouteForStep');
        $method->setAccessible(true);
        
        $testCases = [
            'assets' => 'setup.assets',
            'welcome' => 'setup.welcome',
            'database' => 'setup.database',
            'admin' => 'setup.admin',
            'storage' => 'setup.storage',
            'complete' => 'setup.complete',
            'unknown' => 'setup.welcome', // default fallback
        ];
        
        foreach ($testCases as $step => $expectedRoute) {
            $result = $method->invoke($this->middleware, $step);
            $this->assertEquals($expectedRoute, $result, "Failed for step: {$step}");
        }
    }
}