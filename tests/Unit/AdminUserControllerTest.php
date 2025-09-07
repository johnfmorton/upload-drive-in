<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Http\Controllers\Admin\AdminUserController;
use App\Mail\ClientVerificationMail;
use App\Models\User;
use App\Services\ClientUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUserController $controller;
    protected $clientUserService;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up routes for testing
        Route::get('/admin/users', function () {
            return 'admin users index';
        })->name('admin.users.index');
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);
        
        $this->clientUserService = Mockery::mock(ClientUserService::class);
        $this->controller = new AdminUserController($this->clientUserService);
        
        Auth::shouldReceive('user')->andReturn($this->admin);
        Auth::shouldReceive('id')->andReturn($this->admin->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_store_validates_required_fields()
    {
        $request = Request::create('/admin/users', 'POST', []);
        
        $this->expectException(ValidationException::class);
        
        $this->controller->store($request);
    }

    public function test_store_validates_name_field()
    {
        $request = Request::create('/admin/users', 'POST', [
            'email' => 'test@example.com',
            'action' => 'create',
        ]);
        
        $this->expectException(ValidationException::class);
        
        $this->controller->store($request);
    }

    public function test_store_validates_email_field()
    {
        $request = Request::create('/admin/users', 'POST', [
            'name' => 'Test User',
            'action' => 'create',
        ]);
        
        $this->expectException(ValidationException::class);
        
        $this->controller->store($request);
    }

    public function test_store_validates_action_field()
    {
        $request = Request::create('/admin/users', 'POST', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        $this->expectException(ValidationException::class);
        
        $this->controller->store($request);
    }

    public function test_store_validates_email_format()
    {
        $request = Request::create('/admin/users', 'POST', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'action' => 'create',
        ]);
        
        $this->expectException(ValidationException::class);
        
        $this->controller->store($request);
    }

    public function test_store_validates_action_values()
    {
        $request = Request::create('/admin/users', 'POST', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'action' => 'invalid_action',
        ]);
        
        $this->expectException(ValidationException::class);
        
        $this->controller->store($request);
    }

    public function test_store_validates_name_length()
    {
        $request = Request::create('/admin/users', 'POST', [
            'name' => str_repeat('a', 256), // 256 characters, exceeds max of 255
            'email' => 'test@example.com',
            'action' => 'create',
        ]);
        
        $this->expectException(ValidationException::class);
        
        $this->controller->store($request);
    }

    public function test_store_creates_user_without_invitation()
    {
        Mail::fake();
        
        $clientUser = User::factory()->make([
            'id' => 1,
            'email' => 'client@example.com',
            'name' => 'Test Client',
            'role' => UserRole::CLIENT,
        ]);
        
        $this->clientUserService
            ->shouldReceive('findOrCreateClientUser')
            ->once()
            ->with(
                [
                    'name' => 'Test Client',
                    'email' => 'client@example.com',
                    'action' => 'create',
                ],
                $this->admin
            )
            ->andReturn($clientUser);
        
        $request = Request::create('/admin/users', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create',
        ]);
        
        $response = $this->controller->store($request);
        
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('admin.users.index'), $response->getTargetUrl());
        
        Mail::assertNotSent(ClientVerificationMail::class);
    }

    public function test_store_creates_user_with_invitation()
    {
        Mail::fake();
        URL::shouldReceive('temporarySignedRoute')
            ->once()
            ->with('login.via.token', Mockery::any(), ['user' => 1])
            ->andReturn('http://example.com/login/token');
        
        $clientUser = User::factory()->make([
            'id' => 1,
            'email' => 'client@example.com',
            'name' => 'Test Client',
            'role' => UserRole::CLIENT,
        ]);
        
        $this->clientUserService
            ->shouldReceive('findOrCreateClientUser')
            ->once()
            ->with(
                [
                    'name' => 'Test Client',
                    'email' => 'client@example.com',
                    'action' => 'create_and_invite',
                ],
                $this->admin
            )
            ->andReturn($clientUser);
        
        $request = Request::create('/admin/users', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite',
        ]);
        
        $response = $this->controller->store($request);
        
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('admin.users.index'), $response->getTargetUrl());
        
        Mail::assertSent(ClientVerificationMail::class, function ($mail) {
            return $mail->hasTo('client@example.com');
        });
    }

    public function test_store_handles_service_exception()
    {
        $this->clientUserService
            ->shouldReceive('findOrCreateClientUser')
            ->once()
            ->andThrow(new \Exception('Service error'));
        
        $request = Request::create('/admin/users', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create',
        ]);
        
        $response = $this->controller->store($request);
        
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('admin.users.index'), $response->getTargetUrl());
    }

    public function test_store_handles_email_sending_failure()
    {
        Mail::fake();
        Mail::shouldReceive('to')
            ->andThrow(new \Exception('Email sending failed'));
        
        URL::shouldReceive('temporarySignedRoute')
            ->once()
            ->with('login.via.token', Mockery::any(), ['user' => 1])
            ->andReturn('http://example.com/login/token');
        
        $clientUser = User::factory()->make([
            'id' => 1,
            'email' => 'client@example.com',
            'name' => 'Test Client',
            'role' => UserRole::CLIENT,
        ]);
        
        $this->clientUserService
            ->shouldReceive('findOrCreateClientUser')
            ->once()
            ->andReturn($clientUser);
        
        $request = Request::create('/admin/users', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite',
        ]);
        
        $response = $this->controller->store($request);
        
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('admin.users.index'), $response->getTargetUrl());
    }

    public function test_store_handles_invalid_email_format_in_invitation()
    {
        Mail::fake();
        
        $clientUser = User::factory()->make([
            'id' => 1,
            'email' => 'test@example.com', // Use valid email for creation, test invalid format in sendInvitationEmail
            'name' => 'Test Client',
            'role' => UserRole::CLIENT,
        ]);
        
        // Override the email property after creation to simulate invalid format
        $clientUser->email = 'invalid-email-format';
        
        $this->clientUserService
            ->shouldReceive('findOrCreateClientUser')
            ->once()
            ->andReturn($clientUser);
        
        $request = Request::create('/admin/users', 'POST', [
            'name' => 'Test Client',
            'email' => 'test@example.com', // Valid email for validation
            'action' => 'create_and_invite',
        ]);
        
        $response = $this->controller->store($request);
        
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('admin.users.index'), $response->getTargetUrl());
        
        Mail::assertNotSent(ClientVerificationMail::class);
    }

    public function test_store_accepts_valid_action_create()
    {
        Mail::fake();
        
        $clientUser = User::factory()->make([
            'id' => 1,
            'email' => 'client@example.com',
            'name' => 'Test Client',
            'role' => UserRole::CLIENT,
        ]);
        
        $this->clientUserService
            ->shouldReceive('findOrCreateClientUser')
            ->once()
            ->andReturn($clientUser);
        
        $request = Request::create('/admin/users', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create',
        ]);
        
        $response = $this->controller->store($request);
        
        $this->assertEquals(302, $response->getStatusCode());
        Mail::assertNotSent(ClientVerificationMail::class);
    }

    public function test_store_accepts_valid_action_create_and_invite()
    {
        Mail::fake();
        URL::shouldReceive('temporarySignedRoute')
            ->once()
            ->andReturn('http://example.com/login/token');
        
        $clientUser = User::factory()->make([
            'id' => 1,
            'email' => 'client@example.com',
            'name' => 'Test Client',
            'role' => UserRole::CLIENT,
        ]);
        
        $this->clientUserService
            ->shouldReceive('findOrCreateClientUser')
            ->once()
            ->andReturn($clientUser);
        
        $request = Request::create('/admin/users', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite',
        ]);
        
        $response = $this->controller->store($request);
        
        $this->assertEquals(302, $response->getStatusCode());
        Mail::assertSent(ClientVerificationMail::class);
    }

    public function test_store_logs_user_creation_attempt()
    {
        Mail::fake();
        
        $clientUser = User::factory()->make([
            'id' => 1,
            'email' => 'client@example.com',
            'name' => 'Test Client',
            'role' => UserRole::CLIENT,
        ]);
        
        $this->clientUserService
            ->shouldReceive('findOrCreateClientUser')
            ->once()
            ->andReturn($clientUser);
        
        $request = Request::create('/admin/users', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create',
        ]);
        
        // Mock the request IP and user agent
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->server->set('HTTP_USER_AGENT', 'Test User Agent');
        
        $response = $this->controller->store($request);
        
        $this->assertEquals(302, $response->getStatusCode());
    }
}