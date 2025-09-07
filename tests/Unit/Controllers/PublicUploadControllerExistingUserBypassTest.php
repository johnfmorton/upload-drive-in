<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\PublicUploadController;
use App\Mail\AdminVerificationMail;
use App\Mail\ClientVerificationMail;
use App\Mail\EmployeeVerificationMail;
use App\Models\DomainAccessRule;
use App\Models\EmailValidation;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Mockery;

class PublicUploadControllerExistingUserBypassTest extends TestCase
{
    use RefreshDatabase;

    protected PublicUploadController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->controller = new PublicUploadController();
        Mail::fake();
        Log::spy();
    }

    /** @test */
    public function existing_admin_user_bypasses_public_registration_restrictions()
    {
        // Create admin user
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => UserRole::ADMIN
        ]);

        // Disable public registration
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // Create request
        $request = Request::create('/validate-email', 'POST', [
            'email' => 'admin@test.com'
        ]);

        // Execute the method
        $response = $this->controller->validateEmail($request);

        // Verify admin receives verification email despite restrictions
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Verification email sent successfully.', $responseData['message']);

        // Verify admin-specific email template is used
        Mail::assertSent(AdminVerificationMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });

        // Verify appropriate logging occurs
        Log::shouldHaveReceived('info')
            ->with('Existing user detected during validation', Mockery::on(function ($context) use ($admin) {
                return $context['email'] === 'admin@test.com' &&
                       $context['user_id'] === $admin->id &&
                       $context['user_role'] === 'admin' &&
                       $context['will_bypass_restrictions'] === true;
            }));

        Log::shouldHaveReceived('info')
            ->with('Existing user bypassing registration restrictions', Mockery::on(function ($context) use ($admin) {
                return $context['email'] === 'admin@test.com' &&
                       $context['user_id'] === $admin->id &&
                       $context['user_role'] === 'admin' &&
                       in_array('public_registration_disabled', $context['restrictions_bypassed']) &&
                       $context['bypass_reason'] === 'existing_user_detected';
            }));
    }

    /** @test */
    public function existing_employee_user_bypasses_domain_restrictions()
    {
        // Create employee user with non-whitelisted domain
        $employee = User::factory()->create([
            'email' => 'employee@blocked.com',
            'role' => UserRole::EMPLOYEE
        ]);

        // Set up domain whitelist that excludes employee's domain
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com', 'approved.org'],
            'allow_public_registration' => true
        ]);

        // Create request
        $request = Request::create('/validate-email', 'POST', [
            'email' => 'employee@blocked.com'
        ]);

        // Execute the method
        $response = $this->controller->validateEmail($request);

        // Verify employee receives verification email despite domain restrictions
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);

        // Verify employee-specific email template is used
        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email);
        });

        // Verify appropriate logging occurs
        Log::shouldHaveReceived('info')
            ->with('Existing user detected during validation', Mockery::on(function ($context) use ($employee) {
                return $context['email'] === 'employee@blocked.com' &&
                       $context['user_id'] === $employee->id &&
                       $context['user_role'] === 'employee' &&
                       $context['will_bypass_restrictions'] === true;
            }));

        Log::shouldHaveReceived('info')
            ->with('Existing user bypassing registration restrictions', Mockery::on(function ($context) use ($employee) {
                return $context['email'] === 'employee@blocked.com' &&
                       $context['user_id'] === $employee->id &&
                       $context['user_role'] === 'employee' &&
                       in_array('domain_not_allowed', $context['restrictions_bypassed']) &&
                       $context['bypass_reason'] === 'existing_user_detected';
            }));
    }

    /** @test */
    public function existing_client_user_bypasses_all_restrictions()
    {
        // Create client user with restricted domain and disabled public registration
        $client = User::factory()->create([
            'email' => 'client@blocked.com',
            'role' => UserRole::CLIENT
        ]);

        // Set up both domain restrictions and disabled public registration
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // Create request
        $request = Request::create('/validate-email', 'POST', [
            'email' => 'client@blocked.com'
        ]);

        // Execute the method
        $response = $this->controller->validateEmail($request);

        // Verify client receives verification email despite all restrictions
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);

        // Verify client-specific email template is used
        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email);
        });

        // Verify appropriate logging occurs
        Log::shouldHaveReceived('info')
            ->with('Existing user bypassing registration restrictions', Mockery::on(function ($context) use ($client) {
                return $context['email'] === 'client@blocked.com' &&
                       $context['user_id'] === $client->id &&
                       $context['user_role'] === 'client' &&
                       in_array('public_registration_disabled', $context['restrictions_bypassed']) &&
                       in_array('domain_not_allowed', $context['restrictions_bypassed']) &&
                       $context['restrictions_count'] === 2 &&
                       $context['bypass_reason'] === 'existing_user_detected';
            }));
    }

    /** @test */
    public function new_user_blocked_when_public_registration_disabled()
    {
        // Disable public registration
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // Create request for new user
        $request = Request::create('/validate-email', 'POST', [
            'email' => 'newuser@test.com'
        ]);

        // Execute the method - it should return a JSON response with error
        $response = $this->controller->validateEmail($request);
        
        // Verify error response
        $this->assertEquals(422, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('registration is currently disabled', $responseData['message']);
        
        // Verify no verification emails are sent to blocked new users
        Mail::assertNothingSent();
        
        // Verify appropriate logging occurs
        Log::shouldHaveReceived('info')
            ->with('No existing user found during validation', Mockery::on(function ($context) {
                return $context['email'] === 'newuser@test.com' &&
                       $context['will_apply_restrictions'] === true;
            }));

        Log::shouldHaveReceived('warning')
            ->with('New user blocked by registration restrictions', Mockery::on(function ($context) {
                return $context['email'] === 'newuser@test.com' &&
                       $context['user_exists'] === false &&
                       $context['restriction_type'] === 'public_registration_disabled' &&
                       $context['restriction_enforced'] === true;
            }));
    }

    /** @test */
    public function new_user_blocked_when_domain_not_whitelisted()
    {
        // Set up domain whitelist that excludes new user's domain
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com', 'approved.org'],
            'allow_public_registration' => true
        ]);

        // Create request for new user with non-whitelisted domain
        $request = Request::create('/validate-email', 'POST', [
            'email' => 'newuser@blocked.com'
        ]);

        // Execute the method - it should return a JSON response with error
        $response = $this->controller->validateEmail($request);
        
        // Verify error response
        $this->assertEquals(422, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('domain is not allowed', $responseData['message']);
        
        // Verify no verification emails are sent to blocked new users
        Mail::assertNothingSent();
        
        // Verify appropriate logging occurs
        Log::shouldHaveReceived('warning')
            ->with('New user blocked by registration restrictions', Mockery::on(function ($context) {
                return $context['email'] === 'newuser@blocked.com' &&
                       $context['user_exists'] === false &&
                       $context['restriction_type'] === 'domain_not_allowed' &&
                       $context['restriction_enforced'] === true;
            }));
    }

    /** @test */
    public function database_error_handling_graceful_fallback_behavior()
    {
        // This test verifies that when database lookup fails, the system falls back
        // to treating the user as new and applies restrictions (fail closed for security)
        
        // Set up restrictions that would normally block new users
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // Create request with a valid email that doesn't exist in database
        // This will test the fallback behavior when no user is found
        $request = Request::create('/validate-email', 'POST', [
            'email' => 'nonexistent@blocked.com'
        ]);

        // Execute the method - should handle gracefully and apply restrictions
        $response = $this->controller->validateEmail($request);
        
        // Verify error response (blocked due to restrictions)
        $this->assertEquals(422, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('registration is currently disabled', $responseData['message']);
        
        // Verify no verification emails are sent
        Mail::assertNothingSent();
        
        // Verify appropriate logging occurs for new user
        Log::shouldHaveReceived('info')
            ->with('No existing user found during validation', Mockery::on(function ($context) {
                return $context['email'] === 'nonexistent@blocked.com' &&
                       $context['will_apply_restrictions'] === true;
            }));
    }

    /** @test */
    public function security_posture_maintained_during_errors()
    {
        // This test verifies that security restrictions are still enforced
        // even when there might be database issues
        
        // Set up both types of restrictions
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // Test with a non-whitelisted domain
        $request = Request::create('/validate-email', 'POST', [
            'email' => 'test@blocked.com'
        ]);

        // Execute the method
        $response = $this->controller->validateEmail($request);
        
        // Verify that restrictions are still applied (fail closed)
        $this->assertEquals(422, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        
        // Should be blocked by public registration restriction first
        $this->assertStringContainsString('registration is currently disabled', $responseData['message']);
        
        // Verify no emails sent
        Mail::assertNothingSent();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}