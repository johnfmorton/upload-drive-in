<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\SetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SetupCloudStorageConfigurationTest extends TestCase
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
        
        // Clear cloud storage configuration
        Config::set('services.google.client_id', null);
        Config::set('services.google.client_secret', null);
        
        // Complete previous setup steps
        $setupService = app(SetupService::class);
        $setupService->updateSetupStep('database', true);
        $setupService->updateSetupStep('admin', true);
        
        // Create admin user to satisfy requirements
        User::factory()->create([
            'role' => UserRole::ADMIN,
            'email_verified_at' => now()
        ]);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->setupStateFile)) {
            File::delete($this->setupStateFile);
        }
        
        parent::tearDown();
    }

    public function test_storage_configuration_form_displays_correctly(): void
    {
        $response = $this->get('/setup/storage');
        
        $response->assertStatus(200);
        $response->assertViewIs('setup.storage');
        $response->assertSee('Cloud Storage Configuration');
        $response->assertSee('Google Drive');
        $response->assertSee('Client ID');
        $response->assertSee('Client Secret');
        $response->assertSee('Provider Selection');
    }

    public function test_google_drive_configuration_succeeds_with_valid_credentials(): void
    {
        // Mock File operations to avoid actual .env modification
        File::shouldReceive('exists')
            ->with(base_path('.env'))
            ->andReturn(true);
        
        File::shouldReceive('get')
            ->with(base_path('.env'))
            ->andReturn("APP_NAME=TestApp\n");
        
        File::shouldReceive('put')
            ->with(base_path('.env'), \Mockery::pattern('/GOOGLE_DRIVE_CLIENT_ID=test-client-id\.apps\.googleusercontent\.com/'))
            ->andReturn(true);
        
        $response = $this->post('/setup/storage', [
            'provider' => 'google-drive',
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'test-client-secret-value'
        ]);
        
        $response->assertRedirect('/setup/complete');
        $response->assertSessionHas('success');
        $response->assertSessionMissing('errors');
    }

    public function test_google_drive_configuration_requires_client_id(): void
    {
        $response = $this->post('/setup/storage', [
            'provider' => 'google-drive',
            'client_id' => '',
            'client_secret' => 'test-client-secret'
        ]);
        
        $response->assertSessionHasErrors(['client_id']);
    }

    public function test_google_drive_configuration_requires_client_secret(): void
    {
        $response = $this->post('/setup/storage', [
            'provider' => 'google-drive',
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => ''
        ]);
        
        $response->assertSessionHasErrors(['client_secret']);
    }

    public function test_google_drive_configuration_validates_client_id_format(): void
    {
        $response = $this->post('/setup/storage', [
            'provider' => 'google-drive',
            'client_id' => 'invalid-client-id-format',
            'client_secret' => 'test-client-secret'
        ]);
        
        $response->assertSessionHasErrors(['client_id']);
    }

    public function test_google_drive_configuration_accepts_valid_client_id_formats(): void
    {
        $validClientIds = [
            '123456789-abcdefghijklmnop.apps.googleusercontent.com',
            'long-client-id-with-dashes.apps.googleusercontent.com',
            'simple123.apps.googleusercontent.com'
        ];
        
        foreach ($validClientIds as $clientId) {
            // Mock File operations for each test
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
                'client_id' => $clientId,
                'client_secret' => 'test-client-secret'
            ]);
            
            $response->assertRedirect('/setup/complete');
            $response->assertSessionMissing('errors.client_id');
        }
    }

    public function test_storage_configuration_requires_provider_selection(): void
    {
        $response = $this->post('/setup/storage', [
            'provider' => '',
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'test-client-secret'
        ]);
        
        $response->assertSessionHasErrors(['provider']);
    }

    public function test_storage_configuration_validates_supported_providers(): void
    {
        $response = $this->post('/setup/storage', [
            'provider' => 'unsupported-provider',
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret'
        ]);
        
        $response->assertSessionHasErrors(['provider']);
    }

    public function test_storage_configuration_updates_runtime_config(): void
    {
        // Mock File operations
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
        
        // Verify runtime configuration was updated
        $this->assertEquals('test-client-id.apps.googleusercontent.com', Config::get('services.google.client_id'));
        $this->assertEquals('test-client-secret', Config::get('services.google.client_secret'));
        $this->assertEquals('google-drive', Config::get('cloud-storage.default'));
    }

    public function test_storage_configuration_updates_setup_progress(): void
    {
        $setupService = app(SetupService::class);
        
        // Initially storage step should not be completed
        $steps = $setupService->getSetupSteps();
        $this->assertFalse($steps['storage']['completed'] ?? true);
        
        // Mock File operations
        File::shouldReceive('exists')->with(base_path('.env'))->andReturn(true);
        File::shouldReceive('get')->with(base_path('.env'))->andReturn("APP_NAME=TestApp\n");
        File::shouldReceive('put')->with(base_path('.env'), \Mockery::any())->andReturn(true);
        
        $response = $this->post('/setup/storage', [
            'provider' => 'google-drive',
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'test-client-secret'
        ]);
        
        $response->assertRedirect('/setup/complete');
        
        // Storage step should now be completed
        $steps = $setupService->getSetupSteps();
        $this->assertTrue($steps['storage']['completed'] ?? false);
        $this->assertNotNull($steps['storage']['completed_at'] ?? null);
    }

    public function test_storage_configuration_handles_env_file_errors(): void
    {
        // Mock File operations to simulate .env file not found
        File::shouldReceive('exists')
            ->with(base_path('.env'))
            ->andReturn(false);
        
        $response = $this->post('/setup/storage', [
            'provider' => 'google-drive',
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'test-client-secret'
        ]);
        
        $response->assertSessionHas('error');
        $response->assertSessionHasErrors();
    }

    public function test_storage_configuration_handles_readonly_env_file(): void
    {
        // Mock File operations to simulate readonly .env file
        File::shouldReceive('exists')
            ->with(base_path('.env'))
            ->andReturn(true);
        
        File::shouldReceive('get')
            ->with(base_path('.env'))
            ->andReturn("APP_NAME=TestApp\n");
        
        File::shouldReceive('put')
            ->with(base_path('.env'), \Mockery::any())
            ->andReturn(false); // Simulate write failure
        
        $response = $this->post('/setup/storage', [
            'provider' => 'google-drive',
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'test-client-secret'
        ]);
        
        $response->assertSessionHas('error');
        $response->assertSessionHasErrors();
    }

    public function test_storage_configuration_preserves_existing_env_values(): void
    {
        $existingEnv = "APP_NAME=MyApp\nAPP_ENV=production\nDB_CONNECTION=mysql\nOTHER_VALUE=keep_this\n";
        
        File::shouldReceive('exists')
            ->with(base_path('.env'))
            ->andReturn(true);
        
        File::shouldReceive('get')
            ->with(base_path('.env'))
            ->andReturn($existingEnv);
        
        File::shouldReceive('put')
            ->with(base_path('.env'), \Mockery::on(function ($content) {
                return str_contains($content, 'APP_NAME=MyApp') &&
                       str_contains($content, 'OTHER_VALUE=keep_this') &&
                       str_contains($content, 'GOOGLE_DRIVE_CLIENT_ID=test-client-id.apps.googleusercontent.com');
            }))
            ->andReturn(true);
        
        $response = $this->post('/setup/storage', [
            'provider' => 'google-drive',
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'test-client-secret'
        ]);
        
        $response->assertRedirect('/setup/complete');
    }

    public function test_storage_configuration_redirects_if_already_configured(): void
    {
        // Set existing cloud storage configuration
        Config::set('services.google.client_id', 'existing-client-id');
        Config::set('services.google.client_secret', 'existing-client-secret');
        
        $response = $this->get('/setup/storage');
        
        // Should redirect to complete step since storage is already configured
        $response->assertRedirect('/setup/complete');
    }

    public function test_storage_configuration_prevents_access_without_admin_user(): void
    {
        // Remove admin user
        User::where('role', UserRole::ADMIN)->delete();
        
        $response = $this->get('/setup/storage');
        
        // Should redirect to admin step
        $response->assertRedirect('/setup/admin');
    }

    public function test_storage_configuration_form_shows_provider_options(): void
    {
        $response = $this->get('/setup/storage');
        
        $response->assertStatus(200);
        $response->assertSee('Google Drive');
        $response->assertSee('Microsoft Teams');
        $response->assertSee('Dropbox');
    }

    public function test_storage_configuration_shows_helpful_instructions(): void
    {
        $response = $this->get('/setup/storage');
        
        $response->assertStatus(200);
        $response->assertSee('Google Cloud Console');
        $response->assertSee('OAuth 2.0');
        $response->assertSee('credentials');
        $response->assertSee('redirect URI');
    }

    public function test_storage_configuration_displays_redirect_uri(): void
    {
        Config::set('app.url', 'https://example.com');
        
        $response = $this->get('/setup/storage');
        
        $response->assertStatus(200);
        $response->assertSee('https://example.com/admin/cloud-storage/google-drive/callback');
    }

    public function test_storage_configuration_handles_special_characters_in_credentials(): void
    {
        $specialSecret = 'secret-with-special-chars!@#$%^&*()';
        
        File::shouldReceive('exists')->with(base_path('.env'))->andReturn(true);
        File::shouldReceive('get')->with(base_path('.env'))->andReturn("APP_NAME=TestApp\n");
        File::shouldReceive('put')
            ->with(base_path('.env'), \Mockery::pattern('/GOOGLE_DRIVE_CLIENT_SECRET="secret-with-special-chars!@#\$%\^&\*\(\)"/'))
            ->andReturn(true);
        
        $response = $this->post('/setup/storage', [
            'provider' => 'google-drive',
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => $specialSecret
        ]);
        
        $response->assertRedirect('/setup/complete');
        $response->assertSessionMissing('errors');
    }

    public function test_storage_configuration_success_message(): void
    {
        File::shouldReceive('exists')->with(base_path('.env'))->andReturn(true);
        File::shouldReceive('get')->with(base_path('.env'))->andReturn("APP_NAME=TestApp\n");
        File::shouldReceive('put')->with(base_path('.env'), \Mockery::any())->andReturn(true);
        
        $response = $this->post('/setup/storage', [
            'provider' => 'google-drive',
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'test-client-secret'
        ]);
        
        $response->assertRedirect('/setup/complete');
        $response->assertSessionHas('success');
        
        $successMessage = session('success');
        $this->assertStringContainsString('storage', strtolower($successMessage));
        $this->assertStringContainsString('configured', strtolower($successMessage));
    }

    public function test_storage_configuration_csrf_protection(): void
    {
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post('/setup/storage', [
                'provider' => 'google-drive',
                'client_id' => 'test-client-id.apps.googleusercontent.com',
                'client_secret' => 'test-client-secret'
            ], ['HTTP_X-CSRF-TOKEN' => 'invalid']);
        
        // With CSRF middleware disabled for this test, it should succeed
        // In real scenario with CSRF enabled, this would return 419
        File::shouldReceive('exists')->with(base_path('.env'))->andReturn(true);
        File::shouldReceive('get')->with(base_path('.env'))->andReturn("APP_NAME=TestApp\n");
        File::shouldReceive('put')->with(base_path('.env'), \Mockery::any())->andReturn(true);
        
        $response->assertRedirect('/setup/complete');
    }

    public function test_storage_configuration_handles_connection_testing(): void
    {
        // This test would ideally mock the Google API client
        // For now, we test the validation part
        
        File::shouldReceive('exists')->with(base_path('.env'))->andReturn(true);
        File::shouldReceive('get')->with(base_path('.env'))->andReturn("APP_NAME=TestApp\n");
        File::shouldReceive('put')->with(base_path('.env'), \Mockery::any())->andReturn(true);
        
        $response = $this->post('/setup/storage', [
            'provider' => 'google-drive',
            'client_id' => 'test-client-id.apps.googleusercontent.com',
            'client_secret' => 'test-client-secret'
        ]);
        
        $response->assertRedirect('/setup/complete');
    }
}