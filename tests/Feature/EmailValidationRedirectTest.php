<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\EmailValidation;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailValidationRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_email_validation_from_employee_page_redirects_back_to_employee_page()
    {
        // Create an employee
        $employee = User::factory()->create([
            'email' => 'john@company.com',
            'role' => UserRole::EMPLOYEE,
        ]);

        // Submit email validation from employee-specific page
        $response = $this->post('/validate-email', [
            'email' => 'client1@example.com',
            'intended_url' => 'https://upload-drive-in.ddev.site/upload/john'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify intended URL is stored in session
        $this->assertEquals('https://upload-drive-in.ddev.site/upload/john', session('intended_url'));

        // Get the verification code from the database (created by the controller)
        $validation = EmailValidation::where('email', 'client1@example.com')->first();
        $this->assertNotNull($validation);

        // Verify email and check redirect
        $verifyResponse = $this->get("/verify-email/{$validation->verification_code}/client1@example.com");
        
        $verifyResponse->assertRedirect('https://upload-drive-in.ddev.site/upload/john');
        $verifyResponse->assertSessionHas('success');
    }

    public function test_email_validation_from_admin_page_redirects_back_to_admin_page()
    {
        // Create an admin
        $admin = User::factory()->create([
            'email' => 'admin@company.com',
            'role' => UserRole::ADMIN,
        ]);

        // Submit email validation from admin-specific page
        $response = $this->post('/validate-email', [
            'email' => 'client2@example.com',
            'intended_url' => 'https://upload-drive-in.ddev.site/upload/admin'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify intended URL is stored in session
        $this->assertEquals('https://upload-drive-in.ddev.site/upload/admin', session('intended_url'));

        // Get the verification code from the database (created by the controller)
        $validation = EmailValidation::where('email', 'client2@example.com')->first();
        $this->assertNotNull($validation);

        // Verify email and check redirect
        $verifyResponse = $this->get("/verify-email/{$validation->verification_code}/client2@example.com");
        
        $verifyResponse->assertRedirect('https://upload-drive-in.ddev.site/upload/admin');
        $verifyResponse->assertSessionHas('success');
    }

    public function test_email_validation_from_generic_page_redirects_to_client_dashboard()
    {
        // Submit email validation from generic page (no intended_url)
        $response = $this->post('/validate-email', [
            'email' => 'client3@example.com'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify no intended URL is stored in session
        $this->assertNull(session('intended_url'));

        // Get the verification code from the database (created by the controller)
        $validation = EmailValidation::where('email', 'client3@example.com')->first();
        $this->assertNotNull($validation);

        // Verify email and check redirect goes to client upload page
        $verifyResponse = $this->get("/verify-email/{$validation->verification_code}/client3@example.com");
        
        $verifyResponse->assertRedirect(route('client.upload-files'));
        $verifyResponse->assertSessionHas('success');
    }

    public function test_existing_employee_user_redirects_to_employee_dashboard_when_no_intended_url()
    {
        // Create an existing employee user
        $employee = User::factory()->create([
            'email' => 'employee@company.com',
            'role' => UserRole::EMPLOYEE,
            'username' => 'employee'
        ]);

        // Submit email validation from generic page
        $response = $this->post('/validate-email', [
            'email' => 'employee@company.com'
        ]);

        $response->assertStatus(200);

        // Get the verification code from the database (created by the controller)
        $validation = EmailValidation::where('email', 'employee@company.com')->first();
        $this->assertNotNull($validation);

        // Verify email and check redirect goes to employee dashboard
        $verifyResponse = $this->get("/verify-email/{$validation->verification_code}/employee@company.com");
        
        $verifyResponse->assertRedirect(route('employee.dashboard', ['username' => 'employee']));
        $verifyResponse->assertSessionHas('success');
    }

    public function test_existing_admin_user_redirects_to_admin_dashboard_when_no_intended_url()
    {
        // Create an existing admin user
        $admin = User::factory()->create([
            'email' => 'admin2@company.com',
            'role' => UserRole::ADMIN,
        ]);

        // Submit email validation from generic page
        $response = $this->post('/validate-email', [
            'email' => 'admin2@company.com'
        ]);

        $response->assertStatus(200);

        // Get the verification code from the database (created by the controller)
        $validation = EmailValidation::where('email', 'admin2@company.com')->first();
        $this->assertNotNull($validation);

        // Verify email and check redirect goes to admin dashboard
        $verifyResponse = $this->get("/verify-email/{$validation->verification_code}/admin2@company.com");
        
        $verifyResponse->assertRedirect(route('admin.dashboard'));
        $verifyResponse->assertSessionHas('success');
    }
}