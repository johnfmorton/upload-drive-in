<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use App\Mail\AdminVerificationMail;
use App\Mail\EmployeeVerificationMail;
use App\Mail\ClientVerificationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_receives_admin_verification_email_when_requesting_new_verification()
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($admin)
            ->post('/email/verification-notification');

        $response->assertRedirect();
        $response->assertSessionHas('status', 'verification-link-sent');

        Mail::assertSent(AdminVerificationMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });
    }

    public function test_employee_user_receives_employee_verification_email_when_requesting_new_verification()
    {
        Mail::fake();

        $employee = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($employee)
            ->post('/email/verification-notification');

        $response->assertRedirect();
        $response->assertSessionHas('status', 'verification-link-sent');

        Mail::assertSent(EmployeeVerificationMail::class, function ($mail) use ($employee) {
            return $mail->hasTo($employee->email);
        });
    }

    public function test_client_user_receives_client_verification_email_when_requesting_new_verification()
    {
        Mail::fake();

        $client = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($client)
            ->post('/email/verification-notification');

        $response->assertRedirect();
        $response->assertSessionHas('status', 'verification-link-sent');

        Mail::assertSent(ClientVerificationMail::class, function ($mail) use ($client) {
            return $mail->hasTo($client->email);
        });
    }

    public function test_already_verified_user_is_redirected_to_dashboard()
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post('/email/verification-notification');

        $response->assertRedirect(route('dashboard'));

        Mail::assertNothingSent();
    }
}