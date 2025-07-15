<?php

namespace Tests\Feature;

use App\Models\EmailValidation;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_redirects_to_correct_client_route(): void
    {
        // Create an email validation record
        $email = 'test@example.com';
        $code = 'test-verification-code';
        
        EmailValidation::create([
            'email' => $email,
            'verification_code' => $code,
            'expires_at' => now()->addHours(1),
        ]);

        // Simulate clicking the verification link
        $response = $this->get(route('verify-email', [
            'code' => $code,
            'email' => $email
        ]));

        // Should redirect to client upload-files route
        $response->assertRedirect(route('client.upload-files'));
        $response->assertSessionHas('success', 'Email verified successfully. You can now upload files.');

        // Verify user was created and logged in
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role' => UserRole::CLIENT->value,
        ]);

        $user = User::where('email', $email)->first();
        $this->assertAuthenticatedAs($user);
    }
}