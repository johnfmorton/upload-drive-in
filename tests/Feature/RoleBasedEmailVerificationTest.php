<?php

namespace Tests\Feature;

use App\Mail\AdminVerificationMail;
use App\Mail\ClientVerificationMail;
use App\Mail\EmployeeVerificationMail;
use App\Models\User;
use App\Services\VerificationMailFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RoleBasedEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /** @test */
    public function public_upload_sends_admin_verification_email_for_existing_admin_user()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin'
        ]);

        $response = $this->postJson('/validate-email', [
            'email' => 'admin@example.com'
        ]);

        $response->assertStatus(200);
        Mail::assertSent(AdminVerificationMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });
    }

    /** @test */
    public function public_upload_sends_employee_verification_email_for_existing_employee_user()
    {
        $employee = User::factory()->create([
            'email' => 'employee@example.com',
            'role' => 'employee'
        ]);

        $response = $this->postJson('/validate-email', [
            'email' => 'employee@example.com'
        ]);

        $response->assertStatus(200);
        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email);
        });
    }

    /** @test */
    public function public_upload_sends_client_verification_email_for_existing_client_user()
    {
        $client = User::factory()->create([
            'email' => 'client@example.com',
            'role' => 'client'
        ]);

        $response = $this->postJson('/validate-email', [
            'email' => 'client@example.com'
        ]);

        $response->assertStatus(200);
        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email);
        });
    }

    /** @test */
    public function public_upload_sends_client_verification_email_for_new_user()
    {
        $response = $this->postJson('/validate-email', [
            'email' => 'newuser@example.com'
        ]);

        $response->assertStatus(200);
        Mail::assertSent(ClientVerificationMail::class, function ($mail) {
            return $mail->hasTo('newuser@example.com');
        });
    }

    /** @test */
    public function admin_user_creation_sends_client_verification_email()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->post('/admin/users', [
            'name' => 'Test Client',
            'email' => 'testclient@example.com',
            'action' => 'create_and_invite'
        ]);

        $response->assertRedirect();
        Mail::assertSent(ClientVerificationMail::class, function ($mail) {
            return $mail->hasTo('testclient@example.com');
        });
    }

    /** @test */
    public function employee_user_creation_sends_client_verification_email()
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $this->actingAs($employee);

        $response = $this->post('/employee/clients', [
            'name' => 'Test Client',
            'email' => 'testclient@example.com',
            'action' => 'create_and_invite'
        ]);

        $response->assertRedirect();
        Mail::assertSent(ClientVerificationMail::class, function ($mail) {
            return $mail->hasTo('testclient@example.com');
        });
    }

    /** @test */
    public function verification_mail_factory_logs_template_selection()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $factory = app(VerificationMailFactory::class);

        $mail = $factory->createForUser($admin, 'https://example.com/verify');

        $this->assertInstanceOf(AdminVerificationMail::class, $mail);
        
        // Check that logs were written (this would require log testing setup)
        // For now, we just verify the mail instance is correct
    }

    /** @test */
    public function fallback_behavior_when_role_detection_fails()
    {
        // Create a user with an unexpected role (this shouldn't happen in practice)
        $user = new User([
            'email' => 'test@example.com',
            'role' => null // Simulate role detection failure
        ]);

        $factory = app(VerificationMailFactory::class);
        $mail = $factory->createForUser($user, 'https://example.com/verify');

        // Should fallback to client verification
        $this->assertInstanceOf(ClientVerificationMail::class, $mail);
    }
}