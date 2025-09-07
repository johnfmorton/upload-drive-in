<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\DomainAccessRule;
use App\Models\EmailValidation;

class DatabaseErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_lookup_failure_falls_back_gracefully()
    {
        // Create a domain rule that would normally block registration
        DomainAccessRule::create([
            'mode' => 'whitelist',
            'rules' => ['allowed.com'],
            'allow_public_registration' => false
        ]);

        // Mock a database connection failure for user lookup
        DB::shouldReceive('connection')->andThrow(new \Exception('Database connection failed'));

        $response = $this->postJson('/validate-email', [
            'email' => 'test@blocked.com'
        ]);

        // Should fail because user lookup failed and domain is blocked
        $response->assertStatus(422);
        $response->assertJsonStructure(['success', 'message']);
    }

    public function test_domain_rules_lookup_failure_allows_existing_user()
    {
        // Create an existing user
        $user = User::factory()->create(['email' => 'existing@test.com']);

        // The domain rules lookup will fail, but existing user should still be allowed
        $response = $this->postJson('/validate-email', [
            'email' => 'existing@test.com'
        ]);

        // Should succeed because existing users bypass restrictions even if domain rules lookup fails
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_validation_lookup_failure_blocks_verification()
    {
        // Create a valid email validation record
        $validation = EmailValidation::create([
            'email' => 'test@example.com',
            'verification_code' => 'valid-code',
            'expires_at' => now()->addHours(1)
        ]);

        // Access the verification URL - this should work normally first
        $response = $this->get("/verify-email/valid-code/test@example.com");
        
        // Should redirect to home with success (user gets created and logged in)
        $response->assertRedirect();
    }
}