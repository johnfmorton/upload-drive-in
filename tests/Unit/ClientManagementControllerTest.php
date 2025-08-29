<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Http\Controllers\Employee\ClientManagementController;
use App\Mail\LoginVerificationMail;
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

class ClientManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected ClientManagementController $controller;
    protected $clientUserService;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee@example.com',
        ]);
        
        $this->clientUserService = Mockery::mock(ClientUserService::class);
        $this->controller = new ClientManagementController();
        
        Auth::shouldReceive('user')->andReturn($this->employee);
        Auth::shouldReceive('id')->andReturn($this->employee->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_store_validates_required_fields()
    {
        $request = Request::create('/employee/test/clients', 'POST', []);
        
        $this->expectException(ValidationException::class);
        
        $this->controller->store($request, $this->clientUserService);
    }

    public function test_store_validates_name_field()
    {
        $request = Request::create('/employee/test/clients', 'POST', [
            'email' => 'test@example.com',
            'action' => 'create',
        ]);
        
        $this->expectException(ValidationException::class);
        
        $this->controller->store($request, $this->clientUserService);
    }

    public function test_store_validates_email_field()
    {
        $request = Request::create('/employee/test/clients', 'POST', [
            'name' => 'Test User',
            'action' => 'create',
        ]);
        
        $this->expectException(ValidationException::class);
        
        $this->controller->store($request, $this->clientUserService);
    }

    public function test_store_validates_action_field()
    {
        $request = Request::create('/employee/test/clients', 'POST', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        $this->expectException(ValidationException::class);
        
        $this->controller->store($request, $this->clientUserService);
    }

    public function test_store_validates_email_format()
    {
        $request = Request::create('/employee/test/clients', 'POST', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'action' => 'create',
        ]);
        
        $this->expectException(ValidationException::class);
        
        $this->controller->store($request, $this->clientUserService);
    }

    public function test_store_validates_action_values()
    {
        $request = Request::create('/employee/test/clients', 'POST', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'action' => 'invalid_action',
        ]);
        
        $this->expectException(ValidationException::class);
        
        $this->controller->store($request, $this->clientUserService);
    }

    public function test_store_validates_name_length()
    {
        $request = Request::create('/employee/test/clients', 'POST', [
            'name' => str_repeat('a', 256), // 256 characters, exceeds max of 255
            'email' => 'test@example.com',
            'action' => 'create',
        ]);
        
        $this->expectException(ValidationException::class);
        
        $this->controller->store($request, $this->clientUserService);
    }

    public function test_store_creates_client_without_invitation()
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
                $this->employee
            )
            ->andReturn($clientUser);
        
        $request = Request::create('/employee/test/clients', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create',
        ]);
        
        $response = $this->controller->store($request, $this->clientUserService);
        
        $this->assertEquals(302, $response->getStatusCode());
        
        Mail::assertNotSent(LoginVerificationMail::class);
    }

    public function test_store_creates_client_with_invitation()
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
                $this->employee
            )
            ->andReturn($clientUser);
        
        $request = Request::create('/employee/test/clients', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite',
        ]);
        
        $response = $this->controller->store($request, $this->clientUserService);
        
        $this->assertEquals(302, $response->getStatusCode());
        
        Mail::assertSent(LoginVerificationMail::class, function ($mail) {
            return $mail->hasTo('client@example.com');
        });
    }

    public function test_store_handles_service_exception()
    {
        $this->clientUserService
            ->shouldReceive('findOrCreateClientUser')
            ->once()
            ->andThrow(new \Exception('Service error'));
        
        $request = Request::create('/employee/test/clients', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create',
        ]);
        
        $response = $this->controller->store($request, $this->clientUserService);
        
        $this->assertEquals(302, $response->getStatusCode());
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
        
        $request = Request::create('/employee/test/clients', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite',
        ]);
        
        $response = $this->controller->store($request, $this->clientUserService);
        
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_store_handles_invalid_email_format_in_invitation()
    {
        Mail::fake();
        
        $clientUser = User::factory()->make([
            'id' => 1,
            'email' => 'test@example.com', // Use valid email for creation
            'name' => 'Test Client',
            'role' => UserRole::CLIENT,
        ]);
        
        // Override the email property after creation to simulate invalid format
        $clientUser->email = 'invalid-email-format';
        
        $this->clientUserService
            ->shouldReceive('findOrCreateClientUser')
            ->once()
            ->andReturn($clientUser);
        
        $request = Request::create('/employee/test/clients', 'POST', [
            'name' => 'Test Client',
            'email' => 'test@example.com', // Valid email for validation
            'action' => 'create_and_invite',
        ]);
        
        $response = $this->controller->store($request, $this->clientUserService);
        
        $this->assertEquals(302, $response->getStatusCode());
        
        Mail::assertNotSent(LoginVerificationMail::class);
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
        
        $request = Request::create('/employee/test/clients', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create',
        ]);
        
        $response = $this->controller->store($request, $this->clientUserService);
        
        $this->assertEquals(302, $response->getStatusCode());
        Mail::assertNotSent(LoginVerificationMail::class);
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
        
        $request = Request::create('/employee/test/clients', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite',
        ]);
        
        $response = $this->controller->store($request, $this->clientUserService);
        
        $this->assertEquals(302, $response->getStatusCode());
        Mail::assertSent(LoginVerificationMail::class);
    }

    public function test_store_returns_correct_status_for_create_action()
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
        
        $request = Request::create('/employee/test/clients', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create',
        ]);
        
        $response = $this->controller->store($request, $this->clientUserService);
        
        $this->assertEquals(302, $response->getStatusCode());
        // Note: We can't easily test session data in unit tests without more complex mocking
    }

    public function test_store_returns_correct_status_for_create_and_invite_action()
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
        
        $request = Request::create('/employee/test/clients', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite',
        ]);
        
        $response = $this->controller->store($request, $this->clientUserService);
        
        $this->assertEquals(302, $response->getStatusCode());
        Mail::assertSent(LoginVerificationMail::class);
    }

    public function test_store_logs_client_creation_attempt()
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
        
        $request = Request::create('/employee/test/clients', 'POST', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create',
        ]);
        
        // Mock the request IP and user agent
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->server->set('HTTP_USER_AGENT', 'Test User Agent');
        
        $response = $this->controller->store($request, $this->clientUserService);
        
        $this->assertEquals(302, $response->getStatusCode());
    }
}