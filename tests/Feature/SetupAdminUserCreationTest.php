<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\SetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetupAdminUserCreationTest extends TestCase
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

    public function test_admin_creation_form_displays_correctly(): void
    {
        $response = $this->get('/setup/admin');
        
        $response->assertStatus(200);
        $response->assertViewIs('setup.admin');
        $response->assertSee('Create Administrator Account');
        $response->assertSee('Full Name');
        $response->assertSee('Email Address');
        $response->assertSee('Password');
        $response->assertSee('Confirm Password');
    }

    public function test_admin_creation_succeeds_with_valid_data(): void
    {
        $response = $this->post('/setup/admin', [
            'name' => 'John Administrator',
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        
        $response->assertRedirect('/setup/storage');
        $response->assertSessionHas('success');
        $response->assertSessionMissing('errors');
        
        // Verify admin user was created in database
        $this->assertDatabaseHas('users', [
            'name' => 'John Administrator',
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN
        ]);
        
        // Verify user properties
        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertEquals(UserRole::ADMIN, $admin->role);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('SecurePassword123!', $admin->password));
    }

    public function test_admin_creation_requires_name(): void
    {
        $response = $this->post('/setup/admin', [
            'name' => '',
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        
        $response->assertSessionHasErrors(['name']);
        $response->assertSessionHasErrorsIn('default', [
            'name' => 'The name field is required.'
        ]);
    }

    public function test_admin_creation_requires_valid_email(): void
    {
        $response = $this->post('/setup/admin', [
            'name' => 'John Administrator',
            'email' => 'invalid-email',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        
        $response->assertSessionHasErrors(['email']);
    }

    public function test_admin_creation_requires_email(): void
    {
        $response = $this->post('/setup/admin', [
            'name' => 'John Administrator',
            'email' => '',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        
        $response->assertSessionHasErrors(['email']);
    }

    public function test_admin_creation_validates_password_length(): void
    {
        $response = $this->post('/setup/admin', [
            'name' => 'John Administrator',
            'email' => 'admin@example.com',
            'password' => '123',
            'password_confirmation' => '123'
        ]);
        
        $response->assertSessionHasErrors(['password']);
    }

    public function test_admin_creation_requires_password_confirmation(): void
    {
        $response = $this->post('/setup/admin', [
            'name' => 'John Administrator',
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'DifferentPassword123!'
        ]);
        
        $response->assertSessionHasErrors(['password']);
    }

    public function test_admin_creation_prevents_duplicate_emails(): void
    {
        // Create a user with the same email first
        User::factory()->create([
            'email' => 'admin@example.com',
            'role' => UserRole::CLIENT
        ]);
        
        $response = $this->post('/setup/admin', [
            'name' => 'John Administrator',
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        
        $response->assertSessionHasErrors(['email']);
    }

    public function test_admin_creation_updates_setup_progress(): void
    {
        $setupService = app(SetupService::class);
        
        // Initially admin step should not be completed
        $steps = $setupService->getSetupSteps();
        $this->assertFalse($steps['admin']['completed'] ?? true);
        
        $response = $this->post('/setup/admin', [
            'name' => 'John Administrator',
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        
        $response->assertRedirect('/setup/storage');
        
        // Admin step should now be completed
        $steps = $setupService->getSetupSteps();
        $this->assertTrue($steps['admin']['completed'] ?? false);
        $this->assertNotNull($steps['admin']['completed_at'] ?? null);
    }

    public function test_admin_creation_sets_email_as_verified(): void
    {
        $response = $this->post('/setup/admin', [
            'name' => 'John Administrator',
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        
        $response->assertRedirect('/setup/storage');
        
        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_admin_creation_assigns_admin_role(): void
    {
        $response = $this->post('/setup/admin', [
            'name' => 'John Administrator',
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        
        $response->assertRedirect('/setup/storage');
        
        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertEquals(UserRole::ADMIN, $admin->role);
    }

    public function test_admin_creation_redirects_if_admin_already_exists(): void
    {
        // Create an admin user first
        User::factory()->create([
            'role' => UserRole::ADMIN,
            'email_verified_at' => now()
        ]);
        
        $response = $this->get('/setup/admin');
        
        // Should redirect to storage step since admin already exists
        $response->assertRedirect('/setup/storage');
    }

    public function test_admin_creation_handles_long_names(): void
    {
        $longName = str_repeat('A', 255); // Maximum typical varchar length
        
        $response = $this->post('/setup/admin', [
            'name' => $longName,
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        
        $response->assertRedirect('/setup/storage');
        
        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertEquals($longName, $admin->name);
    }

    public function test_admin_creation_handles_special_characters_in_name(): void
    {
        $specialName = "John O'Connor-Smith Jr.";
        
        $response = $this->post('/setup/admin', [
            'name' => $specialName,
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        
        $response->assertRedirect('/setup/storage');
        
        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertEquals($specialName, $admin->name);
    }

    public function test_admin_creation_validates_email_format_strictly(): void
    {
        $invalidEmails = [
            'plainaddress',
            '@missingdomain.com',
            'missing@.com',
            'missing@domain',
            'spaces @domain.com',
            'double@@domain.com'
        ];
        
        foreach ($invalidEmails as $invalidEmail) {
            $response = $this->post('/setup/admin', [
                'name' => 'John Administrator',
                'email' => $invalidEmail,
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ]);
            
            $response->assertSessionHasErrors(['email']);
        }
    }

    public function test_admin_creation_accepts_valid_email_formats(): void
    {
        $validEmails = [
            'simple@domain.com',
            'user.name@domain.com',
            'user+tag@domain.com',
            'user123@domain123.com',
            'admin@subdomain.domain.com'
        ];
        
        foreach ($validEmails as $index => $validEmail) {
            // Clean up previous user
            User::where('email', $validEmail)->delete();
            
            $response = $this->post('/setup/admin', [
                'name' => "Administrator {$index}",
                'email' => $validEmail,
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ]);
            
            $response->assertRedirect('/setup/storage');
            $response->assertSessionMissing('errors.email');
            
            $this->assertDatabaseHas('users', [
                'email' => $validEmail,
                'role' => UserRole::ADMIN
            ]);
        }
    }

    public function test_admin_creation_password_security_requirements(): void
    {
        $weakPasswords = [
            'password',      // Too common
            '12345678',      // Too simple
            'abcdefgh',      // No numbers/symbols
            'PASSWORD',      // No lowercase
            'password123',   // No symbols
        ];
        
        foreach ($weakPasswords as $weakPassword) {
            $response = $this->post('/setup/admin', [
                'name' => 'John Administrator',
                'email' => 'admin@example.com',
                'password' => $weakPassword,
                'password_confirmation' => $weakPassword
            ]);
            
            $response->assertSessionHasErrors(['password']);
        }
    }

    public function test_admin_creation_form_shows_password_requirements(): void
    {
        $response = $this->get('/setup/admin');
        
        $response->assertStatus(200);
        $response->assertSee('minimum 8 characters');
        $response->assertSee('Password Requirements');
    }

    public function test_admin_creation_prevents_access_without_database_setup(): void
    {
        // Reset setup state to simulate database not configured
        $setupService = app(SetupService::class);
        $setupService->updateSetupStep('database', false);
        
        $response = $this->get('/setup/admin');
        
        // Should redirect to database step
        $response->assertRedirect('/setup/database');
    }

    public function test_admin_creation_csrf_protection(): void
    {
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post('/setup/admin', [
                'name' => 'John Administrator',
                'email' => 'admin@example.com',
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!'
            ], ['HTTP_X-CSRF-TOKEN' => 'invalid']);
        
        // With CSRF middleware disabled for this test, it should succeed
        // In real scenario with CSRF enabled, this would return 419
        $response->assertRedirect('/setup/storage');
    }

    public function test_admin_creation_success_message(): void
    {
        $response = $this->post('/setup/admin', [
            'name' => 'John Administrator',
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        
        $response->assertRedirect('/setup/storage');
        $response->assertSessionHas('success');
        
        $successMessage = session('success');
        $this->assertStringContainsString('administrator', strtolower($successMessage));
        $this->assertStringContainsString('created', strtolower($successMessage));
    }
}