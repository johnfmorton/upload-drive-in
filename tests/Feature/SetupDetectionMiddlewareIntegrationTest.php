<?php

namespace Tests\Feature;

use App\Services\SetupDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SetupDetectionMiddlewareIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Register test routes
        Route::get('/setup/instructions', function () {
            return 'Setup instructions page';
        })->name('setup.instructions');
        
        Route::get('/test-route', function () {
            return 'Protected content';
        })->middleware(\App\Http\Middleware\SetupDetectionMiddleware::class);
    }

    /** @test */
    public function middleware_allows_setup_instructions_route()
    {
        $response = $this->get('/setup/instructions');
        
        $response->assertStatus(200);
        $response->assertSeeText('Setup instructions page');
    }

    /** @test */
    public function middleware_allows_asset_routes()
    {
        $response = $this->get('/build/app.js');
        
        // Should not redirect to setup instructions
        $response->assertStatus(404); // File doesn't exist, but not redirected
    }

    /** @test */
    public function middleware_redirects_when_setup_incomplete()
    {
        // Mock the setup detection service to return false
        $this->mock(SetupDetectionService::class, function ($mock) {
            $mock->shouldReceive('isSetupComplete')->andReturn(false);
        });

        $response = $this->get('/test-route');
        
        $response->assertRedirect('/setup/instructions');
    }

    /** @test */
    public function middleware_allows_access_when_setup_complete()
    {
        // Mock the setup detection service to return true
        $this->mock(SetupDetectionService::class, function ($mock) {
            $mock->shouldReceive('isSetupComplete')->andReturn(true);
        });

        $response = $this->get('/test-route');
        
        $response->assertStatus(200);
        $response->assertSeeText('Protected content');
    }

    /** @test */
    public function middleware_returns_json_for_ajax_requests_when_setup_incomplete()
    {
        // Mock the setup detection service
        $this->mock(SetupDetectionService::class, function ($mock) {
            $mock->shouldReceive('isSetupComplete')->andReturn(false);
            $mock->shouldReceive('getMissingRequirements')->andReturn(['Database not configured']);
        });

        $response = $this->getJson('/test-route');
        
        $response->assertStatus(503);
        $response->assertJson([
            'error' => 'Setup required',
            'missing_requirements' => ['Database not configured']
        ]);
    }
}