<?php

namespace Tests\Feature;

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
 * Feature tests for edge cases in existing user email verification.
 * 
 * These tests verify complex scenarios including intended URL handling,
 * multiple verification attempts, and mixed domain rule configurations.
 */
class ExistingUserEmailVerificationEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    // ========================================
    // 8.1 Test intended URL handling for existing users
    // ========================================

    #[Test]
    public function existing_admin_user_with_intended_url_parameter_stores_and_uses_url()
    {
        // Create an existing admin user
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN,
            'email_verified_at' => null
        ]);

        // Disable public registration to test bypass
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        $intendedUrl = 'https://example.com/admin/special-page';

        // Step 1: Admin requests email validation with intended URL
        $response = $this->postJson('/validate-email', [
            'email' => 'admin@example.com',
            'intended_url' => $intendedUrl
        ]);

        // Verify validation request was successful despite restrictions
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify AdminVerificationMail was sent
        Mail::assertSent(AdminVerificationMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email) &&
                   $mail->userRole === 'admin';
        });

        // Verify intended URL is stored in session
        $this->assertEquals($intendedUrl, session('intended_url'));

        // Get the validation record
        $validation = EmailValidation::where('email', 'admin@example.com')->first();
        $this->assertNotNull($validation);

        // Step 2: Admin clicks verification link
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'admin@example.com'
        ]));

        // Verify admin is logged in
        $this->assertAuthenticatedAs($admin);

        // Verify admin is redirected to intended URL (not default dashboard)
        $verificationResponse->assertRedirect($intendedUrl);
        $verificationResponse->assertSessionHas('success', 'Email verified successfully.');
    }

    #[Test]
    public function existing_employee_user_with_employee_upload_url_stores_and_uses_url()
    {
        // Create admin user (owner)
        $adminUser = User::factory()->create(['role' => UserRole::ADMIN]);

        // Create an existing employee user
        $employee = User::factory()->create([
            'email' => 'employee@example.com',
            'role' => UserRole::EMPLOYEE,
            'owner_id' => $adminUser->id,
            'username' => 'testemployee',
            'email_verified_at' => null
        ]);

        // Set up domain restrictions that would block new users
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => true
        ]);

        $employeeUploadUrl = "/upload/{$employee->username}";

        // Step 1: Employee requests email validation with employee upload URL
        $response = $this->postJson('/validate-email', [
            'email' => 'employee@example.com',
            'intended_url' => url($employeeUploadUrl)
        ]);

        // Verify validation request was successful despite domain restrictions
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify EmployeeVerificationMail was sent
        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email) &&
                   $mail->userRole === 'employee';
        });

        // Verify intended URL is stored in session
        $this->assertEquals(url($employeeUploadUrl), session('intended_url'));

        // Get the validation record
        $validation = EmailValidation::where('email', 'employee@example.com')->first();
        $this->assertNotNull($validation);

        // Step 2: Employee clicks verification link
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'employee@example.com'
        ]));

        // Verify employee is logged in
        $this->assertAuthenticatedAs($employee);

        // Verify employee is redirected to intended URL (employee upload page)
        $verificationResponse->assertRedirect($employeeUploadUrl);
        $verificationResponse->assertSessionHas('success', 'Email verified successfully.');
    }

    #[Test]
    public function existing_client_user_with_general_intended_url_stores_and_uses_url()
    {
        // Create an existing client user
        $client = User::factory()->create([
            'email' => 'client@blocked.com',
            'role' => UserRole::CLIENT,
            'email_verified_at' => null
        ]);

        // Set up both restrictions that would block new users
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        $generalIntendedUrl = 'https://example.com/client/special-dashboard';

        // Step 1: Client requests email validation with general intended URL
        $response = $this->postJson('/validate-email', [
            'email' => 'client@blocked.com',
            'intended_url' => $generalIntendedUrl
        ]);

        // Verify validation request was successful despite all restrictions
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify ClientVerificationMail was sent
        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email) &&
                   $mail->userRole === 'client';
        });

        // Verify intended URL is stored in session
        $this->assertEquals($generalIntendedUrl, session('intended_url'));

        // Get the validation record
        $validation = EmailValidation::where('email', 'client@blocked.com')->first();
        $this->assertNotNull($validation);

        // Step 2: Client clicks verification link
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'client@blocked.com'
        ]));

        // Verify client is logged in
        $this->assertAuthenticatedAs($client);

        // Verify client is redirected to intended URL (not default upload page)
        $verificationResponse->assertRedirect($generalIntendedUrl);
        $verificationResponse->assertSessionHas('success', 'Email verified successfully.');
    }

    #[Test]
    public function existing_user_without_intended_url_uses_default_redirect()
    {
        // Create an existing employee user
        $employee = User::factory()->create([
            'email' => 'employee@example.com',
            'role' => UserRole::EMPLOYEE,
            'username' => 'testemployee',
            'email_verified_at' => null
        ]);

        // Set up restrictions
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // Step 1: Employee requests email validation WITHOUT intended URL
        $response = $this->postJson('/validate-email', [
            'email' => 'employee@example.com'
            // No intended_url parameter
        ]);

        // Verify validation request was successful
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify no intended URL is stored in session
        $this->assertNull(session('intended_url'));

        // Get the validation record
        $validation = EmailValidation::where('email', 'employee@example.com')->first();
        $this->assertNotNull($validation);

        // Step 2: Employee clicks verification link
        $verificationResponse = $this->get(route('verify-email', [
            'code' => $validation->verification_code,
            'email' => 'employee@example.com'
        ]));

        // Verify employee is logged in
        $this->assertAuthenticatedAs($employee);

        // Verify employee is redirected to default employee dashboard
        $verificationResponse->assertRedirect(route('employee.dashboard', ['username' => $employee->username]));
        $verificationResponse->assertSessionHas('success', 'Email verified successfully.');
    }

    // ========================================
    // 8.2 Test multiple verification attempts by existing users
    // ========================================

    #[Test]
    public function existing_admin_user_multiple_verification_attempts_bypass_restrictions_consistently()
    {
        // Create an existing admin user
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN,
            'email_verified_at' => null
        ]);

        // Disable public registration to test bypass
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // First verification attempt
        $response1 = $this->postJson('/validate-email', [
            'email' => 'admin@example.com'
        ]);

        $response1->assertStatus(200);
        $response1->assertJson(['success' => true]);

        // Verify AdminVerificationMail was sent
        Mail::assertSent(AdminVerificationMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email) &&
                   $mail->userRole === 'admin';
        });

        // Clear mail fake to track second attempt
        Mail::fake();

        // Second verification attempt (should also bypass restrictions)
        $response2 = $this->postJson('/validate-email', [
            'email' => 'admin@example.com'
        ]);

        $response2->assertStatus(200);
        $response2->assertJson(['success' => true]);

        // Verify AdminVerificationMail was sent again with same template
        Mail::assertSent(AdminVerificationMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email) &&
                   $mail->userRole === 'admin';
        });

        // Clear mail fake to track third attempt
        Mail::fake();

        // Third verification attempt (should still bypass restrictions)
        $response3 = $this->postJson('/validate-email', [
            'email' => 'admin@example.com'
        ]);

        $response3->assertStatus(200);
        $response3->assertJson(['success' => true]);

        // Verify AdminVerificationMail was sent again with consistent template
        Mail::assertSent(AdminVerificationMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email) &&
                   $mail->userRole === 'admin';
        });

        // Verify that each attempt created/updated the email validation record
        $validation = EmailValidation::where('email', 'admin@example.com')->first();
        $this->assertNotNull($validation);
        $this->assertNotNull($validation->verification_code);
    }

    #[Test]
    public function existing_employee_user_multiple_verification_attempts_use_consistent_email_template()
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

        // Set up domain restrictions that would block new users
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => true
        ]);

        // First verification attempt
        $response1 = $this->postJson('/validate-email', [
            'email' => 'employee@blocked.com'
        ]);

        $response1->assertStatus(200);
        $response1->assertJson(['success' => true]);

        // Verify EmployeeVerificationMail was sent
        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email) &&
                   $mail->userRole === 'employee';
        });

        // Verify no ClientVerificationMail was sent
        Mail::assertNotSent(ClientVerificationMail::class);
        Mail::assertNotSent(AdminVerificationMail::class);

        // Clear mail fake to track second attempt
        Mail::fake();

        // Second verification attempt
        $response2 = $this->postJson('/validate-email', [
            'email' => 'employee@blocked.com'
        ]);

        $response2->assertStatus(200);
        $response2->assertJson(['success' => true]);

        // Verify EmployeeVerificationMail was sent again (consistent template)
        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email) &&
                   $mail->userRole === 'employee';
        });

        // Verify no other mail types were sent
        Mail::assertNotSent(ClientVerificationMail::class);
        Mail::assertNotSent(AdminVerificationMail::class);

        // Clear mail fake to track third attempt
        Mail::fake();

        // Third verification attempt
        $response3 = $this->postJson('/validate-email', [
            'email' => 'employee@blocked.com'
        ]);

        $response3->assertStatus(200);
        $response3->assertJson(['success' => true]);

        // Verify EmployeeVerificationMail was sent again (still consistent)
        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email) &&
                   $mail->userRole === 'employee';
        });

        // Verify no other mail types were sent
        Mail::assertNotSent(ClientVerificationMail::class);
        Mail::assertNotSent(AdminVerificationMail::class);
    }

    #[Test]
    public function existing_client_user_multiple_verification_attempts_bypass_all_restrictions_consistently()
    {
        // Create an existing client user
        $client = User::factory()->create([
            'email' => 'client@blocked.com',
            'role' => UserRole::CLIENT,
            'email_verified_at' => null
        ]);

        // Set up both restrictions that would block new users
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // First verification attempt
        $response1 = $this->postJson('/validate-email', [
            'email' => 'client@blocked.com'
        ]);

        $response1->assertStatus(200);
        $response1->assertJson(['success' => true]);

        // Verify ClientVerificationMail was sent
        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email) &&
                   $mail->userRole === 'client';
        });

        // Clear mail fake to track second attempt
        Mail::fake();

        // Second verification attempt (should still bypass all restrictions)
        $response2 = $this->postJson('/validate-email', [
            'email' => 'client@blocked.com'
        ]);

        $response2->assertStatus(200);
        $response2->assertJson(['success' => true]);

        // Verify ClientVerificationMail was sent again with consistent template
        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email) &&
                   $mail->userRole === 'client';
        });

        // Clear mail fake to track third attempt
        Mail::fake();

        // Third verification attempt (should still bypass all restrictions)
        $response3 = $this->postJson('/validate-email', [
            'email' => 'client@blocked.com'
        ]);

        $response3->assertStatus(200);
        $response3->assertJson(['success' => true]);

        // Verify ClientVerificationMail was sent again with consistent template
        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email) &&
                   $mail->userRole === 'client';
        });

        // Verify that the email validation record exists and is updated
        $validation = EmailValidation::where('email', 'client@blocked.com')->first();
        $this->assertNotNull($validation);
        $this->assertNotNull($validation->verification_code);
    }

    #[Test]
    public function multiple_verification_attempts_by_different_existing_users_work_independently()
    {
        // Create multiple existing users
        $admin = User::factory()->create([
            'email' => 'admin@blocked.com',
            'role' => UserRole::ADMIN,
            'email_verified_at' => null
        ]);

        $employee = User::factory()->create([
            'email' => 'employee@blocked.com',
            'role' => UserRole::EMPLOYEE,
            'username' => 'testemployee',
            'email_verified_at' => null
        ]);

        $client = User::factory()->create([
            'email' => 'client@blocked.com',
            'role' => UserRole::CLIENT,
            'email_verified_at' => null
        ]);

        // Set up restrictions that would block new users
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // Admin verification attempt
        $adminResponse = $this->postJson('/validate-email', [
            'email' => 'admin@blocked.com'
        ]);

        $adminResponse->assertStatus(200);
        $adminResponse->assertJson(['success' => true]);

        // Employee verification attempt
        $employeeResponse = $this->postJson('/validate-email', [
            'email' => 'employee@blocked.com'
        ]);

        $employeeResponse->assertStatus(200);
        $employeeResponse->assertJson(['success' => true]);

        // Client verification attempt
        $clientResponse = $this->postJson('/validate-email', [
            'email' => 'client@blocked.com'
        ]);

        $clientResponse->assertStatus(200);
        $clientResponse->assertJson(['success' => true]);

        // Verify each user got their appropriate email template
        Mail::assertSent(AdminVerificationMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email) && $mail->userRole === 'admin';
        });

        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email) && $mail->userRole === 'employee';
        });

        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email) && $mail->userRole === 'client';
        });

        // Verify each user has their own validation record
        $adminValidation = EmailValidation::where('email', 'admin@blocked.com')->first();
        $employeeValidation = EmailValidation::where('email', 'employee@blocked.com')->first();
        $clientValidation = EmailValidation::where('email', 'client@blocked.com')->first();

        $this->assertNotNull($adminValidation);
        $this->assertNotNull($employeeValidation);
        $this->assertNotNull($clientValidation);

        // Verify each has unique verification codes
        $this->assertNotEquals($adminValidation->verification_code, $employeeValidation->verification_code);
        $this->assertNotEquals($adminValidation->verification_code, $clientValidation->verification_code);
        $this->assertNotEquals($employeeValidation->verification_code, $clientValidation->verification_code);
    }

    // ========================================
    // 8.3 Test mixed scenarios with domain rules
    // ========================================

    #[Test]
    public function existing_user_with_whitelisted_domain_when_public_registration_disabled_bypasses_restrictions()
    {
        // Create an existing user with a whitelisted domain
        $user = User::factory()->create([
            'email' => 'user@allowed.com',
            'role' => UserRole::CLIENT,
            'email_verified_at' => null
        ]);

        // Set up whitelist mode with public registration disabled
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // Existing user should bypass restrictions even though they have a whitelisted domain
        $response = $this->postJson('/validate-email', [
            'email' => 'user@allowed.com'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify ClientVerificationMail was sent
        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) &&
                   $mail->userRole === 'client';
        });

        // Verify that a new user with the same domain would be blocked
        $newUserResponse = $this->postJson('/validate-email', [
            'email' => 'newuser@allowed.com'
        ]);

        $newUserResponse->assertStatus(422);
        $newUserResponse->assertJsonStructure([
            'success',
            'message'
        ]);
        $this->assertFalse($newUserResponse->json('success'));

        // Verify no additional mail was sent for the blocked new user
        Mail::assertSent(ClientVerificationMail::class, 1);
    }

    #[Test]
    public function existing_user_with_blacklisted_domain_in_blacklist_mode_bypasses_restrictions()
    {
        // Create an existing user with a domain that would be blacklisted
        $user = User::factory()->create([
            'email' => 'user@blocked.com',
            'role' => UserRole::EMPLOYEE,
            'username' => 'testemployee',
            'email_verified_at' => null
        ]);

        // Set up blacklist mode that blocks the user's domain
        DomainAccessRule::create([
            'mode' => 'blacklist',
            'rules' => ['blocked.com'],
            'allow_public_registration' => true
        ]);

        // Existing user should bypass restrictions even though their domain is blacklisted
        $response = $this->postJson('/validate-email', [
            'email' => 'user@blocked.com'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify EmployeeVerificationMail was sent
        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) &&
                   $mail->userRole === 'employee';
        });

        // Verify that a new user with the same blacklisted domain would be blocked
        $newUserResponse = $this->postJson('/validate-email', [
            'email' => 'newuser@blocked.com'
        ]);

        $newUserResponse->assertStatus(422);
        $newUserResponse->assertJsonStructure([
            'success',
            'message'
        ]);
        $this->assertFalse($newUserResponse->json('success'));

        // Verify no additional mail was sent for the blocked new user
        Mail::assertSent(EmployeeVerificationMail::class, 1);
    }

    #[Test]
    public function existing_users_always_bypass_regardless_of_domain_rule_configuration()
    {
        // Create existing users with various domain patterns
        $adminAllowed = User::factory()->create([
            'email' => 'admin@allowed.com',
            'role' => UserRole::ADMIN,
            'email_verified_at' => null
        ]);

        $employeeBlocked = User::factory()->create([
            'email' => 'employee@blocked.com',
            'role' => UserRole::EMPLOYEE,
            'username' => 'testemployee',
            'email_verified_at' => null
        ]);

        $clientRandom = User::factory()->create([
            'email' => 'client@random.com',
            'role' => UserRole::CLIENT,
            'email_verified_at' => null
        ]);

        // Test with whitelist mode + public registration disabled
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // All existing users should bypass restrictions
        $adminResponse = $this->postJson('/validate-email', [
            'email' => 'admin@allowed.com'
        ]);
        $adminResponse->assertStatus(200);
        $adminResponse->assertJson(['success' => true]);

        $employeeResponse = $this->postJson('/validate-email', [
            'email' => 'employee@blocked.com'
        ]);
        $employeeResponse->assertStatus(200);
        $employeeResponse->assertJson(['success' => true]);

        $clientResponse = $this->postJson('/validate-email', [
            'email' => 'client@random.com'
        ]);
        $clientResponse->assertStatus(200);
        $clientResponse->assertJson(['success' => true]);

        // Verify appropriate email templates were sent
        Mail::assertSent(AdminVerificationMail::class, function ($mail) use ($adminAllowed) {
            return $mail->hasTo($adminAllowed->email) && $mail->userRole === 'admin';
        });

        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($employeeBlocked) {
            return $mail->hasTo($employeeBlocked->email) && $mail->userRole === 'employee';
        });

        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($clientRandom) {
            return $mail->hasTo($clientRandom->email) && $mail->userRole === 'client';
        });

        // Verify new users would still be blocked appropriately
        $newUserAllowedResponse = $this->postJson('/validate-email', [
            'email' => 'newuser@allowed.com'
        ]);
        $newUserAllowedResponse->assertStatus(422); // Blocked by public registration disabled

        $newUserBlockedResponse = $this->postJson('/validate-email', [
            'email' => 'newuser@blocked.com'
        ]);
        $newUserBlockedResponse->assertStatus(422); // Blocked by domain + public registration

        $newUserRandomResponse = $this->postJson('/validate-email', [
            'email' => 'newuser@random.com'
        ]);
        $newUserRandomResponse->assertStatus(422); // Blocked by domain + public registration
    }

    #[Test]
    public function existing_users_bypass_complex_domain_rule_configurations()
    {
        // Create existing users
        $adminUser = User::factory()->create([
            'email' => 'admin@corporate.com',
            'role' => UserRole::ADMIN,
            'email_verified_at' => null
        ]);

        $employeeUser = User::factory()->create([
            'email' => 'employee@freelance.com',
            'role' => UserRole::EMPLOYEE,
            'username' => 'testemployee',
            'email_verified_at' => null
        ]);

        $clientUser = User::factory()->create([
            'email' => 'client@competitor.com',
            'role' => UserRole::CLIENT,
            'email_verified_at' => null
        ]);

        // Test scenario 1: Strict whitelist with public registration disabled
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['corporate.com', 'trusted.com'],
            'allow_public_registration' => false
        ]);

        // All existing users should bypass, regardless of whether their domain is whitelisted
        $adminResponse = $this->postJson('/validate-email', [
            'email' => 'admin@corporate.com' // This domain IS whitelisted
        ]);
        $adminResponse->assertStatus(200);

        $employeeResponse = $this->postJson('/validate-email', [
            'email' => 'employee@freelance.com' // This domain is NOT whitelisted
        ]);
        $employeeResponse->assertStatus(200);

        $clientResponse = $this->postJson('/validate-email', [
            'email' => 'client@competitor.com' // This domain is NOT whitelisted
        ]);
        $clientResponse->assertStatus(200);

        // Clear mail fake and update to blacklist mode
        Mail::fake();
        DomainAccessRule::query()->delete();
        DomainAccessRule::create([
            'mode' => 'blacklist',
            'rules' => ['competitor.com', 'spam.com'],
            'allow_public_registration' => true
        ]);

        // Test scenario 2: Blacklist mode with public registration enabled
        // All existing users should still bypass, regardless of blacklist status
        $adminResponse2 = $this->postJson('/validate-email', [
            'email' => 'admin@corporate.com' // This domain is NOT blacklisted
        ]);
        $adminResponse2->assertStatus(200);

        $employeeResponse2 = $this->postJson('/validate-email', [
            'email' => 'employee@freelance.com' // This domain is NOT blacklisted
        ]);
        $employeeResponse2->assertStatus(200);

        $clientResponse2 = $this->postJson('/validate-email', [
            'email' => 'client@competitor.com' // This domain IS blacklisted
        ]);
        $clientResponse2->assertStatus(200);

        // Verify appropriate email templates were sent in both scenarios
        Mail::assertSent(AdminVerificationMail::class, function ($mail) use ($adminUser) {
            return $mail->hasTo($adminUser->email) && $mail->userRole === 'admin';
        });

        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($employeeUser) {
            return $mail->hasTo($employeeUser->email) && $mail->userRole === 'employee';
        });

        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($clientUser) {
            return $mail->hasTo($clientUser->email) && $mail->userRole === 'client';
        });
    }

    #[Test]
    public function existing_users_bypass_when_no_domain_rules_exist()
    {
        // Create existing users
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN,
            'email_verified_at' => null
        ]);

        $employee = User::factory()->create([
            'email' => 'employee@example.com',
            'role' => UserRole::EMPLOYEE,
            'username' => 'testemployee',
            'email_verified_at' => null
        ]);

        // No domain rules exist (default open configuration)
        $this->assertDatabaseEmpty('domain_access_rules');

        // All existing users should work normally
        $adminResponse = $this->postJson('/validate-email', [
            'email' => 'admin@example.com'
        ]);
        $adminResponse->assertStatus(200);
        $adminResponse->assertJson(['success' => true]);

        $employeeResponse = $this->postJson('/validate-email', [
            'email' => 'employee@example.com'
        ]);
        $employeeResponse->assertStatus(200);
        $employeeResponse->assertJson(['success' => true]);

        // New users should also work (no restrictions)
        $newUserResponse = $this->postJson('/validate-email', [
            'email' => 'newuser@example.com'
        ]);
        $newUserResponse->assertStatus(200);
        $newUserResponse->assertJson(['success' => true]);

        // Verify appropriate email templates were sent
        Mail::assertSent(AdminVerificationMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email) && $mail->userRole === 'admin';
        });

        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email) && $mail->userRole === 'employee';
        });

        // New user gets client template (default for new users)
        Mail::assertSent(ClientVerificationMail::class, function ($mail) {
            return $mail->hasTo('newuser@example.com') && $mail->userRole === 'client';
        });
    }
}