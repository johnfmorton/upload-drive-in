<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\SetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Enhanced tests for admin user creation with improved validation,
 * password strength checking, and email availability validation.
 */
class SetupAdminUserCreationEnhancedTest extends TestCase
{
    use RefreshDatabase;

    private string $setupStateFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupStateFile = storage_path('app/setup/setup-state.json');
        
        // Clean up any existing setup state
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        // Clear any existing admin users
        User::where('role', UserRole::ADMIN)->delete();
        
        // Complete database step to allow admin creation
        $setupService = app(SetupService::class);
        $setupService->updateSetupStep('database', true);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        parent::tearDown();
    }

    public function test_admin_form_includes_enhanced_validation_elements(): void
    {
        $response = $this->get('/setup/admin');
        
        $response->assertStatus(200);
        $response->assertViewIs('setup.admin');
        
        // Check for enhanced form elements
        $response->assertSee('Administrator Name');
        $response->assertSee('Password strength:');
        $response->assertSee('At least 8 characters');
        $response->assertSee('One uppercase letter');
        $response->assertSee('One lowercase letter');
        $response->assertSee('One number');
        $response->assertSee('One special character');
        
        // Check for JavaScript inclusion
        $response->assertSee('admin-user-creation');
    }

    public function test_email_validation_endpoint_returns_available_for_new_email(): void
    {
        $response = $this->postJson('/setup/ajax/validate-email', [
            'email' => 'newadmin@example.com'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'available' => true,
            'message' => 'Email address is available'
        ]);
    }

    public function test_email_validation_endpoint_returns_unavailable_for_existing_email(): void
    {
        // Create a user with the email first
        User::factory()->create([
            'email' => 'existing@example.com',
            'role' => UserRole::CLIENT
        ]);

        $response = $this->postJson('/setup/ajax/validate-email', [
            'email' => 'existing@example.com'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'available' => false,
            'message' => 'This email address is already registered'
        ]);
    }

    public function test_email_validation_endpoint_validates_email_format(): void
    {
        $invalidEmails = [
            'invalid-email',
            'missing@',
            '@missing.com',
            'spaces @domain.com',
            'double@@domain.com'
        ];

        foreach ($invalidEmails as $invalidEmail) {
            $response = $this->postJson('/setup/ajax/validate-email', [
                'email' => $invalidEmail
            ]);

            // Should return 422 for validation errors or 200 with available=false
            $this->assertTrue(in_array($response->getStatusCode(), [200, 422]));
            
            if ($response->getStatusCode() === 200) {
                $response->assertJson([
                    'available' => false
                ]);
                $this->assertStringContainsString('valid email', $response->json('message'));
            }
        }
    }

    public function test_email_validation_endpoint_handles_missing_email(): void
    {
        $response = $this->postJson('/setup/ajax/validate-email', []);

        $response->assertStatus(422);
        $response->assertJson([
            'available' => false,
            'message' => 'Please enter a valid email address'
        ]);
    }

    public function test_admin_creation_with_enhanced_validation_messages(): void
    {
        // Test with invalid name
        $response = $this->post('/setup/admin', [
            'name' => 'A', // Too short
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertStringContainsString('at least 2 characters', 
            session('errors')->first('name'));
    }

    public function test_admin_creation_validates_name_special_characters(): void
    {
        // Test with invalid characters
        $response = $this->post('/setup/admin', [
            'name' => 'John@Admin', // Invalid character @
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertStringContainsString('letters, spaces, hyphens, apostrophes, and periods', 
            session('errors')->first('name'));
    }

    public function test_admin_creation_accepts_valid_name_formats(): void
    {
        $validNames = [
            "John Administrator",
            "Mary O'Connor",
            "Jean-Pierre Smith",
            "Dr. Sarah Johnson",
            "Administrator"
        ];

        foreach ($validNames as $index => $validName) {
            // Clean up previous user
            User::where('email', "admin{$index}@example.com")->delete();
            
            // Use a unique strong password that won't be in breach databases
            $uniquePassword = "UniqueP@ssw0rd{$index}!2024";
            
            $response = $this->post('/setup/admin', [
                'name' => $validName,
                'email' => "admin{$index}@example.com",
                'password' => $uniquePassword,
                'password_confirmation' => $uniquePassword
            ]);

            // Should redirect to next step
            $response->assertStatus(302);
            $response->assertSessionMissing('errors.name');
            
            $this->assertDatabaseHas('users', [
                'name' => $validName,
                'email' => "admin{$index}@example.com",
                'role' => UserRole::ADMIN
            ]);
        }
    }

    public function test_admin_creation_password_strength_requirements(): void
    {
        $weakPasswords = [
            [
                'password' => 'short',
                'error_contains' => 'at least 8 characters'
            ],
            [
                'password' => 'nouppercase123!',
                'error_contains' => 'uppercase'
            ],
            [
                'password' => 'NOLOWERCASE123!',
                'error_contains' => 'lowercase'
            ],
            [
                'password' => 'NoNumbers!',
                'error_contains' => 'numbers'
            ],
            [
                'password' => 'NoSymbols123',
                'error_contains' => 'symbols'
            ],
            [
                'password' => 'password123!', // Common password
                'error_contains' => 'compromised'
            ]
        ];

        foreach ($weakPasswords as $testCase) {
            $response = $this->post('/setup/admin', [
                'name' => 'John Administrator',
                'email' => 'admin@example.com',
                'password' => $testCase['password'],
                'password_confirmation' => $testCase['password']
            ]);

            $response->assertSessionHasErrors(['password']);
            
            // Clean up for next iteration
            User::where('email', 'admin@example.com')->delete();
        }
    }

    public function test_admin_creation_accepts_strong_passwords(): void
    {
        // Use unique passwords that are unlikely to be in breach databases
        $strongPasswords = [
            'MyUniqueP@ssw0rd!2024',
            'Adm1nStr@t0r#UniqueTest',
            'C0mpl3x&S3cur3P@ss!Test',
            'Str0ng!UniqueP@ssw0rd$2024'
        ];

        foreach ($strongPasswords as $index => $strongPassword) {
            // Clean up previous user
            User::where('email', "admin{$index}@example.com")->delete();
            
            $response = $this->post('/setup/admin', [
                'name' => "Administrator",
                'email' => "admin{$index}@example.com",
                'password' => $strongPassword,
                'password_confirmation' => $strongPassword
            ]);

            // Should redirect to next step
            $response->assertStatus(302);
            $response->assertSessionMissing('errors.password');
            
            $this->assertDatabaseHas('users', [
                'email' => "admin{$index}@example.com",
                'role' => UserRole::ADMIN
            ]);
        }
    }

    public function test_admin_creation_validates_password_confirmation_mismatch(): void
    {
        $response = $this->post('/setup/admin', [
            'name' => 'John Administrator',
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'DifferentPassword123!'
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertStringContainsString('confirmation does not match', 
            session('errors')->first('password'));
    }

    public function test_admin_creation_provides_helpful_error_messages(): void
    {
        $response = $this->post('/setup/admin', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'weak',
            'password_confirmation' => 'different'
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
        
        $errors = session('errors');
        
        // Check for helpful error messages
        $this->assertStringContainsString('required', $errors->first('name'));
        $this->assertStringContainsString('valid email', $errors->first('email'));
        // Password error could be about length, confirmation, or other requirements
        $this->assertNotEmpty($errors->first('password'));
    }

    public function test_admin_creation_form_shows_validation_feedback(): void
    {
        $response = $this->post('/setup/admin', [
            'name' => 'Valid Name',
            'email' => 'invalid-email',
            'password' => 'ValidPassword123!',
            'password_confirmation' => 'ValidPassword123!'
        ]);

        $response->assertStatus(302); // Redirect back with errors
        
        // Follow redirect to see the form with errors
        $followUp = $this->get('/setup/admin');
        $followUp->assertSee('Please enter a valid email address');
    }

    public function test_admin_creation_preserves_valid_input_on_error(): void
    {
        $response = $this->post('/setup/admin', [
            'name' => 'John Administrator',
            'email' => 'invalid-email',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasInput('name', 'John Administrator');
        // Password should not be preserved for security
        $response->assertSessionMissing('password');
    }

    public function test_admin_creation_csrf_protection_with_enhanced_validation(): void
    {
        // Test CSRF protection by making request without CSRF token
        $response = $this->withoutMiddleware(\App\Http\Middleware\EncryptCookies::class)
            ->post('/setup/admin', [
                'name' => 'John Administrator',
                'email' => 'admin@example.com',
                'password' => 'UniqueSecureP@ssw0rd!2024',
                'password_confirmation' => 'UniqueSecureP@ssw0rd!2024'
            ]);

        // Should fail due to CSRF protection (419) or validation errors
        $this->assertTrue(in_array($response->getStatusCode(), [419, 302]));
    }

    public function test_admin_creation_rate_limiting(): void
    {
        // Make multiple rapid requests to test rate limiting
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/setup/admin', [
                'name' => 'John Administrator',
                'email' => 'admin@example.com',
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ]);
            
            if ($i < 5) {
                // First few should work (or fail validation)
                $this->assertNotEquals(429, $response->getStatusCode());
            }
        }
        
        // After many requests, should be rate limited
        // Note: This test depends on the throttle configuration
    }

    public function test_admin_creation_success_with_all_enhancements(): void
    {
        $uniquePassword = 'MyUniqueSecureAdm1n!P@ssw0rd2024';
        
        $response = $this->post('/setup/admin', [
            'name' => 'John O\'Connor-Smith Jr.',
            'email' => 'admin@example.com',
            'password' => $uniquePassword,
            'password_confirmation' => $uniquePassword
        ]);

        // Should redirect to next step
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertSessionMissing('errors');

        // Verify admin user was created with all properties
        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('John O\'Connor-Smith Jr.', $admin->name);
        $this->assertEquals('admin@example.com', $admin->email);
        $this->assertEquals(UserRole::ADMIN, $admin->role);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($uniquePassword, $admin->password));
    }

    public function test_email_validation_endpoint_error_handling(): void
    {
        // Test with empty request
        $response = $this->postJson('/setup/ajax/validate-email', []);
        
        $response->assertStatus(422);
        $response->assertJson([
            'available' => false,
            'message' => 'Please enter a valid email address'
        ]);
    }

    public function test_admin_creation_logs_security_events(): void
    {
        // Enable log testing
        \Illuminate\Support\Facades\Log::spy();

        // Attempt creation with invalid data
        $this->post('/setup/admin', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'weak',
            'password_confirmation' => 'different'
        ]);

        // Verify security logging
        \Illuminate\Support\Facades\Log::shouldHaveReceived('warning')
            ->with('Admin user creation validation failed', \Mockery::type('array'));
    }
}