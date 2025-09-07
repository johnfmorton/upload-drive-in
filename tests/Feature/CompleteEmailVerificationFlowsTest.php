<?php

namespace Tests\Feature;

use App\Mail\AdminVerificationMail;
use App\Mail\ClientVerificationMail;
use App\Mail\EmployeeVerificationMail;
use App\Models\EmailValidation;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Complete end-to-end feature tests for email verification flows.
 * 
 * These tests verify the complete user journey from email validation
 * through verification and login for each user role.
 */
class CompleteEmailVerificationFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    #[Test]
    public function admin_user_email_verification_end_to_end_flow()
    {
        // Create an existing admin user
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN,
            'email_verified_at' => null
        ]);

        // Step 1: Admin requests email validation via public upload form
        $response = $this->postJson('/validate-email', [
            'email' => 'admin@example.com'
        ]);

        // Verify validation request was successful
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify AdminVerificationMail was sent
        Mail::assertSent(AdminVerificationMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email) &&
                   $mail->userRole === 'admin' &&
                   $mail->companyName === config('app.company_name', config('app.name'));
        });

        // Verify email validation record was created
        $validation = EmailValidation::where('email', 'admin@example.com')->first();
        $this->assertNotNull($validation);
        $this->assertNotNull($validation->verification_code);
        $this->assertNull($validation->verified_at);

        // Step 2: Admin clicks verification link in email
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'admin@example.com'
        ]));

        // Verify admin is redirected to admin dashboard
        $verificationResponse->assertRedirect(route('admin.dashboard'));
        $verificationResponse->assertSessionHas('success', 'Email verified successfully.');

        // Verify admin is now logged in
        $this->assertAuthenticatedAs($admin);

        // Verify email validation record is marked as verified
        $validation->refresh();
        $this->assertNotNull($validation->verified_at);
    }

    #[Test]
    public function employee_user_email_verification_end_to_end_flow()
    {
        // Create an admin user (owner)
        $adminUser = User::factory()->create(['role' => UserRole::ADMIN]);

        // Create an existing employee user
        $employee = User::factory()->create([
            'email' => 'employee@example.com',
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $adminUser->id,
            'username' => 'testemployee',
            'email_verified_at' => null
        ]);

        // Step 1: Employee requests email validation via public upload form
        $response = $this->postJson('/validate-email', [
            'email' => 'employee@example.com'
        ]);

        // Verify validation request was successful
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify EmployeeVerificationMail was sent
        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email) &&
                   $mail->userRole === 'employee' &&
                   $mail->companyName === config('app.company_name', config('app.name'));
        });

        // Verify email validation record was created
        $validation = EmailValidation::where('email', 'employee@example.com')->first();
        $this->assertNotNull($validation);
        $this->assertNotNull($validation->verification_code);
        $this->assertNull($validation->verified_at);

        // Step 2: Employee clicks verification link in email
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'employee@example.com'
        ]));

        // Verify employee is redirected to employee dashboard
        $verificationResponse->assertRedirect(route('employee.dashboard', ['username' => $employee->username]));
        $verificationResponse->assertSessionHas('success', 'Email verified successfully.');

        // Verify employee is now logged in
        $this->assertAuthenticatedAs($employee);

        // Verify email validation record is marked as verified
        $validation->refresh();
        $this->assertNotNull($validation->verified_at);
    }

    #[Test]
    public function client_user_email_verification_end_to_end_flow()
    {
        // Create an existing client user
        $client = User::factory()->create([
            'email' => 'client@example.com',
            'role' => UserRole::CLIENT,
            'email_verified_at' => null
        ]);

        // Step 1: Client requests email validation via public upload form
        $response = $this->postJson('/validate-email', [
            'email' => 'client@example.com'
        ]);

        // Verify validation request was successful
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify ClientVerificationMail was sent
        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email) &&
                   $mail->userRole === 'client' &&
                   $mail->companyName === config('app.company_name', config('app.name'));
        });

        // Verify email validation record was created
        $validation = EmailValidation::where('email', 'client@example.com')->first();
        $this->assertNotNull($validation);
        $this->assertNotNull($validation->verification_code);
        $this->assertNull($validation->verified_at);

        // Step 2: Client clicks verification link in email
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'client@example.com'
        ]));

        // Verify client is redirected to client upload page
        $verificationResponse->assertRedirect(route('client.upload-files'));
        $verificationResponse->assertSessionHas('success', 'Email verified successfully. You can now upload files.');

        // Verify client is now logged in
        $this->assertAuthenticatedAs($client);

        // Verify email validation record is marked as verified
        $validation->refresh();
        $this->assertNotNull($validation->verified_at);
    }

    #[Test]
    public function public_upload_email_verification_uses_client_template_for_new_users()
    {
        // Step 1: New user requests email validation via public upload form
        $response = $this->postJson('/validate-email', [
            'email' => 'newuser@example.com'
        ]);

        // Verify validation request was successful
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify ClientVerificationMail was sent (fallback for new users)
        Mail::assertSent(ClientVerificationMail::class, function ($mail) {
            return $mail->hasTo('newuser@example.com') &&
                   $mail->userRole === 'client' &&
                   $mail->companyName === config('app.company_name', config('app.name'));
        });

        // Verify email validation record was created
        $validation = EmailValidation::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($validation);
        $this->assertNotNull($validation->verification_code);
        $this->assertNull($validation->verified_at);

        // Step 2: New user clicks verification link in email
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'newuser@example.com'
        ]));

        // Verify new user was created as client
        $newUser = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals(UserRole::CLIENT, $newUser->role);

        // Verify user is redirected to client upload page
        $verificationResponse->assertRedirect(route('client.upload-files'));
        $verificationResponse->assertSessionHas('success', 'Email verified successfully. You can now upload files.');

        // Verify new user is now logged in
        $this->assertAuthenticatedAs($newUser);

        // Verify email validation record is marked as verified
        $validation->refresh();
        $this->assertNotNull($validation->verified_at);
    }

    #[Test]
    public function admin_user_creation_sends_client_verification_email_end_to_end()
    {
        // Create admin user
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);

        // Step 1: Admin creates new client user with invitation
        $response = $this->post('/admin/users', [
            'name' => 'Test Client',
            'email' => 'testclient@example.com',
            'action' => 'create_and_invite'
        ]);

        // Verify admin is redirected back to users index
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        // Verify client user was created
        $client = User::where('email', 'testclient@example.com')->first();
        $this->assertNotNull($client);
        $this->assertEquals(UserRole::CLIENT, $client->role);
        $this->assertEquals('Test Client', $client->name);

        // Verify ClientVerificationMail was sent (clients get client template)
        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email) &&
                   $mail->userRole === 'client' &&
                   $mail->companyName === config('app.company_name', config('app.name'));
        });

        // Step 2: Extract verification URL from mail and simulate click
        $sentMail = Mail::sent(ClientVerificationMail::class)->first();
        $this->assertNotNull($sentMail);
        
        // The verification URL should be a temporary signed route
        $verificationUrl = $sentMail->verificationUrl;
        $this->assertStringContainsString('login/via-token', $verificationUrl);

        // Step 3: Client clicks verification link
        $verificationResponse = $this->get($verificationUrl);

        // Verify client is logged in and redirected appropriately
        $this->assertAuthenticatedAs($client);
        
        // Client should be redirected to upload page
        $verificationResponse->assertRedirect(route('client.upload-files'));
    }

    #[Test]
    public function employee_user_creation_sends_employee_verification_email_end_to_end()
    {
        // Create admin user
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);

        // Step 1: Admin creates new employee user with invitation
        $response = $this->post('/admin/employees', [
            'name' => 'Test Employee',
            'email' => 'testemployee@example.com',
            'action' => 'create_and_invite'
        ]);

        // Verify admin is redirected back to employees index
        $response->assertRedirect(route('admin.employees.index'));
        $response->assertSessionHas('success');

        // Verify employee user was created
        $employee = User::where('email', 'testemployee@example.com')->first();
        $this->assertNotNull($employee);
        $this->assertEquals(UserRole::EMPLOYEE, $employee->role);
        $this->assertEquals('Test Employee', $employee->name);
        $this->assertEquals($admin->id, $employee->owner_id);

        // Verify EmployeeVerificationMail was sent
        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email) &&
                   $mail->userRole === 'employee' &&
                   $mail->companyName === config('app.company_name', config('app.name'));
        });

        // Step 2: Extract verification URL from mail and simulate click
        $sentMail = Mail::sent(EmployeeVerificationMail::class)->first();
        $this->assertNotNull($sentMail);
        
        // The verification URL should be a temporary signed route
        $verificationUrl = $sentMail->verificationUrl;
        $this->assertStringContainsString('login/via-token', $verificationUrl);

        // Step 3: Employee clicks verification link
        $verificationResponse = $this->get($verificationUrl);

        // Verify employee is logged in and redirected appropriately
        $this->assertAuthenticatedAs($employee);
        
        // Employee should be redirected to employee dashboard
        $verificationResponse->assertRedirect(route('employee.dashboard', ['username' => $employee->username]));
    }

    #[Test]
    public function employee_client_creation_sends_client_verification_email_end_to_end()
    {
        // Create admin and employee users
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $admin->id,
            'username' => 'testemployee'
        ]);
        $this->actingAs($employee);

        // Step 1: Employee creates new client user with invitation
        $response = $this->post("/employee/{$employee->username}/clients", [
            'name' => 'Test Client',
            'email' => 'testclient@example.com',
            'action' => 'create_and_invite'
        ]);

        // Verify employee is redirected back with success status
        $response->assertRedirect();
        $response->assertSessionHas('status', 'employee-client-created-and-invited');

        // Verify client user was created
        $client = User::where('email', 'testclient@example.com')->first();
        $this->assertNotNull($client);
        $this->assertEquals(UserRole::CLIENT, $client->role);
        $this->assertEquals('Test Client', $client->name);

        // Verify ClientVerificationMail was sent (clients get client template)
        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email) &&
                   $mail->userRole === 'client' &&
                   $mail->companyName === config('app.company_name', config('app.name'));
        });

        // Step 2: Extract verification URL from mail and simulate click
        $sentMail = Mail::sent(ClientVerificationMail::class)->first();
        $this->assertNotNull($sentMail);
        
        // The verification URL should be a temporary signed route
        $verificationUrl = $sentMail->verificationUrl;
        $this->assertStringContainsString('login/via-token', $verificationUrl);

        // Step 3: Client clicks verification link
        $verificationResponse = $this->get($verificationUrl);

        // Verify client is logged in and redirected appropriately
        $this->assertAuthenticatedAs($client);
        
        // Client should be redirected to upload page
        $verificationResponse->assertRedirect(route('client.upload-files'));
    }

    #[Test]
    public function public_upload_with_intended_url_creates_proper_relationships()
    {
        // Create an employee user with matching email pattern
        $employee = User::factory()->create([
            'email' => 'testemployee@example.com',
            'role' => UserRole::EMPLOYEE,
            'username' => 'testemployee'
        ]);

        // Step 1: New user requests email validation with intended URL for specific employee
        $intendedUrl = "/upload/{$employee->username}";
        $response = $this->postJson('/validate-email', [
            'email' => 'newclient@example.com',
            'intended_url' => url($intendedUrl)
        ]);

        // Verify validation request was successful
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify ClientVerificationMail was sent
        Mail::assertSent(ClientVerificationMail::class, function ($mail) {
            return $mail->hasTo('newclient@example.com');
        });

        // Get the validation record
        $validation = EmailValidation::where('email', 'newclient@example.com')->first();
        $this->assertNotNull($validation);

        // Step 2: New user clicks verification link
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'newclient@example.com'
        ]));

        // Verify new user was created
        $newClient = User::where('email', 'newclient@example.com')->first();
        $this->assertNotNull($newClient);
        $this->assertEquals(UserRole::CLIENT, $newClient->role);

        // Verify user is redirected to intended URL
        $verificationResponse->assertRedirect($intendedUrl);
        $verificationResponse->assertSessionHas('success', 'Email verified successfully.');

        // Verify client-employee relationship was created
        $this->assertTrue($newClient->companyUsers()->where('company_user_id', $employee->id)->exists());

        // Verify user is logged in
        $this->assertAuthenticatedAs($newClient);
    }

    #[Test]
    public function verification_flow_handles_expired_verification_codes()
    {
        // Create an email validation record that's expired
        $validation = EmailValidation::create([
            'email' => 'test@example.com',
            'verification_code' => 'expired_code',
            'expires_at' => now()->subHour() // Expired 1 hour ago
        ]);

        // Attempt to verify with expired code
        $response = $this->get(route('verify-email', [
            'code' => 'expired_code',
            'email' => 'test@example.com'
        ]));

        // Verify user is redirected to home with error
        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error', 'Invalid or expired verification link.');

        // Verify no user is logged in
        $this->assertGuest();
    }

    #[Test]
    public function verification_flow_handles_invalid_verification_codes()
    {
        // Create an email validation record
        $validation = EmailValidation::create([
            'email' => 'test@example.com',
            'verification_code' => 'valid_code',
            'expires_at' => now()->addHour()
        ]);

        // Attempt to verify with wrong code
        $response = $this->get(route('verify-email', [
            'code' => 'wrong_code',
            'email' => 'test@example.com'
        ]));

        // Verify user is redirected to home with error
        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error', 'Invalid or expired verification link.');

        // Verify no user is logged in
        $this->assertGuest();

        // Verify validation record is not marked as verified
        $validation->refresh();
        $this->assertNull($validation->verified_at);
    }

    #[Test]
    public function verification_flow_creates_admin_relationship_for_orphaned_clients()
    {
        // Create an admin user
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        // Step 1: New user requests email validation
        $response = $this->postJson('/validate-email', [
            'email' => 'orphanclient@example.com'
        ]);

        $response->assertStatus(200);

        // Get the validation record
        $validation = EmailValidation::where('email', 'orphanclient@example.com')->first();

        // Step 2: User verifies email (no intended URL, so becomes orphaned client)
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'orphanclient@example.com'
        ]));

        // Verify new client was created
        $newClient = User::where('email', 'orphanclient@example.com')->first();
        $this->assertNotNull($newClient);
        $this->assertEquals(UserRole::CLIENT, $newClient->role);

        // Verify client was automatically associated with admin as fallback
        $this->assertTrue($newClient->companyUsers()->where('company_user_id', $admin->id)->exists());

        // Verify user is redirected to client upload page
        $verificationResponse->assertRedirect(route('client.upload-files'));
    }
}