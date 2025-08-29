<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\LoginVerificationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminUserControllerDualActionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);
    }

    public function test_admin_can_create_user_without_invitation()
    {
        Mail::fake();

        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create',
        ]);

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success', 'Client user created successfully. You can provide them with their login link manually.');

        $this->assertDatabaseHas('users', [
            'email' => 'client@example.com',
            'name' => 'Test Client',
            'role' => UserRole::CLIENT,
        ]);

        Mail::assertNotSent(LoginVerificationMail::class);
    }

    public function test_admin_can_create_user_with_invitation()
    {
        Mail::fake();

        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite',
        ]);

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success', 'Client user created and invitation sent successfully.');

        $this->assertDatabaseHas('users', [
            'email' => 'client@example.com',
            'name' => 'Test Client',
            'role' => UserRole::CLIENT,
        ]);

        Mail::assertSent(LoginVerificationMail::class, function ($mail) {
            return $mail->hasTo('client@example.com');
        });
    }

    public function test_admin_user_creation_requires_action_parameter()
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            // Missing 'action' parameter
        ]);

        $response->assertSessionHasErrors(['action']);
    }

    public function test_admin_user_creation_validates_action_parameter()
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'invalid_action',
        ]);

        $response->assertSessionHasErrors(['action']);
    }

    public function test_admin_user_creation_handles_existing_user()
    {
        Mail::fake();

        // Create an existing user
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'role' => UserRole::CLIENT,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Updated Name',
            'email' => 'existing@example.com',
            'action' => 'create_and_invite',
        ]);

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('success', 'Client user created and invitation sent successfully.');

        // Verify the user still exists and invitation was sent
        $this->assertDatabaseHas('users', [
            'email' => 'existing@example.com',
            'role' => UserRole::CLIENT,
        ]);

        Mail::assertSent(LoginVerificationMail::class);
    }
}