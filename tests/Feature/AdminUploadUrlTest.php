<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUploadUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_has_upload_url()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN,
        ]);

        $uploadUrl = $admin->getUploadUrl();

        $this->assertNotNull($uploadUrl);
        $this->assertStringContainsString('/upload/admin', $uploadUrl);
    }

    public function test_employee_user_has_upload_url()
    {
        $employee = User::factory()->create([
            'email' => 'employee@company.com',
            'role' => UserRole::EMPLOYEE,
        ]);

        $uploadUrl = $employee->getUploadUrl();

        $this->assertNotNull($uploadUrl);
        $this->assertStringContainsString('/upload/employee', $uploadUrl);
    }

    public function test_client_user_does_not_have_upload_url()
    {
        $client = User::factory()->create([
            'email' => 'client@example.com',
            'role' => UserRole::CLIENT,
        ]);

        $uploadUrl = $client->getUploadUrl();

        $this->assertNull($uploadUrl);
    }

    public function test_admin_dashboard_displays_upload_url()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Personal Upload Page');
        $response->assertSee('/upload/admin');
    }

    public function test_public_upload_page_works_for_admin()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->get('/upload/admin');

        $response->assertStatus(200);
    }
}