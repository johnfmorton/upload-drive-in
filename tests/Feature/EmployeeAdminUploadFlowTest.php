<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmployeeAdminUploadFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_employee_upload_page_shows_email_validation_for_guests()
    {
        // Create an employee
        $employee = User::factory()->create([
            'email' => 'john@company.com',
            'role' => UserRole::EMPLOYEE,
        ]);

        // Visit employee upload page as guest
        $response = $this->get('/upload/john');

        $response->assertStatus(200);
        $response->assertSee('Upload Files for');
        $response->assertSee('Please verify your email address to upload files');
        $response->assertSee('Authentication Required');
        
        // Check that the intended URL is set in the form
        $response->assertSee('name="intended_url"', false);
        $response->assertSee('/upload/john', false);
    }

    public function test_admin_upload_page_shows_email_validation_for_guests()
    {
        // Create an admin
        $admin = User::factory()->create([
            'email' => 'admin@company.com',
            'role' => UserRole::ADMIN,
        ]);

        // Visit admin upload page as guest
        $response = $this->get('/upload/admin');

        $response->assertStatus(200);
        $response->assertSee('Upload Files for');
        $response->assertSee('Please verify your email address to upload files');
        $response->assertSee('Authentication Required');
        
        // Check that the intended URL is set in the form
        $response->assertSee('name="intended_url"', false);
        $response->assertSee('/upload/admin', false);
    }

    public function test_generic_upload_page_does_not_include_intended_url()
    {
        // Visit generic upload page
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Email Address');
        
        // Check that no intended URL is included
        $response->assertDontSee('name="intended_url"', false);
    }

    public function test_nonexistent_user_upload_page_returns_404()
    {
        // Try to visit upload page for non-existent user
        $response = $this->get('/upload/nonexistent');

        $response->assertStatus(404);
    }

    public function test_client_user_upload_page_returns_404()
    {
        // Create a client user
        $client = User::factory()->create([
            'email' => 'client@company.com',
            'role' => UserRole::CLIENT,
        ]);

        // Try to visit upload page for client user
        $response = $this->get('/upload/client');

        $response->assertStatus(404);
    }

    public function test_authenticated_user_can_access_employee_upload_page()
    {
        // Create an employee and a client
        $employee = User::factory()->create([
            'email' => 'john@company.com',
            'role' => UserRole::EMPLOYEE,
        ]);

        $client = User::factory()->create([
            'email' => 'client@example.com',
            'role' => UserRole::CLIENT,
        ]);

        // Visit employee upload page as authenticated client
        $response = $this->actingAs($client)->get('/upload/john');

        $response->assertStatus(200);
        $response->assertSee('Upload Files');
        $response->assertDontSee('Authentication Required');
        $response->assertDontSee('Please verify your email address');
    }

    public function test_authenticated_user_can_access_admin_upload_page()
    {
        // Create an admin and a client
        $admin = User::factory()->create([
            'email' => 'admin@company.com',
            'role' => UserRole::ADMIN,
        ]);

        $client = User::factory()->create([
            'email' => 'client@example.com',
            'role' => UserRole::CLIENT,
        ]);

        // Visit admin upload page as authenticated client
        $response = $this->actingAs($client)->get('/upload/admin');

        $response->assertStatus(200);
        $response->assertSee('Upload Files');
        $response->assertDontSee('Authentication Required');
        $response->assertDontSee('Please verify your email address');
    }
}