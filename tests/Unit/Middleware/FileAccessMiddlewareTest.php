<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Http\Middleware\FileAccessMiddleware;
use App\Models\FileUpload;
use App\Models\User;
use App\Models\ClientUserRelationship;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\ParameterBag;

class FileAccessMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private FileAccessMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new FileAccessMiddleware();
    }

    public function test_unauthenticated_user_receives_401()
    {
        // Don't authenticate any user
        Auth::shouldReceive('user')->andReturn(null);
        Log::shouldReceive('warning')->once();

        $request = Request::create('/admin/files/1/download');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Should not reach here');
        });

        $this->assertEquals(401, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Authentication required to access files.', $responseData['message']);
    }

    public function test_file_not_found_returns_404()
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        Auth::shouldReceive('user')->andReturn($user);
        Log::shouldReceive('warning')->once();

        $request = Request::create('/admin/files/999/download');
        $route = new Route(['GET'], '/admin/files/{file}/download', []);
        $route->bind($request);
        $route->setParameter('file', 999);
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $response = $this->middleware->handle($request, function () {
            return new Response('Should not reach here');
        });

        $this->assertEquals(404, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('File not found.', $responseData['message']);
    }

    public function test_admin_can_access_any_file()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $file = FileUpload::factory()->create(['client_user_id' => $client->id]);

        Auth::shouldReceive('user')->andReturn($admin);
        Log::shouldReceive('info')->once();

        $request = Request::create('/admin/files/' . $file->id . '/download');
        $route = new Route(['GET'], '/admin/files/{file}/download', []);
        $route->bind($request);
        $route->setParameter('file', $file);
        $route->name('admin.files.download');
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_client_cannot_access_other_clients_files()
    {
        $client1 = User::factory()->create(['role' => UserRole::CLIENT]);
        $client2 = User::factory()->create(['role' => UserRole::CLIENT]);
        $file = FileUpload::factory()->create(['client_user_id' => $client2->id]);

        Auth::shouldReceive('user')->andReturn($client1);
        Log::shouldReceive('warning')->once();

        $request = Request::create('/admin/files/' . $file->id . '/download');
        $route = new Route(['GET'], '/admin/files/{file}/download', []);
        $route->bind($request);
        $route->setParameter('file', $file);
        $route->name('admin.files.download');
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $response = $this->middleware->handle($request, function () {
            return new Response('Should not reach here');
        });

        $this->assertEquals(403, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('You can only access files you have uploaded', $responseData['message']);
    }

    public function test_client_can_access_their_own_files()
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $file = FileUpload::factory()->create(['client_user_id' => $client->id]);

        Auth::shouldReceive('user')->andReturn($client);
        Log::shouldReceive('info')->once();

        $request = Request::create('/admin/files/' . $file->id . '/download');
        $route = new Route(['GET'], '/admin/files/{file}/download', []);
        $route->bind($request);
        $route->setParameter('file', $file);
        $route->name('admin.files.download');
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_employee_can_access_managed_client_files()
    {
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $file = FileUpload::factory()->create(['client_user_id' => $client->id]);

        // Create relationship
        ClientUserRelationship::create([
            'company_user_id' => $employee->id,
            'client_user_id' => $client->id,
            'is_primary' => true,
        ]);

        Auth::shouldReceive('user')->andReturn($employee);
        Log::shouldReceive('info')->once();

        $request = Request::create('/admin/files/' . $file->id . '/download');
        $route = new Route(['GET'], '/admin/files/{file}/download', []);
        $route->bind($request);
        $route->setParameter('file', $file);
        $route->name('admin.files.download');
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_employee_cannot_access_unmanaged_client_files()
    {
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $file = FileUpload::factory()->create(['client_user_id' => $client->id]);

        // No relationship created

        Auth::shouldReceive('user')->andReturn($employee);
        Log::shouldReceive('warning')->once();

        $request = Request::create('/admin/files/' . $file->id . '/download');
        $route = new Route(['GET'], '/admin/files/{file}/download', []);
        $route->bind($request);
        $route->setParameter('file', $file);
        $route->name('admin.files.download');
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $response = $this->middleware->handle($request, function () {
            return new Response('Should not reach here');
        });

        $this->assertEquals(403, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('You can only access files from clients you manage', $responseData['message']);
    }

    public function test_employee_can_access_files_they_uploaded()
    {
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $file = FileUpload::factory()->create(['uploaded_by_user_id' => $employee->id]);

        Auth::shouldReceive('user')->andReturn($employee);
        Log::shouldReceive('info')->once();

        $request = Request::create('/admin/files/' . $file->id . '/download');
        $route = new Route(['GET'], '/admin/files/{file}/download', []);
        $route->bind($request);
        $route->setParameter('file', $file);
        $route->name('admin.files.download');
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_middleware_logs_successful_access()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $file = FileUpload::factory()->create();

        Auth::shouldReceive('user')->andReturn($admin);
        Log::shouldReceive('info')->once()->with('User accessed file', \Mockery::type('array'));

        $request = Request::create('/admin/files/' . $file->id . '/preview');
        $route = new Route(['GET'], '/admin/files/{file}/preview', []);
        $route->bind($request);
        $route->setParameter('file', $file);
        $route->name('admin.files.preview');
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_identifies_different_actions()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $file = FileUpload::factory()->create();

        Auth::shouldReceive('user')->andReturn($admin);

        $actions = [
            'admin.files.preview' => 'preview',
            'admin.files.download' => 'download',
            'admin.files.thumbnail' => 'thumbnail',
            'admin.files.delete' => 'delete',
        ];

        foreach ($actions as $routeName => $expectedAction) {
            Log::shouldReceive('info')->once()->with('User accessed file', \Mockery::on(function ($data) use ($expectedAction) {
                return $data['action'] === $expectedAction;
            }));

            $request = Request::create('/admin/files/' . $file->id . '/action');
            $route = new Route(['GET'], '/admin/files/{file}/action', []);
            $route->bind($request);
            $route->setParameter('file', $file);
            $route->name($routeName);
            $request->setRouteResolver(function () use ($route) {
                return $route;
            });

            $response = $this->middleware->handle($request, function () {
                return new Response('Success');
            });

            $this->assertEquals(200, $response->getStatusCode());
        }
    }
}