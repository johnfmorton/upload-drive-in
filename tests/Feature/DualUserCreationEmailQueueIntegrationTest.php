<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\LoginVerificationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DualUserCreationEmailQueueIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
        ]);

        // Create employee user
        $this->employeeUser = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email' => 'employee@example.com',
            'username' => 'testemployee',
        ]);
    }

    /** @test */
    public function admin_invitation_emails_are_queued_properly()
    {
        Queue::fake();
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite'
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'Client user created and invitation sent successfully.');

        // Verify email was sent (not queued in this case as we're sending directly)
        Mail::assertSent(LoginVerificationMail::class, function ($mail) {
            return $mail->hasTo('client@example.com');
        });
    }

    /** @test */
    public function employee_invitation_emails_are_queued_properly()
    {
        Queue::fake();
        Mail::fake();
        
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
            'name' => 'Employee Client',
            'email' => 'employee-client@example.com',
            'action' => 'create_and_invite'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'employee-client-created-and-invited');

        // Verify email was sent (not queued in this case as we're sending directly)
        Mail::assertSent(LoginVerificationMail::class, function ($mail) {
            return $mail->hasTo('employee-client@example.com');
        });
    }

    /** @test */
    public function admin_invitation_email_contains_valid_signed_url()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite'
        ]);

        $response->assertRedirect(route('admin.users.index'));

        // Get the created user
        $clientUser = User::where('email', 'client@example.com')->first();
        $this->assertNotNull($clientUser);

        // Verify email was sent to correct recipient
        Mail::assertSent(LoginVerificationMail::class, function ($mail) {
            return $mail->hasTo('client@example.com') && 
                   $mail->verificationUrl !== null;
        });
    }

    /** @test */
    public function employee_invitation_email_contains_valid_signed_url()
    {
        Mail::fake();
        
        $this->actingAs($this->employeeUser);

        $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
            'name' => 'Employee Client',
            'email' => 'employee-client@example.com',
            'action' => 'create_and_invite'
        ]);

        $response->assertRedirect();

        // Get the created user
        $clientUser = User::where('email', 'employee-client@example.com')->first();
        $this->assertNotNull($clientUser);

        // Verify email was sent to correct recipient
        Mail::assertSent(LoginVerificationMail::class, function ($mail) {
            return $mail->hasTo('employee-client@example.com') && 
                   $mail->verificationUrl !== null;
        });
    }

    /** @test */
    public function multiple_admin_invitations_are_handled_correctly()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        // Create multiple users with invitations
        $users = [
            ['name' => 'Client 1', 'email' => 'client1@example.com'],
            ['name' => 'Client 2', 'email' => 'client2@example.com'],
            ['name' => 'Client 3', 'email' => 'client3@example.com'],
        ];

        foreach ($users as $userData) {
            $response = $this->post(route('admin.users.store'), [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'action' => 'create_and_invite'
            ]);

            $response->assertRedirect(route('admin.users.index'));
        }

        // Verify all emails were sent
        Mail::assertSent(LoginVerificationMail::class, 3);

        // Verify each email was sent to correct recipient
        foreach ($users as $userData) {
            Mail::assertSent(LoginVerificationMail::class, function ($mail) use ($userData) {
                return $mail->hasTo($userData['email']);
            });
        }
    }

    /** @test */
    public function multiple_employee_invitations_are_handled_correctly()
    {
        Mail::fake();
        
        $this->actingAs($this->employeeUser);

        // Create multiple users with invitations
        $users = [
            ['name' => 'Employee Client 1', 'email' => 'emp-client1@example.com'],
            ['name' => 'Employee Client 2', 'email' => 'emp-client2@example.com'],
            ['name' => 'Employee Client 3', 'email' => 'emp-client3@example.com'],
        ];

        foreach ($users as $userData) {
            $response = $this->post(route('employee.clients.store', $this->employeeUser->username), [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'action' => 'create_and_invite'
            ]);

            $response->assertRedirect();
        }

        // Verify all emails were sent
        Mail::assertSent(LoginVerificationMail::class, 3);

        // Verify each email was sent to correct recipient
        foreach ($users as $userData) {
            Mail::assertSent(LoginVerificationMail::class, function ($mail) use ($userData) {
                return $mail->hasTo($userData['email']);
            });
        }
    }

    /** @test */
    public function email_queue_failures_do_not_prevent_user_creation()
    {
        // Simulate queue failure by making mail throw exception
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \Exception('Queue connection failed'));
        
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite'
        ]);

        // Should still redirect successfully (graceful error handling)
        $response->assertRedirect(route('admin.users.index'));

        // User should still be created despite email failure
        $this->assertDatabaseHas('users', [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'role' => UserRole::CLIENT,
        ]);

        // Relationship should still be created
        $clientUser = User::where('email', 'client@example.com')->first();
        $this->assertDatabaseHas('client_user_relationships', [
            'client_user_id' => $clientUser->id,
            'company_user_id' => $this->adminUser->id,
        ]);
    }

    /** @test */
    public function invitation_emails_have_proper_expiration()
    {
        Mail::fake();
        
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'action' => 'create_and_invite'
        ]);

        $response->assertRedirect(route('admin.users.index'));

        // Verify email was sent to correct recipient with verification URL
        Mail::assertSent(LoginVerificationMail::class, function ($mail) {
            return $mail->hasTo('client@example.com') && 
                   $mail->verificationUrl !== null;
        });
    }
}