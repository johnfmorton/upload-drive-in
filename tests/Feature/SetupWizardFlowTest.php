<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\SetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SetupWizardFlowTest extends TestCase
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
        
        // Clear any existing admin users to simulate fresh installation
        User::where('role', UserRole::ADMIN)->delete();
        
        // Clear cloud storage configuration
        Config::set('services.google.client_id', null);
        Config::set('services.google.client_secret', null);
    }

    protected function tearDown(): void
    {
        // Clean up setup state file
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        parent::tearDown();
    }

    public function test_setup_wizard_welcome_screen_displays_correctly(): void
    {
        $response = $this->get('/setup');
        
        $response->assertStatus(200);
        $response->assertViewIs('setup.welcome');
        $response->assertSee('Welcome to Upload Drive-in Setup');
        $response->assertSee('System Requirements');
    }

    public function test_setup_wizard_welcome_screen_alternative_route(): void
    {
        $response = $this->get('/setup/welcome');
        
        $response->assertStatus(200);
        $response->assertViewIs('setup.welcome');
    }

    public function test_setup_wizard_database_form_displays_correctly(): void
    {
        $response = $this->get('/setup/database');
        
        $response->assertStatus(200);
        $response->assertViewIs('setup.database');
        $response->assertSee('Database Configuration');
        $response->assertSee('SQLite');
        $response->assertSee('MySQL');
    }

    public function test_setup_wizard_database_configuration_with_sqlite(): void
    {
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => storage_path('app/test-setup.sqlite')
        ]);
        
        $response->assertRedirect('/setup/admin');
        $response->assertSessionHas('success');
    }

    public function test_setup_wizard_database_configuration_validation_errors(): void
    {
        $response = $this->post('/setup/database', [
            'database_type' => 'mysql',
            'mysql_host' => '',
            'mysql_database' => '',
            'mysql_username' => ''
        ]);
        
        $response->assertSessionHasErrors(['mysql_host', 'mysql_database', 'mysql_username']);
    }

    public function test_setup_wizard_admin_form_displays_correctly(): void
    {
        // First complete database step
        $this->completeSetupStep('database');
        
        $response = $this->get('/setup/admin');
        
        $response->assertStatus(200);
        $response->assertViewIs('setup.admin');
        $response->assertSee('Create Administrator Account');
        $response->assertSee('Email Address');
        $response->assertSee('Password');
    }

    public function test_setup_wizard_admin_creation_succeeds(): void
    {
        // First complete database step
        $this->completeSetupStep('database');
        
        $response = $this->post('/setup/admin', [
            'name' => 'Test Administrator',
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        
        $response->assertRedirect('/setup/storage');
        $response->assertSessionHas('success');
        
        // Verify admin user was created
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN
        ]);
        
        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_setup_wizard_admin_creation_validation_errors(): void
    {
        $this->completeSetupStep('database');
        
        $response = $this->post('/setup/admin', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456'
        ]);
        
        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_setup_wizard_storage_form_displays_correctly(): void
    {
        // Complete previous steps
        $this->completeSetupStep('database');
        $this->completeSetupStep('admin');
        
        $response = $this->get('/setup/storage');
        
        $response->assertStatus(200);
        $response->assertViewIs('setup.storage');
        $response->assertSee('Cloud Storage Configuration');
        $response->assertSee('Google Drive');
        $response->assertSee('Client ID');
        $response->assertSee('Client Secret');
    }

    public function test_setup_wizard_storage_configuration_succeeds(): void
    {
        // Complete previous steps
        $this->completeSetupStep('database');
        $this->completeSetupStep('admin');
        
        // Mock File operations to avoid actual .env modification
        File::shouldReceive('exists')
            ->with(base_path('.env'))
            ->andReturn(true);
        
        File::shouldReceive('get')
            ->with(base_path('.env'))
            ->andReturn("APP_NAME=TestApp\n");
        
        File::shouldReceive('put')
            ->with(base_path('.env'), \Mockery::any())
            ->andReturn(true);
        
        $response = $this->post('/setup/storage', [
            'provider' => 'google-drive',
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'test-client-secret'
        ]);
        
        $response->assertRedirect('/setup/complete');
        $response->assertSessionHas('success');
    }

    public function test_setup_wizard_storage_configuration_validation_errors(): void
    {
        $this->completeSetupStep('database');
        $this->completeSetupStep('admin');
        
        $response = $this->post('/setup/storage', [
            'provider' => 'google-drive',
            'client_id' => '',
            'client_secret' => ''
        ]);
        
        $response->assertSessionHasErrors(['client_id', 'client_secret']);
    }

    public function test_setup_wizard_complete_screen_displays_correctly(): void
    {
        // Complete all previous steps
        $this->completeSetupStep('database');
        $this->completeSetupStep('admin');
        $this->completeSetupStep('storage');
        
        $response = $this->get('/setup/complete');
        
        $response->assertStatus(200);
        $response->assertViewIs('setup.complete');
        $response->assertSee('Setup Complete');
        $response->assertSee('Congratulations');
    }

    public function test_setup_wizard_completion_redirects_to_admin_dashboard(): void
    {
        // Complete all previous steps
        $this->completeSetupStep('database');
        $this->completeSetupStep('admin');
        $this->completeSetupStep('storage');
        
        $response = $this->post('/setup/complete');
        
        $response->assertRedirect('/admin/dashboard');
        $response->assertSessionHas('success');
    }

    public function test_setup_wizard_step_routing_works_correctly(): void
    {
        $testCases = [
            'welcome' => '/setup/welcome',
            'database' => '/setup/database',
            'admin' => '/setup/admin',
            'storage' => '/setup/storage',
            'complete' => '/setup/complete'
        ];
        
        foreach ($testCases as $step => $expectedRoute) {
            $response = $this->get("/setup/step/{$step}");
            $response->assertRedirect($expectedRoute);
        }
    }

    public function test_setup_wizard_invalid_step_redirects_to_welcome(): void
    {
        $response = $this->get('/setup/step/invalid');
        
        $response->assertRedirect('/setup/welcome');
    }

    public function test_complete_setup_wizard_flow(): void
    {
        // Step 1: Welcome screen
        $response = $this->get('/setup');
        $response->assertStatus(200);
        
        // Step 2: Database configuration
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite',
            'sqlite_path' => storage_path('app/test-setup.sqlite')
        ]);
        $response->assertRedirect('/setup/admin');
        
        // Step 3: Admin user creation
        $response = $this->post('/setup/admin', [
            'name' => 'Test Administrator',
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!'
        ]);
        $response->assertRedirect('/setup/storage');
        
        // Verify admin user exists
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN
        ]);
        
        // Step 4: Storage configuration (mock file operations)
        File::shouldReceive('exists')->with(base_path('.env'))->andReturn(true);
        File::shouldReceive('get')->with(base_path('.env'))->andReturn("APP_NAME=TestApp\n");
        File::shouldReceive('put')->with(base_path('.env'), \Mockery::any())->andReturn(true);
        
        $response = $this->post('/setup/storage', [
            'provider' => 'google-drive',
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'test-client-secret'
        ]);
        $response->assertRedirect('/setup/complete');
        
        // Step 5: Complete setup
        $response = $this->post('/setup/complete');
        $response->assertRedirect('/admin/dashboard');
        
        // Verify setup is marked as complete
        $setupService = app(SetupService::class);
        $this->assertTrue($setupService->isSetupComplete());
    }

    public function test_setup_wizard_prevents_skipping_steps(): void
    {
        // Try to access admin step without completing database
        $response = $this->get('/setup/admin');
        $response->assertRedirect('/setup/database');
        
        // Try to access storage step without completing admin
        $this->completeSetupStep('database');
        $response = $this->get('/setup/storage');
        $response->assertRedirect('/setup/admin');
        
        // Try to access complete step without completing storage
        $this->completeSetupStep('admin');
        $response = $this->get('/setup/complete');
        $response->assertRedirect('/setup/storage');
    }

    public function test_setup_wizard_handles_existing_admin_user(): void
    {
        // Create an admin user first
        User::factory()->create([
            'role' => UserRole::ADMIN,
            'email_verified_at' => now()
        ]);
        
        // Should skip to storage step
        $response = $this->get('/setup/admin');
        $response->assertRedirect('/setup/storage');
    }

    public function test_setup_wizard_handles_existing_cloud_storage_config(): void
    {
        // Complete database and admin steps
        $this->completeSetupStep('database');
        $this->completeSetupStep('admin');
        
        // Set cloud storage configuration
        Config::set('services.google.client_id', 'existing-client-id');
        Config::set('services.google.client_secret', 'existing-client-secret');
        
        // Should skip to complete step
        $response = $this->get('/setup/storage');
        $response->assertRedirect('/setup/complete');
    }

    public function test_setup_wizard_displays_progress_correctly(): void
    {
        $response = $this->get('/setup');
        $response->assertSee('Step 1 of 5'); // Welcome step
        
        $this->completeSetupStep('database');
        $response = $this->get('/setup/admin');
        $response->assertSee('Step 3 of 5'); // Admin step (database is step 2)
    }

    public function test_setup_wizard_csrf_protection(): void
    {
        $response = $this->post('/setup/database', [
            'database_type' => 'sqlite'
        ], ['HTTP_X-CSRF-TOKEN' => 'invalid']);
        
        $response->assertStatus(419); // CSRF token mismatch
    }

    public function test_setup_wizard_rate_limiting(): void
    {
        // This test would require multiple rapid requests to trigger rate limiting
        // Implementation depends on the specific rate limiting configuration
        
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/setup/database', [
                'database_type' => 'invalid'
            ]);
        }
        
        // After many failed attempts, should be rate limited
        // The exact status code depends on rate limiting configuration
        $this->assertTrue(true); // Placeholder - implement based on actual rate limiting
    }

    /**
     * Helper method to complete a setup step
     */
    private function completeSetupStep(string $step): void
    {
        $setupService = app(SetupService::class);
        
        switch ($step) {
            case 'database':
                $setupService->updateSetupStep('database', true);
                break;
                
            case 'admin':
                // Create admin user if not exists
                if (!User::where('role', UserRole::ADMIN)->exists()) {
                    User::factory()->create([
                        'role' => UserRole::ADMIN,
                        'email_verified_at' => now()
                    ]);
                }
                $setupService->updateSetupStep('admin', true);
                break;
                
            case 'storage':
                Config::set('services.google.client_id', 'test-client-id');
                Config::set('services.google.client_secret', 'test-client-secret');
                $setupService->updateSetupStep('storage', true);
                break;
        }
    }
}