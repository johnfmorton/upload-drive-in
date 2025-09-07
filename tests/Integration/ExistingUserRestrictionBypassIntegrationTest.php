<?php

namespace Tests\Integration;

use App\Mail\AdminVerificationMail;
use App\Mail\ClientVerificationMail;
use App\Mail\EmployeeVerificationMail;
use App\Models\DomainAccessRule;
use App\Models\EmailValidation;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Integration tests for existing user restriction bypass functionality.
 * 
 * These tests verify that existing users can complete the full verification flow
 * even when security restrictions (public registration disabled, domain restrictions)
 * are in place, while new users are properly blocked by the same restrictions.
 */
class ExistingUserRestrictionBypassIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    #[Test]
    public function existing_admin_bypasses_public_registration_disabled_and_completes_verification_flow()
    {
        // Create an existing admin user
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => UserRole::ADMIN,
            'email_verified_at' => null
        ]);

        // Disable public registration
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // Step 1: Admin submits email despite public registration being disabled
        $response = $this->postJson('/validate-email', [
            'email' => 'admin@test.com'
        ]);

        // Verify admin request was successful (bypassed restriction)
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify AdminVerificationMail was sent
        Mail::assertSent(AdminVerificationMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email) &&
                   $mail->userRole === 'admin';
        });

        // Verify email validation record was created
        $validation = EmailValidation::where('email', 'admin@test.com')->first();
        $this->assertNotNull($validation);
        $this->assertNotNull($validation->verification_code);

        // Step 2: Admin clicks verification link
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'admin@test.com'
        ]));

        // Verify admin is redirected to admin dashboard
        $verificationResponse->assertRedirect(route('admin.dashboard'));
        $verificationResponse->assertSessionHas('success', 'Email verified successfully.');

        // Verify admin is logged in
        $this->assertAuthenticatedAs($admin);

        // Verify email validation record is marked as verified
        $validation->refresh();
        $this->assertNotNull($validation->verified_at);

        // Step 3: Log out admin and verify new user is still blocked by the same restriction
        $this->post('/logout');
        $this->assertGuest();
        
        Mail::fake();
        $newUserResponse = $this->postJson('/validate-email', [
            'email' => 'newuser@test.com'
        ]);

        $newUserResponse->assertStatus(422);
        $newUserResponse->assertJson([
            'success' => false,
            'message' => 'New user registration is currently disabled. If you already have an account, please try again or contact support.'
        ]);
        Mail::assertNothingSent();
    }

    #[Test]
    public function existing_employee_bypasses_domain_restrictions_and_completes_verification_flow()
    {
        // Create admin user (owner)
        $adminUser = User::factory()->create(['role' => UserRole::ADMIN]);

        // Create an existing employee user with non-whitelisted domain
        $employee = User::factory()->create([
            'email' => 'employee@blocked.com',
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $adminUser->id,
            'username' => 'testemployee',
            'email_verified_at' => null
        ]);

        // Set up domain whitelist that excludes employee's domain
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com', 'whitelisted.com'],
            'allow_public_registration' => true
        ]);

        // Step 1: Employee submits email despite domain not being whitelisted
        $response = $this->postJson('/validate-email', [
            'email' => 'employee@blocked.com'
        ]);

        // Verify employee request was successful (bypassed domain restriction)
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify EmployeeVerificationMail was sent
        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email) &&
                   $mail->userRole === 'employee';
        });

        // Verify email validation record was created
        $validation = EmailValidation::where('email', 'employee@blocked.com')->first();
        $this->assertNotNull($validation);
        $this->assertNotNull($validation->verification_code);

        // Step 2: Employee clicks verification link
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'employee@blocked.com'
        ]));

        // Verify employee is redirected to employee dashboard
        $verificationResponse->assertRedirect(route('employee.dashboard', ['username' => $employee->username]));
        $verificationResponse->assertSessionHas('success', 'Email verified successfully.');

        // Verify employee is logged in
        $this->assertAuthenticatedAs($employee);

        // Verify email validation record is marked as verified
        $validation->refresh();
        $this->assertNotNull($validation->verified_at);

        // Step 3: Log out employee and verify new user with same domain is still blocked
        $this->post('/logout');
        $this->assertGuest();
        
        Mail::fake();
        $newUserResponse = $this->postJson('/validate-email', [
            'email' => 'newuser@blocked.com'
        ]);

        $newUserResponse->assertStatus(422);
        $newUserResponse->assertJson([
            'success' => false,
            'message' => 'This email domain is not allowed for new registrations. If you already have an account, please try again or contact support.'
        ]);
        Mail::assertNothingSent();
    }

    #[Test]
    public function existing_client_bypasses_all_restrictions_and_completes_verification_flow()
    {
        // Create an existing client user with restricted domain
        $client = User::factory()->create([
            'email' => 'client@blocked.com',
            'role' => UserRole::CLIENT,
            'email_verified_at' => null
        ]);

        // Set up both domain restrictions and disable public registration
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // Step 1: Client submits email despite all restrictions
        $response = $this->postJson('/validate-email', [
            'email' => 'client@blocked.com'
        ]);

        // Verify client request was successful (bypassed all restrictions)
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify ClientVerificationMail was sent
        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email) &&
                   $mail->userRole === 'client';
        });

        // Verify email validation record was created
        $validation = EmailValidation::where('email', 'client@blocked.com')->first();
        $this->assertNotNull($validation);
        $this->assertNotNull($validation->verification_code);

        // Step 2: Client clicks verification link
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'client@blocked.com'
        ]));

        // Verify client is redirected to client upload interface
        $verificationResponse->assertRedirect(route('client.upload-files'));
        $verificationResponse->assertSessionHas('success', 'Email verified successfully. You can now upload files.');

        // Verify client is logged in
        $this->assertAuthenticatedAs($client);

        // Verify email validation record is marked as verified
        $validation->refresh();
        $this->assertNotNull($validation->verified_at);

        // Step 3: Log out client and verify new user is still blocked by both restrictions
        $this->post('/logout');
        $this->assertGuest();
        
        Mail::fake();
        $newUserResponse = $this->postJson('/validate-email', [
            'email' => 'newuser@blocked.com'
        ]);

        $newUserResponse->assertStatus(422);
        $newUserResponse->assertJson([
            'success' => false,
            'message' => 'New user registration is currently disabled. If you already have an account, please try again or contact support.'
        ]);
        Mail::assertNothingSent();
    }

    #[Test]
    public function existing_admin_with_intended_url_completes_flow_and_redirects_correctly()
    {
        // Create an existing admin user
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => UserRole::ADMIN,
            'email_verified_at' => null
        ]);

        // Disable public registration
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // Step 1: Admin submits email with intended URL
        $intendedUrl = '/admin/users';
        $response = $this->postJson('/validate-email', [
            'email' => 'admin@test.com',
            'intended_url' => url($intendedUrl)
        ]);

        // Verify admin request was successful
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify AdminVerificationMail was sent
        Mail::assertSent(AdminVerificationMail::class);

        // Get validation record
        $validation = EmailValidation::where('email', 'admin@test.com')->first();
        $this->assertNotNull($validation);

        // Step 2: Admin clicks verification link
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'admin@test.com'
        ]));

        // Verify admin is redirected to intended URL (admin users can use intended URLs)
        $verificationResponse->assertRedirect($intendedUrl);

        // Verify admin is logged in
        $this->assertAuthenticatedAs($admin);
    }

    #[Test]
    public function existing_employee_with_intended_url_completes_flow_and_redirects_correctly()
    {
        // Create admin user (owner)
        $adminUser = User::factory()->create(['role' => UserRole::ADMIN]);

        // Create an existing employee user
        $employee = User::factory()->create([
            'email' => 'employee@blocked.com',
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $adminUser->id,
            'username' => 'testemployee',
            'email_verified_at' => null
        ]);

        // Set up domain restrictions
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => true
        ]);

        // Step 1: Employee submits email with intended URL
        $intendedUrl = '/employee/testemployee/files';
        $response = $this->postJson('/validate-email', [
            'email' => 'employee@blocked.com',
            'intended_url' => url($intendedUrl)
        ]);

        // Verify employee request was successful
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify EmployeeVerificationMail was sent
        Mail::assertSent(EmployeeVerificationMail::class);

        // Get validation record
        $validation = EmailValidation::where('email', 'employee@blocked.com')->first();
        $this->assertNotNull($validation);

        // Step 2: Employee clicks verification link
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'employee@blocked.com'
        ]));

        // Verify employee is redirected to intended URL (employee users can use intended URLs)
        $verificationResponse->assertRedirect($intendedUrl);

        // Verify employee is logged in
        $this->assertAuthenticatedAs($employee);
    }

    #[Test]
    public function existing_client_with_intended_url_completes_flow_and_redirects_correctly()
    {
        // Create an existing client user
        $client = User::factory()->create([
            'email' => 'client@blocked.com',
            'role' => UserRole::CLIENT,
            'email_verified_at' => null
        ]);

        // Set up all restrictions
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // Step 1: Client submits email with intended URL
        $intendedUrl = '/some-specific-page';
        $response = $this->postJson('/validate-email', [
            'email' => 'client@blocked.com',
            'intended_url' => url($intendedUrl)
        ]);

        // Verify client request was successful
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify ClientVerificationMail was sent
        Mail::assertSent(ClientVerificationMail::class);

        // Get validation record
        $validation = EmailValidation::where('email', 'client@blocked.com')->first();
        $this->assertNotNull($validation);

        // Step 2: Client clicks verification link
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'client@blocked.com'
        ]));

        // Verify client is redirected to intended URL
        $verificationResponse->assertRedirect($intendedUrl);

        // Verify client is logged in
        $this->assertAuthenticatedAs($client);
    }

    #[Test]
    public function multiple_existing_user_verification_attempts_consistently_bypass_restrictions()
    {
        // Create an existing admin user
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => UserRole::ADMIN,
            'email_verified_at' => null
        ]);

        // Disable public registration
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // Step 1: First verification attempt
        $response1 = $this->postJson('/validate-email', [
            'email' => 'admin@test.com'
        ]);

        $response1->assertStatus(200);
        Mail::assertSent(AdminVerificationMail::class);

        // Step 2: Second verification attempt (should also succeed)
        Mail::fake(); // Reset mail fake
        $response2 = $this->postJson('/validate-email', [
            'email' => 'admin@test.com'
        ]);

        $response2->assertStatus(200);
        Mail::assertSent(AdminVerificationMail::class);

        // Step 3: Third verification attempt (should also succeed)
        Mail::fake(); // Reset mail fake
        $response3 = $this->postJson('/validate-email', [
            'email' => 'admin@test.com'
        ]);

        $response3->assertStatus(200);
        Mail::assertSent(AdminVerificationMail::class);

        // Verify all attempts used admin template consistently
        $this->assertTrue(true); // All assertions above passed
    }

    #[Test]
    public function existing_user_with_blacklisted_domain_bypasses_blacklist_restrictions()
    {
        // Create an existing employee user with blacklisted domain
        $adminUser = User::factory()->create(['role' => UserRole::ADMIN]);
        $employee = User::factory()->create([
            'email' => 'employee@blacklisted.com',
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $adminUser->id,
            'username' => 'testemployee',
            'email_verified_at' => null
        ]);

        // Set up domain blacklist that includes employee's domain
        DomainAccessRule::create([
            'mode' => 'blacklist',
            'rules' => ['blacklisted.com', 'spam.com'],
            'allow_public_registration' => true
        ]);

        // Step 1: Employee submits email despite domain being blacklisted
        $response = $this->postJson('/validate-email', [
            'email' => 'employee@blacklisted.com'
        ]);

        // Verify employee request was successful (bypassed blacklist)
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify EmployeeVerificationMail was sent
        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email) &&
                   $mail->userRole === 'employee';
        });

        // Get validation record
        $validation = EmailValidation::where('email', 'employee@blacklisted.com')->first();
        $this->assertNotNull($validation);

        // Step 2: Employee clicks verification link
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'employee@blacklisted.com'
        ]));

        // Verify employee is redirected to employee dashboard
        $verificationResponse->assertRedirect(route('employee.dashboard', ['username' => $employee->username]));

        // Verify employee is logged in
        $this->assertAuthenticatedAs($employee);

        // Step 3: Log out employee and verify new user with same blacklisted domain is still blocked
        $this->post('/logout');
        $this->assertGuest();
        
        Mail::fake();
        $newUserResponse = $this->postJson('/validate-email', [
            'email' => 'newuser@blacklisted.com'
        ]);

        $newUserResponse->assertStatus(422);
        $newUserResponse->assertJson([
            'success' => false,
            'message' => 'This email domain is not allowed for new registrations. If you already have an account, please try again or contact support.'
        ]);
    }
}