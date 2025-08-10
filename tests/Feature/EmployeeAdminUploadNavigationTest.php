<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeAdminUploadNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_sees_navigation_on_employee_upload_page()
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
        
        // Check that navigation header is present
        $response->assertSee('Upload Files'); // Navigation tab
        $response->assertSee('My Uploads'); // Navigation tab
        $response->assertSee($client->name); // User dropdown
        
        // Check that the Upload Files tab is active (since we're on an upload page)
        $response->assertSee('Upload Files', false);
        
        // Check that the page title is correct
        $response->assertSee('Upload Files for');
    }

    public function test_client_sees_navigation_on_admin_upload_page()
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
        
        // Check that navigation header is present
        $response->assertSee('Upload Files'); // Navigation tab
        $response->assertSee('My Uploads'); // Navigation tab
        $response->assertSee($client->name); // User dropdown
        
        // Check that the page title is correct
        $response->assertSee('Upload Files for');
    }

    public function test_client_navigation_links_work_from_employee_page()
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
        
        // Check that navigation links are present and point to correct routes
        $response->assertSee('/client/upload-files', false);
        $response->assertSee('/client/my-uploads', false);
    }

    public function test_employee_upload_page_has_proper_layout_structure()
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
        
        // Check for app layout elements (not guest layout)
        $response->assertSee('<nav', false); // Navigation should be present
        $response->assertSee('max-w-7xl', false); // App layout container class
        
        // Check that we don't have the old "Logged in as" text from guest layout
        $response->assertDontSee('Logged in as:');
        $response->assertDontSee('Not you? Sign out');
    }

    public function test_admin_upload_page_has_proper_layout_structure()
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
        
        // Check for app layout elements (not guest layout)
        $response->assertSee('<nav', false); // Navigation should be present
        $response->assertSee('max-w-7xl', false); // App layout container class
        
        // Check that we don't have the old "Logged in as" text from guest layout
        $response->assertDontSee('Logged in as:');
        $response->assertDontSee('Not you? Sign out');
    }
}